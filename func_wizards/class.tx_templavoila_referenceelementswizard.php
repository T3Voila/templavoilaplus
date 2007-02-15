<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006  Robert Lemke (robert@typo3.org)
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
 * Reference elements wizard,
 * References all unused elements in a treebranch to a specific point in the TV-DS
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
 *   53: class tx_templavoila_referenceElementsWizard extends t3lib_extobjbase
 *   60:     function modMenu()
 *   79:     function main()
 *  178:     function createReferencesForTree($tree)
 *  192:     function createReferencesForPage($pageUid)
 *  222:     function getUnreferencedElementsRecords($pid)
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_t3lib.'class.t3lib_pagetree.php');
require_once(PATH_t3lib.'class.t3lib_extobjbase.php');
require_once(t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_api.php');

class tx_templavoila_referenceElementsWizard extends t3lib_extobjbase {

	/**
	 * Returns the menu array
	 *
	 * @return	array
	 */
	function modMenu()	{
		global $LANG;

		return array (
			'depth' => array(
				0 => $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.depth_0'),
				1 => $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.depth_1'),
				2 => $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.depth_2'),
				3 => $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.depth_3'),
			)
		);
	}

	/**
	 * Main function
	 *
	 * @return	string		Output HTML for the module.
	 * @access	public
	 */
	function main()	{
		global $BACK_PATH,$LANG,$SOBE, $BE_USER, $TYPO3_DB;

		$output = '';
		$this->templavoilaAPIObj = t3lib_div::makeInstance ('tx_templavoila_api');

			// Showing the tree:
			// Initialize starting point of page tree:
		$treeStartingPoint = intval($this->pObj->id);
		$treeStartingRecord = t3lib_BEfunc::getRecord('pages', $treeStartingPoint);
		$depth = $this->pObj->MOD_SETTINGS['depth'];

			// Initialize tree object:
		$tree = t3lib_div::makeInstance('t3lib_pageTree');
		$tree->init('AND '.$GLOBALS['BE_USER']->getPagePermsClause(1));

			// Creating top icon; the current page
		$HTML = t3lib_iconWorks::getIconImage('pages', $treeStartingRecord, $GLOBALS['BACK_PATH'],'align="top"');
		$tree->tree[] = array(
			'row' => $treeStartingRecord,
			'HTML' => $HTML
		);

			// Create the tree from starting point:
		if ($depth>0)	{
			$tree->getTree($treeStartingPoint, $depth, '');
		}

			// Set CSS styles specific for this document:
		$this->pObj->content = str_replace('/*###POSTCSSMARKER###*/','
			TABLE.c-list TR TD { white-space: nowrap; vertical-align: top; }
		',$this->pObj->content);

			// Process commands:
		if (t3lib_div::_GP('createReferencesForPage')) $this->createReferencesForPage(t3lib_div::_GP('createReferencesForPage'));
		if (t3lib_div::_GP('createReferencesForTree')) $this->createReferencesForTree($tree);

			// Traverse tree:
		$output = '';
		$counter = 0;
		foreach($tree->tree as $row)	{
			$unreferencedElementRecordsArr = $this->getUnreferencedElementsRecords($row['row']['uid']);

			if (count($unreferencedElementRecordsArr)) {
				$createReferencesLink = '<a href="index.php?id='.$this->pObj->id.'&createReferencesForPage='.$row['row']['uid'].'">Reference elements</a>';
			} else {
				$createReferencesLink = '';
			}

			$rowTitle = $row['HTML'].t3lib_BEfunc::getRecordTitle('pages',$row['row'],TRUE);
			$cellAttrib = ($row['row']['_CSSCLASS'] ? ' class="'.$row['row']['_CSSCLASS'].'"' : '');

			$tCells = array();
			$tCells[]='<td nowrap="nowrap"'.$cellAttrib.'>'.$rowTitle.'</td>';
			$tCells[]='<td>'.count($unreferencedElementRecordsArr).'</td>';
			$tCells[]='<td nowrap="nowrap">'.$createReferencesLink.'</td>';

			$output .= '
				<tr class="bgColor'.($counter%2 ? '-20':'-10').'">
					'.implode('
					',$tCells).'
				</tr>';

			$counter++;
		}

			// Create header:
		$tCells = array();
		$tCells[]='<td>Page:</td>';
		$tCells[]='<td>No. of unreferenced elements:</td>';
		$tCells[]='<td>&nbsp;</td>';

			// Depth selector:
		$depthSelectorBox = t3lib_BEfunc::getFuncMenu($this->pObj->id,'SET[depth]',$this->pObj->MOD_SETTINGS['depth'],$this->pObj->MOD_MENU['depth'],'index.php');

		$finalOutput = '
			<br />
			'.$depthSelectorBox.'
			<a href="index.php?id='.$this->pObj->id.'&createReferencesForTree=1">Reference elements for whole tree</a><br />
			<br />
			<table border="0" cellspacing="1" cellpadding="0" class="lrPadding c-list">
				<tr class="bgColor5 tableheader">
					'.implode('
					',$tCells).'
				</tr>'.
				$output.'
			</table>
		';

		return $finalOutput;
	}

	/**
	 * References all unreferenced elements in the given page tree
	 *
	 * @param	array		$tree: Page tree array
	 * @return	void
	 * @access	protected
	 */
	function createReferencesForTree($tree) {
		foreach($tree->tree as $row)	{
			$this->createReferencesForPage($row['row']['uid']);
		}
	}

	/**
	 * References all unreferenced elements with the specified
	 * parent id (page uid)
	 *
	 * @param	integer		$pageUid: Parent id of the elements to reference
	 * @return	void
	 * @access	protected
	 */
	function createReferencesForPage($pageUid) {

		$unreferencedElementRecordsArr = $this->getUnreferencedElementsRecords($pageUid);
		foreach ($unreferencedElementRecordsArr as $elementUid => $elementRecord) {

			$contentAreaFieldName = $this->templavoilaAPIObj->ds_getFieldNameByColumnPosition($pageUid, $elementRecord['colpos']);
			if ($contentAreaFieldName !== FALSE) {
				$destinationPointer = array (
					'table' => 'pages',
					'uid' => $pageUid,
					'sheet' => 'sDEF',
					'sLang' => 'lDEF',
					'field' => $contentAreaFieldName,
					'vLang' => 'vDEF',
					'position' => -1
				);

				$this->templavoilaAPIObj->referenceElementByUid ($elementUid, $destinationPointer);
			}
		}
	}

	/**
	 * Returns an array of tt_content records which are not referenced on
	 * the page with the given uid (= parent id).
	 *
	 * @param	integer		$pid: Parent id of the content elements (= uid of the page)
	 * @return	array		Array of tt_content records with the following fields: uid, header, bodytext, sys_language_uid and colpos
	 * @access	protected
	 */
	function getUnreferencedElementsRecords($pid) {
		global $TYPO3_DB;

		$elementRecordsArr = array();
		$referencedElementsArr = $this->templavoilaAPIObj->flexform_getListOfSubElementUidsRecursively ('pages', $pid, $dummyArr=array());

		$res = $TYPO3_DB->exec_SELECTquery (
			'uid, header, bodytext, sys_language_uid, colPos',
			'tt_content',
			'pid='.intval($pid).
				(count($referencedElementsArr) ? ' AND uid NOT IN ('.implode(',',$referencedElementsArr).')' : '').
				' AND t3ver_wsid='.intval($BE_USER->workspace).
				t3lib_BEfunc::deleteClause('tt_content').
				t3lib_BEfunc::versioningPlaceholderClause('tt_content'),
			'',
			'sorting'
		);

		if ($res) {
			while(($elementRecordArr = $TYPO3_DB->sql_fetch_assoc($res)) !== FALSE) {
				$elementRecordsArr[$elementRecordArr['uid']] = $elementRecordArr;
			}
		}
		return $elementRecordsArr;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/func_wizards/class.tx_templavoila_referenceelementswizard.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/func_wizards/class.tx_templavoila_referenceelementswizard.php']);
}


?>