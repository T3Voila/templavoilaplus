<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003, 2004  Robert Lemke (robert@typo3.org)
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
 * @author     Robert Lemke <robert@typo3.org>
 * @coauthor   Kasper Skårhøj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  129: class tx_templavoila_module1 extends t3lib_SCbase
 *
 *              SECTION: Initialization functions
 *  169:     function init()
 *  203:     function menuConfig()
 *
 *              SECTION: Main functions
 *  255:     function main()
 *
 *              SECTION: Rendering functions
 *  382:     function renderEditPageScreen()
 *  444:     function renderEditPageScreenMountPoint()
 *
 *              SECTION: Framework rendering functions
 *  495:     function renderFrameWork_allSheets($dsInfo, $parentPos='', $clipboardElInPath=0, $referenceInPath=0)
 *  532:     function renderFrameWork($dsInfo, $parentPos, $clipboardElInPath, $referenceInPath, $sheet)
 *
 *              SECTION: Rendering functions for certain subparts
 *  786:     function renderPreviewContent ($row, $table)
 *  873:     function renderLocalizationInfoTable($dsInfo)
 *
 *              SECTION: Link functions
 *  962:     function linkEdit($str, $table, $uid)
 *  974:     function linkNew($str, $parentRecord)
 *  987:     function linkUnlink($str, $unlinkRecord, $realDelete=FALSE)
 * 1004:     function linkMakeLocal($str, $makeLocalRecord)
 * 1019:     function linkPaste($str, $source, $destination, $cmd)
 * 1031:     function linkCopyCut($str, $source, $cmd)
 * 1040:     function linkParams()
 *
 *              SECTION: Command functions
 * 1066:     function cmd_createNewRecord ($parentRecord, $defVals='')
 * 1084:     function cmd_unlinkRecord ($unlinkRecord)
 * 1096:     function cmd_deleteRecord ($deleteRecord)
 * 1108:     function cmd_makeLocalRecord ($makeLocalRecord)
 * 1120:     function cmd_pasteRecord ($pasteMode)
 * 1132:     function cmd_createNewTranslation ($languageUid)
 *
 *              SECTION: Processing
 * 1158:     function createPage($pageArray,$positionPid)
 * 1194:     function createDefaultRecords ($table, $uid, $prevDS=-1, $level=0)
 * 1245:     function insertRecord($destination, $row)
 * 1260:     function pasteRecord($pasteCmd, $source, $destination)
 *
 *              SECTION: Structure functions
 * 1284:     function getStorageFolderPid($positionPid)
 * 1305:     function getDStreeForPage($table, $id, $prevRecList='', $row='')
 * 1407:     function getExpandedDataStructure($table, $field, $row)
 * 1442:     function checkRulesForElement ($table, $uid)
 *
 *              SECTION: Miscelleaneous functions
 * 1465:     function getAvailableLanguages($id=0, $onlyIsoCoded=true, $setDefault=true, $setMulti=false)
 * 1535:     function printContent()
 *
 * TOTAL FUNCTIONS: 32
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
$BE_USER->modAccess($MCONF,1);    								// This checks permissions and exits if the users has no permission for entry.

t3lib_extMgm::isLoaded('cms',1);

	// We need the TCE forms functions
require_once (PATH_t3lib.'class.t3lib_loaddbgroup.php');
require_once (PATH_t3lib.'class.t3lib_tcemain.php');

	// Include rules engine
