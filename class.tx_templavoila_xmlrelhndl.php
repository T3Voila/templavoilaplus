<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003, 2004  Kasper Skårhøj (kasper@typo3.com) / Robert Lemke (robert@typo3.org)
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
 * @author		Kasper Skårhøj <kasper@typo3.com>
 * @coauthor	Robert Lemke <robert@typo3.org>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   73: class tx_templavoila_xmlrelhndl
 *   91:     function init($altRoot)
 *  105:     function insertRecord($destination, $row)
 *  152:     function pasteRecord($pasteCmd, $source, $destination)
 *
 *              SECTION: Execute changes to the FlexForm structures
 *  285:     function _insertReference($itemArray, $refArr, $item)
 *  306:     function _moveReference($itemArray, $destRefArr, $sourceItemArray, $sourceRefArr, $item_table, $item_uid, $movePid)
 *  345:     function _removeReference($itemArray, $refArr)
 *  368:     function _changeReference($itemArray, $refArr, $newUid)
 *  390:     function _updateFlexFormRefList($refArr, $idListArr)
 *  408:     function _deleteContentElement($uid)
 *
 *              SECTION: Helper functions
 *  444:     function _insertReferenceInList($itemArray, $refArr, $item, $sourceRefArr=FALSE)
 *  489:     function _getCopyUid($itemAtPosition_uid, $pid)
 *  514:     function _splitAndValidateReference($string)
 *  527:     function _getItemArrayFromXML($xmlString, $refArr)
 *
 * TOTAL FUNCTIONS: 13
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



 	// We need the TCE forms functions
require_once (PATH_t3lib.'class.t3lib_loaddbgroup.php');
require_once (PATH_t3lib.'class.t3lib_tcemain.php');

/**
 * Handler class for FlexForm relations between content elements and host object
 *
 * @author		Kasper Skaarhoj <kasper@typo3.com>
 * @coauthor	Robert Lemke <robert@typo3.org>
 * @package		TYPO3
 * @subpackage	tx_templavoila
 */
class tx_templavoila_xmlrelhndl {

		// External, static:
	var $rootTable = 'pages';					// The table of the root level.
	var $flexFieldIndex = array(
		'tt_content' => 'tx_templavoila_flex',
		'pages' => 'tx_templavoila_flex',
	);




	/**
	 * Initialize, setting alternative root table and flex field if needed.
	 *
	 * @param	array		Alternative root
	 * @return	void
	 */
	function init($altRoot)	{
		if ($altRoot['table'])	{
			$this->rootTable = $altRoot['table'];
			$this->flexFieldIndex[$this->rootTable] = $altRoot['field_flex'];
		}
	}

