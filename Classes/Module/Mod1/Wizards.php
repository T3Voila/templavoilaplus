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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Submodule 'Wizards' for the templavoila page module
 *
 * Note: This class is closely bound to the page module class and uses many variables and functions directly. After major modifications of
 *       the page module all functions of this wizard class should be checked to make sure that they still work.
 *
 * @author Robert Lemke <robert@typo3.org>
 */
class Wizards implements SingletonInterface
{
    /**
     * @var \Ppi\TemplaVoilaPlus\Service\ApiService
     */
    protected $apiObj;

    /**
     * @var \tx_templavoilaplus_module1
     */
    public $pObj; // A pointer to the parent object, that is the templavoila page module script. Set by calling the method init() of this class.

    /**
     * Config of TCAdefaults
     *
     * @var array
     */
    public $TCAdefaultOverride;

    /**
     * Initializes the wizards object. The calling class must make sure that the right locallang files are already loaded.
     * This method is usually called by the templavoila page module.
     *
     * @param \tx_templavoilaplus_module1 $pObj Reference to the parent object ($this)
     *
     * @return void
     */
    public function init($pObj)
    {
        // Make local reference to some important variables:
        $this->pObj = $pObj;
        $this->moduleTemplate = $this->pObj->moduleTemplate;
        $this->apiObj = $this->pObj->apiObj;
    }

    /********************************************
     *
     * Wizards render functions
     *
     ********************************************/

