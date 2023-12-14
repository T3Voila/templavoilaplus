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
use Tvp\TemplaVoilaPlus\Domain\Repository\Localization\LocalizationRepository;
use Tvp\TemplaVoilaPlus\Domain\Repository\PageRepository;
use Tvp\TemplaVoilaPlus\Utility\IconUtility;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class PageLayoutController extends ActionController
{
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
     * @var array|false
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
    protected $currentLanguageUid = 0;

    /**
     * Contains the current language mode (strict, fallback, fallback-to-what)
     */
    protected $languageAspect = null;

    /**
     * Contains records of all available languages (not hidden, with ISOcode), including the default
     * language and multiple languages. Used for displaying the flags for content elements, set in init().
     *
     * @var array
     */
    protected $allAvailableLanguages = [];

    /**
     * Same as $allAvailableLanguages, except filtered for if a valid page record for the language exists.
     * the translated pages can be hidden, but not deleted.
     *
     * @var array
     */
    protected $allExistingPageLanguages = [];

    /**
     * the languages navbar should only be enabled if necessary
     * @var bool
     */
    protected $localizationPossible = true;

    /**
     * Contains requested fluid partials in rendering areas
     *
     * @var array
     */
    protected $contentPartials = [];

    /** @var \TYPO3\CMS\Backend\Clipboard\Clipboard */
    protected $typo3Clipboard;

    /** @TODO: previously undefined members, needed for 8.1 compat */
    protected $translatorMode;
    protected $rootElementTable;
    protected $rootElementRecord;

    /** @var BackendConfiguration */
    protected $configuration;

    /** @var Typo3Version */
    protected $typo3Version;

    /** @var ModuleTemplateFactory  */
    protected $moduleTemplateFactory;

    /** @var PageRenderer */
    protected $pageRenderer;

    /** @var IconFactory */
    protected $iconFactory;

    public function __construct(
        Typo3Version $typo3Version,
        ModuleTemplateFactory $moduleTemplateFactory,
        IconFactory $iconFactory
    ) {
        $this->typo3Version = $typo3Version;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->configuration = new BackendConfiguration();
        $this->iconFactory = $iconFactory;
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
        $this->modSharedTSconfig['properties'] = $pageTsConfig['mod.']['SHARED.'] ?? null;
        $this->modTSconfig['properties'] = $pageTsConfig['mod.']['web_txtemplavoilaplusLayout.'] ?? null;

        $this->initializeCurrentLanguage();

        // if pageId is available the row will be inside pageInfo
        $this->setPageInfo();

        /** @TODO better handle this with an configuration object */
        $this->settings['configuration'] = [
            'allAvailableLanguages' => $this->allAvailableLanguages,
            // If we have more then "all-languages" and 1 editors language available
            'moreThenOneLanguageAvailable' => count($this->allAvailableLanguages) > 2 ? true : false,
            'allExistingPageLanguages' => $this->allExistingPageLanguages,
            'moreThanOneLanguageShouldBeShown' => count($this->allExistingPageLanguages) > 1 ? true : false,
            'languageAspect' => $this->languageAspect,
            'localizationPossible' => $this->localizationPossible,
            'lllFile' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/PageLayout.xlf',
            'userSettings' => TemplaVoilaUtility::getBackendUser()->uc['templavoilaplus'] ?? [],
            'is11orNewer' => version_compare($this->typo3Version->getVersion(), '11.0.0', '>=') ? true : false,
            'is12orNewer' => version_compare($this->typo3Version->getVersion(), '12.0.0', '>=') ? true : false,
            'is13orNewer' => version_compare($this->typo3Version->getVersion(), '13.0.0', '>=') ? true : false,
            'TCA' => $GLOBALS['TCA'],
        ];
    }

    public function getCurrentLanguageUid(): int
    {
        return $this->currentLanguageUid;
    }

    public function getCurrentPageUid(): int
    {
        return $this->pageId;
    }

    public function getCurrentPageInfo(): array
    {
        return $this->pageInfo;
    }

    /**
     * Displays the page with layout and content elements
     */
    public function showAction()
    {
        $this->initializeTypo3Clipboard();

        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

        $this->view->getRenderingContext()->getTemplatePaths()->fillDefaultsByPackageName('templavoilaplus');

        $contentHeader = '';
        $contentBody = '';
        $contentFooter = '';

        $access = isset($this->pageInfo['uid']) && (int)$this->pageInfo['uid'] > 0;

        if ($access) {
            $this->calcPerms = $this->getCalcPerms($this->pageInfo['uid']);
            $this->checkContentFromPid();

            $activePage = $this->pageInfo;
            if ($this->currentLanguageUid !== 0) {
                $row = BackendUtility::getRecordLocalization('pages', $this->pageId, $this->currentLanguageUid);
                if ($row) {
                    $activePage = $row[0];
                }
            }
            $pageTitle = BackendUtility::getRecordTitle('pages', $activePage);

            $contentBody .= $this->callHandler(BackendConfiguration::HANDLER_DOCTYPE, $activePage['doktype'], $activePage);
        } else {
            $pageTitle = '';
            if (GeneralUtility::_GP('id') === null || GeneralUtility::_GP('id') === '0') {
                //  no page selected
                $this->addFlashMessage(
                    TemplaVoilaUtility::getLanguageService()->getLL('infoDefaultIntroduction'),
                    TemplaVoilaUtility::getLanguageService()->getLL('title'),
                    FlashMessage::INFO
                );
                $this->view->assign('tutorial', true);
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
        $this->view->assign('pageDescription', $activePage[$GLOBALS['TCA']['pages']['ctrl']['descriptionColumn']] ?? '');
        $this->view->assign('pageDoktype', $activePage['doktype'] ?? null);
        $this->view->assign('pageMessages', $this->getFlashMessageQueue('TVP')->getAllMessages());

        $this->view->assign('calcPerms', $this->calcPerms);
        $this->view->assign('basicEditRights', $this->hasBasicEditRights(isset($this->pageInfo['uid']) ? 'pages' : null, isset($this->pageInfo['uid']) ? $this->pageInfo : null));
        $this->view->assign('clipboard', $this->clipboard2fluid());


        $this->view->assign('localization', LocalizationRepository::fetchRecordLocalizations('pages', $this->pageId));
        $this->view->assign('contentPartials', $this->contentPartials);

        $this->view->assign('settings', $this->settings);

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $event = $this->eventDispatcher->dispatch(new ModifyPageLayoutContentEvent($this->request, $moduleTemplate));
        $this->view->assign('contentHeader', $event->getHeaderContent());
        $this->view->assign('contentFooter', $event->getFooterContent());

        if ($this->pageInfo !== false) {
            $moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageInfo);
        }
        $this->registerDocheaderButtons($moduleTemplate);

        // $this->view->setFlashMessageQueue($this->getFlashMessageQueue());

        $moduleTemplate->setContent($this->view->render('Show'));
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * This checks pages.content_from_pid in both directions to show if this page shows other content
     * or if this pages content is shown somewhere else
     *
     * @return void
     */
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
                    'icon' => IconUtility::getRecordIconIdentifier('pages', $contentPage['uid'], 'apps-pagetree-page-shortcut'),
                ]]
            );
        }

        // If this pages content is displayed somewhere else
        /** @var PageRepository */
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $pages = $pageRepository->getPagesUsingContentFrom((int)$this->pageInfo['uid']);

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
                    'icon' => IconUtility::getRecordIconIdentifier('pages', $contentPage['uid'], 'apps-pagetree-page-shortcut'),
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
        $clipBoard = [
            '__totalCount__' => 0,
        ];

        foreach ($this->typo3Clipboard->clipData as $clipBoardName => $clipBoardData) {
            if (isset($clipBoardData['el'])) {
                foreach ($clipBoardData['el'] as $clipBoardElement => $value) {
                    [$table, $uid] = explode('|', $clipBoardElement);
                    if (!isset($clipBoard[$table])) {
                        $clipBoard[$table] = ['count' => 0];
                    }
                    $clipBoard['__totalCount__']++;
                    $clipBoard[$table]['count']++;
                }
            }
        }

        return $clipBoard;
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
    protected function registerDocheaderButtons(ModuleTemplate $moduleTemplate)
    {
        $coreLangFile = 'LLL:EXT:' . TemplaVoilaUtility::getCoreLangPath() . 'locallang_core.xlf:';

        // View page
        $this->addDocHeaderButton(
            $moduleTemplate,
            'view',
            TemplaVoilaUtility::getLanguageService()->sL($coreLangFile . 'labels.showPage'),
            'actions-document-view'
        );
        if (!($this->modTSconfig['properties']['disableIconToolbar'] ?? null)) {
            if (!$this->translatorMode) {
                if ($this->permissionPageNew()) {
                    // Create new page (wizard)
                    $this->addDocHeaderButton(
                        $moduleTemplate,
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
                        $moduleTemplate,
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
                        $moduleTemplate,
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
                $moduleTemplate,
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
                $moduleTemplate,
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
                $moduleTemplate,
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
        ModuleTemplate $moduleTemplate,
        $module,
        $title,
        $icon,
        array $params = [],
        $buttonPosition = ButtonBar::BUTTON_POSITION_LEFT,
        $buttonGroup = 1
    ) {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $url = '#';

        switch ($module) {
            case 'view':
                $previewDataAttributes = PreviewUriBuilder::create((int)$this->pageId)
                    ->withRootLine(BackendUtility::BEgetRootLine($this->pageId))
                    ->withLanguage($this->currentLanguageUid)
                    ->buildDispatcherDataAttributes();
                break;
            default:
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
        }
        $button = $buttonBar->makeLinkButton()
            ->setDataAttributes($previewDataAttributes ?? [])
            ->setHref($url)
            ->setTitle($title)
            ->setIcon($this->iconFactory->getIcon($icon, Icon::SIZE_SMALL));
        $buttonBar->addButton($button, $buttonPosition, $buttonGroup);
    }

    /**
     * Adds csh icon to the right document header button bar
     */
    public function addCshButton($fieldName)
    {
        /** @var ButtonBar $buttonBar */
        // $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        //
        // $contextSensitiveHelpButton = $buttonBar->makeHelpButton()
        //     ->setModuleName('_MOD_Backend\PageLayout')
        //     ->setFieldName($fieldName);
        // $buttonBar->addButton($contextSensitiveHelpButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * Adds shortcut icon to the right document header button bar
     */
    public function addShortcutButton()
    {
        /** @var ButtonBar $buttonBar */
        // $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        //
        // $shortcutButton = $buttonBar->makeShortcutButton()
        //     ->setModuleName('Backend\PageLayout')
        //     ->setGetVariables(
        //         [
        //             'id',
        //             'M',
        //             'edit_record',
        //             'pointer',
        //             'new_unique_uid',
        //             'search_field',
        //             'search_levels',
        //             'showLimit',
        //         ]
        //     )
        //     ->setSetVariables([]/*array_keys($this->MOD_MENU) @TODO*/);
        // $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
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
            $id = $record[($table == 'pages' ? 'uid' : 'pid')] ?? 0;
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
     * Calls defined hooks from TYPO3_CONF_VARS']['SC_OPTIONS']['templavoilaplus']['PageLayout'][$hookName . 'FunctionHook']
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

        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['templavoilaplus']['PageLayout'][$hookName . 'FunctionHook'])) {
            $renderFunctionHook = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['templavoilaplus']['PageLayout'][$hookName . 'FunctionHook'];
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
        // navbarLanguage should be disabled if the site has just one language
        $this->localizationPossible = count($this->allAvailableLanguages) > 2;
        $this->allExistingPageLanguages = TemplaVoilaUtility::getExistingPageLanguages($this->pageId, true, true, $this->modSharedTSconfig);
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

        $this->languageAspect = TemplaVoilaUtility::fetchLanguageAspect($this->pageId, $this->currentLanguageUid);
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
