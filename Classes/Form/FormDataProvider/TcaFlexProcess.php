<?php
namespace Ppi\TemplaVoilaPlus\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\FlexFormSegment;
use TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Process data structures and data values, calculate defaults.
 *
 * This is typically the last provider, executed after TcaFlexPrepare
 */
class TcaFlexProcess implements FormDataProviderInterface
{
    /**
     * Determine possible pageTsConfig overrides and apply them to ds.
     * Determine available languages and sanitize dv for further processing. Then kick
     * and validate further details like excluded fields. Finally for each possible
     * value and ds call FormDataCompiler with set FlexFormSegment group to resolve
     * single field stuff like item processor functions.
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'flex') {
                continue;
            }

            $flexIdentifier = $this->getFlexIdentifier($result, $fieldName);
            $pageTsConfigOfFlex = $this->getPageTsOfFlex($result, $fieldName, $flexIdentifier);
            $result = $this->modifyOuterDataStructure($result, $fieldName, $pageTsConfigOfFlex);
            $result = $this->removeExcludeFieldsFromDataStructure($result, $fieldName, $flexIdentifier);
            $result = $this->removeDisabledFieldsFromDataStructure($result, $fieldName, $pageTsConfigOfFlex);
            $result = $this->prepareLanguageHandlingInDataValues($result, $fieldName);
            $result = $this->modifyDataStructureAndDataValuesByFlexFormSegmentGroup($result, $fieldName, $pageTsConfigOfFlex);
            $result = $this->addDataStructurePointersToMetaData($result, $fieldName);
            if (!empty($result['flexSectionContainerPreparation']) && version_compare(TYPO3_version, '8.6.0', '>=')) {
                // Create data and default values for a new section container, set by FormFlexAjaxController
                $result = $this->prepareNewSectionContainer($result, $fieldName);
            }
        }

        return $result;
    }

    /**
     * Take care of ds_pointerField and friends to determine the correct sub array within
     * TCA config ds.
     *
     * Gets extension identifier. Use second pointer field if it's value is not empty, "list" or "*",
     * else it must be a plugin and first one will be used.
     * This code basically determines the sub key of ds field:
     * config = array(
     *  ds => array(
     *    'aFlexConfig' => '<flexXml ...
     *     ^^^^^^^^^^^
     * $flexformIdentifier contains "aFlexConfig" after this operation.
     *
     * @todo: This method is only implemented half. It basically should do all the
     * @todo: pointer handling that is done within BackendUtility::getFlexFormDS() to $srcPointer.
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @return string Pointer
     */
    protected function getFlexIdentifier(array $result, $fieldName)
    {
        // @todo: Current implementation with the "list_type, CType" fallback is rather limited and customized for
        // @todo: tt_content, also it forces a ds_pointerField to be defined and a casual "default" sub array does not work
        $pointerFields = !empty($result['processedTca']['columns'][$fieldName]['config']['ds_pointerField'])
            ? $result['processedTca']['columns'][$fieldName]['config']['ds_pointerField']
            : 'list_type,CType';
        $pointerFields = GeneralUtility::trimExplode(',', $pointerFields);
        $flexformIdentifier = !empty($result['databaseRow'][$pointerFields[0]]) ? $result['databaseRow'][$pointerFields[0]] : '';
        if (!empty($result['databaseRow'][$pointerFields[1]])
            && $result['databaseRow'][$pointerFields[1]] !== 'list'
            && $result['databaseRow'][$pointerFields[1]] !== '*'
        ) {
            $flexformIdentifier = $result['databaseRow'][$pointerFields[1]];
        }
        if (empty($flexformIdentifier)) {
            $flexformIdentifier = 'default';
        }

        return $flexformIdentifier;
    }

