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
 * @author     Robert Lemke <rl@robertlemke.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   73: class tx_templavoila_module1 extends t3lib_SCbase 
 *   79:     function init()    
 *   91:     function main()    
 *  145:     function renderEditPageScreen()    
 *  166:     function renderCreatePageScreen ($id) 
 *  185:     function printContent()    
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
 
 
 
 
 

 


	// Initialize module
unset($MCONF);    
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:templavoila/mod1/locallang.php');
require_once (PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);    // This checks permissions and exits if the users has no permission for entry.


	// We need the TCE forms functions
require_once (PATH_t3lib.'class.t3lib_loaddbgroup.php');


/**
 * Module 'Page' for the 'templavoila' extension.
 * 
 * @author     Robert Lemke <rl@robertlemke.de>
 */
class tx_templavoila_module1 extends t3lib_SCbase {
	var $pageinfo;

	/**
	 * @return	[type]		...
	 */
    function init()    {
        global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
        parent::init();
    }

	/**
	 * Main function of the module. Write the content to $this->content
	 * 
	 * * @return	[type]		...
	 * 
	 * @return	[type]		...
	 */
	function main()    {
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
        
			// Access check!
			// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if ($this->id && $access)    {
				// Draw the header.
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="post">';

			$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.path').': '.t3lib_div::fixed_lgd_pre($this->pageinfo['_thePath'],50);

				// Get the parameters
			$cmd = t3lib_div::GPvar ('cmd');
			$positionPID = t3lib_div::GPvar ('positionPid');
			
debug ($positionPID);
			switch ($cmd) {
					// Create a new page
				case 'crPage' :
					$this->content.=$this->renderCreatePageScreen ($pageId);
					break;
					
					// Default: Edit an existing page
				default:
//					$this->content.=$this->renderEditPageScreen ();
					$this->content.=$this->renderCreatePageScreen (35); # FOR DEBUGGING
				
			}		  
		} else {
			    // If no access or if ID == zero
			
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			
			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}

		$this->content.=$this->doc->middle();
		$this->content.=$this->doc->endPage();
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function renderEditPageScreen()    {
		global $LANG, $BE_USER;

		$content =$this->doc->startPage($LANG->getLL('title'));
		$content.=$this->doc->header($LANG->getLL('title_editpage'));
		$content.=$this->doc->spacer(5);

      if ($BE_USER->mayMakeShortcut())    {
			$content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
      }
		$content.=$this->doc->spacer(10);
				
		return $content;		
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$id: ...
	 * @return	[type]		...
	 */
    function renderCreatePageScreen ($id) {
		global $LANG, $BE_USER;

			//	Output first part of the screen
		$content =$this->doc->startPage($LANG->getLL ('createnewpage_title'));
		$content.=$this->doc->header($LANG->getLL('createnewpage_title'));
		$content.=$this->doc->spacer(5);
		$content.=$LANG->getLL ('createnewpage_introduction');
		$content.=$this->doc->spacer(5);
		$content.=$this->doc->sectionHeader ($LANG->getLL ('createnewpage_pagetitle_label'));
		$content.=$LANG->getLL ('createnewpage_pagetitle_introduction');
		return $content;
   }

	/**
	 * Prints out the module HTML
	 * 
	 * @return	[type]		...
	 */
	function printContent()    {
		echo $this->content;
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/index.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_templavoila_module1');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
