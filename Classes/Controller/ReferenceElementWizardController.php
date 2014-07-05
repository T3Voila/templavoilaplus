<?php
namespace Extension\Templavoila\Controller;

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
 * @author Robert Lemke <robert@typo3.org>
 */
class ReferenceElementWizardController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {

	/**
	 * @var array
	 */
	protected $modSharedTSconfig;

	/**
	 * @var array
	 */
	protected $allAvailableLanguages;

	/**
	 * @var \Extension\Templavoila\Service\ApiService
	 */
	protected $templavoilaAPIObj;

	/**
	 * Returns the menu array
	 *
	 * @return array
	 */
	function modMenu() {
		global $LANG;

		return array(
			'depth' => array(
				0 => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL('LLL:EXT:lang/locallang_core.php:labels.depth_0'),
				1 => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL('LLL:EXT:lang/locallang_core.php:labels.depth_1'),
				2 => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL('LLL:EXT:lang/locallang_core.php:labels.depth_2'),
				3 => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL('LLL:EXT:lang/locallang_core.php:labels.depth_3'),
				999 => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL('LLL:EXT:lang/locallang_core.php:labels.depth_infi'),
			)
		);
	}

	/**
	 * Main function
	 *
	 * @return string Output HTML for the module.
	 * @access public
	 */
	function main() {
		global $BACK_PATH, $LANG, $SOBE, $BE_USER, $TYPO3_DB;

		$this->modSharedTSconfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig($this->pObj->id, 'mod.SHARED');
		$this->allAvailableLanguages = $this->getAvailableLanguages(0, TRUE, TRUE, TRUE);

		$output = '';
		$this->templavoilaAPIObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Extension\\Templavoila\\Service\\ApiService');

		// Showing the tree:
		// Initialize starting point of page tree:
		$treeStartingPoint = intval($this->pObj->id);
		$treeStartingRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $treeStartingPoint);
		$depth = $this->pObj->MOD_SETTINGS['depth'];

		// Initialize tree object:
		/** @var \TYPO3\CMS\Backend\Tree\View\PageTreeView $tree */
		$tree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\View\\PageTreeView');
		$tree->init('AND ' . \Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->getPagePermsClause(1));

		// Creating top icon; the current page
		$HTML = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', $treeStartingRecord);
		$tree->tree[] = array(
			'row' => $treeStartingRecord,
			'HTML' => $HTML
		);

		// Create the tree from starting point:
		if ($depth > 0) {
			$tree->getTree($treeStartingPoint, $depth, '');
		}

