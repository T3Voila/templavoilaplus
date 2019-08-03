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
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\CMS\Form\Service\TranslationService;
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
     * Partly Taken from TYPO3\CMS\Form\Controller\FormEditorController
     *
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
        $formEditorDefinitions = [
            'formElements' => [
                'DataStructure' => [
                    'editors' => [
                        0 => [
                            'identifier' => 'header',
                            'templateName' => 'Inspector-FormElementHeaderEditor',
                        ],
                        1 => [
                            'identifier' => 'label',
                            'templateName' => 'Inspector-TextEditor',
                            'label' => 'formEditor.elements.BaseFormElementMixin.editor.label.label',
                            'propertyPath' => 'label',
                        ],
                    ],
                    '_isCompositeFormElement' => FALSE,
                    '_isTopLevelFormElement' => TRUE,
                    'paginationTitle' => 'Sheet {0} of {1}',
                    'iconIdentifier' => 'extensions-templavoila-datastructure-default',
                ],
                'Sheet' => [
                    'editors' => [
                        0 => [
                            'identifier' => 'header',
                            'templateName' => 'Inspector-FormElementHeaderEditor',
                        ],
                        1 => [
                            'identifier' => 'label',
                            'templateName' => 'Inspector-TextEditor',
                            'label' => 'formEditor.elements.BaseFormElementMixin.editor.label.label',
                            'propertyPath' => 'label',
                        ],
                    ],
                    '_isCompositeFormElement' => TRUE,
                    '_isTopLevelFormElement' => TRUE,
                    'iconIdentifier' => 't3-form-icon-page',
                ],
            ],
            'finishers' => [],
            'validators' => [],
            'formElementPropertyValidators' => [],
        ];

        $formEditorAppInitialData = [
            'formEditorDefinitions' => $formEditorDefinitions,
            'formDefinition' => $formDefinition,
            'formPersistenceIdentifier' => $uuid . ':' . $file,
            'prototypeName' => 'standard',
            'endpoints' => [
                'formPageRenderer' => $this->controllerContext->getUriBuilder()->uriFor('renderFormPage'),
                'saveForm' => $this->controllerContext->getUriBuilder()->uriFor('saveForm')
            ],
            'additionalViewModelModules' => null,
            'maximumUndoSteps' => 20,
        ];

        $this->view->assign('formEditorAppInitialData', json_encode($formEditorAppInitialData));
        $this->view->assign('stylesheets', ['EXT:form/Resources/Public/Css/form.css']);
        $this->view->assign('formEditorTemplates', $this->renderFormEditorTemplates($formEditorDefinitions));
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
     * Taken from TYPO3\CMS\Form\Controller\FormEditorController
     *
     * Render the "text/x-formeditor-template" templates.
     *
     * @param array $formEditorDefinitions
     * @return string
     */
    protected function renderFormEditorTemplates(array $formEditorDefinitions): string
    {
//         $fluidConfiguration = $this->prototypeConfiguration['formEditor']['formEditorFluidConfiguration'];
//         $formEditorPartials = $this->prototypeConfiguration['formEditor']['formEditorPartials'];

        $fluidConfiguration = [
            'templatePathAndFilename' => 'EXT:form/Resources/Private/Backend/Templates/FormEditor/InlineTemplates.html',
            'partialRootPaths' => [10 => 'EXT:form/Resources/Private/Backend/Partials/FormEditor/'],
            'layoutRootPaths' => [10 => 'EXT:form/Resources/Private/Backend/Layouts/FormEditor/'],
        ];

        $formEditorPartials = [
            'FormElement-_ElementToolbar' => 'Stage/_ElementToolbar',
            'FormElement-_UnknownElement' => 'Stage/_UnknownElement',
            'FormElement-Page' => 'Stage/Page',
            'FormElement-SummaryPage' => 'Stage/SummaryPage',
            'FormElement-Fieldset' => 'Stage/Fieldset',
            'FormElement-GridContainer' => 'Stage/Fieldset',
            'FormElement-GridRow' => 'Stage/Fieldset',
            'FormElement-Text' => 'Stage/SimpleTemplate',
            'FormElement-Password' => 'Stage/SimpleTemplate',
            'FormElement-AdvancedPassword' => 'Stage/SimpleTemplate',
            'FormElement-Textarea' => 'Stage/SimpleTemplate',
            'FormElement-Checkbox' => 'Stage/SimpleTemplate',
            'FormElement-MultiCheckbox' => 'Stage/SelectTemplate',
            'FormElement-MultiSelect' => 'Stage/SelectTemplate',
            'FormElement-RadioButton' => 'Stage/SelectTemplate',
            'FormElement-SingleSelect' => 'Stage/SelectTemplate',
            'FormElement-DatePicker' => 'Stage/SimpleTemplate',
            'FormElement-StaticText' => 'Stage/StaticText',
            'FormElement-Hidden' => 'Stage/SimpleTemplate',
            'FormElement-ContentElement' => 'Stage/ContentElement',
            'FormElement-FileUpload' => 'Stage/FileUploadTemplate',
            'FormElement-ImageUpload' => 'Stage/FileUploadTemplate',
            'Modal-InsertElements' => 'Modals/InsertElements',
            'Modal-InsertPages' => 'Modals/InsertPages',
            'Modal-ValidationErrors' => 'Modals/ValidationErrors',
            'Inspector-FormElementHeaderEditor' => 'Inspector/FormElementHeaderEditor',
            'Inspector-CollectionElementHeaderEditor' => 'Inspector/CollectionElementHeaderEditor',
            'Inspector-TextEditor' => 'Inspector/TextEditor',
            'Inspector-PropertyGridEditor' => 'Inspector/PropertyGridEditor',
            'Inspector-SingleSelectEditor' => 'Inspector/SingleSelectEditor',
            'Inspector-MultiSelectEditor' => 'Inspector/MultiSelectEditor',
            'Inspector-GridColumnViewPortConfigurationEditor' => 'Inspector/GridColumnViewPortConfigurationEditor',
            'Inspector-TextareaEditor' => 'Inspector/TextareaEditor',
            'Inspector-RemoveElementEditor' => 'Inspector/RemoveElementEditor',
            'Inspector-FinishersEditor' => 'Inspector/FinishersEditor',
            'Inspector-ValidatorsEditor' => 'Inspector/ValidatorsEditor',
            'Inspector-RequiredValidatorEditor' => 'Inspector/RequiredValidatorEditor',
            'Inspector-CheckboxEditor' => 'Inspector/CheckboxEditor',
            'Inspector-Typo3WinBrowserEditor' => 'Inspector/Typo3WinBrowserEditor',
        ];

        if (!isset($fluidConfiguration['templatePathAndFilename'])) {
            throw new RenderingException(
                'The option templatePathAndFilename must be set.',
                1485636499
            );
        }
        if (
            !isset($fluidConfiguration['layoutRootPaths'])
            || !is_array($fluidConfiguration['layoutRootPaths'])
        ) {
            throw new RenderingException(
                'The option layoutRootPaths must be set.',
                1480294721
            );
        }
        if (
            !isset($fluidConfiguration['partialRootPaths'])
            || !is_array($fluidConfiguration['partialRootPaths'])
        ) {
            throw new RenderingException(
                'The option partialRootPaths must be set.',
                1480294722
            );
        }

        $insertRenderablesPanelConfiguration = $this->getInsertRenderablesPanelConfiguration($formEditorDefinitions['formElements']);

        $view = $this->objectManager->get(TemplateView::class);
        $view->setControllerContext(clone $this->controllerContext);
        $view->getRenderingContext()->getTemplatePaths()->fillFromConfigurationArray($fluidConfiguration);
        $view->setTemplatePathAndFilename($fluidConfiguration['templatePathAndFilename']);
        $view->assignMultiple([
            'insertRenderablesPanelConfiguration' => $insertRenderablesPanelConfiguration,
            'formEditorPartials' => $formEditorPartials,
        ]);

        return $view->render();
    }


    /**
     * Taken from TYPO3\CMS\Form\Controller\FormEditorController
     *
     * Prepare the formElements.*.formEditor section from the YAML settings.
     * Sort all formElements into groups and add additional data.
     *
     * @param array $formElementsDefinition
     * @return array
     */
    protected function getInsertRenderablesPanelConfiguration(array $formElementsDefinition): array
    {
        $formElementGroups = []; //isset($this->prototypeConfiguration['formEditor']['formElementGroups']) ? $this->prototypeConfiguration['formEditor']['formElementGroups'] : [];
        $formElementsByGroup = [];

        foreach ($formElementsDefinition as $formElementName => $formElementConfiguration) {
            if (!isset($formElementConfiguration['group'])) {
                continue;
            }
            if (!isset($formElementsByGroup[$formElementConfiguration['group']])) {
                $formElementsByGroup[$formElementConfiguration['group']] = [];
            }

            $formElementConfiguration = TranslationService::getInstance()->translateValuesRecursive(
                $formElementConfiguration,
                $this->prototypeConfiguration['formEditor']['translationFile']
            );

            $formElementsByGroup[$formElementConfiguration['group']][] = [
                'key' => $formElementName,
                'cssKey' => preg_replace('/[^a-z0-9]/', '-', strtolower($formElementName)),
                'label' => $formElementConfiguration['label'],
                'sorting' => $formElementConfiguration['groupSorting'],
                'iconIdentifier' => $formElementConfiguration['iconIdentifier'],
            ];
        }

        $formGroups = [];
        foreach ($formElementGroups as $groupName => $groupConfiguration) {
            if (!isset($formElementsByGroup[$groupName])) {
                continue;
            }

            usort($formElementsByGroup[$groupName], function ($a, $b) {
                return $a['sorting'] - $b['sorting'];
            });
            unset($formElementsByGroup[$groupName]['sorting']);

            $groupConfiguration = TranslationService::getInstance()->translateValuesRecursive(
                $groupConfiguration,
                $this->prototypeConfiguration['formEditor']['translationFile']
            );

            $formGroups[] = [
                'key' => $groupName,
                'elements' => $formElementsByGroup[$groupName],
                'label' => $groupConfiguration['label'],
            ];
        }

        return $formGroups;
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

