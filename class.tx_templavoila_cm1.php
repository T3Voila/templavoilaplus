<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   61: class tx_templavoila_cm1
 *   72:     function main(&$backRef,$menuItems,$table,$uid)
 *  107:     function includeLL()
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
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
		global $BE_USER, $TCA, $LANG, $TYPO3_DB;

		$localItems = Array();
		if (!$backRef->cmLevel)	{
			$LL = $this->includeLL();
				// Remove items that are not relevant in this context:
			if (t3lib_div::GPvar('callingScriptId') == 'ext/templavoila/mod1/index.php')	{
				unset($menuItems['new']);
				unset($menuItems['copy']);
				unset($menuItems['cut']);
				unset($menuItems['pasteinto']);
				unset($menuItems['pasteafter']);
				unset($menuItems['delete']);

				$lastWasSpacer = FALSE;
				foreach($menuItems as $kI => $vI)	{
					if ($vI == 'spacer')	{
						if ($lastWasSpacer)	{
							unset($menuItems[$kI]);
						}
						$lastWasSpacer = TRUE;
					} else {
						$lastWasSpacer = FALSE;
					}
				}
				if ($lastWasSpacer)	{
					unset($menuItems[$kI]);
				}
			}

				// Adding link for Mapping tool:
			if (t3lib_div::inList('tx_templavoila_tmplobj,tx_templavoila_datastructure,tx_templavoila_content',$table) || @is_file($table))	{
				$localItems = Array();

				if (@is_file($table))	{
					$url = t3lib_extMgm::extRelPath('templavoila').'cm1/index.php?file='.rawurlencode($table);	//.'&mapElPath='.rawurlencode('[ROOT]');
				} else {
					$url = t3lib_extMgm::extRelPath('templavoila').'cm1/index.php?table='.rawurlencode($table).'&uid='.$uid.'&_reload_from=1';
				}
				$localItems[] = $backRef->linkItem(
					$LANG->getLLL('cm1_title',$LL),
					$backRef->excludeIcon('<img src="'.$backRef->backPath.t3lib_extMgm::extRelPath('templavoila').'cm1/cm_icon.gif" width="15" height="12" border="0" align="top" alt="" />'),
					$backRef->urlRefForCM($url),
					1	// Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
				);
			}

				// Adding link for "View: Sub elements":
			if ($table == 'tt_content' && $backRef->rec['tx_templavoila_flex']) {
				$localItems = Array();

				$url = t3lib_extMgm::extRelPath('templavoila').'mod1/index.php?id='.intval($backRef->rec['pid']).
							'&altRoot[table]='.rawurlencode($table).
							'&altRoot[uid]='.$uid.
							'&altRoot[field_flex]=tx_templavoila_flex';

				$localItems[] = $backRef->linkItem(
					$LANG->getLLL('cm1_viewsubelements',$LL),
					$backRef->excludeIcon('<img src="'.$backRef->backPath.t3lib_extMgm::extRelPath('templavoila').'cm1/cm_icon.gif" width="15" height="12" border="0" align="top" alt="" />'),
					$backRef->urlRefForCM($url),
					1	// Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
				);

			}

				// Adding link for "View: Flexform XML" (admin only):
			if ($BE_USER->isAdmin() && ('tt_content' == $table || 'pages' == $table) && $backRef->rec['tx_templavoila_flex']) {
				$url = t3lib_extMgm::extRelPath('templavoila').'cm2/index.php?'.
							'&viewRec[table]='.rawurlencode($table).
							'&viewRec[uid]='.$uid.
							'&viewRec[field_flex]=tx_templavoila_flex';

				$localItems[] = $backRef->linkItem(
					$LANG->getLLL('cm1_viewflexformxml',$LL),
					$backRef->excludeIcon('<img src="'.$backRef->backPath.t3lib_extMgm::extRelPath('templavoila').'cm2/cm_icon.gif" width="15" height="12" border="0" align="top" alt="" />'),
					$backRef->urlRefForCM($url),
					1	// Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
				);
			}

			if ($table=='tt_content') {
					// Adding link for "Pages using this element":
				$localItems[] = $backRef->linkItem(
					$LANG->getLLL('cm1_pagesusingthiselement',$LL),
					$backRef->excludeIcon('<img src="'.t3lib_extMgm::extRelPath('templavoila').'cm1/cm_icon_activate.gif" width="15" height="12" border=0 align=top>'),
					"top.loadTopMenu('".t3lib_div::linkThisScript()."&cmLevel=1&subname=tx_templavoila_cm1_pagesusingthiselement');return false;",
					0,
					1
				);
			}
		} else {
			if (t3lib_div::GPvar('subname') == 'tx_templavoila_cm1_pagesusingthiselement') {
				$menuItems = array ();
				$continueProcessing = false;
				$url = t3lib_extMgm::extRelPath('templavoila').'mod1/index.php?id=';

					// Generate a list of pages where this element is also being used:
				$res = $TYPO3_DB->exec_SELECTquery ('*', 'tx_templavoila_elementreferences', 'uid='.$backRef->rec['uid']);
				if ($res) {
					while ($referenceRecord = $TYPO3_DB->sql_fetch_assoc ($res)) {
						$pageRecord = t3lib_beFunc::getRecord ('pages', $referenceRecord['pid']);
						$icon = t3lib_iconWorks::getIconImage('pages', $pageRecord, $backRef->backPath);
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
								$backRef->urlRefForCM($url.$pageRecord['uid']),
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

	/**
	 * Includes the [extDir]/locallang.php and returns the $LOCAL_LANG array found in that file.
	 *
	 * @return	array		The $LOCAL_LANG array from the locallang.php file.
	 */
	function includeLL()	{
		include(t3lib_extMgm::extPath('templavoila').'locallang.php');
		return $LOCAL_LANG;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_cm1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_cm1.php']);
}
?>