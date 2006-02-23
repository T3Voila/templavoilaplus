<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003, 2004, 2005  Kasper Skårhøj (kasper@typo3.com) / Robert Lemke (robert@typo3.org)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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
/**
 * Script contains handler class for FlexForm relations between content elements and host object
 *
 * NOTE: Usage of this class is deprecated. Use tx_templavoila_api instead!
 *       This class is not used anywhere within TemplaVoila anymore.
 *
 * @author		Kasper Skårhøj <kasperYYYY@typo3.com>
 * @coauthor	Robert Lemke <robert@typo3.org>
 * @deprecated	version - 1.0.0
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   83: class tx_templavoila_xmlrelhndl
 *  104:     function init($altRoot)
 *  121:     function insertRecord($destination, $row)
 *  146:     function pasteRecord($pasteCmd, $source, $destination)
 *  182:     function getRecord($location)
 *
 *              SECTION: Execute changes to the FlexForm structures
 *  212:     function _insertReference($itemArr, $refArr, $item)
 *  236:     function _moveReference($itemArray, $destRefArr, $sourceItemArray, $sourceRefArr, $item_table, $item_uid, $movePid)
 *  278:     function _removeReference($itemArray, $refArr)
 *  304:     function _changeReference($itemArray, $refArr, $newUid)
 *  329:     function _updateFlexFormRefList($refArr, $idListArr)
 *  350:     function _deleteContentElement($uid)
 *
 *              SECTION: Helper functions
 *  389:     function _insertReferenceInList($itemArray, $refArr, $item, $sourceRefArr=FALSE)
 *  437:     function _getCopyUid($itemAtPosition_uid, $pid)
 *  465:     function _getListOfSubElementsRecursively ($table, $uid, &$recordUids)
 *  511:     function _splitAndValidateReference($string)
 *  531:     function _getItemArrayFromXML($xmlString, $refArr)
 *
 * TOTAL FUNCTIONS: 15
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



 	// We need the TCE forms functions
require_once (PATH_t3lib.'class.t3lib_loaddbgroup.php');
require_once (PATH_t3lib.'class.t3lib_tcemain.php');

	// Include the new API:
require_once (t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_api.php');

/**
 * Handler class for FlexForm relations between content elements and host object
 *
 * @author		Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @coauthor	Robert Lemke <robert@typo3.org>
 * @package		TYPO3
 * @subpackage	tx_templavoila
 * @deprecated	version - 1.0.0
 */
class tx_templavoila_xmlrelhndl {

		// External, static:
	var $rootTable = 'pages';					// The table of the root level.
	var $flexFieldIndex = array(
		'tt_content' => 'tx_templavoila_flex',
		'pages' => 'tx_templavoila_flex',
	);

	var	$templavoilaAPIObj;




	/**
	 * Initialize, setting alternative root table and flex field if needed.
	 *
	 * @param	array		Alternative root
	 * @return	void
	 * @deprecated version - 1.0.0
	 */
	function init($altRoot)	{
		if ($altRoot['table'])	{
			$this->rootTable = $altRoot['table'];
			$this->flexFieldIndex[$this->rootTable] = $altRoot['field_flex'];
		}
		$this->templavoilaAPIObj = $altRoot['table'] ? new tx_templavoila_api ($altRoot['table']) : new tx_templavoila_api();
	}

	/**
	 * Creates a page content element (tt_content) and inserts reference in FlexForm field.
	 * NOTE: This function is deprecated. It calls the method "insertElement" of the newer TemplaVoila API.
	 *
	 * @param	string		$destination: Position reference of where to create new element. Syntax is: [table]:[uid]:[sheet]:[structure Language]:[FlexForm field name]:[value language]:[index of reference position in field value]
	 * @param	array		$row: Array of default field values for creating the content element record. 'pid' and 'uid' are overridden.
	 * @return	integer		uid of the created content element (if any)
	 * @deprecated version - 1.0.0
	 */
	function insertRecord($destination, $row)	{

		$tmpArr = explode (':', $destination);
		$destinationArr = array (
			'table' => $tmpArr[0],
			'uid' => $tmpArr[1],
			'sheet' => $tmpArr[2],
			'sLang' => $tmpArr[3],
			'field' => $tmpArr[4],
			'vLang' => $tmpArr[5],
			'position' => $tmpArr[6]
		);
		return $this->templavoilaAPIObj->insertElement ($destinationArr, $row);
	}

