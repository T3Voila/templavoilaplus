<?php
namespace Extension\Templavoila\Service\ItemProcFunc;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class/Function which manipulates the item-array for table/field tx_templavoila_tmplobj_datastructure.
 *
 * @author Kasper Skaarhoj <kasper@typo3.com>
 */
class StaticDataStructuresHandler {

	/**
	 * @var array
	 */
	protected $conf;

	/**
	 * @var string
	 */
	public $prefix = 'Static: ';

	/**
	 * @var string
	 */
	public $iconPath = '../uploads/tx_templavoila/';

	/**
	 * Adds static data structures to selector box items arrays.
	 * Adds ALL available structures
	 *
	 * @param array &$params Array of items passed by reference.
	 * @param object &$pObj The parent object (\TYPO3\CMS\Backend\Form\FormEngine / \TYPO3\CMS\Backend\Form\DataPreprocessor depending on context)
	 *
	 * @return void
	 */
	public function main(&$params, &$pObj) {
		$removeDSItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'ds');

		$dsRepo = GeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\DataStructureRepository::class);
		$dsList = $dsRepo->getAll();

		$params['items'] = array(
			array(
				'', ''
			)
		);

		foreach ($dsList as $dsObj) {
			/** @var \Extension\Templavoila\Domain\Model\AbstractDataStructure $dsObj */
			if ($dsObj->isPermittedForUser($params['row'], $removeDSItems)) {
				$params['items'][] = array(
					$dsObj->getLabel(),
					$dsObj->getKey(),
					$dsObj->getIcon()
				);
			}
		}
	}

	/**
	 * Adds Template Object records to selector box for Content Elements of the "Plugin" type.
	 *
	 * @param array &$params Array of items passed by reference.
	 * @param \TYPO3\CMS\Backend\Form\FormEngine|\TYPO3\CMS\Backend\Form\DataPreprocessor $pObj The parent object (\TYPO3\CMS\Backend\Form\FormEngine / \TYPO3\CMS\Backend\Form\DataPreprocessor depending on context)
	 *
	 * @return void
	 */
	public function pi_templates(&$params, $pObj) {
		// Find the template data structure that belongs to this plugin:
		$piKey = $params['row']['list_type'];
		$templateRef = $GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoila_cm1']['piKey2DSMap'][$piKey]; // This should be a value of a Data Structure.
		$storagePid = $this->getStoragePid($params, $pObj);

		if ($templateRef && $storagePid) {

			// Select all Template Object Records from storage folder, which are parent records and which has the data structure for the plugin:
			$res = $this->getDatabaseConnection()->exec_SELECTquery(
				'title,uid,previewicon',
				'tx_templavoila_tmplobj',
				'tx_templavoila_tmplobj.pid=' . $storagePid . ' AND tx_templavoila_tmplobj.datastructure=' .  $this->getDatabaseConnection()->fullQuoteStr($templateRef, 'tx_templavoila_tmplobj') . ' AND tx_templavoila_tmplobj.parent=0',
				'',
				'tx_templavoila_tmplobj.title'
			);

			// Traverse these and add them. Icons are set too if applicable.
			while (FALSE != ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res))) {
				if ($row['previewicon']) {
					$icon = '../' . $GLOBALS['TCA']['tx_templavoila_tmplobj']['columns']['previewicon']['config']['uploadfolder'] . '/' . $row['previewicon'];
				} else {
					$icon = '';
				}
				$params['items'][] = array($this->getLanguageService()->sL($row['title']), $row['uid'], $icon);
			}
		}
	}

	/**
	 * Creates the DS selector box. This function takes into account TS
	 * config override of the GRSP.
	 *
	 * @param array $params Parameters to the itemsProcFunc
	 * @param \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems $pObj Calling object
	 *
	 * @return void
	 */
	public function dataSourceItemsProcFunc(array &$params, \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems &$pObj) {

		$storagePid = $this->getStoragePid($params, $pObj);
		$scope = $this->getScope($params);

		$removeDSItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'ds');

		$dsRepo = GeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\DataStructureRepository::class);
		$dsList = $dsRepo->getDatastructuresByStoragePidAndScope($storagePid, $scope);

		$params['items'] = array(
			array(
				'', ''
			)
		);

		foreach ($dsList as $dsObj) {
			/** @var \Extension\Templavoila\Domain\Model\AbstractDataStructure $dsObj */
			if ($dsObj->isPermittedForUser($params['row'], $removeDSItems)) {
				$params['items'][] = array(
					$dsObj->getLabel(),
					$dsObj->getKey(),
					$dsObj->getIcon()
				);
			}
		}
	}

	/**
	 * Adds items to the template object selector according to the current
	 * extension mode.
	 *
	 * @param array $params Parameters for itemProcFunc
	 * @param \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems $pObj Calling class
	 *
	 * @return void
	 */
	public function templateObjectItemsProcFunc(array &$params, \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems &$pObj) {
		$this->conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);

		if ($this->conf['enable.']['selectDataStructure']) {
			$this->templateObjectItemsProcFuncForCurrentDS($params, $pObj);
		} else {
			$this->templateObjectItemsProcFuncForAllDSes($params, $pObj);
		}
	}

	/**
	 * Adds items to the template object selector according to the scope and
	 * storage folder of the current page/element.
	 *
	 * @param array $params Parameters for itemProcFunc
	 * @param \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems $pObj Calling class
	 *
	 * @return void
	 */
	protected function templateObjectItemsProcFuncForCurrentDS(array &$params, \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems &$pObj) {
		// Get DS
		$tsConfig = & $pObj->cachedTSconfig[$params['table'] . ':' . $params['row']['uid']];
		$fieldName = $params['field'] == 'tx_templavoila_next_to' ? 'tx_templavoila_next_ds' : 'tx_templavoila_ds';
		$dataSource = $tsConfig['_THIS_ROW'][$fieldName];

		$storagePid = $this->getStoragePid($params, $pObj);

		$removeTOItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'to');

		$dsRepo = GeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\DataStructureRepository::class);
		$toRepo = GeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\TemplateRepository::class);

		$params['items'] = array(
			array(
				'', ''
			)
		);

		try {
			$ds = $dsRepo->getDatastructureByUidOrFilename($dataSource);
			if (strlen($dataSource)) {
				$toList = $toRepo->getTemplatesByDatastructure($ds, $storagePid);
				foreach ($toList as $toObj) {
					/** @var \Extension\Templavoila\Domain\Model\Template $toObj */
					if (!$toObj->hasParent() && $toObj->isPermittedForUser($params['table'], $removeTOItems)) {
						$params['items'][] = array(
							$toObj->getLabel(),
							$toObj->getKey(),
							$toObj->getIcon()
						);
					}
				}
			}
		} catch (\InvalidArgumentException $e) {
			// we didn't find the DS which we were looking for therefore an empty list is returned
		}
	}

	/**
	 * Adds items to the template object selector according to the scope and
	 * storage folder of the current page/element.
	 *
	 * @param array $params Parameters for itemProcFunc
	 * @param \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems $pObj Calling class
	 *
	 * @return void
	 */
	protected function templateObjectItemsProcFuncForAllDSes(array &$params, \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems &$pObj) {
		$storagePid = $this->getStoragePid($params, $pObj);
		$scope = $this->getScope($params);

		$removeDSItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'ds');
		$removeTOItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'to');

		$dsRepo = GeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\DataStructureRepository::class);
		$toRepo = GeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\TemplateRepository::class);
		$dsList = $dsRepo->getDatastructuresByStoragePidAndScope($storagePid, $scope);

		$params['items'] = array(
			array(
				'', ''
			)
		);

		foreach ($dsList as $dsObj) {
			/** @var \Extension\Templavoila\Domain\Model\AbstractDataStructure $dsObj */
			if (!$dsObj->isPermittedForUser($params['row'], $removeDSItems)) {
				continue;
			}
			$curDS = array();
			$curDS[] = array(
				$dsObj->getLabel(),
				'--div--'
			);

			$toList = $toRepo->getTemplatesByDatastructure($dsObj, $storagePid);
			foreach ($toList as $toObj) {
				/** @var \Extension\Templavoila\Domain\Model\Template $toObj */
				if (!$toObj->hasParent() && $toObj->isPermittedForUser($params['row'], $removeTOItems)) {
					$curDS[] = array(
						$toObj->getLabel(),
						$toObj->getKey(),
						$toObj->getIcon()
					);
				}
			}
			if (count($curDS) > 1) {
				$params['items'] = array_merge($params['items'], $curDS);
			}
		}
	}

	/**
	 * Retrieves DS/TO storage pid for the current page. This function expectes
	 * to be called from the itemsProcFunc only!
	 *
	 * @param array $params Parameters as come to the itemsProcFunc
	 * @param \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems $pObj Calling object
	 *
	 * @return integer Storage pid
	 */
	protected function getStoragePid(array &$params, \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems &$pObj) {
		// Get default first
		$tsConfig = & $pObj->cachedTSconfig[$params['table'] . ':' . $params['row']['uid']];
		$storagePid = (int)$tsConfig['_STORAGE_PID'];

		// Check for alternative storage folder
		$field = $params['table'] == 'pages' ? 'uid' : 'pid';
		$modTSConfig = BackendUtility::getModTSconfig($params['row'][$field], 'tx_templavoila.storagePid');
		if (is_array($modTSConfig) && MathUtility::canBeInterpretedAsInteger($modTSConfig['value'])) {
			$storagePid = (int)$modTSConfig['value'];
		}

		return $storagePid;
	}

	/**
	 * Determine scope from current paramset
	 *
	 * @param array $params
	 *
	 * @return integer
	 */
	protected function getScope(array $params) {
		switch ($params['table']) {
			case 'pages':
				$scope = \Extension\Templavoila\Domain\Model\AbstractDataStructure::SCOPE_PAGE;
			break;
			case 'tt_content':
				$scope = \Extension\Templavoila\Domain\Model\AbstractDataStructure::SCOPE_FCE;
			break;
			default:
				$scope = \Extension\Templavoila\Domain\Model\AbstractDataStructure::SCOPE_UNKNOWN;
		}
		return $scope;
	}

	/**
	 * Find relevant removeItems blocks for a certain field with the given paramst
	 *
	 * @param array $params
	 * @param string $field
	 *
	 * @return array
	 */
	protected function getRemoveItems($params, $field) {
		$pid = $params['row'][$params['table'] == 'pages' ? 'uid' : 'pid'];
		$modTSConfig = BackendUtility::getModTSconfig($pid, 'TCEFORM.' . $params['table'] . '.' . $field . '.removeItems');

		return GeneralUtility::trimExplode(',', $modTSConfig['value'], TRUE);
	}


	/**
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}
}
