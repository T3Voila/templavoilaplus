<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Controller\Backend\Handler;

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

use Tvp\TemplaVoilaPlus\Controller\Backend\PageLayoutController;
use Tvp\TemplaVoilaPlus\Domain\Repository\PageRepository;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DoktypeShortcutHandler extends AbstractDoktypeHandler
{
    /**
     * Displays the edit page screen if the currently selected page is of the doktype "Shortcut"
     *
     * @param PageLayoutController $controller
     * @param array $pageRecord The current page record
     *
     * @return string HTML output from this submodule
     */
    public function handle(PageLayoutController $controller, array $pageRecord): string
    {
        self::addLocalizationInformationForPage($controller, $pageRecord);
        $targetUid = 0;
        $targetPageRecord = [];
        $shortcutMode = (int)$pageRecord['shortcut_mode'];

        $pageRepositoryClass = PageRepository::class;

        switch ($shortcutMode) {
            // Should be SHORTCUT_MODE_SELECT
            case $pageRepositoryClass::SHORTCUT_MODE_NONE:
                // Use selected page
                $targetUid = (int)$pageRecord['shortcut'];
                break;
            case $pageRepositoryClass::SHORTCUT_MODE_FIRST_SUBPAGE:
                // First subpage of current/selected page
                $pageRepository = GeneralUtility::makeInstance($pageRepositoryClass);
                $subpages = $pageRepository->getMenu((int)$pageRecord['shortcut'] ?: (int)$pageRecord['uid']);
                if (count($subpages)) {
                    $result = array_values($subpages)[0];
                }
                if ($result) {
                    $targetUid = $result['uid'];
                }
                break;
            case $pageRepositoryClass::SHORTCUT_MODE_PARENT_PAGE:
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
            case $pageRepositoryClass::SHORTCUT_MODE_RANDOM_SUBPAGE:
                // Random subpage of current/selected page
            default:
                // Random and other shortcut modes not supported
                break;
        }

        $url = '';
        if ($targetUid) {
            $targetPageRecord = BackendUtility::getRecordWSOL('pages', $targetUid);
            /** @var $uriBuilder \TYPO3\CMS\Backend\Routing\UriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
            $url = $uriBuilder->buildUriFromRoute(
                'web_TemplaVoilaPlusLayout',
                [
                    'id' => $targetUid,
                ]
            );
        }

        $controller->addFlashMessage(
            sprintf(
                TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/PageLayout.xlf:infoDoktypeShortcutCannotEdit' . $shortcutMode),
                $targetPageRecord ? BackendUtility::getRecordTitle('pages', $targetPageRecord) : ''
            ),
            TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/PageLayout.xlf:titleDoktypeShortcut'),
            FlashMessage::INFO,
            false,
            [[
                'url' => (string)$url,
                'label' => TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/PageLayout.xlf:hintDoktypeShortcutJumpToDestination'),
                'icon' => 'apps-pagetree-page-shortcut',
            ]]
        );

        return '';
    }
}
