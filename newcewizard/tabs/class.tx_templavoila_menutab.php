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
 * This class produces a code for text, text with images, images, form, etc
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_templavoila
 */

class tx_templavoila_menutab extends tx_templavoila_baseTab {

	function getTabContent() {
		$elements = array();

		$defVals = $this->pObj->getDefVals();

		t3lib_div::loadTCA('tt_content');
		foreach ($GLOBALS['TCA']['tt_content']['columns']['menu_type']['config']['items'] as $menuDef) {
			$icon = /*(count($menuDef) < 3 ? ($menuDef[1] == 2 ?*/ 'gfx/c_wiz/sitemap2.gif' /*: 'gfx/c_wiz/sitemap.gif') : $menuDef[2])*/;
			$this->createAndAddElement($elements,
				$icon,
				$GLOBALS['LANG']->sL($menuDef[0]),
				'',
				'menu',
				'&amp;defVals[tt_content][CType]=menu&amp;defVals[tt_content][menu_type]=' . $menuDef[1] . $defVals
			);
		}

		return $this->render($elements);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/tabs/class.tx_templavoila_menutab.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/tabs/class.tx_templavoila_menutab.php']);
}

?>