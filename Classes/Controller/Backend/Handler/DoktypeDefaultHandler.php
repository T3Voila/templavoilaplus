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

use Ppi\TemplaVoilaPlus\Controller\Backend\PageLayoutController;
use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

class DoktypeDefaultHandler
{
    /**
     * Displays the content of the page on the doktype "Default"/"BE_User_Section"
     *
     * @param PageLayoutController $controller
     * @param array $pageRecord The current page record
     *
     * @return string HTML output from this submodule
     */
    public function handle(PageLayoutController $controller, array $pageRecord)
    {
        return 'Not yet implemented';
    }
}
