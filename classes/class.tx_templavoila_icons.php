<?php
/***************************************************************
* Copyright notice
*
* (c)  2010 Tolleiv Nietsch <nietsch@aoemedia.de>
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
 * Class which adds an additional layer for icon creation
 *
 */
final class tx_templavoila_icons {

	/**
	 *
	 * @param string $lang
	 * @return string
	 */
	public static function getFlagIconForLanguage($flagName, $options = array()) {

		$flag = null;
		if (!strlen($flagName)) {
			$flagName = 'unknown';
		}
		if (tx_templavoila_div::convertVersionNumberToInteger(TYPO3_version) < 4005000) {
		   if ($flagName == 'unknown') {
				   $flagName = $flagName . '.gif';
		   } elseif($flagName == 'multiple') {
				   $flagName = 'multi-language.gif';
		   }
		   $alt = isset($options['alt']) ? ' alt="' . $options['alt'] . '"' : ' alt=""';
		   $title = isset($options['title']) ? ' title="' . $options['title'] . '"' : '';
		   $flag = '<img src="' . self::getFlagIconFileForLanguage($flagName) . '"'. $title . $alt .'/>';
		} else {
		   $flag = t3lib_iconWorks::getSpriteIcon('flags-' . $flagName, $options);
		}
		return $flag;

	}

	/**
	 *
	 * @param string $lang
	 * @return string
	 */
	public static function getFlagIconFileForLanguage($flagName) {

		$flag = null;
		if (!strlen($flagName)) {
			$flagName = 'unknown';
		}
		if (tx_templavoila_div::convertVersionNumberToInteger(TYPO3_version) < 4005000) {
			$flagAbsPath = t3lib_div::getFileAbsFileName($GLOBALS['TCA']['sys_language']['columns']['flag']['config']['fileFolder']);
			$flagIconPath = $GLOBALS['BACK_PATH'] . '../' . substr($flagAbsPath, strlen(PATH_site));
			if (is_file($flagAbsPath . $flagName)) {
				$flag = $flagIconPath . $flagName;
			}
		} else {
				// same dirty trick as for #17286 in Core
			if(is_file(t3lib_div::getFileAbsFileName('EXT:t3skin/images/flags/'. $flagName . '.png', FALSE))) {
				   // resolving extpath on its own because otherwise this might not return a relative path
				$flag = $GLOBALS['BACK_PATH'] . t3lib_extMgm::extRelPath('t3skin') . '/images/flags/' . $flagName . '.png';
			}
		}
		return $flag;
	}
}
?>
