<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003, 2004, 2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * templavoila module cm1
 *
 * $Id$
 *
 * @author		Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @co-author	Robert Lemke <robert@typo3.org>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  125: class tx_templavoila_cm1 extends t3lib_SCbase
 *  189:     function menuConfig()
 *  209:     function main()
 *  230:     function printContent()
 *
 *              SECTION: MODULE mode
 *  259:     function main_mode()
 *  352:     function renderFile()
 *  793:     function renderDSO()
 *  928:     function renderTO()
 * 1096:     function renderTO_editProcessing(&$dataStruct,$row,$theFile)
 *
 *              SECTION: Mapper functions
 * 1317:     function renderHeaderSelection($displayFile,$currentHeaderMappingInfo,$showBodyTag,$htmlAfterDSTable='')
 * 1382:     function renderTemplateMapper($displayFile,$path,$dataStruct=array(),$currentMappingInfo=array(),$htmlAfterDSTable='')
 * 1570:     function drawDataStructureMap($dataStruct,$mappingMode=0,$currentMappingInfo=array(),$pathLevels=array(),$optDat=array(),$contentSplittedByMapping=array(),$level=0,$tRows=array(),$formPrefix='',$path='',$mapOK=1)
 * 1786:     function drawDataStructureMap_editItem($formPrefix,$key,$value,$level)
 * 1905:     function drawDataStructureMap_editItem_editTypeExtra($type, $formFieldName, $curValue)
 *
 *              SECTION: Helper-functions for File-based DS/TO creation
 * 1955:     function substEtypeWithRealStuff(&$elArray,$v_sub=array(),$scope = 0)
 * 2236:     function substEtypeWithRealStuff_contentInfo($content)
 *
 *              SECTION: Various helper functions
 * 2283:     function getDataStructFromDSO($datString,$file='')
 * 2299:     function linkForDisplayOfPath($title,$path)
 * 2319:     function linkThisScript($array=array())
 * 2342:     function makeIframeForVisual($file,$path,$limitTags,$showOnly,$preview=0)
 * 2358:     function explodeMappingToTagsStr($mappingToTags,$unsetAll=0)
 * 2376:     function unsetArrayPath(&$dataStruct,$ref)
 * 2393:     function cleanUpMappingInfoAccordingToDS(&$currentMappingInfo,$dataStruct)
 * 2412:     function findingStorageFolderIds()
 *
 *              SECTION: DISPLAY mode
 * 2458:     function main_display()
 * 2503:     function displayFileContentWithMarkup($content,$path,$relPathFix,$limitTags)
 * 2539:     function displayFileContentWithPreview($content,$relPathFix)
 * 2575:     function displayFrameError($error)
 * 2602:     function cshItem($table,$field,$BACK_PATH,$wrap='',$onlyIconMode=FALSE, $styleAttrib='')
 * 2615:     function lipsumLink($formElementName)
 *
 * TOTAL FUNCTIONS: 29
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:templavoila/cm1/locallang.xml');
require_once (PATH_t3lib.'class.t3lib_scbase.php');