	/**
	 * Creates a page content element (tt_content) and inserts reference in FlexForm field
	 *
	 * @param	string		$destination: Position reference of where to create new element. Syntax is: [table]:[uid]:[sheet]:[structure Language]:[FlexForm field name]:[value language]:[index of reference position in field value]
	 * @param	array		$row: Array of default field values for creating the content element record. 'pid' and 'uid' are overridden.
	 * @return	integer		uid of the created content element (if any)
	 */
	function insertRecord($destination, $row)	{

			// Split reference into parts:
		$destRefArr = $this->_splitAndValidateReference($destination);

			// Check that destination table is acceptable:
		if (t3lib_div::inList($this->rootTable.',tt_content',$destRefArr[0])) {

				// Get the destination record content:
			$destinationRec = t3lib_BEfunc::getRecord($destRefArr[0],$destRefArr[1],'uid,pid,'.$this->flexFieldIndex[$destRefArr[0]]);
			if (is_array($destinationRec))	{

					// First, create record:
				$dataArr = array();
				$dataArr['tt_content']['NEW'] = $row;
				$dataArr['tt_content']['NEW']['pid'] = ($destRefArr[0]=='pages' ? $destinationRec['uid'] : $destinationRec['pid']);
				unset($dataArr['tt_content']['NEW']['uid']);

					// If the destination is not the default language, try to set the old-style sys_language_uid field accordingly
				if ($destRefArr[3] != 'lDEF' || $destRefArr[5] != 'vDEF') {
					$languageKey = $destRefArr[5] != 'vDEF' ? $destRefArr[5] : $destRefArr[3];
					$staticLangRows = t3lib_BEfunc::getRecordsByField('static_languages', 'lg_iso_2', substr($languageKey, 1));
					if (isset($staticLangRows[0]['uid'])) {
						$languageRecords = t3lib_BEfunc::getRecordsByField('sys_language',	'static_lang_isocode', $staticLangRows[0]['uid']);
						if (isset($languageRecords[0]['uid'])) {
							$dataArr['tt_content']['NEW']['sys_language_uid'] = $languageRecords[0]['uid'];
						}
					}
				}

					// Instantiate TCEmain and create the record:
				$tce = t3lib_div::makeInstance('t3lib_TCEmain');
				$tce->stripslashes_values = 0;
				$tce->start($dataArr,array());
				$tce->process_datamap();
				$id = $tce->substNEWwithIDs['NEW'];

					// If a new record was created, insert the uid in the FlexForm reference field:
				if ($id)	{

						// Insert the element in the current list:
					$itemArray = $this->_getItemArrayFromXML($destinationRec[$this->flexFieldIndex[$destRefArr[0]]], $destRefArr);
					$this->_insertReference($itemArray, $destRefArr, 'tt_content_'.$id);

						// Return the uid of the new tt_content element:
					return $id;
				}
			}
		}
	}