		// Set CSS styles specific for this document:
		$this->pObj->content = str_replace('/*###POSTCSSMARKER###*/', '
			TABLE.c-list TR TD { white-space: nowrap; vertical-align: top; }
		', $this->pObj->content);

		// Process commands:
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('createReferencesForPage')) {
			$this->createReferencesForPage(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('createReferencesForPage'));
		}
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('createReferencesForTree')) {
			$this->createReferencesForTree($tree);
		}

		// Traverse tree:
		$output = '';
		$counter = 0;
		foreach ($tree->tree as $row) {
			$unreferencedElementRecordsArr = $this->getUnreferencedElementsRecords($row['row']['uid']);

			if (count($unreferencedElementRecordsArr)) {
				$createReferencesLink = '<a href="index.php?id=' . $this->pObj->id . '&createReferencesForPage=' . $row['row']['uid'] . '">Reference elements</a>';
			} else {
				$createReferencesLink = '';
			}

			$rowTitle = $row['HTML'] . \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle('pages', $row['row'], TRUE);
			$cellAttrib = ($row['row']['_CSSCLASS'] ? ' class="' . $row['row']['_CSSCLASS'] . '"' : '');

			$tCells = array();
			$tCells[] = '<td nowrap="nowrap"' . $cellAttrib . '>' . $rowTitle . '</td>';
			$tCells[] = '<td>' . count($unreferencedElementRecordsArr) . '</td>';
			$tCells[] = '<td nowrap="nowrap">' . $createReferencesLink . '</td>';

			$output .= '
				<tr class="bgColor' . ($counter % 2 ? '-20' : '-10') . '">
					' . implode('
					', $tCells) . '
				</tr>';

			$counter++;
		}

		// Create header:
		$tCells = array();
		$tCells[] = '<td>Page:</td>';
		$tCells[] = '<td>No. of unreferenced elements:</td>';
		$tCells[] = '<td>&nbsp;</td>';

		// Depth selector:
		$depthSelectorBox = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu($this->pObj->id, 'SET[depth]', $this->pObj->MOD_SETTINGS['depth'], $this->pObj->MOD_MENU['depth'], 'index.php');

		$finalOutput = '
			<br />
			' . $depthSelectorBox . '
			<a href="index.php?id=' . $this->pObj->id . '&createReferencesForTree=1">Reference elements for whole tree</a><br />
			<br />
			<table border="0" cellspacing="1" cellpadding="0" class="lrPadding c-list">
				<tr class="bgColor5 tableheader">
					' . implode('
					', $tCells) . '
				</tr>' .
			$output . '
			</table>
		';

		return $finalOutput;
	}

	/**
	 * References all unreferenced elements in the given page tree
	 *
	 * @param \TYPO3\CMS\Backend\Tree\View\PageTreeView $tree : Page tree array
	 *
	 * @return void
	 * @access protected
	 */
	function createReferencesForTree($tree) {
		foreach ($tree->tree as $row) {
			$this->createReferencesForPage($row['row']['uid']);
		}
	}

	/**
	 * References all unreferenced elements with the specified
	 * parent id (page uid)
	 *
	 * @param integer $pageUid : Parent id of the elements to reference
	 *
	 * @return void
	 * @access protected
	 */
	function createReferencesForPage($pageUid) {

		$unreferencedElementRecordsArr = $this->getUnreferencedElementsRecords($pageUid);
		$langField = $GLOBALS['TCA']['tt_content']['ctrl']['languageField'];
		foreach ($unreferencedElementRecordsArr as $elementUid => $elementRecord) {
			$lDef = array();
			$vDef = array();
			if ($langField && $elementRecord[$langField]) {
				$pageRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('pages', $pageUid);
				$xml = \TYPO3\CMS\Backend\Utility\BackendUtility::getFlexFormDS($GLOBALS['TCA']['pages']['columns']['tx_templavoila_flex']['config'], $pageRec, 'pages', 'tx_templavoila_ds');
				$langChildren = intval($xml['meta']['langChildren']);
				$langDisable = intval($xml['meta']['langDisable']);
				if ($elementRecord[$langField] == -1) {
					$translatedLanguagesArr = $this->getAvailableLanguages($pageUid);
					foreach ($translatedLanguagesArr as $lUid => $lArr) {
						if ($lUid >= 0) {
							$lDef[] = $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l' . $lArr['ISOcode']);
							$vDef[] = $langDisable ? 'vDEF' : ($langChildren ? 'v' . $lArr['ISOcode'] : 'vDEF');
						}
					}
				} elseif ($rLang = $this->allAvailableLanguages[$elementRecord[$langField]]) {
					$lDef[] = $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l' . $rLang['ISOcode']);
					$vDef[] = $langDisable ? 'vDEF' : ($langChildren ? 'v' . $rLang['ISOcode'] : 'vDEF');
				} else {
					$lDef[] = 'lDEF';
					$vDef[] = 'vDEF';
				}
			} else {
				$lDef[] = 'lDEF';
				$vDef[] = 'vDEF';
			}
			$contentAreaFieldName = $this->templavoilaAPIObj->ds_getFieldNameByColumnPosition($pageUid, $elementRecord['colPos']);
			if ($contentAreaFieldName !== FALSE) {
				foreach ($lDef as $iKey => $lKey) {
					$vKey = $vDef[$iKey];
					$destinationPointer = array(
						'table' => 'pages',
						'uid' => $pageUid,
						'sheet' => 'sDEF',
						'sLang' => $lKey,
						'field' => $contentAreaFieldName,
						'vLang' => $vKey,
						'position' => -1
					);

					$this->templavoilaAPIObj->referenceElementByUid($elementUid, $destinationPointer);
				}
			}
		}
	}

	/**
	 * Returns an array of tt_content records which are not referenced on
	 * the page with the given uid (= parent id).
	 *
	 * @param integer $pid : Parent id of the content elements (= uid of the page)
	 *
	 * @return array Array of tt_content records with the following fields: uid, header, bodytext, sys_language_uid and colpos
	 * @access protected
	 */
	function getUnreferencedElementsRecords($pid) {

		$elementRecordsArr = array();
		$referencedElementsArr = $this->templavoilaAPIObj->flexform_getListOfSubElementUidsRecursively('pages', $pid, $dummyArr = array());

		$res = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTquery(
			'uid, header, bodytext, sys_language_uid, colPos',
			'tt_content',
			'pid=' . intval($pid) .
			(count($referencedElementsArr) ? ' AND uid NOT IN (' . implode(',', $referencedElementsArr) . ')' : '') .
			' AND t3ver_wsid=' . intval(\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->workspace) .
			' AND l18n_parent=0' .
			\TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tt_content') .
			\TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('tt_content'),
			'',
			'sorting'
		);

		if ($res) {
			while (($elementRecordArr = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_fetch_assoc($res)) !== FALSE) {
				$elementRecordsArr[$elementRecordArr['uid']] = $elementRecordArr;
			}
		}

		return $elementRecordsArr;
	}

	/**
	 * @param integer $id
	 * @param boolean $onlyIsoCoded
	 * @param boolean $setDefault
	 * @param boolean $setMulti
	 *
	 * @return array
	 */
	function getAvailableLanguages($id = 0, $onlyIsoCoded = TRUE, $setDefault = TRUE, $setMulti = FALSE) {
		global $LANG, $TYPO3_DB, $BE_USER, $TCA, $BACK_PATH;

		$flagAbsPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($TCA['sys_language']['columns']['flag']['config']['fileFolder']);
		$flagIconPath = $BACK_PATH . '../' . substr($flagAbsPath, strlen(PATH_site));

		$output = array();
		$excludeHidden = \Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->isAdmin() ? '1' : 'sys_language.hidden=0';

		if ($id) {
			$excludeHidden .= ' AND pages_language_overlay.deleted=0';
			$res = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTquery(
				'DISTINCT sys_language.*',
				'pages_language_overlay,sys_language',
				'pages_language_overlay.sys_language_uid=sys_language.uid AND pages_language_overlay.pid=' . intval($id) . ' AND ' . $excludeHidden,
				'',
				'sys_language.title'
			);
		} else {
			$res = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTquery(
				'sys_language.*',
				'sys_language',
				$excludeHidden,
				'',
				'sys_language.title'
			);
		}

		if ($setDefault) {
			$output[0] = array(
				'uid' => 0,
				'title' => strlen($this->modSharedTSconfig['properties']['defaultLanguageLabel']) ? $this->modSharedTSconfig['properties']['defaultLanguageLabel'] : \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('defaultLanguage'),
				'ISOcode' => 'DEF',
				'flagIcon' => strlen($this->modSharedTSconfig['properties']['defaultLanguageFlag']) ? $this->modSharedTSconfig['properties']['defaultLanguageFlag'] : NULL
			);
		}

		if ($setMulti) {
			$output[-1] = array(
				'uid' => -1,
				'title' => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('multipleLanguages'),
				'ISOcode' => 'DEF',
				'flagIcon' => 'multiple',
			);
		}

		while (TRUE == ($row = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
			\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('sys_language', $row);
			$output[$row['uid']] = $row;

			if ($row['static_lang_isocode']) {
				$staticLangRow = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('static_languages', $row['static_lang_isocode'], 'lg_iso_2');
				if ($staticLangRow['lg_iso_2']) {
					$output[$row['uid']]['ISOcode'] = $staticLangRow['lg_iso_2'];
				}
			}
			if (strlen($row['flag'])) {
				$output[$row['uid']]['flagIcon'] = $row['flag'];
			}

			if ($onlyIsoCoded && !$output[$row['uid']]['ISOcode']) {
				unset($output[$row['uid']]);
			}
		}

		return $output;
	}
}
