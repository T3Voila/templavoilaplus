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
 * @coauthor   Kasper Skårhøj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   84: class tx_templavoila_module1 extends t3lib_SCbase 
 *   92:     function init()    
 *  101:     function main()    
 *
 *              SECTION: Rendering functions
 *  180:     function renderEditPageScreen()    
 *  206:     function renderCreatePageScreen ($positionPid) 
 *  284:     function renderFrameWork($dsInfo)	
 *  321:     function renderTemplateSelector ($storageFolderPID, $templateType='tmplobj') 
 *  358:     function printContent()    
 *
 *              SECTION: Processing
 *  389:     function createPage($pageArray,$positionPid)	
 *  408:     function getStorageFolderPid($positionPid)	
 *  427:     function getDStreeForPage($table,$id,$prevRecList='',$row='')	
 *  480:     function getExpandedDataStructure($table,$field,$row)	
 *  514:     function evaluateRuleOnElements($rules,$ruleConstants,$elArray)	
 *  525:     function getDefaultElements($rules,$ruleConstants)	
 *
 * TOTAL FUNCTIONS: 13
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
 * @author     Robert Lemke <rl@robertlemke.de>
 */
class tx_templavoila_module1 extends t3lib_SCbase {
	var $pageinfo;
	var $rules;				// Holds an instance of the tx_templavoila_rule
	var $modTSconfig;
	
	var $global_tt_content_elementRegister=array();

	/**
	 * Initialisation
	 * 
	 * @return	void		
	 */
	function init()    {
		parent::init();
		$this->rules = t3lib_div::makeInstance('tx_templavoila_rules');			
	}

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
	 * Main function of the module. Write the content to $this->content
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
			$this->doc->backPath = $BACK_PATH;
			$this->doc->divClass = '';
			$this->doc->form='<form action="index.php?id='.$this->id.'" method="post" autocomplete="off">';

			$this->doc->JScode=$this->doc->wrapScriptTags('
				function jumpToUrl(URL)	{
					document.location = URL;
					return false;
				}
				if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
			');

			$CMparts=$this->doc->getContextMenuCode();
			$this->doc->bodyTagAdditions = $CMparts[1];
			$this->doc->JScode.=$CMparts[0];
			$this->doc->postCode.= $CMparts[2];
			
				// Just to include the Stylesheet information, nothing more:
			$this->doc->getTabMenu(0,'_',0,array(''=>''));
			
			
				// Create new CMD:
			$createNew = t3lib_div::GPvar('createNew');
			if ($createNew)	{
				$this->insertRecord($createNew,array(
					'header' => 'blablabla',
					'CType' => 'text'
				));
				
				header('Location: '.t3lib_div::locationHeaderUrl('index.php?id='.$this->id));
			}
			
				// Unlink
			$unlink = t3lib_div::GPvar('unlink');
			if ($unlink)	{
				$this->pasteRecord('unlink', $unlink, '');
				
				header('Location: '.t3lib_div::locationHeaderUrl('index.php?id='.$this->id));
			}

				// PASTE:
			$pasteCmd = t3lib_div::GPvar('pasteCmd');
			if ($pasteCmd) {
				$this->pasteRecord($pasteCmd, t3lib_div::GPvar('target'), t3lib_div::GPvar('destination'));

				header('Location: '.t3lib_div::locationHeaderUrl('index.php?id='.$this->id));
			}
			
			
			
			
			

	
				// Get the parameters
			$cmd = t3lib_div::GPvar ('cmd');
			$positionPid = t3lib_div::GPvar ('positionPid');
			
			switch ($cmd) {
				
					// Create a new page
				case 'crPage' :
						// Output the page creation form
					$this->content.=$this->renderCreatePageScreen ($positionPid);
					break;

					// Default: Edit an existing page
				default:
					$this->content.=$this->renderEditPageScreen ();
					$this->content.='<br />'.t3lib_BEfunc::getFuncMenu($this->id,'SET[view]',$this->MOD_SETTINGS['view'],$this->MOD_MENU['view'],'','');
					if ($BE_USER->mayMakeShortcut())    {
						$this->content.='<br /><br />'.$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']);
					}
			}		  

#	debug($this->MOD_SETTINGS);
#	debug($GLOBALS['HTTP_GET_VARS']);		
			
/*		
			$pageArray=array();
			$pageArray['title']='Hello Robert!';
			debug($this->createPage($pageArray,$positionPid));
*/

		} else {
			    // If no access or if ID == zero
			
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			
			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
		}
		$this->content.=$this->doc->endPage();
	}

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/********************************************
	 *
	 * Rendering functions
	 *
	 ********************************************/
	 
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


		$this->global_tt_content_elementRegister=array();
		$dsInfo = $this->getDStreeForPage('pages',$this->id);
