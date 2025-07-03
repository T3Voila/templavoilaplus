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
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Controller to migrate/update the DataStructure for TYPO3 v12 LTS
 *
 * @author Alexander Opitz <opitz.alexander@googlemail.com>
 */
class DataStructureV12Controller extends AbstractUpdateController
{
    protected $errors = [];

    protected function stepStartAction()
    {
        return $this->moduleTemplate->renderResponse('stepStart');
    }

    protected function stepFinalAction()
    {
        /** @var DataStructureUpdateHandler */
        $handler = GeneralUtility::makeInstance(DataStructureUpdateHandler::class);
        $count = $handler->updateAllDs(
            [],
            [
                [$this, 'migrateInternalTypeFolderToTypeFolder'],
                [$this, 'migrateRequiredFlag'],
                [$this, 'migrateNullFlag'],
                [$this, 'migrateEmailFlagToEmailType'],
                [$this, 'migrateTypeNoneColsToSize'],
                [$this, 'migratePasswordAndSaltedPasswordToPasswordType'],
                [$this, 'migrateRenderTypeInputDateTimeToTypeDatetime'],
                [$this, 'removeAuthModeEnforce'],
                [$this, 'removeSelectAuthModeIndividualItemsKeyword'],
                [$this, 'migrateAuthMode'],
                [$this, 'migrateRenderTypeColorpickerToTypeColor'],
                [$this, 'migrateRenderTypeInputDateTimeToTypeDatetime'],
            //                [$this, 'removeAlwaysDescription'],
                [$this, 'migrateFalHandlingInInlineToTypeFile'],
            //                [$this, 'removeCtrlCruserId'],
                [$this, 'removeFalRelatedElementBrowserOptions'],
                [$this, 'removeFalRelatedOptionsFromTypeInline'],
                [$this, 'removePassContentFromTypeNone'],
                [$this, 'migrateItemsToAssociativeArray'],
                [$this, 'removeMmInsertFields'],
            //                [$this, 'removeAllowLanguageSynchronizationFromColumnsOverrides'],
            ]
        );

        $this->moduleTemplate->assignMultiple([
            'count' => $count,
            'hasErrors' => !empty($this->errors),
            'errors' => $this->errors,
        ]);
        return $this->moduleTemplate->renderResponse('stepFinal');
    }

    /**
     * Migrates [config][internal_type] = 'folder' to [config][type] = 'folder'.
     * Also removes [config][internal_type] completely, if present.
     */
    public function migrateInternalTypeFolderToTypeFolder(array &$fieldConfig): bool
    {
        $changed = false;
        if (($fieldConfig['config']['type'] ?? '') === 'group' && isset($fieldConfig['config']['internal_type'])) {
            if ($fieldConfig['config']['internal_type'] === 'folder') {
                $fieldConfig['config']['type'] = 'folder';
            }
            unset($fieldConfig['config']['internal_type']);
            $changed = true;
        }
        return $changed;
    }

    /**
     * Migrates [config][eval] = 'required' to [config][required] = true and removes 'required' from [config][eval].
     * If [config][eval] becomes empty, it will be removed completely.
     */
    public function migrateRequiredFlag(array &$fieldConfig): bool
    {
        $changed = false;
        if (GeneralUtility::inList($fieldConfig['config']['eval'] ?? '', 'required')) {
            $evalList = GeneralUtility::trimExplode(',', $fieldConfig['config']['eval'], true);
            // Remove "required" from $evalList
            $evalList = array_filter($evalList, static function (string $eval) {
                return $eval !== 'required';
            });
            if ($evalList !== []) {
                // Write back filtered 'eval'
                $fieldConfig['config']['eval'] = implode(',', $evalList);
            } else {
                // 'eval' is empty, remove whole configuration
                unset($fieldConfig['config']['eval']);
            }

            $fieldConfig['config']['required'] = true;

            $changed = true;
        }
        return $changed;
    }

