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

class DoktypeSpacerHandler
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
        $controller->addFlashMessage(
            TemplaVoilaUtility::getLanguageService()->getLL('infoDoktypeSpacerCannotEdit'),
            TemplaVoilaUtility::getLanguageService()->getLL('titleDoktypeSpacer'),
            FlashMessage::INFO
        );

        return '';
    }
}
