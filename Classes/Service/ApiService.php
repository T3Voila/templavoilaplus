<?php

namespace Tvp\TemplaVoilaPlus\Service;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Public API class for proper handling of content elements and other useful TemplaVoila related functions
 */
class ApiService
{
    /**
     * @var string
     */
    protected $rootTable;

    /**
     * @var bool
     */
    public $debug = false;

    /**
     * @var array
     */
    protected $cachedModWebTSconfig = [];

    /** @var IconFactory */
    protected $iconFactory;

    /**
     * @param string $rootTable Usually the root table is "pages" but another table can be specified (eg. "tt_content")
     */
    public function __construct($rootTable = 'pages')
    {
        $this->rootTable = $rootTable;
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }


    /******************************************************
     *
     * Flexform helper functions (public)
     *
     ******************************************************/

    /**
     * Returns an array of flexform pointers pointing to all occurrences of a tt_content record with uid $recordUid
     * on the page with uid $pageUid.
     *
     * @param int $elementUid UID of a tt_content record
     * @param int $pageUid UID of the page to search in
     *
     * @return array Array of flexform pointers
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function flexform_getPointersByRecord($elementUid, $pageUid)
    {
        // phpcs:enable
        $dummyArr = [];
        $flexformPointersArr = $this->flexform_getFlexformPointersToSubElementsRecursively('pages', $pageUid, $dummyArr);

        $resultPointersArr = [];
        if (is_array($flexformPointersArr)) {
            foreach ($flexformPointersArr as $flexformPointerArr) {
                if ($flexformPointerArr['targetCheckUid'] == $elementUid) {
                    $resultPointersArr[] = $flexformPointerArr;
                }
            }
        }

        return $resultPointersArr;
    }

    /**
     * Returns an array of flexform pointers to all sub elements of the element specified by $table and $uid.
     *
     * @param string $table Name of the table of the parent element ('pages' or 'tt_content')
     * @param int $uid UID of the parent element
     * @param array $flexformPointers Array of flexform pointers - used internally, don't touch
     * @param integer $recursionDepth Tracks the current level of recursion - used internally, don't touch.
     *
     * @return array Array of flexform pointers
     */
    // phpcs:disable Generic.Metrics.NestingLevel
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function flexform_getFlexformPointersToSubElementsRecursively($table, $uid, &$flexformPointers, $recursionDepth = 0)
    {
        // phpcs:enable
        if (!is_array($flexformPointers)) {
            $flexformPointers = [];
        }
        $parentRecord = BackendUtility::getRecordWSOL($table, $uid, 'uid,pid,tx_templavoilaplus_flex,tx_templavoilaplus_map');

        if ($parentRecord === null) {
            return $flexformPointers;
        }

        $flexFieldArr = GeneralUtility::xml2array($parentRecord['tx_templavoilaplus_flex']);
        $expandedDataStructure = $this->ds_getExpandedDataStructure($table, $parentRecord);

        if (is_array($flexFieldArr) && is_array($flexFieldArr['data'])) {
            foreach ($flexFieldArr['data'] as $sheetKey => $languagesArr) {
                if (is_array($languagesArr)) {
                    foreach ($languagesArr as $languageKey => $fieldsArr) {
                        if (is_array($fieldsArr)) {
                            foreach ($fieldsArr as $fieldName => $valuesArr) {
                                if (is_array($valuesArr)) {
                                    foreach ($valuesArr as $valueName => $value) {
                                        $fieldDS = $expandedDataStructure[$sheetKey]['ROOT']['el'][$fieldName];
                                        if (
                                            ($fieldDS['tx_templavoilaplus']['eType'] ?? '') === 'ce' /** @TODO What the hell? */
                                            || (
                                                ($fieldDS['config']['allowed'] ?? '') === 'tt_content'
                                                && ($fieldDS['config']['internal_type'] ?? '') === 'db'
                                             )
                                        ) {
                                            $valueItems = GeneralUtility::intExplode(',', $value);
                                            if (is_array($valueItems)) {
                                                $position = 1;
                                                foreach ($valueItems as $subElementUid) {
                                                    if ($subElementUid > 0) {
                                                        $flexformPointers[] = [
                                                            'table' => $table,
                                                            'uid' => $uid,
                                                            'sheet' => $sheetKey,
                                                            'sLang' => $languageKey,
                                                            'field' => $fieldName, /** @TODO What is with sections/arrays? */
                                                            'vLang' => $valueName,
                                                            'position' => $position,
                                                            'targetCheckUid' => $subElementUid,
                                                        ];
                                                        if ($recursionDepth < 100) {
                                                            $this->flexform_getFlexformPointersToSubElementsRecursively('tt_content', $subElementUid, $flexformPointers, $recursionDepth + 1);
                                                        }
                                                        $position++;
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
            }
        }

        return $flexformPointers;
    }

    /******************************************************
     *
     * Flexform helper functions (protected)
     *
     ******************************************************/



    /******************************************************
     *
     * Data structure helper functions (public)
     *
     ******************************************************/

    /**
     * Maps old-style tt_content column positions (0 = Normal, 1 = Left etc.) to data structure field names.
     *
     * If the fields are configured by using the "oldStyleColumnNumber" tag, the correct field name will be returned
     * by using this information. If no configuration was found for the given column position, the field name of
     * the "Normal" column will be returned. If the "Normal" column is not defined either, the field name of the
     * first field of eType "ce" will be delivered.
     *
     * If all that fails, this function returns FALSE.
     *
     * @param int $contextPageUid The (current) page uid, used to determine which page datastructure is selected
     * @param int $columnPosition Column number to search a field for
     *
     * @return mixed Either the field name relating to the given column number or FALSE if all fall back methods failed and no suitable field could be found.
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function ds_getFieldNameByColumnPosition($contextPageUid, $columnPosition)
    {
        // phpcs:enable
        $foundFieldName = false;
        $columnsAndFieldNamesArr = [];
        $fieldNameOfFirstCEField = null;

        $pageRow = BackendUtility::getRecordWSOL('pages', $contextPageUid);
        if (!is_array($pageRow)) {
            return false;
        }

        $dataStructureArr = $this->ds_getExpandedDataStructure('pages', $pageRow);

        // Traverse the data structure and search for oldStyleColumnNumber configurations:
        if (is_array($dataStructureArr)) {
            foreach ($dataStructureArr as $sheetDataStructureArr) {
                if (is_array($sheetDataStructureArr['ROOT']['el'])) {
                    foreach ($sheetDataStructureArr['ROOT']['el'] as $fieldName => $fieldConfiguration) {
                        if (is_array($fieldConfiguration)) {
                            if (isset($fieldConfiguration['tx_templavoilaplus']['oldStyleColumnNumber'])) {
                                $columnNumber = $fieldConfiguration['tx_templavoilaplus']['oldStyleColumnNumber'];
                                if (!isset($columnsAndFieldNamesArr[$columnNumber])) {
                                    $columnsAndFieldNamesArr[$columnNumber] = $fieldName;
                                }
                            }
                            if ($fieldConfiguration['tx_templavoilaplus']['eType'] == 'ce' && !isset($fieldNameOfFirstCEField)) {
                                $fieldNameOfFirstCEField = $fieldName;
                            }
                        }
                    }
                }
            }
        }

        // Let's see what we have found:
        if (isset($columnsAndFieldNamesArr[$columnPosition])) {
            $foundFieldName = $columnsAndFieldNamesArr[$columnPosition];
        } elseif (isset($columnsAndFieldNamesArr[0])) {
            $foundFieldName = $columnsAndFieldNamesArr[0];
        } elseif (isset($fieldNameOfFirstCEField)) {
            $foundFieldName = $fieldNameOfFirstCEField;
        }

        return $foundFieldName;
    }

    /**
     * Maps data structure field names to old-style tt_content column positions (0 = Normal, 1 = Left etc.)
     *
     * If the fields are configured by using the "oldStyleColumnNumber" tag, the correct column number will be returned
     * by using this information. If no configuration was found for the given field, 0 will be returned.
     *
     * Reverse function of ds_getFieldNameByColumnPosition()
     *
     * @param int $contextPageUid The (current) page uid, used to determine which page datastructure is selected
     * @param string $fieldName Field name in the data structure we are searching the column number for
     *
     * @return int The column number as used in the "colpos" field in tt_content
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function ds_getColumnPositionByFieldName($contextPageUid, $fieldName)
    {
        // phpcs:enable
        $pageRow = BackendUtility::getRecordWSOL('pages', $contextPageUid);
        if (is_array($pageRow)) {
            $dataStructureArr = $this->ds_getExpandedDataStructure('pages', $pageRow);

            // Traverse the data structure and search for oldStyleColumnNumber configurations:
            if (is_array($dataStructureArr)) {
                foreach ($dataStructureArr as $sheetDataStructureArr) {
                    if (is_array($sheetDataStructureArr['ROOT']['el'])) {
                        if (is_array($sheetDataStructureArr['ROOT']['el'][$fieldName])) {
                            if (isset($sheetDataStructureArr['ROOT']['el'][$fieldName]['tx_templavoilaplus']['oldStyleColumnNumber'])) {
                                return (int)$sheetDataStructureArr['ROOT']['el'][$fieldName]['tx_templavoilaplus']['oldStyleColumnNumber'];
                            }
                        }
                    }
                }
            }
        }

        return 0;
    }

    /**
     * Returns the data structure for a flexform field ("tx_templavoilaplus_flex") from $table (by using $row). The DS will
     * be expanded, ie. you can be sure that it is structured by sheets even if only one sheet exists.
     *
     * @param string $table The table name, usually "pages" or "tt_content"
     * @param array $row The data row (used to get DS if DS is dependent on the data in the record)
     *
     * @return array The data structure, expanded for all sheets inside.
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function ds_getExpandedDataStructure($table, array $row)
    {
        // phpcs:enable
        $dataStructureArr = [];

        $flexFormTools = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class);
        $dataStructureIdentifier = $flexFormTools->getDataStructureIdentifier(
            $GLOBALS['TCA'][$table]['columns']['tx_templavoilaplus_flex'],
            $table,
            'tx_templavoilaplus_flex',
            $row
        );
        $dataStructureArr = $flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);
        $expandedDataStructureArr = $dataStructureArr['sheets'];

        return $expandedDataStructureArr;
    }

    /******************************************************
     *
     * Miscellaneous functions (protected)
     *
     ******************************************************/

    /**
     * Sets a flag to tell the TemplaVoila TCEmain userfunctions if this API has called a TCEmain
     * function. If this flag is set, the TemplaVoila TCEmain userfunctions will be skipped to
     * avoid infinite loops and other bad effects.
     *
     * @param bool $flag If TRUE, our user functions will be omitted
     */
    public function setTCEmainRunningFlag($flag)
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoilaplus_api']['apiIsRunningTCEmain'] = $flag;
    }

    /**
     * Returns the current flag which tells TemplaVoila TCEmain userfunctions if this API has called a TCEmain
     * function. If this flag is set, the TemplaVoila TCEmain userfunctions will be skipped to
     * avoid infinite loops and other bad effects.
     *
     * @return bool TRUE if flag is set, otherwise FALSE;
     */
    public function getTCEmainRunningFlag()
    {
        return $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoilaplus_api']['apiIsRunningTCEmain'] ? true : false;
    }


    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    public function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * NEW PARTS OR ACCEPTED
     */
    /**
     * @return string|null
     */
    public function getMapIdentifierFromRootline(array $rootline)
    {
        $mapBackupIdentifier = null;

        $isFirst = true;
        // Find in rootline upwards
        foreach ($rootline as $key => $pageRecord) {
            if ($isFirst) {
                $isFirst = false;
                continue;
            }

            if ($pageRecord['tx_templavoilaplus_next_map'] ?? null) {
                // If there is a next-level MAP:
                return $pageRecord['tx_templavoilaplus_next_map'];
            }
            if (($pageRecord['tx_templavoilaplus_map'] ?? null) && !$mapBackupIdentifier) {
                // Otherwise try the NORMAL MAP as backup
                $mapBackupIdentifier = $pageRecord['tx_templavoilaplus_map'];
            }
        }

        return $mapBackupIdentifier;
    }

    public function getBackendRootline($uid): array
    {
        $rootLine = BackendUtility::BEgetRootLine($uid, '', true);
        foreach ($rootLine as $key => $rootLineRecord) {
            $rootLine[$key] = BackendUtility::getRecordWSOL('pages', $rootLineRecord['uid']);
        }
        return $rootLine;
    }
}
