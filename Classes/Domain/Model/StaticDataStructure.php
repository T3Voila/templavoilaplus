<?php
namespace Extension\Templavoila\Domain\Model;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010 Tolleiv Nietsch <tolleiv.nietsch@typo3.org>
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
 * @author Tolleiv Nietsch <tolleiv.nietsch@typo3.org>
 */
class StaticDataStructure extends AbstractDataStructure {

	/**
	 * @var string
	 */
	protected $filename;

	/**
	 * @param integer $key
	 */
	public function __construct($key) {

		$conf = \Extension\Templavoila\Domain\Repository\DataStructureRepository::getStaticDatastructureConfiguration();

		if (!isset($conf[$key])) {
			throw new \InvalidArgumentException(
				'Argument was supposed to be an existing datastructure',
				1283192644
			);
		}

		$this->filename = $conf[$key]['path'];

		$this->setLabel($conf[$key]['title']);
		$this->setScope($conf[$key]['scope']);
		// path relative to typo3 maindir
		$this->setIcon('../' . $conf[$key]['icon']);
	}

	/**
	 *
	 * @return string;
	 */
	public function getStoragePids() {
		$pids = array();
		$toList = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTgetRows(
			'tx_templavoila_tmplobj.uid,tx_templavoila_tmplobj.pid',
			'tx_templavoila_tmplobj',
			'tx_templavoila_tmplobj.datastructure=' . \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->fullQuoteStr($this->filename, 'tx_templavoila_tmplobj') . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_templavoila_tmplobj')
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
		$file = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->filename);
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
	 *
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
		$file = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->filename);
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
		$file = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->filename);
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
	 *
	 * @return mixed
	 */
	public function getBeLayout() {
		$beLayout = FALSE;
		$file = substr(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->filename), 0, -3) . 'html';
		if (file_exists($file)) {
			$beLayout = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL($file);
		}

		return $beLayout;
	}

	/**
	 * @param void
	 *
	 * @return string
	 */
	public function getSortingFieldValue() {
		return $this->getLabel(); // required to resolve LLL texts
	}
}
