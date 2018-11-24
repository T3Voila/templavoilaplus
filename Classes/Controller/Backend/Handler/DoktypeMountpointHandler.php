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

class DoktypeMountpointHandler
{
    /**
     * Displays the edit page screen if the currently selected page is of the doktype "Mount Point"
     *
     * @param PageLayoutController $controller
     * @param array $pageRecord The current page record
     *
     * @return string HTML output from this submodule
     */
    public function handle(PageLayoutController $controller, array $pageRecord)
    {
        // Mountpoint starts here but start content is taken from this page
        if (!$pageRecord['mount_pid_ol']) {
            // @TODO How to include DoktypePage from here?
            return '';
        }

        $mountSourcePageRecord = BackendUtility::getRecordWSOL('pages', $pageRecord['mount_pid']);

        $controller->addFlashMessage(
            sprintf(
                TemplaVoilaUtility::getLanguageService()->getLL('infoDoktypeMountpointCannotEdit'), 
                $mountSourcePageRecord['title']
            ),
            TemplaVoilaUtility::getLanguageService()->getLL('titleDoktypeMountpoint'),
            FlashMessage::INFO
        );

        return $this->getLinkButton(
            $controller, 
            BackendUtility::getModuleUrl(
                'web_txtemplavoilaplusLayout',
                [
                    'id' => $pageRecord['mount_pid'],
                ]
            )
        );
            ;
    }
    
    protected function getLinkButton(PageLayoutController $controller, $url)
    {
        if ($url && parse_url($url)) {
            return '<a href="' . $url . '"'
                . ' class="btn btn-default btn-sm"'
                . '>'
                . $controller->getView()->getModuleTemplate()->getIconFactory()->getIcon('apps-pagetree-page-mountpoint', Icon::SIZE_SMALL)->render()
                . ' ' . sprintf(
                    TemplaVoilaUtility::getLanguageService()->getLL('hintDoktypeMountpointOpen', true),
                    htmlspecialchars($url)
                )
                . '</a>';
        }
        return '';
    }
}
