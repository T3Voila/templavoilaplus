<?php
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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

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
     * Displays the page with layout and content elements
     */
    public function showAction()
    {
        $this->registerDocheaderButtons();
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
                        TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:editPageProperties'),
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
                        TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:move_page'),
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
                TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:recordHistory'),
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
                TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.showList'),
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
                TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.clear_cache'),
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
            ->setSetVariables([]/*array_keys($this->MOD_MENU)*/);
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }
}
