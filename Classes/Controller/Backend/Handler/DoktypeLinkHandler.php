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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use Tvp\TemplaVoilaPlus\Controller\Backend\PageLayoutController;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

class DoktypeLinkHandler
{
    /**
     * Displays the edit page screen if the currently selected page is of the doktype "External URL"
     *
     * @param PageLayoutController $controller
     * @param array $pageRecord The current page record
     *
     * @return string HTML output from this submodule
     */
    public function handle(PageLayoutController $controller, array $pageRecord): string
    {
        if (version_compare(TYPO3_version, '9.0.0', '>=')) {
            $controller->addFlashMessage(
                sprintf(
                    TemplaVoilaUtility::getLanguageService()->getLL('infoDoktypeLinkCannotEdit', true),
                    htmlspecialchars($pageRecord['url'])
                ),
                TemplaVoilaUtility::getLanguageService()->getLL('titleDoktypeLink'),
                FlashMessage::INFO
            );

            return $this->getLinkButton($controller, $pageRecord['url']);
        } else {
            return $this->handle8($controller, $pageRecord);
        }
    }

    public function handle8(PageLayoutController $controller, array $pageRecord)
    {
        switch ($pageRecord['urltype']) {
            case 2:
                $url = 'ftp://' . $pageRecord['url'];
                break;
            case 3:
                $url = 'mailto:' . $pageRecord['url'];
                break;
            case 4:
                $url = 'https://' . $pageRecord['url'];
                break;
            default:
                // Check if URI scheme already present. We support only Internet-specific notation,
                // others are not relevant for us (see http://www.ietf.org/rfc/rfc3986.txt for details)
                if (preg_match('/^[a-z]+[a-z0-9\+\.\-]*:\/\//i', $pageRecord['url'])) {
                    // Do not add any other scheme
                    $url = $pageRecord['url'];
                    break;
                }
            // fall through
            case 1:
                $url = 'http://' . $pageRecord['url'];
                break;
        }

        // check if there is a notice on this URL type
        $notice = TemplaVoilaUtility::getLanguageService()->getLL('infoDoktypeLinkCannotEdit' . $pageRecord['urltype'], true);
        if (!$notice) {
            $notice = TemplaVoilaUtility::getLanguageService()->getLL('infoDoktypeLinkCannotEdit1', true);
        }

        $controller->addFlashMessage(
            $notice,
            TemplaVoilaUtility::getLanguageService()->getLL('titleDoktypeLink'),
            FlashMessage::INFO,
            false,
            [[
                'url' => (string)$url,
                'label' => TemplaVoilaUtility::getLanguageService()->getLL('hintDoktypeLinkOpen', true),
                'icon' => 'apps-pagetree-page-shortcut-external',
            ]]
        );

        return '';
    }
}
