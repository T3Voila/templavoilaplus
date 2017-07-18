<?php
namespace Ppi\TemplaVoilaPlus\Controller\Update;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

use Ppi\TemplaVoilaPlus\Domain\Repository\DataStructureRepository;

/**
 * Controller to migrate/update the DataStructure
 * @TODO We need more migrations, see TcaMigration in TYPO3 Core
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class DataStructureUpdateController extends StepUpdateController
{
    protected $errors = [];

    protected function stepStart()
    {
        $dsRepo = GeneralUtility::makeInstance(DataStructureRepository::class);
        $this->fluid->assignMultiple([
            'dataStructures' => $dsRepo->getAll(),
        ]);
    }

    protected function stepFinal()
    {
        $handler = GeneralUtility::makeInstance(DataStructureUpdateHandler::class);
        $count = $handler->updateAllDs(
            [],
            [
                [$this, 'migrateWizardScriptToModule'],
                [$this, 'migrateT3editorWizardToRenderTypeT3editorIfNotEnabledByTypeConfig'],
                [$this, 'migrateIconsForFormFieldWizardToNewLocation'],
                [$this, 'cleanupEmptyWizardFields'],
            ]
        );

        $this->fluid->assignMultiple([
            'count' => $count,
            'errors' => $this->errors,
        ]);
    }

    /*
     * Migrate ['wizard']['script'] to ['wizard']['module']
     * Migrate wizard wizard_element_browser and mode = wizard into wizard wizard_link
     * Partly from TYPO3 6.2 LTS
     *   TYPO3\CMS\Backend\Form\FormEngine::renderWizards
     */
    public function migrateWizardScriptToModule(array &$element)
    {
        $changed = false;

        $convertableScript = [
            'wizard_add.php',
            'wizard_colorpicker.php',
            'wizard_edit.php',
            'wizard_forms.php',
            'wizard_list.php',
            'wizard_rte.php',
            'wizard_table.php',
            'browse_links.php',
            'sysext/cms/layout/wizard_backend_layout.php'
        ];

        if (isset($element['TCEforms']['config']['wizards']) // if wizards is set
            && is_array($element['TCEforms']['config']['wizards']) // and there are wizards
        ) {
            foreach ($element['TCEforms']['config']['wizards'] as &$wizardConfig) {
                $cleaned = false;

                // Convert ['script'] to ['module']['name']
                if (isset($wizardConfig['script'])) {
                    if (!isset($wizardConfig['module']['name'])) {
                        // Convert of EXT: calls not possible
                        if (substr($wizardConfig['script'], 0, 4) === 'EXT:') {
                            $this->errors[]
                                = 'Cannot migrate wizard script: ' . $wizardConfig['script']
                                    . ' Look into documentation of the extension how to use it now.';
                            continue;
                        } else {
                            $parsedWizardUrl = parse_url($wizardConfig['script']);
                            if (in_array($parsedWizardUrl['path'], $convertableScript)) {
                                $wizardConfig['module']['name'] = str_replace(
                                    array('.php', 'browse_links', 'sysext/cms/layout/wizard_backend_layout'),
                                    array('', 'wizard_element_browser', 'wizard_backend_layout'),
                                    $parsedWizardUrl['path']
                                );

                                if (isset($parsedWizardUrl['query'])) {
                                    $urlParameters = [];
                                    parse_str($parsedWizardUrl['query'], $urlParameters);
                                    $wizardConfig['module']['urlParameters'] = $urlParameters;
                                }

                                $cleaned = true;
                            } else {
                                $this->errors[] = 'Cannot migrate wizard script: ' . $wizardConfig['script'];
                            }
                        }
                    } else {
                        // ['module']['name'] already set, so ['script'] can be cleaned
                        $cleaned = true;
                    }

                    if ($cleaned) {
                        unset($wizardConfig['script']);
                    }
                }

                // Convert ['module']['name'] = 'wizard_element_browser'
                // && ['module']['urlParameters']['mode'] = 'wizard'
                // to ['module']['name'] = 'wizard_link'
                if (isset($wizardConfig['module']['name'])
                    && $wizardConfig['module']['name'] === 'wizard_element_browser'
                    && isset($wizardConfig['module']['urlParameters']['mode'])
                    && $wizardConfig['module']['urlParameters']['mode'] === 'wizard'
                ) {
                    $wizardConfig['module']['name'] = 'wizard_link';
                    unset ($wizardConfig['module']['urlParameters']['mode']);
                    if (empty($wizardConfig['module']['urlParameters'])) {
                        unset($wizardConfig['module']['urlParameters']);
                    }
                    $cleaned = true;
                }

                $changed = $changed || $cleaned;
            }
        }
        return $changed;
    }

    /**
     * Migrate type=text field with t3editor wizard to renderType=t3editor without this wizard
     * From TYPO3 7 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateT3editorWizardToRenderTypeT3editorIfNotEnabledByTypeConfig
     *
     * @param array $element The field element TCA
     * @return boolean True if changed otherwise false
     */
    public function migrateT3editorWizardToRenderTypeT3editorIfNotEnabledByTypeConfig(array &$element)
    {
        $changed = false;

        if (isset($element['TCEforms']['config']['wizards']) // if wizards is set
            && is_array($element['TCEforms']['config']['wizards']) // and there are wizards
        ) {
            foreach ($element['TCEforms']['config']['wizards'] as $wizardName => &$wizardConfig) {

                if (
                    !empty($wizardConfig['userFunc']) // a userFunc is defined
                    && trim($wizardConfig['userFunc']) === 'TYPO3\\CMS\\T3editor\\FormWizard->main' // and set to FormWizard
                    && (
                        !isset($wizardConfig['enableByTypeConfig']) // and enableByTypeConfig is not set
                        || (isset($wizardConfig['enableByTypeConfig']) && !$wizardConfig['enableByTypeConfig'])  // or set, but not enabled
                    )
                ) {
                    // Set renderType from text to t3editor
                    $element['TCEforms']['config']['renderType'] = 't3editor';
                    // Move format parameter
                    if (!empty($wizardConfig['params']['format'])) {
                        $element['TCEforms']['config']['format'] = $wizardConfig['params']['format'];
                    }
                    // Unset this wizard definition
                    unset($element['TCEforms']['config']['wizards'][$wizardName]);

                    $changed = true;
                }
            }
        }

        return $changed;
    }

    /**
     * Migrate core icons for form field wizard to new location
     * From TYPO3 7 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateIconsForFormFieldWizardToNewLocation
     *
     * @param array $element The field element TCA
     * @return boolean True if changed otherwise false
     */
    protected function migrateIconsForFormFieldWizardToNewLocation(array &$element)
    {
        $changed = false;

        $old2newFileLocations = [
            'add.gif' => 'actions-add',
            'link_popup.gif' => 'actions-wizard-link',
            'wizard_rte2.gif' => 'actions-wizard-rte',
            'wizard_table.gif' => 'content-table',
            'edit2.gif' => 'actions-open',
            'list.gif' => 'actions-system-list-open',
            'wizard_forms.gif' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_forms.gif',
            'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_add.gif' => 'actions-add',
            'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif' => 'content-table',
            'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_edit.gif' => 'actions-open',
            'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_list.gif' => 'actions-system-list-open',
            'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif' => 'actions-wizard-link',
            'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_rte.gif' => 'actions-wizard-rte'
        ];

        if (
            isset($element['TCEforms']['config']['wizards']) // if wizards is set
            && is_array($element['TCEforms']['config']['wizards']) // and there are wizards
        ) {
            foreach ($element['TCEforms']['config']['wizards'] as $wizardName => &$wizardConfig) {
                if (!is_array($wizardConfig)) {
                    continue;
                }

                if (
                    !isset($wizardConfig['icon']) // a icon is defined
                    && isset($old2newFileLocations[$wizardConfig['icon']]) // and set to known icon
                ) {
                    $wizardConfig['icon'] = $old2newFileLocations[$wizardConfig['icon']];
                    $changed = true;
                }
            }
        }

        return $changed;
    }

    /**
     * removes wizard element, if empty
     *
     * @param array $element The field element TCA
     * @return boolean True if changed otherwise false
     */
    public function cleanupEmptyWizardFields(array &$element)
    {
        $changed = false;

        if (isset($element['TCEforms']['config']['wizards']) // if wizards is set
            && (
                ! is_array($element['TCEforms']['config']['wizards']) // and there is no wizard array
                || empty($element['TCEforms']['config']['wizards'])  // or it is empty
            )
        ) {
            unset($element['TCEforms']['config']['wizards']);
            $changed = true;
        }

        return $changed;
    }
}
