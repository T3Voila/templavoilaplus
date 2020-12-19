<?php
declare(strict_types=1);
namespace Tvp\TemplaVoilaPlus\Form\FormDataProvider;

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
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class EvaluateDisplayConditions implements the TCA 'displayCond' option.
 * The display condition is a colon separated string which describes
 * the condition to decide whether a form field should be displayed.
 *
 * PHP 7.0 as _only_ used in TYPO3 v8 and up code path
 */
class EvaluateDisplayConditions
    extends \TYPO3\CMS\Backend\Form\FormDataProvider\EvaluateDisplayConditions
{
    /**
     * Parsing the condition should not be done per language as this is stable accross all languages.
     */

    /**
     * Find field value the condition refers to for "FIELD:" conditions.  For "normal" TCA fields this is the value of
     * a "neighbor" field, but in flex form context it can be prepended with a sheet name. The method sorts out the
     * details and returns the current field value.
     *
     * @param string $givenFieldName The full name used in displayCond. Can have sheet names included in flex context
     * @param array $databaseRow Incoming database row values
     * @param array $flexContext Detailed flex context if display condition is within a flex field, needed to determine field value for "FIELD" conditions
     * @throws \RuntimeException
     * @return mixed The current field value from database row or a deeper flex form structure field.
     */
    protected function findFieldValue(string $givenFieldName, array $databaseRow, array $flexContext = [])
    {
        $fieldValue = null;

        // Early return for "normal" tca fields
        if (empty($flexContext)) {
            if (array_key_exists($givenFieldName, $databaseRow)) {
                $fieldValue = $databaseRow[$givenFieldName];
            }
            return $fieldValue;
        }

        // Early return for "parentRec." pointing to a databaseRow field name
        if (strpos($givenFieldName, 'parentRec.') === 0) {
            $fieldName = substr($givenFieldName, 10);
            if (array_key_exists($fieldName, $databaseRow)) {
                $fieldValue = $databaseRow[$fieldName];
            }
            return $fieldValue;
        }

        if (isset($flexContext['context'])
            && in_array($flexContext['context'], ['flexSheet', 'flexField', 'flexContainerElement'])
        ) {
            // Needs to be qualified per language but atm we are inside parsing the display condition, which shouldn't be
            // different per language
            // So we will evaluate the field value on a later point (@see evaluateFieldValueForLanguage)
            $fieldValue = [
                'fieldName' => $givenFieldName,
                'flexContext' => $flexContext,
            ];
        }

        return $fieldValue;
    }

    protected function evaluateFieldValueForLanguage($fieldFlex, $langSheetLevel, $langElementLevel)
    {
        $fieldValue = null;
        $flexContext = $fieldFlex['flexContext'];
        $givenFieldName = $fieldFlex['fieldName'];

        if ($flexContext['context'] === 'flexSheet') {
            // A display condition on a flex form sheet. Relatively simple: fieldName is either
            // "parentRec.fieldName" pointing to a databaseRow field name, or "sheetName.fieldName" pointing
            // to a field value from a neighbor field.
            if (array_key_exists($givenFieldName, $flexContext['sheetNameFieldNames'])) {
                if ($flexContext['currentSheetName'] === $flexContext['sheetNameFieldNames'][$givenFieldName]['sheetName']) {
                    throw new \RuntimeException(
                        'Configuring displayCond to "' . $givenFieldName . '" on flex form sheet "'
                        . $flexContext['currentSheetName'] . '" referencing a value from the same sheet does not make sense.',
                        1481485705
                    );
                }
            }
            $sheetName = $flexContext['sheetNameFieldNames'][$givenFieldName]['sheetName'];
            $fieldName = $flexContext['sheetNameFieldNames'][$givenFieldName]['fieldName'];
            if (!isset($flexContext['flexFormRowData']['data'][$sheetName][$langSheetLevel][$fieldName][$langElementLevel])) {
                throw new \RuntimeException(
                    'Flex form displayCond on sheet "' . $flexContext['currentSheetName'] . '" references field "' . $fieldName
                    . '" of sheet "' . $sheetName . '", but that field does not exist in current data structure',
                    1481488492
                );
            }
            $fieldValue = $flexContext['flexFormRowData']['data'][$sheetName][$langSheetLevel][$fieldName][$langElementLevel];
        } elseif ($flexContext['context'] === 'flexField') {
            // A display condition on a flex field. Handle "parentRec." similar to sheet conditions,
            // get a list of "local" field names and see if they are used as reference, else see if a
            // "sheetName.fieldName" field reference is given
            $listOfLocalFlexFieldNames = array_keys(
                $flexContext['flexFormDataStructure']['sheets'][$flexContext['currentSheetName']]['ROOT']['el']
            );
            if (in_array($givenFieldName, $listOfLocalFlexFieldNames, true)) {
                // Condition references field name of the same sheet
                $sheetName = $flexContext['currentSheetName'];
                if (!isset($flexContext['flexFormRowData']['data'][$sheetName][$langSheetLevel][$givenFieldName][$langElementLevel])) {
                    throw new \RuntimeException(
                        'Flex form displayCond on field "' . $flexContext['currentFieldName'] . '" on flex form sheet "'
                        . $flexContext['currentSheetName'] . '" references field "' . $givenFieldName . '", but a field value'
                        . ' does not exist in this sheet',
                        1481492953
                    );
                }
                $fieldValue = $flexContext['flexFormRowData']['data'][$sheetName][$langSheetLevel][$givenFieldName][$langElementLevel];
            } elseif (in_array($givenFieldName, array_keys($flexContext['sheetNameFieldNames'], true))) {
                // Condition references field name including a sheet name
                $sheetName = $flexContext['sheetNameFieldNames'][$givenFieldName]['sheetName'];
                $fieldName = $flexContext['sheetNameFieldNames'][$givenFieldName]['fieldName'];
                $fieldValue = $flexContext['flexFormRowData']['data'][$sheetName][$langSheetLevel][$fieldName][$langElementLevel];
            } else {
                throw new \RuntimeException(
                    'Flex form displayCond on field "' . $flexContext['currentFieldName'] . '" on flex form sheet "'
                    . $flexContext['currentSheetName'] . '" references a field or field / sheet combination "'
                    . $givenFieldName . '" that might be defined in given data structure but is not found in data values.',
                    1481496170
                );
            }
        } elseif ($flexContext['context'] === 'flexContainerElement') {
            // A display condition on a flex form section container element. Handle "parentRec.", compare to a
            // list of local field names, compare to a list of field names from same sheet, compare to a list
            // of sheet fields from other sheets.
            $currentSheetName = $flexContext['currentSheetName'];
            $currentFieldName = $flexContext['currentFieldName'];
            $currentContainerIdentifier = $flexContext['currentContainerIdentifier'];
            $currentContainerElementName = $flexContext['currentContainerElementName'];
            $listOfLocalContainerElementNames = array_keys(
                $flexContext['flexFormDataStructure']['sheets'][$currentSheetName]['ROOT']
                    ['el'][$currentFieldName]
                    ['children'][$currentContainerIdentifier]
                    ['el']
            );
            $listOfLocalContainerElementNamesWithSheetName = [];
            foreach ($listOfLocalContainerElementNames as $aContainerElementName) {
                $listOfLocalContainerElementNamesWithSheetName[$currentSheetName . '.' . $aContainerElementName] = [
                    'containerElementName' => $aContainerElementName,
                ];
            }
            $listOfLocalFlexFieldNames = array_keys(
                $flexContext['flexFormDataStructure']['sheets'][$currentSheetName]['ROOT']['el']
            );
            if (in_array($givenFieldName, $listOfLocalContainerElementNames, true)) {
                // Condition references field of same container instance
                $containerType = array_shift(array_keys(
                    $flexContext['flexFormRowData']['data'][$currentSheetName]
                        [$langSheetLevel][$currentFieldName]
                        ['el'][$currentContainerIdentifier]
                ));
                $fieldValue = $flexContext['flexFormRowData']['data'][$currentSheetName]
                    [$langSheetLevel][$currentFieldName]
                    ['el'][$currentContainerIdentifier]
                    [$containerType]
                    ['el'][$givenFieldName][$langElementLevel];
            } elseif (in_array($givenFieldName, array_keys($listOfLocalContainerElementNamesWithSheetName, true))) {
                // Condition references field name of same container instance and has sheet name included
                $containerType = array_shift(array_keys(
                    $flexContext['flexFormRowData']['data'][$currentSheetName]
                    [$langSheetLevel][$currentFieldName]
                    ['el'][$currentContainerIdentifier]
                ));
                $fieldName = $listOfLocalContainerElementNamesWithSheetName[$givenFieldName]['containerElementName'];
                $fieldValue = $flexContext['flexFormRowData']['data'][$currentSheetName]
                    [$langSheetLevel][$currentFieldName]
                    ['el'][$currentContainerIdentifier]
                    [$containerType]
                    ['el'][$fieldName][$langElementLevel];
            } elseif (in_array($givenFieldName, $listOfLocalFlexFieldNames, true)) {
                // Condition reference field name of sheet this section container is in
                $fieldValue = $flexContext['flexFormRowData']['data'][$currentSheetName]
                    [$langSheetLevel][$givenFieldName][$langElementLevel];
            } elseif (in_array($givenFieldName, array_keys($flexContext['sheetNameFieldNames'], true))) {
                $sheetName = $flexContext['sheetNameFieldNames'][$givenFieldName]['sheetName'];
                $fieldName = $flexContext['sheetNameFieldNames'][$givenFieldName]['fieldName'];
                $fieldValue = $flexContext['flexFormRowData']['data'][$sheetName][$langSheetLevel][$fieldName][$langElementLevel];
            } else {
                $containerType = array_shift(array_keys(
                    $flexContext['flexFormRowData']['data'][$currentSheetName]
                    [$langSheetLevel][$currentFieldName]
                    ['el'][$currentContainerIdentifier]
                ));
                throw new \RuntimeException(
                    'Flex form displayCond on section container field "' . $currentContainerElementName . '" of container type "'
                    . $containerType . '" on flex form sheet "'
                    . $flexContext['currentSheetName'] . '" references a field or field / sheet combination "'
                    . $givenFieldName . '" that might be defined in given data structure but is not found in data values.',
                    1481634649
                );
            }
        }

        return $fieldValue;
    }

    /**
     * Loop through TCA, find prepared conditions and evaluate them. Delete either the
     * field itself if the condition did not match, or the 'displayCond' in TCA.
     *
     * @param array $result
     * @return array
     */
    protected function evaluateConditions(array $result): array
    {
        // Evaluate normal tca fields first
        $listOfFlexFieldNames = [];
        foreach ($result['processedTca']['columns'] as $columnName => $columnConfiguration) {
            $conditionResult = true;
            if (isset($columnConfiguration['displayCond'])) {
                $conditionResult = $this->evaluateConditionRecursive($columnConfiguration['displayCond']);
                if (!$conditionResult) {
                    unset($result['processedTca']['columns'][$columnName]);
                } else {
                    // Always unset the whole parsed display condition to save some memory, we're done with them
                    unset($result['processedTca']['columns'][$columnName]['displayCond']);
                }
            }
            // If field was not removed and if it is a flex field, add to list of flex fields to scan
            if ($conditionResult && $columnConfiguration['config']['type'] === 'flex') {
                $listOfFlexFieldNames[] = $columnName;
            }
        }

        foreach ($listOfFlexFieldNames as $columnName) {
            $languagesOnSheetLevel = $result['processedTca']['columns'][$columnName]['config']['ds']['meta']['languagesOnSheetLevel'];
            $languagesOnElementLevel = $result['processedTca']['columns'][$columnName]['config']['ds']['meta']['languagesOnElement'];

            // Evaluate fields per lang
            foreach ($languagesOnSheetLevel as $isoSheetLevel) {
                $langSheetLevel = 'l' . $isoSheetLevel;
                foreach ($languagesOnElementLevel as $isoElementLevel) {
                    $langElementLevel = 'v' . $isoElementLevel;
                    $result = $this->evaluateConditionsForLanguage($result, $columnName, $langSheetLevel, $langElementLevel);
                }
            }
        }

        // Unset all displayCond after we processed them
        foreach ($listOfFlexFieldNames as $columnName) {
            $columnConfiguration = $result['processedTca']['columns'][$columnName];
            foreach ($columnConfiguration['config']['ds']['sheets'] as $sheetName => $sheetConfiguration) {
                // Unset per sheet
                if (is_array($sheetConfiguration['ROOT']['displayCond'])) {
                    unset(
                        $result['processedTca']['columns'][$columnName]['config']['ds']
                            ['sheets'][$sheetName]['ROOT']['displayCond']
                    );
                }

                foreach ($sheetConfiguration['ROOT']['el'] as $flexField => $flexConfiguration) {
                    // Unset per flex field
                    if (is_array($flexConfiguration['displayCond'])) {
                        unset(
                            $result['processedTca']['columns'][$columnName]['config']['ds']
                                ['sheets'][$sheetName]['ROOT']
                                ['el'][$flexField]['displayCond']
                        );
                    }

                    if (isset($flexConfiguration['children'])) {
                        foreach ($flexConfiguration['children'] as $containerInstanceName => $containerDataStructure) {
                            if (isset($containerDataStructure['el']) && is_array($containerDataStructure['el'])) {
                                foreach ($containerDataStructure['el'] as $containerElementName => $containerElementConfiguration) {
                                    // Unset per container
                                    if (is_array($containerElementConfiguration['displayCond'])) {
                                        unset(
                                            $result['processedTca']['columns'][$columnName]['config']['ds']
                                                ['sheets'][$sheetName]['ROOT']
                                                ['el'][$flexField]
                                                ['children'][$containerInstanceName]
                                                ['el'][$containerElementName]['displayCond']
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    protected function evaluateConditionsForLanguage(array $result, $columnName, $langSheetLevel, $langElementLevel): array
    {
        // Search for flex fields and evaluate sheet conditions throwing them away if needed
        $columnConfiguration = $result['processedTca']['columns'][$columnName];
        foreach ($columnConfiguration['config']['ds']['sheets'] as $sheetName => $sheetConfiguration) {
            if (is_array($sheetConfiguration['ROOT']['displayCond'])) {
                if (!$this->evaluateConditionRecursiveByLanguage($sheetConfiguration['ROOT']['displayCond'], $langSheetLevel, $langElementLevel)) {
                    unset($result['processedTca']['columns'][$columnName]['config']['ds']['sheets'][$sheetName][$langElementLevel]);
                }
            }
        }

        // With full sheets gone we loop over display conditions of single fields in flex to throw fields away if needed
        $columnConfiguration = $result['processedTca']['columns'][$columnName];
        if (is_array($columnConfiguration['config']['ds']['sheets'])) {
            foreach ($columnConfiguration['config']['ds']['sheets'] as $sheetName => $sheetConfiguration) {
                if (is_array($sheetConfiguration['ROOT']['el'])) {
                    foreach ($sheetConfiguration['ROOT']['el'] as $flexField => $flexConfiguration) {
                        $conditionResult = true;
                        if (is_array($flexConfiguration['displayCond'])) {
                            $conditionResult = $this->evaluateConditionRecursiveByLanguage($flexConfiguration['displayCond'], $langSheetLevel, $langElementLevel);
                            if (!$conditionResult) {
                                unset(
                                    $result['processedTca']['columns'][$columnName]['config']['ds']
                                        ['sheets'][$sheetName][$langSheetLevel]['ROOT']
                                        ['el'][$flexField][$langElementLevel]
                                );
                            }
                        }
                        // If it was not removed and if the field is a section container, add it to the section container list
                        if ($conditionResult
                            && isset($flexConfiguration['type']) && $flexConfiguration['type'] === 'array'
                            && isset($flexConfiguration['section']) && $flexConfiguration['section'] == 1
                            && isset($flexConfiguration['children']) && is_array($flexConfiguration['children'])
                        ) {
                            // Loop over found section container elements and evaluate their conditions
                            foreach ($flexConfiguration['children'] as $containerInstanceName => $containerDataStructure) {
                                if (isset($containerDataStructure['el']) && is_array($containerDataStructure['el'])) {
                                    foreach ($containerDataStructure['el'] as $containerElementName => $containerElementConfiguration) {
                                        if (is_array($containerElementConfiguration['displayCond'])) {
                                            if (!$this->evaluateConditionRecursive($containerElementConfiguration['displayCond'])) {
                                                unset(
                                                    $result['processedTca']['columns'][$columnName]['config']['ds']
                                                        ['sheets'][$sheetName][$langSheetLevel]['ROOT']
                                                        ['el'][$flexField][$langElementLevel]
                                                        ['children'][$containerInstanceName]
                                                        ['el'][$containerElementName]
                                                );
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    protected function evaluateConditionRecursiveByLanguage(array $conditionArray, $langSheetLevel, $langElementLevel)
    {
        switch ($conditionArray['type']) {
            case 'AND':
                $result = true;
                foreach ($conditionArray['subConditions'] as $subCondition) {
                    $result = $result && $this->evaluateConditionRecursiveByLanguage($subCondition, $langSheetLevel, $langElementLevel);
                }
                return $result;
            case 'OR':
                $result = false;
                foreach ($conditionArray['subConditions'] as $subCondition) {
                    $result = $result || $this->evaluateConditionRecursiveByLanguage($subCondition, $langSheetLevel, $langElementLevel);
                }
                return $result;
            case 'FIELD':
                $conditionArray['fieldValue'] = $this->evaluateFieldValueForLanguage($conditionArray['fieldValue'], $langSheetLevel, $langElementLevel);
                return $this->matchFieldCondition($conditionArray);
            case 'HIDE_FOR_NON_ADMINS':
                return (bool)$this->getBackendUser()->isAdmin();
            case 'REC':
                return $this->matchRecordCondition($conditionArray);
            case 'VERSION':
                return $this->matchVersionCondition($conditionArray);
            case 'USER':
                return $this->matchUserCondition($conditionArray);
        }
        return false;
    }
}
