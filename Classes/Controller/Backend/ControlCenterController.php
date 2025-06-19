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

use Psr\Http\Message\ResponseInterface;
use Tvp\TemplaVoilaPlus\Service\ConfigurationService;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ControlCenterController extends ActionController
{
    /**
     * We define BackendTemplateView above so we will get it.
     *
     * @var BackendTemplateView
     * @api
     */
    protected $view;

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
        $this->pageId = (int)($this->request->getParsedBody()['id'] ?? $this->request->getQueryParams()['id'] ?? 0);
        $pageTsConfig = BackendUtility::getPagesTSconfig($this->pageId);

        // if pageId is available the row will be inside pageInfo
        $this->setPageInfo();
    }

    /**
     * Displays the menu cards
     */
    public function showAction(): ResponseInterface
    {
        $this->view->getRenderingContext()->getTemplatePaths()->fillDefaultsByPackageName('templavoilaplus');

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placesService = $configurationService->getPlacesService();

        $dataStructurePlaces = $placesService->getAvailablePlacesUsingConfigurationHandlerIdentifier(
            \Tvp\TemplaVoilaPlus\Handler\Configuration\DataConfigurationHandler::$identifier
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

        $moduleTemplateFactory = GeneralUtility::makeInstance(ModuleTemplateFactory::class);
        $moduleTemplate = $moduleTemplateFactory->create($GLOBALS['TYPO3_REQUEST']);
        $moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageInfo);
        $moduleTemplate->setContent($this->view->render('Show'));

        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    public function debugAction(): ResponseInterface
    {
        $this->view->getRenderingContext()->getTemplatePaths()->fillDefaultsByPackageName('templavoilaplus');

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placesService = $configurationService->getPlacesService();
        $availablePlaces = $placesService->getAvailablePlaces();

        $availableHandler = $configurationService->getAvailableHandlers();

        $this->view->assign('pageTitle', 'TemplaVoilà! Plus - Control Center - Debug');

        $this->view->assign('availablePlaces', $availablePlaces);
        $this->view->assign('availableHandler', $availableHandler);

        $moduleTemplateFactory = GeneralUtility::makeInstance(ModuleTemplateFactory::class);
        $moduleTemplate = $moduleTemplateFactory->create($GLOBALS['TYPO3_REQUEST']);
        $moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageInfo);

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $button = $buttonBar->makeLinkButton()
            ->setHref($this->uriBuilder->uriFor('show', [], 'Backend\ControlCenter'))
            ->setTitle('Back')
            ->setIcon($iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
        $buttonBar->addButton($button, ButtonBar::BUTTON_POSITION_LEFT, 1);

        $moduleTemplate->setContent($this->view->render('Debug'));

        return $this->htmlResponse($moduleTemplate->renderContent());
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
