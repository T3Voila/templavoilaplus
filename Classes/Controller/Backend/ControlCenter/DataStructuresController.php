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
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\CMS\Form\Controller\FormEditorController;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\CMS\Frontend\Page\PageRepository;


use Ppi\TemplaVoilaPlus\Configuration\BackendConfiguration;
use Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure;
use Ppi\TemplaVoilaPlus\Service\ConfigurationService;
use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

class DataStructuresController extends FormEditorController
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
        $this->registerDocheaderButtons();
        $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation([]);
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());

        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $dataStructurePlaces = $configurationService->getDataStructurePlaces();

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
     * @param string $identifier Identifier inside the dataStructurePlace
     * @return void
     */
    public function editAction($uuid, $identifier)
    {
        $this->registerDocheaderButtons();
        $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation([]);
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());

        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $dataStructurePlace = $configurationService->getDataStructurePlace($uuid);
        $dataStructure = $dataStructurePlace->getHandler()->getConfiguration($identifier);

        $prototypeName = 'tvp-dynamic-structures';
        $formDefinition = $this->transformDataStructureForFormEditor($uuid, $prototypeName, $dataStructure);

        $this->prototypeConfiguration = $configurationService->getFormPrototypeConfiguration($prototypeName);
        $formEditorDefinitions = $this->getFormEditorDefinitions();

        $formEditorAppInitialData = [
            'formEditorDefinitions' => $formEditorDefinitions,
            'formDefinition' => $formDefinition,
            'formPersistenceIdentifier' => $uuid . ':' . $identifier,
            'prototypeName' => $prototypeName,
            'endpoints' => [
                'formPageRenderer' => $this->controllerContext->getUriBuilder()->uriFor('renderFormPage'),
                'saveForm' => $this->controllerContext->getUriBuilder()->uriFor('saveForm')
            ],
            'additionalViewModelModules' => $this->prototypeConfiguration['formEditor']['dynamicRequireJsModules']['additionalViewModelModules'],
            'maximumUndoSteps' => 20,
        ];

        $this->view->assign('formEditorAppInitialData', json_encode($formEditorAppInitialData));
        $this->view->assign('stylesheets', $this->resolveResourcePaths($this->prototypeConfiguration['formEditor']['stylesheets']));
        $this->view->assign('formEditorTemplates', $this->renderFormEditorTemplates($formEditorDefinitions));
        $this->view->assign('dynamicRequireJsModules', $this->prototypeConfiguration['formEditor']['dynamicRequireJsModules']);

        $this->view->setLayoutRootPaths(['EXT:form/Resources/Private/Backend/Layouts']);
        $this->view->setPartialRootPaths(['EXT:form/Resources/Private/Backend/Partials']);
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

        if (isset($getVars['action']) && $getVars['action'] === 'list') {
            $backButton = $buttonBar->makeLinkButton()
                ->setDataAttributes(['identifier' => 'backButton'])
                ->setHref($this->getControllerContext()->getUriBuilder()->uriFor('show', [], 'Backend\ControlCenter'))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:' . TemplaVoilaUtility::getCoreLangPath() . 'locallang_core.xlf:labels.goBack'))
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
        }

        if (isset($getVars['action']) && $getVars['action'] === 'edit') {
            $newPageButton = $buttonBar->makeInputButton()
                ->setDataAttributes(['action' => 'formeditor-new-page', 'identifier' => 'headerNewPage'])
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:formEditor.new_page_button'))
                ->setName('formeditor-new-page')
                ->setValue('new-page')
                ->setClasses('t3-form-element-new-page-button hidden')
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-page-new', Icon::SIZE_SMALL));

            $closeButton = $buttonBar->makeLinkButton()
                ->setDataAttributes(['identifier' => 'closeButton'])
                ->setHref(BackendUtility::getModuleUrl('web_TemplaVoilaPlusControlcenter', ['tx_templavoilaplus_web_templavoilapluscontrolcenter' => ['controller' => 'Backend\ControlCenter\DataStructures']]))
                ->setClasses('t3-form-element-close-form-button hidden')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:' . TemplaVoilaUtility::getCoreLangPath() . 'locallang_core.xlf:rm.closeDoc'))
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-close', Icon::SIZE_SMALL));

            $saveButton = $buttonBar->makeInputButton()
                ->setDataAttributes(['identifier' => 'saveButton'])
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:formEditor.save_button'))
                ->setName('formeditor-save-form')
                ->setValue('save')
                ->setClasses('t3-form-element-save-form-button hidden')
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-document-save', Icon::SIZE_SMALL))
                ->setShowLabelText(true);

            $formSettingsButton = $buttonBar->makeInputButton()
                ->setDataAttributes(['identifier' => 'formSettingsButton'])
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:formEditor.form_settings_button'))
                ->setName('formeditor-form-settings')
                ->setValue('settings')
                ->setClasses('t3-form-element-form-settings-button hidden')
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-system-extension-configure', Icon::SIZE_SMALL))
                ->setShowLabelText(true);

            $undoButton = $buttonBar->makeInputButton()
                ->setDataAttributes(['identifier' => 'undoButton'])
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:formEditor.undo_button'))
                ->setName('formeditor-undo-form')
                ->setValue('undo')
                ->setClasses('t3-form-element-undo-form-button hidden disabled')
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));

            $redoButton = $buttonBar->makeInputButton()
                ->setDataAttributes(['identifier' => 'redoButton'])
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:formEditor.redo_button'))
                ->setName('formeditor-redo-form')
                ->setValue('redo')
                ->setClasses('t3-form-element-redo-form-button hidden disabled')
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-forward', Icon::SIZE_SMALL));

            $buttonBar->addButton($newPageButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
            $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
            $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
            $buttonBar->addButton($formSettingsButton, ButtonBar::BUTTON_POSITION_LEFT, 4);
            $buttonBar->addButton($undoButton, ButtonBar::BUTTON_POSITION_LEFT, 5);
            $buttonBar->addButton($redoButton, ButtonBar::BUTTON_POSITION_LEFT, 5);
        }
    }

    /**
     * @todo move this to FormDefinitionConversionService
     * @param array $formDefinition
     * @return array
     */
    protected function transformDataStructureForFormEditor($uuid, $prototypeName, AbstractDataStructure $dataStructure): array
    {
        $dataStructureArray = $dataStructure->getDataStructureArray();

        $formDefinition = [
            'type' => 'DataStructure',
            'identifier' => $uuid, // . '-' . $file,
            'label' => $dataStructure->getLabel(),
            'renderables' => [],
            'prototypeName' => $prototypeName,
        ];

        $sheets = [];
        if (isset($dataStructureArray['sheets'])) {
            //$this->transformMultiSheetDataForFormEditor($dataStructureArray['sheets'])
        } elseif (isset($dataStructureArray['ROOT'])) {
            $sheets = [$this->transformSingleSheetDataForFormEditor($dataStructureArray['ROOT'])];
        } else {
            $sheets = [[
                'type' => 'Sheet',
                'identifier' => 'ROOT',
                'label' => 'Sheet 1',

                '_orig_type' => [
                    'value' => 'Sheet',
                ],
            ]];
        }

        $formDefinition['renderables'] = $sheets;

        return $formDefinition;
    }

    protected function transformSingleSheetDataForFormEditor(array $sheetStructure): array
    {
        $sheet = [
            'type' => 'Sheet',
            'identifier' => 'ROOT',
            'label' => $sheetStructure['tx_templavoilaplus']['title'],
            'description' => $sheetStructure['tx_templavoilaplus']['description'],

            '_orig_type' => [
                'value' => 'Sheet',
            ],
        ];

        if ($sheetStructure['type'] === 'array' && is_array($sheetStructure['el'])) {
            $sheet['renderables'] = $this->transformElementArrayDataForFormEditor($sheetStructure['el']);
        }

        return $sheet;
    }

    protected function transformElementArrayDataForFormEditor(array $arrayStructure): array
    {
        $elements = [];
        foreach($arrayStructure as $identifier => $elementStructure) {
            if (!empty($elementStructure['tx_templavoilaplus']['eType'])) {
                switch ($elementStructure['tx_templavoilaplus']['eType']) {
                    case 'TypoScriptObject':
                        $element = [
                            'type' => $elementStructure['tx_templavoilaplus']['eType'],
                            'identifier' => $identifier,
                            'label' => $elementStructure['tx_templavoilaplus']['title'],

                            '_orig_type' => [
                                'value' => $elementStructure['tx_templavoilaplus']['eType'],
                            ],
                            '_orig_identifier' => [
                                'value' => $identifier,
                            ],

                            'typoScriptObjectPath' => $elementStructure['tx_templavoilaplus']['TypoScriptObjPath'],
                        ];
                        break;
                    case 'ce':
                        $element = [
                            'type' => $elementStructure['tx_templavoilaplus']['eType'],
                            'identifier' => $identifier,
                            'label' => $elementStructure['tx_templavoilaplus']['title'],

                        ];
                        break;
                    case 'none':
                        $element = [
                            'type' => $elementStructure['tx_templavoilaplus']['eType'],
                            'identifier' => $identifier,
                            'label' => $elementStructure['tx_templavoilaplus']['title'],

                            'typoScript' => $elementStructure['tx_templavoilaplus']['TypoScript'],
                        ];
                        break;
                    case 'custom':
                        $element = [
                            'type' => $elementStructure['tx_templavoilaplus']['eType'],
                            'identifier' => $identifier,
                            'label' => $elementStructure['tx_templavoilaplus']['title'],

                            'typoScript' => $elementStructure['tx_templavoilaplus']['TypoScript'],
                        ];
                        break;
                    case 'input':
                        $element = [
                            'type' => $elementStructure['tx_templavoilaplus']['eType'],
                            'identifier' => $identifier,
                            'label' => $elementStructure['tx_templavoilaplus']['title'],

                            'typoScript' => $elementStructure['tx_templavoilaplus']['TypoScript'],

                            'tceLabel' => $elementStructure['TCEforms']['label'],
                            'tceConfigSize' => $elementStructure['TCEforms']['config']['size'],
                            'tceConfigEval' => $elementStructure['TCEforms']['config']['eval'],
                        ];
                        break;
                    case 'select':
                        $element = [
                            'type' => $elementStructure['tx_templavoilaplus']['eType'],
                            'identifier' => $identifier,
                            'label' => $elementStructure['tx_templavoilaplus']['title'],

                            'typoScript' => $elementStructure['tx_templavoilaplus']['TypoScript'],

                            'tceLabel' => $elementStructure['TCEforms']['label'],
                            'tceConfigItems' => $this->convertTceItemsToForm($elementStructure['TCEforms']['config']['items']),
                        ];
                        break;
                    case 'link':
                        $element = [
                            'type' => $elementStructure['tx_templavoilaplus']['eType'],
                            'identifier' => $identifier,
                            'label' => $elementStructure['tx_templavoilaplus']['title'],

                            'typoScript' => $elementStructure['tx_templavoilaplus']['TypoScript'],
                            // @TODO We should update the eType link to a better TCEforms config
                            // What could be changeable here?
                        ];
                        break;
                    case 'rte':
                        $element = [
                            'type' => $elementStructure['tx_templavoilaplus']['eType'],
                            'identifier' => $identifier,
                            'label' => $elementStructure['tx_templavoilaplus']['title'],

                            'typoScript' => $elementStructure['tx_templavoilaplus']['TypoScript'],
                            // @TODO We should update the eType rte to a better TCEforms config
                            // What could be changeable here?
                        ];
                        break;
                    default:
var_dump($elementStructure);die();
                        $element = [
                            'type' => $elementStructure['tx_templavoilaplus']['eType'],
                            'identifier' => $identifier,
                            'label' => $elementStructure['tx_templavoilaplus']['title'],
                        ];
                }
                $elements[] = $element;
            }
        }

        return $elements;
    }

    /**
     * Converts from iTCE items array into EXT:form items array
     * Caution, do not use values twice, no icon support
     * @TODO Can we support icons pro option?
     * @return array
     **/
    protected function convertTceItemsToForm(array $items = []): array
    {
        $rseult = [];

        foreach ($items as $item) {
            $result[$item[1]] = $item[0];
        }

        return $result;
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
        $formElementGroups = [
            'TypoScript' => [
                'label' => 'TypoScript',
            ],
            'Fields' => [
                'label' => 'Fields',
            ],
        ];
        $formElementsByGroup = [];

        foreach ($formElementsDefinition as $formElementName => $formElementConfiguration) {
            if (!isset($formElementConfiguration['group'])) {
                continue;
            }
            if (!isset($formElementsByGroup[$formElementConfiguration['group']])) {
                $formElementsByGroup[$formElementConfiguration['group']] = [];
            }

//             $formElementConfiguration = TranslationService::getInstance()->translateValuesRecursive(
//                 $formElementConfiguration,
//                 [] // @TODO Translation file
//             );

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

//             $groupConfiguration = TranslationService::getInstance()->translateValuesRecursive(
//                 $groupConfiguration,
//                 '' // @TODO Translation file
//             );

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
     * @param string $identifier Identifier inside the dataStructurePlace
     * @return void
     */
    public function deleteAction($uuid, $identifier)
    {
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $dataStructurePlace = $configurationService->getDataStructurePlace($uuid);

        foreach ($dataStructurePlaces['files'] as $fileObject) {
            if ($fileObject->getIdentifier() === $identifier) {
                $fileObject->delete();
            }
        }

        $this->addFlashMessage(
            'DataStructure ' . $identifier . ' deleted.',
            '',
            FlashMessage::INFO
        );

        $this->redirect('list');
    }

    protected function reorderDataStructurePlacesByScope(array $dataStructurePlaces): array
    {
        $dataStructurePlacesByScope = [];
        foreach ($dataStructurePlaces as $uuid => $dataStructurePlace) {
            $dataStructurePlacesByScope[$dataStructurePlace->getScope()][] = $dataStructurePlace;
        }

        return $dataStructurePlacesByScope;
    }
}
