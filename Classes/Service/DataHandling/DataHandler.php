<?php

namespace Tvp\TemplaVoilaPlus\Service\DataHandling;

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
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Class being included by TCEmain using a hook
 *
 * @author Robert Lemke <robert@typo3.org>
 * @package TYPO3
 * @subpackage templavoila
 */
class DataHandler
{

    /**
     * @var boolean
     */
    public $debug = false;

    /**
     * @return \Tvp\TemplaVoilaPlus\Service\DataHandling\DataHandler
     */
    public function __construct()
    {
    }

    /********************************************
     *
     * Public API (called by hook handler)
     *
     ********************************************/

    /**
     * This method is called by a hook in the TYPO3 Core Engine (TCEmain). If a tt_content record is
     * going to be processed, this function saves the "incomingFieldArray" for later use in some
     * post processing functions (see other functions below).
     *
     * @param array $incomingFieldArray The original field names and their values before they are processed
     * @param string $table The table TCEmain is currently processing
     * @param string $id The records id (if any)
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $reference Reference to the parent object (TCEmain)
     *
     * @return void
     */
    public function processDatamap_preProcessFieldArray(array &$incomingFieldArray, $table, $id, \TYPO3\CMS\Core\DataHandling\DataHandler &$reference)
    {
        if ($this->debug) {
            GeneralUtility::devLog('processDatamap_preProcessFieldArray', 'templavoilaplus', 0, array($incomingFieldArray, $table, $id));
        }

        if ($GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoilaplus_api']['apiIsRunningTCEmain']) {
            return;
        }

        // Update DS if TO was changed
        $this->updateDataSourceFromTemplateObject($table, $incomingFieldArray, $reference->BE_USER);

        if ($table == 'tt_content') {
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoilaplus_tcemain']['preProcessFieldArrays'][$id] = $incomingFieldArray;
        }
    }

    /**
     * This method is called by a hook in the TYPO3 Core Engine (TCEmain).
     *
     * @param string $command The TCEmain operation status, fx. 'update'
     * @param string $table The table TCEmain is currently processing
     * @param string $id The records id (if any)
     * @param array $value The field names and their values to be processed
     * @param object $reference Reference to the parent object (TCEmain)
     *
     * @return void
     * @todo "delete" should search for all references to the element.
     */
    public function processCmdmap_preProcess(&$command, $table, $id, $value, &$reference)
    {

        if ($this->debug) {
            GeneralUtility::devLog('processCmdmap_preProcess', 'templavoilaplus', 0, array($command, $table, $id, $value));
        }
        if ($GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoilaplus_api']['apiIsRunningTCEmain']) {
            return;
        }

        if ($table != 'tt_content') {
            return;
        }

        $templaVoilaAPI = GeneralUtility::makeInstance(\Tvp\TemplaVoilaPlus\Service\ApiService::class);

        switch ($command) {
            case 'delete':
                $record = BackendUtility::getRecord('tt_content', $id);
                // Check for FCE access
                $params = array(
                    'table' => $table,
                    'row' => $record,
                );
                if (
                    !GeneralUtility::callUserFunction(
                        \Tvp\TemplaVoilaPlus\Service\UserFunc\Access::class . '->recordEditAccessInternals',
                        $params,
                        $this
                    )
                ) {
                    $reference->newlog(sprintf($this->getLanguageService()->getLL('access_noModifyAccess'), $table, $id), 1);
                    $command = ''; // Do not delete! A hack but there is no other way to prevent deletion...
                } else {
                    if ((int)$record['t3ver_oid'] > 0 && $record['pid'] == -1) {
                        // we unlink a offline version in a workspace
                        if (abs($record['t3ver_wsid']) !== 0) {
                            $record = BackendUtility::getRecord('tt_content', (int)$record['t3ver_oid']);
                        }
                    }
                    // avoid that deleting offline version in the live workspace unlinks the online version - see #11359
                    if ($record['uid'] && $record['pid']) {
                        $sourceFlexformPointersArr = $templaVoilaAPI->flexform_getPointersByRecord($record['uid'], $record['pid']);
                        $sourceFlexformPointer = $sourceFlexformPointersArr[0];
                        $templaVoilaAPI->unlinkElement($sourceFlexformPointer);
                    }
                }
                break;
        }
    }

