<?php
namespace Ppi\TemplaVoilaPlus\Module\Mod1;

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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Submodule 'clipboard' for the templavoila page module
 *
 * @author Robert Lemke <robert@typo3.org>
 */
class Clipboard implements SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Backend\Clipboard\Clipboard
     */
    protected $t3libClipboardObj;

    /**
     * @var array
     */
    protected $deleteUids;

    /**
     * A pointer to the parent object, that is the templavoila page module script. Set by calling the method init() of this class.
     *
     * @var \tx_templavoilaplus_module1
     */
    public $pObj;

    /**
     * Initializes the clipboard object. The calling class must make sure that the right locallang files are already loaded.
     * This method is usually called by the templavoila page module.
     *
     * Also takes the GET variable "CB" and submits it to the t3lib clipboard class which handles all
     * the incoming information and stores it in the user session.
     *
     * @param \tx_templavoilaplus_module1 $pObj Reference to the parent object ($this)
     *
     * @return void
     */
    public function init($pObj)
    {
        // Make local reference to some important variables:
        $this->pObj = $pObj;

        // Initialize the t3lib clipboard:
        $this->t3libClipboardObj = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Clipboard\Clipboard::class);
        $this->t3libClipboardObj->initializeClipboard();
        $this->t3libClipboardObj->lockToNormal();

        // Clipboard actions are handled:
        $CB = GeneralUtility::_GP('CB'); // CB is the clipboard command array
        $this->t3libClipboardObj->setCmd($CB); // Execute commands.

        if (isset($CB['setFlexMode'])) {
            switch ($CB['setFlexMode']) {
                case 'copy':
                    $this->t3libClipboardObj->clipData['normal']['flexMode'] = 'copy';
                    break;
                case 'cut':
                    $this->t3libClipboardObj->clipData['normal']['flexMode'] = 'cut';
                    break;
                case 'ref':
                    $this->t3libClipboardObj->clipData['normal']['flexMode'] = 'ref';
                    break;
                default:
                    unset($this->t3libClipboardObj->clipData['normal']['flexMode']);
                    break;
            }
        }

        $this->t3libClipboardObj->cleanCurrent(); // Clean up pad
        $this->t3libClipboardObj->endClipboard(); // Save the clipboard content

        // Add a list of non-used elements to the sidebar:
        $this->pObj->sideBarObj->addItem('nonUsedElements', $this, 'sidebar_renderNonUsedElements', TemplaVoilaUtility::getLanguageService()->getLL('nonusedelements'), 30);
    }

    /**
     * Renders the copy, cut and reference buttons for the element specified by the
     * flexform pointer.
     *
     * @param array $elementPointer Flex form pointer specifying the element we want to render the buttons for
     * @param string $listOfButtons A comma separated list of buttons which should be rendered. Possible values: 'copy', 'cut' and 'ref'
     *
     * @return string HTML output: linked images which act as copy, cut and reference buttons
     */
    public function element_getSelectButtons($elementPointer, $listOfButtons = 'copy,cut,ref')
    {
        $clipActive_copy = $clipActive_cut = $clipActive_ref = false;
        if (!$elementPointer = $this->pObj->apiObj->flexform_getValidPointer($elementPointer)) {
            return '';
        }
        $elementRecord = $this->pObj->apiObj->flexform_getRecordByPointer($elementPointer);

        // Fetch the element from the "normal" clipboard (if any) and set the button states accordingly:
        if (is_array($this->t3libClipboardObj->clipData['normal']['el'])) {
            $clipboardElementPointerString = reset($this->t3libClipboardObj->clipData['normal']['el']);
            $clipboardElementTableAndUid = key($this->t3libClipboardObj->clipData['normal']['el']);

            $clipboardElementPointer = $this->pObj->apiObj->flexform_getValidPointer($clipboardElementPointerString);

            // If we have no flexform reference pointing to the element, we create a short flexform pointer pointing to the record directly:
            if (!is_array($clipboardElementPointer)) {
                list ($clipboardElementTable, $clipboardElementUid) = explode('|', $clipboardElementTableAndUid);
                $pointToTheSameRecord = ($elementRecord['uid'] == $clipboardElementUid);
            } else {
                unset($clipboardElementPointer['targetCheckUid']);
                unset($elementPointer['targetCheckUid']);
                $pointToTheSameRecord = ($clipboardElementPointer == $elementPointer);
            }

            // Set whether the current element is selected for copy/cut/reference or not:
            if ($pointToTheSameRecord) {
                $selectMode = isset($this->t3libClipboardObj->clipData['normal']['flexMode']) ? $this->t3libClipboardObj->clipData['normal']['flexMode'] : ($this->t3libClipboardObj->clipData['normal']['mode'] == 'copy' ? 'copy' : 'cut');
                $clipActive_copy = ($selectMode == 'copy');
                $clipActive_cut = ($selectMode == 'cut');
                $clipActive_ref = ($selectMode == 'ref');
            }
        }

        $output = '';

        foreach (explode(',', $listOfButtons) as $button) {
            if (!in_array($button, $this->pObj->blindIcons)) {
                switch ($button) {
                    case 'copy':
                        $title = TemplaVoilaUtility::getLanguageService()->getLL('copyrecord');
                        $icon = 'actions-edit-copy';
                        $copyMode = 1;
                        $clipBoardElement = [
                            'tt_content|' . $elementRecord['uid'] => $this->pObj->apiObj->flexform_getStringFromPointer($elementPointer)
                        ];
                        break;
                    case 'cut':
                        $title = TemplaVoilaUtility::getLanguageService()->getLL('cutrecord');
                        $icon = 'actions-edit-cut';
                        $copyMode = 0;
                        $clipBoardElement = [
                            'tt_content|' . $elementRecord['uid'] => $this->pObj->apiObj->flexform_getStringFromPointer($elementPointer)
                        ];
                        break;
                    case 'ref':
                        $title = TemplaVoilaUtility::getLanguageService()->getLL('createreference');
                        $icon = 'extensions-templavoila-clip_ref';
                        $copyMode = 1;
                        $clipBoardElement = [
                            'tt_content|' . $elementRecord['uid'] => 1
                        ];
                        break;
                    default:
                        continue;
                }

                $isActive = ($selectMode === $button);

                $params = $this->pObj->getLinkParameters(
                    [
                        'CB' => [
                            'setCopyMode' => $copyMode,
                            'setFlexMode' => $button,
                            'removeAll' => ($isActive ? 'normal' : ''),
                            'el' => ($isActive ? '' : $clipBoardElement),
                        ],
                    ]
                );

                $output .= $this->pObj->buildButton(
                    'web_txtemplavoilaplusLayout',
                    $title,
                    $icon . ($isActive ? '-release' : ''),
                    $params
                );
            }
        }

        return $output;
    }

    /**
     * Renders and returns paste buttons for the destination specified by the flexform pointer.
     * The buttons are (or is) only rendered if a suitable element is found in the "normal" clipboard
     * and if it is valid to paste it at the given position.
     *
     * @param array $destinationPointer Flexform pointer defining the destination location where a possible element would be pasted.
     *
     * @return string HTML output: linked image(s) which act as paste button(s)
     */
    public function element_getPasteButtons($destinationPointer)
    {
        if (in_array('paste', $this->pObj->blindIcons)) {
            return '';
        }

        $origDestinationPointer = $destinationPointer;
        if (!$destinationPointer = $this->pObj->apiObj->flexform_getValidPointer($destinationPointer)) {
            return '';
        }
        if (!is_array($this->t3libClipboardObj->clipData['normal']['el'])) {
            return '';
        }

        $clipboardElementPointerString = reset($this->t3libClipboardObj->clipData['normal']['el']);
        $clipboardElementTableAndUid = key($this->t3libClipboardObj->clipData['normal']['el']);
        $clipboardElementPointer = $this->pObj->apiObj->flexform_getValidPointer($clipboardElementPointerString);

        // If we have no flexform reference pointing to the element, we create a short flexform pointer pointing to the record directly:
        list ($clipboardElementTable, $clipboardElementUid) = explode('|', $clipboardElementTableAndUid);
        if (!is_array($clipboardElementPointer)) {
            if ($clipboardElementTable != 'tt_content') {
                return '';
            }

            $clipboardElementPointer = array(
                'table' => 'tt_content',
                'uid' => $clipboardElementUid
            );
        }

        // If the destination element is already a sub element of the clipboard element, we mustn't show any paste icon:
        $destinationRecord = $this->pObj->apiObj->flexform_getRecordByPointer($destinationPointer);
        $clipboardElementRecord = $this->pObj->apiObj->flexform_getRecordByPointer($clipboardElementPointer);
        $dummyArr = array();
        $clipboardSubElementUidsArr = $this->pObj->apiObj->flexform_getListOfSubElementUidsRecursively('tt_content', $clipboardElementRecord['uid'], $dummyArr);
        $clipboardElementHasSubElements = count($clipboardSubElementUidsArr) > 0;

        if ($clipboardElementHasSubElements) {
            if (array_search($destinationRecord['uid'], $clipboardSubElementUidsArr) !== false) {
                return '';
            }
            if ($origDestinationPointer['uid'] == $clipboardElementUid) {
                return '';
            }
        }

        // Prepare the ingredients for the different buttons:
        $pasteMode = isset($this->t3libClipboardObj->clipData['normal']['flexMode'])
            ? $this->t3libClipboardObj->clipData['normal']['flexMode']
            : ($this->t3libClipboardObj->clipData['normal']['mode'] == 'copy'
                ? 'copy'
                : 'cut'
            );

        $sourcePointerString = $this->pObj->apiObj->flexform_getStringFromPointer($clipboardElementPointer);
        $destinationPointerString = $this->pObj->apiObj->flexform_getStringFromPointer($destinationPointer);

        $output = '';
        if (!in_array('pasteAfter', $this->pObj->blindIcons)) {
            $output .= $this->pObj->buildButton(
                'web_txtemplavoilaplusLayout',
                TemplaVoilaUtility::getLanguageService()->getLL('pasterecord'),
                'extensions-templavoila-paste',
                $this->pObj->getLinkParameters(
                    [
                        'CB' => [
                            'removeAll' => ($this->pObj->modTSconfig['properties']['keepElementsInClipboard'] ? '' : 'normal'),
                        ],
                        'pasteRecord' => $pasteMode,
                        'source' => $sourcePointerString,
                        'destination' => $destinationPointerString,
                    ]
                )
            );
        }
        // FCEs with sub elements have two different paste icons, normal elements only one:
        if ($pasteMode == 'copy' && $clipboardElementHasSubElements && !in_array('pasteSubRef', $this->pObj->blindIcons)) {
            $output .= $this->pObj->buildButton(
                'web_txtemplavoilaplusLayout',
                TemplaVoilaUtility::getLanguageService()->getLL('pastefce_andreferencesubs'),
                'extensions-templavoila-pasteSubRef',
                $this->pObj->getLinkParameters(
                    [
                        'CB' => [
                            'removeAll' => ($this->pObj->modTSconfig['properties']['keepElementsInClipboard'] ? '' : 'normal'),
                        ],
                        'pasteRecord' => 'copyref',
                        'source' => $sourcePointerString,
                        'destination' => $destinationPointerString,
                    ]
                )
            );
        }

        return $output;
    }

    /**
     * Displays a list of local content elements on the page which were NOT used in the hierarchical structure of the page.
     *
     * @return string HTML output
     * @access protected
     */
    public function sidebar_renderNonUsedElements()
    {
        $output = '';
        $elementRows = array();
        $usedUids = array_keys($this->pObj->global_tt_content_elementRegister);
        $usedUids[] = 0;
        $pid = $this->pObj->id; // If workspaces should evaluated non-used elements it must consider the id: For "element" and "branch" versions it should accept the incoming id, for "page" type versions it must be remapped (because content elements are then related to the id of the offline version)

        $res = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTquery(
            BackendUtility::getCommonSelectFields('tt_content', '', array('uid', 'header', 'bodytext', 'sys_language_uid')),
            'tt_content',
            'pid=' . (int)$pid . ' ' .
            'AND uid NOT IN (' . implode(',', $usedUids) . ') ' .
            'AND ( t3ver_state NOT IN (1,3) OR (t3ver_wsid > 0 AND t3ver_wsid = ' . (int)TemplaVoilaUtility::getBackendUser()->workspace . ') )' .
            BackendUtility::deleteClause('tt_content') .
            BackendUtility::versioningPlaceholderClause('tt_content'),
            '',
            'uid'
        );

        $this->deleteUids = array(); // Used to collect all those tt_content uids with no references which can be deleted
        while (false !== ($row = TemplaVoilaUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
            $elementPointerString = 'tt_content:' . $row['uid'];

            // Prepare the language icon:
            $languageLabel = htmlspecialchars($this->pObj->allAvailableLanguages[$row['sys_language_uid']]['title']);
            if ($this->pObj->allAvailableLanguages[$row['sys_language_uid']]['flagIcon']) {
                $languageIcon = \Ppi\TemplaVoilaPlus\Utility\IconUtility::getFlagIconForLanguage($this->pObj->allAvailableLanguages[$row['sys_language_uid']]['flagIcon'], array('title' => $languageLabel, 'alt' => $languageLabel));
            } else {
                $languageIcon = ($languageLabel && $row['sys_language_uid'] ? '[' . $languageLabel . ']' : '');
            }

            // Prepare buttons:
            $cutButton = $this->element_getSelectButtons($elementPointerString, 'ref');
            $recordButton = BackendUtility::wrapClickMenuOnIcon(
                $this->pObj->getIconFactory()->getIconForRecord('tt_content', $row, Icon::SIZE_SMALL)->render(),
                'tt_content',
                $row['uid'],
                true,
                '&callingScriptId=' . rawurlencode($this->pObj->doc->scriptID),
                'new,copy,cut,pasteinto,pasteafter,delete'
            );

            if (TemplaVoilaUtility::getBackendUser()->workspace) {
                $wsRow = BackendUtility::getRecordWSOL('tt_content', $row['uid']);
                $isDeletedInWorkspace = $wsRow['t3ver_state'] == 2;
            } else {
                $isDeletedInWorkspace = false;
            }
            if (!$isDeletedInWorkspace) {
                $elementRows[] = '
                    <tr id="' . $elementPointerString . '" class="tpm-nonused-element">
                        <td class="tpm-nonused-controls">
                            <div aria-label="" role="toolbar" class="btn-toolbar">
                                <div class="btn-group">
                                    <span class="btn btn-primary disabled btn-sm">' . $languageIcon . '</span>
                                    ' . $cutButton . $this->renderReferenceCount($row['uid']) . '
                                    <span class="btn btn-default btn-sm">' . $recordButton . '</span>
                                </div>
                            </div>
                        </td>
                        <td class="tpm-nonused-preview">'
                             . htmlspecialchars(BackendUtility::getRecordTitle('tt_content', $row))
                    . '</td>
                    </tr>';
            }
        }

        if (count($elementRows)) {
            // Control for deleting all deleteable records:
            $deleteAll = '';
            if (count($this->deleteUids)) {
                $params = '';
                foreach ($this->deleteUids as $deleteUid) {
                    $params .= '&cmd[tt_content][' . $deleteUid . '][delete]=1';
                }
                $label = TemplaVoilaUtility::getLanguageService()->getLL('rendernonusedelements_deleteall');
                $deleteAll = $this->pObj->buildButtonFromUrl(
                    'jumpToUrl(' . BackendUtility::getLinkToDataHandlerAction($params, -1) . ');return false;',
                    $label,
                    'actions-edit-delete',
                    $label,
                    'warning'
                );
            }

            // Create table and header cell:
            $output = '
                <table class="tpm-nonused-elements lrPadding" border="0" cellpadding="0" cellspacing="1" width="100%">
                    <tr class="bgColor4-20">
                        <td colspan="3">' . TemplaVoilaUtility::getLanguageService()->getLL('inititemno_elementsNotBeingUsed', true) . ':</td>
                    </tr>
                    ' . implode('', $elementRows) . '
                    <tr class="bgColor4">
                        <td colspan="3" class="tpm-nonused-deleteall">' . $deleteAll . '</td>
                    </tr>
                </table>
            ';
        }

        return $output;
    }

    /**
     * Render a reference count in form of an HTML table for the content
     * element specified by $uid.
     *
     * @param integer $uid Element record Uid
     *
     * @return string HTML-table
     * @access protected
     */
    public function renderReferenceCount($uid)
    {
        $rows = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            'sys_refindex',
            'ref_table=' . TemplaVoilaUtility::getDatabaseConnection()->fullQuoteStr('tt_content', 'sys_refindex') .
            ' AND ref_uid=' . (int)$uid .
            ' AND deleted=0'
        );

        // Compile information for title tag:
        $infoData = array();
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (TemplaVoilaUtility::getBackendUser()->workspace && $row['tablename'] == 'pages' && $this->pObj->id == $row['recuid']) {
                    // We would have found you but we didn't - you're most likely deleted
                } elseif (TemplaVoilaUtility::getBackendUser()->workspace && $row['tablename'] == 'tt_content' && $this->pObj->global_tt_content_elementRegister[$row['recuid']] > 0) {
                    // We would have found you but we didn't - you're most likely deleted
                } else {
                    $infoData[] = $row['tablename'] . ':' . $row['recuid'] . ':' . $row['field'];
                }
            }
        }
        if (count($infoData)) {
            return $this->pObj->buildButtonFromUrl(
                'top.launchView(\'tt_content\', \'' . $uid . '\'); return false;',
                GeneralUtility::fixed_lgd_cs(implode(' / ', $infoData), 100),
                '',
                'Ref: ' . count($infoData)
            );
        } else {
            $this->deleteUids[] = $uid;
            $params = '&cmd[tt_content][' . $uid . '][delete]=1';

            return $this->pObj->buildButtonFromUrl(
                'jumpToUrl(' . BackendUtility::getLinkToDataHandlerAction($params, -1). ');return false;',
                TemplaVoilaUtility::getLanguageService()->getLL('renderreferencecount_delete', true),
                'actions-edit-delete',
                '',
                'warning'
            );
        }
    }
}