#debug($dsInfo);
		$content.=$this->renderFrameWork($dsInfo,'',(!trim($this->MOD_SETTINGS['clip'].$this->MOD_SETTINGS['clip_parentPos']) ? 1 : 0));
		$content.=$this->renderNonUsed();
		$content.=$this->doc->spacer(10);
		
		$content.=t3lib_div::view_array($this->global_tt_content_elementRegister);
				
		return $content;		
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$id: ...
	 * @return	[type]		...
	 */
    function renderCreatePageScreen ($positionPid) {
		global $LANG, $BE_USER, $TYPO3_CONF_VARS;

			// The user already submitted the create page form:
		if (t3lib_div::GPvar ('doCreate')) {

				// Check if the HTTP_REFERER is valid
			$refInfo=parse_url(t3lib_div::getIndpEnv("HTTP_REFERER"));
			$httpHost = t3lib_div::getIndpEnv("TYPO3_HOST_ONLY");
			if ($httpHost==$refInfo["host"] || t3lib_div::GPvar("vC")==$BE_USER->veriCode() || $TYPO3_CONF_VARS["SYS"]["doNotCheckReferer"])	{
					// Create new page
				$newID = $this->createPage (t3lib_div::GPvar('data'), $positionPid);		
				if ($newID > 0) {		
						// Creating the page was successful, so just return the editscreen of the new page
					$content.=$this->renderEditPageScreen ($newId);					
					return $content;
				}
			} else {
				debug("Error: Referer host did not match with server host.");
			}
		}

			//	Output first part of the screen
		$content =$this->doc->startPage($LANG->getLL ('createnewpage_title'));
		$content.=$this->doc->header($LANG->getLL('createnewpage_title'));
		$content.=$this->doc->spacer(5);
		$content.=$LANG->getLL ('createnewpage_introduction');
		$content.=$this->doc->spacer(5);
		
		$content.=$this->doc->sectionHeader ($LANG->getLL ('createnewpage_hide_header'));
		$content.=$LANG->getLL ('createnewpage_hide_description');
		$content.=$this->doc->spacer(5);
		$content.='<input type="checkbox" name="data[hide]" checked="checked"/><br />';
		$content.=$this->doc->spacer(10);

		$content.=$this->doc->sectionHeader ($LANG->getLL ('createnewpage_pagetitle_header'));
		$content.=$LANG->getLL ('createnewpage_pagetitle_description');
		$content.=$this->doc->spacer(5);
		$content.='<input type="text" name="data[title]"'.$this->doc->formWidth(30).' /><br />';
		$content.=$this->doc->spacer(10);

		$tmplSelectorCode = '';
		$tmplSelector = $this->renderTemplateSelector ($this->getStorageFolderPid($positionPid),'tmplobj');
		if ($tmplSelector) {
			$tmplSelectorCode.=$LANG->getLL ('createnewpage_templateobject_createemptypage');
			$tmplSelectorCode.=$this->doc->spacer(5);
			$tmplSelectorCode.=$tmplSelector;
			$tmplSelectorCode.=$this->doc->spacer(10);
		}

		$tmplSelector = $this->renderTemplateSelector ($this->getStorageFolderPid($positionPid),'t3d');
		if ($tmplSelector) {
			$tmplSelectorCode.=$LANG->getLL ('createnewpage_templateobject_createpagewithdefaultcontent');
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
		
		$content.='<input type="hidden" name="doCreate" value="1" />';	
		$content.='<input type="submit" name="create" value="'.$LANG->getLL('createnewpage_submitlabel').'" />';	
									
		return $content;
	}
	
	/**
	 * Renders the "basic" display framework.
	 * 
	 * @param	array		DataStructure info array (the whole tree)
	 * @return	string		HTML
	 */
	function renderFrameWork($dsInfo,$parentPos='',$clipboardElInPath=0)	{
		$sheet=isset($dsInfo['sub'][$this->MOD_SETTINGS['sheetData']]) ? $this->MOD_SETTINGS['sheetData'] : 'sDEF';
		
			// Make sheet menu:
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
		
		$clipActive_copy = ($this->MOD_SETTINGS['clip']=='copy' && $this->MOD_SETTINGS['clip_parentPos']==$parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id'] ? '_h' : '');
		$clipActive_cut = ($this->MOD_SETTINGS['clip']=='cut' && $this->MOD_SETTINGS['clip_parentPos']==$parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id'] ? '_h' : '');
		$clipActive_ref = ($this->MOD_SETTINGS['clip']=='ref' && $this->MOD_SETTINGS['clip_parentPos']==$parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id'] ? '_h' : '');
		$clipboardElInPath = trim($clipActive_copy.$clipActive_cut.$clipActive_ref)||$clipboardElInPath?1:0;
		
		
		$cells=array();
		$headerCells=array();
		if (is_array($dsInfo['sub'][$sheet]))	{
			foreach($dsInfo['sub'][$sheet] as $fieldID => $fieldContent)	{
				$counter=0;

				$elList=$this->linkNew('<img src="'.$this->doc->backPath.'gfx/new_el.gif" align="absmiddle" vspace="5" border="0" title="New" alt="" />',$dsInfo['el']['table'].':'.$dsInfo['el']['id'].':'.$sheet.':'.$fieldID.':'.$counter);
				if (!$clipboardElInPath)	$elList.=$this->linkPaste('<img src="'.$this->doc->backPath.'gfx/clip_pasteafter.gif" align="absmiddle" vspace="5" border="0" title="Paste" alt="" />',$dsInfo['el']['table'].':'.$dsInfo['el']['id'].':'.$sheet.':'.$fieldID.':'.$counter,$this->MOD_SETTINGS['clip_parentPos'],$this->MOD_SETTINGS['clip']);
				

				if (is_array($fieldContent['el']))	{
					foreach($fieldContent['el'] as $k => $v)	{
						$counter=$v['el']['index'];
						$elList.=$this->renderFrameWork($v,$dsInfo['el']['table'].':'.$dsInfo['el']['id'].':'.$sheet.':'.$fieldID.':'.$counter,$clipboardElInPath);
						$elList.=$this->linkNew('<img src="'.$this->doc->backPath.'gfx/new_el.gif" align="absmiddle" vspace="5" border="0" title="New" alt="" />',$dsInfo['el']['table'].':'.$dsInfo['el']['id'].':'.$sheet.':'.$fieldID.':'.$counter);
						if (!$clipboardElInPath)	$elList.=$this->linkPaste('<img src="'.$this->doc->backPath.'gfx/clip_pasteafter.gif" align="absmiddle" vspace="5" border="0" title="Paste" alt="" />',$dsInfo['el']['table'].':'.$dsInfo['el']['id'].':'.$sheet.':'.$fieldID.':'.$counter,$this->MOD_SETTINGS['clip_parentPos'],$this->MOD_SETTINGS['clip']);
					}
				}

				$headerCells[]='<td valign="top" width="'.round(100/count($dsInfo['sub'][$sheet])).'%" style="background-color: '.$this->doc->bgColor4.';">'.$GLOBALS['LANG']->sL($fieldContent['meta']['title'],1).'</td>';
				$cells[]='<td valign="top" width="'.round(100/count($dsInfo['sub'][$sheet])).'%" style="border: dashed 1px #666666; padding: 5px 5px 5px 5px;">'.$elList.'</td>';
			}
		}
		
		$content=is_array($dsInfo['el']['previewContent']) ? implode('<br />', $dsInfo['el']['previewContent']) : '';
		$content.= '<table border="0" cellpadding="2" cellspacing="2" width="100%">
			<tr>'.implode('',$headerCells).'</tr>
			<tr>'.implode('',$cells).'</tr>
		</table>';
		
		$isLocal = $dsInfo['el']['table']=='pages' || $dsInfo['el']['pid']==$this->id;	// Pages should be seem as local...
		
		$extPath = '';
		if (!$isLocal)	{
			$extPath = ' - PATH: '.t3lib_BEfunc::getRecordPath($dsInfo['el']['pid'],$this->perms_clause,30);
		}

		$recordIcon = '<img src="'.$this->doc->backPath.$dsInfo['el']['icon'].'" align="absmiddle" width="18" height="16" border="0" title="'.htmlspecialchars('['.$dsInfo['el']['table'].':'.$dsInfo['el']['id'].']'.$extPath).'" alt="" />';
		$recordIcon = $this->doc->wrapClickMenuOnIcon($recordIcon,$dsInfo['el']['table'],$dsInfo['el']['id']);
		
		if ($dsInfo['el']['table']!='pages')	{
			$linkCopy = $this->linkCopyCut('<img src="'.$this->doc->backPath.'gfx/clip_copy'.$clipActive_copy.'.gif" title="Copy" border="0" alt="" />',($clipActive_copy ? '' : $parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id']),'copy');
			$linkCut = $this->linkCopyCut('<img src="'.$this->doc->backPath.'gfx/clip_cut'.$clipActive_cut.'.gif" title="Move" border="0" alt="" />',($clipActive_cut ? '' : $parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id']),'cut');
			$linkRef = $this->linkCopyCut('<img src="./clip_ref'.$clipActive_ref.'.gif" title="Reference" border="0" alt="" />',($clipActive_ref ? '' : $parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id']),'ref');
			$linkUnlink = $this->linkUnlink('<img src="'.$this->doc->backPath.'gfx/garbage.gif" title="Unlink" border="0" alt="" />',$parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id']);
		} else {
			$linkCopy = '';
			$linkCut = '';
			$linkRef = '';
			$linkUnlink = '';
		}
		
		$content=$sheetMenu.'
		<table border="0" cellpadding="2" cellspacing="0" style="border: 1px solid black; margin-bottom:5px;" width="100%">
			<tr style="background-color: '.($dsInfo['el']['table']=='pages' ? $this->doc->bgColor2 : ($isLocal ? $this->doc->bgColor5 : $this->doc->bgColor6)).';">
				<td>'.$recordIcon.'&nbsp;'.($isLocal?'':'<em>').$GLOBALS['LANG']->sL($dsInfo['el']['title'],1).($isLocal?'':'</em>').'</td>
				<td nowrap="nowrap" align="right" valign="top">'.
					$linkCopy.
					$linkCut.
					$linkRef.
					$linkUnlink.
					($isLocal ? $this->editLink('<img src="'.$this->doc->backPath.'gfx/edit2.gif" title="Edit" border="0" alt="" />',$dsInfo['el']['table'],$dsInfo['el']['id']) : '').
				'</td>
			</tr>
			<tr><td colspan="2">'.$content.'</td></tr>
		</table>
		';
		
		return $content;
	}   
	
	function renderNonUsed()	{
		$usedUids = array_keys($this->global_tt_content_elementRegister);
		$usedUids[]=0;
		
		$query = 'SELECT uid,header FROM tt_content WHERE pid='.intval($this->id).' AND uid NOT IN ('.implode(',',$usedUids).')'.
				t3lib_BEfunc::deleteClause('tt_content').' ORDER BY uid';
		$res = mysql(TYPO3_db,$query);
		$tRows=array();
		while($row=mysql_fetch_assoc($res))	{
			$clipActive_cut = ($this->MOD_SETTINGS['clip']=='ref' && $this->MOD_SETTINGS['clip_parentPos']=='/tt_content:'.$row['uid'] ? '_h' : '');
			$linkIcon = $this->linkCopyCut('<img src="'.$this->doc->backPath.'gfx/clip_cut'.$clipActive_cut.'.gif" title="Move" border="0" alt="" />',($clipActive_cut ? '' : '/tt_content:'.$row['uid']),'ref');
			$tRows[]='<tr><td>'.$linkIcon.htmlspecialchars($row['header']).'</td></tr>';
		}
		
		return '<table border="1">'.implode('',$tRows).'</table>';
	}
	
	function editLink($str,$table,$uid)	{
		$onClick = t3lib_BEfunc::editOnClick('&edit['.$table.']['.$uid.']=edit',$this->doc->backPath);
		return '<a href="#" onclick="'.htmlspecialchars($onClick).'">'.$str.'</a>';
	}
	function linkNew($str,$params)	{
		return '<a href="index.php?id='.$this->id.'&createNew='.rawurlencode($params).'">'.$str.'</a>';
	}
	function linkUnlink($str,$params)	{
		return '<a href="index.php?id='.$this->id.'&unlink='.rawurlencode($params).'">'.$str.'</a>';
	}
	function linkPaste($str,$params,$target,$cmd)	{
		return '<a href="index.php?id='.$this->id.'&SET[clip]=&SET[clip_parentPos]=&pasteCmd='.$cmd.'&destination='.rawurlencode($params).'&target='.rawurlencode($target).'">'.$str.'</a>';
	}
	function linkCopyCut($str,$parentPos,$cmd)	{
		return '<a href="index.php?id='.$this->id.'&SET[clip]='.($parentPos?$cmd:'').'&SET[clip_parentPos]='.rawurlencode($parentPos).'">'.$str.'</a>';
	}

	/**
	 * Renders the template selector.
	 * 
	 * @param	[type]		$storageFolderPID: ...
	 * @param	[type]		$templateType: ...
	 * @return	[type]		...
	 */
	function renderTemplateSelector ($storageFolderPID, $templateType='tmplobj') {
		
		switch ($templateType) {			
			case 'tmplobj':				
				$query='SELECT * FROM `tx_templavoila_tmplobj` WHERE `pid`='.intval($storageFolderPID).t3lib_befunc::deleteClause ('tx_templavoila_tmplobj');
				$res = mysql(TYPO3_db, $query);
				while ($row = @mysql_fetch_assoc($res))	{

						// Check if the preview icon of the template object exists. If it doesn't, use the default icon instead.
					$previewIconFilename = 'uploads/tx_templavoila/'.$row['previewicon'];					
		         if (@is_file(PATH_site.$previewIconFilename)) {
		         	$previewIcon = '<img src="'.$GLOBALS['BACK_PATH'].'../'.$previewIconFilename.'" title="'.htmlspecialchars($row['fileref']).'" alt="" border="0" />';
		         } else {
		         	$previewIcon = '<img src="'.t3lib_div::getFileAbsFileName('EXT:templavoila/res1/default_previewicon.gif',0,1).'" title="'.htmlspecialchars($row['fileref']).'" alt="" border="0" />';
		         }
					$previewIcon = '<a href="#" onclick="this.blur();return false;">'.$previewIcon.'</a>';
		         
					$tmplHTML [] = '<table style="vertical-align:top; display: inline"><tr><td colspan="2"><h3>'.htmlspecialchars($row['title']).'</h3></td></tr>'.
						'<tr><td valign="top">'.$previewIcon.'</td><td style="width: 120px" valign="top"><p>'.htmlspecialchars($row['description']).'</p></td></tr></table>';
				}
				if (is_array ($tmplHTML)) {
					$content = implode (' ',$tmplHTML);
				}
				break;
			
			case 't3d':
				break;
				
		}
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

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/********************************************
	 *
	 * Processing
	 *
	 ********************************************/
	 
	/**
	 * Creates a new page
	 * 
	 * @param	[array]		$pageArray: ...
	 * @param	[integer]		$positionPid: ...
	 * @return	[type]		...
	 */
	function createPage($pageArray,$positionPid)	{
		$dataArr= array();
		$dataArr['pages']['NEW']=$pageArray;
		$dataArr['pages']['NEW']['pid'] = $positionPid;
		unset($dataArr['pages']['NEW']['uid']);
		$tce = t3lib_div::makeInstance("t3lib_TCEmain");
		$tce->stripslashes_values=0;
		$tce->start($dataArr,array());
		$tce->process_datamap();
		return $tce->substNEWwithIDs['NEW'];
	}

	function insertRecord($createNew,$row)	{
		$parts = explode(':',$createNew);

		if (t3lib_div::inList('pages,tt_content',$parts[0]))	{
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
				$ID = $tce->substNEWwithIDs['NEW'];
				
				$xmlContent = t3lib_div::xml2array($parentRec['tx_templavoila_flex']);
				$dat = $xmlContent['data'][$parts[2]]['lDEF'][$parts[3]]['vDEF'];
					// ACHTUNG: We should check if $parts[2] (the sheet key) and $parts[3] (the field name) is actually in the data structure! Otherwise a possible XSS hole!
					
				$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
				$dbAnalysis->start($dat,'tt_content');
				
				$inserted=0;
				$idList=array();
				if ($parts[4]==0)	{
					$idList[]='tt_content_'.$ID;
					$inserted=1;
				}
				$counter=0;
				foreach($dbAnalysis->itemArray as $idSet)	{
					$idList[]=$idSet['table'].'_'.$idSet['id'];
					$counter++;
					if ($parts[4]==$counter)	{
						$idList[]='tt_content_'.$ID;
						$inserted=1;
					}
				}
				if (!$inserted)	{
					$idList[]='tt_content_'.$ID;
				}

					// First, create record:
				$dataArr= array();
				$dataArr[$parts[0]][$parentRec['uid']]=array();
				$dataArr[$parts[0]][$parentRec['uid']]['tx_templavoila_flex']['data'][$parts[2]]['lDEF'][$parts[3]]['vDEF']=implode(',',$idList);

				$tce = t3lib_div::makeInstance("t3lib_TCEmain");
				$tce->stripslashes_values=0;
				$tce->start($dataArr,array());
				$tce->process_datamap();
			}
		}
	}
	
	
	function pasteRecord($pasteCmd, $target, $destination)	{
#debug(array($pasteCmd, $target, $destination));
#exit;	
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
#debug(array($destinationTable,$parts_target[0],$destinationRec['uid'],$parentRec['uid'],$parts_destination[2],$parts_target[2],$parts_destination[3],$parts_target[3]));
									
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
#if (!$sameField)	{debug($dataArr);debug($cmdArray);	exit;	}
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


	/**
	 * Gets the page ID of the folder containing the template objects (for our template selector).
	 * The storage folder is used for that purpose.
	 * 
	 * @param	[integer]		$positionPid
	 * @return	[integer]		PID of the storage folder
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
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$prevRecList: ...
	 * @param	[type]		$row: ...
	 * @return	[type]		...
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
							$this->evaluateRuleOnElements($v['tx_templavoila']['ruleRegEx'],$v['tx_templavoila']['ruleConstants'],$elArray);
						} elseif (is_array($v['TCEforms'])) {
								// Example of how to detect eg. file uploads:
							if ($v['TCEforms']['config']['type']=='group' && $v['TCEforms']['config']['internal_type']=='file')	{
								$xmlContent['data'][$sheetKey]['lDEF'][$k]['vDEF'];
								$thumbnail = t3lib_BEfunc::thumbCode(array('fN'=>$xmlContent['data'][$sheetKey]['lDEF'][$k]['vDEF']),'','fN',$this->doc->backPath,'',$v['TCEforms']['config']['uploadfolder']);
								$tree['el']['previewContent'][]='<strong>'.$GLOBALS['LANG']->sL($v['TCEforms']['label'],1).'</strong> '.$thumbnail;
							} else {
								$tree['el']['previewContent'][]='<strong>'.$GLOBALS['LANG']->sL($v['TCEforms']['label'],1).'</strong> '.htmlspecialchars(t3lib_div::fixed_lgd(strip_tags($xmlContent['data'][$sheetKey]['lDEF'][$k]['vDEF']),200));
							}
						}
					}
				}
			}
		} else {
				// Preview content for non-flexible content elements:
			switch($row['CType'])	{
				case 'text':
					$tree['el']['previewContent'][]='<strong>'.$GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel('tt_content','bodytext'),1).'</strong> '.htmlspecialchars(t3lib_div::fixed_lgd(trim(strip_tags($row['bodytext'])),200));
				break;
			}
		}
		return $tree;
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
	 * This evaluates whether a certain rule is acceptable for the elements found in the field for which the rule applies
	 * 
	 * @param	string		The rule regex
	 * @param	string		The rule constants
	 * @param	array		An array of tt_content element records
	 * @return	array		Array with information about the rule status (includes OK flag, the rule text etc.)
	 */
	function evaluateRuleOnElements($rules,$ruleConstants,$elArray)	{
		return $this->rules->evaluateRulesOnElements ($rules,$ruleConstants,$elArray);
	}

	/**
	 * Returns an array of arrays with default values for records to create in tt_content table based on the rule/+constants given.
	 * 
	 * @param	string		The rule regex
	 * @param	string		The rule constants
	 * @return	array		An array of arrays with keynames matching fields in tt_content to set.
	 */
	function getDefaultElements($rules,$ruleConstants)	{
		return $this->rules->getDefaultElements($rules,$ruleConstants);
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