    /**
     * This function is called by TCEmain after a record has been moved to the first position of
     * the page. We make sure that this is also reflected in the pages references.
     *
     * @param string $table    The table we're dealing with
     * @param integer $uid The record UID
     * @param integer $destPid The page UID of the page the element has been moved to
     * @param array $sourceRecordBeforeMove (A part of) the record before it has been moved (and thus the PID has possibly been changed)
     * @param array $updateFields The updated fields of the record row in question (we don't use that)
     * @param object $reference A reference to the TCEmain instance
     *
     * @return void
     */
    public function moveRecord_firstElementPostProcess($table, $uid, $destPid, $sourceRecordBeforeMove, $updateFields, &$reference)
    {
        if ($this->debug) {
            GeneralUtility::devLog('moveRecord_firstElementPostProcess', 'templavoilaplus', 0, array($table, $uid, $destPid, $sourceRecordBeforeMove, $updateFields));
        }
        if ($GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoilaplus_api']['apiIsRunningTCEmain']) {
            return;
        }
        if ($table != 'tt_content') {
            return;
        }

        $templaVoilaAPI = GeneralUtility::makeInstance(\Tvp\TemplaVoilaPlus\Service\ApiService::class);

        $sourceFlexformPointersArr = $templaVoilaAPI->flexform_getPointersByRecord($uid, $sourceRecordBeforeMove['pid']);
        $sourceFlexformPointer = $sourceFlexformPointersArr[0];

        $mainContentAreaFieldName = $templaVoilaAPI->ds_getFieldNameByColumnPosition($destPid, 0);
        if ($mainContentAreaFieldName !== false) {
            $destinationFlexformPointer = array(
                'table' => 'pages',
                'uid' => $destPid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => $mainContentAreaFieldName,
                'vLang' => 'vDEF',
                'position' => 0
            );
            $templaVoilaAPI->moveElement_setElementReferences($sourceFlexformPointer, $destinationFlexformPointer);
        }
    }

    /**
     * This function is called by TCEmain after a record has been moved to after another record on some
     * the page. We make sure that this is also reflected in the pages references.
     *
     * @param string $table    The table we're dealing with
     * @param integer $uid The record UID
     * @param integer $destPid The page UID of the page the element has been moved to
     * @param integer $origDestPid The "original" PID: This tells us more about after which record our record wants to be moved. So it's not a page uid but a tt_content uid!
     * @param array $sourceRecordBeforeMove (A part of) the record before it has been moved (and thus the PID has possibly been changed)
     * @param array $updateFields The updated fields of the record row in question (we don't use that)
     * @param object $reference A reference to the TCEmain instance
     *
     * @return void
     */
    public function moveRecord_afterAnotherElementPostProcess($table, $uid, $destPid, $origDestPid, $sourceRecordBeforeMove, $updateFields, &$reference)
    {

        if ($this->debug) {
            GeneralUtility::devLog('moveRecord_afterAnotherElementPostProcess', 'templavoilaplus', 0, array($table, $uid, $destPid, $origDestPid, $sourceRecordBeforeMove, $updateFields));
        }
        if ($GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoilaplus_api']['apiIsRunningTCEmain']) {
            return;
        }
        if ($table != 'tt_content') {
            return;
        }

        $templaVoilaAPI = GeneralUtility::makeInstance(\Tvp\TemplaVoilaPlus\Service\ApiService::class);

        $sourceFlexformPointersArr = $templaVoilaAPI->flexform_getPointersByRecord($uid, $sourceRecordBeforeMove['pid']);
        $sourceFlexformPointer = $sourceFlexformPointersArr[0];

        $neighbourFlexformPointersArr = $templaVoilaAPI->flexform_getPointersByRecord(abs($origDestPid), $destPid);
        $neighbourFlexformPointer = $neighbourFlexformPointersArr[0];

        // One-line-fix for frontend editing (see Bug #2154).
        // NOTE: This fix leads to unwanted behaviour in one special and unrealistic situation: If you move the second
        // element to after the first element, it will move to the very first position instead of staying where it is.
        if ($neighbourFlexformPointer['position'] == 1 && $sourceFlexformPointer['position'] == 2) {
            $neighbourFlexformPointer['position'] = 0;
        }

        $templaVoilaAPI->moveElement_setElementReferences($sourceFlexformPointer, $neighbourFlexformPointer);
    }

