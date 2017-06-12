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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains functions for manipulating flex form data
 */
class FlexFormTools extends \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools
{
    /**
     * Handler for Flex Forms
     *
     * @param string $table The table name of the record
     * @param string $field The field name of the flexform field to work on
     * @param array $row The record data array
     * @param object $callBackObj Object in which the call back function is located
     * @param string $callBackMethod_value Method name of call back function in object for values
     * @return bool|string If TRUE, error happened (error string returned)
     */
    public function traverseFlexFormXMLData($table, $field, $row, $callBackObj, $callBackMethod_value)
    {
        if (!is_array($GLOBALS['TCA'][$table]) || !is_array($GLOBALS['TCA'][$table]['columns'][$field])) {
            return 'TCA table/field was not defined.';
        }
        $this->callBackObj = $callBackObj;
        // Get Data Structure:
        $dataStructArray = BackendUtility::getFlexFormDS($GLOBALS['TCA'][$table]['columns'][$field]['config'], $row, $table, $field);
        // If data structure was ok, proceed:
        if (is_array($dataStructArray)) {
            // Get flexform XML data:
            $xmlData = $row[$field];
            if ($xmlData === null) {
                // No data, no traversal
                return;
            }

            // Convert charset:
            if ($this->convertCharset) {
                $xmlHeaderAttributes = GeneralUtility::xmlGetHeaderAttribs($xmlData);
                $storeInCharset = strtolower($xmlHeaderAttributes['encoding']);
                if ($storeInCharset) {
                    $currentCharset = $GLOBALS['LANG']->charSet;
                    $xmlData = $GLOBALS['LANG']->csConvObj->conv($xmlData, $storeInCharset, $currentCharset, 1);
                }
            }

            $editData = GeneralUtility::xml2array($xmlData);
            if (!is_array($editData)) {
                return 'Parsing error: ' . $editData;
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
            if (empty($editData['meta']['currentLangId'])) {
                $editData['meta']['currentLangId'] = array('DEF');
            }
            $editData['meta']['currentLangId'] = array_unique($editData['meta']['currentLangId']);
            if ($langChildren || $langDisabled) {
                $lKeys = array('DEF');
            } else {
                $lKeys = $editData['meta']['currentLangId'];
            }

            // Tabs sheets
            if (is_array($dataStructArray['sheets'])) {
                $sKeys = array_keys($dataStructArray['sheets']);
            } else {
                $sKeys = array('sDEF');
            }
            // Traverse languages:
            foreach ($lKeys as $lKey) {
                foreach ($sKeys as $sheet) {
                    $sheetCfg = $dataStructArray['sheets'][$sheet];
                    list($dataStruct, $sheet) = GeneralUtility::resolveSheetDefInDS($dataStructArray, $sheet);
                    // Render sheet:
                    if (is_array($dataStruct['ROOT']) && is_array($dataStruct['ROOT']['el'])) {
                        // Separate language key
                        $lang = 'l' . $lKey;
                        $PA['vKeys'] = $langChildren && !$langDisabled ? $editData['meta']['currentLangId'] : array('DEF');
                        $PA['lKey'] = $lang;
                        $PA['callBackMethod_value'] = $callBackMethod_value;
                        $PA['table'] = $table;
                        $PA['field'] = $field;
                        $PA['uid'] = $row['uid'];
                        $this->traverseFlexFormXMLData_DS = &$dataStruct;
                        $this->traverseFlexFormXMLData_Data = &$editData;
                        // Render flexform:
                        $this->traverseFlexFormXMLData_recurse($dataStruct['ROOT']['el'], $editData['data'][$sheet][$lang], $PA, 'data/' . $sheet . '/' . $lang);
                    } else {
                        return 'Data Structure ERROR: No ROOT element found for sheet "' . $sheet . '".';
                    }
                }
            }
        } else {
            return 'Data Structure ERROR: ' . $dataStructArray;
        }
    }

    /**
     * Returns an array of available languages to use for FlexForm operations
     *
     * @return array
     */
    public function getAvailableLanguages()
    {
        $isL = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('static_info_tables');
        // Find all language records in the system
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'language_isocode,static_lang_isocode,title,uid',
            'sys_language',
            'pid=0' . BackendUtility::deleteClause('sys_language'),
            '',
            'title'
        );
        // Traverse them
        $output = [];
        $output[0] = [
            'uid' => 0,
            'title' => 'Default language',
            'ISOcode' => 'DEF'
        ];
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $output[$row['uid']] = $row;
            if (!empty($row['language_isocode'])) {
                $output[$row['uid']]['ISOcode'] = $row['language_isocode'];
            } elseif ($isL && $row['static_lang_isocode']) {
                GeneralUtility::deprecationLog('Usage of the field "static_lang_isocode" is discouraged, and will stop working with CMS 8. Use the built-in language field "language_isocode" in your sys_language records.');
                $rr = BackendUtility::getRecord('static_languages', $row['static_lang_isocode'], 'lg_iso_2');
                if ($rr['lg_iso_2']) {
                    $output[$row['uid']]['ISOcode'] = $rr['lg_iso_2'];
                }
            }
            if (!$output[$row['uid']]['ISOcode']) {
                unset($output[$row['uid']]);
            }
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        return $output;
    }

    /***********************************
     *
     * Processing functions
     *
     ***********************************/
    /**
     * Call back function for \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools class
     * Basically just setting the value in a new array (thus cleaning because only values that are valid are visited!)
     *
     * @param array $dsArr Data structure for the current value
     * @param mixed $data Current value
     * @param array $PA Additional configuration used in calling function
     * @param string $path Path of value in DS structure
     * @param FlexFormTools $pObj caller
     * @return void
     */
    public function cleanFlexFormXML_callBackFunction($dsArr, $data, $PA, $path, $pObj)
    {
        // Just setting value in our own result array, basically replicating the structure:
        $pObj->setArrayValueByPath($path, $this->cleanFlexFormXML, $data);
        // Looking if an "extension" called ".vDEFbase" is found and if so, accept that too:
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['flexFormXMLincludeDiffBase']) {
            $vDEFbase = $pObj->getArrayValueByPath($path . '.vDEFbase', $pObj->traverseFlexFormXMLData_Data);
            if (isset($vDEFbase)) {
                $pObj->setArrayValueByPath($path . '.vDEFbase', $this->cleanFlexFormXML, $vDEFbase);
            }
        }
    }
}
