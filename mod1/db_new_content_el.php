<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2003-2004 Robert Lemke (rl@robertlemke.de)
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
*  A copy is found in the textfile GPL.txt and important notices to the license 
*  from the author is found in LICENSE.txt distributed with these scripts.
*
* 
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/** 
 * New content elements wizard for templavoila
 * 
 * $Id$
 * Originally based on the CE wizard / cms extension by Kasper Skaarhoj <kasper@typo3.com>
 * XHTML compatible.
 *
 * @author		Robert Lemke <rl@robertlemke.de>
 * @coauthor	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  100: class tx_templavoila_posMap extends t3lib_positionMap 
 *  110:     function wrapRecordTitle($str,$row)	
 *  124:     function onClickInsertRecord($row,$vv,$moveUid,$pid,$sys_lang=0) 
 *
 *
 *  152: class tx_templavoila_dbnewcontentel 
 *  175:     function init()	
 *  211:     function main()	
 *  355:     function printContent()	
 *
 *              SECTION: OTHER FUNCTIONS:
 *  384:     function getWizardItems()	
 *  394:     function wizardArray()	
 *
 * TOTAL FUNCTIONS: 7
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
 
 
unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');

	// Unset MCONF/MLANG since all we wanted was back path etc. for this particular script.
unset($MCONF);
unset($MLANG);

	// Merging locallang files/arrays:
include ($BACK_PATH.'sysext/lang/locallang_misc.php');
$LOCAL_LANG_orig = $LOCAL_LANG;
include ('locallang_db_new_content_el.php');
$LOCAL_LANG = t3lib_div::array_merge_recursive_overrule($LOCAL_LANG_orig,$LOCAL_LANG);

	// Exits if 'cms' extension is not loaded:
t3lib_extMgm::isLoaded('cms',1);

	// Include needed libraries:
require_once (PATH_t3lib.'class.t3lib_page.php');







/**
 * Script Class for the New Content element wizard
 * 
 * @author	Robert Lemke <rl@robertlemke.de>
 * @package TYPO3
 * @subpackage templavoila
 */
class tx_templavoila_dbnewcontentel {
	
		// Internal, static (from GPvars):
	var $id;					// Page id
	var $parentRecord;			// Parameters for the new record
	var $altRoot;				// Array with alternative table, uid and flex-form field (see index.php in module for details, same thing there.)

		// Internal, static:
	var $doc;					// Internal backend template object

		// Internal, dynamic:
	var $include_once = array();	// Includes a list of files to include between init() and main() - see init()
	var $content;					// Used to accumulate the content of the module.
	var $access;					// Access boolean.
	
