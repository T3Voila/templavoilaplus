<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter;

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
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class DataStructuresController extends ActionController
{
    /**
     * We define BackendTemplateView above so we will get it.
     *
     * @var BackendTemplateView
     * @api
     */
    protected $view;

    /**
     * Initialize action
     */
    protected function initializeAction()
    {
    }

    /**
     * Displays the page with layout and content elements
     */
    public function listAction(): ResponseInterface
    {
        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placesService = $configurationService->getPlacesService();

        $dataStructurePlaces = $placesService->getAvailablePlacesUsingConfigurationHandlerIdentifier(
            \Tvp\TemplaVoilaPlus\Handler\Configuration\DataConfigurationHandler::$identifier
        );
        $placesService->loadConfigurationsByPlaces($dataStructurePlaces);
        $dataStructurePlacesByScope = $placesService->reorderPlacesByScope($dataStructurePlaces);

        $moduleTemplateFactory = GeneralUtility::makeInstance(ModuleTemplateFactory::class);
        $moduleTemplate = $moduleTemplateFactory->create($this->request);

        $moduleTemplate->assign('pageTitle', 'TemplaVoilà! Plus - DataStructure List');

        $moduleTemplate->assign('dataStructurePlacesByScope', $dataStructurePlacesByScope);

        $moduleTemplate->getDocHeaderComponent()->setMetaInformation([]);
        $this->registerDocheaderButtons($moduleTemplate);

        return $moduleTemplate->renderResponse('List');
    }

    /**
     * Registers the Icons into the docheader
     *
     * @throws \InvalidArgumentException
     */
    protected function registerDocheaderButtons(ModuleTemplate $moduleTemplate)
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $getVars = $this->request->getArguments();

        if (isset($getVars['action']) && $getVars['action'] === 'list') {
            $backButton = $buttonBar->makeLinkButton()
                ->setDataAttributes(['identifier' => 'backButton'])
                ->setHref($this->uriBuilder->uriFor('show', [], 'Backend\ControlCenter'))
                ->setTitle(TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:' . TemplaVoilaUtility::getCoreLangPath() . 'locallang_core.xlf:labels.goBack'))
                ->setIcon($iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
        }
    }

    /**
     * Deletes configuration from dataStructurePlace
     * @TODO This implementation is only for complete files not for DB records/overloads/...
     *
     * @param string $placeIdentifier Uuid of dataStructurePlace
     * @param string $configurationIdentifier Identifier inside the dataStructurePlace
     */
    public function deleteAction($placeIdentifier, $configurationIdentifier): ResponseInterface
    {
        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placesService = $configurationService->getPlacesService();

        $dataStructurePlace = $placesService->getPlace(
            $placeIdentifier,
            \Tvp\TemplaVoilaPlus\Handler\Configuration\DataConfigurationHandler::$identifier
        );
        $placesService->loadConfigurationsByPlace($dataStructurePlace);
        // get the LoadSave Handler
        // call remove on the load/save handler

        $this->addFlashMessage(
            'DataStructure ' . $configurationIdentifier . ' (not yet) deleted.',
            '',
            ContextualFeedbackSeverity::INFO
        );

        $this->redirect('list');
    }
}
