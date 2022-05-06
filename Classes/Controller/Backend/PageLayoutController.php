<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Controller\Backend;

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

use Tvp\TemplaVoilaPlus\Configuration\BackendConfiguration;
use Tvp\TemplaVoilaPlus\Core\Messaging\FlashMessage;
use Tvp\TemplaVoilaPlus\Domain\Repository\PageRepository;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

class PageLayoutController extends ActionController
{
    /**
     * Default View Container
     *
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * We define BackendTemplateView above so we will get it.
     *
     * @var BackendTemplateView
     * @api
     */
    protected $view;

    /**
     * @var int the id of current page
     */
    protected $pageId = 0;

    /**
     * Record of current page with _path information BackendUtility::readPageAccess
     *
     * @var array
     */
    protected $pageInfo;

    /**
     * Permissions for the current page
     *
     * @var int
     */
    protected $calcPerms;

    /**
     * @var array
     */
    protected static $calcPermCache = [];

    /**
     * TSconfig from mod.web_txtemplavoilaplusLayout.
     *
     * @var array
     */
    protected $modTSconfig = [];

    /**
     * TSconfig from mod.SHARED.
     *
     * @var array
     */
    protected $modSharedTSconfig = [];

    /**
     * Contains the currently selected language key (Example: DEF or DE)
     *
     * @var string
     */
    protected $currentLanguageKey;

    /**
     * Contains the currently selected language uid (Example: -1, 0, 1, 2, ...)
     *
     * @var int
     */
    protected $currentLanguageUid;

    /**
     * Contains records of all available languages (not hidden, with ISOcode), including the default
     * language and multiple languages. Used for displaying the flags for content elements, set in init().
     *
     * @var array
     */
    protected $allAvailableLanguages = [];

    /**
     * Contains requested fluid partials in rendering areas
     *
     * @var array
     */
    protected $contentPartials = [];

    /** @var \TYPO3\CMS\Backend\Clipboard\Clipboard */
    protected $typo3Clipboard;

    public function __construct()
    {
        $this->configuration = new BackendConfiguration();
    }

    /**
     * Initialize action
     */
    protected function initializeAction()
    {
        TemplaVoilaUtility::getLanguageService()->includeLLFile(
            'EXT:templavoilaplus/Resources/Private/Language/Backend/PageLayout.xlf'
        );

        // determine id parameter
        $this->pageId = (int)GeneralUtility::_GP('id');
        $pageTsConfig = BackendUtility::getPagesTSconfig($this->pageId);
        // @TODO Get rid of this properties key
        $this->modSharedTSconfig['properties'] = $pageTsConfig['mod.']['SHARED.'];
        $this->modTSconfig['properties'] = $pageTsConfig['mod.']['web_txtemplavoilaplusLayout.'];

        $this->initializeCurrentLanguage();

        // if pageId is available the row will be inside pageInfo
        $this->setPageInfo();
    }

    /**
     * Displays the page with layout and content elements
     */
    public function showAction()
    {
        $this->initializeTypo3Clipboard();
        $this->registerDocheaderButtons();
        $this->addViewConfiguration($this->view->getModuleTemplate()->getView());

        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

        $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($this->pageInfo);
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());

        $contentHeader = '';
        $contentBody = '';
        $contentFooter = '';

        $access = isset($this->pageInfo['uid']) && (int)$this->pageInfo['uid'] > 0;

        if ($access) {
            $this->calcPerms = $this->getCalcPerms($this->pageInfo['uid']);
            $this->checkContentFromPid();

            // Additional header content
            $contentHeader = $this->renderFunctionHook('renderHeader');

            // get body content
            $contentBody = $this->renderFunctionHook('renderBody', [], true);

            $activePage = $this->pageInfo;
            if ($this->currentLanguageUid !== 0) {
                $row = BackendUtility::getRecordLocalization('pages', $this->pageId, $this->currentLanguageUid);
                if ($row) {
                    $activePage = $row[0];
                }
            }
            $pageTitle = BackendUtility::getRecordTitle('pages', $activePage);

            $contentBody .= $this->callHandler(BackendConfiguration::HANDLER_DOCTYPE, $activePage['doktype'], $activePage);

            // Additional footer content
            $contentFooter = $this->renderFunctionHook('renderFooter');
        } else {
            if (GeneralUtility::_GP('id') === '0') {
                // normaly no page selected
                $this->addFlashMessage(
                    TemplaVoilaUtility::getLanguageService()->getLL('infoDefaultIntroduction'),
                    TemplaVoilaUtility::getLanguageService()->getLL('title'),
                    FlashMessage::INFO
                );
            } else {
                // NOt found or no show access
                $this->addFlashMessage(
                    TemplaVoilaUtility::getLanguageService()->getLL('infoPageNotFound'),
                    TemplaVoilaUtility::getLanguageService()->getLL('title'),
                    FlashMessage::INFO
                );
            }
        }

        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');

