<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004  Robert Lemke (rl@robertlemke.de)
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
 * Module 'Tools' for the 'templavoila' extension.
 *
 * $Id$
 *
 * @author   Robert Lemke <rl@robertlemke.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   68: class tx_templavoila_module3 extends t3lib_SCbase
 *   79:     function menuConfig()
 *  101:     function main()
 *  136:     function printContent()
 *  145:     function getSubModuleContent ()
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


	// Initialize module
unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:templavoila/mod3/locallang.php');
require_once (PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);    // This checks permissions and exits if the users has no permission for entry.





/**
 * Module 'Tools' for the 'templavoila' extension.
 *
 * @author	Robert Lemke <rl@robertlemke.de>
 * @package		TYPO3
 * @subpackage	tx_templavoila
 */
class tx_templavoila_module3 extends t3lib_SCbase {
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

		$this->MOD_MENU = Array (
			'function' => Array (
				'1' => 'Element reference indexing',
			),
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

			// Draw the header.
		$this->doc = t3lib_div::makeInstance('mediumDoc');
		$this->doc->docType= 'xhtml_trans';
		$this->doc->backPath = $BACK_PATH;
		$this->doc->form='<form action="'.htmlspecialchars('index.php?id='.$this->id).'" method="post" autocomplete="off">';

			// Add some JavaScript:
		$this->doc->JScode.= $this->doc->wrapScriptTags('
			function jumpToUrl(URL)	{	//
				document.location = URL;
			}
		');

		$this->content .= $this->doc->startPage('TemplaVoila');
		$this->content .= $this->doc->section('TemplaVoila Tools',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])));

		$this->content .= $this->getSubModuleContent ();

			// ShortCut
		if ($BE_USER->mayMakeShortcut())	{
			$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
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

	/**
	 * Generates the submodule content
	 *
	 * @return	void
	 */
	function getSubModuleContent ()	{
		$content = '';

		switch((string)$this->MOD_SETTINGS['function'])	{
			case 1:
				require_once('./class.tx_templavoila_submod_elref.php');

				$subModuleObj = t3lib_div::makeInstance('tx_templavoila_submod_elref');
				$content .= $subModuleObj->main ($this);
			break;
		}

		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod3/index.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod3/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('tx_templavoila_module3');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>