    /**
     * Determine TCEFORM.aTable.aField.matchingIdentifier
     *
     * @param array $result Result array
     * @param string $fieldName Handled field name
     * @param string $flexIdentifier Determined identifier
     * @return array PageTsConfig for this flex
     */
    protected function getPageTsOfFlex(array $result, $fieldName, $flexIdentifier)
    {
        $table = $result['tableName'];
        $pageTs = [];
        if (!empty($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.'][$flexIdentifier . '.'])
            && is_array($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.'][$flexIdentifier . '.'])) {
            $pageTs = $result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.'][$flexIdentifier . '.'];
        }
        return $pageTs;
    }

    /**
     * Handle "outer" flex data structure changes like language and sheet
     * description. Does not change "TCA" or values of single elements
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param array $pageTsConfig Given pageTsConfig of this flex form
     * @return array Modified item array
     */
    protected function modifyOuterDataStructure(array $result, $fieldName, $pageTsConfig)
    {
        $modifiedDataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];

        if (isset($pageTsConfig['langDisable'])) {
            $modifiedDataStructure['meta']['langDisable'] = $pageTsConfig['langDisable'];
        }
        if (isset($pageTsConfig['langChildren'])) {
            $modifiedDataStructure['meta']['langChildren'] = $pageTsConfig['langChildren'];
        }

        if (isset($modifiedDataStructure['sheets']) && is_array($modifiedDataStructure['sheets'])) {
            // Handling multiple sheets
            foreach ($modifiedDataStructure['sheets'] as $sheetName => $sheetStructure) {
                if (isset($pageTsConfig[$sheetName . '.']) && is_array($pageTsConfig[$sheetName . '.'])) {
                    $pageTsOfSheet = $pageTsConfig[$sheetName . '.'];

                    // Remove whole sheet if disabled
                    if (!empty($pageTsOfSheet['disabled'])) {
                        unset($modifiedDataStructure['sheets'][$sheetName]);
                        continue;
                    }

                    // sheetTitle, sheetDescription, sheetShortDescr
                    $modifiedDataStructure['sheets'][$sheetName] = $this->modifySingleSheetInformation($sheetStructure, $pageTsOfSheet);
                }
            }
        }

        $modifiedDataStructure['meta']['langDisable'] = isset($modifiedDataStructure['meta']['langDisable'])
            ? (bool)$modifiedDataStructure['meta']['langDisable']
            : false;
        $modifiedDataStructure['meta']['langChildren'] = isset($modifiedDataStructure['meta']['langChildren'])
            ? (bool)$modifiedDataStructure['meta']['langChildren']
            : false;

        $result['processedTca']['columns'][$fieldName]['config']['ds'] = $modifiedDataStructure;

        return $result;
    }

    /**
     * Removes fields from data structure the user has no access to
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param string $flexIdentifier Determined identifier
     * @return array Modified result
     */
    protected function removeExcludeFieldsFromDataStructure(array $result, $fieldName, $flexIdentifier)
    {
        $dataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
        $backendUser = $this->getBackendUser();
        if ($backendUser->isAdmin() || !isset($dataStructure['sheets']) || !is_array($dataStructure['sheets'])) {
            return $result;
        }

        $userNonExcludeFields = GeneralUtility::trimExplode(',', $backendUser->groupData['non_exclude_fields']);
        $excludeFieldsPrefix = $result['tableName'] . ':' . $fieldName . ';' . $flexIdentifier . ';';
        $nonExcludeFields = [];
        foreach ($userNonExcludeFields as $userNonExcludeField) {
            if (strpos($userNonExcludeField, $excludeFieldsPrefix) !== false) {
                $exploded = explode(';', $userNonExcludeField);
                $sheetName = $exploded[2];
                $allowedFlexFieldName = $exploded[3];
                $nonExcludeFields[$sheetName][$allowedFlexFieldName] = true;
            }
        }
        foreach ($dataStructure['sheets'] as $sheetName => $sheetDefinition) {
            if (!isset($sheetDefinition['ROOT']['el']) || !is_array($sheetDefinition['ROOT']['el'])) {
                continue;
            }
            foreach ($sheetDefinition['ROOT']['el'] as $flexFieldName => $fieldDefinition) {
                if (!empty($fieldDefinition['exclude']) && !isset($nonExcludeFields[$sheetName][$flexFieldName])) {
                    unset($result['processedTca']['columns'][$fieldName]['config']['ds']['sheets'][$sheetName]['ROOT']['el'][$flexFieldName]);
                }
            }
        }

        return $result;
    }

    /**
     * Handle "outer" flex data structure changes like language and sheet
     * description. Does not change "TCA" or values of single elements
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param array $pageTsConfig Given pageTsConfig of this flex form
     * @return array Modified item array
     */
    protected function removeDisabledFieldsFromDataStructure(array $result, $fieldName, $pageTsConfig)
    {
        $dataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
        if (!isset($dataStructure['sheets']) || !is_array($dataStructure['sheets'])) {
            return $result;
        }
        foreach ($dataStructure['sheets'] as $sheetName => $sheetDefinition) {
            if (!isset($sheetDefinition['ROOT']['el']) || !is_array($sheetDefinition['ROOT']['el'])
                || !isset($pageTsConfig[$sheetName . '.'])) {
                continue;
            }
            foreach ($sheetDefinition['ROOT']['el'] as $flexFieldName => $fieldDefinition) {
                if (!empty($pageTsConfig[$sheetName . '.'][$flexFieldName . '.']['disabled'])) {
                    unset($result['processedTca']['columns'][$fieldName]['config']['ds']['sheets'][$sheetName]['ROOT']['el'][$flexFieldName]);
                }
            }
        }

        return $result;
    }

    /**
     * Remove data values in languages the user has no access to and add dummy entries
     * for languages that are available but do not exist in data values yet.
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @return array Modified item array
     */
    protected function prepareLanguageHandlingInDataValues(array $result, $fieldName)
    {
        $backendUser = $this->getBackendUser();
        $dataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];

        $langDisabled = $dataStructure['meta']['langDisable'];
        $langChildren = $dataStructure['meta']['langChildren'];

        // Existing page language overlays are only considered if options.checkPageLanguageOverlay is set in userTs
        $checkPageLanguageOverlay = false;
        if (isset($result['userTsConfig']['options.']) && is_array($result['userTsConfig']['options.'])
            && array_key_exists('checkPageLanguageOverlay', $result['userTsConfig']['options.'])
        ) {
            $checkPageLanguageOverlay = (bool)$result['userTsConfig']['options.']['checkPageLanguageOverlay'];
        }

        $systemLanguageRows = $result['systemLanguageRows'];

        // Contains all language iso code that are valid and user has access to
        $availableLanguageCodes = [];
        $defaultCodeWasAdded = false;
        foreach ($systemLanguageRows as $systemLanguageRow) {
            $isoCode = $systemLanguageRow['iso'];
            $isAvailable = true;
            if ($langDisabled && $isoCode !== 'DEF') {
                $isAvailable = false;
            }
            // @todo: Is it possible a user has no write access to default lang? If so, what to do?
            if (!$backendUser->checkLanguageAccess($systemLanguageRow['uid'])) {
                $isAvailable = false;
            }
            if ($checkPageLanguageOverlay && $systemLanguageRow['uid'] > 0) {
                $found = false;
                foreach ($result['pageLanguageOverlayRows'] as $overlayRow) {
                    if ((int)$overlayRow['sys_language_uid'] === (int)$systemLanguageRow['uid']) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $isAvailable = false;
                }
            }
            if ($isoCode === 'DEF' && $defaultCodeWasAdded) {
                $isAvailable = false;
            }
            if ($isAvailable) {
                $availableLanguageCodes[] = strtoupper($isoCode);
            }
            if ($isoCode === 'DEF') {
                $defaultCodeWasAdded = true;
            }
        }
        // Set the list of available languages in the data structure "meta" section to have it
        // available for the render engine to iterate over it.
        $result['processedTca']['columns'][$fieldName]['config']['ds']['meta']['availableLanguageCodes'] = $availableLanguageCodes;

        if (!$langChildren) {
            $allowedLanguageSheetKeys = [];
            foreach ($availableLanguageCodes as $isoCode) {
                $allowedLanguageSheetKeys['l' . $isoCode] = [];
            }
            $result = $this->setLanguageSheetsInDataValues($result, $fieldName, $allowedLanguageSheetKeys);

            // With $langChildren = 0, values must only contain vDEF prefixed keys
            $allowedValueLevelLanguageKeys = [];
            $allowedValueLevelLanguageKeys['vDEF'] = [];
            $allowedValueLevelLanguageKeys['vDEF.vDEFbase'] = [];
            // A richtext special
            $allowedValueLevelLanguageKeys['_TRANSFORM_vDEF.vDEFbase'] = [];
            $result = $this->setLanguageValueLevelValues($result, $fieldName, $allowedValueLevelLanguageKeys);
        } else {
            // langChildren is set - only lDEF as sheet language is allowed, but more fields on value field level
            $allowedLanguageSheetKeys = [
                'lDEF' => [],
            ];
            $result = $this->setLanguageSheetsInDataValues($result, $fieldName, $allowedLanguageSheetKeys);

            $allowedValueLevelLanguageKeys = [];
            foreach ($availableLanguageCodes as $isoCode) {
                $allowedValueLevelLanguageKeys['v' . $isoCode] = [];
                $allowedValueLevelLanguageKeys['v' . $isoCode . '.vDEFbase'] = [];
                $allowedValueLevelLanguageKeys['_TRANSFORM_v' . $isoCode . '.vDEFbase'] = [];
            }
            $result = $this->setLanguageValueLevelValues($result, $fieldName, $allowedValueLevelLanguageKeys);
        }

        return $result;
    }

