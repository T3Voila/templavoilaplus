<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Dmitry Dulepov <dmitry@typo3.org>
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * $Id$
 */


/**
 * This class produces a code for text, text with images, images, form, etc
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_templavoila
 */

class tx_templavoila_fcetab extends tx_templavoila_baseTab {

	function getTabContent() {
		$elements = array();

		$rows = $this->getFCErecords();
		foreach ($rows as $row) {
			if ($row['previewicon']) {
				$image = $GLOBALS['BACK_PATH'] . '../uploads/tx_templavoila/' .
					$row['previewicon'];
			}
			else {
				$image = '../' . t3lib_extMgm::siteRelPath('templavoila') . 'icon_fce_ce.png';
			}
			$this->createAndAddElement($elements,
				$image,
				$row['title'],
				$row['description'],
				'templavoila_pi1',
				'&amp;defVals[tt_content][CType]=templavoila_pi1&amp;defVals[tt_content][tx_templavoila_ds]=' .
					$row['datastructure'] . '&amp;defVals[tt_content][tx_templavoila_to]=' .
					$row['uid']
			);
		}

		return $this->render($elements);
	}

	protected function getFCErecords() {
		$positionPid = $this->pObj->getId();
		$dataStructureRecords = array();
		$storageFolderPID = $this->pObj->getApiObj()->getStorageFolderPid($positionPid);

			// Fetch data structures stored in the database:
		$addWhere = $this->buildRecordWhere('tx_templavoila_datastructure');
		$dataStructureRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_templavoila_datastructure',
			'pid='.intval($storageFolderPID).' AND scope=2' . $addWhere .
				t3lib_BEfunc::deleteClause('tx_templavoila_datastructure').
				t3lib_BEfunc::versioningPlaceholderClause('tx_templavoila_datastructure'),
				'', '', '', 'uid'
		);
/*
			// Fetch static data structures which are stored in XML files:
		if (is_array($GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoila_cm1']['staticDataStructures']))	{
			foreach($GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoila_cm1']['staticDataStructures'] as $staticDataStructureArr)	{
				$staticDataStructureArr['_STATIC'] = TRUE;
				$dataStructureRecords[$staticDataStructureArr['path']] = $staticDataStructureArr;
			}
		}
*/
			// Fetch all template object records which uare based one of the previously fetched data structures:
		$templateObjectRecords = array();
		$addWhere = $this->buildRecordWhere('tx_templavoila_tmplobj');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_templavoila_tmplobj',
			'pid='.intval($storageFolderPID).' AND parent=0' . $addWhere .
				t3lib_BEfunc::deleteClause('tx_templavoila_tmplobj').
				t3lib_BEfunc::versioningPlaceholderClause('tx_templavoila_tmpl'), '', 'sorting'
		);
		while (false !== ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			if (is_array($dataStructureRecords[$row['datastructure']])) {
				$templateObjectRecords[] = $row;
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return $templateObjectRecords;
	}


	/**
	 * Create sql condition for given table to limit records according to user access.
	 *
	 * @param	string	$table	Table nme to fetch records from
	 * @return	string	Condition or empty string
	 */
	function buildRecordWhere($table) {
		$result = array();
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$prefLen = strlen($table) + 1;
			foreach($GLOBALS['BE_USER']->userGroups as $group) {
				$items = t3lib_div::trimExplode(',', $group['tx_templavoila_access'], 1);
				foreach ($items as $ref) {
					if (strstr($ref, $table)) {
						$result[] = intval(substr($ref, $prefLen));
					}
				}
			}
		}
		return (count($result) > 0 ? ' AND uid NOT IN (' . implode(',', $result) . ') ' : '');
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/tabs/class.tx_templavoila_fcetab.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/tabs/class.tx_templavoila_fcetab.php']);
}

?>