	/**
	 * Constructor, initializing internal variables.
	 * 
	 * @return	void		
	 */
	function init()	{
		global $BE_USER,$BACK_PATH,$TBE_MODULES_EXT;
		
			// Setting class files to include:
		if (is_array($TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']))	{
			$this->include_once = array_merge($this->include_once,$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']);
		}
		
			// Setting internal vars:
		$this->id = intval(t3lib_div::GPvar('id'));
		$this->parentRecord = t3lib_div::GPvar('parentRecord');
		$this->altRoot = t3lib_div::GPvar('altRoot');

			// Starting the document template object:
		$this->doc = t3lib_div::makeInstance('mediumDoc');
		$this->doc->docType= 'xhtml_trans';
		$this->doc->backPath = $BACK_PATH;
		$this->doc->JScode='';
		$this->doc->form='<form action="" name="editForm">';
		
			// Getting the current page and receiving access information (used in main())
		$perms_clause = $BE_USER->getPagePermsClause(1);
		$pageinfo = t3lib_BEfunc::readPageAccess($this->id,$perms_clause);
		$this->access = is_array($pageinfo) ? 1 : 0;
	}

	/**
	 * Creating the module output.
	 * 
	 * @return	void		
	 * @todo	provide position mapping if no position is given already. Like the columns selector but for our cascading element style ...
	 */
	function main()	{
		global $LANG,$BACK_PATH;

		if ($this->id && $this->access)	{			
		
			// ***************************
			// Creating content
			// ***************************
			$this->content='';
			$this->content.=$this->doc->startPage($LANG->getLL('newContentElement'));
			$this->content.=$this->doc->header($LANG->getLL('newContentElement'));
			$this->content.=$this->doc->spacer(5);
		
			$elRow = t3lib_BEfunc::getRecord('pages',$this->id);
			$header= t3lib_iconWorks::getIconImage('pages',$elRow,$BACK_PATH,' title="'.htmlspecialchars(t3lib_BEfunc::getRecordIconAltText($elRow,'pages')).'" align="top"');
			$header.= t3lib_BEfunc::getRecordTitle('pages',$elRow,1);
			$this->content.=$this->doc->section('',$header,0,1);
			$this->content.=$this->doc->spacer(10);		
		
				// Wizard
			$wizardCode='';
			$tableRows=array();
			$wizardItems = $this->getWizardItems();

				// Traverse items for the wizard.
				// An item is either a header or an item rendered with a title/description and icon:			
			$counter=0;
			foreach($wizardItems as $key => $wizardItem)	{
				if ($wizardItem['header'])	{
					if ($counter>0) $tableRows[]='
						<tr>
							<td colspan="3"><br /></td>
						</tr>';
					$tableRows[]='
						<tr class="bgColor5">
							<td colspan="3"><strong>'.htmlspecialchars($wizardItem['header']).'</strong></td>
						</tr>';
				} else {
					$tableLinks=array();
										
						// href URI for icon/title:			
					$newRecordLink = 'index.php?'.$this->linkParams().'&createNewRecord='.rawurlencode($this->parentRecord).$wizardItem['params'];

						// Icon:
					$iInfo = @getimagesize($wizardItem['icon']);
					$tableLinks[]='<a href="'.$newRecordLink.'"><img'.t3lib_iconWorks::skinImg($this->doc->backPath,$wizardItem['icon'],'').' alt="" /></a>';

						// Title + description:					
					$tableLinks[]='<a href="'.$newRecordLink.'"><strong>'.htmlspecialchars($wizardItem['title']).'</strong><br />'.nl2br(htmlspecialchars(trim($wizardItem['description']))).'</a>';
			
						// Finally, put it together in a table row:
					$tableRows[]='
						<tr>
							<td valign="top">'.implode('</td>
							<td valign="top">',$tableLinks).'</td>
						</tr>';
					$counter++;
				}
			}
				// Add the wizard table to the content:
			$wizardCode.=$LANG->getLL('sel1',1).'<br /><br />

		
			<!--
				Content Element wizard table:
			-->
				<table border="0" cellpadding="1" cellspacing="2" id="typo3-ceWizardTable">
					'.implode('',$tableRows).'
				</table>';
			$this->content.=$this->doc->section(!$onClickEvent?$LANG->getLL('1_selectType'):'',$wizardCode,0,1);		
		} else {		// In case of no access:
			$this->content='';
			$this->content.=$this->doc->startPage($LANG->getLL('newContentElement'));
			$this->content.=$this->doc->header($LANG->getLL('newContentElement'));
			$this->content.=$this->doc->spacer(5);
		}
	}

	/**
	 * Print out the accumulated content:
	 * 
	 * @return	void		
	 */
	function printContent()	{
		global $SOBE;

		$this->content.= $this->doc->endPage();
		echo $this->content;
	}
	
	function linkParams()	{
		$output = 'id='.$this->id.
				(is_array($this->altRoot) ? t3lib_div::implodeArrayForUrl('altRoot',$this->altRoot) : '');
		return $output;
	}









	/***************************
	 *
	 * OTHER FUNCTIONS:	
	 *
	 ***************************/


	/**
	 * Returns the content of wizardArray() function...
	 * 
	 * @return	array		Returns the content of wizardArray() function...
	 */
	function getWizardItems()	{
		return $this->wizardArray();
	}

	/**
	 * Returns the array of elements in the wizard display.
	 * For the plugin section there is support for adding elements there from a global variable.
	 *
	 * @return	array		
	 */
	function wizardArray()	{
		global $LANG,$TBE_MODULES_EXT;
		
		$wizardItems = array(
			'common' => array('header'=>$LANG->getLL('common')),
			'common_1' => array(
				'icon'=>'gfx/c_wiz/regular_text.gif',
				'title'=>$LANG->getLL('common_1_title'),
				'description'=>$LANG->getLL('common_1_description'),
				'params'=>'&defVals[tt_content][CType]=text'
			),
			'common_2' => array(
				'icon'=>'gfx/c_wiz/text_image_below.gif',
				'title'=>$LANG->getLL('common_2_title'),
				'description'=>$LANG->getLL('common_2_description'),
				'params'=>'&defVals[tt_content][CType]=textpic&defVals[tt_content][imageorient]=8'
			),
			'common_3' => array(
				'icon'=>'gfx/c_wiz/text_image_right.gif',
				'title'=>$LANG->getLL('common_3_title'),
				'description'=>$LANG->getLL('common_3_description'),
				'params'=>'&defVals[tt_content][CType]=textpic&defVals[tt_content][imageorient]=17'
			),
			'common_4' => array(
				'icon'=>'gfx/c_wiz/images_only.gif',
				'title'=>$LANG->getLL('common_4_title'),
				'description'=>$LANG->getLL('common_4_description'),
				'params'=>'&defVals[tt_content][CType]=image&defVals[tt_content][imagecols]=2'
			),
			'common_5' => array(
				'icon'=>'gfx/c_wiz/bullet_list.gif',
				'title'=>$LANG->getLL('common_5_title'),
				'description'=>$LANG->getLL('common_5_description'),
				'params'=>'&defVals[tt_content][CType]=bullets'
			),
			'common_6' => array(
				'icon'=>'gfx/c_wiz/table.gif',
				'title'=>$LANG->getLL('common_6_title'),
				'description'=>$LANG->getLL('common_6_description'),
				'params'=>'&defVals[tt_content][CType]=table'
			),
			'special' => array('header'=>$LANG->getLL('special')),
			'special_1' => array(
				'icon'=>'gfx/c_wiz/filelinks.gif',
				'title'=>$LANG->getLL('special_1_title'),
				'description'=>$LANG->getLL('special_1_description'),
				'params'=>'&defVals[tt_content][CType]=uploads'
			),
			'special_2' => array(
				'icon'=>'gfx/c_wiz/multimedia.gif',
				'title'=>$LANG->getLL('special_2_title'),
				'description'=>$LANG->getLL('special_2_description'),
				'params'=>'&defVals[tt_content][CType]=multimedia'
			),
			'special_3' => array(
				'icon'=>'gfx/c_wiz/sitemap2.gif',
				'title'=>$LANG->getLL('special_3_title'),
				'description'=>$LANG->getLL('special_3_description'),
				'params'=>'&defVals[tt_content][CType]=menu&defVals[tt_content][menu_type]=2'
			),
			'special_4' => array(
				'icon'=>'gfx/c_wiz/html.gif',
				'title'=>$LANG->getLL('special_4_title'),
				'description'=>$LANG->getLL('special_4_description'),
				'params'=>'&defVals[tt_content][CType]=html'
			),
		
		
			'forms' => array('header'=>$LANG->getLL('forms')),
			'forms_1' => array(
				'icon'=>'gfx/c_wiz/mailform.gif',
				'title'=>$LANG->getLL('forms_1_title'),
				'description'=>$LANG->getLL('forms_1_description'),
				'params'=>'&defVals[tt_content][CType]=mailform&defVals[tt_content][bodytext]='.rawurlencode(trim($LANG->getLL ('forms_1_example')))
			),
			'forms_2' => array(
				'icon'=>'gfx/c_wiz/searchform.gif',
				'title'=>$LANG->getLL('forms_2_title'),
				'description'=>$LANG->getLL('forms_2_description'),
				'params'=>'&defVals[tt_content][CType]=search'
			),
			'forms_3' => array(
				'icon'=>'gfx/c_wiz/login_form.gif',
				'title'=>$LANG->getLL('forms_3_title'),
				'description'=>$LANG->getLL('forms_3_description'),
				'params'=>'&defVals[tt_content][CType]=login'
			),
			'plugins' => array('header'=>$LANG->getLL('plugins')),
		);


			// PLUG-INS:
		if (is_array($TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']))	{
			reset($TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']);
			while(list($class,$path)=each($TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']))	{
				$modObj = t3lib_div::makeInstance($class);
				$wizardItems = $modObj->proc($wizardItems);
			}
		}

		return $wizardItems;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/db_new_content_el.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/db_new_content_el.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_templavoila_dbnewcontentel');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();
?>
