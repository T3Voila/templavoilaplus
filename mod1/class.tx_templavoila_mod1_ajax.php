<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Nikolas Hagelstein <lists@shr-now.de>
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
 * Class 'tx_templavoila_mod1_ajax' for the 'templavoila' extension.
 *
 * $Id$
 *
 * @author Nikolas Hagelstein <lists@shr-now.de>
 *
 */
class tx_templavoila_mod1_ajax {
	private $apiObj;

	public function __construct() {
		$this->apiObj = t3lib_div::makeInstance('tx_templavoila_api');
	}

	/**
	 * Performs a move action for the requested element
	 *
	 * @param	array		$params
	 * @param	object		$ajaxObj
	 * @return	void
	 */
	public function moveRecord($params, &$ajaxObj) {

		$sourcePointer = $this->apiObj
			->flexform_getPointerFromString(t3lib_div::_GP('source'));

		$destinationPointer = $this->apiObj
			->flexform_getPointerFromString(t3lib_div::_GP('destination'));

		$this->apiObj->moveElement($sourcePointer, $destinationPointer);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/class.tx_templavoila_mod1_ajax.php'])    {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/class.tx_templavoila_mod1_ajax.php']);
}
?>