	/**
	 * Performs the processing part of pasting a record.
	 * NOTE: This function is deprecated. It calls the methods of the newer TemplaVoila API.
	 *
	 * @param	string		$pasteCmd: Kind of pasting: 'cut', 'copy', 'copyref', 'ref' or 'unlink'
	 * @param	string		$source: String defining the original record. [table]:[uid]:[sheet]:[structure Language]:[FlexForm field name]:[value language]:[index of reference position in field value]/[ref. table]:[ref. uid]. Example: 'pages:78:sDEF:lDEF:field_contentarea:vDEF:0/tt_content:60'. The field name in the table is implicitly 'tx_templavoila_flex'. The definition of the reference element after the slash MUST match the element pointed to by the reference index in the first part. This is a security measure.
	 * @param	string		$destination: Defines the destination where to paste the record (not used when unlinking of course). Syntax is the same as first part of 'source', defining a position in a FlexForm 'tx_templavoila_flex' field.
	 * @return	void
	 * @deprecated version - 1.0.0
	 */
	function pasteRecord($pasteCmd, $source, $destination)	{

			// Split the source definition into parts:
		list($sourceStr,$check,$isLocal,$currentPageId) = explode('/',$source);

		$destinationPointer = $this->templavoilaAPIObj->flexform_getPointerFromString ($destination);
		if ($sourceStr)	{
			$sourcePointer = $this->templavoilaAPIObj->flexform_getPointerFromString ($sourceStr);

			switch ($pasteCmd) {
				case 'copy' :		$this->templavoilaAPIObj->copyElement ($sourcePointer, $destinationPointer); break;
				case 'copyref':		$this->templavoilaAPIObj->copyElement ($sourcePointer, $destinationPointer, FALSE); break;
				case 'localcopy':	$this->templavoilaAPIObj->copyElement ($sourcePointer, $sourcePointer);
									$this->templavoilaAPIObj->unlinkElement ($sourcePointer); break;
				case 'cut':			$this->templavoilaAPIObj->moveElement ($sourcePointer, $destinationPointer); break;
				case 'ref':			$this->templavoilaAPIObj->referenceElement ($sourcePointer, $destinationPointer); break;
				case 'unlink':		$this->templavoilaAPIObj->unlinkElement ($sourcePointer); break;
				case 'delete':		$this->templavoilaAPIObj->deleteElement ($sourcePointer); break;
			}


		} elseif($check && $pasteCmd=='ref') {		// Insert a reference to a content element from "outside" - for example from the clipboard of non-used elements:

			list($table,$uid) = explode(':', $check);
			$this->templavoilaAPIObj->referenceElementByUid ($uid, $destinationPointer);
		}
	}

	/**
	 * Returns a tt_content record specified by a flexform pointer
	 * NOTE: This function is deprecated. It calls the methods of the newer TemplaVoila API.
	 *
	 * @param	string		$location: String defining the record. [table]:[uid]:[sheet]:[structure Language]:[FlexForm field name]:[value language]:[index of reference position in field value]/[ref. table]:[ref. uid]. Example: 'pages:78:sDEF:lDEF:field_contentarea:vDEF:0/tt_content:60'. The field name in the table is implicitly 'tx_templavoila_flex'. The definition of the reference element after the slash MUST match the element pointed to by the reference index in the first part. This is a security measure.
	 * @return	mixed		The record row or FALSE if not successful
	 * @deprecated version - 1.0.0
	 */
	function getRecord($location) {

			// Split the source definition into parts:
		list($locationStr, $check, $isLocal, $currentPageId) = explode('/', $location);
		$flexformPointer = $this->templavoilaAPIObj->flexform_getPointerFromString ($locationStr);

		return $this->templavoilaAPIObj->flexform_getRecordByPointer ($flexformPointer);
	}





	/***************************************
	 *
	 * Execute changes to the FlexForm structures
	 *
	 **************************************/

