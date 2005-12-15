<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003, 2004, 2005 Robert Lemke (robert@typo3.org)
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
 * @coauthor   Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  116: class tx_templavoila_module1 extends t3lib_SCbase
 *
 *              SECTION: Initialization functions
 *  154:     function init()
 *  198:     function menuConfig()
 *
 *              SECTION: Main functions
 *  252:     function main()
 *  346:     function printContent()
 *
 *              SECTION: Rendering functions
 *  366:     function render_editPageScreen()
 *
 *              SECTION: Framework rendering functions
 *  439:     function render_framework_allSheets($contentTreeArr, $parentPointer=array())
 *  475:     function render_framework_singleSheet($contentTreeArr, $sheet, $parentPointer=array())
 *  571:     function render_framework_subElements ($elementContentTreeArr, $sheet)
 *
 *              SECTION: Rendering functions for certain subparts
 *  659:     function render_previewContent($row)
 *  741:     function render_localizationInfoTable($contentTreeArr, $parentPointer)
 *
 *              SECTION: Link functions (protected)
 *  847:     function link_edit($label, $table, $uid)
 *  864:     function link_new($label, $parentPointer)
 *  882:     function link_unlink($label, $unlinkPointer, $realDelete=FALSE)
 *  902:     function link_makeLocal($label, $makeLocalPointer)
 *  914:     function link_getParameters()
 *
 *              SECTION: Processing and structure functions (protected)
 *  942:     function handleIncomingCommands()
 * 1046:     function getContentTree($table, $row, $languageKey='DEF', $prevRecList='')
 * 1150:     function getContentTree_processSubContent($flexformContentArr, $tree, $sheetKey, $lKey, $fieldKey, $vKey, $fieldData, $languageKey, $prevRecList)
 * 1213:     function getContentTree_processSubElements($flexformContentArr, $tree, $sheetKey, $lKey, $fieldKey, $vKey, $fieldData, $row)
 * 1252:     function getContentTree_getLocalizationInfoForElement($contentTreeArr)
 *
 *              SECTION: Miscelleaneous helper functions (protected)
 * 1324:     function getAvailableLanguages($id=0, $onlyIsoCoded=true, $setDefault=true, $setMulti=false)
 * 1396:     function hooks_prepareObjectsArray ($hookName)
 *
 * TOTAL FUNCTIONS: 22
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

	// Initialize module
unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:templavoila/mod1/locallang.xml');
require_once (PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);    								// This checks permissions and exits if the users has no permission for entry.

t3lib_extMgm::isLoaded('cms',1);

	// We need the TCE forms functions
require_once (PATH_t3lib.'class.t3lib_loaddbgroup.php');
require_once (PATH_t3lib.'class.t3lib_tcemain.php');
require_once (PATH_t3lib.'class.t3lib_clipboard.php');

	// Include TemplaVoila API
