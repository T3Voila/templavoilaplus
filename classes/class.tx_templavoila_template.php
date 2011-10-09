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
class tx_templavoila_template {

	protected $row;
	protected $label;
	protected $description;
	protected $iconFile;
	protected $fileref;
	protected $fileref_mtime;
	protected $fileref_md5;
	protected $sortbyField;
	protected $parent;

	/**
	 *
	 * @param integer $uid
	 */
	public function __construct($uid) {
		$this->row = t3lib_beFunc::getRecordWSOL('tx_templavoila_tmplobj', $uid);

		$this->setLabel($this->row['title']);
		$this->setDescription($this->row['description']);
		$this->setIcon($this->row['previewicon']);
		$this->setFileref($this->row['fileref']);
		$this->setFilerefMtime($this->row['fileref_mtime']);
		$this->setFilerefMD5($this->row['fileref_md5']);
		$this->setSortbyField($GLOBALS['TCA']['tx_templavoila_tmplobj']['ctrl']['sortby']);
		$this->setParent($this->row['parent']);
	}

	/**
	 * Retrieve the label of the template
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
	 * Retrieve the description of the template
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 *
	 * @param string $str
	 * @return void
	 */
	protected function setDescription($str) {
		$this->description = $str;
	}

	/**
	 * Determine the icon and append the path - relative to the TYPO3 main folder
	 *
	 * @return string
	 */
	public function getIcon() {
		$icon = '';
		if ($this->iconFile) {
			$icon = '../uploads/tx_templavoila/' . $this->iconFile;
		}
		return $icon;
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
	 * Retrieve the filereference of the template
	 *
	 * @return string
	 */
	public function getFileref() {
		return $this->fileref;
	}

	/**
	 *
	 * @param string $str
	 * @return void
	 */
	protected function setFileref($str) {
		$this->fileref = $str;
	}

	/**
	 * Retrieve the filereference of the template
	 *
	 * @return string
	 */
	public function getFilerefMtime() {
		return $this->fileref_mtime;
	}

	/**
	 *
	 * @param string $str
	 * @return void
	 */
	protected function setFilerefMtime($str) {
		$this->fileref_mtime = $str;
	}

	/**
	 * Retrieve the filereference of the template
	 *
	 * @return string
	 */
	public function getFilerefMD5() {
		return $this->fileref_md5;
	}

	/**
	 *
	 * @param string $str
	 * @return void
	 */
	protected function setFilerefMD5($str) {
		$this->fileref_md5 = $str;
	}

	/**
	 *
	 * @return string - numeric string
	 */
	public function getKey() {
		return $this->row['uid'];
	}

	/**
	 * Retrieve the timestamp of the template
	 *
	 * @return string
	 */
	public function getTstamp() {
		return $this->row['tstamp'];
	}

	/**
	 * Retrieve the creation date of the template
	 *
	 * @return string
	 */
	public function getCrdate() {
		return $this->row['crdate'];
	}

	/**
	 * Retrieve the creation user of the template
	 *
	 * @return string
	 */
	public function getCruser() {
		return $this->row['cruser_id'];
	}

	/**
	 * Retrieve the rendertype of the template
	 *
	 * @return string
	 */
	public function getRendertype() {
		return $this->row['rendertype'];
	}

	/**
	 * Retrieve the system language of the template
	 *
	 * @return integer
	 */
	public function getSyslang() {
		return $this->row['sys_language_uid'];
	}

	/**
	 * Check if this is a subtemplate or not
	 *
	 * @return boolean
	 */
	public function hasParentTemplate() {
		return $this->row['parent'] != 0;
	}

	/**
	 * Determine whether the current user has permission to create elements based on this
	 * template or not
	 *
	 * @param mixed $parentRow
	 * @param mixed $removeItems
	 * @return boolean
	 */
	public function isPermittedForUser($parentRow = array(), $removeItems = array()) {
		if ($GLOBALS['BE_USER']->isAdmin()) {
			return TRUE;
		} else if(in_array($this->getKey(), $removeItems)) {
			return FALSE;
		}
		$permission = TRUE;
		$denyItems = tx_templavoila_div::getDenyListForUser();


		if (isset($parentRow['tx_templavoila_to'])) {
			$currentSetting = $parentRow['tx_templavoila_to'];
		} else {
			$currentSetting = $this->getKey();
		}

		if (isset($parentRow['tx_templavoila_next_to']) &&
			$this->getScope() == tx_templavoila_datastructure::SCOPE_PAGE) {
			$inheritSetting = $parentRow['tx_templavoila_next_to'];
		} else {
			$inheritSetting = -1;
		}

		$key = 'tx_templavoila_tmplobj_' . $this->getKey();
		if (in_array($key, $denyItems) &&
			$key != $currentSetting &&
			$key != $inheritSetting
		) {
			$permission = FALSE;
		}
		return $permission;
	}

	/**
	 * @return tx_templavoila_datastructure
	 */
	public function getDatastructure() {
		$dsRepo = t3lib_div::makeInstance('tx_templavoila_datastructureRepository');
		return $dsRepo->getDatastructureByUidOrFilename($this->row['datastructure']);
	}

	/**
	 * @return int
	 */
	protected function getScope() {
		return $this->getDatastructure()->getScope();
	}

	/**
	 * @param boolean $skipDsDataprot
	 * @return array
	 */
	public function getLocalDataprotXML($skipDsDataprot = FALSE) {
		return t3lib_div::array2xml_cs($this->getLocalDataprotArray($skipDsDataprot), 'T3DataStructure', array('useCDATA' => 1));
	}

	/**
	 * @param boolean $skipDsDataprot
	 * @return array
	 */
	public function getLocalDataprotArray($skipDsDataprot = FALSE) {
		if (!$skipDsDataprot) {
			$dataprot = $this->getDatastructure()->getDataprotArray();
		} else {
			$dataprot = array();
		}
		$toDataprot =  t3lib_div::xml2array($this->row['localprocessing']);

		if (is_array($toDataprot)) {
			$dataprot = t3lib_div::array_merge_recursive_overrule($dataprot, $toDataprot);
		}
		return $dataprot;
	}

	/**
	 * Fetch the the field value based on the given XPath expression.
	 *
	 * @param  string $fieldName XPath expression to look up for an value.
	 *
	 * @return string
	 */
	public function getLocalDataprotValueByXpath($fieldName) {
		$value = '';
		$doc = new DOMDocument;
		$doc->preserveWhiteSpace = false;
		$doc->loadXML($this->getLocalDataprotXML());
		$xpath = new DOMXPath($doc);
		$entries = $xpath->query($fieldName);

		if ($entries->length  < 1) {
			throw new UnexpectedValueException('Nothing found for XPath: "' . $fieldName . '"!');
		}
		
		return $entries->item(0)->nodeValue;
	}

	/**
	 * @param void
	 * @return mixed
	 */
	public function getBeLayout() {
		$beLayout = FALSE;
		if ($this->row['belayout']) {
			$beLayout = t3lib_div::getURL(t3lib_div::getFileAbsFileName($this->row['belayout']));
		} else {
			$beLayout = $this->getDatastructure()->getBeLayout();
		}
		return $beLayout;
	}

	/**
	 * @param string	$fieldname
	 * @return void
	 */
	protected function setSortbyField($fieldname) {
		if (isset($this->row[$fieldname])) {
			$this->sortbyField = $fieldname;
		} elseif (!$this->sortbyField) {
			$this->sortbyField = 'sorting';
		}
	}

	/**
	 * @param void
	 * @return string
	 */
	public function getSortingFieldValue() {
		if ($this->sortbyField == 'title') {
			$fieldVal = $this->getLabel();		// required to resolve LLL texts
		} elseif ($this->sortbyField == 'sorting') {
			$fieldVal = str_pad($this->row[$this->sortbyField], 15, "0", STR_PAD_LEFT);
		} else {
			$fieldVal = $this->row[$this->sortbyField];
		}
		return $fieldVal;
	}

	public function setParent ($parent) {
		$this->parent = $parent;
	}

	public function getParent () {
		return $this->parent;
	}

	public function hasParent() {
		return $this->parent > 0;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/class.tx_templavoila_template.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/class.tx_templavoila_template.php']);
}
?>
