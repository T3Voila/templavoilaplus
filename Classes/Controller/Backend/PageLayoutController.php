<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Controller\Backend;

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

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\Page\PageRepository;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

class PageLayoutController extends ActionController
{
    /**
     * Default View Container
     *
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

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
     * @var integer
     */
    public $calcPerms;

    /**
     * @var array
     */
    static protected $calcPermCache = array();

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
        // if pageId is available the row will be inside pageInfo
        $this->setPageInfo();
    }

    /**
     * Displays the page with layout and content elements
     */
    public function showAction()
    {
        $this->registerDocheaderButtons();
        $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($this->pageInfo);
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());

        $contentHeader = '';
        $contentBody = '';
        $contentFooter = '';
        
        $access = isset($this->pageInfo['uid']) && (int)$this->pageInfo['uid'] > 0;

        if ($access) {
            // Additional header content
            $contentHeader .= $this->renderFunctionHook('renderHeader');

            $this->calcPerms = $this->getCalcPerms($this->pageInfo['uid']);

            // Additional footer content
            $contentFooter .= $this->renderFunctionHook('renderFooter');
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
    }

    /**
     * Registers the Icons into the docheader
     *
     * @throws \InvalidArgumentException
     */
    protected function registerDocheaderButtons()
    {
        // View page
        $this->addDocHeaderButton(
            'view',
            TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.showPage'),
            'actions-document-view'
        );

        if (!$this->modTSconfig['properties']['disableIconToolbar']) {
            if (!$this->translatorMode) {
                if (TemplaVoilaUtility::getBackendUser()->isPSet($this->calcPerms, 'pages', 'new')) {
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

                if (TemplaVoilaUtility::getBackendUser()->isPSet($this->calcPerms, 'pages', 'edit')) {
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
                            'uid'=> $this->pageId,
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
                TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.showList'),
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
                TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.clear_cache'),
                'actions-system-cache-clear',
                [
                    'cacheCmd'=> $this->pageId,
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
                    'showLimit'
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
     * @param integer $pid
     * @TODO Cache realy needed? Statically?
     * @TODO Use constant instead of value 16!
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
     * @TODO Use constant instead of value 16!
     * @TODO rootElement needed? View page content partially?
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