    /**
     * Creates the screen for "new page wizard"
     *
     * @param integer $positionPid Can be positive and negative depending of where the new page is going: Negative always points to a position AFTER the page having the abs. value of the positionId. Positive numbers means to create as the first subpage to another page.
     *
     * @return string Content for the screen output.
     * @todo  Check required field(s), support t3d
     */
    public function renderWizard_createNewPage($positionPid)
    {
        global $TYPO3_CONF_VARS;

        // Get default TCA values specific for the page and user
        $temp = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig(abs($positionPid), 'TCAdefaults');
        if (isset($temp['properties'])) {
            $this->TCAdefaultOverride = $temp['properties'];
        }

        // The user already submitted the create page form:
        if (GeneralUtility::_GP('doCreate') || isset($this->TCAdefaultOverride['pages.']['tx_templavoilaplus_to'])) {
            // Check if the HTTP_REFERER is valid
            $refInfo = parse_url(GeneralUtility::getIndpEnv('HTTP_REFERER'));
            $httpHost = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
            if ($httpHost == $refInfo['host'] || GeneralUtility::_GP('vC') == TemplaVoilaUtility::getBackendUser()->veriCode() || $GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer']) {
                // Create new page
                $newID = $this->createPage(GeneralUtility::_GP('data'), $positionPid);
                if ($newID > 0) {
                    $pageColumnsOnly = $this->getPageColumnsOnlyConfig($newID);

                    $returnUrl = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl(
                        'web_txtemplavoilaplusLayout',
                        [
                            'id' => $newID,
                            'updatePageTree' => 1,
                        ]
                    );

                    // Create parameters and finally run the classic page module's edit form for the new page:
                    header(
                        'Location: ' . GeneralUtility::locationHeaderUrl(
                            \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl(
                                'record_edit',
                                [
                                    'returnUrl' => $returnUrl,
                                    'edit' => [
                                        'pages' => [
                                            $newID => 'edit',
                                        ],
                                    ],
                                    'columnsOnly' => $pageColumnsOnly,
                                ]
                            )
                        )
                    );
                    exit();
                } else {
                    debug('Error: Could not create page!');
                }
            } else {
                debug('Error: Referer host did not match with server host.');
            }
        }

        // Based on t3d/xml templates:
        if (false != ($templateFile = GeneralUtility::_GP('templateFile'))) {
            if (GeneralUtility::getFileAbsFileName($templateFile) && @is_file($templateFile)) {
                // First, find positive PID for import of the page:
                $importPID = \TYPO3\CMS\Backend\Utility\BackendUtility::getTSconfig_pidValue('pages', '', $positionPid);

                // Initialize the import object:
                $import = $this->getImportObject();
                if ($import->loadFile($templateFile, 1)) {
                    // Find the original page id:
                    $origPageId = key($import->dat['header']['pagetree']);

                    // Perform import of content
                    $import->importData($importPID);

                    // Find the new page id (root page):
                    $newID = $import->import_mapId['pages'][$origPageId];

                    if ($newID) {
                        // If the page was destined to be inserted after another page, move it now:
                        if ($positionPid < 0) {
                            $cmd = array();
                            $cmd['pages'][$newID]['move'] = $positionPid;
                            $tceObject = $import->getNewTCE();
                            $tceObject->start(array(), $cmd);
                            $tceObject->process_cmdmap();
                        }

                        // PLAIN COPY FROM ABOVE - BEGIN
                        $pageColumnsOnly = $this->getPageColumnsOnlyConfig($newID);

                        $returnUrl = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl(
                            'web_txtemplavoilaplusLayout',
                            [
                                'id' => $newID,
                                'updatePageTree' => 1,
                            ]
                        );

                        // Create parameters and finally run the classic page module's edit form for the new page:
                        header(
                            'Location: ' . GeneralUtility::locationHeaderUrl(
                                \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl(
                                    'record_edit',
                                    [
                                        'returnUrl' => $returnUrl,
                                        'edit' => [
                                            'pages' => [
                                                $newID => 'edit',
                                            ],
                                        ],
                                        'columnsOnly' => $pageColumnsOnly,
                                    ]
                                )
                            )
                        );
                        exit();
                        // PLAIN COPY FROM ABOVE - END
                    } else {
                        debug('Error: Could not create page!');
                    }
                }
            }
        }
        // Start assembling the HTML output

        $this->moduleTemplate->setForm(
            '<form action="'
            . \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl(
                'web_txtemplavoilaplusLayout',
                [
                    'id' => $this->pObj->id,
                ]
            )
            . '" method="post" autocomplete="off" enctype="' . $TYPO3_CONF_VARS['SYS']['form_enctype'] . '" onsubmit="return TBE_EDITOR_checkSubmit(1);">'
        );

        $content = '<h3>' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:' . TemplaVoilaUtility::getCoreLangPath() . 'locallang_core.xlf:db_new.php.pagetitle') . ':</h3>';
        $this->moduleTemplate->setTitle(TemplaVoilaUtility::getLanguageService()->getLL('createnewpage_title'));

        // Add template selectors
        $tmplSelectorCode = '';
        $tmplSelector = $this->renderTemplateSelector($positionPid, 'tmplobj');
        if ($tmplSelector) {
            $tmplSelectorCode .= $tmplSelector;
        }

        $tmplSelector = $this->renderTemplateSelector($positionPid, 't3d');
        if ($tmplSelector) {
            $tmplSelectorCode .= $tmplSelector;
        }

        if ($tmplSelectorCode) {
            $content .= '<h3>' . htmlspecialchars(TemplaVoilaUtility::getLanguageService()->getLL('createnewpage_selecttemplate')) . '</h3>';
            $content .= TemplaVoilaUtility::getLanguageService()->getLL('createnewpage_templateobject_description');
            $content .= $tmplSelectorCode;
        }

        $content .= '<input type="hidden" name="positionPid" value="' . $positionPid . '" />';
        $content .= '<input type="hidden" name="doCreate" value="1" />';
        $content .= '<input type="hidden" name="cmd" value="crPage" />';

        return $content;
    }

    /********************************************
     *
     * Wizard related helper functions
     *
     ********************************************/

    /**
     * Returns comma seperated field names of page columns to show only on new page. Default this is hidden,title,alias.
     * You configure this with TSconfig 'mod.web_txtemplavoilaplusLayout.createPageWizard.fieldNames'. A value of "*" means show
     * all fields.
     *
     * @param integer $newID Page uid
     * @return string
     */
    private function getPageColumnsOnlyConfig($newID)
    {
        $pageColumnsOnly = 'hidden,title,alias';
        // Get TSconfig for a different selection of fields in the editing form
        $fieldNamesTs = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig($newID, 'mod.web_txtemplavoilaplusLayout.createPageWizard.fieldNames');
        if (isset($fieldNamesTs['value'])) {
            $fieldNamesTsValue = trim($fieldNamesTs['value']);
            if ($fieldNamesTsValue && $fieldNamesTsValue !== '*') {
                $pageColumnsOnly = $fieldNamesTsValue;
            } elseif($fieldNamesTsValue === '*') {
                $pageColumnsOnly = '';
            }
        }

        return $pageColumnsOnly;
    }