	/**
	 * Insert item reference into list.
	 * NOTE: This function is deprecated. Use the newer class tx_templavoila_api instead.
	 *
	 * @param	array		$itemArr: Array of items from a current reference (array of table/uid pairs) (see _getItemArrayFromXML())
	 * @param	array		$refArr: Reference to element in FlexForm structure, splitted into an array (see _splitAndValidateReference())
	 * @param	string		Item reference, typically 'tt_content_'.$uid
	 * @return	void
	 * @access	protected
	 * @deprecated version - 1.0.0
	 */
	function _insertReference($itemArr, $refArr, $item)	{
			// Get new list of IDs:
		$idList = $this->_insertReferenceInList($itemArr, $refArr, $item);

			// Set the data field:
		$this->_updateFlexFormRefList($refArr, $idList);

	}

	/**
	 * Moves a reference into from list to another position.
	 * NOTE: This function is deprecated. Use the newer class tx_templavoila_api instead.
	 *
	 * @param	array		Destination: Array of items from a current reference (array of table/uid pairs) (see _getItemArrayFromXML())
	 * @param	array		Destination: Reference to element in FlexForm structure, splitted into an array (see _splitAndValidateReference())
	 * @param	array		Source: Array of items from a current reference (array of table/uid pairs) (see _getItemArrayFromXML())
	 * @param	array		Source: Reference to element in FlexForm structure, splitted into an array (see _splitAndValidateReference())
	 * @param	string		Item table
	 * @param	integer		Item uid
	 * @param	integer		Move-pid (pos/neg) to which the record should be moved (if at all).
	 * @return	void
	 * @access	protected
	 * @deprecated version - 1.0.0
	 */
	function _moveReference($itemArray, $destRefArr, $sourceItemArray, $sourceRefArr, $item_table, $item_uid, $movePid)	{

			// Set this boolean if the cut operation goes on inside the SAME flexform XML field:
		$sameField = $destRefArr[0]==$sourceRefArr[0]
						&& $destRefArr[1]==$sourceRefArr[1]
						&& $destRefArr[2]==$sourceRefArr[2]
						&& $destRefArr[3]==$sourceRefArr[3]
						&& $destRefArr[4]==$sourceRefArr[4]
						&& $destRefArr[5]==$sourceRefArr[5];

			// Get new list of IDs + Set the data field:
		$idList_dest = $this->_insertReferenceInList($itemArray, $destRefArr, $item_table.'_'.$item_uid, $sameField ? $sourceRefArr : FALSE);
		$this->_updateFlexFormRefList($destRefArr, $idList_dest);

			// Remove old reference unless the ref was in the same field:
		if (!$sameField)	{
			$this->_removeReference($sourceItemArray, $sourceRefArr);
		}

			// If moving element, make sure the PID is changed as well so the element belongs to the page where it is moved to:
		if ($movePid)	{
			$cmdArray = array();
			$cmdArray['tt_content'][$item_uid]['move'] = $movePid;

				// Store:
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values = 0;
			$tce->start(array(),$cmdArray);
			$tce->process_cmdmap();
		}
	}

	/**
	 * Removes a reference from list
	 * NOTE: This function is deprecated. Use the newer class tx_templavoila_api instead.
	 *
	 * @param	array		Array of items from a current reference (array of table/uid pairs) (see _getItemArrayFromXML())
	 * @param	array		Reference to element in FlexForm structure, splitted into an array (see _splitAndValidateReference())
	 * @return	void
	 * @access	protected
	 * @deprecated version - 1.0.0
	 */
	function _removeReference($itemArray, $refArr)	{

			// Unset the reference:
		unset($itemArray[$refArr[6]-1]);

			// Create new list:
		$idList = array();
		foreach($itemArray as $idSet)	{
			$idList[] = $idSet['table'].'_'.$idSet['id'];
		}

			// Set the data field:
		$this->_updateFlexFormRefList($refArr, $idList);
	}

	/**
	 * Change a reference from list
	 * NOTE: This function is deprecated. Use the newer class tx_templavoila_api instead.
	 *
	 * @param	array		Array of items from a current reference (array of table/uid pairs) (see _getItemArrayFromXML())
	 * @param	array		Reference to element in FlexForm structure, splitted into an array (see _splitAndValidateReference())
	 * @param	integer		New uid to set.
	 * @return	void
	 * @access	protected
	 * @deprecated version - 1.0.0
	 */
	function _changeReference($itemArray, $refArr, $newUid)	{

			// Unset the reference:
		$itemArray[$refArr[6]-1]['id'] = $newUid;

			// Create new list:
		$idList = array();
		foreach($itemArray as $idSet)	{
			$idList[] = $idSet['table'].'_'.$idSet['id'];
		}

			// Set the data field:
		$this->_updateFlexFormRefList($refArr, $idList);
	}

