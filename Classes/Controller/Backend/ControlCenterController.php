<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Controller\Backend;

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
use Tvp\TemplaVoilaPlus\Configuration\BackendConfiguration;
use Tvp\TemplaVoilaPlus\Service\ConfigurationService;
use Tvp\TemplaVoilaPlus\Service\PlacesService;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

class ControlCenterController extends ActionController
{
    /**
     * Default View Container
     *
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * We define BackendTemplateView above so we will get it.
     *
     * @var BackendTemplateView
     * @api
     */
    protected $view = null;

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
     * Displays the menu cards
     */
    public function showAction()
    {
        $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($this->pageInfo);
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placesService = $configurationService->getPlacesService();

        $dataStructurePlaces = $placesService->getAvailablePlacesUsingConfigurationHandlerIdentifier(
            \Tvp\TemplaVoilaPlus\Handler\Configuration\DataStructureConfigurationHandler::$identifier
        );
        $mappingPlaces = $placesService->getAvailablePlacesUsingConfigurationHandlerIdentifier(
            \Tvp\TemplaVoilaPlus\Handler\Configuration\MappingConfigurationHandler::$identifier
        );
        $templatePlaces = $placesService->getAvailablePlacesUsingConfigurationHandlerIdentifier(
            \Tvp\TemplaVoilaPlus\Handler\Configuration\TemplateConfigurationHandler::$identifier
        );

        $this->view->assign('pageTitle', 'TemplaVoilà! Plus - Control Center');

        $this->view->assign('dataStructurePlaces', $dataStructurePlaces);
        $this->view->assign('mappingPlaces', $mappingPlaces);
        $this->view->assign('templatePlaces', $templatePlaces);
    }

    public function debugAction()
    {
        $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($this->pageInfo);
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());

        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $button = $buttonBar->makeLinkButton()
            ->setHref($this->getControllerContext()->getUriBuilder()->uriFor('show', [], 'Backend\ControlCenter'))
            ->setTitle('Back')
            ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
        $buttonBar->addButton($button, ButtonBar::BUTTON_POSITION_LEFT, 1);

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placesService = $configurationService->getPlacesService();
        $availablePlaces = $placesService->getAvailablePlaces();

        $availableHandler = $configurationService->getAvailableHandlers();

        $this->view->assign('pageTitle', 'TemplaVoilà! Plus - Control Center - Debug');

        $this->view->assign('availablePlaces', $availablePlaces);
        $this->view->assign('availableHandler', $availableHandler);
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
