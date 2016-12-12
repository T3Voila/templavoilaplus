<?php
namespace Extension\Templavoila\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility as CoreGeneralUtility;

use Extension\Templavoila\Utility\GeneralUtility as TemplavoilaGeneralUtility;

$GLOBALS['LANG']->includeLLFile(
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('templavoila') . 'Resources/Private/Language/BackendControlCenter.xlf'
);

/**
 * Module 'TemplaVoila' for the 'templavoila' extension.
 *
 * @author Kasper Skaarhoj <kasper@typo3.com>
 */
class BackendControlCenterController extends \TYPO3\CMS\Backend\Module\BaseScriptClass
{
    /**
     * @var array
     */
    protected $pidCache;

    /**
     * @var string
     */
    protected $backPath;

    /**
     * Import as first page in root!
     *
     * @var integer
     */
    public $importPageUid = 0;

    /**
     * @var array
     */
    public $pageinfo;

    /**
     * @var array
     */
    public $modTSconfig;

    /**
     * Extension key of this module
     *
     * @var string
     */
    public $extKey = 'templavoila';

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'web_txtemplavoilaM2';

    /**
     * @var array
     */
    public $tFileList = array();

    /**
     * @var array
     */
    public $errorsWarnings = array();

    /**
     * holds the extconf configuration
     *
     * @var array
     */
    public $extConf;

