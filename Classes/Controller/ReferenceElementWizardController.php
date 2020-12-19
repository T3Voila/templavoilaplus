<?php

namespace Tvp\TemplaVoilaPlus\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Reference elements wizard,
 * References all unused elements in a treebranch to a specific point in the TV-DS
 */
class ReferenceElementWizardController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule
{

    /**
     * @var array
     */
    protected $modSharedTSconfig = array();

    /**
     * @var array
     */
    protected $allAvailableLanguages = array();

    /**
     * @var \Tvp\TemplaVoilaPlus\Service\ApiService
     */
    protected $templavoilaAPIObj;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Returns the menu array
     *
     * @return array
     */
    public function modMenu()
    {
        return array(
            'depth' => array(
                0 => $this->getLanguageService()->sL('LLL:EXT:' . TemplaVoilaUtility::getCoreLangPath() . 'locallang_core.xlf:labels.depth_0'),
                1 => $this->getLanguageService()->sL('LLL:EXT:' . TemplaVoilaUtility::getCoreLangPath() . 'locallang_core.xlf:labels.depth_1'),
                2 => $this->getLanguageService()->sL('LLL:EXT:' . TemplaVoilaUtility::getCoreLangPath() . 'locallang_core.xlf:labels.depth_2'),
                3 => $this->getLanguageService()->sL('LLL:EXT:' . TemplaVoilaUtility::getCoreLangPath() . 'locallang_core.xlf:labels.depth_3'),
                999 => $this->getLanguageService()->sL('LLL:EXT:' . TemplaVoilaUtility::getCoreLangPath() . 'locallang_core.xlf:labels.depth_infi'),
            )
        );
    }

    /**
     * Main function
     *
     * @return string Output HTML for the module
     */
    public function main()
    {
        $message = new FlashMessage(
            'Please use this feature with caution. The result may be not as wished! Please backup your TYPO3 Installation before running that wizard.',
            'Caution',
            FlashMessage::WARNING
        );
        $this->getFlashMessageQueue()->enqueue($message);

        $this->moduleTemplate = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\ModuleTemplate::class);
        $this->iconFactory = $this->moduleTemplate->getIconFactory();

        // Adding classic jumpToUrl function, needed for the function menu.
        // Also, the id in the parent frameset is configured.
        $this->getPageRenderer()->addJsInlineCode('templavoilaplus_function', '
            function jumpToUrl(URL)    { //
                document.location = URL;
                return false;
            }
        ');

        $this->modSharedTSconfig = BackendUtility::getModTSconfig($this->pObj->id, 'mod.SHARED');
        $this->allAvailableLanguages = TemplaVoilaUtility::getAvailableLanguages(0, true, true, $this->modSharedTSconfig);

        $this->templavoilaAPIObj = GeneralUtility::makeInstance(\Tvp\TemplaVoilaPlus\Service\ApiService::class);

        // Showing the tree:
        // Initialize starting point of page tree:
        $treeStartingPoint = (int)$this->pObj->id;
        $treeStartingRecord = BackendUtility::getRecord('pages', $treeStartingPoint);
        $depth = $this->pObj->MOD_SETTINGS['depth'];

        // Initialize tree object:
        /** @var \TYPO3\CMS\Backend\Tree\View\PageTreeView $tree */
        $tree = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\View\PageTreeView::class);
        $tree->init('AND ' . $this->getBackendUser()->getPagePermsClause(1));

        // Creating top icon; the current page
        $HTML = $this->iconFactory->getIconForRecord('pages', $treeStartingRecord, Icon::SIZE_SMALL)->render();
        $tree->tree[] = array(
            'row' => $treeStartingRecord,
            'HTML' => $HTML
        );

        // Create the tree from starting point:
        if ($depth > 0) {
            $tree->getTree($treeStartingPoint, $depth, '');
        }