    /**
     * Migrates [config][eval] = 'null' to [config][nullable] = true and removes 'null' from [config][eval].
     * If [config][eval] becomes empty, it will be removed completely.
     */
    public function migrateNullFlag(array &$fieldConfig): bool
    {
        $changed = false;
        if (GeneralUtility::inList($fieldConfig['config']['eval'] ?? '', 'null')) {
            $evalList = GeneralUtility::trimExplode(',', $fieldConfig['config']['eval'], true);
            // Remove "null" from $evalList
            $evalList = array_filter($evalList, static function (string $eval) {
                return $eval !== 'null';
            });
            if ($evalList !== []) {
                // Write back filtered 'eval'
                $fieldConfig['config']['eval'] = implode(',', $evalList);
            } else {
                // 'eval' is empty, remove whole configuration
                unset($fieldConfig['config']['eval']);
            }

            $fieldConfig['config']['nullable'] = true;

            $changed = true;
        }
        return $changed;
    }

    /**
     * Migrates [config][eval] = 'email' to [config][type] = 'email' and removes 'email' from [config][eval].
     * If [config][eval] contains 'trim', it will also be removed. If [config][eval] becomes empty, the option
     * will be removed completely.
     */
    public function migrateEmailFlagToEmailType(array &$fieldConfig): bool
    {
        $changed = false;
        if (
            ($fieldConfig['config']['type'] ?? '') === 'input'
            && GeneralUtility::inList($fieldConfig['config']['eval'] ?? '', 'email')
        ) {
            // Set the TCA type to "email"
            $fieldConfig['config']['type'] = 'email';

            // Unset "max"
            unset($fieldConfig['config']['max']);

            $evalList = GeneralUtility::trimExplode(',', $fieldConfig['config']['eval'], true);
            $evalList = array_filter($evalList, static function (string $eval) {
                // Remove anything except "unique" and "uniqueInPid" from eval
                return in_array($eval, ['unique', 'uniqueInPid'], true);
            });

            if ($evalList !== []) {
                // Write back filtered 'eval'
                $fieldConfig['config']['eval'] = implode(',', $evalList);
            } else {
                // 'eval' is empty, remove whole configuration
                unset($fieldConfig['config']['eval']);
            }

            $changed = true;
        }
        return $changed;
    }

    /**
     * Migrates type => "none" [config][cols] to [config][size] and removes "cols".
     */
    public function migrateTypeNoneColsToSize(array &$fieldConfig): bool
    {
        $changed = false;
        if (($fieldConfig['config']['type'] ?? '') === 'none' && array_key_exists('cols', $fieldConfig['config'])) {
            $fieldConfig['config']['size'] = $fieldConfig['config']['cols'];
            unset($fieldConfig['config']['cols']);

            $changed = true;
        }
        return $changed;
    }


    // phpcs:disable Generic.Metrics.CyclomaticComplexity
    /**
     * Migrates [config][renderType] = 'inputLink' to [config][type] = 'link'.
     * Migrates the [config][fieldConfig][linkPopup] to type specific configuration.
     * Removes option [config][eval].
     * Removes option [config][max], if set.
     * Removes option [config][softref], if set to "typolink".
     */
    public function migrateRenderTypeInputLinkToTypeLink(array &$fieldConfig): bool
    {
        $changed = false;
        if (
            ($fieldConfig['config']['type'] ?? '') === 'input'
            && ($fieldConfig['config']['renderType'] ?? '') === 'inputLink'
        ) {
            // Set the TCA type to "link"
            $fieldConfig['config']['type'] = 'link';

            // Unset "renderType", "max" and "eval"
            unset(
                $fieldConfig['config']['max'],
                $fieldConfig['config']['renderType'],
                $fieldConfig['config']['eval']
            );

            // Unset "softref" if set to "typolink"
            if (($fieldConfig['config']['softref'] ?? '') === 'typolink') {
                unset($fieldConfig['config']['softref']);
            }

            // Migrate the linkPopup configuration
            if (is_array($fieldConfig['config']['fieldControl']['linkPopup'] ?? false)) {
                $linkPopupConfig = $fieldConfig['config']['fieldControl']['linkPopup'];
                if ($linkPopupConfig['options']['blindLinkOptions'] ?? false) {
                    $availableTypes = $GLOBALS['TYPO3_CONF_VARS']['SYS']['linkHandler'] ?? [];
                    if ($availableTypes !== []) {
                        $availableTypes = array_keys($availableTypes);
                    } else {
                        // Fallback to a static list, in case linkHandler configuration is not available at this point
                        $availableTypes = ['page', 'file', 'folder', 'url', 'email', 'record', 'telephone'];
                    }
                    $fieldConfig['config']['allowedTypes'] = array_values(array_diff(
                        $availableTypes,
                        GeneralUtility::trimExplode(',', str_replace('mail', 'email', (string)$linkPopupConfig['options']['blindLinkOptions']), true)
                    ));
                }
                if ($linkPopupConfig['disabled'] ?? false) {
                    $fieldConfig['config']['appearance']['enableBrowser'] = false;
                }
                if ($linkPopupConfig['options']['title'] ?? false) {
                    $fieldConfig['config']['appearance']['browserTitle'] = (string)$linkPopupConfig['options']['title'];
                }
                if ($linkPopupConfig['options']['blindLinkFields'] ?? false) {
                    $fieldConfig['config']['appearance']['allowedOptions'] = array_values(array_diff(
                        ['target', 'title', 'class', 'params', 'rel'],
                        GeneralUtility::trimExplode(',', (string)$linkPopupConfig['options']['blindLinkFields'], true)
                    ));
                }
                if ($linkPopupConfig['options']['allowedExtensions'] ?? false) {
                    $fieldConfig['config']['appearance']['allowedFileExtensions'] = GeneralUtility::trimExplode(
                        ',',
                        (string)$linkPopupConfig['options']['allowedExtensions'],
                        true
                    );
                }
            }

            // Unset ['fieldControl']['linkPopup'] - Note: We do this here to ensure
            // also an invalid (e.g. not an array) field control configuration is removed.
            unset($fieldConfig['config']['fieldControl']['linkPopup']);

            // In case "linkPopup" has been the only configured fieldControl, unset ['fieldControl'], too.
            if (empty($fieldConfig['config']['fieldControl'])) {
                unset($fieldConfig['config']['fieldControl']);
            }

            $changed = true;
        }
        return $changed;
    }
    // phpcs:enable

