<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004-2006 Robert Lemke (robert@typo3.org)
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

	// Include TemplaVoila API:
require_once (t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_api.php');

/**
 * Class being included by TCEmain using a hook
 *
 * @author	Robert Lemke <robert@typo3.org>
 * @package TYPO3
 * @subpackage templavoila
 */
class tx_templavoila_tcemain {

	var $debug = FALSE;

	/**
	 * Extension configuration
	 *
	 * @var	array
	 */
	protected $extConf = array();

	/**
	 * Creates an instance of this class
	 *
	 * @return	void
	 */
	public function __construct() {
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);
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
	 * @param	array		$incomingFieldArray: The original field names and their values before they are processed
	 * @param	string		$table: The table TCEmain is currently processing
	 * @param	string		$id: The records id (if any)
	 * @param	t3lib_TCEmain	$reference: Reference to the parent object (TCEmain)
	 * @return	void
	 * @access	public
	 */
	function processDatamap_preProcessFieldArray(array &$incomingFieldArray, $table, $id, t3lib_TCEmain &$reference) {
		global $TYPO3_DB, $TCA;

		if ($this->debug) {
			t3lib_div::devLog ('processDatamap_preProcessFieldArray', 'templavoila',0,array ($incomingFieldArray, $table, $id));
		}

		if ($GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_api']['apiIsRunningTCEmain']) {
			return;
		}

		if (!$this->extConf['enable.']['selectDataStructure']) {
			// Update DS if TO was changed
			$this->updateDataSourceFromTemplateObject($table, $incomingFieldArray, $reference->BE_USER);
		}

		if ($table == 'tt_content') {
			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_tcemain']['preProcessFieldArrays'][$id] = $incomingFieldArray;
		}
	}

	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain).
	 *
	 * If a record from table "pages" is created or updated with a new DS but no TO is selected, this function
	 * tries to find a suitable TO and adds it to the fieldArray.
	 *
	 * @param	string		$status: The TCEmain operation status, fx. 'update'
	 * @param	string		$table: The table TCEmain is currently processing
	 * @param	string		$id: The records id (if any)
	 * @param	array		$fieldArray: The field names and their values to be processed
	 * @param	object		$reference: Reference to the parent object (TCEmain)
	 * @return	void
	 * @access	public
	 */
	function processDatamap_postProcessFieldArray ($status, $table, $id, &$fieldArray, &$reference) {
		if ($this->debug) t3lib_div::devLog ('processDatamap_postProcessFieldArray', 'templavoila',0,array ($status, $table, $id, $fieldArray));

			// If the references for content element changed at the current page, save that information into the reference table:
		if ($status == 'update' && $table == 'pages' && isset ($fieldArray['tx_templavoila_flex'])) {

			$this->correctSortingAndColposFieldsForPage($fieldArray['tx_templavoila_flex'], $id);

				// If a new data structure has been selected, set a valid template object automatically:
			if (intval ($fieldArray['tx_templavoila_ds']) || intval($fieldArray['tx_templavoila_next_ds'])) {

					// Determine the page uid which ds_getAvailablePageTORecords() can use for finding the storage folder:
				$pid = NULL;
				if ($status == 'update') {
					$pid = $id;
				} elseif ($status == 'new' && intval($fieldArray['storage_pid'] == 0)) {
					$pid = $fieldArray['pid'];
				}

				if (!is_null($pid)) {
					$templaVoilaAPI = t3lib_div::makeInstance('tx_templavoila_api');
					$templateObjectRecords = $templaVoilaAPI->ds_getAvailablePageTORecords ($pid);

					if (is_array ($templateObjectRecords)) {
						foreach ($templateObjectRecords as $templateObjectRecord) {
							if (!isset ($matchingTOUid) && $templateObjectRecord['datastructure'] == $fieldArray['tx_templavoila_ds']) {
								$matchingTOUid = $templateObjectRecord['uid'];
							}
							if (!isset ($matchingNextTOUid) && $templateObjectRecord['datastructure'] == $fieldArray['tx_templavoila_next_ds']) {
								$matchingNextTOUid = $templateObjectRecord['uid'];
							}
						}
							// Finally set the Template Objects if one was found:
						if (intval ($fieldArray['tx_templavoila_ds']) && ($fieldArray['tx_templavoila_to'] == 0)) {
							$fieldArray['tx_templavoila_to'] = $matchingTOUid;
						}
						if (intval ($fieldArray['tx_templavoila_next_ds']) && ($fieldArray['tx_templavoila_next_to'] == 0)) {
							$fieldArray['tx_templavoila_next_to'] = $matchingNextTOUid;
						}
					}
				}
			}
		}

			// Access check for FCE
		if ($table == 'tt_content') {
			if ($status != 'new') {
				$row = t3lib_beFunc::getRecord($table, $id);
			}
			else {
				$row = &$fieldArray;
			}
			if ($row['CType'] == 'templavoila_pi1') {
				$params = array(
					'table' => $table,
					'row' => $row,
				);
				$ref = null;
				if (!t3lib_div::callUserFunction('EXT:templavoila/class.tx_templavoila_access.php:&tx_templavoila_access->recordEditAccessInternals', $params, $ref)) {
					$reference->newlog(sprintf($GLOBALS['LANG']->getLL($status != 'new' ? 'access_noModifyAccess' : 'access_noCrateAccess'), $table, $id), 1);
					$fieldArray = null;
				}
			}
		} else if ($table == 'sys_template' && $status == 'new') {
			if (isset($fieldArray['root']) && $fieldArray['root'] === 1 && isset($fieldArray['clear']) && $fieldArray['clear'] === 3) {
				$fieldArray['config'] = '
page = PAGE
page.10 = USER
page.10.userFunc = tx_templavoila_pi1->main_page
page.10.disableExplosivePreview = 1
				';
			}
		}
	}

	/**
	 * This function is called by TCEmain after a new record has been inserted into the database.
	 * If a new content element has been created, we make sure that it is referenced by its page.
	 *
	 * @param	string		$status: The command which has been sent to processDatamap
	 * @param	string		$table:	The table we're dealing with
	 * @param	mixed		$id: Either the record UID or a string if a new record has been created
	 * @param	array		$fieldArray: The record row how it has been inserted into the database
	 * @param	object		$reference: A reference to the TCEmain instance
	 * @return	void
	 * @access	public
	 */
	function processDatamap_afterDatabaseOperations ($status, $table, $id, $fieldArray, &$reference) {

		if ($this->debug) t3lib_div::devLog ('processDatamap_afterDatabaseOperations ', 'templavoila',0,array ($status, $table,$id,$fieldArray));
		if ($GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_api']['apiIsRunningTCEmain']) return;
		if ($table != 'tt_content') return;

		$templaVoilaAPI = t3lib_div::makeInstance('tx_templavoila_api');

		switch ($status) {
			case 'new' :
				if (!isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_tcemain']['doNotInsertElementRefsToPage'])) {

					t3lib_BEfunc::fixVersioningPid($table, $fieldArray);

					if (isset ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_tcemain']['preProcessFieldArrays'][$id])) {
						$positionReferenceUid = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_tcemain']['preProcessFieldArrays'][$id]['pid'];
						if ($positionReferenceUid < 0) {
							$neighbourFlexformPointersArr = $templaVoilaAPI->flexform_getPointersByRecord (abs($positionReferenceUid ), $fieldArray['pid']);
							$neighbourFlexformPointer = $neighbourFlexformPointersArr[0];

							if (is_array ($neighbourFlexformPointer)) {
								$destinationFlexformPointer = $neighbourFlexformPointer;
							}
						}
					}

					if (!is_array ($destinationFlexformPointer)) {
						$mainContentAreaFieldName = $templaVoilaAPI->ds_getFieldNameByColumnPosition($fieldArray['pid'], intval($fieldArray['colPos']));
						if ($mainContentAreaFieldName !== FALSE) {
							$sorting_field = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
							$sorting = (!$sorting_field ? 0 : ($fieldArray[$sorting_field] ? -$fieldArray[$sorting_field] : 0));

							$destinationFlexformPointer = array (
								'table' => 'pages',
								'uid' => $fieldArray['pid'],
								'sheet' => 'sDEF',
								'sLang' => 'lDEF',
								'field' => $mainContentAreaFieldName,
								'vLang' => 'vDEF',
								'position' => 0
							);

							if ($sorting < 0) {
								$parentRecord = t3lib_BEfunc::getRecordWSOL($destinationFlexformPointer['table'], $destinationFlexformPointer['uid'],'uid,pid,tx_templavoila_flex');
								$currentReferencesArr = $templaVoilaAPI->flexform_getElementReferencesFromXML($parentRecord['tx_templavoila_flex'], $destinationFlexformPointer);
								if (count($currentReferencesArr)) {
									$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,' . $sorting_field, $table, 'uid IN (' . implode(',', $currentReferencesArr) . ')' . t3lib_BEfunc::deleteClause($table));
									$sort = array($reference->substNEWwithIDs[$id] => -$sorting);
									foreach ($rows as $row) {
										$sort[$row['uid']] = $row[$sorting_field];
									}
									asort($sort, SORT_NUMERIC);
									$destinationFlexformPointer['position'] = array_search($reference->substNEWwithIDs[$id], array_keys($sort));
								}
							}
						}
					}
					if (is_array ($destinationFlexformPointer)) {
						$templaVoilaAPI->insertElement_setElementReferences ($destinationFlexformPointer, $reference->substNEWwithIDs[$id]);
					}
				}
				break;
		}

			// clearing the cache of all related pages - see #1332
		if (method_exists($reference, 'clear_cacheCmd')) {
			$element = array(
				'table' => $table,
				'uid' => $id
			);
			$references = tx_templavoila_div::getElementForeignReferences($element, $fieldArray['pid']);
			if (is_array($references) && is_array($references['pages'])) {
				foreach($references['pages'] as $pageUid=>$__) {
					$reference->clear_cacheCmd($pageUid);
				}
			}
		}

		unset ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_tcemain']['preProcessFieldArrays']);
	}

	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain).
	 *
	 * @param	string		$status: The TCEmain operation status, fx. 'update'
	 * @param	string		$table: The table TCEmain is currently processing
	 * @param	string		$id: The records id (if any)
	 * @param	array		$fieldArray: The field names and their values to be processed
	 * @param	object		$reference: Reference to the parent object (TCEmain)
	 * @return	void
	 * @access	public
	 * @todo	"delete" should search for all references to the element.
	 */
	function processCmdmap_preProcess (&$command, $table, $id, $value, &$reference) {

		if ($this->debug) t3lib_div::devLog('processCmdmap_preProcess', 'templavoila', 0, array ($command, $table, $id, $value));
		if ($GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_api']['apiIsRunningTCEmain']) return;
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_tcemain']['doNotInsertElementRefsToPage'])) {
			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_tcemain']['doNotInsertElementRefsToPage']++;
		}
		else {
			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_tcemain']['doNotInsertElementRefsToPage'] = 1;
		}

		if ($table != 'tt_content') return;

		$templaVoilaAPI = t3lib_div::makeInstance('tx_templavoila_api');

		switch ($command) {
			case 'delete' :
				$record = t3lib_beFunc::getRecord('tt_content', $id);
					// Check for FCE access
				$params = array(
					'table' => $table,
					'row' => $record,
				);
				$ref = null;
				if (!t3lib_div::callUserFunction('EXT:templavoila/class.tx_templavoila_access.php:&tx_templavoila_access->recordEditAccessInternals', $params, $ref)) {
					$reference->newlog(sprintf($GLOBALS['LANG']->getLL($status != 'new' ? 'access_noModifyAccess' : 'access_noCrateAccess'), $table, $id), 1);
					$command = '';	// Do not delete! A hack but there is no other way to prevent deletion...
				}
				else {
					if (intval($record['t3ver_oid']) > 0 && $record['pid'] == -1) {
							// we unlink a offline version in a workspace
						if (abs($record['t3ver_wsid']) !== 0) {
							$record = t3lib_BEfunc::getRecord('tt_content', intval($record['t3ver_oid']));
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
			case 'copy':
				unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_tcemain']['doNotInsertElementRefsToPage']);
				break;
		}
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_tcemain']['doNotInsertElementRefsToPage'])) {
			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_tcemain']['doNotInsertElementRefsToPage']--;
		}
	}

	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain).
	 *
	 * @param	string		$status: The TCEmain operation status, fx. 'update'
	 * @param	string		$table: The table TCEmain is currently processing
	 * @param	string		$id: The records id (if any)
	 * @param	array		$fieldArray: The field names and their values to be processed
	 * @param	object		$reference: Reference to the parent object (TCEmain)
	 * @return	void
	 * @access	public
	 */
	function processCmdmap_postProcess($command, $table, $id, $value, &$reference) {

		if ($this->debug) t3lib_div::devLog ('processCmdmap_postProcess', 'templavoila', 0, array ($command, $table, $id, $value));

		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_tcemain']['doNotInsertElementRefsToPage'])) {
			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_tcemain']['doNotInsertElementRefsToPage']--;
			if ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_tcemain']['doNotInsertElementRefsToPage'] == 0) {
				unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_tcemain']['doNotInsertElementRefsToPage']);
			}
		}

	}

	/**
	 * This function is called by TCEmain after a record has been moved to the first position of
	 * the page. We make sure that this is also reflected in the pages references.
	 *
	 * @param	string		$table:	The table we're dealing with
	 * @param	integer		$uid: The record UID
	 * @param	integer		$destPid: The page UID of the page the element has been moved to
	 * @param	array		$sourceRecordBeforeMove: (A part of) the record before it has been moved (and thus the PID has possibly been changed)
	 * @param	array		$updateFields: The updated fields of the record row in question (we don't use that)
	 * @param	object		$reference: A reference to the TCEmain instance
	 * @return	void
	 * @access	public
	 */
	function moveRecord_firstElementPostProcess ($table, $uid, $destPid, $sourceRecordBeforeMove, $updateFields, &$reference) {
		global $TCA;

		if ($this->debug) t3lib_div::devLog ('moveRecord_firstElementPostProcess', 'templavoila', 0, array ($table, $uid, $destPid, $sourceRecordBeforeMove, $updateFields));
		if ($GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_api']['apiIsRunningTCEmain']) return;
		if ($table != 'tt_content') return;

		$templaVoilaAPI = t3lib_div::makeInstance('tx_templavoila_api');

		$sourceFlexformPointersArr = $templaVoilaAPI->flexform_getPointersByRecord ($uid, $sourceRecordBeforeMove['pid']);
		$sourceFlexformPointer = $sourceFlexformPointersArr[0];

		$mainContentAreaFieldName = $templaVoilaAPI->ds_getFieldNameByColumnPosition($destPid, 0);
		if ($mainContentAreaFieldName !== FALSE) {
			$destinationFlexformPointer = array (
				'table' => 'pages',
				'uid' => $destPid,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => $mainContentAreaFieldName,
				'vLang' => 'vDEF',
				'position' => 0
			);
			$templaVoilaAPI->moveElement_setElementReferences ($sourceFlexformPointer, $destinationFlexformPointer);
		}
	}

	/**
	 * This function is called by TCEmain after a record has been moved to after another record on some
	 * the page. We make sure that this is also reflected in the pages references.
	 *
	 * @param	string		$table:	The table we're dealing with
	 * @param	integer		$uid: The record UID
	 * @param	integer		$destPid: The page UID of the page the element has been moved to
	 * @param	integer		$origDestPid: The "original" PID: This tells us more about after which record our record wants to be moved. So it's not a page uid but a tt_content uid!
	 * @param	array		$sourceRecordBeforeMove: (A part of) the record before it has been moved (and thus the PID has possibly been changed)
	 * @param	array		$updateFields: The updated fields of the record row in question (we don't use that)
	 * @param	object		$reference: A reference to the TCEmain instance
	 * @return	void
	 * @access	public
	 */
	function moveRecord_afterAnotherElementPostProcess ($table, $uid, $destPid, $origDestPid, $sourceRecordBeforeMove, $updateFields, &$reference) {

		if ($this->debug) t3lib_div::devLog ('moveRecord_afterAnotherElementPostProcess', 'templavoila', 0, array ($table, $uid, $destPid, $origDestPid, $sourceRecordBeforeMove, $updateFields));
		if ($GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_api']['apiIsRunningTCEmain']) return;
		if ($table != 'tt_content') return;

		$templaVoilaAPI = t3lib_div::makeInstance('tx_templavoila_api');

		$sourceFlexformPointersArr = $templaVoilaAPI->flexform_getPointersByRecord ($uid, $sourceRecordBeforeMove['pid']);
		$sourceFlexformPointer = $sourceFlexformPointersArr[0];

		$neighbourFlexformPointersArr = $templaVoilaAPI->flexform_getPointersByRecord (abs($origDestPid), $destPid);
		$neighbourFlexformPointer = $neighbourFlexformPointersArr[0];

			// One-line-fix for frontend editing (see Bug #2154).
			// NOTE: This fix leads to unwanted behaviour in one special and unrealistic situation: If you move the second
			// element to after the first element, it will move to the very first position instead of staying where it is.
		if ($neighbourFlexformPointer['position'] == 1 && $sourceFlexformPointer['position'] == 2) $neighbourFlexformPointer['position'] = 0;

		$templaVoilaAPI->moveElement_setElementReferences ($sourceFlexformPointer, $neighbourFlexformPointer);
	}

	/**
	 * Sets the sorting field of all tt_content elements found on the specified page
	 * so they reflect the order of the references.
	 *
	 * @param	string		$flexformXML: The flexform XML data of the page
	 * @param	integer		$pid: Current page id
	 * @return	void
	 * @access	protected
	 */
	function correctSortingAndColposFieldsForPage($flexformXML, $pid) {
		global $TCA, $TYPO3_DB;

		$elementsOnThisPage = array ();
		$templaVoilaAPI = t3lib_div::makeInstance('tx_templavoila_api');
		/* @var $templaVoilaAPI tx_templavoila_api */

		$diffBaseEnabled = isset($GLOBALS['TYPO3_CONF_VARS']['BE']['flexFormXMLincludeDiffBase'])
							&& ($GLOBALS['TYPO3_CONF_VARS']['BE']['flexFormXMLincludeDiffBase'] != false);

			// Getting value of the field containing the relations:
		$xmlContentArr = t3lib_div::xml2array($flexformXML);

			// And extract all content element uids and their context from the XML structure:
		if (is_array ($xmlContentArr['data'])) {
			foreach ($xmlContentArr['data'] as $currentSheet => $subArr) {
				if (is_array ($subArr)) {
					foreach ($subArr as $currentLanguage => $subSubArr) {
						if (is_array ($subSubArr)) {
							foreach ($subSubArr as $currentField => $subSubSubArr) {
								if (is_array ($subSubSubArr)) {
									foreach ($subSubSubArr as $currentValueKey => $uidList) {

										if($diffBaseEnabled && preg_match('/\.vDEFbase$/', $currentValueKey)) {
											continue;
										}

										$uidsArr = t3lib_div::trimExplode (',', $uidList);
										if (is_array ($uidsArr)) {
											foreach ($uidsArr as $uid) {
												if (intval($uid)) {
													$elementsOnThisPage[] = array (
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

		$elementCounter = 1;
		$sortNumber = 100;

		$sortByField = $TCA['tt_content']['ctrl']['sortby'];
		if ($sortByField) {
			foreach ($elementsOnThisPage as $elementArr) {
				$colPos = $templaVoilaAPI->ds_getColumnPositionByFieldName($pid, $elementArr['field']);
				$updateFields = array(
					$sortByField => $sortNumber,
					'colPos' => $colPos
				);
				$TYPO3_DB->exec_UPDATEquery (
					'tt_content',
					'uid='.intval($elementArr['uid']),
					$updateFields
				);
				$sortNumber += 100;
			}
		}
	}

	/**
	 * Checks if template object was changed (== exists in the $incomingFieldArray)
	 * and sets data source accordingly.
	 *
	 * @param	string	$table	Table name
	 * @param	string	$incomingFieldArray	Array with fields
	 * @param	t3lib_beUserAuth	$beUser	Current backend user for this operation
	 * @return	void
	 */
	protected function updateDataSourceFromTemplateObject($table, array &$incomingFieldArray, t3lib_beUserAuth &$beUser) {
		if (($table == 'pages' || $table == 'tt_content') &&
		isset($incomingFieldArray['tx_templavoila_to'])) {
			$this->updateDataSourceFieldFromTemplateObjectField($incomingFieldArray, 'tx_templavoila_ds', 'tx_templavoila_to', $beUser);
		}
		if ($table == 'pages' && isset($incomingFieldArray['tx_templavoila_next_to'])) {
			$this->updateDataSourceFieldFromTemplateObjectField($incomingFieldArray, 'tx_templavoila_next_ds', 'tx_templavoila_next_to', $beUser);
		}
	}

	/**
	 * Finds data source value for the current template object and sets it to the
	 * $incomingFieldArray.
	 *
	 * @param	array	$incomingFieldArray	Array with fields
	 * @param	string	$dsField	Data source field name in the $incomingFieldArray
	 * @param	string	$toField	Template object field name in the $incomingFieldArray
	 * @param	t3lib_beUserAuth	$beUser	Current backend user for this operation
	 * @return	void
	 */
	protected function updateDataSourceFieldFromTemplateObjectField(array &$incomingFieldArray, $dsField, $toField, t3lib_beUserAuth &$beUser) {
		$toId = $incomingFieldArray[$toField];
		if (intval($toId) == 0) {
			$incomingFieldArray[$dsField] = '';
		}
		else {
			if ($beUser->workspace) {
				$record = t3lib_BEfunc::getWorkspaceVersionOfRecord($beUser->workspace, 'tx_templavoila_tmplobj', $toId, 'datastructure');
				if (!is_array($record)) {
					$record = t3lib_BEfunc::getRecord('tx_templavoila_tmplobj', $toId, 'datastructure');
				}
			}
			else {
				$record = t3lib_BEfunc::getRecord('tx_templavoila_tmplobj', $toId, 'datastructure');
			}
			if (is_array($record) && isset($record['datastructure'])) {
				$incomingFieldArray[$dsField] = $record['datastructure'];
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
	 * @return mixed - "1" if we grant access and "false" if we can't decide whether to give access or not
	 */
	function checkRecordUpdateAccess($table, $id, $data, $res, &$pObj)	{

		global $TCA;
			// Only perform additional checks if not admin and just for pages table.
		if (($table=='pages') && is_array($data) && !$pObj->admin)	{
			$res = 1;
			foreach ($data as $field => $value)	{
				if (in_array($table.'-'.$field, $pObj->exclude_array) || $pObj->data_disableFields[$table][$id][$field])	{
					continue;
				}
					// we're not inserting useful data - can't make a decission
				if(!is_array($data[$field]['data'])) {
					$res = false;
					break;
				}
					// we're not inserting operating on an flex field - can't make a decission
				if (!is_array($TCA[$table]['columns'][$field]['config']) ||
					$TCA[$table]['columns'][$field]['config']['type'] != 'flex') {
					$res = false;
					break;
				}
					// get the field-information and check if only "ce" fields are updated
				$conf = $TCA[$table]['columns'][$field]['config'];
				$currentRecord = t3lib_BEfunc::getRecord($table, $id);
				$dataStructArray = t3lib_BEfunc::getFlexFormDS($conf, $currentRecord, $table, $field, true);
				foreach ($data[$field]['data'] as $sheet => $sheetData)	{
					if (!is_array($sheetData) || !is_array($dataStructArray['ROOT']['el']))	{
						$res = false;
						break;
					}
					foreach ($sheetData as $lDef => $lData)	{
						if (!is_array($lData))	{
							$res = false;
							break;
						}
						foreach ($lData as $fieldName => $fieldData)	{

							if (!isset($dataStructArray['ROOT']['el'][$fieldName])) {
								$res = false;
								break;
							}

							$fieldConf = $dataStructArray['ROOT']['el'][$fieldName];
							if ($fieldConf['tx_templavoila']['eType'] != 'ce')	{
								$res = false;
								break;
							}
						}
					}
				}
			}
			if (($res==1) && !$pObj->doesRecordExist($table, $id, 'editcontent'))	{
				$res = false;
			}
		}

		return $res;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_tcemain.php'])    {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_tcemain.php']);
}

?>