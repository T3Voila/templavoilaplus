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
				999 => $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.depth_infi'),
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

		$this->modSharedTSconfig = t3lib_BEfunc::getModTSconfig($this->pObj->id, 'mod.SHARED');
		$this->allAvailableLanguages = $this->getAvailableLanguages(0, true, true, true);

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
		$HTML = t3lib_iconWorks::getSpriteIconForRecord('pages', $treeStartingRecord);
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
		$langField = $GLOBALS['TCA']['tt_content']['ctrl']['languageField'];
		foreach ($unreferencedElementRecordsArr as $elementUid => $elementRecord) {
			$lDef = array();
			$vDef = array();
			if ($langField && $elementRecord[$langField])	{
				$pageRec = t3lib_BEfunc::getRecordWSOL('pages', $pageUid);
				$xml = t3lib_BEfunc::getFlexFormDS($GLOBALS['TCA']['pages']['columns']['tx_templavoila_flex']['config'], $pageRec, 'pages', 'tx_templavoila_ds');
				$langChildren = intval($xml['meta']['langChildren']);
				$langDisable = intval($xml['meta']['langDisable']);
				if ($elementRecord[$langField]==-1)	{
					$translatedLanguagesArr = $this->getAvailableLanguages($pageUid);
					foreach ($translatedLanguagesArr as $lUid => $lArr)	{
						if ($lUid>=0)	{
							$lDef[] = $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l'.$lArr['ISOcode']);
							$vDef[]= $langDisable ? 'vDEF' : ($langChildren ? 'v'.$lArr['ISOcode']: 'vDEF');
						}
					}
				} elseif ($rLang = $this->allAvailableLanguages[$elementRecord[$langField]])	{
					$lDef[] = $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l'.$rLang['ISOcode']);
					$vDef[]= $langDisable ? 'vDEF' : ($langChildren ? 'v'.$rLang['ISOcode']: 'vDEF');
				} else	{
					$lDef[] = 'lDEF';
					$vDef[] = 'vDEF';
				}
			} else	{
				$lDef[] = 'lDEF';
				$vDef[] = 'vDEF';
			}
			$contentAreaFieldName = $this->templavoilaAPIObj->ds_getFieldNameByColumnPosition($pageUid, $elementRecord['colPos']);
			if ($contentAreaFieldName !== FALSE) {
				foreach ($lDef as $iKey => $lKey)	{
					$vKey = $vDef[$iKey];
					$destinationPointer = array (
						'table' => 'pages',
						'uid' => $pageUid,
						'sheet' => 'sDEF',
						'sLang' => $lKey,
						'field' => $contentAreaFieldName,
						'vLang' => $vKey,
						'position' => -1
					);

					$this->templavoilaAPIObj->referenceElementByUid ($elementUid, $destinationPointer);
				}
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


	function getAvailableLanguages($id=0, $onlyIsoCoded=true, $setDefault=true, $setMulti=false)	{
		global $LANG, $TYPO3_DB, $BE_USER, $TCA, $BACK_PATH;

		t3lib_div::loadTCA ('sys_language');
		$flagAbsPath = t3lib_div::getFileAbsFileName($TCA['sys_language']['columns']['flag']['config']['fileFolder']);
		$flagIconPath = $BACK_PATH.'../'.substr($flagAbsPath, strlen(PATH_site));

		$output = array();
		$excludeHidden = $BE_USER->isAdmin() ? '1' : 'sys_language.hidden=0';

		if ($id)	{
			$excludeHidden .= ' AND pages_language_overlay.deleted=0';
			$res = $TYPO3_DB->exec_SELECTquery(
				'DISTINCT sys_language.*',
				'pages_language_overlay,sys_language',
				'pages_language_overlay.sys_language_uid=sys_language.uid AND pages_language_overlay.pid='.intval($id).' AND '.$excludeHidden,
				'',
				'sys_language.title'
			);
		} else {
			$res = $TYPO3_DB->exec_SELECTquery(
				'sys_language.*',
				'sys_language',
				$excludeHidden,
				'',
				'sys_language.title'
			);
		}

		if ($setDefault) {
			$output[0]=array(
				'uid' => 0,
				'title' => strlen ($this->modSharedTSconfig['properties']['defaultLanguageLabel']) ? $this->modSharedTSconfig['properties']['defaultLanguageLabel'] : $LANG->getLL('defaultLanguage'),
				'ISOcode' => 'DEF',
				'flagIcon' => strlen($this->modSharedTSconfig['properties']['defaultLanguageFlag']) ? $this->modSharedTSconfig['properties']['defaultLanguageFlag'] : null
			);
		}

		if ($setMulti) {
			$output[-1]=array(
				'uid' => -1,
				'title' => $LANG->getLL ('multipleLanguages'),
				'ISOcode' => 'DEF',
				'flagIcon' => 'multiple',
			);
		}

		while(TRUE == ($row = $TYPO3_DB->sql_fetch_assoc($res)))	{
			t3lib_BEfunc::workspaceOL('sys_language', $row);
			$output[$row['uid']]=$row;

			if ($row['static_lang_isocode'])	{
				$staticLangRow = t3lib_BEfunc::getRecord('static_languages',$row['static_lang_isocode'],'lg_iso_2');
				if ($staticLangRow['lg_iso_2']) {
					$output[$row['uid']]['ISOcode'] = $staticLangRow['lg_iso_2'];
				}
			}
		if (strlen ($row['flag'])) {
				$output[$row['uid']]['flagIcon'] = $row['flag'];
			}

			if ($onlyIsoCoded && !$output[$row['uid']]['ISOcode']) unset($output[$row['uid']]);
		}

		return $output;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/func_wizards/class.tx_templavoila_referenceelementswizard.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/func_wizards/class.tx_templavoila_referenceelementswizard.php']);
}


?>