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
abstract class tx_templavoila_datastructure {

	const SCOPE_UNKNOWN = 0;
	const SCOPE_PAGE = 1;
	const SCOPE_FCE = 2;

	protected $scope = self::SCOPE_UNKNOWN;
	protected $label = '';
	protected $iconFile = '';

	/**
	 * Retrieve the label of the datastructure
	 *
	 * @return string
	 */
	public function getLabel() {
		return $GLOBALS['LANG']->sL($this->label);
	}

	/**
	 *
	 * @param string $str
	 * @return void
	 */
	protected function setLabel($str) {
		$this->label = $str;
	}

	/**
	 * Retrieve the label of the datastructure
	 *
	 * @return string
	 */
	public function getScope() {
		return $this->scope;
	}

	/**
	 *
	 * @param integer $scope
	 * @return void
	 */
	protected function setScope($scope) {
		if ($scope == self::SCOPE_PAGE || $scope == self::SCOPE_FCE) {
			$this->scope = $scope;
		} else {
			$this->scope = self::SCOPE_UNKNOWN;
		}
	}

	/**
	 * However the datastructure is identifiable (uid or filepath
	 * This method deliver the relevant key
	 *
	 * @return string
	 */
	abstract public function getKey();

	/**
	 * Determine the icon and append the path
	 * assuming that the path for the iconFile is relative to the TYPO3 main folder
	 *
	 * @return string
	 */
	public function getIcon() {
			//regex is used to check if there's a filename within the iconFile string
		return preg_replace('/^.*\/([^\/]+\.(gif|png))?$/i','\1',$this->iconFile) ? $this->iconFile : '';
	}

	/**
	 *
	 * @param string $filename
	 * @return void
	 */
	protected function setIcon($filename) {
		$this->iconFile = $filename;
	}

	/**
	 * Determine relevant storage pids for this element,
	 * usually one uid but in certain situations this might contain multiple uids (see staticds)
	 *
	 * @return string
	 */
	abstract public function getStoragePids();

	/**
	 * Provides the datastructure configuration as XML
	 *
	 * @return string
	 */
	abstract public function getDataprotXML();

	/**
	 * Provides the datastructure configuration as array
	 *
	 * @return array
	 */
	public function getDataprotArray() {
		$arr = array();
		$ds = $this->getDataprotXML();
		if (strlen($ds) > 1) {
			$arr = t3lib_div::xml2array($ds);
		}
		return $arr;
	}

	/**
	 * Determine whether the current user has permission to create elements based on this
	 * datastructure or not
	 *
	 * @param mixed $parentRow
	 * @param mixed $removeItems
	 * @return boolean
	 */
	abstract public function isPermittedForUser($parentRow = array(), $removeItems = array());

	/**
	 * Enables to determine whether this element is based on a record or on a file
	 * Required for view-related tasks (edit-icons)
	 *
	 * @return boolean
	 */
	public function isFilebased() {
		return FALSE;
	}

	/**
	 * Retrieve the filereference of the template
	 *
	 * @return string
	 */
	abstract public function getTstamp();

	/**
	 * Retrieve the filereference of the template
	 *
	 * @return string
	 */
	abstract public function getCrdate();

	/**
	 * Retrieve the filereference of the template
	 *
	 * @return string
	 */
	abstract public function getCruser();

	/**
	 * @param void
	 * @return mixed
	 */
	abstract public function getBeLayout();

	/**
	 * @param void
	 * @return string
	 */
	abstract public function getSortingFieldValue();

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/class.tx_templavoila_datastructure.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/class.tx_templavoila_datastructure.php']);
}
?>
