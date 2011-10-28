<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003-2006 Robert Lemke (robert@typo3.org)
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
 * @coauthor   Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @coauthor   Dmitry Dulepov <dmitry@typo3.org>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  122: class tx_templavoila_module1 extends t3lib_SCbase
 *
 *              SECTION: Initialization functions
 *  162:     function init()
 *  213:     function menuConfig()
 *
 *              SECTION: Main functions
 *  271:     function main()
 *  451:     function printContent()
 *
 *              SECTION: Rendering functions
 *  471:     function render_editPageScreen()
 *
 *              SECTION: Framework rendering functions
 *  538:     function render_framework_allSheets($contentTreeArr, $languageKey='DEF', $parentPointer=array(), $parentDsMeta=array())
 *  576:     function render_framework_singleSheet($contentTreeArr, $languageKey, $sheet, $parentPointer=array(), $parentDsMeta=array())
 *  699:     function render_framework_subElements($elementContentTreeArr, $languageKey, $sheet)
 *
 *              SECTION: Rendering functions for certain subparts
 *  818:     function render_previewData($previewData, $elData, $ds_meta, $languageKey, $sheet)
 *  885:     function render_previewContent($row)
 *  971:     function render_localizationInfoTable($contentTreeArr, $parentPointer, $parentDsMeta=array())
 *
 *              SECTION: Outline rendering:
 * 1111:     function render_outline($contentTreeArr)
 * 1217:     function render_outline_element($contentTreeArr, &$entries, $indentLevel=0, $parentPointer=array(), $controls='')
 * 1319:     function render_outline_subElements($contentTreeArr, $sheet, &$entries, $indentLevel)
 * 1404:     function render_outline_localizations($contentTreeArr, &$entries, $indentLevel)
 *
 *              SECTION: Link functions (protected)
 * 1466:     function link_edit($label, $table, $uid, $forced=FALSE)
 * 1487:     function link_new($label, $parentPointer)
 * 1505:     function link_unlink($label, $unlinkPointer, $realDelete=FALSE)
 * 1525:     function link_makeLocal($label, $makeLocalPointer)
 * 1537:     function link_getParameters()
 *
 *              SECTION: Processing and structure functions (protected)
 * 1565:     function handleIncomingCommands()
 *
 *              SECTION: Miscelleaneous helper functions (protected)
 * 1689:     function getAvailableLanguages($id=0, $onlyIsoCoded=true, $setDefault=true, $setMulti=false)
 * 1763:     function hooks_prepareObjectsArray ($hookName)
 * 1780:     function alternativeLanguagesDefined()
 * 1790:     function displayElement($subElementArr)
 *
 * TOTAL FUNCTIONS: 25
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
require_once (t3lib_extMgm::extPath('templavoila').'mod1/class.tx_templavoila_mod1_records.php');
require_once (t3lib_extMgm::extPath('templavoila').'mod1/class.tx_templavoila_mod1_specialdoktypes.php');

