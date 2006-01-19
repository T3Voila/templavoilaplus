<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005  Robert Lemke (robert@typo3.org)
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
 * Public API for TemplaVoila
 *
 * $Id$
 *
 * @author     Robert Lemke <robert@typo3.org>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   99: class tx_templavoila_api
 *  111:     function __construct ($rootTable = 'pages')
 *  121:     function tx_templavoila_api ($alternativeRootTable = 'pages')
 *
 *              SECTION: Element manipulation functions (public)
 *  144:     function insertElement ($destinationPointer, $elementRow)
 *  167:     function insertElement_createRecord ($destinationPointer, $row)
 *  219:     function insertElement_setElementReferences ($destinationPointer, $uid)
 *  241:     function moveElement ($sourcePointer, $destinationPointer)
 *  256:     function moveElement_setElementReferences ($sourcePointer, $destinationPointer)
 *  272:     function copyElement ($sourcePointer, $destinationPointer, $copySubElements = TRUE)
 *  287:     function localizeElement ($sourcePointer, $languageKey)
 *  342:     function referenceElement ($sourcePointer, $destinationPointer)
 *  359:     function referenceElementByUid ($uid, $destinationPointer)
 *  375:     function unlinkElement ($sourcePointer)
 *  388:     function deleteElement ($sourcePointer)
 *
 *              SECTION: Processing functions (protected)
 *  413:     function process ($mode, $sourcePointer, $destinationPointer = NULL, $onlyHandleReferences = FALSE)
 *  470:     function process_move ($sourcePointer, $destinationPointer, $sourceReferencesArr, $destinationReferencesArr, $sourceParentRecord, $destinationParentRecord, $elementRecord, $onlyHandleReferences)
 *  522:     function process_copy ($sourcePointer, $destinationPointer, $sourceReferencesArr, $destinationReferencesArr, $sourceParentRecord, $destinationParentRecord, $elementUid)
 *  559:     function process_copyRecursively ($sourcePointer, $destinationPointer, $sourceReferencesArr, $destinationReferencesArr, $sourceParentRecord, $destinationParentRecord, $elementUid)
 *  598:     function process_reference ($destinationPointer, $destinationReferencesArr, $elementUid)
 *  614:     function process_unlink ($sourcePointer, $sourceReferencesArr)
 *  631:     function process_delete ($sourcePointer, $sourceReferencesArr, $elementUid)
 *
 *              SECTION: Flexform helper functions (public)
 *  674:     function flexform_getValidPointer ($flexformPointer)
 *  719:     function flexform_getPointerFromString ($flexformPointerString)
 *  758:     function flexform_getStringFromPointer ($flexformPointer)
 *  791:     function flexform_getRecordByPointer ($flexformPointer)
 *  815:     function flexform_getPointersByRecord ($elementUid, $pageUid)
 *  839:     function flexform_getElementReferencesFromXML($flexformXML, $flexformPointer)
 *  873:     function flexform_getListOfSubElementUidsRecursively ($table, $uid, &$recordUids)
 *  916:     function flexform_getFlexformPointersToSubElementsRecursively ($table, $uid, &$flexformPointers)
 *
 *              SECTION: Flexform helper functions (protected)
 *  981:     function flexform_insertElementReferenceIntoList($currentReferencesArr, $position, $elementUid)
 * 1021:     function flexform_removeElementReferenceFromList($currentReferencesArr, $position)
 * 1043:     function flexform_storeElementReferencesListInRecord ($referencesArr, $destinationPointer)
 *
 *              SECTION: Data structure helper functions (public)
 * 1084:     function ds_getFieldNameByColumnPosition ($contextPageUid, $columnPosition)
 * 1138:     function ds_getExpandedDataStructure ($table, $row)
 * 1175:     function ds_getAvailableTORecords ($pageUid)
 *
 *              SECTION: Miscellaneous functions (protected)
 * 1218:     function setTCEmainRunningFlag ($flag)
 * 1230:     function getTCEmainRunningFlag ()
 * 1241:     function getStorageFolderPid($pageUid)
 *
 * TOTAL FUNCTIONS: 37
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

/**
 * Public API class for proper handling of content elements and other useful TemplaVoila related functions
 *
 * @author		Robert Lemke <robert@typo3.org>
 * @package		TYPO3
 * @subpackage	tx_templavoila
 */
class tx_templavoila_api {

	var $rootTable;
	var $debug = TRUE;

	/**
	 * The constructor.
	 *
	 * @param	string		$rootTable: Usually the root table is "pages" but another table can be specified (eg. "tt_content")
	 * @return	void
	 * @access public
	 */
	function __construct ($rootTable = 'pages') {
		$this->rootTable = $rootTable;
	}

	/**
	 * PHP4 compatible constructor
	 *
	 * @param	string		$alternativeRootTable: Usually the root table is "pages" but another table can be specified (eg. "tt_content")
	 * @return	void
	 */
	function tx_templavoila_api ($alternativeRootTable = 'pages') {
		return $this->__construct ($alternativeRootTable);
	}





	/******************************************************
	 *
	 * Element manipulation functions (public)
	 *
	 ******************************************************/

	/**
	 * Creates a new content element record and sets the neccessary references to connect
	 * it to the parent element.
	 *
	 * @param	array		$destinationPointer: Flexform pointer defining the parent location of the new element. Position refers to the element _after_ which the new element should be inserted. Position == 0 means before the first element.
	 * @param	array		$elementRow: Array of field keys and values for the new content element record
	 * @return	mixed		The UID of the newly created record or FALSE if operation was not successful
	 * @access public
	 */
	function insertElement ($destinationPointer, $elementRow) {
		if ($this->debug) t3lib_div::devLog ('API: insertElement()', 'templavoila', 0, array ('destinationPointer' => $destinationPointer, 'elementRow' => $elementRow));

			// Check if destination is valid:
		if (!$destinationPointer = $this->flexform_getValidPointer ($destinationPointer)) return FALSE;

		$newRecordUid = $this->insertElement_createRecord($destinationPointer, $elementRow);
		if ($newRecordUid === FALSE) return FALSE;

		$result = $this->insertElement_setElementReferences($destinationPointer, $newRecordUid);
		if ($result === FALSE) return FALSE;

		return $newRecordUid;
	}

