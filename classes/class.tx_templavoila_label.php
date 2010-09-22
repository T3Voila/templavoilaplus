<?php
/***************************************************************
*  Copyright notice
*
*  Copyright (c) 2010, Michael Klapper <michael.klapper@aoemedia.de>
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
 * Class 'tx_templavoila_label' for the 'templavoila' extension.
 *
 * This library contains several functions for displaying the labels in the list view.
 *
 * @author      Michael Klapper <michael.klapper@aoemedia.de>
 *
 * @package     TYPO3
 * @subpackage  tx_templavoila
 */
class tx_templavoila_label {

	/**
	 * Retrive the label for TCAFORM title attribute.
	 *
	 * @param array $params Current record array
	 * @param object
	 *
	 * @access  public
	 * @return  void
	 *
	 * @author  Michael Klapper <michael.klapper@aoemedia.de>
	 */
	public function getLabel(&$params, &$pObj) {
		$params['title'] = $GLOBALS['LANG']->sL($params['row']['title']);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/class.tx_templavoila_label.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/class.tx_templavoila_label.php']);
}

?>
