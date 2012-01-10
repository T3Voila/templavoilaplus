<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * Class/Function which manipulates the item-array for table/field tx_templavoila_tmplobj_datastructure.
 *
 * $Id$
 *
 * @author    Kasper Skaarhoj <kasper@typo3.com>
 */

/**
 * Class/Function which manipulates the item-array for table/field tx_templavoila_tmplobj_datastructure.
 *
 * @author    Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_templavoila
 */
class tx_templavoila_handleStaticDataStructures {
	var $prefix = 'Static: ';
	var $iconPath = '../uploads/tx_templavoila/';

	/**
	 * Adds static data structures to selector box items arrays.
	 * Adds ALL available structures
	 *
	 * @param	array		Array of items passed by reference.
	 * @param	object		The parent object (t3lib_TCEforms / t3lib_transferData depending on context)
	 * @return	void
	 */
	function main(&$params,&$pObj)    {
		$removeDSItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'ds');

		$dsRepo = t3lib_div::makeInstance('tx_templavoila_datastructureRepository');
		$dsList = $dsRepo->getAll();

		$params['items'] = array(
			array(
				'', ''
			)
		);

		foreach ($dsList as $dsObj) {
			if($dsObj->isPermittedForUser($params['row'], $removeDSItems)) {
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
	 * @param	array		Array of items passed by reference.
	 * @param	object		The parent object (t3lib_TCEforms / t3lib_transferData depending on context)
	 * @return	void
	 */
	function pi_templates(&$params,$pObj)	{
		global $TYPO3_DB;
			// Find the template data structure that belongs to this plugin:
		$piKey = $params['row']['list_type'];
		$templateRef = $GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoila_cm1']['piKey2DSMap'][$piKey];	// This should be a value of a Data Structure.
		$storagePid = $this->getStoragePid($params, $pObj);

		if ($templateRef && $storagePid)	{
				// Load the table:
			t3lib_div::loadTCA('tx_templavoila_tmplobj');

				// Select all Template Object Records from storage folder, which are parent records and which has the data structure for the plugin:
			$res = $TYPO3_DB->exec_SELECTquery (
				'title,uid,previewicon',
				'tx_templavoila_tmplobj',
				'tx_templavoila_tmplobj.pid='.$storagePid.' AND tx_templavoila_tmplobj.datastructure='.$TYPO3_DB->fullQuoteStr($templateRef, 'tx_templavoila_tmplobj').' AND tx_templavoila_tmplobj.parent=0',
				'',
				'tx_templavoila_tmplobj.title'
			);

				// Traverse these and add them. Icons are set too if applicable.
			while(false != ($row=$TYPO3_DB->sql_fetch_assoc($res)))	{
				if ($row['previewicon'])	{
					$icon='../'.$GLOBALS['TCA']['tx_templavoila_tmplobj']['columns']['previewicon']['config']['uploadfolder'].'/'.$row['previewicon'];
				} else $icon='';
				$params['items'][]= array($GLOBALS['LANG']->sL($row['title']), $row['uid'], $icon);
			}
		}
	}

	/**
	 * Creates the DS selector box. This function takes into account TS
	 * config override of the GRSP.
	 *
	 * @param	array	$params	Parameters to the itemsProcFunc
	 * @param	t3lib_TCEforms	$pObj	Calling object
	 * @return	void
	 */
	public function dataSourceItemsProcFunc(array &$params, t3lib_TCEforms& $pObj) {

		$storagePid = $this->getStoragePid($params, $pObj);
		$scope = $this->getScope($params);

		$removeDSItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'ds');

		$dsRepo = t3lib_div::makeInstance('tx_templavoila_datastructureRepository');
		$dsList = $dsRepo->getDatastructuresByStoragePidAndScope($storagePid, $scope);

		$params['items'] = array(
			array(
				'', ''
			)
		);

		foreach ($dsList as $dsObj) {
			if($dsObj->isPermittedForUser($params['row'], $removeDSItems)) {
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
	 * @param	array	$params	Parameters for itemProcFunc
	 * @param	t3lib_TCEforms	$pObj	Calling class
	 * @return	void
	 */
	public function templateObjectItemsProcFunc(array &$params, t3lib_TCEforms &$pObj) {
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
	 * @param	array	$params	Parameters for itemProcFunc
	 * @param	t3lib_TCEforms	$pObj	Calling class
	 * @return	void
	 */
	protected function templateObjectItemsProcFuncForCurrentDS(array &$params, t3lib_TCEforms &$pObj) {
			// Get DS
		$tsConfig = &$pObj->cachedTSconfig[$params['table'] . ':' . $params['row']['uid']];
		$fieldName = $params['field'] == 'tx_templavoila_next_to' ? 'tx_templavoila_next_ds' : 'tx_templavoila_ds';
		$dataSource = $tsConfig['_THIS_ROW'][$fieldName];

		$storagePid = $this->getStoragePid($params, $pObj);

		$removeTOItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'to');

		$dsRepo = t3lib_div::makeInstance('tx_templavoila_datastructureRepository');
		$toRepo = t3lib_div::makeInstance('tx_templavoila_templateRepository');

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
					if(!$toObj->hasParent() && $toObj->isPermittedForUser($params['table'], $removeTOItems)) {
						$params['items'][] = array(
							$toObj->getLabel(),
							$toObj->getKey(),
							$toObj->getIcon()
						);
					}
				}
			}
		} catch (InvalidArgumentException $e) {
			// we didn't find the DS which we were looking for therefore an empty list is returned
		}
	}

	/**
	 * Adds items to the template object selector according to the scope and
	 * storage folder of the current page/element.
	 *
	 * @param	array	$params	Parameters for itemProcFunc
	 * @param	t3lib_TCEforms	$pObj	Calling class
	 * @return	void
	 */
	protected function templateObjectItemsProcFuncForAllDSes(array &$params, t3lib_TCEforms &$pObj) {
		$storagePid = $this->getStoragePid($params, $pObj);
		$scope = $this->getScope($params);

		$removeDSItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'ds');
		$removeTOItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'to');

