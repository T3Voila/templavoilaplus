<?php
/***************************************************************
* Copyright notice
*
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

/**
 * Class with static functions for templavoila.
 *
 * @author	Steffen Kamper  <info@sk-typo3.de>
 */
final class tx_templavoila_div {

	/**
	 * Checks if a given string is a valid frame URL to be loaded in the
	 * backend.
	 *
	 * @param string $url potential URL to check
	 *
	 * @return string either $url if $url is considered to be harmless, or an
	 *                empty string otherwise
	 */
	private static function internalSanitizeLocalUrl($url = '') {
		$sanitizedUrl = '';
		$decodedUrl = rawurldecode($url);
		if ($decodedUrl !== t3lib_div::removeXSS($decodedUrl)) {
			$decodedUrl = '';
		}
		if (!empty($url) && $decodedUrl !== '') {
			$testAbsoluteUrl = t3lib_div::resolveBackPath($decodedUrl);
			$testRelativeUrl = t3lib_div::resolveBackPath(
				t3lib_div::dirname(t3lib_div::getIndpEnv('SCRIPT_NAME')) . '/' . $decodedUrl
			);

				// That's what's usually carried in TYPO3_SITE_PATH
			$typo3_site_path = substr(t3lib_div::getIndpEnv('TYPO3_SITE_URL'), strlen(t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST')));

				// Pass if URL is on the current host:
			if (self::isValidUrl($decodedUrl)) {
				if (self::isOnCurrentHost($decodedUrl) && strpos($decodedUrl, t3lib_div::getIndpEnv('TYPO3_SITE_URL')) === 0) {
					$sanitizedUrl = $url;
				}
				// Pass if URL is an absolute file path:
			} elseif (t3lib_div::isAbsPath($decodedUrl) && t3lib_div::isAllowedAbsPath($decodedUrl)) {
				$sanitizedUrl = $url;
				// Pass if URL is absolute and below TYPO3 base directory:
			} elseif (strpos($testAbsoluteUrl, $typo3_site_path) === 0 && substr($decodedUrl, 0, 1) === '/') {
				$sanitizedUrl = $url;
				// Pass if URL is relative and below TYPO3 base directory:
			} elseif (strpos($testRelativeUrl, $typo3_site_path) === 0 && substr($decodedUrl, 0, 1) !== '/') {
				$sanitizedUrl = $url;
			}
		}

		if (!empty($url) && empty($sanitizedUrl)) {
			t3lib_div::sysLog('The URL "' . $url . '" is not considered to be local and was denied.', 'Core', t3lib_div::SYSLOG_SEVERITY_NOTICE);
		}

		return $sanitizedUrl;
	}

	/**
	 * Checks if a given string is a Uniform Resource Locator (URL).
	 *
	 * @param	string		$url: The URL to be validated
	 * @return	boolean		Whether the given URL is valid
	 */
	private static function isValidUrl($url) {
		return (filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED) !== false);
	}


	/**
	 * Checks if a given URL matches the host that currently handles this HTTP request.
	 * Scheme, hostname and (optional) port of the given URL are compared.
	 *
	 * @param	string		$url: URL to compare with the TYPO3 request host
	 * @return	boolean		Whether the URL matches the TYPO3 request host
	 */
	private static function isOnCurrentHost($url) {
		return (stripos($url . '/', t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . '/') === 0);
	}

	public static function getDenyListForUser() {
		$denyItems = array();
		foreach ($GLOBALS['BE_USER']->userGroups as $group) {
			$groupDenyItems = t3lib_div::trimExplode(',', $group['tx_templavoila_access'], true);
			$denyItems = array_merge($denyItems, $groupDenyItems);
		}
		return $denyItems;
	}

	/**
	 * Get a list of referencing elements other than the given pid.
	 *
	 * @param array    array with tablename and uid for a element
	 * @param int      the suppoed source-pid
	 * @param int      recursion limiter
	 * @param array    array containing a list of the actual references
	 * @return boolean true if there are other references for this element
	 */
	public static function getElementForeignReferences($element, $pid, $recursion=99, &$references=null) {
		if (!$recursion) {
			return FALSE;
		}
		if (!is_array($references)) {
			$references = array();
		}
		$refrows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'sys_refindex',
			'ref_table='.$GLOBALS['TYPO3_DB']->fullQuoteStr($element['table'],'sys_refindex').
				' AND ref_uid='.intval($element['uid']).
				' AND deleted=0'
		);

		if(is_array($refrows)) {
			foreach($refrows as $ref) {
				if(strcmp($ref['tablename'],'pages')===0) {
					$references[$ref['tablename']][$ref['recuid']] = TRUE;
				} else {
					if (!isset($references[$ref['tablename']][$ref['recuid']])) {
							// initialize with false to avoid recursion without affecting inner OR combinations
						$references[$ref['tablename']][$ref['recuid']] = FALSE;
						$references[$ref['tablename']][$ref['recuid']] = self::hasElementForeignReferences(array('table'=>$ref['tablename'], 'uid'=>$ref['recuid']), $pid, $recursion-1, $references);
					}
				}
			}
		}

		unset($references['pages'][$pid]);

 		return $references;
	}


	/**
	 * Checks if a element is referenced from other pages / elements on other pages than his own.
	 *
	 * @param array    array with tablename and uid for a element
	 * @param int      the suppoed source-pid
	 * @param int      recursion limiter
	 * @param array    array containing a list of the actual references
	 * @return boolean true if there are other references for this element
	 */
	public static function hasElementForeignReferences($element, $pid, $recursion=99, &$references=null) {
		$references = self::getElementForeignReferences($element, $pid, $recursion, $references);
		$foreignRefs = FALSE;
		if (is_array($references)) {
			unset($references['pages'][$pid]);
			$foreignRefs = count($references['pages']) || count($references['pages_language_overlay']);
		}
		return $foreignRefs;
	}

	/**
	 * Returns an integer from a three part version number, eg '4.12.3' -> 4012003
	 * Compatibility layer to make sure TV works in systems < 4.6
	 *
	 * @see t3lib_utility_VersionNumber::convertVersionNumberToInteger
	 * @param $versionNumber string Version number on format x.x.x
	 * @return integer Integer version of version number (where each part can count to 999)
	 */
	public static function convertVersionNumberToInteger($version) {
		$result = 0;
		if (class_exists('t3lib_utility_VersionNumber')) {
			$result = t3lib_utility_VersionNumber::convertVersionNumberToInteger($version);
		} else {
			$result = t3lib_div::int_from_ver($version);
		}
		return $result;
	}


	/**
	 * Forces the integer $theInt into the boundaries of $min and $max. If the $theInt is FALSE then the $defaultValue is applied.
	 *
	 * @see t3lib_utility_Math::canBeInterpretedAsInteger
	 * @param $theInt integer Input value
	 * @param $min integer Lower limit
	 * @param $max integer Higher limit
	 * @param $defaultValue integer Default value if input is FALSE.
	 * @return integer The input value forced into the boundaries of $min and $max
	 */
	public static function canBeInterpretedAsInteger($var) {

		if (class_exists('t3lib_utility_Math')) {
			$result = t3lib_utility_Math::canBeInterpretedAsInteger($var);
		} else {
			$result = t3lib_div::testInt($var);
		}
		return $result;
	}
}
?>