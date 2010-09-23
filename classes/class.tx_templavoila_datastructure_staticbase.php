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
class tx_templavoila_datastructure_staticbase extends tx_templavoila_datastructure {

	protected $filename;
	/**
	 *
	 * @param integer $uid
	 */
	public function __construct($key) {

		$conf = tx_templavoila_datastructureRepository::getStaticDatastructureConfiguration();

		if (!isset($conf[$key])) {
			throw new InvalidArgumentException(
				'Argument was supposed to be an existing datastructure',
				1283192644
			);
		}

		$this->filename = $conf[$key]['path'];

		$this->setLabel($conf[$key]['title']);
		$this->setScope($conf[$key]['scope']);
			// path relative to typo3 maindir
		$this->setIcon( '../' . $conf[$key]['icon']);
	}

	/**
	 *
	 * @return string;
	 */
	public function getStoragePids() {
		$pids = array();
		$toList = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'tx_templavoila_tmplobj.uid,tx_templavoila_tmplobj.pid',
			'tx_templavoila_tmplobj',
			'tx_templavoila_tmplobj.datastructure=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->filename, 'tx_templavoila_tmplobj') . t3lib_BEfunc::deleteClause('tx_templavoila_tmplobj')
		);
		foreach ($toList as $toRow) {
			$pids[$toRow['pid']]++;
		}
		return implode(',', array_keys($pids));
	}

	/**
	 *
	 * @return string - the filename
	 */
	public function getKey() {
		return $this->filename;
	}

	/**
	 * Provides the datastructure configuration as XML
	 *
	 * @return string
	 */
	public function getDataprotXML() {
		$xml = '';
		$file = t3lib_div::getFileAbsFileName($this->filename);
		if (is_readable($file)) {
			$xml = file_get_contents($file);
		} else {
			// @todo find out if that happens and whether there's a "useful" reaction for that
		}
		return $xml;
	}

	/**
	 * Determine whether the current user has permission to create elements based on this
	 * datastructure or not - not really useable for static datastructure but relevant for
	 * the overall system
	 *
	 * @param mixed $parentRow
	 * @param mixed $removeItems
	 * @return boolean
	 */
	public function isPermittedForUser($parentRow = array(), $removeItems = array()) {
		return TRUE;
	}

	/**
	 * Enables to determine whether this element is based on a record or on a file
	 * Required for view-related tasks (edit-icons)
	 *
	 * @return boolean
	 */
	public function isFilebased() {
		return TRUE;
	}

	/**
	 * Retrieve the filereference of the template
	 *
	 * @return string
	 */
	public function getTstamp() {
		$file = t3lib_div::getFileAbsFileName($this->filename);
		if (is_readable($file)) {
			$tstamp = filemtime($file);
		} else {
			$tstamp = 0;
		}
		return $tstamp;
	}

	/**
	 * Retrieve the filereference of the template
	 *
	 * @return string
	 */
	public function getCrdate() {
		$file = t3lib_div::getFileAbsFileName($this->filename);
		if (is_readable($file)) {
			$tstamp = filectime($file);
		} else {
			$tstamp = 0;
		}
		return $tstamp;
	}

	/**
	 * Retrieve the filereference of the template
	 *
	 * @return string
	 */
	public function getCruser() {
		return 0;
	}

	/**
	 * @param void
	 * @return mixed
	 */
	public function getBeLayout() {
		$beLayout = FALSE;		
		$file = substr(t3lib_div::getFileAbsFileName($this->filename), 0, -3) . 'html';
		if (file_exists($file)) {
			$beLayout = t3lib_div::getURL($file);
		}
		return $beLayout;
	}

	/**
	 * @param void
	 * @return string
	 */
	public function getSortingFieldValue() {
		return $this->getLabel();		// required to resolve LLL texts
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/class.tx_templavoila_datastructure_staticbase.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/class.tx_templavoila_datastructure_staticbase.php']);
}
?>