        // Set CSS styles specific for this document:
        $this->pObj->content = str_replace('/*###POSTCSSMARKER###*/', '
			TABLE.c-list TR TD { white-space: nowrap; vertical-align: top; }
		', $this->pObj->content);

        // Process commands:
        if (GeneralUtility::_GP('createReferencesForPage')) {
            $this->createReferencesForPage(GeneralUtility::_GP('createReferencesForPage'));
        }
        if (GeneralUtility::_GP('createReferencesForTree')) {
            $this->createReferencesForTree($tree);
        }

        // Traverse tree:
        $output = '';
        $counter = 0;
        foreach ($tree->tree as $row) {
            $unreferencedElementRecordsArr = $this->getUnreferencedElementsRecords($row['row']['uid']);

            if (count($unreferencedElementRecordsArr)) {
                $createReferencesLink = '<a href="' . $this->getBaseUrl(['id' => (int)$this->pObj->id, 'createReferencesForPage' => $row['row']['uid']]) . '">Reference elements</a>';
            } else {
                $createReferencesLink = '';
            }

            $rowTitle = $row['HTML'] . BackendUtility::getRecordTitle('pages', $row['row'], true);
            $cellAttrib = ($row['row']['_CSSCLASS'] ? ' class="' . $row['row']['_CSSCLASS'] . '"' : '');

            $tCells = array();
            $tCells[] = '<td nowrap="nowrap"' . $cellAttrib . '>' . $rowTitle . '</td>';
            $tCells[] = '<td>' . count($unreferencedElementRecordsArr) . '</td>';
            $tCells[] = '<td nowrap="nowrap">' . $createReferencesLink . '</td>';

            $output .= '
				<tr class="bgColor' . ($counter % 2 ? '-20' : '-10') . '">
					' . implode('
					', $tCells) . '
				</tr>';

            $counter++;
        }

        // Create header:
        $tCells = array();
        $tCells[] = '<td>Page:</td>';
        $tCells[] = '<td>No. of unreferenced elements:</td>';
        $tCells[] = '<td>&nbsp;</td>';

        // Depth selector:
        $depthSelectorBox = BackendUtility::getFuncMenu(
            $this->pObj->id,
            'SET[depth]',
            $this->pObj->MOD_SETTINGS['depth'],
            $this->pObj->MOD_MENU['depth'],
            'index.php'
        );

        return '
			<br />
			' . $depthSelectorBox . '
			<a href="' . $this->getBaseUrl(['id' => (int)$this->pObj->id, 'createReferencesForTree' => 1]) . '">Reference elements for whole tree</a><br />
			<br />
			<table border="0" cellspacing="1" cellpadding="0" class="lrPadding c-list">
				<tr class="bgColor5 tableheader">
					' . implode('
					', $tCells) . '
				</tr>' .
            $output . '
			</table>
		';
    }

    /**
     * References all unreferenced elements in the given page tree
     *
     * @param \TYPO3\CMS\Backend\Tree\View\PageTreeView $tree Page tree array
     *
     * @return void
     */
    protected function createReferencesForTree($tree)
    {
        foreach ($tree->tree as $row) {
            $this->createReferencesForPage($row['row']['uid']);
        }
    }