	/**
	 * Performs the processing part of pasting a record.
	 *
	 * @param	string		$pasteCmd: Kind of pasting: 'cut', 'copy', 'ref' or 'unlink'
	 * @param	string		$source: String defining the original record. [table]:[uid]:[sheet]:[structure Language]:[FlexForm field name]:[value language]:[index of reference position in field value]/[ref. table]:[ref. uid]. Example: 'pages:78:sDEF:lDEF:field_contentarea:vDEF:0/tt_content:60'. The field name in the table is implicitly 'tx_templavoila_flex'. The definition of the reference element after the slash MUST match the element pointed to by the reference index in the first part. This is a security measure.
	 * @param	string		$destination: Defines the destination where to paste the record (not used when unlinking of course). Syntax is the same as first part of 'source', defining a position in a FlexForm 'tx_templavoila_flex' field.
	 * @return	void
	 */
	function pasteRecord($pasteCmd, $source, $destination)	{

			// Split the source definition into parts:
		list($sourceStr,$check,$isLocal,$currentPageId) = explode('/',$source);

		if ($sourceStr)	{
			$sourceRefArr = $this->_splitAndValidateReference($sourceStr);

				// The 'source' elements actually point to the source element by its current position in a relation field - the $check variable should match what we find...
			if (t3lib_div::inList($this->rootTable.',tt_content',$sourceRefArr[0]))	{

					// Get source record (where the current item is)
				$sourceRec = t3lib_BEfunc::getRecord($sourceRefArr[0],$sourceRefArr[1],'uid,pid,'.$this->flexFieldIndex[$sourceRefArr[0]]);
				if (is_array($sourceRec))	{

						// Get reference items of source field:
					$sourceItemArray = $this->_getItemArrayFromXML($sourceRec[$this->flexFieldIndex[$sourceRefArr[0]]], $sourceRefArr);

						// Getting the item at the index-position:
					$itemOnPosition = $sourceItemArray[$sourceRefArr[6]-1];
					$refID = $itemOnPosition['id'];

						// Now, check if the current element actually matches what it should (otherwise some update must have taken place in between...)
					if ($itemOnPosition['table'].':'.$itemOnPosition['id'] == $check && $itemOnPosition['table']=='tt_content')	{	// None other than tt_content elements are moved around...

						if ($pasteCmd=='unlink')	{	// Removing the reference:
							$this->_removeReference($sourceItemArray, $sourceRefArr);
						} elseif ($pasteCmd=='delete')	{	// Removing AND DELETING the reference:
							$this->_removeReference($sourceItemArray, $sourceRefArr);
							$this->_deleteContentElement($itemOnPosition['id']);
						} elseif ($pasteCmd=='localcopy') {

								// Get the uid of a new tt_content element
							$refID = $this->_getCopyUid($refID,	$currentPageId);

							$this->_changeReference($sourceItemArray, $sourceRefArr, $refID);
						} else {	// Copy or Cut a reference:

								// Now, find destination (record in which to insert the new reference)
							$destRefArr = $this->_splitAndValidateReference($destination);
							if (t3lib_div::inList($this->rootTable.',tt_content',$destRefArr[0]))	{
									// Destination record:
								$destinationRec = t3lib_BEfunc::getRecord($destRefArr[0],intval($destRefArr[1]),'uid,pid,'.$this->flexFieldIndex[$destRefArr[0]]);
								if (is_array($destinationRec))	{

										// Get reference items of destination field:
									$destItemArray = $this->_getItemArrayFromXML($destinationRec[$this->flexFieldIndex[$destRefArr[0]]], $destRefArr);

										// Depending on the paste command, we do...:
									switch ($pasteCmd)	{
										case 'copy':
												// Get the uid of a new tt_content element
											$refID = $this->_getCopyUid(
														$refID,
														$destRefArr[0]=='pages' ? $destinationRec['uid'] : $destinationRec['pid']
													);

											if ($refID)	{	// Only do copy IF a new element was created.
												$this->_insertReference($destItemArray, $destRefArr, 'tt_content_'.$refID);
											}
										break;
										case 'cut':

												// Find destination PID values (considering if table is 'pages' or not)
											$destPid = $destRefArr[0]=='pages' ? $destinationRec['uid'] : $destinationRec['pid'];	// Find true destination PID

												// Get record of the item we are moving:
											$itemRec = t3lib_BEfunc::getRecord($itemOnPosition['table'], $itemOnPosition['id'], 'uid,pid');

												// If the record we are cutting is LOCAL (on the current page) and if the destination PID is different from the record's pid (otherwise a move is non-sense) we set $destPid:
											if ($isLocal && $itemRec['pid']!=$destPid)	{
												$movePid = $destPid;
											} else {
												$movePid = 0;
											}

											$this->_moveReference($destItemArray, $destRefArr, $sourceItemArray, $sourceRefArr, 'tt_content', $refID, $movePid);
										break;
										case 'ref':		// Insert a reference (to Content Element from INSIDE the structure somewhere)
											$this->_insertReference($destItemArray, $destRefArr, 'tt_content_'.$refID);
										break;
									}
								}
							}
						}
					}
				}
			}
		} elseif($check && $pasteCmd=='ref') {		// Insert a reference (to Content Element from outside the structure)

				// Splitting parameters
			$destRefArr = $this->_splitAndValidateReference($destination);
			list($table,$uid) = explode(':', $check);

				// Checking parameters:
			if ($table=='tt_content' && t3lib_div::inList($this->rootTable.',tt_content',$destRefArr[0]))	{
				$destinationRec = t3lib_BEfunc::getRecord($destRefArr[0],$destRefArr[1],'uid,pid,'.$this->flexFieldIndex[$destRefArr[0]]);
				if (is_array($destinationRec))	{

						// Insert the reference:
					$itemArray = $this->_getItemArrayFromXML($destinationRec[$this->flexFieldIndex[$destRefArr[0]]], $destRefArr);
					$this->_insertReference($itemArray, $destRefArr, 'tt_content_'.$uid);
				}
			}
		}
	}












	/***************************************
	 *
	 * Execute changes to the FlexForm structures
	 *
	 **************************************/

