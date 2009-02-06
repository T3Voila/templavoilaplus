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

class tx_templavoila_recenttab extends tx_templavoila_baseTab {

	protected $count = 0;

	function getTabContent() {
		$elements = array();

		if (t3lib_div::_GP('clear_recent')) {
			$GLOBALS['BE_USER']->uc['tx_templavoila_recentce'] = array();
			$GLOBALS['BE_USER']->writeUC();
		}
		else {
			$list = (array)@unserialize($GLOBALS['BE_USER']->uc['tx_templavoila_recentce']);
			foreach ($list as $obj) {
				if ($obj instanceof tx_templavoila_contentElementDescriptor) {
					$elements[] = $obj;
				}
				if (count($elements) == 10) {
					break;
				}
			}
		}

		$this->count = count($elements);
		if ($this->count) {
			$content = $this->render($elements);
			$content .= '<div class="str-content">' .
				'<img ' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],
				'gfx/deletedok.gif') . ' align="left" alt="" /> ' .
				'<a href="' . t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT') .
				'?clear_recent=1&id=' . $this->pObj->getId() . '">' .
				$GLOBALS['LANG']->getLL('newcewizard.clear_recent') .
				'</a>' .
			'</div>';
		}
		else {
			$content = '<div class="str-content">' .
				str_replace('\n', '<br /><br />', $GLOBALS['LANG']->getLL('newcewizard.no_recent_yet')) .
			'</div>';
		}
		return $content;
	}

	function getElementCount() {
		return $this->count;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/tabs/class.tx_templavoila_recenttab.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/tabs/class.tx_templavoila_recenttab.php']);
}

?>