	/**
	 * Sub function of insertElement: creates a new tt_content record in the database.
	 *
	 * @param	array		$destinationPointer: flexform pointer to the parent element of the new record
	 * @param	array		$row: The record data to insert into the database
	 * @return	mixed		The UID of the newly created record or FALSE if operation was not successful
	 * @access public
	 */
	function insertElement_createRecord ($destinationPointer, $row) {
		if ($this->debug) t3lib_div::devLog ('API: insertElement_createRecord()', 'templavoila', 0, array ('destinationPointer' => $destinationPointer, 'row' => $row));

		$parentRecord = t3lib_BEfunc::getRecord($destinationPointer['table'], $destinationPointer['uid'],'uid,pid,t3ver_oid');
		$newRecordPid = ($destinationPointer['table'] == 'pages' ? ($parentRecord['pid'] == -1 ? $parentRecord['t3ver_oid'] : $parentRecord['uid']) : $parentRecord['pid']);

		$dataArr = array();
		$dataArr['tt_content']['NEW'] = $row;
		$dataArr['tt_content']['NEW']['pid'] = $newRecordPid;
		unset($dataArr['tt_content']['NEW']['uid']);

			// If the destination is not the default language, try to set the old-style sys_language_uid field accordingly
		if ($destinationPointer['sLang'] != 'lDEF' || $destinationPointer['vLang'] != 'vDEF') {
			$languageKey = $destinationPointer['vLang'] != 'vDEF' ? $destinationPointer['vLang'] : $destinationPointer['sLang'];
			$staticLanguageRows = t3lib_BEfunc::getRecordsByField('static_languages', 'lg_iso_2', substr($languageKey, 1));
			if (isset($staticLanguageRows[0]['uid'])) {
				$languageRecord = t3lib_BEfunc::getRecordRaw('sys_language', 'static_lang_isocode='.intval($staticLanguageRows[0]['uid']));
				if (isset($languageRecord['uid'])) {
					$dataArr['tt_content']['NEW']['sys_language_uid'] = $languageRecord['uid'];
				}
			}
		}

			// Instantiate TCEmain and create the record:
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');

			// set default TCA values specific for the user
		$TCAdefaultOverride = $GLOBALS['BE_USER']->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride))	{
			$tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}

		$tce->stripslashes_values = 0;
		$flagWasSet = $this->getTCEmainRunningFlag();
		$this->setTCEmainRunningFlag (TRUE);
		$tce->start($dataArr,array());
		$tce->process_datamap();
		$newUid = $tce->substNEWwithIDs['NEW'];
		if (!$flagWasSet) $this->setTCEmainRunningFlag (FALSE);

		return (intval($newUid) ? intval($newUid) : FALSE);
	}

	/**
	 * Sub function of insertElement: sets the references in the parent element for a newly created tt_content
	 * record.
	 *
	 * @param	array		$destinationPointer: Flexform pointer defining the parent element of the new element. Position refers to the element _after_ which the new element should be inserted. Position == 0 means before the first element.
	 * @param	array		$uid: UID of the tt_content record
	 * @return	void
	 * @access public
	 */
	function insertElement_setElementReferences ($destinationPointer, $uid) {
		if ($this->debug) t3lib_div::devLog ('API: insertElement_setElementReferences()', 'templavoila', 0, array ('destinationPointer' => $destinationPointer, 'uid' => $uid));

		$parentRecord = t3lib_BEfunc::getRecordWSOL($destinationPointer['table'], $destinationPointer['uid'],'uid,pid,tx_templavoila_flex');
		if (!is_array ($parentRecord)) return FALSE;

		$currentReferencesArr = $this->flexform_getElementReferencesFromXML ($parentRecord['tx_templavoila_flex'], $destinationPointer);
		$newReferencesArr = $this->flexform_insertElementReferenceIntoList ($currentReferencesArr, $destinationPointer['position'], $uid);
		$this->flexform_storeElementReferencesListInRecord ($newReferencesArr, $destinationPointer);

		return TRUE;
	}

	/**
	 * Moves an element specified by the source pointer to the location specified by
	 * destination pointer.
	 *
	 * @param	array		$sourcePointer: flexform pointer pointing to the element which shall be moved
	 * @param	array		$destinationPointer: flexform pointer to the new location
	 * @return	boolean		TRUE if operation was successfuly, otherwise false
	 * @access public
	 */
	function moveElement ($sourcePointer, $destinationPointer) {
		if ($this->debug) t3lib_div::devLog ('API: moveElement()', 'templavoila', 0, array ('sourcePointer' => $sourcePointer, 'destinationPointer' => $destinationPointer));
		return $this->process ('move', $sourcePointer, $destinationPointer);
	}

	/**
	 * Sets all references for moving an element specified by the source pointer to the location specified by
	 * destination pointer. The record itself won't be modified and therefore setting the PID etc. must be
	 * handled elsewhere.
	 *
	 * @param	array		$sourcePointer: flexform pointer pointing to the element which shall be moved
	 * @param	array		$destinationPointer: flexform pointer to the new location
	 * @return	boolean		TRUE if operation was successfuly, otherwise false
	 * @access public
	 */
	function moveElement_setElementReferences ($sourcePointer, $destinationPointer) {
		if ($this->debug) t3lib_div::devLog ('API: moveElement_setElementReferences()', 'templavoila', 0, array ('sourcePointer' => $sourcePointer, 'destinationPointer' => $destinationPointer));
		return $this->process ('move', $sourcePointer, $destinationPointer, TRUE);
	}

	/**
	 * Makes a true copy of an element specified by the source pointer to the location specified by
	 * destination pointer. By default also copies all sub elements but can be disabled so sub elements
	 * are not copied but referenced.
	 *
	 * @param	array		$sourcePointer: flexform pointer pointing to the element which shall be copied
	 * @param	array		$destinationPointer: flexform pointer to the location for the copy
	 * @param	boolean		$copySubElements: If set to TRUE, also all sub elements will be truly copied
	 * @return	mixed		UID of the created copy, otherwise FALSE
	 * @access public
	 */
	function copyElement ($sourcePointer, $destinationPointer, $copySubElements = TRUE) {
		if ($this->debug) t3lib_div::devLog ('API: copyElement()', 'templavoila', 0, array ('sourcePointer' => $sourcePointer, 'destinationPointer' => $destinationPointer, 'copySubElements' => $copySubElements));
		return $this->process ($copySubElements ? 'copyrecursively' : 'copy', $sourcePointer, $destinationPointer);
	}

	/**
	 * Makes a true copy of a tt_content element specified by the source pointer to the same location but with
	 * the language specified by "languageKey". The new element will refer to the source element as the
	 * original version.
	 *
	 * @param	array		$sourcePointer: flexform pointer pointing to the element which shall be localized
	 * @param	string		$languageKey: A two letter ISO language key (eg. 'EN')
	 * @return	mixed		UID of the created copy, otherwise FALSE
	 * @access public
	 */
	function localizeElement ($sourcePointer, $languageKey) {
		global $TCA;

		if ($this->debug) t3lib_div::devLog ('API: localizeElement()', 'templavoila', 0, array ('sourcePointer' => $sourcePointer, 'languageKey' => $languageKey));

		$sourceElementRecord = $this->flexform_getRecordByPointer ($sourcePointer);
		$parentPageRecord = t3lib_beFunc::getRecordWSOL('pages', $sourceElementRecord['pid']);
		$rawPageDataStructureArr = t3lib_BEfunc::getFlexFormDS($TCA['pages']['columns']['tx_templavoila_flex']['config'], $parentPageRecord, 'pages');

		if ($rawPageDataStructureArr['meta']['langDisable'] == 1) {
			if ($this->debug) t3lib_div::devLog ('API: localizeElement(): Cannot localize element because localization is disabled for the active page datastructure!', 'templavoila', 0);
			return FALSE;
		}

			// Build destination pointer and copy the element:
		$destinationPointer = $sourcePointer;
		$destinationPointer['sLang'] = $rawPageDataStructureArr['meta']['langChildren'] == 1 ? 'lDEF' : 'l'.$languageKey;
		$destinationPointer['vLang'] = $rawPageDataStructureArr['meta']['langChildren'] == 1 ? 'v'.$languageKey : 'vDEF';
		$destinationPointer['position'] = -1;

		$newElementUid = $this->process ('copy', $sourcePointer, $destinationPointer);

			// Modify the copied element's record:
		$dataArr = array();
		$dataArr['tt_content'][$newElementUid]['l18n_parent'] = $sourceElementRecord['uid'];

		$staticLanguageRows = t3lib_BEfunc::getRecordsByField('static_languages', 'lg_iso_2', $languageKey);
		if (isset($staticLanguageRows[0]['uid'])) {
			$languageRecords = t3lib_BEfunc::getRecordsByField('sys_language', 'static_lang_isocode', $staticLanguageRows[0]['uid']);
			if (isset($languageRecords[0]['uid'])) {
				$dataArr['tt_content'][$newElementUid]['sys_language_uid'] = $languageRecords[0]['uid'];
			}
		}

			// Store l18n modified information in the new tt_content record:
		$flagWasSet = $this->getTCEmainRunningFlag();
		$this->setTCEmainRunningFlag (TRUE);
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;
		$tce->start($dataArr,array());
		$tce->process_datamap();
		if (!$flagWasSet) $this->setTCEmainRunningFlag (FALSE);

		return $newElementUid;
	}

	/**
	 * Creates a reference to the element specified by the source pointer at the location specified by
	 * destination pointer.
	 *
	 * @param	array		$sourcePointer: flexform pointer pointing to the reference target
	 * @param	array		$destinationPointer: flexform pointer to the location where the reference should be stored
	 * @return	boolean		TRUE if operation was successfuly, otherwise false
	 * @access public
	 */
	function referenceElement ($sourcePointer, $destinationPointer) {
		if ($this->debug) t3lib_div::devLog ('API: referenceElement()', 'templavoila', 0, array ('sourcePointer' => $sourcePointer, 'destinationPointer' => $destinationPointer));
		return $this->process ('reference', $sourcePointer, $destinationPointer);
	}

	/**
	 * Creates a reference to the tt_content record specified by $uid. Basically does the same
	 * like referenceElement() but doesn't use a sourcePointer to find the reference target.
	 *
	 * Use this function in those situations when no flexform pointer exists, for example if
	 * you want a reference an element which has not yet been referenced anywhere else.
	 *
	 * @param	integer		$uid: UID of the tt_content element which shall be referenced
	 * @param	array		$destinationPointer: flexform pointer to the location where the reference should be stored
	 * @return	boolean		TRUE if operation was successfuly, otherwise false
	 * @access public
	 */
	function referenceElementByUid ($uid, $destinationPointer) {
		if ($this->debug) t3lib_div::devLog ('API: referenceElementByUid()', 'templavoila', 0, array ('uid' => $uid, 'destinationPointer' => $destinationPointer));
		$sourcePointer = array (
			'table' => 'tt_content',
			'uid' => intval($uid)
		);
		return $this->process ('referencebyuid', $sourcePointer, $destinationPointer);
	}

	/**
	 * Removes a reference to the element (= unlinks) specified by the source pointer.
	 *
	 * @param	array		$sourcePointer: flexform pointer pointing to the reference which shall be removed
	 * @return	boolean		TRUE if operation was successfuly, otherwise false
	 * @access public
	 */
	function unlinkElement ($sourcePointer) {
		if ($this->debug) t3lib_div::devLog ('API: unlinkElement()', 'templavoila', 0, array ('sourcePointer' => $sourcePointer));
		return $this->process ('unlink', $sourcePointer);
	}

	/**
	 * Removes a reference to the element (= unlinks) specified by the source pointer AND deletes the
	 * record.
	 *
	 * @param	array		$sourcePointer: flexform pointer pointing to the element which shall be deleted
	 * @return	boolean		TRUE if operation was successfuly, otherwise false
	 * @access public
	 */
	function deleteElement ($sourcePointer) {
		if ($this->debug) t3lib_div::devLog ('API: deleteElement()', 'templavoila', 0, array ('sourcePointer' => $sourcePointer));
		return $this->process ('delete', $sourcePointer);
	}





	/******************************************************
	 *
	 * Processing functions (protected)
	 *
	 ******************************************************/

	/**
	 * This method does the actually processing for the methods moveElement, copyElement etc.
	 *
	 * @param	string		$mode: Kind of processing
	 * @param	array		$sourcePointer: flexform pointer pointing to the element which will be processed. If "sheet", "sLang" etc. are set, it describes the position by specifying the (future) parent. If not, it describes the element directly with "table" and "uid".
	 * @param	mixed		$destinationPointer: flexform pointer to the destination location (if neccessary)
	 * @param	boolean		$onlyHandleReferences: If set, the record itself won't be moved, deleted etc. but only the references are set correctly. Use this feature if you are sure that the record has been handled before (eg. by TCEmain)
	 * @return	mixed		TRUE or something else (depends on operation) if operation was successful, otherwise FALSE
	 * @access protected
	 */
	function process ($mode, $sourcePointer, $destinationPointer = NULL, $onlyHandleReferences = FALSE) {

			// Check and get all information about the source position:
		if (!$sourcePointer = $this->flexform_getValidPointer ($sourcePointer)) return FALSE;
		$sourceParentRecord = t3lib_BEfunc::getRecordWSOL($sourcePointer['table'], $sourcePointer['uid'],'uid,pid,tx_templavoila_flex');
		if (!is_array ($sourceParentRecord)) {
			if ($this->debug) t3lib_div::devLog ('process: Parent record of the element specified by source pointer does not exist!', 2, $sourcePointer);
			return FALSE;
		}
		$sourceReferencesArr = $this->flexform_getElementReferencesFromXML ($sourceParentRecord['tx_templavoila_flex'], $sourcePointer);

			// Check and get all information about the destination position:
		if (is_array ($destinationPointer)) {
			if (!$destinationPointer = $this->flexform_getValidPointer ($destinationPointer)) return FALSE;
			$destinationParentRecord = t3lib_BEfunc::getRecordWSOL($destinationPointer['table'], $destinationPointer['uid'],'uid,pid,tx_templavoila_flex');
			if (!is_array ($destinationParentRecord)) {
				if ($this->debug) t3lib_div::devLog ('process: Parent record of the element specified by destination pointer does not exist!', 2, $destinationPointer);
				return FALSE;
			}
			$destinationReferencesArr = $this->flexform_getElementReferencesFromXML ($destinationParentRecord['tx_templavoila_flex'], $destinationPointer);
		}

			// Get information about the element to be processed:
		if (isset ($sourcePointer['sheet'])) {
			$sourceElementRecord = t3lib_BEfunc::getRecordWSOL('tt_content', $sourceReferencesArr[$sourcePointer['position']],'*');
		} else {
			$sourceElementRecord = t3lib_BEfunc::getRecordWSOL('tt_content', $sourcePointer['uid'],'*');
		}

		switch ($mode) {
			case 'move' :			$result = $this->process_move ($sourcePointer, $destinationPointer, $sourceReferencesArr, $destinationReferencesArr, $sourceParentRecord, $destinationParentRecord, $sourceElementRecord, $onlyHandleReferences); break;
			case 'copy':			$result = $this->process_copy ($sourcePointer, $destinationPointer, $sourceReferencesArr, $destinationReferencesArr, $sourceParentRecord, $destinationParentRecord, $sourceElementRecord['uid']); break;
			case 'copyrecursively':	$result = $this->process_copyRecursively ($sourcePointer, $destinationPointer, $sourceReferencesArr, $destinationReferencesArr, $sourceParentRecord, $destinationParentRecord, $sourceElementRecord['uid']); break;
			case 'reference':		$result = $this->process_reference ($destinationPointer, $destinationReferencesArr, $sourceElementRecord['uid']); break;
			case 'referencebyuid':	$result = $this->process_reference ($destinationPointer, $destinationReferencesArr, $sourcePointer['uid']); break;
			case 'unlink':			$result = $this->process_unlink ($sourcePointer, $sourceReferencesArr); break;
			case 'delete':			$result = $this->process_delete ($sourcePointer, $sourceReferencesArr, $sourceElementRecord['uid']); break;
		}

		return $result;
	}

	/**
	 * Actually moves the specified element and sets the element references of the parent element
	 * accordingly.
	 *
	 * @param	array		$sourcePointer: flexform pointer pointing to the element which will be moved
	 * @param	array		$destinationPointer: flexform pointer to the destination location
	 * @param	array		$sourceReferencesArr: Current list of the parent source's element references
	 * @param	array		$destinationReferencesArr: Current list of the parent destination's element references
	 * @param	array		$sourceParentRecord: Database record of the source location (either from table 'pages' or 'tt_content')
	 * @param	array		$destinationParentRecord: Database record of the destination location (either from table 'pages' or 'tt_content')
	 * @param	array		$elementRecord: The database record of the element to be moved
	 * @param	boolean		$onlyHandleReferences: If TRUE, only the references will be set, the record itself will not be moved (because that happens elsewhere)
	 * @return	boolean		TRUE if operation was successfuly, otherwise false
	 * @access protected
	 */
	function process_move ($sourcePointer, $destinationPointer, $sourceReferencesArr, $destinationReferencesArr, $sourceParentRecord, $destinationParentRecord, $elementRecord, $onlyHandleReferences) {

		$elementUid = $elementRecord['uid'];

			// Move the element within the same parent element:
		if ($sourcePointer['table'] == $destinationPointer['table'] && $sourcePointer['uid'] == $destinationPointer['uid']) {

			$newPosition = ($sourcePointer['position'] < $destinationPointer['position']) ? $destinationPointer['position']-1 : $destinationPointer['position'];
			$newReferencesArr = $this->flexform_removeElementReferenceFromList ($sourceReferencesArr, $sourcePointer['position']);
			$newReferencesArr = $this->flexform_insertElementReferenceIntoList ($newReferencesArr, $newPosition, $elementUid);

			$this->flexform_storeElementReferencesListInRecord ($newReferencesArr, $destinationPointer);

		} else {
				// Move the element to a different parent element:
			$newSourceReferencesArr = $this->flexform_removeElementReferenceFromList ($sourceReferencesArr, $sourcePointer['position']);
			$newDestinationReferencesArr = $this->flexform_insertElementReferenceIntoList ($destinationReferencesArr, $destinationPointer['position'], $elementUid);

			$this->flexform_storeElementReferencesListInRecord ($newSourceReferencesArr, $sourcePointer);
			$this->flexform_storeElementReferencesListInRecord ($newDestinationReferencesArr, $destinationPointer);

				// Make sure the PID is changed as well so the element belongs to the page where it is moved to:
		 	if (!$onlyHandleReferences && $elementRecord['pid'] == $sourceParentRecord['uid']) {
				$destinationPID = $destinationPointer['table'] == 'pages' ? $destinationParentRecord['uid'] : $destinationParentRecord['pid'];
				$cmdArray = array();
				$cmdArray['tt_content'][$elementUid]['move'] = $destinationPID;
				$flagWasSet = $this->getTCEmainRunningFlag();
				$this->setTCEmainRunningFlag (TRUE);
				$tce = t3lib_div::makeInstance('t3lib_TCEmain');
				$tce->stripslashes_values = 0;
				$tce->start(array(),$cmdArray);
				$tce->process_cmdmap();
				if (!$flagWasSet) $this->setTCEmainRunningFlag (FALSE);
		 	}
		}

		return TRUE;
	}

	/**
	 * Makes a copy of the specified element and only points to the sub elements with references.
	 *
	 * @param	array		$sourcePointer: flexform pointer pointing to the element which will be copied
	 * @param	array		$destinationPointer: flexform pointer to the destination location
	 * @param	array		$sourceReferencesArr: Current list of the parent source's element references
	 * @param	array		$destinationReferencesArr: Current list of the parent destination's element references
	 * @param	array		$sourceParentRecord: Database record of the source location (either from table 'pages' or 'tt_content')
	 * @param	array		$destinationParentRecord: Database record of the destination location (either from table 'pages' or 'tt_content')
	 * @param	integer		$elementUid: UID of the element to be copied
	 * @return	mixed		The UID of the newly created copy or FALSE if an error occurred.
	 * @access protected
	 */
	function process_copy ($sourcePointer, $destinationPointer, $sourceReferencesArr, $destinationReferencesArr, $sourceParentRecord, $destinationParentRecord, $elementUid) {

		$destinationPID = $destinationPointer['table'] == 'pages' ? $destinationParentRecord['uid'] : $destinationParentRecord['pid'];

			// Initialize TCEmain and create configuration for copying the specified record
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$cmdArray = array();
		$cmdArray['tt_content'][t3lib_beFunc::wsMapId('tt_content', $elementUid)]['copy'] = $destinationPID;

			// Execute the copy process and finally insert the reference for the element to the destination:
		$flagWasSet = $this->getTCEmainRunningFlag();
		$this->setTCEmainRunningFlag (TRUE);
		$tce->start(array(),$cmdArray);
		$tce->process_cmdmap();
		$newElementUid = $tce->copyMappingArray_merged['tt_content'][$elementUid];
		if (!$flagWasSet) $this->setTCEmainRunningFlag (FALSE);

		$newDestinationReferencesArr = $this->flexform_insertElementReferenceIntoList ($destinationReferencesArr, $destinationPointer['position'], $newElementUid);
		$this->flexform_storeElementReferencesListInRecord ($newDestinationReferencesArr, $destinationPointer);

		return $newElementUid;
	}

	/**
	 * Makes a true copy of the specified element and all sub elements and sets the element references of the parent element
	 * accordingly.
	 *
	 * @param	array		$sourcePointer: flexform pointer pointing to the element which will be copied
	 * @param	array		$destinationPointer: flexform pointer to the destination location
	 * @param	array		$sourceReferencesArr: Current list of the parent source's element references
	 * @param	array		$destinationReferencesArr: Current list of the parent destination's element references
	 * @param	array		$sourceParentRecord: Database record of the source location (either from table 'pages' or 'tt_content')
	 * @param	array		$destinationParentRecord: Database record of the destination location (either from table 'pages' or 'tt_content')
	 * @param	integer		$elementUid: UID of the element to be copied
	 * @return	mixed		The UID of the newly created copy or FALSE if an error occurred.
	 * @access protected
	 */
	function process_copyRecursively ($sourcePointer, $destinationPointer, $sourceReferencesArr, $destinationReferencesArr, $sourceParentRecord, $destinationParentRecord, $elementUid) {

			// Determine the PID of the new location and get uids of all sub elements of the element to be copied:
		$dummyArr = array();
		$destinationPID = $destinationPointer['table'] == 'pages' ? $destinationParentRecord['uid'] : $destinationParentRecord['pid'];
		$subElementUids = $this->flexform_getListOfSubElementUidsRecursively ('tt_content', $elementUid, $dummyArr);

			// Initialize TCEmain and create configuration for copying the specified record (the parent element) and all sub elements:
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$cmdArray = array();
		$cmdArray['tt_content'][$elementUid]['copy'] = $destinationPID;

		foreach ($subElementUids as $subElementUid) {
			$cmdArray['tt_content'][t3lib_beFunc::wsMapId('tt_content', $subElementUid)]['copy'] = $destinationPID;
		}

			// Execute the copy process and finally insert the reference for the parent element to the paste destination:
		$flagWasSet = $this->getTCEmainRunningFlag();
		$this->setTCEmainRunningFlag (TRUE);
		$tce->start(array(),$cmdArray);
		$tce->process_cmdmap();
		if (!$flagWasSet) $this->setTCEmainRunningFlag (FALSE);
		$newElementUid = $tce->copyMappingArray_merged['tt_content'][t3lib_beFunc::wsMapId('tt_content', $elementUid)];

		$newDestinationReferencesArr = $this->flexform_insertElementReferenceIntoList ($destinationReferencesArr, $destinationPointer['position'], $newElementUid);
		$this->flexform_storeElementReferencesListInRecord ($newDestinationReferencesArr, $destinationPointer);

		return $newElementUid;
	}

	/**
	 * Creates a reference which points to the specified element.
	 *
	 * @param	array		$destinationPointer: flexform pointer to the location where the reference should be stored
	 * @param	array		$destinationReferencesArr: Current list of the parent destination's element references
	 * @param	integer		$elementUid: UID of the tt_content element to be referenced
	 * @return	boolean		TRUE if the operation was successful or FALSE if an error occurred.
	 * @access protected
	 */
	function process_reference ($destinationPointer, $destinationReferencesArr, $elementUid) {

		$newDestinationReferencesArr = $this->flexform_insertElementReferenceIntoList ($destinationReferencesArr, $destinationPointer['position'], $elementUid);
		$this->flexform_storeElementReferencesListInRecord ($newDestinationReferencesArr, $destinationPointer);

		return TRUE;
	}

	/**
	 * Removes the specified reference
	 *
	 * @param	array		$sourcePointer: flexform pointer pointing to the reference which shall be removed
	 * @param	array		$sourceReferencesArr: Current list of the parent source's element references
	 * @return	boolean		TRUE if the operation was successful, otherwise FALSE
	 * @access protected
	 */
	function process_unlink ($sourcePointer, $sourceReferencesArr) {

		$newSourceReferencesArr = $this->flexform_removeElementReferenceFromList ($sourceReferencesArr, $sourcePointer['position']);
		$this->flexform_storeElementReferencesListInRecord ($newSourceReferencesArr, $sourcePointer);

		return TRUE;
	}

	/**
	 * Removes the specified reference and truly deletes the record
	 *
	 * @param	array		$sourcePointer: flexform pointer pointing to the element which will be the target of the reference
	 * @param	array		$sourceReferencesArr: Current list of the parent source's element references
	 * @param	integer		$elementUid: UID of the tt_content element to be deleted
	 * @return	boolean		TRUE if the operation was successful, otherwise FALSE
	 * @access protected
	 */
	function process_delete ($sourcePointer, $sourceReferencesArr, $elementUid) {

		if (!$this->process_unlink ($sourcePointer, $sourceReferencesArr)) return FALSE;

		$cmdArray = array();
		$cmdArray['tt_content'][t3lib_beFunc::wsMapId('tt_content', $elementUid)]['delete'] = 1;

			// Store:
		$flagWasSet = $this->getTCEmainRunningFlag();
		$this->setTCEmainRunningFlag (TRUE);
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;
		$tce->start(array(),$cmdArray);
		$tce->process_cmdmap();
		if (!$flagWasSet) $this->setTCEmainRunningFlag (FALSE);

		return TRUE;
	}





	/******************************************************
	 *
	 * Flexform helper functions (public)
	 *
	 ******************************************************/

	/**
	 * Checks if a flexform pointer points to a valid location, ie. the sheets,
	 * fields etc. exist in the target data structure. If it is valid, the pointer
	 * array will be returned.
	 *
	 * If 'targetCheckUid' is set, the uid of the record which is referenced by
	 * the pointer will be checked against it.
	 *
	 * This method takes workspaces into account and corrects the uid if neccessary!
	 *
	 * @param	mixed		$flexformPointer: A flexform pointer referring to the content element. Although an array is preferred, you may also pass a string which will be converted automatically by flexform_getPointerFromString()
	 * @return	mixed		The valid flexform pointer array or FALSE if it was not valid
	 * @access public
	 */
	function flexform_getValidPointer ($flexformPointer) {
		global $BE_USER;

		if (is_string($flexformPointer)) $flexformPointer = $this->flexform_getPointerFromString ($flexformPointer);

		$workspaceVersion = t3lib_BEfunc::getWorkspaceVersionOfRecord ($BE_USER->workspace, $flexformPointer['table'], $flexFormPointer['uid'], 'uid');
		if (is_array ($workspaceVersion)) {
			$flexFormPointer['uid'] = $workspaceVersion['uid'];
		}

		if (!t3lib_div::inList($this->rootTable.',tt_content',$flexformPointer['table'])) {
			if ($this->debug) t3lib_div::devLog ('flexform_getValidPointer: Table "'.$flexformPointer['table'].'" is not in the list of allowed tables!', 'TemplaVoila API', 2, $this->rootTable.',tt_content');
			return FALSE;
		}

		if (!$destinationRecord = t3lib_BEfunc::getRecordWSOL($flexformPointer['table'], $flexformPointer['uid'],'uid,pid,tx_templavoila_flex'.($flexformPointer['table'] == 'page' ? ',t3ver_swapmode':''))) {
			if ($this->debug) t3lib_div::devLog ('flexform_getValidPointer: Pointer destination record not found!', 'TemplaVoila API', 2, $flexformPointer);
			return FALSE;
		}

		if ($flexformPointer['position'] > 0) {
			$elementReferencesArr = $this->flexform_getElementReferencesFromXML ($destinationRecord['tx_templavoila_flex'], $flexformPointer);
			if (!isset ($elementReferencesArr[$flexformPointer['position']]) && $flexformPointer['position'] != -1) {
				if ($this->debug) t3lib_div::devLog ('flexform_getValidPointer: The position in the specified flexform pointer does not exist!', 'TemplaVoila API', 2, $flexformPointer);
				return FALSE;
			}
			if (isset ($flexformPointer['targetCheckUid']) && $elementReferencesArr[$flexformPointer['position']] != $flexformPointer['targetCheckUid']) {
				if ($this->debug) t3lib_div::devLog ('flexform_getValidPointer: The target record uid does not match the targetCheckUid!', 'TemplaVoila API', 2, array ($flexformPointer, $elementReferencesArr));
				return FALSE;
			}
		}

		return $flexformPointer;
	}

	/**
	 * Converts a string of the format "table:uid:sheet:sLang:field:vLang:position/targettable:targetuid" into a flexform
	 * pointer array.
	 *
	 * NOTE: "targettable" currently must be tt_content
	 *
	 * @param	string		$flexformPointerString: A string of the format "table:uid:sheet:sLang:field:vLang:position". The string may additionally contain "/table:uid" which is used to check the target record of the pointer
	 * @return	array		A flexform pointer array which can be used with the functions in tx_templavoila_api
	 * @access public
	 */
	function flexform_getPointerFromString ($flexformPointerString) {

		$tmpArr = explode ('/', $flexformPointerString);
		$locationString= $tmpArr[0];
		$targetCheckString = $tmpArr[1];

		$locationArr = explode (':', $locationString);
		$targetCheckArr = explode (':', $targetCheckArr);

		if (count($targetCheckArr) == 2) {
			$flexformPointer = array (
				'table' => $locationArr[0],
				'uid' => $locationArr[1]
			);
		} else {
			$flexformPointer = array (
				'table' => $locationArr[0],
				'uid' => $locationArr[1],
				'sheet' => $locationArr[2],
				'sLang' => $locationArr[3],
				'field' => $locationArr[4],
				'vLang' => $locationArr[5],
				'position' => $locationArr[6],
				'targetCheckUid' => $recordCheckArr[1],
			);
		}

		return $flexformPointer;
	}

	/**
	 * Converts a flexform pointer array to a string of the format "table:uid:sheet:sLang:field:vLang:position/targettable:targetuid"
	 *
	 * NOTE: "targettable" currently must be tt_content
	 *
	 * @param	array		$flexformPointer: A valid flexform pointer array
	 * @return	mixed		A string of the format "table:uid:sheet:sLang:field:vLang:position". The string might additionally contain "/table:uid" which is used to check the target record of the pointer. If an error occurs: FALSE
	 * @access public
	 */
	function flexform_getStringFromPointer ($flexformPointer) {

		if (!is_array ($flexformPointer)) return FALSE;

		if (isset ($flexformPointer['sheet'])) {
			$flexformPointerString =
				$flexformPointer['table'].':'.
				$flexformPointer['uid'].':'.
				$flexformPointer['sheet'].':'.
				$flexformPointer['sLang'].':'.
				$flexformPointer['field'].':'.
				$flexformPointer['vLang'].':'.
				$flexformPointer['position'];
			if (isset ($flexformPointer['targetCheckuid'])) {
				$flexformPointerString .= '/tt_content:'.$flexformPointer['targetCheckuid'];
			}
		} else {
			$flexformPointerString = $flexformPointer['table'].':'.$flexformPointer['uid'];
		}

		return $flexformPointerString;
	}

	/**
	 * Returns a tt_content record specified by a flexform pointer. The flexform pointer may be an
	 * array or a string. As always with flexform pointers, if only "table" and "uid" are set, it
	 * specifies the record directly, but if sheet, sLang etc. are set, it specifies the location
	 * from the perspective of the parent element.
	 *
	 * @param	mixed		$flexformPointer: A flexform pointer referring to the content element. Although an array is preferred, you may also pass a string which will be converted automatically by flexform_getPointerFromString()
	 * @return	mixed		The record row or FALSE if not successful
	 * @access public
	 */
	function flexform_getRecordByPointer ($flexformPointer) {

		if (is_string($flexformPointer)) $flexformPointer = $this->flexform_getPointerFromString ($flexformPointer);

		if (!$flexformPointer = $this->flexform_getValidPointer ($flexformPointer)) return FALSE;

		if (isset ($flexformPointer['sheet'])) {
			if (!$parentRecord = t3lib_BEfunc::getRecordWSOL($flexformPointer['table'], $flexformPointer['uid'],'uid,tx_templavoila_flex')) return FALSE;
			$elementReferencesArr = $this->flexform_getElementReferencesFromXML ($parentRecord['tx_templavoila_flex'], $flexformPointer);
			return t3lib_BEfunc::getRecordWSOL('tt_content', $elementReferencesArr[$flexformPointer['position']]);
		} else {
			return t3lib_BEfunc::getRecordWSOL('tt_content', $flexformPointer['uid']);
		}
	}

	/**
	 * Returns an array of flexform pointers pointing to all occurrences tt_content record with uid $recordUid
	 * on the page with uid $pageUid.
	 *
	 * @param	integer		$elementUid: UID of a tt_content record
	 * @param	integer		$pageUid: UID of the page to search in
	 * @return	array		Array of flexform pointers
	 * @access public
	 */
	function flexform_getPointersByRecord ($elementUid, $pageUid) {
		$flexformPointersArr = $this->flexform_getFlexformPointersToSubElementsRecursively('pages', $pageUid, $flexformPointers);

		$resultPointersArr = array();
		if (is_array ($flexformPointersArr)) {
			foreach ($flexformPointersArr as $flexformPointerArr) {
				if ($flexformPointerArr['targetCheckUid'] == $elementUid) {
					$resultPointersArr[] = $flexformPointerArr;
				}
			}
		}

		return $resultPointersArr;
	}

	/**
	 * Takes FlexForm XML content in and based on the flexform pointer it will find a list of references, parse them
	 * and return them as an array of tt_content uids.
	 *
	 * @param	string		$flexformXML: XML content of a flexform field
	 * @param	array		$flexformPointer: Pointing to a field in the XML structure to get the list of element references from.
	 * @return	mixed		Numerical array tt_content uids or FALSE if an error occurred (eg. flexformXML was no valid XML)
	 * @access public
	 */
	function flexform_getElementReferencesFromXML($flexformXML, $flexformPointer)	{

			// Getting value of the field containing the relations:
		$flexformXMLArr = t3lib_div::xml2array($flexformXML);
		if (!is_array ($flexformXMLArr) && strlen($flexformXML) > 0) {
			if ($this->debug) t3lib_div::devLog ('flexform_getReferencesToElementsFromXML: flexformXML seems to be no valid XML. Parser error message: '.$flexformXMLArr, 'TemplaVoila API', 2, $flexformXML);
			return FALSE;
		}

		$listOfUIDs = is_array ($flexformXMLArr) ? $flexformXMLArr['data'][$flexformPointer['sheet']][$flexformPointer['sLang']][$flexformPointer['field']][$flexformPointer['vLang']] : '';

			// Getting the relation uids out:
		$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
		$dbAnalysis->start($listOfUIDs, 'tt_content');
		$itemArr = $dbAnalysis->itemArray;

		$elementReferencesArr = array();
		$counter = 1;
		foreach ($itemArr as $itemKeyValuePair) {
			$elementReferencesArr[$counter] = $itemKeyValuePair['id'];
			$counter++;
		}
		return $elementReferencesArr;
	}

	/**
	 * Returns an array of uids of all sub elements of the element specified by $table and $uid.
	 *
	 * @param	string		$table: Name of the table of the parent element ('pages' or 'tt_content')
	 * @param	integer		$uid: UID of the parent element
	 * @param	array		Array of record UIDs - used internally, don't touch
	 * @return	array		Array of record UIDs
	 * @access public
	 */
	function flexform_getListOfSubElementUidsRecursively ($table, $uid, &$recordUids) {

		if (!is_array($recordUids)) $recordUids = array();
		$parentRecord = t3lib_BEfunc::getRecordWSOL($table, $uid, 'uid,pid,tx_templavoila_flex'.($table=='pages' ? ',t3ver_swapmode' : ''));
		$flexFieldArr = t3lib_div::xml2array($parentRecord['tx_templavoila_flex']);

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
													$this->flexform_getListOfSubElementUidsRecursively  ('tt_content', $subElementUid, $recordUids);
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
	 * Returns an array of flexform pointers to all sub elements of the element specified by $table and $uid.
	 *
	 * @param	string		$table: Name of the table of the parent element ('pages' or 'tt_content')
	 * @param	integer		$uid: UID of the parent element
	 * @param	array		$flexformPointers: Array of flexform pointers - used internally, don't touch
	 * @return	array		Array of flexform pointers
	 * @access public
	 */
	function flexform_getFlexformPointersToSubElementsRecursively ($table, $uid, &$flexformPointers) {

		if (!is_array($flexformPointers)) $flexformPointers = array();
		$parentRecord = t3lib_BEfunc::getRecordWSOL($table, $uid, 'uid,pid,tx_templavoila_flex'.($table=='pages' ? ',t3ver_swapmode' : ''));
		$flexFieldArr = t3lib_div::xml2array($parentRecord['tx_templavoila_flex']);

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
											$position = 1;
											foreach ($valueItems as $index => $subElementUid) {
												if ($subElementUid > 0) {
													$flexformPointers[] = array (
														'table' => $table,
														'uid' => $uid,
														'sheet' => $sheetKey,
														'sLang' => $languageKey,
														'field' => $fieldName,
														'vLang' => $valueName,
														'position' => $position,
														'targetCheckUid' => $subElementUid
													);
													$this->flexform_getFlexformPointersToSubElementsRecursively ('tt_content', $subElementUid, $flexformPointers);
													$position ++;
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

	/**
	 * Creates a new reference list (as an array) with the $elementUid inserted into the given reference list
	 *
	 * @param	array		$currentReferencesArr: Array of tt_content uids from a current reference list
	 * @param	integer		$position: Position where the new reference should be inserted: 0 = before the first element, 1 = after the first, 2 = after the second etc., -1 = insert as last element
	 * @param	integer		$elementUid: UID of a tt_content element
	 * @return	array		Array with an updated reference list
	 * @access protected
	 * @see		flexform_getElementReferencesFromXML(), flexform_removeElementReferenceFromList()
	 */
	function flexform_insertElementReferenceIntoList($currentReferencesArr, $position, $elementUid)	{

		$inserted = FALSE;
		$newReferencesArr = array();
		$counter = 1;

		if ($position == 0)	{
			$newReferencesArr[1] = $elementUid;
			$inserted = TRUE;
			$counter = 2;
		}

		if (is_array ($currentReferencesArr)) {
			foreach($currentReferencesArr as $referenceUid)	{
				$newReferencesArr[$counter] = $referenceUid;
				if ($position == $counter)	{
					$counter ++;
					$newReferencesArr[$counter] = $elementUid;
					$inserted = TRUE;
				}
				$counter ++;
			}

			if (!$inserted)	{
				$newReferencesArr[$counter] = $elementUid;
			}
		}
		return $newReferencesArr;
	}

	/**
	 * Removes the element specified by $position from the given list of references and returns
	 * the updated list. (the list is passed and return as an array)
	 *
	 * @param	array		$currentReferencesArr: Array of tt_content uids from a current reference list
	 * @param	integer		$position: Position of the element reference which should be removed. 1 = first element, 2 = second element etc.
	 * @return	array		Array with an updated reference list
	 * @access protected
	 * @see		flexform_getElementReferencesFromXML(), flexform_insertElementReferenceIntoList()
	 */
	function flexform_removeElementReferenceFromList($currentReferencesArr, $position)	{

		unset($currentReferencesArr[$position]);

		$newReferencesArr = array();
		$counter = 1;
		foreach($currentReferencesArr as $uid)	{
			$newReferencesArr[$counter] = $uid;
			$counter++;
		}

		return $newReferencesArr;
	}

	/**
	 * Updates the XML structure with the new list of references to tt_content records.
	 *
	 * @param	array		$referencesArr: The array of tt_content uids (references list) to store in the record
	 * @param	array		$destinationPointer: Flexform pointer to the location where the references list should be stored.
	 * @return	void
	 * @access protected
	 */
	function flexform_storeElementReferencesListInRecord ($referencesArr, $destinationPointer) {
		if ($this->debug) t3lib_div::devLog ('API: flexform_storeElementReferencesListInRecord()', 'templavoila', 0, array ('referencesArr' => $referencesArr, 'destinationPointer' => $destinationPointer));

		$dataArr = array();
		$uid = t3lib_beFunc::wsMapId($destinationPointer['table'],$destinationPointer['uid']);
		$dataArr[$destinationPointer['table']][$uid]['tx_templavoila_flex']['data'][$destinationPointer['sheet']][$destinationPointer['sLang']][$destinationPointer['field']][$destinationPointer['vLang']] = implode(',',$referencesArr);

		$flagWasSet = $this->getTCEmainRunningFlag();
		$this->setTCEmainRunningFlag (TRUE);
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;
		$tce->start($dataArr,array());
		$tce->process_datamap();
		if (!$flagWasSet) $this->setTCEmainRunningFlag (FALSE);
	}





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
	 * @param	array		$contextPageUid: The (current) page uid, used to determine which page datastructure is selected
	 * @param	integer		$columnPosition: Column number to search a field for
	 * @return	mixed		Either the field name relating to the given column number or FALSE if all fall back methods failed and no suitable field could be found.
	 * @access public
	 */
	function ds_getFieldNameByColumnPosition ($contextPageUid, $columnPosition) {
		global $TCA;

		$foundFieldName = FALSE;
		$columnsAndFieldNamesArr = array();
		$fieldNameOfFirstCEField = NULL;

		$pageRow = t3lib_BEfunc::getRecordWSOL('pages', $contextPageUid);
		if (!is_array ($pageRow)) return FALSE;

		$dataStructureArr = $this->ds_getExpandedDataStructure ('pages', $pageRow);

			// Traverse the data structure and search for oldStyleColumnNumber configurations:
		if (is_array ($dataStructureArr)) {
			foreach ($dataStructureArr as $sheetKey => $sheetDataStructureArr) {
				if (is_array ($sheetDataStructureArr['ROOT']['el'])) {
					foreach ($sheetDataStructureArr['ROOT']['el'] as $fieldName => $fieldConfiguration) {
						if (is_array ($fieldConfiguration)) {
							if (isset ($fieldConfiguration['tx_templavoila']['oldStyleColumnNumber'])) {
								$columnNumber = $fieldConfiguration['tx_templavoila']['oldStyleColumnNumber'];
								if (!isset ($columnsAndFieldNamesArr[$columnNumber])) {
									$columnsAndFieldNamesArr[$columnNumber] = $fieldName;
								}
							}
							if ($fieldConfiguration['tx_templavoila']['eType'] == 'ce' && !isset ($fieldNameOfFirstCEField)) {
								$fieldNameOfFirstCEField = $fieldName;
							}
						}
					}
				}
			}
		}

			// Let's see what we have found:
		if (isset ($columnsAndFieldNamesArr[$columnPosition])) {
			$foundFieldName = $columnsAndFieldNamesArr[$columnPosition];
		} elseif (isset ($columnsAndFieldNamesArr[0])) {
			$foundFieldName = $columnsAndFieldNamesArr[0];
		} elseif (isset ($fieldNameOfFirstCEField)) {
			$foundFieldName = $fieldNameOfFirstCEField;
		}

		return $foundFieldName;
	}

	/**
	 * Returns the data structure for a flexform field ("tx_templavoila_flex") from $table (by using $row). The DS will
	 * be expanded, ie. you can be sure that it is structured by sheets even if only one sheet exists.
	 *
	 * @param	string		The table name, usually "pages" or "tt_content"
	 * @param	array		The data row (used to get DS if DS is dependant on the data in the record)
	 * @return	array		The data structure, expanded for all sheets inside.
	 * @access public
	 */
	function ds_getExpandedDataStructure ($table, $row) {
		global $TCA;

		t3lib_div::loadTCA ($table);
		$conf = $TCA[$table]['columns']['tx_templavoila_flex']['config'];
		$dataStructureArr = t3lib_BEfunc::getFlexFormDS($conf, $row, $table);

		$expandedDataStructureArr = array();
		if (!is_array ($dataStructureArr)) $dataStructureArr = array();

		if (is_array($dataStructureArr['sheets']))	{
			foreach ($dataStructureArr['sheets'] as $sheetKey => $sheetInfo)	{
				list ($sheetDataStructureArr, $sheet) = t3lib_div::resolveSheetDefInDS($dataStructureArr, $sheetKey);
				if ($sheet == $sheetKey)	{
					$expandedDataStructureArr[$sheetKey] = $sheetDataStructureArr;
				}
			}
		} else {
			$sheetKey='sDEF';
			list ($sheetDataStructureArr, $sheet) = t3lib_div::resolveSheetDefInDS($dataStructureArr, $sheetKey);
			if ($sheet == $sheetKey)	{
				$expandedDataStructureArr[$sheetKey] = $sheetDataStructureArr;
			}
		}
		return $expandedDataStructureArr;
	}

	/**
	 * Returns an array of available Template Object records from the scope of the given page.
	 *
	 * Note: All TO records which are found in the selected storage folder will be returned, no matter
	 *       if they match the currently selected data structure for the given page.
	 *
	 * @param	integer		$pageUid: (current) page uid, used for finding the correct storage folder
	 * @return	mixed		Array of Template Object records or FALSE if an error occurred.
	 * @access public
	 */
	function ds_getAvailableTORecords ($pageUid) {
		global $TYPO3_DB;

		$storageFolderPID = $this->getStorageFolderPid ($pageUid);

		$tTO = 'tx_templavoila_tmplobj';
		$tDS = 'tx_templavoila_datastructure';
		$res = $TYPO3_DB->exec_SELECTquery (
			"$tTO.*",
			"$tTO LEFT JOIN $tDS ON $tTO.datastructure = $tDS.uid",
			"$tTO.pid=".intval($storageFolderPID)." AND $tDS.scope=1".
				t3lib_befunc::deleteClause ($tTO).t3lib_befunc::deleteClause ($tDS).
				t3lib_BEfunc::versioningPlaceholderClause($tTO).t3lib_BEfunc::versioningPlaceholderClause($tDS)
		);
		if (!$res) return FALSE;

		$templateObjectRecord = array();
		while ($row = $TYPO3_DB->sql_fetch_assoc($res))	{
			$templateObjectRecords[$row['uid']] = $row;
		}

		return $templateObjectRecords;
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
	 * @param	boolean		$flag: If TRUE, our user functions will be omitted
	 * @return	void
	 * @access	protected
	 */
	function setTCEmainRunningFlag ($flag) {
		$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_api']['apiIsRunningTCEmain'] = $flag;
	}

	/**
	 * Returns the current flag which tells TemplaVoila TCEmain userfunctions if this API has called a TCEmain
	 * function. If this flag is set, the TemplaVoila TCEmain userfunctions will be skipped to
	 * avoid infinite loops and other bad effects.
	 *
	 * @return	boolean		TRUE if flag is set, otherwise FALSE;
	 * @access	protected
	 */
	function getTCEmainRunningFlag () {
		return $GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_api']['apiIsRunningTCEmain'] ? TRUE : FALSE;
	}

	/**
	 * Returns the page uid of the selected storage folder from the context of the given page uid.
	 *
	 * @param	integer		$pageUid: Context page uid
	 * @return	integer		PID of the storage folder
	 * @access	public
	 */
	function getStorageFolderPid($pageUid)	{

			// Negative PID values is pointing to a page on the same level as the current.
		if ($pageUid < 0) {
			$pidRow = t3lib_BEfunc::getRecordWSOL('pages',abs($pageUid),'pid');
			$pageUid = $pidRow['pid'];
		}
		$row = t3lib_BEfunc::getRecordWSOL('pages',$pageUid);

		$TSconfig = t3lib_BEfunc::getTCEFORM_TSconfig('pages', $row);
		return intval($TSconfig['_STORAGE_PID']);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_api.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_api.php']);
}

?>