	/**
	 * Insert item reference into list.
	 *
	 * @param	array		$itemArr: Array of items from a current reference (array of table/uid pairs) (see _getItemArrayFromXML())
	 * @param	array		$refArr: Reference to element in FlexForm structure, splitted into an array (see _splitAndValidateReference())
	 * @param	string		Item reference, typically 'tt_content_'.$uid
	 * @return	void
	 */
	function _insertReference($itemArr, $refArr, $item)	{
			// Get new list of IDs:
		$idList = $this->_insertReferenceInList($itemArr, $refArr, $item);

			// Set the data field:
		$this->_updateFlexFormRefList($refArr, $idList);

	}

	/**
	 * Moves a reference into from list to another position.
	 *
	 * @param	array		Destination: Array of items from a current reference (array of table/uid pairs) (see _getItemArrayFromXML())
	 * @param	array		Destination: Reference to element in FlexForm structure, splitted into an array (see _splitAndValidateReference())
	 * @param	array		Source: Array of items from a current reference (array of table/uid pairs) (see _getItemArrayFromXML())
	 * @param	array		Source: Reference to element in FlexForm structure, splitted into an array (see _splitAndValidateReference())
	 * @param	string		Item table
	 * @param	integer		Item uid
	 * @param	integer		Move-pid (pos/neg) to which the record should be moved (if at all).
	 * @return	void
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
	 *
	 * @param	array		Array of items from a current reference (array of table/uid pairs) (see _getItemArrayFromXML())
	 * @param	array		Reference to element in FlexForm structure, splitted into an array (see _splitAndValidateReference())
	 * @return	void
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
	 *
	 * @param	array		Array of items from a current reference (array of table/uid pairs) (see _getItemArrayFromXML())
	 * @param	array		Reference to element in FlexForm structure, splitted into an array (see _splitAndValidateReference())
	 * @param	integer		New uid to set.
	 * @return	void
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
	 *
	 * @param	array		Reference to element in FlexForm structure, splitted into an array (see _splitAndValidateReference())
	 * @param	array		Array of record references
	 * @return	void
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
	 *
	 * @param	integer		UID of the tt_content element to delete.
	 * @return	void
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
	 *
	 * @param	array		Array of items from a current reference (array of table/uid pairs) (see _getItemArrayFromXML())
	 * @param	array		Reference to element in FlexForm structure, splitted into an array (see _splitAndValidateReference())
	 * @param	string		Item reference, typically 'tt_content_'.$uid
	 * @param	mixed		Only for CUT operations within the SAME field (otherwise FALSE): Reference to the *source* element if this is a move operation where we need to remove this (same field...)
	 * @return	array		Array with record reference, ready to implode and store in FlexForm structure field
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
	 *
	 * @param	integer		Uid of original
	 * @param	integer		Pid of the new record
	 * @return	integer		New UID or false if no new copy was created.
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
	 * Split reference
	 * Should also verify the integrity of the reference string since the rest of the application does NOT check it further.
	 *
	 * @param	string		Reference to a tt_content element in a flexform field of references to tt_content elements. Syntax is: [table]:[uid]:[sheet]:[lLanguage]:[FlexForm field name]:[vLanguage]:[index of reference position in field value]. Example: 'pages:78:sDEF:lDEF:field_contentarea:vDEF:0/tt_content:60'
	 * @return	array		Array with each part between ':' in a value with numeric key (exploded)
	 * @todo 	We should check if $refArr[2] (the sheet key), [3] (lKey),[4] (the field name) and [5] (vKey) is actually in the data structure! Otherwise a possible XSS hole! - Basically, we should just create a checksum on the value of the whole parameter!
	 */
	function _splitAndValidateReference($string)	{
		 $refArr = explode(':',$string);

		 return $refArr;
	}

	/**
	 * Takes FlexForm XML content in and based on the reference found in $refArr array it will find the list of references, parse them and return them as an array.
	 *
	 * @param	string		XML content of FlexForm field.
	 * @param	array		Reference; definining which field in the XML to get list of records from.
	 * @return	array		Numerical array of arrays defining items by table and uid key/value pairs.
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