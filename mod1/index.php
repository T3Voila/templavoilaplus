<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2003, 2004  Robert Lemke (rl@robertlemke.de)
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
 * @author     Robert Lemke <rl@robertlemke.de>
 * @coauthor   Kasper Skårhøj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  106: class tx_templavoila_module1 extends t3lib_SCbase 
 *  119:     function init()    
 *  129:     function menuConfig()	
 *  156:     function main()    
 *
 *              SECTION: Command functions
 *  256:     function cmd_createNewRecord ($parentRecord, $defVals='') 
 *  274:     function cmd_unlinkRecord ($unlinkRecord) 
 *  286:     function cmd_pasteRecord ($pasteRecord) 
 *
 *              SECTION: Rendering functions
 *  304:     function renderEditPageScreen()    
 *  336:     function renderCreatePageScreen ($positionPid) 
 *  413:     function renderTemplateSelector ($positionPid, $templateType='tmplobj') 
 *
 *              SECTION: Framework rendering function(s)
 *  477:     function renderFrameWorkBasic($dsInfo,$parentPos='',$clipboardElInPath=0)	
 *  626:     function renderNonUsed()	
 *  652:     function linkEdit($str,$table,$uid)	
 *  664:     function linkNew($str,$parentRecord)	
 *  676:     function linkUnlink($str,$unlinkRecord)	
 *  689:     function linkPaste($str,$params,$target,$cmd)	
 *  701:     function linkCopyCut($str,$parentPos,$cmd)	
 *  710:     function printContent()    
 *
 *              SECTION: Processing
 *  731:     function createPage($pageArray,$positionPid)	
 *  764:     function createDefaultRecords ($table, $uid, $prevDS=-1, $level=0)	
 *  815:     function insertRecord($createNew,$row)	
 *  880:     function pasteRecord($pasteCmd, $target, $destination)	
 *
 *              SECTION: Structure and rules functions
 * 1076:     function getStorageFolderPid($positionPid)	
 * 1097:     function getDStreeForPage($table,$id,$prevRecList='',$row='')	
 * 1173:     function renderPreviewContent ($row, $table) 
 * 1240:     function getExpandedDataStructure($table,$field,$row)	
 * 1271:     function checkRulesForElement ($table, $uid) 
 *
 * TOTAL FUNCTIONS: 26
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
require_once (PATH_t3lib.'class.t3lib_tcemain.php');	

	// Include class for parsing rules based on regular expressions
