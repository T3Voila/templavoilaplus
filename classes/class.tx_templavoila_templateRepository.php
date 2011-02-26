<?php
/***************************************************************
* Copyright notice
*
* (c) 2010 Tolleiv Nietsch <nietsch@aoemedia.de>
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

/**
 * Class to provide unique access to datastructure
 *
 * @author	Tolleiv Nietsch <nietsch@aoemedia.de>
 */
class tx_templavoila_templateRepository {

	/**
	 * Retrieve a single templateobject by uid or xml-file path
	 *
	 * @param integer $uid
	 * @return tx_templavoila_template
	 */
	public function getTemplateByUid($uid) {
		return t3lib_div::makeInstance('tx_templavoila_template', $uid);
	}

	/**
	 * Retrieve template objects which are related to a specific datastructure
	 *
	 * @param tx_templavoila_datastructure
	 * @param integer $pid
	 * @return array
	 */
	public function getTemplatesByDatastructure(tx_templavoila_datastructure $ds, $storagePid = 0) {
		$toList = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows (
			'tx_templavoila_tmplobj.uid',
			'tx_templavoila_tmplobj',
			'tx_templavoila_tmplobj.datastructure=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($ds->getKey(), 'tx_templavoila_tmplobj')
				. (intval($storagePid) > 0 ? ' AND tx_templavoila_tmplobj.pid = ' . intval($storagePid) : '')
				. t3lib_BEfunc::deleteClause('tx_templavoila_tmplobj')
				. ' AND pid!=-1 '
				. t3lib_BEfunc::versioningPlaceholderClause('tx_templavoila_tmplobj')
		);
		$toCollection = array();
		foreach ($toList as $toRec) {
			$toCollection[] = $this->getTemplateByUid($toRec['uid']);
		}
		usort($toCollection, array($this, 'sortTemplates'));
		return $toCollection;
	}

	/**
	 * Retrieve template objects with a certain scope within the given storage folder
	 *
	 * @param integer $pid
	 * @param integer $scope
	 * @return array
	 */
	public function getTemplatesByStoragePidAndScope($storagePid, $scope) {
		$dsRepo = t3lib_div::makeInstance('tx_templavoila_datastructureRepository');
		$dsList = $dsRepo->getDatastructuresByStoragePidAndScope($storagePid, $scope);
		$toCollection = array();
		foreach($dsList as $dsObj) {
			$toCollection = array_merge($toCollection, $this->getTemplatesByDatastructure($dsObj, $storagePid));
		}
		usort($toCollection, array($this, 'sortTemplates'));
		return $toCollection;
	}

	/**
	 * Retrieve template objects which have a specific template as their parent
	 *
	 * @param tx_templavoila_datastructure
	 * @param integer $pid
	 * @return array
	 */
	public function getTemplatesByParentTemplate(tx_templavoila_template $to, $storagePid=0) {
		$toList = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows (
			'tx_templavoila_tmplobj.uid',
			'tx_templavoila_tmplobj',
			'tx_templavoila_tmplobj.parent=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($to->getKey(), 'tx_templavoila_tmplobj')
				. (intval($storagePid) > 0 ? ' AND tx_templavoila_tmplobj.pid = ' . intval($storagePid) : ' AND pid!=-1')
				. t3lib_BEfunc::deleteClause('tx_templavoila_tmplobj')
				. t3lib_BEfunc::versioningPlaceholderClause('tx_templavoila_tmplobj')
		);
		$toCollection = array();
		foreach ($toList as $toRec) {
			$toCollection[] = $this->getTemplateByUid($toRec['uid']);
		}
		usort($toCollection, array($this, 'sortTemplates'));
		return $toCollection;
	}

	/**
	 * Retrieve a collection (array) of tx_templavoila_datastructure objects
	 *
	 * @return array
	 */
	public function getAll($storagePid=0) {
		$toList = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows (
			'tx_templavoila_tmplobj.uid',
			'tx_templavoila_tmplobj',
			'1=1'
				. (intval($storagePid) > 0 ? ' AND tx_templavoila_tmplobj.pid = ' . intval($storagePid) : ' AND tx_templavoila_tmplobj.pid!=-1')
				. t3lib_BEfunc::deleteClause('tx_templavoila_tmplobj')
				. t3lib_BEfunc::versioningPlaceholderClause('tx_templavoila_tmplobj')
		);
		$toCollection = array();
		foreach ($toList as $toRec) {
			$toCollection[] = $this->getTemplateByUid($toRec['uid']);
		}
		usort($toCollection, array($this, 'sortTemplates'));
		return $toCollection;
	}

	/**
	 * Sorts datastructure alphabetically
	 *
	 * @param	tx_templavoila_template $obj1
	 * @param	tx_templavoila_template $obj2
	 * @return	int	Result of the comparison (see strcmp())
	 * @see	usort()
	 * @see	strcmp()
	 */
	public function sortTemplates($obj1, $obj2) {
		return strcmp(strtolower($obj1->getSortingFieldValue()), strtolower($obj2->getSortingFieldValue()));
	}

	/**
	 * Find all folders with template objects
	 *
	 * @return array
	 */
	public function getTemplateStoragePids() {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'pid',
					'tx_templavoila_tmplobj',
					'pid>=0'.t3lib_BEfunc::deleteClause('tx_templavoila_tmplobj'),
					'pid'
				);
		while($res && false !== ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)))	{
			$list[]= $row['pid'];
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $list;
	}

	/**
	 *
	 *
	 * @return integer
	 */
	public function getTemplateCountForPid($pid) {
		$toCnt = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'count(*) as cnt',
					'tx_templavoila_tmplobj',
					'pid=' . intval($pid) .t3lib_BEfunc::deleteClause('tx_templavoila_tmplobj')
				);
		return $toCnt[0]['cnt'];
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/class.tx_templavoila_templateRepository.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/class.tx_templavoila_templateRepository.php']);
}
?>
