<?php

namespace Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\Update;

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

use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Controller to migrate/update the DataStructure
 * @TODO We need more migrations, see TcaMigration in TYPO3 Core
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class DataStructureV8Controller extends AbstractUpdateController
{
    protected $errors = [];

    protected function stepStartAction()
    {
    }

    protected function stepFinalAction()
    {
        /** @var DataStructureUpdateHandler */
        $handler = GeneralUtility::makeInstance(DataStructureUpdateHandler::class);
        $count = $handler->updateAllDs(
            [],
            [
                [$this, 'migrateT3editorWizardToRenderTypeT3editorIfNotEnabledByTypeConfig'],
                [$this, 'migrateIconsForFormFieldWizardToNewLocation'],
                [$this, 'migrateExtAndSysextPathToEXTPath'],
                [$this, 'migrateIconsInOptionTags'],
                [$this, 'migrateSelectFieldRenderType'],
                [$this, 'migrateSelectFieldIconTable'],
                [$this, 'migrateElementBrowserWizardToLinkHandler'],
                [$this, 'migrateDefaultExtrasRteTransFormOptions'],
                [$this, 'migrateSelectTreeOptions'],
                [$this, 'migrateTSconfigSoftReferences'],
                [$this, 'migrateShowIfRteOption'],
                [$this, 'migrateInputDateTimeToRenderType'],
                [$this, 'migrateColorPickerWizardToRenderType'],
                [$this, 'migrateSelectWizardToValuePicker'],
                [$this, 'migrateSliderWizardToSliderConfiguration'],
                [$this, 'migrateLinkWizardToRenderTypeAndFieldControl'],
                [$this, 'migrateEditWizardToFieldControl'],
                [$this, 'migrateAddWizardToFieldControl'],
                [$this, 'migrateListWizardToFieldControl'],
                [$this, 'migrateLastPiecesOfDefaultExtras'],
                [$this, 'migrateTableWizardToRenderType'],
                [$this, 'migrateFullScreenRichtextToFieldControl'],
                [$this, 'migrateSuggestWizardTypeGroup'],
                [$this, 'migrateOptionsOfTypeGroup'],
                [$this, 'migrateSelectShowIconTable'],
                [$this, 'migrateImageManipulationConfig'],
                [$this, 'migrateinputDateTimeMax'],
                [$this, 'migrateInlineOverrideChildTca'],

                [$this, 'cleanupEmptyConfigWizardsFields'],
                [$this, 'cleanupEmptyDefaultExtraFields'],
            ]
        );

        $this->view->assignMultiple([
            'countStatic' => $countStatic,
            'count' => $count,
            'errors' => $this->errors,
        ]);
    }


    /**
     * Migrate type=text field with t3editor wizard to renderType=t3editor without this wizard
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateT3editorWizardToRenderTypeT3editorIfNotEnabledByTypeConfig
     */
    public function migrateT3editorWizardToRenderTypeT3editorIfNotEnabledByTypeConfig(array &$element): bool
    {
        $changed = false;

        if (
            !empty($element['TCEforms']['config']['type']) && trim($element['TCEforms']['config']['type']) === 'text'
            && isset($element['TCEforms']['config']['wizards']) && is_array($element['TCEforms']['config']['wizards'])
        ) {
            foreach ($element['TCEforms']['config']['wizards'] as $wizardName => $wizardConfig) {
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
                    // Unset this wizard definition
                    unset($element['TCEforms']['config']['wizards'][$wizardName]);
                    // Move format parameter
                    if (!empty($wizardConfig['params']['format'])) {
                        $element['TCEforms']['config']['format'] = $wizardConfig['params']['format'];
                    }
                    $changed = true;
                }
            }
        }

        return $changed;
    }

    /**
     * Migrate core icons for form field wizard to new location
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateIconsForFormFieldWizardToNewLocation
     */
    public function migrateIconsForFormFieldWizardToNewLocation(array &$element): bool
    {
        $changed = false;

        $newFileLocations = [
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
        $oldFileNames = array_keys($newFileLocations);

        if (
            isset($element['TCEforms']['config']['wizards'])
            && is_array($element['TCEforms']['config']['wizards']) // and there are wizards
        ) {
            foreach ($element['TCEforms']['config']['wizards'] as $wizardName => $wizardConfig) {
                if (!is_array($wizardConfig)) {
                    continue;
                }

                foreach ($wizardConfig as $option => $value) {
                    if ($option === 'icon' && in_array($value, $oldFileNames, true)) {
                        $element['TCEforms']['config']['wizards'][$wizardName]['icon'] = $newFileLocations[$value];
                        $changed = true;
                    }
                }
            }
        }

        return $changed;
    }

    /**
     * Migrate core icons for form field wizard to new location
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateExtAndSysextPathToEXTPath
     */
    public function migrateExtAndSysextPathToEXTPath(array &$element): bool
    {
        $changed = false;

        if (
            !empty($element['TCEforms']['config']['type']) // type is set
            && trim($element['TCEforms']['config']['type']) === 'select' // to "select"
            && isset($element['TCEforms']['config']['items'])
            && is_array($element['TCEforms']['config']['items']) // and there are items
        ) {
            foreach ($element['TCEforms']['config']['items'] as &$itemConfig) {
                // more then two values? then the third entry is the image path
                if (!empty($itemConfig[2])) {
                    $pathParts = GeneralUtility::trimExplode('/', $itemConfig[2]);
                    // remove first element (ext or sysext)
                    array_shift($pathParts);
                    $path = implode('/', $pathParts);
                    // If the path starts with ext/ or sysext/ migrate it
                    if (
                        strpos($itemConfig[2], 'ext/') === 0
                        || strpos($itemConfig[2], 'sysext/') === 0
                    ) {
                        $itemConfig[2] = 'EXT:' . $path;
                    } elseif (strpos($itemConfig[2], 'i/') === 0) {
                        $itemConfig[2] = 'EXT:backend/Resources/Public/Images/' . substr($itemConfig[2], 2);
                    }
                    $changed = true;
                }
            }
        }

        return $changed;
    }

    /**
     * Migrate "iconsInOptionTags" for "select" TCA fields
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateIconsInOptionTags
     */
    public function migrateIconsInOptionTags(array &$element): bool
    {
        $changed = false;

        if (isset($element['TCEforms']['config']['iconsInOptionTags'])) {
            unset($element['TCEforms']['config']['iconsInOptionTags']);
            $changed = true;
        }

        return $changed;
    }

    /**
     * Migrate "type=select" with "renderMode=[tree|singlebox|checkbox]" to "renderType=[selectTree|selectSingleBox|selectCheckBox]".
     * This migration also take care of "maxitems" settings and set "renderType=[selectSingle|selectMultipleSideBySide]" if no other
     * renderType is already set.
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateSelectFieldRenderType
     */
    public function migrateSelectFieldRenderType(array &$element): bool
    {
        $changed = false;

        // Only handle select fields.
        if (
            !empty($element['TCEforms']['config']['type'])
            && $element['TCEforms']['config']['type'] === 'select'
            && empty($element['TCEforms']['config']['renderType'])
        ) {
            if (!empty($element['TCEforms']['config']['renderMode'])) {
                switch ($element['TCEforms']['config']['renderMode']) {
                    case 'tree':
                        $element['TCEforms']['config']['renderType'] = 'selectTree';
                        break;
                    case 'singlebox':
                        $element['TCEforms']['config']['renderType'] = 'selectSingleBox';
                        break;
                    case 'checkbox':
                        $element['TCEforms']['config']['renderType'] = 'selectCheckBox';
                        break;
                    default:
                        $this->errors[] = 'The render mode ' . $element['TCEforms']['config']['renderMode'] . ' is invalid for the select field';
                }
            } else {
                $maxItems = !empty($element['TCEforms']['config']['maxitems']) ? (int)$element['TCEforms']['config']['maxitems'] : 1;
                if ($maxItems <= 1) {
                    $element['TCEforms']['config']['renderType'] = 'selectSingle';
                } else {
                    $element['TCEforms']['config']['renderType'] = 'selectMultipleSideBySide';
                }
            }
            $changed = true;
        }

        return $changed;
    }

    /**
     * Migrate the visibility of the icon table for fields with "renderType=selectSingle"
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateSelectFieldIconTable
     */
    public function migrateSelectFieldIconTable(array &$element): bool
    {
        $changed = false;

        if (
            !empty($element['TCEforms']['config']['renderType'])
            && $element['TCEforms']['config']['renderType'] !== 'selectSingle'
        ) {
            if (isset($element['TCEforms']['config']['noIconsBelowSelect'])) {
                if (!$element['TCEforms']['config']['noIconsBelowSelect']) {
                    // If old setting was explicitly false, enable icon table if not defined yet
                    if (!isset($element['TCEforms']['config']['showIconTable'])) {
                        $element['TCEforms']['config']['showIconTable'] = true;
                    }
                }
                unset($element['TCEforms']['config']['noIconsBelowSelect']);
                $changed = true;
            }
            if (isset($element['TCEforms']['config']['suppress_icons'])) {
                unset($element['TCEforms']['config']['suppress_icons']);
                $changed = true;
            }
            if (isset($element['TCEforms']['config']['foreign_table_loadIcons'])) {
                unset($element['TCEforms']['config']['foreign_table_loadIcons']);
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * Migrate wizard "wizard_element_browser" used in mode "wizard" to use the "wizard_link" instead
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateElementBrowserWizardToLinkHandler
     */
    public function migrateElementBrowserWizardToLinkHandler(array &$element): bool
    {
        $changed = false;

        if (
            isset($element['TCEforms']['config']['wizards']['link']['module']['name'])
            && $element['TCEforms']['config']['wizards']['link']['module']['name'] === 'wizard_element_browser'
            && isset($element['TCEforms']['config']['wizards']['link']['module']['urlParameters']['mode'])
            && $element['TCEforms']['config']['wizards']['link']['module']['urlParameters']['mode'] === 'wizard'
        ) {
            $element['TCEforms']['config']['wizards']['link']['module']['name'] = 'wizard_link';
            unset($element['TCEforms']['config']['wizards']['link']['module']['urlParameters']['mode']);
            if (empty($element['TCEforms']['config']['wizards']['link']['module']['urlParameters'])) {
                unset($element['TCEforms']['config']['wizards']['link']['module']['urlParameters']);
            }
            $changed = true;
        }

        return $changed;
    }

    /**
     * Migrate defaultExtras "richtext:rte_transform[mode=ts_css]" and similar stuff like
     * "richtext:rte_transform[mode=ts_css]" to "richtext:rte_transform"
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateDefaultExtrasRteTransFormOptions
     */
    public function migrateDefaultExtrasRteTransFormOptions(array &$element): bool
    {
        $changed = false;
        if (
            isset($element['TCEforms']['defaultExtras']) // if defaultExtras is set
        ) {
            $defaultExtrasArray = GeneralUtility::trimExplode(':', $element['TCEforms']['defaultExtras'], true);
            foreach ($defaultExtrasArray as $part => $defaultExtrasField) {
                if (substr($defaultExtrasField, 0, 8) === 'richtext') {
                    $element['TCEforms']['config']['enableRichtext'] = true;
                    $element['TCEforms']['config']['richtextConfiguration'] = 'default';
                    unset($defaultExtrasArray[$part]);
                    $changed = true;
                } elseif (substr($defaultExtrasField, 0, 13) === 'rte_transform') {
                    unset($defaultExtrasArray[$part]);
                    $changed = true;
                }
            }
        }

        if ($changed) {
            $element['TCEforms']['defaultExtras'] = implode(':', $defaultExtrasArray);
        }

        return $changed;
    }

    /**
     * Migrates selectTree fields deprecated options
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateSelectTreeOptions
     */
    public function migrateSelectTreeOptions(array &$element): bool
    {
        $changed = false;

        if (
            isset($element['TCEforms']['config']['renderType'])
            && $element['TCEforms']['config']['renderType'] === 'selectTree'
        ) {
            if (isset($element['TCEforms']['config']['treeConfig']['appearance']['width'])) {
                unset($element['TCEforms']['config']['treeConfig']['appearance']['width']);
                $changed = true;
            }

            if (isset($element['TCEforms']['config']['treeConfig']['appearance']['allowRecursiveMode'])) {
                unset($element['TCEforms']['config']['treeConfig']['appearance']['allowRecursiveMode']);
                $changed = true;
            }

            if (isset($element['TCEforms']['config']['autoSizeMax'])) {
                unset($element['TCEforms']['config']['autoSizeMax']);
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * Migrates selectTree fields deprecated options
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateTSconfigSoftReferences
     */
    public function migrateTSconfigSoftReferences(array &$element): bool
    {
        $changed = false;

        if (isset($element['TCEforms']['config']['softref'])) {
            $softReferences = array_flip(GeneralUtility::trimExplode(',', $element['TCEforms']['config']['softref']));

            if (isset($softReferences['TSconfig'])) {
                $changed = true;
                unset($softReferences['TSconfig']);
            }
            if (isset($softReferences['TStemplate'])) {
                $changed = true;
                unset($softReferences['TStemplate']);
            }
            if ($changed) {
                if (!empty($softReferences)) {
                    $softReferences = array_flip($softReferences);
                    $element['TCEforms']['config']['softref'] = implode(',', $softReferences);
                } else {
                    unset($element['TCEforms']['config']['softref']);
                }
            }
        }

        return $changed;
    }

    /**
     * Removes the option "showIfRTE" for TCA type "check"
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateShowIfRteOption
     */
    public function migrateShowIfRteOption(array &$element): bool
    {
        $changed = false;

        if (
            isset($element['TCEforms']['config'])
            && $element['TCEforms']['config']['type'] === 'check'
        ) {
            if (isset($element['TCEforms']['config']['showIfRTE'])) {
                unset($element['TCEforms']['config']['showIfRTE']);
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * Move all type=input with eval=date/time configuration to an own renderType
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateInputDateTimeToRenderType
     */
    public function migrateInputDateTimeToRenderType(array &$element): bool
    {
        $changed = false;

        if (
            $element['TCEforms']['config']['type'] === 'input'
            && !isset($element['TCEforms']['config']['renderType'])
        ) {
            $eval = $element['TCEforms']['config']['eval'] ?? '';
            $eval = GeneralUtility::trimExplode(',', $eval, true);
            if (
                in_array('date', $eval, true)
                || in_array('datetime', $eval, true)
                || in_array('time', $eval, true)
                || in_array('timesec', $eval, true)
            ) {
                $element['TCEforms']['config']['renderType'] = 'inputDateTime';
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * Migrates fields having a colorpicker wizard to a color field
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateColorPickerWizardToRenderType
     */
    public function migrateColorPickerWizardToRenderType(array &$element): bool
    {
        $changed = false;

        if (
            isset($element['TCEforms']['config'])
            && empty($element['TCEforms']['config']['renderType'])
        ) {
            if ($element['TCEforms']['config']['type'] === 'input') {
                if (isset($element['TCEforms']['config']['wizards']) && is_array($element['TCEforms']['config']['wizards'])) {
                    foreach ($element['TCEforms']['config']['wizards'] as $wizardName => $wizard) {
                        if (isset($wizard['type']) && ($wizard['type'] === 'colorbox')) {
                            $element['TCEforms']['config']['renderType'] = 'colorpicker';
                            unset($element['TCEforms']['config']['wizards'][$wizardName]);
                            $changed = true;
                        }
                    }
                }
            }
        }

        return $changed;
    }

    /**
     * Move type=input with select wizard to config['valuePicker']
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateColorPickerWizardToRenderType
     */
    public function migrateSelectWizardToValuePicker(array &$element): bool
    {
        $changed = false;

        if (
            $element['TCEforms']['config']['type'] === 'input'
            || $element['TCEforms']['config']['type'] === 'text'
        ) {
            if (isset($element['TCEforms']['config']['wizards']) && is_array($element['TCEforms']['config']['wizards'])) {
                foreach ($element['TCEforms']['config']['wizards'] as $wizardName => $wizardConfig) {
                    if (
                        isset($wizardConfig['type'])
                        && $wizardConfig['type'] === 'select'
                        && isset($wizardConfig['items'])
                        && is_array($wizardConfig['items'])
                    ) {
                        $element['TCEforms']['config']['valuePicker']['items'] = $wizardConfig['items'];
                        if (
                            isset($wizardConfig['mode'])
                            && is_string($wizardConfig['mode'])
                            && in_array($wizardConfig['mode'], ['append', 'prepend', ''])
                        ) {
                            $element['TCEforms']['config']['valuePicker']['mode'] = $wizardConfig['mode'];
                        }
                        unset($element['TCEforms']['config']['wizards'][$wizardName]);
                        $changed = true;
                    }
                }
            }
        }

        return $changed;
    }

    /**
     * Move type=input with select wizard to config['valuePicker']
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateSliderWizardToSliderConfiguration
     */
    public function migrateSliderWizardToSliderConfiguration(array &$element): bool
    {
        $changed = false;

        if ($element['TCEforms']['config']['type'] === 'input') {
            if (
                isset($element['TCEforms']['config']['wizards'])
                && is_array($element['TCEforms']['config']['wizards'])
            ) {
                foreach ($element['TCEforms']['config']['wizards'] as $wizardName => $wizardConfig) {
                    if (isset($wizardConfig['type']) && $wizardConfig['type'] === 'slider') {
                        $element['TCEforms']['config']['slider'] = [];
                        if (isset($wizardConfig['width'])) {
                            $element['TCEforms']['config']['slider']['width'] = $wizardConfig['width'];
                        }
                        if (isset($wizardConfig['step'])) {
                            $element['TCEforms']['config']['slider']['step'] = $wizardConfig['step'];
                        }
                        unset($element['TCEforms']['config']['wizards'][$wizardName]);
                        $changed = true;
                    }
                }
            }
        }

        return $changed;
    }

    /**
     * Move type=input fields that have a "link" wizard to an own renderType with fieldControl
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateLinkWizardToRenderTypeAndFieldControl
     */
    public function migrateLinkWizardToRenderTypeAndFieldControl(array &$element): bool
    {
        $changed = false;

        if (
            $element['TCEforms']['config']['type'] === 'input'
            && !isset($element['TCEforms']['config']['renderType'])
        ) {
            if (
                isset($element['TCEforms']['config']['wizards'])
                && is_array($element['TCEforms']['config']['wizards'])
            ) {
                foreach ($element['TCEforms']['config']['wizards'] as $wizardName => $wizardConfig) {
                    if (
                        isset($wizardConfig['type'])
                        && $wizardConfig['type'] === 'popup'
                        && isset($wizardConfig['module']['name'])
                        && $wizardConfig['module']['name'] === 'wizard_link'
                    ) {
                        $element['TCEforms']['config']['renderType'] = 'inputLink';
                        if (isset($wizardConfig['title'])) {
                            $element['TCEforms']['config']['fieldControl']['linkPopup']['options']['title'] = $wizardConfig['title'];
                        }
                        if (isset($wizardConfig['JSopenParams'])) {
                            $element['TCEforms']['config']['fieldControl']['linkPopup']['options']['windowOpenParameters'] = $wizardConfig['JSopenParams'];
                        }
                        if (isset($wizardConfig['params']['blindLinkOptions'])) {
                            $element['TCEforms']['config']['fieldControl']['linkPopup']['options']['blindLinkOptions'] = $wizardConfig['params']['blindLinkOptions'];
                        }
                        if (isset($wizardConfig['params']['blindLinkFields'])) {
                            $element['TCEforms']['config']['fieldControl']['linkPopup']['options']['blindLinkFields'] = $wizardConfig['params']['blindLinkFields'];
                        }
                        if (isset($wizardConfig['params']['allowedExtensions'])) {
                            $element['TCEforms']['config']['fieldControl']['linkPopup']['options']['allowedExtensions'] = $wizardConfig['params']['allowedExtensions'];
                        }
                        unset($element['TCEforms']['config']['wizards'][$wizardName]);
                        $changed = true;
                    }
                }
            }
        }

        return $changed;
    }

    /**
     * Find select and group fields with enabled edit wizard and migrate to "fieldControl"
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateEditWizardToFieldControl
     */
    public function migrateEditWizardToFieldControl(array &$element): bool
    {
        $changed = false;

        if (
            $element['TCEforms']['config']['type'] === 'group'
            || $element['TCEforms']['config']['type'] === 'select'
        ) {
            if (
                isset($element['TCEforms']['config']['wizards'])
                && is_array($element['TCEforms']['config']['wizards'])
            ) {
                foreach ($element['TCEforms']['config']['wizards'] as $wizardName => $wizardConfig) {
                    if (
                        isset($wizardConfig['type'])
                        && $wizardConfig['type'] === 'popup'
                        && isset($wizardConfig['module']['name'])
                        && $wizardConfig['module']['name'] === 'wizard_edit'
                        && !isset($element['TCEforms']['config']['fieldControl']['editPopup'])
                    ) {
                        $element['TCEforms']['config']['fieldControl']['editPopup']['disabled'] = false;
                        if (isset($wizardConfig['title'])) {
                            $element['TCEforms']['config']['fieldControl']['editPopup']['options']['title'] = $wizardConfig['title'];
                        }
                        if (isset($wizardConfig['JSopenParams'])) {
                            $element['TCEforms']['config']['fieldControl']['editPopup']['options']['windowOpenParameters'] = $wizardConfig['JSopenParams'];
                        }
                        unset($element['TCEforms']['config']['wizards'][$wizardName]);
                        $changed = true;
                    }
                }
            }
        }

        return $changed;
    }

    /**
     * Find select and group fields with enabled add wizard and migrate to "fieldControl"
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateAddWizardToFieldControl
     */
    public function migrateAddWizardToFieldControl(array &$element): bool
    {
        $changed = false;

        if (
            $element['TCEforms']['config']['type'] === 'group'
            || $element['TCEforms']['config']['type'] === 'select'
        ) {
            if (
                isset($element['TCEforms']['config']['wizards'])
                && is_array($element['TCEforms']['config']['wizards'])
            ) {
                foreach ($element['TCEforms']['config']['wizards'] as $wizardName => $wizardConfig) {
                    if (
                        isset($wizardConfig['type'])
                        && $wizardConfig['type'] === 'script'
                        && isset($wizardConfig['module']['name'])
                        && $wizardConfig['module']['name'] === 'wizard_add'
                        && !isset($element['TCEforms']['config']['fieldControl']['addRecord'])
                    ) {
                        $element['TCEforms']['config']['fieldControl']['addRecord']['disabled'] = false;
                        if (isset($wizardConfig['title'])) {
                            $element['TCEforms']['config']['fieldControl']['addRecord']['options']['title'] = $wizardConfig['title'];
                        }
                        if (isset($wizardConfig['params']['table'])) {
                            $element['TCEforms']['config']['fieldControl']['addRecord']['options']['table'] = $wizardConfig['params']['table'];
                        }
                        if (isset($wizardConfig['params']['pid'])) {
                            $element['TCEforms']['config']['fieldControl']['addRecord']['options']['pid'] = $wizardConfig['params']['pid'];
                        }
                        if (isset($wizardConfig['params']['setValue'])) {
                            $element['TCEforms']['config']['fieldControl']['addRecord']['options']['setValue'] = $wizardConfig['params']['setValue'];
                        }
                        unset($element['TCEforms']['config']['wizards'][$wizardName]);
                        $changed = true;
                    }
                }
            }
        }

        return $changed;
    }

    /**
     * Find select and group fields with enabled list wizard and migrate to "fieldControl"
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateListWizardToFieldControl
     */
    public function migrateListWizardToFieldControl(array &$element): bool
    {
        $changed = false;

        if (
            $element['TCEforms']['config']['type'] === 'group'
            || $element['TCEforms']['config']['type'] === 'select'
        ) {
            if (
                isset($element['TCEforms']['config']['wizards'])
                && is_array($element['TCEforms']['config']['wizards'])
            ) {
                foreach ($element['TCEforms']['config']['wizards'] as $wizardName => $wizardConfig) {
                    if (
                        isset($wizardConfig['type'])
                        && $wizardConfig['type'] === 'script'
                        && isset($wizardConfig['module']['name'])
                        && $wizardConfig['module']['name'] === 'wizard_list'
                        && !isset($element['TCEforms']['config']['fieldControl']['listModule'])
                    ) {
                        $element['TCEforms']['config']['fieldControl']['listModule']['disabled'] = false;
                        if (isset($wizardConfig['title'])) {
                            $element['TCEforms']['config']['fieldControl']['listModule']['options']['title'] = $wizardConfig['title'];
                        }
                        if (isset($wizardConfig['params']['table'])) {
                            $element['TCEforms']['config']['fieldControl']['listModule']['options']['table'] = $wizardConfig['params']['table'];
                        }
                        if (isset($wizardConfig['params']['pid'])) {
                            $element['TCEforms']['config']['fieldControl']['listModule']['options']['pid'] = $wizardConfig['params']['pid'];
                        }
                        unset($element['TCEforms']['config']['wizards'][$wizardName]);
                        $changed = true;
                    }
                }
            }
        }

        return $changed;
    }

    /**
     * Migrate defaultExtras "nowrap", "enable-tab", "fixed-font". Then drop all
     * remaining "defaultExtras", there shouldn't exist anymore.
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateLastPiecesOfDefaultExtras
     */
    public function migrateLastPiecesOfDefaultExtras(array &$element): bool
    {
        $changed = false;
        if (
            isset($element['TCEforms']['defaultExtras']) // if defaultExtras is set
        ) {
            $defaultExtrasArray = GeneralUtility::trimExplode(':', $element['TCEforms']['defaultExtras'], true);
            foreach ($defaultExtrasArray as $part => $defaultExtrasSetting) {
                if ($defaultExtrasSetting === 'rte_only') {
                    // Not supported anymore
                    unset($defaultExtrasArray[$part]);
                } elseif ($defaultExtrasSetting === 'nowrap') {
                    $element['TCEforms']['config']['wrap'] = 'off';
                } elseif ($defaultExtrasSetting === 'enable-tab') {
                    $element['TCEforms']['config']['enableTabulator'] = true;
                } elseif ($defaultExtrasSetting === 'fixed-font') {
                    $element['TCEforms']['config']['fixedFont'] = true;
                } else {
                    $this->errors[] = 'The defaultExtras setting \'' . $defaultExtrasSetting . '\' is unknown and has been dropped.';
                }
                unset($defaultExtrasArray[$part]);
                $changed = true;
            }
        }

        if ($changed) {
            $element['TCEforms']['defaultExtras'] = implode(':', $defaultExtrasArray);
        }

        return $changed;
    }

    /**
     * Migrate wizard_table script to renderType="textTable" with options in fieldControl
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateTableWizardToRenderType
     */
    public function migrateTableWizardToRenderType(array &$element): bool
    {
        $changed = false;
        if ($element['TCEforms']['config']['type'] === 'text') {
            if (
                isset($element['TCEforms']['config']['wizards'])
                && is_array($element['TCEforms']['config']['wizards'])
            ) {
                foreach ($element['TCEforms']['config']['wizards'] as $wizardName => $wizardConfig) {
                    if (
                        isset($wizardConfig['type'])
                        && $wizardConfig['type'] === 'script'
                        && isset($wizardConfig['module']['name'])
                        && $wizardConfig['module']['name'] === 'wizard_table'
                        && !isset($element['TCEforms']['config']['fieldControl']['tableWizard'])
                        && !isset($element['TCEforms']['config']['renderType'])
                    ) {
                        $element['TCEforms']['config']['renderType'] = 'textTable';
                        if (isset($wizardConfig['title'])) {
                            $element['TCEforms']['config']['fieldControl']['tableWizard']['options']['title'] = $wizardConfig['title'];
                        }
                        if (isset($wizardConfig['params']['xmlOutput']) && (int)$wizardConfig['params']['xmlOutput'] !== 0) {
                            $element['TCEforms']['config']['fieldControl']['tableWizard']['options']['xmlOutput'] = (int)$wizardConfig['params']['xmlOutput'];
                        }
                        if (isset($wizardConfig['params']['numNewRows'])) {
                            $element['TCEforms']['config']['fieldControl']['tableWizard']['options']['numNewRows'] = $wizardConfig['params']['numNewRows'];
                        }
                        unset($element['TCEforms']['config']['wizards'][$wizardName]);
                        $changed = true;
                    }
                }
            }
        }

        return $changed;
    }

    /**
     * Migrate "wizard_rte" wizards to rtehtmlarea fieldControl
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateFullScreenRichtextToFieldControl
     */
    public function migrateFullScreenRichtextToFieldControl(array &$element): bool
    {
        $changed = false;
        if ($element['TCEforms']['config']['type'] === 'text') {
            if (
                isset($element['TCEforms']['config']['wizards'])
                && is_array($element['TCEforms']['config']['wizards'])
            ) {
                foreach ($element['TCEforms']['config']['wizards'] as $wizardName => $wizardConfig) {
                    if (
                        isset($wizardConfig['type'])
                        && $wizardConfig['type'] === 'script'
                        && isset($wizardConfig['module']['name'])
                        && $wizardConfig['module']['name'] === 'wizard_rte'
                        && !isset($element['TCEforms']['config']['fieldControl']['fullScreenRichtext'])
                        && isset($element['TCEforms']['config']['enableRichtext'])
                        && (bool)$element['TCEforms']['config']['enableRichtext'] === true
                    ) {
                        // Field is configured for richtext, so enable the full screen wizard
                        $element['TCEforms']['config']['fieldControl']['fullScreenRichtext']['disabled'] = false;
                        if (isset($wizardConfig['title'])) {
                            $element['TCEforms']['config']['fieldControl']['fullScreenRichtext']['options']['title'] = $wizardConfig['title'];
                        }
                        unset($element['TCEforms']['config']['wizards'][$wizardName]);
                        $changed = true;
                    } elseif (
                        isset($wizardConfig['type'])
                        && $wizardConfig['type'] === 'script'
                        && isset($wizardConfig['module']['name'])
                        && $wizardConfig['module']['name'] === 'wizard_rte'
                        && !isset($element['TCEforms']['config']['fieldControl']['fullScreenRichtext'])
                        && (
                            !isset($element['TCEforms']['config']['enableRichtext'])
                            || isset($element['TCEforms']['config']['enableRichtext']) && (bool)$element['TCEforms']['config']['enableRichtext'] === false
                        )
                    ) {
                        // Wizard is given, but field is not configured for richtext
                        unset($element['TCEforms']['config']['wizards'][$wizardName]);
                        $changed = true;
                    }
                }
            }
        }

        return $changed;
    }

    /**
     * Migrate the "suggest" wizard in type=group to "hideSuggest" and "suggestOptions"
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateSuggestWizardTypeGroup
     */
    public function migrateSuggestWizardTypeGroup(array &$element): bool
    {
        $changed = false;
        if (
            $element['TCEforms']['config']['type'] === 'group'
            && isset($element['TCEforms']['config']['internal_type'])
            && $element['TCEforms']['config']['internal_type'] === 'db'
        ) {
            if (!isset($element['TCEforms']['config']['hideSuggest'])) {
                if (isset($element['TCEforms']['config']['wizards']) && is_array($element['TCEforms']['config']['wizards'])) {
                    foreach ($element['TCEforms']['config']['wizards'] as $wizardName => $wizardConfig) {
                        if (isset($wizardConfig['type']) && $wizardConfig['type'] === 'suggest') {
                            unset($wizardConfig['type']);
                            if (!empty($wizardConfig)) {
                                $element['TCEforms']['config']['suggestOptions'] = $wizardConfig;
                            }
                            unset($element['TCEforms']['config']['wizards'][$wizardName]);
                            $changed = true;
                        }
                    }
                }
            }
        }

        return $changed;
    }

    /**
     * Migrate some detail options of type=group config
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateOptionsOfTypeGroup
     */
    public function migrateOptionsOfTypeGroup(array &$element): bool
    {
        $changed = false;
        if ($element['TCEforms']['config']['type'] === 'group') {
            if (isset($element['TCEforms']['config']['selectedListStyle'])) {
                unset($element['TCEforms']['config']['selectedListStyle']);
                $changed = true;
            }
            if (isset($element['TCEforms']['config']['show_thumbs'])) {
                if ((bool)$element['TCEforms']['config']['show_thumbs'] === false && $element['TCEforms']['config']['internal_type'] === 'db') {
                    $element['TCEforms']['config']['fieldWizard']['recordsOverview']['disabled'] = true;
                } elseif ((bool)$element['TCEforms']['config']['show_thumbs'] === false && $element['TCEforms']['config']['internal_type'] === 'file') {
                    $element['TCEforms']['config']['fieldWizard']['fileThumbnails']['disabled'] = true;
                }
                unset($element['TCEforms']['config']['show_thumbs']);
                $changed = true;
            }
            if (isset($element['TCEforms']['config']['disable_controls']) && is_string($element['TCEforms']['config']['disable_controls'])) {
                $controls = GeneralUtility::trimExplode(',', $element['TCEforms']['config']['disable_controls'], true);
                foreach ($controls as $control) {
                    if ($control === 'browser') {
                        $element['TCEforms']['config']['fieldControl']['elementBrowser']['disabled'] = true;
                    } elseif ($control === 'delete') {
                        $element['TCEforms']['config']['hideDeleteIcon'] = true;
                    } elseif ($control === 'allowedTables') {
                        $element['TCEforms']['config']['fieldWizard']['tableList']['disabled'] = true;
                    } elseif ($control === 'upload') {
                        $element['TCEforms']['config']['fieldWizard']['fileUpload']['disabled'] = true;
                    }
                }
                unset($element['TCEforms']['config']['disable_controls']);
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * Migrate some detail options of type=group config
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateSelectShowIconTable
     */
    public function migrateSelectShowIconTable(array &$element): bool
    {
        $changed = false;
        if (
            $element['TCEforms']['config']['type'] === 'select'
            && isset($element['TCEforms']['config']['renderType'])
            && $element['TCEforms']['config']['renderType'] === 'selectSingle'
        ) {
            if (isset($element['TCEforms']['config']['showIconTable'])) {
                if ((bool)$element['TCEforms']['config']['showIconTable'] === true) {
                    $element['TCEforms']['config']['fieldWizard']['selectIcons']['disabled'] = false;
                }
                unset($element['TCEforms']['config']['showIconTable']);
                $changed = true;
            }
            if (isset($element['TCEforms']['config']['selicon_cols'])) {
                unset($element['TCEforms']['config']['selicon_cols']);
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * Migrate imageManipulation "ratio" config to new "cropVariant" config
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateImageManipulationConfig
     */
    public function migrateImageManipulationConfig(array &$element): bool
    {
        $changed = false;

        if ($element['TCEforms']['config']['type'] === 'imageManipulation') {
            if (isset($element['TCEforms']['config']['enableZoom'])) {
                unset($element['TCEforms']['config']['enableZoom']);
                $changed = true;
            }
            if (isset($element['TCEforms']['config']['ratios'])) {
                $legacyRatios = $element['TCEforms']['config']['ratios'];
                if (!isset($element['TCEforms']['config']['cropVariants'])) {
                    $element['TCEforms']['config']['cropVariants']['default'] = [
                        'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_wizards.xlf:imwizard.crop_variant.default',
                        'allowedAspectRatios' => [],
                        'cropArea' => [
                            'x' => 0.0,
                            'y' => 0.0,
                            'width' => 1.0,
                            'height' => 1.0,
                        ],
                    ];
                    foreach ($legacyRatios as $ratio => $ratioLabel) {
                        $ratio = (float)$ratio;
                        $ratioId = number_format($ratio, 2);
                        $element['TCEforms']['config']['cropVariants']['default']['allowedAspectRatios'][$ratioId] = [
                            'title' => $ratioLabel,
                            'value' => $ratio,
                        ];
                    }
                }
                unset($element['TCEforms']['config']['ratios']);
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * Migrate 'max' for renderType='inputDateTime'
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateinputDateTimeMax
     */
    public function migrateinputDateTimeMax(array &$element): bool
    {
        $changed = false;

        if (isset($element['TCEforms']['config']['renderType'])) {
            if ($element['TCEforms']['config']['renderType'] === 'inputDateTime') {
                if (isset($element['TCEforms']['config']['max'])) {
                    unset($element['TCEforms']['config']['max']);
                    $changed = true;
                }
            }
        }

        return $changed;
    }

    /**
     * Migrate type='inline' properties 'foreign_types', 'foreign_selector_fieldTcaOverride'
     * and 'foreign_record_defaults' to 'overrideChildTca'
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateInlineOverrideChildTca
     */
    public function migrateInlineOverrideChildTca(array &$element): bool
    {
        $changed = false;

        if ($element['TCEforms']['config']['type'] === 'inline') {
            if (isset($element['TCEforms']['config']['foreign_types']) && is_array($element['TCEforms']['config']['foreign_types'])) {
                if (
                    isset($element['TCEforms']['config']['overrideChildTca']['types'])
                    && is_array($element['TCEforms']['config']['overrideChildTca']['types'])
                ) {
                    $element['TCEforms']['config']['overrideChildTca']['types'] = array_replace_recursive(
                        $element['TCEforms']['config']['foreign_types'],
                        $element['TCEforms']['config']['overrideChildTca']['types']
                    );
                } else {
                    $element['TCEforms']['config']['overrideChildTca']['types'] = $element['TCEforms']['config']['foreign_types'];
                }
                unset($element['TCEforms']['config']['foreign_types']);
            }
            if (isset($element['TCEforms']['config']['foreign_selector'], $element['TCEforms']['config']['foreign_selector_fieldTcaOverride']) && is_string($element['TCEforms']['config']['foreign_selector']) && is_array($element['TCEforms']['config']['foreign_selector_fieldTcaOverride'])) {
                $foreignSelectorFieldName = $element['TCEforms']['config']['foreign_selector'];
                if (
                    isset($element['TCEforms']['config']['overrideChildTca']['columns'][$foreignSelectorFieldName])
                    && is_array($element['TCEforms']['config']['overrideChildTca']['columns'][$foreignSelectorFieldName])
                ) {
                    $element['TCEforms']['config']['overrideChildTca']['columns'][$foreignSelectorFieldName] = array_replace_recursive(
                        $element['TCEforms']['config']['foreign_selector_fieldTcaOverride'],
                        $element['TCEforms']['config']['overrideChildTca']['columns'][$foreignSelectorFieldName]
                    );
                } else {
                    $element['TCEforms']['config']['overrideChildTca']['columns'][$foreignSelectorFieldName] = $element['TCEforms']['config']['foreign_selector_fieldTcaOverride'];
                }
                unset($element['TCEforms']['config']['foreign_selector_fieldTcaOverride']);
            }
            if (isset($element['TCEforms']['config']['foreign_record_defaults']) && is_array($element['TCEforms']['config']['foreign_record_defaults'])) {
                foreach ($element['TCEforms']['config']['foreign_record_defaults'] as $childFieldName => $defaultValue) {
                    if (!isset($element['TCEforms']['config']['overrideChildTca']['columns'][$childFieldName]['config']['default'])) {
                        $element['TCEforms']['config']['overrideChildTca']['columns'][$childFieldName]['config']['default'] = $defaultValue;
                    }
                }
                unset($element['TCEforms']['config']['foreign_record_defaults']);
            }
        }

        return $changed;
    }

    /**
     * removes config wizards entry, if empty
     */
    public function cleanupEmptyConfigWizardsFields(array &$element): bool
    {
        $changed = false;

        // If no wizard is left after migration, unset the whole sub array
        if (
            isset($element['TCEforms']['config']['wizards'])
            && empty($element['TCEforms']['config']['wizards'])
        ) {
            unset($element['TCEforms']['config']['wizards']);
        }

        return $changed;
    }

    /**
     * removes defaultExtra entry, if empty
     */
    public function cleanupEmptyDefaultExtraFields(array &$element): bool
    {
        $changed = false;

        if (
            isset($element['TCEforms']['defaultExtras']) // if defaultExtras is set
            && empty($element['TCEforms']['defaultExtras'])  // but is empty
        ) {
            unset($element['TCEforms']['defaultExtras']);
            $changed = true;
        }

        return $changed;
    }
}
