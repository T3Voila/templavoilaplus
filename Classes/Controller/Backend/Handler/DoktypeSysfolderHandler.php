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
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Controller\Backend\PageLayoutController;
use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

class DoktypeSysfolderHandler
{
    /**
     * Displays the edit page screen if the currently selected page is of the doktype "Sysfolder"
     *
     * @param PageLayoutController $controller
     * @param array $pageRecord The current page record
     *
     * @return string HTML output from this submodule
     */
    public function handle(PageLayoutController $controller, array $pageRecord)
    {
        if ($this->userHasAccessToListModule()) {
            $listModuleURL = 'javascript:top.goToModule(\'web_list\',1);';
            $listModuleLink = $this->getLinkButton($controller, $listModuleURL);
        }

        $controller->addFlashMessage(
            TemplaVoilaUtility::getLanguageService()->getLL('infoDoktypeSysfolderCannotEdit'),
            TemplaVoilaUtility::getLanguageService()->getLL('titleDoktypeSysfolder'),
            FlashMessage::INFO
        );

        return $listModuleLink;
    }

    /**
     * @TODO Move into fluid
     */
    protected function getLinkButton(PageLayoutController $controller, $url)
    {
        return '<a href="' . $url . '"'
            . ' class="btn btn-info"'
            . '>'
            . $controller->getView()->getModuleTemplate()->getIconFactory()->getIcon('actions-system-list-open', Icon::SIZE_SMALL)->render()
            . ' ' . TemplaVoilaUtility::getLanguageService()->getLL('hintDoktypeSysfolderOpen', true)
            . '</a>';
    }

    /**
     * Returns true if the logged in BE user has access to the list module.
     *
     * @return boolean
     * @access protected
     */
    protected function userHasAccessToListModule()
    {
        if (!BackendUtility::isModuleSetInTBE_MODULES('web_list')) {
            return false;
        }
        return TemplaVoilaUtility::getBackendUser()->isAdmin()
            || TemplaVoilaUtility::getBackendUser()->check('modules', 'web_list');
    }
}