    /**
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->moduleTemplate = CoreGeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\ModuleTemplate::class);
        $this->iconFactory = $this->moduleTemplate->getIconFactory();
        $this->buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);
    }

    /**
     * Preparing menu content
     *
     * @return void
     */
    public function menuConfig()
    {
        $this->MOD_MENU = [
        ];

        // page/be_user TSconfig settings and blinding of menu-items
        $this->modTSconfig = BackendUtility::getModTSconfig($this->id, 'mod.' . $this->moduleName);

        // CLEANSE SETTINGS
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, CoreGeneralUtility::_GP('SET'), $this->moduleName);
    }

    /*******************************************
     *
     * Main functions
     *
     *******************************************/

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->init();
        $this->main();
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Main function of the module.
     *
     * @return void
     */
    public function main()
    {
        // Access check!
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $pageInfoArr = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $access = is_array($pageInfoArr) ? 1 : 0;

        if ($access) {
            // Draw the header.

            // Add custom styles
            $this->getPageRenderer()->addCssFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($this->extKey) . 'Resources/Public/StyleSheet/mod2_default.css');

            $this->getPageRenderer()->loadJquery();

            // Setup JS for ClickMenu which isn't loaded by ModuleTemplate
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');

            // Set up JS for dynamic tab menu and side bar
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Tabs');

            // Adding classic jumpToUrl function, needed for the function menu.
            // Also, the id in the parent frameset is configured.
            $this->moduleTemplate->addJavaScriptCode('templavoila_function', '
                function jumpToUrl(URL)    { //
                    document.location = URL;
                    return false;
                }
                function setHighlight(id)    {    //
                    if (top.fsMod) {
                        top.fsMod.recentIds["web"]=id;
                        top.fsMod.navFrameHighlightedID["web"]="pages"+id+"_"+top.fsMod.currentBank;    // For highlighting

                        if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav)    {
                            top.content.nav_frame.refresh_nav();
                        }
                    }
                }
            ');

            $this->renderModuleContent();
        } else {
            $flashMessage = CoreGeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('noaccess'),
                '',
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
            $this->content = $flashMessage->render();
        }

        $title = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('title');
        $header = $this->moduleTemplate->header($title);
        $this->moduleTemplate->setTitle($title);

        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageInfoArr);
        $this->setDocHeaderButtons(!isset($pageInfoArr['uid']));

        $this->moduleTemplate->setContent($header . $this->content);
    }

    /**
     * Gets the buttons that shall be rendered in the docHeader.
     *
     * @return array Available buttons for the docHeader
     */
    protected function setDocHeaderButtons()
    {
        $this->addCshButton('');
        $this->addShortcutButton();
    }

    /**
     * Adds csh icon to the right document header button bar
     */
    public function addCshButton($fieldName)
    {
        $contextSensitiveHelpButton = $this->buttonBar->makeHelpButton()
            ->setModuleName('_MOD_' . $this->moduleName)
            ->setFieldName($fieldName);
        $this->buttonBar->addButton($contextSensitiveHelpButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * Adds shortcut icon to the right document header button bar
     */
    public function addShortcutButton()
    {
        $shortcutButton = $this->buttonBar->makeShortcutButton()
            ->setModuleName($this->moduleName)
            ->setGetVariables(
                [
                    'id',
                ]
            )
            ->setSetVariables(array_keys($this->MOD_MENU));
        $this->buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }


    /******************************
     *
     * Rendering module content:
     *
     *******************************/

    /**
     * Renders module content:
     *
     * @return void
     */
    public function renderModuleContent()
    {
        // If there are TO/DS, render the module as usual, otherwise do something else...:
        if ($this->isDataAvailable()) {
            $this->renderModuleContent_mainView();
        } else {
            $this->renderModuleContent_searchForTODS();
        }
    }

    /**
     * Returns true if data TO or DS Data is available on this->id
     *
     * @return bool
     */
    protected function isDataAvailable()
    {
        // We try TO first as DS may be outsourced into files which do not belong to PID
        return ($this->getCountTO($this->id) || $this->getCountDS($this->id));
    }

    /**
     * Returns real count of DS on given page id in contrast to dsRepository::getDatastructureCountForPid()
     *
     * @param integer $id Id of page to look into
     * @return integer Count of available DS
     */
    protected function getCountDS($id)
    {
        return $this->getDatabaseConnection()->exec_SELECTcountRows(
            'uid',
            'tx_templavoila_datastructure',
            'pid=' . (int)$this->id . BackendUtility::deleteClause('tx_templavoila_datastructure')
        );
    }

    /**
     * Returns count of TO in given page id should be same as tsRepository::getTemplateCountForPid()
     *
     * @param integer $id Id of page to look into
     * @return integer Count of available TO
     */
    protected function getCountTO($id)
    {
        return $this->getDatabaseConnection()->exec_SELECTcountRows(
            'uid',
            'tx_templavoila_tmplobj',
            'pid=' . (int)$this->id . BackendUtility::deleteClause('tx_templavoila_tmplobj')
        );
    }

    /**
     * Renders module content, overview of pages with DS/TO on.
     *
     * @return void
     */
    public function renderModuleContent_searchForTODS()
    {
        $dsRepo = CoreGeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\DataStructureRepository::class);
        $toRepo = CoreGeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\TemplateRepository::class);
        $list = $toRepo->getTemplateStoragePids();

        // Traverse the pages found and list in a table:
        $tRows = array();
        $tRows[] = '
            <thead>
                <th class="col-icon" nowrap="nowrap"></th>
                <th class="col-title" nowrap="nowrap">' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('storagefolders', true) . '</th>
                <th>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('datastructures', true) . '</th>
                <th>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('templateobjects', true) . '</th>
            </thead>';

        if (is_array($list)) {
            foreach ($list as $pid) {
                $path = $this->findRecordsWhereUsed_pid($pid);
                if ($path) {
                    $editUrl = BackendUtility::getModuleUrl($this->moduleName, array('id' => $pid));
                    $tRows[] = '
                        <tr>
                            <td class="col-icon" nowrap="nowrap">'
                                . $this->iconFactory->getIconForRecord('pages', BackendUtility::getRecord('pages', $pid), Icon::SIZE_SMALL)->render()
                            . '</td>'
                            . '<td><a href="' . $editUrl . '" onclick="setHighlight(' . $pid . ')">'
                            . htmlspecialchars($path) . '</a></td>
                            <td>' . $dsRepo->getDatastructureCountForPid($pid) . '</td>
                            <td>' . $toRepo->getTemplateCountForPid($pid) . '</td>
                        </tr>';
                }
            }

            // Create overview
            $outputString = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('description_pagesWithCertainDsTo');
            $outputString .= '<br/>';
            $outputString .= '<table border="0" class="table table-striped table-hover">' . implode('', $tRows) . '</table>';

            // Add output:
            $this->content .= $outputString;
        }
    }

    /**
     * Renders module content main view:
     *
     * @return void
     */
    public function renderModuleContent_mainView()
    {
        // Traverse scopes of data structures display template records belonging to them:
        // Each scope is places in its own tab in the tab menu:
        $dsScopes = array(
            \Extension\Templavoila\Domain\Model\AbstractDataStructure::SCOPE_PAGE,
            \Extension\Templavoila\Domain\Model\AbstractDataStructure::SCOPE_FCE,
            \Extension\Templavoila\Domain\Model\AbstractDataStructure::SCOPE_UNKNOWN
        );

        $toIdArray = $parts = array();
        foreach ($dsScopes as $scopePointer) {

            // Create listing for a DS:
            list($content, $dsCount, $toCount, $toIdArrayTmp) = $this->renderDSlisting($scopePointer);
            $toIdArray = array_merge($toIdArrayTmp, $toIdArray);
            $scopeIcon = '';

            // Label for the tab:
            switch ((string) $scopePointer) {
                case \Extension\Templavoila\Domain\Model\AbstractDataStructure::SCOPE_PAGE:
                    $label = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('pagetemplates');
                    $scopeIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', array());
                    break;
                case \Extension\Templavoila\Domain\Model\AbstractDataStructure::SCOPE_FCE:
                    $label = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('fces');
                    $scopeIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('tt_content', array());
                    break;
                case \Extension\Templavoila\Domain\Model\AbstractDataStructure::SCOPE_UNKNOWN:
                    $label = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('other');
                    break;
                default:
                    $label = sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('unknown'), $scopePointer);
                    break;
            }

            // Error/Warning log:
            $errStat = $this->getErrorLog($scopePointer);

            // Add parts for Tab menu:
            $parts[] = array(
                'label' => $label,
                'icon' => $scopeIcon,
                'content' => $content,
                'linkTitle' => 'DS/TO = ' . $dsCount . '/' . $toCount,
                'stateIcon' => $errStat['iconCode']
            );
        }

        // Find lost Template Objects and add them to a TAB if any are found:
        $lostTOs = '';
        $lostTOCount = 0;

        $toRepo = CoreGeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\TemplateRepository::class);
        $toList = $toRepo->getAll($this->id);
        foreach ($toList as $toObj) {
            /** @var \Extension\Templavoila\Domain\Model\Template $toObj */
            if (!in_array($toObj->getKey(), $toIdArray)) {
                $rTODres = $this->renderTODisplay($toObj, -1, 1);
                $lostTOs .= $rTODres['HTML'];
                $lostTOCount++;
            }
        }
        if ($lostTOs) {
            // Add parts for Tab menu:
            $parts[] = array(
                'label' => sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('losttos', true), $lostTOCount),
                'content' => $lostTOs
            );
        }

        // Complete Template File List
        $parts[] = array(
            'label' => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('templatefiles', true),
            'content' => $this->completeTemplateFileList()
        );

        // Errors:
        if (false !== ($errStat = $this->getErrorLog('_ALL'))) {
            $parts[] = array(
                'label' => 'Errors (' . $errStat['count'] . ')',
                'content' => $errStat['content'],
                'stateIcon' => $errStat['iconCode']
            );
        }

        // Add output:
        $this->content .= $this->moduleTemplate->getDynamicTabMenu($parts, 'TEMPLAVOILA:templateOverviewModule:' . $this->id, 1, 0, 300);
    }

    /**
     * Renders Data Structures from $dsScopeArray
     *
     * @param integer $scope
     *
     * @return array Returns array with three elements: 0: content, 1: number of DS shown, 2: number of root-level template objects shown.
     */
    public function renderDSlisting($scope)
    {
        $currentPid = (int)CoreGeneralUtility::_GP('id');
        /** @var \Extension\Templavoila\Domain\Repository\DataStructureRepository $dsRepo */
        $dsRepo = CoreGeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\DataStructureRepository::class);
        /** @var \Extension\Templavoila\Domain\Repository\TemplateRepository $toRepo */
        $toRepo = CoreGeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\TemplateRepository::class);

        if ($this->MOD_SETTINGS['set_unusedDs']) {
            $dsList = $dsRepo->getDatastructuresByScope($scope);
        } else {
            $dsList = $dsRepo->getDatastructuresByStoragePidAndScope($currentPid, $scope);
        }

        $dsCount = 0;
        $toCount = 0;
        $content = '';
        $index = '';
        $toIdArray = array(-1);

        // Traverse data structures to list:
        if (count($dsList)) {
            foreach ($dsList as $dsObj) {
                /** @var \Extension\Templavoila\Domain\Model\AbstractDataStructure $dsObj */

                // Traverse template objects which are not children of anything:
                $TOcontent = '';
                $indexTO = '';

                $toList = $toRepo->getTemplatesByDatastructure($dsObj, $currentPid);

                $newPid = (int)CoreGeneralUtility::_GP('id');
                $newFileRef = '';
                $newTitle = $dsObj->getLabel() . ' [TEMPLATE]';
                if (count($toList)) {
                    foreach ($toList as $toObj) {
                        /** @var \Extension\Templavoila\Domain\Model\Template $toObj */
                        $toIdArray[] = $toObj->getKey();
                        if ($toObj->hasParentTemplate()) {
                            continue;
                        }
                        $rTODres = $this->renderTODisplay($toObj, $scope);
                        $TOcontent .= '<a name="to-' . $toObj->getKey() . '"></a>' . $rTODres['HTML'];
                        $indexTO .= '
                            <tr class="bgColor4">
                                <td>&nbsp;&nbsp;&nbsp;</td>
                                <td><a href="#to-' . $toObj->getKey() . '">' . htmlspecialchars($toObj->getLabel()) . $toObj->hasParentTemplate() . '</a></td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td align="center">' . $rTODres['mappingStatus'] . '</td>
                                <td align="center">' . $rTODres['usage'] . '</td>
                            </tr>';
                        $toCount++;

                        $newPid = -$toObj->getKey();
                        $newFileRef = $toObj->getFileref();
                        $newTitle = $toObj->getLabel() . ' [ALT]';
                    }
                }
                // New-TO link:
                $TOcontent .= '<a href="#" onclick="' . htmlspecialchars(
                    BackendUtility::editOnClick(
                        '&edit[tx_templavoila_tmplobj][' . $newPid . ']=new' .
                        '&defVals[tx_templavoila_tmplobj][datastructure]=' . rawurlencode($dsObj->getKey()) .
                        '&defVals[tx_templavoila_tmplobj][title]=' . rawurlencode($newTitle) .
                        '&defVals[tx_templavoila_tmplobj][fileref]=' . rawurlencode($newFileRef),
                        $this->doc->backPath
                    )
                )
                . '">' . $this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render() . ' '
                . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('createnewto', true)
                . '</a>';

                // Render data structure display
                $rDSDres = $this->renderDataStructureDisplay($dsObj, $scope, $toIdArray);
                $content .= '<a name="ds-' . md5($dsObj->getKey()) . '"></a>' . $rDSDres['HTML'];
                $index .= '
                    <tr class="bgColor4-20">
                        <td colspan="2"><a href="#ds-' . md5($dsObj->getKey()) . '">' . htmlspecialchars($dsObj->getLabel()) . '</a></td>
                        <td align="center">' . $rDSDres['languageMode'] . '</td>
                        <td>' . $rDSDres['container'] . '</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>';
                if ($indexTO) {
                    $index .= $indexTO;
                }
                $dsCount++;

                // Wrap TO elements in a div-tag and add to content:
                if ($TOcontent) {
                    $content .= '<div style="margin-left: 102px;">' . $TOcontent . '</div>';
                }
            }
        }

        if ($index) {
            $content = '<h4>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('overview', true) . '</h4>
                        <table border="0" cellpadding="0" cellspacing="1">
                            <tr class="bgColor5 tableheader">
                                <td colspan="2">' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('dstotitle', true) . '</td>
                                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('localization', true) . '</td>
                                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('containerstatus', true) . '</td>
                                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mappingstatus', true) . '</td>
                                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('usagecount', true) . '</td>
                            </tr>
                        ' . $index . '
                        </table>' .
                $content;
        }

        return array($content, $dsCount, $toCount, $toIdArray);
    }

    /**
     * Rendering a single data structures information
     *
     * @param \Extension\Templavoila\Domain\Model\AbstractDataStructure $dsObj Structure information
     * @param integer $scope Scope.
     * @param array $toIdArray
     *
     * @return string HTML content
     */
    public function renderDataStructureDisplay(\Extension\Templavoila\Domain\Model\AbstractDataStructure $dsObj, $scope, $toIdArray)
    {
        $tableAttribs = ' border="0" cellpadding="1" cellspacing="1" width="98%" style="margin-top: 10px;" class="lrPadding"';

        $XMLinfo = array();
        if ($this->MOD_SETTINGS['set_details']) {
            $XMLinfo = $this->DSdetails($dsObj->getDataprotXML());
        }

        if ($dsObj->isFilebased()) {
            $overlay = 'overlay-edit';
            $fileName = CoreGeneralUtility::getFileAbsFileName($dsObj->getKey());
            $editUrl = BackendUtility::getModuleUrl(
                'file_edit',
                [
                    'target' => $fileName,
                    // Edit file do not support returnUrl anymore
                    // 'returnUrl' => CoreGeneralUtility::sanitizeLocalUrl(CoreGeneralUtility::getIndpEnv('REQUEST_URI')),
                ]
            );
            if (!is_file($fileName)) {
                $overlay = 'overlay-missing';
            } elseif (!is_writable($fileName)) {
                $overlay = 'overlay-locked';
            }
            $dsIcon = '<a href="' . htmlspecialchars($editUrl) . '">' . $this->iconFactory->getIconForFileExtension('xml', Icon::SIZE_SMALL, $overlay)->render() . '</a>';
        } else {
            $dsIcon = $this->iconFactory->getIconForRecord('tx_templavoila_datastructure', [], Icon::SIZE_SMALL)->render();
            $dsIcon = BackendUtility::wrapClickMenuOnIcon($dsIcon, 'tx_templavoila_datastructure', $dsObj->getKey(), true);
        }

        // Preview icon:
        if ($dsObj->getIcon()) {
            if (isset($this->modTSconfig['properties']['dsPreviewIconThumb']) && $this->modTSconfig['properties']['dsPreviewIconThumb'] != '0') {
                $path = realpath(dirname(__FILE__) . '/' . preg_replace('/\w+\/\.\.\//', '', $GLOBALS['BACK_PATH'] . $dsObj->getIcon()));
                $path = str_replace(realpath(PATH_site) . '/', PATH_site, $path);
                if ($path == false) {
                    $previewIcon = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('noicon', true);
                } else {
                    $previewIcon = BackendUtility::getThumbNail($this->doc->backPath . 'thumbs.php', $path,
                        'hspace="5" vspace="5" border="1"',
                        strpos($this->modTSconfig['properties']['dsPreviewIconThumb'], 'x') ? $this->modTSconfig['properties']['dsPreviewIconThumb'] : '');
                }
            } else {
                $previewIcon = '<img src="' . $dsObj->getIcon() . '" alt="" />';
            }
        } else {
            $previewIcon = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('noicon', true);
        }

        // Links:
        $lpXML = '';
        if ($dsObj->isFilebased()) {
            $editLink = $editDataprotLink = '';
            $dsTitle = $dsObj->getLabel();
        } else {
            $editLink = $lpXML .= '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick('&edit[tx_templavoila_datastructure][' . $dsObj->getKey() . ']=edit', $this->doc->backPath)) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open') . '</a>';
            $dsTitle = '<a href="' . htmlspecialchars('../cm1/index.php?table=tx_templavoila_datastructure&uid=' . $dsObj->getKey() . '&id=' . $this->id . '&returnUrl=' . rawurlencode(CoreGeneralUtility::sanitizeLocalUrl(CoreGeneralUtility::getIndpEnv('REQUEST_URI')))) . '">' . htmlspecialchars($dsObj->getLabel()) . '</a>';
        }
        // Compile info table:
        $content = '
        <table' . $tableAttribs . '>
            <tr class="bgColor5">
                <td colspan="3" style="border-top: 1px solid black;">' .
            $dsIcon . ' ' . $dsTitle .
            $editLink .
            '</td>
    </tr>
    <tr class="bgColor4">
        <td rowspan="' . ($this->MOD_SETTINGS['set_details'] ? 4 : 2) . '" style="width: 100px; text-align: center;">' . $previewIcon . '</td>
                ' .
            ($this->MOD_SETTINGS['set_details'] ? '<td style="width:200px">' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('templatestatus', true) . '</td>
                <td>' . $this->findDSUsageWithImproperTOs($dsObj, $scope, $toIdArray) . '</td>' : '') .
            '</tr>
            <tr class="bgColor4">
                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('globalprocessing_xml') . '</td>
                <td>
                    ' . $lpXML . ($dsObj->getDataprotXML() ?
                CoreGeneralUtility::formatSize(strlen($dsObj->getDataprotXML())) . ' bytes' .
                ($this->MOD_SETTINGS['set_details'] ? '<hr/>' . $XMLinfo['HTML'] : '') : '') . '
                </td>
            </tr>' . ($this->MOD_SETTINGS['set_details'] ? '
            <tr class="bgColor4">
                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('created', true) . '</td>
                <td>' . BackendUtility::datetime($dsObj->getCrdate()) . ' ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('byuser', true) . ' [' . $dsObj->getCruser() . ']</td>
            </tr>
            <tr class="bgColor4">
                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('updated', true) . '</td>
                <td>' . BackendUtility::datetime($dsObj->getTstamp()) . '</td>
            </tr>' : '') . '
        </table>
        ';

        // Format XML if requested (renders VERY VERY slow)
        if ($this->MOD_SETTINGS['set_showDSxml']) {
            if ($dsObj->getDataprotXML()) {
                $hlObj = CoreGeneralUtility::makeInstance(\Extension\Templavoila\Service\SyntaxHighlightingService::class);
                $content .= '<pre>' . str_replace(chr(9), '&nbsp;&nbsp;&nbsp;', $hlObj->highLight_DS($dsObj->getDataprotXML())) . '</pre>';
            }
        }

        $containerMode = '';
        if ($this->MOD_SETTINGS['set_details']) {
            if ($XMLinfo['referenceFields']) {
                $containerMode = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('yes', true);
                if ($XMLinfo['languageMode'] === 'Separate') {
                    $containerMode .= ' ' . $this->moduleTemplate->icons(3) . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('containerwithseparatelocalization', true);
                } elseif ($XMLinfo['languageMode'] === 'Inheritance') {
                    $containerMode .= ' ' . $this->moduleTemplate->icons(2);
                    if ($XMLinfo['inputFields']) {
                        $containerMode .= \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mixofcontentandref', true);
                    } else {
                        $containerMode .= \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('nocontentfields', true);
                    }
                }
            } else {
                $containerMode = 'No';
            }

            $containerMode .= ' (ARI=' . $XMLinfo['rootelements'] . '/' . $XMLinfo['referenceFields'] . '/' . $XMLinfo['inputFields'] . ')';
        }

        // Return content
        return array(
            'HTML' => $content,
            'languageMode' => $XMLinfo['languageMode'],
            'container' => $containerMode
        );
    }

    /**
     * Render display of a Template Object
     *
     * @param \Extension\Templavoila\Domain\Model\Template $toObj Template Object record to render
     * @param integer $scope Scope of DS
     * @param integer $children If set, the function is asked to render children to template objects (and should not call it self recursively again).
     *
     * @return string HTML content
     */
    public function renderTODisplay($toObj, $scope, $children = 0)
    {
        // Put together the records icon including content sensitive menu link wrapped around it:
        $recordIcon = $this->iconFactory->getIconForRecord('tx_templavoila_tmplobj', [], Icon::SIZE_SMALL)->render();
        $recordIcon = BackendUtility::wrapClickMenuOnIcon($recordIcon, 'tx_templavoila_tmplobj', $toObj->getKey(), true, '&callingScriptId=' . rawurlencode($this->doc->scriptID));

        // Preview icon:
        if ($toObj->getIcon()) {
            if (isset($this->modTSconfig['properties']['toPreviewIconThumb']) && $this->modTSconfig['properties']['toPreviewIconThumb'] != '0') {
                $path = realpath(dirname(__FILE__) . '/' . preg_replace('/\w+\/\.\.\//', '', $GLOBALS['BACK_PATH'] . $toObj->getIcon()));
                $path = str_replace(realpath(PATH_site) . '/', PATH_site, $path);
                if ($path == false) {
                    $icon = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('noicon', true);
                } else {
                    $icon = BackendUtility::getThumbNail($this->doc->backPath . 'thumbs.php', $path,
                        'hspace="5" vspace="5" border="1"',
                        strpos($this->modTSconfig['properties']['toPreviewIconThumb'], 'x') ? $this->modTSconfig['properties']['toPreviewIconThumb'] : '');
                }
            } else {
                $icon = '<img src="/' . $toObj->getIcon() . '" alt="" />';
            }
        } else {
            $icon = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('noicon', true);
        }

        // Mapping status / link:
        $uriParameters = [
            'table' => 'tx_templavoila_tmplobj',
            '_reload_from' => 1,
            'uid' => $toObj->getKey(),
            'id' => $this->id
            // TODO returnUrl
        ];
        $linkUrl = BackendUtility::getModuleUrl('_txtemplavoilaCM1', $uriParameters);

        $fileReference = CoreGeneralUtility::getFileAbsFileName($toObj->getFileref());
        if (@is_file($fileReference)) {
            $this->tFileList[$fileReference]++;
            $fileRef = '<a href="' . htmlspecialchars($this->doc->backPath . '../' . substr($fileReference, strlen(PATH_site))) . '" target="_blank">' . htmlspecialchars($toObj->getFileref()) . '</a>';
            $fileMsg = '';
            $fileMtime = filemtime($fileReference);
        } else {
            $fileRef = htmlspecialchars($toObj->getFileref());
            $fileMsg = '<div class="typo3-red">ERROR: File not found</div>';
            $fileMtime = 0;
        }

        $mappingStatus_index = '';
        if ($fileMtime && $toObj->getFilerefMtime()) {
            if ($toObj->getFilerefMD5() != '') {
                $modified = (@md5_file($fileReference) != $toObj->getFilerefMD5());
            } else {
                $modified = ($toObj->getFilerefMtime() != $fileMtime);
            }
            if ($modified) {
                $mappingStatus = $mappingStatus_index = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-warning');
                $mappingStatus .= sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('towasupdated', true), BackendUtility::datetime($toObj->getTstamp()));
                $this->setErrorLog($scope, 'warning', sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('warning_mappingstatus', true), $mappingStatus, $toObj->getLabel()));
            } else {
                $mappingStatus = $mappingStatus_index = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-ok');
                $mappingStatus .= \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mapping_uptodate', true);
            }
            $mappingStatus .= '<br/><input type="button" onclick="jumpToUrl(\'' . htmlspecialchars($linkUrl) . '\');" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('update_mapping', true) . '" />';
        } elseif (!$fileMtime) {
            $mappingStatus = $mappingStatus_index = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-error');
            $mappingStatus .= \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('notmapped', true);
            $this->setErrorLog($scope, 'fatal', sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('warning_mappingstatus', true), $mappingStatus, $toObj->getLabel()));

            $mappingStatus .= \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('updatemapping_info');
            $mappingStatus .= '<br/><input type="button" onclick="jumpToUrl(\'' . htmlspecialchars($linkUrl) . '\');" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('map', true) . '" />';
        } else {
            $mappingStatus = '';
            $mappingStatus .= '<input type="button" onclick="jumpToUrl(\'' . htmlspecialchars($linkUrl) . '\');" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('remap', true) . '" />';
            $mappingStatus .= '&nbsp;<input type="button" onclick="jumpToUrl(\'' . htmlspecialchars($linkUrl . '&_preview=1') . '\');" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('preview', true) . '" />';
        }

        if ($this->MOD_SETTINGS['set_details']) {
            $XMLinfo = $this->DSdetails($toObj->getLocalDataprotXML(true));
        } else {
            $XMLinfo = array('HTML' => '');
        }

        // Format XML if requested
        $lpXML = '';
        if ($this->MOD_SETTINGS['set_details']) {
            if ($toObj->getLocalDataprotXML(true)) {
                $hlObj = CoreGeneralUtility::makeInstance(\Extension\Templavoila\Service\SyntaxHighlightingService::class);
                $lpXML = '<pre>' . str_replace(chr(9), '&nbsp;&nbsp;&nbsp;', $hlObj->highLight_DS($toObj->getLocalDataprotXML(true))) . '</pre>';
            }
        }
        $lpXML .= '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick('&edit[tx_templavoila_tmplobj][' . $toObj->getKey() . ']=edit&columnsOnly=localprocessing', $this->doc->backPath)) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open') . '</a>';

        // Compile info table:
        $tableAttribs = ' border="0" cellpadding="1" cellspacing="1" width="98%" style="margin-top: 3px;" class="lrPadding"';

        // Links:
        $toTitle = '<a href="' . htmlspecialchars($linkUrl) . '">' . htmlspecialchars(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL($toObj->getLabel())) . '</a>';
        $editLink = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick('&edit[tx_templavoila_tmplobj][' . $toObj->getKey() . ']=edit', $this->doc->backPath)) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open') . '</a>';

        $fRWTOUres = array();

        if (!$children) {
            if ($this->MOD_SETTINGS['set_details']) {
                $fRWTOUres = $this->findRecordsWhereTOUsed($toObj, $scope);
            }

            $content = '
            <table' . $tableAttribs . '>
                <tr class="bgColor4-20">
                    <td colspan="3">' .
                $recordIcon .
                $toTitle .
                $editLink .
                '</td>
        </tr>
        <tr class="bgColor4">
            <td rowspan="' . ($this->MOD_SETTINGS['set_details'] ? 7 : 4) . '" style="width: 100px; text-align: center;">' . $icon . '</td>
                    <td style="width:200px;">' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('filereference', true) . ':</td>
                    <td>' . $fileRef . $fileMsg . '</td>
                </tr>
                <tr class="bgColor4">
                    <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('description', true) . ':</td>
                    <td>' . htmlspecialchars($toObj->getDescription()) . '</td>
                </tr>
                <tr class="bgColor4">
                    <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mappingstatus', true) . ':</td>
                    <td>' . $mappingStatus . '</td>
                </tr>
                <tr class="bgColor4">
                    <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('localprocessing_xml') . ':</td>
                    <td>
                        ' . $lpXML . ($toObj->getLocalDataprotXML(true) ?
                    CoreGeneralUtility::formatSize(strlen($toObj->getLocalDataprotXML(true))) . ' bytes' .
                    ($this->MOD_SETTINGS['set_details'] ? '<hr/>' . $XMLinfo['HTML'] : '') : '') . '
                    </td>
                </tr>' . ($this->MOD_SETTINGS['set_details'] ? '
                <tr class="bgColor4">
                    <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('usedby', true) . ':</td>
                    <td>' . $fRWTOUres['HTML'] . '</td>
                </tr>
                <tr class="bgColor4">
                    <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('created', true) . ':</td>
                    <td>' . BackendUtility::datetime($toObj->getCrdate()) . ' ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('byuser', true) . ' [' . $toObj->getCruser() . ']</td>
                </tr>
                <tr class="bgColor4">
                    <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('updated', true) . ':</td>
                    <td>' . BackendUtility::datetime($toObj->getTstamp()) . '</td>
                </tr>' : '') . '
            </table>
            ';
        } else {
            $content = '
            <table' . $tableAttribs . '>
                <tr class="bgColor4-20">
                    <td colspan="3">' .
                $recordIcon .
                $toTitle .
                $editLink .
                '</td>
        </tr>
        <tr class="bgColor4">
            <td style="width:200px;">' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('filereference', true) . ':</td>
                    <td>' . $fileRef . $fileMsg . '</td>
                </tr>
                <tr class="bgColor4">
                    <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mappingstatus', true) . ':</td>
                    <td>' . $mappingStatus . '</td>
                </tr>
                <tr class="bgColor4">
                    <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('rendertype', true) . ':</td>
                    <td>' . $this->getProcessedValue('tx_templavoila_tmplobj', 'rendertype', $toObj->getRendertype()) . '</td>
                </tr>
                <tr class="bgColor4">
                    <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('language', true) . ':</td>
                    <td>' . $this->getProcessedValue('tx_templavoila_tmplobj', 'sys_language_uid', $toObj->getSyslang()) . '</td>
                </tr>
                <tr class="bgColor4">
                    <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('localprocessing_xml') . ':</td>
                    <td>
                        ' . $lpXML . ($toObj->getLocalDataprotXML(true) ?
                    CoreGeneralUtility::formatSize(strlen($toObj->getLocalDataprotXML(true))) . ' bytes' .
                    ($this->MOD_SETTINGS['set_details'] ? '<hr/>' . $XMLinfo['HTML'] : '') : '') . '
                    </td>
                </tr>' . ($this->MOD_SETTINGS['set_details'] ? '
                <tr class="bgColor4">
                    <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('created', true) . ':</td>
                    <td>' . BackendUtility::datetime($toObj->getCrdate()) . ' ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('byuser', true) . ' [' . $toObj->getCruser() . ']</td>
                </tr>
                <tr class="bgColor4">
                    <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('updated', true) . ':</td>
                    <td>' . BackendUtility::datetime($toObj->getTstamp()) . '</td>
                </tr>' : '') . '
            </table>
            ';
        }

        // Traverse template objects which are not children of anything:
        $toRepo = CoreGeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\TemplateRepository::class);
        $toChildren = $toRepo->getTemplatesByParentTemplate($toObj);

        if (!$children && count($toChildren)) {
            $TOchildrenContent = '';
            foreach ($toChildren as $toChild) {
                $rTODres = $this->renderTODisplay($toChild, $scope, 1);
                $TOchildrenContent .= $rTODres['HTML'];
            }
            $content .= '<div style="margin-left: 102px;">' . $TOchildrenContent . '</div>';
        }

        // Return content
        return array('HTML' => $content, 'mappingStatus' => $mappingStatus_index, 'usage' => $fRWTOUres['usage']);
    }

    /**
     * Creates listings of pages / content elements where template objects are used.
     *
     * @param \Extension\Templavoila\Domain\Model\Template $toObj Template Object record
     * @param integer $scope Scope value. 1) page,  2) content elements
     *
     * @return string HTML table listing usages.
     */
    public function findRecordsWhereTOUsed($toObj, $scope)
    {
        $output = array();

        switch ($scope) {
            case 1: // PAGES:
                // Header:
                $output[] = '
                            <tr class="bgColor5 tableheader">
                                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('toused_pid', true) . ':</td>
                                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('toused_title', true) . ':</td>
                                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('toused_path', true) . ':</td>
                                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('toused_workspace', true) . ':</td>
                            </tr>';

                // Main templates:
                $dsKey = $toObj->getDatastructure()->getKey();
                $res = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTquery(
                    'uid,title,pid,t3ver_wsid,t3ver_id',
                    'pages',
                    '(
                        (tx_templavoila_to=' . (int)$toObj->getKey() . ' AND tx_templavoila_ds=' . \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->fullQuoteStr($dsKey, 'pages') . ') OR
                        (tx_templavoila_next_to=' . (int)$toObj->getKey() . ' AND tx_templavoila_next_ds=' . \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->fullQuoteStr($dsKey, 'pages') . ')
                    )' .
                    BackendUtility::deleteClause('pages')
                );

                while (false !== ($pRow = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
//                     $path = $this->findRecordsWhereUsed_pid($pRow['uid']);
                    if ($path) {
                        $output[] = '
                            <tr class="bgColor4-20">
                                <td nowrap="nowrap">' .
//                             '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick('&edit[pages][' . $pRow['uid'] . ']=edit', $this->doc->backPath)) . '" title="Edit">' .
                            htmlspecialchars($pRow['uid']) .
                            '</a></td>
                        <td nowrap="nowrap">' .
                            htmlspecialchars($pRow['title']) .
                            '</td>
                        <td nowrap="nowrap">' .
//                             '<a href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($pRow['uid'], $this->doc->backPath) . 'return false;') . '" title="View">' .
                            htmlspecialchars($path) .
                            '</a></td>
                        <td nowrap="nowrap">' .
                            htmlspecialchars($pRow['pid'] == -1 ? 'Offline version 1.' . $pRow['t3ver_id'] . ', WS: ' . $pRow['t3ver_wsid'] : 'LIVE!') .
                            '</td>
                    </tr>';
                    } else {
                        $output[] = '
                            <tr class="bgColor4-20">
                                <td nowrap="nowrap">' .
                            htmlspecialchars($pRow['uid']) .
                            '</td>
                        <td><em>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('noaccess', true) . '</em></td>
                                <td>-</td>
                                <td>-</td>
                            </tr>';
                    }
                }
                \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_free_result($res);
                break;
            case 2:

                // Select Flexible Content Elements:
                $res = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTquery(
                    'uid,header,pid,t3ver_wsid,t3ver_id',
                    'tt_content',
                    'CType=' . \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->fullQuoteStr('templavoila_pi1', 'tt_content') .
                    ' AND tx_templavoila_to=' . (int)$toObj->getKey() .
                    ' AND tx_templavoila_ds=' . \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->fullQuoteStr($toObj->getDatastructure()->getKey(), 'tt_content') .
                    BackendUtility::deleteClause('tt_content'),
                    '',
                    'pid'
                );

                // Header:
                $output[] = '
                            <tr class="bgColor5 tableheader">
                                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('toused_uid', true) . ':</td>
                                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('toused_header', true) . ':</td>
                                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('toused_path', true) . ':</td>
                                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('toused_workspace', true) . ':</td>
                            </tr>';

                // Elements:
                while (false !== ($pRow = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
//                     $path = $this->findRecordsWhereUsed_pid($pRow['pid']);
                    if ($path) {
                        $output[] = '
                            <tr class="bgColor4-20">
                                <td nowrap="nowrap">' .
//                             '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick('&edit[tt_content][' . $pRow['uid'] . ']=edit', $this->doc->backPath)) . '" title="Edit">' .
                            htmlspecialchars($pRow['uid']) .
                            '</a></td>
                        <td nowrap="nowrap">' .
                            htmlspecialchars($pRow['header']) .
                            '</td>
                        <td nowrap="nowrap">' .
//                             '<a href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($pRow['pid'], $this->doc->backPath) . 'return false;') . '" title="View page">' .
                            htmlspecialchars($path) .
                            '</a></td>
                        <td nowrap="nowrap">' .
                            htmlspecialchars($pRow['pid'] == -1 ? 'Offline version 1.' . $pRow['t3ver_id'] . ', WS: ' . $pRow['t3ver_wsid'] : 'LIVE!') .
                            '</td>
                    </tr>';
                    } else {
                        $output[] = '
                            <tr class="bgColor4-20">
                                <td nowrap="nowrap">' .
                            htmlspecialchars($pRow['uid']) .
                            '</td>
                        <td><em>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('noaccess', true) . '</em></td>
                                <td>-</td>
                                <td>-</td>
                            </tr>';
                    }
                }
                \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_free_result($res);
                break;
        }

        // Create final output table:
        $outputString = '';
        if (count($output)) {
            if (count($output) > 1) {
                $outputString = sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('toused_usedin', true), count($output) - 1) . '
                    <table border="0" cellspacing="1" cellpadding="1" class="lrPadding">'
                    . implode('', $output) . '
                </table>';
            } else {
                $outputString = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-warning') . 'No usage!';
                $this->setErrorLog($scope, 'warning', sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('warning_mappingstatus', true), $outputString, $toObj->getLabel()));
            }
        }

        return array('HTML' => $outputString, 'usage' => count($output) - 1);
    }

    /**
     * Creates listings of pages / content elements where NO or WRONG template objects are used.
     *
     * @param \Extension\Templavoila\Domain\Model\AbstractDataStructure $dsObj Data Structure ID
     * @param integer $scope Scope value. 1) page,  2) content elements
     * @param array $toIdArray Array with numerical toIDs. Must be integers and never be empty. You can always put in "-1" as dummy element.
     *
     * @return string HTML table listing usages.
     */
    public function findDSUsageWithImproperTOs($dsObj, $scope, $toIdArray)
    {
        $output = array();

        switch ($scope) {
            case 1: //
                // Header:
                $output[] = '
                            <tr class="bgColor5 tableheader">
                                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('toused_title', true) . ':</td>
                                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('toused_path', true) . ':</td>
                            </tr>';

                // Main templates:
                $res = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTquery(
                    'uid,title,pid',
                    'pages',
                    '(
                        (tx_templavoila_to NOT IN (' . implode(',', $toIdArray) . ') AND tx_templavoila_ds=' . \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->fullQuoteStr($dsObj->getKey(), 'pages') . ') OR
                        (tx_templavoila_next_to NOT IN (' . implode(',', $toIdArray) . ') AND tx_templavoila_next_ds=' . \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->fullQuoteStr($dsObj->getKey(), 'pages') . ')
                    )' .
                    BackendUtility::deleteClause('pages')
                );

                while (false !== ($pRow = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
                    $path = $this->findRecordsWhereUsed_pid($pRow['uid']);
                    if ($path) {
                        $output[] = '
                            <tr class="bgColor4-20">
                                <td nowrap="nowrap">' .
                            '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick('&edit[pages][' . $pRow['uid'] . ']=edit', $this->doc->backPath)) . '">' .
                            htmlspecialchars($pRow['title']) .
                            '</a></td>
                        <td nowrap="nowrap">' .
                            '<a href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($pRow['uid'], $this->doc->backPath) . 'return false;') . '">' .
                            htmlspecialchars($path) .
                            '</a></td>
                    </tr>';
                    } else {
                        $output[] = '
                            <tr class="bgColor4-20">
                                <td><em>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('noaccess', true) . '</em></td>
                                <td>-</td>
                            </tr>';
                    }
                }
                \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_free_result($res);
                break;
            case 2:

                // Select Flexible Content Elements:
                $res = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTquery(
                    'uid,header,pid',
                    'tt_content',
                    'CType=' . \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->fullQuoteStr('templavoila_pi1', 'tt_content') .
                    ' AND tx_templavoila_to NOT IN (' . implode(',', $toIdArray) . ')' .
                    ' AND tx_templavoila_ds=' . \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->fullQuoteStr($dsObj->getKey(), 'tt_content') .
                    BackendUtility::deleteClause('tt_content'),
                    '',
                    'pid'
                );

                // Header:
                $output[] = '
                            <tr class="bgColor5 tableheader">
                                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('toused_header', true) . ':</td>
                                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('toused_path', true) . ':</td>
                            </tr>';

                // Elements:
                while (false !== ($pRow = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
                    $path = $this->findRecordsWhereUsed_pid($pRow['pid']);
                    if ($path) {
                        $output[] = '
                            <tr class="bgColor4-20">
                                <td nowrap="nowrap">' .
                            '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick('&edit[tt_content][' . $pRow['uid'] . ']=edit', $this->doc->backPath)) . '" title="Edit">' .
                            htmlspecialchars($pRow['header']) .
                            '</a></td>
                        <td nowrap="nowrap">' .
                            '<a href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($pRow['pid'], $this->doc->backPath) . 'return false;') . '" title="View page">' .
                            htmlspecialchars($path) .
                            '</a></td>
                    </tr>';
                    } else {
                        $output[] = '
                            <tr class="bgColor4-20">
                                <td><em>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('noaccess', true) . '</em></td>
                                <td>-</td>
                            </tr>';
                    }
                }
                \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_free_result($res);
                break;
        }

        // Create final output table:
        $outputString = '';
        if (count($output)) {
            if (count($output) > 1) {
                $outputString = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-error') .
                    sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('invalidtemplatevalues', true), count($output) - 1);
                $this->setErrorLog($scope, 'fatal', $outputString);

                $outputString .= '<table border="0" cellspacing="1" cellpadding="1" class="lrPadding">' . implode('', $output) . '</table>';
            } else {
                $outputString = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-ok') .
                    \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('noerrorsfound', true);
            }
        }

        return $outputString;
    }

    /**
     * Checks if a PID value is accessible and if so returns the path for the page.
     * Processing is cached so many calls to the function are OK.
     *
     * @param integer $pid Page id for check
     *
     * @return string Page path of PID if accessible. otherwise zero.
     */
    public function findRecordsWhereUsed_pid($pid)
    {
        if (!isset($this->pidCache[$pid])) {
            $this->pidCache[$pid] = array();

            $pageinfo = BackendUtility::readPageAccess($pid, $this->perms_clause);
            $this->pidCache[$pid]['path'] = $pageinfo['_thePath'];
        }

        return $this->pidCache[$pid]['path'];
    }

    /**
     * Creates a list of all template files used in TOs
     *
     * @return string HTML table
     */
    public function completeTemplateFileList()
    {
        $output = '';
        if (is_array($this->tFileList)) {
            $output = '';

            // USED FILES:
            $tRows = array();
            $tRows[] = '
                <tr class="bgColor5 tableheader">
                    <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('file', true) . '</td>
                    <td align="center">' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('usagecount', true) . '</td>
                    <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newdsto', true) . '</td>
                </tr>';

            $i = 0;
            foreach ($this->tFileList as $tFile => $count) {
                $tRows[] = '
                    <tr class="' . ($i++ % 2 == 0 ? 'bgColor4' : 'bgColor6') . '">
                        <td>' .
                    '<a href="' . htmlspecialchars($this->doc->backPath . '../' . substr($tFile, strlen(PATH_site))) . '" target="_blank">' .
                    \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-view') . ' ' . htmlspecialchars(substr($tFile, strlen(PATH_site))) .
                    '</a></td>
                <td align="center">' . $count . '</td>
                        <td>' .
                    '<a href="' . htmlspecialchars($this->cm1Link . '?id=' . $this->id . '&file=' . rawurlencode($tFile)) . '&mapElPath=%5BROOT%5D">' .
                    \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new') . ' ' . htmlspecialchars('Create...') .
                    '</a></td>
            </tr>';
            }

            if (count($tRows) > 1) {
                $output .= '
                <h3>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('usedfiles', true) . ':</h3>
                <table border="0" cellpadding="1" cellspacing="1" class="typo3-dblist">
                    ' . implode('', $tRows) . '
                </table>
                ';
            }

            $files = $this->getTemplateFiles();

            // TEMPLATE ARCHIVE:
            if (count($files)) {

                $tRows = array();
                $tRows[] = '
                    <tr class="bgColor5 tableheader">
                        <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('file', true) . '</td>
                        <td align="center">' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('usagecount', true) . '</td>
                        <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newdsto', true) . '</td>
                    </tr>';

                $i = 0;
                foreach ($files as $tFile) {
                    $tRows[] = '
                        <tr class="' . ($i++ % 2 == 0 ? 'bgColor4' : 'bgColor6') . '">
                            <td>' .
                        '<a href="' . htmlspecialchars($this->doc->backPath . '../' . substr($tFile, strlen(PATH_site))) . '" target="_blank">' .
                        \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-view') . ' ' . htmlspecialchars(substr($tFile, strlen(PATH_site))) .
                        '</a></td>
                    <td align="center">' . ($this->tFileList[$tFile] ? $this->tFileList[$tFile] : '-') . '</td>
                            <td>' .
                        '<a href="' . htmlspecialchars($this->cm1Link . '?id=' . $this->id . '&file=' . rawurlencode($tFile)) . '&mapElPath=%5BROOT%5D">' .
                        \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new') . ' ' . htmlspecialchars('Create...') .
                        '</a></td>
                </tr>';
                }

                if (count($tRows) > 1) {
                    $output .= '
                    <h3>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('templatearchive', true) . ':</h3>
                    <table border="0" cellpadding="1" cellspacing="1" class="typo3-dblist">
                        ' . implode('', $tRows) . '
                    </table>
                    ';
                }
            }
        }

        return $output;
    }

    /**
     * Get the processed value analog to BackendUtility::getProcessedValue
     * but take additional TSconfig values into account
     *
     * @param string $table
     * @param string $typeField
     * @param string $typeValue
     *
     * @return string
     */
    protected function getProcessedValue($table, $typeField, $typeValue)
    {
        $value = BackendUtility::getProcessedValue($table, $typeField, $typeValue);
        if (!$value) {
            $TSConfig = BackendUtility::getPagesTSconfig($this->id);
            if (isset($TSConfig['TCEFORM.'][$table . '.'][$typeField . '.']['addItems.'][$typeValue])) {
                $value = $TSConfig['TCEFORM.'][$table . '.'][$typeField . '.']['addItems.'][$typeValue];
            }
        }

        return $value;
    }

    /**
     * Stores errors/warnings inside the class.
     *
     * @param string $scope Scope string, 1=page, 2=ce, _ALL= all errors
     * @param string $type "fatal" or "warning"
     * @param string $HTML HTML content for the error.
     *
     * @return void
     * @see getErrorLog()
     */
    public function setErrorLog($scope, $type, $HTML)
    {
        $this->errorsWarnings['_ALL'][$type][] = $this->errorsWarnings[$scope][$type][] = $HTML;
    }

    /**
     * Returns status for a single scope
     *
     * @param string $scope Scope string
     *
     * @return array Array with content
     * @see setErrorLog()
     */
    public function getErrorLog($scope)
    {
        $errStat = false;
        if (is_array($this->errorsWarnings[$scope])) {
            $errStat = array();

            if (is_array($this->errorsWarnings[$scope]['warning'])) {
                $errStat['count'] = count($this->errorsWarnings[$scope]['warning']);
                $errStat['content'] = '<h3>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('warnings', true) . '</h3>' . implode('<hr/>', $this->errorsWarnings[$scope]['warning']);
                $errStat['iconCode'] = 2;
            }

            if (is_array($this->errorsWarnings[$scope]['fatal'])) {
                $errStat['count'] = count($this->errorsWarnings[$scope]['fatal']) . ($errStat['count'] ? '/' . $errStat['count'] : '');
                $errStat['content'] .= '<h3>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('fatalerrors', true) . '</h3>' . implode('<hr/>', $this->errorsWarnings[$scope]['fatal']);
                $errStat['iconCode'] = 3;
            }
        }

        return $errStat;
    }

    /**
     * Shows a graphical summary of a array-tree, which suppose was a XML
     * (but don't need to). This function works recursively.
     *
     * @param array $DStree an array holding the DSs defined structure
     *
     * @return string HTML showing an overview of the DS-structure
     */
    public function renderDSdetails($DStree)
    {
        $HTML = '';

        if (is_array($DStree) && (count($DStree) > 0)) {
            $HTML .= '<dl class="DS-details">';

            foreach ($DStree as $elm => $def) {
                if (!is_array($def)) {
                    $HTML .= '<p>' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-error') . sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('invaliddatastructure_xmlbroken', true), $elm) . '</p>';
                    break;
                }

                $HTML .= '<dt>';
                $HTML .= ($elm == "meta" ? \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('configuration', true) : $def['tx_templavoila']['title'] . ' (' . $elm . ')');
                $HTML .= '</dt>';
                $HTML .= '<dd>';

                /* this is the configuration-entry ------------------------------ */
                if ($elm == "meta") {
                    /* The basic XML-structure of an meta-entry is:
                     *
                     * <meta>
                     *     <langDisable>        -> no localization
                     *     <langChildren>        -> no localization for children
                     *     <sheetSelector>        -> a php-function for selecting "sDef"
                     * </meta>
                     */

                    /* it would also be possible to use the 'list-style-image'-property
                     * for the flags, which would be more sensible to IE-bugs though
                     */
                    $conf = '';
                    if (isset($def['langDisable'])) {
                        $conf .= '<li>' .
                            (($def['langDisable'] == 1)
                                ? \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-error')
                                : \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-ok')
                            ) . ' ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('fceislocalized', true) . '</li>';
                    }
                    if (isset($def['langChildren'])) {
                        $conf .= '<li>' .
                            (($def['langChildren'] == 1)
                                ? \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-ok')
                                : \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-error')
                            ) . ' ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('fceinlineislocalized', true) . '</li>';
                    }
                    if (isset($def['sheetSelector'])) {
                        $conf .= '<li>' .
                            (($def['sheetSelector'] != '')
                                ? \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-ok')
                                : \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-error')
                            ) . ' custom sheet-selector' .
                            (($def['sheetSelector'] != '')
                                ? ' [<em>' . $def['sheetSelector'] . '</em>]'
                                : ''
                            ) . '</li>';
                    }

                    if ($conf != '') {
                        $HTML .= '<ul class="DS-config">' . $conf . '</ul>';
                    }
                } /* this a container for repetitive elements --------------------- */
                else if (isset($def['section']) && ($def['section'] == 1)) {
                    $HTML .= '<p>[..., ..., ...]</p>';
                } /* this a container for cellections of elements ----------------- */
                else {
                    if (isset($def['type']) && ($def['type'] == "array")) {
                        $HTML .= '<p>[...]</p>';
                    } /* this a regular entry ----------------------------------------- */
                    else {
                        $tco = true;
                        /* The basic XML-structure of an entry is:
                         *
                         * <element>
                         *     <tx_templavoila>    -> entries with informational character belonging to this entry
                         *     <TCEforms>        -> entries being used for TCE-construction
                         *     <type + el + section>    -> subsequent hierarchical construction
                         *    <langOverlayMode>    -> ??? (is it the language-key?)
                         * </element>
                         */
                        if (($tv = $def['tx_templavoila'])) {
                            /* The basic XML-structure of an tx_templavoila-entry is:
                             *
                             * <tx_templavoila>
                             *     <title>            -> Human readable title of the element
                             *     <description>        -> A description explaining the elements function
                             *     <sample_data>        -> Some sample-data (can't contain HTML)
                             *     <eType>            -> The preset-type of the element, used to switch use/content of TCEforms/TypoScriptObjPath
                             *     <oldStyleColumnNumber>    -> for distributing the fields across the tt_content column-positions
                             *     <proc>            -> define post-processes for this element's value
                             *        <int>        -> this element's value will be cast to an integer (if exist)
                             *        <HSC>        -> this element's value will convert special chars to HTML-entities (if exist)
                             *        <stdWrap>    -> an implicit stdWrap for this element, "stdWrap { ...inside... }"
                             *     </proc>
                             *    <TypoScript_constants>    -> an array of constants that will be substituted in the <TypoScript>-element
                             *     <TypoScript>        ->
                             *     <TypoScriptObjPath>    ->
                             * </tx_templavoila>
                             */

                            if (isset($tv['description']) && ($tv['description'] != '')) {
                                $HTML .= '<p>"' . $tv['description'] . '"</p>';
                            }

                            /* it would also be possible to use the 'list-style-image'-property
                             * for the flags, which would be more sensible to IE-bugs though
                             */
                            $proc = '';
                            if (isset($tv['proc']) && isset($tv['proc']['int'])) {
                                $proc .= '<li>' .
                                    (($tv['proc']['int'] == 1)
                                        ? \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-ok')
                                        : \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-error')
                                    ) . ' ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('casttointeger', true) . '</li>';
                            }
                            if (isset($tv['proc']) && isset($tv['proc']['HSC'])) {
                                $proc .= '<li>' .
                                    (($tv['proc']['HSC'] == 1)
                                        ? \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-ok')
                                        : \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-error')
                                    ) . ' ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('hsced', true) .
                                    (($tv['proc']['HSC'] == 1)
                                        ? ' ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('hsc_on', true)
                                        : ' ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('hsc_off', true)
                                    ) . '</li>';
                            }
                            if (isset($tv['proc']) && isset($tv['proc']['stdWrap'])) {
                                $proc .= '<li>' .
                                    (($tv['proc']['stdWrap'] != '')
                                        ? \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-ok')
                                        : \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-error')
                                    ) . ' ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('stdwrap', true) . '</li>';
                            }

                            if ($proc != '') {
                                $HTML .= '<ul class="DS-proc">' . $proc . '</ul>';
                            }
                            //TODO: get the registered eTypes and use the labels
                            switch ($tv['eType']) {
                                case "input":
                                    $preset = 'Plain input field';
                                    $tco = false;
                                    break;
                                case "input_h":
                                    $preset = 'Header field';
                                    $tco = false;
                                    break;
                                case "input_g":
                                    $preset = 'Header field, Graphical';
                                    $tco = false;
                                    break;
                                case "text":
                                    $preset = 'Text area for bodytext';
                                    $tco = false;
                                    break;
                                case "rte":
                                    $preset = 'Rich text editor for bodytext';
                                    $tco = false;
                                    break;
                                case "link":
                                    $preset = 'Link field';
                                    $tco = false;
                                    break;
                                case "int":
                                    $preset = 'Integer value';
                                    $tco = false;
                                    break;
                                case "image":
                                    $preset = 'Image field';
                                    $tco = false;
                                    break;
                                case "imagefixed":
                                    $preset = 'Image field, fixed W+H';
                                    $tco = false;
                                    break;
                                case "select":
                                    $preset = 'Selector box';
                                    $tco = false;
                                    break;
                                case "ce":
                                    $preset = 'Content Elements';
                                    $tco = true;
                                    break;
                                case "TypoScriptObject":
                                    $preset = 'TypoScript Object Path';
                                    $tco = true;
                                    break;

                                case "none":
                                    $preset = 'None';
                                    $tco = true;
                                    break;
                                default:
                                    $preset = 'Custom [' . $tv['eType'] . ']';
                                    $tco = true;
                                    break;
                            }

                            switch ($tv['oldStyleColumnNumber']) {
                                case 0:
                                    $column = 'Normal [0]';
                                    break;
                                case 1:
                                    $column = 'Left [1]';
                                    break;
                                case 2:
                                    $column = 'Right [2]';
                                    break;
                                case 3:
                                    $column = 'Border [3]';
                                    break;
                                default:
                                    $column = 'Custom [' . $tv['oldStyleColumnNumber'] . ']';
                                    break;
                            }

                            $notes = '';
                            if (($tv['eType'] != "TypoScriptObject") && isset($tv['TypoScriptObjPath'])) {
                                $notes .= '<li>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('redundant', true) . ' &lt;TypoScriptObjPath&gt;-entry</li>';
                            }
                            if (($tv['eType'] == "TypoScriptObject") && isset($tv['TypoScript'])) {
                                $notes .= '<li>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('redundant', true) . ' &lt;TypoScript&gt;-entry</li>';
                            }
                            if ((($tv['eType'] == "TypoScriptObject") || !isset($tv['TypoScript'])) && isset($tv['TypoScript_constants'])) {
                                $notes .= '<li>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('redundant', true) . ' &lt;TypoScript_constants&gt;-' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('entry', true) . '</li>';
                            }
                            if (isset($tv['proc']) && isset($tv['proc']['int']) && ($tv['proc']['int'] == 1) && isset($tv['proc']['HSC'])) {
                                $notes .= '<li>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('redundant', true) . ' &lt;proc&gt;&lt;HSC&gt;-' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('redundant', true) . '</li>';
                            }
                            if (isset($tv['TypoScriptObjPath']) && preg_match('/[^a-zA-Z0-9\.\:_]/', $tv['TypoScriptObjPath'])) {
                                $notes .= '<li><strong>&lt;TypoScriptObjPath&gt;-' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('illegalcharacters', true) . '</strong></li>';
                            }

                            $tsstats = '';
                            if (isset($tv['TypoScript_constants'])) {
                                $tsstats .= '<li>' . sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('dsdetails_tsconstants', true), count($tv['TypoScript_constants'])) . '</li>';
                            }
                            if (isset($tv['TypoScript'])) {
                                $tsstats .= '<li>' . sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('dsdetails_tslines', true), (1 + strlen($tv['TypoScript']) - strlen(str_replace("\n", "", $tv['TypoScript'])))) . '</li>';
                            }
                            if (isset($tv['TypoScriptObjPath'])) {
                                $tsstats .= '<li>' . sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('dsdetails_tsutilize', true), '<em>' . $tv['TypoScriptObjPath'] . '</em>') . '</li>';
                            }

                            $HTML .= '<dl class="DS-infos">';
                            $HTML .= '<dt>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('dsdetails_preset', true) . ':</dt>';
                            $HTML .= '<dd>' . $preset . '</dd>';
                            $HTML .= '<dt>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('dsdetails_column', true) . ':</dt>';
                            $HTML .= '<dd>' . $column . '</dd>';
                            if ($tsstats != '') {
                                $HTML .= '<dt>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('dsdetails_ts', true) . ':</dt>';
                                $HTML .= '<dd><ul class="DS-stats">' . $tsstats . '</ul></dd>';
                            }
                            if ($notes != '') {
                                $HTML .= '<dt>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('dsdetails_notes', true) . ':</dt>';
                                $HTML .= '<dd><ul class="DS-notes">' . $notes . '</ul></dd>';
                            }
                            $HTML .= '</dl>';
                        } else {
                            $HTML .= '<p>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('dsdetails_nobasicdefinitions', true) . '</p>';
                        }

                        /* The basic XML-structure of an TCEforms-entry is:
                         *
                         * <TCEforms>
                         *     <label>            -> TCE-label for the BE
                         *     <config>        -> TCE-configuration array
                         * </TCEforms>
                         */
                        if (!($def['TCEforms'])) {
                            if (!$tco) {
                                $HTML .= '<p>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('dsdetails_notceformdefinitions', true) . '</p>';
                            }
                        }
                    }
                }

                /* there are some childs to process ----------------------------- */
                if (isset($def['type']) && ($def['type'] == "array")) {

                    if (isset($def['section']))
                        ;
                    if (isset($def['el']))
                        $HTML .= $this->renderDSdetails($def['el']);
                }

                $HTML .= '</dd>';
            }

            $HTML .= '</dl>';
        } else
            $HTML .= '<p>' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-warning') . ' The element has no children!</p>';

        return $HTML;
    }

    /**
     * Show meta data part of Data Structure
     *
     * @param string $DSstring
     *
     * @return array
     */
    public function DSdetails($DSstring)
    {
        if (trim($DSstring) === '') {
            // Empty DS
            return [];
        }
        $DScontent = CoreGeneralUtility::xml2array($DSstring);

        if (!is_array($DScontent)) {
            if (trim($DScontent) === '') {
                // Empty DS XML
                return [];
            } else {
                // Errors in DS XML
                return [
                    'HTML' => '<p>' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-error') . $DScontent,
                ];
            }
        }

        $inputFields = 0;
        $referenceFields = 0;
        $rootelements = 0;
        if (is_array($DScontent) && is_array($DScontent['ROOT']['el'])) {
            foreach ($DScontent['ROOT']['el'] as $elCfg) {
                $rootelements++;
                if (isset($elCfg['TCEforms'])) {

                    // Assuming that a reference field for content elements is recognized like this, increment counter. Otherwise assume input field of some sort.
                    if ($elCfg['TCEforms']['config']['type'] === 'group' && $elCfg['TCEforms']['config']['allowed'] === 'tt_content') {
                        $referenceFields++;
                    } else {
                        $inputFields++;
                    }
                }
                if (isset($elCfg['el']))
                    $elCfg['el'] = '...';
                unset($elCfg['tx_templavoila']['sample_data']);
                unset($elCfg['tx_templavoila']['tags']);
                unset($elCfg['tx_templavoila']['eType']);
            }
        }

        /*    $DScontent = array('meta' => $DScontent['meta']);    */

        $languageMode = '';
        if (is_array($DScontent['meta'])) {
            if ($DScontent['meta']['langDisable']) {
                $languageMode = 'Disabled';
            } elseif ($DScontent['meta']['langChildren']) {
                $languageMode = 'Inheritance';
            } else {
                $languageMode = 'Separate';
            }
        }

        return array(
            'HTML' => /*CoreGeneralUtility::view_array($DScontent).'Language Mode => "'.$languageMode.'"<hr/>
                        Root Elements = '.$rootelements.', hereof ref/input fields = '.($referenceFields.'/'.$inputFields).'<hr/>
                        '.$rootElementsHTML*/
                $this->renderDSdetails($DScontent),
            'languageMode' => $languageMode,
            'rootelements' => $rootelements,
            'inputFields' => $inputFields,
            'referenceFields' => $referenceFields
        );
    }

    /**
     * Initialize the import-engine
     *
     * @return \TYPO3\CMS\Impexp\ImportExport Returns object ready to import the import-file used to create the basic site!
     */
    public function getImportObj()
    {
        /** @var \TYPO3\CMS\Impexp\ImportExport $import */
        $import = CoreGeneralUtility::makeInstance(\tx_impexp::class);
        $import->init(0, 'import');
        $import->enableLogging = true;

        return $import;
    }

    /**
     * Syntax Highlighting of TypoScript code
     *
     * @param string $v String of TypoScript code
     *
     * @return string HTML content with it highlighted.
     */
    public function syntaxHLTypoScript($v)
    {
        $tsparser = CoreGeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::class);
        $tsparser->lineNumberOffset = 0;
        $TScontent = $tsparser->doSyntaxHighlight(trim($v) . chr(10), '', 1);

        return $TScontent;
    }

    /**
     * Produce WRAP value
     *
     * @param array $cfg menuItemSuggestion configuration
     *
     * @return string Wrap for TypoScript
     */
    public function makeWrap($cfg)
    {
        if (!$cfg['bulletwrap']) {
            $wrap = $cfg['wrap'];
        } else {
            $wrap = $cfg['wrap'] . '  |*|  ' . $cfg['bulletwrap'] . $cfg['wrap'];
        }

        return preg_replace('/[' . chr(10) . chr(13) . chr(9) . ']/', '', $wrap);
    }

    /**
     * Returns the code that the menu was mapped to in the HTML
     *
     * @param string $field "Field" from Data structure, either "field_menu" or "field_submenu"
     *
     * @return string
     */
    public function getMenuDefaultCode($field)
    {
        // Select template record and extract menu HTML content
        $toRec = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $this->wizardData['templateObjectId']);
        $tMapping = unserialize($toRec['templatemapping']);

        return $tMapping['MappingData_cached']['cArray'][$field];
    }

    /**
     * Saves the menu TypoScript code
     *
     * @return void
     */
    public function saveMenuCode()
    {
        // Save menu code to template record:
        $cfg = CoreGeneralUtility::_POST('CFG');
        if (isset($cfg['menuCode'])) {

            // Get template record:
            $TSrecord = BackendUtility::getRecord('sys_template', $this->wizardData['typoScriptTemplateID']);
            if (is_array($TSrecord)) {
                $data = array();
                $data['sys_template'][$TSrecord['uid']]['config'] = '

## Menu [Begin]
' . trim($cfg['menuCode']) . '
## Menu [End]



' . $TSrecord['config'];

                // Execute changes:
                $tce = CoreGeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                $tce->stripslashes_values = 0;
                $tce->dontProcessTransformations = 1;
                $tce->start($data, Array());
                $tce->process_datamap();
            }
        }
    }

    /**
     * Tries to fetch the background color of a GIF or PNG image.
     *
     * @param string $filePath Filepath (absolute) of the image (must exist)
     *
     * @return string HTML hex color code, if any.
     */
    public function getBackgroundColor($filePath)
    {
        if (substr($filePath, -4) == '.gif' && function_exists('imagecreatefromgif')) {
            $im = @imagecreatefromgif($filePath);
        } elseif (substr($filePath, -4) == '.png' && function_exists('imagecreatefrompng')) {
            $im = @imagecreatefrompng($filePath);
        } else {
            $im = null;
        }

        if (is_resource($im)) {
            $values = imagecolorsforindex($im, imagecolorat($im, 3, 3));
            $color = '#' . substr('00' . dechex($values['red']), -2) .
                substr('00' . dechex($values['green']), -2) .
                substr('00' . dechex($values['blue']), -2);

            return $color;
        }

        return false;
    }

    /**
     * Find and check all template paths
     *
     * @param boolean $relative if true returned paths are relative
     * @param boolean $check if true the patchs are checked
     *
     * @return array all relevant template paths
     */
    protected function getTemplatePaths($relative = false, $check = true)
    {
        $templatePaths = array();
        if (strlen($this->modTSconfig['properties']['templatePath'])) {
            $paths = CoreGeneralUtility::trimExplode(',', $this->modTSconfig['properties']['templatePath'], true);
        } else {
            $paths = array('templates');
        }

        $prefix = CoreGeneralUtility::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']);

        foreach (\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->getFileStorages() AS $driver) {
            /** @var TYPO3\CMS\Core\Resource\ResourceStorage $driver */
            $driverpath = $driver->getConfiguration();
            $driverpath = CoreGeneralUtility::getFileAbsFileName($driverpath['basePath']);
            foreach ($paths as $path) {
                if (CoreGeneralUtility::isFirstPartOfStr($prefix . $path, $driverpath) && is_dir($prefix . $path)) {
                    $templatePaths[] = ($relative ? $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] : $prefix) . $path;
                } else {
                    if (!$check) {
                        $templatePaths[] = ($relative ? $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] : $prefix) . $path;
                    }
                }
            }
        }

        return $templatePaths;
    }

    /**
     * Find and check all templates within the template paths
     *
     * @return array all relevant templates
     */
    protected function getTemplateFiles()
    {
        $paths = $this->getTemplatePaths();
        $files = array();
        foreach ($paths as $path) {
            $files = array_merge(CoreGeneralUtility::getAllFilesAndFoldersInPath(array(), $path . ((substr($path, -1) != '/') ? '/' : ''), 'html,htm,tmpl', 0), $files);
        }

        return $files;
    }

    public function getLinkParameters(array $extraParams = [])
    {
        return array_merge(
            [
                'id' => $this->id,
            ],
            $extraParams
        );
    }

    public function getBaseUrl(array $extraParams = [])
    {
        return BackendUtility::getModuleUrl(
            $this->moduleName,
            $this->getLinkParameters($extraParams)
        );
    }

    /**
     * Builds a bootstrap button for given url
     *
     * @param string $clickUrl
     * @param string $title
     * @param string $icon
     * @param string $text
     * @param string $buttonType Type of the html button, see bootstrap
     * @param string $extraClass Extra class names to add to the bootstrap button classes
     * @param string $rel Data for the rel attrib
     * @return string
     */
    public function buildButtonFromUrl(
        $clickUrl, $title, $icon, $text = '', $buttonType = 'default', $extraClass = '', $rel = null
    ) {
        return '<a href="#"' . ($rel ? ' rel="' . $rel . '"' : '')
            . ' class="btn btn-' . $buttonType . ' btn-sm' . ($extraClass ? ' ' . $extraClass : '') . '"'
            . ' onclick="' . $clickUrl . '" title="' . $title . '">'
            . $this->iconFactory->getIcon($icon, Icon::SIZE_SMALL)->render()
            . ($text ? ' ' . $text : '')
            . '</a>';
    }

    /**
     * Adds an icon button to the document header button bar (left or right)
     *
     * @param string $module Name of the module this icon should link to
     * @param string $title Title of the button
     * @param string $icon Name of the Icon (inside IconFactory)
     * @param array $params Array of parameters which should be added to module call
     * @param string $buttonPosition left|right to position button inside the bar
     * @param integer $buttonGroup Number of the group the icon should go in
     */
    public function addDocHeaderButton(
        $module,
        $title,
        $icon,
        array $params = [],
        $buttonPosition = ButtonBar::BUTTON_POSITION_LEFT,
        $buttonGroup = 1
    ) {
        $url = BackendUtility::getModuleUrl(
            $module,
            array_merge(
                $params,
                [
                    'returnUrl' => CoreGeneralUtility::getIndpEnv('REQUEST_URI'),
                ]
            )
        );

        $button = $this->buttonBar->makeLinkButton()
            ->setHref($url)
            ->setTitle($title)
            ->setIcon($this->iconFactory->getIcon($icon, Icon::SIZE_SMALL));
        $this->buttonBar->addButton($button, $buttonPosition, $buttonGroup);
    }
}