    /**
     * References all unreferenced elements with the specified
     * parent id (page uid)
     *
     * @param integer $pageUid Parent id of the elements to reference
     *
     * @return void
     */
    protected function createReferencesForPage($pageUid)
    {
        $unreferencedElementRecordsArr = $this->getUnreferencedElementsRecords($pageUid);
        $langField = $GLOBALS['TCA']['tt_content']['ctrl']['languageField'];
        foreach ($unreferencedElementRecordsArr as $elementUid => $elementRecord) {
            $lDef = array();
            $vDef = array();
            if ($langField && $elementRecord[$langField]) {
                $pageRec = BackendUtility::getRecordWSOL('pages', $pageUid);
                $xml = TemplaVoilaUtility::getFlexFormDS(
                    $GLOBALS['TCA']['pages']['columns']['tx_templavoilaplus_flex']['config'],
                    $pageRec,
                    'pages',
                    'tx_templavoilaplus_ds'
                );
                $langChildren = (int)$xml['meta']['langChildren'];
                $langDisable = (int)$xml['meta']['langDisable'];
                if ($elementRecord[$langField] == -1) {
                    $translatedLanguagesArr = TemplaVoilaUtility::getAvailableLanguages($pageUid, true, false, $this->modSharedTSconfig);
                    foreach ($translatedLanguagesArr as $lUid => $lArr) {
                        if ($lUid >= 0) {
                            $lDef[] = $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l' . $lArr['ISOcode']);
                            $vDef[] = $langDisable ? 'vDEF' : ($langChildren ? 'v' . $lArr['ISOcode'] : 'vDEF');
                        }
                    }
                } elseif ($rLang = $this->allAvailableLanguages[$elementRecord[$langField]]) {
                    $lDef[] = $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l' . $rLang['ISOcode']);
                    $vDef[] = $langDisable ? 'vDEF' : ($langChildren ? 'v' . $rLang['ISOcode'] : 'vDEF');
                } else {
                    $lDef[] = 'lDEF';
                    $vDef[] = 'vDEF';
                }
            } else {
                $lDef[] = 'lDEF';
                $vDef[] = 'vDEF';
            }
            $contentAreaFieldName = $this->templavoilaAPIObj->ds_getFieldNameByColumnPosition($pageUid, $elementRecord['colPos']);
            if ($contentAreaFieldName !== false) {
                foreach ($lDef as $iKey => $lKey) {
                    $vKey = $vDef[$iKey];
                    $destinationPointer = array(
                        'table' => 'pages',
                        'uid' => $pageUid,
                        'sheet' => 'sDEF',
                        'sLang' => $lKey,
                        'field' => $contentAreaFieldName,
                        'vLang' => $vKey,
                        'position' => -1
                    );

                    $this->templavoilaAPIObj->referenceElementByUid($elementUid, $destinationPointer);
                }
            }
        }
    }

    /**
     * Returns an array of tt_content records which are not referenced on
     * the page with the given uid (= parent id).
     *
     * @param integer $pid Parent id of the content elements (= uid of the page)
     *
     * @return array Array of tt_content records with the following fields: uid, header, bodytext, sys_language_uid and colpos
     */
    protected function getUnreferencedElementsRecords($pid)
    {
        $elementRecordsArr = array();
        $dummyArr = array();
        $referencedElementsArr = $this->templavoilaAPIObj->flexform_getListOfSubElementUidsRecursively('pages', $pid, $dummyArr);

        $res = $this->getDatabaseConnection()->exec_SELECTquery(
            'uid, header, bodytext, sys_language_uid, colPos',
            'tt_content',
            'pid=' . (int)$pid .
            (count($referencedElementsArr) ? ' AND uid NOT IN (' . implode(',', $referencedElementsArr) . ')' : '') .
            ' AND t3ver_wsid=' . (int)$this->getBackendUser()->workspace .
            ' AND l18n_parent=0' .
            BackendUtility::deleteClause('tt_content') .
            BackendUtility::versioningPlaceholderClause('tt_content'),
            '',
            'sorting'
        );

        if ($res) {
            while (($elementRecordArr = $this->getDatabaseConnection()->sql_fetch_assoc($res)) !== false) {
                $elementRecordsArr[$elementRecordArr['uid']] = $elementRecordArr;
            }
            $this->getDatabaseConnection()->sql_free_result($res);
        }

        return $elementRecordsArr;
    }

    public function getBaseUrl(array $extraParams = [])
    {
        return BackendUtility::getModuleUrl(
            'web_func',
            $this->getLinkParameters($extraParams)
        );
    }

    public function getLinkParameters(array $extraParams = [])
    {
        return array_merge(
            [
                'SET' => [
                    'function' => self::class,
                ],
            ],
            $extraParams
        );
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return TemplaVoilaUtility::getDatabaseConnection();
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return FlashMessageQueue
     */
    protected function getFlashMessageQueue()
    {
        if (!isset($this->flashMessageQueue)) {
            /** @var FlashMessageService $service */
            $service = GeneralUtility::makeInstance(FlashMessageService::class);
            $this->flashMessageQueue = $service->getMessageQueueByIdentifier();
        }
        return $this->flashMessageQueue;
    }
}
