<?php
namespace Ppi\TemplaVoilaPlus\Configuration\FlexForm;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowLoopException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowRootException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidPointerFieldValueException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains functions for manipulating flex form data
 */
class FlexFormTools8 extends FlexFormTools
{
    /**
     * Handler for Flex Forms
     *
     * @param string $table The table name of the record
     * @param string $field The field name of the flexform field to work on
     * @param array $row The record data array
     * @param object $callBackObj Object in which the call back function is located
     * @param string $callBackMethod_value Method name of call back function in object for values
     * @return bool|string true on success, string if error happened (error string returned)
     */
    public function traverseFlexFormXMLData($table, $field, $row, $callBackObj, $callBackMethod_value)
    {
        if (!is_array($GLOBALS['TCA'][$table]) || !is_array($GLOBALS['TCA'][$table]['columns'][$field])) {
            return 'TCA table/field was not defined.';
        }
        $this->callBackObj = $callBackObj;

        // Get data structure. The methods may throw various exceptions, with some of them being
        // ok in certain scenarios, for instance on new record rows. Those are ok to "eat" here
        // and substitute with a dummy DS.
        $dataStructureArray = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'el' => [],
                    ],
                ],
            ],
        ];
        try {
            $dataStructureIdentifier = $this->getDataStructureIdentifier($GLOBALS['TCA'][$table]['columns'][$field], $table, $field, $row);
            $dataStructureArray = $this->parseDataStructureByIdentifier($dataStructureIdentifier);
        } catch (InvalidParentRowException $e) {
        } catch (InvalidParentRowLoopException $e) {
        } catch (InvalidParentRowRootException $e) {
        } catch (InvalidPointerFieldValueException $e) {
        } catch (InvalidIdentifierException $e) {
        }

        // Get flexform XML data
        $editData = GeneralUtility::xml2array($row[$field]);
        if (!is_array($editData)) {
            return 'Parsing error: ' . $editData;
        }
        // Check if $dataStructureArray['sheets'] is indeed an array before loop or it will crash with runtime error
        if (!is_array($dataStructureArray['sheets'])) {
            return 'Data Structure ERROR: sheets is defined but not an array for table ' . $table . (isset($row['uid']) ? ' and uid ' . $row['uid'] : '');
        }

        // Language settings:
        $langChildren = 0;
        $langDisabled = 0;
        if (isset($dataStructArray['meta'])) {
            $langChildren = $dataStructArray['meta']['langChildren'] ? 1 : 0;
            $langDisabled = $dataStructArray['meta']['langDisable'] ? 1 : 0;
        }

        // Empty or invalid <meta>
        if (!isset($editData['meta']) || !is_array($editData['meta'])) {
            $editData['meta'] = [];
        }
        $editData['meta']['currentLangId'] = [];
        $languages = $this->getAvailableLanguages();
        foreach ($languages as $lInfo) {
            $editData['meta']['currentLangId'][] = $lInfo['ISOcode'];
        }
        $editData['meta']['currentLangId'] = array_unique($editData['meta']['currentLangId']);
        if ($langChildren || $langDisabled) {
            $lKeys = ['DEF'];
        } else {
            $lKeys = $editData['meta']['currentLangId'];
        }

        // Traverse languages:
        foreach ($dataStructureArray['sheets'] as $sheetKey => $sheetData) {
            // Render sheet:
            if (is_array($sheetData['ROOT']) && is_array($sheetData['ROOT']['el'])) {
                $lang = 'l' . $lKey;

                $PA['vKeys'] = $langChildren && !$langDisabled ? $editData['meta']['currentLangId'] : ['DEF'];
                $PA['lKey'] = $lang;
                $PA['callBackMethod_value'] = $callBackMethod_value;
                $PA['table'] = $table;
                $PA['field'] = $field;
                $PA['uid'] = $row['uid'];
                // Render flexform:
                $this->traverseFlexFormXMLData_recurse($sheetData['ROOT']['el'], $editData['data'][$sheetKey][$lang], $PA, 'data/' . $sheetKey . '/' . $lang);
            } else {
                return 'Data Structure ERROR: No ROOT element found for sheet "' . $sheetKey . '".';
            }
        }
        return true;
    }
}