    /**
     * Renders the template selector.
     *
     * @param integer $positionPid Position id. Can be positive and negative depending of where the new page is going: Negative always points to a position AFTER the page having the abs. value of the positionId. Positive numbers means to create as the first subpage to another page.
     * @param string $templateType The template type, 'tmplobj' or 't3d'
     *
     * @return string HTML output containing a table with the template selector
     */
    public function renderTemplateSelector($positionPid, $templateType = 'tmplobj')
    {
        // Negative PID values is pointing to a page on the same level as the current.
        if ($positionPid < 0) {
            $pidRow = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('pages', abs($positionPid), 'pid');
            $parentPageId = $pidRow['pid'];
        } else {
            $parentPageId = $positionPid;
        }

        $storageFolderPID = $this->apiObj->getStorageFolderPid($parentPageId);
        $tmplHTML = array();
        $defaultIcon = $this->pObj->getIconFactory()->getIcon('extensions-templavoila-default-preview-icon', Icon::SIZE_LARGE)->render();
        $previewIcon = '';

        // look for TCEFORM.pages.tx_templavoilaplus_ds.removeItems / TCEFORM.pages.tx_templavoilaplus_to.removeItems
        $disallowedPageTemplateItems = $this->getDisallowedTSconfigItemsByFieldName($parentPageId, 'tx_templavoilaplus_ds');
        $disallowedDesignTemplateItems = $this->getDisallowedTSconfigItemsByFieldName($parentPageId, 'tx_templavoilaplus_to');

        switch ($templateType) {
            case 'tmplobj':
                // Create the "Default template" entry
                //Fetch Default TO
                $fakeRow = array('uid' => $parentPageId);
                $defaultTO = $this->pObj->apiObj->getContentTree_fetchPageTemplateObject($fakeRow);

                $previewIcon = $defaultIcon;
                // Create the "Default template" entry
                if ($defaultTO['previewicon']) {
                    if (@is_file(GeneralUtility::getFileAbsFileName('uploads/tx_templavoilaplus/' . $defaultTO['previewicon']))) {
                        $previewIcon = '<input type="image" class="c-inputButton" name="i0" value="0" src="' . '/uploads/tx_templavoilaplus/' . $defaultTO['previewicon'] . '" title="" />';
                        $previewIcon = '<img src="/uploads/tx_templavoilaplus/' . $defaultTO['previewicon'] . '">';
                    }
                }

                $description = $defaultTO['description'] ? htmlspecialchars($defaultTO['description']) : TemplaVoilaUtility::getLanguageService()->getLL('template_descriptiondefault', true);
                $tmplHTML [] = '<table style="float:left; width: 100%;" valign="top">
                <tr>
                    <td colspan="2" nowrap="nowrap">
                        <h3 class="bgColor3-20">' . htmlspecialchars(TemplaVoilaUtility::getLanguageService()->getLL('template_titleInherit')) . '</h3>
                    </td>
                </tr><tr>
                    <td style="padding: 0 5px" valign="top"><button type="submit" name="data[tx_templavoilaplus_to]" value="0" style="background: none; border: none;">' . $previewIcon . '</button></td>
                    <td width="120" valign="top">
                        <p><h4>' . htmlspecialchars(TemplaVoilaUtility::getLanguageService()->sL($defaultTO['title'])) . '</h4>' . TemplaVoilaUtility::getLanguageService()->sL($description) . '</p>
                    </td>
                </tr>
                </table>';

                $dsRepo = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Domain\Repository\DataStructureRepository::class);
                $toRepo = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Domain\Repository\TemplateRepository::class);
                $dsList = $dsRepo->getDatastructuresByStoragePidAndScope($storageFolderPID, \Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure::SCOPE_PAGE);
                foreach ($dsList as $dsObj) {
                    /** @var \Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure $dsObj */
                    if (GeneralUtility::inList($disallowedPageTemplateItems, $dsObj->getKey()) ||
                        !$dsObj->isPermittedForUser()
                    ) {
                        continue;
                    }

                    $toList = $toRepo->getTemplatesByDatastructure($dsObj, $storageFolderPID);
                    foreach ($toList as $toObj) {
                        /** @var \Ppi\TemplaVoilaPlus\Domain\Model\Template $toObj */
                        if ($toObj->hasParentTemplate() && $toObj->getRendertype() !== '') {
                            continue;
                        }
                        if ($toObj->getKey() === $defaultTO['uid']
                            || !$toObj->isPermittedForUser()
                            || GeneralUtility::inList($disallowedDesignTemplateItems, $toObj->getKey())
                        ) {
                            continue;
                        }

                        $tmpFilename = $toObj->getIcon();
                        $previewIcon = $defaultIcon;
                        if ($tmpFilename) {
                            if (@is_file(GeneralUtility::getFileAbsFileName(PATH_site . $tmpFilename))) {
                                // Note: we cannot use value of image input element because MSIE replaces this value with mouse coordinates! Thus on click we set value to a hidden field. See http://bugs.typo3.org/view.php?id=3376
                                $previewIcon = '<img src="/' . $tmpFilename . '">';
                            }
                        }
                        $description = $toObj->getDescription() ? htmlspecialchars($toObj->getDescription()) : TemplaVoilaUtility::getLanguageService()->getLL('template_nodescriptionavailable');
                        $tmplHTML [] = '<table style="width: 100%;" valign="top"><tr><td colspan="2" nowrap="nowrap"><h3 class="bgColor3-20">' . htmlspecialchars($toObj->getLabel()) . '</h3></td></tr>' .
                            '<tr><td style="padding: 0 5px" valign="top"><button type="submit" name="data[tx_templavoilaplus_to]" value="' . $toObj->getKey() . '" style="background: none; border: none;">' . $previewIcon . '</button></td><td width="120" valign="top"><p>' . TemplaVoilaUtility::getLanguageService()->sL($description) . '</p></td></tr></table>';
                    }
                }
                break;