require_once (t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_rules.php');

	// Include class for management of the relations inside the FlexForm XML:
require_once (t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_xmlrelhndl.php');

	// Include class for rendering the side bar and wizards:
require_once (t3lib_extMgm::extPath('templavoila').'mod1/class.tx_templavoila_mod1_sidebar.php');
require_once (t3lib_extMgm::extPath('templavoila').'mod1/class.tx_templavoila_mod1_wizards.php');

/**
 * Module 'Page' for the 'templavoila' extension.
 *
 * @author		Robert Lemke <robert@typo3.org>
 * @coauthor	Kasper Skaarhoj <kasper@typo3.com>
 * @package		TYPO3
 * @subpackage	tx_templavoila
 */
class tx_templavoila_module1 extends t3lib_SCbase {

	var $modTSconfig;								// This module's TSconfig
	var $modSharedTSconfig;							// TSconfig from mod.SHARED
	var $extKey = 'templavoila';					// Extension key of this module

	var $global_tt_content_elementRegister=array(); // Contains a list of all content elements which are used on the page currently being displayed (with version, sheet and language currently set). Mainly used for showing "unused elements" in sidebar.
	var $elementBlacklist=array();					// Used in renderFrameWork (list of CEs causing errors)

	var $altRoot = array();							// Keys: "table", "uid", "field_flex" - thats all to define another "rootTable" than "pages" (using default field "tx_templavoila_flex" for flex form content)
	var $versionId = 0;								// The current version id
	var $editVersionUid = 0;

	var $currentDataStructureArr = array();			// Contains the data structure XML structure indexed by tablenames ('pages', 'tt_content') as an array of the currently selected DS record when editing a page
	var $currentPageRecord;							// Contains the page record (from table 'pages') of the current page when editing a page
	var $currentLanguageKey;						// Contains the currently selected language key (Example: DEF or DE)
	var $currentLanguageUid;						// Contains the currently selected language uid (Example: -1, 0, 1, 2, ...)
	var $allAvailableLanguages = array();			// Contains records of all available languages (not hidden, with ISOcode), including the default language and multiple languages. Used for displaying the flags for content elements, set in init().
	var $translatedLanguagesArr = array();			// Select language for which there is a page translation

	var $doc;										// Instance of template doc class
	var $sideBarObj;								// Instance of sidebar class
	var $wizardsObj;								// Instance of wizards class
	var $rulesObj;									// Instance of the tx_templavoila_rule

	var $sideBarPosition = 'toptabs';				// The position of the navigation bar ("sidebar"). Possible values (see sidebar class!): toptabs, toprows, left



	/*******************************************
	 *
	 * Initialization functions
	 *
	 *******************************************/

	/**
	 * Initialisation of this backend module
	 *
	 * @return	void
	 */
	function init()    {
		parent::init();

		$this->rulesObj =& t3lib_div::getUserObj ('&tx_templavoila_rules','');
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);

		$this->altRoot = t3lib_div::_GP('altRoot');
		$this->versionId = t3lib_div::_GP('versionId');

			// Fill array allAvailableLanguages and currently selected language (from language selector or from outside)
		$this->allAvailableLanguages = $this->getAvailableLanguages(0, true, true, true);
		$this->currentLanguageKey = $this->allAvailableLanguages[$this->MOD_SETTINGS['language']]['ISOcode'];
		$this->currentLanguageUid = $this->allAvailableLanguages[$this->MOD_SETTINGS['language']]['uid'];

			// If no translations exist for this page, set the current language to default (as there won't be a language selector)
		$this->translatedLanguagesArr = $this->getAvailableLanguages($this->id);
		if (count($this->translatedLanguagesArr) == 1) {	// Only default language exists
			$this->currentLanguageKey = 'DEF';
		}

			// Initialize side bar and wizards:
		$this->sideBarObj =& t3lib_div::getUserObj ('&tx_templavoila_mod1_sidebar','');
		$this->sideBarObj->init($this);
		$this->sideBarObj->position = $this->sideBarPosition;

		$this->wizardsObj = t3lib_div::getUserObj('&tx_templavoila_mod1_wizards','');
		$this->wizardsObj->init($this);
	}

	/**
	 * Preparing menu content and initializing clipboard and module TSconfig
	 *
	 * @return	void
	 */
	function menuConfig()	{
		global $LANG, $TYPO3_CONF_VARS;

			// Prepare array of sys_language uids for available translations:
		$this->translatedLanguagesArr = $this->getAvailableLanguages($this->id);
		$translatedLanguagesUids = array();
		foreach ($this->translatedLanguagesArr as $languageRecord) {
			$translatedLanguagesUids[$languageRecord['uid']] = $languageRecord['title'];
		}

		$this->MOD_MENU = array(
			'tt_content_showHidden' => 1,
			'language' => $translatedLanguagesUids,
			'clip_parentPos' => '',
			'clip' => '',
		);

			// Hook: menuConfig_preProcessModMenu
		if (is_array ($TYPO3_CONF_VARS['EXTCONF']['templavoila']['mod1']['menuConfigClass'])) {
			foreach ($TYPO3_CONF_VARS['EXTCONF']['templavoila']['mod1']['menuConfigClass'] as $classRef) {
				$hookObj = &t3lib_div::getUserObj ($classRef);
				if (method_exists ($hookObj, 'menuConfig_preProcessModMenu')) {
					$hookObj->menuConfig_preProcessModMenu ($this->MOD_MENU, $this);
				}
			}
		}

			// page/be_user TSconfig settings and blinding of menu-items
		$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->id,'mod.'.$this->MCONF['name']);
		$this->MOD_MENU['view'] = t3lib_BEfunc::unsetMenuItems($this->modTSconfig['properties'],$this->MOD_MENU['view'],'menu.function');

		$this->modSharedTSconfig = t3lib_BEfunc::getModTSconfig($this->id, 'mod.SHARED');

			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);
	}





	/*******************************************
	 *
	 * Main functions
	 *
	 *******************************************/

	/**
	 * Main function of the module.
	 *
	 * @return	void
	 */
	function main()    {
		global $BE_USER,$LANG,$BACK_PATH;

			// Access check! The page will show only if there is a valid page and if this page may be viewed by the user
		if (is_array($this->altRoot))	{
			$access = true;
		} else {
			$pageInfoArr = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
			$access = (intval($pageInfoArr['uid'] > 0));
		}

		if ($access)    {

				// Check if we have to update the pagetree:
			if (t3lib_div::_GP('updatePageTree')) {
				t3lib_BEfunc::getSetUpdateSignal('updatePageTree');
			}

				// Draw the header.
			$this->doc = t3lib_div::makeInstance('noDoc');
			$this->doc->docType= 'xhtml_trans';
			$this->doc->backPath = $BACK_PATH;
			$this->doc->divClass = '';
			$this->doc->form='<form action="'.htmlspecialchars('index.php?'.$this->linkParams()).'" method="post" autocomplete="off">';

				// Adding classic jumpToUrl function, needed for the function menu. Also, the id in the parent frameset is configured.
			$this->doc->JScode = $this->doc->wrapScriptTags('
				function jumpToUrl(URL)	{ //
					document.location = URL;
					return false;
				}
				if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
			');

				// Set up JS for dynamic tab menu and side bar
			$this->doc->JScode .= $this->doc->getDynTabMenuJScode();
			$this->doc->JScode .= $this->sideBarObj->getJScode();

				// Setting up support for context menus (when clicking the items icon)
			$CMparts = $this->doc->getContextMenuCode();
			$this->doc->bodyTagAdditions = $CMparts[1];
			$this->doc->JScode.= $CMparts[0];
			$this->doc->postCode.= $CMparts[2];

				// Go through the commands and check if we have to do some action:
			$commands = array ('createNewRecord', 'unlinkRecord', 'deleteRecord','pasteRecord', 'makeLocalRecord', 'createNewTranslation');
			foreach ($commands as $cmd) {
				unset ($params);
				$params = t3lib_div::_GP($cmd);
				$function = 'cmd_'.$cmd;

					// If the current function has a parameter passed by GET or POST, call the related function:
				if ($params && is_callable(array ($this, $function))) {
				 	$this->$function ($params);
				}
			}

				// Show the "edit current page" screen along with the sidebar. The sidebar needs information which is only available after
				// rendering the edit screen, that's why we delay the output. The sidebar will only be rendered at THIS point, if its position is "left"
			$editPageScreen = $this->renderEditPageScreen ();
			$sideBar = ($this->sideBarPosition == 'left') ? $this->sideBarObj->render($dsInfo) : FALSE;

				// Create make-shortcut-icon:
			if ($BE_USER->mayMakeShortcut()) {
				$editPageScreen .= '<br />'.$this->doc->makeShortcutIcon('id,altRoot',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']);
			}
				// "Show hidden" checkbox:
			$editPageScreen .= ' '.t3lib_BEfunc::getFuncCheck($this->id,'SET[tt_content_showHidden]',$this->MOD_SETTINGS['tt_content_showHidden'],'','').' '.$LANG->getLL('printconte_showHiddenElements','1');

				// Start creating HTML output
			$this->content .= $this->doc->startPage($LANG->getLL('title'));

			if ($sideBar) {
				$this->content .= '
					<table cellspacing="0" cellpadding="0" style="width:100%; height:550px; padding:0; margin:0;">
						<tr>
							<td style="vertical-align:top;">'.$sideBar.'</td>
							<td style="vertical-align:top; padding-bottom:20px;" width="99%">'.$editPageScreen.'</td>
						</tr>
					</table>
				';
			} else {
				$this->content .= $editPageScreen;
			}
			unset ($sideBar);
			unset ($editPageScreen);

		} else {	// No access or no current page uid:

			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->docType= 'xhtml_trans';
			$this->doc->backPath = $BACK_PATH;
			$this->content.=$this->doc->startPage($LANG->getLL('title'));

			$cmd = t3lib_div::_GP ('cmd');
			switch ($cmd) {

					// Create a new page
				case 'crPage' :
						// Output the page creation form
					$this->content .= $this->wizardsObj->renderWizard_createNewPage (t3lib_div::_GP ('positionPid'));
					break;

					// If no access or if ID == zero
				default:
					$this->content.=$this->doc->header($LANG->getLL('title'));
					$this->content.=$LANG->getLL('default_introduction');
			}
		}
		$this->content.=$this->doc->endPage();
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
		global $LANG, $BE_USER, $TYPO3_CONF_VARS;

			// Reset internal variable which registers all used content elements:
		$this->global_tt_content_elementRegister=array();

			// Setting whether an element is on the clipboard or not
		$clipboardElInPath = (!trim($this->MOD_SETTINGS['clip'].$this->MOD_SETTINGS['clip_parentPos']) ? 1 : 0);

			// Get current page record for later usage
		$this->currentPageRecord = t3lib_BEfunc::getRecord ('pages', $this->id);
		$this->topPagePid = $this->id;

			// Get data structure array for the page/content elements.
			// Returns a BIG array reflecting the "treestructure" of the pages content elements.
		if (is_array($this->altRoot))	{
			#($this->editVersionUid ? $this->editVersionUid : $this->altRoot['uid'])
			$dsArr = $this->getDStreeForPage($this->altRoot['table'], $this->altRoot['uid']);
		} else {
				// Get current page record for later usage
/*			if ($this->editVersionUid && $this->editVersionUid != $this->id)	{
				$this->currentPageRecord = t3lib_BEfunc::getRecord ('pages', $this->editVersionUid);
				$this->topPagePid = $this->editVersionUid;
			}
	*/		$dsArr = $this->getDStreeForPage('pages', $this->topPagePid, '', $this->currentPageRecord);
		}

			// Check if it makes sense to allow editing of this page and if it's not, show a message
		switch ($this->currentPageRecord['doktype']) {
			case 7:		// Mount Point
				if ($this->currentPageRecord['mount_pid_ol']) {
					return $content . $this->renderEditPageScreenMountPoint($this->id);
				}
			break;
		}

			// Create a back button if neccessary:
		if (is_array ($this->altRoot)) {
			$content .= '<div style="text-align:right; width:100%; margin-bottom:5px;"><a href="index.php?id='.$this->id.'"><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/goback.gif','').' title="'.htmlspecialchars($LANG->getLL ('goback')).'" alt="" /></a></div>';
		}

			// Hook for content at the very top (fx. a toolbar):
		if (is_array ($TYPO3_CONF_VARS['EXTCONF']['templavoila']['mod1']['renderTopToolbar'])) {
			foreach ($TYPO3_CONF_VARS['EXTCONF']['templavoila']['mod1']['renderTopToolbar'] as $_funcRef) {
				$_params = array ();
				$content .= t3lib_div::callUserFunction($_funcRef, $_params, $this);
			}
		}

			// Display the nested page structure:
		$content.= $this->renderFramework_allSheets($dsArr,'',$clipboardElInPath);
		$content .= t3lib_BEfunc::cshItem('_MOD_web_txtemplavoilaM1', 'pagemodule', $this->doc->backPath,'|<br/>');
		$content .= t3lib_BEfunc::cshItem('_MOD_web_txtemplavoilaM1', '', $this->doc->backPath,'<hr/>|What is the TemplaVoila Page module?');

		return $content;
	}

	/**
	 * Displays the edit page screen if the currently selected page is of the doktype "Mount Point"
	 *
	 * @return	string		The modules content
	 */
	function renderEditPageScreenMountPoint()    {
		global $LANG, $BE_USER, $TYPO3_CONF_VARS;

			// Put together the records icon including content sensitive menu link wrapped around it:
		$recordIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/i/pages_mountpoint.gif','').' style="text-align: center; vertical-align: middle;" width="18" height="16" border="0" title="'.htmlspecialchars('[pages]'.$extPath).'" alt="" />';
		$recordIcon = $this->doc->wrapClickMenuOnIcon($recordIcon, 'pages', $this->id, 1, '&callingScriptId='.rawurlencode($this->doc->scriptID));

		$viewPageIcon = '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($this->id, $this->doc->backPath, t3lib_BEfunc::BEgetRootLine($this->id))).'">';
		$viewPageIcon .= '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/zoom.gif','width="12" height="12"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.showPage',1).'" hspace="3" alt="" style="text-align: center; vertical-align: middle;" />';

		$mountSourcePageRecord = t3lib_beFunc::getRecord ('pages', $this->currentPageRecord['mount_pid']);

		$content = '
			<table border="0" cellpadding="2" cellspacing="0" style="border: 1px solid black; margin-bottom:5px; width:100%">
				<tr style="background-color: '.$this->doc->bgColor2.';">
					<td nowrap="nowrap" colspan="2">
						'.$recordIcon.'
						'.$viewPageIcon.'
						</a>
						'.htmlspecialchars($this->currentPageRecord['title']).'
					</td>
				</tr>
				<tr>
					<td style="width:80%;">
					'.htmlspecialchars(sprintf ($LANG->getLL ('cannotedit_doktypemountpoint'), $mountSourcePageRecord['title'])).'<br /><br />
					<strong><a href="index.php?id='.$this->currentPageRecord['mount_pid'].'">'.htmlspecialchars($LANG->getLL ('jumptomountsourcepage')).'</a></strong>
					</td>
					<td>&nbsp;</td>
				</tr>
			</table>
		';
		return $content;
	}





	/*******************************************
	 *
	 * Framework rendering functions
	 *
	 *******************************************/

	/**
	 * @param	array		$dsInfo: DataStructure info array (the whole tree)
	 * @param	string		$parentPos: Pointer to parent element: table:id:sheet:structure language:fieldname:value language:counter (position in list)
	 * @param	boolean		$clipboardElInPath: Tells whether any element registered on the clipboard is found in the current "path" of the recursion. If true, it normally means that no paste-in symbols are shown since elements are not allowed to be pasted/referenced to a position within themselves (would result in recursion).
	 * @param	boolean		$referenceInPath: Is set to the number of references there has been in previous recursions of this function
	 * @return	string		HTML
	 */
	function renderFrameWork_allSheets($dsInfo, $parentPos='', $clipboardElInPath=0, $referenceInPath=0) {

			// If more than one sheet is available, render a dynamic sheet tab menu, otherwise just render the single sheet framework
		if (is_array($dsInfo['sub']) && (count($dsInfo['sub'])>1 || !isset($dsInfo['sub']['sDEF'])))	{
			$parts = array();
			foreach($dsInfo['sub'] as $sheetKey => $sheetInfo)	{

				$this->containedElementsPointer++;
				$this->containedElements[$this->containedElementsPointer] = 0;
				$frContent = $this->renderFrameWork($dsInfo, $parentPos, $clipboardElInPath, $referenceInPath, $sheetKey);

				$parts[] = array(
					'label' => ($dsInfo['meta'][$sheetKey]['title'] ? $dsInfo['meta'][$sheetKey]['title'] : $sheetKey).' ['.$this->containedElements[$this->containedElementsPointer].']',
					'description' => $dsInfo['meta'][$sheetKey]['description'],
					'linkTitle' => $dsInfo['meta'][$sheetKey]['short'],
					'content' => $frContent,
				);

				$this->containedElementsPointer--;
			}
			return $this->doc->getDynTabMenu($parts,'TEMPLAVOILA:pagemodule:'.$parentPos);
		} else {
			return $this->renderFrameWork($dsInfo, $parentPos, $clipboardElInPath, $referenceInPath, 'sDEF');
		}
	}

	/**
	 * Renders the display framework.
	 * Calls itself recursively
	 *
	 * @param	array		$dsInfo: DataStructure info array (the whole tree)
	 * @param	string		$parentPos: Pointer to parent element: table:id:sheet:structure language:fieldname:value language:counter (position in list)
	 * @param	boolean		$clipboardElInPath: Tells whether any element registered on the clipboard is found in the current "path" of the recursion. If true, it normally means that no paste-in symbols are shown since elements are not allowed to be pasted/referenced to a position within themselves (would result in recursion).
	 * @param	boolean		$referenceInPath: Is set to the number of references there has been in previous recursions of this function
	 * @param	string		$sheet: The sheet key of the sheet which should be rendered
	 * @return	string		HTML
	 */
	function renderFrameWork($dsInfo, $parentPos, $clipboardElInPath, $referenceInPath, $sheet) {
		global $LANG, $TYPO3_CONF_VARS, $TCA;

			// First prepare user defined objects (if any) for hooks which extend this function:
		$hookObjectsArr = array();
		if (is_array ($TYPO3_CONF_VARS['EXTCONF']['templavoila']['mod1']['renderFrameworkClass'])) {
			foreach ($TYPO3_CONF_VARS['EXTCONF']['templavoila']['mod1']['renderFrameworkClass'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj ($classRef);
			}
		}

		if (!is_array($this->currentDataStructureArr[$dsInfo['el']['table']]))		{
			$this->currentDataStructureArr[$dsInfo['el']['table']] = array();
		}

			// Take care of the currently selected language, for both concepts - with langChildren enabled and disabled
		$langDisable = intval($this->currentDataStructureArr[$dsInfo['el']['table']]['meta']['langDisable']);

		$langChildren = intval ($this->currentDataStructureArr[$dsInfo['el']['table']]['meta']['langChildren']);
		$lKey = $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l'.$this->currentLanguageKey);
		$vKey = $langDisable ? 'vDEF' : ($langChildren ? 'v'.$this->currentLanguageKey : 'vDEF');

			// The $isLocal flag is used to denote whether an element belongs to the current page or not. If NOT the $isLocal flag means (for instance) that the title bar will be colored differently to show users that this is a foreign element not from this page.
		$isLocal = $dsInfo['el']['table']=='pages' || $dsInfo['el']['pid']==$this->topPagePid;	// Pages have the local style
		if (!$isLocal) { $referenceInPath++; }

			// Set whether the current element is registered for copy/cut/reference or not:
		$clipActive_copy = ($this->MOD_SETTINGS['clip']=='copy' && $this->MOD_SETTINGS['clip_parentPos']==$parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id'].'/'.$isLocal ? '_h' : '');
		$clipActive_cut = ($this->MOD_SETTINGS['clip']=='cut' && $this->MOD_SETTINGS['clip_parentPos']==$parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id'].'/'.$isLocal ? '_h' : '');
		$clipActive_ref = ($this->MOD_SETTINGS['clip']=='ref' && $this->MOD_SETTINGS['clip_parentPos']==$parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id'].'/'.$isLocal ? '_h' : '');
		$clipboardElInPath = trim($clipActive_copy.$clipActive_cut.$clipActive_ref)||$clipboardElInPath?1:0;

			// Set additional information to the title-attribute of the element icon:
		$extPath = '';
		if (!$isLocal)	{
			$extPath = ' - '.$LANG->getLL('path').': '.t3lib_BEfunc::getRecordPath($dsInfo['el']['pid'],$this->perms_clause,30);
		}

		$rulesStatus = $this->checkRulesForElement($dsInfo['el']['table'], $dsInfo['el']['id']);
		if (is_array($rulesStatus['error'])) {
			foreach ($rulesStatus['error'] as $errorElement) {
				$uid = is_null ($errorElement['uid']) ? 'NULL' : $errorElement['uid'];
				$this->elementBlacklist[$uid][$errorElement['fieldname']][] = array (
					'position' => $errorElement['position'],
					'message' => $errorElement['message']
				);
			}
		}

			// Evaluating the rules and set colors to warning scheme if a rule does not apply
		$elementBackgroundStyle = '';
		$elementPageTitlebarColor = isset ($this->currentDataStructureArr['pages']['ROOT']['tx_templavoila']['pageModule']['titleBarColor']) ? $this->currentDataStructureArr['pages']['ROOT']['tx_templavoila']['pageModule']['titleBarColor'] : $this->doc->bgColor2;
		$elementPageTitlebarStyle = 'background-color: '.($dsInfo['el']['table']=='pages' ? $elementPageTitlebarColor : ($isLocal ? $this->doc->bgColor5 : $this->doc->bgColor6)) .';';
		$elementCETitlebarStyle = 'background-color: '.$this->doc->bgColor4.';';
		$headerCellStyle = 'background-color: '.$this->doc->bgColor4.';';
		$cellStyle = 'border: 1px dashed #666666;';

		$errorLineBefore = $errorLineWihtin = $errorLineAfter = $errorLineCell = '';

		$parentPosArr = explode (':',$parentPos);
		$currentFieldName = $parentPosArr[4];

		if (is_array ($this->elementBlacklist[$dsInfo['el']['id']][$currentFieldName])) {
			foreach ($this->elementBlacklist[$dsInfo['el']['id']][$currentFieldName] as $tmpStatusArr) {
				switch ($tmpStatusArr['position']) {
					case 1:
						$elementBackgroundStyle = 'background-color: '. ($GLOBALS['TBE_STYLES']['templavoila_bgColorWarning'] ? $GLOBALS['TBE_STYLES']['templavoila_bgColorWarning']:'#f4b0b0') .';';
						$elementPageTitlebarStyle = 'background-color: '. ($GLOBALS['TBE_STYLES']['templavoila_bgColorPageTitleWarning'] ? $GLOBALS['TBE_STYLES']['templavoila_bgColorPageTitleWarning']:'#ef4545') .';';
						$elementCETitlebarStyle = 'background-color: '. ($GLOBALS['TBE_STYLES']['templavoila_bgColorCETitleWarning'] ? $GLOBALS['TBE_STYLES']['templavoila_bgColorCETitleWarning']:'#ef7c7c') .';';
						$errorLineWithin .= $this->doc->rfw($tmpStatusArr['message']).'<br />';
					break;
					case -1:
						$errorLineBefore .= $this->doc->rfw($tmpStatusArr['message']).'<br />';
					break;
					case 2:
						$errorLineAfter .= $this->doc->rfw($tmpStatusArr['message']).'<br />';
					default:
				}
			}
		}

			// Creating the rule compliance icon, $rulesStatus has been evaluated earlier
		if (!is_null($rulesStatus['ok'])) {
			$title = ($rulesStatus['ok'] === true ? $LANG->getLL ('ruleapplies') : $LANG->getLL ('rulefails'));
			$ruleIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,t3lib_extMgm::extRelPath('templavoila').'mod1/'.($rulesStatus['ok'] === true ? 'green':'red').'led.gif','').' title="'.$title.'" border="0" alt="" style="text-align: center; vertical-align: middle;" />&nbsp;';
		}

			// Traversing the content areas ("zones" - those shown side-by-side with dotted lines in module) of the current element.
			// This part will be skipped, if we're already on the element level, ie. it will be only active if we currently parse an element with sub-elements
		$cells=array();
		$headerCells=array();
		$metaInfoAreaArr = array();

		if (is_array($dsInfo['sub'][$sheet]))	{
			foreach($dsInfo['sub'][$sheet] as $fieldID => $fieldContent)	{
				$counter=0;

					// Only show fields and values of a flexible content element, if either the currently selected language is the DEF language, or the langDisable flag of the FCE's data structure is not set
				if (!$fieldContent['meta']['langDisable'] || $this->currentLanguageKey == 'DEF') {

						// "New" and "Paste" icon:
					$elList = '';
					$elList.=$this->linkNew('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/new_el.gif','').' style="text-align: center; vertical-align: middle;" vspace="5" border="0" title="'.$LANG->getLL ('createnewrecord').'" alt="" />',$dsInfo['el']['table'].':'.$dsInfo['el']['id'].':'.$sheet.':'.$lKey.':'.$fieldID.':'.$vKey.':'.$counter);
					if (!$clipboardElInPath) { $elList.=$this->linkPaste('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/clip_pasteafter.gif','').' style="text-align: center; vertical-align: middle;" vspace="5" border="0" title="'.$LANG->getLL ('pasterecord').'" alt="" />',$this->MOD_SETTINGS['clip_parentPos'], $dsInfo['el']['table'].':'.$dsInfo['el']['id'].':'.$sheet.':'.$lKey.':'.$fieldID.':'.$vKey.':'.$counter, $this->MOD_SETTINGS['clip']); }

						// Render the list of elements (and possibly call itself recursively if needed):
					if (is_array($fieldContent['el_list']))	 {
						foreach($fieldContent['el_list'] as $counter => $k)	{
							$v = $fieldContent['el'][$k];
							$this->containedElements[$this->containedElementsPointer]++;
							$elList.=$this->renderFrameWork_allSheets($v,$dsInfo['el']['table'].':'.$dsInfo['el']['id'].':'.$sheet.':'.$lKey.':'.$fieldID.':'.$vKey.':'.$counter,$clipboardElInPath,$referenceInPath);

								// "New" and "Paste" icon:
							$elList.=$this->linkNew('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/new_el.gif','').' style="text-align: center; vertical-align: middle;" vspace="5" border="0" title="'.$LANG->getLL ('createnewrecord').'" alt="" />',$dsInfo['el']['table'].':'.$dsInfo['el']['id'].':'.$sheet.':'.$lKey.':'.$fieldID.':'.$vKey.':'.$counter);
							if (!$clipboardElInPath)	$elList.=$this->linkPaste('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/clip_pasteafter.gif','').' style="text-align: center; vertical-align: middle;" vspace="5" border="0" title="'.$LANG->getLL ('pasterecord').'" alt="" />',$this->MOD_SETTINGS['clip_parentPos'], $dsInfo['el']['table'].':'.$dsInfo['el']['id'].':'.$sheet.':'.$lKey.':'.$fieldID.':'.$vKey.':'.$counter,$this->MOD_SETTINGS['clip']);
						}
					}

						// Check if rules-related errors must be displayed for this field at the very beginning
					if (is_array ($this->elementBlacklist['NULL'][$fieldID])) {
						foreach ($this->elementBlacklist['NULL'][$fieldID] as $tmpStatusArr) {
							$headerCellStyle = 'background-color: '. ($GLOBALS['TBE_STYLES']['templavoila_bgColorCETitleWarning'] ? $GLOBALS['TBE_STYLES']['templavoila_bgColorCETitleWarning']:'#ef7c7c') .';';
							$cellStyle = 'border: 1px dashed red; ';
							$errorLineCell .= $this->doc->rfw($tmpStatusArr['message']).'<br />';
						}
					}
						// Add cell content to registers:
					$headerCells[]='<td valign="top" width="'.round(100/count($dsInfo['sub'][$sheet])).'%" style="'.$headerCellStyle.' padding-top:0; padding-bottom:0;">'.$LANG->sL($fieldContent['meta']['title'],1).'</td>';
					$cells[]='<td valign="top" width="'.round(100/count($dsInfo['sub'][$sheet])).'%" style="'.$cellStyle.' padding: 5px 5px 5px 5px;">'.$errorLineCell.$elList.'</td>';
				} else {
					$cells[]='
						<td valign="top" width="'.round(100/count($dsInfo['sub'][$sheet])).'%" style="'.$cellStyle.' padding: 5px 5px 5px 5px;">
							<em>'.htmlspecialchars ($LANG->getLL ('willbereplacedbydefaultlanguage')).'</em>
						</td>
					';
				}
			}
		}

			// Compile preview content for the current element:
		$content = is_array($dsInfo['el']['previewContent']) ? implode('<br />', $dsInfo['el']['previewContent']) : '';

			// Add language icon (which will be displayed later on):
		$langLabel = htmlspecialchars ($this->allAvailableLanguages[$dsInfo['el']['sys_language_uid']]['title']);
		$langIcon = $this->allAvailableLanguages[$dsInfo['el']['sys_language_uid']]['flagIcon'] ?
					'<img src="'.$this->allAvailableLanguages[$dsInfo['el']['sys_language_uid']]['flagIcon'].'" title="'.$langLabel.'" alt="'.$langLabel.'" style="text-align: center; vertical-align: middle;" />' :
					($langLabel && $dsInfo['el']['sys_language_uid'] ? '['.$langLabel.']' : '');

			// Compile the content areas for the current element (basically what was put together above):
		if (count ($headerCells) || count ($cells)) {
			$content .= '
				<table border="0" cellpadding="2" cellspacing="2" width="100%">
					<tr>'.(count($headerCells) ? implode('',$headerCells) : '<td>&nbsp;</td>').'</tr>
					<tr>'.(count($cells) ? implode('',$cells) : '<td>&nbsp;</td>').'</tr>
				</table>
			';
		}

			// Put together the records icon including content sensitive menu link wrapped around it:
		$recordIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,$dsInfo['el']['icon'],'').' style="text-align: center; vertical-align: middle;" width="18" height="16" border="0" title="'.htmlspecialchars('['.$dsInfo['el']['table'].':'.$dsInfo['el']['id'].']'.$extPath).'" alt="" />';
		$recordIcon = $this->doc->wrapClickMenuOnIcon($recordIcon,$dsInfo['el']['table'],$dsInfo['el']['id'],1,'&callingScriptId='.rawurlencode($this->doc->scriptID), 'new,copy,cut,pasteinto,pasteafter,delete');