    /**
     * Migrates [config][eval] = 'password' and [config][eval] = 'saltedPassword' to [config][type] = 'password'
     * Sets option "hashed" to FALSE if "saltedPassword" is not set for "password"
     * Removes option [config][eval].
     * Removes option [config][max], if set.
     * Removes option [config][search], if set.
     */
    public function migrateRenderTypeInputDateTimeToTypeDatetime(array &$fieldConfig): bool
    {
        $changed = false;
        if (
            ($fieldConfig['config']['type'] ?? '') === 'input'
            && (GeneralUtility::inList($fieldConfig['config']['eval'] ?? '', 'password')
                || GeneralUtility::inList($fieldConfig['config']['eval'] ?? '', 'saltedPassword'))
        ) {
            // Set the TCA type to "password"
            $fieldConfig['config']['type'] = 'password';

            $evalList = GeneralUtility::trimExplode(',', $fieldConfig['config']['eval'], true);

            // Disable password hashing, if eval=password is used standalone
            if (in_array('password', $evalList, true) && !in_array('saltedPassword', $evalList, true)) {
                $fieldConfig['config']['hashed'] = false;
            }

            // Unset "max", "search" and "eval"
            unset(
                $fieldConfig['config']['max'],
                $fieldConfig['config']['search'],
                $fieldConfig['config']['eval']
            );

            $changed = true;
        }
        return $changed;
    }

