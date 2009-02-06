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

class tx_templavoila_standardelementstab extends tx_templavoila_baseTab {

	function getTabContent() {
		$elements = array();

		$defVals = $this->pObj->getDefVals();
		// Text
		$this->createAndAddElement($elements,
			'gfx/c_wiz/regular_text.gif',
			$GLOBALS['LANG']->getLL('common_1_title'),
			$GLOBALS['LANG']->getLL('common_1_description'),
			'text',
			'&amp;defVals[tt_content][CType]=text' . $defVals
		);
		// Text with image
		$this->createAndAddElement($elements,
			'gfx/c_wiz/text_image_below.gif',
			$GLOBALS['LANG']->getLL('newcewizard.text_with_image'),
			$GLOBALS['LANG']->getLL('newcewizard.text_with_image_desc'),
			'textpic',
			'&amp;defVals[tt_content][CType]=textpic&amp;defVals[tt_content][imageorient]=17' . $defVals
		);
		// Only images
		$this->createAndAddElement($elements,
			'gfx/c_wiz/images_only.gif',
			$GLOBALS['LANG']->getLL('common_4_title'),
			$GLOBALS['LANG']->getLL('common_4_description'),
			'image',
			'&amp;defVals[tt_content][CType]=image&amp;defVals[tt_content][imagecols]=2' . $defVals
		);
		// Bullet list
		$this->createAndAddElement($elements,
			'gfx/c_wiz/bullet_list.gif',
			$GLOBALS['LANG']->getLL('common_5_title'),
			$GLOBALS['LANG']->getLL('common_5_description'),
			'bullets',
			'&amp;defVals[tt_content][CType]=bullets' . $defVals
		);
		// Table
		$this->createAndAddElement($elements,
			'gfx/c_wiz/table.gif',
			$GLOBALS['LANG']->getLL('common_6_title'),
			$GLOBALS['LANG']->getLL('common_6_description'),
			'table',
			'&amp;defVals[tt_content][CType]=table' . $defVals
		);
		// File links
		$this->createAndAddElement($elements,
			'gfx/c_wiz/filelinks.gif',
			$GLOBALS['LANG']->getLL('special_1_title'),
			$GLOBALS['LANG']->getLL('special_1_description'),
			'uploads',
			'&amp;defVals[tt_content][CType]=uploads' . $defVals
		);
		// Multimedia
		$this->createAndAddElement($elements,
			'gfx/c_wiz/multimedia.gif',
			$GLOBALS['LANG']->getLL('special_2_title'),
			$GLOBALS['LANG']->getLL('special_2_description'),
			'multimedia',
			'&amp;defVals[tt_content][CType]=multimedia'.$defVals
		);
		// HTML
		$this->createAndAddElement($elements,
			'gfx/c_wiz/html.gif',
			$GLOBALS['LANG']->getLL('special_4_title'),
			$GLOBALS['LANG']->getLL('special_4_description'),
			'html',
			'&amp;defVals[tt_content][CType]=html' . $defVals
		);

		$content = '';
		if (count($elements) > 0) {
			// Create view and render content
			$viewClass = t3lib_div::makeInstanceClassName('tx_templavoila_tabView');
			$view = new $viewClass($elements, $this->pObj);
			/* @var $view tx_templavoila_tabView */
			$content = $view->render();
		}
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/tabs/class.tx_templavoila_standardelementstab.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/tabs/class.tx_templavoila_standardelementstab.php']);
}

?>