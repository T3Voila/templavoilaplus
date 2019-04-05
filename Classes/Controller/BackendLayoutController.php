<?php
namespace Ppi\TemplaVoilaPlus\Controller;

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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Module 'Page' for the 'templavoilaplus' extension.
 *
 * @author Robert Lemke <robert@typo3.org>
 * @coauthor   Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @coauthor   Dmitry Dulepov <dmitry@typo3.org>
 */

$GLOBALS['LANG']->includeLLFile(
    ExtensionManagementUtility::extPath('templavoilaplus') . 'Resources/Private/Language/BackendLayout.xlf'
);

/**
 * Module 'Page' for the 'templavoilaplus' extension.
 *
 * @author Robert Lemke <robert@typo3.org>
 * @coauthor    Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 */
class BackendLayoutController extends \TYPO3\CMS\Backend\Module\BaseScriptClass
{
    /**
     * @var \tx_templavoilaplus_mod1_localization
     */
    protected $localizationObj;

    /**
     * @var string
     */
    public $rootElementTable;

    /**
     * @var integer
     */
    protected $rootElementUid;

    /**
     * @var array
     */
    public $rootElementRecord;

    /**
     * @var integer
     */
    protected $containedElementsPointer;

    /**
     * @var integer
     */
    public $rootElementUid_pidForContent;

    /**
     * @var string
     */
    public $rootElementLangParadigm;

    /**
     * @var string
     */
    public $rootElementLangMode;

    /**
     * @var object
     */
    protected $pObj;

    /**
     * @var array
     */
    protected $containedElements;

    /**
     * This module's TSconfig
     *
     * @var array
     */
    public $modTSconfig;

    /**
     * TSconfig from mod.SHARED
     *
     * @var array
     */
    public $modSharedTSconfig;

    /**
     * Extension key of this module
     *
     * @var string
     */
    public $extKey = 'templavoilaplus';

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'web_txtemplavoilaplusLayout';

    /**
     * Contains a list of all content elements which are used on the page currently being displayed
     * (with version, sheet and language currently set). Mainly used for showing "unused elements" in sidebar.
     *
     * @var array
     */
    public $global_tt_content_elementRegister = array();

    /**
     * Contains structure telling the localization status of each element
     *
     * @var array
     */
    public $global_localization_status = array();

    /**
     * Keys: "table", "uid" - thats all to define another "rootTable" than "pages" (using default field "tx_templavoilaplus_flex" for flex form content)
     *
     * @var array
     */
    public $altRoot = array();

    /**
     * Versioning: The current version id
     *
     * @var integer
     */
    public $versionId = 0;

    /**
     * Contains the currently selected language key (Example: DEF or DE)
     *
     * @var string
     */
    public $currentLanguageKey;

    /**
     * Contains the currently selected language uid (Example: -1, 0, 1, 2, ...)
     *
     * @var integer
     */
    public $currentLanguageUid;

    /**
     * Contains records of all available languages (not hidden, with ISOcode), including the default
     * language and multiple languages. Used for displaying the flags for content elements, set in init().
     *
     * @var array
     */
    public $allAvailableLanguages = array();

    /**
     * Select language for which there is a page translation
     *
     * @var array
     */
    public $translatedLanguagesArr = array();

    /**
     * ISO codes (for l/v pairs) of translated languages.
     *
     * @var array
     */
    public $translatedLanguagesArr_isoCodes = array();

    /**
     * If this is set, the whole page module scales down functionality so that a translator only needs
     * to look for and click the "Flags" in the interface to localize the page! This flag is set if a
     * user does not have access to the default language; then translator mode is assumed.
     *
     * @var bool
     */
    public $translatorMode = false;

    /**
     * Permissions for the parrent record (normally page). Used for hiding icons.
     *
     * @var integer
     */
    public $calcPerms;

    /**
     * Instance of template doc class
     *
     * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    public $doc;

    /**
     *  Instance of sidebar class
     *
     * @var \tx_templavoilaplus_mod1_sidebar
     */
    public $sideBarObj;

    /**
     * Instance of wizards class
     *
     * @var \tx_templavoilaplus_mod1_wizards
     */
    public $wizardsObj;

    /**
     * Instance of clipboard class
     *
     * @var \tx_templavoilaplus_mod1_clipboard
     */
    public $clipboardObj;

    /**
     * Instance of records class
     *
     * @var \tx_templavoilaplus_mod1_records
     */
    public $recordsObj;

    /**
     * Instance of tx_templavoilaplus_api
     *
     * @var \Ppi\TemplaVoilaPlus\Service\ApiService
     */
    public $apiObj;

    /**
     * Registry for containers for drag and drop id => flexPointer-Pairs
     *
     * @var array
     */
    public $sortableContainers = array();

    /**
     * holds the extconf configuration
     *
     * @var array
     */
    public $extConf;

    /**
     * Icons which shouldn't be rendered by configuration, can contain elements of "new,edit,copy,cut,ref,paste,browse,delete,makeLocal,unlink,hide"
     *
     * @var array
     */
    public $blindIcons = array();

    /**
     * Classes for preview render
     *
     * @var null
     */
    protected $renderPreviewObjects = null;

    /**
     * Classes for preview render
     *
     * @var null
     */
    protected $renderPreviewDataObjects = null;

    /**
     * @var integer
     */
    protected $previewTitleMaxLen = 50;

    /**
     * @var array
     */
    protected $visibleContentHookObjects = array();

    /**
     * @var boolean
     */
    static protected $visibleContentHookObjectsPrepared = false;

    /**
     * @var boolean
     */
    protected $debug = false;

    /**
     * @var array
     */
    static protected $calcPermCache = array();

    /**
     * Setting which new content wizard to use
     *
     * @var string
     */
    protected $newContentWizModuleName = 'new_content_element';

    /**
     * Used for Content preview and is used as flag if content should be linked or not
     *
     * @var boolean
     */
    public $currentElementBelongsToCurrentPage;

    /**
     * Used for edit link of content elements
     *
     * @var array
     */
    public $currentElementParentPointer;

    /**
     * With this doktype the normal Edit screen is rendered
     *
     * @var integer
     */
    const DOKTYPE_NORMAL_EDIT = 1;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var string path to the locallang_core.xlf (which changed in 8.5.0)
     */
    protected $coreLangPath = 'lang/';

    /*******************************************
     *
     * Initialization functions
     *
     *******************************************/

