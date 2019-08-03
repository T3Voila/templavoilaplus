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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Frontend\Page\PageRepository;

use Ppi\TemplaVoilaPlus\Configuration\BackendConfiguration;
use Ppi\TemplaVoilaPlus\Service\ConfigurationService;
use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

class DataStructuresController extends ActionController
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
            'EXT:templavoilaplus/Resources/Private/Language/Backend/ControlCenter/DataStructures.xlf'
        );
    }

    /**
     * Displays the page with layout and content elements
     */
    public function listAction()
    {
        $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation([]);
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());

        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $button = $buttonBar->makeLinkButton()
            ->setHref($this->getControllerContext()->getUriBuilder()->uriFor('show', [], 'Backend\ControlCenter'))
            ->setTitle('Back')
            ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
        $buttonBar->addButton($button, ButtonBar::BUTTON_POSITION_LEFT, 1);

        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $dataStructurePlaces = $configurationService->getDataStructurePlaces();

        $dataStructurePlaces = $this->enrichDataStructurePlacesWithFiles($dataStructurePlaces);
        $dataStructurePlacesByScope = $this->reorderDataStructurePlacesByScope($dataStructurePlaces);

        $this->view->assign('pageTitle', 'TemplaVoilà! Plus - DataStructure List');

        $this->view->assign('dataStructurePlacesByScope', $dataStructurePlacesByScope);
    }

    /**
     * Edits configuration from dataStructurePlace
     *
     * @param string $uuid Uuid of dataStructurePlace
     * @param string $file Identifier inside the dataStructurePlace
     * @return void
     */
    public function editAction($uuid, $file)
    {
//         $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation([]);
//         $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
//
//         $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
//         $button = $buttonBar->makeLinkButton()
//             ->setHref($this->getControllerContext()->getUriBuilder()->uriFor('list'))
//             ->setTitle('Back')
//             ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
//         $buttonBar->addButton($button, ButtonBar::BUTTON_POSITION_LEFT, 1);
        $formDefinition = [
            'type' => 'DataStructure',
            'identifier' => $uuid, // . '-' . $file,
            'label' => $uuid . ':' . $file,
            'renderables' => [
                0 => [
                    'type' => 'Sheet',
                    'identifier' => 'sheet1',
                    'label' => 'Sheet 1',
                ],
            ],
            'prototypeName' => 'standard',
        ];
        $formEditorAppInitialData = [
            'formEditorDefinitions' => [
                'formElements' => [
                    'DataStructure' => [
                        'paginationTitle' => 'Sheet {0} of {1}',
                        'iconIdentifier' => 'extensions-templavoila-datastructure-default',
                    ],
                    'Sheet' => [
                        'iconIdentifier' => 't3-form-icon-page',
                    ],
                ],
                'finishers' => [],
                'validators' => [],
                'formElementPropertyValidators' => [],
            ],
            'formDefinition' => $formDefinition,
            'formPersistenceIdentifier' => 'persIdent', //$uuid . ':' . $file,
            'prototypeName' => 'standard',
            'endpoints' => [
                'formPageRenderer' => $this->controllerContext->getUriBuilder()->uriFor('renderFormPage'),
                'saveForm' => $this->controllerContext->getUriBuilder()->uriFor('saveForm')
            ],
            'additionalViewModelModules' => null,
            'maximumUndoSteps' => 20,
        ];

        $this->view->assign('formEditorAppInitialData', json_encode($formEditorAppInitialData));
//         $this->view->assign('stylesheets', $this->resolveResourcePaths($this->prototypeConfiguration['formEditor']['stylesheets']));
//         $this->view->assign('formEditorTemplates', $this->renderFormEditorTemplates($formEditorDefinitions));
        $this->view->assign(
            'dynamicRequireJsModules',
            [
                'app' => 'TYPO3/CMS/Form/Backend/FormEditor',
                'mediator' => 'TYPO3/CMS/Form/Backend/FormEditor/Mediator',
                'viewModel' => 'TYPO3/CMS/Form/Backend/FormEditor/ViewModel',
            ]
        );

        $this->view->setLayoutRootPaths(['EXT:form/Resources/Private/Backend/Layouts']);
        $this->view->setPartialRootPaths(['EXT:form/Resources/Private/Backend/Partials']);
    }

    /**
     * Deletes configuration from dataStructurePlace
     * @TODO This implementation is only for complete files not for DB records/overloads/...
     *
     * @param string $uuid Uuid of dataStructurePlace
     * @param string $file Identifier inside the dataStructurePlace
     * @return void
     */
    public function deleteAction($uuid, $file)
    {
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $dataStructurePlaces = $configurationService->getDataStructurePlaces();
        $dataStructurePlaces = $this->enrichDataStructurePlacesWithFiles([$uuid => $dataStructurePlaces[$uuid]]);

        foreach ($dataStructurePlaces[$uuid]['files'] as $fileObject) {
            if ($fileObject->getIdentifier() === $file) {
                $fileObject->delete();
            }
        }

        $this->addFlashMessage(
            'DataStructure ' . $file . ' deleted.',
            '',
            FlashMessage::INFO
        );

        $this->redirect('list');
    }

    protected function enrichDataStructurePlacesWithFiles(array $dataStructurePlaces): array
    {
        $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();

        $filter = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter::class);
        $filter->setAllowedFileExtensions('xml');

        foreach ($dataStructurePlaces as $uuid => $dataStructurePlace) {
            $folder = $resourceFactory->retrieveFileOrFolderObject($dataStructurePlace['pathAbs']);
            $folder->setFileAndFolderNameFilters([[$filter, 'filterFileList']]);

           $dataStructurePlaces[$uuid]['files'] = $folder->getFiles(0, 0, \TYPO3\CMS\Core\Resource\Folder::FILTER_MODE_USE_OWN_FILTERS, true);
        }

        return $dataStructurePlaces;
    }

    protected function reorderDataStructurePlacesByScope(array $dataStructurePlaces): array
    {
        $dataStructurePlacesByScope = [];
        foreach ($dataStructurePlaces as $uuid => $dataStructurePlace) {
            $dataStructurePlacesByScope[$dataStructurePlace['scope']][] = $dataStructurePlace;
        }

        return $dataStructurePlacesByScope;
    }
}

