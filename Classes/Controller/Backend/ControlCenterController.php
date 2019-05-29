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

use Ppi\TemplaVoilaPlus\Configuration\BackendConfiguration;
use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

class ControlCenterController extends ActionController
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
     * Initialize action
     */
    protected function initializeAction()
    {
        TemplaVoilaUtility::getLanguageService()->includeLLFile(
            'EXT:templavoilaplus/Resources/Private/Language/Backend/ControlCenter.xlf'
        );

        // determine id parameter
        $this->pageId = (int)GeneralUtility::_GP('id');
        $pageTsConfig = BackendUtility::getPagesTSconfig($this->pageId);

        // if pageId is available the row will be inside pageInfo
        $this->setPageInfo();
    }
    /**
     * Displays the page with layout and content elements
     */
    public function showAction()
    {
        $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($this->pageInfo);
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
    }

    /**
     * Check if page record exists and set pageInfo
     */
    protected function setPageInfo()
    {
        $pagePermsClaus = TemplaVoilaUtility::getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        $this->pageInfo = BackendUtility::readPageAccess($this->pageId, $pagePermsClaus);
    }
}