    /**
     * Migrates [config][renderType] = 'inputDateTime' to [config][type] = 'datetime'.
     * Migrates "date", "time" and "timesec" from [config][eval] to [config][format].
     * Removes option [config][eval].
     * Removes option [config][max], if set.
     * Removes option [config][format], if set.
     * Removes option [config][default], if the default is the native "empty" value
     */
    public function migratePasswordAndSaltedPasswordToPasswordType(array &$fieldConfig): bool
    {
        $changed = false;
        if (
            ($fieldConfig['config']['type'] ?? '') === 'input'
            && ($fieldConfig['config']['renderType'] ?? '') === 'inputDateTime'
        ) {
            // Set the TCA type to "datetime"
            $fieldConfig['config']['type'] = 'datetime';

            $evalList = GeneralUtility::trimExplode(',', $fieldConfig['config']['eval'] ?? '', true);

            // Set the "format" based on "eval". If set to "datetime",
            // no migration is done since this is the default format.
            if (in_array('date', $evalList, true)) {
                $fieldConfig['config']['format'] = 'date';
            } elseif (in_array('time', $evalList, true)) {
                $fieldConfig['config']['format'] = 'time';
            } elseif (in_array('timesec', $evalList, true)) {
                $fieldConfig['config']['format'] = 'timesec';
            }

            if (isset($fieldConfig['config']['default'])) {
                if (in_array($fieldConfig['config']['dbType'] ?? '', QueryHelper::getDateTimeTypes(), true)) {
                    if ($fieldConfig['config']['default'] === QueryHelper::getDateTimeFormats()[$fieldConfig['config']['dbType']]['empty']) {
                        // Unset default for native datetime fields if the default is the native "empty" value
                        unset($fieldConfig['config']['default']);
                    }
                } elseif (!is_int($fieldConfig['config']['default'])) {
                    if ($fieldConfig['config']['default'] === '') {
                        // Always use int as default (string values are no longer supported for "datetime")
                        $fieldConfig['config']['default'] = 0;
                    } elseif (MathUtility::canBeInterpretedAsInteger($fieldConfig['config']['default'])) {
                        // Cast default to int, in case it can be interpreted as integer
                        $fieldConfig['config']['default'] = (int)$fieldConfig['config']['default'];
                    } else {
                        // Unset default in case it's a no longer supported string
                        unset($fieldConfig['config']['default']);
                    }
                }
            }

            // Unset "renderType", "max" and "eval"
            // Note: Also unset "format". This option had been documented but was actually
            //       never used in the FormEngine element. This migration will set it according
            //       to the corresponding "eval" value.
            unset(
                $fieldConfig['config']['max'],
                $fieldConfig['config']['renderType'],
                $fieldConfig['config']['format'],
                $fieldConfig['config']['eval']
            );

            $changed = true;
        }
        return $changed;
    }

    /**
     * Remove ['columns'][aField]['config']['authMode_enforce']
     */
    public function removeAuthModeEnforce(array &$fieldConfig): bool
    {
        $changed = false;
        if (array_key_exists('authMode_enforce', $fieldConfig['config'] ?? [])) {
            unset($fieldConfig['config']['authMode_enforce']);
            $changed = true;
        }
        return $changed;
    }

    /**
     * If a column has authMode=individual and items with the corresponding key on position 5
     * defined, or if EXPL_ALLOW or EXPL_DENY is set for position 6, migrate or remove them.
     */
    public function removeSelectAuthModeIndividualItemsKeyword(array &$fieldConfig): bool
    {
        $changed = false;
        if (($fieldConfig['config']['type'] ?? '') === 'select' || ($fieldConfig['config']['authMode'] ?? '') === 'individual') {
            foreach ($fieldConfig['config']['items'] ?? [] as $index => $item) {
                if (in_array($item[4] ?? '', ['EXPL_ALLOW', 'EXPL_DENY'], true)) {
                    $fieldConfig['config']['items'][$index][4] = '';
                    $changed = true;
                }
                if (isset($item[5])) {
                    unset($fieldConfig['config']['items'][$index][5]);
                    $changed = true;
                }
            }
        }
        return $changed;
    }

    /**
     * See if ['columns'][aField]['config']['authMode'] is not set to 'explicitAllow' and
     * set it to this value if needed.
     */
    public function migrateAuthMode(array &$fieldConfig): bool
    {
        $changed = false;
        if (
            array_key_exists('authMode', $fieldConfig['config'] ?? [])
            && $fieldConfig['config']['authMode'] !== 'explicitAllow'
        ) {
            $fieldConfig['config']['authMode'] = 'explicitAllow';
            $changed = true;
        }
        return $changed;
    }

    /**
     * See if ['columns'][aField]['config']['authMode'] is not set to 'explicitAllow' and
     * set it to this value if needed.
     */
    public function migrateRenderTypeColorpickerToTypeColor(array &$fieldConfig): bool
    {
        $changed = false;
        if (
            ($fieldConfig['config']['type'] ?? '') === 'input'
            && ($fieldConfig['config']['renderType'] ?? '') === 'colorpicker'
        ) {
            // Set the TCA type to "color"
            $fieldConfig['config']['type'] = 'color';

            // Unset "renderType", "max" and "eval"
            unset(
                $fieldConfig['config']['max'],
                $fieldConfig['config']['renderType'],
                $fieldConfig['config']['eval']
            );
            $changed = true;
        }
        return $changed;
    }

