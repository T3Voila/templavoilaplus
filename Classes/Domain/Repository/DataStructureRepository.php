<?php
namespace Extension\Templavoila\Domain\Repository;

/*
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
class DataStructureRepository {

	/**
	 * @var boolean
	 */
	static protected $staticDsInitComplete = FALSE;

	/**
	 * Retrieve a single datastructure by uid or xml-file path
	 *
	 * @param integer $uidOrFile
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return \Extension\Templavoila\Domain\Model\AbstractDataStructure
	 */
	public function getDatastructureByUidOrFilename($uidOrFile) {

		if ((int)$uidOrFile > 0) {
			$className = 'Extension\\Templavoila\\Domain\\Model\\DataStructure';
		} else {
			if (($staticKey = $this->validateStaticDS($uidOrFile)) !== FALSE) {
				$uidOrFile = $staticKey;
				$className = 'Extension\\Templavoila\\Domain\\Model\\StaticDataStructure';
			} else {
				throw new \InvalidArgumentException(
					'Argument was supposed to be either a uid or a filename',
					1273409810
				);
			}
		}

		$ds = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className, $uidOrFile);

		return $ds;
	}

	/**
	 * Retrieve a collection (array) of tx_templavoila_datastructure objects
	 *
	 * @param integer $pid
	 *
	 * @return array
	 */
	public function getDatastructuresByStoragePid($pid) {

		$dscollection = array();
		$confArr = self::getStaticDatastructureConfiguration();
		if (count($confArr)) {
			foreach ($confArr as $conf) {
				$ds = $this->getDatastructureByUidOrFilename($conf['path']);
				$pids = $ds->getStoragePids();
				if ($pids == '' || \TYPO3\CMS\Core\Utility\GeneralUtility::inList($pids, $pid)) {
					$dscollection[] = $ds;
				}
			}
		}

		if (!self::isStaticDsEnabled()) {
			$dsRows = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTgetRows(
				'uid',
				'tx_templavoila_datastructure',
				'pid=' . (int)$pid
				. \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_templavoila_datastructure')
				. ' AND pid!=-1 '
				. \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('tx_templavoila_datastructure')
			);
			foreach ($dsRows as $ds) {
				$dscollection[] = $this->getDatastructureByUidOrFilename($ds['uid']);
			}
		}
		usort($dscollection, array($this, 'sortDatastructures'));

		return $dscollection;
	}

	/**
	 * Retrieve a collection (array) of tx_templavoila_datastructure objects
	 *
	 * @param integer $pid
	 * @param integer $scope
	 *
	 * @return array
	 */
	public function getDatastructuresByStoragePidAndScope($pid, $scope) {
		$dscollection = array();
		$confArr = self::getStaticDatastructureConfiguration();
		if (count($confArr)) {
			foreach ($confArr as $conf) {
				if ($conf['scope'] == $scope) {
					$ds = $this->getDatastructureByUidOrFilename($conf['path']);
					$pids = $ds->getStoragePids();
					if ($pids == '' || \TYPO3\CMS\Core\Utility\GeneralUtility::inList($pids, $pid)) {
						$dscollection[] = $ds;
					}
				}
			}
		}

		if (!self::isStaticDsEnabled()) {
			$dsRows = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTgetRows(
				'uid',
				'tx_templavoila_datastructure',
				'scope=' . (int)$scope . ' AND pid=' . (int)$pid
				. \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_templavoila_datastructure')
				. ' AND pid!=-1 '
				. \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('tx_templavoila_datastructure')
			);
			foreach ($dsRows as $ds) {
				$dscollection[] = $this->getDatastructureByUidOrFilename($ds['uid']);
			}
		}
		usort($dscollection, array($this, 'sortDatastructures'));

		return $dscollection;
	}

	/**
	 * Retrieve a collection (array) of tx_templavoila_datastructure objects
	 *
	 * @param integer $scope
	 *
	 * @return array
	 */
	public function getDatastructuresByScope($scope) {
		$dscollection = array();
		$confArr = self::getStaticDatastructureConfiguration();
		if (count($confArr)) {
			foreach ($confArr as $conf) {
				if ($conf['scope'] == $scope) {
					$ds = $this->getDatastructureByUidOrFilename($conf['path']);
					$dscollection[] = $ds;
				}
			}
		}

		if (!self::isStaticDsEnabled()) {
			$dsRows = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTgetRows(
				'uid',
				'tx_templavoila_datastructure',
				'scope=' . (int)$scope
				. \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_templavoila_datastructure')
				. ' AND pid!=-1 '
				. \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('tx_templavoila_datastructure')
			);
			foreach ($dsRows as $ds) {
				$dscollection[] = $this->getDatastructureByUidOrFilename($ds['uid']);
			}
		}
		usort($dscollection, array($this, 'sortDatastructures'));

		return $dscollection;
	}

	/**
	 * Retrieve a collection (array) of tx_templavoila_datastructure objects
	 *
	 * @return array
	 */
	public function getAll() {
		$dscollection = array();
		$confArr = self::getStaticDatastructureConfiguration();
		if (count($confArr)) {
			foreach ($confArr as $conf) {
				$ds = $this->getDatastructureByUidOrFilename($conf['path']);
				$dscollection[] = $ds;
			}
		}

		if (!self::isStaticDsEnabled()) {
			$dsRows = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTgetRows(
				'uid',
				'tx_templavoila_datastructure',
				'1=1'
				. \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_templavoila_datastructure')
				. ' AND pid!=-1 '
				. \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('tx_templavoila_datastructure')
			);
			foreach ($dsRows as $ds) {
				$dscollection[] = $this->getDatastructureByUidOrFilename($ds['uid']);
			}
		}
		usort($dscollection, array($this, 'sortDatastructures'));

		return $dscollection;
	}

	/**
	 * @param string $file
	 *
	 * @return mixed
	 */
	protected function validateStaticDS($file) {
		$confArr = self::getStaticDatastructureConfiguration();
		$confKey = FALSE;
		if (count($confArr)) {
			$fileAbsName = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file);
			foreach ($confArr as $key => $conf) {
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($conf['path']) == $fileAbsName) {
					$confKey = $key;
					break;
				}
			}
		}

		return $confKey;
	}

	/**
	 * @return boolean
	 */
	protected function isStaticDsEnabled() {
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);

		return $extConf['staticDS.']['enable'];
	}

	/**
	 * @return array
	 */
	static public function getStaticDatastructureConfiguration() {
		$config = array();
		if (!self::$staticDsInitComplete) {
			$extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);
			if ($extConfig['staticDS.']['enable']) {
				\Extension\Templavoila\Utility\StaticDataStructure\ToolsUtility::readStaticDsFilesIntoArray($extConfig);
			}
			self::$staticDsInitComplete = TRUE;
		}
		if (is_array($GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoila_cm1']['staticDataStructures'])) {
			$config = $GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoila_cm1']['staticDataStructures'];
		}

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['staticDataStructures'])) {
			$config = array_merge($config, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['staticDataStructures']);
		}

		$finalConfig = array();
		foreach ($config as $cfg) {
			$key = md5($cfg['path'] . $cfg['title'] . $cfg['scope']);
			$finalConfig[$key] = $cfg;
		}

		return array_values($finalConfig);
	}

	/**
	 * Sorts datastructure alphabetically
	 *
	 * @param \Extension\Templavoila\Domain\Model\AbstractDataStructure $obj1
	 * @param \Extension\Templavoila\Domain\Model\AbstractDataStructure $obj2
	 *
	 * @return integer Result of the comparison (see strcmp())
	 * @see usort()
	 * @see strcmp()
	 */
	public function sortDatastructures($obj1, $obj2) {
		return strcmp(strtolower($obj1->getSortingFieldValue()), strtolower($obj2->getSortingFieldValue()));
	}

	/**
	 * @param integer $pid
	 *
	 * @return integer
	 */
	public function getDatastructureCountForPid($pid) {
		$dsCnt = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTgetRows(
			'DISTINCT datastructure',
			'tx_templavoila_tmplobj',
			'pid=' . (int)$pid . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_templavoila_tmplobj'),
			'datastructure'
		);
		array_unique($dsCnt);

		return count($dsCnt);
	}
}