    /**
     * Feed single flex field and data to FlexFormSegment FormData compiler and merge result.
     * This one is nasty. Goal is to have processed TCA stuff in DS and also have validated / processed data values.
     *
     * Three main parts in this method:
     * * Process values of existing section container for default values
     * * Process values and TCA of possible section container and create a default value row for each
     * * Process TCA of "normal" fields and have default values in data ['templateRows']['containerName'] parallel to section ['el']
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param array $pageTsConfig Given pageTsConfig of this flex form
     * @return array Modified item array
     * @throws \UnexpectedValueException
     */
    protected function modifyDataStructureAndDataValuesByFlexFormSegmentGroup(array $result, $fieldName, $pageTsConfig)
    {
        $dataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
        $dataValues = $result['databaseRow'][$fieldName];
        $tableName = $result['tableName'];
        $tcaValueArrayLanguage = [];

        $availableLanguageCodes = $result['processedTca']['columns'][$fieldName]['config']['ds']['meta']['availableLanguageCodes'];
        if ($dataStructure['meta']['langChildren']) {
            $languagesOnSheetLevel = [ 'DEF' ];
            $languagesOnElementLevel = $availableLanguageCodes;
        } else {
            $languagesOnSheetLevel = $availableLanguageCodes;
            $languagesOnElementLevel = [ 'DEF' ];
        }

        $result['processedTca']['columns'][$fieldName]['config']['ds']['meta']['languagesOnSheetLevel'] = $languagesOnSheetLevel;
        $result['processedTca']['columns'][$fieldName]['config']['ds']['meta']['languagesOnElement'] = $languagesOnElementLevel;

        if (!isset($dataStructure['sheets']) || !is_array($dataStructure['sheets'])) {
            return $result;
        }

        /** @var FlexFormSegment $formDataGroup */
        $formDataGroup = GeneralUtility::makeInstance(FlexFormSegment::class);
        /** @var FormDataCompiler $formDataCompiler */
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);