    /**
     * Migrates [config][eval] = 'int' and [config][eval] = 'double2' to [config][type] = 'number'.
     * The migration only applies to fields without a renderType defined.
     * Adds [config][format] = "decimal" if [config][eval] = double2
     * Removes [config][eval].
     * Removes option [config][max], if set.
     */
    public function migrateEvalIntAndDouble2ToTypeNumber(array &$fieldConfig): bool
    {
        $changed = false;
        if (
            ($fieldConfig['config']['type'] ?? '') === 'input'
            && ($fieldConfig['config']['renderType'] ?? '') === ''
            && (
                GeneralUtility::inList($fieldConfig['config']['eval'] ?? '', 'int')
                || GeneralUtility::inList($fieldConfig['config']['eval'] ?? '', 'double2')
            )
        ) {
            // Set the TCA type to "number"
            $fieldConfig['config']['type'] = 'number';

            $numberType = '';
            $evalList = GeneralUtility::trimExplode(',', $fieldConfig['config']['eval'], true);

            // Convert eval "double2" to format = "decimal" and store the "number type" for the deprecation log
            if (in_array('double2', $evalList, true)) {
                $numberType = 'double2';
                $fieldConfig['config']['format'] = 'decimal';
            } elseif (in_array('int', $evalList, true)) {
                $numberType = 'int';
            }

            // Unset "max" and "eval"
            unset(
                $fieldConfig['config']['max'],
                $fieldConfig['config']['eval']
            );

            $changed = true;
        }
        return $changed;
    }

