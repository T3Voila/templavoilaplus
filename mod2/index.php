<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2003  Robert Lemke (rl@robertlemke.de)
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
 * Module 'Page' for the 'templavoila' extension.
 *
 * $Id$
 *
 * @author   Kasper Skårhøj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */


	// Initialize module
unset($MCONF);    
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:templavoila/mod2/locallang.php');
require_once (PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);    // This checks permissions and exits if the users has no permission for entry.





/**
 * Module 'Page' for the 'templavoila' extension.
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package		TYPO3
 * @subpackage	tx_templavoila
 */
class tx_templavoila_module2 extends t3lib_SCbase {
	var $pageinfo;
	var $modTSconfig;
	var $extKey = 'templavoila';			// Extension key of this module
	
	
	/**
	 * Preparing menu content
	 * 
	 * @return	void		
	 */
	function menuConfig()	{
		global $LANG;
		
		$this->MOD_MENU = array(
		);
		
			// page/be_user TSconfig settings and blinding of menu-items
		$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->id,'mod.'.$this->MCONF['name']);
		$this->MOD_MENU['view'] = t3lib_BEfunc::unsetMenuItems($this->modTSconfig['properties'],$this->MOD_MENU['view'],'menu.function');

			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::GPvar('SET'), $this->MCONF['name']);
	}
	
	/**
	 * Main function of the module.
	 * 
	 * @return	void		Nothing.
	 */
	function main()    {
		global $BE_USER,$LANG,$BACK_PATH;
        
			// Access check!
			// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if ($this->id && $access)    {

				// Draw the header.
			$this->doc = t3lib_div::makeInstance('noDoc');
			$this->doc->docType= 'xhtml_trans';			
			$this->doc->backPath = $BACK_PATH;
			$this->doc->divClass = '';
			$this->doc->form='<form action="'.htmlspecialchars('index.php?id='.$this->id).'" method="post" autocomplete="off">';

				// Adding classic jumpToUrl function, needed for the function menu.
				// Also, the id in the parent frameset is configured.
			$this->doc->JScode=$this->doc->wrapScriptTags('
				function jumpToUrl(URL)	{ //
					document.location = URL;
					return false;
				}
				if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
			');

				// Setting up support for context menus (when clicking the items icon)
			$CMparts=$this->doc->getContextMenuCode();
			$this->doc->bodyTagAdditions = $CMparts[1];
			$this->doc->JScode.=$CMparts[0];
			$this->doc->postCode.= $CMparts[2];


			if ($BE_USER->mayMakeShortcut()) {
				$this->content.='<br /><br />'.$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']);
			}
		} else {	// No access or no current uid:
		}
		$this->content.=$this->doc->endPage();	
	}

	/**
	 * Prints out the module HTML
	 * 
	 * @return	void		
	 */
	function printContent()    {
		echo $this->content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod2/index.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod2/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('tx_templavoila_module2');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>