<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * @author	Kasper Skaarhoj <kasper@typo3.com>
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
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_templavoila
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
	function main(&$backRef,$menuItems,$table,$uid)	{
		global $BE_USER,$TCA,$LANG;
	
		$localItems = Array();
		if (!$backRef->cmLevel)	{
			if (t3lib_div::inList('tx_templavoila_tmplobj,tx_templavoila_datastructure,tx_templavoila_content',$table) || @is_file($table))	{
					// Adds the regular item:
				$LL = $this->includeLL();
			
					// Repeat this (below) for as many items you want to add!
					// Remember to add entries in the localconf.php file for additional titles.
				if (@is_file($table))	{
					$url = t3lib_extMgm::extRelPath('templavoila').'cm1/index.php?file='.rawurlencode($table).'&mapElPath='.rawurlencode('[ROOT]');
				} else {
					$url = t3lib_extMgm::extRelPath('templavoila').'cm1/index.php?table='.rawurlencode($table).'&uid='.$uid.'&_reload_from=1';
				}
				$localItems[] = $backRef->linkItem(
					$GLOBALS['LANG']->getLLL('cm1_title',$LL),
					$backRef->excludeIcon('<img src="'.$backRef->backPath.t3lib_extMgm::extRelPath('templavoila').'cm1/cm_icon.gif" width="15" height="12" border="0" align="top" alt="" />'),
					$backRef->urlRefForCM($url),
					1	// Disables the item in the top-bar. Set this to zero if you with the item to appear in the top bar!
				);
				
				// Simply merges the two arrays together and returns ...
				$menuItems=array_merge($menuItems,$localItems);
			} else return $menuItems;
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