    // phpcs:disable Generic.Metrics.CyclomaticComplexity
    /**
     * Migrates type='inline' with foreign_table='sys_file_reference' to type='file'.
     * Removes table relation related options.
     * Removes no longer available appearance options.
     * Detects usage of "customControls" hook.
     * Migrates renamed appearance options.
     * Migrates allowed file extensions.
     */
    public function migrateFalHandlingInInlineToTypeFile(array &$fieldConfig): bool
    {
        $changed = false;
        if (
            ($fieldConfig['config']['type'] ?? '') === 'inline'
            && ($fieldConfig['config']['foreign_table'] ?? '') === 'sys_file_reference'
        ) {
            // Place to add additional information, which will later be appended to the deprecation message
            $additionalInformation = '';

            // Set the TCA type to "file"
            $fieldConfig['config']['type'] = 'file';

            // "new" control is not supported for this type so remove it altogether for cleaner TCA
            unset($fieldConfig['config']['appearance']['enabledControls']['new']);

            // [appearance][headerThumbnail][field] is not needed anymore
            unset($fieldConfig['config']['appearance']['headerThumbnail']['field']);

            // A couple of further appearance options are not supported by type "file", unset them as well
            unset(
                $fieldConfig['config']['appearance']['showNewRecordLink'],
                $fieldConfig['config']['appearance']['newRecordLinkAddTitle'],
                $fieldConfig['config']['appearance']['newRecordLinkTitle'],
                $fieldConfig['config']['appearance']['levelLinksPosition'],
                $fieldConfig['config']['appearance']['useCombination'],
                $fieldConfig['config']['appearance']['suppressCombinationWarning']
            );

            // Migrate [appearance][showPossibleRecordsSelector] to [appearance][showFileSelectors]
            if (isset($fieldConfig['config']['appearance']['showPossibleRecordsSelector'])) {
                $fieldConfig['config']['appearance']['showFileSelectors'] = $fieldConfig['config']['appearance']['showPossibleRecordsSelector'];
                unset($fieldConfig['config']['appearance']['showPossibleRecordsSelector']);
            }

            // "customControls" hook has been replaced by the CustomFileControlsEvent
            if (isset($fieldConfig['config']['customControls'])) {
                $additionalInformation .= ' The \'customControls\' option is not evaluated anymore and has '
                    . 'to be replaced with the PSR-14 \'CustomFileControlsEvent\'.';
                unset($fieldConfig['config']['customControls']);
            }

            // Migrate element browser related settings
            if (!empty($fieldConfig['config']['overrideChildTca']['columns']['uid_local']['config']['appearance'])) {
                if (!empty($fieldConfig['config']['overrideChildTca']['columns']['uid_local']['config']['appearance']['elementBrowserAllowed'])) {
                    // Migrate "allowed" file extensions from appearance
                    $fieldConfig['config']['allowed'] = $fieldConfig['config']['overrideChildTca']['columns']['uid_local']['config']['appearance']['elementBrowserAllowed'];
                }
                unset(
                    $fieldConfig['config']['overrideChildTca']['columns']['uid_local']['config']['appearance']['elementBrowserType'],
                    $fieldConfig['config']['overrideChildTca']['columns']['uid_local']['config']['appearance']['elementBrowserAllowed']
                );
                if (empty($fieldConfig['config']['overrideChildTca']['columns']['uid_local']['config']['appearance'])) {
                    unset($fieldConfig['config']['overrideChildTca']['columns']['uid_local']['config']['appearance']);
                    if (empty($fieldConfig['config']['overrideChildTca']['columns']['uid_local']['config'])) {
                        unset($fieldConfig['config']['overrideChildTca']['columns']['uid_local']['config']);
                        if (empty($fieldConfig['config']['overrideChildTca']['columns']['uid_local'])) {
                            unset($fieldConfig['config']['overrideChildTca']['columns']['uid_local']);
                            if (empty($fieldConfig['config']['overrideChildTca']['columns'])) {
                                unset($fieldConfig['config']['overrideChildTca']['columns']);
                                if (empty($fieldConfig['config']['overrideChildTca'])) {
                                    unset($fieldConfig['config']['overrideChildTca']);
                                }
                            }
                        }
                    }
                }
            }

            // Migrate file extension filter
            if (!empty($fieldConfig['config']['filter'])) {
                foreach ($fieldConfig['config']['filter'] as $key => $filter) {
                    if (($filter['userFunc'] ?? '') === (FileExtensionFilter::class . '->filterInlineChildren')) {
                        $allowedFileExtensions = (string)($filter['parameters']['allowedFileExtensions'] ?? '');
                        // Note: Allowed file extensions in the filter take precedence over possible
                        // extensions defined for the element browser. This is due to filters are evaluated
                        // by the DataHandler while element browser is only applied in FormEngine UI.
                        if ($allowedFileExtensions !== '') {
                            $fieldConfig['config']['allowed'] = $allowedFileExtensions;
                        }
                        $disallowedFileExtensions = (string)($filter['parameters']['disallowedFileExtensions'] ?? '');
                        if ($disallowedFileExtensions !== '') {
                            $fieldConfig['config']['disallowed'] = $disallowedFileExtensions;
                        }
                        unset($fieldConfig['config']['filter'][$key]);
                    }
                }
                // Remove filter if it got empty
                if (empty($fieldConfig['config']['filter'])) {
                    unset($fieldConfig['config']['filter']);
                }
            }


            // Remove table relation related options, since they are
            // either not needed anymore or set by TcaPreperation automatically.
            unset(
                $fieldConfig['config']['foreign_table'],
                $fieldConfig['config']['foreign_field'],
                $fieldConfig['config']['foreign_sortby'],
                $fieldConfig['config']['foreign_table_field'],
                $fieldConfig['config']['foreign_label'],
                $fieldConfig['config']['foreign_selector'],
                $fieldConfig['config']['foreign_unique']
            );

            $changed = true;
        }
        return $changed;
    }
    // phpcs:enable


    /**
     * Removes the [appearance][elementBrowserType] and [appearance][elementBrowserAllowed]
     * options from TCA type "group" fields.
     */
    public function removeFalRelatedElementBrowserOptions(array &$fieldConfig): bool
    {
        $changed = false;
        if (
            ($fieldConfig['config']['type'] ?? '') === 'group'
            && (
                isset($fieldConfig['config']['appearance']['elementBrowserType'])
                || isset($fieldConfig['config']['appearance']['elementBrowserAllowed'])
            )
        ) {
            unset(
                $fieldConfig['config']['appearance']['elementBrowserType'],
                $fieldConfig['config']['appearance']['elementBrowserAllowed']
            );

            // Also unset "appearance" if empty
            if (empty($fieldConfig['config']['appearance'])) {
                unset($fieldConfig['config']['appearance']);
            }
            $changed = true;
        }
        return $changed;
    }