    /**
     * Initialisation of this backend module
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->moduleTemplate = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\ModuleTemplate::class);
        $this->iconFactory = $this->moduleTemplate->getIconFactory();
        $this->buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $this->coreLangPath = TemplaVoilaUtility::getCoreLangPath();

        $view = $this->moduleTemplate->getView();
        $view->setPartialRootPaths(
            array_merge(
                $view->getPartialRootPaths(),
                ['EXT:templavoilaplus/Resources/Private/Partials']
            )
        );
        $view->setTemplateRootPaths(
            //             array_merge(
            //                 $view->getTemplateRootPaths(),
            //                 ['EXT:templavoilaplus/Resources/Private/Templates']
            //             )
            [
                'EXT:backend/Resources/Private/Templates',
                'EXT:templavoilaplus/Resources/Private/Templates'
            ]
        );
        $view->setTemplate('Module.html');

        $this->modSharedTSconfig = BackendUtility::getModTSconfig($this->id, 'mod.SHARED');
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), $this->moduleName);

        $tsConfig = BackendUtility::getModTSconfig($this->id, 'mod');
        if (isset($tsConfig['properties']['newContentElementWizard.']['override'])) {
            $this->newContentWizModuleName = $tsConfig['properties']['newContentElementWizard.']['override'];
        }

        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoilaplus']);

        $this->altRoot = GeneralUtility::_GP('altRoot');
        $this->versionId = GeneralUtility::_GP('versionId');

        if (isset($this->modTSconfig['properties']['previewTitleMaxLen'])) {
            $this->previewTitleMaxLen = (int)$this->modTSconfig['properties']['previewTitleMaxLen'];
        }

        // enable debug for development
        if (isset($this->modTSconfig['properties']['debug']) && $this->modTSconfig['properties']['debug']) {
            $this->debug = true;
        }
        $this->blindIcons = isset($this->modTSconfig['properties']['blindIcons']) ? GeneralUtility::trimExplode(',', $this->modTSconfig['properties']['blindIcons'], true) : array();

        // Fill array allAvailableLanguages and currently selected language (from language selector or from outside)
        $this->allAvailableLanguages = TemplaVoilaUtility::getAvailableLanguages(0, true, true, $this->modSharedTSconfig);
        $this->currentLanguageKey = $this->allAvailableLanguages[$this->MOD_SETTINGS['language']]['ISOcode'];
        $this->currentLanguageUid = $this->allAvailableLanguages[$this->MOD_SETTINGS['language']]['uid'];

        // If no translations exist for this page, set the current language to default (as there won't be a language selector)
        $this->translatedLanguagesArr = TemplaVoilaUtility::getAvailableLanguages($this->id, true, false, $this->modSharedTSconfig);
        if (count($this->translatedLanguagesArr) == 1) { // Only default language exists
            $this->currentLanguageKey = 'DEF';
        }

        // Set translator mode if the default langauge is not accessible for the user:
        if (!TemplaVoilaUtility::getBackendUser()->checkLanguageAccess(0) && !TemplaVoilaUtility::getBackendUser()->isAdmin()) {
            $this->translatorMode = true;
        }

        // Initialize TemplaVoila API class:
        $this->apiObj = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Service\ApiService::class, $this->altRoot ? $this->altRoot : 'pages');
        if (isset($this->modSharedTSconfig['properties']['useLiveWorkspaceForReferenceListUpdates'])) {
            $this->apiObj->modifyReferencesInLiveWS(true);
        }

        // Initialize side bar and wizards:
        $this->sideBarObj = GeneralUtility::getUserObj('Ppi\\TemplaVoilaPlus\Module\\Mod1\\Sidebar', '');
        $this->sideBarObj->init($this);

        $this->wizardsObj = GeneralUtility::getUserObj('Ppi\\TemplaVoilaPlus\Module\\Mod1\\Wizards', '');
        $this->wizardsObj->init($this);
        // Initialize the clipboard
        $this->clipboardObj = GeneralUtility::getUserObj('Ppi\\TemplaVoilaPlus\Module\\Mod1\\Clipboard', '');
        $this->clipboardObj->init($this);

        // Initialize the record module
        $this->recordsObj = GeneralUtility::getUserObj('Ppi\\TemplaVoilaPlus\Module\\Mod1\\Records', '');
        $this->recordsObj->init($this);
        // Add the localization module if localization is enabled:
        if ($this->alternativeLanguagesDefined()) {
            $this->localizationObj = GeneralUtility::getUserObj('Ppi\\TemplaVoilaPlus\Module\\Mod1\\Localization', '');
            $this->localizationObj->init($this);
        }
    }

    /**
     * Preparing menu content and initializing clipboard and module TSconfig
     *
     * @return void
     */
    public function menuConfig()
    {
        // Set defaults, which can be overwritten
        $this->modTSconfig = [
            'properties' => [
                'showTabsIfEmpty' => false,
                'recordDisplay_tables' => '',
                'tabList' => false,
            ]
        ];

        $this->modTSconfig = array_merge_recursive(
            BackendUtility::getModTSconfig($this->id, 'mod.' . $this->moduleName),
            $this->modTSconfig
        );

        $this->MOD_MENU = array(
            'tt_content_showHidden' => 1,
            'showOutline' => 1,
            'language' => '7', // $translatedLanguagesUids,
            'clip_parentPos' => '',
            'clip' => '',
            'langDisplayMode' => '',
            'recordsView_table' => '',
            'recordsView_start' => '',
            'disablePageStructureInheritance' => '',
        );

        // Hook: menuConfig_preProcessModMenu
        $menuHooks = $this->hooks_prepareObjectsArray('menuConfigClass');
        foreach ($menuHooks as $hookObj) {
            if (method_exists($hookObj, 'menuConfig_preProcessModMenu')) {
                $hookObj->menuConfig_preProcessModMenu($this->MOD_MENU, $this);
            }
        }

        // page/be_user TSconfig settings and blinding of menu-items
        $this->MOD_MENU['view'] = BackendUtility::unsetMenuItems($this->modTSconfig['properties'], $this->MOD_MENU['view'], 'menu.function');

        // CLEANSE SETTINGS
        $this->MOD_SETTINGS = BackendUtility::getModuleData([$this->MOD_MENU], GeneralUtility::_GP('SET'), $this->moduleName);
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
     * @throws RuntimeException
     *
     * @return void
     */
    public function main()
    {
        $this->content = '';

        // Access check! The page will show only if there is a valid page and if this page may be viewed by the user
        if (is_array($this->altRoot)) {
            $access = true;
            // get PID of altRoot Element to get pageInfoArr
            $altRootRecord = BackendUtility::getRecordWSOL($this->altRoot['table'], $this->altRoot['uid'], 'pid');
            $pageInfoArr = BackendUtility::readPageAccess($altRootRecord['pid'], $this->perms_clause);
        } else {
            $pageInfoArr = BackendUtility::readPageAccess($this->id, $this->perms_clause);
            $access = isset($pageInfoArr['uid']) && (int)$pageInfoArr['uid'] > 0 ? true : false;
        }

        if ($access) {

            // Additional header content
            $this->content .= $this->renderFunctionHook('renderHeader');

            $this->calcPerms = $this->getCalcPerms($pageInfoArr['uid']);

            // Define the root element record:
            $this->rootElementTable = is_array($this->altRoot) ? $this->altRoot['table'] : 'pages';
            $this->rootElementUid = is_array($this->altRoot) ? $this->altRoot['uid'] : $this->id;
            $this->rootElementRecord = BackendUtility::getRecordWSOL($this->rootElementTable, $this->rootElementUid, '*');
            if ($this->rootElementRecord['t3ver_oid'] && $this->rootElementRecord['pid'] < 0) {
                // typo3 lacks a proper API to properly detect Offline versions and extract Live Versions therefore this is done by hand
                if ($this->rootElementTable == 'pages') {
                    $this->rootElementUid_pidForContent = $this->rootElementRecord['t3ver_oid'];
                } else {
                    throw new \RuntimeException('Further execution of code leads to PHP errors.', 1404750505);
                    $liveRec = BackendUtility::getLiveRecord($this->rootElementTable, $this->rootElementUid);
                    $this->rootElementUid_pidForContent = $liveRec['pid'];
                }
            } else {
                // If pages use current UID, otherwhise you must use the PID to define the Page ID
                if ($this->rootElementTable == 'pages') {
                    $this->rootElementUid_pidForContent = $this->rootElementRecord['uid'];
                } else {
                    $this->rootElementUid_pidForContent = $this->rootElementRecord['pid'];
                }
            }

            // Check if we have to update the pagetree:
            if (GeneralUtility::_GP('updatePageTree')) {
                BackendUtility::setUpdateSignal('updatePageTree');
            }

            // Add custom styles
            $styleSheetFile = 'EXT:' . $this->extKey . '/Resources/Public/StyleSheet/mod1_default.css';

            if (isset($this->modTSconfig['properties']['stylesheet'])) {
                $styleSheetFile = $this->modTSconfig['properties']['stylesheet'];
            }

            $this->getPageRenderer()->addCssFile($styleSheetFile);

            if (isset($this->modTSconfig['properties']['stylesheet.'])) {
                foreach ($this->modTSconfig['properties']['stylesheet.'] as $file) {
                    $this->getPageRenderer()->addCssFile($file);
                }
            }

            // Adding classic jumpToUrl function, needed for the function menu. Also, the id in the parent frameset is configured.
            $this->moduleTemplate->addJavaScriptCode('templavoilaplus_base', '
                if (top.fsMod) top.fsMod.recentIds["web"] = ' . (int)$this->id . ';
                ' . $this->moduleTemplate->redirectUrls() . '
                var T3_TV_MOD1_BACKPATH = "' . $relativeExtensionPath . '";
                var T3_TV_MOD1_RETURNURL = "' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')) . '";
            ');

            $this->getPageRenderer()->loadJquery();

            // Setup JS for ContextMenu which isn't loaded by ModuleTemplate
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');

            // Set up JS for dynamic tab menu and side bar
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Tabs');

            $this->moduleTemplate->addJavaScriptCode('templavoilaplus_function', '
                TYPO3.jQuery(document).off(\'click.tab.data-api\', \'[data-toggle="tab"]\');
                TYPO3.jQuery(document).on(\'click.tab.data-api\', \'[data-toggle="tab"]\', function (e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    var tab = TYPO3.jQuery(TYPO3.jQuery(this).attr(\'href\'));
                    var activate = !tab.hasClass(\'active\');
                    TYPO3.jQuery(\'div.tab-content>div.tab-pane.active\').removeClass(\'active\');
                    TYPO3.jQuery(\'ul.nav.nav-tabs>li.active\').removeClass(\'active\');
                    if (activate) {
                        TYPO3.jQuery(this).tab(\'show\')
                    } else {
                        TYPO3.Tabs.storeActiveTab(e.currentTarget.id, \'\');
                    }
                    return true;
                });
                var typo3pageModule = {
                    /**
                     * Initialization
                     */
                    init: function() {
                        typo3pageModule.enableHighlighting();
                    },

                    /**
                     * This method is used to bind the higlighting function "setActive"
                     * to the mouseenter event and the "setInactive" to the mouseleave event.
                     */
                    enableHighlighting: function() {
                        TYPO3.jQuery(\'.pagecontainer\').on(\'mouseover\', typo3pageModule.setActive);
                    },

                    /**
                     * This method is used as an event handler when the
                     * user hovers the a content element.
                     */
                    setActive: function(e) {
                        TYPO3.jQuery(\'.pagecontainer .active\').removeClass(\'active\').addClass(\'inactive\');
                        if (e) {
                            $element = TYPO3.jQuery(e.target);
                            if (!$element.hasClass(\'t3-page-ce\')) {
                                $element = $element.closest(\'.t3-page-ce\');
                            }
                            if ($element) {
                                $element.removeClass(\'inactive\').addClass(\'active\');
                                e.stopPropagation();
                            }
                        }
                    }
                }

                TYPO3.jQuery(function() {
                    typo3pageModule.init();
                });
            ');

            $this->addJsLibrary(
                'templavoilaplus_mod1',
                'EXT:' . $this->extKey . '/Resources/Public/JavaScript/templavoila.js'
            );

            if (isset($this->modTSconfig['properties']['javascript.']) && is_array($this->modTSconfig['properties']['javascript.'])) {
                // add custom javascript files
                foreach ($this->modTSconfig['properties']['javascript.'] as $key => $filename) {
                    if ($filename) {
                        $this->addJsLibrary($key, $filename);
                    }
                }
            }

            $this->handleIncomingCommands();

            // Start creating HTML output

            $render_editPageScreen = true;

            // Show message if the page is of a special doktype:
            if ($this->rootElementTable == 'pages') {
                // Initialize the special doktype class:
                $specialDoktypesObj =& GeneralUtility::getUserObj('Ppi\\TemplaVoilaPlus\Module\\Mod1\\Specialdoktypes', '');
                $specialDoktypesObj->init($this);
                $doktype = $this->rootElementRecord['doktype'];

                // if doktype is configured as editType render normal edit view
                $docTypesToEdit = $this->modTSconfig['properties']['additionalDoktypesRenderToEditView'];
                if ($docTypesToEdit && GeneralUtility::inList($docTypesToEdit, $doktype)) {
                    //Make sure it is editable by page module
                    $doktype = self::DOKTYPE_NORMAL_EDIT;
                }

                $methodName = 'renderDoktype_' . $doktype;
                if (method_exists($specialDoktypesObj, $methodName)) {
                    $result = $specialDoktypesObj->$methodName($this->rootElementRecord);
                    if ($result !== false) {
                        $this->content .= $result;
                        if (TemplaVoilaUtility::getBackendUser()->isPSet($this->calcPerms, 'pages', 'edit')) {
                            // Edit icon only if page can be modified by user
                            $editLinkContent
                                = $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render()
                                . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:' . $this->coreLangPath . 'locallang_mod_web_list.xlf:editPage');
                            $this->content .= '<br/><br/><strong>' . $this->link_edit($editLinkContent, 'pages', $this->id) . '</strong>';
                        }
                        $render_editPageScreen = false; // Do not output editing code for special doctypes!
                    }
                }
            }

            if ($render_editPageScreen) {
                $editCurrentPageHTML = '';

                // warn if page renders content from other page
                if ($this->rootElementRecord['content_from_pid']) {
                    $contentPage = BackendUtility::getRecord('pages', (int)$this->rootElementRecord['content_from_pid']);
                    $title = BackendUtility::getRecordTitle('pages', $contentPage);
                    $url = $this->getBaseUrl(['id' => (int)$this->rootElementRecord['content_from_pid']]);
                    $clickUrl = 'jumpToUrl(\'' . $url . '\');return false;';

                    $this->moduleTemplate->addFlashMessage(
                        sprintf(
                            TemplaVoilaUtility::getLanguageService()->getLL('content_from_pid_title'),
                            $title
                        ),
                        '',
                        \TYPO3\CMS\Core\Messaging\FlashMessage::INFO
                    );
                    $this->content .= $this->buildButtonFromUrl(
                        $clickUrl,
                        $this->getLanguageService()->getLL('jumptocontentfrompidpage', true),
                        'apps-pagetree-page-content-from-page'
                    );
                }
                // Render "edit current page" (important to do before calling ->sideBarObj->render() - otherwise the translation tab is not rendered!
                $editCurrentPageHTML .= $this->render_editPageScreen();

                $this->content .= $editCurrentPageHTML;

                // Create sortables
                if (is_array($this->sortableContainers)) {
                    $script =
                        'var sortableSource = null' . "\n"
                        . 'var sortable_containers = ' . json_encode($this->sortableContainers) . ';' . "\n"
                        . 'var sortable_removeHidden = ' . ($this->MOD_SETTINGS['tt_content_showHidden'] !== '0' ? 'false;' : 'true;') . "\n"
                        . 'var sortable_linkParameters = \'' . $this->link_getParameters() . '\';';

                    $linkedTogether = json_encode(array_keys($this->sortableContainers));
                    $script .= 'require([\'jquery\', \'jquery-ui/sortable\'], function ($) {TYPO3.jQuery(function() {';
                    foreach ($this->sortableContainers as $key => $unused) {
                        $script .= "\n" . 'tv_createSortable(\'' . $key . '\',' . $linkedTogether . ');';
                    }
                    $script .= '});});';
                    $this->content .= GeneralUtility::wrapJS($script);
                }
            }

            // Additional footer content
            $this->content .= $this->renderFunctionHook('renderFooter');
        } else { // No access or no current page uid:
            if (!isset($pageInfoArr['uid'])) {
                $this->moduleTemplate->addFlashMessage(
                    TemplaVoilaUtility::getLanguageService()->getLL('page_not_found'),
                    TemplaVoilaUtility::getLanguageService()->getLL('title'),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::INFO
                );
            } else {
                $this->moduleTemplate->addFlashMessage(
                    TemplaVoilaUtility::getLanguageService()->getLL('default_introduction'),
                    TemplaVoilaUtility::getLanguageService()->getLL('title'),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::INFO
                );
            }
        }

        $cmd = GeneralUtility::_GP('cmd');
        if ($cmd == 'crPage') { // create a new page
            $this->content = $this->wizardsObj->renderWizard_createNewPage((int) GeneralUtility::_GP('positionPid'));
        }

        $this->moduleTemplate->setTitle(TemplaVoilaUtility::getLanguageService()->getLL('title'));
        if (is_array($pageInfoArr)) {
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageInfoArr);
            if ($cmd != 'crPage') {
                $this->moduleTemplate->getView()->assign('tabMenu', $this->render_sidebar());
                $this->setDocHeaderButtons();
            }
        }
        $this->moduleTemplate->setContent($this->content);
    }

    /*************************
     *
     * RENDERING UTILITIES
     *
     *************************/

    protected function addJsLibrary($key, $filename)
    {
        $this->getPageRenderer()->addJsLibrary(
            $key,
            $filename,
            null,
            !$this->debug, // Compress if not debug
            false,
            '',
            $this->debug // Exclude from concatenation if debug
        );
    }

    /**
     * Create the buttons for top bar
     */
    protected function setDocHeaderButtons()
    {
        // View page
        $this->addDocHeaderButton(
            'view',
            TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:' . $this->coreLangPath . 'locallang_core.xlf:labels.showPage', 1),
            'actions-document-view'
        );

        if (!$this->modTSconfig['properties']['disableIconToolbar']) {
            if (!$this->translatorMode) {
                if (TemplaVoilaUtility::getBackendUser()->isPSet($this->calcPerms, 'pages', 'new')) {
                    // Create new page (wizard)
                    $this->addDocHeaderButton(
                        'db_new',
                        TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newPage', 1),
                        'actions-page-new',
                        [
                            'id' => $this->id,
                            'pagesOnly' => 1,
                        ],
                        ButtonBar::BUTTON_POSITION_LEFT,
                        2
                    );
                }

                if (TemplaVoilaUtility::getBackendUser()->isPSet($this->calcPerms, 'pages', 'edit')) {
                    // Edit page properties
                    $this->addDocHeaderButton(
                        'record_edit',
                        TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:editPageProperties', 1),
                        'actions-page-open',
                        [
                            'edit' => [
                                'pages' => [
                                    $this->id => 'edit',
                                ],
                            ],
                        ]
                    );
                    // Move page
                    $this->addDocHeaderButton(
                        'move_element',
                        TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:move_page', 1),
                        'actions-page-move',
                        [
                            'table' => 'pages',
                            'uid'=> $this->id,
                        ],
                        ButtonBar::BUTTON_POSITION_LEFT,
                        2
                    );
                }
            }

            // Page history
            $this->addDocHeaderButton(
                'record_history',
                TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:recordHistory', 1),
                'actions-document-history-open',
                [
                    'element' => 'pages:' . $this->id,
                ],
                ButtonBar::BUTTON_POSITION_LEFT,
                3
            );

            $this->addCshButton('pagemodule');
        }

        $this->addShortcutButton();

        // If access to Web>List for user, then link to that module.
        if (TemplaVoilaUtility::getBackendUser()->check('modules', 'web_list')) {
            $this->addDocHeaderButton(
                'web_list',
                TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:' . $this->coreLangPath . 'locallang_core.xlf:labels.showList', 1),
                'actions-system-list-open',
                [
                    'id' => $this->id,
                ],
                ButtonBar::BUTTON_POSITION_RIGHT,
                1
            );
        }

        if ($this->id) {
            $this->addDocHeaderButton(
                'tce_db',
                TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:' . $this->coreLangPath . 'locallang_core.xlf:labels.clear_cache', 1),
                'actions-system-cache-clear',
                [
                    'cacheCmd'=> $this->id,
                    'redirect' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                ],
                ButtonBar::BUTTON_POSITION_RIGHT,
                2
            );
        }
    }

    /**
     * Builds a bootstrap button
     *
     * @param string $module
     * @param string $title
     * @param string $icon
     * @param array $params
     * @param string $buttonType Type of the html button, see bootstrap
     * @param string $extraClass Extra class names to add to the bootstrap button classes
     * @return string
     */
    public function buildButton($module, $title, $icon, $params = [], $buttonType = 'default', $extraClass = '')
    {
        global $BACK_PATH;

        $clickUrl = '';
        $rel = null;

        switch ($module) {
            case 'wizard_element_browser':
                $clickUrl = 'browserPos = this;setFormValueOpenBrowser('
                    . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl($module))
                    . ',\'db\',\'browser[communication]|||tt_content\'); return false;';
                $rel = BackendUtility::getModuleUrl($this->moduleName, $params);
                break;
            case 'view':
                $viewAddGetVars = $this->currentLanguageUid ? '&L=' . $this->currentLanguageUid : '';
                $clickUrl = htmlspecialchars(
                    BackendUtility::viewOnClick(
                        $this->id,
                        $BACK_PATH,
                        BackendUtility::BEgetRootLine($this->id),
                        '',
                        '',
                        $viewAddGetVars
                    )
                );
                break;
            default:
                $url = BackendUtility::getModuleUrl(
                    $module,
                    array_merge(
                        $params,
                        [
                            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                        ]
                    )
                );
                $clickUrl = 'jumpToUrl(' . GeneralUtility::quoteJSvalue($url) . ');return false;';
        }
        return $this->buildButtonFromUrl($clickUrl, $title, $icon, '', $buttonType, $extraClass, $rel);
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
        $url = '#';
        $onClick = null;

        switch ($module) {
            case 'view':
                $viewAddGetVars = $this->currentLanguageUid ? '&L=' . $this->currentLanguageUid : '';
                $onClick = BackendUtility::viewOnClick(
                    $this->id,
                    '',
                    BackendUtility::BEgetRootLine($this->id),
                    '',
                    '',
                    $viewAddGetVars
                );
                break;
            default:
                $url = BackendUtility::getModuleUrl(
                    $module,
                    array_merge(
                        $params,
                        [
                            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                        ]
                    )
                );
        }
        $button = $this->buttonBar->makeLinkButton()
            ->setHref($url)
            ->setOnClick($onClick)
            ->setTitle($title)
            ->setIcon($this->iconFactory->getIcon($icon, Icon::SIZE_SMALL));
        $this->buttonBar->addButton($button, $buttonPosition, $buttonGroup);
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
                    'M',
                    'edit_record',
                    'pointer',
                    'new_unique_uid',
                    'search_field',
                    'search_levels',
                    'showLimit'
                ]
            )
            ->setSetVariables(array_keys($this->MOD_MENU));
        $this->buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
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
        $clickUrl,
        $title,
        $icon,
        $text = '',
        $buttonType = 'default',
        $extraClass = '',
        $rel = null
    ) {
        return '<a href="#"' . ($rel ? ' rel="' . $rel . '"' : '')
            . ' class="btn btn-' . $buttonType . ' btn-sm' . ($extraClass ? ' ' . $extraClass : '') . '"'
            . ' onclick="' . $clickUrl . '" title="' . $title . '">'
            . $this->iconFactory->getIcon($icon, Icon::SIZE_SMALL)->render()
            . ($text ? ' ' . $text : '')
            . '</a>';
    }

    /********************************************
     *
     * Rendering functions
     *
     ********************************************/

    /**
     * Displays the default view of a page, showing the nested structure of elements.
     *
     * @return string The modules content
     * @access protected
     */
    public function render_editPageScreen()
    {
        global $TYPO3_CONF_VARS;

        $output = '';

        // Fetch the content structure of page:
        $contentTreeData = $this->apiObj->getContentTree($this->rootElementTable, $this->rootElementRecord); // TODO Dima: seems like it does not return <TCEForms> for elements inside sectiions. Thus titles are not visible for these elements!

        if (empty($contentTreeData['tree'])) {
            return TemplaVoilaUtility::getLanguageService()->getLL('info_selectTemplateDesign');
        }

        // Set internal variable which registers all used content elements:
        $this->global_tt_content_elementRegister = $contentTreeData['contentElementUsage'];

        // Setting localization mode for root element:
        $this->rootElementLangMode = $contentTreeData['tree']['ds_meta']['langDisable'] ? 'disable' : ($contentTreeData['tree']['ds_meta']['langChildren'] ? 'inheritance' : 'separate');
        $this->rootElementLangParadigm = ($this->modTSconfig['properties']['translationParadigm'] == 'free') ? 'free' : 'bound';

        // Create a back button if neccessary:
        if (is_array($this->altRoot)) {
            $output .= '<div style="text-align:right; width:100%; margin-bottom:5px;">'
            . $this->buildButton(
                $this->moduleName,
                TemplaVoilaUtility::getLanguageService()->getLL('goback'),
                'actions-view-go-back',
                [
                    'id' => $this->id,
                ]
            )
            . '</div>';
        }

        // Hook for content at the very top (fx. a toolbar):
        if (is_array($TYPO3_CONF_VARS['EXTCONF']['templavoilaplus']['mod1']['renderTopToolbar'])) {
            GeneralUtility::deprecationLog('TemplaVoila Plus: The Hook '
                . '$TYPO3_CONF_VARS[\'EXTCONF\'][\'templavoilaplus\'][\'mod1\'][\'renderTopToolbar\']'
                . 'is deprecated. Please use '
                . '$TYPO3_CONF_VARS[\'SC_OPTIONS\'][\'templavoilaplus\'][\'BackendLayout\'][\'renderEditPageHeaderFunctionHook\']'
                . 'This Hook will be removed with v8.'
            );

            foreach ($TYPO3_CONF_VARS['EXTCONF']['templavoilaplus']['mod1']['renderTopToolbar'] as $_funcRef) {
                $_params = array();
                $output .= GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }

        // Hook for content at the very top of editable page (fx. a toolbar):
        $output .= $this->renderFunctionHook('renderEditPageHeader');

        // We show a warning if the user may edit the pagecontent and is not permitted to edit the "content" fields at the same time
        if (!TemplaVoilaUtility::getBackendUser()->isAdmin() && $this->modTSconfig['properties']['enableContentAccessWarning']) {
            if (!($this->hasBasicEditRights())) {
                $this->moduleTemplate->addFlashMessage(
                    TemplaVoilaUtility::getLanguageService()->getLL('missing_edit_right_detail'),
                    TemplaVoilaUtility::getLanguageService()->getLL('missing_edit_right'),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::INFO
                );
            }
        }

        // Display the content as outline or the nested page structure:
        if ((TemplaVoilaUtility::getBackendUser()->isAdmin() || $this->modTSconfig['properties']['enableOutlineForNonAdmin'])
            && $this->MOD_SETTINGS['showOutline']
        ) {
            $output .= $this->render_outline($contentTreeData['tree']);
        } else {
            $output .= $this->render_framework_allSheets($contentTreeData['tree'], $this->currentLanguageKey);
        }

        // See http://bugs.typo3.org/view.php?id=4821
        $renderHooks = $this->hooks_prepareObjectsArray('render_editPageScreen');
        if (!empty($renderHooks)) {
            GeneralUtility::deprecationLog('TemplaVoila Plus: The Object Hook '
                . '$TYPO3_CONF_VARS[\'EXTCONF\'][\'templavoilaplus\'][\'mod1\'][\'render_editPageScreen\']'
                . 'is deprecated. Please use the Function Hook '
                . '$TYPO3_CONF_VARS[\'SC_OPTIONS\'][\'templavoilaplus\'][\'BackendLayout\'][\'renderEditPageFooterFunctionHook\']'
                . 'This Hook will be removed with v8.'
            );
        }
        foreach ($renderHooks as $hookObj) {
            if (method_exists($hookObj, 'render_editPageScreen_addContent')) {
                $output .= $hookObj->render_editPageScreen_addContent($this);
            }
        }

        // Hook for content at the very top of editable page (fx. a toolbar):
        $output .= $this->renderFunctionHook('renderEditPageFooter');

        return $output;
    }

    /*******************************************
     *
     * Framework rendering functions
     *
     *******************************************/

    /**
     * Rendering the sheet tabs if applicable for the content Tree Array
     *
     * @param array $contentTreeArr DataStructure info array (the whole tree)
     * @param string $languageKey Language key for the display
     * @param array $parentPointer Flexform Pointer to parent element
     * @param array $parentDsMeta Meta array from parent DS (passing information about parent containers localization mode)
     *
     * @return string HTML
     * @access protected
     * @see render_framework_singleSheet()
     */
    public function render_framework_allSheets($contentTreeArr, $languageKey = 'DEF', $parentPointer = array(), $parentDsMeta = array())
    {
        // If more than one sheet is available, render a dynamic sheet tab menu, otherwise just render the single sheet framework
        if (is_array($contentTreeArr['sub']) && (count($contentTreeArr['sub']) > 1 || !isset($contentTreeArr['sub']['sDEF']))) {
            $parts = array();
            foreach (array_keys($contentTreeArr['sub']) as $sheetKey) {
                $this->containedElementsPointer++;
                $this->containedElements[$this->containedElementsPointer] = 0;
                $frContent = $this->render_framework_singleSheet($contentTreeArr, $languageKey, $sheetKey, $parentPointer, $parentDsMeta);

                $parts[] = array(
                    'label' => ($contentTreeArr['meta'][$sheetKey]['title'] ? $contentTreeArr['meta'][$sheetKey]['title'] : $sheetKey), #.' ['.$this->containedElements[$this->containedElementsPointer].']',
                    'description' => $contentTreeArr['meta'][$sheetKey]['description'],
                    'linkTitle' => $contentTreeArr['meta'][$sheetKey]['short'],
                    'content' => $frContent,
                );

                $this->containedElementsPointer--;
            }

            return $this->moduleTemplate->getDynamicTabMenu($parts, 'TEMPLAVOILA:pagemodule:' . $this->apiObj->flexform_getStringFromPointer($parentPointer));
        } else {
            return $this->render_framework_singleSheet($contentTreeArr, $languageKey, 'sDEF', $parentPointer, $parentDsMeta);
        }
    }

    /**
     * Renders the display framework of a single sheet. Calls itself recursively
     *
     * @param array $contentTreeArr DataStructure info array (the whole tree)
     * @param string $languageKey Language key for the display
     * @param string $sheet The sheet key of the sheet which should be rendered
     * @param array $parentPointer Flexform pointer to parent element
     * @param array $parentDsMeta Meta array from parent DS (passing information about parent containers localization mode)
     *
     * @return string HTML
     * @access protected
     * @see render_framework_singleSheet()
     */
    public function render_framework_singleSheet($contentTreeArr, $languageKey, $sheet, $parentPointer = array(), $parentDsMeta = array())
    {
        $elementBelongsToCurrentPage = false;
        $pid = $contentTreeArr['el']['table'] == 'pages' ? $contentTreeArr['el']['uid'] : $contentTreeArr['el']['pid'];
        if ($contentTreeArr['el']['table'] == 'pages' || $contentTreeArr['el']['pid'] == $this->rootElementUid_pidForContent) {
            $elementBelongsToCurrentPage = true;
        } else {
            if ($contentTreeArr['el']['_ORIG_uid']) {
                $record = BackendUtility::getMovePlaceholder('tt_content', $contentTreeArr['el']['uid']);
                if (is_array($record) && $record['t3ver_move_id'] == $contentTreeArr['el']['uid']) {
                    $elementBelongsToCurrentPage = $this->rootElementUid_pidForContent == $record['pid'];
                    $pid = $record['pid'];
                }
            }
        }
        $calcPerms = $this->getCalcPerms($pid);

        $canEditElement = TemplaVoilaUtility::getBackendUser()->isPSet($calcPerms, 'pages', 'editcontent');
        $canEditContent = TemplaVoilaUtility::getBackendUser()->isPSet($this->calcPerms, 'pages', 'editcontent');

        $elementClass = 'tpm-container-element';
        $elementClass .= ' tpm-container-element-depth-' . $contentTreeArr['depth'];
        $elementClass .= ' tpm-container-element-depth-' . ($contentTreeArr['depth'] % 2 ? 'odd' : 'even');

        $recordIcon = '<span ' . BackendUtility::getRecordToolTip($contentTreeArr['el'], $contentTreeArr['el']['table']) . '>'
            . $contentTreeArr['el']['iconTag']
            . '</span>';

        $menuCommands = array();
        if (TemplaVoilaUtility::getBackendUser()->isPSet($calcPerms, 'pages', 'new')) {
            $menuCommands[] = 'new';
        }
        if ($canEditContent) {
            $menuCommands[] = 'copy,cut,pasteinto,pasteafter,delete';
        } else {
            $menuCommands[] = 'copy';
        }

        $titleBarLeftButtons = $this->translatorMode ? $recordIcon : (count($menuCommands) == 0 ? $recordIcon : BackendUtility::wrapClickMenuOnIcon($recordIcon, $contentTreeArr['el']['table'], $contentTreeArr['el']['uid'], true, '', implode(',', $menuCommands)));
        $titleBarLeftButtons .= $this->getRecordStatHookValue($contentTreeArr['el']['table'], $contentTreeArr['el']['uid']);
        $titleBarLeftButtons = '<span class="tpm-buttonsLeft">' . $titleBarLeftButtons . '</span>';

        unset($menuCommands);

        $languageUid = 0;
        $elementTitlebarClass = '';
        $titleBarRightButtons = '';
        // Prepare table specific settings:
        switch ($contentTreeArr['el']['table']) {
            case 'pages':
                $elementTitlebarClass = 't3-page-column-header';
                $elementClass .= ' pagecontainer';
                $languageUid = $this->currentLanguageUid;
                if ($this->currentLanguageUid !== 0) {
                    $row = BackendUtility::getRecordLocalization($contentTreeArr['el']['table'], $contentTreeArr['el']['uid'], $this->currentLanguageUid);
                    if ($row) {
                        $contentTreeArr['el']['fullTitle'] = BackendUtility::getRecordTitle('pages', $row[0]);
                    }
                }
                break;

            case 'tt_content':
                $this->currentElementParentPointer = $parentPointer;

                $elementTitlebarClass = 't3-page-ce-header '
                    . ($elementBelongsToCurrentPage ? 'tpm-titlebar' : 'tpm-titlebar-fromOtherPage');
                $elementClass .= ' t3-page-ce tpm-content-element tpm-ctype-' . $contentTreeArr['el']['CType'] . ' tpm-layout-' . $contentTreeArr['el']['layout'];

                if ($contentTreeArr['el']['isHidden']) {
                    $elementClass .= ' tpm-hidden t3-page-ce-hidden';
                }
                if ($contentTreeArr['el']['CType'] == 'templavoilaplus_pi1') {
                    //fce
                    $elementClass .= ' tpm-fce tpm-fce_' . (int)$contentTreeArr['el']['TO'];
                }

                $languageUid = $contentTreeArr['el']['sys_language_uid'];
                $elementPointer = 'tt_content:' . $contentTreeArr['el']['uid'];

                $linkCopy = $this->clipboardObj->element_getSelectButtons($parentPointer, 'copy,ref');

                if (!$this->translatorMode) {
                    if ($canEditContent) {
                        // array('title' => TemplaVoilaUtility::getLanguageService()->getLL('makeLocal'));
                        $iconMakeLocal = $this->iconFactory->getIcon('extensions-templavoila-makelocalcopy', Icon::SIZE_SMALL)->render();
                        $linkMakeLocal = !$elementBelongsToCurrentPage && !in_array('makeLocal', $this->blindIcons) ? $this->link_makeLocal($iconMakeLocal, $parentPointer) : '';
                        $linkCut = $this->clipboardObj->element_getSelectButtons($parentPointer, 'cut');
                        if ($this->modTSconfig['properties']['enableDeleteIconForLocalElements'] < 2 ||
                            !$elementBelongsToCurrentPage ||
                            $this->global_tt_content_elementRegister[$contentTreeArr['el']['uid']] > 1
                        ) {
                            $linkUnlink = !in_array('unlink', $this->blindIcons) ? $this->link_unlink('extensions-templavoila-unlink', TemplaVoilaUtility::getLanguageService()->getLL('unlinkRecord'), $parentPointer, false, false, $elementPointer) : '';
                        } else {
                            $linkUnlink = '';
                        }
                    } else {
                        $linkMakeLocal = $linkCut = $linkUnlink = '';
                    }

                    if ($canEditElement && TemplaVoilaUtility::getBackendUser()->recordEditAccessInternals('tt_content', $contentTreeArr['previewData']['fullRow'])) {
                        if (($elementBelongsToCurrentPage || $this->modTSconfig['properties']['enableEditIconForRefElements']) && !in_array('edit', $this->blindIcons)) {
                            $linkEdit = $this->buildButtonEdit($contentTreeArr['el']);
                        } else {
                            $linkEdit = '';
                        }
                        $linkHide = !in_array('hide', $this->blindIcons) ? $this->buildButtonHide($contentTreeArr['el']) : '';

                        if ($canEditContent && $this->modTSconfig['properties']['enableDeleteIconForLocalElements'] && $elementBelongsToCurrentPage) {
                            $hasForeignReferences = TemplaVoilaUtility::hasElementForeignReferences($contentTreeArr['el'], $contentTreeArr['el']['pid']);
                            $linkDelete = !in_array('delete', $this->blindIcons) ? $this->link_unlink('actions-edit-delete', TemplaVoilaUtility::getLanguageService()->getLL('deleteRecord'), $parentPointer, true, $hasForeignReferences, $elementPointer) : '';
                        } else {
                            $linkDelete = '';
                        }
                    } else {
                        $linkDelete = $linkEdit = $linkHide = '';
                    }
                    $titleBarRightButtons = $linkEdit . $linkHide . $linkCopy . $linkCut . $linkMakeLocal . $linkUnlink . $linkDelete;
                } else {
                    $titleBarRightButtons = $linkCopy;
                }
                break;
        }

        // Prepare the language icon:
        $languageLabel = htmlspecialchars($this->allAvailableLanguages[$languageUid]['title']);
        if ($this->allAvailableLanguages[$languageUid]['flagIcon']) {
            $languageIcon = \Ppi\TemplaVoilaPlus\Utility\IconUtility::getFlagIconForLanguage(
                $this->allAvailableLanguages[$languageUid]['flagIcon'],
                [
                    'title' => $languageLabel,
                    'alt' => $languageLabel
                ]
            );
        } else {
            $languageIcon = ($languageLabel && $languageUid ? '[' . $languageLabel . ']' : '');
        }

        $languageIcon = '<span class="tpm-langIcon">'
            . $this->link_edit($languageIcon, $contentTreeArr['el']['table'], $contentTreeArr['el']['uid'], true, $contentTreeArr['el']['pid'], '')
            . '</span>';

        // Create warning messages if neccessary:
        $warnings = '';

        if (!$this->modTSconfig['properties']['disableReferencedElementNotification'] && !$elementBelongsToCurrentPage) {
            $warnings .= $this->moduleTemplate->icons(1) . ' <em>' . htmlspecialchars(sprintf(TemplaVoilaUtility::getLanguageService()->getLL('info_elementfromotherpage'), $contentTreeArr['el']['uid'], $contentTreeArr['el']['pid'])) . '</em><br />';
        }

        if (!$this->modTSconfig['properties']['disableElementMoreThanOnceWarning'] && $this->global_tt_content_elementRegister[$contentTreeArr['el']['uid']] > 1 && $this->rootElementLangParadigm != 'free') {
            $warnings .= $this->moduleTemplate->icons(2) . ' <em>' . htmlspecialchars(sprintf(TemplaVoilaUtility::getLanguageService()->getLL('warning_elementusedmorethanonce'), $this->global_tt_content_elementRegister[$contentTreeArr['el']['uid']], $contentTreeArr['el']['uid'])) . '</em><br />';
        }

        // Displaying warning for container content (in default sheet - a limitation) elements if localization is enabled:
        $isContainerEl = false;
        if (isset($contentTreeArr['sub']['sDEF']) && !empty($contentTreeArr['sub']['sDEF'])) {
            $isContainerEl = true;
        }
        if (!$this->modTSconfig['properties']['disableContainerElementLocalizationWarning'] && $this->rootElementLangParadigm != 'free' && $isContainerEl && $contentTreeArr['el']['table'] === 'tt_content' && $contentTreeArr['el']['CType'] === 'templavoilaplus_pi1' && !$contentTreeArr['ds_meta']['langDisable']) {
            if ($contentTreeArr['ds_meta']['langChildren']) {
                if (!$this->modTSconfig['properties']['disableContainerElementLocalizationWarning_warningOnly']) {
                    $warnings .= $this->moduleTemplate->icons(2) . ' <em>' . TemplaVoilaUtility::getLanguageService()->getLL('warning_containerInheritance') . '</em><br />';
                }
            } else {
                $warnings .= $this->moduleTemplate->icons(3) . ' <em>' . TemplaVoilaUtility::getLanguageService()->getLL('warning_containerSeparate') . '</em><br />';
            }
        }

        // Preview made:
        $previewContent = $contentTreeArr['ds_meta']['disableDataPreview'] ? '&nbsp;' : $this->render_previewData($contentTreeArr['previewData'], $contentTreeArr['el'], $contentTreeArr['ds_meta'], $languageKey, $sheet);

        // Wrap workspace notification colors:
        if ($contentTreeArr['el']['_ORIG_uid']) {
            $elementTitlebarClass .= ' ver-element';
        }

        $title = GeneralUtility::fixed_lgd_cs($contentTreeArr['el']['fullTitle'], $this->previewTitleMaxLen);

        // Finally assemble the table:
        $finalContent = '
            <div class="' . $elementClass . '">
                <a name="c' . md5($this->apiObj->flexform_getStringFromPointer($this->currentElementParentPointer) . $contentTreeArr['el']['uid']) . '"></a>
                <div class="tpm-titlebar ' . $elementTitlebarClass . '">
                    <div class="t3-page-ce-header-icons-right">
                    ' . $titleBarRightButtons . '
                    </div>
                    <div class="t3-page-ce-header-icons-left">'
                     . $languageIcon . ' ' . $titleBarLeftButtons . ' '
                    . '<div class="nobr sortable_handle ui-sortable-handle">' .
            ($elementBelongsToCurrentPage ? '' : '<em>') . htmlspecialchars($title) . ($elementBelongsToCurrentPage ? '' : '</em>') .
                    '</div>
                    </div>
                </div>
                <div class="t3-page-ce-body tpm-sub-elements">' .
                    ($warnings ? '<div class="tpm-warnings">' . $warnings . '</div>' : '') .
                    $this->render_framework_subElements($contentTreeArr, $languageKey, $sheet, $calcPerms) .
                    '<div class="tpm-preview">' . $previewContent . '</div>' .
                    $this->render_localizationInfoTable($contentTreeArr, $parentPointer, $parentDsMeta) .
                '</div>
            </div>';

        return $finalContent;
    }

    /**
     * Renders the sub elements of the given elementContentTree array. This function basically
     * renders the "new" and "paste" buttons for the parent element and then traverses through
     * the sub elements (if any exist). The sub element's (preview-) content will be rendered
     * by render_framework_singleSheet().
     *
     * Calls render_framework_allSheets() and therefore generates a recursion.
     *
     * @param array $elementContentTreeArr Content tree starting with the element which possibly has sub elements
     * @param string $languageKey Language key for current display
     * @param string $sheet Key of the sheet we want to render
     * @param integer $calcPerms Defined the access rights for the enclosing parent
     *
     * @throws RuntimeException
     *
     * @return string HTML output (a table) of the sub elements and some "insert new" and "paste" buttons
     * @access protected
     * @see render_framework_allSheets(), render_framework_singleSheet()
     */
    public function render_framework_subElements($elementContentTreeArr, $languageKey, $sheet, $calcPerms = 0)
    {
        $beTemplate = '';

        $canEditContent = TemplaVoilaUtility::getBackendUser()->isPSet($calcPerms, 'pages', 'editcontent');

        // Define l/v keys for current language:
        $langChildren = (int)$elementContentTreeArr['ds_meta']['langChildren'];
        $langDisable = (int)$elementContentTreeArr['ds_meta']['langDisable'];

        $lKey = $this->determineFlexLanguageKey($langDisable, $langChildren, $languageKey);
        $vKey = $this->determineFlexValueKey($langDisable, $langChildren, $languageKey);
        if ($elementContentTreeArr['el']['table'] == 'pages' && $langDisable != 1 && $langChildren == 1) {
            if ($this->disablePageStructureInheritance($elementContentTreeArr, $sheet, $lKey, $vKey)) {
                $lKey = $this->determineFlexLanguageKey(1, $langChildren, $languageKey);
                $vKey = $this->determineFlexValueKey(1, $langChildren, $languageKey);
            } else {
                if (!TemplaVoilaUtility::getBackendUser()->isAdmin()) {
                    $this->moduleTemplate->addFlashMessage(
                        TemplaVoilaUtility::getLanguageService()->getLL('page_structure_inherited_detail'),
                        TemplaVoilaUtility::getLanguageService()->getLL('page_structure_inherited'),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::INFO
                    );
                }
            }
        }

        if (!is_array($elementContentTreeArr['sub'][$sheet]) || !is_array($elementContentTreeArr['sub'][$sheet][$lKey])) {
            return '';
        }

        $output = '';
        $cells = array();

        // get used TO
        if (isset($elementContentTreeArr['el']['TO']) && (int)$elementContentTreeArr['el']['TO']) {
            $toRecord = BackendUtility::getRecordWSOL('tx_templavoilaplus_tmplobj', (int)$elementContentTreeArr['el']['TO']);
        } else {
            $toRecord = $this->apiObj->getContentTree_fetchPageTemplateObject($this->rootElementRecord);
        }

        try {
            $toRepo = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Domain\Repository\TemplateRepository::class);
            /** @var $toRepo \Ppi\TemplaVoilaPlus\Domain\Repository\TemplateRepository */
            $to = $toRepo->getTemplateByUid($toRecord['uid']);
            /** @var $to \Ppi\TemplaVoilaPlus\Domain\Model\Template */
            $beTemplate = $to->getBeLayout();
        } catch (InvalidArgumentException $e) {
            $to = null;
            // might happen if uid was not what the Repo expected - that's ok here
        }

        if (!$to instanceof \Ppi\TemplaVoilaPlus\Domain\Model\Template) {
            throw new \RuntimeException('Further execution of code leads to PHP errors.', 1404750505);
        }

        if ($beTemplate === false && isset($elementContentTreeArr['ds_meta']['beLayout'])) {
            $beTemplate = $elementContentTreeArr['ds_meta']['beLayout'];
        }

        // no layout, no special rendering
        $flagRenderBeLayout = $beTemplate ? true : false;

        // Traverse container fields:
        foreach ($elementContentTreeArr['sub'][$sheet][$lKey] as $fieldID => $fieldValuesContent) {
            try {
                $newValue = $to->getLocalDataprotValueByXpath('//' . $fieldID . '/tx_templavoilaplus/preview');
                $elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['tx_templavoilaplus']['preview'] = $newValue;
            } catch (\UnexpectedValueException $e) {
            }

            if (is_array($fieldValuesContent[$vKey]) && (
                    $elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['isMapped'] ||
                    $elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['type'] == 'no_map'
                ) &&
                $elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['tx_templavoilaplus']['preview'] != 'disable'
            ) {
                $fieldContent = $fieldValuesContent[$vKey];

                $cellContent = '';

                // Create flexform pointer pointing to "before the first sub element":
                $subElementPointer = array(
                    'table' => $elementContentTreeArr['el']['table'],
                    'uid' => $elementContentTreeArr['el']['uid'],
                    'sheet' => $sheet,
                    'sLang' => $lKey,
                    'field' => $fieldID,
                    'vLang' => $vKey,
                    'position' => 0
                );

                $maxItemsReached = false;
                if (isset($elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['TCEforms']['config']['maxitems'])) {
                    $maxCnt = (int)$elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['TCEforms']['config']['maxitems'];
                    $maxItemsReached = is_array($fieldContent['el_list']) && count($fieldContent['el_list']) >= $maxCnt;

                    if ($maxItemsReached) {
                        $this->moduleTemplate->addFlashMessage(
                            sprintf(
                                TemplaVoilaUtility::getLanguageService()->getLL('maximal_content_elements'),
                                $maxCnt,
                                $elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['TCEforms']['label']
                            ),
                            '',
                            \TYPO3\CMS\Core\Messaging\FlashMessage::INFO
                        );
                    }
                }

                $canCreateNew = $canEditContent && !$maxItemsReached;

                $canDragDrop = !$maxItemsReached && $canEditContent &&
                    $elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['tx_templavoilaplus']['enableDragDrop'] !== '0' &&
                    $this->modTSconfig['properties']['enableDragDrop'] !== '0';

                if (!$this->translatorMode && $canCreateNew) {
                    $cellContent .= $this->link_bottomControls($subElementPointer, $canCreateNew);
                }

                // Render the list of elements (and possibly call itself recursively if needed):
                if (is_array($fieldContent['el_list'])) {
                    foreach ($fieldContent['el_list'] as $position => $subElementKey) {
                        $subElementArr = $fieldContent['el'][$subElementKey];

                        if ((!$subElementArr['el']['isHidden'] || $this->MOD_SETTINGS['tt_content_showHidden'] !== '0') && $this->displayElement($subElementArr)) {
                            // When "onlyLocalized" display mode is set and an alternative language gets displayed
                            if (($this->MOD_SETTINGS['langDisplayMode'] == 'onlyLocalized') && $this->currentLanguageUid > 0) {
                                // Default language element. Subsitute displayed element with localized element
                                if (($subElementArr['el']['sys_language_uid'] == 0) && is_array($subElementArr['localizationInfo'][$this->currentLanguageUid]) && ($localizedUid = $subElementArr['localizationInfo'][$this->currentLanguageUid]['localization_uid'])) {
                                    $localizedRecord = BackendUtility::getRecordWSOL('tt_content', $localizedUid, '*');
                                    $tree = $this->apiObj->getContentTree('tt_content', $localizedRecord);
                                    $subElementArr = $tree['tree'];
                                }
                            }
                            $this->containedElements[$this->containedElementsPointer]++;

                            // Modify the flexform pointer so it points to the position of the curren sub element:
                            $subElementPointer['position'] = $position;

                            if (!$this->translatorMode) {
                                $cellContent .= '<div' . ($canDragDrop ? ' class="sortableItem tpm-element"' : ' class="tpm-element"') . ' id="' . $this->getSortableItemHash($this->apiObj->flexform_getStringFromPointer($subElementPointer)) . '">';
                            }

                            $cellContent .= $this->render_framework_allSheets($subElementArr, $languageKey, $subElementPointer, $elementContentTreeArr['ds_meta']);

                            if (!$this->translatorMode && $canCreateNew) {
                                $cellContent .= $this->link_bottomControls($subElementPointer, $canCreateNew);
                            }

                            if (!$this->translatorMode) {
                                $cellContent .= '</div>';
                            }
                        } else {
                            // Modify the flexform pointer so it points to the position of the curren sub element:
                            $subElementPointer['position'] = $position;

                            $cellId = $this->getSortableItemHash($this->apiObj->flexform_getStringFromPointer($subElementPointer));
                            $cellFragment = '<div' . ($canDragDrop ? ' class="sortableItem tpm-element"' : ' class="tpm-element"') . ' id="' . $cellId . '"></div>';

                            $cellContent .= $cellFragment;
                        }
                    }
                }

                $tmpArr = $subElementPointer;
                unset($tmpArr['position']);
                $cellId = $this->getSortableItemHash($this->apiObj->flexform_getStringFromPointer($tmpArr));
                $cellIdStr = ' id="' . $cellId . '"';

                $this->sortableContainers['#' . $cellId] = $this->apiObj->flexform_getStringFromPointer($tmpArr);

                // Add cell content to registers:
                if ($flagRenderBeLayout == true) {
                    $beTemplateCell = '<div class="t3-page-column-header">
                    <div class="t3-page-column-header-label">' . TemplaVoilaUtility::getLanguageService()->sL($fieldContent['meta']['title'], 1) . '</div>
                    </div>
                    <div ' . $cellIdStr . ' class="t3-page-ce-wrapper">
                    ' . $cellContent . '
                    </div>';


                    $beTemplate = str_replace('###' . $fieldID . '###', $beTemplateCell, $beTemplate);
                } else {
                    $width = round(100 / count($elementContentTreeArr['sub'][$sheet][$lKey]));
                    $cells[] = array(
                        'id' => $cellId,
                        'idStr' => $cellIdStr,
                        'title' => TemplaVoilaUtility::getLanguageService()->sL($fieldContent['meta']['title'], 1),
                        'width' => $width,
                        'content' => $cellContent
                    );
                }
            }
        }

        if ($flagRenderBeLayout) {
            //replace lang markers
            $beTemplate = preg_replace_callback(
                "/###(LLL:[\w\-\/:]+?\.xml\:[\w\-\.]+?)###/",
                function($matches) {
                    return $GLOBALS["LANG"]->sL($matches[1], 1);
                },
                $beTemplate
            );

            // removes not used markers
            $beTemplate = preg_replace("/###field_.*?###/", '', $beTemplate);

            return $beTemplate;
        }

        // Compile the content area for the current element
        if (count($cells)) {
            $hookObjectsArr = $this->hooks_prepareObjectsArray('renderFrameworkClass');
            $alreadyRendered = false;
            $output = '';
            foreach ($hookObjectsArr as $hookObj) {
                if (method_exists($hookObj, 'composeSubelements')) {
                    $hookObj->composeSubelements($cells, $elementContentTreeArr, $output, $alreadyRendered, $this);
                }
            }

            if (!$alreadyRendered) {
                $headerCells = $contentCells = array();
                foreach ($cells as $cell) {
                    $headerCells[] = vsprintf('<td width="%4$d%%" class="bgColor6 tpm-title-cell">%3$s</td>', $cell);
                    $contentCells[] = vsprintf('<td %2$s width="%4$d%%" class="tpm-content-cell">%5$s</td>', $cell);
                }

                $output = '
                    <table border="0" cellpadding="2" cellspacing="2" width="100%" class="tpm-subelement-table">
                        <tr>' . (count($headerCells) ? implode('', $headerCells) : '<td>&nbsp;</td>') . '</tr>
                        <tr>' . (count($contentCells) ? implode('', $contentCells) : '<td>&nbsp;</td>') . '</tr>
                    </table>
                ';
            }
        }

        return $output;
    }

    /**
     * @param string $langDisable
     * @param string $langChildren
     * @param string $languageKey
     *
     * @return string
     */
    protected function determineFlexLanguageKey($langDisable, $langChildren, $languageKey)
    {
        return $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l' . $languageKey);
    }

    /**
     * @param boolean $langDisable
     * @param string $langChildren
     * @param string $languageKey
     *
     * @return string
     */
    protected function determineFlexValueKey($langDisable, $langChildren, $languageKey)
    {
        return $langDisable ? 'vDEF' : ($langChildren ? 'v' . $languageKey : 'vDEF');
    }

    /**
     * @param array $elementContentTreeArr
     * @param string $sheet
     * @param string $lKey
     * @param string $vKey
     *
     * @return boolean
     */
    protected function disablePageStructureInheritance($elementContentTreeArr, $sheet, $lKey, $vKey)
    {
        $disable = false;
        if (TemplaVoilaUtility::getBackendUser()->isAdmin()) {
            //if page DS and the checkbox is not set use always langDisable in inheritance mode
            $disable = $this->MOD_SETTINGS['disablePageStructureInheritance'] != '1';
        } else {
            $hasLocalizedValues = false;
            $adminOnly = $this->modTSconfig['properties']['adminOnlyPageStructureInheritance'];
            if ($adminOnly == 'strict') {
                $disable = true;
            } else {
                if ($adminOnly == 'fallback' && isset($elementContentTreeArr['sub'][$sheet][$lKey])) {
                    foreach ($elementContentTreeArr['previewData']['sheets'][$sheet] as $fieldData) {
                        $hasLocalizedValues |= isset($fieldData['data'][$lKey][$vKey])
                            && ($fieldData['data'][$lKey][$vKey] != null)
                            && ($fieldData['isMapped'] == true)
                            && (!isset($fieldData['TCEforms']['displayCond']) || $fieldData['TCEforms']['displayCond'] != 'HIDE_L10N_SIBLINGS');
                    }
                } else {
                    if ($adminOnly == 'false') {
                        $disable = $this->MOD_SETTINGS['disablePageStructureInheritance'] != '1';
                    }
                }
            }
            // we disable it if the path wasn't already created (by an admin)
            $disable |= !$hasLocalizedValues;
        }

        return $disable;
    }

    /*******************************************
     *
     * Rendering functions for certain subparts
     *
     *******************************************/

    /**
     * Rendering the preview of content for Page module.
     *
     * @param array $previewData Array with data from which a preview can be rendered.
     * @param array $elData Element data
     * @param array $ds_meta Data Structure Meta data
     * @param string $languageKey Current language key (so localized content can be shown)
     * @param string $sheet Sheet key
     *
     * @return string HTML content
     */
    public function render_previewData($previewData, $elData, $ds_meta, $languageKey, $sheet)
    {

        $this->currentElementBelongsToCurrentPage = $elData['table'] == 'pages' || $elData['pid'] == $this->rootElementUid_pidForContent;

        // General preview of the row:
        $previewContent = is_array($previewData['fullRow']) && $elData['table'] == 'tt_content' ? $this->render_previewContent($previewData['fullRow']) : '';

        // Preview of FlexForm content if any:
        if (is_array($previewData['sheets'][$sheet])) {
            // Define l/v keys for current language:
            $langChildren = (int)$ds_meta['langChildren'];
            $langDisable = (int)$ds_meta['langDisable'];
            $lKey = $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l' . $languageKey);
            $vKey = $langDisable ? 'vDEF' : ($langChildren ? 'v' . $languageKey : 'vDEF');

            foreach ($previewData['sheets'][$sheet] as $fieldData) {
                if (isset($fieldData['tx_templavoilaplus']['preview']) && $fieldData['tx_templavoilaplus']['preview'] == 'disable') {
                    continue;
                }

                $TCEformsConfiguration = $fieldData['TCEforms']['config'];
                $TCEformsLabel = $this->localizedFFLabel($fieldData['TCEforms']['label'], 1); // title for non-section elements

                if ($fieldData['type'] == 'array') { // Making preview for array/section parts of a FlexForm structure:;
                    if (is_array($fieldData['childElements'][$lKey])) {
                        $subData = $this->render_previewSubData($fieldData['childElements'][$lKey], $elData['table'], $previewData['fullRow']['uid'], $vKey);
                        $previewContent .= $this->link_edit($subData, $elData['table'], $previewData['fullRow']['uid']);
                    } else {
                        // no child elements found here
                    }
                } else { // Preview of flexform fields on top-level:
                    $fieldValue = $fieldData['data'][$lKey][$vKey];

                    if ($TCEformsConfiguration['type'] == 'group') {
                        if ($TCEformsConfiguration['internal_type'] == 'file') {
                            // Render preview for images:
                            $thumbnail = BackendUtility::thumbCode(array('dummyFieldName' => $fieldValue), '', 'dummyFieldName', '', '', $TCEformsConfiguration['uploadfolder']);
                            $previewContent .= '<strong>' . $TCEformsLabel . '</strong> ' . $thumbnail . '<br />';
                        } elseif ($TCEformsConfiguration['internal_type'] === 'db') {
                            if (!$this->renderPreviewDataObjects) {
                                $this->renderPreviewDataObjects = $this->hooks_prepareObjectsArray('renderPreviewDataClass');
                            }
                            if (isset($this->renderPreviewDataObjects[$TCEformsConfiguration['allowed']])
                                && method_exists($this->renderPreviewDataObjects[$TCEformsConfiguration['allowed']], 'render_previewData_typeDb')
                            ) {
                                $previewContent .= $this->renderPreviewDataObjects[$TCEformsConfiguration['allowed']]->render_previewData_typeDb($fieldValue, $fieldData, $previewData['fullRow']['uid'], $elData['table'], $this);
                            }
                        }
                    } else {
                        if ($TCEformsConfiguration['type'] != '') {
                            // Render for everything else:
                            $previewContent .= '<strong>' . $TCEformsLabel . '</strong> ' . (!$fieldValue ? '' : $this->link_edit(htmlspecialchars(GeneralUtility::fixed_lgd_cs(strip_tags($fieldValue), 200)), $elData['table'], $previewData['fullRow']['uid'])) . '<br />';
                        }
                    }
                }
            }
        }

        return $previewContent;
    }

    /**
     * Merge the datastructure and the related content into a proper tree-structure
     *
     * @param array $fieldData
     * @param string $table
     * @param integer $uid
     * @param string $vKey
     *
     * @return string
     */
    public function render_previewSubData($fieldData, $table, $uid, $vKey)
    {
        if (!is_array($fieldData)) {
            return '';
        }

        $result = '';
        foreach ($fieldData as $fieldValue) {
            if (isset($fieldValue['config']['tx_templavoilaplus']['preview']) && $fieldValue['config']['tx_templavoilaplus']['preview'] == 'disable') {
                continue;
            }

            if ($fieldValue['config']['type'] == 'array') {
                if (isset($fieldValue['data']['el'])) {
                    if ($fieldValue['config']['section']) {
                        $result .= '<strong>';
                        $label = ($fieldValue['config']['TCEforms']['label'] ? $fieldValue['config']['TCEforms']['label'] : $fieldValue['config']['title']);
                        $result .= $this->localizedFFLabel($label, 1);
                        $result .= '</strong>';
                        $result .= '<ul>';
                        foreach ($fieldValue['data']['el'] as $sub) {
                            $data = $this->render_previewSubData($sub, $table, $uid, $vKey);
                            if ($data) {
                                $result .= '<li>' . $data . '</li>';
                            }
                        }
                        $result .= '</ul>';
                    } else {
                        $result .= $this->render_previewSubData($fieldValue['data']['el'], $table, $uid, $vKey);
                    }
                }
            } else {
                $label = $data = '';
                if (isset($fieldValue['config']['TCEforms']['config']['type']) && $fieldValue['config']['TCEforms']['config']['type'] == 'group') {
                    if ($fieldValue['config']['TCEforms']['config']['internal_type'] == 'file') {
                        // Render preview for images:
                        $thumbnail = BackendUtility::thumbCode(array('dummyFieldName' => $fieldValue['data'][$vKey]), '', 'dummyFieldName', '', '', $fieldValue['config']['TCEforms']['config']['uploadfolder']);
                        if (isset($fieldValue['config']['TCEforms']['label'])) {
                            $label = $this->localizedFFLabel($fieldValue['config']['TCEforms']['label'], 1);
                        }
                        $data = $thumbnail;
                    }
                } else {
                    if (isset($fieldValue['config']['TCEforms']['config']['type']) && $fieldValue['config']['TCEforms']['config']['type'] != '') {
                        // Render for everything else:
                        if (isset($fieldValue['config']['TCEforms']['label'])) {
                            $label = $this->localizedFFLabel($fieldValue['config']['TCEforms']['label'], 1);
                        }
                        $data = (!$fieldValue['data'][$vKey] ? '' : $this->link_edit(htmlspecialchars(GeneralUtility::fixed_lgd_cs(strip_tags($fieldValue['data'][$vKey]), 200)), $table, $uid));
                    } else {
                        // @todo no idea what we should to here
                    }
                }

                if ($label && $data) {
                    $result .= '<strong>' . $label . '</strong> ' . $data . '<br />';
                }
            }
        }

        return $result;
    }

    /**
     * Returns an HTMLized preview of a certain content element. If you'd like to register a new content type, you can easily use the hook
     * provided at the beginning of the function.
     *
     * @param array $row The row of tt_content containing the content element record.
     *
     * @return string HTML preview content
     * @access protected
     * @see getContentTree(), render_localizationInfoTable()
     */
    public function render_previewContent($row)
    {
        $output = '';
        $hookObjectsArr = $this->hooks_prepareObjectsArray('renderPreviewContentClass');
        $alreadyRendered = false;
        // Hook: renderPreviewContent_preProcess. Set 'alreadyRendered' to true if you provided a preview content for the current cType !
        foreach ($hookObjectsArr as $hookObj) {
            if (method_exists($hookObj, 'renderPreviewContent_preProcess')) {
                $output .= $hookObj->renderPreviewContent_preProcess($row, 'tt_content', $alreadyRendered, $this);
            }
        }

        if (!$alreadyRendered) {
            if (!$this->renderPreviewObjects) {
                $this->renderPreviewObjects = $this->hooks_prepareObjectsArray('renderPreviewContent');
            }

            if (isset($this->renderPreviewObjects[$row['CType']]) && method_exists($this->renderPreviewObjects[$row['CType']], 'render_previewContent')) {
                $output .= $this->renderPreviewObjects[$row['CType']]->render_previewContent($row, 'tt_content', $output, $alreadyRendered, $this);
            } elseif (isset($this->renderPreviewObjects['default']) && method_exists($this->renderPreviewObjects['default'], 'render_previewContent')) {
                $output .= $this->renderPreviewObjects['default']->render_previewContent($row, 'tt_content', $output, $alreadyRendered, $this);
            } else {
                // nothing is left to render the preview - happens if someone broke the configuration
            }
        }

        return $output;
    }

    /**
     * Renders a little table containing previews of translated version of the current content element.
     *
     * @param array $contentTreeArr Part of the contentTreeArr for the element
     * @param string $parentPointer Flexform pointer pointing to the current element (from the parent's perspective)
     * @param array $parentDsMeta Meta array from parent DS (passing information about parent containers localization mode)
     *
     * @return string HTML
     * @access protected
     * @see render_framework_singleSheet()
     */
    public function render_localizationInfoTable($contentTreeArr, $parentPointer, $parentDsMeta = array())
    {
        // LOCALIZATION information for content elements (non Flexible Content Elements)
        $output = '';
        if ($contentTreeArr['el']['table'] == 'tt_content' && $contentTreeArr['el']['sys_language_uid'] <= 0) {
            // Traverse the available languages of the page (not default and [All])
            $tRows = array();
            foreach ($this->translatedLanguagesArr as $sys_language_uid => $sLInfo) {
                if ($this->MOD_SETTINGS['langDisplayMode'] && ($this->currentLanguageUid != $sys_language_uid)) {
                    continue;
                }
                if ($sys_language_uid > 0) {
                    $l10nInfo = '';
                    $flagLink_begin = $flagLink_end = '';

                    switch ((string) $contentTreeArr['localizationInfo'][$sys_language_uid]['mode']) {
                        case 'exists':
                            $olrow = BackendUtility::getRecordWSOL('tt_content', $contentTreeArr['localizationInfo'][$sys_language_uid]['localization_uid']);

                            // In my WS or viewable in Live?
                            if ((int)$olrow['t3ver_state'] !== 0
                                && (int)$olrow['t3ver_wsid'] !== (int)$GLOBALS['BE_USER']->workspace
                            ) {
                                // @TODO Output that it is localized on another WS?
                                continue 2;
                            }

                            $localizedRecordInfo = array(
                                'uid' => $olrow['uid'],
                                'row' => $olrow,
                                'content' => $this->render_previewContent($olrow)
                            );

                            // Put together the records icon including content sensitive menu link wrapped around it:
                            $recordIcon_l10n = $this->iconFactory->getIconForRecord('tt_content', $localizedRecordInfo['row'], Icon::SIZE_SMALL)->render();
                            if (!$this->translatorMode) {
                                $recordIcon_l10n = BackendUtility::wrapClickMenuOnIcon($recordIcon_l10n, 'tt_content', $localizedRecordInfo['uid'], true, '', 'new,copy,cut,pasteinto,pasteafter');
                            }
                            $l10nInfo =
                                '<a name="c' . md5($this->apiObj->flexform_getStringFromPointer($this->currentElementParentPointer) . $localizedRecordInfo['row']['uid']) . '"></a>' .
                                '<a name="c' . md5($this->apiObj->flexform_getStringFromPointer($this->currentElementParentPointer) . $localizedRecordInfo['row']['l18n_parent'] . $localizedRecordInfo['row']['sys_language_uid']) . '"></a>' .
                                $this->getRecordStatHookValue('tt_content', $localizedRecordInfo['row']['uid']) .
                                $recordIcon_l10n .
                                htmlspecialchars(GeneralUtility::fixed_lgd_cs(strip_tags(BackendUtility::getRecordTitle('tt_content', $localizedRecordInfo['row'])), $this->previewTitleMaxLen));

                            $l10nInfo .= '<br/>' . $localizedRecordInfo['content'];

                            list($flagLink_begin, $flagLink_end) = explode('|*|', $this->link_edit('|*|', 'tt_content', $localizedRecordInfo['uid'], true));
                            if ($this->translatorMode && $flagLink_begin !== '') {
                                $l10nInfo .= '<br/>' . $flagLink_begin . '<em>' . TemplaVoilaUtility::getLanguageService()->getLL('clickToEditTranslation') . '</em>' . $flagLink_end;
                            }

                            // Wrap workspace notification colors:
                            if ($olrow['_ORIG_uid']) {
                                $l10nInfo = '<div class="ver-element">' . $l10nInfo . '</div>';
                            }

                            $this->global_localization_status[$sys_language_uid][] = array(
                                'status' => 'exist',
                                'parent_uid' => $contentTreeArr['el']['uid'],
                                'localized_uid' => $localizedRecordInfo['row']['uid'],
                                'sys_language' => $contentTreeArr['el']['sys_language_uid']
                            );
                            break;
                        case 'localize':
                            if (isset($this->modTSconfig['properties']['hideCopyForTranslation'])) {
                                $showLocalizationLinks = 0;
                            } else {
                                if ($this->rootElementLangParadigm == 'free') {
                                    $showLocalizationLinks = !$parentDsMeta['langDisable']; // For this paradigm, show localization links only if localization is enabled for DS (regardless of Inheritance and Separate)
                                } else {
                                    $showLocalizationLinks = ($parentDsMeta['langDisable'] || $parentDsMeta['langChildren']); // Adding $parentDsMeta['langDisable'] here means that the "Create a copy for translation" link is shown only if the parent container element has localization mode set to "Disabled" or "Inheritance" - and not "Separate"!
                                }
                            }

                            // Assuming that only elements which have the default language set are candidates for localization. In case the language is [ALL] then it is assumed that the element should stay "international".
                            if ((int) $contentTreeArr['el']['sys_language_uid'] === 0 && $showLocalizationLinks) {
                                // Copy for language:
                                if ($this->rootElementLangParadigm == 'free') {
                                    $sourcePointerString = $this->apiObj->flexform_getStringFromPointer($parentPointer);
                                    $onClick = "document.location='"
                                        . BackendUtility::getModuleUrl($this->moduleName, $this->getLinkParameters(['source' => $sourcePointerString, 'localizeElement' => $sLInfo['ISOcode']]))
                                        . "'; return false;";
                                } else {
                                    $params = '&cmd[tt_content][' . $contentTreeArr['el']['uid'] . '][localize]=' . $sys_language_uid;
                                    $onClick = "document.location='" . BackendUtility::getLinkToDataHandlerAction($params, GeneralUtility::getIndpEnv('REQUEST_URI') . '#c' . md5($this->apiObj->flexform_getStringFromPointer($parentPointer) . $contentTreeArr['el']['uid'] . $sys_language_uid)) . "'; return false;";
                                }

                                $linkLabel = TemplaVoilaUtility::getLanguageService()->getLL('createcopyfortranslation', true) . ' (' . htmlspecialchars($sLInfo['title']) . ')';
                                $localizeIcon = $this->iconFactory->getIcon('actions-edit-copy', Icon::SIZE_SMALL)->render();

                                $l10nInfo = '<a class="tpm-clipCopyTranslation" href="#" onclick="' . htmlspecialchars($onClick) . '">' . $localizeIcon . '</a>';
                                $l10nInfo .= ' <em><a href="#" onclick="' . htmlspecialchars($onClick) . '">' . $linkLabel . '</a></em>';
                                $flagLink_begin = '<a href="#" onclick="' . htmlspecialchars($onClick) . '">';
                                $flagLink_end = '</a>';

                                $this->global_localization_status[$sys_language_uid][] = array(
                                    'status' => 'localize',
                                    'parent_uid' => $contentTreeArr['el']['uid'],
                                    'sys_language' => $contentTreeArr['el']['sys_language_uid']
                                );
                            }
                            break;
                        case 'localizedFlexform':
                            // Here we want to show the "Localized FlexForm" information (and link to edit record) _only_ if there are other fields than group-fields for content elements: It only makes sense for a translator to deal with the record if that is the case.
                            // Change of strategy (27/11): Because there does not have to be content fields; could be in sections or arrays and if thats the case you still want to localize them! There has to be another way...
                            // if (count($contentTreeArr['contentFields']['sDEF']))    {
                            list($flagLink_begin, $flagLink_end) = explode('|*|', $this->link_edit('|*|', 'tt_content', $contentTreeArr['el']['uid'], true));
                            $l10nInfo = $flagLink_begin . '<em>[' . TemplaVoilaUtility::getLanguageService()->getLL('performTranslation') . ']</em>' . $flagLink_end;
                            $this->global_localization_status[$sys_language_uid][] = array(
                                'status' => 'flex',
                                'parent_uid' => $contentTreeArr['el']['uid'],
                                'sys_language' => $contentTreeArr['el']['sys_language_uid']
                            );
                            // }
                            break;
                    }

                    if ($l10nInfo && TemplaVoilaUtility::getBackendUser()->checkLanguageAccess($sys_language_uid)) {
                        $tRows[] = '
                            <tr class="tpm-language-row">
                                <td class="tpm-language-edit">' . $flagLink_begin . \Ppi\TemplaVoilaPlus\Utility\IconUtility::getFlagIconForLanguage($sLInfo['flagIcon'], array('title' => $sLInfo['title'], 'alt' => $sLInfo['title'])) . $flagLink_end . '</td>
                                <td class="tpm-language-info">' . $l10nInfo . '</td>
                            </tr>';
                    }
                }
            }

            $output = count($tRows) ? '
                <table border="0" cellpadding="0" cellspacing="1" width="100%" class="lrPadding tpm-localisation-info-table">
                    <tr class="bgColor4-20">
                        <td colspan="2">' . TemplaVoilaUtility::getLanguageService()->getLL('element_localizations', true) . ':</td>
                    </tr>
                    ' . implode('', $tRows) . '
                </table>
            ' : '';
        }

        return $output;
    }

    /*******************************************
     *
     * Outline rendering:
     *
     *******************************************/

    /**
     * Rendering the outline display of the page structure
     *
     * @param array $contentTreeArr DataStructure info array (the whole tree)
     *
     * @return string HTML
     */
    public function render_outline($contentTreeArr)
    {
        // Load possible website languages:
        $this->translatedLanguagesArr_isoCodes = array();
        foreach ($this->translatedLanguagesArr as $langInfo) {
            if ($langInfo['ISOcode']) {
                $this->translatedLanguagesArr_isoCodes['all_lKeys'][] = 'l' . $langInfo['ISOcode'];
                $this->translatedLanguagesArr_isoCodes['all_vKeys'][] = 'v' . $langInfo['ISOcode'];
            }
        }

        // Rendering the entries:
        $entries = array();
        $this->render_outline_element($contentTreeArr, $entries);

        // Header of table:
        $output = '';
        $output .= '<tr class="bgColor5 tableheader">
                <td class="nobr">' . TemplaVoilaUtility::getLanguageService()->getLL('outline_header_title', true) . '</td>
                <td class="nobr">' . TemplaVoilaUtility::getLanguageService()->getLL('outline_header_controls', true) . '</td>
                <td class="nobr">' . TemplaVoilaUtility::getLanguageService()->getLL('outline_header_status', true) . '</td>
                <td class="nobr">' . TemplaVoilaUtility::getLanguageService()->getLL('outline_header_element', true) . '</td>
            </tr>';

        // Render all entries:
        $xmlCleanCandidates = false;
        foreach ($entries as $entry) {
            // Create indentation code:
            $indent = '';
            for ($a = 0; $a < $entry['indentLevel']; $a++) {
                $indent .= '&nbsp;&nbsp;&nbsp;&nbsp;';
            }

            // Create status for FlexForm XML:
            // WARNING: Also this section contains cleaning of XML which is sort of mixing functionality but a quick and easy solution for now.
            // @Robert: How would you like this implementation better? Please advice and I will change it according to your wish!
            $status = '';
            if ($entry['table'] && $entry['uid']) {
                $flexObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class);
                $recRow = BackendUtility::getRecordWSOL($entry['table'], $entry['uid']);
                if ($recRow['tx_templavoilaplus_flex']) {
                    // Clean XML:
                    $newXML = $flexObj->cleanFlexFormXML($entry['table'], 'tx_templavoilaplus_flex', $recRow);

                    // If the clean-all command is sent AND there is a difference in current/clean XML, save the clean:
                    if (GeneralUtility::_POST('_CLEAN_XML_ALL') && md5($recRow['tx_templavoilaplus_flex']) != md5($newXML)) {
                        $dataArr = array();
                        $dataArr[$entry['table']][$entry['uid']]['tx_templavoilaplus_flex'] = $newXML;

                        // Init TCEmain object and store:
                        $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                        $tce->stripslashes_values = 0;
                        $tce->start($dataArr, array());
                        $tce->process_datamap();

                        // Re-fetch record:
                        $recRow = BackendUtility::getRecordWSOL($entry['table'], $entry['uid']);
                    }

                    // Render status:
                    $xmlUrl = BackendUtility::getModuleUrl(
                        'templavoilaplus_flexform_cleaner',
                        [
                            'id' => (int)$clickMenu->rec['pid'],
                            'viewRec' => [
                                'table' => $entry['table'],
                                'uid' => $entry['uid'],
                                'field_flex' => 'tx_templavoilaplus_flex',
                            ],
                        ]
                    );

                    if (md5($recRow['tx_templavoilaplus_flex']) != md5($newXML)) {
                        $status = $this->moduleTemplate->icons(1) . '<a href="' . htmlspecialchars($xmlUrl) . '">' . TemplaVoilaUtility::getLanguageService()->getLL('outline_status_dirty', 1) . '</a><br/>';
                        $xmlCleanCandidates = true;
                    } else {
                        $status = $this->moduleTemplate->icons(-1) . '<a href="' . htmlspecialchars($xmlUrl) . '">' . TemplaVoilaUtility::getLanguageService()->getLL('outline_status_clean', 1) . '</a><br/>';
                    }
                }
            }

            // Compile table row:
            $class = ($entry['isNewVersion'] ? 'bgColor5' : 'bgColor4') . ' ' . $entry['elementTitlebarClass'];
            $output .= '<tr class="' . $class . '">
                    <td class="nobr">' . $indent . $entry['icon'] . $entry['flag'] . $entry['title'] . '</td>
                    <td class="nobr">' . $entry['controls'] . '</td>
                    <td>' . $status . $entry['warnings'] . ($entry['isNewVersion'] ? $this->moduleTemplate->icons(1) . 'New version!' : '') . '</td>
                    <td class="nobr">' . htmlspecialchars($entry['id'] ? $entry['id'] : $entry['table'] . ':' . $entry['uid']) . '</td>
                </tr>';
        }
        $output = '<table border="0" cellpadding="1" cellspacing="1" class="tpm-outline-table">' . $output . '</table>';

        // Show link for cleaning all XML structures:
        if ($xmlCleanCandidates) {
            $output .= '<br/>
                ' . BackendUtility::cshItem('_MOD_web_txtemplavoilaplusLayout', 'outline_status_cleanall') . '
                <input type="submit" value="' . TemplaVoilaUtility::getLanguageService()->getLL('outline_status_cleanAll', true) . '" name="_CLEAN_XML_ALL" /><br/><br/>
            ';
        }

        return $output;
    }

    /**
     * Rendering a single element in outline:
     *
     * @param array $contentTreeArr DataStructure info array (the whole tree)
     * @param array $entries Entries accumulated in this array (passed by reference)
     * @param integer $indentLevel Indentation level
     * @param array $parentPointer Element position in structure
     * @param string $controls HTML for controls to add for this element
     *
     * @return void
     * @access protected
     * @see render_outline_allSheets()
     */
    public function render_outline_element($contentTreeArr, &$entries, $indentLevel = 0, $parentPointer = array(), $controls = '')
    {
        // Get record of element:
        $elementBelongsToCurrentPage = $contentTreeArr['el']['table'] == 'pages' || $contentTreeArr['el']['pid'] == $this->rootElementUid_pidForContent;

        $recordIcon = $contentTreeArr['el']['iconTag'];

        $titleBarLeftButtons = $this->translatorMode ? $recordIcon : BackendUtility::wrapClickMenuOnIcon($recordIcon, $contentTreeArr['el']['table'], $contentTreeArr['el']['uid'], true, '', 'new,copy,cut,pasteinto,pasteafter,delete');
        $titleBarLeftButtons .= $this->getRecordStatHookValue($contentTreeArr['el']['table'], $contentTreeArr['el']['uid']);

        $languageUid = 0;
        $titleBarRightButtons = '';
        // Prepare table specific settings:
        switch ($contentTreeArr['el']['table']) {
            case 'pages':
                $iconEdit = $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render();
                $titleBarLeftButtons .= $this->translatorMode ? '' : $this->link_edit($iconEdit, $contentTreeArr['el']['table'], $contentTreeArr['el']['uid']);
                $titleBarRightButtons = '';

                $addGetVars = ($this->currentLanguageUid ? '&L=' . $this->currentLanguageUid : '');
                $viewPageOnClick = 'onclick= "' . htmlspecialchars(BackendUtility::viewOnClick($contentTreeArr['el']['uid'], '', BackendUtility::BEgetRootLine($contentTreeArr['el']['uid']), '', '', $addGetVars)) . '"';
                $viewPageIcon = $this->iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL)->render();
                $titleBarLeftButtons .= '<a href="#" ' . $viewPageOnClick . '>' . $viewPageIcon . '</a>';
                break;
            case 'tt_content':
                $languageUid = $contentTreeArr['el']['sys_language_uid'];

                if (!$this->translatorMode) {
                    // Create CE specific buttons:
                    $iconMakeLocal = $this->iconFactory->getIcon('extensions-templavoila-makelocalcopy', Icon::SIZE_SMALL)->render();
                    $linkMakeLocal = !$elementBelongsToCurrentPage ? $this->link_makeLocal($iconMakeLocal, $parentPointer) : '';
                    if ($this->modTSconfig['properties']['enableDeleteIconForLocalElements'] < 2 ||
                        !$elementBelongsToCurrentPage ||
                        $this->global_tt_content_elementRegister[$contentTreeArr['el']['uid']] > 1
                    ) {
                        $linkUnlink = $this->link_unlink('extensions-templavoila-unlink', TemplaVoilaUtility::getLanguageService()->getLL('unlinkRecord'), $parentPointer, false);
                    } else {
                        $linkUnlink = '';
                    }
                    if ($this->modTSconfig['properties']['enableDeleteIconForLocalElements'] && $elementBelongsToCurrentPage) {
                        $hasForeignReferences = TemplaVoilaUtility::hasElementForeignReferences($contentTreeArr['el'], $contentTreeArr['el']['pid']);
                        $linkDelete = $this->link_unlink('actions-edit-delete', TemplaVoilaUtility::getLanguageService()->getLL('deleteRecord'), $parentPointer, true, $hasForeignReferences);
                    } else {
                        $linkDelete = '';
                    }
                    $linkEdit = ($elementBelongsToCurrentPage ? $this->buildButtonEdit($contentTreeArr['el']) : '');

                    $titleBarRightButtons = $linkEdit . $this->clipboardObj->element_getSelectButtons($parentPointer) . $linkMakeLocal . $linkUnlink . $linkDelete;
                }
                break;
        }

        // Prepare the language icon:

        if ($languageUid > 0) {
            $languageLabel = htmlspecialchars($this->pObj->allAvailableLanguages[$languageUid]['title']);
            if ($this->pObj->allAvailableLanguages[$languageUid]['flagIcon']) {
                $languageIcon = \Ppi\TemplaVoilaPlus\Utility\IconUtility::getFlagIconForLanguage($this->pObj->allAvailableLanguages[$languageUid]['flagIcon'], array('title' => $languageLabel, 'alt' => $languageLabel));
            } else {
                $languageIcon = '[' . $languageLabel . ']';
            }
        } else {
            $languageIcon = '';
        }

        // If there was a langauge icon and the language was not default or [all] and if that langauge is accessible for the user, then wrap the flag with an edit link (to support the "Click the flag!" principle for translators)
        if ($languageIcon && $languageUid > 0 && TemplaVoilaUtility::getBackendUser()->checkLanguageAccess($languageUid) && $contentTreeArr['el']['table'] === 'tt_content') {
            $languageIcon = $this->link_edit($languageIcon, 'tt_content', $contentTreeArr['el']['uid'], true);
        }

        // Create warning messages if neccessary:
        $warnings = '';
        if ($this->global_tt_content_elementRegister[$contentTreeArr['el']['uid']] > 1 && $this->rootElementLangParadigm != 'free') {
            $warnings .= '<br/>' . $this->moduleTemplate->icons(2) . ' <em>' . htmlspecialchars(sprintf(TemplaVoilaUtility::getLanguageService()->getLL('warning_elementusedmorethanonce', ''), $this->global_tt_content_elementRegister[$contentTreeArr['el']['uid']], $contentTreeArr['el']['uid'])) . '</em>';
        }

        // Displaying warning for container content (in default sheet - a limitation) elements if localization is enabled:
        $isContainerEl = false;
        if (isset($contentTreeArr['sub']['sDEF']) && !empty($contentTreeArr['sub']['sDEF'])) {
            $isContainerEl = true;
        }
        if (!$this->modTSconfig['properties']['disableContainerElementLocalizationWarning'] && $this->rootElementLangParadigm != 'free' && $isContainerEl && $contentTreeArr['el']['table'] === 'tt_content' && $contentTreeArr['el']['CType'] === 'templavoilaplus_pi1' && !$contentTreeArr['ds_meta']['langDisable']) {
            if ($contentTreeArr['ds_meta']['langChildren']) {
                if (!$this->modTSconfig['properties']['disableContainerElementLocalizationWarning_warningOnly']) {
                    $warnings .= '<br/>' . $this->moduleTemplate->icons(2) . ' <b>' . TemplaVoilaUtility::getLanguageService()->getLL('warning_containerInheritance_short') . '</b>';
                }
            } else {
                $warnings .= '<br/>' . $this->moduleTemplate->icons(3) . ' <b>' . TemplaVoilaUtility::getLanguageService()->getLL('warning_containerSeparate_short') . '</b>';
            }
        }

        // Create entry for this element:
        $entries[] = array(
            'indentLevel' => $indentLevel,
            'icon' => $titleBarLeftButtons,
            'title' => ($elementBelongsToCurrentPage ? '' : '<em>') . htmlspecialchars($contentTreeArr['el']['title']) . ($elementBelongsToCurrentPage ? '' : '</em>'),
            'warnings' => $warnings,
            'controls' => $titleBarRightButtons . $controls,
            'table' => $contentTreeArr['el']['table'],
            'uid' => $contentTreeArr['el']['uid'],
            'flag' => $languageIcon,
            'isNewVersion' => $contentTreeArr['el']['_ORIG_uid'] ? true : false,
            'elementTitlebarClass' => (!$elementBelongsToCurrentPage ? 'tpm-elementRef' : 'tpm-element') . ' tpm-outline-level' . $indentLevel
        );

        // Create entry for localizaitons...
        $this->render_outline_localizations($contentTreeArr, $entries, $indentLevel + 1);

        // Create entries for sub-elements in all sheets:
        if ($contentTreeArr['sub']) {
            foreach ($contentTreeArr['sub'] as $sheetKey => $sheetInfo) {
                if (is_array($sheetInfo)) {
                    $this->render_outline_subElements($contentTreeArr, $sheetKey, $entries, $indentLevel + 1);
                }
            }
        }
    }

    /**
     * Rendering outline for child-elements
     *
     * @param array $contentTreeArr DataStructure info array (the whole tree)
     * @param string $sheet Which sheet to display
     * @param array $entries Entries accumulated in this array (passed by reference)
     * @param integer $indentLevel Indentation level
     *
     * @return void
     * @access protected
     */
    public function render_outline_subElements($contentTreeArr, $sheet, &$entries, $indentLevel)
    {
        // Define l/v keys for current language:
        $langChildren = (int)$contentTreeArr['ds_meta']['langChildren'];
        $langDisable = (int)$contentTreeArr['ds_meta']['langDisable'];
        $lKeys = $langDisable ? array('lDEF') : ($langChildren ? array('lDEF') : $this->translatedLanguagesArr_isoCodes['all_lKeys']);
        $vKeys = $langDisable ? array('vDEF') : ($langChildren ? $this->translatedLanguagesArr_isoCodes['all_vKeys'] : array('vDEF'));

        // Traverse container fields:
        foreach ($lKeys as $lKey) {
            // Traverse fields:
            if (is_array($contentTreeArr['sub'][$sheet][$lKey])) {
                foreach ($contentTreeArr['sub'][$sheet][$lKey] as $fieldID => $fieldValuesContent) {
                    foreach ($vKeys as $vKey) {
                        if (is_array($fieldValuesContent[$vKey])) {
                            $fieldContent = $fieldValuesContent[$vKey];

                            // Create flexform pointer pointing to "before the first sub element":
                            $subElementPointer = array(
                                'table' => $contentTreeArr['el']['table'],
                                'uid' => $contentTreeArr['el']['uid'],
                                'sheet' => $sheet,
                                'sLang' => $lKey,
                                'field' => $fieldID,
                                'vLang' => $vKey,
                                'position' => 0
                            );

                            if (!$this->translatorMode) {
                                // "New" and "Paste" icon:
                                $controls = $this->buildButtonNew($subElementPointer);

                                $controls .= $this->clipboardObj->element_getPasteButtons($subElementPointer);
                            } else {
                                $controls = '';
                            }

                            // Add entry for lKey level:
                            $specialPath = ($sheet != 'sDEF' ? '<' . $sheet . '>' : '') . ($lKey != 'lDEF' ? '<' . $lKey . '>' : '') . ($vKey != 'vDEF' ? '<' . $vKey . '>' : '');
                            $entries[] = array(
                                'indentLevel' => $indentLevel,
                                'icon' => '',
                                'title' => '<b>' . TemplaVoilaUtility::getLanguageService()->sL($fieldContent['meta']['title'], 1) . '</b>' . ($specialPath ? ' <em>' . htmlspecialchars($specialPath) . '</em>' : ''),
                                'id' => '<' . $sheet . '><' . $lKey . '><' . $fieldID . '><' . $vKey . '>',
                                'controls' => $controls,
                                'elementTitlebarClass' => 'tpm-container tpm-outline-level' . $indentLevel,
                            );

                            // Render the list of elements (and possibly call itself recursively if needed):
                            if (is_array($fieldContent['el_list'])) {
                                foreach ($fieldContent['el_list'] as $position => $subElementKey) {
                                    $subElementArr = $fieldContent['el'][$subElementKey];
                                    if (!$subElementArr['el']['isHidden'] || $this->MOD_SETTINGS['tt_content_showHidden'] !== '0') {
                                        // Modify the flexform pointer so it points to the position of the curren sub element:
                                        $subElementPointer['position'] = $position;

                                        if (!$this->translatorMode) {
                                            // "New" and "Paste" icon:
                                            $controls = $this->buildButtonNew($subElementPointer);
                                            $controls .= $this->clipboardObj->element_getPasteButtons($subElementPointer);
                                        }

                                        $this->render_outline_element($subElementArr, $entries, $indentLevel + 1, $subElementPointer, $controls);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Renders localized elements of a record
     *
     * @param array $contentTreeArr Part of the contentTreeArr for the element
     * @param array $entries Entries accumulated in this array (passed by reference)
     * @param integer $indentLevel Indentation level
     *
     * @return string HTML
     * @access protected
     * @see render_framework_singleSheet()
     */
    public function render_outline_localizations($contentTreeArr, &$entries, $indentLevel)
    {
        if ($contentTreeArr['el']['table'] == 'tt_content' && $contentTreeArr['el']['sys_language_uid'] <= 0) {
            // Traverse the available languages of the page (not default and [All])
            foreach ($this->translatedLanguagesArr as $sys_language_uid => $sLInfo) {
                if ($sys_language_uid > 0 && TemplaVoilaUtility::getBackendUser()->checkLanguageAccess($sys_language_uid)) {
                    switch ((string) $contentTreeArr['localizationInfo'][$sys_language_uid]['mode']) {
                        case 'exists':
                            // Get localized record:
                            $olrow = BackendUtility::getRecordWSOL('tt_content', $contentTreeArr['localizationInfo'][$sys_language_uid]['localization_uid']);

                            // Put together the records icon including content sensitive menu link wrapped around it:
                            $recordIcon_l10n = $this->getRecordStatHookValue('tt_content', $olrow['uid']) .
                                $this->iconFactory->getIconForRecord('tt_content', $olrow, Icon::SIZE_SMALL)->render();
                            if (!$this->translatorMode) {
                                $recordIcon_l10n = BackendUtility::wrapClickMenuOnIcon($recordIcon_l10n, 'tt_content', $olrow['uid'], true, '', 'new,copy,cut,pasteinto,pasteafter');
                            }

                            list($flagLink_begin, $flagLink_end) = explode('|*|', $this->link_edit('|*|', 'tt_content', $olrow['uid'], true));

                            // Create entry for this element:
                            $entries[] = array(
                                'indentLevel' => $indentLevel,
                                'icon' => $recordIcon_l10n,
                                'title' => BackendUtility::getRecordTitle('tt_content', $olrow),
                                'table' => 'tt_content',
                                'uid' => $olrow['uid'],
                                'flag' => $flagLink_begin . \Ppi\TemplaVoilaPlus\Utility\IconUtility::getFlagIconForLanguage($sLInfo['flagIcon'], array('title' => $sLInfo['title'], 'alt' => $sLInfo['title'])) . $flagLink_end,
                                'isNewVersion' => $olrow['_ORIG_uid'] ? true : false,
                            );
                            break;
                    }
                }
            }
        }
    }

    /**
     * Renders the sidebar, including the relevant hook objects
     *
     * @return string
     */
    protected function render_sidebar()
    {
        // Hook for adding new sidebars or removing existing
        $sideBarHooks = $this->hooks_prepareObjectsArray('sideBarClass');
        foreach ($sideBarHooks as $hookObj) {
            if (method_exists($hookObj, 'main_alterSideBar')) {
                $hookObj->main_alterSideBar($this->sideBarObj, $this);
            }
        }

        return $this->sideBarObj->render();
    }

    /*******************************************
     *
     * Link functions (protected)
     *
     *******************************************/

    /**
     * Returns an HTML link for editing
     *
     * @param string $label The label (or image)
     * @param string $table The table, fx. 'tt_content'
     * @param integer $uid The uid of the element to be edited
     * @param boolean $forced By default the link is not shown if translatorMode is set, but with this boolean it can be forced anyway.
     * @param integer $usePid ...
     *
     * @return string HTML anchor tag containing the label and the correct link
     * @access protected
     */
    public function link_edit($label, $table, $uid, $forced = false, $usePid = 0)
    {
        if ($label) {
            $pid = $table == 'pages' ? $uid : $usePid;
            $calcPerms = $pid == 0 ? $this->calcPerms : $this->getCalcPerms($pid);

            if (($table == 'pages' && ($calcPerms & 2) ||
                    $table != 'pages' && ($calcPerms & 16)) &&
                (!$this->translatorMode || $forced)
            ) {
                if ($table == "pages" && $this->currentLanguageUid) {
                    return '<a class="tpm-pageedit" href="'
                        . BackendUtility::getModuleUrl($this->moduleName, $this->getLinkParameters(['editPageLanguageOverlay' => $this->currentLanguageUid]))
                        . '">' . $label . '</a>';
                } else {
                    $onClick = BackendUtility::editOnClick('&edit[' . $table . '][' . $uid . ']=edit', '', -1);
                    return '<a class="tpm-edit" href="#" onclick="' . htmlspecialchars($onClick) . '">' . $label . '</a>';
                }
            } else {
                return $label;
            }
        }

        return '';
    }

    /**
     * Returns an HTML link for editing element
     *
     * @param array $el
     *
     * @return string HTML anchor tag containing the label and the correct link
     * @access protected
     */
    public function buildButtonEdit($el)
    {
        $uid = $el['uid'];
        $table = $el['table'];

        $workspaceRec = BackendUtility::getWorkspaceVersionOfRecord(TemplaVoilaUtility::getBackendUser()->workspace, $table, $uid);
        $workspaceId = ($workspaceRec['uid'] > 0) ? $workspaceRec['uid'] : $uid;

        return $this->buildButtonFromUrl(
            BackendUtility::editOnClick('&edit[' . $table . '][' . $workspaceId . ']=edit', '', -1),
            TemplaVoilaUtility::getLanguageService()->getLL('editrecord'),
            'actions-document-open'
        );
    }

    /**
     * Returns an HTML link for (un)hiding element
     *
     * @param array $el
     *
     * @return string HTML anchor tag containing the label and the correct link
     * @access protected
     */
    public function buildButtonHide($el)
    {
        $uid = $el['uid'];
        $table = $el['table'];
        $hidden = $el['isHidden'];

        $workspaceRec = BackendUtility::getWorkspaceVersionOfRecord(TemplaVoilaUtility::getBackendUser()->workspace, $table, $uid);
        $workspaceId = ($workspaceRec['uid'] > 0) ? $workspaceRec['uid'] : $uid;

        $returnUrl = ($this->currentElementParentPointer) ? GeneralUtility::getIndpEnv('REQUEST_URI') . '#c' . md5($this->apiObj->flexform_getStringFromPointer($this->currentElementParentPointer) . $uid) : GeneralUtility::getIndpEnv('REQUEST_URI');
        $params = '&data[' . $table . '][' . $workspaceId . '][hidden]=' . (1 - $hidden);

        $clickUrl =
            'sortable_' . ($hidden ? 'un' : '') . 'hideRecord(this, \'' . htmlspecialchars(BackendUtility::getLinkToDataHandlerAction($params, $returnUrl)) . '\');';

        return $this->buildButtonFromUrl(
            $clickUrl,
            TemplaVoilaUtility::getLanguageService()->getLL($hidden ? 'unhiderecord' : 'hiderecord'),
            $hidden ? 'actions-edit-unhide' : 'actions-edit-hide'
        );
    }

    /**
     * Returns an HTML link for browse for record
     *
     * @param string $label The label (or image)
     * @param array $parentPointer Flexform pointer defining the parent element of the new record
     *
     * @return string HTML anchor tag containing the label and the correct link
     * @access protected
     */
    public function buildButtonBrowse($parentPointer)
    {
        return $this->buildButton(
            'wizard_element_browser',
            TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:' . $this->coreLangPath . 'locallang_core.xlf:labels.browse_db'),
            'actions-insert-record',
            $this->getLinkParameters([
                'pasteRecord' => 'ref',
                'source' => '###',
                'destination' => $this->apiObj->flexform_getStringFromPointer($parentPointer)
            ]),
            'default',
            'tpm-new'
        );
    }

    /**
     * Returns an HTML link for creating a new record
     *
     * @param array $parentPointer Flexform pointer defining the parent element of the new record
     *
     * @return string HTML anchor tag containing the label and the correct link
     * @access protected
     */
    public function buildButtonNew($parentPointer)
    {
        return $this->buildButton(
            $this->newContentWizModuleName,
            TemplaVoilaUtility::getLanguageService()->getLL('createnewrecord'),
            'actions-document-new',
            $this->getLinkParameters([
                'parentRecord' => $this->apiObj->flexform_getStringFromPointer($parentPointer),
                'colPos' => 0,
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                'uid_pid' => $this->id,
            ]),
            'default',
            'tpm-new'
        );
    }

    /**
     * Returns an HTML link for unlinking a content element. Unlinking means that the record still exists but
     * is not connected to any other content element or page.
     *
     * @param string $iconName Name of icon
     * @param string $title Link title
     * @param array $unlinkPointer Flexform pointer pointing to the element to be unlinked
     * @param boolean $realDelete If set, the record is not just unlinked but deleted!
     * @param boolean $foreignReferences If set, the record seems to have references on other pages
     * @param string $elementPointer
     *
     * @return string HTML anchor tag containing the label and the unlink-link
     * @access protected
     */
    public function link_unlink($iconName, $title, $unlinkPointer, $realDelete = false, $foreignReferences = false, $elementPointer = '')
    {
        $label = $this->iconFactory->getIcon($iconName, Icon::SIZE_SMALL)->render();

        $unlinkPointerString = $this->apiObj->flexform_getStringFromPointer($unlinkPointer);
        $encodedUnlinkPointerString = rawurlencode($unlinkPointerString);

        if ($realDelete) {
            $LLlabel = $foreignReferences ? 'deleteRecordWithReferencesMsg' : 'deleteRecordMsg';

            return '<a class="btn btn-warning btn-sm tpm-delete" title="' . $title . '" href="'
                . BackendUtility::getModuleUrl($this->moduleName, $this->getLinkParameters(['deleteRecord' => $unlinkPointerString]))
                . '" onclick="' . htmlspecialchars('return confirm(' . GeneralUtility::quoteJSvalue(TemplaVoilaUtility::getLanguageService()->getLL($LLlabel)) . ');') . '">' . $label . '</a>';
        } else {
            return '<a class="btn btn-default btn-sm tpm-unlink" title="' . $title . '" href="javascript:'
                . htmlspecialchars('if (confirm(' . GeneralUtility::quoteJSvalue(TemplaVoilaUtility::getLanguageService()->getLL('unlinkRecordMsg')) . '))') . 'sortable_unlinkRecord(\'' . $encodedUnlinkPointerString . '\',\'' . $this->getSortableItemHash($unlinkPointerString) . '\',\'' . $elementPointer . '\');">' . $label . '</a>';
        }
    }

    /**
     * Returns an HTML link for making a reference content element local to the page (copying it).
     *
     * @param string $label The label
     * @param array $makeLocalPointer Flexform pointer pointing to the element which shall be copied
     *
     * @return string HTML anchor tag containing the label and the unlink-link
     * @access protected
     */
    public function link_makeLocal($label, $makeLocalPointer)
    {
        return '<a class="btn btn-default btn-sm tpm-makeLocal" href="'
            . BackendUtility::getModuleUrl($this->moduleName, $this->getLinkParameters(['makeLocalRecord' => $this->apiObj->flexform_getStringFromPointer($makeLocalPointer)]))
            . '" onclick="' . htmlspecialchars('return confirm(' . GeneralUtility::quoteJSvalue(TemplaVoilaUtility::getLanguageService()->getLL('makeLocalMsg')) . ');') . '">' . $label . '</a>';
    }

    /**
     * Creates additional parameters which are used for linking to the current page while editing it
     *
     * @return string parameters
     * @access public
     */
    public function link_getParameters()
    {
        $output =
            'id=' . $this->id .
            (is_array($this->altRoot) ? GeneralUtility::implodeArrayForUrl('altRoot', $this->altRoot) : '') .
            ($this->versionId ? '&amp;versionId=' . rawurlencode($this->versionId) : '');

        return $output;
    }

    public function getLinkParameters(array $extraParams = [])
    {
        return array_merge(
            [
                'id' => $this->id,
                'altRoot' => $this->altRoot,
                'versionId' => $this->versionId,
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
     * Render the bottom controls which (might) contain the new, browse and paste-buttons
     * which sit below each content element
     *
     * @param array $elementPointer
     * @param boolean $canCreateNew
     *
     * @return string
     */
    protected function link_bottomControls($elementPointer, $canCreateNew)
    {
        $output = '<div class="t3-page-ce t3js-page-ce">';

        // "New" icon:
        if ($canCreateNew && !in_array('new', $this->blindIcons)) {
            $output .= $this->buildButtonNew($elementPointer);
        }

        // "Browse Record" icon
        if ($canCreateNew && !in_array('browse', $this->blindIcons)) {
            $output .= $this->buildButtonBrowse($elementPointer);
        }

        // "Paste" icon
        if ($canCreateNew) {
            $output .= '<span class="sortablePaste">' .
                $this->clipboardObj->element_getPasteButtons($elementPointer) .
                '&nbsp;</span>';
        }

        $output .= '</div>';

        return $output;
    }

    /*************************************************
     *
     * Processing and structure functions (protected)
     *
     *************************************************/

    /**
     * Checks various GET / POST parameters for submitted commands and handles them accordingly.
     * All commands will trigger a redirect by sending a location header after they work is done.
     *
     * Currently supported commands: 'createNewRecord', 'unlinkRecord', 'deleteRecord','pasteRecord',
     * 'makeLocalRecord', 'localizeElement' and 'editPageLanguageOverlay'
     *
     * @return void
     * @access protected
     */
    public function handleIncomingCommands()
    {
        $possibleCommands = array('createNewRecord', 'unlinkRecord', 'deleteRecord', 'pasteRecord', 'makeLocalRecord', 'localizeElement', 'editPageLanguageOverlay');

        $hooks = $this->hooks_prepareObjectsArray('handleIncomingCommands');

        foreach ($possibleCommands as $command) {
            if (($commandParameters = GeneralUtility::_GP($command)) != '') {
                $redirectLocation = BackendUtility::getModuleUrl($this->moduleName, $this->getLinkParameters());

                $skipCurrentCommand = false;
                foreach ($hooks as $hookObj) {
                    if (method_exists($hookObj, 'handleIncomingCommands_preProcess')) {
                        $skipCurrentCommand = $skipCurrentCommand || $hookObj->handleIncomingCommands_preProcess($command, $redirectLocation, $this);
                    }
                }

                if ($skipCurrentCommand) {
                    continue;
                }

                switch ($command) {
                    case 'createNewRecord':
                        // Historically "defVals" has been used for submitting the preset row data for the new element, so we still support it here:
                        $defVals = GeneralUtility::_GP('defVals');
                        $newRow = is_array($defVals['tt_content']) ? $defVals['tt_content'] : array();

                        // Create new record and open it for editing
                        $destinationPointer = $this->apiObj->flexform_getPointerFromString($commandParameters);
                        $newUid = $this->apiObj->insertElement($destinationPointer, $newRow);
                        if ($this->editingOfNewElementIsEnabled($newRow['tx_templavoilaplus_ds'], $newRow['tx_templavoilaplus_to'])) {
                            // TODO If $newUid==0, than we could create new element. Need to handle it...
                            $redirectLocation = BackendUtility::getModuleUrl('record_edit', [
                                'edit' => ['tt_content' => [$newUid => 'edit']],
                                'returnUrl' => $redirectLocation
                            ]);
                        }
                        break;

                    case 'unlinkRecord':
                        $unlinkDestinationPointer = $this->apiObj->flexform_getPointerFromString($commandParameters);
                        $this->apiObj->unlinkElement($unlinkDestinationPointer);
                        break;

                    case 'deleteRecord':
                        $deleteDestinationPointer = $this->apiObj->flexform_getPointerFromString($commandParameters);
                        $this->apiObj->deleteElement($deleteDestinationPointer);
                        break;

                    case 'pasteRecord':
                        $sourcePointer = $this->apiObj->flexform_getPointerFromString(GeneralUtility::_GP('source'));
                        $destinationPointer = $this->apiObj->flexform_getPointerFromString(GeneralUtility::_GP('destination'));
                        switch ($commandParameters) {
                            case 'copy':
                                $this->apiObj->copyElement($sourcePointer, $destinationPointer);
                                break;
                            case 'copyref':
                                $this->apiObj->copyElement($sourcePointer, $destinationPointer, false);
                                break;
                            case 'cut':
                                $this->apiObj->moveElement($sourcePointer, $destinationPointer);
                                break;
                            case 'ref':
                                list(, $uid) = explode(':', GeneralUtility::_GP('source'));
                                $this->apiObj->referenceElementByUid($uid, $destinationPointer);
                                break;
                        }
                        break;

                    case 'makeLocalRecord':
                        $sourcePointer = $this->apiObj->flexform_getPointerFromString($commandParameters);
                        $this->apiObj->copyElement($sourcePointer, $sourcePointer);
                        $this->apiObj->unlinkElement($sourcePointer);
                        break;

                    case 'localizeElement':
                        $sourcePointer = $this->apiObj->flexform_getPointerFromString(GeneralUtility::_GP('source'));
                        $this->apiObj->localizeElement($sourcePointer, $commandParameters);
                        break;

                    case 'editPageLanguageOverlay':
                        // Look for pages language overlay record for language:
                        $sys_language_uid = (int)$commandParameters;
                        $params = null;

                        if ($sys_language_uid != 0) {
                            $params = $this->getEditParams($this->id, $sys_language_uid);
                        } else {
                            // Edit default language (page properties)
                            // No workspace overlay because we already on this page
                            $params = [
                                'edit' => [
                                    'pages' => [
                                        (int)$this->id => 'edit',
                                    ]
                                ]
                            ];
                        }

                        if ($params) {
                            $params['returnUrl'] = $this->getBaseUrl();
                            $redirectLocation = BackendUtility::getModuleUrl('record_edit', $params);
                        }
                        break;
                }

                foreach ($hooks as $hookObj) {
                    if (method_exists($hookObj, 'handleIncomingCommands_postProcess')) {
                        $hookObj->handleIncomingCommands_postProcess($command, $redirectLocation, $this);
                    }
                }
            }
        }

        if (isset($redirectLocation)) {
            header('Location: ' . GeneralUtility::locationHeaderUrl($redirectLocation));
        }
    }

    /**
     * @TODO with 8.0 this should go elsewhere and not laying around inside controller.
     * Do not depend on this function.
     */
    protected function getEditParams($id, $sys_language_uid)
    {
        $table = 'pages_language_overlay';
        $params = false;
        if (version_compare(TYPO3_version, '9.0.0', '>=')) {
            $table = 'pages';
            // Since 9.0 we do not have pages_language_overlay anymore
            $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

            $languagePageRecord = $queryBuilder
                ->select('*')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($sys_language_uid, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
                )
                ->execute()
                ->fetch();
        } else {
            // Since 8.2 we have Doctrine
            $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getQueryBuilderForTable('pages_language_overlay');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

            $languagePageRecord = $queryBuilder
                ->select('*')
                ->from('pages_language_overlay')
                ->where(
                    $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($sys_language_uid, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
                )
                ->execute()
                ->fetch();
        }

        if ($languagePageRecord) {
            BackendUtility::workspaceOL($table, $languagePageRecord);
        }

        if (is_array($languagePageRecord)) {
            $params = [
                'edit' => [
                    $table => [
                        $languagePageRecord['uid'] => 'edit',
                    ]
                ]
            ];
        }

        return $params;
    }

    /***********************************************
     *
     * Miscelleaneous helper functions (protected)
     *
     ***********************************************/

    /**
     * Returns an array of registered instantiated classes for a certain hook.
     *
     * @param string $hookName Name of the hook
     *
     * @return array Array of object references
     * @access protected
     */
    public function hooks_prepareObjectsArray($hookName)
    {
        global $TYPO3_CONF_VARS;

        $hookObjectsArr = array();
        if (@is_array($TYPO3_CONF_VARS['EXTCONF']['templavoilaplus']['mod1'][$hookName])) {
            foreach ($TYPO3_CONF_VARS['EXTCONF']['templavoilaplus']['mod1'][$hookName] as $key => $classRef) {
                $hookObjectsArr[$key] = & GeneralUtility::getUserObj($classRef);
            }
        }

        return $hookObjectsArr;
    }

    /**
     * Checks if translation to alternative languages can be applied to this page.
     *
     * @return boolean <code>true</code> if alternative languages exist
     */
    public function alternativeLanguagesDefined()
    {
        return count($this->allAvailableLanguages) > 2;
    }

    /**
     * Defines if an element is to be displayed in the TV page module (could be filtered out by language settings)
     *
     * @param array $subElementArr Sub element array
     *
     * @return boolean Display or not
     */
    public function displayElement($subElementArr)
    {
        // Don't display when "selectedLanguage" is choosen
        $displayElement = !$this->MOD_SETTINGS['langDisplayMode'];
        // Set to true when current language is not an alteranative (in this case display all elements)
        $displayElement |= ($this->currentLanguageUid <= 0);
        // When language of CE is ALL or default display it.
        $displayElement |= ($subElementArr['el']['sys_language_uid'] <= 0);
        // Display elements which have their language set to the currently displayed language.
        $displayElement |= ($this->currentLanguageUid == $subElementArr['el']['sys_language_uid']);

        if (!static::$visibleContentHookObjectsPrepared) {
            $this->visibleContentHookObjects = $this->hooks_prepareObjectsArray('visibleContentClass');
            static::$visibleContentHookObjectsPrepared = true;
        }
        foreach ($this->visibleContentHookObjects as $hookObj) {
            if (method_exists($hookObj, 'displayElement')) {
                $hookObj->displayElement($subElementArr, $displayElement, $this);
            }
        }

        return $displayElement;
    }

    /**
     * Returns label, localized and converted to current charset. Label must be from FlexForm (= always in UTF-8).
     *
     * @param string $label Label
     * @param boolean $hsc <code>true</code> if HSC required
     *
     * @return string Converted label
     */
    public function localizedFFLabel($label, $hsc)
    {
        if (substr($label, 0, 4) === 'LLL:') {
            $label = TemplaVoilaUtility::getLanguageService()->sL($label);
        }
        $result = htmlspecialchars($label, $hsc);

        return $result;
    }

    /**
     * @param string $table
     * @param integer $id
     *
     * @return string
     */
    public function getRecordStatHookValue($table, $id)
    {
        // Call stats information hook
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'])) {
            $stat = '';
            $_params = array($table, $id);
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef) {
                $stat .= GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }

            return $stat;
        }

        return '';
    }

    public function getModuleTemplate()
    {
        return $this->moduleTemplate;
    }

    public function getModuleName()
    {
        return $this->moduleName;
    }

    public function getIconFactory()
    {
        return $this->iconFactory;
    }

    /**
     * Checks whether the datastructure for a new FCE contains the noEditOnCreation meta configuration
     *
     * @param integer $dsUid uid of the datastructure we want to check
     * @param integer $toUid uid of the tmplobj we want to check
     *
     * @return boolean
     */
    protected function editingOfNewElementIsEnabled($dsUid, $toUid)
    {
        if (!strlen($dsUid) || !(int)$toUid) {
            return true;
        }
        $editingEnabled = true;
        try {
            /** @var \Ppi\TemplaVoilaPlus\Domain\Repository\TemplateRepository $toRepo */
            $toRepo = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Domain\Repository\TemplateRepository::class);
            $to = $toRepo->getTemplateByUid($toUid);
            $xml = $to->getLocalDataprotArray();
            if (isset($xml['meta']['noEditOnCreation'])) {
                $editingEnabled = $xml['meta']['noEditOnCreation'] != 1;
            }
        } catch (InvalidArgumentException $e) {
            //  might happen if uid was not what the Repo expected - that's ok here
        }

        return $editingEnabled;
    }

    /**
     * Generates a hash for sortable items for html drag'n'drop.
     *
     * @param string $pointerStr the sourcePointer for the referenced element
     *
     * @return string the key for the related html-element
     */
    protected function getSortableItemHash($pointerStr)
    {
        $key = 'item' . md5($pointerStr);
        return $key;
    }

    /**
     * @param integer $pid
     *
     * @return integer
     */
    protected function getCalcPerms($pid)
    {
        if (!isset(self::$calcPermCache[$pid])) {
            $row = BackendUtility::getRecordWSOL('pages', $pid);
            $calcPerms = TemplaVoilaUtility::getBackendUser()->calcPerms($row);
            if (!$this->hasBasicEditRights('pages', $row)) {
                // unsetting the "edit content" right - which is 16
                $calcPerms = $calcPerms & ~16;
            }
            self::$calcPermCache[$pid] = $calcPerms;
        }

        return self::$calcPermCache[$pid];
    }

    /**
     * @param string $table
     * @param array $record
     *
     * @return boolean
     */
    protected function hasBasicEditRights($table = null, array $record = null)
    {
        if ($table == null) {
            $table = $this->rootElementTable;
        }

        if (empty($record)) {
            $record = $this->rootElementRecord;
        }

        if (TemplaVoilaUtility::getBackendUser()->isAdmin()) {
            $hasEditRights = true;
        } else {
            $id = $record[($table == 'pages' ? 'uid' : 'pid')];
            $pageRecord = BackendUtility::getRecordWSOL('pages', $id);

            $mayEditPage = TemplaVoilaUtility::getBackendUser()->doesUserHaveAccess($pageRecord, 16);
            $mayModifyTable = GeneralUtility::inList(TemplaVoilaUtility::getBackendUser()->groupData['tables_modify'], $table);
            $mayEditContentField = GeneralUtility::inList(TemplaVoilaUtility::getBackendUser()->groupData['non_exclude_fields'], $table . ':tx_templavoilaplus_flex');
            $hasEditRights = $mayEditPage && $mayModifyTable && $mayEditContentField;
        }

        return $hasEditRights;
    }

    /**
     * Calls defined hooks from TYPO3_CONF_VARS']['SC_OPTIONS']['templavoilaplus']['BackendLayout'][$hookName . 'FunctionHook']
     * and returns there result as combined string.
     *
     * @param string $hookName Name of the hook to call
     * @param array $params Paremeters to give to the called hook function
     *
     * @return string
     */
    protected function renderFunctionHook($hookName, $params = [])
    {
        $result = '';

        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['templavoilaplus']['BackendLayout'][$hookName . 'FunctionHook'])) {
            $renderFunctionHook = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['templavoilaplus']['BackendLayout'][$hookName . 'FunctionHook'];
            if (is_array($renderFunctionHook)) {
                foreach ($renderFunctionHook as $hook) {
                    $params = [];
                    $result .= (string) GeneralUtility::callUserFunction($hook, $params, $this);
                }
            }
        }


        return $result;
    }
}