        $this->view->assign('pageId', $this->pageId);
        $this->view->assign('pageInfo', $this->pageInfo);
        $this->view->assign('pageTitle', $pageTitle);
        $this->view->assign('pageDoktype', $activePage['doktype']);
        $this->view->assign('pageMessages', $this->getFlashMessageQueue('TVP')->getAllMessages());

        $this->view->assign('calcPerms', $this->calcPerms);
        $this->view->assign('basicEditRights', $this->hasBasicEditRights());

        /** @TODO better handle this with an configuration object */
        $this->view->assign(
            'configuration',
            [
                'allAvailableLanguages' => $this->allAvailableLanguages,
                // If we have more then "all-languages" and 1 editors language available
                'moreThenOneLanguageAvailable' => count($this->allAvailableLanguages) > 2 ? true : false,
                'lllFile' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/PageLayout.xlf',
                'clipboard' => $this->clipboard2fluid(),
                'userSettings' => TemplaVoilaUtility::getBackendUser()->uc['templavoilaplus'] ?? [],
            ]
        );

        $this->view->assign('contentPartials', $this->contentPartials);
        // @TODO Deprecate following parts and the renderFunctionHooks? Replace them with Handlers?
        // Or use these hooks so they can add Partials?
        $this->view->assign('contentHeader', $contentHeader);
        $this->view->assign('contentBody', $contentBody);
        $this->view->assign('contentFooter', $contentFooter);