/**
 * Module 'Page' for the 'templavoila' extension.
 *
 * @author		Robert Lemke <robert@typo3.org>
 * @coauthor	Kasper Skaarhoj <kasperYYYY@typo3.com>
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
	var $translatedLanguagesArr_isoCodes = array();	// ISO codes (for l/v pairs) of translated languages.
	var $translatorMode = FALSE;					// If this is set, the whole page module scales down functionality so that a translator only needs  to look for and click the "Flags" in the interface to localize the page! This flag is set if a user does not have access to the default language; then translator mode is assumed.
	var $calcPerms;									// Permissions for the parrent record (normally page). Used for hiding icons.

	var $doc;										// Instance of template doc class
	var $sideBarObj;								// Instance of sidebar class
	var $wizardsObj;								// Instance of wizards class
	var $clipboardObj;								// Instance of clipboard class
	var $recordsObj;								// Instance of records class
	/**
	 * @var tx_templavoila_api
	 */
	var $apiObj;									// Instance of tx_templavoila_api
	var $sortableContainers = array();				// Contains the containers for drag and drop
	var $allItems = array();						// Registry for all id => flexPointer-Pairs
	var $sortableItems = array();					// Registry for sortable id => flexPointer-Pairs

	var $extConf;									// holds the extconf configuration

	var $blindIcons = array();						// Icons which shouldn't be rendered by configuration, can contain elements of "new,edit,copy,cut,ref,paste,browse,delete,makeLocal,unlink,hide"

	protected $renderPreviewObjects = NULL;			// Classes for preview render
	protected $renderPreviewDataObjects = NULL;			// Classes for preview render
	protected $previewTitleMaxLen = 50;
	protected $visibleContentHookObjects = NULL;
	protected $debug = FALSE;
	protected static $calcPermCache = array();

	protected $newContentWizScriptPath = 'db_new_content_el.php';	// Setting which new content wizard to use

	public $currentElementBelongsToCurrentPage;		// Used for Content preview and is used as flag if content should be linked or not
	const DOKTYPE_NORMAL_EDIT = 1;					// With this doktype the normal Edit screen is rendered

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

		$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->id, 'mod.' . $this->MCONF['name']);
		$this->modSharedTSconfig = t3lib_BEfunc::getModTSconfig($this->id, 'mod.SHARED');
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);

		$tmpTSc = t3lib_BEfunc::getModTSconfig($this->id,'mod.web_list');
		$tmpTSc = $tmpTSc ['properties']['newContentWiz.']['overrideWithExtension'];
		if ($tmpTSc != 'templavoila' && t3lib_extMgm::isLoaded($tmpTSc)) {
			$this->newContentWizScriptPath = $GLOBALS['BACK_PATH'] . t3lib_extMgm::extRelPath($tmpTSc).'mod1/db_new_content_el.php';
		}

		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);

		$this->altRoot = t3lib_div::_GP('altRoot');
		$this->versionId = t3lib_div::_GP('versionId');

		if (isset($this->modTSconfig['properties']['previewTitleMaxLen'])) {
			$this->previewTitleMaxLen = intval($this->modTSconfig['properties']['previewTitleMaxLen']);
		}

			// enable debug for development
		if ($this->modTSconfig['properties']['debug']) {
			$this->debug = TRUE;
		}
		$this->blindIcons = isset($this->modTSconfig['properties']['blindIcons']) ? t3lib_div::trimExplode(',', $this->modTSconfig['properties']['blindIcons'], TRUE) : array();

		$this->addToRecentElements();

			// Fill array allAvailableLanguages and currently selected language (from language selector or from outside)
		$this->allAvailableLanguages = $this->getAvailableLanguages(0, true, true, true);
		$this->currentLanguageKey = $this->allAvailableLanguages[$this->MOD_SETTINGS['language']]['ISOcode'];
		$this->currentLanguageUid = $this->allAvailableLanguages[$this->MOD_SETTINGS['language']]['uid'];

			// If no translations exist for this page, set the current language to default (as there won't be a language selector)
		$this->translatedLanguagesArr = $this->getAvailableLanguages($this->id);
		if (count($this->translatedLanguagesArr) == 1) {	// Only default language exists
			$this->currentLanguageKey = 'DEF';
		}

			// Set translator mode if the default langauge is not accessible for the user:
		if (!$GLOBALS['BE_USER']->checkLanguageAccess(0) && !$GLOBALS['BE_USER']->isAdmin())	{
			$this->translatorMode = TRUE;
		}

			// Initialize side bar and wizards:
		$this->sideBarObj =& t3lib_div::getUserObj ('&tx_templavoila_mod1_sidebar','');
		$this->sideBarObj->init($this);
		$this->sideBarObj->position = isset($this->modTSconfig['properties']['sideBarPosition']) ? $this->modTSconfig['properties']['sideBarPosition'] : 'toptabs';

		$this->wizardsObj = t3lib_div::getUserObj('&tx_templavoila_mod1_wizards','');
		$this->wizardsObj->init($this);

			// Initialize TemplaVoila API class:
		$this->apiObj = t3lib_div::makeInstance('tx_templavoila_api', $this->altRoot ? $this->altRoot : 'pages');
		if (isset($this->modSharedTSconfig['properties']['useLiveWorkspaceForReferenceListUpdates'])) {
			$this->apiObj->modifyReferencesInLiveWS(true);
		}
			// Initialize the clipboard
		$this->clipboardObj =& t3lib_div::getUserObj ('&tx_templavoila_mod1_clipboard','');
		$this->clipboardObj->init($this);

			// Initialize the record module
		$this->recordsObj =& t3lib_div::getUserObj ('&tx_templavoila_mod1_records','');
		$this->recordsObj->init($this);
				// Add the localization module if localization is enabled:
		if ($this->alternativeLanguagesDefined()) {
			$this->localizationObj =& t3lib_div::getUserObj ('&tx_templavoila_mod1_localization','');
			$this->localizationObj->init($this);
		}
	}

	/**
	 * Preparing menu content and initializing clipboard and module TSconfig
	 *
	 * @return	void
	 * @access public
	 */
	function menuConfig()	{
		global $TYPO3_CONF_VARS;

			// Prepare array of sys_language uids for available translations:
		$this->translatedLanguagesArr = $this->getAvailableLanguages($this->id);
		$translatedLanguagesUids = array();
		foreach ($this->translatedLanguagesArr as $languageRecord) {
			$translatedLanguagesUids[$languageRecord['uid']] = $languageRecord['title'];
		}

		$this->MOD_MENU = array(
			'tt_content_showHidden' => 1,
			'showOutline' => 1,
			'language' => $translatedLanguagesUids,
			'clip_parentPos' => '',
			'clip' => '',
			'langDisplayMode' => '',
			'recordsView_table' => '',
			'recordsView_start' => '',
			'disablePageStructureInheritance' => ''
		);

			// Hook: menuConfig_preProcessModMenu
		$menuHooks = $this->hooks_prepareObjectsArray('menuConfigClass');
		foreach ($menuHooks as $hookObj) {
			if (method_exists ($hookObj, 'menuConfig_preProcessModMenu')) {
				$hookObj->menuConfig_preProcessModMenu ($this->MOD_MENU, $this);
			}
		}

			// page/be_user TSconfig settings and blinding of menu-items
		$this->MOD_MENU['view'] = t3lib_BEfunc::unsetMenuItems($this->modTSconfig['properties'],$this->MOD_MENU['view'],'menu.function');

		if (!isset($this->modTSconfig['properties']['sideBarEnable'])) {
			$this->modTSconfig['properties']['sideBarEnable'] = 1;
		}

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

		$this->content = '';

			// Access check! The page will show only if there is a valid page and if this page may be viewed by the user
		if (is_array($this->altRoot))	{
			$access = true;
				// get PID of altRoot Element to get pageInfoArr
			$altRootRecord = t3lib_BEfunc::getRecordWSOL ($this->altRoot['table'], $this->altRoot['uid'], 'pid');
			$pageInfoArr = t3lib_BEfunc::readPageAccess ($altRootRecord['pid'], $this->perms_clause);
		} else {
			$pageInfoArr = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
			$access = (intval($pageInfoArr['uid'] > 0));
		}

		if ($access)    {
			if (t3lib_div::_GP('ajaxUnlinkRecord')) {
				$unlinkDestinationPointer = $this->apiObj->flexform_getPointerFromString(t3lib_div::_GP('ajaxUnlinkRecord'));
				$this->apiObj->unlinkElement($unlinkDestinationPointer);
			}

			$this->calcPerms = $this->getCalcPerms($pageInfoArr['uid']);

				// Define the root element record:
			$this->rootElementTable = is_array($this->altRoot) ? $this->altRoot['table'] : 'pages';
			$this->rootElementUid = is_array($this->altRoot) ? $this->altRoot['uid'] : $this->id;
			$this->rootElementRecord = t3lib_BEfunc::getRecordWSOL($this->rootElementTable, $this->rootElementUid, '*');
			if ($this->rootElementRecord['t3ver_swapmode']==0 && $this->rootElementRecord['_ORIG_uid'] ) {
				$this->rootElementUid_pidForContent = $this->rootElementRecord['_ORIG_uid'];
			} else if ($this->rootElementRecord['t3ver_swapmode']==-1 && $this->rootElementRecord['t3ver_oid'] && $this->rootElementRecord['pid'] < 0) {
					// typo3 lacks a proper API to properly detect Offline versions and extract Live Versions therefore this is done by hand
				if ($this->rootElementTable == 'pages') {
					$this->rootElementUid_pidForContent = $this->rootElementRecord['t3ver_oid'];
				} else {
					$liveRec = t3lib_beFunc::getLiveRecord($this->rootElementTable, $this->rootElementUid);
					$this->rootElementUid_pidForContent = $liveRec['pid'];
				}
			}else{
				// If pages use current UID, otherwhise you must use the PID to define the Page ID
				if ($this->rootElementTable == 'pages') {
					$this->rootElementUid_pidForContent = $this->rootElementRecord['uid'];
				}else{
					$this->rootElementUid_pidForContent = $this->rootElementRecord['pid'];
				}
			}

				// Check if we have to update the pagetree:
			if (t3lib_div::_GP('updatePageTree')) {
				t3lib_BEfunc::setUpdateSignal('updatePageTree');
			}

				// Draw the header.
			$this->doc = t3lib_div::makeInstance('template');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->setModuleTemplate('EXT:templavoila/resources/templates/mod1_default.html');
			$this->doc->docType= 'xhtml_trans';

			$this->doc->bodyTagId = 'typo3-mod-php';
			$this->doc->divClass = '';
			$this->doc->form='<form action="'.htmlspecialchars('index.php?'.$this->link_getParameters()).'" method="post">';

				// Add custom styles
			$styleSheetFile = t3lib_extMgm::extPath($this->extKey) . 'mod1/pagemodule_' . substr(TYPO3_version, 0, 3) . '.css';
			if (file_exists($styleSheetFile)) {
				$styleSheetFile = t3lib_extMgm::extRelPath($this->extKey) . 'mod1/pagemodule_' . substr(TYPO3_version, 0, 3) . '.css';
			} else {
				$styleSheetFile = t3lib_extMgm::extRelPath($this->extKey) . 'mod1/pagemodule.css';
			}

			if (isset($this->modTSconfig['properties']['stylesheet'])) {
					$styleSheetFile = $this->modTSconfig['properties']['stylesheet'];
			}

			$this->doc->getPageRenderer()->addCssFile($GLOBALS['BACK_PATH'] . $styleSheetFile);

			if (isset($this->modTSconfig['properties']['stylesheet.'])) {
				foreach($this->modTSconfig['properties']['stylesheet.'] as $file) {
					if(substr($file,0,4) == 'EXT:') {
						list($extKey,$local) = explode('/',substr($file,4),2);
						$filename='';
						if (strcmp($extKey,'') && t3lib_extMgm::isLoaded($extKey) && strcmp($local,''))	{
							$file = t3lib_extMgm::extRelPath($extKey).$local;
						}
					}
					$this->doc->getPageRenderer()->addCssFile($GLOBALS['BACK_PATH'] . $file);
				}
			}

				// Adding classic jumpToUrl function, needed for the function menu. Also, the id in the parent frameset is configured.
			$this->doc->JScode = $this->doc->wrapScriptTags('
				if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
				' . $this->doc->redirectUrls() . '
				var T3_TV_MOD1_BACKPATH = "' . $BACK_PATH . '";
				var T3_TV_MOD1_RETURNURL = "' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) . '";
			');

			$this->doc->getPageRenderer()->loadExtJs();
			$this->doc->JScode .= $this->doc->wrapScriptTags('
				var typo3pageModule = {
					/**
					 * Initialization
					 */
					init: function() {
						typo3pageModule.enableHighlighting();
					},

					/**
					 * This method is used to bind the higlighting function "setActive"
					 * to the mouseenter event and the "setInactive" to the mouseleave event.
					 */
					enableHighlighting: function() {
						Ext.get(\'typo3-docbody\')
							.on(\'mouseover\', typo3pageModule.setActive,typo3pageModule);
					},

					/**
					 * This method is used as an event handler when the
					 * user hovers the a content element.
					 */
					setActive: function(e, t) {
						Ext.select(\'.active\').removeClass(\'active\').addClass(\'inactive\');
						var parent = Ext.get(t).findParent(\'.t3-page-ce\', null, true);
						if (parent) {
							parent.removeClass(\'inactive\').addClass(\'active\');
						}
					}
				}

				Ext.onReady(function() {
					typo3pageModule.init();
				});
			');

				// Preparing context menues
				// this also adds prototype to the list of required libraries
			$CMparts = $this->doc->getContextMenuCode();

			$mod1_file = 'dragdrop' . ($this->debug ? '.js': '-min.js');
			if (method_exists('t3lib_div', 'createVersionNumberedFilename')) {
				$mod1_file = t3lib_div::createVersionNumberedFilename($mod1_file);
			} else {
				$mod1_file .= '?' . filemtime(t3lib_extMgm::extPath('templavoila') . 'mod1/' . $mod1_file);
			}

				//Prototype /Scriptaculous
				// prototype is loaded before, so no need to include twice.
			$this->doc->JScodeLibArray['scriptaculous'] = '<script src="' . $this->doc->backPath . 'contrib/scriptaculous/scriptaculous.js?load=effects,dragdrop,builder" type="text/javascript"></script>';
			$this->doc->JScodeLibArray['templavoila_mod1'] = '<script src="' . $this->doc->backPath . '../' . t3lib_extMgm::siteRelPath('templavoila') . 'mod1/' . $mod1_file . '" type="text/javascript"></script>';

			if (isset($this->modTSconfig['properties']['javascript.']) && is_array($this->modTSconfig['properties']['javascript.'])) {
					// add custom javascript files
				foreach ($this->modTSconfig['properties']['javascript.'] as $key => $value) {
					if ($value) {
						if(substr($value,0,4) == 'EXT:') {
							list($extKey,$local) = explode('/',substr($value,4),2);
							$filename='';
							if (strcmp($extKey,'') && t3lib_extMgm::isLoaded($extKey) && strcmp($local,''))	{
								$value = t3lib_extMgm::extRelPath($extKey).$local;
							}
						}
						$this->doc->JScodeLibArray[$key] = '<script src="' . $this->doc->backPath . htmlspecialchars($value) . '" type="text/javascript"></script>';
					}
				}
			}

				// Set up JS for dynamic tab menu and side bar
			if(tx_templavoila_div::convertVersionNumberToInteger(TYPO3_version) < 4005000) {
				$this->doc->JScode .= $this->doc->getDynTabMenuJScode();
			} else {
				$this->doc->loadJavascriptLib('js/tabmenu.js');
			}

			$this->doc->JScode .= $this->modTSconfig['properties']['sideBarEnable'] ? $this->sideBarObj->getJScode() : '';

				// Setting up support for context menus (when clicking the items icon)
			$this->doc->bodyTagAdditions = $CMparts[1];
			$this->doc->JScode.= $CMparts[0];
			$this->doc->postCode.= $CMparts[2];

			// CSS for drag and drop

			if (t3lib_extMgm::isLoaded('t3skin')) {
				// Fix padding for t3skin in disabled tabs
				$this->doc->inDocStyles .= '
					table.typo3-dyntabmenu td.disabled, table.typo3-dyntabmenu td.disabled_over, table.typo3-dyntabmenu td.disabled:hover { padding-left: 10px; }
				';
			}

			$this->handleIncomingCommands();

				// Start creating HTML output

			$render_editPageScreen = true;


				// Show message if the page is of a special doktype:
			if ($this->rootElementTable == 'pages') {

					// Initialize the special doktype class:
				$specialDoktypesObj =& t3lib_div::getUserObj ('&tx_templavoila_mod1_specialdoktypes','');
				$specialDoktypesObj->init($this);
				$doktype = $this->rootElementRecord['doktype'];

					// if doktype is configured as editType render normal edit view
				$docTypesToEdit = $this->modTSconfig['properties']['additionalDoktypesRenderToEditView'];
				if ($docTypesToEdit && t3lib_div::inList($docTypesToEdit, $doktype)) {
						//Make sure it is editable by page module
					$doktype = self::DOKTYPE_NORMAL_EDIT;
    			}


				$methodName = 'renderDoktype_' . $doktype;
				if (method_exists($specialDoktypesObj, $methodName)) {
					$result = $specialDoktypesObj->$methodName($this->rootElementRecord);
					if ($result !== FALSE) {
						$this->content .= $result;
						if ($GLOBALS['BE_USER']->isPSet($this->calcPerms, 'pages', 'edit')) {
							// Edit icon only if page can be modified by user
							$iconEdit = t3lib_iconWorks::getSpriteIcon('actions-document-open', array('title' => $LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:editPage')));
							$this->content .= '<br/><br/><strong>'.$this->link_edit($iconEdit . $LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:editPage'), 'pages', $this->id) . '</strong>';
						}
						$render_editPageScreen = false; // Do not output editing code for special doctypes!
					}
				}
			}

			if ($render_editPageScreen) {
				$editCurrentPageHTML = '';

					// warn if page renders content from other page
				if ($this->rootElementRecord['content_from_pid']) {
					$contentPage = t3lib_BEfunc::getRecord('pages', intval($this->rootElementRecord['content_from_pid']));
					$title = t3lib_BEfunc::getRecordTitle('pages', $contentPage);
					$linkToPid = 'index.php?id=' . intval($this->rootElementRecord['content_from_pid']);
					$link = '<a href="' . $linkToPid . '">' . htmlspecialchars($title) . ' (PID ' . intval($this->rootElementRecord['content_from_pid']) . ')</a>';
					$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						'',
						sprintf($LANG->getLL('content_from_pid_title'), $link),
						t3lib_FlashMessage::INFO
					);
					$editCurrentPageHTML = '';
					t3lib_FlashMessageQueue::addMessage($flashMessage);
				}
					// Render "edit current page" (important to do before calling ->sideBarObj->render() - otherwise the translation tab is not rendered!
				$editCurrentPageHTML .= $this->render_editPageScreen();

				if (t3lib_div::_GP('ajaxUnlinkRecord')) {
					$this->render_editPageScreen();
					echo $this->render_sidebar();
					exit;
				}

				$this->content .= $editCurrentPageHTML;

					// Create sortables
				if (is_array($this->sortableContainers)) {
					$script = '';
					$sortable_items_json = json_encode ($this->sortableItems);
					$all_items_json = json_encode ($this->allItems);

					$script .=
						'var all_items = ' . $all_items_json . ';' .
						'var sortable_items = ' . $sortable_items_json . ';' .
						'var sortable_removeHidden = ' . ($this->MOD_SETTINGS['tt_content_showHidden'] !== '0' ? 'false;' : 'true;') .
						'var sortable_linkParameters = \'' . $this->link_getParameters() . '\';';

					$containment = '[' . t3lib_div::csvValues($this->sortableContainers, ',', '"') . ']';
					$script .= 'Event.observe(window,"load",function(){';
					foreach ($this->sortableContainers as $s) {
						$script .= 'tv_createSortable(\'' . $s . '\',' . $containment . ');';
					}
					$script .= '});';
					$this->content .= t3lib_div::wrapJS($script);
				}

				$this->doc->divClass = 'tpm-editPageScreen';
			}

		} else {	// No access or no current page uid:
			$this->doc = t3lib_div::makeInstance('template');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->setModuleTemplate('EXT:templavoila/resources/templates/mod1_noaccess.html');
			$this->doc->docType= 'xhtml_trans';

			$this->doc->bodyTagId = 'typo3-mod-php';

			$cmd = t3lib_div::_GP ('cmd');
			switch ($cmd) {

					// Create a new page
				case 'crPage' :
						// Output the page creation form
					$this->content .= $this->wizardsObj->renderWizard_createNewPage (t3lib_div::_GP ('positionPid'));
					break;

					// If no access or if ID == zero
				default:
					$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$LANG->getLL('default_introduction'),
						$LANG->getLL('title'),
						t3lib_FlashMessage::INFO
					);
					$this->content .= $flashMessage->render();
			}
		}

			// Place content inside template
		$content  = $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
		$content .= $this->doc->moduleBody(
			array(),
			$this->getDocHeaderButtons(),
			$this->getBodyMarkers()
		);
		$content .= $this->doc->endPage();

			// Replace content with templated content
		$this->content = $content;
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


	/*************************
	 *
	 * RENDERING UTILITIES
	 *
	 *************************/

	/**
	 * Gets the filled markers that are used in the HTML template.
	 *
	 * @return	array		The filled marker array
	 */
	protected function getBodyMarkers() {

		$bodyMarkers = array(
			'TITLE'		=> $GLOBALS['LANG']->getLL('title'),
		);

		if ($this->modTSconfig['properties']['sideBarEnable'] && $this->sideBarObj->position == 'left') {
			$sidebarMode = 'SIDEBAR_LEFT';
		} elseif($this->modTSconfig['properties']['sideBarEnable']) {
			$sidebarMode = 'SIDEBAR_TOP';
		} else {
			$sidebarMode = 'SIDEBAR_DISABLED';
		}

		$editareaTpl = t3lib_parsehtml::getSubpart($this->doc->moduleTemplate, $sidebarMode);
		if ($editareaTpl) {
			$editareaMarkers = array(
				'TABROW'	=> $this->render_sidebar(),
				'CONTENT'	=> $this->content
			);
			$editareaMarkers['FLASHMESSAGES'] = t3lib_FlashMessageQueue::renderFlashMessages();

			$editareaContent = t3lib_parsehtml::substituteMarkerArray($editareaTpl, $editareaMarkers, '###|###', true);

			$bodyMarkers['EDITAREA'] = $editareaContent;
		} else {
			$bodyMarkers['CONTENT'] = $this->content;
		}
		return $bodyMarkers;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @param	string	Identifier for function of module
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getDocHeaderButtons()	{
		global $TCA, $LANG, $BACK_PATH, $BE_USER;

		$buttons = array(
			'csh' => '',
			'view' => '',
			'history_page' => '',
			'move_page' => '',
			'move_record' => '',
			'new_page' => '',
			'edit_page' => '',
			'record_list' => '',
			'shortcut' => '',
			'cache' => ''
		);

			// View page
		$viewAddGetVars = $this->currentLanguageUid ? '&L=' . $this->currentLanguageUid : '';
		$buttons['view'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::viewOnClick($this->id, $BACK_PATH, t3lib_BEfunc::BEgetRootLine($this->id), '', '', $viewAddGetVars)) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-document-view', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showPage', 1))).
				'</a>';

			// Shortcut
		if ($BE_USER->mayMakeShortcut())	{
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
		}

			// If access to Web>List for user, then link to that module.
		if ($BE_USER->check('modules','web_list'))	{
			if (tx_templavoila_div::convertVersionNumberToInteger(TYPO3_version) < 4005000) {
				$href = $GLOBALS['BACK_PATH'] . 'db_list.php?id=' . $this->id . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
			} else {
				$href = t3lib_BEfunc::getModuleUrl('web_list', array ('id' => $this->id, 'returnUrl' => t3lib_div::getIndpEnv('REQUEST_URI')) );
			}
			$buttons['record_list'] = '<a href="' . htmlspecialchars($href) . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-system-list-open', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showList', 1))).
					'</a>';
		}

		if (!$this->modTSconfig['properties']['disableIconToolbar'])	{

				// Page history
			$buttons['history_page'] = '<a href="#" onclick="' . htmlspecialchars('jumpToUrl(\'' . $BACK_PATH . 'show_rechis.php?element=' . rawurlencode('pages:' . $this->id) . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) . '#latest\');return false;') . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-document-history-open', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:cms/layout/locallang.xml:recordHistory', 1))).
						'</a>';

			if (!$this->translatorMode && $GLOBALS['BE_USER']->isPSet($this->calcPerms, 'pages', 'new'))	{
					// Create new page (wizard)
				$buttons['new_page'] = '<a href="#" onclick="' . htmlspecialchars('jumpToUrl(\'' . $BACK_PATH . 'db_new.php?id=' . $this->id . '&pagesOnly=1&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI') . '&updatePageTree=true') . '\');return false;') . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-page-new', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:cms/layout/locallang.xml:newPage', 1))).
							'</a>';
			}

			if (!$this->translatorMode && $GLOBALS['BE_USER']->isPSet($this->calcPerms, 'pages', 'edit'))	{
					// Edit page properties
				$params='&edit[pages][' . $this->id . ']=edit';
				$buttons['edit_page'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $BACK_PATH)) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-document-open', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:cms/layout/locallang.xml:editPageProperties', 1))).
							'</a>';
					// Move page
				$buttons['move_page'] = '<a href="' . htmlspecialchars($BACK_PATH . 'move_el.php?table=pages&uid=' . $this->id . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-page-move', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:cms/layout/locallang.xml:move_page', 1))).
							'</a>';
			}

			$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_txtemplavoilaM1', 'pagemodule', $BACK_PATH);

			if ($this->id) {
				$cacheUrl = $GLOBALS['BACK_PATH'] . 'tce_db.php?vC=' . $GLOBALS['BE_USER']->veriCode() .
					t3lib_BEfunc::getUrlToken('tceAction') .
					'&redirect=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) .
					'&cacheCmd=' . $this->id;

				$buttons['cache'] = '<a href="' . $cacheUrl . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.clear_cache', TRUE) . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-system-cache-clear') .
					'</a>';
			}
		}

		return $buttons;
	}

	/**
	 * Gets the button to set a new shortcut in the backend (if current user is allowed to).
	 *
	 * @return	string		HTML representiation of the shortcut button
	 */
	protected function getShortcutButton() {
		$result = '';
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$result = $this->doc->makeShortcutIcon('', 'function', $this->MCONF['name']);
		}

		return $result;
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

		$output = '';

			// Fetch the content structure of page:
		$contentTreeData = $this->apiObj->getContentTree($this->rootElementTable, $this->rootElementRecord); // TODO Dima: seems like it does not return <TCEForms> for elements inside sectiions. Thus titles are not visible for these elements!

			// Set internal variable which registers all used content elements:
		$this->global_tt_content_elementRegister = $contentTreeData['contentElementUsage'];

			// Setting localization mode for root element:
		$this->rootElementLangMode = $contentTreeData['tree']['ds_meta']['langDisable'] ? 'disable' : ($contentTreeData['tree']['ds_meta']['langChildren'] ? 'inheritance' : 'separate');
		$this->rootElementLangParadigm = ($this->modTSconfig['properties']['translationParadigm'] == 'free') ? 'free' : 'bound';

			// Create a back button if neccessary:
		if (is_array ($this->altRoot)) {
			$output .= '<div style="text-align:right; width:100%; margin-bottom:5px;"><a href="index.php?id='.$this->id.'">'.
						t3lib_iconWorks::getSpriteIcon('actions-view-go-back', array('title' => htmlspecialchars($LANG->getLL ('goback')))).
						'</a></div>';
		}

			// Hook for content at the very top (fx. a toolbar):
		if (is_array ($TYPO3_CONF_VARS['EXTCONF']['templavoila']['mod1']['renderTopToolbar'])) {
			foreach ($TYPO3_CONF_VARS['EXTCONF']['templavoila']['mod1']['renderTopToolbar'] as $_funcRef) {
				$_params = array ();
				$output .= t3lib_div::callUserFunction($_funcRef, $_params, $this);
			}
		}

			// We show a warning if the user may edit the pagecontent and is not permitted to edit the "content" fields at the same time
		if (!$BE_USER->isAdmin() && $this->modTSconfig['properties']['enableContentAccessWarning']) {
			if (!($this->hasBasicEditRights())) {
				$message = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$LANG->getLL('missing_edit_right_detail'),
					$LANG->getLL('missing_edit_right'),
					t3lib_FlashMessage::INFO
				);
				t3lib_FlashMessageQueue::addMessage($message);
			}
		}

			// Display the content as outline or the nested page structure:
		if (
			($BE_USER->isAdmin() || $this->modTSconfig['properties']['enableOutlineForNonAdmin'])
				&& $this->MOD_SETTINGS['showOutline']
		) {
			$output.= $this->render_outline($contentTreeData['tree']);
		} else {
			$output.= $this->render_framework_allSheets($contentTreeData['tree'], $this->currentLanguageKey);
		}

			// See http://bugs.typo3.org/view.php?id=4821
		$renderHooks = $this->hooks_prepareObjectsArray('render_editPageScreen');
		foreach ($renderHooks as $hookObj)	{
			if (method_exists ($hookObj, 'render_editPageScreen_addContent')) {
				$output .= $hookObj->render_editPageScreen_addContent($this);
			}
		}

			// show sys_notes
		include_once(PATH_typo3 . 'class.db_list.inc');
		$sys_notes = recordList::showSysNotesForPage();
		if ($sys_notes) {
			$output .= '</div><div>'.$this->doc->section($LANG->sL('LLL:EXT:cms/layout/locallang.xml:internalNotes'), str_replace('sysext/sys_note/ext_icon.gif', $GLOBALS['BACK_PATH'] . 'sysext/sys_note/ext_icon.gif', $sys_notes), 0, 1);
		}
		return $output;
	}





	/*******************************************
	 *
	 * Framework rendering functions
	 *
	 *******************************************/

	/**
	 * Rendering the sheet tabs if applicable for the content Tree Array
	 *
	 * @param	array		$contentTreeArr: DataStructure info array (the whole tree)
	 * @param	string		$languageKey: Language key for the display
	 * @param	array		$parentPointer: Flexform Pointer to parent element
	 * @param	array		$parentDsMeta: Meta array from parent DS (passing information about parent containers localization mode)
	 * @return	string		HTML
	 * @access protected
	 * @see	render_framework_singleSheet()
	 */
	function render_framework_allSheets($contentTreeArr, $languageKey='DEF', $parentPointer=array(), $parentDsMeta=array()) {

			// If more than one sheet is available, render a dynamic sheet tab menu, otherwise just render the single sheet framework
		if (is_array($contentTreeArr['sub']) && (count($contentTreeArr['sub'])>1 || !isset($contentTreeArr['sub']['sDEF'])))	{
			$parts = array();
			foreach(array_keys($contentTreeArr['sub']) as $sheetKey)	{

				$this->containedElementsPointer++;
				$this->containedElements[$this->containedElementsPointer] = 0;
				$frContent = $this->render_framework_singleSheet($contentTreeArr, $languageKey, $sheetKey, $parentPointer, $parentDsMeta);

				$parts[] = array(
					'label' => ($contentTreeArr['meta'][$sheetKey]['title'] ? $contentTreeArr['meta'][$sheetKey]['title'] : $sheetKey),	#.' ['.$this->containedElements[$this->containedElementsPointer].']',
					'description' => $contentTreeArr['meta'][$sheetKey]['description'],
					'linkTitle' => $contentTreeArr['meta'][$sheetKey]['short'],
					'content' => $frContent,
				);

				$this->containedElementsPointer--;
			}
			return $this->doc->getDynTabMenu($parts,'TEMPLAVOILA:pagemodule:'.$this->apiObj->flexform_getStringFromPointer($parentPointer));
		} else {
			return $this->render_framework_singleSheet($contentTreeArr, $languageKey, 'sDEF', $parentPointer, $parentDsMeta);
		}
	}

	/**
	 * Renders the display framework of a single sheet. Calls itself recursively
	 *
	 * @param	array		$contentTreeArr: DataStructure info array (the whole tree)
	 * @param	string		$languageKey: Language key for the display
	 * @param	string		$sheet: The sheet key of the sheet which should be rendered
	 * @param	array		$parentPointer: Flexform pointer to parent element
	 * @param	array		$parentDsMeta: Meta array from parent DS (passing information about parent containers localization mode)
	 * @return	string		HTML
	 * @access protected
	 * @see	render_framework_singleSheet()
	 */
	function render_framework_singleSheet($contentTreeArr, $languageKey, $sheet, $parentPointer=array(), $parentDsMeta=array()) {
		global $LANG, $TYPO3_CONF_VARS;

		$elementBelongsToCurrentPage = FALSE;
		$pid = $contentTreeArr['el']['table'] == 'pages' ? $contentTreeArr['el']['uid'] : $contentTreeArr['el']['pid'];
		if ($contentTreeArr['el']['table'] == 'pages' || $contentTreeArr['el']['pid'] == $this->rootElementUid_pidForContent) {
			$elementBelongsToCurrentPage = TRUE;
		} else if ($contentTreeArr['el']['_ORIG_uid']) {
			$record = t3lib_BEfunc::getMovePlaceholder('tt_content', $contentTreeArr['el']['uid']);
			if (is_array($record) && $record['t3ver_move_id'] == $contentTreeArr['el']['uid']) {
				$elementBelongsToCurrentPage = $this->rootElementUid_pidForContent == $record['pid'];
				$pid = $record['pid'];
			}
		}
		$calcPerms = $this->getCalcPerms($pid);

		$canEditElement = $GLOBALS['BE_USER']->isPSet($calcPerms, 'pages', 'editcontent');
		$canEditContent = $GLOBALS['BE_USER']->isPSet($this->calcPerms, 'pages', 'editcontent');

		$elementClass = 'tpm-container-element';
		$elementClass.= ' tpm-container-element-depth-' . $contentTreeArr['depth'];
		$elementClass.= ' tpm-container-element-depth-' . ($contentTreeArr['depth']%2 ? 'odd' : 'even');


		// Prepare the record icon including a content sensitive menu link wrapped around it:
		if (isset($contentTreeArr['el']['iconTag'])) {
			$recordIcon = $contentTreeArr['el']['iconTag'];
		} else {
			$recordIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,$contentTreeArr['el']['icon'],'').' border="0" title="'.htmlspecialchars('['.$contentTreeArr['el']['table'].':'.$contentTreeArr['el']['uid'].']').'" alt="" />';
		}
		$menuCommands = array();
		if ($GLOBALS['BE_USER']->isPSet($calcPerms, 'pages', 'new')) {
			$menuCommands[] = 'new';
		}
		if ($canEditContent) {
			$menuCommands[] = 'copy,cut,pasteinto,pasteafter,delete';
		}
		else {
			$menuCommands[] = 'copy';
		}

		$titleBarLeftButtons = $this->translatorMode ? $recordIcon : (count($menuCommands) == 0 ? $recordIcon : $this->doc->wrapClickMenuOnIcon($recordIcon,$contentTreeArr['el']['table'], $contentTreeArr['el']['uid'], 1,'&amp;callingScriptId='.rawurlencode($this->doc->scriptID), implode(',', $menuCommands)));
		$titleBarLeftButtons.= $this->getRecordStatHookValue($contentTreeArr['el']['table'],$contentTreeArr['el']['uid']);
		unset($menuCommands);

			// Prepare table specific settings:
		switch ($contentTreeArr['el']['table']) {

			case 'pages' :
				$elementTitlebarClass = 'tpm-titlebar-page';
				$elementClass .= ' pagecontainer';
				$titleBarRightButtons = '';
			break;

			case 'tt_content' :

				$elementTitlebarClass = $elementBelongsToCurrentPage ? 'tpm-titlebar' : 'tpm-titlebar-fromOtherPage';
				$elementClass .= ' tpm-content-element tpm-ctype-' . $contentTreeArr['el']['CType'];

				if ($contentTreeArr['el']['isHidden']) {
					$elementClass .= ' tpm-hidden t3-page-ce-hidden';
				}
				if ($contentTreeArr['el']['CType'] == 'templavoila_pi1') {
						//fce
					$elementClass .= ' tpm-fce tpm-fce_' . intval($contentTreeArr['el']['TO']);
				}

				$languageUid = $contentTreeArr['el']['sys_language_uid'];
				$elementPointer = 'tt_content:' . $contentTreeArr['el']['uid'];

				$linkCopy = $this->clipboardObj->element_getSelectButtons($parentPointer, 'copy,ref');

				if (!$this->translatorMode) {

					if ($canEditContent) {
						$iconMakeLocal = t3lib_iconWorks::getSpriteIcon('extensions-templavoila-makelocalcopy', array('title' => $LANG->getLL('makeLocal')));
						$linkMakeLocal = !$elementBelongsToCurrentPage && !in_array('makeLocal', $this->blindIcons) ? $this->link_makeLocal($iconMakeLocal, $parentPointer) : '';
						$linkCut = $this->clipboardObj->element_getSelectButtons($parentPointer, 'cut');
						if(	$this->modTSconfig['properties']['enableDeleteIconForLocalElements'] < 2 ||
							!$elementBelongsToCurrentPage ||
							$this->global_tt_content_elementRegister[$contentTreeArr['el']['uid']] > 1
						) {
							$iconUnlink = t3lib_iconWorks::getSpriteIcon('extensions-templavoila-unlink', array('title' => $LANG->getLL('unlinkRecord')));
							$linkUnlink = !in_array('unlink', $this->blindIcons) ? $this->link_unlink($iconUnlink, $parentPointer, FALSE, FALSE, $elementPointer) : '';
						} else {
							$linkUnlink = '';
						}
					} else {
						$linkMakeLocal = $linkCut = $linkUnlink = '';
					}

					if ($canEditElement && $GLOBALS['BE_USER']->recordEditAccessInternals('tt_content', $contentTreeArr['previewData']['fullRow'])) {
						if (($elementBelongsToCurrentPage || $this->modTSconfig['properties']['enableEditIconForRefElements']) && !in_array('edit', $this->blindIcons)) {
							$iconEdit = t3lib_iconWorks::getSpriteIcon('actions-document-open', array('title' => $LANG->getLL('editrecord')));
							$linkEdit = $this->link_edit($iconEdit, $contentTreeArr['el']['table'],$contentTreeArr['el']['uid'], false,$contentTreeArr['el']['pid']);
						} else {
							$linkEdit = '';
						}
						$linkHide = !in_array('hide', $this->blindIcons) ? $this->icon_hide($contentTreeArr['el']) : '';

						if( $canEditContent && $this->modTSconfig['properties']['enableDeleteIconForLocalElements'] && $elementBelongsToCurrentPage ) {
							$hasForeignReferences = tx_templavoila_div::hasElementForeignReferences($contentTreeArr['el'],$contentTreeArr['el']['pid']);
							$iconDelete = t3lib_iconWorks::getSpriteIcon('actions-edit-delete', array('title' => $LANG->getLL('deleteRecord')));
							$linkDelete = !in_array('delete', $this->blindIcons) ? $this->link_unlink($iconDelete, $parentPointer, TRUE, $hasForeignReferences, $elementPointer) : '';
						} else {
							$linkDelete = '';
						}
					} else {
						$linkDelete = $linkEdit = $linkHide = '';
					}
					$titleBarRightButtons = $linkEdit . $linkHide . $linkCopy . $linkCut . $linkMakeLocal . $linkUnlink . $linkDelete;
				}
				else {
					$titleBarRightButtons = $linkCopy;
				}
			break;
		}

			// Prepare the language icon:
		$languageLabel = htmlspecialchars ($this->allAvailableLanguages[$contentTreeArr['el']['sys_language_uid']]['title']);
		if ($this->allAvailableLanguages[$languageUid]['flagIcon']) {
			$languageIcon = tx_templavoila_icons::getFlagIconForLanguage($this->allAvailableLanguages[$languageUid]['flagIcon'], array('title' => $languageLabel, 'alt' => $languageLabel));
		} else {
			$languageIcon = ($languageLabel && $languageUid ? '[' . $languageLabel . ']' : '');
		}

			// If there was a language icon and the language was not default or [all] and if that langauge is accessible for the user, then wrap the  flag with an edit link (to support the "Click the flag!" principle for translators)
		if ($languageIcon && $languageUid>0 && $GLOBALS['BE_USER']->checkLanguageAccess($languageUid) && $contentTreeArr['el']['table']==='tt_content')	{
			$languageIcon = $this->link_edit($languageIcon, 'tt_content', $contentTreeArr['el']['uid'], TRUE, $contentTreeArr['el']['pid'], 'tpm-langIcon');
		} elseif ($languageIcon) {
			$languageIcon = '<span class="tpm-langIcon">' . $languageIcon . '</span>';
		}

			// Create warning messages if neccessary:
		$warnings = '';

		if (!$this->modTSconfig['properties']['disableReferencedElementNotification'] && !$elementBelongsToCurrentPage) {
			$warnings .= $this->doc->icons(1).' <em>'.htmlspecialchars(sprintf($LANG->getLL('info_elementfromotherpage'), $contentTreeArr['el']['uid'], $contentTreeArr['el']['pid'])).'</em><br />';
		}

		if (!$this->modTSconfig['properties']['disableElementMoreThanOnceWarning'] && $this->global_tt_content_elementRegister[$contentTreeArr['el']['uid']] > 1 && $this->rootElementLangParadigm !='free') {
			$warnings .= $this->doc->icons(2).' <em>'.htmlspecialchars(sprintf($LANG->getLL('warning_elementusedmorethanonce',''), $this->global_tt_content_elementRegister[$contentTreeArr['el']['uid']], $contentTreeArr['el']['uid'])).'</em><br />';
		}

			// Displaying warning for container content (in default sheet - a limitation) elements if localization is enabled:
		$isContainerEl = count($contentTreeArr['sub']['sDEF']);
		if (!$this->modTSconfig['properties']['disableContainerElementLocalizationWarning'] && $this->rootElementLangParadigm !='free' && $isContainerEl && $contentTreeArr['el']['table'] === 'tt_content' && $contentTreeArr['el']['CType'] === 'templavoila_pi1' && !$contentTreeArr['ds_meta']['langDisable'])	{
			if ($contentTreeArr['ds_meta']['langChildren'])	{
				if (!$this->modTSconfig['properties']['disableContainerElementLocalizationWarning_warningOnly']) {
					$warnings .= $this->doc->icons(2).' <em>'.$LANG->getLL('warning_containerInheritance').'</em><br />';
				}
			} else {
				$warnings .= $this->doc->icons(3).' <em>'.$LANG->getLL('warning_containerSeparate').'</em><br />';
			}
		}

			// Preview made:
		$previewContent = $contentTreeArr['ds_meta']['disableDataPreview'] ? '&nbsp;' : $this->render_previewData($contentTreeArr['previewData'], $contentTreeArr['el'], $contentTreeArr['ds_meta'], $languageKey, $sheet);

			// Wrap workspace notification colors:
		if ($contentTreeArr['el']['_ORIG_uid'])	{
			$previewContent = '<div class="ver-element">'.($previewContent ? $previewContent : '<em>[New version]</em>').'</div>';
		}


		$title = t3lib_div::fixed_lgd_cs($contentTreeArr['el']['fullTitle'], $this->previewTitleMaxLen);

			// Finally assemble the table:
		$finalContent = '
			<div class="' . $elementClass . '">
				<div class="tpm-titlebar t3-page-ce-header ' . $elementTitlebarClass .'">
					<div class="t3-row-header">
						<div class="tpm-element-control">
						' . $titleBarRightButtons . '
						</div>
						<div class="tpm-element-title">' .
						$languageIcon .
						$titleBarLeftButtons .
							'<div class="nobr sortable_handle">' .
							($elementBelongsToCurrentPage ? '' : '<em>') . htmlspecialchars($title) . ($elementBelongsToCurrentPage ? '' : '</em>') .
							'</div>
						</div>
					</div>
				</div>
				<div class="tpm-sub-elements">' .
					($warnings ? '<div class="tpm-warnings">' . $warnings . '</div>' : '' ) .
					$this->render_framework_subElements($contentTreeArr, $languageKey, $sheet, $calcPerms) .
					'<div class="tpm-preview">' . $previewContent . '</div>' .
					$this->render_localizationInfoTable($contentTreeArr, $parentPointer, $parentDsMeta) .
				'</div>
			</div>
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
	 * @param	string		$languageKey: Language key for current display
	 * @param	string		$sheet: Key of the sheet we want to render
	 * @param	integer		$calcPerms: Defined the access rights for the enclosing parent
	 * @return	string		HTML output (a table) of the sub elements and some "insert new" and "paste" buttons
	 * @access protected
	 * @see render_framework_allSheets(), render_framework_singleSheet()
	 */
	function render_framework_subElements($elementContentTreeArr, $languageKey, $sheet, $calcPerms=0){
		global $LANG;

		$beTemplate = '';
		$flagRenderBeLayout = false;

		$canEditContent = $GLOBALS['BE_USER']->isPSet($calcPerms, 'pages', 'editcontent');

			// Define l/v keys for current language:
		$langChildren = intval($elementContentTreeArr['ds_meta']['langChildren']);
		$langDisable = intval($elementContentTreeArr['ds_meta']['langDisable']);

			//if page DS and the checkbox is not set use always langDisable in inheritance mode
		if ($elementContentTreeArr['el']['table']=='pages' && $GLOBALS['BE_USER']->isAdmin()) {
			if ($langDisable!=1 && $this->MOD_SETTINGS['disablePageStructureInheritance']!='1' && $langChildren==1) {
				$langDisable=1;
			}
		}

		$lKey = $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l'.$languageKey);
		$vKey = $langDisable ? 'vDEF' : ($langChildren ? 'v'.$languageKey : 'vDEF');

		if (!is_array($elementContentTreeArr['sub'][$sheet]) || !is_array($elementContentTreeArr['sub'][$sheet][$lKey])) return '';

		$output = '';
		$cells = array();

			// get used TO
		if( isset($elementContentTreeArr['el']['TO']) && intval($elementContentTreeArr['el']['TO'])) {
			$toRecord = t3lib_BEfunc::getRecordWSOL('tx_templavoila_tmplobj', intval($elementContentTreeArr['el']['TO']));
		} else {
			$toRecord = $this->apiObj->getContentTree_fetchPageTemplateObject($this->rootElementRecord);
		}

		try{
			$toRepo = t3lib_div::makeInstance('tx_templavoila_templateRepository'); /** @var $toRepo tx_templavoila_templateRepository */
			$to = $toRepo->getTemplateByUid($toRecord['uid']); /** @var $to tx_templavoila_template */
			$beTemplate = $to->getBeLayout();
		} catch (InvalidArgumentException $e) {
			// might happen if uid was not what the Repo expected - that's ok here
		}

		if ($beTemplate === FALSE && isset($elementContentTreeArr['ds_meta']['beLayout'])) {
			$beTemplate = $elementContentTreeArr['ds_meta']['beLayout'];
		}

			// no layout, no special rendering
		$flagRenderBeLayout = $beTemplate? TRUE : FALSE;

			// Traverse container fields:
		foreach($elementContentTreeArr['sub'][$sheet][$lKey] as $fieldID => $fieldValuesContent)	{

			try {
				$newValue = $to->getLocalDataprotValueByXpath('//' . $fieldID . '/tx_templavoila/preview');
				$elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['tx_templavoila']['preview'] = $newValue;
			} catch (UnexpectedValueException $e) {}

			if ( is_array($fieldValuesContent[$vKey]) && (
				$elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['isMapped'] ||
				$elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['type'] == 'no_map'
				) &&
				$elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['tx_templavoila']['preview'] != 'disable'
			) {
				$fieldContent = $fieldValuesContent[$vKey];

				$cellContent = '';

					// Create flexform pointer pointing to "before the first sub element":
				$subElementPointer = array (
					'table' => $elementContentTreeArr['el']['table'],
					'uid' => $elementContentTreeArr['el']['uid'],
					'sheet' => $sheet,
					'sLang' => $lKey,
					'field' => $fieldID,
					'vLang' => $vKey,
					'position' => 0
				);

				if (isset($elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['TCEforms']['config']['maxitems'])) {
					$maxCnt = $elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['TCEforms']['config']['maxitems'];
					$maxItemsReached = is_array($fieldContent['el_list']) && count($fieldContent['el_list']) >= $maxCnt;
				} else {
					$maxItemsReached = FALSE;
				}

				if ($maxItemsReached) {
					$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						'',
						sprintf(
							$GLOBALS['LANG']->getLL('maximal_content_elements'),
							$maxCnt,
							$elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['tx_templavoila']['title']
						),
						t3lib_FlashMessage::INFO
					);
					t3lib_FlashMessageQueue::addMessage($flashMessage);
				}

				$canCreateNew = $canEditContent && !$maxItemsReached;

				$canDragDrop = !$maxItemsReached && $canEditContent &&
								$elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['tx_templavoila']['enableDragDrop'] !== '0' &&
								$this->modTSconfig['properties']['enableDragDrop'] !== '0';

				if (!$this->translatorMode && $canCreateNew)	{
					$cellContent .= $this->link_bottomControls($subElementPointer, $canCreateNew);
				}

					// Render the list of elements (and possibly call itself recursively if needed):
				if (is_array($fieldContent['el_list'])) {
					foreach($fieldContent['el_list'] as $position => $subElementKey)	{
						$subElementArr = $fieldContent['el'][$subElementKey];

						if ((!$subElementArr['el']['isHidden'] || $this->MOD_SETTINGS['tt_content_showHidden'] !== '0') && $this->displayElement($subElementArr))	{

								// When "onlyLocalized" display mode is set and an alternative language gets displayed
							if (($this->MOD_SETTINGS['langDisplayMode'] == 'onlyLocalized') && $this->currentLanguageUid>0)	{

									// Default language element. Subsitute displayed element with localized element
								if (($subElementArr['el']['sys_language_uid']==0) && is_array($subElementArr['localizationInfo'][$this->currentLanguageUid]) && ($localizedUid = $subElementArr['localizationInfo'][$this->currentLanguageUid]['localization_uid']))	{
									$localizedRecord = t3lib_BEfunc::getRecordWSOL('tt_content', $localizedUid, '*');
									$tree = $this->apiObj->getContentTree('tt_content', $localizedRecord);
									$subElementArr = $tree['tree'];
								}
							}
							$this->containedElements[$this->containedElementsPointer]++;

								// Modify the flexform pointer so it points to the position of the curren sub element:
							$subElementPointer['position'] = $position;

							if (!$this->translatorMode) {
								$cellContent .= '<div' . ($canDragDrop ? ' class="sortableItem tpm-element t3-page-ce inactive"' : ' class="tpm-element t3-page-ce inactive"') . ' id="' . $this->addSortableItem ($this->apiObj->flexform_getStringFromPointer ($subElementPointer), $canDragDrop) . '">';
							}

							$cellContent .= $this->render_framework_allSheets($subElementArr, $languageKey, $subElementPointer, $elementContentTreeArr['ds_meta']);

							if (!$this->translatorMode && $canCreateNew)	{
								$cellContent .= $this->link_bottomControls($subElementPointer,$canCreateNew );
							}

							if (!$this->translatorMode) {
								$cellContent .= '</div>';
							}

						} else {
								// Modify the flexform pointer so it points to the position of the curren sub element:
							$subElementPointer['position'] = $position;

							$cellId = $this->addSortableItem ($this->apiObj->flexform_getStringFromPointer ($subElementPointer), $canDragDrop);
							$cellFragment = '<div' . ($canDragDrop ? ' class="sortableItem tpm-element"' : ' class="tpm-element"') . ' id="' . $cellId . '"></div>';

							$cellContent .= $cellFragment;

						}
					}
				}

				$cellIdStr = '';
				$tmpArr = $subElementPointer;
				unset($tmpArr['position']);
				$cellId = $this->addSortableItem ($this->apiObj->flexform_getStringFromPointer ($tmpArr), $canDragDrop);
				$cellIdStr = ' id="' . $cellId . '"';
				if ($canDragDrop) {
					$this->sortableContainers[] = $cellId;
				}


					// Add cell content to registers:
				if ($flagRenderBeLayout==TRUE) {
					$beTemplateCell = '<table width="100%" class="beTemplateCell">
					<tr>
						<td class="bgColor6 tpm-title-cell">' . $LANG->sL($fieldContent['meta']['title'], 1) . '</td>
					</tr>
					<tr>
						<td ' . $cellIdStr . ' class="tpm-content-cell">' . $cellContent . '</td>
					</tr>
					</table>';
					$beTemplate = str_replace('###'.$fieldID.'###', $beTemplateCell, $beTemplate);
				} else {
					$width = round(100 / count($elementContentTreeArr['sub'][$sheet][$lKey]));
					$cells[] = array(
						'id' => $cellId,
						'idStr' => $cellIdStr,
						'title' => $LANG->sL($fieldContent['meta']['title'], 1),
						'width' => $width,
						'content' => $cellContent
					);
				}
			}
		}

		if ($flagRenderBeLayout) {
				//replace lang markers
			$beTemplate = preg_replace_callback(
				"/###(LLL:[\w-\/:]+?\.xml\:[\w-\.]+?)###/",
				create_function(
					'$matches',
					'return $GLOBALS["LANG"]->sL($matches[1], 1);'
				),
				$beTemplate
			);

			// removes not used markers
			$beTemplate = preg_replace("/###field_.*?###/", '', $beTemplate);
			return $beTemplate;
		}

			// Compile the content area for the current element
		if (count ($cells)) {

			$hookObjectsArr = $this->hooks_prepareObjectsArray ('renderFrameworkClass');
			$alreadyRendered = FALSE;
			$output = '';
			foreach($hookObjectsArr as $hookObj) {
				if (method_exists ($hookObj, 'composeSubelements')) {
					$hookObj->composeSubelements($cells, $elementContentTreeArr, $output, $alreadyRendered, $this);
				}
			}

			if (!$alreadyRendered) {
				$headerCells = $contentCells = array();
				foreach ($cells as $cell) {
					$headerCells[] = vsprintf('<td width="%4$d%%" class="bgColor6 tpm-title-cell">%3$s</td>', $cell);
					$contentCells[] = vsprintf('<td %2$s width="%4$d%%" class="tpm-content-cell">%5$s</td>', $cell);
				}

				$output = '
					<table border="0" cellpadding="2" cellspacing="2" width="100%" class="tpm-subelement-table">
						<tr>' . (count($headerCells) ? implode('', $headerCells) : '<td>&nbsp;</td>') . '</tr>
						<tr>' . (count($contentCells) ? implode('', $contentCells) : '<td>&nbsp;</td>') . '</tr>
					</table>
				';
			}
		}

		return $output;
	}





	/*******************************************
	 *
	 * Rendering functions for certain subparts
	 *
	 *******************************************/

	/**
	 * Rendering the preview of content for Page module.
	 *
	 * @param	array		$previewData: Array with data from which a preview can be rendered.
	 * @param	array		$elData: Element data
	 * @param	array		$ds_meta: Data Structure Meta data
	 * @param	string		$languageKey: Current language key (so localized content can be shown)
	 * @param	string		$sheet: Sheet key
	 * @return	string		HTML content
	 */
	function render_previewData($previewData, $elData, $ds_meta, $languageKey, $sheet)	{
		global $LANG;

		$this->currentElementBelongsToCurrentPage = $elData['table'] == 'pages' || $elData['pid'] == $this->rootElementUid_pidForContent;

			// General preview of the row:
		$previewContent = is_array($previewData['fullRow']) && $elData['table']=='tt_content' ? $this->render_previewContent($previewData['fullRow']) : '';

			// Preview of FlexForm content if any:
		if (is_array($previewData['sheets'][$sheet]))	{

				// Define l/v keys for current language:
			$langChildren = intval($ds_meta['langChildren']);
			$langDisable = intval($ds_meta['langDisable']);
			$lKey = $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l'.$languageKey);
			$vKey = $langDisable ? 'vDEF' : ($langChildren ? 'v'.$languageKey : 'vDEF');

			foreach($previewData['sheets'][$sheet] as $fieldData)	{

				if(isset($fieldData['tx_templavoila']['preview']) && $fieldData['tx_templavoila']['preview'] == 'disable') {
					continue;
				}

				$TCEformsConfiguration = $fieldData['TCEforms']['config'];
				$TCEformsLabel = $this->localizedFFLabel($fieldData['TCEforms']['label'], 1);	// title for non-section elements

				if ($fieldData['type']=='array')	{	// Making preview for array/section parts of a FlexForm structure:;
					if(is_array($fieldData['childElements'][$lKey])) {
						$subData = $this->render_previewSubData($fieldData['childElements'][$lKey], $elData['table'], $previewData['fullRow']['uid'], $vKey);
						$previewContent .= $this->link_edit($subData, $elData['table'], $previewData['fullRow']['uid']);
					} else {
						// no child elements found here
					}
				} else {	// Preview of flexform fields on top-level:
					$fieldValue = $fieldData['data'][$lKey][$vKey];

					if ($TCEformsConfiguration['type'] == 'group') {
						if ($TCEformsConfiguration['internal_type'] == 'file')	{
							// Render preview for images:
							$thumbnail = t3lib_BEfunc::thumbCode (array('dummyFieldName'=> $fieldValue), '', 'dummyFieldName', $this->doc->backPath, '', $TCEformsConfiguration['uploadfolder']);
							$previewContent .= '<strong>'.$TCEformsLabel.'</strong> '.$thumbnail.'<br />';
						} elseif ($TCEformsConfiguration['internal_type'] === 'db') {
							if (!$this->renderPreviewDataObjects) {
								$this->renderPreviewDataObjects = $this->hooks_prepareObjectsArray ('renderPreviewDataClass');
							}
							if (isset($this->renderPreviewDataObjects[$TCEformsConfiguration['allowed']])
								&& method_exists($this->renderPreviewDataObjects[$TCEformsConfiguration['allowed']], 'render_previewData_typeDb')) {
								$previewContent .= $this->renderPreviewDataObjects[$TCEformsConfiguration['allowed']]->render_previewData_typeDb ($fieldValue, $fieldData, $previewData['fullRow']['uid'], $elData['table'], $this);
							}
						}
					} else if ($TCEformsConfiguration['type'] != '') {
						// Render for everything else:
						$previewContent .= '<strong>'.$TCEformsLabel.'</strong> '. (!$fieldValue ? '' : $this->link_edit(htmlspecialchars(t3lib_div::fixed_lgd_cs(strip_tags($fieldValue),200)), $elData['table'], $previewData['fullRow']['uid'])).'<br />';
					}
				}
			}
		}

		return $previewContent;
	}

	/**
	 * Merge the datastructure and the related content into a proper tree-structure
	 *
	 * @param array $fieldData
	 * @param string $table
	 * @param integer $uid
	 * @param string $vKey
	 * @return array
	 */
	function render_previewSubData($fieldData, $table, $uid, $vKey) {
		if (!is_array($fieldData)) {
			return;
		}

		$result = '';
		foreach ($fieldData as $fieldKey => $fieldValue) {

			if (isset($fieldValue['config']['tx_templavoila']['preview']) && $fieldValue['config']['tx_templavoila']['preview'] == 'disable') {
				continue;
			}

			if ($fieldValue['config']['type'] == 'array') {
				if (isset($fieldValue['data']['el'])) {
					if ($fieldValue['config']['section']) {
						$result .= '<strong>';
 						$label = ($fieldValue['config']['TCEforms']['label'] ? $fieldValue['config']['TCEforms']['label'] : $fieldValue['config']['tx_templavoila']['title']);
 						$result .= $this->localizedFFLabel($label, 1);
						$result .= '</strong>';
						$result .= '<ul>';
						foreach ($fieldValue['data']['el'] as $i => $sub) {
							$data = $this->render_previewSubData($sub, $table, $uid, $vKey);
							if ($data) {
								$result .= '<li>' . $data . '</li>';
							}
						}
						$result .= '</ul>';
					} else {
						$result .= $this->render_previewSubData($fieldValue['data']['el'], $table, $uid, $vKey);
					}
				}
			} else {
				$label = $data = '';
			 	if (isset($fieldValue['config']['TCEforms']['config']['type']) && $fieldValue['config']['TCEforms']['config']['type'] == 'group') {
					if ($fieldValue['config']['TCEforms']['config']['internal_type'] == 'file')	{
							// Render preview for images:
						$thumbnail = t3lib_BEfunc::thumbCode (array('dummyFieldName'=> $fieldValue['data'][$vKey]), '', 'dummyFieldName', $this->doc->backPath, '', $fieldValue['config']['TCEforms']['config']['uploadfolder']);
						if (isset($fieldValue['config']['TCEforms']['label'])) {
							$label = $this->localizedFFLabel($fieldValue['config']['TCEforms']['label'], 1);
						}
						$data = $thumbnail;
					}
				} else if (isset($fieldValue['config']['TCEforms']['config']['type']) && $fieldValue['config']['TCEforms']['config']['type'] != '') {
						// Render for everything else:
					if (isset($fieldValue['config']['TCEforms']['label'])) {
						$label = $this->localizedFFLabel($fieldValue['config']['TCEforms']['label'], 1);
					}
					$data = (!$fieldValue['data'][$vKey] ? '' : $this->link_edit(htmlspecialchars(t3lib_div::fixed_lgd_cs(strip_tags($fieldValue['data'][$vKey]),200)), $table, $uid));
				} else {
					// @todo no idea what we should to here
				}

				if ($label && $data) {
					$result .= '<strong>' . $label . '</strong> ' . $data . '<br />';
				}
			}
		}
		return $result;
	}

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
			// Hook: renderPreviewContent_preProcess. Set 'alreadyRendered' to true if you provided a preview content for the current cType !
		foreach($hookObjectsArr as $hookObj) {
			if (method_exists ($hookObj, 'renderPreviewContent_preProcess')) {
				$output .= $hookObj->renderPreviewContent_preProcess ($row, 'tt_content', $alreadyRendered, $this);
			}
		}

		if(!$alreadyRendered) {
			if (!$this->renderPreviewObjects) {
				$this->renderPreviewObjects = $this->hooks_prepareObjectsArray ('renderPreviewContent');
			}

			if (isset($this->renderPreviewObjects[$row['CType']]) && method_exists($this->renderPreviewObjects[$row['CType']], 'render_previewContent')) {
				$output .= $this->renderPreviewObjects[$row['CType']]->render_previewContent ($row, 'tt_content', $output, $alreadyRendered, $this);
			} elseif (isset($this->renderPreviewObjects['default']) && method_exists($this->renderPreviewObjects['default'], 'render_previewContent')) {
				$output .= $this->renderPreviewObjects['default']->render_previewContent ($row, 'tt_content', $output, $alreadyRendered, $this);
			} else {
				// nothing is left to render the preview - happens if someone broke the configuration
			}
		}

		return $output;
	}


	/**
	 * Renders a little table containing previews of translated version of the current content element.
	 *
	 * @param	array		$contentTreeArr: Part of the contentTreeArr for the element
	 * @param	string		$parentPointer: Flexform pointer pointing to the current element (from the parent's perspective)
	 * @param	array		$parentDsMeta: Meta array from parent DS (passing information about parent containers localization mode)
	 * @return	string		HTML
	 * @access protected
	 * @see 	render_framework_singleSheet()
	 */
	function render_localizationInfoTable($contentTreeArr, $parentPointer, $parentDsMeta=array()) {
		global $LANG, $BE_USER;

				// LOCALIZATION information for content elements (non Flexible Content Elements)
		$output = '';
		if ($contentTreeArr['el']['table']=='tt_content' && $contentTreeArr['el']['sys_language_uid']<=0)	{

				// Traverse the available languages of the page (not default and [All])
			$tRows=array();
			foreach($this->translatedLanguagesArr as $sys_language_uid => $sLInfo)	{
				if ($this->MOD_SETTINGS['langDisplayMode'] && ($this->currentLanguageUid != $sys_language_uid)) continue;
				if ($sys_language_uid > 0)	{
					$l10nInfo = '';
					$flagLink_begin = $flagLink_end = '';

					switch((string)$contentTreeArr['localizationInfo'][$sys_language_uid]['mode'])	{
						case 'exists':
							$olrow = t3lib_BEfunc::getRecordWSOL('tt_content',$contentTreeArr['localizationInfo'][$sys_language_uid]['localization_uid']);

							$localizedRecordInfo = array(
								'uid' => $olrow['uid'],
								'row' => $olrow,
								'content' => $this->render_previewContent($olrow)
							);

								// Put together the records icon including content sensitive menu link wrapped around it:
							$recordIcon_l10n = t3lib_iconWorks::getSpriteIconForRecord('tt_content', $localizedRecordInfo['row']);
							if (!$this->translatorMode)	{
								$recordIcon_l10n = $this->doc->wrapClickMenuOnIcon($recordIcon_l10n,'tt_content',$localizedRecordInfo['uid'],1,'&amp;callingScriptId='.rawurlencode($this->doc->scriptID), 'new,copy,cut,pasteinto,pasteafter');
							}
							$l10nInfo =
								$this->getRecordStatHookValue('tt_content', $localizedRecordInfo['row']['uid']).
								$recordIcon_l10n .
								htmlspecialchars(t3lib_div::fixed_lgd_cs(strip_tags(t3lib_BEfunc::getRecordTitle('tt_content', $localizedRecordInfo['row'])), $this->previewTitleMaxLen));

							$l10nInfo.= '<br/>'.$localizedRecordInfo['content'];

							list($flagLink_begin, $flagLink_end) = explode('|*|', $this->link_edit('|*|', 'tt_content', $localizedRecordInfo['uid'], TRUE));
							if ($this->translatorMode)	{
								$l10nInfo.= '<br/>'.$flagLink_begin.'<em>'.$LANG->getLL('clickToEditTranslation').'</em>'.$flagLink_end;
							}

								// Wrap workspace notification colors:
							if ($olrow['_ORIG_uid'])	{
								$l10nInfo = '<div class="ver-element">'.$l10nInfo.'</div>';
							}

							$this->global_localization_status[$sys_language_uid][]=array(
								'status' => 'exist',
								'parent_uid' => $contentTreeArr['el']['uid'],
								'localized_uid' => $localizedRecordInfo['row']['uid'],
								'sys_language' => $contentTreeArr['el']['sys_language_uid']
							);
						break;
						case 'localize':

							if (isset($this->modTSconfig['properties']['hideCopyForTranslation'])) {
								$showLocalizationLinks = 0;
							} else {
								if ($this->rootElementLangParadigm =='free')	{
									$showLocalizationLinks = !$parentDsMeta['langDisable'];	// For this paradigm, show localization links only if localization is enabled for DS (regardless of Inheritance and Separate)
								} else {
									$showLocalizationLinks = ($parentDsMeta['langDisable'] || $parentDsMeta['langChildren']);	// Adding $parentDsMeta['langDisable'] here means that the "Create a copy for translation" link is shown only if the parent container element has localization mode set to "Disabled" or "Inheritance" - and not "Separate"!
								}
							}

								// Assuming that only elements which have the default language set are candidates for localization. In case the language is [ALL] then it is assumed that the element should stay "international".
							if ((int)$contentTreeArr['el']['sys_language_uid']===0 && $showLocalizationLinks)	{

									// Copy for language:
								if ($this->rootElementLangParadigm =='free')	{
									$sourcePointerString = $this->apiObj->flexform_getStringFromPointer($parentPointer);
									$onClick = "document.location='index.php?".$this->link_getParameters().'&source='.rawurlencode($sourcePointerString).'&localizeElement='.$sLInfo['ISOcode']."'; return false;";
								} else {
									$params='&cmd[tt_content]['.$contentTreeArr['el']['uid'].'][localize]='.$sys_language_uid;
									$onClick = "document.location='".$GLOBALS['SOBE']->doc->issueCommand($params)."'; return false;";
								}

								$linkLabel = $LANG->getLL('createcopyfortranslation',1).' ('.htmlspecialchars($sLInfo['title']).')';
								$localizeIcon = t3lib_iconWorks::getSpriteIcon('actions-edit-copy', array('title'=>$linkLabel));

								$l10nInfo = '<a class="tpm-clipCopyTranslation" href="#" onclick="'.htmlspecialchars($onClick).'">'.$localizeIcon.'</a>';
								$l10nInfo .= ' <em><a href="#" onclick="'.htmlspecialchars($onClick).'">'.$linkLabel.'</a></em>';
								$flagLink_begin = '<a href="#" onclick="'.htmlspecialchars($onClick).'">';
								$flagLink_end = '</a>';

								$this->global_localization_status[$sys_language_uid][]=array(
									'status' => 'localize',
									'parent_uid' => $contentTreeArr['el']['uid'],
									'sys_language' => $contentTreeArr['el']['sys_language_uid']
								);
							}
						break;
						case 'localizedFlexform':
								// Here we want to show the "Localized FlexForm" information (and link to edit record) _only_ if there are other fields than group-fields for content elements: It only makes sense for a translator to deal with the record if that is the case.
								// Change of strategy (27/11): Because there does not have to be content fields; could be in sections or arrays and if thats the case you still want to localize them! There has to be another way...
							// if (count($contentTreeArr['contentFields']['sDEF']))	{
								list($flagLink_begin, $flagLink_end) = explode('|*|', $this->link_edit('|*|', 'tt_content', $contentTreeArr['el']['uid'], TRUE));
								$l10nInfo = $flagLink_begin.'<em>[' . $LANG->getLL('performTranslation') . ']</em>'.$flagLink_end;
								$this->global_localization_status[$sys_language_uid][]=array(
									'status' => 'flex',
									'parent_uid' => $contentTreeArr['el']['uid'],
									'sys_language' => $contentTreeArr['el']['sys_language_uid']
								);
							// }
						break;
					}

					if ($l10nInfo && $BE_USER->checkLanguageAccess($sys_language_uid))	{
						$tRows[]='
							<tr class="bgColor4">
								<td width="1%">' . $flagLink_begin . tx_templavoila_icons::getFlagIconForLanguage($sLInfo['flagIcon'], array('title' => $sLInfo['title'], 'alt' => $sLInfo['title'])) . $flagLink_end . '</td>
								<td width="99%">' . $l10nInfo . '</td>
							</tr>';
					}
				}
			}

			$output = count($tRows) ? '
				<table border="0" cellpadding="0" cellspacing="1" width="100%" class="lrPadding tpm-localisation-info-table">
					<tr class="bgColor4-20">
						<td colspan="2">'.$LANG->getLL('element_localizations',1).':</td>
					</tr>
					'.implode('',$tRows).'
				</table>
			' : '';
		}
		return $output;
	}






	/*******************************************
	 *
	 * Outline rendering:
	 *
	 *******************************************/

	/**
	 * Rendering the outline display of the page structure
	 *
	 * @param	array		$contentTreeArr: DataStructure info array (the whole tree)
	 * @return	string		HTML
	 */
	function render_outline($contentTreeArr)	{
		global $LANG;

			// Load possible website languages:
		$this->translatedLanguagesArr_isoCodes = array();
		foreach($this->translatedLanguagesArr as $langInfo)	{
			if ($langInfo['ISOcode'])	{
				$this->translatedLanguagesArr_isoCodes['all_lKeys'][]='l'.$langInfo['ISOcode'];
				$this->translatedLanguagesArr_isoCodes['all_vKeys'][]='v'.$langInfo['ISOcode'];
			}
		}

			// Rendering the entries:
		$entries = array();
		$this->render_outline_element($contentTreeArr,$entries);

			// Header of table:
		$output='';
		$output.='<tr class="bgColor5 tableheader">
				<td class="nobr">'.$LANG->getLL('outline_header_title',1).'</td>
				<td class="nobr">'.$LANG->getLL('outline_header_controls',1).'</td>
				<td class="nobr">'.$LANG->getLL('outline_header_status',1).'</td>
				<td class="nobr">'.$LANG->getLL('outline_header_element',1).'</td>
			</tr>';

			// Render all entries:
		$xmlCleanCandidates = FALSE;
		foreach($entries as $entry)	{

				// Create indentation code:
			$indent = '';
			for($a=0;$a<$entry['indentLevel'];$a++)	{
				$indent.='&nbsp;&nbsp;&nbsp;&nbsp;';
			}

				// Create status for FlexForm XML:
				// WARNING: Also this section contains cleaning of XML which is sort of mixing functionality but a quick and easy solution for now.
				// @Robert: How would you like this implementation better? Please advice and I will change it according to your wish!
			$status = '';
			if ($entry['table'] && $entry['uid'])	{
				$flexObj = t3lib_div::makeInstance('t3lib_flexformtools');
				$recRow = t3lib_BEfunc::getRecordWSOL($entry['table'], $entry['uid']);
				if ($recRow['tx_templavoila_flex'])	{

						// Clean XML:
					$newXML = $flexObj->cleanFlexFormXML($entry['table'],'tx_templavoila_flex',$recRow);

						// If the clean-all command is sent AND there is a difference in current/clean XML, save the clean:
					if (t3lib_div::_POST('_CLEAN_XML_ALL') && md5($recRow['tx_templavoila_flex'])!=md5($newXML)) {
						$dataArr = array();
						$dataArr[$entry['table']][$entry['uid']]['tx_templavoila_flex'] = $newXML;

							// Init TCEmain object and store:
						$tce = t3lib_div::makeInstance('t3lib_TCEmain');
						$tce->stripslashes_values=0;
						$tce->start($dataArr,array());
						$tce->process_datamap();

							// Re-fetch record:
						$recRow = t3lib_BEfunc::getRecordWSOL($entry['table'], $entry['uid']);
					}

						// Render status:
					$xmlUrl = '../cm2/index.php?viewRec[table]='.$entry['table'].'&viewRec[uid]='.$entry['uid'].'&viewRec[field_flex]=tx_templavoila_flex';
					if (md5($recRow['tx_templavoila_flex'])!=md5($newXML))	{
						$status = $this->doc->icons(1).'<a href="'.htmlspecialchars($xmlUrl).'">'.$LANG->getLL('outline_status_dirty',1).'</a><br/>';
						$xmlCleanCandidates = TRUE;
					} else {
						$status = $this->doc->icons(-1).'<a href="'.htmlspecialchars($xmlUrl).'">'.$LANG->getLL('outline_status_clean',1).'</a><br/>';
					}
				}
			}

				// Compile table row:
			$class = ($entry['isNewVersion']?'bgColor5':'bgColor4') . ' ' . $entry['elementTitlebarClass'];
			$output.='<tr class="' . $class .'">
					<td class="nobr">'.$indent.$entry['icon'].$entry['flag'].$entry['title'].'</td>
					<td class="nobr">'.$entry['controls'].'</td>
					<td>'.$status.$entry['warnings'].($entry['isNewVersion']?$this->doc->icons(1).'New version!':'').'</td>
					<td class="nobr">'.htmlspecialchars($entry['id'] ? $entry['id'] : $entry['table'].':'.$entry['uid']).'</td>
				</tr>';
		}
		$output = '<table border="0" cellpadding="1" cellspacing="1" class="tpm-outline-table">' . $output . '</table>';

			// Show link for cleaning all XML structures:
		if ($xmlCleanCandidates)	{
			$output.= '<br/>
				'. t3lib_BEfunc::cshItem('_MOD_web_txtemplavoilaM1', 'outline_status_cleanall', $this->doc->backPath).'
				<input type="submit" value="'.$LANG->getLL('outline_status_cleanAll',1).'" name="_CLEAN_XML_ALL" /><br/><br/>
			';
		}

		return $output;
	}

	/**
	 * Rendering a single element in outline:
	 *
	 * @param	array		$contentTreeArr: DataStructure info array (the whole tree)
	 * @param	array		$entries: Entries accumulated in this array (passed by reference)
	 * @param	integer		$indentLevel: Indentation level
	 * @param	array		$parentPointer: Element position in structure
	 * @param	string		$controls: HTML for controls to add for this element
	 * @return	void
	 * @access protected
	 * @see	render_outline_allSheets()
	 */
	function render_outline_element($contentTreeArr, &$entries, $indentLevel=0, $parentPointer=array(), $controls='') {
		global $LANG, $TYPO3_CONF_VARS;

			// Get record of element:
		$elementBelongsToCurrentPage = $contentTreeArr['el']['table'] == 'pages' || $contentTreeArr['el']['pid'] == $this->rootElementUid_pidForContent;

			// Prepare the record icon including a context sensitive menu link wrapped around it:
		if (isset($contentTreeArr['el']['iconTag'])) {
			$recordIcon = $contentTreeArr['el']['iconTag'];
		} else {
			$recordIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,$contentTreeArr['el']['icon'],'').' border="0" title="'.htmlspecialchars('['.$contentTreeArr['el']['table'].':'.$contentTreeArr['el']['uid'].']').'" alt="" />';
		}

		$titleBarLeftButtons = $this->translatorMode ? $recordIcon : $this->doc->wrapClickMenuOnIcon($recordIcon,$contentTreeArr['el']['table'], $contentTreeArr['el']['uid'], 1,'&amp;callingScriptId='.rawurlencode($this->doc->scriptID), 'new,copy,cut,pasteinto,pasteafter,delete');
		$titleBarLeftButtons.= $this->getRecordStatHookValue($contentTreeArr['el']['table'],$contentTreeArr['el']['uid']);

			// Prepare table specific settings:
		switch ($contentTreeArr['el']['table']) {
			case 'pages' :
				$iconEdit = t3lib_iconWorks::getSpriteIcon('actions-document-open', array('title' => $LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:editPage')));
				$titleBarLeftButtons .= $this->translatorMode ? '' : $this->link_edit($iconEdit,$contentTreeArr['el']['table'],$contentTreeArr['el']['uid']);
				$titleBarRightButtons = '';

				$addGetVars = ($this->currentLanguageUid?'&L='.$this->currentLanguageUid:'');
				$viewPageOnClick = 'onclick= "'.htmlspecialchars(t3lib_BEfunc::viewOnClick($contentTreeArr['el']['uid'], $this->doc->backPath, t3lib_BEfunc::BEgetRootLine($contentTreeArr['el']['uid']),'','',$addGetVars)).'"';
				$viewPageIcon = t3lib_iconWorks::getSpriteIcon('actions-document-view', array('title' => $LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.showPage',1)));
				$titleBarLeftButtons .= '<a href="#" '.$viewPageOnClick.'>'.$viewPageIcon.'</a>';
			break;
			case 'tt_content' :
				$languageUid = $contentTreeArr['el']['sys_language_uid'];
				$elementPointer = 'tt_content:' . $contentTreeArr['el']['uid'];

				if ($this->translatorMode)	{
					$titleBarRightButtons = '';
				} else {
						// Create CE specific buttons:
					$iconMakeLocal = t3lib_iconWorks::getSpriteIcon('extensions-templavoila-makelocalcopy', array('title' => $LANG->getLL('makeLocal')));
					$linkMakeLocal = !$elementBelongsToCurrentPage ? $this->link_makeLocal($iconMakeLocal, $parentPointer) : '';
					if(	$this->modTSconfig['properties']['enableDeleteIconForLocalElements'] < 2 ||
						!$elementBelongsToCurrentPage ||
						$this->global_tt_content_elementRegister[$contentTreeArr['el']['uid']] > 1
					) {
						$iconUnlink = t3lib_iconWorks::getSpriteIcon('extensions-templavoila-unlink', array('title' => $LANG->getLL('unlinkRecord')));
						$linkUnlink = $this->link_unlink($iconUnlink, $parentPointer, FALSE);
					} else {
						$linkUnlink ='';
					}
					if( $this->modTSconfig['properties']['enableDeleteIconForLocalElements'] && $elementBelongsToCurrentPage ) {
						$hasForeignReferences = tx_templavoila_div::hasElementForeignReferences($contentTreeArr['el'],$contentTreeArr['el']['pid']);
						$iconDelete = t3lib_iconWorks::getSpriteIcon('actions-edit-delete', array('title' => $LANG->getLL('deleteRecord')));
						$linkDelete = $this->link_unlink($iconDelete, $parentPointer, TRUE, $hasForeignReferences);
					} else {
						$linkDelete = '';
					}
					$iconEdit = t3lib_iconWorks::getSpriteIcon('actions-document-open', array('title' => $LANG->getLL('editrecord')));
					$linkEdit = ($elementBelongsToCurrentPage ? $this->link_edit($iconEdit,$contentTreeArr['el']['table'],$contentTreeArr['el']['uid']) : '');

					$titleBarRightButtons = $linkEdit . $this->clipboardObj->element_getSelectButtons ($parentPointer) . $linkMakeLocal . $linkUnlink . $linkDelete;
				}
			break;
		}

			// Prepare the language icon:

		if ($languageUid > 0) {
			$languageLabel = htmlspecialchars($this->pObj->allAvailableLanguages[$languageUid]['title']);
			if ($this->pObj->allAvailableLanguages[$languageUid]['flagIcon']) {
				$languageIcon = tx_templavoila_icons::getFlagIconForLanguage($this->pObj->allAvailableLanguages[$languageUid]['flagIcon'], array('title' => $languageLabel, 'alt' => $languageLabel));
			} else {
				$languageIcon = '[' . $languageLabel . ']';
			}
		} else {
			$languageIcon = '';
		}

			// If there was a langauge icon and the language was not default or [all] and if that langauge is accessible for the user, then wrap the flag with an edit link (to support the "Click the flag!" principle for translators)
		if ($languageIcon && $languageUid>0 && $GLOBALS['BE_USER']->checkLanguageAccess($languageUid) && $contentTreeArr['el']['table']==='tt_content')	{
			$languageIcon = $this->link_edit($languageIcon, 'tt_content', $contentTreeArr['el']['uid'], TRUE);
		}

			// Create warning messages if neccessary:
		$warnings = '';
		if ($this->global_tt_content_elementRegister[$contentTreeArr['el']['uid']] > 1 && $this->rootElementLangParadigm !='free') {
			$warnings .= '<br/>'.$this->doc->icons(2).' <em>'.htmlspecialchars(sprintf($LANG->getLL('warning_elementusedmorethanonce',''), $this->global_tt_content_elementRegister[$contentTreeArr['el']['uid']], $contentTreeArr['el']['uid'])).'</em>';
		}

			// Displaying warning for container content (in default sheet - a limitation) elements if localization is enabled:
		$isContainerEl = count($contentTreeArr['sub']['sDEF']);
		if (!$this->modTSconfig['properties']['disableContainerElementLocalizationWarning'] && $this->rootElementLangParadigm !='free' && $isContainerEl && $contentTreeArr['el']['table'] === 'tt_content' && $contentTreeArr['el']['CType'] === 'templavoila_pi1' && !$contentTreeArr['ds_meta']['langDisable'])	{
			if ($contentTreeArr['ds_meta']['langChildren'])	{
				if (!$this->modTSconfig['properties']['disableContainerElementLocalizationWarning_warningOnly']) {
					$warnings .= '<br/>'.$this->doc->icons(2).' <b>'.$LANG->getLL('warning_containerInheritance_short').'</b>';
				}
			} else {
				$warnings .= '<br/>'.$this->doc->icons(3).' <b>'.$LANG->getLL('warning_containerSeparate_short').'</b>';
			}
		}

			// Create entry for this element:
		$entries[] = array(
			'indentLevel' => $indentLevel,
			'icon' => $titleBarLeftButtons,
			'title' => ($elementBelongsToCurrentPage?'':'<em>').htmlspecialchars($contentTreeArr['el']['title']).($elementBelongsToCurrentPage ? '' : '</em>'),
			'warnings' => $warnings,
			'controls' => $titleBarRightButtons.$controls,
			'table' => $contentTreeArr['el']['table'],
			'uid' =>  $contentTreeArr['el']['uid'],
			'flag' => $languageIcon,
			'isNewVersion' => $contentTreeArr['el']['_ORIG_uid'] ? TRUE : FALSE,
			'elementTitlebarClass' => (!$elementBelongsToCurrentPage ? 'tpm-elementRef' : 'tpm-element') . ' tpm-outline-level' . $indentLevel
		);


			// Create entry for localizaitons...
		$this->render_outline_localizations($contentTreeArr, $entries, $indentLevel+1);

			// Create entries for sub-elements in all sheets:
		if ($contentTreeArr['sub'])	{
			foreach($contentTreeArr['sub'] as $sheetKey => $sheetInfo)	{
				if (is_array($sheetInfo))	{
					$this->render_outline_subElements($contentTreeArr, $sheetKey, $entries, $indentLevel+1);
				}
			}
		}
	}

	/**
	 * Rendering outline for child-elements
	 *
	 * @param	array		$contentTreeArr: DataStructure info array (the whole tree)
	 * @param	string		$sheet: Which sheet to display
	 * @param	array		$entries: Entries accumulated in this array (passed by reference)
	 * @param	integer		$indentLevel: Indentation level
	 * @return	void
	 * @access protected
	 */
	function render_outline_subElements($contentTreeArr, $sheet, &$entries, $indentLevel)	{
		global $LANG;

			// Define l/v keys for current language:
		$langChildren = intval($contentTreeArr['ds_meta']['langChildren']);
		$langDisable = intval($contentTreeArr['ds_meta']['langDisable']);
		$lKeys = $langDisable ? array('lDEF') : ($langChildren ? array('lDEF') : $this->translatedLanguagesArr_isoCodes['all_lKeys']);
		$vKeys = $langDisable ? array('vDEF') : ($langChildren ? $this->translatedLanguagesArr_isoCodes['all_vKeys'] : array('vDEF'));

			// Traverse container fields:
		foreach($lKeys as $lKey)	{

				// Traverse fields:
			if (is_array($contentTreeArr['sub'][$sheet][$lKey]))	{
				foreach($contentTreeArr['sub'][$sheet][$lKey] as $fieldID => $fieldValuesContent)	{
					foreach($vKeys as $vKey)	{

						if (is_array($fieldValuesContent[$vKey]))	{
							$fieldContent = $fieldValuesContent[$vKey];

								// Create flexform pointer pointing to "before the first sub element":
							$subElementPointer = array (
								'table' => $contentTreeArr['el']['table'],
								'uid' => $contentTreeArr['el']['uid'],
								'sheet' => $sheet,
								'sLang' => $lKey,
								'field' => $fieldID,
								'vLang' => $vKey,
								'position' => 0
							);

							if (!$this->translatorMode)	{
									// "New" and "Paste" icon:
								$newIcon = t3lib_iconWorks::getSpriteIcon('actions-document-new', array('title' => $LANG->getLL ('createnewrecord')));
								$controls = $this->link_new($newIcon, $subElementPointer);
								$controls .= $this->clipboardObj->element_getPasteButtons($subElementPointer);
							}

								// Add entry for lKey level:
							$specialPath = ($sheet!='sDEF'?'<'.$sheet.'>':'').($lKey!='lDEF'?'<'.$lKey.'>':'').($vKey!='vDEF'?'<'.$vKey.'>':'');
							$entries[] = array(
								'indentLevel' => $indentLevel,
								'icon' => '',
								'title' => '<b>'.$LANG->sL($fieldContent['meta']['title'],1).'</b>'.($specialPath ? ' <em>'.htmlspecialchars($specialPath).'</em>' : ''),
								'id' => '<'.$sheet.'><'.$lKey.'><'.$fieldID.'><'.$vKey.'>',
								'controls' => $controls,
								'elementTitlebarClass' => 'tpm-container tpm-outline-level' . $indentLevel,
							);

								// Render the list of elements (and possibly call itself recursively if needed):
							if (is_array($fieldContent['el_list']))	 {
								foreach($fieldContent['el_list'] as $position => $subElementKey)	{
									$subElementArr = $fieldContent['el'][$subElementKey];
									if (!$subElementArr['el']['isHidden'] || $this->MOD_SETTINGS['tt_content_showHidden'] !== '0')	{

											// Modify the flexform pointer so it points to the position of the curren sub element:
										$subElementPointer['position'] = $position;

										if (!$this->translatorMode)	{
												// "New" and "Paste" icon:
											$newIcon = t3lib_iconWorks::getSpriteIcon('actions-document-new', array('title' => $LANG->getLL ('createnewrecord')));
											$controls = $this->link_new($newIcon, $subElementPointer);
											$controls .= $this->clipboardObj->element_getPasteButtons ($subElementPointer);
										}

										$this->render_outline_element($subElementArr, $entries, $indentLevel+1, $subElementPointer, $controls);
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Renders localized elements of a record
	 *
	 * @param	array		$contentTreeArr: Part of the contentTreeArr for the element
	 * @param	array		$entries: Entries accumulated in this array (passed by reference)
	 * @param	integer		$indentLevel: Indentation level
	 * @return	string		HTML
	 * @access protected
	 * @see 	render_framework_singleSheet()
	 */
	function render_outline_localizations($contentTreeArr, &$entries, $indentLevel) {
		global $LANG, $BE_USER;

		if ($contentTreeArr['el']['table']=='tt_content' && $contentTreeArr['el']['sys_language_uid']<=0)	{

				// Traverse the available languages of the page (not default and [All])
			foreach($this->translatedLanguagesArr as $sys_language_uid => $sLInfo)	{
				if ($sys_language_uid > 0 && $BE_USER->checkLanguageAccess($sys_language_uid))	{
					switch((string)$contentTreeArr['localizationInfo'][$sys_language_uid]['mode'])	{
						case 'exists':

								// Get localized record:
							$olrow = t3lib_BEfunc::getRecordWSOL('tt_content',$contentTreeArr['localizationInfo'][$sys_language_uid]['localization_uid']);

								// Put together the records icon including content sensitive menu link wrapped around it:
							$recordIcon_l10n = $this->getRecordStatHookValue('tt_content', $olrow['uid']).
								t3lib_iconWorks::getSpriteIconForRecord('tt_content', $olrow);
							if (!$this->translatorMode)	{
								$recordIcon_l10n = $this->doc->wrapClickMenuOnIcon($recordIcon_l10n,'tt_content',$olrow['uid'],1,'&amp;callingScriptId='.rawurlencode($this->doc->scriptID), 'new,copy,cut,pasteinto,pasteafter');
							}

							list($flagLink_begin, $flagLink_end) = explode('|*|', $this->link_edit('|*|', 'tt_content', $olrow['uid'], TRUE));

								// Create entry for this element:
							$entries[] = array(
								'indentLevel' => $indentLevel,
								'icon' => $recordIcon_l10n,
								'title' => t3lib_BEfunc::getRecordTitle('tt_content', $olrow),
								'table' => 'tt_content',
								'uid' =>  $olrow['uid'],
								'flag' => $flagLink_begin . tx_templavoila_icons::getFlagIconForLanguage($sLInfo['flagIcon'], array('title' => $sLInfo['title'], 'alt' => $sLInfo['title'])) . $flagLink_end,
								'isNewVersion' => $olrow['_ORIG_uid'] ? TRUE : FALSE,
							);
						break;
					}
				}
			}
		}
	}

	/**
	 * Renders the sidebar, including the relevant hook objects
	 *
	 * @return string
	 */
	protected function render_sidebar() {
			// Hook for adding new sidebars or removing existing
		$sideBarHooks = $this->hooks_prepareObjectsArray('sideBarClass');
		foreach ($sideBarHooks as $hookObj)	{
			if (method_exists($hookObj, 'main_alterSideBar')) {
				$hookObj->main_alterSideBar($this->sideBarObj, $this);
			}
		}
		return $this->sideBarObj->render();
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
	 * @param	boolean		$forced: By default the link is not shown if translatorMode is set, but with this boolean it can be forced anyway.
	 * @param	integer		$usePid: ...
	 * @param	string		$linkClass: css class to use for regular content elements
	 * @return	string		HTML anchor tag containing the label and the correct link
	 * @access protected
	 */
	function link_edit($label, $table, $uid, $forced=FALSE, $usePid=0, $linkClass='')	{
		if ($label) {
			$class = $linkClass ? $linkClass : 'tpm-edit';
			$pid = $table == 'pages' ? $uid : $usePid;
			$calcPerms = $pid == 0 ? $this->calcPerms : $this->getCalcPerms($pid);

			if (($table == 'pages' && ($calcPerms & 2) ||
				$table != 'pages' && ($calcPerms & 16)) &&
				(!$this->translatorMode || $forced))	{
					if($table == "pages" &&	 $this->currentLanguageUid) {
						return '<a class="tpm-pageedit" href="index.php?'.$this->link_getParameters().'&amp;editPageLanguageOverlay='.$this->currentLanguageUid.'">'.$label.'</a>';
					} else {
						$onClick = t3lib_BEfunc::editOnClick('&edit['.$table.']['.$uid.']=edit', $this->doc->backPath);
						return '<a class="' . $class . '" href="#" onclick="'.htmlspecialchars($onClick).'">'.$label.'</a>';
					}
				} else {
					return $label;
				}
		}
		return '';
	}

	/**
	 * Returns an HTML link for hiding
	 *
	 * @param	string		$label: The label (or image)
	 * @param	string		$table: The table, fx. 'tt_content'
	 * @param	integer		$uid: The uid of the element to be hidden/unhidden
	 * @param	boolean		$forced: By default the link is not shown if translatorMode is set, but with this boolean it can be forced anyway.
	 * @return	string		HTML anchor tag containing the label and the correct link
	 * @access protected
	 */
	function icon_hide($el) {
		global $LANG;

		$iconOptions = array(
			'title' => ($el['table'] == 'pages' ? $LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:hidePage') : $LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:hide'))
		);
		$hideIcon = t3lib_iconWorks::getSpriteIcon('actions-edit-hide', $iconOptions);

		$iconOptions = array(
			'title' => ($el['table'] == 'pages' ? $LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:unHidePage') : $LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:unHide'))
		);
		$unhideIcon = t3lib_iconWorks::getSpriteIcon('actions-edit-unhide', $iconOptions);

		if ($el['isHidden'])
			$label = $unhideIcon;
		else
			$label = $hideIcon;

		return $this->link_hide($label, $el['table'], $el['uid'], $el['isHidden'], false, $el['pid']);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$label: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @param	[type]		$hidden: ...
	 * @param	[type]		$forced: ...
	 * @param	integer			$usePid: ...
	 * @return	[type]		...
	 */
	function link_hide($label, $table, $uid, $hidden, $forced=FALSE, $usePid = 0) {
		if ($label) {

			$pid = $table == 'pages' ? $uid : $usePid;
			$calcPerms = $pid == 0 ? $this->calcPerms : $this->getCalcPerms($pid);

			if (($table == 'pages' && ($calcPerms & 2) ||
				 $table != 'pages' && ($calcPerms & 16)) &&
				(!$this->translatorMode || $forced))	{

					$workspaceRec = t3lib_BEfunc::getWorkspaceVersionOfRecord($GLOBALS['BE_USER']->workspace, $table, $uid);
					$workspaceId =  ($workspaceRec['uid'] > 0) ? $workspaceRec['uid'] : $uid;
					if ($table == "pages" && $this->currentLanguageUid) {
						$params = '&data['.$table.']['.$workspaceId.'][hidden]=' . (1 - $hidden);
					//	return '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') . '">'.$label.'</a>';
					} else {
						$params = '&data['.$table.']['.$workspaceId.'][hidden]=' . (1 - $hidden);
					//	return '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') . '">'.$label.'</a>';

						/* the commands are indipendent of the position,
						 * so sortable doesn't need to update these and we
						 * can safely use '#'
						 */
						if ($hidden)
							return '<a href="#" class="tpm-hide" onclick="sortable_unhideRecord(this, \'' . htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($params, -1)) . '\');">' . $label . '</a>';
						else
							return '<a href="#" class="tpm-hide" onclick="sortable_hideRecord(this, \'' . htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($params, -1)) . '\');">' . $label . '</a>';
					}
				} else {
					return $label;
				}
		}
		return '';
	}

	/**
	 * Returns an HTML link for browse for record
	 *
	 * @param	string		$label: The label (or image)
	 * @param	array		$parentPointer: Flexform pointer defining the parent element of the new record
	 * @return	string		HTML anchor tag containing the label and the correct link
	 * @access protected
	 */
	function link_browse($label, $parentPointer)	{

		$parameters =
			$this->link_getParameters().
			'&pasteRecord=ref' .
			'&source=' . rawurlencode('###').
			'&destination=' . rawurlencode($this->apiObj->flexform_getStringFromPointer($parentPointer));
		$onClick =
			'browserPos = this;' .
			'setFormValueOpenBrowser(\'db\',\'browser[communication]|||tt_content\');'.
			'return false;';

		return '<a href="#" class="tpm-browse" rel="index.php?' . htmlspecialchars($parameters) . '" onclick="' . htmlspecialchars($onClick) . '">' . $label . '</a>';
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
			'&amp;parentRecord='.rawurlencode($this->apiObj->flexform_getStringFromPointer($parentPointer)).
			'&amp;returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
		return '<a class="tpm-new" href="' . $this->newContentWizScriptPath . '?' . $parameters . '">' . $label . '</a>';
	}

	/**
	 * Returns an HTML link for unlinking a content element. Unlinking means that the record still exists but
	 * is not connected to any other content element or page.
	 *
	 * @param	string		$label: The label
	 * @param	array		$unlinkPointer: Flexform pointer pointing to the element to be unlinked
	 * @param	boolean		$realDelete: If set, the record is not just unlinked but deleted!
	 * @param   boolean     $foreignReferences: If set, the record seems to have references on other pages
	 * @return	string		HTML anchor tag containing the label and the unlink-link
	 * @access protected
	 */
	function link_unlink($label, $unlinkPointer, $realDelete=FALSE, $foreignReferences=FALSE, $elementPointer='')	{

		$unlinkPointerString = $this->apiObj->flexform_getStringFromPointer ($unlinkPointer);
		$encodedUnlinkPointerString = rawurlencode($unlinkPointerString);

		if ($realDelete)	{
			$LLlabel = $foreignReferences ? 'deleteRecordWithReferencesMsg' : 'deleteRecordMsg';
			return '<a class="tpm-delete" href="index.php?' . $this->link_getParameters() . '&amp;deleteRecord=' . $encodedUnlinkPointerString . '" onclick="' . htmlspecialchars('return confirm(' . $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL($LLlabel)) . ');') . '">' . $label . '</a>';
		} else {
			return '<a class="tpm-unlink" href="javascript:'.htmlspecialchars('if (confirm(' . $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL('unlinkRecordMsg')) . '))') . 'sortable_unlinkRecord(\'' . $encodedUnlinkPointerString . '\',\'' . $this->addSortableItem ($unlinkPointerString) . '\',\'' . $elementPointer . '\');">' . $label . '</a>';
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

		return '<a class="tpm-makeLocal" href="index.php?'.$this->link_getParameters().'&amp;makeLocalRecord='.rawurlencode($this->apiObj->flexform_getStringFromPointer($makeLocalPointer)).'" onclick="'.htmlspecialchars('return confirm('.$LANG->JScharCode($LANG->getLL('makeLocalMsg')).');').'">'.$label.'</a>';
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


	/**
	 * Render the bottom controls which (might) contain the new, browse and paste-buttons
	 * which sit below each content element
	 *
	 * @param array $elementPointer
	 * @param boolean $canCreateNew
	 * @return string
	 */
	protected function link_bottomControls($elementPointer, $canCreateNew) {

		$output = '<span class="tpm-bottom-controls">';

			// "New" icon:
		if ($canCreateNew && !in_array('new', $this->blindIcons)) {
			$iconOptions = array(
				'title' => $GLOBALS['LANG']->getLL ('createnewrecord')
			);
			$newIcon = t3lib_iconWorks::getSpriteIcon('actions-document-new', $iconOptions);
			$output .= $this->link_new($newIcon, $elementPointer);
		}

			// "Browse Record" icon
		if ($canCreateNew && !in_array('browse', $this->blindIcons)) {
			$iconOptions = array(
				'title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.browse_db'),
				'class' => 'browse'
			);
			$newIcon = t3lib_iconWorks::getSpriteIcon('actions-insert-record', $iconOptions);
			$output .= $this->link_browse($newIcon, $elementPointer);
		}

			// "Paste" icon
		if ($canCreateNew) {
			$output .= '<span class="sortablePaste">' .
				$this->clipboardObj->element_getPasteButtons ($elementPointer) .
				'&nbsp;</span>';
		}

		$output .= '</span>';

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
	 * 'makeLocalRecord', 'localizeElement', 'createNewPageTranslation' and 'editPageLanguageOverlay'
	 *
	 * @return	void
	 * @access protected
	 */
	function handleIncomingCommands() {

		$possibleCommands = array ('createNewRecord', 'unlinkRecord', 'deleteRecord','pasteRecord', 'makeLocalRecord', 'localizeElement', 'createNewPageTranslation', 'editPageLanguageOverlay');

		$hooks = $this->hooks_prepareObjectsArray('handleIncomingCommands');

		foreach ($possibleCommands as $command) {
			if (($commandParameters = t3lib_div::_GP($command)) != '') {

				$redirectLocation = 'index.php?'.$this->link_getParameters();

				$skipCurrentCommand = false;
				foreach ($hooks as $hookObj) {
					if (method_exists ($hookObj, 'handleIncomingCommands_preProcess')) {
						$skipCurrentCommand = $skipCurrentCommand || $hookObj->handleIncomingCommands_preProcess ($command, $redirectLocation, $this);
					}
				}

				if ($skipCurrentCommand) {
					continue;
				}

				switch ($command) {

					case 'createNewRecord':
							// Historically "defVals" has been used for submitting the preset row data for the new element, so we still support it here:
						$defVals = t3lib_div::_GP('defVals');
						$newRow = is_array ($defVals['tt_content']) ? $defVals['tt_content'] : array();

							// Create new record and open it for editing
						$destinationPointer = $this->apiObj->flexform_getPointerFromString($commandParameters);
						$newUid = $this->apiObj->insertElement($destinationPointer, $newRow);
						if( $this->editingOfNewElementIsEnabled( $newRow['tx_templavoila_ds'], $newRow['tx_templavoila_to'] ) ) {
								// TODO If $newUid==0, than we could create new element. Need to handle it...
							$redirectLocation = $GLOBALS['BACK_PATH'].'alt_doc.php?edit[tt_content]['.$newUid.']=edit&returnUrl='.rawurlencode(t3lib_extMgm::extRelPath('templavoila').'mod1/index.php?'.$this->link_getParameters());
						}
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
							case 'ref':		list(,$uid) = explode(':', t3lib_div::_GP('source'));
											$this->apiObj->referenceElementByUid ($uid, $destinationPointer);
							break;

						}
					break;

					case 'makeLocalRecord':
						$sourcePointer = $this->apiObj->flexform_getPointerFromString ($commandParameters);
						$this->apiObj->copyElement ($sourcePointer, $sourcePointer);
						$this->apiObj->unlinkElement ($sourcePointer);
					break;

					case 'localizeElement':
						$sourcePointer = $this->apiObj->flexform_getPointerFromString (t3lib_div::_GP('source'));
						$this->apiObj->localizeElement ($sourcePointer, $commandParameters);
					break;

					case 'createNewPageTranslation':
							// Create parameters and finally run the classic page module for creating a new page translation
						$params = '&edit[pages_language_overlay]['.intval (t3lib_div::_GP('pid')).']=new&overrideVals[pages_language_overlay][doktype]=' . intval(t3lib_div::_GP('doktype')) . '&overrideVals[pages_language_overlay][sys_language_uid]='.intval($commandParameters);
						$returnUrl = '&returnUrl='.rawurlencode(t3lib_extMgm::extRelPath('templavoila').'mod1/index.php?'.$this->link_getParameters());
						$redirectLocation = $GLOBALS['BACK_PATH'].'alt_doc.php?'.$params.$returnUrl;
					break;

					case 'editPageLanguageOverlay':
							// Look for pages language overlay record for language:
						$sys_language_uid = intval($commandParameters);
						$params = '';
						if ($sys_language_uid != 0) {
							// Edit overlay record
							list($pLOrecord) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
									'*',
									'pages_language_overlay',
									'pid='.intval($this->id).' AND sys_language_uid='.$sys_language_uid.
										t3lib_BEfunc::deleteClause('pages_language_overlay').
										t3lib_BEfunc::versioningPlaceholderClause('pages_language_overlay')
								);
							if ($pLOrecord) {
								t3lib_beFunc::workspaceOL('pages_language_overlay', $pLOrecord);
								if (is_array($pLOrecord))	{
									$params = '&edit[pages_language_overlay]['.$pLOrecord['uid'].']=edit';
								}
							}
						}
						else {
							// Edit default language (page properties)
							// No workspace overlay because we already on this page
							$params = '&edit[pages]['.intval($this->id).']=edit';
						}
						if ($params) {
							$returnUrl = '&returnUrl='.rawurlencode(t3lib_extMgm::extRelPath('templavoila').'mod1/index.php?'.$this->link_getParameters());
							$redirectLocation = $GLOBALS['BACK_PATH'].'alt_doc.php?'.$params.$returnUrl;	//.'&localizationMode=text';
						}
					break;
				}

				foreach ($hooks as $hookObj) {
					if (method_exists ($hookObj, 'handleIncomingCommands_postProcess')) {
						$hookObj->handleIncomingCommands_postProcess ($command, $redirectLocation, $this);
					}
				}

			}
		}

		if (isset ($redirectLocation)) {
			header('Location: '.t3lib_div::locationHeaderUrl($redirectLocation));
		}
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

		$output = array();
		$excludeHidden = $BE_USER->isAdmin() ? '1=1' : 'sys_language.hidden=0';

		if ($id) {
			$excludeHidden .= ' AND pages_language_overlay.deleted=0';
			$res = $TYPO3_DB->exec_SELECTquery(
				'DISTINCT sys_language.*, pages_language_overlay.hidden as PLO_hidden, pages_language_overlay.title as PLO_title',
				'pages_language_overlay,sys_language',
				'pages_language_overlay.sys_language_uid=sys_language.uid AND pages_language_overlay.pid='.intval($id).' AND '.$excludeHidden,
				'',
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
				'title' => strlen ($this->modSharedTSconfig['properties']['defaultLanguageLabel']) ? $this->modSharedTSconfig['properties']['defaultLanguageLabel'] : $LANG->getLL('defaultLanguage'),
				'ISOcode' => 'DEF',
				'flagIcon' => strlen($this->modSharedTSconfig['properties']['defaultLanguageFlag']) ? $this->modSharedTSconfig['properties']['defaultLanguageFlag'] : null
			);
		}

		if ($setMulti) {
			$output[-1]=array(
				'uid' => -1,
				'title' => $LANG->getLL ('multipleLanguages'),
				'ISOcode' => 'DEF',
				'flagIcon' => 'multiple',
			);
		}

		while(TRUE == ($row = $TYPO3_DB->sql_fetch_assoc($res)))	{
			t3lib_BEfunc::workspaceOL('sys_language', $row);
			if ($id) {
				$table = 'pages_language_overlay';
				$enableFields = t3lib_BEfunc::BEenableFields ( $table );
				if (trim($enableFields) == 'AND') {
					$enableFields = '';
				}
				$enableFields .= t3lib_BEfunc::deleteClause($table);

					// Selecting overlay record:
				$resP = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',
					'pages_language_overlay',
					'pid='.intval($id).'
						AND sys_language_uid='.intval($row['uid']),
					'',
					'',
					'1'
				);
				$pageRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resP);
				$GLOBALS['TYPO3_DB']->sql_free_result($resP);
				t3lib_BEfunc::workspaceOL('pages_language_overlay', $pageRow);
				$row['PLO_hidden'] = $pageRow['hidden'];
				$row['PLO_title'] = $pageRow['title'];
			}
			$output[$row['uid']]=$row;

			if ($row['static_lang_isocode'])	{
				$staticLangRow = t3lib_BEfunc::getRecord('static_languages',$row['static_lang_isocode'],'lg_iso_2');
				if ($staticLangRow['lg_iso_2']) {
					$output[$row['uid']]['ISOcode'] = $staticLangRow['lg_iso_2'];
				}
			}
			if (strlen($row['flag'])) {
				$output[$row['uid']]['flagIcon'] = $row['flag'];
			}

			if ($onlyIsoCoded && !$output[$row['uid']]['ISOcode']) unset($output[$row['uid']]);

			$disableLanguages = t3lib_div::trimExplode(',', $this->modSharedTSconfig['properties']['disableLanguages'], 1);
			foreach ($disableLanguages as $language) {
					// $language is the uid of a sys_language
				unset($output[$language]);
			}
		}

		return $output;
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
			foreach ($TYPO3_CONF_VARS['EXTCONF']['templavoila']['mod1'][$hookName] as $key => $classRef) {
				$hookObjectsArr[$key] = &t3lib_div::getUserObj ($classRef);
			}
		}
		return $hookObjectsArr;
	}

	/**
	 * Checks if translation to alternative languages can be applied to this page.
	 *
	 * @return	boolean		<code>true</code> if alternative languages exist
	 */
	function alternativeLanguagesDefined() {
		return count($this->allAvailableLanguages) > 2;
	}

	/**
	 * Defines if an element is to be displayed in the TV page module (could be filtered out by language settings)
	 *
	 * @param	array		Sub element array
	 * @return	boolean		Display or not
	 */
	function displayElement($subElementArr)	{
			// Don't display when "selectedLanguage" is choosen
		$displayElement = !$this->MOD_SETTINGS['langDisplayMode'];
			// Set to true when current language is not an alteranative (in this case display all elements)
		$displayElement |= ($this->currentLanguageUid<=0);
			// When language of CE is ALL or default display it.
		$displayElement |= ($subElementArr['el']['sys_language_uid']<=0);
			// Display elements which have their language set to the currently displayed language.
		$displayElement |= ($this->currentLanguageUid==$subElementArr['el']['sys_language_uid']);

		if ($this->visibleContentHookObjects === NULL) {
			$this->visibleContentHookObjects = $this->hooks_prepareObjectsArray('visibleContentClass');
		}
		foreach ($this->visibleContentHookObjects as $hookObj) {
			if (method_exists ($hookObj, 'displayElement')) {
				$hookObj->displayElement ($subElementArr, $displayElement, $this);
			}
		}
		return $displayElement;
	}

	/**
	 * Returns label, localized and converted to current charset. Label must be from FlexForm (= always in UTF-8).
	 *
	 * @param	string	$label	Label
	 * @param	boolean	$hsc	<code>true</code> if HSC required
	 * @return	string	Converted label
	 */
	function localizedFFLabel($label, $hsc) {

		$charset = $GLOBALS['LANG']->origCharSet;
		if ($GLOBALS['LANG']->origCharSet != $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']) {
			$GLOBALS['LANG']->origCharSet = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'];
		}
		if (substr($label, 0, 4) === 'LLL:') {
			$label = $GLOBALS['LANG']->sL($label);
		}
		$result = htmlspecialchars($label, $hsc);
		$GLOBALS['LANG']->origCharSet = $charset;
		return $result;
	}

	function getRecordStatHookValue($table,$id)	{
			// Call stats information hook
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks']))	{
			$stat='';
			$_params = array($table,$id);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef)	{
				$stat.=t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
			return $stat;
		}
	}

	/**
	 * Adds element to the list of recet elements
	 *
	 * @return	void
	 */
	protected function addToRecentElements() {
		// Add recent element
		$ser = t3lib_div::_GP('ser');
		if ($ser) {

			// Include file required to unserialization
			t3lib_div::requireOnce(t3lib_extMgm::extPath('templavoila', 'newcewizard/model/class.tx_templavoila_contentelementdescriptor.php'));

			$obj = @unserialize(base64_decode($ser));

			if ($obj instanceof tx_templavoila_contentElementDescriptor) {
				$data = (array)@unserialize($GLOBALS['BE_USER']->uc['tx_templavoila_recentce']);
				// Find this element
				$pos = false;
				for ($i = 0; $i < count($data); $i++) {
					// Notice: must be "==", not "==="!
					if ($data[$i] == $obj) {
						$pos = $i;
						break;
					}
				}
				if ($pos !== 0) {
					if ($pos !== false) {
						// Remove it
						array_splice($data, $pos, 1);
					}
					else {
						// Check if there are more than necessary elements
						if (count($data) >= 10) {
							$data = array_slice($data, 0, 9);
						}
					}
					array_unshift($data, $obj);
					$GLOBALS['BE_USER']->uc['tx_templavoila_recentce'] = serialize($data);
					$GLOBALS['BE_USER']->writeUC();
				}
			}
		}
	}

	/**
	 * Checks whether the datastructure for a new FCE contains the noEditOnCreation meta configuration
	 *
	 * @param integer $dsUid	uid of the datastructure we want to check
	 * @param integer $toUid	uid of the tmplobj we want to check
	 * @return boolean
	 */
	protected function editingOfNewElementIsEnabled($dsUid, $toUid) {
		if ( !strlen($dsUid) || !intval($toUid)) {
			return TRUE;
		}
		$editingEnabled = TRUE;
		try {
			$toRepo = t3lib_div::makeInstance('tx_templavoila_templateRepository');
			$to = $toRepo->getTemplateByUid($toUid);
			$xml = $to->getLocalDataprotArray();
			if (isset($xml['meta']['noEditOnCreation'])) {
				$editingEnabled = $xml['meta']['noEditOnCreation'] != 1;
			}
		} catch(InvalidArgumentException $e) {
			//  might happen if uid was not what the Repo expected - that's ok here
		}
		return $editingEnabled;
	}

	/**
	 * Adds a flexPointer to the stack of sortable items for drag&drop
	 *
	 * @param string   the sourcePointer for the referenced element
	 * @param boolean  determine wether the element should be used for drag and drop
	 * @return string the key for the related html-element
	 */
	protected function addSortableItem($pointerStr, $addToSortables=true) {
		$key = 'item' . md5($pointerStr);
		if ($addToSortables) {
			$this->sortableItems[$key] = $pointerStr;
		}
		$this->allItems[$key] = $pointerStr;
		return $key;
	}

	/**
	 *
	 * @param 	integer	$pid
	 * @return	integer
	 */
	protected function getCalcPerms($pid) {
		if (!isset(self::$calcPermCache[$pid])) {
			$row = t3lib_BEfunc::getRecordWSOL('pages', $pid);
			$calcPerms = $GLOBALS['BE_USER']->calcPerms($row);
			if (!$this->hasBasicEditRights('pages', $row)) {
					// unsetting the "edit content" right - which is 16
				$calcPerms = $calcPerms & ~16;
			}
			self::$calcPermCache[$pid] = $calcPerms;
		}
		return self::$calcPermCache[$pid];
	}

	/**
	 * @param  $table
	 * @param  $record
	 * @return bool
	 */
	protected function hasBasicEditRights($table = null,array $record = null) {


		$hasEditRights = FALSE;

		if ($table == null) {
			$table = $this->rootElementTable;
		}

		if (empty($record)) {
			$record = $this->rootElementRecord;
		}

		if ($GLOBALS['BE_USER']->isAdmin()) {
			$hasEditRights = TRUE;
		} else {
			$id = $record[($table == 'pages' ? 'uid' : 'pid')];
			$pageRecord = t3lib_BEfunc::getRecordWSOL('pages', $id);

			$mayEditPage = $GLOBALS['BE_USER']->doesUserHaveAccess($pageRecord, 16);
			$mayModifyTable = t3lib_div::inList($GLOBALS['BE_USER']->groupData['tables_modify'], $table);
			$mayEditContentField = t3lib_div::inList($GLOBALS['BE_USER']->groupData['non_exclude_fields'], $table . ':tx_templavoila_flex');
			$hasEditRights = $mayEditPage && $mayModifyTable && $mayEditContentField;
		}
		return $hasEditRights;
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
