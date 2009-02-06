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
 * This class produces a code for forms
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_templavoila
 */
class tx_templavoila_formstab extends tx_templavoila_baseTab {

	function getTabContent() {
		$elements = array();

		$this->createAndAddElement($elements,
			'gfx/c_wiz/mailform.gif',
			$GLOBALS['LANG']->sL('LLL:EXT:templavoila/mod1/locallang_db_new_content_el.xml:forms_1_title'),
			$GLOBALS['LANG']->sL('LLL:EXT:templavoila/mod1/locallang_db_new_content_el.xml:forms_1_description'),
			'mailform',
			'&amp;defVals[tt_content][CType]=mailform&amp;defVals[tt_content][bodytext]=' . rawurlencode(trim($GLOBALS['LANG']->sL('LLL:EXT:templavoila/mod1/locallang_db_new_content_el.xml:forms_1_example')))
		);
		$this->createAndAddElement($elements,
			'gfx/c_wiz/searchform.gif',
			$GLOBALS['LANG']->sL('LLL:EXT:templavoila/mod1/locallang_db_new_content_el.xml:forms_2_title'),
			$GLOBALS['LANG']->sL('LLL:EXT:templavoila/mod1/locallang_db_new_content_el.xml:forms_2_description'),
			'search',
			'&amp;defVals[tt_content][CType]=mailform&amp;defVals[tt_content][bodytext]=' . rawurlencode(trim($GLOBALS['LANG']->sL('LLL:EXT:templavoila/mod1/locallang_db_new_content_el.xml:forms_2_example')))
		);
		$this->createAndAddElement($elements,
			'gfx/c_wiz/login_form.gif',
			$GLOBALS['LANG']->sL('LLL:EXT:templavoila/mod1/locallang_db_new_content_el.xml:forms_3_title'),
			$GLOBALS['LANG']->sL('LLL:EXT:templavoila/mod1/locallang_db_new_content_el.xml:forms_3_description'),
			'login',
			'&amp;defVals[tt_content][CType]=mailform&amp;defVals[tt_content][bodytext]=' . rawurlencode(trim($GLOBALS['LANG']->sL('LLL:EXT:templavoila/mod1/locallang_db_new_content_el.xml:forms_3_example')))
		);

		// Call hooks
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['newcewizard']['forms'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['newcewizard']['forms'] as $userFunc) {
				$params = array(
					'pObj' => &$this,
					'elements' => &$elements
				);
				t3lib_div::callUserFunction($userFunc, $params, $this);
			}
		}

		return $this->render($elements);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/tabs/class.tx_templavoila_formstab.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/tabs/class.tx_templavoila_formstab.php']);
}

?>