require_once (t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_rules.php'); 	


/**
 * Module 'Page' for the 'templavoila' extension.
 * 
 * @author		Robert Lemke <rl@robertlemke.de>
 * @coauthor	Kasper Skaarhoj <kasper@typo3.com>
 * @package		TYPO3
 * @subpackage	tx_templavoila
 */
class tx_templavoila_module1 extends t3lib_SCbase {
	var $pageinfo;
	var $rules;								// Holds an instance of the tx_templavoila_rule
	var $modTSconfig;
	var $extKey = 'templavoila';			// Extension key of this module	
	
	var $global_tt_content_elementRegister=array();
	var $elementBlacklist=array();			// Used in renderFrameWorkBasic (list of CEs causing errors)
	
	/**
	 * Initialisation of this backend module
	 * 
	 * @return	void		
	 */
	function init()    {
		parent::init();
		$this->rules = t3lib_div::makeInstance('tx_templavoila_rules');
	}

	/**
	 * Preparing menu content
	 * 
	 * @return	void		
	 */
	function menuConfig()	{
		global $LANG;
		
		$this->MOD_MENU = array(
			'view' => array(
				0 => $LANG->getLL('view_basic'),
				1 => $LANG->getLL('view_fancy'),
				2 => $LANG->getLL('view_tree')
			),
			'sheetData' => '',
			'clip_parentPos' => '',
			'clip' => '',
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
	 * @return	void		
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

				// Just to include the Stylesheet information, nothing more:
			$this->doc->getTabMenu(0,'_',0,array(''=>''));

				// Go through the commands and check if we have to do some action:
			$commands = array ('createNewRecord', 'unlinkRecord','pasteRecord');
			foreach ($commands as $cmd) {
				unset ($params);
				$params = t3lib_div::GPvar($cmd);
				$function = 'cmd_'.$cmd;
					// If the current function has a parameter passed by GET or POST, call the related function:
				if ($params && is_callable(array ($this, $function))) {
				 	$this->$function ($params);
				}			
			}
				// Check if we have to update the pagetree:
			if (t3lib_div::GPvar('updatePageTree')) {
				t3lib_BEfunc::getSetUpdateSignal('updatePageTree');
			}
			
				// Show the "edit current page" dialog
			$this->content.=$this->renderEditPageScreen ();
			$this->content.='<br />'.t3lib_BEfunc::getFuncMenu($this->id,'SET[view]',$this->MOD_SETTINGS['view'],$this->MOD_MENU['view'],'','');

			if ($BE_USER->mayMakeShortcut()) {
				$this->content.='<br /><br />'.$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']);
			}
		} else {	// No access or no current page uid:
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->docType= 'xhtml_trans';
			$this->doc->backPath = $BACK_PATH;
			$this->content.=$this->doc->startPage($LANG->getLL('title'));

			$cmd = t3lib_div::GPvar ('cmd');
			switch ($cmd) {
				
					// Create a new page
				case 'crPage' :
						// Output the page creation form
					$this->content.=$this->renderCreatePageScreen (t3lib_div::GPvar ('positionPid'));
					break;

					// If no access or if ID == zero
				default:					
					$this->content.=$this->doc->header($LANG->getLL('title'));
					$this->content.=$LANG->getLL('default_introduction');
			}
		}
		$this->content.=$this->doc->endPage();	
	}
	
	
	
	/**
	 * *****************************************
	 * 
	 * Command functions
	 * 
	 * *****************************************/
	 
	/**
	 * Initiates processing for creating a new record.
	 * 
	 * @param	string		$params: Array containing parameters for creating the new record (see function)
	 * @param	array		$defVals: Array containing default values for the new record, e.g. [tt_content][CType] = 'text'
	 * @return	void		
	 * @see		insertRecord ()
	 */
	function cmd_createNewRecord ($parentRecord, $defVals='') {
			// Historically "defVals" has been used for submitting the row data. We still use it and use it for our new row:
		$defVals = (string)$defVals == '' ? t3lib_div::GPvar('defVals') : $defVals;
		$row = $defVals['tt_content'];
		
			// Create new record and open it for editing
		$newUid = $this->insertRecord($parentRecord, $row);
		$location = $GLOBALS['BACK_PATH'].'alt_doc.php?edit[tt_content]['.$newUid.']=edit&returnUrl='.t3lib_extMgm::extRelPath('templavoila').'mod1/index.php?id='.$this->id;
		header('Location: '.t3lib_div::locationHeaderUrl($location));
	}

	/**
	 * Initiates processing for unlinking a record.
	 * 
	 * @param	string		$unlinkRecord: The element to be unlinked.
	 * @return	void		
	 * @see		pasteRecord ()
	 */
	function cmd_unlinkRecord ($unlinkRecord) {	
		$this->pasteRecord('unlink', $unlinkRecord, '');
		header('Location: '.t3lib_div::locationHeaderUrl('index.php?id='.$this->id));
	}

	/**
	 * Initiates processing for pasting a record.
	 * 
	 * @param	string		$pasteRecord: The element to be pasted.
	 * @return	void		
	 * @see		pasteRecord ()
	 */
	function cmd_pasteRecord ($pasteRecord) {
		$this->pasteRecord($pasteRecord, t3lib_div::GPvar('target'), t3lib_div::GPvar('destination'));
		header('Location: '.t3lib_div::locationHeaderUrl('index.php?id='.$this->id));
	}
			
	
	
	/********************************************
	 *
	 * Rendering functions
	 *
	 ********************************************/
	 
	/**
	 * Displays the default view of a page, showing the nested structure of elements.
	 * 
	 * @return	string		The modules content
	 */
	function renderEditPageScreen()    {
		global $LANG, $BE_USER;

			// Adding header information:
		$content =$this->doc->startPage($LANG->getLL('title'));
		$content.=$this->doc->header($LANG->getLL('title_editpage'));
		$content.=$this->doc->spacer(5);

			// Reset internal variable:
		$this->global_tt_content_elementRegister=array();
		
			// Get data structure array for the page/content elements.
			// Returns a BIG array reflecting the "treestructure" of the pages content elements.
		$dsInfo = $this->getDStreeForPage('pages',$this->id);
			// Setting whether an element is on the clipboard
		$clipboardElInPath = (!trim($this->MOD_SETTINGS['clip'].$this->MOD_SETTINGS['clip_parentPos']) ? 1 : 0);
			// Display the nested page structure:
		$this->elementBlacklist=array ();
		$content.=$this->renderFrameworkBasic($dsInfo,'',$clipboardElInPath);
			// Display elements not used in the page structure (has to be done differently)
		$content.=$this->renderNonUsed();
			// Display (debug) the element counts on the page (has to be done differently)		
#		$content.=t3lib_div::view_array($this->global_tt_content_elementRegister);
		return $content;		
	}

	/**
	 * Creates the screen for "new page wizard"
	 * 
	 * @param	integer		$positionPid: Can be positive and negative depending of where the new page is going: Negative always points to a position AFTER the page having the abs. value of the positionId. Positive numbers means to create as the first subpage to another page.
	 * @return	string		Content for the screen output.
	 * @todo				Check required field(s), support t3d
	 */
    function renderCreatePageScreen ($positionPid) {
		global $LANG, $BE_USER, $TYPO3_CONF_VARS;

			// The user already submitted the create page form:
		if (t3lib_div::GPvar ('doCreate')) {
				// Check if the HTTP_REFERER is valid
			$refInfo=parse_url(t3lib_div::getIndpEnv('HTTP_REFERER'));
			$httpHost = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
			if ($httpHost==$refInfo['host'] || t3lib_div::GPvar('vC')==$BE_USER->veriCode() || $TYPO3_CONF_VARS['SYS']['doNotCheckReferer'])	{
					// Create new page
				$newID = $this->createPage (t3lib_div::GPvar('data'), $positionPid);
				if ($newID > 0) {		
						// Creating the page was successful, now create the default content elements if any
					$this->createDefaultRecords ('pages',$newID);
					header('Location: '.t3lib_div::locationHeaderUrl('index.php?id='.$newID.'&updatePageTree=1'));
					return;
				} else { debug('Error: Could not create page!'); }
			} else { debug('Error: Referer host did not match with server host.'); }
		}

		$this->doc->form='<form action="'.htmlspecialchars('index.php?id='.$this->id).'" method="post" autocomplete="off">';
		$this->doc->divClass = '';

			// Just to include the Stylesheet information, nothing more:
		$this->doc->getTabMenu(0,'_',0,array(''=>''));
		
		$content =$this->doc->startPage($LANG->getLL ('createnewpage_title'));
		$content.=$this->doc->header($LANG->getLL('createnewpage_title'));
		$content.=$this->doc->spacer(5);
		
		$content.=$this->doc->sectionHeader ($LANG->getLL ('createnewpage_hide_header'));
		$content.='<input type="checkbox" value="1" name="data[hidden]" checked="checked"/> '.$LANG->getLL ('createnewpage_hide_description');
		$content.=$this->doc->spacer(10);

		$content.=$this->doc->sectionHeader ($LANG->getLL ('createnewpage_pagetitle_header'));
		$content.='<input type="text" name="data[title]"'.$this->doc->formWidth(30).' /><br />';
		$content.=$this->doc->spacer(5);
		$content.=$LANG->getLL ('createnewpage_pagetitle_description');
		$content.=$this->doc->spacer(10);

		$tmplSelectorCode = '';
		$tmplSelector = $this->renderTemplateSelector ($positionPid,'tmplobj');
		if ($tmplSelector) {
#			$tmplSelectorCode.='<em>'.$LANG->getLL ('createnewpage_templateobject_createemptypage').'</em>';
			$tmplSelectorCode.=$this->doc->spacer(5);
			$tmplSelectorCode.=$tmplSelector;
			$tmplSelectorCode.=$this->doc->spacer(10);
		}

		$tmplSelector = $this->renderTemplateSelector ($positionPid,'t3d');
		if ($tmplSelector) {
			$tmplSelectorCode.='<em>'.$LANG->getLL ('createnewpage_templateobject_createpagewithdefaultcontent').'</em>';
			$tmplSelectorCode.=$this->doc->spacer(5);
			$tmplSelectorCode.=$tmplSelector;
			$tmplSelectorCode.=$this->doc->spacer(10);
		}

		if ($tmplSelectorCode) {
			$content.=$this->doc->sectionHeader ($LANG->getLL ('createnewpage_templateobject_header'));
			$content.=$LANG->getLL ('createnewpage_templateobject_description');
			$content.=$this->doc->spacer(10);
			$content.=$tmplSelectorCode;
		}

		$content .= '<input type="hidden" name="doCreate" value="1" />';
		$content .= '<input type="hidden" name="positionPid" value="'.$positionPid.'" />';
		$content .= '<input type="hidden" name="cmd" value="crPage" />';
		return $content;
	}

	/**
	 * Renders the template selector.
	 * 
	 * @param	integer		Position id. Can be positive and negative depending of where the new page is going: Negative always points to a position AFTER the page having the abs. value of the positionId. Positive numbers means to create as the first subpage to another page.
	 * @param	string		$templateType: The template type, currently only 'tmplobj' is supported, 't3d' is planned
	 * @return	string		HTML output containing a table with the template selector
	 */
	function renderTemplateSelector ($positionPid, $templateType='tmplobj') {
		global $LANG;
		$storageFolderPID = $this->getStorageFolderPid($positionPid);
		
		switch ($templateType) {			
			case 'tmplobj':				
						// Create the "Default template" entry
				$previewIconFilename = $GLOBALS['BACK_PATH'].'../'.t3lib_extMgm::siteRelPath($this->extKey).'res1/default_previewicon.gif';
				$previewIcon = '<input type="image" class="c-inputButton" name="data[tx_templavoila_to]" value="0" src="'.$previewIconFilename.'" title="" />';
				$description = htmlspecialchars($LANG->getLL ('template_descriptiondefault'));
				$tmplHTML [] = '<table style="float:left; width: 100%;" valign="top"><tr><td colspan="2" nowrap="nowrap"><h3 class="bgcolor4-20">'.htmlspecialchars($LANG->getLL ('template_titledefault')).'</h3></td></tr>'.
					'<tr><td valign="top">'.$previewIcon.'</td><td width="120" valign="top"><p>'.$description.'</p></td></tr></table>';

				$tTO = 'tx_templavoila_tmplobj';
				$tDS = 'tx_templavoila_datastructure';
				$query="SELECT $tTO.* FROM $tTO LEFT JOIN $tDS ON $tTO.datastructure = $tDS.uid WHERE $tTO.pid=".intval($storageFolderPID)." AND $tDS.scope=1".t3lib_befunc::deleteClause ($tTO).t3lib_befunc::deleteClause ($tDS);
				$res = mysql(TYPO3_db, $query);
				while ($row = @mysql_fetch_assoc($res))	{
						// Check if preview icon exists, otherwise use default icon:
					$tmpFilename = 'uploads/tx_templavoila/'.$row['previewicon'];
					$previewIconFilename = (@is_file(PATH_site.$tmpFilename)) ? ($GLOBALS['BACK_PATH'].'../'.$tmpFilename) : ($GLOBALS['BACK_PATH'].'../'.t3lib_extMgm::siteRelPath($this->extKey).'res1/default_previewicon.gif');
					$previewIcon = '<input type="image" class="c-inputButton" name="data[tx_templavoila_to]" value="'.$row['uid'].'" src="'.$previewIconFilename.'" title="" />';
					$description = $row['description'] ? htmlspecialchars($row['description']) : $LANG->getLL ('template_nodescriptionavailable');
					$tmplHTML [] = '<table style="width: 100%;" valign="top"><tr><td colspan="2" nowrap="nowrap"><h3>'.htmlspecialchars($row['title']).'</h3></td></tr>'.
						'<tr><td valign="top">'.$previewIcon.'</td><td width="120" valign="top"><p>'.$description.'</p></td></tr></table>';
				}
				if (is_array ($tmplHTML)) {
					$counter = 0;
					$content .= '<table>';
					foreach ($tmplHTML as $single) {						
						$content .= ($counter ? '':'<tr>').'<td valign="top">'.$single.'</td>'.($counter ? '</tr>':'');
						$counter ++;
						if ($counter > 1) { $counter = 0; }
					}
					$content .= '</table>';
				}
				break;
			
			case 't3d':
				break;
				
		}
		return $content;
	}

	
	
	
	
	/********************************************
	 *
	 * Framework rendering function(s)
	 *
	 ********************************************/

	/**
	 * Renders the "basic" display framework.
	 * Calls itself recursively
	 * 
	 * @param	array		DataStructure info array (the whole tree)
	 * @param	string		Pointer to parent element: table, id, sheet, fieldname, counter (position in list)
	 * @param	boolean		Tells whether any element registered on the clipboard is found in the current "path" of the recursion. If true, it normally means that no paste-in symbols are shown since elements are not allowed to be pasted/referenced to a position within themselves (would result in recursion).
	 * @return	string		HTML
	 */
	function renderFrameWorkBasic($dsInfo,$parentPos='',$clipboardElInPath=0)	{
		global $LANG;
		
			// Setting the sheet ID:
		$sheet=isset($dsInfo['sub'][$this->MOD_SETTINGS['sheetData']]) ? $this->MOD_SETTINGS['sheetData'] : 'sDEF';
		
			// Make sheet menu:
			// Design-wise this will change most likely. And we also need to do a proper registration of the sheets since it only 
			// works for the page at this point - not content elements deeper in the structure.
		$sheetMenu='';
		if (is_array($dsInfo['sub']))	{
			if (count($dsInfo['sub'])>1 || !isset($dsInfo['sub']['sDEF']))	{

				$count = count($dsInfo['sub']);
				$widthLeft = 1;
				$addToAct = 5;
	
				$widthRight = max (1,floor(30-pow($count,1.72)));
				$widthTabs = 100 - $widthRight - $widthLeft;
				$widthNo = floor(($widthTabs - $addToAct)/$count);
				$addToAct = max ($addToAct,$widthTabs-($widthNo*$count));
				$widthAct = $widthNo + $addToAct;
				$widthRight = 100 - ($widthLeft + ($count*$widthNo) + $addToAct);


				$first=true;
				foreach($dsInfo['sub'] as $sheetK => $sheetI)	{

					$isActive = !strcmp($sheet,$sheetK);
					$class = $isActive ? "tabact" : "tab";
					$width = $isActive ? $widthAct : $widthNo;
	
					$label = htmlspecialchars($sheetK);
					$link = htmlspecialchars('index.php?id='.$this->id.'&SET[sheetData]='.$sheetK);

					$sheetMenu.='<td width="'.$width.'%" class="'.$class.'"'.($first?' style="border-left: solid #000 1px;"':'').'>'.
						'<a href="'.$link.'"'.($first?' style="padding-left:5px;padding-right:2px;"':'').'><strong>'.
						$label.
						'</strong></a></td>';

					$first=false;
				}
								
				$sheetMenu = '<table cellpadding="0" cellspacing="0" border="0" style="padding-left: 3px; padding-right: 3px;"><tr>'.$sheetMenu.'</tr></table>';
			}
		}
		
			// Setting whether the current element is registered for copy/cut/reference:
		$clipActive_copy = ($this->MOD_SETTINGS['clip']=='copy' && $this->MOD_SETTINGS['clip_parentPos']==$parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id'] ? '_h' : '');
		$clipActive_cut = ($this->MOD_SETTINGS['clip']=='cut' && $this->MOD_SETTINGS['clip_parentPos']==$parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id'] ? '_h' : '');
		$clipActive_ref = ($this->MOD_SETTINGS['clip']=='ref' && $this->MOD_SETTINGS['clip_parentPos']==$parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id'] ? '_h' : '');
		$clipboardElInPath = trim($clipActive_copy.$clipActive_cut.$clipActive_ref)||$clipboardElInPath?1:0;

			// The $isLocal flag is used to denote whether an element belongs to the current page or not. If NOT the $isLocal flag means (for instance) that the title bar will be colored differently to show users that this is a foreign element not from this page.
		$isLocal = $dsInfo['el']['table']=='pages' || $dsInfo['el']['pid']==$this->id;	// Pages should be seem as local...

			// Setting additional information to the title-attribute of the element icon:		
		$extPath = '';
		if (!$isLocal)	{
			$extPath = ' - PATH: '.t3lib_BEfunc::getRecordPath($dsInfo['el']['pid'],$this->perms_clause,30);
		}

			// Evaluating the rules and set colors to warning scheme if a rule does not apply
		$elementBackgroundStyle = '';
		$elementPageTitlebarStyle = 'background-color: '.($dsInfo['el']['table']=='pages' ? $this->doc->bgColor2 : ($isLocal ? $this->doc->bgColor5 : $this->doc->bgColor6)) .';';
		$elementCETitlebarStyle = 'background-color: '.$this->doc->bgColor4.';';
		$errorLineBefore = $errorLineAfter = '';

		$rulesStatus = $this->checkRulesForElement($dsInfo['el']['table'], $dsInfo['el']['id']);
#debug ($rulesStatus,'rulesStatus',__LINE__,__FILE__,10);
		if (is_array($rulesStatus['error'])) {
			foreach ($rulesStatus['error'] as $errorElement) {
				$this->elementBlacklist[$errorElement['uid']]['position'] = $errorElement['position'];
				$this->elementBlacklist[$errorElement['uid']]['message'] = $errorElement['message'];
			}
		}
#debug ($this->elementBlacklist,'elementBlacklist');	

		switch ($this->elementBlacklist[$dsInfo['el']['id']]['position']) {
			case 1:
				$elementBackgroundStyle = 'background-color: '. ($GLOBALS['TBE_STYLES']['templavoila_bgColorWarning'] ? $GLOBALS['TBE_STYLES']['templavoila_bgColorWarning']:'#f4b0b0') .';';
				$elementPageTitlebarStyle = 'background-color: '. ($GLOBALS['TBE_STYLES']['templavoila_bgColorPageTitleWarning'] ? $GLOBALS['TBE_STYLES']['templavoila_bgColorPageTitleWarning']:'#ef4545') .';';
				$elementCETitlebarStyle = 'background-color: '. ($GLOBALS['TBE_STYLES']['templavoila_bgColorCETitleWarning'] ? $GLOBALS['TBE_STYLES']['templavoila_bgColorCETitleWarning']:'#ef7c7c') .';';
				$errorLineWithin = $this->doc->rfw($this->elementBlacklist[$dsInfo['el']['id']]['message']).'<br >';
				break;
			case -1:
				$errorLineBefore = $this->doc->rfw($this->elementBlacklist[$dsInfo['el']['id']]['message']).'<br >';
				break;
			case 2:
				$errorLineAfter = $this->doc->rfw($this->elementBlacklist[$dsInfo['el']['id']]['message']).'<br >';
			default:
		}

			// Traversing the content areas ("zones" - those shown side-by-side with dotted lines in module) of the current element:
		$cells=array();
		$headerCells=array();
		if (is_array($dsInfo['sub'][$sheet]))	{
			foreach($dsInfo['sub'][$sheet] as $fieldID => $fieldContent)	{
				$counter=0;

					// "New" and "Paste" icon:
				$elList=$this->linkNew('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/new_el.gif','').' align="absmiddle" vspace="5" border="0" title="'.$LANG->getLL ('createnewrecord').'" alt="" />',$dsInfo['el']['table'].':'.$dsInfo['el']['id'].':'.$sheet.':'.$fieldID.':'.$counter);
				if (!$clipboardElInPath)	$elList.=$this->linkPaste('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/clip_pasteafter.gif','').' align="absmiddle" vspace="5" border="0" title="'.$LANG->getLL ('pasterecord').'" alt="" />',$dsInfo['el']['table'].':'.$dsInfo['el']['id'].':'.$sheet.':'.$fieldID.':'.$counter,$this->MOD_SETTINGS['clip_parentPos'],$this->MOD_SETTINGS['clip']);
				
					// Render the list of elements (and possibly call itself recursively if needed):
				if (is_array($fieldContent['el']))	{
					foreach($fieldContent['el'] as $k => $v)	{
						$counter=$v['el']['index'];
						$elList.=$this->renderFrameWorkBasic($v,$dsInfo['el']['table'].':'.$dsInfo['el']['id'].':'.$sheet.':'.$fieldID.':'.$counter,$clipboardElInPath);
							
							// "New" and "Paste" icon:
						$elList.=$this->linkNew('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/new_el.gif','').' align="absmiddle" vspace="5" border="0" title="'.$LANG->getLL ('createnewrecord').'" alt="" />',$dsInfo['el']['table'].':'.$dsInfo['el']['id'].':'.$sheet.':'.$fieldID.':'.$counter);
						if (!$clipboardElInPath)	$elList.=$this->linkPaste('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/clip_pasteafter.gif','').' align="absmiddle" vspace="5" border="0" title="'.$LANG->getLL ('pasterecord').'" alt="" />',$dsInfo['el']['table'].':'.$dsInfo['el']['id'].':'.$sheet.':'.$fieldID.':'.$counter,$this->MOD_SETTINGS['clip_parentPos'],$this->MOD_SETTINGS['clip']);
					}
				}

					// Add cell content to registers:
				$headerCells[]='<td valign="top" width="'.round(100/count($dsInfo['sub'][$sheet])).'%" style="'.$elementCETitlebarStyle.'">'.$GLOBALS['LANG']->sL($fieldContent['meta']['title'],1).'</td>';
				$cells[]='<td valign="top" width="'.round(100/count($dsInfo['sub'][$sheet])).'%" style="border: dashed 1px #666666; padding: 5px 5px 5px 5px;">'.$elList.'</td>';
			}
		}
		
			// Compile preview content for the current element:
		$content=is_array($dsInfo['el']['previewContent']) ? implode('<br />', $dsInfo['el']['previewContent']) : '';
			// Compile the content areas for the current element (basically what was put together above):
		$content.= '<table border="0" cellpadding="2" cellspacing="2" width="100%">
			<tr>'.implode('',$headerCells).'</tr>
			<tr>'.implode('',$cells).'</tr>
		</table>';
		
			// Creating the rule compliance icon, $rulesStatus has been evaluated earlier
		if (!is_null($rulesStatus['ok'])) {
			$title = ($rulesStatus['ok'] === true ? $LANG->getLL ('ruleapplies') : $LANG->getLL ('rulefails'));
			$showRuleIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,t3lib_extMgm::extRelPath('templavoila').'mod1/'.($rulesStatus['ok'] === true ? 'green':'red').'led.gif','').' title="'.$title.'" border="0" alt="" align="absmiddle" />&nbsp;';
		}
		
			// Put together the records icon including content sensitive menu link wrapped around it:
		$recordIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,$dsInfo['el']['icon'],'').' align="absmiddle" width="18" height="16" border="0" title="'.htmlspecialchars('['.$dsInfo['el']['table'].':'.$dsInfo['el']['id'].']'.$extPath).'" alt="" title="" />';
		$recordIcon = $this->doc->wrapClickMenuOnIcon($recordIcon,$dsInfo['el']['table'],$dsInfo['el']['id']);
				
		if ($dsInfo['el']['table']!='pages')	{
			$linkCopy = $this->linkCopyCut('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/clip_copy'.$clipActive_copy.'.gif','').' title="'.$LANG->getLL ('copyrecord').'" border="0" alt="" />',($clipActive_copy ? '' : $parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id']),'copy');
			$linkCut = $this->linkCopyCut('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/clip_cut'.$clipActive_cut.'.gif','').' title="'.$LANG->getLL ('cutrecord').'" border="0" alt="" />',($clipActive_cut ? '' : $parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id']),'cut');
			$linkRef = $this->linkCopyCut('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,t3lib_extMgm::extRelPath('templavoila').'mod1/clip_ref'.$clipActive_ref.'.gif','').' title="'.$LANG->getLL ('createreference').'" border="0" alt="" />',($clipActive_ref ? '' : $parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id']),'ref');
			$linkUnlink = $this->linkUnlink('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/garbage.gif','').' title="'.$LANG->getLL ('unlinkrecord').'" border="0" alt="" />',$parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id']);
		} else {
			$linkCopy = '';
			$linkCut = '';
			$linkRef = '';
			$linkUnlink = '';
			$viewPageIcon = '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($dsInfo['el']['id'],$this->doc->backPath,t3lib_BEfunc::BEgetRootLine($dsInfo['el']['id']))).'">'.
				'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/zoom.gif','width="12" height="12"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showPage',1).'" hspace="3" alt="" align="absmiddle" />'.
				'</a>';
		}

		$content = $sheetMenu . $errorLineBefore . '
		<table border="0" cellpadding="2" cellspacing="0" style="border: 1px solid black; margin-bottom:5px; '.$elementBackgroundStyle.'" width="100%">
			<tr style="'.$elementPageTitlebarStyle.';">
				<td>'.$showRuleIcon.$recordIcon.$viewPageIcon.'&nbsp;'.($isLocal?'':'<em>').$GLOBALS['LANG']->sL($dsInfo['el']['title'],1).($isLocal?'':'</em>'). '</td>
				<td nowrap="nowrap" align="right" valign="top">'.
					$linkCopy.
					$linkCut.
					$linkRef.
					$linkUnlink.
					($isLocal ? $this->linkEdit('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','').' title="'.$LANG->getLL ('editrecord').'" border="0" alt="" />',$dsInfo['el']['table'],$dsInfo['el']['id']) : '').
				'</td>
			</tr>
			<tr><td colspan="2">'.$content.'</td></tr>
		</table>
		'.$errorLineAfter.'
		';
		
		return $content;
	}

	/**
	 * Displays a list of local content elements on the page which were NOT used in the hierarchical structure of the page.
	 * 
	 * @return	string		Display output HTML
	 * @todo	Clean up this list so it becomes full-features for the elements shown (having all the editing, copy, cut, blablabla features that other elements have)
	 */
	function renderNonUsed()	{
		global $LANG;
		
		$usedUids = array_keys($this->global_tt_content_elementRegister);
		$usedUids[]=0;
		
		$query = 'SELECT uid,header FROM tt_content WHERE pid='.intval($this->id).' AND uid NOT IN ('.implode(',',$usedUids).')'.t3lib_BEfunc::deleteClause('tt_content').' ORDER BY uid';
		$res = mysql(TYPO3_db,$query);
		$tRows='';
		while($row=mysql_fetch_assoc($res))	{
			$clipActive_cut = ($this->MOD_SETTINGS['clip']=='ref' && $this->MOD_SETTINGS['clip_parentPos']=='/tt_content:'.$row['uid'] ? '_h' : '');
			$linkIcon = $this->linkCopyCut('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/clip_cut'.$clipActive_cut.'.gif','').' title="'.$LANG->getLL ('cutrecord').'" border="0" alt="" />',($clipActive_cut ? '' : '/tt_content:'.$row['uid']),'ref');
			$tRows[]='<tr><td>'.$linkIcon.htmlspecialchars($row['header']).'</td></tr>';
		}
		$out = is_array ($tRows) ? ('Not used records:<br /><table border="1">'.implode('',$tRows).'</table>') : '';
		return $out;
	}

	/**
	 * Returns an HTML link for editing
	 * 
	 * @param	string		$str: The label (or image)
	 * @param	string		$table: The table, fx. 'tt_content'
	 * @param	integer		$uid: The uid of the element to be edited
	 * @return	string		HTML anchor tag containing the label and the correct link
	 */
	function linkEdit($str,$table,$uid)	{
		$onClick = t3lib_BEfunc::editOnClick('&edit['.$table.']['.$uid.']=edit',$this->doc->backPath);
		return '<a style="text-decoration: none;" href="#" onclick="'.htmlspecialchars($onClick).'">'.$str.'</a>';
	}

	/**
	 * Returns an HTML link for creating a new record
	 * 
	 * @param	string		$str: The label (or image)
	 * @param	string		$parentRecord: The parameters for creating the new record. Example: pages:78:sDEF:field_contentarea:0
	 * @return	string		HTML anchor tag containing the label and the correct link
	 */
	function linkNew($str,$parentRecord)	{
		return '<a href="'.htmlspecialchars('db_new_content_el.php?id='.$this->id.'&parentRecord='.rawurlencode($parentRecord)).'">'.$str.'</a>';
	}

	/**
	 * Returns an HTML link for unlinking a content element. Unlinking means that the record still exists but
	 * is not connected to any other content element or page.
	 * 
	 * @param	string		$str: The label
	 * @param	string		$unlinkRecord: The parameters for unlinking the record. Example: pages:78:sDEF:field_contentarea:0
	 * @return	string		HTML anchor tag containing the label and the unlink-link
	 */
	function linkUnlink($str,$unlinkRecord)	{
		return '<a href="index.php?id='.$this->id.'&unlinkRecord='.rawurlencode($unlinkRecord).'">'.$str.'</a>';
	}

	/**
	 * Returns an HTML link for pasting a content element. Pasting means either copying or moving a record.
	 * 
	 * @param	string		$str: The label
	 * @param	string		$params: The parameters defining the original record. Example: pages:78:sDEF:field_contentarea:0
	 * @param	string		$target: The parameters defining the target where to paste the original record
	 * @param	string		$cmd: The paste mode, usually set in the clipboard: 'cut' or 'copy'
	 * @return	string		HTML anchor tag containing the label and the paste-link
	 */
	function linkPaste($str,$params,$target,$cmd)	{
		return '<a href="index.php?id='.$this->id.'&SET[clip]=&SET[clip_parentPos]=&pasteRecord='.$cmd.'&destination='.rawurlencode($params).'&target='.rawurlencode($target).'">'.$str.'</a>';
	}

	/**
	 * Returns an HTML link for marking a content element (i.e. transferring to the clipboard) for copying or cutting.
	 * 
	 * @param	string		$str: The label
	 * @param	string		$parentPos: The parameters defining the original record. Example: pages:78:sDEF:field_contentarea::tt_content:115
	 * @param	string		$cmd: The marking mode: 'cut' or 'copy'
	 * @return	string		HTML anchor tag containing the label and the cut/copy link
	 */
	function linkCopyCut($str,$parentPos,$cmd)	{
		return '<a href="index.php?id='.$this->id.'&SET[clip]='.($parentPos?$cmd:'').'&SET[clip_parentPos]='.rawurlencode($parentPos).'">'.$str.'</a>';
	}

	/**
	 * Prints out the module HTML
	 * 
	 * @return	void		
	 */
	function printContent()    {
		echo $this->content;
	}

	
	
	
	
	/********************************************
	 *
	 * Processing
	 *
	 ********************************************/
	 
	/**
	 * Performs the neccessary steps to creates a new page
	 * 
	 * @param	array		$pageArray: array containing the fields for the new page
	 * @param	integer		$positionPid: location within the page tree (parent id)
	 * @return	integer		uid of the new page record
	 */
	function createPage($pageArray,$positionPid)	{
		$dataArr= array();
		$dataArr['pages']['NEW']=$pageArray;
		$dataArr['pages']['NEW']['pid'] = $positionPid;
		if (is_null($dataArr['pages']['NEW']['hidden'])) { 
			$dataArr['pages']['NEW']['hidden'] = 0; 
		}
		unset($dataArr['pages']['NEW']['uid']);

			// If no data structure is set, try to find one by using the template object
		if ($dataArr['pages']['NEW']['tx_templavoila_to'] && !$dataArr['pages']['NEW']['tx_templavoila_ds']) {
			$templateObjectRow = t3lib_BEfunc::getRecord('tx_templavoila_tmplobj',$dataArr['pages']['NEW']['tx_templavoila_to'],'datastructure');
			$dataArr['pages']['NEW']['tx_templavoila_ds'] = $templateObjectRow['datastructure'];
		}

		$tce = t3lib_div::makeInstance("t3lib_TCEmain");
		$tce->stripslashes_values=0;
		$tce->start($dataArr,array());
		$tce->process_datamap();
		return $tce->substNEWwithIDs['NEW'];
	}

	/**
	 * Creates default records which are defined in the datastructure's rules scheme. The property in the DS is called "ruleDefaultElements"
	 * and consists of elements only (instead of a real regular expression)! I.e. if your regular expression is like this: "(ab){2}c", it makes 
	 * sense having ruleDefaultElements contain this list of elements: "ababc".
	 *
	 * Calls itself recursively.
	 * 
	 * @param	string		$table: The table, usually "pages" or "tt_content"
	 * @param	integer		$uid: The UID
	 * @param	integer		$prevDS: Internally used to make sure data structures are not created recursively ("previous data structure")
	 * @param	integer		$level: Internally used for determine the level of recursiveness
	 * @return	void		nothing
	 * @todo				Check for rules compliance? (might not be necessary if we expect ruleDefaultElements to be valid)
	 */
	function createDefaultRecords ($table, $uid, $prevDS=-1, $level=0)	{
		global $TCA, $LANG;
		
		$tableRow = t3lib_BEfunc::getRecord ($table, $uid);
			// Recursivity check and only care about page records or flexible content elements:
		if (($level<10) && 
		    ($tableRow['tx_templavoila_ds'] != $prevDS) &&
		    ($table != 'tt_content' || $tableRow['CType'] == 'templavoila_pi1') 
		   ) {	
			$recRow = t3lib_BEfunc::getRecord ('tx_templavoila_datastructure', $tableRow['tx_templavoila_ds']);
			$xmlContent = t3lib_div::xml2array ($recRow['dataprot']);
			if (is_array ($xmlContent)) {
				foreach ($xmlContent['ROOT']['el'] as $key=>$field) {
						// Count backwards, because we're always using the same $key:
					for ($counter=strlen(trim ($field['tx_templavoila']['ruleDefaultElements'])); $counter >=0; $counter--) {
						$CType = t3lib_div::trimExplode (',',$this->rules->getCTypeFromToken (trim($field['tx_templavoila']['ruleDefaultElements'][$counter]), $field['tx_templavoila']['ruleConstants']));
						switch ($CType[0]) {						   
							case 'templavoila_pi1': 
								$TOrow = t3lib_BEfunc::getRecord('tx_templavoila_tmplobj', $CType[1], 'datastructure');
								$conf = array (
									'CType' => $CType[0],
									'tx_templavoila_ds' => $TOrow['datastructure'],
									'tx_templavoila_to' => intval($CType[1]),
								);
								$ceUid = $this->insertRecord($table.':'.intval($uid).':sDEF:'.$key,$conf);
								$this->createDefaultRecords ('tt_content', intval($ceUid), $tableRow['tx_templavoila_ds'], $level+1);
								break;
							case '':
								break;
							default:
								$conf = array (
									'CType' => $CType[0],
									'bodytext' => $LANG->getLL ('newce_defaulttext_'.$CType[0]), 
								);
								$this->insertRecord($table.':'.intval($uid).':sDEF:'.$key,$conf);
								break;
						}
					}
				}
			}
		}
	}	
	
	/**
	 * Inserts a new record (page content element)
	 * 
	 * @param	string		$createNew: consists of several parts separated by colon
	 * @param	array		$row: Array of parameters for creating the new record.
	 * @return	integer		uid of the created content element (if any)
	 */
	function insertRecord($createNew,$row)	{
		$parts = explode(':',$createNew);
		if (t3lib_div::inList('pages,tt_content',$parts[0])) {
			$parentRec = t3lib_BEfunc::getRecord($parts[0],intval($parts[1]),'uid,pid,tx_templavoila_flex');
			if (is_array($parentRec))	{
					// First, create record:
				$dataArr= array();
				$dataArr['tt_content']['NEW']=$row;
				$dataArr['tt_content']['NEW']['pid'] = ($parts[0]=="pages" ? $parentRec['uid'] : $parentRec['pid']);
				unset($dataArr['pages']['NEW']['uid']);
				$tce = t3lib_div::makeInstance("t3lib_TCEmain");
				$tce->stripslashes_values=0;
				$tce->start($dataArr,array());
				$tce->process_datamap();
				$id = $tce->substNEWwithIDs['NEW'];

				$xmlContent = t3lib_div::xml2array($parentRec['tx_templavoila_flex']);
				$dat = $xmlContent['data'][$parts[2]]['lDEF'][$parts[3]]['vDEF'];
					// ACHTUNG: We should check if $parts[2] (the sheet key) and $parts[3] (the field name) is actually in the 
					// data structure! Otherwise a possible XSS hole!
					
				$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
				$dbAnalysis->start($dat,'tt_content');

				$inserted=0;
				$idList=array();
				if ($parts[4]==0)	{
					$idList[]='tt_content_'.$id;
					$inserted=1;
				}
				$counter=0;
				foreach($dbAnalysis->itemArray as $idSet)	{
					$idList[]=$idSet['table'].'_'.$idSet['id'];
					$counter++;
					if ($parts[4]==$counter)	{
						$idList[]='tt_content_'.$id;
						$inserted=1;
					}
				}
				if (!$inserted)	{
					$idList[]='tt_content_'.$id;
				}

				$dataArr= array();
				$dataArr[$parts[0]][$parentRec['uid']]=array();
				$dataArr[$parts[0]][$parentRec['uid']]['tx_templavoila_flex']['data'][$parts[2]]['lDEF'][$parts[3]]['vDEF']=implode(',',$idList);

				$tce = t3lib_div::makeInstance("t3lib_TCEmain");
				$tce->stripslashes_values=0;
				$tce->start($dataArr,array());
				$tce->process_datamap();
				
				return $id;
			}
		}
	}

	/**
	 * Performs the processing part of pasting a record.
	 * 
	 * @param	string		$pasteCmd: Kind of pasting: 'cut', 'copy' or 'unlink'
	 * @param	string		$target: String defining the original record. Example: pages:78:sDEF:field_contentarea:0
	 * @param	string		$destination: Defines the destination where to paste the record (not used when unlinking of course).
	 * @return	void		nothing
	 */
	function pasteRecord($pasteCmd, $target, $destination)	{

			// Split the target definition into parts:
		list($targetStr,$check) = explode('/',$target);
		
		if ($targetStr)	{
			$parts_target = explode(':',$targetStr);
				// The "target" elements actually point to the target by its current position in a relation field - the $check variable should match what we find...
			if (t3lib_div::inList('pages,tt_content',$parts_target[0]))	{
				$parentRec = t3lib_BEfunc::getRecord($parts_target[0],intval($parts_target[1]),'uid,pid,tx_templavoila_flex');
				if (is_array($parentRec))	{
						// Get XML content from that field.
					$xmlContent = t3lib_div::xml2array($parentRec['tx_templavoila_flex']);
					$dat = $xmlContent['data'][$parts_target[2]]['lDEF'][$parts_target[3]]['vDEF'];
					
					$dbAnalysis_target = t3lib_div::makeInstance('t3lib_loadDBGroup');
					$dbAnalysis_target->start($dat,'tt_content');
					
					$itemOnPosition = $dbAnalysis_target->itemArray[$parts_target[4]-1];
					
						// Now, check it the current element actually matches what it should (otherwise some update must have taken place in between...)
					if ($itemOnPosition['table'].':'.$itemOnPosition['id'] == $check && $itemOnPosition['table']=='tt_content')	{	// None other than tt_content elements are moved around...
						if ($pasteCmd=='unlink')	{
							unset($dbAnalysis_target->itemArray[$parts_target[4]-1]);
							$idList_target=array();
							foreach($dbAnalysis_target->itemArray as $idSet)	{
								$idList_target[]=$idSet['table'].'_'.$idSet['id'];
							}
		
							$dataArr=array();
							$dataArr[$parts_target[0]][$parentRec['uid']]['tx_templavoila_flex']['data'][$parts_target[2]]['lDEF'][$parts_target[3]]['vDEF']=implode(',',$idList_target);
	
							$tce = t3lib_div::makeInstance("t3lib_TCEmain");
							$tce->stripslashes_values=0;
							$tce->start($dataArr,array());
							$tce->process_datamap();
						} else {
								// Now, find destination:
							$parts_destination = explode(':',$destination);
							$destinationTable = $parts_destination[0];
							if (t3lib_div::inList('pages,tt_content',$destinationTable))	{
								$destinationRec = t3lib_BEfunc::getRecord($destinationTable,intval($parts_destination[1]),'uid,pid,tx_templavoila_flex');
								if (is_array($destinationRec))	{
									$sameField = $destinationTable==$parts_target[0] 
													&& $destinationRec['uid']==$parentRec['uid'] 
													&& $parts_destination[2]==$parts_target[2] 
													&& $parts_destination[3]==$parts_target[3];

										// Get XML content from that field.
									$xmlContent = t3lib_div::xml2array($destinationRec['tx_templavoila_flex']);
									$dat = $xmlContent['data'][$parts_destination[2]]['lDEF'][$parts_destination[3]]['vDEF'];
									
									$dbAnalysis_dest = t3lib_div::makeInstance('t3lib_loadDBGroup');
									$dbAnalysis_dest->start($dat,'tt_content');
		
									if ($pasteCmd=='copy')	{
										$cmdArray=array();
										$cmdArray['tt_content'][$itemOnPosition['id']]['copy']=($destinationTable=="pages" ? $destinationRec['uid'] : $destinationRec['pid']);
										$tce = t3lib_div::makeInstance("t3lib_TCEmain");
										$tce->start(array(),$cmdArray);
										$tce->process_cmdmap();
										
										if ($tce->copyMappingArray['tt_content'][$itemOnPosition['id']])	{
											$ID = $itemOnPosition['id'] = $tce->copyMappingArray['tt_content'][$itemOnPosition['id']];	
										} else {
											# Defaulting to making a reference:
												// Move/Ref:
											$ID=$itemOnPosition['id'];	
										}
									} else {
											// Move/Ref:
										$ID=$itemOnPosition['id'];
									}
									
									$inserted=0;
									$idList_dest=array();
									if ($parts_destination[4]==0)	{
										$idList_dest[]='tt_content_'.$ID;
										$inserted=1;
									}
									$counter=0;
									foreach($dbAnalysis_dest->itemArray as $idSet)	{
										$counter++;
										if ($pasteCmd=='cut' && $sameField && $parts_target[4]==$counter)	{
											# Do nothing, don't add it again!
										} else {
											$idList_dest[]=$idSet['table'].'_'.$idSet['id'];
										}
										
										if ($parts_destination[4]==$counter)	{
											$idList_dest[]='tt_content_'.$ID;
											$inserted=1;
										}
									}
									if (!$inserted)	{
										$idList_dest[]='tt_content_'.$ID;
									}
		
										// First, create record:
									$dataArr=array();
									$cmdArray=array();
									$dataArr[$destinationTable][$destinationRec['uid']]['tx_templavoila_flex']['data'][$parts_destination[2]]['lDEF'][$parts_destination[3]]['vDEF']=implode(',',$idList_dest);
		
									if ($pasteCmd=='cut' && !$sameField)	{
										unset($dbAnalysis_target->itemArray[$parts_target[4]-1]);
										$idList_target=array();
										foreach($dbAnalysis_target->itemArray as $idSet)	{
											$idList_target[]=$idSet['table'].'_'.$idSet['id'];
										}
										$dataArr[$parts_target[0]][$parentRec['uid']]['tx_templavoila_flex']['data'][$parts_target[2]]['lDEF'][$parts_target[3]]['vDEF']=implode(',',$idList_target);
									}
									
										// If moving element, make sure the PID is changed as well so the element belongs to the page where it is moved to:
									if ($pasteCmd=='cut')	{
										$cmdArray['tt_content'][$ID]['move'] = ($destinationTable=="pages" ? $destinationRec['uid'] : $destinationRec['pid']);
									}
									
									$tce = t3lib_div::makeInstance("t3lib_TCEmain");
									$tce->stripslashes_values=0;
									$tce->start($dataArr,$cmdArray);
									$tce->process_datamap();
									$tce->process_cmdmap();
								}
							}
						}
					}
				}
			}
		} elseif($check && $pasteCmd=='ref') {

			$parts_destination = explode(':',$destination);
			$destinationTable = $parts_destination[0];
			list($table,$uid) = explode(':',$check);

			if ($table=='tt_content' && t3lib_div::inList('pages,tt_content',$destinationTable))	{
				$destinationRec = t3lib_BEfunc::getRecord($destinationTable,intval($parts_destination[1]),'uid,pid,tx_templavoila_flex');
				if (is_array($destinationRec))	{
						// Get XML content from that field.
					$xmlContent = t3lib_div::xml2array($destinationRec['tx_templavoila_flex']);
					$dat = $xmlContent['data'][$parts_destination[2]]['lDEF'][$parts_destination[3]]['vDEF'];
					
					$dbAnalysis_dest = t3lib_div::makeInstance('t3lib_loadDBGroup');
					$dbAnalysis_dest->start($dat,'tt_content');

						// Move/Ref:
					$ID=$uid;
					
					$inserted=0;
					$idList_dest=array();
					if ($parts_destination[4]==0)	{
						$idList_dest[]='tt_content_'.$ID;
						$inserted=1;
					}
					$counter=0;
					foreach($dbAnalysis_dest->itemArray as $idSet)	{
						$counter++;
						$idList_dest[]=$idSet['table'].'_'.$idSet['id'];
						if ($parts_destination[4]==$counter)	{
							$idList_dest[]='tt_content_'.$ID;
							$inserted=1;
						}
					}
					if (!$inserted)	{
						$idList_dest[]='tt_content_'.$ID;
					}

						// First, create record:
					$dataArr=array();
					$dataArr[$destinationTable][$destinationRec['uid']]['tx_templavoila_flex']['data'][$parts_destination[2]]['lDEF'][$parts_destination[3]]['vDEF']=implode(',',$idList_dest);

					$tce = t3lib_div::makeInstance("t3lib_TCEmain");
					$tce->stripslashes_values=0;
					$tce->start($dataArr,array());
					$tce->process_datamap();
				}
			}
		}
	}

	
	
	
	
	/********************************************
	 *
	 * Structure and rules functions
	 *
	 ********************************************/

	/**
	 * Gets the page ID of the folder containing the template objects (for our template selector).
	 * The storage folder is used for that purpose.
	 * 
	 * @param	integer		$positionPid
	 * @return	integer		PID of the storage folder
	 */
	function getStorageFolderPid($positionPid)	{
			// Negative PID values is pointing to a page on the same level as the current.
		if ($positionPid<0) {
			$pidRow = t3lib_BEfunc::getRecord('pages',abs($positionPid),'pid');
			$positionPid = $pidRow['pid'];
		}
		$row = t3lib_BEfunc::getRecord('pages',$positionPid);

		$TSconfig = t3lib_BEfunc::getTCEFORM_TSconfig('pages',$row);
		return intval($TSconfig['_STORAGE_PID']);
	}
		
	/**
	 * Returns the data structure for a certain page.
	 * 
	 * @param	string		$table: Table which contains the element. Only records from table 'pages' or free content elements from 'tt_content' are handled
	 * @param	integer		$id: The uid of the record
	 * @param	string		$prevRecList: comma separated list of uids, used internally for recursive calls
	 * @param	array		$row: Row of a record, used internally for recursive calls
	 * @return	array		The data structure tree
	 */
	function getDStreeForPage($table,$id,$prevRecList='',$row='')	{
		global $TCA;
		
		$row = is_array($row) ? $row : t3lib_BEfunc::getRecord($table,$id);
		$tree=array();
		$tree['el']=array(
			'table' => $table,
			'id' => $id,
			'pid' => $row['pid'],
			'title' => t3lib_div::fixed_lgd(t3lib_BEfunc::getRecordTitle($table,$row),50),
			'icon' => t3lib_iconWorks::getIcon($table,$row)
		);

		if ($table=='pages' or ($table=='tt_content' && $row['CType']=='templavoila_pi1'))	{
			$expDS = $this->getExpandedDataStructure($table,'tx_templavoila_flex',$row);
			$xmlContent = t3lib_div::xml2array($row['tx_templavoila_flex']);
		
			foreach($expDS as $sheetKey => $sVal)	{
				$tree['sub'][$sheetKey]=array();
				if (is_array($sVal['ROOT']['el']))	{
					foreach($sVal['ROOT']['el'] as $k => $v)	{
						if ($v['TCEforms']['config']['type']=='group' && $v['TCEforms']['config']['internal_type']=='db' && $v['TCEforms']['config']['allowed']=='tt_content')	{
							$tree['sub'][$sheetKey][$k]=array();
							$tree['sub'][$sheetKey][$k]['el']=array();
							$tree['sub'][$sheetKey][$k]['meta']=array(
								'title' => $v['TCEforms']['label']
							);
							
							$dat = $xmlContent['data'][$sheetKey]['lDEF'][$k]['vDEF'];
							$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
							$dbAnalysis->start($dat,'tt_content');
							$elArray=array();
							
							foreach($dbAnalysis->itemArray as $counter => $recIdent)	{
								$this->global_tt_content_elementRegister[$recIdent['id']]++;
								$idStr=$recIdent['table'].':'.$recIdent['id'];
								$idRow = $elArray[] = t3lib_BEfunc::getRecord($recIdent['table'],$recIdent['id']);
								if (!t3lib_div::inList($prevRecList,$idStr))	{
									if (is_array($idRow))	{
										$tree['sub'][$sheetKey][$k]['el'][$idStr] = $this->getDStreeForPage($recIdent['table'],$recIdent['id'],$prevRecList.','.$idStr,$idRow);
										$tree['sub'][$sheetKey][$k]['el'][$idStr]['el']['index'] = $counter+1;
									} else {
										# ERROR: The element referenced was deleted!
									}
								} else {
									# ERROR: recursivity error!	
								}
							}
#							$this->evaluateRuleOnElements($v['tx_templavoila']['ruleRegEx'],$v['tx_templavoila']['ruleConstants'],$elArray);
						} elseif (is_array($v['TCEforms'])) {
								// Example of how to detect eg. file uploads:
							if ($v['TCEforms']['config']['type']=='group' && $v['TCEforms']['config']['internal_type']=='file')	{
								$xmlContent['data'][$sheetKey]['lDEF'][$k]['vDEF'];
								$thumbnail = t3lib_BEfunc::thumbCode(array('fN'=>$xmlContent['data'][$sheetKey]['lDEF'][$k]['vDEF']),'','fN',$this->doc->backPath,'',$v['TCEforms']['config']['uploadfolder']);
								$tree['el']['previewContent'][]='<strong>'.$GLOBALS['LANG']->sL($v['TCEforms']['label'],1).'</strong> '.$thumbnail;
							} else {
								$tree['el']['previewContent'][]='<strong>'.$GLOBALS['LANG']->sL($v['TCEforms']['label'],1).'</strong> '.$this->linkEdit(htmlspecialchars(t3lib_div::fixed_lgd(strip_tags($xmlContent['data'][$sheetKey]['lDEF'][$k]['vDEF']),200)),$table,$row['uid']);
							}
						}
					}
				}
			}
		} else {
			$tree['el']['previewContent'][] = $this->renderPreviewContent($row, $table);
		}
		return $tree;
	}

	/**
	 * Returns an HTMLized preview of a certain content element. If you'd like to register a new content type, you can easily extend
	 * this function before the switch clause. Set $alreadyHandled if you provided a preview content.
	 * 
	 * @param	array		$row: The row containing the content element record; especially $row['CType'] and $row['bodytext'] are used.
	 * @param	string		$table: Name of the CType's MySQL table
	 * @return	string		HTML preview content
	 */
	function renderPreviewContent ($row, $table) {
		
			// Preview content for non-flexible content elements:
		switch($row['CType'])	{
			case 'text':		//	Text	
			case 'table':		//	Table
			case 'mailform':	//	Form
				$out='<strong>'.$GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel('tt_content','bodytext'),1).'</strong> '.$this->linkEdit(htmlspecialchars(t3lib_div::fixed_lgd(trim(strip_tags($row['bodytext'])),200)),$table,$row['uid']);
				break;
			case 'image':		//	Image
				$out='<strong>'.$GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel('tt_content','image'),1).'</strong><br /> '.t3lib_BEfunc::thumbCode ($row, $table, 'image', $this->doc->backPath, '', $v['TCEforms']['config']['uploadfolder']);
				break;
			case 'textpic':		//	Text w/image
			case 'splash':		//	Textbox
				$thumbnail = '<strong>'.$GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel('tt_content','image'),1).'</strong><br />';
				$thumbnail .= t3lib_BEfunc::thumbCode ($row, $table, 'image', $this->doc->backPath, '', $v['TCEforms']['config']['uploadfolder']);
				$text = '<strong>'.$GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel('tt_content','bodytext'),1).'</strong> ' . $this->linkEdit(htmlspecialchars(t3lib_div::fixed_lgd(trim(strip_tags($row['bodytext'])),200)),$table,$row['uid']);
				$out='<table><tr><td valign="top">'.$text.'</td><td valign="top">'.$thumbnail.'</td></tr></table>';
				break;
			case 'bullets':		//	Bullets
				$htmlBullets = '';
				$bulletsArr = explode ("\n", t3lib_div::fixed_lgd($row['bodytext'],200));
				if (is_array ($bulletsArr)) {
					foreach ($bulletsArr as $listItem) {
						$htmlBullets .= htmlspecialchars(trim(strip_tags($listItem))).'<br />';
					}
				}					
				$out='<strong>'.$GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel('tt_content','bodytext'),1).'</strong><br />'.$this->linkEdit($htmlBullets,$table,$row['uid']);
				break;
			case 'uploads':		//	Filelinks
				$out='<strong>'.$GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel('tt_content','media'),1).'</strong><br />' . $this->linkEdit(str_replace (',','<br />',htmlspecialchars(t3lib_div::fixed_lgd(trim(strip_tags($row['media'])),200))),$table,$row['uid']);
				break;
			case 'multimedia':	//	Multimedia
				$out='<strong>'.$GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel('tt_content','multimedia'),1).'</strong><br />' . $this->linkEdit (str_replace (',','<br />',htmlspecialchars(t3lib_div::fixed_lgd(trim(strip_tags($row['multimedia'])),200))),$table,$row['uid']);
				break;
			case 'menu':		//	Menu / Sitemap
				$out='<strong>'.$GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel('tt_content','menu_type')).'</strong> '.$GLOBALS['LANG']->sL(t3lib_BEfunc::getLabelFromItemlist('tt_content','menu_type',$row['menu_type'])).'<br />'.
					'<strong>'.$GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel('tt_content','pages')).'</strong> '.$row['pages'];
				break;
			case 'list':		//	Insert Plugin
				$out='<strong>'.$GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel('tt_content','list_type')).'</strong> '.$this->linkEdit(htmlspecialchars($GLOBALS['LANG']->sL(t3lib_BEfunc::getLabelFromItemlist('tt_content','menu_type',$row['list_type'])).' '.$row['list_type']),$table,$row['uid']);
				break;
			case 'html':		//	HTML
				$out='<strong>'.$GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel('tt_content','bodytext'),1).'</strong> '.$this->linkEdit (htmlspecialchars(t3lib_div::fixed_lgd(trim($row['bodytext']),200)),$table,$row['uid']);
				break;
			case 'search':		//	Search Box
			case 'login':		//	Login Box
			case 'shortcut':	//	Insert records
			case 'div':			//	Divider
				break;
			default:
				if (!$alreadyHandled) {
						// return CType name for unhandled CType
					$out='<strong>'.htmlspecialchars ($row['CType']).'</strong>';
				}
		}
		return $out;
	}
	
	/**
	 * Returns the data structure for a flexform field ($field) from $table (from $row)
	 * 
	 * @param	string		The table name
	 * @param	string		The field name
	 * @param	array		The data row (used to get DS if DS is dependant on the data in the record)
	 * @return	array		The data structure, expanded for all sheets inside.
	 */
	function getExpandedDataStructure($table,$field,$row)	{
		global $TCA;
		
		t3lib_div::loadTCA($table);
		$conf = $TCA[$table]['columns'][$field]['config'];
		$dataStructArray = t3lib_BEfunc::getFlexFormDS($conf,$row,$table);
		$output=array();
		if (is_array($dataStructArray['sheets']))	{
			foreach($dataStructArray['sheets'] as $sheetKey => $sheetInfo)	{
				list ($dataStruct, $sheet) = t3lib_div::resolveSheetDefInDS($dataStructArray,$sheetKey);
				if ($sheet == $sheetKey)	{
					$output[$sheetKey]=$dataStruct;
				}
			}
		} else {
			$sheetKey='sDEF';
			list ($dataStruct, $sheet) = t3lib_div::resolveSheetDefInDS($dataStructArray,$sheetKey);
			if ($sheet == $sheetKey)	{
				$output[$sheetKey]=$dataStruct;
			}	
		}
		return $output;
	}

	/**
	 * Checks if an element's children comply with the rules
	 * 
	 * @param	string		The content element's table
	 * @param	integer		The element's uid
	 * @return	array
	 */
	 function checkRulesForElement ($table, $uid) {	 	
		return $this->rules->evaluateRulesForElement ($table, $uid);
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