		$dsRepo = t3lib_div::makeInstance('tx_templavoila_datastructureRepository');
		$toRepo = t3lib_div::makeInstance('tx_templavoila_templateRepository');
		$dsList = $dsRepo->getDatastructuresByStoragePidAndScope($storagePid, $scope);

		$params['items'] = array(
			array(
				'', ''
			)
		);

		foreach($dsList as $dsObj) {
			if(!$dsObj->isPermittedForUser($params['row'], $removeDSItems)) {
				continue;
 			}
			$curDS = array();
			$curDS[] = array(
				$dsObj->getLabel(),
				'--div--'
			);

			$toList = $toRepo->getTemplatesByDatastructure($dsObj, $storagePid);
			foreach ($toList as $toObj) {
				if(!$toObj->hasParent() && $toObj->isPermittedForUser($params['row'], $removeTOItems)) {
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
	 * @param	array	$params	Parameters as come to the itemsProcFunc
	 * @param	t3lib_TCEforms	$pObj	Calling object
	 * @return	int	Storage pid
	 */
	protected function getStoragePid(array &$params, t3lib_TCEforms &$pObj) {
		// Get default first
		$tsConfig = &$pObj->cachedTSconfig[$params['table'].':'.$params['row']['uid']];
		$storagePid = intval($tsConfig['_STORAGE_PID']);

			// Check for alternative storage folder
		$field = $params['table'] == 'pages' ? 'uid' : 'pid';
		$modTSConfig = t3lib_BEfunc::getModTSconfig($params['row'][$field], 'tx_templavoila.storagePid');
		if (is_array($modTSConfig) && tx_templavoila_div::canBeInterpretedAsInteger($modTSConfig['value'])) {
			$storagePid = intval($modTSConfig['value']);
		}
		return $storagePid;
	}

	/**
	 * Determine scope from current paramset
 	 *
	 * @param array $params
	 * @return integer
 	 */
	protected function getScope(array $params) {
		$scope = tx_templavoila_datastructure::SCOPE_UNKNOWN;
		if ($params['table'] == 'pages') {
			$scope = tx_templavoila_datastructure::SCOPE_PAGE;
		} elseif ($params['table'] == 'tt_content') {
			$scope = tx_templavoila_datastructure::SCOPE_FCE;
 		}
		return $scope;
 	}

 	/**
	 * Find relevant removeItems blocks for a certain field with the given paramst
 	 *
	 * @param array $params
	 * @param string $field
	 * @return array
 	 */
	protected function getRemoveItems($params, $field) {
		$pid = $params['row'][$params['table'] == 'pages' ? 'uid' : 'pid'];
		$modTSConfig = t3lib_BEfunc::getModTSconfig($pid, 'TCEFORM.' . $params['table'] . '.' . $field . '.removeItems');
		return t3lib_div::trimExplode(',', $modTSConfig['value'], TRUE);
 	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_handlestaticdatastructures.php'])    {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_handlestaticdatastructures.php']);
}
?>
