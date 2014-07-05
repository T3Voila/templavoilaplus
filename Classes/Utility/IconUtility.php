<?php
namespace Extension\Templavoila\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c)  2010 Tolleiv Nietsch <tolleiv.nietsch@typo3.org>
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
final class IconUtility {

	/**
	 * @param string $flagName
	 * @param array $options
	 *
	 * @return string
	 */
	public static function getFlagIconForLanguage($flagName, $options = array()) {
		$flagName = (strlen($flagName) > 0) ? $flagName : 'unknown';

		return \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('flags-' . ($flagName ? : 'unknown') , $options);
	}

	/**
	 * @param string $flagName
	 *
	 * @return string
	 */
	public static function getFlagIconFileForLanguage($flagName) {
		$flag = '';
		$flagName = (strlen($flagName) > 0) ? $flagName : 'unknown';

		// same dirty trick as for #17286 in Core
		if (is_file(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:t3skin/images/flags/' . $flagName . '.png', FALSE))) {
			// resolving extpath on its own because otherwise this might not return a relative path
			$flag = $GLOBALS['BACK_PATH'] . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('t3skin') . '/images/flags/' . $flagName . '.png';
		}

		return $flag;
	}
}
