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
	 * Retrieve a single datastructure by uid or xml-file path
	 *
	 * @param integer $uid
	 * @return tx_templavoila_datastructure
	 */
	public function getTemplateByUid($uid) {
		$to = null;
		if(version_compare(TYPO3_version,'4.3.0','<')) {
			$className = t3lib_div::makeInstanceClassName('tx_templavoila_template');
			$to = new $className($uid);
		} else {
			$to = t3lib_div::makeInstance('tx_templavoila_template', $uid);
		}
		return $to;
	}

	/**
	 * Retrieve a single datastructure by uid or xml-file path
	 *
	 * @param integer $uid
	 * @return tx_templavoila_datastructure
	 */
	public function getTemplatesByDatastructure(tx_templavoila_datastructure $ds) {
		$toList = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows (
			'tx_templavoila_tmplobj.uid',
			'tx_templavoila_tmplobj',
			'tx_templavoila_tmplobj.datastructure=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($ds->getKey(), 'tx_templavoila_tmplobj') . t3lib_BEfunc::deleteClause('tx_templavoila_tmplobj')
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
		return strcmp(strtolower($obj1->getLabel()), strtolower($obj2->getLabel()));
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/class.tx_templavoila_templateRepository.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/class.tx_templavoila_templateRepository.php']);
}
?>