        $this->view->assignMultiple([
            'is8orNewer' => version_compare(TYPO3_version, '8.0.0', '>=') ? true : false,
            'is9orNewer' => version_compare(TYPO3_version, '9.0.0', '>=') ? true : false,
            'is10orNewer' => version_compare(TYPO3_version, '10.0.0', '>=') ? true : false,
            'is11orNewer' => version_compare(TYPO3_version, '11.0.0', '>=') ? true : false,
            'is12orNewer' => version_compare(TYPO3_version, '12.0.0', '>=') ? true : false,
        ]);
    }

    protected function checkContentFromPid()
    {
        // If content from different pid is displayed
        if ($this->pageInfo['content_from_pid']) {
            $contentPage = (array)BackendUtility::getRecord('pages', (int)$this->pageInfo['content_from_pid']);
            $linkToPage = GeneralUtility::linkThisScript(['id' => $this->pageInfo['content_from_pid']]);
            $title = BackendUtility::getRecordTitle('pages', $contentPage)
                . ' [' . $contentPage['uid'] . ']';

            $this->addFlashMessage(
                sprintf(
                    TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:content_from_pid_title'),
                    $title
                ),
                '',
                FlashMessage::INFO,
                false,
                [[
                    'url' => (string)$linkToPage,
                    'label' => $title,
                    'icon' => 'apps-pagetree-page-shortcut',
                ]]
            );
        }

        /** @var PageRepository */
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $pages = $pageRepository->getPagesUsingContentFrom($this->pageInfo['uid']);

        if (count($pages)) {
            $titles = [];
            $buttons = [];
            foreach ($pages as $contentPage) {
                $title = BackendUtility::getRecordTitle('pages', $contentPage)
                . ' [' . $contentPage['uid'] . ']';
                $titles[] = $title;
                $buttons[] = [
                    'url' => $linkToPage = GeneralUtility::linkThisScript(['id' => $contentPage['uid']]),
                    'label' => $title,
                    'icon' => 'apps-pagetree-page-shortcut',
                ];
            }

            $this->addFlashMessage(
                sprintf(
                    TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:content_on_pid_title'),
                    implode(', ', $titles)
                ),
                '',
                FlashMessage::INFO,
                false,
                $buttons
            );
        }
    }

    protected function initializeTypo3Clipboard()
    {
        // Initialize the t3lib clipboard:
        $this->typo3Clipboard = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Clipboard\Clipboard::class);
        $this->typo3Clipboard->initializeClipboard();
        $this->typo3Clipboard->lockToNormal();
    }

    protected function clipboard2fluid(): array
    {
        $clipboard = [
            'hasContent' => (isset($this->typo3Clipboard->clipData['normal']['el'])),
            'object' => $this->typo3Clipboard,
        ];

        if ($clipboard['hasContent']) {
            $element = key($this->typo3Clipboard->clipData['normal']['el']);
            [$clipboard['table'], $clipboard['uid']] = explode('|', $element);
            $clipboard['mode'] = $this->typo3Clipboard->clipData['normal']['mode'];
        }

        return $clipboard;
    }

    /**
     * Taken from ActionController but extended to ADD module configuration
     * @param ViewInterface $view
     */
    protected function addViewConfiguration(ViewInterface $view)
    {
        // Template Path Override
        $extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );

        $templatePaths = $view->getRenderingContext()->getTemplatePaths();

        // set TemplateRootPaths
        $setting = 'templateRootPaths';
        $parameter = $this->getViewProperty($extbaseFrameworkConfiguration, $setting);
        // no need to bother if there is nothing to set
        if ($parameter) {
            $parameter = $templatePaths->getTemplateRootPaths() + $parameter;
            $templatePaths->setTemplateRootPaths($parameter);
        }

        // set LayoutRootPaths
        $viewSetFunctionName = 'setLayoutRootPaths';
        $setting = 'layoutRootPaths';
        $parameter = $this->getViewProperty($extbaseFrameworkConfiguration, $setting);
        // no need to bother if there is nothing to set
        if ($parameter) {
            $parameter = $templatePaths->getLayoutRootPaths() + $parameter;
            $templatePaths->setLayoutRootPaths($parameter);
        }

        // set PartialRootPaths
        $setting = 'partialRootPaths';
        $parameter = $this->getViewProperty($extbaseFrameworkConfiguration, $setting);
        // no need to bother if there is nothing to set
        if ($parameter) {
            $parameter = $templatePaths->getPartialRootPaths() + $parameter;
            $templatePaths->setPartialRootPaths($parameter);
        }
    }

    public function addContentPartial($contentPart, $partialName)
    {
        $this->contentPartials[$contentPart][] = $partialName;
    }

    public function getModSharedTSconfig()
    {
        return $this->modSharedTSconfig;
    }

    /**
     * Registers the Icons into the docheader
     *
     * @throws \InvalidArgumentException
     */
    protected function registerDocheaderButtons()
    {
        $coreLangFile = 'LLL:EXT:' . TemplaVoilaUtility::getCoreLangPath() . 'locallang_core.xlf:';

        // View page
        $this->addDocHeaderButton(
            'view',
            TemplaVoilaUtility::getLanguageService()->sL($coreLangFile . 'labels.showPage'),
            'actions-document-view'
        );

        if (!$this->modTSconfig['properties']['disableIconToolbar']) {
            if (!$this->translatorMode) {
                if ($this->permissionPageNew()) {
                    // Create new page (wizard)
                    $this->addDocHeaderButton(
                        'db_new',
                        TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newPage'),
                        'actions-page-new',
                        [
                            'id' => $this->pageId,
                            'pagesOnly' => 1,
                        ],
                        ButtonBar::BUTTON_POSITION_LEFT,
                        2
                    );
                }

                if ($this->permissionPageEdit()) {
                    // Edit page properties
                    $this->addDocHeaderButton(
                        'record_edit',
                        TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:editPageProperties'),
                        'actions-page-open',
                        [
                            'edit' => [
                                'pages' => [
                                    $this->pageId => 'edit',
                                ],
                            ],
                        ]
                    );
                    // Move page
                    $this->addDocHeaderButton(
                        'move_element',
                        TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:move_page'),
                        'actions-page-move',
                        [
                            'table' => 'pages',
                            'uid' => $this->pageId,
                        ],
                        ButtonBar::BUTTON_POSITION_LEFT,
                        2
                    );
                }
            }

            // Page history
            $this->addDocHeaderButton(
                'record_history',
                TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:recordHistory'),
                'actions-document-history-open',
                [
                    'element' => 'pages:' . $this->pageId,
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
                TemplaVoilaUtility::getLanguageService()->sL($coreLangFile . 'labels.showList'),
                'actions-system-list-open',
                [
                    'id' => $this->pageId,
                ],
                ButtonBar::BUTTON_POSITION_RIGHT,
                1
            );
        }

        if ($this->pageId) {
            $this->addDocHeaderButton(
                'tce_db',
                TemplaVoilaUtility::getLanguageService()->sL($coreLangFile . 'labels.clear_cache'),
                'actions-system-cache-clear',
                [
                    'cacheCmd' => $this->pageId,
                    'redirect' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                ],
                ButtonBar::BUTTON_POSITION_RIGHT,
                2
            );
        }
    }

    /**
     * Adds an icon button to the document header button bar (left or right)
     *
     * @param string $module Name of the module this icon should link to
     * @param string $title Title of the button
     * @param string $icon Name of the Icon (inside IconFactory)
     * @param array $params Array of parameters which should be added to module call
     * @param string $buttonPosition left|right to position button inside the bar
     * @param int $buttonGroup Number of the group the icon should go in
     */
    public function addDocHeaderButton(
        $module,
        $title,
        $icon,
        array $params = [],
        $buttonPosition = ButtonBar::BUTTON_POSITION_LEFT,
        $buttonGroup = 1
    ) {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();

        $url = '#';
        $onClick = null;

        switch ($module) {
            case 'view':
                $viewAddGetVars = $this->currentLanguageUid ? '&L=' . $this->currentLanguageUid : '';
                $onClick = BackendUtility::viewOnClick(
                    $this->pageId,
                    '',
                    BackendUtility::BEgetRootLine($this->pageId),
                    '',
                    '',
                    $viewAddGetVars
                );
                break;
            default:
                if (version_compare(TYPO3_version, '9.0.0', '>=')) {
                    /** @var $uriBuilder \TYPO3\CMS\Backend\Routing\UriBuilder */
                    $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
                    $url = $uriBuilder->buildUriFromRoute(
                        $module,
                        array_merge(
                            $params,
                            [
                                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                            ]
                        )
                    );
                } else {
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
        }
        $button = $buttonBar->makeLinkButton()
            ->setHref($url)
            ->setOnClick($onClick)
            ->setTitle($title)
            ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon($icon, Icon::SIZE_SMALL));
        $buttonBar->addButton($button, $buttonPosition, $buttonGroup);
    }

    /**
     * Adds csh icon to the right document header button bar
     */
    public function addCshButton($fieldName)
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();

        $contextSensitiveHelpButton = $buttonBar->makeHelpButton()
            ->setModuleName('_MOD_Backend\PageLayout')
            ->setFieldName($fieldName);
        $buttonBar->addButton($contextSensitiveHelpButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * Adds shortcut icon to the right document header button bar
     */
    public function addShortcutButton()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();

        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName('Backend\PageLayout')
            ->setGetVariables(
                [
                    'id',
                    'M',
                    'edit_record',
                    'pointer',
                    'new_unique_uid',
                    'search_field',
                    'search_levels',
                    'showLimit',
                ]
            )
            ->setSetVariables([]/*array_keys($this->MOD_MENU) @TODO*/);
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * Check if page record exists and set pageInfo
     */
    protected function setPageInfo()
    {
        $pagePermsClaus = TemplaVoilaUtility::getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        $this->pageInfo = BackendUtility::readPageAccess($this->pageId, $pagePermsClaus);
    }

    /**
     * @param int $pid
     * @TODO Cache realy needed? Statically?
     * @TODO Use constant instead of value 16!
     *
     * @return int
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
     * @TODO Use constant instead of value 16!
     * @TODO rootElement needed? View page content partially?
     *
     * @return bool
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
     * @param string $key
     *
     * @return mixed
     */
    public function getSetting($key)
    {
        return isset($this->settings[$key]) ? $this->settings[$key] : null;
    }

    public function getView()
    {
        return $this->view;
    }

    /**
     * Calls defined hooks from TYPO3_CONF_VARS']['SC_OPTIONS']['templavoilaplus']['BackendLayout'][$hookName . 'FunctionHook']
     * and returns there result as combined string.
     *
     * @param string $hookName Name of the hook to call
     * @param array $params Paremeters to give to the called hook function
     * @param bool $stopOnConsume stop calling more function hooks if a result is not false/empty
     *
     * @return string
     */
    protected function renderFunctionHook($hookName, $params = [], $stopOnConsume = false)
    {
        $result = '';

        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['templavoilaplus']['BackendLayout'][$hookName . 'FunctionHook'])) {
            $renderFunctionHook = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['templavoilaplus']['BackendLayout'][$hookName . 'FunctionHook'];
            if (is_array($renderFunctionHook)) {
                foreach ($renderFunctionHook as $hook) {
                    $params = [];
                    $result .= (string)GeneralUtility::callUserFunction($hook, $params, $this);
                    if ($stopOnConsume && $result) {
                        break;
                    }
                }
            }
        }

        return $result;
    }

    protected function callHandler($type, $key, ...$param)
    {
        if ($this->configuration->haveItem($type, $key)) {
            $handler = GeneralUtility::makeInstance($this->configuration->getItem($type, $key));
            return $handler->handle($this, ...$param);
        }
    }

    protected function initializeCurrentLanguage()
    {
        // Fill array allAvailableLanguages and currently selected language (from language selector or from outside)
        $this->allAvailableLanguages = TemplaVoilaUtility::getAvailableLanguages($this->pageId, true, true, $this->modSharedTSconfig);
        $languageFromSession = (int)TemplaVoilaUtility::getBackendUser()->getSessionData('templavoilaplus.language');
        // determine language parameter
        $this->currentLanguageUid = (int)GeneralUtility::_GP('language') > 0
            ? (int)GeneralUtility::_GP('language')
            : $languageFromSession;
        if ($this->request->hasArgument('language')) {
            $this->currentLanguageUid = (int)$this->request->getArgument('language');
        }
        // Check if language is available
        if (!isset($this->allAvailableLanguages[$this->currentLanguageUid])) {
            $this->currentLanguageUid = 0;
        }
        // if changed save to session
        if ($languageFromSession !== $this->currentLanguageUid) {
            TemplaVoilaUtility::getBackendUser()->setAndSaveSessionData(
                'templavoilaplus.language',
                $this->currentLanguageUid
            );
        }
        $this->currentLanguageKey = $this->allAvailableLanguages[$this->currentLanguageUid]['ISOcode'];
    }

    /**
     * Check if new page can be created by current user
     *
     * @return bool
     */
    public function permissionPageNew(): bool
    {
        return TemplaVoilaUtility::getBackendUser()->isAdmin() || ($this->calcPerms & Permission::PAGE_NEW + Permission::CONTENT_EDIT) === Permission::PAGE_NEW + Permission::CONTENT_EDIT;
    }

    /**
     * Check if page can be edited by current user
     *
     * @return bool
     */
    public function permissionPageEdit(): bool
    {
        return TemplaVoilaUtility::getBackendUser()->isAdmin() || ($this->calcPerms & Permission::PAGE_EDIT) === Permission::PAGE_EDIT;
    }

    /**
     * Check if content can be edited by current user
     *
     * @return bool
     */
    public function permissionContentEdit(): bool
    {
        return TemplaVoilaUtility::getBackendUser()->isAdmin() || ($this->calcPerms & Permission::CONTENT_EDIT) === Permission::CONTENT_EDIT;
    }

    /**
     * Creates a Message object and adds it to the FlashMessageQueue.
     *
     * @param string $messageBody The message
     * @param string $messageTitle Optional message title
     * @param int $severity Optional severity, must be one of \TYPO3\CMS\Core\Messaging\FlashMessage constants
     * @param bool $storeInSession Optional, defines whether the message should be stored in the session (default) or not
     * @param array $buttons Optional array of button configuration
     * @throws \InvalidArgumentException if the message body is no string
     */
    public function addFlashMessage(
        $messageBody,
        $messageTitle = '',
        $severity = FlashMessage::OK,
        $storeInSession = false,
        array $buttons = []
    ) {
        /* @var \Tvp\TemplaVoilaPlus\Core\Messaging\FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            (string)$messageBody,
            (string)$messageTitle,
            $severity,
            $storeInSession,
            $buttons
        );

        $this->getFlashMessageQueue('TVP')->enqueue($flashMessage);
    }
}
