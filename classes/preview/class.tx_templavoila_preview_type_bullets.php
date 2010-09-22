<?php
/***************************************************************
* Copyright notice
*
* (c) 2010 Tolleiv Nietsch (info@tolleiv.de)
* (c) 2010 Steffen Kamper (info@sk-typo3.de)
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

require_once(t3lib_extMgm::extPath('templavoila') . 'classes/preview/class.tx_templavoila_preview_type_text.php');

class tx_templavoila_preview_type_bullets extends tx_templavoila_preview_type_text {

	protected $previewField = 'bodytext';

	/**
	 * (non-PHPdoc)
	 * @see classes/preview/tx_templavoila_preview_type_text#getPreviewData($row)
	 */
	protected function getPreviewData($row) {
		if (isset($this->parentObj->modTSconfig['properties']['previewDataMaxLen'])) {
			$max = intval($this->parentObj->modTSconfig['properties']['previewDataMaxLen']);
		} else {
			$max = 2000;
		}
		$htmlBullets = '';
		$bulletsArr = explode ("\n", $this->preparePreviewData($row['bodytext']));
		if (is_array ($bulletsArr)) {
			foreach ($bulletsArr as $listItem) {
				$processedItem = t3lib_div::fixed_lgd_cs(trim(strip_tags($listItem)), $max);
				$max -= strlen($processedItem);
				$htmlBullets .= '<li>' . htmlspecialchars($processedItem) . '</li>';
				if (!$max) {
					break;
				}
			}
		}
		return '<ul>' . $htmlBullets . '</ul>';
	}



}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/preview/class.tx_templavoila_preview_type_bullets.php'])    {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/preview/class.tx_templavoila_preview_type_bullets.php']);
}

?>