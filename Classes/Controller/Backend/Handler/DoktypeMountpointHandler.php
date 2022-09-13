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
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;

class DoktypeMountpointHandler extends AbstractDoktypeHandler
{
    /**
     * Displays the edit page screen if the currently selected page is of the doktype "Mount Point"
     *
     * @param PageLayoutController $controller
     * @param array $pageRecord The current page record
     *
     * @return string HTML output from this submodule
     */
    public function handle(PageLayoutController $controller, array $pageRecord): string
    {
        self::addLocalizationInformationForPage($controller,$pageRecord);
        // Mountpoint starts here but start content is taken from this page
        if (!$pageRecord['mount_pid_ol']) {
            // @TODO How to include DoktypePage from here?
            return '';
        }

        $mountSourcePageRecord = BackendUtility::getRecordWSOL('pages', $pageRecord['mount_pid']);

        if (version_compare(TYPO3_version, '9.0.0', '>=')) {
            /** @var $uriBuilder \TYPO3\CMS\Backend\Routing\UriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
            $url = $uriBuilder->buildUriFromRoute(
                'web_TemplaVoilaPlusLayout',
                [
                    'id' => $pageRecord['mount_pid'],
                ]
            );
        } else {
            $url = BackendUtility::getModuleUrl(
                'web_TemplaVoilaPlusLayout',
                [
                    'id' => $pageRecord['mount_pid'],
                ]
            );
        }

        $controller->addFlashMessage(
            sprintf(
                TemplaVoilaUtility::getLanguageService()->getLL('infoDoktypeMountpointCannotEdit'),
                $mountSourcePageRecord['title']
            ),
            TemplaVoilaUtility::getLanguageService()->getLL('titleDoktypeMountpoint'),
            FlashMessage::INFO,
            false,
            [[
                'url' => (string)$url,
                'label' => TemplaVoilaUtility::getLanguageService()->getLL('hintDoktypeMountpointOpen', true),
                'icon' => 'apps-pagetree-page-mountpoint',
            ]]
        );

        return '';
    }
}