	/**
	 * Updates the XML structure with the new list of references to tt_content records.
	 * NOTE: This function is deprecated. Use the newer class tx_templavoila_api instead.
	 *
	 * @param	array		Reference to element in FlexForm structure, splitted into an array (see _splitAndValidateReference())
	 * @param	array		Array of record references
	 * @return	void
	 * @access	protected
	 * @deprecated version - 1.0.0
	 */
	function _updateFlexFormRefList($refArr, $idListArr)	{
			// Set the data field:
		$dataArr = array();
		$dataArr[$refArr[0]][$refArr[1]][$this->flexFieldIndex[$refArr[0]]]['data'][$refArr[2]][$refArr[3]][$refArr[4]][$refArr[5]] = implode(',',$idListArr);

			// Execute:
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;
		$tce->start($dataArr,array());
		$tce->process_datamap();
	}

	/**
	 * Deletes an element from tt_content table using TCEmain
	 * NOTE: This function is deprecated. Use the newer class tx_templavoila_api instead.
	 *
	 * @param	integer		UID of the tt_content element to delete.
	 * @return	void
	 * @access	protected
	 * @deprecated version - 1.0.0
	 */
	function _deleteContentElement($uid)	{
		$cmdArray = array();
		$cmdArray['tt_content'][$uid]['delete'] = 1;

			// Store:
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;
		$tce->start(array(),$cmdArray);
		$tce->process_cmdmap();
	}











	/***************************************
	 *
	 * Helper functions
	 *
	 **************************************/

	/**
	 * Creates a new reference list (as array) with the $item element inserted
	 * NOTE: This function is deprecated. Use the newer class tx_templavoila_api instead.
	 *
	 * @param	array		Array of items from a current reference (array of table/uid pairs) (see _getItemArrayFromXML())
	 * @param	array		Reference to element in FlexForm structure, splitted into an array (see _splitAndValidateReference())
	 * @param	string		Item reference, typically 'tt_content_'.$uid
	 * @param	mixed		Only for CUT operations within the SAME field (otherwise FALSE): Reference to the *source* element if this is a move operation where we need to remove this (same field...)
	 * @return	array		Array with record reference, ready to implode and store in FlexForm structure field
	 * @access	protected
	 * @deprecated version - 1.0.0
	 */
	function _insertReferenceInList($itemArray, $refArr, $item, $sourceRefArr=FALSE)	{

			// Initialize:
		$inserted = 0;
		$idList = array();
		$counter = 0;

			// Insert first:
		if ($refArr[6]==0)	{
			$idList[] = $item;
			$inserted = 1;
		}

			// Insert somewhere in between current elements:
		foreach($itemArray as $idSet)	{
			$counter++;

				// Always insert the previous element UNLESS we are cutting an element from the same record!
			if (!is_array($sourceRefArr) || $sourceRefArr[6]!=$counter)	{
				$idList[] = $idSet['table'].'_'.$idSet['id'];
			}

				// Add new item:
			if ($refArr[6]==$counter)	{
				$idList[] = $item;
				$inserted = 1;
			}
		}

			// If NOT inserted yet, insert in the end (would be a mistake...)
		if (!$inserted)	{
			$idList[] = $item;
		}

			// Return array of ids:
		return $idList;
	}

	/**
	 * Copy the element with uid $itemAtPosition_uid and return the new uid
	 * NOTE: This function is deprecated. Use the newer class tx_templavoila_api instead.
	 *
	 * @param	integer		Uid of original
	 * @param	integer		Pid of the new record
	 * @return	integer		New UID or false if no new copy was created.
	 * @access	protected
	 * @deprecated version - 1.0.0
	 */
	function _getCopyUid($itemAtPosition_uid, $pid)	{

			// Create copy:
		$cmdArray = array();
		$cmdArray['tt_content'][$itemAtPosition_uid]['copy'] = $pid;
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->start(array(),$cmdArray);
		$tce->process_cmdmap();

			// Set the id of the new copy:
		if ($tce->copyMappingArray['tt_content'][$itemAtPosition_uid])	{

				// Return the uid:
			return $tce->copyMappingArray['tt_content'][$itemAtPosition_uid];
		}
	}

