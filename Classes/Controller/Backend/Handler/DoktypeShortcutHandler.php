<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Controller\Backend\Handler;

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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Frontend\Page\PageRepository;

use Ppi\TemplaVoilaPlus\Controller\Backend\PageLayoutController;
use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

class DoktypeShortcutHandler
{

    /**
     * Displays the edit page screen if the currently selected page is of the doktype "Shortcut"
     *
     * @param PageLayoutController $controller
     * @param array $pageRecord The current page record
     *
     * @return string HTML output from this submodule
     */
    public function handle(PageLayoutController $controller, array $pageRecord)
    {
        $targetUid = 0;
        $targetPageRecord = [];
        $shortcutMode = (int)$pageRecord['shortcut_mode'];
        
        switch ($shortcutMode) {
            case PageRepository::SHORTCUT_MODE_NONE: // Should be SHORTCUT_MODE_SELECT
                // Use selected page
                $targetUid = (int)$pageRecord['shortcut'];
                break;
            case PageRepository::SHORTCUT_MODE_FIRST_SUBPAGE:
                // First subpage of current/selected page
                $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
                $result = $pageRepository->getFirstWebPage((int)$pageRecord['shortcut'] ?: (int)$pageRecord['uid']);
                if ($result) {
                    $targetUid = $result['uid'];
                }
                break;
            case PageRepository::SHORTCUT_MODE_PARENT_PAGE:
                // Parent page of current/selected page
                if ((int)$pageRecord['shortcut']) {
                    $shortcutTargetRecord = BackendUtility::getRecord('pages', (int)$pageRecord['shortcut']);
                    if ($shortcutTargetRecord) {
                        $targetUid = (int)$shortcutTargetRecord['pid'];
                    }
                } else {
                    $targetUid = (int)$pageRecord['pid'];
                }
                break;
            case PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE:
                // Random subpage of current/selected page
            default:
                // Random and other shortcut modes not supported
                break;
        }

        if ($targetUid) {
            $targetPageRecord = BackendUtility::getRecordWSOL('pages', $targetUid);
            $url = BackendUtility::getModuleUrl(
                'web_txtemplavoilaplusLayout',
                [
                    'id' => $this->pageId,
                ]
            );
        }

        $controller->addFlashMessage(
            sprintf(
                TemplaVoilaUtility::getLanguageService()->getLL('infoDoktypeShortcutCannotEdit' . $shortcutMode),
                $targetPageRecord ? BackendUtility::getRecordTitle('pages', $targetPageRecord) : ''
            ),
            TemplaVoilaUtility::getLanguageService()->getLL('titleDoktypeShortcut'),
            FlashMessage::INFO
        );

        if ($targetUid) {
            return $this->getLinkButton($controller, $url);
        }

        return '';
    }
    
    protected function getLinkButton(PageLayoutController $controller, $url)
    {
        if ($url && parse_url($url)) {
            return '<a href="' . $url . '"'
                . ' class="btn btn-default btn-sm"'
                . '>'
                . $controller->getView()->getModuleTemplate()->getIconFactory()->getIcon('apps-pagetree-page-shortcut', Icon::SIZE_SMALL)->render()
                . ' ' . sprintf(
                    TemplaVoilaUtility::getLanguageService()->getLL('hintDoktypeShortcutJumpToDestination', true),
                    htmlspecialchars($url)
                )
                . '</a>';
        }
        return '';
    }
}
