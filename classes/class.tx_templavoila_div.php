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
	 * Wrapper function for checking valid URL for redirect
	 *
	 * @param $url
	 */
	public static function sanitizeLocalUrl($url = '') {
		if (t3lib_div::compat_version('4.3')) {
			return t3lib_div::sanitizeLocalUrl($url);
		} elseif (t3lib_div::compat_version('4.2') && method_exists('t3lib_div', 'sanitizeLocalUrl')) {
			return t3lib_div::sanitizeLocalUrl($url);
		} else {
			return self::internalSanitizeLocalUrl($url);
		}

	}


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
		if (!t3lib_div::compat_version('4.1')) {
			if ($decodedUrl !== t3lib_div::removeXSS($decodedUrl)) {
				$decodedUrl = '';
			}
		}
		if (!empty($url) && $decodedUrl !== '') {
			$testAbsoluteUrl = t3lib_div::resolveBackPath($decodedUrl);
			$testRelativeUrl = t3lib_div::resolveBackPath(
				t3lib_div::dirname(t3lib_div::getIndpEnv('SCRIPT_NAME')) . '/' . $decodedUrl
			);

				// Pass if URL is on the current host:
			if (t3lib_div::isValidUrl($decodedUrl)) {
				if (t3lib_div::isOnCurrentHost($decodedUrl) && strpos($decodedUrl, t3lib_div::getIndpEnv('TYPO3_SITE_URL')) === 0) {
					$sanitizedUrl = $url;
				}
				// Pass if URL is an absolute file path:
			} elseif (t3lib_div::isAbsPath($decodedUrl) && t3lib_div::isAllowedAbsPath($decodedUrl)) {
				$sanitizedUrl = $url;
				// Pass if URL is absolute and below TYPO3 base directory:
			} elseif (strpos($testAbsoluteUrl, t3lib_div::getIndpEnv('TYPO3_SITE_PATH')) === 0 && substr($decodedUrl, 0, 1) === '/') {
				$sanitizedUrl = $url;
				// Pass if URL is relative and below TYPO3 base directory:
			} elseif (strpos($testRelativeUrl, t3lib_div::getIndpEnv('TYPO3_SITE_PATH')) === 0 && substr($decodedUrl, 0, 1) !== '/') {
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

}
?>