#		$realDelete = $isLocal;	// content elements that are NOT references from other pages will be deleted when unlinked
		$realDelete = FALSE;	// Eventually it seems that deleting content elements is not a good long term idea. Therefore, regardless of situation, we ALWAYS unlink - unused Content Elements can be cleaned up by some other tool some other day.

		$linkCustom = $linkCopy = $linkCut = $linkRef = $linkUnlink = $linkMakeLocal = $titleBarTDParams = $contentWrapPre = $contentWrapPost = '';

		if ($dsInfo['el']['table']!='tt_content')	{

			$viewPageIcon = '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($dsInfo['el']['table']=='pages'?$dsInfo['el']['id']:$dsInfo['el']['pid'],$this->doc->backPath,t3lib_BEfunc::BEgetRootLine($dsInfo['el']['id']),'','',($this->currentLanguageUid?'&L='.$this->currentLanguageUid:''))).'">'.
				'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/zoom.gif','width="12" height="12"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.showPage',1).'" hspace="3" alt="" style="text-align: center; vertical-align: middle;" />'.
				'</a>';
			if ($this->sideBarPosition == 'toprows' || $this->sideBarPosition == 'toptabs') {
				$contentWrapPre .= $this->sideBarObj->render($dsInfo);
			}

		} else {
			$linkMakeLocal = (!$isLocal && $referenceInPath<=1) ? $this->linkMakeLocal('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,t3lib_extMgm::extRelPath('templavoila').'mod1/makelocalcopy.gif','').' title="'.$LANG->getLL('makeLocal').'" border="0" alt="" />', $parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id'].'/'.$isLocal.'/'.$this->id) : '';
			$linkCopy = $isLocal ? $this->linkCopyCut('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/clip_copy'.$clipActive_copy.'.gif','').' title="'.$LANG->getLL ('copyrecord').'" border="0" alt="" />',($clipActive_copy ? '' : $parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id'].'/'.$isLocal),'copy') : '';
			$linkCut = $this->linkCopyCut('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/clip_cut'.$clipActive_cut.'.gif','').' title="'.$LANG->getLL ('cutrecord').'" border="0" alt="" />',($clipActive_cut ? '' : $parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id'].'/'.$isLocal),'cut');
			$linkRef = $this->linkCopyCut('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,t3lib_extMgm::extRelPath('templavoila').'mod1/clip_ref'.$clipActive_ref.'.gif','').' title="'.$LANG->getLL ('createreference').'" border="0" alt="" />',($clipActive_ref ? '' : $parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id'].'/'.$isLocal),'ref');
   			$linkUnlink = $this->linkUnlink('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/garbage.gif','').' title="'.$LANG->getLL($realDelete ? 'deleteRecord' : 'unlinkRecord').'" border="0" alt="" />', $parentPos.'/'.$dsInfo['el']['table'].':'.$dsInfo['el']['id'].'/'.$isLocal, $realDelete);

			if (is_array ($dsInfo['sub']) && $this->altRoot['uid'] != $dsInfo['el']['id']) {
				$viewSubElementsUrl = 'index.php?id='.$this->id.
					'&altRoot[table]='.rawurlencode($dsInfo['el']['table']).
					'&altRoot[uid]='.$dsInfo['el']['id'].
					'&altRoot[field_flex]=tx_templavoila_flex';
				$titleBarTDParams .= ' onClick="jumpToUrl(\''.$viewSubElementsUrl.'\');"';
				$titleBarTDParams .= ' style="cursor:pointer;cursor:hand" title="'.htmlspecialchars($LANG->getLL ('viewsubelements')).'"';
			}
		}

			// Create informational table about localized versions of elements
		$llTable = $this->renderLocalizationInfoTable ($dsInfo);

			// Hook: renderFrameWork_preProcessOutput
		reset($hookObjectsArr);
		while (list(,$hookObj) = each($hookObjectsArr)) {
			if (method_exists ($hookObj, 'renderFrameWork_preProcessOutput')) {
				$hookObj->renderFrameWork_preProcessOutput ($dsInfo, $sheet, $content, $isLocal, $linkCustom, $this);
			}

		}

		$finalContent =
			($errorLineBefore ? '<br />'.$errorLineBefore : ''). '
		<table border="0" cellpadding="0" cellspacing="0" style="border: 1px solid black; margin-bottom:5px; '.$elementBackgroundStyle.'" width="100%">
			<tr style="'.$elementPageTitlebarStyle.';">
				<td nowrap="nowrap">'.$ruleIcon.$langIcon.$recordIcon.$viewPageIcon.'</td><td width="95%" '.$titleBarTDParams.'>'.($isLocal?'':'<em>').htmlspecialchars($dsInfo['el']['title']).($isLocal?'':'</em>'). '</td>
				<td nowrap="nowrap" align="right" valign="top">'.
					$linkCustom.
					$linkMakeLocal.
					$linkCopy.
					$linkCut.
					$linkRef.
					$linkUnlink.
					($isLocal ? $this->linkEdit('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','').' title="'.$LANG->getLL ('editrecord').'" border="0" alt="" />',$dsInfo['el']['table'],$dsInfo['el']['id']) : '').
				'</td>
			</tr>
			<tr>
				<td colspan="3">'.
					$contentWrapPre.
					$content.
					($errorLineWithin ? '<br />'.$errorLineWithin : '').
					$llTable.
					$contentWrapPost.
				'</td>
			</tr>
		</table>
		'.$errorLineAfter.'
		';

		return $finalContent;
	}





	/*******************************************
	 *
	 * Rendering functions for certain subparts
	 *
	 *******************************************/

	/**
	 * Returns an HTMLized preview of a certain content element. If you'd like to register a new content type, you can easily use the hook
	 * provided at the beginning of the function.
	 *
	 * @param	array		$row: The row containing the content element record; especially $row['CType'] and $row['bodytext'] are used.
	 * @param	string		$table: Name of the CType's DB table
	 * @return	string		HTML preview content
	 */
	function renderPreviewContent ($row, $table) {
		global $TYPO3_CONF_VARS, $LANG;

			// First prepare user defined objects (if any) for hooks which extend this function:
		$hookObjectsArr = array();
		if (is_array ($TYPO3_CONF_VARS['EXTCONF']['templavoila']['mod1']['renderPreviewContentClass'])) {
			foreach ($TYPO3_CONF_VARS['EXTCONF']['templavoila']['mod1']['renderPreviewContentClass'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj ($classRef);
			}
		}

		$alreadyRendered = false;
		$out = '';

			// Hook: renderPreviewContent_preProcess. Set 'alreadyRendered' to true if you provided a preview content for the current cType !
		reset($hookObjectsArr);
		while (list(,$hookObj) = each($hookObjectsArr)) {
			if (method_exists ($hookObj, 'renderPreviewContent_preProcess')) {
				$out .= $hookObj->renderPreviewContent_preProcess ($row, $table, $alreadyRendered, $this);
			}
		}

		if (!$alreadyRendered) {
				// Preview content for non-flexible content elements:
			switch($row['CType'])	{
				case 'text':		//	Text
				case 'table':		//	Table
				case 'mailform':	//	Form
					$out = $this->linkEdit('<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','bodytext'),1).'</strong> '.htmlspecialchars(t3lib_div::fixed_lgd(trim(strip_tags($row['bodytext'])),200)),$table,$row['uid']);
					break;
				case 'image':		//	Image
					$out = $this->linkEdit('<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','image'),1).'</strong><br /> ', $table, $row['uid']).t3lib_BEfunc::thumbCode ($row, $table, 'image', $this->doc->backPath, '', $v['TCEforms']['config']['uploadfolder']);
					break;
				case 'textpic':		//	Text w/image
				case 'splash':		//	Textbox
					$thumbnail = '<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','image'),1).'</strong><br />';
					$thumbnail .= t3lib_BEfunc::thumbCode ($row, $table, 'image', $this->doc->backPath, '', $v['TCEforms']['config']['uploadfolder']);
					$text = $this->linkEdit('<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','bodytext'),1).'</strong> '.htmlspecialchars(t3lib_div::fixed_lgd(trim(strip_tags($row['bodytext'])),200)),$table,$row['uid']);
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
					$out = $this->linkEdit('<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','bodytext'),1).'</strong><br />'.$htmlBullets, $table, $row['uid']);
					break;
				case 'uploads':		//	Filelinks
					$out = $this->linkEdit('<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','media'),1).'</strong><br />'.str_replace (',','<br />',htmlspecialchars(t3lib_div::fixed_lgd(trim(strip_tags($row['media'])),200))), $table, $row['uid']);
					break;
				case 'multimedia':	//	Multimedia
					$out = $this->linkEdit ('<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','multimedia'),1).'</strong><br />' . str_replace (',','<br />',htmlspecialchars(t3lib_div::fixed_lgd(trim(strip_tags($row['multimedia'])),200))), $table, $row['uid']);
					break;
				case 'menu':		//	Menu / Sitemap
					$out = $this->linkEdit ('<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','menu_type')).'</strong> '.$LANG->sL(t3lib_BEfunc::getLabelFromItemlist('tt_content','menu_type',$row['menu_type'])).'<br />'.
						'<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','pages')).'</strong> '.$row['pages'], $table, $row['uid']);
					break;
				case 'list':		//	Insert Plugin
					$out = $this->linkEdit('<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','list_type')).'</strong> ' . htmlspecialchars($LANG->sL(t3lib_BEfunc::getLabelFromItemlist('tt_content','menu_type',$row['list_type'])).' '.$row['list_type']), $table, $row['uid']);
					break;
				case 'html':		//	HTML
					$out = $this->linkEdit ('<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','bodytext'),1).'</strong> ' . htmlspecialchars(t3lib_div::fixed_lgd(trim($row['bodytext']),200)),$table,$row['uid']);
					break;
				case 'search':		//	Search Box
				case 'login':		//	Login Box
				case 'shortcut':	//	Insert records
				case 'div':			//	Divider
					break;
				default:
						// return CType name for unhandled CType
					$out='<strong>'.htmlspecialchars ($row['CType']).'</strong>';
			}
		}
		return $out;
	}

	/**
	 * Renders a little table containing previews of translated version of the current content element.
	 *
	 * @param	array		$dsInfo: The current dsInfo array
	 * @return	string		HTML
	 * @see renderFramwork()
	 * @todo	The localize element should be inserted automatically into the flexform field of the foreign language!
	 */
	function renderLocalizationInfoTable($dsInfo) {

				// LOCALIZATION information for content elements (non Flexible Content Elements)
		$llTable = '';
		if ($this->modTSconfig['properties']['enableCElocalizationInfo'] && $dsInfo['el']['table']=='tt_content' && $dsInfo['el']['sys_language_uid']<=0)	{

				// Finding translations of this record:
				// Select overlay record:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'*',
						'tt_content',
						'pid='.intval($dsInfo['el']['pid']).
							' AND sys_language_uid>0'.
							' AND l18n_parent='.intval($dsInfo['el']['id']).
							t3lib_BEfunc::deleteClause('tt_content')
					);
			$attachedLocalizations = array();
			while($olrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				if (!isset($attachedLocalizations[$olrow['sys_language_uid']]))	{
					$attachedLocalizations[$olrow['sys_language_uid']] = array(
						'uid' => $olrow['uid'],
						'row' => $olrow,
						'content' => $this->renderPreviewContent($olrow, 'tt_content')
					);
				}
			}

				// Traverse the available languages of the page (not default and [All])
			$tRows=array();
			foreach($this->translatedLanguagesArr as $sys_language_uid => $sLInfo)	{
				if ($sys_language_uid > 0)	{
					if (is_array($attachedLocalizations[$sys_language_uid]))	{
						#$lC = $this->linkEdit('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','').' title="'.$LANG->getLL ('editrecord').'" border="0" alt="" />','tt_content',$attachedLocalizations[$sys_language_uid]['uid']);

							// Put together the records icon including content sensitive menu link wrapped around it:
						$recordIcon_l10n = t3lib_iconWorks::getIconImage('tt_content',$attachedLocalizations[$sys_language_uid]['row'],$this->doc->backPath,'class="absmiddle"');
						$recordIcon_l10n = $this->doc->wrapClickMenuOnIcon($recordIcon_l10n,'tt_content',$attachedLocalizations[$sys_language_uid]['uid'],1,'&callingScriptId='.rawurlencode($this->doc->scriptID), 'new,copy,cut,pasteinto,pasteafter');
						$lC = $recordIcon_l10n.t3lib_BEfunc::getRecordTitle('tt_content',$attachedLocalizations[$sys_language_uid]['row']);

						$lC.= '<br/>'.$attachedLocalizations[$sys_language_uid]['content'];
					} elseif ($dsInfo['el']['CType']!='templavoila_pi1') {	// Dont localize Flexible Content Elements (might change policy on this or make it configurable if any would rather like to localize FCEs by duplicate records instead of internally in the FlexForm XML)
							// Copy for language:
						$params='&cmd[tt_content]['.$dsInfo['el']['id'].'][localize]='.$sys_language_uid;
						$onClick = "document.location='".$GLOBALS['SOBE']->doc->issueCommand($params)."'; return false;";

						$lC = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/clip_copy.gif','width="12" height="12"').' class="bottom" title="Create localization copy ('.htmlspecialchars($sLInfo['title']).')" alt="" />';
						$lC = '<a href="#" onclick="'.htmlspecialchars($onClick).'">'.$lC.'</a> <b style="color:red;">(!)</b>';
					}

					if ($lC)	{
						$tRows[]='
							<tr class="bgColor4">
								<td width="1%">'.($sLInfo['flagIcon'] ? '<img src="'.$sLInfo['flagIcon'].'" alt="'.htmlspecialchars($sLInfo['title']).'" title="'.htmlspecialchars($sLInfo['title']).'" />' : $sLInfo['title']).'</td>
								<td width="99%">'.$lC.'</td>
							</tr>';
					}
				}
			}

			$llTable = count($tRows) ? '
				<table border="0" cellpadding="0" cellspacing="1" width="100%" class="lrPadding">
					<tr class="bgColor4-20">
						<td colspan="2">Localizations:</td>
					</tr>
					'.implode('',$tRows).'
				</table>
			' : '';
		}
		return $llTable;
	}





	/*******************************************
	 *
	 * Link functions
	 *
	 *******************************************/

	/**
	 * Returns an HTML link for editing
	 *
	 * @param	string		$str: The label (or image)
	 * @param	string		$table: The table, fx. 'tt_content'
	 * @param	integer		$uid: The uid of the element to be edited
	 * @return	string		HTML anchor tag containing the label and the correct link
	 */
	function linkEdit($str, $table, $uid)	{
		$onClick = t3lib_BEfunc::editOnClick('&edit['.$table.']['.$uid.']=edit',$this->doc->backPath);
		return '<a style="text-decoration: none;" href="#" onclick="'.htmlspecialchars($onClick).'">'.$str.'</a>';
	}

	/**
	 * Returns an HTML link for creating a new record
	 *
	 * @param	string		$str: The label (or image)
	 * @param	string		$parentRecord: The parameters for creating the new record. Example: pages:78:sDEF:lDEF:field_contentarea:vDEF:0
	 * @return	string		HTML anchor tag containing the label and the correct link
	 */
	function linkNew($str, $parentRecord)	{
		return '<a href="'.htmlspecialchars('db_new_content_el.php?'.$this->linkParams().'&parentRecord='.rawurlencode($parentRecord)).'">'.$str.'</a>';
	}

	/**
	 * Returns an HTML link for unlinking a content element. Unlinking means that the record still exists but
	 * is not connected to any other content element or page.
	 *
	 * @param	string		$str: The label
	 * @param	string		$unlinkRecord: The parameters for unlinking the record. Example: pages:78:sDEF:lDEF:field_contentarea:vDEF:0
	 * @param	[type]		$realDelete: ...
	 * @return	string		HTML anchor tag containing the label and the unlink-link
	 */
	function linkUnlink($str, $unlinkRecord, $realDelete=FALSE)	{
		global $LANG;

		if ($realDelete)	{
			return '<a href="index.php?'.$this->linkParams().'&deleteRecord='.rawurlencode($unlinkRecord).'" onclick="'.htmlspecialchars('return confirm('.$LANG->JScharCode($LANG->getLL('deleteRecordMsg')).');').'">'.$str.'</a>';
		} else {
			return '<a href="index.php?'.$this->linkParams().'&unlinkRecord='.rawurlencode($unlinkRecord).'">'.$str.'</a>';
		}
	}

	/**
	 * Returns an HTML link for making a reference content element local to the page (copying it).
	 *
	 * @param	string		$str: The label
	 * @param	string		$unlinkRecord: The parameters for unlinking the record. Example: pages:78:sDEF:lDEF:field_contentarea:vDEF:0
	 * @return	string		HTML anchor tag containing the label and the unlink-link
	 */
	function linkMakeLocal($str, $makeLocalRecord)	{
		global $LANG;

		return '<a href="index.php?'.$this->linkParams().'&makeLocalRecord='.rawurlencode($makeLocalRecord).'" onclick="'.htmlspecialchars('return confirm('.$LANG->JScharCode($LANG->getLL('makeLocalMsg')).');').'">'.$str.'</a>';
	}

	/**
	 * Returns an HTML link for pasting a content element. Pasting means either copying or moving a record.
	 *
	 * @param	string		$str: The label
	 * @param	string		$params: The parameters defining the original record. Example: pages:78:sDEF:lDEF:field_contentarea:vDEF:0
	 * @param	string		$destination: The parameters defining the target where to paste the original record
	 * @param	string		$cmd: The paste mode, usually set in the clipboard: 'cut' or 'copy'
	 * @return	string		HTML anchor tag containing the label and the paste-link
	 */
	function linkPaste($str, $source, $destination, $cmd)	{
		return '<a href="index.php?'.$this->linkParams().'&SET[clip]=&SET[clip_parentPos]=&pasteRecord='.$cmd.'&source='.rawurlencode($source).'&destination='.rawurlencode($destination).'">'.$str.'</a>';
	}

	/**
	 * Returns an HTML link for marking a content element (i.e. transferring to the clipboard) for copying or cutting.
	 *
	 * @param	string		$str: The label
	 * @param	string		$source: The parameters defining the original record. Example: pages:78:sDEF:lDEF:field_contentarea:vDEF::tt_content:115
	 * @param	string		$cmd: The marking mode: 'cut' or 'copy'
	 * @return	string		HTML anchor tag containing the label and the cut/copy link
	 */
	function linkCopyCut($str, $source, $cmd)	{
		return '<a href="index.php?'.$this->linkParams().'&SET[clip]='.($source?$cmd:'').'&SET[clip_parentPos]='.rawurlencode($source).'">'.$str.'</a>';
	}

	/**
	 * Creates additional parameters which are used for linking to the current page while editing it
	 *
	 * @return	string		parameters
	 */
	function linkParams()	{
		$output =
			'id='.$this->id.
			(is_array($this->altRoot) ? t3lib_div::implodeArrayForUrl('altRoot',$this->altRoot) : '') .
			($this->versionId ? '&versionId='.rawurlencode($this->versionId) : '');
		return $output;
	}





	/*******************************************
	 *
	 * Command functions
	 *
	 *******************************************/

	/**
	 * Initiates processing for creating a new record.
	 *
	 * @param	string		$parentRecord:
	 * @param	array		$defVals: Array containing default values for the new record, e.g. [tt_content][CType] = 'text'
	 * @return	void
	 * @see		insertRecord ()
	 */
	function cmd_createNewRecord ($parentRecord, $defVals='') {
			// Historically "defVals" has been used for submitting the row data. We still use it and use it for our new row:
		$defVals = (string)$defVals == '' ? t3lib_div::_GP('defVals') : $defVals;
		$row = $defVals['tt_content'];

			// Create new record and open it for editing
		$newUid = $this->insertRecord($parentRecord, $row);
		$location = $GLOBALS['BACK_PATH'].'alt_doc.php?edit[tt_content]['.$newUid.']=edit&returnUrl='.rawurlencode(t3lib_extMgm::extRelPath('templavoila').'mod1/index.php?'.$this->linkParams());
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
		header('Location: '.t3lib_div::locationHeaderUrl('index.php?'.$this->linkParams()));
	}

	/**
	 * Initiates processing for unlinking AND DELETING a record.
	 *
	 * @param	string		$unlinkRecord: The element to be unlinked.
	 * @return	void
	 * @see		pasteRecord ()
	 */
	function cmd_deleteRecord ($deleteRecord) {
		$this->pasteRecord('delete', $deleteRecord, '');
		header('Location: '.t3lib_div::locationHeaderUrl('index.php?'.$this->linkParams()));
	}

	/**
	 * Initiates processing for making a local copy of a record.
	 *
	 * @param	string		$unlinkRecord: The element to be copied to current page.
	 * @return	void
	 * @see		pasteRecord ()
	 */
	function cmd_makeLocalRecord ($makeLocalRecord) {
		$this->pasteRecord('localcopy', $makeLocalRecord, '');
		header('Location: '.t3lib_div::locationHeaderUrl('index.php?'.$this->linkParams()));
	}

	/**
	 * Initiates processing for pasting a record.
	 *
	 * @param	string		$pasteMode: "cut" or "copy"
	 * @return	void
	 * @see		pasteRecord ()
	 */
	function cmd_pasteRecord ($pasteMode) {
		$this->pasteRecord($pasteMode, t3lib_div::_GP('source'), t3lib_div::_GP('destination'));
		header('Location: '.t3lib_div::locationHeaderUrl('index.php?'.$this->linkParams()));
	}

	/**
	 * Initiates processing for creating a new page language overlay. The PID (uid of the page
	 * to be translated) must be passed by GET/POST!
	 *
	 * @param	integer		$languageUid: The uid of the language to be used for the new translation
	 * @return	void
	 */
	function cmd_createNewTranslation ($languageUid) {
			// Create parameters and finally run the classic page module for creating a new page translation
		$params = '&edit[pages_language_overlay]['.intval (t3lib_div::_GP('pid')).']=new&overrideVals[pages_language_overlay][sys_language_uid]='.intval($languageUid);
		$returnUrl = '&returnUrl='.rawurlencode(t3lib_extMgm::extRelPath('templavoila').'mod1/index.php?'.$this->linkParams());
		$location = $GLOBALS['BACK_PATH'].'alt_doc.php?'.$params.$returnUrl;

		header('Location: '.t3lib_div::locationHeaderUrl($location));
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
		$dataArr = array();
		$dataArr['pages']['NEW'] = $pageArray;
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

		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
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
	 * @todo				Check for rules compliance? (might not be necessary if we expect ruleDefaultElements to be valid), Use current Sheet / Language
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
						$CType = t3lib_div::trimExplode (',',$this->rulesObj->getCTypeFromToken (trim($field['tx_templavoila']['ruleDefaultElements'][$counter]), $field['tx_templavoila']['ruleConstants']));
						switch ($CType[0]) {
							case 'templavoila_pi1':
								$TOrow = t3lib_BEfunc::getRecord('tx_templavoila_tmplobj', $CType[1], 'datastructure');
								$conf = array (
									'CType' => $CType[0],
									'tx_templavoila_ds' => $TOrow['datastructure'],
									'tx_templavoila_to' => intval($CType[1]),
								);

								$ceUid = $this->insertRecord($table.':'.intval($uid).':sDEF:lDEF:'.$key.':vDEF',$conf);
								$this->createDefaultRecords ('tt_content', intval($ceUid), $tableRow['tx_templavoila_ds'], $level+1);
								break;
							case '':
								break;
							default:
								$conf = array (
									'CType' => $CType[0],
									'bodytext' => $LANG->getLL ('newce_defaulttext_'.$CType[0]),
								);
								$this->insertRecord($table.':'.intval($uid).':sDEF:lDEF:'.$key.':vDEF',$conf);
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
	 * @param	string		$destination: consists of several parts separated by colon. Example: pages:78:sDEF:lDEF:field_contentarea:vDEF:0
	 * @param	array		$row: Array of parameters for creating the new record.
	 * @return	integer		uid of the created content element (if any)
	 */
	function insertRecord($destination, $row)	{
		$handler = t3lib_div::makeInstance('tx_templavoila_xmlrelhndl');
		$handler->init($this->altRoot);

		return $handler->insertRecord($destination, $row);
	}

	/**
	 * Performs the processing part of pasting a record.
	 *
	 * @param	string		$pasteCmd: Kind of pasting: 'cut', 'copy' or 'unlink'
	 * @param	string		$source: String defining the original record. Example: pages:78:sDEF:lDEF:field_contentarea:vDEF:0
	 * @param	string		$destination: Defines the destination where to paste the record (not used when unlinking of course).
	 * @return	void		nothing
	 */
	function pasteRecord($pasteCmd, $source, $destination)	{
		$handler = t3lib_div::makeInstance('tx_templavoila_xmlrelhndl');
		$handler->init($this->altRoot);

		return $handler->pasteRecord($pasteCmd, $source, $destination);
	}





	/********************************************
	 *
	 * Structure functions
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
	function getDStreeForPage($table, $id, $prevRecList='', $row='')	{
		global $TCA, $LANG;

		$row = is_array($row) ? $row : t3lib_BEfunc::getRecord($table,$id);
		$tree=array();
		$tree['el']=array(
			'table' => $table,
			'id' => $id,
			'pid' => $row['pid'],
			'title' => t3lib_div::fixed_lgd(t3lib_BEfunc::getRecordTitle($table, $row),50),
			'icon' => t3lib_iconWorks::getIcon($table, $row),
			'sys_language_uid' => $row['sys_language_uid'],
			'l18n_parent' => $row['l18n_parent'],
			'CType' => $row['CType'],
		);

		if ($table=='pages' || $table==$this->altRoot['table'] || ($table=='tt_content' && $row['CType']=='templavoila_pi1'))	{
			$flexFName = $this->altRoot['table']==$table ? $this->altRoot['field_flex'] : 'tx_templavoila_flex';
			$expDS = $this->getExpandedDataStructure($table, $flexFName, $row);
			$xmlContent = t3lib_div::xml2array($row[$flexFName]);

			foreach($expDS as $sheetKey => $sVal)	{
				$tree['sub'][$sheetKey] = array();
				$tree['meta'][$sheetKey] = array(
					'title' => (is_array($sVal) && $sVal['ROOT']['TCEforms']['sheetTitle'] ? $GLOBALS['LANG']->sL($sVal['ROOT']['TCEforms']['sheetTitle']) : ''),
					'description' => (is_array($sVal) && $sVal['ROOT']['TCEforms']['sheetDescription'] ? $GLOBALS['LANG']->sL($sVal['ROOT']['TCEforms']['sheetDescription']) : ''),
					'short' => (is_array($sVal) && $sVal['ROOT']['TCEforms']['sheetShortDescr'] ? $GLOBALS['LANG']->sL($sVal['ROOT']['TCEforms']['sheetShortDescr']) : '')
				);

				if (is_array($sVal) && is_array($sVal['ROOT']['el']))	{
					foreach($sVal['ROOT']['el'] as $k => $v) {

							// Take care of the currently selected language, for both concepts - with langChildren enabled and disabled
						$langChildren = intval ($sVal['meta']['langChildren']);
						$lKey = $langChildren ? 'lDEF' : 'l'.$this->currentLanguageKey;
						$vKey = $langChildren ? 'v'.$this->currentLanguageKey : 'vDEF';

						if ($v['TCEforms']['config']['type']=='group' && $v['TCEforms']['config']['internal_type']=='db' && $v['TCEforms']['config']['allowed']=='tt_content')	{
							$tree['sub'][$sheetKey][$k]=array();
							$tree['sub'][$sheetKey][$k]['el']=array();
							$tree['sub'][$sheetKey][$k]['meta']=array(
								'title' => $v['TCEforms']['label'],
								'langDisable' => $sVal['meta']['langDisable'],
								'langChildren' => $sVal['meta']['langChildren'],
							);

							$dat = $xmlContent['data'][$sheetKey][$lKey][$k][$vKey];
							$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
							$dbAnalysis->start($dat,'tt_content');
							$elArray=array();

							foreach($dbAnalysis->itemArray as $counter => $recIdent)	{
								$this->global_tt_content_elementRegister[$recIdent['id']]++;
								$idStr=$recIdent['table'].':'.$recIdent['id'];

								$nextSubRecord = t3lib_BEfunc::getRecord($recIdent['table'],$recIdent['id']);
								if (is_array($nextSubRecord) && ($this->MOD_SETTINGS['tt_content_showHidden'] || ($TCA[$recIdent['table']]['ctrl']['enablecolumns']['disabled'] && !$nextSubRecord[$TCA[$recIdent['table']]['ctrl']['enablecolumns']['disabled']])))	{
									$idRow = $elArray[] = $nextSubRecord;
								} else {
									$idRow = $elArray[] = NULL;
								}


								if (!t3lib_div::inList($prevRecList,$idStr))	{
									if (is_array($idRow))	{
										$tree['sub'][$sheetKey][$k]['el'][$idStr] = $this->getDStreeForPage($recIdent['table'],$recIdent['id'],$prevRecList.','.$idStr,$idRow);
										$tree['sub'][$sheetKey][$k]['el'][$idStr]['el']['index'] = $counter+1;
										$tree['sub'][$sheetKey][$k]['el_list'][($counter+1)] = $idStr;
									} else {
										# ERROR: The element referenced was deleted!
									}
								} else {
									# ERROR: recursivity error!
								}
							}
#							$this->evaluateRuleOnElements($v['tx_templavoila']['ruleRegEx'],$v['tx_templavoila']['ruleConstants'],$elArray);
						} elseif (is_array($v['TCEforms'])) {
							if ($v['TCEforms']['config']['type']=='group' && $v['TCEforms']['config']['internal_type']=='file')	{
								$xmlContent['data'][$sheetKey][$lKey][$k][$vKey];
								$thumbnail = t3lib_BEfunc::thumbCode(array('fN'=>$xmlContent['data'][$sheetKey][$lKey][$k][$vKey]),'','fN',$this->doc->backPath,'',$v['TCEforms']['config']['uploadfolder']);
								$tree['el']['previewContent'][]='<strong>'.$LANG->sL($v['TCEforms']['label'],1).'</strong> '.$thumbnail;
							} else {
								$tree['el']['previewContent'][]='<strong>'.$LANG->sL($v['TCEforms']['label'],1).'</strong> '.$this->linkEdit(htmlspecialchars(t3lib_div::fixed_lgd(strip_tags($xmlContent['data'][$sheetKey][$lKey][$k][$vKey]),200)),$table,$row['uid']);
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
	 * Returns the data structure for a flexform field ($field) from $table (from $row)
	 *
	 * @param	string		The table name
	 * @param	string		The field name
	 * @param	array		The data row (used to get DS if DS is dependant on the data in the record)
	 * @return	array		The data structure, expanded for all sheets inside.
	 */
	function getExpandedDataStructure($table, $field, $row)	{
		global $TCA;

		t3lib_div::loadTCA ($table);
		$conf = $TCA[$table]['columns'][$field]['config'];
		$dataStructArray = t3lib_BEfunc::getFlexFormDS($conf, $row, $table);

			// Save the raw data structure for later usage
		$this->currentDataStructureArr[$table] = $dataStructArray;

		$output=array();
		if (is_array($dataStructArray['sheets']))	{
			foreach($dataStructArray['sheets'] as $sheetKey => $sheetInfo)	{
				list ($dataStruct, $sheet) = t3lib_div::resolveSheetDefInDS($dataStructArray, $sheetKey);
				if ($sheet == $sheetKey)	{
					$output[$sheetKey]=$dataStruct;
				}
			}
		} else {
			$sheetKey='sDEF';
			list ($dataStruct, $sheet) = t3lib_div::resolveSheetDefInDS($dataStructArray, $sheetKey);
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
		return $this->rulesObj->evaluateRulesForElement ($table, $uid);
	 }





	/********************************************
	 *
	 * Miscelleaneous functions
	 *
	 ********************************************/

	/**
	 * Returns an array of available languages (to use for FlexForms)
	 *
	 * @param	integer		Page id: If zero, the query will select all sys_language records from root level. If set to another value, the query will select all sys_language records that has a pages_language_overlay record on that page (and is not hidden, unless you are admin user)
	 * @param	boolean		If set, only languages which are paired with a static_info_table / static_language record will be returned.
	 * @param	boolean		If set, an array entry for a default language is set.
	 * @param	boolean		If set, an array entry for "multiple languages" is added (uid -1)
	 * @return	array
	 */
	function getAvailableLanguages($id=0, $onlyIsoCoded=true, $setDefault=true, $setMulti=false)	{
		global $LANG, $TYPO3_DB, $BE_USER, $TCA, $BACK_PATH;

		t3lib_div::loadTCA ('sys_language');
		$flagAbsPath = t3lib_div::getFileAbsFileName($TCA['sys_language']['columns']['flag']['config']['fileFolder']);
		$flagIconPath = $BACK_PATH.'../'.substr($flagAbsPath, strlen(PATH_site));

		$output = array();
		$excludeHidden = $BE_USER->isAdmin() ? '1=1' : 'sys_language.hidden=0';

		if ($id)	{
			$res = $TYPO3_DB->exec_SELECTquery(
							'sys_language.*',
							'pages_language_overlay,sys_language',
							'pages_language_overlay.sys_language_uid=sys_language.uid AND pages_language_overlay.pid='.intval($id).' AND '.$excludeHidden,
							'pages_language_overlay.sys_language_uid',
							'sys_language.title'
			);
		} else {
			$res = $TYPO3_DB->exec_SELECTquery(
							'sys_language.*',
							'sys_language',
							$excludeHidden,
							'',
							'sys_language.title'
			);
		}

		if ($setDefault) {
			$output[0]=array(
				'uid' => 0,
				'title' => strlen ($this->modSharedTSconfig['properties']['defaultLanguageFlag']) ? $this->modSharedTSconfig['properties']['defaultLanguageLabel'].' ('.$LANG->getLL ('defaultLanguage').')' : $LANG->getLL ('defaultLanguage'),
				'ISOcode' => 'DEF',
				'flagIcon' => strlen($this->modSharedTSconfig['properties']['defaultLanguageFlag']) && @is_file($flagAbsPath.$this->modSharedTSconfig['properties']['defaultLanguageFlag']) ? $flagIconPath.$this->modSharedTSconfig['properties']['defaultLanguageFlag'] : null,
			);
		}

		if ($setMulti) {
			$output[-1]=array(
				'uid' => -1,
				'title' => $LANG->getLL ('multipleLanguages'),
				'ISOcode' => 'DEF',
				'flagIcon' => $flagIconPath.'multi-language.gif',
			);
		}

		while($row = $TYPO3_DB->sql_fetch_assoc($res))	{
			$output[$row['uid']]=$row;

			if ($row['static_lang_isocode'])	{
				$staticLangRow = t3lib_BEfunc::getRecord('static_languages',$row['static_lang_isocode'],'lg_iso_2');
				if ($staticLangRow['lg_iso_2']) {
					$output[$row['uid']]['ISOcode'] = $staticLangRow['lg_iso_2'];
				}
			}
			if (strlen ($row['flag'])) {
				$output[$row['uid']]['flagIcon'] = @is_file($flagAbsPath.$row['flag']) ? $flagIconPath.$row['flag'] : '';
			}

			if ($onlyIsoCoded && !$output[$row['uid']]['ISOcode']) unset($output[$row['uid']]);
		}

		return $output;
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

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/index.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/index.php']);
}

	// Make instance:
$SOBE = t3lib_div::makeInstance('tx_templavoila_module1');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>