require_once (t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_api.php');

	// Include class for rendering the side bar and wizards:
require_once (t3lib_extMgm::extPath('templavoila').'mod1/class.tx_templavoila_mod1_sidebar.php');
require_once (t3lib_extMgm::extPath('templavoila').'mod1/class.tx_templavoila_mod1_wizards.php');
require_once (t3lib_extMgm::extPath('templavoila').'mod1/class.tx_templavoila_mod1_clipboard.php');
require_once (t3lib_extMgm::extPath('templavoila').'mod1/class.tx_templavoila_mod1_localization.php');
require_once (t3lib_extMgm::extPath('templavoila').'mod1/class.tx_templavoila_mod1_specialdoktypes.php');

/**
 * Module 'Page' for the 'templavoila' extension.
 *
 * @author		Robert Lemke <robert@typo3.org>
 * @coauthor	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package		TYPO3
 * @subpackage	tx_templavoila
 */
class tx_templavoila_module1 extends t3lib_SCbase {

	var $modTSconfig;								// This module's TSconfig
	var $modSharedTSconfig;							// TSconfig from mod.SHARED
	var $extKey = 'templavoila';					// Extension key of this module

	var $global_tt_content_elementRegister=array(); // Contains a list of all content elements which are used on the page currently being displayed (with version, sheet and language currently set). Mainly used for showing "unused elements" in sidebar.
	var $global_localization_status=array(); 		// Contains structure telling the localization status of each element

	var $altRoot = array();							// Keys: "table", "uid" - thats all to define another "rootTable" than "pages" (using default field "tx_templavoila_flex" for flex form content)
	var $versionId = 0;								// Versioning: The current version id

	var $currentLanguageKey;						// Contains the currently selected language key (Example: DEF or DE)
	var $currentLanguageUid;						// Contains the currently selected language uid (Example: -1, 0, 1, 2, ...)
	var $allAvailableLanguages = array();			// Contains records of all available languages (not hidden, with ISOcode), including the default language and multiple languages. Used for displaying the flags for content elements, set in init().
	var $translatedLanguagesArr = array();			// Select language for which there is a page translation

	var $doc;										// Instance of template doc class
	var $sideBarObj;								// Instance of sidebar class
	var $wizardsObj;								// Instance of wizards class
	var $clipboardObj;								// Instance of clipboard class
	var $rulesObj;									// Instance of the tx_templavoila_rule
	var $apiObj;									// Instance of tx_templavoila_api



	/*******************************************
	 *
	 * Initialization functions
	 *
	 *******************************************/

	/**
	 * Initialisation of this backend module
	 *
	 * @return	void
	 * @access public
	 */
	function init()    {
		parent::init();

		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);

		$this->id = t3lib_beFunc::wsMapId ('pages', $this->id);	// FIXME

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
		$this->sideBarObj->position = isset ($this->modTSconfig['properties']['sideBarPosition']) ? $this->modTSconfig['properties']['sideBarPosition'] : 'toptabs';

		$this->wizardsObj = t3lib_div::getUserObj('&tx_templavoila_mod1_wizards','');
		$this->wizardsObj->init($this);

			// Initialize TemplaVoila API class:
		$apiClassName = t3lib_div::makeInstanceClassName('tx_templavoila_api');
		$this->apiObj = new $apiClassName ($this->altRoot ? $this->altRoot : 'pages');

			// Initialize the clipboard
		$this->clipboardObj =& t3lib_div::getUserObj ('&tx_templavoila_mod1_clipboard','');
		$this->clipboardObj->init($this);
	}

	/**
	 * Preparing menu content and initializing clipboard and module TSconfig
	 *
	 * @return	void
	 * @access public
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
			'showTranslationInfo' => 0,
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
	 * @access public
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
			$this->doc->form='<form action="'.htmlspecialchars('index.php?'.$this->link_getParameters()).'" method="post" autocomplete="off">';

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

			$this->handleIncomingCommands();

				// Start creating HTML output
			$this->content .= $this->doc->startPage($LANG->getLL('title'));

				// Show the "edit current page" screen along with the sidebar
			$shortCut = ($BE_USER->mayMakeShortcut() ? '<br />'.$this->doc->makeShortcutIcon('id,altRoot',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']) : '');
			if ($this->sideBarObj->position == 'left') {
				$this->content .= '
					<table cellspacing="0" cellpadding="0" style="width:100%; height:550px; padding:0; margin:0;">
						<tr>
							<td style="vertical-align:top;">'.$this->sideBarObj->render().'</td>
							<td style="vertical-align:top; padding-bottom:20px;" width="99%">'.$this->render_editPageScreen ().$shortCut;'</td>
						</tr>
					</table>
				';
			} else {
				$this->content .= $this->render_editPageScreen ().$shortCut;
			}

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

	/**
	 * Echoes the HTML output of this module
	 *
	 * @return	void
	 * @access public
	 */
	function printContent()    {
		echo $this->content;
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
	 * @access protected
	 */
	function render_editPageScreen()    {
		global $LANG, $BE_USER, $TYPO3_CONF_VARS;

			// Reset internal variable which registers all used content elements:
		$this->global_tt_content_elementRegister=array();

			// Define the root element record:
		$this->rootElementTable = is_array($this->altRoot) ? $this->altRoot['table'] : 'pages';
		$this->rootElementUid = is_array($this->altRoot) ? $this->altRoot['uid'] : $this->id;
		$this->rootElementRecord = t3lib_BEfunc::getRecordWSOL($this->rootElementTable, $this->rootElementUid, '*');

			// Check if it makes sense to allow editing of this page and if not, show a message:
		if ($this->rootElementTable == 'pages') {

				// Initialize the special doktype class:
			$specialDoktypesObj =& t3lib_div::getUserObj ('&tx_templavoila_mod1_specialdoktypes','');
			$specialDoktypesObj->init($this);

			$methodName = 'renderDoktype_'.$this->rootElementRecord['doktype'];
			if (method_exists($specialDoktypesObj, $methodName)) {
				$result = $specialDoktypesObj->$methodName($this->rootElementRecord);
				if ($result !== FALSE) return $result;
			}
		}

		$contentTreeArr = $this->getContentTree($this->rootElementTable, $this->rootElementRecord, $this->currentLanguageKey);
		$this->rootElementLangDisable = $contentTreeArr['ds_meta']['langDisable'];

			// Create a back button if neccessary:
		if (is_array ($this->altRoot)) {
			$content = '<div style="text-align:right; width:100%; margin-bottom:5px;"><a href="index.php?id='.$this->id.'"><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/goback.gif','').' title="'.htmlspecialchars($LANG->getLL ('goback')).'" alt="" /></a></div>';
		}

			// Add the localization module if localization is enabled:
		if (!$this->rootElementLangDisable) {
			$this->localizationObj =& t3lib_div::getUserObj ('&tx_templavoila_mod1_localization','');
			$this->localizationObj->init($this);
		}

			// Hook for content at the very top (fx. a toolbar):
		if (is_array ($TYPO3_CONF_VARS['EXTCONF']['templavoila']['mod1']['renderTopToolbar'])) {
			foreach ($TYPO3_CONF_VARS['EXTCONF']['templavoila']['mod1']['renderTopToolbar'] as $_funcRef) {
				$_params = array ();
				$content .= t3lib_div::callUserFunction($_funcRef, $_params, $this);
			}
		}

			// Display the nested page structure:
		$content.= $this->render_framework_allSheets($contentTreeArr);

		$content .= t3lib_BEfunc::cshItem('_MOD_web_txtemplavoilaM1', 'pagemodule', $this->doc->backPath,'|<br/>');
		$content .= t3lib_BEfunc::cshItem('_MOD_web_txtemplavoilaM1', '', $this->doc->backPath,'<hr/>|What is the TemplaVoila Page module?');

		return $content;
	}





	/*******************************************
	 *
	 * Framework rendering functions
	 *
	 *******************************************/

	/**
	 * @param	array		$contentTreeArr: DataStructure info array (the whole tree)
	 * @param	array		$parentPointer: Flexform Pointer to parent element
	 * @return	string		HTML
	 * @access protected
	 * @see	render_framework_singleSheet()
	 */
	function render_framework_allSheets($contentTreeArr, $parentPointer=array()) {

			// If more than one sheet is available, render a dynamic sheet tab menu, otherwise just render the single sheet framework
		if (is_array($contentTreeArr['sub']) && (count($contentTreeArr['sub'])>1 || !isset($contentTreeArr['sub']['sDEF'])))	{
			$parts = array();
			foreach($contentTreeArr['sub'] as $sheetKey => $sheetInfo)	{

				$this->containedElementsPointer++;
				$this->containedElements[$this->containedElementsPointer] = 0;
				$frContent = $this->render_framework_singleSheet($contentTreeArr, $sheetKey, $parentPointer);

				$parts[] = array(
					'label' => ($contentTreeArr['meta'][$sheetKey]['title'] ? $contentTreeArr['meta'][$sheetKey]['title'] : $sheetKey).' ['.$this->containedElements[$this->containedElementsPointer].']',
					'description' => $contentTreeArr['meta'][$sheetKey]['description'],
					'linkTitle' => $contentTreeArr['meta'][$sheetKey]['short'],
					'content' => $frContent,
				);

				$this->containedElementsPointer--;
			}
			return $this->doc->getDynTabMenu($parts,'TEMPLAVOILA:pagemodule:'.$this->apiObj->flexform_getStringFromPointer($parentPointer));
		} else {
			return $this->render_framework_singleSheet($contentTreeArr, 'sDEF', $parentPointer);
		}
	}

	/**
	 * Renders the display framework. Calls itself recursively
	 *
	 * @param	array		$contentTreeArr: DataStructure info array (the whole tree)
	 * @param	array		$parentPointer: Flexform pointer to parent element
	 * @param	string		$sheet: The sheet key of the sheet which should be rendered
	 * @return	string		HTML
	 * @access protected
	 * @see	render_framework_singleSheet()
	 */
	function render_framework_singleSheet($contentTreeArr, $sheet, $parentPointer=array()) {
		global $TYPO3_DB, $LANG, $TYPO3_CONF_VARS, $TCA;

		$hookObjectsArr = $this->hooks_prepareObjectsArray ('renderFrameWorkClass');

		$elementRecord = t3lib_beFunc::getRecordWSOL($contentTreeArr['el']['table'], $contentTreeArr['el']['uid'], '*');
		$elementBelongsToCurrentPage = $contentTreeArr['el']['table'] == 'pages' || $contentTreeArr['el']['pid'] == $this->rootElementUid;
		$correctedElementUid = t3lib_beFunc::wsMapId ($contentTreeArr['el']['table'],$contentTreeArr['el']['uid']);

			// Prepare the record icon including a content sensitive menu link wrapped around it:
		$recordIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,$contentTreeArr['el']['icon'],'').' style="text-align: center; vertical-align: middle;" width="18" height="16" border="0" title="'.htmlspecialchars('['.$contentTreeArr['el']['table'].':'.$contentTreeArr['el']['uid'].']').'" alt="" />';
		$titleBarLeftButtons = $this->doc->wrapClickMenuOnIcon($recordIcon,$contentTreeArr['el']['table'], $correctedElementUid, 1,'&amp;callingScriptId='.rawurlencode($this->doc->scriptID), 'new,copy,cut,pasteinto,pasteafter,delete');

			// Prepare table specific settings:
		switch ($contentTreeArr['el']['table']) {

			case 'pages' :

#				$elementTitlebarColor = isset ($this->currentDataStructureArr['pages']['ROOT']['tx_templavoila']['pageModule']['titleBarColor']) ? $this->currentDataStructureArr['pages']['ROOT']['tx_templavoila']['pageModule']['titleBarColor'] : $this->doc->bgColor2;
#				$elementTitlebarStyle = 'background-color: '.$elementTitlebarColor;

				$titleBarLeftButtons .= $this->link_edit('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','').' title="'.htmlspecialchars($LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:editPage')).'" alt="" style="text-align: center; vertical-align: middle; border:0;" />',$contentTreeArr['el']['table'],$contentTreeArr['el']['uid']);
				$titleBarRightButtons = '';

				$viewPageOnClick = 'onclick= "'.htmlspecialchars(t3lib_BEfunc::viewOnClick($correctedElementUid, $this->doc->backPath, t3lib_BEfunc::BEgetRootLine($correctedElementUid),'','',($this->currentLanguageUid?'&L='.$this->currentLanguageUid:''))).'"';
				$viewPageIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/zoom.gif','width="12" height="12"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.showPage',1).'" hspace="3" alt="" style="text-align: center; vertical-align: middle;" />';
				$titleBarLeftButtons .= '<a href="#" '.$viewPageOnClick.'>'.$viewPageIcon.'</a>';

				$contentWrapPre .= ($this->sideBarObj->position == 'toprows' || $this->sideBarObj->position == 'toptabs') ? $this->sideBarObj->render() : '';

			break;

			case 'tt_content' :

 				$elementTitlebarColor = ($elementBelongsToCurrentPage ? $this->doc->bgColor5 : $this->doc->bgColor6);
				$elementTitlebarStyle = 'background-color: '.$elementTitlebarColor;

				$languageUid = $contentTreeArr['el']['sys_language_uid'];

					// Create CE specific buttons:
				$linkMakeLocal = !$elementBelongsToCurrentPage ? $this->link_makeLocal('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,t3lib_extMgm::extRelPath('templavoila').'mod1/makelocalcopy.gif','').' title="'.$LANG->getLL('makeLocal').'" border="0" alt="" />', $parentPointer) : '';
				$linkUnlink = $this->link_unlink('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/garbage.gif','').' title="'.$LANG->getLL('unlinkRecord').'" border="0" alt="" />', $parentPointer, FALSE);
				$linkEdit = ($elementBelongsToCurrentPage ? $this->link_edit('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','').' title="'.$LANG->getLL ('editrecord').'" border="0" alt="" />',$contentTreeArr['el']['table'],$contentTreeArr['el']['uid']) : '');

				$titleBarRightButtons = $linkEdit . $this->clipboardObj->element_getSelectButtons ($parentPointer) . $linkMakeLocal . $linkUnlink;

			break;
		}

			// Prepare the language icon:
		$languageLabel = htmlspecialchars ($this->allAvailableLanguages[$contentTreeArr['el']['sys_language_uid']]['title']);
		$languageIcon = $this->allAvailableLanguages[$languageUid]['flagIcon'] ? '<img src="'.$this->allAvailableLanguages[$languageUid]['flagIcon'].'" title="'.$languageLabel.'" alt="'.$languageLabel.'" style="text-align: center; vertical-align: middle;" />' : ($languageLabel && $languageUid ? '['.$languageLabel.']' : '');

			// Create warning messages if neccessary:
		$warnings = '';
		if ($this->global_tt_content_elementRegister[$contentTreeArr['el']['uid']] > 1) {
			$warnings .= '<br/>'.$this->doc->icons(2).' <em>'.htmlspecialchars(sprintf($LANG->getLL('warning_elementusedmorethanonce',''), $this->global_tt_content_elementRegister[$contentTreeArr['el']['uid']], $contentTreeArr['el']['uid'])).'</em>';
		}

			// Finally assemble the table:
		$finalContent ='
			<table cellpadding="0" cellspacing="0" style="width: 100%; border: 1px solid black; margin-bottom:5px;">
				<tr style="'.$elementTitlebarStyle.';">
					<td nowrap="nowrap" style="vertical-align:top;">'.
						$languageIcon.
						$titleBarLeftButtons.
						($elementBelongsToCurrentPage?'':'<em>').htmlspecialchars($contentTreeArr['el']['title']).($elementBelongsToCurrentPage ? '' : '</em>').
						$warnings.
					'</td>
					<td nowrap="nowrap" style="text-align:right; vertical-align:top;">'.
						$titleBarRightButtons.
					'</td>
				</tr>
				<tr>
					<td colspan="2">'.
						$contentWrapPre.
						$this->render_framework_subElements ($contentTreeArr, $sheet).
						$contentTreeArr['el']['previewContent'].
						($this->MOD_SETTINGS['showTranslationInfo'] ? $this->render_localizationInfoTable ($contentTreeArr, $parentPointer) : '').
					'</td>
				</tr>
			</table>
		';

		return $finalContent;
	}

	/**
	 * Renders the sub elements of the given elementContentTree array. This function basically
	 * renders the "new" and "paste" buttons for the parent element and then traverses through
	 * the sub elements (if any exist). The sub element's (preview-) content will be rendered
	 * by render_framework_singleSheet().
	 *
	 * Calls render_framework_allSheets() and therefore generates a recursion.
	 *
	 * @param	array		$elementContentTreeArr: Content tree starting with the element which possibly has sub elements
	 * @param	string		$sheet: Key of the sheet we want to render
	 * @return	string		HTML output (a table) of the sub elements and some "insert new" and "paste" buttons
	 * @access protected
	 * @see render_framework_allSheets(), render_framework_singleSheet()
	 */
	function render_framework_subElements ($elementContentTreeArr, $sheet){
		global $LANG;

		if (!is_array ($elementContentTreeArr['sub'][$sheet])) return '';

		$output = '';
		$cells = array();
		$headerCells = array();

		foreach($elementContentTreeArr['sub'][$sheet] as $fieldID => $fieldContent)	{

			$cellContent = '';

				// Create flexform pointer pointing to "before the first sub element":
			$subElementPointer = array (
				'table' => $elementContentTreeArr['el']['table'],
				'uid' => $elementContentTreeArr['el']['uid'],
				'sheet' => $sheet,
				'sLang' => $elementContentTreeArr['sub'][$sheet][$fieldID]['meta']['sLang'],
				'field' => $fieldID,
				'vLang' => $elementContentTreeArr['sub'][$sheet][$fieldID]['meta']['vLang'],
				'position' => 0
			);

				// "New" and "Paste" icon:
			$newIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/new_el.gif','').' style="text-align: center; vertical-align: middle;" vspace="5" hspace="1" border="0" title="'.$LANG->getLL ('createnewrecord').'" alt="" />';
			$cellContent .= $this->link_new($newIcon, $subElementPointer);
			$cellContent .= $this->clipboardObj->element_getPasteButtons ($subElementPointer);

				// Render the list of elements (and possibly call itself recursively if needed):
			if (is_array($fieldContent['el_list']))	 {
				foreach($fieldContent['el_list'] as $position => $subElementKey)	{
					$subElementArr = $fieldContent['el'][$subElementKey];
					if (!$subElementArr['hideInDisplay'])	{
						$this->containedElements[$this->containedElementsPointer]++;

							// Modify the flexform pointer so it points to the position of the curren sub element:
						$subElementPointer ['position'] = $position;

						$cellContent .= $this->render_framework_allSheets($subElementArr, $subElementPointer);

							// "New" and "Paste" icon:
						$newIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/new_el.gif','').' style="text-align: center; vertical-align: middle;" vspace="5" hspace="1" border="0" title="'.$LANG->getLL ('createnewrecord').'" alt="" />';
						$cellContent .= $this->link_new($newIcon, $subElementPointer);

						$cellContent .= $this->clipboardObj->element_getPasteButtons ($subElementPointer);
					}
				}
			}

				// Add cell content to registers:
			$headerCells[]='<td valign="top" width="'.round(100/count($elementContentTreeArr['sub'][$sheet])).'%" style="background-color: '.$this->doc->bgColor4.'; padding-top:0; padding-bottom:0;">'.$LANG->sL($fieldContent['meta']['title'],1).'</td>';
			$cells[]='<td valign="top" width="'.round(100/count($elementContentTreeArr['sub'][$sheet])).'%" style="border: 1px dashed #666666; padding: 5px 5px 5px 5px;">'.$cellContent.'</td>';

		}

			// Compile the content area for the current element (basically what was put together above):
		if (count ($headerCells) || count ($cells)) {
			$output = '
				<table border="0" cellpadding="2" cellspacing="2" width="100%">
					<tr>'.(count($headerCells) ? implode('', $headerCells) : '<td>&nbsp;</td>').'</tr>
					<tr>'.(count($cells) ? implode('', $cells) : '<td>&nbsp;</td>').'</tr>
				</table>
			';
		}

		return $output;
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
	 * @param	array		$row: The row of tt_content containing the content element record.
	 * @return	string		HTML preview content
	 * @access protected
	 * @see		getContentTree(), render_localizationInfoTable()
	 */
	function render_previewContent($row) {
		global $TYPO3_CONF_VARS, $LANG;

		$hookObjectsArr = $this->hooks_prepareObjectsArray ('renderPreviewContentClass');
		$alreadyRendered = FALSE;
		$output = '';

			// Hook: renderPreviewContent_preProcess. Set 'alreadyRendered' to true if you provided a preview content for the current cType !
		reset($hookObjectsArr);
		while (list(,$hookObj) = each($hookObjectsArr)) {
			if (method_exists ($hookObj, 'renderPreviewContent_preProcess')) {
				$output .= $hookObj->renderPreviewContent_preProcess ($row, 'tt_content', $alreadyRendered, $this);
			}
		}

		if (!$alreadyRendered) {
				// Preview content for non-flexible content elements:
			switch($row['CType'])	{
				case 'text':		//	Text
				case 'table':		//	Table
				case 'mailform':	//	Form
					$output = $this->link_edit('<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','bodytext'),1).'</strong> '.htmlspecialchars(t3lib_div::fixed_lgd_cs(trim(strip_tags($row['bodytext'])),2000)),'tt_content',$row['uid']).'<br />';
					break;
				case 'image':		//	Image
					$output = $this->link_edit('<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','image'),1).'</strong><br /> ', 'tt_content', $row['uid']).t3lib_BEfunc::thumbCode ($row, 'tt_content', 'image', $this->doc->backPath, '', $v['TCEforms']['config']['uploadfolder']).'<br />';
					break;
				case 'textpic':		//	Text w/image
				case 'splash':		//	Textbox
					$thumbnail = '<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','image'),1).'</strong><br />';
					$thumbnail .= t3lib_BEfunc::thumbCode ($row, 'tt_content', 'image', $this->doc->backPath, '', $v['TCEforms']['config']['uploadfolder']);
					$text = $this->link_edit('<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','bodytext'),1).'</strong> '.htmlspecialchars(t3lib_div::fixed_lgd_cs(trim(strip_tags($row['bodytext'])),2000)),'tt_content',$row['uid']);
					$output='<table><tr><td valign="top">'.$text.'</td><td valign="top">'.$thumbnail.'</td></tr></table>'.'<br />';
					break;
				case 'bullets':		//	Bullets
					$htmlBullets = '';
					$bulletsArr = explode ("\n", t3lib_div::fixed_lgd_cs($row['bodytext'],2000));
					if (is_array ($bulletsArr)) {
						foreach ($bulletsArr as $listItem) {
							$htmlBullets .= htmlspecialchars(trim(strip_tags($listItem))).'<br />';
						}
					}
					$output = $this->link_edit('<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','bodytext'),1).'</strong><br />'.$htmlBullets, 'tt_content', $row['uid']).'<br />';
					break;
				case 'uploads':		//	Filelinks
					$output = $this->link_edit('<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','media'),1).'</strong><br />'.str_replace (',','<br />',htmlspecialchars(t3lib_div::fixed_lgd_cs(trim(strip_tags($row['media'])),2000))), 'tt_content', $row['uid']).'<br />';
					break;
				case 'multimedia':	//	Multimedia
					$output = $this->link_edit ('<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','multimedia'),1).'</strong><br />' . str_replace (',','<br />',htmlspecialchars(t3lib_div::fixed_lgd_cs(trim(strip_tags($row['multimedia'])),2000))), 'tt_content', $row['uid']).'<br />';
					break;
				case 'menu':		//	Menu / Sitemap
					$output = $this->link_edit ('<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','menu_type')).'</strong> '.$LANG->sL(t3lib_BEfunc::getLabelFromItemlist('tt_content','menu_type',$row['menu_type'])).'<br />'.
						'<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','pages')).'</strong> '.$row['pages'], 'tt_content', $row['uid']).'<br />';
					break;
				case 'list':		//	Insert Plugin
					$output = $this->link_edit('<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','list_type')).'</strong> ' . htmlspecialchars($LANG->sL(t3lib_BEfunc::getLabelFromItemlist('tt_content','menu_type',$row['list_type'])).' '.$row['list_type']), 'tt_content', $row['uid']).'<br />';
					break;
				case 'html':		//	HTML
					$output = $this->link_edit ('<strong>'.$LANG->sL(t3lib_BEfunc::getItemLabel('tt_content','bodytext'),1).'</strong> ' . htmlspecialchars(t3lib_div::fixed_lgd_cs(trim($row['bodytext']),2000)),'tt_content',$row['uid']).'<br />';
					break;
				case 'search':			//	Search Box
				case 'login':			//	Login Box
				case 'shortcut':		//	Insert records
				case 'div':				//	Divider
				case 'templavoila_pi1': //	Flexible Content Element: Rendered directly in getContentTree*()
					break;
				default:
						// return CType name for unhandled CType
					$output='<strong>'.htmlspecialchars ($row['CType']).'</strong><br />';
			}
		}
		return $output;
	}

	/**
	 * Renders a little table containing previews of translated version of the current content element.
	 *
	 * @param	array		$contentTreeArr: Part of the contentTreeArr for the element
	 * @param	string		$parentPointer: Flexform pointer pointing to the current element (from the parent's perspective)
	 * @return	string		HTML
	 * @access protected
	 * @see 	render_framework_singleSheet()
	 */
	function render_localizationInfoTable($contentTreeArr, $parentPointer) {
		global $LANG, $BE_USER;

				// LOCALIZATION information for content elements (non Flexible Content Elements)
		$llTable = '';
		if ($contentTreeArr['el']['table']=='tt_content' && $contentTreeArr['el']['sys_language_uid']<=0 && !$this->rootElementLangDisable)	{

				// Traverse the available languages of the page (not default and [All])
			$tRows=array();
			foreach($this->translatedLanguagesArr as $sys_language_uid => $sLInfo)	{
				if ($sys_language_uid > 0)	{
					$lC = '';

					switch((string)$contentTreeArr['localizationInfo'][$sys_language_uid]['mode'])	{
						case 'exists':
							$olrow = t3lib_BEfunc::getRecord('tt_content',$contentTreeArr['localizationInfo'][$sys_language_uid]['localization_uid']);
							$localizedRecordInfo = array(
								'uid' => $olrow['uid'],
								'row' => $olrow,
								'content' => $this->render_previewContent($olrow)
							);

								// Put together the records icon including content sensitive menu link wrapped around it:
							$recordIcon_l10n = t3lib_iconWorks::getIconImage('tt_content',$localizedRecordInfo['row'],$this->doc->backPath,'class="absmiddle" title="'.htmlspecialchars('[tt_content:'.$localizedRecordInfo['uid'].']').'"');
							$recordIcon_l10n = $this->doc->wrapClickMenuOnIcon($recordIcon_l10n,'tt_content',$localizedRecordInfo['uid'],1,'&amp;callingScriptId='.rawurlencode($this->doc->scriptID), 'new,copy,cut,pasteinto,pasteafter');
							$lC = $recordIcon_l10n.t3lib_BEfunc::getRecordTitle('tt_content', $localizedRecordInfo['row']);

							$lC.= '<br/>'.$localizedRecordInfo['content'];

							$this->global_localization_status[$sys_language_uid][]=array(
								'status' => 'exist',
								'parent_uid' => $contentTreeArr['el']['uid'],
								'localized_uid' => $localizedRecordInfo['row']['uid'],
								'sys_language' => $contentTreeArr['el']['sys_language_uid']
							);
						break;
						case 'localize':
								// Assuming that only elements which have the default language set are candidates for localization. In case the language is [ALL] then it is assumed that the element should stay "international".
							if ((int)$contentTreeArr['el']['sys_language_uid']===0)	{

								$linkLabel = $LANG->getLL('createcopyfortranslation',1).' ('.htmlspecialchars($sLInfo['title']).')';
								$localizeIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/clip_copy.gif','width="12" height="12"').' class="bottom" title="'.$linkLabel.'" alt="" />';
								$sourcePointerString = $this->apiObj->flexform_getStringFromPointer ($parentPointer);
								$lC = '<a href="index.php?'.$this->link_getParameters().'&amp;source='.rawurlencode($sourcePointerString).'&amp;localizeRecord='.$sLInfo['ISOcode'].'">'.$localizeIcon.'</a>';
								$lC .= ' <em><a href="index.php?'.$this->link_getParameters().'&amp;source='.rawurlencode($sourcePointerString).'&amp;localizeRecord='.$sLInfo['ISOcode'].'">'.$linkLabel.'</a></em>';


								$this->global_localization_status[$sys_language_uid][]=array(
									'status' => 'localize',
									'parent_uid' => $contentTreeArr['el']['uid'],
									'sys_language' => $contentTreeArr['el']['sys_language_uid']
								);
							}
						break;
						case 'localizedFlexform':
							$lC = '<em>[Localized FlexForm]</em>';

							$this->global_localization_status[$sys_language_uid][]=array(
								'status' => 'flex',
								'parent_uid' => $contentTreeArr['el']['uid'],
								'sys_language' => $contentTreeArr['el']['sys_language_uid']
							);
						break;
					}

					if (1 && $lC && $BE_USER->checkLanguageAccess($sys_language_uid))	{
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
	 * Link functions (protected)
	 *
	 *******************************************/

	/**
	 * Returns an HTML link for editing
	 *
	 * @param	string		$label: The label (or image)
	 * @param	string		$table: The table, fx. 'tt_content'
	 * @param	integer		$uid: The uid of the element to be edited
	 * @return	string		HTML anchor tag containing the label and the correct link
	 * @access protected
	 */
	function link_edit($label, $table, $uid)	{
		if($table == "pages" && $this->currentLanguageUid) {
			return '<a href="index.php?'.$this->link_getParameters().'&amp;editPageLanguageOverlay='.$this->currentLanguageUid.'">'.$label.'</a>';
		} else {
			$onClick = t3lib_BEfunc::editOnClick('&edit['.$table.']['.$uid.']=edit',$this->doc->backPath);
			return '<a style="text-decoration: none;" href="#" onclick="'.htmlspecialchars($onClick).'">'.$label.'</a>';
		}
	}

	/**
	 * Returns an HTML link for creating a new record
	 *
	 * @param	string		$label: The label (or image)
	 * @param	array		$parentPointer: Flexform pointer defining the parent element of the new record
	 * @return	string		HTML anchor tag containing the label and the correct link
	 * @access protected
	 */
	function link_new($label, $parentPointer)	{

		$parameters =
			$this->link_getParameters().
			'&amp;parentRecord='.rawurlencode($this->apiObj->flexform_getStringFromPointer($parentPointer));
		return '<a href="'.'db_new_content_el.php?'.$parameters.'">'.$label.'</a>';
	}

	/**
	 * Returns an HTML link for unlinking a content element. Unlinking means that the record still exists but
	 * is not connected to any other content element or page.
	 *
	 * @param	string		$label: The label
	 * @param	array		$unlinkPointer: Flexform pointer pointing to the element to be unlinked
	 * @param	[type]		$realDelete: ...
	 * @return	string		HTML anchor tag containing the label and the unlink-link
	 * @access protected
	 */
	function link_unlink($label, $unlinkPointer, $realDelete=FALSE)	{
		global $LANG;

		$unlinkPointerString = rawurlencode($this->apiObj->flexform_getStringFromPointer ($unlinkPointer));

		if ($realDelete)	{
			return '<a href="index.php?'.$this->link_getParameters().'&amp;deleteRecord='.$unlinkPointerString.'" onclick="'.htmlspecialchars('return confirm('.$LANG->JScharCode($LANG->getLL('deleteRecordMsg')).');').'">'.$label.'</a>';
		} else {
			return '<a href="index.php?'.$this->link_getParameters().'&amp;unlinkRecord='.$unlinkPointerString.'" onclick="'.htmlspecialchars('return confirm('.$LANG->JScharCode($LANG->getLL('unlinkRecordMsg')).');').'">'.$label.'</a>';
		}
	}

	/**
	 * Returns an HTML link for making a reference content element local to the page (copying it).
	 *
	 * @param	string		$label: The label
	 * @param	array		$makeLocalPointer: Flexform pointer pointing to the element which shall be copied
	 * @return	string		HTML anchor tag containing the label and the unlink-link
	 * @access protected
	 */
	function link_makeLocal($label, $makeLocalPointer)	{
		global $LANG;

		return '<a href="index.php?'.$this->link_getParameters().'&amp;makeLocalRecord='.rawurlencode($this->apiObj->flexform_getStringFromPointer($makeLocalPointer)).'" onclick="'.htmlspecialchars('return confirm('.$LANG->JScharCode($LANG->getLL('makeLocalMsg')).');').'">'.$label.'</a>';
	}

	/**
	 * Creates additional parameters which are used for linking to the current page while editing it
	 *
	 * @return	string		parameters
	 * @access public
	 */
	function link_getParameters()	{
		$output =
			'id='.$this->id.
			(is_array($this->altRoot) ? t3lib_div::implodeArrayForUrl('altRoot',$this->altRoot) : '') .
			($this->versionId ? '&amp;versionId='.rawurlencode($this->versionId) : '');
		return $output;
	}





	/*************************************************
	 *
	 * Processing and structure functions (protected)
	 *
	 *************************************************/

	/**
	 * Checks various GET / POST parameters for submitted commands and handles them accordingly.
	 * All commands will trigger a redirect by sending a location header after they work is done.
	 *
	 * Currently supported commands: 'createNewRecord', 'unlinkRecord', 'deleteRecord','pasteRecord',
	 * 'makeLocalRecord', 'localizeRecord', 'createNewPageTranslation' and 'editPageLanguageOverlay'
	 *
	 * @return	void
	 * @access protected
	 */
	function handleIncomingCommands() {

		$possibleCommands = array ('createNewRecord', 'unlinkRecord', 'deleteRecord','pasteRecord', 'makeLocalRecord', 'localizeRecord', 'createNewPageTranslation', 'editPageLanguageOverlay');

		foreach ($possibleCommands as $command) {
			if ($commandParameters = t3lib_div::_GP($command)) {

				$redirectLocation = 'index.php?'.$this->link_getParameters();

				switch ($command) {

					case 'createNewRecord':
							// Historically "defVals" has been used for submitting the preset row data for the new element, so we still support it here:
						$defVals = t3lib_div::_GP('defVals');
						$newRow = is_array ($defVals['tt_content']) ? $defVals['tt_content'] : array();

							// Create new record and open it for editing
						$destinationPointer = $this->apiObj->flexform_getPointerFromString($commandParameters);
						$newUid = $this->apiObj->insertElement($destinationPointer, $newRow);
						$redirectLocation = $GLOBALS['BACK_PATH'].'alt_doc.php?edit[tt_content]['.$newUid.']=edit&returnUrl='.rawurlencode(t3lib_extMgm::extRelPath('templavoila').'mod1/index.php?'.$this->link_getParameters());
					break;

					case 'unlinkRecord':
						$unlinkDestinationPointer = $this->apiObj->flexform_getPointerFromString($commandParameters);
						$this->apiObj->unlinkElement($unlinkDestinationPointer);
					break;

					case 'deleteRecord':
						$deleteDestinationPointer = $this->apiObj->flexform_getPointerFromString($commandParameters);
						$this->apiObj->deleteElement($deleteDestinationPointer);
					break;

					case 'pasteRecord':
						$sourcePointer = $this->apiObj->flexform_getPointerFromString (t3lib_div::_GP('source'));
						$destinationPointer = $this->apiObj->flexform_getPointerFromString (t3lib_div::_GP('destination'));
						switch ($commandParameters) {
							case 'copy' :	$this->apiObj->copyElement ($sourcePointer, $destinationPointer); break;
							case 'copyref':	$this->apiObj->copyElement ($sourcePointer, $destinationPointer, FALSE); break;
							case 'cut':		$this->apiObj->moveElement ($sourcePointer, $destinationPointer); break;
							case 'ref':		list($table,$uid) = explode(':', t3lib_div::_GP('source'));
											$this->apiObj->referenceElementByUid ($uid, $destinationPointer);
							break;

						}
					break;

					case 'makeLocalRecord':
						$sourcePointer = $this->apiObj->flexform_getPointerFromString ($commandParameters);
						$this->apiObj->copyElement ($sourcePointer, $sourcePointer);
						$this->apiObj->unlinkElement ($sourcePointer);
					break;

					case 'localizeRecord':
						$sourcePointer = $this->apiObj->flexform_getPointerFromString (t3lib_div::_GP('source'));
						$this->apiObj->localizeElement ($sourcePointer, $commandParameters);
					break;

					case 'createNewPageTranslation':
							// Create parameters and finally run the classic page module for creating a new page translation
						$params = '&edit[pages_language_overlay]['.intval (t3lib_div::_GP('pid')).']=new&overrideVals[pages_language_overlay][sys_language_uid]='.intval($commandParameters);
						$returnUrl = '&returnUrl='.rawurlencode(t3lib_extMgm::extRelPath('templavoila').'mod1/index.php?'.$this->link_getParameters());
						$redirectLocation = $GLOBALS['BACK_PATH'].'alt_doc.php?'.$params.$returnUrl;
					break;

					case 'editPageLanguageOverlay':
							// Look for pages language overlay record for language:
						list($pLOrecord) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
								'*',
								'pages_language_overlay',
								'pid='.intval($this->id).' AND sys_language_uid='.intval($commandParameters).
									t3lib_BEfunc::deleteClause('pages_language_overlay').
									t3lib_BEfunc::versioningPlaceholderClause('pages_language_overlay')
							);
						t3lib_beFunc::workspaceOL('pages_language_overlay', $pLOrecord);

						if (is_array($pLOrecord))	{
							$params = '&edit[pages_language_overlay]['.$pLOrecord['uid'].']=edit';
							$returnUrl = '&returnUrl='.rawurlencode(t3lib_extMgm::extRelPath('templavoila').'mod1/index.php?'.$this->link_getParameters());
							$redirectLocation = $GLOBALS['BACK_PATH'].'alt_doc.php?'.$params.$returnUrl.'&localizationMode=text';
						}
					break;
				}
			}
		}

		if (isset ($redirectLocation)) {
			header('Location: '.t3lib_div::locationHeaderUrl($redirectLocation));
		}
	}

	/**
	 * Returns the content tree (based on the data structure) for a certain page or a flexible content element. In case of a page it will contain all the references
	 * to content elements (and some more information) and in case of a FCE, references to its sub-elements.
	 *
	 * This function also adds preview content for later display in the page module. The rendering for traditional content elements is done in render_previewContent(),
	 * all other elements are rendered in the getContentTree*() functions.
	 *
	 * @param	string		$table: Table which contains the (XML) data structure. Only records from table 'pages' or flexible content elements from 'tt_content' are handled
	 * @param	array		$row: Record of the root element where the tree starts
	 * @param	string		$languageKey: The content tree will only contain elements which would be shown when the given language is selected. Use uppercase country codes, fx. "DEF", "DE" or "EN"
	 * @param	string		$prevRecList: comma separated list of uids, used internally for recursive calls. Don't mess with it!
	 * @return	array		The content tree
	 * @access protected
	 */
	function getContentTree($table, $row, $languageKey='DEF', $prevRecList='')	{
		global $TCA, $LANG;

		$tree=array();
		$tree['el']=array(
			'table' => $table,
			'uid' => $row['uid'],
			'pid' => $row['pid'],
			'title' => t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle($table, $row),50),
			'icon' => t3lib_iconWorks::getIcon($table, $row),
			'previewContent' => ($table == 'tt_content' ? $this->render_previewContent($row) : NULL),
			'sys_language_uid' => $row['sys_language_uid'],
			'l18n_parent' => $row['l18n_parent'],
			'CType' => $row['CType'],
		);

		if ($table == 'pages' || $table == $this->altRoot['table'] || ($table=='tt_content' && $row['CType']=='templavoila_pi1'))	{

			t3lib_div::loadTCA ($table);
			$rawDataStructureArr = t3lib_BEfunc::getFlexFormDS($TCA[$table]['columns']['tx_templavoila_flex']['config'], $row, $table);
			$expandedDataStructureArr = $this->apiObj->ds_getExpandedDataStructure($table, $row);

			$tree['ds_meta'] = $rawDataStructureArr['meta'];
			$flexformContentArr = t3lib_div::xml2array($row['tx_templavoila_flex']);
			if (!is_array($flexformContentArr))	$flexformContentArr = array();

				// Respect the currently selected language, for both concepts - with langChildren enabled and disabled:
			$langChildren = intval($tree['ds_meta']['langChildren']);
			$langDisable = intval($tree['ds_meta']['langDisable']);

			$lKey = $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l'.$languageKey);
			$vKey = $langDisable ? 'vDEF' : ($langChildren ? 'v'.$languageKey : 'vDEF');

			$tree['el']['sLang'] = $lKey;
			$tree['el']['vLang'] = $vKey;

			if ($table == 'pages') {
				$currentTemplateObject = $this->getPageTemplateObject($row);
				if (is_array ($currentTemplateObject)) {
					$templateMappingArr = unserialize($currentTemplateObject['templatemapping']);	
				}
			}	

			foreach($expandedDataStructureArr as $sheetKey => $sheetData)	{

					// Add some sheet meta information:
				$tree['sub'][$sheetKey] = array();
				$tree['meta'][$sheetKey] = array(
					'title' => (is_array($sheetData) && $sheetData['ROOT']['TCEforms']['sheetTitle'] ? $LANG->sL($sheetData['ROOT']['TCEforms']['sheetTitle']) : ''),
					'description' => (is_array($sheetData) && $sheetData['ROOT']['TCEforms']['sheetDescription'] ? $LANG->sL($sheetData['ROOT']['TCEforms']['sheetDescription']) : ''),
					'short' => (is_array($sheetData) && $sheetData['ROOT']['TCEforms']['sheetShortDescr'] ? $LANG->sL($sheetData['ROOT']['TCEforms']['sheetShortDescr']) : ''),
				);

					// Traverse the sheet's elements:
				if (is_array($sheetData) && is_array($sheetData['ROOT']['el']))	{
					foreach($sheetData['ROOT']['el'] as $fieldKey => $fieldData) {
						
						if (is_array($templateMappingArr['MappingInfo']['ROOT']['el'][$fieldKey]) || $table!='pages') {
							$tree['fields'][$fieldKey] = array();
	
							$TCEformsConfiguration = $fieldData['TCEforms']['config'];
							$TCEformsLabel = $LANG->sL($fieldData['TCEforms']['label'], 1);
	
							if (isset ($flexformContentArr['data'][$sheetKey][$lKey][$fieldKey][$vKey])) {
								$fieldValue = $flexformContentArr['data'][$sheetKey][$lKey][$fieldKey][$vKey];
	
									// Render preview for images:
								if ($TCEformsConfiguration['type'] == 'group' && $TCEformsConfiguration['internal_type'] == 'file')	{
									$thumbnail = t3lib_BEfunc::thumbCode (array('dummyFieldName'=> $fieldValue), '', 'dummyFieldName', $this->doc->backPath, '', $TCEformsConfiguration['uploadfolder']);
									$tree['el']['previewContent'] .= '<strong>'.$TCEformsLabel.'</strong> '.$thumbnail.'<br />';
	
									// Render images for everything else:
								} elseif ($TCEformsConfiguration['type'] != 'group') {
									$tree['el']['previewContent'] .= '<strong>'.$TCEformsLabel.'</strong> '. $this->link_edit(htmlspecialchars(t3lib_div::fixed_lgd_cs(strip_tags($fieldValue),200)), 'tt_content', $row['uid']).'<br />';
								}
							}
	
								// If the current field points to other content elements, process them:
							if ($fieldData['TCEforms']['config']['type'] == 'group' &&
								$fieldData['TCEforms']['config']['internal_type'] == 'db' &&
								$fieldData['TCEforms']['config']['allowed'] == 'tt_content')	{
									$tree = $this->getContentTree_processSubContent ($flexformContentArr, $tree, $sheetKey, $lKey, $fieldKey, $vKey, $fieldData, $languageKey, $prevRecList);
	
	 							// If the current field contains no content itself but has sub elements (sections / containers):
							} elseif ($fieldData['type'] == 'array') {
								$tree = $this->getContentTree_processSubElements ($flexformContentArr, $tree, $sheetKey, $lKey, $fieldKey, $vKey, $fieldData, $row);
							}
						}
					}
				}
			}
		}

			// Add localization info for this element:
		$tree['localizationInfo'] = $this->getContentTree_getLocalizationInfoForElement($tree);

		return $tree;
	}

	/**
	 * Sub function for rendering the content tree. Handles sub content elements of the current element.
	 *
	 * @param	array		$flexformContentArr: Flexform content of the current element, as an array
	 * @param	array		$tree: The content tree we are currently building
	 * @param	string		$sheetKey: The key of the currently selected sheet
	 * @param	string		$lKey: The current structure language key
	 * @param	string		$fieldKey: Name of the current field
	 * @param	string		$vKey: Language key of the current value language
	 * @param	array		$fieldData: Data of the current field from the expanded datastructure
	 * @param	string		$languageKey: Key of the currently selected language
	 * @param	string		$prevRecList: List of previously processed record uids
	 * @return	array		The modified tree
	 * @access protected
	 */
	function getContentTree_processSubContent($flexformContentArr, $tree, $sheetKey, $lKey, $fieldKey, $vKey, $fieldData, $languageKey, $prevRecList) {
		global $TCA;

		$tree['sub'][$sheetKey][$fieldKey]=array();
		$tree['sub'][$sheetKey][$fieldKey]['el']=array();
		$tree['sub'][$sheetKey][$fieldKey]['meta']=array(
			'title' => $fieldData['TCEforms']['label'],
			'langDisable' => $tree['ds_meta']['langDisable'],
			'langChildren' => $tree['ds_meta']['langChildren'],
			'sLang' => $lKey,
			'vLang' => $vKey
		);

		$listOfSubElementUids = $flexformContentArr['data'][$sheetKey][$lKey][$fieldKey][$vKey];
		$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
		$dbAnalysis->start($listOfSubElementUids, 'tt_content');
		$elArray = array();

		foreach($dbAnalysis->itemArray as $counter => $recIdent)	{
			$this->global_tt_content_elementRegister[$recIdent['id']]++;
			$idStr = $recIdent['table'].':'.$recIdent['id'];

			$nextSubRecord = t3lib_BEfunc::getRecordWSOL($recIdent['table'],$recIdent['id']);
			if (is_array($nextSubRecord))	{
				$idRow = $elArray[] = $nextSubRecord;
			} else {
				$idRow = $elArray[] = NULL;
			}
			$hideInDisplay = !($this->MOD_SETTINGS['tt_content_showHidden'] || ($TCA[$recIdent['table']]['ctrl']['enablecolumns']['disabled'] && !$nextSubRecord[$TCA[$recIdent['table']]['ctrl']['enablecolumns']['disabled']]));

			if (!t3lib_div::inList($prevRecList,$idStr))	{
				if (is_array($idRow))	{
					$tree['sub'][$sheetKey][$fieldKey]['el'][$idStr] = $this->getContentTree($recIdent['table'],$idRow, $languageKey, $prevRecList.','.$idStr);
					$tree['sub'][$sheetKey][$fieldKey]['el'][$idStr]['el']['index'] = $counter+1;
					$tree['sub'][$sheetKey][$fieldKey]['el'][$idStr]['hideInDisplay'] = $hideInDisplay;
					$tree['sub'][$sheetKey][$fieldKey]['el_list'][($counter+1)] = $idStr;

				} else {
					# ERROR: The element referenced was deleted! - or hidden :-)
				}
			} else {
				# ERROR: recursivity error!
			}
		}
		return $tree;
	}

	/**
	 * Sub function for rendering the content tree. Handles sub elements which don't have any content
	 * but do have sub elements (eg. sections and containers).
	 *
	 * @param	array		$flexformContentArr: Flexform content of the current element, as an array
	 * @param	array		$tree: The content tree we are currently building
	 * @param	string		$sheetKey: The key of the currently selected sheet
	 * @param	string		$lKey: The current structure language key
	 * @param	string		$fieldKey: Name of the current field
	 * @param	string		$vKey: Language key of the current value language
	 * @param	array		$fieldData: Data of the current field from the expanded datastructure
	 * @param	array		$row: Current tt_content record to be processed
	 * @return	array		The modified tree
	 * @access protected
	 * @todo	Does not support nested SC/CO yet and should be designed to run recursively!
	 */
	function getContentTree_processSubElements($flexformContentArr, $tree, $sheetKey, $lKey, $fieldKey, $vKey, $fieldData, $row) {

		$tree['fields'][$fieldKey]['tx_templavoila']['title'] = $fieldData['tx_templavoila']['title'];
		$tree['fields'][$fieldKey]['tx_templavoila']['section'] = $fieldData['section'];

		if (is_array($flexformContentArr['data'][$sheetKey][$lKey][$fieldKey]['el'])) {
			if ($fieldData['section']) {
				foreach($flexformContentArr['data'][$sheetKey][$lKey][$fieldKey]['el'] as $sectionIndex => $sectionData) {
					$sectionFieldKey = key($sectionData);
					if (is_array ($sectionData[$sectionFieldKey]['el'])) {

						$tree['el']['previewContent'] .= '<ul>';
						foreach ($sectionData[$sectionFieldKey]['el'] as $containerFieldKey => $containerData) {
							$tree['el']['previewContent'] .= '<li><strong>'.$containerFieldKey.'</strong> '.$this->link_edit(htmlspecialchars(t3lib_div::fixed_lgd_cs(strip_tags($containerData[$vKey]),200)), 'tt_content', $row['uid']).'</li>';
						}
						$tree['el']['previewContent'] .= '</ul>';

					}
				}
			} else {
 				foreach ($flexformContentArr['data'][$sheetKey][$lKey][$fieldKey]['el'] as $containerKey => $containerData) {
					$tree['el']['previewContent'] .= $this->link_edit(htmlspecialchars(t3lib_div::fixed_lgd_cs(strip_tags($containerData[$vKey]),200)), 'tt_content', $row['uid']).'<br />';
				}
			}
		}

		return $tree;
	}


	/**
	 * Returns information about localization of traditional content elements (non FCEs).
	 * It will be added to the content tree by getContentTree().
	 *
	 * @param	array		$contentTreeArr: Part of the content tree of the element to create the localization information for.
	 * @return	array		Array of sys_language UIDs with some information as the value
	 * @access protected
	 * @see		getContentTree()
	 */
	function getContentTree_getLocalizationInfoForElement($contentTreeArr) {
		global $TYPO3_DB;

		$localizationInfoArr = array();
		if ($contentTreeArr['el']['table']=='tt_content' && $contentTreeArr['el']['sys_language_uid']<=0)	{

				// Finding translations of this record and select overlay record:
			$fakeElementRow = array ('uid' => $contentTreeArr['el']['uid'], 'pid' => $contentTreeArr['el']['pid']);
			t3lib_beFunc::fixVersioningPID ('tt_content', $fakeElementRow);
			$res = $TYPO3_DB->exec_SELECTquery(
						'*',
						'tt_content',
						'pid='.$fakeElementRow['pid'].
							' AND sys_language_uid>0'.
							' AND l18n_parent='.intval($contentTreeArr['el']['uid']).
							t3lib_BEfunc::deleteClause('tt_content')
					);
			$attachedLocalizations = array();
			while($olrow = $TYPO3_DB->sql_fetch_assoc($res))	{
				t3lib_BEfunc::workspaceOL('tt_content',$olrow);
				if (!isset($attachedLocalizations[$olrow['sys_language_uid']]))	{
					$attachedLocalizations[$olrow['sys_language_uid']] = $olrow['uid'];
				}
			}

				// Traverse the available languages of the page (not default and [All])
			foreach($this->translatedLanguagesArr as $sys_language_uid => $sLInfo)	{
				if ($sys_language_uid > 0)	{
					if (isset($attachedLocalizations[$sys_language_uid]))	{
						$localizationInfoArr[$sys_language_uid] = array();
						$localizationInfoArr[$sys_language_uid]['mode'] = 'exists';
						$localizationInfoArr[$sys_language_uid]['localization_uid'] = $attachedLocalizations[$sys_language_uid];

						$this->global_tt_content_elementRegister[$attachedLocalizations[$sys_language_uid]]++;
					} elseif ($contentTreeArr['el']['CType']!='templavoila_pi1') {	// Dont localize Flexible Content Elements (might change policy on this or make it configurable if any would rather like to localize FCEs by duplicate records instead of internally in the FlexForm XML)
						if ((int)$contentTreeArr['el']['sys_language_uid']===0)	{	// Assuming that only elements which have the default language set are candidates for localization. In case the language is [ALL] then it is assumed that the element should stay "international".
							$localizationInfoArr[$sys_language_uid] = array();
							$localizationInfoArr[$sys_language_uid]['mode'] = 'localize';
						}
					} elseif(!$contentTreeArr['ds_meta']['langDisable'] && (int)$contentTreeArr['el']['sys_language_uid']===-1) {	// CType == templavoila_pi1
						$localizationInfoArr[$sys_language_uid] = array();
						$localizationInfoArr[$sys_language_uid]['mode'] = 'localizedFlexform';
					} else {
						$localizationInfoArr[$sys_language_uid] = array();
						$localizationInfoArr[$sys_language_uid]['mode'] = 'no_localization';
					}
				}
			}
		}
		return $localizationInfoArr;
	}





	/***********************************************
	 *
	 * Miscelleaneous helper functions (protected)
	 *
	 ***********************************************/

	/**
	 * Returns an array of available languages (to use for FlexForms)
	 *
	 * @param	integer		$id: If zero, the query will select all sys_language records from root level. If set to another value, the query will select all sys_language records that has a pages_language_overlay record on that page (and is not hidden, unless you are admin user)
	 * @param	boolean		$onlyIsoCoded: If set, only languages which are paired with a static_info_table / static_language record will be returned.
	 * @param	boolean		$setDefault: If set, an array entry for a default language is set.
	 * @param	boolean		$setMulti: If set, an array entry for "multiple languages" is added (uid -1)
	 * @return	array
	 * @access protected
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
				'title' => strlen ($this->modSharedTSconfig['properties']['defaultLanguage.']['title']) ? $this->modSharedTSconfig['properties']['defaultLanguage.']['title'] : $LANG->getLL ('defaultLanguage'),
				'ISOcode' => 'DEF',
				'flagIcon' => strlen($this->modSharedTSconfig['properties']['defaultLanguage.']['flag']) && @is_file($flagAbsPath.$this->modSharedTSconfig['properties']['defaultLanguage.']['flag']) ? $flagIconPath.$this->modSharedTSconfig['properties']['defaultLanguage.']['flag'] : null,
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
			t3lib_BEfunc::workspaceOL('sys_language', $row);
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
	 * Finds the currently selected template object by climbing up the root line. 
	 * 
	 * @param	array		$row: A page record
	 * @return	mixed		The template object record or FALSE if none was found
	 * @access protected
	 */
	function getPageTemplateObject($row) {
		$templateObjectUid = intval($row['tx_templavoila_to']);
		if (!$templateObjectUid) {
			$rootLine = t3lib_beFunc::BEgetRootLine($row['uid'],'', TRUE);
			foreach($rootLine as $rootLineRecord) {
				$pageRecord = t3lib_beFunc::getRecord('pages', $rootLineRecord['uid']);
				if (($row['uid'] != $pageRecord['uid']) && $pageRecord['tx_templavoila_next_to'])	{	// If there is a next-level TO:
					$templateObjectUid = $pageRecord['tx_templavoila_next_to'];
					break;
				} elseif ($pageRecord['tx_templavoila_to'])	{	// Otherwise try the NORMAL TO:
					$templateObjectUid = $pageRecord['tx_templavoila_to'];
					break;
				}
			}
		}
		return t3lib_beFunc::getRecordWSOL('tx_templavoila_tmplobj', $templateObjectUid);		
	}

	/**
	 * Returns an array of registered instantiated classes for a certain hook.
	 *
	 * @param	string		$hookName: Name of the hook
	 * @return	array		Array of object references
	 * @access protected
	 */
	function hooks_prepareObjectsArray ($hookName) {
		global $TYPO3_CONF_VARS;

		$hookObjectsArr = array();
		if (is_array ($TYPO3_CONF_VARS['EXTCONF']['templavoila']['mod1'][$hookName])) {
			foreach ($TYPO3_CONF_VARS['EXTCONF']['templavoila']['mod1'][$hookName] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj ($classRef);
			}
		}
		return $hookObjectsArr;
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