            case 't3d':
                if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('impexp')) {
                    // Read template files from a certain folder. I suggest this is configurable in some way. But here it is hardcoded for initial tests.
                    $templateFolder = GeneralUtility::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] . '/export/templates/');
                    $files = GeneralUtility::getFilesInDir($templateFolder, 't3d,xml', 1, 1);

                    // Traverse the files found:
                    foreach ($files as $absPath) {
                        // Initialize the import object:
                        $import = $this->getImportObject();
                        if ($import->loadFile($absPath)) {
                            if (is_array($import->dat['header']['pagetree'])) { // This means there are pages in the file, we like that...:

                                // Page tree:
                                reset($import->dat['header']['pagetree']);
                                $pageTree = current($import->dat['header']['pagetree']);

                                // Thumbnail icon:
                                $iconTag = '';
                                if (is_array($import->dat['header']['thumbnail'])) {
                                    $pI = pathinfo($import->dat['header']['thumbnail']['filename']);
                                    if (GeneralUtility::inList('gif,jpg,png,jpeg', strtolower($pI['extension']))) {
                                        // Construct filename and write it:
                                        $fileName = GeneralUtility::getFileAbsFileName(
                                            'typo3temp/importthumb_' . GeneralUtility::shortMD5($absPath) . '.' . $pI['extension']
                                        );
                                        GeneralUtility::writeFile($fileName, $import->dat['header']['thumbnail']['content']);

                                        // Check that the image really is an image and not a malicious PHP script...
                                        if (getimagesize($fileName)) {
                                            // Create icon tag:
                                            $iconTag = '<img src="' . $GLOBALS['BACK_PATH'] . '../' . substr($fileName, strlen(PATH_site)) . '" ' . $import->dat['header']['thumbnail']['imgInfo'][3] . ' vspace="5" style="border: solid black 1px;" alt="" />';
                                        } else {
                                            GeneralUtility::unlink_tempfile($fileName);
                                        }
                                    }
                                }

                                $aTagB = '<a href="' . htmlspecialchars(GeneralUtility::linkThisScript(array('templateFile' => $absPath))) . '">';
                                $aTagE = '</a>';
                                $tmplHTML [] = '<table style="float:left; width: 100%;" valign="top"><tr><td colspan="2" nowrap="nowrap">
                    <h3 class="bgColor3-20">' . $aTagB . htmlspecialchars($import->dat['header']['meta']['title'] ? $import->dat['header']['meta']['title'] : basename($absPath)) . $aTagE . '</h3></td></tr>
                    <tr><td valign="top">' . $aTagB . $iconTag . $aTagE . '</td><td valign="top"><p>' . htmlspecialchars($import->dat['header']['meta']['description']) . '</p>
                        <em>Levels: ' . (count($pageTree) > 1 ? 'Deep structure' : 'Single page') . '<br/>
                        File: ' . basename($absPath) . '</em></td></tr></table>';
                            }
                        }
                    }
                }
                break;
        }

        $content = '';
        if (is_array($tmplHTML) && count($tmplHTML)) {
            $counter = 0;
            $content .= '<table>';
            foreach ($tmplHTML as $single) {
                $content .= ($counter ? '' : '<tr>') . '<td valign="top">' . $single . '</td>' . ($counter ? '</tr>' : '');
                $counter++;
                if ($counter > 1) {
                    $counter = 0;
                }
            }
            $content .= '</table>';
        }

        return $content;
    }

    /**
     * Performs the neccessary steps to creates a new page
     *
     * @param array $pageArray array containing the fields for the new page
     * @param integer $positionPid location within the page tree (parent id)
     *
     * @return integer uid of the new page record
     */
    public function createPage($pageArray, $positionPid)
    {
        $positionPageMoveToRow = \TYPO3\CMS\Backend\Utility\BackendUtility::getMovePlaceholder('pages', abs($positionPid));
        if (is_array($positionPageMoveToRow)) {
            $positionPid = ($positionPid > 0) ? $positionPageMoveToRow['uid'] : '-' . $positionPageMoveToRow['uid'];
        }

        $dataArr = array();
        $dataArr['pages']['NEW'] = $pageArray;
        $dataArr['pages']['NEW']['pid'] = $positionPid;
        if (is_null($dataArr['pages']['NEW']['hidden'])) {
            $dataArr['pages']['NEW']['hidden'] = 0;
        }
        unset($dataArr['pages']['NEW']['uid']);

        // If no data structure is set, try to find one by using the template object
        if ($dataArr['pages']['NEW']['tx_templavoilaplus_to'] && !$dataArr['pages']['NEW']['tx_templavoilaplus_ds']) {
            $templateObjectRow = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('tx_templavoilaplus_tmplobj', $dataArr['pages']['NEW']['tx_templavoilaplus_to'], 'uid,pid,datastructure');
            $dataArr['pages']['NEW']['tx_templavoilaplus_ds'] = $templateObjectRow['datastructure'];
        }

        $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);

        if (is_array($this->TCAdefaultOverride)) {
            $tce->setDefaultsFromUserTS($this->TCAdefaultOverride);
        }

        $tce->stripslashes_values = 0;
        $tce->start($dataArr, array());
        $tce->process_datamap();

        return $tce->substNEWwithIDs['NEW'];
    }

    /**
     * @return \TYPO3\CMS\Impexp\ImportExport
     */
    public function getImportObject()
    {
        $import = GeneralUtility::makeInstance(\tx_impexp::class);
        $import->init();

        return $import;
    }

    /**
     * Create sql condition for given table to limit records according to user access.
     *
     * @param string $table Table nme to fetch records from
     *
     * @return string Condition or empty string
     */
    public function buildRecordWhere($table)
    {
        $result = array();
        if (!TemplaVoilaUtility::getBackendUser()->isAdmin()) {
            $prefLen = strlen($table) + 1;
            foreach (TemplaVoilaUtility::getBackendUser()->userGroups as $group) {
                $items = GeneralUtility::trimExplode(',', $group['tx_templavoilaplus_access'], 1);
                foreach ($items as $ref) {
                    if (strstr($ref, $table)) {
                        $result[] = (int)substr($ref, $prefLen);
                    }
                }
            }
        }

        return (count($result) > 0 ? ' AND ' . $table . '.uid NOT IN (' . implode(',', $result) . ') ' : '');
    }

    /**
     * Extract the disallowed TCAFORM field values of $fieldName given field
     *
     * @param integer $positionPid
     * @param string $fieldName field name of TCAFORM
     *
     * @access private
     * @return string comma seperated list of integer
     */
    public function getDisallowedTSconfigItemsByFieldName($positionPid, $fieldName)
    {
        // Negative PID values is pointing to a page on the same level as the current.
        if ($positionPid < 0) {
            $pidRow = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('pages', abs($positionPid), 'pid');
            $parentPageId = $pidRow['pid'];
        } else {
            $parentPageId = $positionPid;
        }

        // Get PageTSconfig for reduce the output of selectded template structs
        $disallowPageTemplateStruct = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig(abs($parentPageId), 'TCEFORM.pages.' . $fieldName);

        if (isset($disallowPageTemplateStruct['properties']['removeItems'])) {
            $disallowedPageTemplateList = $disallowPageTemplateStruct['properties']['removeItems'];
        } else {
            $disallowedPageTemplateList = '';
        }

        $tmp_disallowedPageTemplateItems = array_unique(GeneralUtility::intExplode(',', GeneralUtility::expandList($disallowedPageTemplateList), true));

        return (count($tmp_disallowedPageTemplateItems)) ? implode(',', $tmp_disallowedPageTemplateItems) : '0';
    }
}