	/**
	 * Returns a comma separated list of uids of all sub elements of the element specified by $table and $uid.
	 * NOTE: This function is deprecated. Use the newer class tx_templavoila_api instead.
	 *
	 * @param	string		$table: Name of the table of the parent element ('pages' or 'tt_content')
	 * @param	integer		$uid: UID of the parent element
	 * @param	array		Array of record UIDs - used internally, don't touch
	 * @return	array		Array of record UIDs
	 * @access	protected
	 * @deprecated version - 1.0.0
	 */
	function _getListOfSubElementsRecursively ($table, $uid, &$recordUids) {

			// Fetch the specified record, find all sub elements
		$parentRecord = t3lib_BEfunc::getRecord ($table, $uid, 'uid,pid,'.$this->flexFieldIndex[$table]);
		$flexFieldArr = t3lib_div::xml2array($parentRecord[$this->flexFieldIndex[$table]]);

#t3lib_div::devLog('_getListOfSubElementsRecursively ('.$table.', '.$uid.', '.implode(',',$recordUids).')', 'tx_templavoila', 1);

		if (is_array ($flexFieldArr['data'])) {
			foreach ($flexFieldArr['data'] as $sheetKey => $languagesArr) {
				if (is_array ($languagesArr)) {
					foreach ($languagesArr as $languageKey=> $fieldsArr) {
						if (is_array ($fieldsArr)) {
							foreach ($fieldsArr as $fieldName => $valuesArr) {
								if (is_array ($valuesArr)) {
									foreach ($valuesArr as $valueName => $value) {
										$valueItems = t3lib_div::intExplode (',', $value);
										if (is_array($valueItems)) {
											foreach ($valueItems as $index => $subElementUid) {
												if ($subElementUid > 0) {
													$recordUids[] = $subElementUid;
													$this->_getListOfSubElementsRecursively ('tt_content', $subElementUid, $recordUids);
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
		return $recordUids;
	}

	/**
	 * Split reference
	 * FIXME Should also verify the integrity of the reference string since the rest of the application does NOT check it further.
	 * NOTE: This function is deprecated. Use the newer class tx_templavoila_api instead.
	 *
	 * @param	string		Reference to a tt_content element in a flexform field of references to tt_content elements. Syntax is: [table]:[uid]:[sheet]:[lLanguage]:[FlexForm field name]:[vLanguage]:[index of reference position in field value]. Example: 'pages:78:sDEF:lDEF:field_contentarea:vDEF:0/tt_content:60'
	 * @return	array		Array with each part between ':' in a value with numeric key (exploded)
	 * @access	protected
	 * @deprecated version - 1.0.0
	 */
	function _splitAndValidateReference($string)	{
		 $refArr = explode(':',$string);

		 if ($version = t3lib_BEfunc::getWorkspaceVersionOfRecord($GLOBALS['BE_USER']->workspace, $refArr[0], $refArr[1], 'uid'))	{
			$refArr[1] = $version['uid'];
		 }

		 return $refArr;
	}

	/**
	 * Takes FlexForm XML content in and based on the reference found in $refArr array it will find the list of references, parse them and return them as an array.
	 * NOTE: This function is deprecated. Use the newer class tx_templavoila_api instead.
	 *
	 * @param	string		XML content of FlexForm field.
	 * @param	array		Reference; definining which field in the XML to get list of records from.
	 * @return	array		Numerical array of arrays defining items by table and uid key/value pairs.
	 * @access	protected
	 * @deprecated version - 1.0.0
	 */
	function _getItemArrayFromXML($xmlString, $refArr)	{

			// Getting value of the field containing the relations:
		$xmlContent = t3lib_div::xml2array($xmlString);
		$dat = is_array($xmlContent) ? $xmlContent['data'][$refArr[2]][$refArr[3]][$refArr[4]][$refArr[5]] : '';

			// Getting the relation uids out:
		$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
		$dbAnalysis->start($dat, 'tt_content');

			// Return array of items:
		return $dbAnalysis->itemArray;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_xmlrelhndl.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_xmlrelhndl.php']);
}
?>