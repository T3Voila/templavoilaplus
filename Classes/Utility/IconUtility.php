<?php
namespace Extension\Templavoila\Utility;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Class which adds an additional layer for icon creation
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
