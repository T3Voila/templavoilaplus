<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Dmitry Dulepov <dmitry@typo3.org>
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * $Id$
 */


/**
 * This class is a base class for all tabs
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_templavoila
 */
abstract class tx_templavoila_baseTab {

	/**
	 * Document. This is a shortcut to $this->pObj->doc.
	 *
	 * @var	template
	 */
	protected	$doc;

	/**
	 * Parent object
	 *
	 * @var	tx_templavoila_newcewizard
	 */
	protected	$pObj;

	/**
	 * Keeps element access information. Key is type, value is boolean value.
	 *
	 * @var	array
	 */
	protected	$elementAccessCache = array();

	/**
	 * Creates an instance of this class
	 *
	 * @param	template	$doc	Document
	 * @param	tx_templavoila_newcewizard	$pObj	Content element wizard
	 */
	public function __construct(tx_templavoila_newcewizard &$pObj) {
		$this->doc = &$pObj->getDoc();
		$this->pObj = $pObj;
	}

	/**
	 * Generates content for this tab
	 *
	 * @return	string	Generated HTML
	 */
	abstract public function getTabContent();


	/**
	 * Checks access to the element type and creates element descriptor
	 *
	 * @param	array	$elements	Elements array to add to
	 * @param	string	$image	Image name
	 * @param	string	$title	Title
	 * @param	string	$description	Description
	 * @param	string	$cType	cType identifier
	 * @param	string	$defValues	Default values
	 * @return	tx_templavoila_contentElementDescriptor	Descriptor or null
	 */
	protected function createAndAddElement(array &$elements, $image, $title, $description, $cType, $defValues) {
		$element = $this->createElement($image, $title, $description, $cType, $defValues);
		if (!is_null($element)) {
			$elements[] = $element;
		}
	}

	/**
	 * Checks access to the element type and creates element descriptor
	 *
	 * @param	string	$image	Image name
	 * @param	string	$title	Title
	 * @param	string	$description	Description
	 * @param	string	$cType	cType identifier
	 * @param	string	$parameters	Parameters
	 * @return	tx_templavoila_contentElementDescriptor	Descriptor or null
	 */
	protected function createElement($image, $title, $description, $cType, $parameters) {
		$result = null;
		if (!isset($this->elementAccessCache[$cType])) {
			$this->elementAccessCache[$cType] = $GLOBALS['BE_USER']->checkAuthMode('tt_content', 'CType', $cType, 'explicitDeny');
		}
		if ($this->elementAccessCache[$cType]) {
			$result = t3lib_div::makeInstance('tx_templavoila_contentElementDescriptor');
			/* @var $item tx_templavoila_contentElementDescriptor */
			$result->setCType($cType);
			$result->setDescription($description);
			$result->setImage($image);
			$result->setParameters($parameters);
			$result->setTitle($title);
		}
		return $result;
	}

	/**
	 * Helper function to perform standard rendering using default view
	 *
	 * @param	array	$elements	Array of elements as filled by createAndAddElement()
	 * @return	string	Generated HTML
	 */
	protected function render(array $elements) {
		$content = '';
		if (count($elements) > 0) {
			// Create view and render content
			if(version_compare(TYPO3_version,'4.3.0','<')) {
				$viewClass = t3lib_div::makeInstanceClassName('tx_templavoila_tabView');
				$view = new $viewClass($elements, $this->pObj);
			} else {
				$view = t3lib_div::makeInstance('tx_templavoila_tabView', $elements, $this->pObj);
			}
			/* @var $view tx_templavoila_tabView */
			$content = $view->render();
		}
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/tabs/class.tx_templavoila_basetab.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/tabs/class.tx_templavoila_basetab.php']);
}

?>