        foreach ($dataStructure['sheets'] as $dataStructureSheetName => $dataStructureSheetDefinition) {
            if (!isset($dataStructureSheetDefinition['ROOT']['el']) || !is_array($dataStructureSheetDefinition['ROOT']['el'])) {
                continue;
            }
            $dataStructureFields = $dataStructureSheetDefinition['ROOT']['el'];

            // Prepare pageTsConfig of this sheet
            $pageTsConfig['TCEFORM.'][$tableName . '.'] = [];
            if (isset($pageTsConfig[$dataStructureSheetName . '.']) && is_array($pageTsConfig[$dataStructureSheetName . '.'])) {
                $pageTsConfig['TCEFORM.'][$tableName . '.'] = $pageTsConfig[$dataStructureSheetName . '.'];
            }

            // It is possible to have a flex field field with of foreign_table (eg. type=select) that has markers in
             // a foreign_table_where like ###PAGE_TSCONFIG_ID###. It was possible to set this in page TSConfig for flex fields like this:
             // TCEFORM.theTable.theFlexfield.PAGE_TSCONFIG_ID = 42
             // This hands over this PAGE_TSCONFIG_ID to all flex fields that have this foreign_table_where marker.
             // This is a contradiction to the "usual" page TSConfig flex configuration that should be done for single flex fields:
             // TCEFORM.theTable.theFlexfield.theDataStructure.theSheet.theField.PAGE_TSCONFIG_ID = 42
             // The below code is a hack to still simulate the old behavior that is now deprecated.
             // @deprecated since TYPO3 v8, will be removed in TYPO3 v9
             // When deleting this code and comment block, the according code within AbstractItemProvider can be removed, too.
            if (isset($result['pageTsConfig']['TCEFORM.'][$tableName . '.'][$fieldName . '.']['PAGE_TSCONFIG_ID'])) {
                GeneralUtility::deprecationLog(
                    'The page TSConfig setting TCEFORM.' . $tableName . '.' . $fieldName . '.PAGE_TSCONFIG_ID for flex forms'
                    . ' is deprecated. Use this setting for single flex fields instead, example: TCEFORM.' . $tableName . '.'
                    . $fieldName . '.theDataStructureName.theSheet.theFieldName.PAGE_TSCONFIG_ID. Be aware these settings are'
                    . ' no longer allowed for fields within flex form section container elements.'
                );
                $pageTsConfig['flexHack.']['PAGE_TSCONFIG_ID'] = $result['pageTsConfig']['TCEFORM.'][$tableName . '.'][$fieldName . '.']['PAGE_TSCONFIG_ID'];
            }
            if (isset($result['pageTsConfig']['TCEFORM.'][$tableName . '.'][$fieldName . '.']['PAGE_TSCONFIG_IDLIST'])) {
                GeneralUtility::deprecationLog(
                    'The page TSConfig setting TCEFORM.' . $tableName . '.' . $fieldName . '.PAGE_TSCONFIG_IDLIST for flex forms'
                    . ' is deprecated. Use this setting for single flex fields instead, example: TCEFORM.' . $tableName . '.'
                    . $fieldName . '.theDataStructureName.theSheet.theFieldName.PAGE_TSCONFIG_IDLIST. Be aware these settings are'
                    . ' no longer allowed for fields within flex form section container elements.'
                );
                $pageTsConfig['flexHack.']['PAGE_TSCONFIG_IDLIST'] = $result['pageTsConfig']['TCEFORM.'][$tableName . '.'][$fieldName . '.']['PAGE_TSCONFIG_IDLIST'];
            }
            if (isset($result['pageTsConfig']['TCEFORM.'][$tableName . '.'][$fieldName . '.']['PAGE_TSCONFIG_STR'])) {
                GeneralUtility::deprecationLog(
                    'The page TSConfig setting TCEFORM.' . $tableName . '.' . $fieldName . '.PAGE_TSCONFIG_STR for flex forms'
                    . ' is deprecated. Use this setting for single flex fields instead, example: TCEFORM.' . $tableName . '.'
                    . $fieldName . '.theDataStructureName.theSheet.theFieldName.PAGE_TSCONFIG_STR.  Be aware these settings are'
                    . ' no longer allowed for fields within flex form section container elements.'
                );
                $pageTsConfig['flexHack.']['PAGE_TSCONFIG_STR'] = $result['pageTsConfig']['TCEFORM.'][$tableName . '.'][$fieldName . '.']['PAGE_TSCONFIG_STR'];
            }

             // List of "new" tca fields that have no value within the flexform, yet. Those will be compiled in one go later.
             $tcaNewColumns = [];
             // List of "edit" tca fields that have a value in flexform, already. Those will be compiled in one go later.
             $tcaEditColumns = [];
             // Contains the data values for the "edit" tca fields.
             $tcaValueArray = [
                 'uid' => $result['databaseRow']['uid'],
             ];

             foreach ($languagesOnSheetLevel as $isoSheetLevel) {
                 $langSheetLevel = 'l' . $isoSheetLevel;
                 $result['processedTca']['columns'][$fieldName]['config']['ds']['sheets'][$dataStructureSheetName][$langSheetLevel]
                    = $result['processedTca']['columns'][$fieldName]['config']['ds']['sheets'][$dataStructureSheetName];
                 foreach ($dataStructureFields as $dataStructureFieldName => $dataStructureFieldDefinition) {
                     if (isset($dataStructureFieldDefinition['type']) && $dataStructureFieldDefinition['type'] === 'array'
                        && isset($dataStructureFieldDefinition['section']) && (string)$dataStructureFieldDefinition['section'] === '1'
                     ) {
                         // A section
                         $containerDataStructuresPerContainer = [];

                         // Existing section container elements
                         if (isset($dataValues['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureFieldName]['el'])
                            && is_array($dataValues['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureFieldName]['el'])
                         ) {
                              $containerArray = $dataValues['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureFieldName]['el'];
                             foreach ($containerArray as $aContainerIdentifier => $aContainerArray) {
                                 if (is_array($aContainerArray)) {
                                     foreach ($aContainerArray as $aContainerName => $aContainerElementArray) {
                                         if ($aContainerName === '_TOGGLE') {
                                             // Don't handle internal toggle state field
                                             continue;
                                            }
                                            if (!isset($dataStructureFields[$dataStructureFieldName]['el'][$aContainerName])) {
                                                // Container not defined in ds
                                                continue;
                                            }
                                            $vanillaContainerDataStructure = $dataStructureFields[$dataStructureFieldName]['el'][$aContainerName];

                                            $newColumns = [];
                                            $editColumns = [];
                                            $valueArray = [];

                                            foreach ($vanillaContainerDataStructure['el'] as $singleFieldName => $singleFieldConfiguration) {
                                                // $singleFieldValueArray = ['data']['sSections']['lDEF']['section_1']['el']['1']['container_1']['el']['element_1']
                                                $singleFieldValueArray = [];
                                                if (isset($aContainerElementArray['el'][$singleFieldName])
                                                && is_array($aContainerElementArray['el'][$singleFieldName])
                                                 ) {
                                                    $singleFieldValueArray = $aContainerElementArray['el'][$singleFieldName];
                                                }

                                                foreach ($languagesOnElementLevel as $isoElementLevel) {
                                                    $langElementLevel = 'v' . $isoElementLevel;
                                                    if (array_key_exists($langElementLevel, $singleFieldValueArray)) {
                                                        $valueArray[$langElementLevel][$singleFieldName] = $singleFieldValueArray[$langElementLevel];
                                                    } else {
                                                        $newColumns[$langElementLevel][$singleFieldName] = $singleFieldConfiguration;
                                                    }
                                                    $editColumns[$langElementLevel][$singleFieldName] = $singleFieldConfiguration;
                                                }
                                            }

                                            foreach ($valueArray as $langElementLevel => $tcaValueArray) {
                                                // uid of "parent" is given down for inline elements to resolve correctly
                                                $tcaValueArray['uid'] = $result['databaseRow']['uid'];

                                                if (version_compare(TYPO3_version, '8.6.0', '>=')) {
                                                    $inputToFlexFormSegment = [
                                                        'tableName' => $result['tableName'],
                                                        'command' => '',
                                                        // It is currently not possible to have pageTsConfig for section container
                                                        'pageTsConfig' => [],
                                                        'databaseRow' => $tcaValueArray,
                                                        'processedTca' => [
                                                            'ctrl' => [],
                                                            'columns' => [],
                                                        ],
                                                        'selectTreeCompileItems' => $result['selectTreeCompileItems'],
                                                        'flexParentDatabaseRow' => $result['databaseRow'],
                                                    ];
                                                } else {
                                                    $inputToFlexFormSegment = [
                                                        'tableName' => $result['tableName'],
                                                        'command' => '',
                                                        // It is currently not possible to have pageTsConfig for section container
                                                        'pageTsConfig' => [],
                                                        'databaseRow' => $tcaValueArray,
                                                        'processedTca' => [
                                                            'ctrl' => [],
                                                            'columns' => [],
                                                        ],
                                                        'flexParentDatabaseRow' => $result['databaseRow'],
                                                    ];
                                                }
                                                if (!empty($newColumns[$langElementLevel])) {
                                                    // This is scenario "field has been added to data structure, but field value does not exist in value array yet"
                                                    // We want that stuff like TCA "default" values are then applied to those fields. What we do here is
                                                    // calling the data compiler with those "new" fields to fetch their values and set them in value array.
                                                    // Those fields are then compiled a second time in the "edit" phase to prepare their final TCA.
                                                    // This two-phase compiling is needed to ensure that for instance display conditions work with
                                                    // fields that may just have been added to the data structure but are not yet initialized as data value.
                                                    $inputToFlexFormSegment['command'] = 'new';
                                                    $inputToFlexFormSegment['processedTca']['columns'] = $newColumns[$langElementLevel];
                                                    $flexSegmentResult = $formDataCompiler->compile($inputToFlexFormSegment);

                                                    foreach ($newColumns[$langElementLevel] as $singleFieldName => $_) {
                                                        // Set data value result
                                                        if (array_key_exists($singleFieldName, $flexSegmentResult['databaseRow'])) {
                                                            $result['databaseRow'][$fieldName]
                                                                ['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureFieldName]['el']
                                                                [$aContainerIdentifier][$aContainerName]['el']
                                                                [$singleFieldName][$langElementLevel]
                                                                = $flexSegmentResult['databaseRow'][$singleFieldName];
                                                        }
                                                    }
                                                }
                                                if (!empty($editColumns[$langElementLevel])) {
                                                    $inputToFlexFormSegment['command'] = 'edit';
                                                    $inputToFlexFormSegment['processedTca']['columns'] = $editColumns[$langElementLevel];
                                                    $flexSegmentResult = $formDataCompiler->compile($inputToFlexFormSegment);

                                                    if (!isset($containerDataStructuresPerContainer[$aContainerIdentifier])) {
                                                        $containerDataStructuresPerContainer[$aContainerIdentifier] = $vanillaContainerDataStructure;
                                                        $containerDataStructuresPerContainer[$aContainerIdentifier]['el'] = [];
                                                    }

                                                    foreach ($editColumns[$langElementLevel] as $singleFieldName => $_) {
                                                        // Set data value result
                                                        if (array_key_exists($singleFieldName, $flexSegmentResult['databaseRow'])) {
                                                            $result['databaseRow'][$fieldName]
                                                                ['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureFieldName]['el']
                                                                [$aContainerIdentifier][$aContainerName]['el']
                                                                [$singleFieldName][$langElementLevel]
                                                                = $flexSegmentResult['databaseRow'][$singleFieldName];
                                                        }
                                                        // Set TCA structure result, actually, this call *might* be obsolete since the "dummy"
                                                        // handling below will set it again.
                                                        $result['processedTca']['columns'][$fieldName]['config']['ds']
                                                            ['sheets'][$dataStructureSheetName][$langSheetLevel]['ROOT']['el'][$dataStructureFieldName]['el']
                                                            [$aContainerName]['el'][$langElementLevel]
                                                            = $flexSegmentResult['processedTca']['columns'][$singleFieldName];

                                                        if (!isset($containerDataStructuresPerContainer[$aContainerIdentifier]['el'][$singleFieldName])) {
                                                            $containerDataStructuresPerContainer[$aContainerIdentifier]['el'][$singleFieldName]
                                                                = $flexSegmentResult['processedTca']['columns'][$singleFieldName];
                                                        }
                                                        $containerDataStructuresPerContainer[$aContainerIdentifier]['el'][$singleFieldName][$langElementLevel]
                                                            = $flexSegmentResult['processedTca']['columns'][$singleFieldName];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                } // End of existing data value handling
                                // Set 'data structures per container' next to 'el' that contains vanilla data structures

                                $result['processedTca']['columns'][$fieldName]['config']['ds']
                                    ['sheets'][$dataStructureSheetName][$langSheetLevel]['ROOT']['el']
                                    [$dataStructureFieldName]['children'] = $containerDataStructuresPerContainer;
                            } else {
                                // Force the section data array to be an empty array if there are no existing containers
                                $result['databaseRow'][$fieldName]
                                    ['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureFieldName]['el'] = [];
                                // Force data structure array to be empty if there are no existing containers
                                $result['processedTca']['columns'][$fieldName]['config']['ds']
                                    ['sheets'][$dataStructureSheetName][$langSheetLevel]['ROOT']['el']
                                    [$dataStructureFieldName]['children'] = [];
                            }
                            // Prepare "fresh" row for every possible container
                            if (isset($dataStructureFields[$dataStructureFieldName]['el']) && is_array($dataStructureFields[$dataStructureFieldName]['el'])) {
                                foreach ($dataStructureFields[$dataStructureFieldName]['el'] as $possibleContainerName => $possibleContainerConfiguration) {
                                    if (isset($possibleContainerConfiguration['el']) && is_array($possibleContainerConfiguration['el'])) {
                                        // Initialize result data array templateRows
                                        $result['databaseRow'][$fieldName]
                                            ['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureFieldName]['templateRows']
                                            [$possibleContainerName]['el'] = [];
                                        foreach ($possibleContainerConfiguration['el'] as $singleFieldName => $singleFieldConfiguration) {
                                            // Nesting type=inline in container sections is not supported. Throw an exception if configured.
                                            if (isset($singleFieldConfiguration['config']['type']) && $singleFieldConfiguration['config']['type'] === 'inline') {
                                                throw new \UnexpectedValueException(
                                                    'Invalid flex form data structure on field name "' . $fieldName . '" with element "' . $singleFieldName . '"'
                                                    . ' in section container "' . $possibleContainerName . '": Nesting inline elements in flex form'
                                                    . ' sections is not allowed.',
                                                    1458745468
                                                );
                                            }

                                            // Nesting sections is not supported. Throw an exception if configured.
                                            if (is_array($singleFieldConfiguration)
                                            && isset($singleFieldConfiguration['type']) && $singleFieldConfiguration['type'] === 'array'
                                            && isset($singleFieldConfiguration['section']) && (string)$singleFieldConfiguration['section'] === '1'
                                            ) {
                                                throw new \UnexpectedValueException(
                                                    'Invalid flex form data structure on field name "' . $fieldName . '" with element "' . $singleFieldName . '"'
                                                    . ' in section container "' . $possibleContainerName . '": Nesting sections in container elements'
                                                    . ' sections is not allowed.',
                                                    1458745712
                                                );
                                            }
                                            foreach ($languagesOnElementLevel as $isoElementLevel) {
                                                $langElementLevel = 'v' . $isoElementLevel;
                                                if (version_compare(TYPO3_version, '8.6.0', '>=')) {
                                                    $inputToFlexFormSegment = [
                                                        'tableName' => $result['tableName'],
                                                        'command' => 'new',
                                                        'pageTsConfig' => [],
                                                        'databaseRow' => [
                                                            'uid' => $result['databaseRow']['uid'],
                                                        ],
                                                        'processedTca' => [
                                                            'ctrl' => [],
                                                            'columns' => [
                                                                $singleFieldName => $singleFieldConfiguration,
                                                            ],
                                                        ],
                                                        'selectTreeCompileItems' => $result['selectTreeCompileItems'],
                                                        'flexParentDatabaseRow' => $result['databaseRow'],
                                                    ];
                                                } else {
                                                    $inputToFlexFormSegment = [
                                                        'tableName' => $result['tableName'],
                                                        'command' => 'new',
                                                        'pageTsConfig' => [],
                                                        'databaseRow' => [
                                                            'uid' => $result['databaseRow']['uid'],
                                                        ],
                                                        'processedTca' => [
                                                            'ctrl' => [],
                                                            'columns' => [
                                                                $singleFieldName => $singleFieldConfiguration,
                                                            ],
                                                        ],
                                                        'flexParentDatabaseRow' => $result['databaseRow'],
                                                    ];
                                                }
                                                $flexSegmentResult = $formDataCompiler->compile($inputToFlexFormSegment);
                                                if (array_key_exists($singleFieldName, $flexSegmentResult['databaseRow'])) {
                                                    $result['databaseRow'][$fieldName]
                                                        ['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureFieldName]['templateRows']
                                                        [$possibleContainerName]['el'][$singleFieldName][$langElementLevel]
                                                        = $flexSegmentResult['databaseRow'][$singleFieldName];
                                                }
                                                $result['processedTca']['columns'][$fieldName]['config']['ds']
                                                    ['sheets'][$dataStructureSheetName][$langSheetLevel]['ROOT']['el'][$dataStructureFieldName]['el']
                                                    [$possibleContainerName]['el'][$singleFieldName][$langElementLevel]
                                                    = $flexSegmentResult['processedTca']['columns'][$singleFieldName];
                                            }
                                        }
                                    }
                                }
                            } // End of preparation for each possible container

                        // A "normal" TCA element
                        } else {
                            foreach ($languagesOnElementLevel as $isoElementLevel) {
                                $langElementLevel = 'v' . $isoElementLevel;
                                if (isset($dataValues['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureFieldName])
                                    && array_key_exists($langElementLevel, $dataValues['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureFieldName])
                                ) {
                                    $tcaEditColumns[$dataStructureFieldName] = $dataStructureFieldDefinition;
                                } else {
                                    $tcaNewColumns[$dataStructureFieldName] = $dataStructureFieldDefinition;
                                }
                                $tcaValueArrayLanguage[$langElementLevel][$dataStructureFieldName]
                                    = $dataValues['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureFieldName][$langElementLevel];
                               }
                        } // End of single element handling
                    }

                    foreach ($tcaValueArrayLanguage as $langElementLevel => $tcaValueArray) {
                        // uid of "parent" is given down for inline elements to resolve correctly
                        $tcaValueArray['uid'] = $result['databaseRow']['uid'];

                        if (version_compare(TYPO3_version, '8.6.0', '>=')) {
                            // process the tca columns for the current sheet
                            $inputToFlexFormSegment = [
                                // tablename of "parent" is given down for inline elements to resolve correctly
                                'tableName' => $result['tableName'],
                                'command' => '',
                                'pageTsConfig' => $pageTsConfig,
                                'databaseRow' => $tcaValueArray,
                                'processedTca' => [
                                    'ctrl' => [],
                                    'columns' => [],
                                ],
                                'selectTreeCompileItems' => $result['selectTreeCompileItems'],
                                'flexParentDatabaseRow' => $result['databaseRow'],
                            ];
                        } else {
                            // process the tca columns for the current sheet
                            $inputToFlexFormSegment = [
                                // tablename of "parent" is given down for inline elements to resolve correctly
                                'tableName' => $result['tableName'],
                                'command' => '',
                                'pageTsConfig' => $pageTsConfig,
                                'databaseRow' => $tcaValueArray,
                                'processedTca' => [
                                    'ctrl' => [],
                                    'columns' => [],
                                ],
                                'flexParentDatabaseRow' => $result['databaseRow'],
                            ];
                        }

                        if (!empty($tcaNewColumns)) {
                            $inputToFlexFormSegment['command'] = 'new';
                            $inputToFlexFormSegment['processedTca']['columns'] = $tcaNewColumns;
                            $flexSegmentResult = $formDataCompiler->compile($inputToFlexFormSegment);

                            foreach ($tcaNewColumns as $dataStructureFieldName => $_) {
                                // Set data value result
                                if (array_key_exists($dataStructureFieldName, $flexSegmentResult['databaseRow'])) {
                                    $result['databaseRow'][$fieldName]
                                        ['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureFieldName][$langElementLevel]
                                        = $flexSegmentResult['databaseRow'][$dataStructureFieldName];
                                }
                                // Set TCA structure result
                                $result['processedTca']['columns'][$fieldName]['config']['ds']
                                    ['sheets'][$dataStructureSheetName][$langSheetLevel]['ROOT']['el'][$dataStructureFieldName][$langElementLevel]
                                    = $flexSegmentResult['processedTca']['columns'][$dataStructureFieldName];
                            }
                        }

                        if (!empty($tcaEditColumns)) {
                            $inputToFlexFormSegment['command'] = 'edit';
                            $inputToFlexFormSegment['processedTca']['columns'] = $tcaEditColumns;
                            $flexSegmentResult = $formDataCompiler->compile($inputToFlexFormSegment);

                            foreach ($tcaEditColumns as $dataStructureFieldName => $_) {
                                // Set data value result
                                if (array_key_exists($dataStructureFieldName, $flexSegmentResult['databaseRow'])) {
                                    $result['databaseRow'][$fieldName]
                                        ['data'][$dataStructureSheetName][$langSheetLevel][$dataStructureFieldName][$langElementLevel]
                                        = $flexSegmentResult['databaseRow'][$dataStructureFieldName];
                                }
                                // Set TCA structure result
                                $result['processedTca']['columns'][$fieldName]['config']['ds']
                                    ['sheets'][$dataStructureSheetName][$langSheetLevel]['ROOT']['el'][$dataStructureFieldName][$langElementLevel]
                                    = $flexSegmentResult['processedTca']['columns'][$dataStructureFieldName];
                            }
                        }
                    }
                }
        }

        return $result;
    }

    /**
     * Prepare data structure and data values for a new section container.
     *
     * @param array $result Incoming result array
     * @param string $fieldName The field name with this flex form
     * @return array Modified result
     */
    protected function prepareNewSectionContainer(array $result, string $fieldName)
    {
        $flexSectionContainerPreparation = $result['flexSectionContainerPreparation'];
        $flexFormSheetName = $flexSectionContainerPreparation['flexFormSheetName'];
        $flexFormFieldName = $flexSectionContainerPreparation['flexFormFieldName'];
        $flexFormContainerName = $flexSectionContainerPreparation['flexFormContainerName'];
        $flexFormContainerIdentifier = $flexSectionContainerPreparation['flexFormContainerIdentifier'];

        $containerConfiguration = $result['processedTca']['columns'][$fieldName]['config']['ds']
            ['sheets'][$flexFormSheetName]['ROOT']['el'][$flexFormFieldName]['el'][$flexFormContainerName];

        if (isset($containerConfiguration['el']) && is_array($containerConfiguration['el'])) {
            $formDataGroup = GeneralUtility::makeInstance(FlexFormSegment::class);
            $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
            $inputToFlexFormSegment = [
                'tableName' => $result['tableName'],
                'command' => 'new',
                // It is currently not possible to have pageTsConfig for section container
                'pageTsConfig' => [],
                'databaseRow' => [
                    'uid' => $result['databaseRow']['uid'],
                ],
                'processedTca' => [
                    'ctrl' => [],
                    'columns' => $containerConfiguration['el'],
                ],
                'selectTreeCompileItems' => $result['selectTreeCompileItems'],
                'flexParentDatabaseRow' => $result['databaseRow'],
            ];
            $flexSegmentResult = $formDataCompiler->compile($inputToFlexFormSegment);

            foreach ($containerConfiguration['el'] as $singleFieldName => $singleFieldConfiguration) {
                // Set 'data structures for this new container' to 'children'
                $result['processedTca']['columns'][$fieldName]['config']['ds']
                    ['sheets'][$flexFormSheetName]['ROOT']['el']
                    [$flexFormFieldName]['children'][$flexFormContainerIdentifier]
                    = $containerConfiguration;
                $result['processedTca']['columns'][$fieldName]['config']['ds']
                    ['sheets'][$flexFormSheetName]['ROOT']['el']
                    [$flexFormFieldName]['children'][$flexFormContainerIdentifier]['el']
                    = $flexSegmentResult['processedTca']['columns'];
                // Set calculated value - this especially contains "default values from TCA"
                $result['databaseRow'][$fieldName]['data'][$flexFormSheetName]['lDEF']
                    [$flexFormFieldName]['el']
                    [$flexFormContainerIdentifier][$flexFormContainerName]['el'][$singleFieldName]['vDEF']
                    = $flexSegmentResult['databaseRow'][$singleFieldName];
            }
        }

        return $result;
    }
    /**
     * Modify data structure of a single "sheet"
     * Sets "secondary" data like sheet names and so on, but does NOT modify single elements
     *
     * @param array $dataStructure Given data structure
     * @param array $pageTsOfSheet Page Ts config of given field
     * @return array Modified data structure
     */
    protected function modifySingleSheetInformation(array $dataStructure, array $pageTsOfSheet)
    {
        // Return if no elements defined
        if (!isset($dataStructure['ROOT']['el']) || !is_array($dataStructure['ROOT']['el'])) {
            return $dataStructure;
        }

        // Rename sheet (tab)
        if (!empty($pageTsOfSheet['sheetTitle'])) {
            $dataStructure['ROOT']['sheetTitle'] = $pageTsOfSheet['sheetTitle'];
        }
        // Set sheet description (tab)
        if (!empty($pageTsOfSheet['sheetDescription'])) {
            $dataStructure['ROOT']['sheetDescription'] = $pageTsOfSheet['sheetDescription'];
        }
        // Set sheet short description (tab)
        if (!empty($pageTsOfSheet['sheetShortDescr'])) {
            $dataStructure['ROOT']['sheetShortDescr'] = $pageTsOfSheet['sheetShortDescr'];
        }

        return $dataStructure;
    }

    /**
     * Add new sheet languages not yet in data values and remove invalid ones
     *
     * databaseRow['aFlex']['data']['sDEF'] = array('lDEF', 'lNotAllowed');
     * allowedLanguageKeys = array('lDEF', 'lNEW')
     * -> databaseRow['aFlex']['data']['sDEF'] = array('lDEF', 'lNEW');
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param array $allowedKeys List of allowed keys
     * @return array Modified result
     */
    protected function setLanguageSheetsInDataValues(array $result, $fieldName, array $allowedKeys)
    {
        $valueArray = [];
        if (isset($result['databaseRow'][$fieldName]['data']) && is_array($result['databaseRow'][$fieldName]['data'])) {
            $valueArray = $result['databaseRow'][$fieldName]['data'];
        }
        foreach ($valueArray as $sheetName => $sheetLanguages) {
            // Add iso code with empty array if it does not yet exist in data
            // and remove codes from data that do not exist in $allowed
            $result['databaseRow'][$fieldName]['data'][$sheetName]
                = array_intersect_key(array_merge($allowedKeys, $sheetLanguages), $allowedKeys);
        }
        return $result;
    }

    /**
     * Remove invalid keys from data value array the user has no access to
     * or that were removed or similar to prevent any rendering of this stuff
     *
     * Handles this for "normal" fields and also for section container element values.
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param array $allowedKeys List of allowed keys
     * @return array Modified result
     */
    protected function setLanguageValueLevelValues(array $result, $fieldName, $allowedKeys)
    {
        $valueArray = [];
        if (isset($result['databaseRow'][$fieldName]['data']) && is_array($result['databaseRow'][$fieldName]['data'])) {
            $valueArray = $result['databaseRow'][$fieldName]['data'];
        }
        foreach ($valueArray as $sheetName => $sheetLanguages) {
            if (!is_array($sheetLanguages)) {
                continue;
            }
            foreach ($sheetLanguages as $languageName => $languageFields) {
                if (!is_array($languageFields)) {
                    continue;
                }
                foreach ($languageFields as $flexFieldName => $fieldValues) {
                    if (!is_array($fieldValues)) {
                        continue;
                    }
                    $allowedSingleValues = [];
                    foreach ($fieldValues as $fieldValueName => $fieldValueValue) {
                        if (is_array($fieldValueValue) && $fieldValueName === 'el') {
                            // A section container
                            foreach ($fieldValueValue as $sectionNumber => $sectionElementArray) {
                                if (is_array($sectionElementArray)) {
                                    $allowedSingleValues['el'][$sectionNumber] = [];
                                    foreach ($sectionElementArray as $sectionElementName => $containerElementArray) {
                                        if (isset($containerElementArray['el']) && is_array($containerElementArray['el']) && !empty($containerElementArray['el'])) {
                                            foreach ($containerElementArray['el'] as $aContainerElementName => $aContainerElementValues) {
                                                if (is_array($aContainerElementValues)) {
                                                    foreach ($aContainerElementValues as $aContainerElementValueKey => $aContainerElementValueValue) {
                                                        if (array_key_exists($aContainerElementValueKey, $allowedKeys)) {
                                                            $allowedSingleValues['el'][$sectionNumber][$sectionElementName]
                                                            ['el'][$aContainerElementName][$aContainerElementValueKey] = $aContainerElementValueValue;
                                                        }
                                                    }
                                                } else {
                                                    $allowedSingleValues['el'][$sectionNumber][$sectionElementName]['el']
                                                    [$aContainerElementName] = $aContainerElementValues;
                                                }
                                            }
                                        } else {
                                            $allowedSingleValues['el'][$sectionNumber][$sectionElementName] = $containerElementArray;
                                        }
                                    }
                                } else {
                                    $allowedSingleValues = $sectionElementArray;
                                }
                            }
                        } else {
                            // "normal" value field
                            if (array_key_exists($fieldValueName, $allowedKeys)) {
                                $allowedSingleValues[$fieldValueName] = $fieldValueValue;
                            }
                        }
                    }
                    $result['databaseRow'][$fieldName]['data'][$sheetName][$languageName][$flexFieldName] = $allowedSingleValues;
                }
            }
        }
        return $result;
    }

    /**
     * Add fields and values used by ds_pointerField to the meta data array so they can be used in AJAX context during rendering.
     *
     * @todo: This method is a stopgap measure to get required information into the AJAX controller
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @return array
     * @internal
     */
    protected function addDataStructurePointersToMetaData(array $result, $fieldName)
    {
        if (empty($result['processedTca']['columns'][$fieldName]['config']['ds_pointerField'])) {
            return $result;
        }

        $pointerFields = GeneralUtility::trimExplode(
            ',',
            $result['processedTca']['columns'][$fieldName]['config']['ds_pointerField']
        );
        $dsPointers = [
            $pointerFields[0] => !empty($result['databaseRow'][$pointerFields[0]]) ? $result['databaseRow'][$pointerFields[0]] : ''
        ];

        if (!empty($pointerFields[1])) {
            $dsPointers[$pointerFields[1]] =
                !empty($result['databaseRow'][$pointerFields[1]]) ? $result['databaseRow'][$pointerFields[1]] : '';
        }
        $result['processedTca']['columns'][$fieldName]['config']['ds']['meta']['dataStructurePointers'] = $dsPointers;
        return $result;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
