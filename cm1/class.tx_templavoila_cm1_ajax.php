<?php
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
 * Ajax class for displaying content form a file
 */
class tx_templavoila_cm1_ajax {

	/**
	 * Return the content of the current "displayFile"
	 *
	 * @param array $params
	 * @param object $ajaxObj
	 *
	 * @return void
	 */
	public function getDisplayFileContent($params, &$ajaxObj) {
		$session = \Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->getSessionData(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('key'));
		echo \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($session['displayFile']));
	}
}
