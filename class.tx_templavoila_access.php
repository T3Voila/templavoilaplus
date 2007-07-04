<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2006 Dmitry Dulepov (dmitry@typo3.org)
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
 * Class 'tx_templavoila_access' for the templavoila extension.
 *
 * $Id: $
 *
 * @author     Dmitry Dulepov <dmitry@typo3.org>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   55: class tx_templavoila_access
 *   64:     function recordEditAccessInternals($params, $ref)
 *   93:     function checkObjectAccess($table, $uid, $be_user)
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_typo3.'sysext/lang/lang.php');

/**
 * Class being included by UserAuthGroup using a hook
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package TYPO3
 * @subpackage templavoila
 */
class tx_templavoila_access {

	/**
	 * Checks if user is allowed to modify FCE.
	 *
	 * @param	array		$params	Parameters
	 * @param	object		$ref	Parent object
	 * @return	boolean		<code>true</code> if change is allowed
	 */
	function recordEditAccessInternals($params, $ref) {
		if ($params['table'] == 'tt_content' && is_array($params['idOrRow']) && $params['idOrRow']['CType'] == 'templavoila_pi1') {
			if (!$ref) {
				$user = &$GLOBALS['BE_USER'];
			}
			else {
				$user = &$ref;
			}
			if ($user->isAdmin()) {
				return true;
			}
			$error = '';
			if (!$this->checkObjectAccess('tx_templavoila_datastructure', $params['idOrRow']['tx_templavoila_ds'], $ref)) {
				$error = 'access_noDSaccess';
			}
			elseif (!$this->checkObjectAccess('tx_templavoila_tmplobj', $params['idOrRow']['tx_templavoila_to'], $ref)) {
				 $error = 'access_noTOaccess';
			}
			else {
				return true;
			}
			if ($ref) {
				if ($GLOBALS['LANG']) {
					$lang = &$GLOBALS['LANG'];
				}
				else {
					$lang = t3lib_div::makeInstance('language');
					$lang->init($BE_USER->uc['lang']);
				}
				$ref->errorMsg = $lang->sL('LLL:EXT:templavoila/locallang_access.xml:' . $error);
			}
			return false;
		}
		return true;
	}

	/**
	 * Checks user's access to given database object
	 *
	 * @param	string		$table	Table name
	 * @param	int		$uid	UID of the record
	 * @param	object		$be_user	BE user object
	 * @return	boolean		<code>true</code> if access is allowed
	 */
	function checkObjectAccess($table, $uid, $be_user) {
		if (!$be_user) {
			$be_user = $GLOBALS['BE_USER'];
		}
		if (!$be_user->isAdmin()) {
			$prefLen = strlen($table) + 1;
			foreach($be_user->userGroups as $group) {
				$items = t3lib_div::trimExplode(',', $group['tx_templavoila_access'], 1);
				foreach ($items as $ref) {
					if (strstr($ref, $table)) {
						if ($uid == intval(substr($ref, $prefLen))) {
							return false;
						}
					}
				}
			}
		}
		return true;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_access.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_access.php']);
}

?>