require_once(t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_htmlmarkup.php');
require_once(PATH_t3lib.'class.t3lib_tcemain.php');


if (t3lib_extMgm::isLoaded('lorem_ipsum'))	{
	// Dmitry: this dependency on lorem_ipsum is bad :(
	// http://bugs.typo3.org/view.php?id=3691
	require_once(t3lib_extMgm::extPath('lorem_ipsum').'class.tx_loremipsum_wiz.php');
	if (t3lib_extMgm::isLoaded('rtehtmlarea'))	{
		require_once(t3lib_extMgm::extPath('rtehtmlarea').'class.tx_rtehtmlarea_base.php');
	}
}


/*************************************
 *
 * Short glossary;
 *
 * DS - Data Structure
 * DSO - Data Structure Object (table record)
 * TO - Template Object
 *
 ************************************/







/**
 * Class for controlling the TemplaVoila module.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @co-author	Robert Lemke <robert@typo3.org>
 * @package TYPO3
 * @subpackage tx_templavoila
 */
class tx_templavoila_cm1 extends t3lib_SCbase {

		// Static:
	var $theDisplayMode = '';	// Set to ->MOD_SETTINGS[]
	var $head_markUpTags = array(
			// Block elements:
		'title' => array(),
		'script' => array(),
		'style' => array(),
			// Single elements:

		'link' => array('single'=>1),
		'meta' => array('single'=>1),
	);
	var $extKey = 'templavoila';			// Extension key of this module
	var $dsTypes;

		// Internal, dynamic:
	var $markupFile = '';		// Used to store the name of the file to mark up with a given path.
	var $markupObj = '';
	var $elNames = array();
	var $editDataStruct=0;		// Setting whether we are editing a data structure or not.
	var $storageFolders = array();	// Storage folders as key(uid) / value (title) pairs.
	var $storageFolders_pidList=0;	// The storageFolders pids imploded to a comma list including "0"

		// GPvars:
	var $mode;					// Looking for "&mode", which defines if we draw a frameset (default), the module (mod) or display (display)

		// GPvars for MODULE mode
	var $displayFile = '';		// (GPvar "file", shared with DISPLAY mode!) The file to display, if file is referenced directly from filelist module. Takes precedence over displayTable/displayUid
	var $displayTable = '';		// (GPvar "table") The table from which to display element (Data Structure object [tx_templavoila_datastructure], template object [tx_templavoila_tmplobj])
	var $displayUid = '';		// (GPvar "uid") The UID to display (from ->displayTable)
	var $displayPath = '';		// (GPvar "htmlPath") The "HTML-path" to display from the current file
	var $returnUrl = '';		// (GPvar "returnUrl") Return URL if the script is supplied with that.

		// GPvars for MODULE mode, specific to mapping a DS:
	var $_preview;
	var $htmlPath;
	var $mapElPath;
	var $doMappingOfPath;
	var $showPathOnly;
	var $mappingToTags;
	var $DS_element;
	var $DS_cmd;
	var $fieldName;

		// GPvars for MODULE mode, specific to creating a DS:
	var $_load_ds_xml_content;
	var $_load_ds_xml_to;
	var $_saveDSandTO_TOuid;
	var $_saveDSandTO_title;
	var $_saveDSandTO_type;
	var $_saveDSandTO_pid;

		// GPvars for DISPLAY mode:
	var $show;					// Boolean; if true no mapping-links are rendered.
	var $preview;				// Boolean; if true, the currentMappingInfo preview data is merged in
	var $limitTags;				// String, list of tags to limit display by
	var $path;					// HTML-path to explode in template.

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig()    {
		$this->MOD_MENU = Array (
			'displayMode' => array	(
				'explode' => 'Mode: Exploded Visual',
#				'_' => 'Mode: Overlay',
				'source' => 'Mode: HTML Source ',
#				'borders' => 'Mode: Table Borders',
			),
			'showDSxml' => ''
		);
		parent::menuConfig();
	}

	/**
	 * Returns an abbrevation and a description for a given element-type.
	 *
	 * @return	array
	 */
	function dsTypeInfo($conf) {
			// Icon:
		if ($conf['type']=='section')
			return $this->dsTypes['sc'];

		if ($conf['type']=='array') {
			if (!$conf['section'])
				return $this->dsTypes['co'];
			return $this->dsTypes['sc'];
		}

		if ($conf['type']=='attr')
			return $this->dsTypes['at'];

		if ($conf['type']=='no_map')
			return $this->dsTypes['no'];

		return $this->dsTypes['el'];
	}

	/**
	 * Main function, distributes the load between the module and display modes.
	 * "Display" mode is when the exploded template file is shown in an IFRAME
	 *
	 * @return	void
	 */
	function main()	{
			// Setting GPvars:
		$this->mode = t3lib_div::_GP('mode');

			// Selecting display or module mode:
		switch((string)$this->mode)	{
			case 'display':
				$this->main_display();
			break;
			default:
				$this->main_mode();
			break;
		}
	}

	/**
	 * Prints module content.
	 * Is only used in case of &mode = "mod" since both "display" mode and frameset is outputted + exiting before this is called.
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content.=$this->doc->middle();
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Makes a context-free xml-string from an array.
	 *
	 * @return	string
	 */
	function flattenarray($array, $pfx = '') {
		if (!is_array($array)) {
			if (is_string($array))
				return $array;
			else
				return '';
		}

		return str_replace("<>\n", '', str_replace("</>", '', t3lib_div::array2xml($array,'',-1,'',0,array('useCDATA' => 1))));
	}

	/**
	 * Makes an array from a context-free xml-string.
	 *
	 * @return	array
	 */
	function unflattenarray($string) {
		if (!is_string($string) || !trim($string)) {
			if (is_array($string))
				return $string;
			else
				return array();
		}

		return t3lib_div::xml2array('<grouped>' . $string . '</grouped>');
	}

	/**
	 * Merges two arrays recursively and "binary safe" (integer keys are overridden as well), overruling similar values in the first array ($arr0) with the values of the second array ($arr1)
	 * In case of identical keys, ie. keeping the values of the second.
	 * Usage: 0
	 *
	 * @param	array		First array
	 * @param	array		Second array, overruling the first array
	 * @param	boolean		If set, keys that are NOT found in $arr0 (first array) will not be set. Thus only existing value can/will be overruled from second array.
	 * @param	boolean		If set, values from $arr1 will overrule if they are empty or zero. Default: true
	 * @param	boolean		If set, anything will override arrays in $arr0
	 * @return	array		Resulting array where $arr1 values has overruled $arr0 values
	 */
	function array_merge_recursive_overrule($arr0,$arr1,$notAddKeys=0,$includeEmtpyValues=true,$kill=true) {
		foreach ($arr1 as $key => $val) {
			if(is_array($arr0[$key])) {
				if (is_array($arr1[$key]))	{
					$arr0[$key] = $this->array_merge_recursive_overrule($arr0[$key],$arr1[$key],$notAddKeys,$includeEmtpyValues,$kill);
				}
				else if ($kill) {
					if ($includeEmtpyValues || $val) {
						$arr0[$key] = $val;
					}
				}
			} else {
				if ($notAddKeys) {
					if (isset($arr0[$key])) {
						if ($includeEmtpyValues || $val) {
							$arr0[$key] = $val;
						}
					}
				} else {
					if ($includeEmtpyValues || $val) {
						$arr0[$key] = $val;
					}
				}
			}
		}
		reset($arr0);
		return $arr0;
	}

	/*****************************************
	 *
	 * MODULE mode
	 *
	 *****************************************/

	/**
	 * Main function of the MODULE. Write the content to $this->content
	 * There are three main modes:
	 * - Based on a file reference, creating/modifying a DS/TO
	 * - Based on a Template Object uid, remapping
	 * - Based on a Data Structure uid, selecting a Template Object to map.
	 *
	 * @return	void
	 */
	function main_mode()	{
		global $LANG, $BACK_PATH;

			// Draw the header.
		$this->doc = t3lib_div::makeInstance('noDoc');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->docType = 'xhtml_trans';
		$this->doc->inDocStylesArray[]='
			DIV.typo3-noDoc { width: 98%; margin: 0 0 0 0; }
			DIV.typo3-noDoc H2 { width: 100%; }
			TABLE#c-mapInfo {margin-top: 10px; margin-bottom: 5px; }
			TABLE#c-mapInfo TR TD {padding-right: 20px;}
			select option.pagetemplate {background-image:url(../icon_pagetemplate.gif);background-repeat: no-repeat; background-position: 5px 50%; padding: 1px 0 3px 24px; -webkit-background-size: 0;}
			select option.fce {background-image:url(../icon_fce_ce.png);background-repeat: no-repeat; background-position: 5px 50%; padding: 1px 0 3px 24px; -webkit-background-size: 0;}
		';

			// Add custom styles
		$this->doc->styleSheetFile2 = t3lib_extMgm::extRelPath($this->extKey)."cm1/styles.css";

			// General GPvars for module mode:
		$this->displayFile = t3lib_div::_GP('file');
		$this->displayTable = t3lib_div::_GP('table');
		$this->displayUid = t3lib_div::_GP('uid');
		$this->displayPath = t3lib_div::_GP('htmlPath');
		$this->returnUrl = t3lib_div::_GP('returnUrl');

			// GPvars specific to the DS listing/table and mapping features:
		$this->_preview = t3lib_div::_GP('_preview');
		$this->mapElPath = t3lib_div::_GP('mapElPath');
		$this->doMappingOfPath = t3lib_div::_GP('doMappingOfPath');
		$this->showPathOnly = t3lib_div::_GP('showPathOnly');
		$this->mappingToTags = t3lib_div::_GP('mappingToTags');
		$this->DS_element = t3lib_div::_GP('DS_element');
		$this->DS_cmd = t3lib_div::_GP('DS_cmd');
		$this->fieldName = t3lib_div::_GP('fieldName');

			// GPvars specific for DS creation from a file.
		$this->_load_ds_xml_content = t3lib_div::_GP('_load_ds_xml_content');
		$this->_load_ds_xml_to = t3lib_div::_GP('_load_ds_xml_to');
		$this->_saveDSandTO_TOuid = t3lib_div::_GP('_saveDSandTO_TOuid');
		$this->_saveDSandTO_title = t3lib_div::_GP('_saveDSandTO_title');
		$this->_saveDSandTO_type = t3lib_div::_GP('_saveDSandTO_type');
		$this->_saveDSandTO_pid = t3lib_div::_GP('_saveDSandTO_pid');
		$this->DS_element_DELETE = t3lib_div::_GP('DS_element_DELETE');

			// Finding Storage folder:
		$this->findingStorageFolderIds();

			// Setting up form-wrapper:
		$this->doc->form='<form action="'.$this->linkThisScript(array()).'" method="post" name="pageform">';

			// JavaScript
		$this->doc->JScode.= $this->doc->wrapScriptTags('
			script_ended = 0;
			function jumpToUrl(URL)	{	//
				document.location = URL;
			}
			function updPath(inPath)	{	//
				document.location = "'.t3lib_div::linkThisScript(array('htmlPath'=>'','doMappingOfPath'=>1)).'&htmlPath="+top.rawurlencode(inPath);
			}
		').$this->doc->getDynTabMenuJScode();

			// Setting up the context sensitive menu:
		$CMparts = $this->doc->getContextMenuCode();
		$this->doc->bodyTagAdditions = $CMparts[1];
		$this->doc->JScode.=$CMparts[0];
		$this->doc->postCode.= $CMparts[2];

		$this->content.=$this->doc->startPage($LANG->getLL('title'));
		$this->content.=$this->doc->header($LANG->getLL('title'));
		$this->content.=$this->doc->spacer(5);

		if ($this->returnUrl)	{
		$this->content.='<a href="'.htmlspecialchars($this->returnUrl).'" class="typo3-goBack">'.
			'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/goback.gif','width="14" height="14"').' alt="" />'.
			$LANG->sL('LLL:EXT:lang/locallang_misc.xml:goBack',1).
			'</a><br/>';
		}

			// Icons
		$this->dsTypes = array(
			'sc' => $LANG->getLL('dsTypes_section') . ': ',
			'co' => $LANG->getLL('dsTypes_container') . ': ',
			'el' => $LANG->getLL('dsTypes_attribute') . ': ',
			'at' => $LANG->getLL('dsTypes_element') . ': ',
			'no' => $LANG->getLL('dsTypes_notmapped') . 'Not : ');
		foreach ($this->dsTypes as $id => $title) {
			$this->dsTypes[$id] = array(
					// abbrevation
				$id,
					// descriptive title
				$title,
					// image-path
				t3lib_iconWorks::skinImg($this->doc->backPath,t3lib_extMgm::extRelPath('templavoila').'cm1/item_'.$id.'.gif','width="24" height="16" border="0" style="margin-right: 5px;"'),
					// background-path
				t3lib_iconWorks::skinImg($this->doc->backPath,t3lib_extMgm::extRelPath('templavoila').'cm1/item_'.$id.'.gif','',1)
			);

				// information
			$this->dsTypes[$id][4] = @getimagesize($this->dsTypes[$id][3]);
		}

			// Render content, depending on input values:
		if ($this->displayFile)	{	// Browsing file directly, possibly creating a template/data object records.
			$this->renderFile();
		} elseif ($this->displayTable=='tx_templavoila_datastructure') {	// Data source display
			$this->renderDSO();
		} elseif ($this->displayTable=='tx_templavoila_tmplobj') {	// Data source display
			$this->renderTO();
		}

			// Add spacer:
		$this->content.=$this->doc->spacer(10);
	}

	/**
	 * Renders the display of DS/TO creation directly from a file
	 *
	 * @return	void
	 */
	function renderFile()	{
		global $TYPO3_DB;

		if (@is_file($this->displayFile) && t3lib_div::getFileAbsFileName($this->displayFile))		{

				// Converting GPvars into a "cmd" value:
			$cmd = '';
			if (t3lib_div::_GP('_load_ds_xml'))	{	// Loading DS from XML or TO uid
				$cmd = 'load_ds_xml';
			} elseif (t3lib_div::_GP('_clear'))	{	// Resetting mapping/DS
				$cmd = 'clear';
			} elseif (t3lib_div::_GP('_saveDSandTO'))	{	// Saving DS and TO to records.
				$cmd = 'saveDSandTO';
			} elseif (t3lib_div::_GP('_updateDSandTO'))	{	// Updating DS and TO
				$cmd = 'updateDSandTO';
			} elseif (t3lib_div::_GP('_showXMLDS'))	{	// Showing current DS as XML
				$cmd = 'showXMLDS';
			} elseif (t3lib_div::_GP('_preview'))	{	// Previewing mappings
				$cmd = 'preview';
			} elseif (t3lib_div::_GP('_save_data_mapping'))	{	// Saving mapping to Session
				$cmd = 'save_data_mapping';
			} elseif (t3lib_div::_GP('_updateDS')) {
				$cmd = 'updateDS';
			} elseif (t3lib_div::_GP('DS_element_DELETE'))	{
				$cmd = 'DS_element_DELETE';
			} elseif (t3lib_div::_GP('_saveScreen'))	{
				$cmd = 'saveScreen';
			} elseif (t3lib_div::_GP('_loadScreen'))	{
				$cmd = 'loadScreen';
			}

				// Init settings:
			$this->editDataStruct=1;	// Edit DS...
			$content='';
			$msg = array();

				// Checking Storage Folder PID:
			if (!count($this->storageFolders))	{
				$msg[] = '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/icon_fatalerror.gif', 'width="18" height="16"').' border="0" align="top" class="absmiddle" alt="" /><strong>'.$GLOBALS['LANG']->getLL('error').'</strong> '.$GLOBALS['LANG']->getLL('errorNoStorageFolder');
			}

				// Session data
			if ($cmd=='clear')	{	// Reset session data:
				$sesDat = array();
				$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
			} else {	// Get session data:
				$sesDat = $GLOBALS['BE_USER']->getSessionData($this->MCONF['name'].'_mappingInfo');
			}

				// Loading DS from either XML or a Template Object (containing reference to DS)
			if ($cmd=='load_ds_xml' && ($this->_load_ds_xml_content || $this->_load_ds_xml_to))	{
				$to_uid = $this->_load_ds_xml_to;
				if ($to_uid)	{
					$toREC = t3lib_BEfunc::getRecordWSOL('tx_templavoila_tmplobj',$to_uid);
					$tM = unserialize($toREC['templatemapping']);
					$sesDat=array();
					$sesDat['currentMappingInfo'] = $tM['MappingInfo'];
					$sesDat['currentMappingInfo_head'] = $tM['MappingInfo_head'];
					$dsREC = t3lib_BEfunc::getRecordWSOL('tx_templavoila_datastructure',$toREC['datastructure']);

					$ds = t3lib_div::xml2array($dsREC['dataprot']);
					$sesDat['dataStruct'] = $sesDat['autoDS'] = $ds; // Just set $ds, not only its ROOT! Otherwise <meta> will be lost.
					$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
				} else {
					$ds = t3lib_div::xml2array($this->_load_ds_xml_content);
					$sesDat=array();
					$sesDat['dataStruct'] = $sesDat['autoDS'] = $ds;
					$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
				}
			}

				// Setting Data Structure to value from session data - unless it does not exist in which case a default structure is created.
			$dataStruct = is_array($sesDat['autoDS']) ? $sesDat['autoDS'] : array(
				'meta' => array(
					'langDisable' => '1',
				),
				'ROOT' => array (
					'tx_templavoila' => array (
						'title' => 'ROOT',
						'description' => $GLOBALS['LANG']->getLL('rootDescription'),
					),
					'type' => 'array',
					'el' => array()
				)
			);

				// Setting Current Mapping information to session variable content OR blank if none exists.
			$currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : array();
			$this->cleanUpMappingInfoAccordingToDS($currentMappingInfo,$dataStruct);	// This will clean up the Current Mapping info to match the Data Structure.

				// CMD switch:
			switch($cmd)	{
					// Saving incoming Mapping Data to session data:
				case 'save_data_mapping':
					$inputData = t3lib_div::_GP('dataMappingForm',1);
					if (is_array($inputData))	{
						$sesDat['currentMappingInfo'] = $currentMappingInfo = $this->array_merge_recursive_overrule($currentMappingInfo,$inputData);
						$sesDat['dataStruct'] = $dataStruct;
						$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
					}
				break;
					// Saving incoming Data Structure settings to session data:
				case 'updateDS':
					$inDS = t3lib_div::_GP('autoDS',1);
					if (is_array($inDS))	{
						$sesDat['dataStruct'] = $sesDat['autoDS'] = $dataStruct = $this->array_merge_recursive_overrule($dataStruct,$inDS);
						$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
					}
				break;
					// If DS element is requested for deletion, remove it and update session data:
				case 'DS_element_DELETE':
					$ref = explode('][',substr($this->DS_element_DELETE,1,-1));
					$this->unsetArrayPath($dataStruct,$ref);
					$sesDat['dataStruct'] = $sesDat['autoDS'] = $dataStruct;
					$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
				break;
			}

				// Creating $templatemapping array with cached mapping content:
			if (t3lib_div::inList('showXMLDS,saveDSandTO,updateDSandTO',$cmd))	{

					// Template mapping prepared:
				$templatemapping=array();
				$templatemapping['MappingInfo'] = $currentMappingInfo;
				if (isset($sesDat['currentMappingInfo_head'])) {
					$templatemapping['MappingInfo_head'] = $sesDat['currentMappingInfo_head'];
				}

					// Getting cached data:
				reset($dataStruct);
				$fileContent = t3lib_div::getUrl($this->displayFile);
				$htmlParse = t3lib_div::makeInstance('t3lib_parsehtml');
				$relPathFix = dirname(substr($this->displayFile,strlen(PATH_site))).'/';
				$fileContent = $htmlParse->prefixResourcePath($relPathFix,$fileContent);
				$this->markupObj = t3lib_div::makeInstance('tx_templavoila_htmlmarkup');
				$contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($fileContent,$currentMappingInfo);
				$templatemapping['MappingData_cached'] = $contentSplittedByMapping['sub']['ROOT'];

				list($html_header) =  $this->markupObj->htmlParse->getAllParts($htmlParse->splitIntoBlock('head',$fileContent),1,0);
				$this->markupObj->tags = $this->head_markUpTags;	// Set up the markupObject to process only header-section tags:

				if (isset($templatemapping['MappingInfo_head'])) {
					$h_currentMappingInfo=array();
					$currentMappingInfo_head = $templatemapping['MappingInfo_head'];
					if (is_array($currentMappingInfo_head['headElementPaths']))	{
						foreach($currentMappingInfo_head['headElementPaths'] as $kk => $vv)	{
							$h_currentMappingInfo['el_'.$kk]['MAP_EL'] = $vv;
						}
					}

					$contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($html_header,$h_currentMappingInfo);
					$templatemapping['MappingData_head_cached'] = $contentSplittedByMapping;

						// Get <body> tag:
					$reg='';
					eregi('<body[^>]*>',$fileContent,$reg);
					$templatemapping['BodyTag_cached'] = $currentMappingInfo_head['addBodyTag'] ? $reg[0] : '';
				}

				if ($cmd != 'showXMLDS') {
					// Set default flags to <meta> tag
					if (!isset($dataStruct['meta'])) {
						// Make sure <meta> goes at the beginning of data structure.
						// This is not critical for typo3 but simply convinient to
						// people who used to see it at the beginning.
						$dataStruct = array_merge(array('meta'=>array()), $dataStruct);
					}
					if ($this->_saveDSandTO_type == 1) {
						// If we save a page template, set langDisable to 1 as per localization guide
						if (!isset($dataStruct['meta']['langDisable'])) {
							$dataStruct['meta']['langDisable'] = '1';
						}
					}
					else {
						// FCE defaults to inheritance
						if (!isset($dataStruct['meta']['langDisable'])) {
							$dataStruct['meta']['langDisable'] = '0';
							$dataStruct['meta']['langChildren'] = '1';
						}
					}
				}
			}

				// CMD switch:
			switch($cmd)	{
					// If it is requested to save the current DS and mapping information to a DS and TO record, then...:
				case 'saveDSandTO':

						// DS:
					$dataArr=array();
					$dataArr['tx_templavoila_datastructure']['NEW']['pid']=intval($this->_saveDSandTO_pid);
					$dataArr['tx_templavoila_datastructure']['NEW']['title']=$this->_saveDSandTO_title;
					$dataArr['tx_templavoila_datastructure']['NEW']['scope']=$this->_saveDSandTO_type;

						// Modifying data structure with conversion of preset values for field types to actual settings:
					$storeDataStruct = $dataStruct;
					if (is_array($storeDataStruct['ROOT']['el']))
						$this->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'],$contentSplittedByMapping['sub']['ROOT'],$dataArr['tx_templavoila_datastructure']['NEW']['scope']);
					$dataProtXML = t3lib_div::array2xml_cs($storeDataStruct,'T3DataStructure', array('useCDATA' => 1));
					$dataArr['tx_templavoila_datastructure']['NEW']['dataprot'] = $dataProtXML;

						// Init TCEmain object and store:
					$tce = t3lib_div::makeInstance("t3lib_TCEmain");
					$tce->stripslashes_values=0;
					$tce->start($dataArr,array());
					$tce->process_datamap();

						// If that succeeded, create the TO as well:
					if ($tce->substNEWwithIDs['NEW'])	{
						$dataArr=array();
						$dataArr['tx_templavoila_tmplobj']['NEW']['pid']=intval($this->_saveDSandTO_pid);
						$dataArr['tx_templavoila_tmplobj']['NEW']['title']=$this->_saveDSandTO_title.' [Template]';
						$dataArr['tx_templavoila_tmplobj']['NEW']['datastructure']=intval($tce->substNEWwithIDs['NEW']);
						$dataArr['tx_templavoila_tmplobj']['NEW']['fileref']=substr($this->displayFile,strlen(PATH_site));
						$dataArr['tx_templavoila_tmplobj']['NEW']['templatemapping']=serialize($templatemapping);
						$dataArr['tx_templavoila_tmplobj']['NEW']['fileref_mtime'] = @filemtime($this->displayFile);
						$dataArr['tx_templavoila_tmplobj']['NEW']['fileref_md5'] = @md5_file($this->displayFile);

							// Init TCEmain object and store:
						$tce = t3lib_div::makeInstance("t3lib_TCEmain");
						$tce->stripslashes_values=0;
						$tce->start($dataArr,array());
						$tce->process_datamap();

						if ($tce->substNEWwithIDs['NEW'])	{
							$msg[] = '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/icon_ok.gif', 'width="18" height="16"').' border="0" align="top" class="absmiddle" alt="" />'.sprintf($GLOBALS['LANG']->getLL('msgDSTOSaved'), $dataArr['tx_templavoila_tmplobj']['NEW']['datastructure'], $tce->substNEWwithIDs['NEW'], $this->_saveDSandTO_pid);
						} else {
							$msg[] = '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/icon_warning.gif', 'width="18" height="16"').' border="0" align="top" class="absmiddle" alt="" /><strong>'.$GLOBALS['LANG']->getLL('error').':</strong> '.sprintf($GLOBALS['LANG']->getLL('errorTONotSaved'), $dataArr['tx_templavoila_tmplobj']['NEW']['datastructure']);
						}
					} else {
						$msg[] = '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/icon_warning.gif','width="18" height="16"').' border="0" align="top" class="absmiddle" alt="" /><strong>'.$GLOBALS['LANG']->getLL('error').':</strong> '.$GLOBALS['LANG']->getLL('errorTONotCreated');
					}

					unset($tce);

					// Clear cached header info because saveDSandTO always resets headers
					$sesDat['currentMappingInfo_head'] = '';
					$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
				break;
					// Updating DS and TO records:
				case 'updateDSandTO':

						// Looking up the records by their uids:
					$toREC = t3lib_BEfunc::getRecordWSOL('tx_templavoila_tmplobj',$this->_saveDSandTO_TOuid);
					$dsREC = t3lib_BEfunc::getRecordWSOL('tx_templavoila_datastructure',$toREC['datastructure']);

						// If they are found, continue:
					if ($toREC['uid'] && $dsREC['uid'])	{

							// DS:
						$dataArr=array();

							// Modifying data structure with conversion of preset values for field types to actual settings:
						$storeDataStruct=$dataStruct;
						if (is_array($storeDataStruct['ROOT']['el']))
							$this->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'],$contentSplittedByMapping['sub']['ROOT'],$dsREC['scope']);
						$dataProtXML = t3lib_div::array2xml_cs($storeDataStruct,'T3DataStructure', array('useCDATA' => 1));
						$dataArr['tx_templavoila_datastructure'][$dsREC['uid']]['dataprot'] = $dataProtXML;

							// Init TCEmain object and store:
						$tce = t3lib_div::makeInstance('t3lib_TCEmain');
						$tce->stripslashes_values=0;
						$tce->start($dataArr,array());
						$tce->process_datamap();

							// TO:
						$TOuid = t3lib_BEfunc::wsMapId('tx_templavoila_tmplobj',$toREC['uid']);
						$dataArr=array();
						$dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref']=substr($this->displayFile,strlen(PATH_site));
						$dataArr['tx_templavoila_tmplobj'][$TOuid]['templatemapping']=serialize($templatemapping);
						$dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref_mtime'] = @filemtime($this->displayFile);
						$dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref_md5'] = @md5_file($this->displayFile);

							// Init TCEmain object and store:
						$tce = t3lib_div::makeInstance('t3lib_TCEmain');
						$tce->stripslashes_values=0;
						$tce->start($dataArr,array());
						$tce->process_datamap();

						unset($tce);

						$msg[] = '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/icon_note.gif','width="18" height="16"').' border="0" align="top" class="absmiddle" alt="" />'.sprintf($GLOBALS['LANG']->getLL('msgDSTOUpdated'), $dsREC['uid'], $toREC['uid']);

						// Clear cached header info because updateDSandTO always resets headers
						$sesDat['currentMappingInfo_head'] = '';
						$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
					}
				break;
			}


				// Header:
			$tRows = array();
			$relFilePath = substr($this->displayFile,strlen(PATH_site));
			$onCl = 'return top.openUrlInWindow(\''.t3lib_div::getIndpEnv('TYPO3_SITE_URL').$relFilePath.'\',\'FileView\');';
			$tRows[]='
				<tr>
					<td class="bgColor5"><strong>'.$this->cshItem('xMOD_tx_templavoila','mapping_file',$this->doc->backPath,'|') . $GLOBALS['LANG']->getLL('templateFile').':</strong></td>
					<td class="bgColor4"><a href="#" onclick="'.htmlspecialchars($onCl).'">'.htmlspecialchars($relFilePath).'</a></td>
				</tr>';
				// Write header of page:
			$content.='

				<!--
					Create Data Structure Header:
				-->
				<table border="0" cellpadding="2" cellspacing="1" id="c-toHeader">
					'.implode('',$tRows).'
				</table>
			';


				// Messages:
			if (is_array($msg))	{
				$content.='

					<!--
						Messages:
					-->
					'.implode('<br />',$msg).'
				';
			}


				// Generate selector box options:
				// Storage Folders for elements:
			$sf_opt=array();
			$res = $TYPO3_DB->exec_SELECTquery (
				'*',
				'pages',
				'uid IN ('.$this->storageFolders_pidList.')'.t3lib_BEfunc::deleteClause('pages'),
				'',
				'title'
			);
			while(false !== ($row = $TYPO3_DB->sql_fetch_assoc($res)))	{
				$sf_opt[]='<option value="'.htmlspecialchars($row['uid']).'">'.htmlspecialchars($row['title'].' (UID:'.$row['uid'].')').'</option>';
			}

				// Template Object records:
			$opt=array();
			$opt[]='<option value="0"></option>';
			$res = $TYPO3_DB->exec_SELECTquery (
				'tx_templavoila_tmplobj.*,tx_templavoila_datastructure.scope',
				'tx_templavoila_tmplobj LEFT JOIN tx_templavoila_datastructure ON tx_templavoila_datastructure.uid=tx_templavoila_tmplobj.datastructure',
				'tx_templavoila_tmplobj.pid IN ('.$this->storageFolders_pidList.') AND tx_templavoila_tmplobj.datastructure>0 '.
					t3lib_BEfunc::deleteClause('tx_templavoila_tmplobj').
					t3lib_BEfunc::versioningPlaceholderClause('tx_templavoila_tmplobj'),
				'',
				'tx_templavoila_datastructure.scope, tx_templavoila_tmplobj.title'
			);
			$sFolder = '';
			$optGroupOpen = false;
			while(false !== ($row = $TYPO3_DB->sql_fetch_assoc($res)))	{
				t3lib_BEfunc::workspaceOL('tx_templavoila_tmplobj',$row);
				if ($sFolder != $this->storageFolders[$row['pid']]) {
					 $sFolder = $this->storageFolders[$row['pid']];
					 if ($optGroupOpen) {
						$opt[] = '</optgroup>';
					 }
					 $opt[] = '<optgroup label="' . htmlspecialchars($sFolder . ' (PID: ' . $row['pid'] . ')') . '">';
					 $optGroupOpen = true;
				}
				$opt[]= '<option value="' .htmlspecialchars($row['uid']).'" ' .
					($row['scope'] == 1 ? 'class="pagetemplate"">' : 'class="fce">') .
					 htmlspecialchars($row['title'] . ' (UID:' . $row['uid'] . ')').'</option>';
			}
			if ($optGroupOpen) {
				$opt[] = '</optgroup>';
			}
					 
				// Module Interface output begin:
			switch($cmd)	{
					// Show XML DS
				case 'showXMLDS':
					require_once(PATH_t3lib.'class.t3lib_syntaxhl.php');

						// Make instance of syntax highlight class:
					$hlObj = t3lib_div::makeInstance('t3lib_syntaxhl');

					$storeDataStruct=$dataStruct;
					if (is_array($storeDataStruct['ROOT']['el']))
						$this->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'],$contentSplittedByMapping['sub']['ROOT']);
					$dataStructureXML = t3lib_div::array2xml_cs($storeDataStruct,'T3DataStructure', array('useCDATA' => 1));

					$content.='
						<input type="submit" name="_DO_NOTHING" value="Go back" title="' . $GLOBALS['LANG']->getLL('buttonGoBack') . '" />
						<h3>'.$GLOBALS['LANG']->getLL('titleXmlConfiguration').':</h3>
						'.$this->cshItem('xMOD_tx_templavoila','mapping_file_showXMLDS',$this->doc->backPath,'|<br/>').'
						<pre>'.$hlObj->highLight_DS($dataStructureXML).'</pre>';
				break;
				case 'loadScreen':

					$content.='
						<h3>'.$GLOBALS['LANG']->getLL('titleLoadDSXml').'</h3>
						'.$this->cshItem('xMOD_tx_templavoila','mapping_file_loadDSXML',$this->doc->backPath,'|<br/>').'
						<p>'.$GLOBALS['LANG']->getLL('selectTOrecrdToLoadDSFrom').':</p>
						<select name="_load_ds_xml_to">'.implode('',$opt).'</select>
						<br />
						<p>'.$GLOBALS['LANG']->getLL('pasteDSXml').':</p>
						<textarea rows="15" name="_load_ds_xml_content" wrap="off"'.$GLOBALS['TBE_TEMPLATE']->formWidthText(48,'width:98%;','off').'></textarea>
						<br />
						<input type="submit" name="_load_ds_xml" value="'.$GLOBALS['LANG']->getLL('loadDSXml').'" />
						<input type="submit" name="_" value="' . $GLOBALS['LANG']->getLL('buttonCancel') . '" />
						';
				break;
				case 'saveScreen':

					$content.='
						<h3>' . $GLOBALS['LANG']->getLL('createDSTO') . ':</h3>
						'.$this->cshItem('xMOD_tx_templavoila','mapping_file_createDSTO',$this->doc->backPath,'|<br/>').'
						<table border="0" cellpadding="2" cellspacing="2">
							<tr>
								<td class="bgColor5"><strong>' . $GLOBALS['LANG']->getLL('titleDSTO') . ':</strong></td>
								<td class="bgColor4"><input type="text" name="_saveDSandTO_title" /></td>
							</tr>
							<tr>
								<td class="bgColor5"><strong>' . $GLOBALS['LANG']->getLL('templateType') . ':</strong></td>
								<td class="bgColor4">
									<select name="_saveDSandTO_type">
										<option value="1">' . $GLOBALS['LANG']->getLL('pageTemplate') . '</option>
										<option value="2">' . $GLOBALS['LANG']->getLL('contentElement') . '</option>
										<option value="0">' . $GLOBALS['LANG']->getLL('undefined') . '</option>
									</select>
								</td>
							</tr>
							<tr>
								<td class="bgColor5"><strong>' . $GLOBALS['LANG']->getLL('storeInPID') . ':</strong></td>
								<td class="bgColor4">
									<select name="_saveDSandTO_pid">
										'.implode('
										',$sf_opt).'
									</select>
								</td>
							</tr>
						</table>

						<input type="submit" name="_saveDSandTO" value="' . $GLOBALS['LANG']->getLL('createDSTOshort') . '" />
						<input type="submit" name="_" value="' . $GLOBALS['LANG']->getLL('buttonCancel') . '" />



						<h3>' . $GLOBALS['LANG']->getLL('updateDSTO') . ':</h3>
						<table border="0" cellpadding="2" cellspacing="2">
							<tr>
								<td class="bgColor5"><strong>' . $GLOBALS['LANG']->getLL('selectTO') . ':</strong></td>
								<td class="bgColor4">
									<select name="_saveDSandTO_TOuid">
										'.implode('
										',$opt).'
									</select>
								</td>
							</tr>
						</table>

						<input type="submit" name="_updateDSandTO" value="UPDATE TO (and DS)" onclick="return confirm(\'' . $GLOBALS['LANG']->getLL('saveDSTOconfirm') . '\');" />
						<input type="submit" name="_" value="' . $GLOBALS['LANG']->getLL('buttonCancel') . '" />
						';
				break;
				default:
						// Creating menu:
					$menuItems = array();
					$menuItems[]='<input type="submit" name="_showXMLDS" value="' . $GLOBALS['LANG']->getLL('buttonShowXML') . '" title="' . $GLOBALS['LANG']->getLL('buttonTitle_showXML') . '" />';
					$menuItems[]='<input type="submit" name="_clear" value="' . $GLOBALS['LANG']->getLL('buttonClearAll') . '" title="' . $GLOBALS['LANG']->getLL('buttonTitle_clearAll') . '" /> ';
					$menuItems[]='<input type="submit" name="_preview" value="' . $GLOBALS['LANG']->getLL('buttonPreview') . '" title="' . $GLOBALS['LANG']->getLL('buttonTitle_preview') . '" />';
					$menuItems[]='<input type="submit" name="_saveScreen" value="' . $GLOBALS['LANG']->getLL('buttonSaveAs') . '" title="' . $GLOBALS['LANG']->getLL('buttonTitle_saveAs') . '" />';
					$menuItems[]='<input type="submit" name="_loadScreen" value="' . $GLOBALS['LANG']->getLL('buttonLoad') . '" title="' . $GLOBALS['LANG']->getLL('buttonTitle_load') . '" />';
					$menuItems[]='<input type="submit" name="_DO_NOTHING" value="' . $GLOBALS['LANG']->getLL('buttonRefresh') . '" title="' . $GLOBALS['LANG']->getLL('buttonTitle_refresh') . '" />';

					$menuContent = '

						<!--
							Menu for creation Data Structures / Template Objects
						-->
						<table border="0" cellpadding="2" cellspacing="2" id="c-toMenu">
							<tr class="bgColor5">
								<td>'.implode('</td>
								<td>',$menuItems).'</td>
							</tr>
						</table>
					';

					$content.='

					<!--
						Data Structure creation table:
					-->
					<h3>' . $this->cshItem('xMOD_tx_templavoila','mapping_file',$this->doc->backPath,'|') . $GLOBALS['LANG']->getLL('buildingDS') . ':</h3>' .
						$this->renderTemplateMapper($this->displayFile,$this->displayPath,$dataStruct,$currentMappingInfo,$menuContent);
				break;
			}
		}

		$this->content.=$this->doc->section('',$content,0,1);
	}

	/**
	 * Renders the display of Data Structure Objects.
	 *
	 * @return	void
	 */
	function renderDSO()	{
		global $TYPO3_DB;
		if (intval($this->displayUid)>0)	{
			$row = t3lib_BEfunc::getRecordWSOL('tx_templavoila_datastructure',$this->displayUid);
			if (is_array($row))	{

					// Get title and icon:
				$icon = t3lib_iconworks::getIconImage('tx_templavoila_datastructure',$row,$GLOBALS['BACK_PATH'],' align="top" title="' . $GLOBALS['LANG']->getLL('renderDSO_uid') . ': '.$this->displayUid.'"');
				$title = t3lib_BEfunc::getRecordTitle('tx_templavoila_datastructure',$row,1);
				$content.=$this->doc->wrapClickMenuOnIcon($icon,'tx_templavoila_datastructure',$row['uid'],1).
						'<strong>'.$title.'</strong><br />';

					// Get Data Structure:
				$origDataStruct = $dataStruct = $this->getDataStructFromDSO($row['dataprot']);

				if (is_array($dataStruct))	{
						// Showing Data Structure:
					$tRows = $this->drawDataStructureMap($dataStruct);
					$content.='

					<!--
						Data Structure content:
					-->
					<div id="c-ds">
						<h4>' . $GLOBALS['LANG']->getLL('renderDSO_dataStructure') . ':</h4>
						<table border="0" cellspacing="2" cellpadding="2">
									<tr class="bgColor5">
										<td nowrap="nowrap"><strong>' . $GLOBALS['LANG']->getLL('renderDSO_dataElement') . ':</strong>'.
											$this->cshItem('xMOD_tx_templavoila','mapping_head_dataElement',$this->doc->backPath,'',TRUE).
											'</td>
										<td nowrap="nowrap"><strong>' . $GLOBALS['LANG']->getLL('renderDSO_mappingInstructions') . ':</strong>'.
											$this->cshItem('xMOD_tx_templavoila','mapping_head_mapping_instructions',$this->doc->backPath,'',TRUE).
											'</td>
										<td nowrap="nowrap"><strong>' . $GLOBALS['LANG']->getLL('renderDSO_rules') . ':</strong>'.
											$this->cshItem('xMOD_tx_templavoila','mapping_head_Rules',$this->doc->backPath,'',TRUE).
											'</td>
									</tr>
						'.implode('',$tRows).'
						</table>
					</div>';

						// CSH
					$content.= $this->cshItem('xMOD_tx_templavoila','mapping_ds',$this->doc->backPath);
				} else {
					$content.='<h4>'.$GLOBALS['LANG']->getLL('error').': '.$GLOBALS['LANG']->getLL('noDSDefined').'</h4>';
				}

					// Get Template Objects pointing to this Data Structure
				$res = $TYPO3_DB->exec_SELECTquery (
					'*',
					'tx_templavoila_tmplobj',
					'pid IN ('.$this->storageFolders_pidList.') AND datastructure='.intval($row['uid']).
						t3lib_BEfunc::deleteClause('tx_templavoila_tmplobj').
						t3lib_BEfunc::versioningPlaceholderClause('tx_templavoila_tmplobj')
				);
				$tRows=array();
				$tRows[]='
							<tr class="bgColor5">
								<td><strong>' . $GLOBALS['LANG']->getLL('renderDSO_uid') . ':</strong></td>
								<td><strong>' . $GLOBALS['LANG']->getLL('renderDSO_title') . ':</strong></td>
								<td><strong>' . $GLOBALS['LANG']->getLL('renderDSO_fileRef') . ':</strong></td>
								<td><strong>' . $GLOBALS['LANG']->getLL('renderDSO_dataLgd') . ':</strong></td>
							</tr>';
				$TOicon = t3lib_iconworks::getIconImage('tx_templavoila_tmplobj',array(),$GLOBALS['BACK_PATH'],' align="top"');

					// Listing Template Objects with links:
				while(false !== ($TO_Row = $TYPO3_DB->sql_fetch_assoc($res)))	{
					t3lib_BEfunc::workspaceOL('tx_templavoila_tmplobj',$TO_Row);
					$tRows[]='
							<tr class="bgColor4">
								<td>['.$TO_Row['uid'].']</td>
								<td nowrap="nowrap">'.$this->doc->wrapClickMenuOnIcon($TOicon,'tx_templavoila_tmplobj',$TO_Row['uid'],1).
									'<a href="'.htmlspecialchars('index.php?table=tx_templavoila_tmplobj&uid='.$TO_Row['uid'].'&_reload_from=1').'">'.
									t3lib_BEfunc::getRecordTitle('tx_templavoila_tmplobj',$TO_Row,1).'</a>'.
									'</td>
								<td nowrap="nowrap">'.htmlspecialchars($TO_Row['fileref']).' <strong>'.
								(!t3lib_div::getFileAbsFileName($TO_Row['fileref'],1) ? $GLOBALS['LANG']->getLL('renderDSO_notFound') : $GLOBALS['LANG']->getLL('renderDSO_ok')).'</strong></td>
								<td>'.strlen($TO_Row['templatemapping']).'</td>
							</tr>';
				}

				$content.='

					<!--
						Template Objects attached to Data Structure Record:
					-->
					<div id="c-to">
						<h4>' . $GLOBALS['LANG']->getLL('renderDSO_usedTO') . ':</h4>
						<table border="0" cellpadding="2" cellspacing="2">
						'.implode('',$tRows).'
						</table>
					</div>';

					// CSH
				$content.= $this->cshItem('xMOD_tx_templavoila','mapping_ds_to',$this->doc->backPath);

					// Display XML of data structure:
				if (is_array($dataStruct))	{
					require_once(PATH_t3lib.'class.t3lib_syntaxhl.php');

						// Make instance of syntax highlight class:
					$hlObj = t3lib_div::makeInstance('t3lib_syntaxhl');

					$dataStructureXML = t3lib_div::array2xml_cs($origDataStruct,'T3DataStructure', array('useCDATA' => 1));
					$content.='

					<!--
						Data Structure XML:
					-->
					<br />
					<div id="c-dsxml">
						<h3>' . $GLOBALS['LANG']->getLL('renderDSO_XML') . ':</h3>
						'.$this->cshItem('xMOD_tx_templavoila','mapping_ds_showXML',$this->doc->backPath).'
						<p>'.t3lib_BEfunc::getFuncCheck('','SET[showDSxml]',$this->MOD_SETTINGS['showDSxml'],'',t3lib_div::implodeArrayForUrl('',$_GET,'',1,1)).' Show XML</p>
						<pre>'.
							($this->MOD_SETTINGS['showDSxml'] ? $hlObj->highLight_DS($dataStructureXML) : '').'
						</pre>
					</div>
					';
				}
			} else {
				$content.= sprintf($GLOBALS['LANG']->getLL('errorNoDSrecord'), $this->displayUid);
			}
			$this->content.=$this->doc->section($GLOBALS['LANG']->getLL('renderDSO_DSO'), $content,0,1);
		} else {
			$this->content.=$this->doc->section($GLOBALS['LANG']->getLL('errorInDSO'), '' . $GLOBALS['LANG']->getLL('renderDSO_noUid'), 0, 1, 3);
		}
	}

	/**
	 * Renders the display of Template Objects.
	 *
	 * @return	void
	 */
	function renderTO()	{
		if (intval($this->displayUid)>0)	{
			$row = t3lib_BEfunc::getRecordWSOL('tx_templavoila_tmplobj',$this->displayUid);

			if (is_array($row))	{

				$tRows=array();
				$tRows[]='
					<tr class="bgColor5">
						<td colspan="2"><strong>' . $GLOBALS['LANG']->getLL('renderTO_toDetails') . ':</strong>'.
							$this->cshItem('xMOD_tx_templavoila','mapping_to',$this->doc->backPath,'').
							'</td>
					</tr>';

					// Get title and icon:
				$icon = t3lib_iconworks::getIconImage('tx_templavoila_tmplobj',$row,$GLOBALS['BACK_PATH'],' align="top" title="UID: '.$this->displayUid.'"');
				$title = t3lib_BEfunc::getRecordTitle('tx_templavoila_tmplobj',$row,1);
				$tRows[]='
					<tr class="bgColor4">
						<td>'.$GLOBALS['LANG']->getLL('templateObject').':</td>
						<td>'.$this->doc->wrapClickMenuOnIcon($icon,'tx_templavoila_tmplobj',$row['uid'],1).$title.'</td>
					</tr>';

					// Find the file:
				$theFile = t3lib_div::getFileAbsFileName($row['fileref'],1);
				if ($theFile && @is_file($theFile))	{
					$relFilePath = substr($theFile,strlen(PATH_site));
					$onCl = 'return top.openUrlInWindow(\''.t3lib_div::getIndpEnv('TYPO3_SITE_URL').$relFilePath.'\',\'FileView\');';
					$tRows[]='
						<tr class="bgColor4">
							<td>'.$GLOBALS['LANG']->getLL('templateFile').':</td>
							<td><a href="#" onclick="'.htmlspecialchars($onCl).'">'.htmlspecialchars($relFilePath).'</a></td>
						</tr>';

						// Finding Data Structure Record:
					$DSOfile='';
					$dsValue = $row['datastructure'];
					if ($row['parent'])	{
						$parentRec = t3lib_BEfunc::getRecordWSOL('tx_templavoila_tmplobj',$row['parent'],'datastructure');
						$dsValue=$parentRec['datastructure'];
					}

					if (t3lib_div::testInt($dsValue))	{
						$DS_row = t3lib_BEfunc::getRecordWSOL('tx_templavoila_datastructure',$dsValue);
					} else {
						$DSOfile = t3lib_div::getFileAbsFileName($dsValue);
					}
					if (is_array($DS_row) || @is_file($DSOfile))	{

							// Get main DS array:
						if (is_array($DS_row))	{
								// Get title and icon:
							$icon = t3lib_iconworks::getIconImage('tx_templavoila_datastructure',$DS_row,$GLOBALS['BACK_PATH'],' align="top" title="UID: '.$DS_row['uid'].'"');
							$title = t3lib_BEfunc::getRecordTitle('tx_templavoila_datastructure',$DS_row,1);
							$tRows[]='
								<tr class="bgColor4">
									<td>' . $GLOBALS['LANG']->getLL('renderTO_dsRecord') . ':</td>
									<td>'.$this->doc->wrapClickMenuOnIcon($icon,'tx_templavoila_datastructure',$DS_row['uid'],1).$title.'</td>
								</tr>';

								// Link to updating DS/TO:
							$onCl = 'index.php?file='.rawurlencode($theFile).'&_load_ds_xml=1&_load_ds_xml_to='.$row['uid'];
							$onClMsg = '
								if (confirm(unescape(\''.rawurlencode($GLOBALS['LANG']->getLL('renderTO_updateWarningConfirm')).'\'))) {
									document.location=\''.$onCl.'\';
								}
								return false;
								';
							$tRows[]='
								<tr class="bgColor4">
									<td>&nbsp;</td>
									<td><input type="submit" name="_" value="' . $GLOBALS['LANG']->getLL('renderTO_editDSTO') . '" onclick="'.htmlspecialchars($onClMsg).'"/>'.
										$this->cshItem('xMOD_tx_templavoila','mapping_to_modifyDSTO',$this->doc->backPath,'').
										'</td>
								</tr>';

								// Read Data Structure:
							$dataStruct = $this->getDataStructFromDSO($DS_row['dataprot']);
						} else {
								// Show filepath of external XML file:
							$relFilePath = substr($DSOfile,strlen(PATH_site));
							$onCl = 'return top.openUrlInWindow(\''.t3lib_div::getIndpEnv('TYPO3_SITE_URL').$relFilePath.'\',\'FileView\');';
							$tRows[]='
								<tr class="bgColor4">
									<td>' . $GLOBALS['LANG']->getLL('renderTO_dsFile') . ':</td>
									<td><a href="#" onclick="'.htmlspecialchars($onCl).'">'.htmlspecialchars($relFilePath).'</a></td>
								</tr>';

								// Read Data Structure:
							$dataStruct = $this->getDataStructFromDSO('',$DSOfile);
						}

							// Write header of page:
						$content.= '

							<!--
								Template Object Header:
							-->
							<h3>' . $GLOBALS['LANG']->getLL('renderTO_toInfo') . ':</h3>
							<table border="0" cellpadding="2" cellspacing="1" id="c-toHeader">
								'.implode('',$tRows).'
							</table>
						';


							// If there is a valid data structure, draw table:
						if (is_array($dataStruct))	{

							// Working on Header and Body of HTML source:

								// -- Processing the header editing --
							list($editContent,$currentHeaderMappingInfo) = $this->renderTO_editProcessing($dataStruct,$row,$theFile, 1);

									// Determine if DS is a template record and if it is a page template:
								$showBodyTag = !is_array($DS_row) || $DS_row['scope']==1 ? TRUE : FALSE;

							$parts = array();
							$parts[] = array(
								'label' => $GLOBALS['LANG']->getLL('tabTODetails'),
								'content' => $content
							);

								// -- Processing the head editing
							$headerContent.='
								<!--
									HTML header parts selection:
								-->
							<h3>'.$GLOBALS['LANG']->getLL('mappingHeadParts').': '.$this->cshItem('xMOD_tx_templavoila','mapping_to_headerParts',$this->doc->backPath,'').'</h3>
								'.$this->renderHeaderSelection($theFile,$currentHeaderMappingInfo,$showBodyTag,$editContent);

							$parts[] = array(
								'label' => $GLOBALS['LANG']->getLL('tabHeadParts'),
								'content' => $headerContent
							);

								// -- Processing the body editing --
							list($editContent,$currentMappingInfo) = $this->renderTO_editProcessing($dataStruct,$row,$theFile, 0);

							$bodyContent.='
								<!--
									Data Structure mapping table:
								-->
							<h3>'.$GLOBALS['LANG']->getLL('mappingBodyParts').':</h3>
								'.$this->renderTemplateMapper($theFile,$this->displayPath,$dataStruct,$currentMappingInfo,$editContent);

							$parts[] = array(
								'label' => $GLOBALS['LANG']->getLL('tabBodyParts'),
								'content' => $bodyContent
							);

						} else $content.= $GLOBALS['LANG']->getLL('error') . ': ' . sprintf($GLOBALS['LANG']->getLL('errorNoDSfound'), $dsValue);
					} else $content.= $GLOBALS['LANG']->getLL('error') . ': ' . sprintf($GLOBALS['LANG']->getLL('errorNoDSfound'), $dsValue);
				} else $content.= $GLOBALS['LANG']->getLL('error') . ': ' . sprintf($GLOBALS['LANG']->getLL('errorFileNotFound'), $row['fileref']);
			} else $content.= $GLOBALS['LANG']->getLL('error') . ': ' . sprintf($GLOBALS['LANG']->getLL('errorNoTOfound'), $this->displayUid);

			$parts[0]['content'] = $content;
		} else {
			$this->content.=$this->doc->section($GLOBALS['LANG']->getLL('templateObject').' '.$GLOBALS['LANG']->getLL('error'), $GLOBALS['LANG']->getLL('errorNoUidFound'),0,1,3);
		}

			// show tab menu
		if (is_array($parts)) {
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('mappingTitle'), ''.
				$this->doc->getDynTabMenu($parts,'TEMPLAVOILA:templateModule:'.$this->id, 0,0,300)
				,0,1);
		}
	}

	/**
	 * Process editing of a TO for renderTO() function
	 *
	 * @param	array		Data Structure. Passed by reference; The sheets found inside will be resolved if found!
	 * @param	array		TO record row
	 * @param	string		Template file path (absolute)
	 * @param   integer		Process the headerPart instead of the bodyPart
	 * @return	array		Array with two keys (0/1) with a) content and b) currentMappingInfo which is retrieved inside (currentMappingInfo will be different based on whether "head" or "body" content is "mapped")
	 * @see renderTO()
	 */
	function renderTO_editProcessing(&$dataStruct,$row,$theFile, $headerPart = 0)	{
		$msg = array();

			// Converting GPvars into a "cmd" value:
		$cmd = '';
		if (t3lib_div::_GP('_reload_from'))	{	// Reverting to old values in TO
			$cmd = 'reload_from';
		} elseif (t3lib_div::_GP('_clear'))	{	// Resetting mapping
			$cmd = 'clear';
		} elseif (t3lib_div::_GP('_save_data_mapping'))	{	// Saving to Session
			$cmd = 'save_data_mapping';
		} elseif (t3lib_div::_GP('_save_to') || t3lib_div::_GP('_save_to_return'))	{	// Saving to Template Object
			$cmd = 'save_to';
		}

			// Getting data from tmplobj
		$templatemapping = unserialize($row['templatemapping']);
		if (!is_array($templatemapping))	$templatemapping=array();

			// If that array contains sheets, then traverse them:
		if (is_array($dataStruct['sheets']))	{
			$dSheets = t3lib_div::resolveAllSheetsInDS($dataStruct);
			$dataStruct=array(
				'ROOT' => array (
					'tx_templavoila' => array (
						'title' => $GLOBALS['LANG']->getLL('rootMultiTemplate_title'),
						'description' => $GLOBALS['LANG']->getLL('rootMultiTemplate_description'),
					),
					'type' => 'array',
					'el' => array()
				)
			);
			foreach($dSheets['sheets'] as $nKey => $lDS)	{
				if (is_array($lDS['ROOT']))	{
					$dataStruct['ROOT']['el'][$nKey] = $lDS['ROOT'];
				}
			}
		}

			// Get session data:
		$sesDat = $GLOBALS['BE_USER']->getSessionData($this->MCONF['name'].'_mappingInfo');

			// Set current mapping info arrays:
		$currentMappingInfo_head = is_array($sesDat['currentMappingInfo_head']) ? $sesDat['currentMappingInfo_head'] : array();
		$currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : array();
		$this->cleanUpMappingInfoAccordingToDS($currentMappingInfo,$dataStruct);

		// Perform processing for head
			// GPvars, incoming data
		$checkboxElement = t3lib_div::_GP('checkboxElement',1);
		$addBodyTag = t3lib_div::_GP('addBodyTag');

			// Update session data:
		if ($cmd=='reload_from' || $cmd=='clear')	{
			$currentMappingInfo_head = is_array($templatemapping['MappingInfo_head'])&&$cmd!='clear' ? $templatemapping['MappingInfo_head'] : array();
			$sesDat['currentMappingInfo_head'] = $currentMappingInfo_head;
			$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
		} else {
			if ($cmd=='save_data_mapping' || $cmd=='save_to')	{
				$sesDat['currentMappingInfo_head'] = $currentMappingInfo_head = array(
					'headElementPaths' => $checkboxElement,
					'addBodyTag' => $addBodyTag?1:0
				);
				$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
			}
		}

		// Perform processing for  body
			// GPvars, incoming data
		$inputData = t3lib_div::_GP('dataMappingForm',1);

			// Update session data:
		if ($cmd=='reload_from' || $cmd=='clear')	{
			$currentMappingInfo = is_array($templatemapping['MappingInfo'])&&$cmd!='clear' ? $templatemapping['MappingInfo'] : array();
			$this->cleanUpMappingInfoAccordingToDS($currentMappingInfo,$dataStruct);
			$sesDat['currentMappingInfo'] = $currentMappingInfo;
			$sesDat['dataStruct'] = $dataStruct;
			$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
		} else {
			if ($cmd=='save_data_mapping' && is_array($inputData))	{
				$sesDat['currentMappingInfo'] = $currentMappingInfo = $this->array_merge_recursive_overrule($currentMappingInfo,$inputData);
				$sesDat['dataStruct'] = $dataStruct;		// Adding data structure to session data so that the PREVIEW window can access the DS easily...
				$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
			}
		}

			// SAVE to template object
		if ($cmd=='save_to')	{
			$dataArr=array();

				// Set content, either for header or body:
			$templatemapping['MappingInfo_head'] = $currentMappingInfo_head;
			$templatemapping['MappingInfo'] = $currentMappingInfo;

				// Getting cached data:
			reset($dataStruct);
				// Init; read file, init objects:
			$fileContent = t3lib_div::getUrl($theFile);
			$htmlParse = t3lib_div::makeInstance('t3lib_parsehtml');
			$this->markupObj = t3lib_div::makeInstance('tx_templavoila_htmlmarkup');

				// Fix relative paths in source:
			$relPathFix=dirname(substr($theFile,strlen(PATH_site))).'/';
			$uniqueMarker = uniqid('###') . '###';
			$fileContent = $htmlParse->prefixResourcePath($relPathFix,$fileContent, array('A' => $uniqueMarker));
			$fileContent = $this->fixPrefixForLinks($relPathFix, $fileContent, $uniqueMarker);


				// Get BODY content for caching:
			$contentSplittedByMapping=$this->markupObj->splitContentToMappingInfo($fileContent,$currentMappingInfo);
			$templatemapping['MappingData_cached'] = $contentSplittedByMapping['sub']['ROOT'];

				// Get HEAD content for caching:
			list($html_header) =  $this->markupObj->htmlParse->getAllParts($htmlParse->splitIntoBlock('head',$fileContent),1,0);
			$this->markupObj->tags = $this->head_markUpTags;	// Set up the markupObject to process only header-section tags:

			$h_currentMappingInfo=array();
			if (is_array($currentMappingInfo_head['headElementPaths']))	{
				foreach($currentMappingInfo_head['headElementPaths'] as $kk => $vv)	{
					$h_currentMappingInfo['el_'.$kk]['MAP_EL'] = $vv;
				}
			}

			$contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($html_header,$h_currentMappingInfo);
			$templatemapping['MappingData_head_cached'] = $contentSplittedByMapping;

				// Get <body> tag:
			$reg='';
			eregi('<body[^>]*>',$fileContent,$reg);
			$templatemapping['BodyTag_cached'] = $currentMappingInfo_head['addBodyTag'] ? $reg[0] : '';

			$TOuid = t3lib_BEfunc::wsMapId('tx_templavoila_tmplobj',$row['uid']);
			$dataArr['tx_templavoila_tmplobj'][$TOuid]['templatemapping'] = serialize($templatemapping);
			$dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref_mtime'] = @filemtime($theFile);
			$dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref_md5'] = @md5_file($theFile);

			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values=0;
			$tce->start($dataArr,array());
			$tce->process_datamap();
			unset($tce);
			$msg[] = $GLOBALS['LANG']->getLL('msgMappingSaved');
			$row = t3lib_BEfunc::getRecordWSOL('tx_templavoila_tmplobj',$this->displayUid);
			$templatemapping = unserialize($row['templatemapping']);

			if (t3lib_div::_GP('_save_to_return'))	{
				header('Location: '.t3lib_div::locationHeaderUrl($this->returnUrl));
				exit;
			}
		}

			// Making the menu
		$menuItems=array();
		$menuItems[]='<input type="submit" name="_clear" value="' . $GLOBALS['LANG']->getLL('buttonSet') . '" title="' . $GLOBALS['LANG']->getLL('buttonClearAllMappingTitle') . '" />';

			// Make either "Preview" button (body) or "Set" button (header)
		if ($headerPart)	{	// Header:
			$menuItems[] = '<input type="submit" name="_save_data_mapping" value="' . $GLOBALS['LANG']->getLL('buttonSet') . '" title="' . $GLOBALS['LANG']->getLL('buttonSetTitle') . '" />';
		} else {	// Body:
			$menuItems[] = '<input type="submit" name="_preview" value="' . $GLOBALS['LANG']->getLL('buttonPreview') . '" title="' . $GLOBALS['LANG']->getLL('buttonPreviewMappingTitle') . '" />';
		}

		$menuItems[]='<input type="submit" name="_save_to" value="' . $GLOBALS['LANG']->getLL('buttonSave') . '" title="' . $GLOBALS['LANG']->getLL('buttonSaveTOTitle') . '" />';

		if ($this->returnUrl)	{
			$menuItems[]='<input type="submit" name="_save_to_return" value="' . $GLOBALS['LANG']->getLL('buttonSaveAndReturn') . '" title="' . $GLOBALS['LANG']->getLL('buttonSaveAndReturnTitle') . '" />';
		}

			// If a difference is detected...:
		if (
				(serialize($templatemapping['MappingInfo_head']) != serialize($currentMappingInfo_head))	||
				(serialize($templatemapping['MappingInfo']) != serialize($currentMappingInfo))
			)	{
			$menuItems[]='<input type="submit" name="_reload_from" value="' . $GLOBALS['LANG']->getLL('buttonRevert') . '" title="'.sprintf($GLOBALS['LANG']->getLL('buttonRevertTitle'), $headerPart ? 'HEAD' : 'BODY') . '" />';
			$msg[] = $GLOBALS['LANG']->getLL('msgMappingIsDifferent');
		}

		$content = '

			<!--
				Menu for saving Template Objects
			-->
			<table border="0" cellpadding="2" cellspacing="2" id="c-toMenu">
				<tr class="bgColor5">
					<td>'.implode('</td>
					<td>',$menuItems).'</td>
				</tr>
			</table>
		';

			// Making messages:
		foreach($msg as $msgStr)	{
			$content.='
			<p><img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/icon_note.gif','width="18" height="16"').' border="0" align="top" class="absmiddle" alt="" /><strong>'.htmlspecialchars($msgStr).'</strong></p>';
		}


		return array($content, $headerPart ? $currentMappingInfo_head : $currentMappingInfo);
	}





	/*******************************
	 *
	 * Mapper functions
	 *
	 *******************************/

	/**
	 * Renders the table with selection of part from the HTML header + bodytag.
	 *
	 * @param	string		The abs file name to read
	 * @param	array		Header mapping information
	 * @param	boolean		If true, show body tag.
	 * @param	string		HTML content to show after the Data Structure table.
	 * @return	string		HTML table.
	 */
	function renderHeaderSelection($displayFile,$currentHeaderMappingInfo,$showBodyTag,$htmlAfterDSTable='')	{

			// Get file content
		$this->markupFile = $displayFile;
		$fileContent = t3lib_div::getUrl($this->markupFile);

			// Init mark up object.
		$this->markupObj = t3lib_div::makeInstance('tx_templavoila_htmlmarkup');
		$this->markupObj->init();

			// Get <body> tag:
		$reg='';
		eregi('<body[^>]*>',$fileContent,$reg);
		$html_body = $reg[0];

			// Get <head>...</head> from template:
		$splitByHeader = $this->markupObj->htmlParse->splitIntoBlock('head',$fileContent);
		list($html_header) =  $this->markupObj->htmlParse->getAllParts($splitByHeader,1,0);

			// Set up the markupObject to process only header-section tags:
		$this->markupObj->tags = $this->head_markUpTags;
		$this->markupObj->checkboxPathsSet = is_array($currentHeaderMappingInfo['headElementPaths']) ? $currentHeaderMappingInfo['headElementPaths'] : array();
		$this->markupObj->maxRecursion = 0;		// Should not enter more than one level.

			// Markup the header section data with the header tags, using "checkbox" mode:
		$tRows = $this->markupObj->markupHTMLcontent($html_header,$GLOBALS['BACK_PATH'], '','script,style,link,meta','checkbox');
		$bodyTagRow = $showBodyTag ? '
				<tr class="bgColor2">
					<td><input type="checkbox" name="addBodyTag" value="1"'.($currentHeaderMappingInfo['addBodyTag'] ? ' checked="checked"' : '').' /></td>
					<td><img src="../html_tags/body.gif" width="32" height="9" alt="" /></td>
					<td><pre>'.htmlspecialchars($html_body).'</pre></td>
				</tr>' : '';

		$headerParts = '
			<!--
				Header parts:
			-->
			<table border="0" cellpadding="2" cellspacing="2" id="c-headerParts">
				<tr class="bgColor5">
					<td><strong>' . $GLOBALS['LANG']->getLL('include') . ':</strong></td>
					<td><strong>' . $GLOBALS['LANG']->getLL('tag') . ':</strong></td>
					<td><strong>' . $GLOBALS['LANG']->getLL('tagContent') . ':</strong></td>
				</tr>
				'.$tRows.'
				'.$bodyTagRow.'
			</table>' .
			'<p style="margin: 5px 3px">' .
			'<img'.t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/icon_warning.gif', 'width="18" height="16"').' alt="" align="absmiddle" /> '.
			'<strong>' . $GLOBALS['LANG']->getLL('msgHeaderSet') . '</strong></p>' .
			$this->cshItem('xMOD_tx_templavoila','mapping_to_headerParts_buttons',$this->doc->backPath,'').$htmlAfterDSTable;

			// Return result:
		return $headerParts;
	}

	/**
	 * Creates the template mapper table + form for either direct file mapping or Template Object
	 *
	 * @param	string		The abs file name to read
	 * @param	string		The HTML-path to follow. Eg. 'td#content table[1] tr[1] / INNER | img[0]' or so. Normally comes from clicking a tag-image in the display frame.
	 * @param	array		The data Structure to map to
	 * @param	array		The current mapping information
	 * @param	string		HTML content to show after the Data Structure table.
	 * @return	string		HTML table.
	 */
	function renderTemplateMapper($displayFile,$path,$dataStruct=array(),$currentMappingInfo=array(),$htmlAfterDSTable='')	{

			// Get file content
		$this->markupFile = $displayFile;
		$fileContent = t3lib_div::getUrl($this->markupFile);

			// Init mark up object.
		$this->markupObj = t3lib_div::makeInstance('tx_templavoila_htmlmarkup');

			// Load splitted content from currentMappingInfo array (used to show us which elements maps to some real content).
		$contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($fileContent,$currentMappingInfo);

			// Show path:
		$pathRendered=t3lib_div::trimExplode('|',$path,1);
		$acc=array();
		foreach($pathRendered as $k => $v)	{
			$acc[]=$v;
			$pathRendered[$k]=$this->linkForDisplayOfPath($v,implode('|',$acc));
		}
		array_unshift($pathRendered,$this->linkForDisplayOfPath('[ROOT]',''));

			// Get attributes of the extracted content:
		$attrDat=array();
		$contentFromPath = $this->markupObj->splitByPath($fileContent,$path);	// ,'td#content table[1] tr[1]','td#content table[1]','map#cdf / INNER','td#content table[2] tr[1] td[1] table[1] tr[4] td.bckgd1[2] table[1] tr[1] td[1] table[1] tr[1] td.bold1px[1] img[1] / RANGE:img[2]'
		$firstTag = $this->markupObj->htmlParse->getFirstTag($contentFromPath[1]);
		list($attrDat) = $this->markupObj->htmlParse->get_tag_attributes($firstTag,1);

			// Make options:
		$pathLevels = $this->markupObj->splitPath($path);
		$lastEl = end($pathLevels);

		$optDat=array();
		$optDat[$lastEl['path']]='OUTER (Include tag)';
		$optDat[$lastEl['path'].'/INNER']='INNER (Exclude tag)';

			// Tags, which will trigger "INNER" to be listed on top (because it is almost always INNER-mapping that is needed)
		if (t3lib_div::inList('body,span,h1,h2,h3,h4,h5,h6,div,td,p,b,i,u,a',$lastEl['el']))	{
			$optDat = array_reverse($optDat);
		}

			// Add options for "samelevel" elements:
		$sameLevelElements = $this->markupObj->elParentLevel[$lastEl['parent']];
		if (is_array($sameLevelElements))	{
			$startFound=0;
			foreach($sameLevelElements as $rEl) 	{
				if ($startFound)	{
					$optDat[$lastEl['path'].'/RANGE:'.$rEl]='RANGE to "'.$rEl.'"';
				}

				if (trim($lastEl['parent'].' '.$rEl)==$lastEl['path'])	$startFound=1;
			}
		}

			// Add options for attributes:
		if (is_array($attrDat))	{
			foreach($attrDat as $attrK => $v)	{
				$optDat[$lastEl['path'].'/ATTR:'.$attrK]='ATTRIBUTE "'.$attrK.'" (= '.t3lib_div::fixed_lgd_cs($v,15).')';
			}
		}

			// Create Data Structure table:
		$content.='

			<!--
				Data Structure table:
			-->
			<table border="0" cellspacing="2" cellpadding="2">
			<tr class="bgColor5">
				<td nowrap="nowrap"><strong>' . $GLOBALS['LANG']->getLL('mapDataElement') . ':</strong>' .
					$this->cshItem('xMOD_tx_templavoila','mapping_head_dataElement',$this->doc->backPath, '', TRUE) .
					'</td>
				'.($this->editDataStruct ? '<td nowrap="nowrap"><strong>' . $GLOBALS['LANG']->getLL('mapField') . ':</strong>' .
					$this->cshItem('xMOD_tx_templavoila','mapping_head_Field', $this->doc->backPath, '', TRUE) .
					'</td>' : '') . '
				<td nowrap="nowrap"><strong>' . (!$this->_preview ? $GLOBALS['LANG']->getLL('mapInstructions') : $GLOBALS['LANG']->getLL('mapSampleData')) . '</strong>' .
					$this->cshItem('xMOD_tx_templavoila','mapping_head_' . (!$this->_preview ? 'mapping_instructions' : 'sample_data'), $this->doc->backPath, '', TRUE) .
					'<br /><img src="clear.gif" width="200" height="1" alt="" /></td>
				<td nowrap="nowrap"><strong>' . $GLOBALS['LANG']->getLL('mapHTMLpath') . ':</strong>' .
					$this->cshItem('xMOD_tx_templavoila', 'mapping_head_HTMLpath', $this->doc->backPath, '', TRUE) .
					'</td>
				<td nowrap="nowrap"><strong>' . $GLOBALS['LANG']->getLL('mapAction') . ':</strong>' .
					$this->cshItem('xMOD_tx_templavoila', 'mapping_head_Action', $this->doc->backPath, '', TRUE) .
					'</td>
				<td nowrap="nowrap"><strong>' . $GLOBALS['LANG']->getLL('mapRules') . ':</strong>' .
					$this->cshItem('xMOD_tx_templavoila', 'mapping_head_Rules', $this->doc->backPath, '', TRUE) .
					'</td>
				'.($this->editDataStruct ? '<td nowrap="nowrap"><strong>' . $GLOBALS['LANG']->getLL('mapEdit') . ':</strong>' .
					$this->cshItem('xMOD_tx_templavoila', 'mapping_head_Edit', $this->doc->backPath, '', TRUE) .
					'</td>' : '') . '
			</tr>
			'.implode('', $this->drawDataStructureMap($dataStruct, 1, $currentMappingInfo, $pathLevels, $optDat, $contentSplittedByMapping)) . '</table>
			' . $htmlAfterDSTable .
			$this->cshItem('xMOD_tx_templavoila', 'mapping_basics', $this->doc->backPath, '');

			// Make mapping window:
		$limitTags = implode(',',array_keys($this->explodeMappingToTagsStr($this->mappingToTags,1)));
		if (($this->mapElPath && !$this->doMappingOfPath) || $this->showPathOnly || $this->_preview)	{
			$content.=
			'

			<!--
				Visual Mapping Window (Iframe)
			-->
			<h3>' . $GLOBALS['LANG']->getLL('mapMappingWindow') . ':</h3>
			<!-- <p><strong>File:</strong> '.htmlspecialchars($displayFile).'</p> -->
			<p>'.
				t3lib_BEfunc::getFuncMenu('','SET[displayMode]',$this->MOD_SETTINGS['displayMode'],$this->MOD_MENU['displayMode'],'',t3lib_div::implodeArrayForUrl('',$_GET,'',1,1)).
				$this->cshItem('xMOD_tx_templavoila','mapping_window_modes',$this->doc->backPath,'').
				'</p>';

			if ($this->_preview)	{
				$content.='

					<!--
						Preview information table
					-->
					<table border="0" cellpadding="4" cellspacing="2" id="c-mapInfo">
						<tr class="bgColor5"><td><strong>' . $GLOBALS['LANG']->getLL('mapPreviewInfo') . ':</strong>'.
							$this->cshItem('xMOD_tx_templavoila','mapping_window_help',$this->doc->backPath,'').
							'</td></tr>
					</table>
				';

					// Add the Iframe:
				$content.=$this->makeIframeForVisual($displayFile,'','',0,1);
			} else {
				$tRows=array();
				if ($this->showPathOnly)	{
					$tRows[]='
						<tr class="bgColor4">
							<td class="bgColor5"><strong>' . $GLOBALS['LANG']->getLL('mapHTMLpath') . ':</strong></td>
							<td>'.htmlspecialchars($this->displayPath).'</td>
						</tr>
					';
				} else {
					$tRows[]='
						<tr class="bgColor4">
							<td class="bgColor5"><strong>' . $GLOBALS['LANG']->getLL('mapDSelement') . '::</strong></td>
							<td>'.$this->elNames[$this->mapElPath]['tx_templavoila']['title'].'</td>
						</tr>
						<tr class="bgColor4">
							<td class="bgColor5"><strong>' . $GLOBALS['LANG']->getLL('mapLimitToTags') . '::</strong></td>
							<td>'.htmlspecialchars(($limitTags?strtoupper($limitTags):'(ALL TAGS)')).'</td>
						</tr>
						<tr class="bgColor4">
							<td class="bgColor5"><strong>' . $GLOBALS['LANG']->getLL('instructions') . ':</strong></td>
							<td>'.htmlspecialchars($this->elNames[$this->mapElPath]['tx_templavoila']['description']).'</td>
						</tr>
					';

				}
				$content.='

					<!--
						Mapping information table
					-->
					<table border="0" cellpadding="2" cellspacing="2" id="c-mapInfo">
						'.implode('',$tRows).'
					</table>
				';

					// Add the Iframe:
				$content.=$this->makeIframeForVisual($displayFile,$this->displayPath,$limitTags,$this->doMappingOfPath);
			}
		}

		return $content;
	}

	/**
	 * Renders the hierarchical display for a Data Structure.
	 * Calls itself recursively
	 *
	 * @param	array		Part of Data Structure (array of elements)
	 * @param	boolean		If true, the Data Structure table will show links for mapping actions. Otherwise it will just layout the Data Structure visually.
	 * @param	array		Part of Current mapping information corresponding to the $dataStruct array - used to evaluate the status of mapping for a certain point in the structure.
	 * @param	array		Array of HTML paths
	 * @param	array		Options for mapping mode control (INNER, OUTER etc...)
	 * @param	array		Content from template file splitted by current mapping info - needed to evaluate whether mapping information for a certain level actually worked on live content!
	 * @param	integer		Recursion level, counting up
	 * @param	array		Accumulates the table rows containing the structure. This is the array returned from the function.
	 * @param	string		Form field prefix. For each recursion of this function, two [] parts are added to this prefix
	 * @param	string		HTML path. For each recursion a section (divided by "|") is added.
	 * @param	boolean		If true, the "Map" link can be shown, otherwise not. Used internally in the recursions.
	 * @return	array		Table rows as an array of <tr> tags, $tRows
	 */
	function drawDataStructureMap($dataStruct,$mappingMode=0,$currentMappingInfo=array(),$pathLevels=array(),$optDat=array(),$contentSplittedByMapping=array(),$level=0,$tRows=array(),$formPrefix='',$path='',$mapOK=1)	{

		$bInfo = t3lib_div::clientInfo();
		$multilineTooltips = ($bInfo['BROWSER'] == 'msie');
        $rowIndex = -1;
        
			// Data Structure array must be ... and array of course...
		if (is_array($dataStruct))	{
			foreach($dataStruct as $key => $value)	{
                $rowIndex++; 
                
				if ($key == 'meta') {
					// Do not show <meta> information in mapping interface!
					continue;
				}

				if (is_array($value))	{	// The value of each entry must be an array.

						// ********************
						// Making the row:
						// ********************
					$rowCells=array();

						// Icon:
					$info = $this->dsTypeInfo($value);
					$icon = '<img'.$info[2].' alt="" title="'.$info[1].$key.'" class="absmiddle" />';

						// Composing title-cell:
					if (preg_match('/^LLL:/', $value['tx_templavoila']['title'])) {
						$translatedTitle = $GLOBALS['LANG']->sL($value['tx_templavoila']['title']);
						$translateIcon = '<sup title="' . $GLOBALS['LANG']->getLL('displayDSTitleTranslated') . '">*</sup>';
					}
					else {
						$translatedTitle = $value['tx_templavoila']['title'];
						$translateIcon = '';
					}
					$this->elNames[$formPrefix.'['.$key.']']['tx_templavoila']['title'] = $icon.'<strong>'.htmlspecialchars(t3lib_div::fixed_lgd_cs($translatedTitle, 30)).'</strong>'.$translateIcon;
					$rowCells['title'] = '<img src="clear.gif" width="'.($level*16).'" height="1" alt="" />'.$this->elNames[$formPrefix.'['.$key.']']['tx_templavoila']['title'];

						// Description:
					$this->elNames[$formPrefix.'['.$key.']']['tx_templavoila']['description'] = $rowCells['description'] = htmlspecialchars($value['tx_templavoila']['description']);


						// In "mapping mode", render HTML page and Command links:
					if ($mappingMode)	{

							// HTML-path + CMD links:
						$isMapOK = 0;
						if ($currentMappingInfo[$key]['MAP_EL'])	{	// If mapping information exists...:
							if (isset($contentSplittedByMapping['cArray'][$key]))	{	// If mapping of this information also succeeded...:
								$cF = implode(chr(10),t3lib_div::trimExplode(chr(10),$contentSplittedByMapping['cArray'][$key],1));
								if (strlen($cF)>200)	{
									$cF = t3lib_div::fixed_lgd_cs($cF,90).' '.t3lib_div::fixed_lgd_cs($cF,-90);
								}

									// Render HTML path:
								list($pI) = $this->markupObj->splitPath($currentMappingInfo[$key]['MAP_EL']);
								$rowCells['htmlPath'] = '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/icon_ok2.gif', 'width="18" height="16"') . ' border="0" alt="" title="' . 
														htmlspecialchars($cF ? sprintf($GLOBALS['LANG']->getLL('displayDSContentFound'), strlen($contentSplittedByMapping['cArray'][$key])) . ($multilineTooltips ? ':' . chr(10) . chr(10) . $cF : '') : $GLOBALS['LANG']->getLL('displayDSContentEmpty')) . '" class="absmiddle" />'.
														'<img src="../html_tags/' . $pI['el'] . '.gif" height="9" border="0" alt="" hspace="3" class="absmiddle" title="---' . htmlspecialchars(t3lib_div::fixed_lgd_cs($currentMappingInfo[$key]['MAP_EL'], -80)) . '" />' .
														($pI['modifier'] ? $pI['modifier'] . ($pI['modifier_value'] ? ':' . ($pI['modifier'] != 'RANGE' ? $pI['modifier_value'] : '...') : '') : '');
								$rowCells['htmlPath'] = '<a href="'.$this->linkThisScript(array('htmlPath'=>$path.($path?'|':'').ereg_replace('\/[^ ]*$','',$currentMappingInfo[$key]['MAP_EL']),'showPathOnly'=>1)).'">'.$rowCells['htmlPath'].'</a>';

									// CMD links, default content:
								$rowCells['cmdLinks'] = '<span class="nobr"><input type="submit" value="Re-Map" name="_" onclick="document.location=\'' . 
														$this->linkThisScript(array(
																				'mapElPath' => $formPrefix . '[' . $key . ']', 
																				'htmlPath' => $path, 
																				'mappingToTags' => $value['tx_templavoila']['tags']
																				)) . '\';return false;" title="' . $GLOBALS['LANG']->getLL('buttonRemapTitle') . '" />' .
														'<input type="submit" value="' . $GLOBALS['LANG']->getLL('buttonChangeMode') . '" name="_" onclick="document.location=\'' .
														$this->linkThisScript(array(
																				'mapElPath' => $formPrefix . '[' . $key . ']',
																				'htmlPath' => $path . ($path ? '|' :'') . $pI['path'],
																				'doMappingOfPath' => 1
																				)) . '\';return false;" title="' . $GLOBALS['LANG']->getLL('buttonChangeMode') . '" /></span>';

									// If content mapped ok, set flag:
								$isMapOK=1;
							} else {	// Issue warning if mapping was lost:
								$rowCells['htmlPath'] = '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/icon_warning.gif', 'width="18" height="16"') . 
								' border="0" alt="" title="' . $GLOBALS['LANG']->getLL('msgNoContentFound') . '" class="absmiddle" />' . htmlspecialchars($currentMappingInfo[$key]['MAP_EL']);
							}
						} else {	// For non-mapped cases, just output a no-break-space:
							$rowCells['htmlPath'] = '&nbsp;';
						}

							// CMD links; Content when current element is under mapping, then display control panel or message:
						if ($this->mapElPath == $formPrefix.'['.$key.']')	{
							if ($this->doMappingOfPath)	{

									// Creating option tags:
								$lastLevel = end($pathLevels);
								$tagsMapping = $this->explodeMappingToTagsStr($value['tx_templavoila']['tags']);
								$mapDat = is_array($tagsMapping[$lastLevel['el']]) ? $tagsMapping[$lastLevel['el']] : $tagsMapping['*'];
								unset($mapDat['']);
								if (is_array($mapDat) && !count($mapDat))	unset($mapDat);

									// Create mapping options:
								$didSetSel=0;
								$opt=array();
								foreach($optDat as $k => $v)	{
									list($pI) = $this->markupObj->splitPath($k);

									if (($value['type']=='attr' && $pI['modifier']=='ATTR') || ($value['type']!='attr' && $pI['modifier']!='ATTR'))	{
										if (
												(!$this->markupObj->tags[$lastLevel['el']]['single'] || $pI['modifier']!='INNER') &&
												(!is_array($mapDat) || ($pI['modifier']!='ATTR' && isset($mapDat[strtolower($pI['modifier']?$pI['modifier']:'outer')])) || ($pI['modifier']=='ATTR' && (isset($mapDat['attr']['*']) || isset($mapDat['attr'][$pI['modifier_value']]))))

											)	{

											if($k==$currentMappingInfo[$key]['MAP_EL'])	{
												$sel = ' selected="selected"';
												$didSetSel=1;
											} else {
												$sel = '';
											}
											$opt[]='<option value="'.htmlspecialchars($k).'"'.$sel.'>'.htmlspecialchars($v).'</option>';
										}
									}
								}

									// Finally, put together the selector box:
								$rowCells['cmdLinks'] = '<img src="../html_tags/'.$lastLevel['el'].'.gif" height="9" border="0" alt="" class="absmiddle" title="---'.htmlspecialchars(t3lib_div::fixed_lgd_cs($lastLevel['path'],-80)).'" /><br />
									<select name="dataMappingForm'.$formPrefix.'['.$key.'][MAP_EL]">
										'.implode('
										',$opt).'
										<option value=""></option>
									</select>
									<br />
									<input type="submit" name="_save_data_mapping" value="' . $GLOBALS['LANG']->getLL('buttonSet') . '" />
									<input type="submit" name="_" value="' . $GLOBALS['LANG']->getLL('buttonCancel') . '" />';
								$rowCells['cmdLinks'].=
									$this->cshItem('xMOD_tx_templavoila','mapping_modeset',$this->doc->backPath,'',FALSE,'margin-bottom: 0px;');
							} else {
								$rowCells['cmdLinks'] = '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/icon_note.gif','width="18" height="16"') . ' border="0" alt="" class="absmiddle" />
														<strong>' . $GLOBALS['LANG']->getLL('msgHowToMap') . '</strong>';
								$rowCells['cmdLinks'].= '<br />
										<input type="submit" value="' . $GLOBALS['LANG']->getLL('buttonCancel') . '" name="_" onclick="document.location=\''.$this->linkThisScript(array()).'\';return false;" />';
							}
						} elseif (!$rowCells['cmdLinks'] && $mapOK && $value['type']!='no_map') {
							$rowCells['cmdLinks'] = '<input type="submit" value="' . $GLOBALS['LANG']->getLL('buttonMap') . '" name="_" onclick="document.location=\'' .
													$this->linkThisScript(array(
																			'mapElPath' => $formPrefix . '[' . $key . ']',
																			'htmlPath' => $path,
																			'mappingToTags' => $value['tx_templavoila']['tags']
																		)) . '\';return false;" />';
						}
					}

						// Display mapping rules:
					$rowCells['tagRules'] = implode('<br />', t3lib_div::trimExplode(',', strtolower($value['tx_templavoila']['tags']), 1));
					if (!$rowCells['tagRules'])	{
						$rowCells['tagRules'] = $GLOBALS['LANG']->getLL('all');
					}

						// Display edit/delete icons:
					if ($this->editDataStruct)	{
						$editAddCol = '<a href="' . $this->linkThisScript(array(
																		'DS_element' => $formPrefix . '[' . $key . ']'
																		)) . '">' .
										'<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/edit2.gif', 'width="11" height="12"') . 
										' hspace="2" border="0" alt="" title="' . $GLOBALS['LANG']->getLL('editEntry') . '" /></a>
										<a href="' . $this->linkThisScript(array(
																		'DS_element_DELETE' => $formPrefix . '[' . $key . ']'
																		)) . '">' .
										'<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/garbage.gif', 'width="11" height="12"') .
										' hspace="2" border="0" alt="" title="' . $GLOBALS['LANG']->getLL('deleteEntry') . '" onclick=" return confirm(\'' . $GLOBALS['LANG']->getLL('confirmDeleteEntry') . 
										'\');" /></a>';
						$editAddCol = '<td nowrap="nowrap">' . $editAddCol . '</td>';
					} else {
						$editAddCol = '';
					}

						// Description:
					if ($this->_preview)	{
						$rowCells['description'] = is_array($value['tx_templavoila']['sample_data']) ? t3lib_div::view_array($value['tx_templavoila']['sample_data']) : '[' . $GLOBALS['LANG']->getLL('noSampleData') . ']';
					}

						// Getting editing row, if applicable:
					list($addEditRows,$placeBefore) = $this->drawDataStructureMap_editItem($formPrefix,$key,$value,$level);

						// Add edit-row if found and destined to be set BEFORE:
					if ($addEditRows && $placeBefore)	{
						$tRows[]= $addEditRows;
					}
					else

						// Put row together
					if (!$this->mapElPath || $this->mapElPath == $formPrefix.'['.$key.']')	{
						$tRows[]='

							<tr class="' . ($rowIndex % 2 ? 'bgColor4' : 'bgColor6') . '">
							<td nowrap="nowrap" valign="top">'.$rowCells['title'].'</td>
							'.($this->editDataStruct ? '<td nowrap="nowrap">'.$key.'</td>' : '').'
							<td>'.$rowCells['description'].'</td>
							'.($mappingMode
									?
								'<td nowrap="nowrap">'.$rowCells['htmlPath'].'</td>
								<td>'.$rowCells['cmdLinks'].'</td>'
									:
								''
							).'
							<td>'.$rowCells['tagRules'].'</td>
							'.$editAddCol.'
						</tr>';
					}

						// Recursive call:
					if (($value['type']=='array') ||
						($value['type']=='section')) {
						$tRows = $this->drawDataStructureMap(
							$value['el'],
							$mappingMode,
							$currentMappingInfo[$key]['el'],
							$pathLevels,
							$optDat,
							$contentSplittedByMapping['sub'][$key],
							$level+1,
							$tRows,
							$formPrefix.'['.$key.'][el]',
							$path.($path?'|':'').$currentMappingInfo[$key]['MAP_EL'],
							$isMapOK
						);
					}
						// Add edit-row if found and destined to be set AFTER:
					if ($addEditRows && !$placeBefore)	{
						$tRows[]= $addEditRows;
					}
				}
			}
		}

		return $tRows;
	}

	/**
	 * Creates the editing row for a Data Structure element - when DS's are build...
	 *
	 * @param	string		Form element prefix
	 * @param	string		Key for form element
	 * @param	array		Values for element
	 * @param	integer		Indentation level
	 * @return	array		Two values, first is addEditRows (string HTML content), second is boolean whether to place row before or after.
	 */
	function drawDataStructureMap_editItem($formPrefix,$key,$value,$level)	{

			// Init:
		$addEditRows='';
		$placeBefore=0;

			// If editing command is set:
		if ($this->editDataStruct)	{
			if ($this->DS_element == $formPrefix.'['.$key.']')	{	// If the editing-command points to this element:

					// Initialize, detecting either "add" or "edit" (default) mode:
				$autokey='';
				if ($this->DS_cmd=='add')	{
					if (trim($this->fieldName)!='[' . htmlspecialchars($GLOBALS['LANG']->getLL('mapEnterNewFieldname')) . ']' && trim($this->fieldName)!='field_')	{
						$autokey = strtolower(ereg_replace('[^[:alnum:]_]', '', trim($this->fieldName)));
						if (isset($value['el'][$autokey]))	{
							$autokey .= '_' . substr(md5(microtime()), 0, 2);
						}
					} else {
						$autokey = 'field_' . substr(md5(microtime()), 0, 6);
					}

						// new entries are more offset
					$level = $level + 1;

					$formFieldName = 'autoDS'.$formPrefix.'['.$key.'][el]['.$autokey.']';
					$insertDataArray=array();
				} else {
					$placeBefore = 1;

					$formFieldName = 'autoDS'.$formPrefix.'['.$key.']';
					$insertDataArray=$value;
				}

				/* put these into array-form for preset-completition */
				$insertDataArray['tx_templavoila']['TypoScript_constants'] =
					$this->unflattenarray($insertDataArray['tx_templavoila']['TypoScript_constants']);
				$insertDataArray['TCEforms']['config'] =
					$this->unflattenarray($insertDataArray['TCEforms']['config']);

				/* do the preset-completition */
				$real = array($key => &$insertDataArray);
				$this->substEtypeWithRealStuff($real);

				/* ... */
				if (($insertDataArray['type'] == 'array') &&
					($insertDataArray['section']))
					$insertDataArray['type'] = 'section';

					// Create form:
				/* The basic XML-structure of an tx_templavoila-entry is:
				 *
				 * <tx_templavoila>
				 * 	<title>			-> Human readable title of the element
				 * 	<description>		-> A description explaining the elements function
				 * 	<sample_data>		-> Some sample-data (can't contain HTML)
				 * 	<eType>			-> The preset-type of the element, used to switch use/content of TCEforms/TypoScriptObjPath
				 * 	<oldStyleColumnNumber>	-> for distributing the fields across the tt_content column-positions
				 * </tx_templavoila>
				 */
				$form = '
				<dl id="dsel-general" class="DS-config">
					<!-- always present options +++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
					<dt><label>' . $GLOBALS['LANG']->getLL('renderDSO_title') . ':</label></dt>
					<dd><input type="text" size="80" name="' . $formFieldName . '[tx_templavoila][title]" value="' . htmlspecialchars($insertDataArray['tx_templavoila']['title']) . '" /></dd>

					<dt><label>' . $GLOBALS['LANG']->getLL('renderDSO_mappingInstructions') . ':</label></dt>
					<dd><input type="text" size="80" name="' . $formFieldName . '[tx_templavoila][description]" value="' . htmlspecialchars($insertDataArray['tx_templavoila']['description']) . '" /></dd>

					' . (($insertDataArray['type'] != 'array') &&
					($insertDataArray['type'] != 'section') ? '
					<!-- non-array options ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
					<dt><label>' . $GLOBALS['LANG']->getLL('mapSampleData') . ':</label></dt>
					<dd><textarea cols="80" rows="5" name="' . $formFieldName . '[tx_templavoila][sample_data][]">' . htmlspecialchars($insertDataArray['tx_templavoila']['sample_data'][0]) . '</textarea>
					' . $this->lipsumLink($formFieldName . '[tx_templavoila][sample_data]') . '</dd>

					<dt><label>' . $GLOBALS['LANG']->getLL('mapElementPreset') . ':</label></dt>
					<dd><select name="' . $formFieldName . '[tx_templavoila][eType]">
						<optgroup class="c-divider" label="' . $GLOBALS['LANG']->getLL('mapPresetGroups_tceFields') . '">
							<option value="input"' . ($insertDataArray['tx_templavoila']['eType']=='input' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_plainInput') . '</option>
							<option value="input_h"' . ($insertDataArray['tx_templavoila']['eType']=='input_h' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_headerField') . '</option>
							<option value="input_g"' . ($insertDataArray['tx_templavoila']['eType']=='input_g' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_gHederField') . '</option>
							<option value="text"' . ($insertDataArray['tx_templavoila']['eType']=='text' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_textarea') . '</option>
							<option value="rte"' . ($insertDataArray['tx_templavoila']['eType']=='rte' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_rte') . '</option>
							<option value="link"' . ($insertDataArray['tx_templavoila']['eType']=='link' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_linkField') . '</option>
							<option value="int"' . ($insertDataArray['tx_templavoila']['eType']=='int' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_integer') . '</option>
							<option value="image"' . ($insertDataArray['tx_templavoila']['eType']=='image' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_image') . '</option>
							<option value="imagefixed"' . ($insertDataArray['tx_templavoila']['eType']=='imagefixed' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_imageFixed') . '</option>
							<option value="select"' . ($insertDataArray['tx_templavoila']['eType']=='select' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_select') . '</option>
							<option value="ce"'. ($insertDataArray['tx_templavoila']['eType']=='ce' ? ' selected="selected"' : '') . '>' . sprintf($GLOBALS['LANG']->getLL('mapPresets_ce'), $insertDataArray['tx_templavoila']['oldStyleColumnNumber'] ? $insertDataArray['tx_templavoila']['oldStyleColumnNumber'] : $GLOBALS['LANG']->getLL('toBeDefined')) . '</option>
						</optgroup>
						<optgroup class="c-divider" label="' . $GLOBALS['LANG']->getLL('mapPresetGroups_ts') . '">
							<option value="TypoScriptObject"'.($insertDataArray['tx_templavoila']['eType']=='TypoScriptObject' ? ' selected="selected"' : '').'>' . $GLOBALS['LANG']->getLL('mapPresets_TSobjectPath') . '</option>
							<option value="none"'. ($insertDataArray['tx_templavoila']['eType']=='none' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_none') . '</option>
						</optgroup>
						<optgroup class="c-divider" label="' . $GLOBALS['LANG']->getLL('mapPresetGroups_other') . '">
							<option value="custom"'. ($insertDataArray['tx_templavoila']['eType']=='custom' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_customTCA') . '</option>
						</optgroup>
					</select><input type="hidden"
						name="'.$formFieldName.'[tx_templavoila][eType_before]"
						value="'.$insertDataArray['tx_templavoila']['eType'].'" /></dd>
					' :'').'

					<dt><label>Mapping rules:</label></dt>
					<dd><input type="text" size="80" name="'.$formFieldName.'[tx_templavoila][tags]" value="'.htmlspecialchars($insertDataArray['tx_templavoila']['tags']).'" /></dd>
				</dl>';

			/*	// The dam-tv-connector will substitute the text above, that's §$%*%&"$%, but well anyway, let's not break it
				if (count($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['cm1']['eTypesExtraFormFields']) > 0) {
				$form .= '
						<optgroup class="c-divider" label="Extra Elements">';
					foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['cm1']['eTypesExtraFormFields'] as $key => $value) {
							<option value="'.$key.'"'.($insertDataArray['tx_templavoila']['eType']==$key ? ' selected="selected"' : '').'>'.$key.'</option>
					}
				$form .= '
						</optgroup>';
				}	*/

				if (($insertDataArray['type'] != 'array') &&
					($insertDataArray['type'] != 'section')) {
					/* The Typoscript-related XML-structure of an tx_templavoila-entry is:
					 *
					 * <tx_templavoila>
					 *	<TypoScript_constants>	-> an array of constants that will be substituted in the <TypoScript>-element
					 * 	<TypoScript>		->
					 * </tx_templavoila>
					 */
					if ($insertDataArray['tx_templavoila']['eType'] != 'TypoScriptObject')
					$form .= '
					<dl id="dsel-ts" class="DS-config">
						<dt><label>' . $GLOBALS['LANG']->getLL('mapTSconstants') . ':</label></dt>
						<dd><textarea class="xml" cols="80" rows="10" name="'.$formFieldName.'[tx_templavoila][TypoScript_constants]">'.htmlspecialchars($this->flattenarray($insertDataArray['tx_templavoila']['TypoScript_constants'])).'</textarea></dd>
						<dt><label>' . $GLOBALS['LANG']->getLL('mapTScode') . ':</label></dt>
						<dd><textarea class="code" cols="80" rows="10" name="'.$formFieldName.'[tx_templavoila][TypoScript]">'.htmlspecialchars($insertDataArray['tx_templavoila']['TypoScript']).'</textarea></dd>
					</dl>';

					/* The Typoscript-related XML-structure of an tx_templavoila-entry is:
					 *
					 * <tx_templavoila>
					 * 	<TypoScriptObjPath>	->
					 * </tx_templavoila>
					 */
					if (($extra = $this->drawDataStructureMap_editItem_editTypeExtra(
						$insertDataArray['tx_templavoila']['eType'],
						$formFieldName.'[tx_templavoila][eType_EXTRA]',
						($insertDataArray['tx_templavoila']['eType_EXTRA'] ?	// Use eType_EXTRA only if it is set (could be modified, etc), otherwise use TypoScriptObjPath!
							$insertDataArray['tx_templavoila']['eType_EXTRA'] :
								($insertDataArray['tx_templavoila']['TypoScriptObjPath'] ?
								array('objPath' => $insertDataArray['tx_templavoila']['TypoScriptObjPath']) : ''))
						)))
					$form .= '
					<dl id="dsel-extra" class="DS-config">
						<dt>' . $GLOBALS['LANG']->getLL('mapExtraOptions') . '</dt>
						<dd>'.$extra.'</dd>
					</dl>';

					/* The process-related XML-structure of an tx_templavoila-entry is:
					 *
					 * <tx_templavoila>
					 * 	<proc>			-> define post-processes for this element's value
					 *		<int>		-> this element's value will be cast to an integer (if exist)
					 *		<HSC>		-> this element's value will convert special chars to HTML-entities (if exist)
					 *		<stdWrap>	-> an implicit stdWrap for this element, "stdWrap { ...inside... }"
					 * 	</proc>
					 * </tx_templavoila>
					 */
					$form .= '
					<dl id="dsel-proc" class="DS-config">
						<dt>' . $GLOBALS['LANG']->getLL('mapPostProcesses') . ':</dt>
						<dd>
							<input type="checkbox" class="checkbox" id="tv_proc_int" name="' . $formFieldName . '[tx_templavoila][proc][int]" value="1" ' . ($insertDataArray['tx_templavoila']['proc']['int'] ? 'checked="checked"' : '') . ' /> 
							<label for="tv_proc_int">' . $GLOBALS['LANG']->getLL('mapPPcastInteger') . '</label><br />
							<input type="checkbox" class="checkbox" id="tv_proc_hsc" name="' . $formFieldName . '[tx_templavoila][proc][HSC]" value="1" ' . ($insertDataArray['tx_templavoila']['proc']['HSC'] ? 'checked="checked"' : '') . ' /> 
							<label for="tv_proc_hsc">' . $GLOBALS['LANG']->getLL('mapPPhsc') . '</label>
						</dd>

						<dt><label>' . $GLOBALS['LANG']->getLL('mapCustomStdWrap') . ':</label></dt>
						<dd><textarea class="code" cols="80" rows="10" name="'.$formFieldName.'[tx_templavoila][proc][stdWrap]">'.htmlspecialchars($insertDataArray['tx_templavoila']['proc']['stdWrap']).'</textarea></dd>
					</dl>';

					/* The basic XML-structure of an TCEforms-entry is:
					 *
					 * <TCEforms>
					 * 	<label>			-> TCE-label for the BE
					 * 	<config>		-> TCE-configuration array
					 * </TCEforms>
					 */
					if ($insertDataArray['tx_templavoila']['eType'] != 'TypoScriptObject')
					$form .= '
					<dl id="dsel-tce" class="DS-config">
						<dt><label>' . $GLOBALS['LANG']->getLL('mapTCElabel') . ':</label></dt>
						<dd><input type="text" size="80" name="'.$formFieldName.'[TCEforms][label]" value="'.htmlspecialchars($insertDataArray['TCEforms']['label']).'" /></dd>

						<dt><label>' . $GLOBALS['LANG']->getLL('mapTCEconf') . ':</label></dt>
						<dd><textarea class="xml" cols="80" rows="10" name="'.$formFieldName.'[TCEforms][config]">'.htmlspecialchars($this->flattenarray($insertDataArray['TCEforms']['config'])).'</textarea></dd>

						<dt><label>' . $GLOBALS['LANG']->getLL('mapTCEextras') . ':</label></dt>
						<dd><input type="text" size="80" name="'.$formFieldName.'[TCEforms][defaultExtras]" value="'.htmlspecialchars($insertDataArray['TCEforms']['defaultExtras']).'" /></dd>
					</dl>';
				}

				$formSubmit = '
					<input type="hidden" name="DS_element" value="'.htmlspecialchars($this->DS_cmd=='add' ? $this->DS_element.'[el]['.$autokey.']' : $this->DS_element).'" />
					<input type="submit" name="_updateDS" value="'.($this->DS_cmd=='add' ? $GLOBALS['LANG']->getLL('buttonAdd') : $GLOBALS['LANG']->getLL('buttonUpdate')).'" />
					<!--	<input type="submit" name="'.$formFieldName.'" value="' . $GLOBALS['LANG']->getLL('buttonDelete') . ' (!)" />  -->
					<input type="submit" name="_" value="'.($this->DS_cmd=='add' ? $GLOBALS['LANG']->getLL('buttonCancel') : $GLOBALS['LANG']->getLL('buttonCancelClose')).'" onclick="document.location=\''.$this->linkThisScript().'\'; return false;" />
				';


				/* The basic XML-structure of an entry is:
				 *
				 * <element>
				 * 	<tx_templavoila>	-> entries with informational character belonging to this entry
				 * 	<TCEforms>		-> entries being used for TCE-construction
				 * 	<type + el + section>	-> subsequent hierarchical construction
				 *	<langOverlayMode>	-> ??? (is it the language-key?)
				 * </element>
				 */

					// Icons:
				$info = $this->dsTypeInfo($insertDataArray);

				$addEditRows='<tr class="tv-edit-row">
					<td valign="top" style="padding: 0.5em; padding-left: '.(($level)*16+3).'px" nowrap="nowrap">
						<select style="margin: 4px 0 4px 0; padding: 1px 1px 1px 30px; background: 0 50% url(' . $info[3] . ') no-repeat; width: 150px !important;" title="Mapping Type" name="'.$formFieldName.'[type]">
							<optgroup class="c-divider" label="' . $GLOBALS['LANG']->getLL('mapElContainers') . '">
								<option style="padding: 1px 1px 1px 30px; background: 0 50% url(' . $this->dsTypes['sc'][3] . ') no-repeat;" value="section"'. ($insertDataArray['type']=='section' ? ' selected="selected"' : '').'>' . $GLOBALS['LANG']->getLL('mapSection') . '</option>
								<option style="padding: 1px 1px 1px 30px; background: 0 50% url(' . $this->dsTypes['co'][3] . ') no-repeat;" value="array"'.   ($insertDataArray['type']=='array'   ? ' selected="selected"' : '').'>' . $GLOBALS['LANG']->getLL('mapContainer') . '</option>
							</optgroup>
							<optgroup class="c-divider" label="' . $GLOBALS['LANG']->getLL('mapElElements') . '">
								<option style="padding: 1px 1px 1px 30px; background: 0 50% url(' . $this->dsTypes['el'][3] . ') no-repeat;" value=""'.        ($insertDataArray['type']==''        ? ' selected="selected"' : '').'>' . $GLOBALS['LANG']->getLL('mapElement') . '</option>
								<option style="padding: 1px 1px 1px 30px; background: 0 50% url(' . $this->dsTypes['at'][3] . ') no-repeat;" value="attr"'.    ($insertDataArray['type']=='attr'    ? ' selected="selected"' : '').'>' . $GLOBALS['LANG']->getLL('mapAttribute') . '</option>
							</optgroup>
							<optgroup class="c-divider" label="' . $GLOBALS['LANG']->getLL('mapPresetGroups_other') . '">
								<option style="padding: 1px 1px 1px 30px; background: 0 50% url(' . $this->dsTypes['no'][3] . ') no-repeat;" value="no_map"'.  ($insertDataArray['type']=='no_map'  ? ' selected="selected"' : '').'>' . $GLOBALS['LANG']->getLL('mapNotMapped') . '</option>
							</optgroup>
						</select>
						<div style="margin: 0.25em;">' .
							($this->DS_cmd=='add' ? $autokey . ' <strong>(new)</strong>:<br />' : $key) .
						'</div>
						<input id="dsel-act" type="hidden" name="dsel_act" />
						<ul id="dsel-menu" class="DS-tree">
							<li><a id="dssel-general" class="active" href="#" onclick="" title="' . $GLOBALS['LANG']->getLL('mapEditConfiguration') . '">' . $GLOBALS['LANG']->getLL('mapConfiguration') . '</a>
								<ul>
									<li><a id="dssel-proc" href="#" title="' . $GLOBALS['LANG']->getLL('mapEditDataProcessing') . '">' . $GLOBALS['LANG']->getLL('mapDataProcessing') . '</a></li>
									<li><a id="dssel-ts" href="#" title="' . $GLOBALS['LANG']->getLL('mapEditTyposcript') . '">' . $GLOBALS['LANG']->getLL('mapTyposcript') . '</a></li>
									<li class="last-child"><a id="dssel-extra" href="#" title="' . $GLOBALS['LANG']->getLL('mapEditExtra') . '">' . $GLOBALS['LANG']->getLL('mapExtra') . '</a></li>
								</ul>
							</li>
							<li class="last-child"><a id="dssel-tce" href="#" title="' . $GLOBALS['LANG']->getLL('mapEditTCEform') . '">' . $GLOBALS['LANG']->getLL('mapTCEform') . '</a></li>
						</ul>
						' . $this->cshItem('xMOD_tx_templavoila', 'mapping_editform', $this->doc->backPath, '', FALSE, 'margin-bottom: 0px;') . '
					</td>
					<td valign="top" style="padding: 0.5em;" colspan="3">
						'.$form.'
						<script type="text/javascript">
							var dsel_act = "' . (t3lib_div::_GP('dsel_act') ? t3lib_div::_GP('dsel_act') : 'general') . '";
							var dsel_menu = [
								{"id" : "general",		"avail" : true,	"label" : "' . $GLOBALS['LANG']->getLL('mapConfiguration') . '",	"title" : "' . $GLOBALS['LANG']->getLL('mapEditConfiguration') . '",	"childs" : [
									{"id" : "ts",		"avail" : true,	"label" : "' . $GLOBALS['LANG']->getLL('mapDataProcessing') . '",	"title" : "' . $GLOBALS['LANG']->getLL('mapEditDataProcessing') . '"},
									{"id" : "extra",	"avail" : true,	"label" : "' . $GLOBALS['LANG']->getLL('mapTyposcript') . '",		"title" : "' . $GLOBALS['LANG']->getLL('mapEditTyposcript') . '"},
									{"id" : "proc",		"avail" : true,	"label" : "' . $GLOBALS['LANG']->getLL('mapExtra') . '",	"title" : "' . $GLOBALS['LANG']->getLL('mapEditExtra') . '"}]},
								{"id" : "tce",			"avail" : true,	"label" : "' . $GLOBALS['LANG']->getLL('mapTCEform') . '",		"title" : "' . $GLOBALS['LANG']->getLL('mapEditTCEform') . '"}
							];

							function dsel_menu_construct(dsul, dsmn) {
								if (dsul) {
									while (dsul.childNodes.length)
										dsul.removeChild(dsul.childNodes[0]);
									for (var el = 0, pos = 0; el < dsmn.length; el++) {
										var tab = document.getElementById("dsel-" + dsmn[el]["id"]);
										var stl = "none";
										if (tab) { if (dsmn[el]["avail"]) {
											var tx = document.createTextNode(dsmn[el]["label"]);
											var ac = document.createElement("a"); ac.appendChild(tx);
											var li = document.createElement("li"); li.appendChild(ac);
											ac.title = dsmn[el]["title"]; ac.href = "#dsel-menu"; ac.rel = dsmn[el]["id"];
											ac.className = (dsel_act == dsmn[el]["id"] ? "active" : "");
											ac.onclick = function() { dsel_act = this.rel; dsel_menu_reset(); };
											if (dsmn[el]["childs"]) {
												var ul = document.createElement("ul");
												dsel_menu_construct(ul, dsmn[el]["childs"]);
												li.appendChild(ul);
											}
											dsul.appendChild(li);
											stl = (dsel_act == dsmn[el]["id"] ? "" : "none");
										} tab.style.display = stl; }
									}
									if (dsul.lastChild)
										dsul.lastChild.className = "last-child";
								}
							}

							function dsel_menu_reset() {
								dsel_menu_construct(document.getElementById("dsel-menu"), dsel_menu);
								document.getElementById("dsel-act").value = dsel_act;
							}

							dsel_menu_reset();
						</script>
					</td>
					<td class="edit-ds-actioncontrols" colspan="3">
					' . $formSubmit . '
					</td>
				</tr>';
			} elseif (!$this->DS_element && ($value['type']=='array' || $value['type']=='section') && !$this->mapElPath) {
				$addEditRows='<tr class="bgColor4">
					<td colspan="7"><img src="clear.gif" width="'.(($level+1)*16).'" height="1" alt="" />'.
					'<input type="text" name="'.md5($formPrefix.'['.$key.']').'" value="[' . htmlspecialchars($GLOBALS['LANG']->getLL('mapEnterNewFieldname')) . ']" onfocus="if (this.value==\'[' . $GLOBALS['LANG']->getLL('mapEnterNewFieldname') . ']\'){this.value=\'field_\';}" />'.
					'<input type="submit" name="_" value="Add" onclick="document.location=\''.$this->linkThisScript(array('DS_element'=>$formPrefix.'['.$key.']','DS_cmd'=>'add')).'&amp;fieldName=\'+document.pageform[\''.md5($formPrefix.'['.$key.']').'\'].value; return false;" />'.
					$this->cshItem('xMOD_tx_templavoila','mapping_addfield',$this->doc->backPath,'',FALSE,'margin-bottom: 0px;').
					'</td>
				</tr>';
			}
		}

			// Return edit row:
		return array($addEditRows,$placeBefore);
	}

	/**
	 * Renders extra form fields for configuration of the Editing Types.
	 *
	 * @param	string		Editing Type string
	 * @param	string		Form field name prefix
	 * @param	array		Current values for the form field name prefix.
	 * @return	string		HTML with extra form fields
	 * @access	private
	 * @see drawDataStructureMap_editItem()
	 */
	function drawDataStructureMap_editItem_editTypeExtra($type, $formFieldName, $curValue)	{
			// If a user function was registered, use that instead of our own handlers:
		if (isset ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['cm1']['eTypesExtraFormFields'][$type])) {
			$_params = array (
				'type' => $type,
				'formFieldName' => $formFieldName,
				'curValue' => $curValue,
			);
			$output = t3lib_div::callUserFunction($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['cm1']['eTypesExtraFormFields'][$type], $_params, $this);
		} else {
			switch($type)	{
				case 'TypoScriptObject':
					$output = '
						<table border="0" cellpadding="2" cellspacing="0">
							<tr>
								<td>' . $GLOBALS['LANG']->getLL('mapObjectPath') . ':</td>
								<td><input type="text" name="'.$formFieldName.'[objPath]" value="'.htmlspecialchars($curValue['objPath'] ? $curValue['objPath'] : 'lib.myObject').'" /></td>
							</tr>
						</table>';
				break;
			}
		}
		return $output;
	}









	/****************************************************
	 *
	 * Helper-functions for File-based DS/TO creation
	 *
	 ****************************************************/

	/**
	 * When mapping HTML files to DS the field types are selected amount some presets - this function converts these presets into the actual settings needed in the DS
	 * Typically called like: ->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'],$contentSplittedByMapping['sub']['ROOT']);
	 * Notice: this function is used to preview XML also. In this case it is always called with $scope=0, so XML for 'ce' type will not contain wrap with TYPO3SEARCH_xxx. Currently there is no way to avoid it.
	 *
	 * @param	array		$elArray: Data Structure, passed by reference!
	 * @param	array		$v_sub: Actual template content splitted by Data Structure
	 * @param	int		$scope: Scope as defined in tx_templavoila_datastructure.scope
	 * @return	void		Note: The result is directly written in $elArray
	 * @see renderFile()
	 */
	function substEtypeWithRealStuff(&$elArray,$v_sub=array(),$scope = 0)	{

		$eTypeCECounter = 0;

		t3lib_div::loadTCA('tt_content');
        
			// Traverse array
		foreach($elArray as $key => $value)	{      
				// this MUST not ever enter the XMLs (it will break TV)
			if ($elArray[$key]['type'] == 'section') {
				$elArray[$key]['type'] = 'array';
				$elArray[$key]['section'] = '1';
			} else {
				$elArray[$key]['section'] = '0';
			}
			
			// put these into array-form for preset-completition
			if (!is_array($elArray[$key]['tx_templavoila']['TypoScript_constants'])) {
				$elArray[$key]['tx_templavoila']['TypoScript_constants'] = $this->unflattenarray($elArray[$key]['tx_templavoila']['TypoScript_constants']);
			}
			if (!is_array($elArray[$key]['TCEforms']['config'])) {
				$elArray[$key]['TCEforms']['config'] = $this->unflattenarray($elArray[$key]['TCEforms']['config']);
			}
			

			/* ---------------------------------------------------------------------- */
				// this is too much different to preserve any previous information
			$reset = isset($elArray[$key]['tx_templavoila']['eType_before']) &&
					($elArray[$key]['tx_templavoila']['eType_before'] !=
					$elArray[$key]['tx_templavoila']['eType']);

			unset($elArray[$key]['tx_templavoila']['eType_before']);
		//	unset($elArray[$key]['tx_templavoila']['proc']);

			/* ---------------------------------------------------------------------- */
			if (is_array ($elArray[$key]['tx_templavoila']['sample_data'])) {
				foreach ($elArray[$key]['tx_templavoila']['sample_data'] as $tmpKey => $tmpValue) {
					$elArray[$key]['tx_templavoila']['sample_data'][$tmpKey] = htmlspecialchars($tmpValue);
				}
			} else {
				$elArray[$key]['tx_templavoila']['sample_data']= htmlspecialchars($elArray[$key]['tx_templavoila']['sample_data']);
			}
            
			/* ---------------------------------------------------------------------- */
			if ($elArray[$key]['type']=='array')	{	// If array, then unset:
				unset($elArray[$key]['tx_templavoila']['sample_data']);
			} else {	// Only non-arrays can have configuration (that is elements and attributes)

					// Getting some information about the HTML content (eg. images width/height if applicable)
				$contentInfo = $this->substEtypeWithRealStuff_contentInfo(trim($v_sub['cArray'][$key]));

					// Based on the eType (the preset type) we make configuration settings.
					// If a user function was registered, use that instead of our own handlers:
				if (isset ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['cm1']['eTypesConfGen'][$elArray[$key]['tx_templavoila']['eType']])) {
					$_params = array (
						'key' => $key,
						'elArray' => &$elArray,
						'contentInfo' => $contentInfo,
					);

					$bef = $elArray[$key]['tx_templavoila']['TypoScript'];

					t3lib_div::callUserFunction($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['cm1']['eTypesConfGen'][$elArray[$key]['tx_templavoila']['eType']], $_params, $this,'');

					if (!$reset && trim($bef))
						$elArray[$key]['tx_templavoila']['TypoScript'] = $bef;
				} else {
					switch($elArray[$key]['tx_templavoila']['eType'])	{
						case 'text':
							/* preserve previous config, if of the right kind */
							if (($reset = $reset || ($elArray[$key]['TCEforms']['config']['type'] != 'text'))) {
								$elArray[$key]['TCEforms']['label']=$elArray[$key]['tx_templavoila']['title'];
							$elArray[$key]['TCEforms']['config'] = array(
								'type' => 'text',
								'cols' => '48',
								'rows' => '5',
							);
							}

							/* preserve previous config, if explicitly set */
							if (!isset($elArray[$key]['tx_templavoila']['proc']['HSC']))
							$elArray[$key]['tx_templavoila']['proc']['HSC']=1;
						break;
						case 'rte':
							/* preserve previous config, if of the right kind */
							if (($reset = $reset || ($elArray[$key]['TCEforms']['config']['type'] != 'text'))) {
								$elArray[$key]['TCEforms']['label']=$elArray[$key]['tx_templavoila']['title'];
							$elArray[$key]['TCEforms']['config'] = array(
								'type' => 'text',
								'cols' => '48',
								'rows' => '5',
								'softref' => (isset($GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['softref']) ?
												$GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['softref'] :
												'typolink_tag,images,email[subst],url'),
							);
							}

							/* preserve previous config, if explicitly set */
							if (!$elArray[$key]['TCEforms']['defaultExtras'])
							$elArray[$key]['TCEforms']['defaultExtras'] = 'richtext:rte_transform[flag=rte_enabled|mode=ts_css]';
							/* preserve previous config, if explicitly set */
							if (!isset($elArray[$key]['TCEforms']['proc']['HSC']))
							$elArray[$key]['tx_templavoila']['proc']['HSC']=0;

							/* preserve previous config, if of the right kind */
							if ($reset || !trim($elArray[$key]['tx_templavoila']['TypoScript'])) {
							$elArray[$key]['tx_templavoila']['TypoScript'] = '
	10 = TEXT
	10.current = 1
	10.parseFunc = < lib.parseFunc_RTE
					';			// Proper alignment (at least for the first level)
										}
						break;
						case 'image':
						case 'imagefixed':
							/* preserve previous config, if of the right kind */
							if (($reset = $reset || ($elArray[$key]['TCEforms']['config']['type'] != 'group'))) {
								$elArray[$key]['TCEforms']['label']=$elArray[$key]['tx_templavoila']['title'];
							$elArray[$key]['TCEforms']['config'] = array(
								'type' => 'group',
								'internal_type' => 'file',
								'allowed' => 'gif,png,jpg,jpeg',
								'max_size' => '1000',
								'uploadfolder' => 'uploads/tx_templavoila',
								'show_thumbs' => '1',
								'size' => '1',
								'maxitems' => '1',
								'minitems' => '0'
							);
							}

							$maxW = $contentInfo['img']['width'] ? $contentInfo['img']['width'] : 200;
							$maxH = $contentInfo['img']['height'] ? $contentInfo['img']['height'] : 150;
 							$typoScriptImageObject = ($elArray[$key]['type'] == 'attr') ? 'IMG_RESOURCE' : 'IMAGE';

							/* preserve previous config, if of the right kind */
							if ($reset || !trim($elArray[$key]['tx_templavoila']['TypoScript'])) {
							if ($elArray[$key]['tx_templavoila']['eType']=='image')	{
								$elArray[$key]['tx_templavoila']['TypoScript'] = '
	10 = '.$typoScriptImageObject.'
	10.file.import = uploads/tx_templavoila/
	10.file.import.current = 1
	10.file.import.listNum = 0
	10.file.maxW = '.$maxW.'
					';			// Proper alignment (at least for the first level)
							} else {
								$elArray[$key]['tx_templavoila']['TypoScript'] = '
	10 = '.$typoScriptImageObject.'
	10.file.XY = '.$maxW.','.$maxH.'
#	10.file.format = jpg
#	10.file.quality = 80
		10.file.import = uploads/tx_templavoila/
		10.file.import.current = 1
		10.file.import.listNum = 0
		10.file.maxW = '.$maxW.'
		10.file.minW = '.$maxW.'
		10.file.maxH = '.$maxH.'
		10.file.minH = '.$maxH.'
					';			// Proper alignment (at least for the first level)
	}
							}

								// Finding link-fields on same level and set the image to be linked by that TypoLink:
							$elArrayKeys = array_keys($elArray);
							foreach($elArrayKeys as $theKey)	{
								if ($elArray[$theKey]['tx_templavoila']['eType']=='link')	{
									$elArray[$key]['tx_templavoila']['TypoScript'].= '
	10.stdWrap.typolink.parameter.field = '.$theKey.'
					';			// Proper alignment (at least for the first level)
									break;
								}
							}
						break;
						case 'link':
							/* preserve previous config, if of the right kind */
							if (($reset = $reset || ($elArray[$key]['TCEforms']['config']['type'] != 'input'))) {
								$elArray[$key]['TCEforms']['label']=$elArray[$key]['tx_templavoila']['title'];
							$elArray[$key]['TCEforms']['config'] = array(
								'type' => 'input',
								'size' => '15',
								'max' => '256',
								'checkbox' => '',
								'eval' => 'trim',
								'wizards' => Array(
									'_PADDING' => 2,
									'link' => Array(
										'type' => 'popup',
										'title' => 'Link',
										'icon' => 'link_popup.gif',
										'script' => 'browse_links.php?mode=wizard',
										'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
									)
								)
							);
							}

							/* preserve previous config, if of the right kind */
							if ($reset || !trim($elArray[$key]['tx_templavoila']['TypoScript'])) {
							if ($elArray[$key]['type'] == 'attr') {
								$elArray[$key]['tx_templavoila']['TypoScript'] = '
	10 = TEXT
	10.typolink.parameter.current = 1
	10.typolink.returnLast = url
					';			// Proper alignment (at least for the first level)
									/* preserve previous config, if explicitly set */
									if (!isset($elArray[$key]['TCEforms']['proc']['HSC']))
								$elArray[$key]['tx_templavoila']['proc']['HSC']=1;
							}
							else {
								$elArray[$key]['tx_templavoila']['TypoScript'] = '
	10 = TEXT
	10.typolink.parameter.current = 1
					';			// Proper alignment (at least for the first level)
							}
							}
						break;
						case 'ce':    
							/* preserve previous config, if of the right kind */
							if (($reset || ($elArray[$key]['TCEforms']['config']['type'] != 'group'))) {
								$elArray[$key]['TCEforms']['label']=$elArray[$key]['tx_templavoila']['title'];
								$elArray[$key]['TCEforms']['config'] = array(
									'type' => 'group',
									'internal_type' => 'db',
									'allowed' => 'tt_content',
									'size' => '5',
									'maxitems' => '200',
									'minitems' => '0',
									'multiple' => '1',
									'show_thumbs' => '1'
								);
							}

							/* preserve previous config, if of the right kind */
							if ($reset || !trim($elArray[$key]['tx_templavoila']['TypoScript'])) {
							$elArray[$key]['tx_templavoila']['TypoScript'] = '
	10= RECORDS
	10.source.current=1
	10.tables = tt_content' . ($scope == 1 ? '
	10.wrap = <!--TYPO3SEARCH_begin--> | <!--TYPO3SEARCH_end-->' : '') . '
					';			// Proper alignment (at least for the first level)
							}

							$elArray[$key]['tx_templavoila']['oldStyleColumnNumber'] = $eTypeCECounter;
							$eTypeCECounter++;
						break;
						case 'int':
							/* preserve previous config, if of the right kind */
							if (($reset = $reset || ($elArray[$key]['TCEforms']['config']['type'] != 'input'))) {
								$elArray[$key]['TCEforms']['label']=$elArray[$key]['tx_templavoila']['title'];
							$elArray[$key]['TCEforms']['config'] = array(
								'type' => 'input',
								'size' => '4',
								'max' => '4',
								'eval' => 'int',
								'checkbox' => '0',
								'range' => Array (
									'upper' => '999',
									'lower' => '25'
								),
								'default' => 0
							);
							}
						break;
						case 'select':
							/* preserve previous config, if of the right kind */
							if (($reset = $reset || ($elArray[$key]['TCEforms']['config']['type'] != 'select'))) {
								$elArray[$key]['TCEforms']['label']=$elArray[$key]['tx_templavoila']['title'];
							$elArray[$key]['TCEforms']['config'] = array(
								'type' => 'select',
								'items' => Array (
									Array('', ''),
									Array('Value 1', 'Value 1'),
									Array('Value 2', 'Value 2'),
									Array('Value 3', 'Value 3'),
								),
								'default' => '0'
							);
							}
						break;
						case 'input':
						case 'input_h':
						case 'input_g':
							/* preserve previous config, if of the right kind */
							if (($reset = $reset || ($elArray[$key]['TCEforms']['config']['type'] != 'input'))) {
								$elArray[$key]['TCEforms']['label']=$elArray[$key]['tx_templavoila']['title'];
							$elArray[$key]['TCEforms']['config'] = array(
								'type' => 'input',
								'size' => '48',
								'eval' => 'trim',
							);
							}

							if ($elArray[$key]['tx_templavoila']['eType']=='input_h')	{	// Text-Header
									// Finding link-fields on same level and set the image to be linked by that TypoLink:
								$elArrayKeys = array_keys($elArray);
								foreach($elArrayKeys as $theKey)	{
									if ($elArray[$theKey]['tx_templavoila']['eType']=='link')	{
										$elArray[$key]['tx_templavoila']['TypoScript'] = '
	10 = TEXT
	10.current = 1
	10.typolink.parameter.field = '.$theKey.'
										';
									}
								}
							} elseif ($elArray[$key]['tx_templavoila']['eType']=='input_g')	{	// Graphical-Header

								$maxW = $contentInfo['img']['width'] ? $contentInfo['img']['width'] : 200;
								$maxH = $contentInfo['img']['height'] ? $contentInfo['img']['height'] : 20;

								$elArray[$key]['tx_templavoila']['TypoScript'] = '
	10 = IMAGE
	10.file = GIFBUILDER
	10.file {
	  XY = '.$maxW.','.$maxH.'
	  backColor = #999999

	  10 = TEXT
	  10.text.current = 1
	  10.text.case = upper
	  10.fontColor = #FFCC00
	  10.fontFile =  t3lib/fonts/vera.ttf
	  10.niceText = 0
	  10.offset = 0,14
	  10.fontSize = 14
	}
								';
							} else {	// Normal output.
								$elArray[$key]['tx_templavoila']['proc']['HSC']=1;
							}

							if ($reset)
								unset($elArray[$key]['tx_templavoila']['TypoScript']);
						break;
						case 'TypoScriptObject':
							unset($elArray[$key]['tx_templavoila']['TypoScript_constants']);
							unset($elArray[$key]['tx_templavoila']['TypoScript']);

							unset($elArray[$key]['TCEforms']['config']);

							/* preserve previous config, if of the right kind */
							if (($reset = $reset || ($elArray[$key]['tx_templavoila']['TypoScriptObjPath'] == ''))) {
							$elArray[$key]['tx_templavoila']['TypoScriptObjPath'] =
								($elArray[$key]['tx_templavoila']['eType_EXTRA']['objPath'] ?
									$elArray[$key]['tx_templavoila']['eType_EXTRA']['objPath'] :
									($elArray[$key]['tx_templavoila']['TypoScriptObjPath'] ?
										$elArray[$key]['tx_templavoila']['TypoScriptObjPath'] : ''));
							}
						break;
						case 'none':
							unset($elArray[$key]['TCEforms']['config']);
						break;
					}
				}	// End switch else
				if ($elArray[$key]['tx_templavoila']['eType'] != 'TypoScriptObject') {
					if (isset($elArray[$key]['tx_templavoila']['TypoScriptObjPath'])) {
						unset($elArray[$key]['tx_templavoila']['TypoScriptObjPath']);
					}
				}
				else if (isset($elArray[$key]['tx_templavoila']['eType_EXTRA']['objPath'])) {
					unset($elArray[$key]['tx_templavoila']['eType_EXTRA']['objPath']);
					if (count($elArray[$key]['tx_templavoila']['eType_EXTRA']) == 0) {
						unset($elArray[$key]['tx_templavoila']['eType_EXTRA']);
					}
				}

					// Setting TCEforms title for element if configuration is found:
				if (!is_array($elArray[$key]['TCEforms']['config'])) {
					unset($elArray[$key]['TCEforms']);
				}
			}

				// Apart from converting eType to configuration, we also clean up other aspects:
			if (!$elArray[$key]['type'])
				unset($elArray[$key]['type']);
			if (!$elArray[$key]['section'])
				unset($elArray[$key]['section']);
			else {
				unset($elArray[$key]['tx_templavoila']['TypoScript_constants']);
				unset($elArray[$key]['tx_templavoila']['TypoScript']);
				unset($elArray[$key]['tx_templavoila']['proc']);
				unset($elArray[$key]['TCEforms']);
			}

			if (!$elArray[$key]['tx_templavoila']['description'])
				unset($elArray[$key]['tx_templavoila']['description']);
			if (!$elArray[$key]['tx_templavoila']['tags'])
				unset($elArray[$key]['tx_templavoila']['tags']);
			if (!$elArray[$key]['tx_templavoila']['TypoScript_constants'])
				unset($elArray[$key]['tx_templavoila']['TypoScript_constants']);
			if (!$elArray[$key]['TCEforms']['defaultExtras'])
				unset($elArray[$key]['TCEforms']['defaultExtras']);

				// Run this function recursively if needed:
			if (is_array($elArray[$key]['el']))	{
				$this->substEtypeWithRealStuff($elArray[$key]['el'],$v_sub['sub'][$key],$scope);
			}
		}	// End loop
	}

	/**
	 * Analyzes the input content for various stuff which can be used to generate the DS.
	 * Basically this tries to intelligently guess some settings.
	 *
	 * @param	string		HTML Content string
	 * @return	array		Configuration
	 * @see substEtypeWithRealStuff()
	 */
	function substEtypeWithRealStuff_contentInfo($content)	{
		if ($content)	{
			if (substr($content,0,4)=='<img')	{
				$attrib = t3lib_div::get_tag_attributes($content);
				if ((!$attrib['width'] || !$attrib['height']) && $attrib['src'])	{
					$pathWithNoDots = t3lib_div::resolveBackPath($attrib['src']);
					$filePath = t3lib_div::getFileAbsFileName($pathWithNoDots);
					if ($filePath && @is_file($filePath))	{
						$imgInfo = @getimagesize($filePath);

						if (!$attrib['width'])	$attrib['width']=$imgInfo[0];
						if (!$attrib['height'])	$attrib['height']=$imgInfo[1];
					}
				}
				return array('img'=>$attrib);
			}
		}
		return false;
	}















	/*******************************
	 *
	 * Various helper functions
	 *
	 *******************************/

	/**
	 * Returns Data Structure from the $datString
	 *
	 * @param	string		XML content which is parsed into an array, which is returned.
	 * @param	string		Absolute filename from which to read the XML data. Will override any input in $datString
	 * @return	mixed		The variable $dataStruct. Should be array. If string, then no structures was found and the function returns the XML parser error.
	 */
	function getDataStructFromDSO($datString,$file='')	{
		if ($file)	{
			$dataStruct = t3lib_div::xml2array(t3lib_div::getUrl($file));
		} else {
			$dataStruct = t3lib_div::xml2array($datString);
		}
		return $dataStruct;
	}

	/**
	 * Creating a link to the display frame for display of the "HTML-path" given as $path
	 *
	 * @param	string		The text to link
	 * @param	string		The path string ("HTML-path")
	 * @return	string		HTML link, pointing to the display frame.
	 */
	function linkForDisplayOfPath($title,$path)	{
		$theArray=array(
			'file' => $this->markupFile,
			'path' => $path,
			'mode' => 'display'
		);
		$p = t3lib_div::implodeArrayForUrl('',$theArray);

		$content.='<strong><a href="'.htmlspecialchars('index.php?'.$p).'" target="display">'.$title.'</a></strong>';
		return $content;
	}

	/**
	 * Creates a link to this script, maintaining the values of the displayFile, displayTable, displayUid variables.
	 * Primarily used by ->drawDataStructureMap
	 *
	 * @param	array		Overriding parameters.
	 * @return	string		URL, already htmlspecialchars()'ed
	 * @see drawDataStructureMap()
	 */
	function linkThisScript($array=array())	{
		$theArray=array(
			'file' => $this->displayFile,
			'table' => $this->displayTable,
			'uid' => $this->displayUid,
			'returnUrl' => $this->returnUrl
		);
		$p = t3lib_div::implodeArrayForUrl('',array_merge($theArray,$array),'',1);

		return htmlspecialchars('index.php?'.$p);
	}

	/**
	 * Creates the HTML code for the IFRAME in which the display mode is shown:
	 *
	 * @param	string		File name to display in exploded mode.
	 * @param	string		HTML-page
	 * @param	string		Tags which is the only ones to show
	 * @param	boolean		If set, the template is only shown, mapping links disabled.
	 * @param	boolean		Preview enabled.
	 * @return	string		HTML code for the IFRAME.
	 * @see main_display()
	 */
	function makeIframeForVisual($file,$path,$limitTags,$showOnly,$preview=0)	{
		$url = 'index.php?mode=display'.
				'&file='.rawurlencode($file).
				'&path='.rawurlencode($path).
				'&preview='.($preview?1:0).
				($showOnly?'&show=1':'&limitTags='.rawurlencode($limitTags));
		return '<iframe width="100%" height="500" src="'.htmlspecialchars($url).'#_MARKED_UP_ELEMENT" style="border: 1xpx solid black;"></iframe>';
	}

	/**
	 * Converts a list of mapping rules to an array
	 *
	 * @param	string		Mapping rules in a list
	 * @param	boolean		If set, then the ALL rule (key "*") will be unset.
	 * @return	array		Mapping rules in a multidimensional array.
	 */
	function explodeMappingToTagsStr($mappingToTags,$unsetAll=0)	{
		$elements = t3lib_div::trimExplode(',',strtolower($mappingToTags));
		$output=array();
		foreach($elements as $v)	{
			$subparts = t3lib_div::trimExplode(':',$v);
			$output[$subparts[0]][$subparts[1]][($subparts[2]?$subparts[2]:'*')]=1;
		}
		if ($unsetAll)	unset($output['*']);
		return $output;
	}

	/**
	 * General purpose unsetting of elements in a multidimensional array
	 *
	 * @param	array		Array from which to remove elements (passed by reference!)
	 * @param	array		An array where the values in the specified order points to the position in the array to unset.
	 * @return	void
	 */
	function unsetArrayPath(&$dataStruct,$ref)	{
		$key = array_shift($ref);

		if (!count($ref))	{
			unset($dataStruct[$key]);
		} elseif (is_array($dataStruct[$key]))	{
			$this->unsetArrayPath($dataStruct[$key],$ref);
		}
	}

	/**
	 * Function to clean up "old" stuff in the currentMappingInfo array. Basically it will remove EVERYTHING which is not known according to the input Data Structure
	 *
	 * @param	array		Current Mapping info (passed by reference)
	 * @param	array		Data Structure
	 * @return	void
	 */
	function cleanUpMappingInfoAccordingToDS(&$currentMappingInfo,$dataStruct)	{
		if (is_array($currentMappingInfo))	{
			foreach($currentMappingInfo as $key => $value)	{
				if (!isset($dataStruct[$key]))	{
					unset($currentMappingInfo[$key]);
				} else {
					if (is_array($dataStruct[$key]['el']))	{
						$this->cleanUpMappingInfoAccordingToDS($currentMappingInfo[$key]['el'],$dataStruct[$key]['el']);
					}
				}
			}
		}
	}

	/**
	 * Generates $this->storageFolders with available sysFolders linked to as storageFolders for the user
	 *
	 * @return	void		Modification in $this->storageFolders array
	 */
	function findingStorageFolderIds()	{
		global $TYPO3_DB;

			// Init:
		$readPerms = $GLOBALS['BE_USER']->getPagePermsClause(1);
		$this->storageFolders=array();

			// Looking up all references to a storage folder:
		$res = $TYPO3_DB->exec_SELECTquery (
			'uid,storage_pid',
			'pages',
			'storage_pid>0'.t3lib_BEfunc::deleteClause('pages')
		);
		while(false !== ($row = $TYPO3_DB->sql_fetch_assoc($res)))	{
			if ($GLOBALS['BE_USER']->isInWebMount($row['storage_pid'],$readPerms))	{
				$storageFolder = t3lib_BEfunc::getRecord('pages',$row['storage_pid'],'uid,title');
				if ($storageFolder['uid'])	{
					$this->storageFolders[$storageFolder['uid']] = $storageFolder['title'];
				}
			}
		}

			// Compopsing select list:
		$sysFolderPIDs = array_keys($this->storageFolders);
		$sysFolderPIDs[]=0;
		$this->storageFolders_pidList = implode(',',$sysFolderPIDs);
	}







	/*****************************************
	 *
	 * DISPLAY mode
	 *
	 *****************************************/

	/**
	 * Outputs the display of a marked-up HTML file in the IFRAME
	 *
	 * @return	void		Exits before return
	 * @see makeIframeForVisual()
	 */
	function main_display()	{

			// Setting GPvars:
		$this->displayFile = t3lib_div::_GP('file');
		$this->show = t3lib_div::_GP('show');
		$this->preview = t3lib_div::_GP('preview');
		$this->limitTags = t3lib_div::_GP('limitTags');
		$this->path = t3lib_div::_GP('path');

			// Checking if the displayFile parameter is set:
		if (@is_file($this->displayFile) && t3lib_div::getFileAbsFileName($this->displayFile))		{	// FUTURE: grabbing URLS?: 		.... || substr($this->displayFile,0,7)=='http://'
			$content = t3lib_div::getUrl($this->displayFile);
			if ($content)	{
				$relPathFix = $GLOBALS['BACK_PATH'].'../'.dirname(substr($this->displayFile,strlen(PATH_site))).'/';

				if ($this->preview)	{	// In preview mode, merge preview data into the template:
						// Add preview data to file:
					$content = $this->displayFileContentWithPreview($content,$relPathFix);
				} else {
						// Markup file:
					$content = $this->displayFileContentWithMarkup($content,$this->path,$relPathFix,$this->limitTags);
				}
					// Output content:
				echo $content;
			} else {
				$this->displayFrameError($GLOBALS['LANG']->getLL('errorNoContentInFile') . ': <em>'.htmlspecialchars($this->displayFile).'</em>');
			}
		} else {
			$this->displayFrameError($GLOBALS['LANG']->getLL('errorNoFileToDisplay'));
		}

			// Exit since a full page has been outputted now.
		exit;
	}

	/**
	 * This will mark up the part of the HTML file which is pointed to by $path
	 *
	 * @param	string		The file content as a string
	 * @param	string		The "HTML-path" to split by
	 * @param	string		The rel-path string to fix images/links with.
	 * @param	string		List of tags to show
	 * @return	void		Exits...
	 * @see main_display()
	 */
	function displayFileContentWithMarkup($content,$path,$relPathFix,$limitTags)	{
		$markupObj = t3lib_div::makeInstance('tx_templavoila_htmlmarkup');
		$markupObj->gnyfImgAdd = $this->show ? '' : 'onclick="return parent.updPath(\'###PATH###\');"';
		$markupObj->pathPrefix = $path?$path.'|':'';
		$markupObj->onlyElements = $limitTags;

#		$markupObj->setTagsFromXML($content);

		$cParts = $markupObj->splitByPath($content,$path);
		if (is_array($cParts))	{
			$cParts[1] = $markupObj->markupHTMLcontent(
							$cParts[1],
							$GLOBALS['BACK_PATH'],
							$relPathFix,
							implode(',',array_keys($markupObj->tags)),
							$this->MOD_SETTINGS['displayMode']
						);
			$cParts[0] = $markupObj->passthroughHTMLcontent($cParts[0],$relPathFix,$this->MOD_SETTINGS['displayMode']);
			$cParts[2] = $markupObj->passthroughHTMLcontent($cParts[2],$relPathFix,$this->MOD_SETTINGS['displayMode']);
			if (trim($cParts[0]))	{
				$cParts[1]='<a name="_MARKED_UP_ELEMENT"></a>'.$cParts[1];
			}
			return implode('',$cParts);
		}
		$this->displayFrameError($cParts);
		return '';
	}

	/**
	 * This will add preview data to the HTML file used as a template according to the currentMappingInfo
	 *
	 * @param	string		The file content as a string
	 * @param	string		The rel-path string to fix images/links with.
	 * @return	void		Exits...
	 * @see main_display()
	 */
	function displayFileContentWithPreview($content,$relPathFix)	{

			// Getting session data to get currentMapping info:
		$sesDat = $GLOBALS['BE_USER']->getSessionData($this->MCONF['name'].'_mappingInfo');
		$currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : array();

			// Init mark up object.
		$this->markupObj = t3lib_div::makeInstance('tx_templavoila_htmlmarkup');
		$this->markupObj->htmlParse = t3lib_div::makeInstance('t3lib_parsehtml');

			// Splitting content, adding a random token for the part to be previewed:
		$contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($content,$currentMappingInfo);
		$token = md5(microtime());
		$content = $this->markupObj->mergeSampleDataIntoTemplateStructure($sesDat['dataStruct'],$contentSplittedByMapping,$token);

			// Exploding by that token and traverse content:
		$pp = explode($token,$content);
		foreach($pp as $kk => $vv)	{
			$pp[$kk] = $this->markupObj->passthroughHTMLcontent($vv,$relPathFix,$this->MOD_SETTINGS['displayMode'],$kk==1?'font-size:11px; color:#000066;':'');
		}

			// Adding a anchor point (will work in most cases unless put into a table/tr tag etc).
		if (trim($pp[0]))	{
			$pp[1]='<a name="_MARKED_UP_ELEMENT"></a>'.$pp[1];
		}

			// Implode content and return it:
		return implode('',$pp);
	}

	/**
	 * Outputs a simple HTML page with an error message
	 *
	 * @param	string		Error message for output in <h2> tags
	 * @return	void		Echos out an HTML page.
	 */
	function displayFrameError($error)	{
			echo '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
	<title>Untitled</title>
</head>

<body bgcolor="#eeeeee">
<h2>ERROR: '.$error.'</h2>
</body>
</html>
			';
	}

	/**
	 * Wrapper function for context sensitive help - for downwards compatibility with TYPO3 prior 3.7.x
	 *
	 * @param	string		Table name ('_MOD_'+module name)
	 * @param	string		Field name (CSH locallang main key)
	 * @param	string		Back path
	 * @param	string		Wrap code for icon-mode, splitted by "|". Not used for full-text mode.
	 * @param	boolean		If set, the full text will never be shown (only icon). Useful for places where it will break the page if the table with full text is shown.
	 * @param	string		Additional style-attribute content for wrapping table (full text mode only)
	 * @return	string		HTML content for help text
	 */
	function cshItem($table,$field,$BACK_PATH,$wrap='',$onlyIconMode=FALSE, $styleAttrib='')	{
		if (is_callable (array ('t3lib_BEfunc','cshItem'))) {
			return t3lib_BEfunc::cshItem ($table,$field,$BACK_PATH,$wrap,$onlyIconMode, $styleAttrib);
		}
		return '';
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$formElementName: ...
	 * @return	[type]		...
	 */
	function lipsumLink($formElementName)	{
		if (t3lib_extMgm::isLoaded('lorem_ipsum'))	{
			$LRobj = t3lib_div::makeInstance('tx_loremipsum_wiz');
			$LRobj->backPath = $this->doc->backPath;

			$PA = array(
				'fieldChangeFunc' => array(),
				'formName' => 'pageform',
				'itemName' => $formElementName.'[]',
				'params' => array(
#					'type' => 'header',
					'type' => 'description',
					'add' => 1,
					'endSequence' => '46,32',
				)
			);

			return $LRobj->main($PA,'ID:templavoila');
		}
		return '';
	}

	function buildCachedMappingInfo_head($currentMappingInfo_head, $html_header) {
		$h_currentMappingInfo=array();
		if (is_array($currentMappingInfo_head['headElementPaths']))	{
			foreach($currentMappingInfo_head['headElementPaths'] as $kk => $vv)	{
				$h_currentMappingInfo['el_'.$kk]['MAP_EL'] = $vv;
			}
		}

		return $this->markupObj->splitContentToMappingInfo($html_header,$h_currentMappingInfo);
	}

	/**
	 * Checks if link points to local marker or not and sets prefix accordingly.
	 *
	 * @param	string	$relPathFix	Prefix
	 * @param	string	$fileContent	Content
	 * @param	string	$uniqueMarker	Marker inside links
	 * @return	string	Content
	 */
	function fixPrefixForLinks($relPathFix, $fileContent, $uniqueMarker) {
		$parts = explode($uniqueMarker, $fileContent);
		$count = count($parts);
		if ($count > 1) {
			for ($i = 1; $i < $count; $i++) {
				if ($parts[$i]{0} != '#') {
					$parts[$i] = $relPathFix . $parts[$i];
				}
			}
		}
		return implode($parts);
	}

}

if (!function_exists('md5_file')) {
	function md5_file($file, $raw = false) {
		return md5(file_get_contents($file), $raw);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/cm1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/cm1/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('tx_templavoila_cm1');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
