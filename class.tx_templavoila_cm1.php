<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004, 2005 Kasper Skaarhoj (kasper@typo3.com)
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
 * Addition of an item to the clickmenu
 *
 * $Id$
 *
 * @author		Kasper Skaarhoj <kasper@typo3.com>
 * @coauthor 	Robert Lemke <robert@typo3.org>
 */

/**
 * Class which will add menu items to click menus for the extension TemplaVoila
 *
 * @author 		Kasper Skaarhoj <kasper@typo3.com>
 * @coauthor 	Robert Lemke <robert@typo3.org>
 * @package 	TYPO3
 * @subpackage	tx_templavoila
 */
class tx_templavoila_cm1 {

	/**
	 * Main function, adding items to the click menu array.
	 *
	 * @param	object		Reference to the parent object of the clickmenu class which calls this function
	 * @param	array		The current array of menu items - you have to add or remove items to this array in this function. Thats the point...
	 * @param	string		The database table OR filename
	 * @param	integer		For database tables, the UID
	 * @return	array		The modified menu array.
	 */
	function main(&$backRef, $menuItems, $table, $uid) {
		global $BE_USER, $LANG, $TYPO3_DB;

		$localItems = array();
		if (!$backRef->cmLevel)	{
			$LL = $LANG->includeLLFile(t3lib_extMgm::extPath('templavoila').'locallang.xml', 0);

				// Adding link for Mapping tool:
			if (@is_file($table)) {
				if ($BE_USER->isAdmin()) {
					if (function_exists('finfo_open')) {
						$finfoMode = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
						$fi = finfo_open($finfoMode);
						$mimeInformation = @finfo_file($fi, $table);
						$enabled = FALSE;
						if (t3lib_div::isFirstPartOfStr($mimeInformation, 'text/html') ||
							t3lib_div::isFirstPartOfStr($mimeInformation, 'application/xml')) {
							$enabled = TRUE;
						}
						finfo_close($fi);
					}
					else {
						$pi = @pathinfo($table);
						$enabled = preg_match('/(html?|tmpl|xml)/', $pi['extension']);
					}
					if ($enabled) {
						$url = t3lib_extMgm::extRelPath('templavoila').'cm1/index.php?file='.rawurlencode($table);
						$localItems[] = $backRef->linkItem(
							$LANG->getLLL('cm1_title',$LL,1),
							$backRef->excludeIcon('<img src="'.$backRef->backPath.t3lib_extMgm::extRelPath('templavoila').'cm1/cm_icon.gif" width="15" height="12" border="0" align="top" alt="" />'),
							$backRef->urlRefForCM($url, 'returnUrl'),
							1	// Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
						);
					}
				}
			}
			elseif (t3lib_div::inList('tx_templavoila_tmplobj,tx_templavoila_datastructure,tx_templavoila_content',$table)) {
				$url = t3lib_extMgm::extRelPath('templavoila').'cm1/index.php?table='.rawurlencode($table).'&uid='.$uid.'&_reload_from=1';
				$localItems[] = $backRef->linkItem(
					$LANG->getLLL('cm1_title',$LL,1),
					$backRef->excludeIcon('<img src="'.$backRef->backPath.t3lib_extMgm::extRelPath('templavoila').'cm1/cm_icon.gif" width="15" height="12" border="0" align="top" alt="" />'),
					$backRef->urlRefForCM($url, 'returnUrl'),
					1	// Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
				);
			}

			$isTVelement = ('tt_content' == $table && $backRef->rec['CType']=='templavoila_pi1' || 'pages' == $table) && $backRef->rec['tx_templavoila_flex'];

				// Adding link for "View: Sub elements":
			if ($table == 'tt_content' && $isTVelement) {
				$localItems = array();

				$url = t3lib_extMgm::extRelPath('templavoila').'mod1/index.php?id='.intval($backRef->rec['pid']).
							'&altRoot[table]='.rawurlencode($table).
							'&altRoot[uid]='.$uid.
							'&altRoot[field_flex]=tx_templavoila_flex';

				$localItems[] = $backRef->linkItem(
					$LANG->getLLL('cm1_viewsubelements',$LL,1),
					$backRef->excludeIcon('<img src="'.$backRef->backPath.t3lib_extMgm::extRelPath('templavoila').'cm1/cm_icon.gif" width="15" height="12" border="0" align="top" alt="" />'),
					$backRef->urlRefForCM($url, 'returnUrl'),
					1	// Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
				);

			}

				// Adding link for "View: Flexform XML" (admin only):
			if ($BE_USER->isAdmin() && $isTVelement) {
				$url = t3lib_extMgm::extRelPath('templavoila').'cm2/index.php?'.
							'&viewRec[table]='.rawurlencode($table).
							'&viewRec[uid]='.$uid.
							'&viewRec[field_flex]=tx_templavoila_flex';

				$localItems[] = $backRef->linkItem(
					$LANG->getLLL('cm1_viewflexformxml',$LL,1),
					$backRef->excludeIcon('<img src="'.$backRef->backPath.t3lib_extMgm::extRelPath('templavoila').'cm2/cm_icon.gif" width="15" height="12" border="0" align="top" alt="" />'),
					$backRef->urlRefForCM($url, 'returnUrl'),
					1	// Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
				);
			}

				// Adding link for "View: DS/TO" (admin only):
			if ($BE_USER->isAdmin() && $isTVelement) {

				if (tx_templavoila_div::canBeInterpretedAsInteger($backRef->rec['tx_templavoila_ds']))	{
					$url = t3lib_extMgm::extRelPath('templavoila').'cm1/index.php?'.
								'table=tx_templavoila_datastructure&uid='.$backRef->rec['tx_templavoila_ds'];

					$localItems[] = $backRef->linkItem(
						$LANG->getLLL('cm_viewdsto',$LL,1).' ['.$backRef->rec['tx_templavoila_ds'].'/'.$backRef->rec['tx_templavoila_to'].']',
						$backRef->excludeIcon('<img src="'.$backRef->backPath.t3lib_extMgm::extRelPath('templavoila').'cm2/cm_icon.gif" width="15" height="12" border="0" align="top" alt="" />'),
						$backRef->urlRefForCM($url, 'returnUrl'),
						1	// Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
					);
				}
			}

#			if ($table=='tt_content') {
#					// Adding link for "Pages using this element":
#				$localItems[] = $backRef->linkItem(
#					$LANG->getLLL('cm1_pagesusingthiselement',$LL),
#					$backRef->excludeIcon('<img src="'.t3lib_extMgm::extRelPath('templavoila').'cm1/cm_icon_activate.gif" width="15" height="12" border=0 align=top>'),
#					"top.loadTopMenu('".t3lib_div::linkThisScript()."&cmLevel=1&subname=tx_templavoila_cm1_pagesusingthiselement');return false;",
#					0,
#					1
#				);
#			}
		} else {
			if (t3lib_div::_GP('subname') == 'tx_templavoila_cm1_pagesusingthiselement') {
				$menuItems = array ();
				$url = t3lib_extMgm::extRelPath('templavoila').'mod1/index.php?id=';

					// Generate a list of pages where this element is also being used:
				$res = $TYPO3_DB->exec_SELECTquery ('*', 'tx_templavoila_elementreferences', 'uid='.$backRef->rec['uid']);
				if ($res) {
					while (false != ($referenceRecord = $TYPO3_DB->sql_fetch_assoc ($res))) {
						$pageRecord = t3lib_beFunc::getRecord('pages', $referenceRecord['pid']);
						$icon = t3lib_iconWorks::getSpriteIconForRecord('pages', $pageRecord);
	// To do: Display language flag icon and jump to correct language
#						if ($referenceRecord['lkey'] != 'lDEF') {
#							$icon .= ' lKey:'.$referenceRecord['lkey'];
#						} elseif ($referenceRecord['vkey'] != 'vDEF') {
#							$icon .= ' vKey:'.$referenceRecord['vkey'];
#						}
						if (is_array ($pageRecord)) {
							$menuItems[] = $backRef->linkItem(
								$icon,
								t3lib_beFunc::getRecordTitle('pages', $pageRecord, 1),
								$backRef->urlRefForCM($url.$pageRecord['uid'], 'returnUrl'),
								1	// Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
							);
						}
					}
				}
			}
		}

			// Simply merges the two arrays together and returns ...
		if (count($localItems))	{
			$menuItems = array_merge($menuItems,$localItems);
		}

		return $menuItems;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_cm1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_cm1.php']);
}
?>