    /**
     * Sets the sorting field of all tt_content elements found on the specified page
     * so they reflect the order of the references.
     *
     * @param string $flexformXML The flexform XML data of the page
     * @param integer $pid Current page id
     *
     * @return void
     * @access protected
     */
    public function correctSortingAndColposFieldsForPage($flexformXML, $pid)
    {
        global $TCA;

        $elementsOnThisPage = array();

        /** @var \Tvp\TemplaVoilaPlus\Service\ApiService */
        $templaVoilaAPI = GeneralUtility::makeInstance(\Tvp\TemplaVoilaPlus\Service\ApiService::class);

        $diffBaseEnabled = isset($GLOBALS['TYPO3_CONF_VARS']['BE']['flexFormXMLincludeDiffBase'])
            && ($GLOBALS['TYPO3_CONF_VARS']['BE']['flexFormXMLincludeDiffBase'] != false);

        // Getting value of the field containing the relations:
        $xmlContentArr = GeneralUtility::xml2array($flexformXML);

        // And extract all content element uids and their context from the XML structure:
        if (isset($xmlContentArr['data']) && is_array($xmlContentArr['data'])) {
            foreach ($xmlContentArr['data'] as $currentSheet => $subArr) {
                if (is_array($subArr)) {
                    foreach ($subArr as $currentLanguage => $subSubArr) {
                        if (is_array($subSubArr)) {
                            foreach ($subSubArr as $currentField => $subSubSubArr) {
                                if (is_array($subSubSubArr)) {
                                    foreach ($subSubSubArr as $currentValueKey => $uidList) {
                                        if ($diffBaseEnabled && preg_match('/\.vDEFbase$/', $currentValueKey)) {
                                            continue;
                                        }
                                        if (is_array($uidList)) {
                                            $uidsArr = $uidList;
                                        } else {
                                            $uidsArr = GeneralUtility::trimExplode(',', $uidList);
                                        }
                                        if (is_array($uidsArr)) {
                                            foreach ($uidsArr as $uid) {
                                                if ((int)$uid) {
                                                    $elementsOnThisPage[] = array(
                                                        'uid' => $uid,
                                                        'skey' => $currentSheet,
                                                        'lkey' => $currentLanguage,
                                                        'vkey' => $currentValueKey,
                                                        'field' => $currentField,
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
        }

        $sortNumber = 100;

        $sortByField = $TCA['tt_content']['ctrl']['sortby'];
        if ($sortByField) {
            /** @var \TYPO3\CMS\Core\Database\Connection */
            $connection = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
                ->getConnectionForTable('tt_content');
            foreach ($elementsOnThisPage as $elementArr) {
                $colPos = $templaVoilaAPI->ds_getColumnPositionByFieldName($pid, $elementArr['field']);
                $connection->update(
                    'tt_content',
                    [
                        $sortByField => $sortNumber,
                        'colPos' => $colPos
                    ],
                    [
                        'uid' => (int)$elementArr['uid']
                    ]
                );
                $sortNumber += 100;
            }
        }
    }

    /**
     * Checks if template object was changed (== exists in the $incomingFieldArray)
     * and sets data source accordingly.
     *
     * @param string $table Table name
     * @param array &$incomingFieldArray Array with fields
     * @param \TYPO3\CMS\Core\Authentication\BackendUserAuthentication &$beUser Current backend user for this operation
     *
     * @return void
     */
    protected function updateDataSourceFromTemplateObject($table, array &$incomingFieldArray, \TYPO3\CMS\Core\Authentication\BackendUserAuthentication &$beUser)
    {
        if (
            ($table == 'pages' || $table == 'tt_content') &&
            isset($incomingFieldArray['tx_templavoilaplus_to'])
        ) {
            $this->updateDataSourceFieldFromTemplateObjectField($incomingFieldArray, 'tx_templavoilaplus_ds', 'tx_templavoilaplus_to', $beUser);
        }
        if ($table == 'pages' && isset($incomingFieldArray['tx_templavoilaplus_next_to'])) {
            $this->updateDataSourceFieldFromTemplateObjectField($incomingFieldArray, 'tx_templavoilaplus_next_ds', 'tx_templavoilaplus_next_to', $beUser);
        }
    }

    /**
     * Finds data source value for the current template object and sets it to the
     * $incomingFieldArray.
     *
     * @param array $incomingFieldArray Array with fields
     * @param string $dsField Data source field name in the $incomingFieldArray
     * @param string $toField Template object field name in the $incomingFieldArray
     * @param \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $beUser Current backend user for this operation
     *
     * @return void
     */
    protected function updateDataSourceFieldFromTemplateObjectField(array &$incomingFieldArray, $dsField, $toField, \TYPO3\CMS\Core\Authentication\BackendUserAuthentication &$beUser)
    {
        $toId = $incomingFieldArray[$toField];
        if ((int)$toId == 0) {
            $incomingFieldArray[$dsField] = '';
        } else {
            if ($beUser->workspace) {
                $record = BackendUtility::getWorkspaceVersionOfRecord($beUser->workspace, 'tx_templavoilaplus_tmplobj', $toId, 'datastructure');
                if (!is_array($record)) {
                    $record = BackendUtility::getRecord('tx_templavoilaplus_tmplobj', $toId, 'datastructure');
                }
            } else {
                $record = BackendUtility::getRecord('tx_templavoilaplus_tmplobj', $toId, 'datastructure');
            }

            if (is_array($record) && isset($record['datastructure'])) {
                $incomingFieldArray[$dsField] = (!is_numeric($record['datastructure']) ? 'FILE:' : '') . $record['datastructure'];
            }
        }
    }

    /**
     * Using the checkRecordUpdateAccess hook to grant access to flexfields if the user
     * make the attempt to update a reference list within a flex field
     *
     * @see http://bugs.typo3.org/view.php?id=485
     *
     * @param string $table
     * @param integer $id
     * @param array $data
     * @param boolean $res
     * @param object $pObj
     *
     * @return mixed - "1" if we grant access and "false" if we can't decide whether to give access or not
     */
    public function checkRecordUpdateAccess($table, $id, $data, $res, &$pObj)
    {

        global $TCA;
        // Only perform additional checks if not admin and just for pages table.
        if (($table == 'pages') && is_array($data) && !$pObj->admin) {
            $res = 1;
            foreach ($data as $field => $value) {
                if (in_array($table . '-' . $field, $pObj->getExcludeListArray()) || $pObj->data_disableFields[$table][$id][$field]) {
                    continue;
                }
                // we're not inserting useful data - can't make a decission
                if (!is_array($data[$field]) || !is_array($data[$field]['data'])) {
                    $res = null;
                    break;
                }
                // we're not inserting operating on an flex field - can't make a decission
                if (
                    !is_array($TCA[$table]['columns'][$field]['config']) ||
                    $TCA[$table]['columns'][$field]['config']['type'] != 'flex'
                ) {
                    $res = null;
                    break;
                }
                // get the field-information and check if only "ce" fields are updated
                $conf = $TCA[$table]['columns'][$field]['config'];
                $currentRecord = BackendUtility::getRecord($table, $id);
                $dataStructArray = TemplaVoilaUtility::getFlexFormDS($conf, $currentRecord, $table, $field);
                foreach ($data[$field]['data'] as $sheetData) {
                    if (!is_array($sheetData) || !is_array($dataStructArray['ROOT']['el'])) {
                        $res = null;
                        break;
                    }
                    foreach ($sheetData as $lData) {
                        if (!is_array($lData)) {
                            $res = null;
                            break;
                        }
                        foreach ($lData as $fieldName => $fieldData) {
                            if (!isset($dataStructArray['ROOT']['el'][$fieldName])) {
                                $res = null;
                                break;
                            }

                            $fieldConf = $dataStructArray['ROOT']['el'][$fieldName];
                            if ($fieldConf['tx_templavoilaplus']['eType'] != 'ce') {
                                $res = null;
                                break;
                            }
                        }
                    }
                }
            }
            if (($res == 1) && !$pObj->doesRecordExist($table, $id, 'editcontent')) {
                $res = null;
            }
        }

        return $res;
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    public function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
