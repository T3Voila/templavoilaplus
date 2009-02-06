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

class tx_templavoila_pluginstab extends tx_templavoila_baseTab {

	protected $pluginItems;

	function getTabContent() {
		$elements = array();

		$defVals = $this->pObj->getDefVals();

		// All other plugins

		$this->createAndAddElement($elements,
			'gfx/c_wiz/user_defined.gif',
			$GLOBALS['LANG']->sL('LLL:EXT:cms/layout/locallang_db_new_content_el.xml:plugins_general_title'),
			'',//$GLOBALS['LANG']->sL('LLL:EXT:cms/layout/locallang_db_new_content_el.xml:plugins_general_description'),
			'list',
			'&amp;defVals[tt_content][CType]=list'
		);

		$wizardItems = array();
		if (is_array($GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses']))	{
			reset($GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses']);
			while(list($class,$path)=each($GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses']))	{
				$modObj = t3lib_div::makeInstance($class);
				$wizardItems = $modObj->proc($wizardItems);
			}
		}

		foreach ($wizardItems as $item) {
			$this->createAndAddElement($elements, $item['icon'], $item['title'],
				$item['description'], 'list', $item['params']);
		}

/*
		t3lib_div::loadTCA('tt_content');
		$this->pluginItems = $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'];
		foreach ($this->pluginItems as $key => $plugin) {
			$this->pluginItems[$key][0] = $GLOBALS['LANG']->sL($plugin[0]);
		}
		uksort($this->pluginItems, array($this, 'sortPlugins'));
		foreach ($this->pluginItems as $plugin) {
			$title = $plugin[0];
			if ($title) {
				$icon = (count($plugin) < 3 ? 'gfx/c_wiz/user_defined.gif' : $plugin[2]);
				$this->createAndAddElement($elements,
					t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], $icon, '', 1),
					$title,
					'',
					'list',
					'&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=' . $plugin[1] . $defVals
				);
			}
		}
*/
		return $this->render($elements);
	}

	public function sortPlugins($key1, $key2) {
		return strcasecmp($this->pluginItems[$key1][0], $this->pluginItems[$key2][1]);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/tabs/class.tx_templavoila_pluginstab.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/tabs/class.tx_templavoila_pluginstab.php']);
}

?>