    /**
     * Removes the following options from TCA type "inline" fields:
     * - [appearance][headerThumbnail]
     * - [appearance][fileUploadAllowed]
     * - [appearance][fileByUrlAllowed]
     */
    public function removeFalRelatedOptionsFromTypeInline(array &$fieldConfig): bool
    {
        $changed = false;
        if (
            ($fieldConfig['config']['type'] ?? '') === 'inline'
            && (
                isset($fieldConfig['config']['appearance']['headerThumbnail'])
                || isset($fieldConfig['config']['appearance']['fileUploadAllowed'])
                || isset($fieldConfig['config']['appearance']['fileByUrlAllowed'])
            )
        ) {
            unset(
                $fieldConfig['config']['appearance']['headerThumbnail'],
                $fieldConfig['config']['appearance']['fileUploadAllowed'],
                $fieldConfig['config']['appearance']['fileByUrlAllowed']
            );

            // Also unset "appearance" if empty
            if (empty($fieldConfig['config']['appearance'])) {
                unset($fieldConfig['config']['appearance']);
            }
            $changed = true;
        }
        return $changed;
    }


    /**
     * Removes ['config']['pass_content'] from TCA type "none" fields
     */
    public function removePassContentFromTypeNone(array &$fieldConfig): bool
    {
        $changed = false;
        if (
            ($fieldConfig['config']['type'] ?? '') === 'none'
            && array_key_exists('pass_content', $fieldConfig['config'] ?? [])
        ) {
            unset($fieldConfig['config']['pass_content']);
            $changed = true;
        }
        return $changed;
    }


    /**
     * Converts the item list of type "select", "radio" and "check" to an associated array.
     *
     * // From:
     * [
     *     0 => 'A label',
     *     1 => 'value',
     *     2 => 'icon-identifier',
     *     3 => 'group1',
     *     4 => 'a custom description'
     * ]
     *
     * // To:
     * [
     *     'label' => 'A label',
     *     'value' => 'value',
     *     'icon' => 'icon-identifier',
     *     'group' => 'group1',
     *     'description' => 'a custom description'
     * ]
     */
    public function migrateItemsToAssociativeArray(array &$fieldConfig): bool
    {
        $changed = false;
        if (
            array_key_exists('items', $fieldConfig['config'] ?? [])
            && in_array(($fieldConfig['config']['type'] ?? ''), ['select', 'radio', 'check'], true)
        ) {
            $hasLegacyItemConfiguration = false;
            $items = $fieldConfig['config']['items'];
            foreach ($items as $key => $item) {
                if (!is_array($item)) {
                    continue;
                }
                if (array_key_exists(0, $item)) {
                    $hasLegacyItemConfiguration = true;
                    $items[$key]['label'] = $item[0];
                    unset($items[$key][0]);
                }
                if (($fieldConfig['config']['type'] !== 'check') && array_key_exists(1, $item)) {
                    $hasLegacyItemConfiguration = true;
                    $items[$key]['value'] = $item[1];
                    unset($items[$key][1]);
                }
                if ($fieldConfig['config']['type'] === 'select') {
                    if (array_key_exists(2, $item)) {
                        $hasLegacyItemConfiguration = true;
                        $items[$key]['icon'] = $item[2];
                        unset($items[$key][2]);
                    }
                    if (array_key_exists(3, $item)) {
                        $hasLegacyItemConfiguration = true;
                        $items[$key]['group'] = $item[3];
                        unset($items[$key][3]);
                    }
                    if (array_key_exists(4, $item)) {
                        $hasLegacyItemConfiguration = true;
                        $items[$key]['description'] = $item[4];
                        unset($items[$key][4]);
                    }
                }
            }
            if ($hasLegacyItemConfiguration) {
                $fieldConfig['config']['items'] = $items;
                $changed = true;
            }
        }
        return $changed;
    }

    public function removeMmInsertFields(array &$fieldConfig): bool
    {
        $changed = false;
        if (isset($fieldConfig['config']['MM_insert_fields'])) {
            // @deprecated since v12.
            //             *Enable* the commented unset line in v13 when removing MM_insert_fields deprecations.
            //             *Enable* the disabled unit test set.
            // unset($tca[$table]['columns'][$fieldName]['config']['MM_insert_fields']);
            $this->errors[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' uses '
                . '\'MM_insert_fields\'. This config key is obsolete and should be removed. '
                . 'Please adjust your TCA accordingly.';
        }
        return $changed;
    }
}
