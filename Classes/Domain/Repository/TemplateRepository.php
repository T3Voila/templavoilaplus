<?php
namespace Extension\Templavoila\Domain\Repository;

/**
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

/**
 * Class to provide unique access to datastructure
 *
 * @author Tolleiv Nietsch <tolleiv.nietsch@typo3.org>
 */
class TemplateRepository {

	/**
	 * Retrieve a single templateobject by uid or xml-file path
	 *
	 * @param integer $uid
	 *
	 * @return \Extension\Templavoila\Domain\Model\Template
	 */
	public function getTemplateByUid($uid) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Extension\\Templavoila\\Domain\\Model\\Template', $uid);
	}

	/**
	 * Retrieve template objects which are related to a specific datastructure
	 *
	 * @param \Extension\Templavoila\Domain\Model\AbstractDataStructure $ds
	 * @param integer $storagePid
	 *
	 * @return array
	 */
	public function getTemplatesByDatastructure(\Extension\Templavoila\Domain\Model\AbstractDataStructure $ds, $storagePid = 0) {
		$toList = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTgetRows(
			'tx_templavoila_tmplobj.uid',
			'tx_templavoila_tmplobj',
			'tx_templavoila_tmplobj.datastructure=' . \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->fullQuoteStr($ds->getKey(), 'tx_templavoila_tmplobj')
			. (intval($storagePid) > 0 ? ' AND tx_templavoila_tmplobj.pid = ' . intval($storagePid) : '')
			. \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_templavoila_tmplobj')
			. ' AND pid!=-1 '
			. \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('tx_templavoila_tmplobj')
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
	 * @param integer $storagePid
	 * @param integer $scope
	 *
	 * @return array
	 */
	public function getTemplatesByStoragePidAndScope($storagePid, $scope) {
		$dsRepo = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Extension\\Templavoila\\Domain\\Repository\\DataStructureRepository');
		$dsList = $dsRepo->getDatastructuresByStoragePidAndScope($storagePid, $scope);
		$toCollection = array();
		foreach ($dsList as $dsObj) {
			$toCollection = array_merge($toCollection, $this->getTemplatesByDatastructure($dsObj, $storagePid));
		}
		usort($toCollection, array($this, 'sortTemplates'));

		return $toCollection;
	}

	/**
	 * Retrieve template objects which have a specific template as their parent
	 *
	 * @param \Extension\Templavoila\Domain\Model\Template $to
	 * @param integer $storagePid
	 *
	 * @return array
	 */
	public function getTemplatesByParentTemplate(\Extension\Templavoila\Domain\Model\Template $to, $storagePid = 0) {
		$toList = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTgetRows(
			'tx_templavoila_tmplobj.uid',
			'tx_templavoila_tmplobj',
			'tx_templavoila_tmplobj.parent=' . \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->fullQuoteStr($to->getKey(), 'tx_templavoila_tmplobj')
			. (intval($storagePid) > 0 ? ' AND tx_templavoila_tmplobj.pid = ' . intval($storagePid) : ' AND pid!=-1')
			. \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_templavoila_tmplobj')
			. \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('tx_templavoila_tmplobj')
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
	 * @param integer $storagePid
	 *
	 * @return array
	 */
	public function getAll($storagePid = 0) {
		$toList = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTgetRows(
			'tx_templavoila_tmplobj.uid',
			'tx_templavoila_tmplobj',
			'1=1'
			. (intval($storagePid) > 0 ? ' AND tx_templavoila_tmplobj.pid = ' . intval($storagePid) : ' AND tx_templavoila_tmplobj.pid!=-1')
			. \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_templavoila_tmplobj')
			. \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('tx_templavoila_tmplobj')
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
	 * @param \Extension\Templavoila\Domain\Model\Template $obj1
	 * @param \Extension\Templavoila\Domain\Model\Template $obj2
	 *
	 * @return integer Result of the comparison (see strcmp())
	 * @see usort()
	 * @see strcmp()
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
		$res = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTquery(
			'pid',
			'tx_templavoila_tmplobj',
			'pid>=0' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_templavoila_tmplobj'),
			'pid'
		);
		$list = array();
		while ($res && FALSE !== ($row = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
			$list[] = $row['pid'];
		}
		\Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_free_result($res);

		return $list;
	}

	/**
	 * @param integer $pid
	 *
	 * @return integer
	 */
	public function getTemplateCountForPid($pid) {
		$toCnt = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTgetRows(
			'count(*) as cnt',
			'tx_templavoila_tmplobj',
			'pid=' . intval($pid) . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_templavoila_tmplobj')
		);

		return $toCnt[0]['cnt'];
	}
}
