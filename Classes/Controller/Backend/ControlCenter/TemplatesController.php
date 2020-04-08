<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Controller\Backend\ControlCenter;

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
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

use Ppi\TemplaVoilaPlus\Configuration\BackendConfiguration;
use Ppi\TemplaVoilaPlus\Service\ConfigurationService;
use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

class TemplatesController extends ActionController
{
    /**
     * Default View Container
     *
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * Initialize action
     */
    protected function initializeAction()
    {
        TemplaVoilaUtility::getLanguageService()->includeLLFile(
            'EXT:templavoilaplus/Resources/Private/Language/Backend/ControlCenter/Template.xlf'
        );
    }

    /**
     * List all available configurations for templates
     */
    public function listAction()
    {
        $this->registerDocheaderButtons();
        $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation([]);
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());

        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $templatePlaces = $configurationService->getTemplatePlaces();

        $templatePlacesByScope = $this->reorderDataStructurePlacesByScope($templatePlaces);

        $this->view->assign('pageTitle', 'TemplaVoilà! Plus - Templates List');

        $this->view->assign('templatePlacesByScope', $templatePlacesByScope);
    }


    /**
     * Show information about one template configuration
     *
     * @param string $uuid Uuid of TemplatePlace
     * @param string $identifier Identifier inside the dataStructurePlace
     * @return void
     */
    public function infoAction($uuid, $identifier)
    {
        $this->registerDocheaderButtons();
        $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation([]);
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());

        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $templatePlace = $configurationService->getTemplatePlace($uuid);
        $templateConfiguration = $templatePlace->getHandler()->getConfiguration($identifier);

        $this->view->assign('pageTitle', 'TemplaVoilà! Plus - Templates Info');

        $this->view->assign('templatePlace', $templatePlace);
        $this->view->assign('templateConfiguration', $templateConfiguration);
    }

    /**
     * Registers the Icons into the docheader
     *
     * @throws \InvalidArgumentException
     */
    protected function registerDocheaderButtons()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $getVars = $this->request->getArguments();

        if (isset($getVars['action']) && ($getVars['action'] === 'list' || $getVars['action'] === 'info')) {
            $backButton = $buttonBar->makeLinkButton()
                ->setDataAttributes(['identifier' => 'backButton'])
                ->setHref($this->getControllerContext()->getUriBuilder()->uriFor('show', [], 'Backend\ControlCenter'))
                ->setTitle(TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:' . TemplaVoilaUtility::getCoreLangPath() . 'locallang_core.xlf:labels.goBack'))
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
        }
    }

    protected function reorderDataStructurePlacesByScope(array $templatePlaces): array
    {
        $templatePlacesByScope = [];
        foreach ($templatePlaces as $uuid => $templatePlace) {
            $templatePlacesByScope[$templatePlace->getScope()][] = $templatePlace;
        }

        return $templatePlacesByScope;
    }
}
