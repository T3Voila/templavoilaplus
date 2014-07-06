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
 * @author Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @co-author    Robert Lemke <robert@typo3.org>
 */

// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require(dirname(__FILE__) . '/conf.php');
require($BACK_PATH . 'init.php');
$LANG->includeLLFile('EXT:templavoila/cm1/locallang.xml');

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
 * @author Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @co-author    Robert Lemke <robert@typo3.org>
 * @package TYPO3
 * @subpackage tx_templavoila
 */
class tx_templavoila_cm1 extends \TYPO3\CMS\Backend\Module\BaseScriptClass {

	/**
	 * @var string
	 */
	protected $DS_element_DELETE;

	/**
	 * @var array
	 */
	protected $pageinfo;

	/**
	 * @var string
	 */
	protected $sessionKey;

	/**
	 * @var string
	 */
	protected $backPath;

	// Static:
	public $theDisplayMode = ''; // Set to ->MOD_SETTINGS[]
	public $head_markUpTags = array(
		// Block elements:
		'title' => array(),
		'script' => array(),
		'style' => array(),
		// Single elements:

		'link' => array('single' => 1),
		'meta' => array('single' => 1),
	);

	public $extKey = 'templavoila'; // Extension key of this module
	public $dsTypes;

	// Internal, dynamic:
	public $markupFile = ''; // Used to store the name of the file to mark up with a given path.

	/**
	 * @var \Extension\Templavoila\Domain\Model\HtmlMarkup
	 */
	public $markupObj;

	public $elNames = array();

	public $editDataStruct = 0; // Setting whether we are editing a data structure or not.
	public $storageFolders = array(); // Storage folders as key(uid) / value (title) pairs.
	public $storageFolders_pidList = 0; // The storageFolders pids imploded to a comma list including "0"

	// GPvars:
	public $mode; // Looking for "&mode", which defines if we draw a frameset (default), the module (mod) or display (display)

	// GPvars for MODULE mode
	public $displayFile = ''; // (GPvar "file", shared with DISPLAY mode!) The file to display, if file is referenced directly from filelist module. Takes precedence over displayTable/displayUid
	public $displayTable = ''; // (GPvar "table") The table from which to display element (Data Structure object [tx_templavoila_datastructure], template object [tx_templavoila_tmplobj])
	public $displayUid = ''; // (GPvar "uid") The UID to display (from ->displayTable)
	public $displayPath = ''; // (GPvar "htmlPath") The "HTML-path" to display from the current file
	public $returnUrl = ''; // (GPvar "returnUrl") Return URL if the script is supplied with that.

	// GPvars for MODULE mode, specific to mapping a DS:
	public $_preview;

	public $htmlPath;

	public $mapElPath;

	public $doMappingOfPath;

	public $showPathOnly;

	public $mappingToTags;

	public $DS_element;

	public $DS_cmd;

	public $fieldName;

	// GPvars for MODULE mode, specific to creating a DS:
	public $_load_ds_xml_content;

	public $_load_ds_xml_to;

	public $_saveDSandTO_TOuid;

	public $_saveDSandTO_title;

	public $_saveDSandTO_type;

	public $_saveDSandTO_pid;

	// GPvars for DISPLAY mode:
	public $show; // Boolean; if true no mapping-links are rendered.
	public $preview; // Boolean; if true, the currentMappingInfo preview data is merged in
	public $limitTags; // String, list of tags to limit display by
	public $path; // HTML-path to explode in template.

	/**
	 * @var \tx_templavoila_cm1_dsEdit
	 */
	public $dsEdit; // instance of class tx_templavoila_cm1_dsEdit

	/**
	 * @var \tx_templavoila_cm1_eTypes
	 */
	public $eTypes; // instance of class tx_templavoila_cm1_eTypes

	public $extConf; // holds the extconf configuration
	public $staticDS = FALSE; // Boolean; if true DS records are file based

	public static $gnyfStyleBlock = '
	.gnyfBox { position:relative; }
	.gnyfElement {	color: black; font-family:monospace;font-size:12px !important; line-height:1.3em !important; font-weight:normal; text-transform:none; letter-spacing:auto; cursor: pointer; margin: 0; padding:0 7px; overflow: hidden; text-align: center; position: absolute;  border-radius: 0.4em; -o-border-radius: 0.4em; -moz-border-radius: 0.4em; -webkit-border-radius: 0.4em; background-color: #ffffff;	}
	.dso_table .gnyfElement { position: relative; }
	span.gnyfElement:hover {	z-index: 100;	box-shadow: rgba(0, 0, 0, 0.5) 0 0 4px 2px;	-o-box-shadow: rgba(0, 0, 0, 0.5) 0 0 4px 2px;	-moz-box-shadow: rgba(0, 0, 0, 0.5) 0 0 4px 2px;	-webkit-box-shadow: rgba(0, 0, 0, 0.5) 0 0 4px 2px;	}
	a > span.gnyfElement, td > span.gnyfElement {	position:relative;	}
	a > .gnyfElement:hover, td > .gnyfElement:hover  { box-shadow: none;	-o-box-shadow: none;	-moz-box-shadow: none;	-webkit-box-shadow: none;	}
	.gnyfRoot { background-color:#9bff9b; }
	.gnyfDocument { background-color:#788cff; }
	.gnyfText { background-color:#ffff64; }
	.gnyfGrouping { background-color:#ff9650; }
	.gnyfForm { background-color:#64ff64; }
	.gnyfSections { background-color:#a0afff; }
	.gnyfInterative { background-color:#0096ff; }
	.gnyfTable { background-color:#ff9664; }
	.gnyfEmbedding { background-color:#ff96ff; }
	.gnyfInteractive { background-color: #d3d3d3; }
';

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return void
	 */
	public function menuConfig() {
		$this->MOD_MENU = Array(
			'displayMode' => array(
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
	 * @param array $conf
	 *
	 * @return array
	 */
	public function dsTypeInfo($conf) {
		// Icon:
		if ($conf['type'] == 'section') {
			return $this->dsTypes['sc'];
		}

		if ($conf['type'] == 'array') {
			if (!$conf['section']) {
				return $this->dsTypes['co'];
			}

			return $this->dsTypes['sc'];
		}

		if ($conf['type'] == 'attr') {
			return $this->dsTypes['at'];
		}

		if ($conf['type'] == 'no_map') {
			return $this->dsTypes['no'];
		}

		return $this->dsTypes['el'];
	}

	/**
	 * Main function, distributes the load between the module and display modes.
	 * "Display" mode is when the exploded template file is shown in an IFRAME
	 *
	 * @return void
	 */
	public function main() {

		// Initialize ds_edit
		$this->dsEdit = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj('tx_templavoila_cm1_dsedit', '');
		$this->dsEdit->init($this);

		// Initialize eTypes
		$this->eTypes = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj('tx_templavoila_cm1_eTypes', '');
		$this->eTypes->init($this);

		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);
		$this->staticDS = ($this->extConf['staticDS.']['enable']);

		// Setting GPvars:
		$this->mode = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('mode');

		// Selecting display or module mode:
		switch ((string) $this->mode) {
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
	 * @return void
	 */
	public function printContent() {
		$this->content .= $this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Makes a context-free xml-string from an array.
	 *
	 * @param array $array
	 * @param string $pfx
	 *
	 * @return string
	 */
	public function flattenarray($array, $pfx = '') {
		if (!is_array($array)) {
			if (is_string($array)) {
				return $array;
			} else {
				return '';
			}
		}

		return str_replace("<>\n", '', str_replace("</>", '', \TYPO3\CMS\Core\Utility\GeneralUtility::array2xml($array, '', -1, '', 0, array('useCDATA' => 1))));
	}

	/**
	 * Makes an array from a context-free xml-string.
	 *
	 * @param string $string
	 *
	 * @return array
	 */
	public function unflattenarray($string) {
		if (!is_string($string) || !trim($string)) {
			if (is_array($string)) {
				return $string;
			} else {
				return array();
			}
		}

		return \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array('<grouped>' . $string . '</grouped>');
	}

	/**
	 * Merges two arrays recursively and "binary safe" (integer keys are overridden as well), overruling similar values in the first array ($arr0) with the values of the second array ($arr1)
	 * In case of identical keys, ie. keeping the values of the second.
	 * Usage: 0
	 *
	 * @param array $arr0 First array
	 * @param array $arr1 Second array, overruling the first array
	 * @param integer $notAddKeys If set, keys that are NOT found in $arr0 (first array) will not be set. Thus only existing value can/will be overruled from second array.
	 * @param boolean $includeEmtpyValues If set, values from $arr1 will overrule if they are empty or zero. Default: true
	 * @param boolean $kill If set, anything will override arrays in $arr0
	 *
	 * @return array Resulting array where $arr1 values has overruled $arr0 values
	 */
	public function array_merge_recursive_overrule($arr0, $arr1, $notAddKeys = 0, $includeEmtpyValues = TRUE, $kill = TRUE) {
		foreach ($arr1 as $key => $val) {
			if (is_array($arr0[$key])) {
				if (is_array($arr1[$key])) {
					$arr0[$key] = $this->array_merge_recursive_overrule($arr0[$key], $arr1[$key], $notAddKeys, $includeEmtpyValues, $kill);
				} else {
					if ($kill) {
						if ($includeEmtpyValues || $val) {
							$arr0[$key] = $val;
						}
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
	 * @return void
	 */
	public function main_mode() {
		global $LANG, $BACK_PATH;

		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('template');
		$this->doc->docType = 'xhtml_trans';
		$this->doc->backPath = $BACK_PATH;
		$this->doc->setModuleTemplate('EXT:templavoila/Resources/templates/cm1_default.html');
		$this->doc->bodyTagId = 'typo3-mod-php';
		$this->doc->divClass = '';

		$this->doc->inDocStylesArray[] = '
			#templavoila-frame-visual { height:500px; display:block; margin:0 5px; width:98%; border: 1xpx solid black;}
			DIV.typo3-fullDoc H2 { width: 100%; }
			TABLE#c-mapInfo {margin-top: 10px; margin-bottom: 5px; }
			TABLE#c-mapInfo TR TD {padding-right: 20px;}
			select option.pagetemplate {background-image:url(../Resources/Public/Icon/icon_pagetemplate.gif);background-repeat: no-repeat; background-position: 5px 50%; padding: 1px 0 3px 24px; -webkit-background-size: 0;}
			select option.fce {background-image:url(../Resources/Public/Icon/icon_fce_ce.png);background-repeat: no-repeat; background-position: 5px 50%; padding: 1px 0 3px 24px; -webkit-background-size: 0;}
			#c-toMenu { margin-bottom:10px; }
		';
		$this->doc->inDocStylesArray[] = self::$gnyfStyleBlock;

		// Add custom styles
		$this->doc->styleSheetFile2 = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($this->extKey) . "cm1/styles.css";

		// General GPvars for module mode:
		$this->displayFile = \Extension\Templavoila\Domain\Model\File::filename(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('file'));
		$this->displayTable = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('table');
		$this->displayUid = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('uid');
		$this->displayPath = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('htmlPath');
		$this->returnUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('returnUrl'));

		// GPvars specific to the DS listing/table and mapping features:
		$this->_preview = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_preview');
		$this->mapElPath = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('mapElPath');
		$this->doMappingOfPath = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('doMappingOfPath');
		$this->showPathOnly = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('showPathOnly');
		$this->mappingToTags = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('mappingToTags');
		$this->DS_element = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('DS_element');
		$this->DS_cmd = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('DS_cmd');
		$this->fieldName = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('fieldName');

		// GPvars specific for DS creation from a file.
		$this->_load_ds_xml_content = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_load_ds_xml_content');
		$this->_load_ds_xml_to = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_load_ds_xml_to');
		$this->_saveDSandTO_TOuid = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_saveDSandTO_TOuid');
		$this->_saveDSandTO_title = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_saveDSandTO_title');
		$this->_saveDSandTO_type = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_saveDSandTO_type');
		$this->_saveDSandTO_pid = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_saveDSandTO_pid');
		$this->DS_element_DELETE = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('DS_element_DELETE');

		// Finding Storage folder:
		$this->findingStorageFolderIds();

		// Setting up form-wrapper:
		$this->doc->form = '<form action="' . $this->linkThisScript(array()) . '" method="post" name="pageform">';

		// JavaScript
		$this->doc->JScode .= $this->doc->wrapScriptTags('
			script_ended = 0;
			function jumpToUrl(URL)	{	//
				document.location = URL;
			}
			function updPath(inPath)	{	//
				document.location = "' . \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('htmlPath' => '', 'doMappingOfPath' => 1)) . '&htmlPath="+top.rawurlencode(inPath);
			}

			function openValidator(key) {
				new Ajax.Request("' . $GLOBALS['BACK_PATH'] . 'ajax.php?ajaxID=tx_templavoila_cm1_ajax::getDisplayFileContent&key=" + key, {
					onSuccess: function(response) {
						var valform = new Element(\'form\',{method: \'post\', target:\'_blank\', action: \'http://validator.w3.org/check#validate_by_input\'});
						valform.insert(new Element(\'input\',{name: \'fragment\', value:response.responseText, type: \'hidden\'}));$(document.body).insert(valform);
						valform.submit();
					}
				});
			}
		');

		$this->doc->loadJavascriptLib('sysext/backend/Resources/Public/JavaScript/tabmenu.js');

		// Setting up the context sensitive menu:
		$CMparts = $this->doc->getContextMenuCode();
		$this->doc->bodyTagAdditions = $CMparts[1];
		$this->doc->JScode .= $CMparts[0];
		$this->doc->postCode .= $CMparts[2];

		// Icons
		$this->dsTypes = array(
			'sc' => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('dsTypes_section') . ': ',
			'co' => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('dsTypes_container') . ': ',
			'el' => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('dsTypes_attribute') . ': ',
			'at' => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('dsTypes_element') . ': ',
			'no' => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('dsTypes_notmapped') . 'Not : ');
		foreach ($this->dsTypes as $id => $title) {
			$this->dsTypes[$id] = array(
				// abbrevation
				$id,
				// descriptive title
				$title,
				// image-path
				\TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'cm1/item_' . $id . '.gif', 'width="24" height="16" border="0" style="margin-right: 5px;"'),
				// background-path
				\TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'cm1/item_' . $id . '.gif', '', 1)
			);

			// information
			$this->dsTypes[$id][4] = @getimagesize($this->dsTypes[$id][3]);
		}

		// Render content, depending on input values:
		if ($this->displayFile) { // Browsing file directly, possibly creating a template/data object records.
			$this->renderFile();
		} elseif ($this->displayTable == 'tx_templavoila_datastructure') { // Data source display
			$this->renderDSO();
		} elseif ($this->displayTable == 'tx_templavoila_tmplobj') { // Data source display
			$this->renderTO();
		}

		// Add spacer:
		$this->content .= $this->doc->spacer(10);

		$this->pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->id, $this->perms_clause);
		$docHeaderButtons = $this->getDocHeaderButtons();
		$docContent = array(
			'CSH' => $docHeaderButtons['csh'],
			'CONTENT' => $this->content
		);

		$content = $this->doc->startPage(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('title'));
		$content .= $this->doc->moduleBody(
			$this->pageinfo,
			$docHeaderButtons,
			$docContent
		);
		$content .= $this->doc->endPage();

		// Replace content with templated content
		$this->content = $content;
	}

	/**
	 * Gets the buttons that shall be rendered in the docHeader.
	 *
	 * @return array Available buttons for the docHeader
	 */
	protected function getDocHeaderButtons() {
		$buttons = array(
			'csh' => \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_web_txtemplavoilaCM1', '', $this->backPath),
			'back' => '',
			'shortcut' => $this->getShortcutButton(),
		);

		// Back
		if ($this->returnUrl) {
			$backIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-view-go-back');
			$buttons['back'] = '<a href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisUrl($this->returnUrl)) . '" class="typo3-goBack" title="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL('LLL:EXT:lang/locallang_core.php:labels.goBack', TRUE) . '">' .
				$backIcon .
				'</a>';
		}

		return $buttons;
	}

	/**
	 * Gets the button to set a new shortcut in the backend (if current user is allowed to).
	 *
	 * @return string HTML representiation of the shortcut button
	 */
	protected function getShortcutButton() {
		$result = '';
		if (\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->mayMakeShortcut()) {
			$result = $this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
		}

		return $result;
	}

	/**
	 * Renders the display of DS/TO creation directly from a file
	 *
	 * @return void
	 */
	public function renderFile() {
		global $TYPO3_DB;

		if (@is_file($this->displayFile) && \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->displayFile)) {

			// Converting GPvars into a "cmd" value:
			$cmd = '';
			$msg = array();
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_load_ds_xml')) { // Loading DS from XML or TO uid
				$cmd = 'load_ds_xml';
			} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_clear')) { // Resetting mapping/DS
				$cmd = 'clear';
			} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_saveDSandTO')) { // Saving DS and TO to records.
				if (!strlen(trim($this->_saveDSandTO_title))) {
					$cmd = 'saveScreen';
					$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
						'\TYPO3\CMS\Core\Messaging\FlashMessage',
						\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('errorNoToTitleDefined'),
						'',
						\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
					);
					$msg[] = $flashMessage->render();
				} else {
					$cmd = 'saveDSandTO';
				}
			} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_updateDSandTO')) { // Updating DS and TO
				$cmd = 'updateDSandTO';
			} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_showXMLDS')) { // Showing current DS as XML
				$cmd = 'showXMLDS';
			} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_preview')) { // Previewing mappings
				$cmd = 'preview';
			} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_save_data_mapping')) { // Saving mapping to Session
				$cmd = 'save_data_mapping';
			} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_updateDS')) {
				$cmd = 'updateDS';
			} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('DS_element_DELETE')) {
				$cmd = 'DS_element_DELETE';
			} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_saveScreen')) {
				$cmd = 'saveScreen';
			} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_loadScreen')) {
				$cmd = 'loadScreen';
			} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_save')) {
				$cmd = 'saveUpdatedDSandTO';
			} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_saveExit')) {
				$cmd = 'saveUpdatedDSandTOandExit';
			}

			// Init settings:
			$this->editDataStruct = 1; // Edit DS...
			$content = '';

			// Checking Storage Folder PID:
			if (!count($this->storageFolders)) {
				$msg[] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-error') . '<strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('error') . '</strong> ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('errorNoStorageFolder');
			}

			// Session data
			$this->sessionKey = $this->MCONF['name'] . '_mappingInfo:' . $this->_load_ds_xml_to;
			if ($cmd == 'clear') { // Reset session data:
				$sesDat = array('displayFile' => $this->displayFile, 'TO' => $this->_load_ds_xml_to, 'DS' => $this->displayUid);
				\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
			} else { // Get session data:
				$sesDat = \Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->getSessionData($this->sessionKey);
			}
			if ($this->_load_ds_xml_to) {
				$toREC = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $this->_load_ds_xml_to);
				if ($this->staticDS) {
					$dsREC['dataprot'] = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($toREC['datastructure']));
				} else {
					$dsREC = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('tx_templavoila_datastructure', $toREC['datastructure']);
				}
			}

			// Loading DS from either XML or a Template Object (containing reference to DS)
			if ($cmd == 'load_ds_xml' && ($this->_load_ds_xml_content || $this->_load_ds_xml_to)) {
				$to_uid = $this->_load_ds_xml_to;
				if ($to_uid) {
					$tM = unserialize($toREC['templatemapping']);
					$sesDat = array('displayFile' => $this->displayFile, 'TO' => $this->_load_ds_xml_to, 'DS' => $this->displayUid);
					$sesDat['currentMappingInfo'] = $tM['MappingInfo'];
					$sesDat['currentMappingInfo_head'] = $tM['MappingInfo_head'];
					$ds = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($dsREC['dataprot']);
					$sesDat['dataStruct'] = $sesDat['autoDS'] = $ds; // Just set $ds, not only its ROOT! Otherwise <meta> will be lost.
					\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
				} else {
					$ds = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($this->_load_ds_xml_content);
					$sesDat = array('displayFile' => $this->displayFile, 'TO' => $this->_load_ds_xml_to, 'DS' => $this->displayUid);
					$sesDat['dataStruct'] = $sesDat['autoDS'] = $ds;
					\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
				}
			}

			// Setting Data Structure to value from session data - unless it does not exist in which case a default structure is created.
			$dataStruct = is_array($sesDat['autoDS']) ? $sesDat['autoDS'] : array(
				'meta' => array(
					'langDisable' => '1',
				),
				'ROOT' => array(
					'tx_templavoila' => array(
						'title' => 'ROOT',
						'description' => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('rootDescription'),
					),
					'type' => 'array',
					'el' => array()
				)
			);

			// Setting Current Mapping information to session variable content OR blank if none exists.
			$currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : array();
			$this->cleanUpMappingInfoAccordingToDS($currentMappingInfo, $dataStruct); // This will clean up the Current Mapping info to match the Data Structure.

			// CMD switch:
			switch ($cmd) {
				// Saving incoming Mapping Data to session data:
				case 'save_data_mapping':
					$inputData = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('dataMappingForm', 1);
					if (is_array($inputData)) {
						$sesDat['currentMappingInfo'] = $currentMappingInfo = $this->array_merge_recursive_overrule($currentMappingInfo, $inputData);
						$sesDat['dataStruct'] = $dataStruct;
						\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
					}
					break;
				// Saving incoming Data Structure settings to session data:
				case 'updateDS':
					$inDS = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('autoDS', 1);
					if (is_array($inDS)) {
						$sesDat['dataStruct'] = $sesDat['autoDS'] = $dataStruct = $this->array_merge_recursive_overrule($dataStruct, $inDS);
						\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
					}
					break;
				// If DS element is requested for deletion, remove it and update session data:
				case 'DS_element_DELETE':
					$ref = explode('][', substr($this->DS_element_DELETE, 1, -1));
					$this->unsetArrayPath($dataStruct, $ref);
					$sesDat['dataStruct'] = $sesDat['autoDS'] = $dataStruct;
					\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
					break;
			}

			// Creating $templatemapping array with cached mapping content:
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('showXMLDS,saveDSandTO,updateDSandTO,saveUpdatedDSandTO,saveUpdatedDSandTOandExit', $cmd)) {

				// Template mapping prepared:
				$templatemapping = array();
				$templatemapping['MappingInfo'] = $currentMappingInfo;
				if (isset($sesDat['currentMappingInfo_head'])) {
					$templatemapping['MappingInfo_head'] = $sesDat['currentMappingInfo_head'];
				}

				// Getting cached data:
				reset($dataStruct);
				$fileContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($this->displayFile);
				$htmlParse = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Html\\HtmlParser');
				$relPathFix = dirname(substr($this->displayFile, strlen(PATH_site))) . '/';
				$fileContent = $htmlParse->prefixResourcePath($relPathFix, $fileContent);
				$this->markupObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Extension\\Templavoila\\Domain\\Model\\HtmlMarkup');
				$contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($fileContent, $currentMappingInfo);
				$templatemapping['MappingData_cached'] = $contentSplittedByMapping['sub']['ROOT'];

				list($html_header) = $this->markupObj->htmlParse->getAllParts($htmlParse->splitIntoBlock('head', $fileContent), 1, 0);
				$this->markupObj->tags = $this->head_markUpTags; // Set up the markupObject to process only header-section tags:

				if (isset($templatemapping['MappingInfo_head'])) {
					$h_currentMappingInfo = array();
					$currentMappingInfo_head = $templatemapping['MappingInfo_head'];
					if (is_array($currentMappingInfo_head['headElementPaths'])) {
						foreach ($currentMappingInfo_head['headElementPaths'] as $kk => $vv) {
							$h_currentMappingInfo['el_' . $kk]['MAP_EL'] = $vv;
						}
					}

					$contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($html_header, $h_currentMappingInfo);
					$templatemapping['MappingData_head_cached'] = $contentSplittedByMapping;

					// Get <body> tag:
					$reg = '';
					preg_match('/<body[^>]*>/i', $fileContent, $reg);
					$templatemapping['BodyTag_cached'] = $currentMappingInfo_head['addBodyTag'] ? $reg[0] : '';
				}

				if ($cmd != 'showXMLDS') {
					// Set default flags to <meta> tag
					if (!isset($dataStruct['meta'])) {
						// Make sure <meta> goes at the beginning of data structure.
						// This is not critical for typo3 but simply convinient to
						// people who used to see it at the beginning.
						$dataStruct = array_merge(array('meta' => array()), $dataStruct);
					}
					if ($this->_saveDSandTO_type == 1) {
						// If we save a page template, set langDisable to 1 as per localization guide
						if (!isset($dataStruct['meta']['langDisable'])) {
							$dataStruct['meta']['langDisable'] = '1';
						}
					} else {
						// FCE defaults to inheritance
						if (!isset($dataStruct['meta']['langDisable'])) {
							$dataStruct['meta']['langDisable'] = '0';
							$dataStruct['meta']['langChildren'] = '1';
						}
					}
				}
			}

			// CMD switch:
			switch ($cmd) {
				// If it is requested to save the current DS and mapping information to a DS and TO record, then...:
				case 'saveDSandTO':
					$newID = '';
					// Init TCEmain object and store:
					$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("TYPO3\\CMS\\Core\\DataHandling\\DataHandler");
					$tce->stripslashes_values = 0;

					// DS:

					// Modifying data structure with conversion of preset values for field types to actual settings:
					$storeDataStruct = $dataStruct;
					if (is_array($storeDataStruct['ROOT']['el'])) {
						$this->eTypes->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'], $contentSplittedByMapping['sub']['ROOT'], $dataArr['tx_templavoila_datastructure']['NEW']['scope']);
					}
					$dataProtXML = \TYPO3\CMS\Core\Utility\GeneralUtility::array2xml_cs($storeDataStruct, 'T3DataStructure', array('useCDATA' => 1));

					if ($this->staticDS) {
						$title = preg_replace('|[/,\."\']+|', '_', $this->_saveDSandTO_title) . ' (' . ($this->_saveDSandTO_type == 1 ? 'page' : 'fce') . ').xml';
						$path = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->_saveDSandTO_type == 2 ? $this->extConf['staticDS.']['path_fce'] : $this->extConf['staticDS.']['path_page']) . $title;
						\TYPO3\CMS\Core\Utility\GeneralUtility::writeFile($path, $dataProtXML);
						$newID = substr($path, strlen(PATH_site));
					} else {
						$dataArr = array();
						$dataArr['tx_templavoila_datastructure']['NEW']['pid'] = intval($this->_saveDSandTO_pid);
						$dataArr['tx_templavoila_datastructure']['NEW']['title'] = $this->_saveDSandTO_title;
						$dataArr['tx_templavoila_datastructure']['NEW']['scope'] = $this->_saveDSandTO_type;
						$dataArr['tx_templavoila_datastructure']['NEW']['dataprot'] = $dataProtXML;

						// start data processing
						$tce->start($dataArr, array());
						$tce->process_datamap();
						$newID = intval($tce->substNEWwithIDs['NEW']);
					}

					// If that succeeded, create the TO as well:
					if ($newID) {
						$dataArr = array();
						$dataArr['tx_templavoila_tmplobj']['NEW']['pid'] = intval($this->_saveDSandTO_pid);
						$dataArr['tx_templavoila_tmplobj']['NEW']['title'] = $this->_saveDSandTO_title . ' [Template]';
						$dataArr['tx_templavoila_tmplobj']['NEW']['datastructure'] = $newID;
						$dataArr['tx_templavoila_tmplobj']['NEW']['fileref'] = substr($this->displayFile, strlen(PATH_site));
						$dataArr['tx_templavoila_tmplobj']['NEW']['templatemapping'] = serialize($templatemapping);
						$dataArr['tx_templavoila_tmplobj']['NEW']['fileref_mtime'] = @filemtime($this->displayFile);
						$dataArr['tx_templavoila_tmplobj']['NEW']['fileref_md5'] = @md5_file($this->displayFile);

						// Init TCEmain object and store:
						$tce->start($dataArr, array());
						$tce->process_datamap();
						$newToID = intval($tce->substNEWwithIDs['NEW']);
						if ($newToID) {
							$msg[] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-ok') .
								sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('msgDSTOSaved'),
									$dataArr['tx_templavoila_tmplobj']['NEW']['datastructure'],
									$tce->substNEWwithIDs['NEW'], $this->_saveDSandTO_pid);
						} else {
							$msg[] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-warning') . '<strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('error') . ':</strong> ' . sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('errorTONotSaved'), $dataArr['tx_templavoila_tmplobj']['NEW']['datastructure']);
						}
					} else {
						$msg[] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-warning') . ' border="0" align="top" class="absmiddle" alt="" /><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('error') . ':</strong> ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('errorTONotCreated');
					}

					unset($tce);
					if ($newID && $newToID) {
						//redirect to edit view
						$redirectUrl = 'index.php?file=' . rawurlencode($this->displayFile) . '&_load_ds_xml=1&_load_ds_xml_to=' . $newToID . '&uid=' . rawurlencode($newID) . '&returnUrl=' . rawurlencode('../mod2/index.php?id=' . intval($this->_saveDSandTO_pid));
						header('Location:' . \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($redirectUrl));
						exit;
					} else {
						// Clear cached header info because saveDSandTO always resets headers
						$sesDat['currentMappingInfo_head'] = '';
						\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
					}
					break;
				// Updating DS and TO records:
				case 'updateDSandTO':
				case 'saveUpdatedDSandTO':
				case 'saveUpdatedDSandTOandExit':

					if ($cmd == 'updateDSandTO') {
						// Looking up the records by their uids:
						$toREC = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $this->_saveDSandTO_TOuid);
					} else {
						$toREC = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $this->_load_ds_xml_to);
					}
					if ($this->staticDS) {
						$dsREC['uid'] = $toREC['datastructure'];
					} else {
						$dsREC = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('tx_templavoila_datastructure', $toREC['datastructure']);
					}

					// If they are found, continue:
					if ($toREC['uid'] && $dsREC['uid']) {
						// Init TCEmain object and store:
						$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
						$tce->stripslashes_values = 0;

						// Modifying data structure with conversion of preset values for field types to actual settings:
						$storeDataStruct = $dataStruct;
						if (is_array($storeDataStruct['ROOT']['el'])) {
							$this->eTypes->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'], $contentSplittedByMapping['sub']['ROOT'], $dsREC['scope']);
						}
						$dataProtXML = \TYPO3\CMS\Core\Utility\GeneralUtility::array2xml_cs($storeDataStruct, 'T3DataStructure', array('useCDATA' => 1));

						// DS:
						if ($this->staticDS) {
							$path = PATH_site . $dsREC['uid'];
							\TYPO3\CMS\Core\Utility\GeneralUtility::writeFile($path, $dataProtXML);
						} else {
							$dataArr = array();
							$dataArr['tx_templavoila_datastructure'][$dsREC['uid']]['dataprot'] = $dataProtXML;

							// process data
							$tce->start($dataArr, array());
							$tce->process_datamap();
						}

						// TO:
						$TOuid = \TYPO3\CMS\Backend\Utility\BackendUtility::wsMapId('tx_templavoila_tmplobj', $toREC['uid']);
						$dataArr = array();
						$dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref'] = substr($this->displayFile, strlen(PATH_site));
						$dataArr['tx_templavoila_tmplobj'][$TOuid]['templatemapping'] = serialize($templatemapping);
						$dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref_mtime'] = @filemtime($this->displayFile);
						$dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref_md5'] = @md5_file($this->displayFile);

						$tce->start($dataArr, array());
						$tce->process_datamap();

						unset($tce);

						$msg[] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-notification') . sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('msgDSTOUpdated'), $dsREC['uid'], $toREC['uid']);

						if ($cmd == 'updateDSandTO') {
							if (!$this->_load_ds_xml_to) {
								//new created was saved to existing DS/TO, redirect to edit view
								$redirectUrl = 'index.php?file=' . rawurlencode($this->displayFile) . '&_load_ds_xml=1&_load_ds_xml_to=' . $toREC['uid'] . '&uid=' . rawurlencode($dsREC['uid']) . '&returnUrl=' . rawurlencode('../mod2/index.php?id=' . intval($this->_saveDSandTO_pid));
								header('Location:' . \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($redirectUrl));
								exit;
							} else {
								// Clear cached header info because updateDSandTO always resets headers
								$sesDat['currentMappingInfo_head'] = '';
								\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
							}
						} elseif ($cmd == 'saveUpdatedDSandTOandExit') {
							header('Location:' . \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($this->returnUrl));
						}
					}
					break;
			}

			// Header:
			$tRows = array();
			$relFilePath = substr($this->displayFile, strlen(PATH_site));
			$onCl = 'return top.openUrlInWindow(\'' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $relFilePath . '\',\'FileView\');';
			$tRows[] = '
				<tr>
					<td class="bgColor5" rowspan="2">' . $this->cshItem('xMOD_tx_templavoila', 'mapping_file', $this->doc->backPath, '|') . '</td>
					<td class="bgColor5" rowspan="2"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('templateFile') . ':</strong></td>
					<td class="bgColor4"><a href="#" onclick="' . htmlspecialchars($onCl) . '">' . htmlspecialchars($relFilePath) . '</a></td>
				</tr>
 				<tr>
					<td class="bgColor4">
						<a href="#" onclick ="openValidator(\'' . $this->sessionKey . '\');return false;">
						' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('extensions-templavoila-htmlvalidate') . '
							' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('validateTpl') . '
						</a>
					</td>
				</tr>
				<tr>
					<td class="bgColor5">&nbsp;</td>
					<td class="bgColor5"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('templateObject') . ':</strong></td>
					<td class="bgColor4">' . ($toREC ? htmlspecialchars(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL($toREC['title'])) : \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mappingNEW')) . '</td>
				</tr>';
			if ($this->staticDS) {
				$onClick = 'return top.openUrlInWindow(\'' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $toREC['datastructure'] . '\',\'FileView\');';
				$tRows[] = '
				<tr>
					<td class="bgColor5">&nbsp;</td>
					<td class="bgColor5"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderDSO_XML') . ':</strong></td>
					<td class="bgColor4"><a href="#" onclick="' . htmlspecialchars($onClick) . '">' . htmlspecialchars($toREC['datastructure']) . '</a></td>
				</tr>';
			} else {
				$tRows[] = '
				<tr>
					<td class="bgColor5">&nbsp;</td>
					<td class="bgColor5"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderTO_dsRecord') . ':</strong></td>
					<td class="bgColor4">' . ($dsREC ? htmlspecialchars(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL($dsREC['title'])) : \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mappingNEW')) . '</td>
				</tr>';
			}

			// Write header of page:
			$content .= '

				<!--
					Create Data Structure Header:
				-->
				<table border="0" cellpadding="2" cellspacing="1" id="c-toHeader">
					' . implode('', $tRows) . '
				</table><br />
			';

			// Messages:
			if (is_array($msg)) {
				$content .= '

					<!--
						Messages:
					-->
					' . implode('<br />', $msg) . '
				';
			}

			// Generate selector box options:
			// Storage Folders for elements:
			$sf_opt = array();
			$res = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTquery(
				'*',
				'pages',
				'uid IN (' . $this->storageFolders_pidList . ')' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('pages'),
				'',
				'title'
			);
			while (FALSE !== ($row = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
				$sf_opt[] = '<option value="' . htmlspecialchars($row['uid']) . '">' . htmlspecialchars($row['title'] . ' (UID:' . $row['uid'] . ')') . '</option>';
			}

			// Template Object records:
			$opt = array();
			$opt[] = '<option value="0"></option>';
			if ($this->staticDS) {
				$res = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTquery(
					'*, CASE WHEN LOCATE(' . \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->fullQuoteStr('(fce)', 'tx_templavoila_tmplobj') . ', datastructure)>0 THEN 2 ELSE 1 END AS scope',
					'tx_templavoila_tmplobj',
					'pid IN (' . $this->storageFolders_pidList . ') AND datastructure!=' . \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->fullQuoteStr('', 'tx_templavoila_tmplobj') .
					\TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_templavoila_tmplobj') .
					\TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('tx_templavoila_tmplobj'),
					'',
					'scope,title'
				);
			} else {
				$res = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTquery(
					'tx_templavoila_tmplobj.*,tx_templavoila_datastructure.scope',
					'tx_templavoila_tmplobj LEFT JOIN tx_templavoila_datastructure ON tx_templavoila_datastructure.uid=tx_templavoila_tmplobj.datastructure',
					'tx_templavoila_tmplobj.pid IN (' . $this->storageFolders_pidList . ') AND tx_templavoila_tmplobj.datastructure>0 ' .
					\TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_templavoila_tmplobj') .
					\TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('tx_templavoila_tmplobj'),
					'',
					'tx_templavoila_datastructure.scope, tx_templavoila_tmplobj.pid, tx_templavoila_tmplobj.title'
				);
			}
			$storageFolderPid = 0;
			$optGroupOpen = FALSE;
			while (FALSE !== ($row = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
				$scope = $row['scope'];
				unset($row['scope']);
				\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('tx_templavoila_tmplobj', $row);
				if ($storageFolderPid != $row['pid']) {
					$storageFolderPid = $row['pid'];
					if ($optGroupOpen) {
						$opt[] = '</optgroup>';
					}
					$opt[] = '<optgroup label="' . htmlspecialchars($this->storageFolders[$storageFolderPid] . ' (PID: ' . $storageFolderPid . ')') . '">';
					$optGroupOpen = TRUE;
				}
				$opt[] = '<option value="' . htmlspecialchars($row['uid']) . '" ' .
					($scope == 1 ? 'class="pagetemplate"">' : 'class="fce">') .
					htmlspecialchars(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL($row['title']) . ' (UID:' . $row['uid'] . ')') . '</option>';
			}
			if ($optGroupOpen) {
				$opt[] = '</optgroup>';
			}

			// Module Interface output begin:
			switch ($cmd) {
				// Show XML DS
				case 'showXMLDS':

					// Make instance of syntax highlight class:
					$hlObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Extension\\Templavoila\\Service\\SyntaxHighlightingService');

					$storeDataStruct = $dataStruct;
					if (is_array($storeDataStruct['ROOT']['el'])) {
						$this->eTypes->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'], $contentSplittedByMapping['sub']['ROOT']);
					}
					$dataStructureXML = \TYPO3\CMS\Core\Utility\GeneralUtility::array2xml_cs($storeDataStruct, 'T3DataStructure', array('useCDATA' => 1));

					$content .= '
						<input type="submit" name="_DO_NOTHING" value="Go back" title="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonGoBack') . '" />
						<h3>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('titleXmlConfiguration') . ':</h3>
						' . $this->cshItem('xMOD_tx_templavoila', 'mapping_file_showXMLDS', $this->doc->backPath, '|<br/>') . '
						<pre>' . $hlObj->highLight_DS($dataStructureXML) . '</pre>';
					break;
				case 'loadScreen':

					$content .= '
						<h3>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('titleLoadDSXml') . '</h3>
						' . $this->cshItem('xMOD_tx_templavoila', 'mapping_file_loadDSXML', $this->doc->backPath, '|<br/>') . '
						<p>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('selectTOrecrdToLoadDSFrom') . ':</p>
						<select name="_load_ds_xml_to">' . implode('', $opt) . '</select>
						<br />
						<p>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('pasteDSXml') . ':</p>
						<textarea rows="15" name="_load_ds_xml_content" wrap="off"' . $GLOBALS['TBE_TEMPLATE']->formWidthText(48, 'width:98%;', 'off') . '></textarea>
						<br />
						<input type="submit" name="_load_ds_xml" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('loadDSXml') . '" />
						<input type="submit" name="_" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonCancel') . '" />
						';
					break;
				case 'saveScreen':

					$content .= '
						<h3>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('createDSTO') . ':</h3>
						' . $this->cshItem('xMOD_tx_templavoila', 'mapping_file_createDSTO', $this->doc->backPath, '|<br/>') . '
						<table border="0" cellpadding="2" cellspacing="2" class="dso_table">
							<tr>
								<td class="bgColor5"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('titleDSTO') . ':</strong></td>
								<td class="bgColor4"><input type="text" name="_saveDSandTO_title" /></td>
							</tr>
							<tr>
								<td class="bgColor5"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('templateType') . ':</strong></td>
								<td class="bgColor4">
									<select name="_saveDSandTO_type">
										<option value="1">' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('pageTemplate') . '</option>
										<option value="2">' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('contentElement') . '</option>
										<option value="0">' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('undefined') . '</option>
									</select>
								</td>
							</tr>
							<tr>
								<td class="bgColor5"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('storeInPID') . ':</strong></td>
								<td class="bgColor4">
									<select name="_saveDSandTO_pid">
										' . implode('
										', $sf_opt) . '
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
										' . implode('
										', $opt) . '
									</select>
								</td>
							</tr>
						</table>

						<input type="submit" name="_updateDSandTO" value="UPDATE TO (and DS)" onclick="return confirm(' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->JScharCode(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('saveDSTOconfirm')) . ');" />
						<input type="submit" name="_" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonCancel') . '" />
						';
					break;
				default:
					// Creating menu:
					$menuItems = array();
					$menuItems[] = '<input type="submit" name="_showXMLDS" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonShowXML') . '" title="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonTitle_showXML') . '" />';
					$menuItems[] = '<input type="submit" name="_clear" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonClearAll') . '" title="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonTitle_clearAll') . '" /> ';
					$menuItems[] = '<input type="submit" name="_preview" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonPreview') . '" title="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonTitle_preview') . '" />';
					if (is_array($toREC) && is_array($dsREC)) {
						$menuItems[] = '<input type="submit" name="_save" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonSave') . '" title="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonTitle_save') . '" />';
						$menuItems[] = '<input type="submit" name="_saveExit" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonSaveExit') . '" title="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonTitle_saveExit') . '" />';
					}
					$menuItems[] = '<input type="submit" name="_saveScreen" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonSaveAs') . '" title="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonTitle_saveAs') . '" />';
					$menuItems[] = '<input type="submit" name="_loadScreen" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonLoad') . '" title="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonTitle_load') . '" />';
					$menuItems[] = '<input type="submit" name="_DO_NOTHING" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonRefresh') . '" title="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonTitle_refresh') . '" />';

					$menuContent = '

						<!--
							Menu for creation Data Structures / Template Objects
						-->
						<table border="0" cellpadding="2" cellspacing="2" id="c-toMenu">
							<tr class="bgColor5">
								<td>' . implode('</td>
								<td>', $menuItems) . '</td>
							</tr>
						</table>
					';

					$content .= '

					<!--
						Data Structure creation table:
					-->
					<h3>' . $this->cshItem('xMOD_tx_templavoila', 'mapping_file', $this->doc->backPath, '|') . $GLOBALS['LANG']->getLL('buildingDS') . ':</h3>' .
						$this->renderTemplateMapper($this->displayFile, $this->displayPath, $dataStruct, $currentMappingInfo, $menuContent);
					break;
			}
		}

		$this->content .= $this->doc->section('', $content, 0, 1);
	}

	/**
	 * Renders the display of Data Structure Objects.
	 *
	 * @return void
	 */
	public function renderDSO() {
		global $TYPO3_DB;
		if (intval($this->displayUid) > 0) { // TODO: static ds support
			$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('tx_templavoila_datastructure', $this->displayUid);
			if (is_array($row)) {

				// Get title and icon:
				$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('tx_templavoila_datastructure', $row);
				$title = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle('tx_templavoila_datastructure', $row, 1);
				$content .= $this->doc->wrapClickMenuOnIcon($icon, 'tx_templavoila_datastructure', $row['uid'], 1) .
					'<strong>' . $title . '</strong><br />';

				// Get Data Structure:
				$origDataStruct = $dataStruct = $this->getDataStructFromDSO($row['dataprot']);

				if (is_array($dataStruct)) {
					// Showing Data Structure:
					$tRows = $this->drawDataStructureMap($dataStruct);
					$content .= '

					<!--
						Data Structure content:
					-->
					<div id="c-ds">
						<h4>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderDSO_dataStructure') . ':</h4>
						<table border="0" cellspacing="2" cellpadding="2" class="dso_table">
									<tr class="bgColor5">
										<td nowrap="nowrap"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderDSO_dataElement') . ':</strong>' .
						$this->cshItem('xMOD_tx_templavoila', 'mapping_head_dataElement', $this->doc->backPath, '', TRUE) .
						'</td>
					<td nowrap="nowrap"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderDSO_mappingInstructions') . ':</strong>' .
						$this->cshItem('xMOD_tx_templavoila', 'mapping_head_mapping_instructions', $this->doc->backPath, '', TRUE) .
						'</td>
					<td nowrap="nowrap"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderDSO_rules') . ':</strong>' .
						$this->cshItem('xMOD_tx_templavoila', 'mapping_head_Rules', $this->doc->backPath, '', TRUE) .
						'</td>
				</tr>
	' . implode('', $tRows) . '
						</table>
					</div>';

					// CSH
					$content .= $this->cshItem('xMOD_tx_templavoila', 'mapping_ds', $this->doc->backPath);
				} else {
					$content .= '<h4>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('error') . ': ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('noDSDefined') . '</h4>';
				}

				// Get Template Objects pointing to this Data Structure
				$res = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTquery(
					'*',
					'tx_templavoila_tmplobj',
					'pid IN (' . $this->storageFolders_pidList . ') AND datastructure=' . intval($row['uid']) .
					\TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_templavoila_tmplobj') .
					\TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('tx_templavoila_tmplobj')
				);
				$tRows = array();
				$tRows[] = '
							<tr class="bgColor5">
								<td><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderDSO_uid') . ':</strong></td>
								<td><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderDSO_title') . ':</strong></td>
								<td><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderDSO_fileRef') . ':</strong></td>
								<td><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderDSO_dataLgd') . ':</strong></td>
							</tr>';
				$TOicon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('tx_templavoila_tmplobj', array());

				// Listing Template Objects with links:
				while (FALSE !== ($TO_Row = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
					\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('tx_templavoila_tmplobj', $TO_Row);
					$tRows[] = '
							<tr class="bgColor4">
								<td>[' . $TO_Row['uid'] . ']</td>
								<td nowrap="nowrap">' . $this->doc->wrapClickMenuOnIcon($TOicon, 'tx_templavoila_tmplobj', $TO_Row['uid'], 1) .
						'<a href="' . htmlspecialchars('index.php?table=tx_templavoila_tmplobj&uid=' . $TO_Row['uid'] . '&_reload_from=1') . '">' .
						\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle('tx_templavoila_tmplobj', $TO_Row, 1) . '</a>' .
						'</td>
					<td nowrap="nowrap">' . htmlspecialchars($TO_Row['fileref']) . ' <strong>' .
						(!\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($TO_Row['fileref'], 1) ? \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderDSO_notFound') : \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderDSO_ok')) . '</strong></td>
								<td>' . strlen($TO_Row['templatemapping']) . '</td>
							</tr>';
				}

				$content .= '

					<!--
						Template Objects attached to Data Structure Record:
					-->
					<div id="c-to">
						<h4>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderDSO_usedTO') . ':</h4>
						<table border="0" cellpadding="2" cellspacing="2" class="dso_table">
						' . implode('', $tRows) . '
						</table>
					</div>';

				// CSH
				$content .= $this->cshItem('xMOD_tx_templavoila', 'mapping_ds_to', $this->doc->backPath);

				// Display XML of data structure:
				if (is_array($dataStruct)) {

					// Make instance of syntax highlight class:
					$hlObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Extension\\Templavoila\\Service\\SyntaxHighlightingService');

					$dataStructureXML = \TYPO3\CMS\Core\Utility\GeneralUtility::array2xml_cs($origDataStruct, 'T3DataStructure', array('useCDATA' => 1));
					$content .= '

					<!--
						Data Structure XML:
					-->
					<br />
					<div id="c-dsxml">
						<h3>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderDSO_XML') . ':</h3>
						' . $this->cshItem('xMOD_tx_templavoila', 'mapping_ds_showXML', $this->doc->backPath) . '
						<p>' . \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck('', 'SET[showDSxml]', $this->MOD_SETTINGS['showDSxml'], '', \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $_GET, '', 1, 1)) . ' Show XML</p>
						<pre>' .
						($this->MOD_SETTINGS['showDSxml'] ? $hlObj->highLight_DS($dataStructureXML) : '') . '
						</pre>
					</div>
					';
				}
			} else {
				$content .= sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('errorNoDSrecord'), $this->displayUid);
			}
			$this->content .= $this->doc->section(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderDSO_DSO'), $content, 0, 1);
		} else {
			$this->content .= $this->doc->section(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('errorInDSO'), '' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderDSO_noUid'), 0, 1, 3);
		}
	}

	/**
	 * Renders the display of Template Objects.
	 *
	 * @return void
	 */
	public function renderTO() {
		if (intval($this->displayUid) > 0) {
			$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $this->displayUid);

			if (is_array($row)) {

				$tRows = array();
				$tRows[] = '
					<tr class="bgColor5">
						<td colspan="2"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderTO_toDetails') . ':</strong>' .
					$this->cshItem('xMOD_tx_templavoila', 'mapping_to', $this->doc->backPath, '') .
					'</td>
			</tr>';

				// Get title and icon:
				$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('tx_templavoila_tmplobj', $row);

				$title = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle('tx_templavoila_tmplobj', $row);
				$title = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitlePrep(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL($title));
				$tRows[] = '
					<tr class="bgColor4">
						<td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('templateObject') . ':</td>
						<td>' . $this->doc->wrapClickMenuOnIcon($icon, 'tx_templavoila_tmplobj', $row['uid'], 1) . $title . '</td>
					</tr>';

				// Session data
				$sessionKey = $this->MCONF['name'] . '_validatorInfo:' . $row['uid'];
				$sesDat = array('displayFile' => $row['fileref']);
				\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->setAndSaveSessionData($sessionKey, $sesDat);

				// Find the file:
				$theFile = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($row['fileref'], 1);
				if ($theFile && @is_file($theFile)) {
					$relFilePath = substr($theFile, strlen(PATH_site));
					$onCl = 'return top.openUrlInWindow(\'' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $relFilePath . '\',\'FileView\');';
					$tRows[] = '
						<tr class="bgColor4">
							<td rowspan="2">' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('templateFile') . ':</td>
							<td><a href="#" onclick="' . htmlspecialchars($onCl) . '">' . htmlspecialchars($relFilePath) . '</a></td>
						</tr>
						<tr class="bgColor4">
							<td>
								<a href="#" onclick ="openValidator(\'' . $sessionKey . '\');return false;">
									' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('extensions-templavoila-htmlvalidate') . '
									' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('validateTpl') . '
								</a>
							</td>
						</tr>';

					// Finding Data Structure Record:
					$DSOfile = '';
					$dsValue = $row['datastructure'];
					if ($row['parent']) {
						$parentRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $row['parent'], 'datastructure');
						$dsValue = $parentRec['datastructure'];
					}

					if (\Extension\Templavoila\Utility\GeneralUtility::canBeInterpretedAsInteger($dsValue)) {
						$DS_row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('tx_templavoila_datastructure', $dsValue);
					} else {
						$DSOfile = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($dsValue);
					}
					if (is_array($DS_row) || @is_file($DSOfile)) {

						// Get main DS array:
						if (is_array($DS_row)) {
							// Get title and icon:
							$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('tx_templavoila_datastructure', $DS_row);
							$title = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle('tx_templavoila_datastructure', $DS_row);
							$title = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitlePrep(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL($title));

							$tRows[] = '
								<tr class="bgColor4">
									<td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderTO_dsRecord') . ':</td>
									<td>' . $this->doc->wrapClickMenuOnIcon($icon, 'tx_templavoila_datastructure', $DS_row['uid'], 1) . $title . '</td>
								</tr>';

							// Link to updating DS/TO:
							$onCl = 'index.php?file=' . rawurlencode($theFile) . '&_load_ds_xml=1&_load_ds_xml_to=' . $row['uid'] . '&uid=' . $DS_row['uid'] . '&returnUrl=' . $this->returnUrl;
							$onClMsg = '
								if (confirm(' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->JScharCode(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderTO_updateWarningConfirm')) . ')) {
									document.location=\'' . $onCl . '\';
								}
								return false;
								';
							$tRows[] = '
								<tr class="bgColor4">
									<td>&nbsp;</td>
									<td><input type="submit" name="_" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderTO_editDSTO') . '" onclick="' . htmlspecialchars($onClMsg) . '"/>' .
								$this->cshItem('xMOD_tx_templavoila', 'mapping_to_modifyDSTO', $this->doc->backPath, '') .
								'</td>
						</tr>';

							// Read Data Structure:
							$dataStruct = $this->getDataStructFromDSO($DS_row['dataprot']);
						} else {
							// Show filepath of external XML file:
							$relFilePath = substr($DSOfile, strlen(PATH_site));
							$onCl = 'return top.openUrlInWindow(\'' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $relFilePath . '\',\'FileView\');';
							$tRows[] = '
								<tr class="bgColor4">
									<td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderTO_dsFile') . ':</td>
									<td><a href="#" onclick="' . htmlspecialchars($onCl) . '">' . htmlspecialchars($relFilePath) . '</a></td>
								</tr>';
							$onCl = 'index.php?file=' . rawurlencode($theFile) . '&_load_ds_xml=1&_load_ds_xml_to=' . $row['uid'] . '&uid=' . rawurlencode($DSOfile) . '&returnUrl=' . $this->returnUrl;
							$onClMsg = '
								if (confirm(' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->JScharCode(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderTO_updateWarningConfirm')) . ')) {
									document.location=\'' . $onCl . '\';
								}
								return false;
								';
							$tRows[] = '
								<tr class="bgColor4">
									<td>&nbsp;</td>
									<td><input type="submit" name="_" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderTO_editDSTO') . '" onclick="' . htmlspecialchars($onClMsg) . '"/>' .
								$this->cshItem('xMOD_tx_templavoila', 'mapping_to_modifyDSTO', $this->doc->backPath, '') .
								'</td>
						</tr>';

							// Read Data Structure:
							$dataStruct = $this->getDataStructFromDSO('', $DSOfile);
						}

						// Write header of page:
						$content .= '

							<!--
								Template Object Header:
							-->
							<h3>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('renderTO_toInfo') . ':</h3>
							<table border="0" cellpadding="2" cellspacing="1" id="c-toHeader">
								' . implode('', $tRows) . '
							</table>
						';

						// If there is a valid data structure, draw table:
						if (is_array($dataStruct)) {

							// Working on Header and Body of HTML source:

							// -- Processing the header editing --
							list($editContent, $currentHeaderMappingInfo) = $this->renderTO_editProcessing($dataStruct, $row, $theFile, 1);

							// Determine if DS is a template record and if it is a page template:
							$showBodyTag = !is_array($DS_row) || $DS_row['scope'] == 1 ? TRUE : FALSE;

							$parts = array();
							$parts[] = array(
								'label' => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('tabTODetails'),
								'content' => $content
							);

							// -- Processing the head editing
							$headerContent .= '
								<!--
									HTML header parts selection:
								-->
							<h3>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mappingHeadParts') . ': ' . $this->cshItem('xMOD_tx_templavoila', 'mapping_to_headerParts', $this->doc->backPath, '') . '</h3>
								' . $this->renderHeaderSelection($theFile, $currentHeaderMappingInfo, $showBodyTag, $editContent);

							$parts[] = array(
								'label' => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('tabHeadParts'),
								'content' => $headerContent
							);

							// -- Processing the body editing --
							list($editContent, $currentMappingInfo) = $this->renderTO_editProcessing($dataStruct, $row, $theFile, 0);

							$bodyContent .= '
								<!--
									Data Structure mapping table:
								-->
							<h3>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mappingBodyParts') . ':</h3>
								' . $this->renderTemplateMapper($theFile, $this->displayPath, $dataStruct, $currentMappingInfo, $editContent);

							$parts[] = array(
								'label' => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('tabBodyParts'),
								'content' => $bodyContent
							);
						} else {
							$content .= \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('error') . ': ' . sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('errorNoDSfound'), $dsValue);
						}
					} else {
						$content .= \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('error') . ': ' . sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('errorNoDSfound'), $dsValue);
					}
				} else {
					$content .= \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('error') . ': ' . sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('errorFileNotFound'), $row['fileref']);
				}
			} else {
				$content .= \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('error') . ': ' . sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('errorNoTOfound'), $this->displayUid);
			}

			$parts[0]['content'] = $content;
		} else {
			$this->content .= $this->doc->section(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('templateObject') . ' ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('error'), \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('errorNoUidFound'), 0, 1, 3);
		}

		// show tab menu
		if (is_array($parts)) {
			$this->content .= $this->doc->section(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mappingTitle'), '' .
				$this->doc->getDynTabMenu($parts, 'TEMPLAVOILA:templateModule:' . $this->id, 0, 0, 300)
				, 0, 1);
		}
	}

	/**
	 * Process editing of a TO for renderTO() function
	 *
	 * @param array &$dataStruct Data Structure. Passed by reference; The sheets found inside will be resolved if found!
	 * @param array $row TO record row
	 * @param string Template file path (absolute)
	 * @param integer $headerPart Process the headerPart instead of the bodyPart
	 *
	 * @return array Array with two keys (0/1) with a) content and b) currentMappingInfo which is retrieved inside (currentMappingInfo will be different based on whether "head" or "body" content is "mapped")
	 * @see renderTO()
	 */
	public function renderTO_editProcessing(&$dataStruct, $row, $theFile, $headerPart = 0) {
		$msg = array();

		// Converting GPvars into a "cmd" value:
		$cmd = '';
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_reload_from')) { // Reverting to old values in TO
			$cmd = 'reload_from';
		} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_clear')) { // Resetting mapping
			$cmd = 'clear';
		} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_save_data_mapping')) { // Saving to Session
			$cmd = 'save_data_mapping';
		} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_save_to') || \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_save_to_return')) { // Saving to Template Object
			$cmd = 'save_to';
		}

		// Getting data from tmplobj
		$templatemapping = unserialize($row['templatemapping']);
		if (!is_array($templatemapping)) {
			$templatemapping = array();
		}

		// If that array contains sheets, then traverse them:
		if (is_array($dataStruct['sheets'])) {
			$dSheets = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveAllSheetsInDS($dataStruct);
			$dataStruct = array(
				'ROOT' => array(
					'tx_templavoila' => array(
						'title' => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('rootMultiTemplate_title'),
						'description' => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('rootMultiTemplate_description'),
					),
					'type' => 'array',
					'el' => array()
				)
			);
			foreach ($dSheets['sheets'] as $nKey => $lDS) {
				if (is_array($lDS['ROOT'])) {
					$dataStruct['ROOT']['el'][$nKey] = $lDS['ROOT'];
				}
			}
		}

		// Get session data:
		$sesDat = \Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->getSessionData($this->sessionKey);

		// Set current mapping info arrays:
		$currentMappingInfo_head = is_array($sesDat['currentMappingInfo_head']) ? $sesDat['currentMappingInfo_head'] : array();
		$currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : array();
		$this->cleanUpMappingInfoAccordingToDS($currentMappingInfo, $dataStruct);

		// Perform processing for head
		// GPvars, incoming data
		$checkboxElement = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('checkboxElement', 1);
		$addBodyTag = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('addBodyTag');

		// Update session data:
		if ($cmd == 'reload_from' || $cmd == 'clear') {
			$currentMappingInfo_head = is_array($templatemapping['MappingInfo_head']) && $cmd != 'clear' ? $templatemapping['MappingInfo_head'] : array();
			$sesDat['currentMappingInfo_head'] = $currentMappingInfo_head;
			\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
		} else {
			if ($cmd == 'save_data_mapping' || $cmd == 'save_to') {
				$sesDat['currentMappingInfo_head'] = $currentMappingInfo_head = array(
					'headElementPaths' => $checkboxElement,
					'addBodyTag' => $addBodyTag ? 1 : 0
				);
				\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
			}
		}

		// Perform processing for  body
		// GPvars, incoming data
		$inputData = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('dataMappingForm', 1);

		// Update session data:
		if ($cmd == 'reload_from' || $cmd == 'clear') {
			$currentMappingInfo = is_array($templatemapping['MappingInfo']) && $cmd != 'clear' ? $templatemapping['MappingInfo'] : array();
			$this->cleanUpMappingInfoAccordingToDS($currentMappingInfo, $dataStruct);
			$sesDat['currentMappingInfo'] = $currentMappingInfo;
			$sesDat['dataStruct'] = $dataStruct;
			\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
		} else {
			if ($cmd == 'save_data_mapping' && is_array($inputData)) {
				$sesDat['currentMappingInfo'] = $currentMappingInfo = $this->array_merge_recursive_overrule($currentMappingInfo, $inputData);
				$sesDat['dataStruct'] = $dataStruct; // Adding data structure to session data so that the PREVIEW window can access the DS easily...
				\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
			}
		}

		// SAVE to template object
		if ($cmd == 'save_to') {
			$dataArr = array();

			// Set content, either for header or body:
			$templatemapping['MappingInfo_head'] = $currentMappingInfo_head;
			$templatemapping['MappingInfo'] = $currentMappingInfo;

			// Getting cached data:
			reset($dataStruct);
			// Init; read file, init objects:
			$fileContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($theFile);
			$htmlParse = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Html\\HtmlParser');
			$this->markupObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Extension\\Templavoila\\Domain\\Model\\HtmlMarkup');

			// Fix relative paths in source:
			$relPathFix = dirname(substr($theFile, strlen(PATH_site))) . '/';
			$uniqueMarker = uniqid('###') . '###';
			$fileContent = $htmlParse->prefixResourcePath($relPathFix, $fileContent, array('A' => $uniqueMarker));
			$fileContent = $this->fixPrefixForLinks($relPathFix, $fileContent, $uniqueMarker);

			// Get BODY content for caching:
			$contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($fileContent, $currentMappingInfo);
			$templatemapping['MappingData_cached'] = $contentSplittedByMapping['sub']['ROOT'];

			// Get HEAD content for caching:
			list($html_header) = $this->markupObj->htmlParse->getAllParts($htmlParse->splitIntoBlock('head', $fileContent), 1, 0);
			$this->markupObj->tags = $this->head_markUpTags; // Set up the markupObject to process only header-section tags:

			$h_currentMappingInfo = array();
			if (is_array($currentMappingInfo_head['headElementPaths'])) {
				foreach ($currentMappingInfo_head['headElementPaths'] as $kk => $vv) {
					$h_currentMappingInfo['el_' . $kk]['MAP_EL'] = $vv;
				}
			}

			$contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($html_header, $h_currentMappingInfo);
			$templatemapping['MappingData_head_cached'] = $contentSplittedByMapping;

			// Get <body> tag:
			$reg = '';
			preg_match('/<body[^>]*>/i', $fileContent, $reg);
			$templatemapping['BodyTag_cached'] = $currentMappingInfo_head['addBodyTag'] ? $reg[0] : '';

			$TOuid = \TYPO3\CMS\Backend\Utility\BackendUtility::wsMapId('tx_templavoila_tmplobj', $row['uid']);
			$dataArr['tx_templavoila_tmplobj'][$TOuid]['templatemapping'] = serialize($templatemapping);
			$dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref_mtime'] = @filemtime($theFile);
			$dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref_md5'] = @md5_file($theFile);

			$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
			$tce->stripslashes_values = 0;
			$tce->start($dataArr, array());
			$tce->process_datamap();
			unset($tce);
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				'\TYPO3\CMS\Core\Messaging\FlashMessage',
				\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('msgMappingSaved'),
				'',
				\TYPO3\CMS\Core\Messaging\FlashMessage::OK
			);
			$msg[] .= $flashMessage->render();
			$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $this->displayUid);
			$templatemapping = unserialize($row['templatemapping']);

			if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_save_to_return')) {
				header('Location: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($this->returnUrl));
				exit;
			}
		}

		// Making the menu
		$menuItems = array();
		$menuItems[] = '<input type="submit" name="_clear" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonClearAll') . '" title="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonClearAllMappingTitle') . '" />';

		// Make either "Preview" button (body) or "Set" button (header)
		if ($headerPart) { // Header:
			$menuItems[] = '<input type="submit" name="_save_data_mapping" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonSet') . '" title="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonSetTitle') . '" />';
		} else { // Body:
			$menuItems[] = '<input type="submit" name="_preview" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonPreview') . '" title="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonPreviewMappingTitle') . '" />';
		}

		$menuItems[] = '<input type="submit" name="_save_to" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonSave') . '" title="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonSaveTOTitle') . '" />';

		if ($this->returnUrl) {
			$menuItems[] = '<input type="submit" name="_save_to_return" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonSaveAndReturn') . '" title="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonSaveAndReturnTitle') . '" />';
		}

		// If a difference is detected...:
		if (
			(serialize($templatemapping['MappingInfo_head']) != serialize($currentMappingInfo_head)) ||
			(serialize($templatemapping['MappingInfo']) != serialize($currentMappingInfo))
		) {
			$menuItems[] = '<input type="submit" name="_reload_from" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonRevert') . '" title="' . sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonRevertTitle'), $headerPart ? 'HEAD' : 'BODY') . '" />';

			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				'\TYPO3\CMS\Core\Messaging\FlashMessage',
				\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('msgMappingIsDifferent'),
				'',
				\TYPO3\CMS\Core\Messaging\FlashMessage::INFO
			);
			$msg[] .= $flashMessage->render();
		}

		$content = '

			<!--
				Menu for saving Template Objects
			-->
			<table border="0" cellpadding="2" cellspacing="2" id="c-toMenu">
				<tr class="bgColor5">
					<td>' . implode('</td>
					<td>', $menuItems) . '</td>
				</tr>
			</table>
		';

		// @todo - replace with FlashMessage Queue
		$content .= implode('', $msg);

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
	 * @param string $displayFile The abs file name to read
	 * @param array $currentHeaderMappingInfo Header mapping information
	 * @param boolean $showBodyTag If true, show body tag.
	 * @param string $htmlAfterDSTable HTML content to show after the Data Structure table.
	 *
	 * @return string HTML table.
	 */
	public function renderHeaderSelection($displayFile, $currentHeaderMappingInfo, $showBodyTag, $htmlAfterDSTable = '') {

		// Get file content
		$this->markupFile = $displayFile;
		$fileContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($this->markupFile);

		// Init mark up object.
		$this->markupObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Extension\\Templavoila\\Domain\\Model\\HtmlMarkup');
		$this->markupObj->init();

		// Get <body> tag:
		$reg = '';
		preg_match('/<body[^>]*>/i', $fileContent, $reg);
		$html_body = $reg[0];

		// Get <head>...</head> from template:
		$splitByHeader = $this->markupObj->htmlParse->splitIntoBlock('head', $fileContent);
		list($html_header) = $this->markupObj->htmlParse->getAllParts($splitByHeader, 1, 0);

		// Set up the markupObject to process only header-section tags:
		$this->markupObj->tags = $this->head_markUpTags;
		$this->markupObj->checkboxPathsSet = is_array($currentHeaderMappingInfo['headElementPaths']) ? $currentHeaderMappingInfo['headElementPaths'] : array();
		$this->markupObj->maxRecursion = 0; // Should not enter more than one level.

		// Markup the header section data with the header tags, using "checkbox" mode:
		$tRows = $this->markupObj->markupHTMLcontent($html_header, $GLOBALS['BACK_PATH'], '', 'script,style,link,meta', 'checkbox');
		$bodyTagRow = $showBodyTag ? '
				<tr class="bgColor2">
					<td><input type="checkbox" name="addBodyTag" value="1"' . ($currentHeaderMappingInfo['addBodyTag'] ? ' checked="checked"' : '') . ' /></td>
					<td>' . \Extension\Templavoila\Domain\Model\HtmlMarkup::getGnyfMarkup('body') . '</td>
					<td><pre>' . htmlspecialchars($html_body) . '</pre></td>
				</tr>' : '';

		$headerParts = '
			<!--
				Header parts:
			-->
			<table width="100%" border="0" cellpadding="2" cellspacing="2" id="c-headerParts">
				<tr class="bgColor5">
					<td><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('include') . ':</strong></td>
					<td><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('tag') . ':</strong></td>
					<td><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('tagContent') . ':</strong></td>
				</tr>
				' . $tRows . '
				' . $bodyTagRow . '
			</table><br />';

		$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'\TYPO3\CMS\Core\Messaging\FlashMessage',
			\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('msgHeaderSet'),
			'',
			\TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
		);
		$headerParts .= $flashMessage->render();

		$headerParts .= $this->cshItem('xMOD_tx_templavoila', 'mapping_to_headerParts_buttons', $this->doc->backPath, '') . $htmlAfterDSTable;

		// Return result:
		return $headerParts;
	}

	/**
	 * Creates the template mapper table + form for either direct file mapping or Template Object
	 *
	 * @param string $displayFile The abs file name to read
	 * @param string $path The HTML-path to follow. Eg. 'td#content table[1] tr[1] / INNER | img[0]' or so. Normally comes from clicking a tag-image in the display frame.
	 * @param array $dataStruct The data Structure to map to
	 * @param array $currentMappingInfo The current mapping information
	 * @param string $htmlAfterDSTable HTML content to show after the Data Structure table.
	 *
	 * @return string HTML table.
	 */
	public function renderTemplateMapper($displayFile, $path, $dataStruct = array(), $currentMappingInfo = array(), $htmlAfterDSTable = '') {

		// Get file content
		$this->markupFile = $displayFile;
		$fileContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($this->markupFile);

		// Init mark up object.
		$this->markupObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Extension\\Templavoila\\Domain\\Model\\HtmlMarkup');

		// Load splitted content from currentMappingInfo array (used to show us which elements maps to some real content).
		$contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($fileContent, $currentMappingInfo);

		// Show path:
		$pathRendered = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $path, 1);
		$acc = array();
		foreach ($pathRendered as $k => $v) {
			$acc[] = $v;
			$pathRendered[$k] = $this->linkForDisplayOfPath($v, implode('|', $acc));
		}
		array_unshift($pathRendered, $this->linkForDisplayOfPath('[ROOT]', ''));

		// Get attributes of the extracted content:
		$attrDat = array();
		$contentFromPath = $this->markupObj->splitByPath($fileContent, $path); // ,'td#content table[1] tr[1]','td#content table[1]','map#cdf / INNER','td#content table[2] tr[1] td[1] table[1] tr[4] td.bckgd1[2] table[1] tr[1] td[1] table[1] tr[1] td.bold1px[1] img[1] / RANGE:img[2]'
		$firstTag = $this->markupObj->htmlParse->getFirstTag($contentFromPath[1]);
		list($attrDat) = $this->markupObj->htmlParse->get_tag_attributes($firstTag, 1);

		// Make options:
		$pathLevels = $this->markupObj->splitPath($path);
		$lastEl = end($pathLevels);

		$optDat = array();
		$optDat[$lastEl['path']] = 'OUTER (Include tag)';
		$optDat[$lastEl['path'] . '/INNER'] = 'INNER (Exclude tag)';

		// Tags, which will trigger "INNER" to be listed on top (because it is almost always INNER-mapping that is needed)
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('body,span,h1,h2,h3,h4,h5,h6,div,td,p,b,i,u,a', $lastEl['el'])) {
			$optDat = array_reverse($optDat);
		}

		list($parentElement, $sameLevelElements) = $this->getRangeParameters($lastEl, $this->markupObj->elParentLevel);
		if (is_array($sameLevelElements)) {
			$startFound = 0;
			foreach ($sameLevelElements as $rEl) {
				if ($startFound) {
					$optDat[$lastEl['path'] . '/RANGE:' . $rEl] = 'RANGE to "' . $rEl . '"';
				}

				// If the element has an ID the path doesn't include parent nodes
				// If it has an ID and a CSS Class - we need to throw that CSS Class(es) away - otherwise they won't match
				$curPath = stristr($rEl, '#') ? preg_replace('/^(\w+)\.?.*#(.*)$/i', '\1#\2', $rEl) : trim($parentElement . ' ' . $rEl);
				if ($curPath == $lastEl['path']) {
					$startFound = 1;
				}
			}
		}

		// Add options for attributes:
		if (is_array($attrDat)) {
			foreach ($attrDat as $attrK => $v) {
				$optDat[$lastEl['path'] . '/ATTR:' . $attrK] = 'ATTRIBUTE "' . $attrK . '" (= ' . \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($v, 15) . ')';
			}
		}

		// Create Data Structure table:
		$content = '

			<!--
				Data Structure table:
			-->
			<table border="0" cellspacing="2" cellpadding="2" class="dso_table">
			<tr class="bgColor5">
				<td nowrap="nowrap"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mapDataElement') . ':</strong>' .
			$this->cshItem('xMOD_tx_templavoila', 'mapping_head_dataElement', $this->doc->backPath, '', TRUE) .
			'</td>
		' . ($this->editDataStruct ? '<td nowrap="nowrap"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mapField') . ':</strong>' .
				$this->cshItem('xMOD_tx_templavoila', 'mapping_head_Field', $this->doc->backPath, '', TRUE) .
				'</td>' : '') . '
				<td nowrap="nowrap"><strong>' . (!$this->_preview ? \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mapInstructions') : \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mapSampleData')) . '</strong>' .
			$this->cshItem('xMOD_tx_templavoila', 'mapping_head_' . (!$this->_preview ? 'mapping_instructions' : 'sample_data'), $this->doc->backPath, '', TRUE) .
			'<br /><img src="clear.gif" width="200" height="1" alt="" /></td>
		<td nowrap="nowrap"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mapHTMLpath') . ':</strong>' .
			$this->cshItem('xMOD_tx_templavoila', 'mapping_head_HTMLpath', $this->doc->backPath, '', TRUE) .
			'</td>
		<td nowrap="nowrap"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mapAction') . ':</strong>' .
			$this->cshItem('xMOD_tx_templavoila', 'mapping_head_Action', $this->doc->backPath, '', TRUE) .
			'</td>
		<td nowrap="nowrap"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mapRules') . ':</strong>' .
			$this->cshItem('xMOD_tx_templavoila', 'mapping_head_Rules', $this->doc->backPath, '', TRUE) .
			'</td>
		' . ($this->editDataStruct ? '<td nowrap="nowrap"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mapEdit') . ':</strong>' .
				$this->cshItem('xMOD_tx_templavoila', 'mapping_head_Edit', $this->doc->backPath, '', TRUE) .
				'</td>' : '') . '
			</tr>
			' . implode('', $this->drawDataStructureMap($dataStruct, 1, $currentMappingInfo, $pathLevels, $optDat, $contentSplittedByMapping)) . '</table>
			' . $htmlAfterDSTable .
			$this->cshItem('xMOD_tx_templavoila', 'mapping_basics', $this->doc->backPath, '');

		// Make mapping window:
		$limitTags = implode(',', array_keys($this->explodeMappingToTagsStr($this->mappingToTags, 1)));
		if (($this->mapElPath && !$this->doMappingOfPath) || $this->showPathOnly || $this->_preview) {
			$content .=
				'

				<!--
					Visual Mapping Window (Iframe)
				-->
				<h3>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mapMappingWindow') . ':</h3>
			<!-- <p><strong>File:</strong> ' . htmlspecialchars($displayFile) . '</p> -->
			<p>' .
				\TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu('', 'SET[displayMode]', $this->MOD_SETTINGS['displayMode'], $this->MOD_MENU['displayMode'], 'index.php', \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $_GET, '', 1, 1)) .
				$this->cshItem('xMOD_tx_templavoila', 'mapping_window_modes', $this->doc->backPath, '') .
				'</p>';

			if ($this->_preview) {
				$content .= '

					<!--
						Preview information table
					-->
					<table border="0" cellpadding="4" cellspacing="2" id="c-mapInfo">
						<tr class="bgColor5"><td><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mapPreviewInfo') . ':</strong>' .
					$this->cshItem('xMOD_tx_templavoila', 'mapping_window_help', $this->doc->backPath, '') .
					'</td></tr>
			</table>
		';

				// Add the Iframe:
				$content .= $this->makeIframeForVisual($displayFile, '', '', 0, 1);
			} else {
				$tRows = array();
				if ($this->showPathOnly) {
					$tRows[] = '
						<tr class="bgColor4">
							<td class="bgColor5"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mapHTMLpath') . ':</strong></td>
							<td>' . htmlspecialchars(str_replace('~~~', ' ', $this->displayPath)) . '</td>
						</tr>
					';
				} else {
					$tRows[] = '
						<tr class="bgColor4">
							<td class="bgColor5"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mapDSelement') . ':</strong></td>
							<td>' . $this->elNames[$this->mapElPath]['tx_templavoila']['title'] . '</td>
						</tr>
						<tr class="bgColor4">
							<td class="bgColor5"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mapLimitToTags') . ':</strong></td>
							<td>' . htmlspecialchars(($limitTags ? strtoupper($limitTags) : '(ALL TAGS)')) . '</td>
						</tr>
						<tr class="bgColor4">
							<td class="bgColor5"><strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('mapInstructions') . ':</strong></td>
							<td>' . htmlspecialchars($this->elNames[$this->mapElPath]['tx_templavoila']['description']) . '</td>
						</tr>
					';
				}
				$content .= '

					<!--
						Mapping information table
					-->
					<table border="0" cellpadding="2" cellspacing="2" id="c-mapInfo">
						' . implode('', $tRows) . '
					</table>
				';

				// Add the Iframe:
				$content .= $this->makeIframeForVisual($displayFile, $this->displayPath, $limitTags, $this->doMappingOfPath);
			}
		}

		return $content;
	}

	/**
	 * Determines parentElement and sameLevelElements for the RANGE mapping mode
	 *
	 * @todo    this functions return value pretty dirty, but due to the fact that this is something which
	 *     should at least be encapsulated the bad coding habit it preferred just for readability of the remaining code
	 *
	 * @param array    Array containing information about the current element
	 * @param array    Array containing information about all mapable elements
	 *
	 * @return array    Array containing 0 => parentElement (string) and 1 => sameLevelElements (array)
	 */
	protected function getRangeParameters($lastEl, $elParentLevel) {
		/**
		 * Add options for "samelevel" elements -
		 * If element has an id the "parent" is empty, therefore we need two steps to get the elements (see #11842)
		 */
		$sameLevelElements = array();
		if (strlen($lastEl['parent'])) {
			// we have a "named" parent
			$parentElement = $lastEl['parent'];
			$sameLevelElements = $elParentLevel[$parentElement];
		} elseif (count($elParentLevel) == 1) {
			// we have no real parent - happens if parent element is mapped with INNER
			$parentElement = $lastEl['parent'];
			$sameLevelElements = $elParentLevel[$parentElement];
		} else {
			//there's no parent - maybe because it was wrapped with INNER therefore we try to find it ourselfs
			$parentElement = '';
			$hasId = stristr($lastEl['path'], '#');
			foreach ($elParentLevel as $pKey => $pValue) {
				if (in_array($lastEl['path'], $pValue)) {
					$parentElement = $pKey;
					break;
				} elseif ($hasId) {
					foreach ($pValue as $pElement) {
						if (stristr($pElement, '#') && preg_replace('/^(\w+)\.?.*#(.*)$/i', '\1#\2', $pElement) == $lastEl['path']) {
							$parentElement = $pKey;
							break;
						}
					}
				}
			}

			if (!$hasId && preg_match('/\[\d+\]$/', $lastEl['path'])) {
				// we have a nameless element, therefore the index is used
				$pos = preg_replace('/^.*\[(\d+)\]$/', '\1', $lastEl['path']);
				// index is "corrected" by one to include the current element in the selection
				$sameLevelElements = array_slice($elParentLevel[$parentElement], $pos - 1);
			} else {
				// we have to search ourselfs because there was no parent and no numerical index to find the right elements
				$foundCurrent = FALSE;
				if (is_array($elParentLevel[$parentElement])) {
					foreach ($elParentLevel[$parentElement] as $element) {
						$curPath = stristr($element, '#') ? preg_replace('/^(\w+)\.?.*#(.*)$/i', '\1#\2', $element) : $element;
						if ($curPath == $lastEl['path']) {
							$foundCurrent = TRUE;
						}
						if ($foundCurrent) {
							$sameLevelElements[] = $curPath;
						}
					}
				}
			}
		}

		return array($parentElement, $sameLevelElements);
	}

	/**
	 * Renders the hierarchical display for a Data Structure.
	 * Calls itself recursively
	 *
	 * @param array $dataStruct Part of Data Structure (array of elements)
	 * @param integer $mappingMode If true, the Data Structure table will show links for mapping actions. Otherwise it will just layout the Data Structure visually.
	 * @param array $currentMappingInfo Part of Current mapping information corresponding to the $dataStruct array - used to evaluate the status of mapping for a certain point in the structure.
	 * @param array $pathLevels Array of HTML paths
	 * @param array $optDat Options for mapping mode control (INNER, OUTER etc...)
	 * @param array $contentSplittedByMapping Content from template file splitted by current mapping info - needed to evaluate whether mapping information for a certain level actually worked on live content!
	 * @param integer $level Recursion level, counting up
	 * @param array $tRows Accumulates the table rows containing the structure. This is the array returned from the function.
	 * @param string $formPrefix Form field prefix. For each recursion of this function, two [] parts are added to this prefix
	 * @param string $path HTML path. For each recursion a section (divided by "|") is added.
	 * @param integer $mapOK
	 *
	 * @internal param boolean $mapOk If true, the "Map" link can be shown, otherwise not. Used internally in the recursions.
	 *
	 * @return array Table rows as an array of <tr> tags, $tRows
	 */
	public function drawDataStructureMap($dataStruct, $mappingMode = 0, $currentMappingInfo = array(), $pathLevels = array(), $optDat = array(), $contentSplittedByMapping = array(), $level = 0, $tRows = array(), $formPrefix = '', $path = '', $mapOK = 1) {

		$bInfo = \TYPO3\CMS\Core\Utility\GeneralUtility::clientInfo();
		$multilineTooltips = ($bInfo['BROWSER'] == 'msie');
		$rowIndex = -1;

		// Data Structure array must be ... and array of course...
		if (is_array($dataStruct)) {
			foreach ($dataStruct as $key => $value) {
				$rowIndex++;

				if ($key == 'meta') {
					// Do not show <meta> information in mapping interface!
					continue;
				}

				if (is_array($value)) { // The value of each entry must be an array.

					// ********************
					// Making the row:
					// ********************
					$rowCells = array();

					// Icon:
					$info = $this->dsTypeInfo($value);
					$icon = '<img' . $info[2] . ' alt="" title="' . $info[1] . $key . '" class="absmiddle" />';

					// Composing title-cell:
					if (preg_match('/^LLL:/', $value['tx_templavoila']['title'])) {
						$translatedTitle = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL($value['tx_templavoila']['title']);
						$translateIcon = '<sup title="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('displayDSTitleTranslated') . '">*</sup>';
					} else {
						$translatedTitle = $value['tx_templavoila']['title'];
						$translateIcon = '';
					}
					$this->elNames[$formPrefix . '[' . $key . ']']['tx_templavoila']['title'] = $icon . '<strong>' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($translatedTitle, 30)) . '</strong>' . $translateIcon;
					$rowCells['title'] = '<img src="clear.gif" width="' . ($level * 16) . '" height="1" alt="" />' . $this->elNames[$formPrefix . '[' . $key . ']']['tx_templavoila']['title'];

					// Description:
					$this->elNames[$formPrefix . '[' . $key . ']']['tx_templavoila']['description'] = $rowCells['description'] = htmlspecialchars($value['tx_templavoila']['description']);

					// In "mapping mode", render HTML page and Command links:
					if ($mappingMode) {

						// HTML-path + CMD links:
						$isMapOK = 0;
						if ($currentMappingInfo[$key]['MAP_EL']) { // If mapping information exists...:

							$mappingElement = str_replace('~~~', ' ', $currentMappingInfo[$key]['MAP_EL']);
							if (isset($contentSplittedByMapping['cArray'][$key])) { // If mapping of this information also succeeded...:
								$cF = implode(chr(10), \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(chr(10), $contentSplittedByMapping['cArray'][$key], 1));

								if (strlen($cF) > 200) {
									$cF = \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($cF, 90) . ' ' . \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($cF, -90);
								}

								// Render HTML path:
								list($pI) = $this->markupObj->splitPath($currentMappingInfo[$key]['MAP_EL']);

								$tagIcon = \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'html_tags/' . $pI['el'] . '.gif', 'height="17"') . ' alt="" border="0"';

								$okTitle = htmlspecialchars($cF ? sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('displayDSContentFound'), strlen($contentSplittedByMapping['cArray'][$key])) . ($multilineTooltips ? ':' . chr(10) . chr(10) . $cF : '') : \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('displayDSContentEmpty'));

								$rowCells['htmlPath'] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-ok', array('title' => $okTitle)) .
									\Extension\Templavoila\Domain\Model\HtmlMarkup::getGnyfMarkup($pI['el'], '---' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($mappingElement, -80))) .
									($pI['modifier'] ? $pI['modifier'] . ($pI['modifier_value'] ? ':' . ($pI['modifier'] != 'RANGE' ? $pI['modifier_value'] : '...') : '') : '');
								$rowCells['htmlPath'] = '<a href="' . $this->linkThisScript(array(
										'htmlPath' => $path . ($path ? '|' : '') . preg_replace('/\/[^ ]*$/', '', $currentMappingInfo[$key]['MAP_EL']),
										'showPathOnly' => 1,
										'DS_element' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('DS_element')
									)) . '">' . $rowCells['htmlPath'] . '</a>';

								// CMD links, default content:
								$rowCells['cmdLinks'] = '<span class="nobr"><input type="submit" value="Re-Map" name="_" onclick="document.location=\'' .
									$this->linkThisScript(array(
										'mapElPath' => $formPrefix . '[' . $key . ']',
										'htmlPath' => $path,
										'mappingToTags' => $value['tx_templavoila']['tags'],
										'DS_element' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('DS_element')
									)) . '\';return false;" title="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonRemapTitle') . '" />' .
									'<input type="submit" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonChangeMode') . '" name="_" onclick="document.location=\'' .
									$this->linkThisScript(array(
										'mapElPath' => $formPrefix . '[' . $key . ']',
										'htmlPath' => $path . ($path ? '|' : '') . $pI['path'],
										'doMappingOfPath' => 1,
										'DS_element' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('DS_element')
									)) . '\';return false;" title="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonChangeMode') . '" /></span>';

								// If content mapped ok, set flag:
								$isMapOK = 1;
							} else { // Issue warning if mapping was lost:
								$rowCells['htmlPath'] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-warning', array('title' => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('msgNoContentFound'))) . htmlspecialchars($mappingElement);
							}
						} else { // For non-mapped cases, just output a no-break-space:
							$rowCells['htmlPath'] = '&nbsp;';
						}

						// CMD links; Content when current element is under mapping, then display control panel or message:
						if ($this->mapElPath == $formPrefix . '[' . $key . ']') {
							if ($this->doMappingOfPath) {

								// Creating option tags:
								$lastLevel = end($pathLevels);
								$tagsMapping = $this->explodeMappingToTagsStr($value['tx_templavoila']['tags']);
								$mapDat = is_array($tagsMapping[$lastLevel['el']]) ? $tagsMapping[$lastLevel['el']] : $tagsMapping['*'];
								unset($mapDat['']);
								if (is_array($mapDat) && !count($mapDat)) {
									unset($mapDat);
								}

								// Create mapping options:
								$didSetSel = 0;
								$opt = array();
								foreach ($optDat as $k => $v) {
									list($pI) = $this->markupObj->splitPath($k);

									if (($value['type'] == 'attr' && $pI['modifier'] == 'ATTR') || ($value['type'] != 'attr' && $pI['modifier'] != 'ATTR')) {
										if (
											(!$this->markupObj->tags[$lastLevel['el']]['single'] || $pI['modifier'] != 'INNER') &&
											(!is_array($mapDat) || ($pI['modifier'] != 'ATTR' && isset($mapDat[strtolower($pI['modifier'] ? $pI['modifier'] : 'outer')])) || ($pI['modifier'] == 'ATTR' && (isset($mapDat['attr']['*']) || isset($mapDat['attr'][$pI['modifier_value']]))))

										) {

											if ($k == $currentMappingInfo[$key]['MAP_EL']) {
												$sel = ' selected="selected"';
												$didSetSel = 1;
											} else {
												$sel = '';
											}
											$opt[] = '<option value="' . htmlspecialchars($k) . '"' . $sel . '>' . htmlspecialchars($v) . '</option>';
										}
									}
								}

								// Finally, put together the selector box:
								$rowCells['cmdLinks'] = \Extension\Templavoila\Domain\Model\HtmlMarkup::getGnyfMarkup($pI['el'], '---' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($lastLevel['path'], -80))) .
									'<br /><select name="dataMappingForm' . $formPrefix . '[' . $key . '][MAP_EL]">
										' . implode('
										', $opt) . '
										<option value=""></option>
									</select>
									<br />
									<input type="submit" name="_save_data_mapping" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonSet') . '" />
									<input type="submit" name="_" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonCancel') . '" />';
								$rowCells['cmdLinks'] .=
									$this->cshItem('xMOD_tx_templavoila', 'mapping_modeset', $this->doc->backPath, '', FALSE, 'margin-bottom: 0px;');
							} else {
								$rowCells['cmdLinks'] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-notification') . '
														<strong>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('msgHowToMap') . '</strong>';
								$rowCells['cmdLinks'] .= '<br />
										<input type="submit" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonCancel') . '" name="_" onclick="document.location=\'' .
									$this->linkThisScript(array(
										'DS_element' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('DS_element')
									)) . '\';return false;" />';
							}
						} elseif (!$rowCells['cmdLinks'] && $mapOK && $value['type'] != 'no_map') {
							$rowCells['cmdLinks'] = '<input type="submit" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('buttonMap') . '" name="_" onclick="document.location=\'' .
								$this->linkThisScript(array(
									'mapElPath' => $formPrefix . '[' . $key . ']',
									'htmlPath' => $path,
									'mappingToTags' => $value['tx_templavoila']['tags'],
									'DS_element' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('DS_element')
								)) . '\';return false;" />';
						}
					}

					// Display mapping rules:
					$rowCells['tagRules'] = implode('<br />', \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', strtolower($value['tx_templavoila']['tags']), 1));
					if (!$rowCells['tagRules']) {
						$rowCells['tagRules'] = $GLOBALS['LANG']->getLL('all');
					}

					// Display edit/delete icons:
					if ($this->editDataStruct) {
						$editAddCol = '<a href="' . $this->linkThisScript(array(
								'DS_element' => $formPrefix . '[' . $key . ']'
							)) . '">' .
							\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open', array('title' => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('editEntry'))) .
							'</a>
							<a href="' . $this->linkThisScript(array(
								'DS_element_DELETE' => $formPrefix . '[' . $key . ']'
							)) . '"
											onClick="return confirm(' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->JScharCode(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('confirmDeleteEntry')) . ');">' .
							\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-delete', array('title' => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('deleteEntry'))) .
							'</a>';
						$editAddCol = '<td nowrap="nowrap">' . $editAddCol . '</td>';
					} else {
						$editAddCol = '';
					}

					// Description:
					if ($this->_preview) {
						if (!is_array($value['tx_templavoila']['sample_data'])) {
							$rowCells['description'] = '[' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('noSampleData') . ']';
						} else {
							$rowCells['description'] = \TYPO3\CMS\Core\Utility\DebugUtility::viewArray($value['tx_templavoila']['sample_data']);
						}
					}

					// Getting editing row, if applicable:
					list($addEditRows, $placeBefore) = $this->dsEdit->drawDataStructureMap_editItem($formPrefix, $key, $value, $level, $rowCells);

					// Add edit-row if found and destined to be set BEFORE:
					if ($addEditRows && $placeBefore) {
						$tRows[] = $addEditRows;
					} else // Put row together
					{
						if (!$this->mapElPath || $this->mapElPath == $formPrefix . '[' . $key . ']') {
							$tRows[] = '

							<tr class="' . ($rowIndex % 2 ? 'bgColor4' : 'bgColor6') . '">
							<td nowrap="nowrap" valign="top">' . $rowCells['title'] . '</td>
							' . ($this->editDataStruct ? '<td nowrap="nowrap">' . $key . '</td>' : '') . '
							<td>' . $rowCells['description'] . '</td>
							' . ($mappingMode
									?
									'<td nowrap="nowrap">' . $rowCells['htmlPath'] . '</td>
								<td>' . $rowCells['cmdLinks'] . '</td>'
									:
									''
								) . '
							<td>' . $rowCells['tagRules'] . '</td>
							' . $editAddCol . '
						</tr>';
						}
					}

					// Recursive call:
					if (($value['type'] == 'array') ||
						($value['type'] == 'section')
					) {
						$tRows = $this->drawDataStructureMap(
							$value['el'],
							$mappingMode,
							$currentMappingInfo[$key]['el'],
							$pathLevels,
							$optDat,
							$contentSplittedByMapping['sub'][$key],
							$level + 1,
							$tRows,
							$formPrefix . '[' . $key . '][el]',
							$path . ($path ? '|' : '') . $currentMappingInfo[$key]['MAP_EL'],
							$isMapOK
						);
					}
					// Add edit-row if found and destined to be set AFTER:
					if ($addEditRows && !$placeBefore) {
						$tRows[] = $addEditRows;
					}
				}
			}
		}

		return $tRows;
	}


	/*******************************
	 *
	 * Various helper functions
	 *
	 *******************************/

	/**
	 * Returns Data Structure from the $datString
	 *
	 * @param string $datString XML content which is parsed into an array, which is returned.
	 * @param string $file Absolute filename from which to read the XML data. Will override any input in $datString
	 *
	 * @return mixed The variable $dataStruct. Should be array. If string, then no structures was found and the function returns the XML parser error.
	 */
	public function getDataStructFromDSO($datString, $file = '') {
		if ($file) {
			$dataStruct = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array(\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($file));
		} else {
			$dataStruct = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($datString);
		}

		return $dataStruct;
	}

	/**
	 * Creating a link to the display frame for display of the "HTML-path" given as $path
	 *
	 * @param string $title The text to link
	 * @param string $path The path string ("HTML-path")
	 *
	 * @return string HTML link, pointing to the display frame.
	 */
	public function linkForDisplayOfPath($title, $path) {
		$theArray = array(
			'file' => $this->markupFile,
			'path' => $path,
			'mode' => 'display'
		);
		$p = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $theArray);

		$content .= '<strong><a href="' . htmlspecialchars('index.php?' . $p) . '" target="display">' . $title . '</a></strong>';

		return $content;
	}

	/**
	 * Creates a link to this script, maintaining the values of the displayFile, displayTable, displayUid variables.
	 * Primarily used by ->drawDataStructureMap
	 *
	 * @param array $array Overriding parameters.
	 *
	 * @return string URL, already htmlspecialchars()'ed
	 * @see drawDataStructureMap()
	 */
	public function linkThisScript($array = array()) {
		$theArray = array(
			'id' => $this->id, // id of the current sysfolder
			'file' => $this->displayFile,
			'table' => $this->displayTable,
			'uid' => $this->displayUid,
			'returnUrl' => $this->returnUrl,
			'_load_ds_xml_to' => $this->_load_ds_xml_to
		);
		$p = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', array_merge($theArray, $array), '', 1);

		return htmlspecialchars('index.php?' . $p);
	}

	/**
	 * Creates the HTML code for the IFRAME in which the display mode is shown:
	 *
	 * @param string $file File name to display in exploded mode.
	 * @param string $path HTML-page
	 * @param string $limitTags Tags which is the only ones to show
	 * @param boolean $showOnly If set, the template is only shown, mapping links disabled.
	 * @param integer $preview Preview enabled.
	 *
	 * @return string HTML code for the IFRAME.
	 * @see main_display()
	 */
	public function makeIframeForVisual($file, $path, $limitTags, $showOnly, $preview = 0) {
		$url = 'index.php?mode=display' .
			'&file=' . rawurlencode($file) .
			'&path=' . rawurlencode($path) .
			'&preview=' . ($preview ? 1 : 0) .
			($showOnly ? '&show=1' : '&limitTags=' . rawurlencode($limitTags));

		return '<iframe id="templavoila-frame-visual" src="' . htmlspecialchars($url) . '#_MARKED_UP_ELEMENT"></iframe>';
	}

	/**
	 * Converts a list of mapping rules to an array
	 *
	 * @param string $mappingToTags Mapping rules in a list
	 * @param integer $unsetAll If set, then the ALL rule (key "*") will be unset.
	 *
	 * @return array Mapping rules in a multidimensional array.
	 */
	public function explodeMappingToTagsStr($mappingToTags, $unsetAll = 0) {
		$elements = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', strtolower($mappingToTags));
		$output = array();
		foreach ($elements as $v) {
			$subparts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $v);
			$output[$subparts[0]][$subparts[1]][($subparts[2] ? $subparts[2] : '*')] = 1;
		}
		if ($unsetAll) {
			unset($output['*']);
		}

		return $output;
	}

	/**
	 * General purpose unsetting of elements in a multidimensional array
	 *
	 * @param array &$dataStruct Array from which to remove elements (passed by reference!)
	 * @param array $ref An array where the values in the specified order points to the position in the array to unset.
	 *
	 * @return void
	 */
	public function unsetArrayPath(&$dataStruct, $ref) {
		$key = array_shift($ref);

		if (!count($ref)) {
			unset($dataStruct[$key]);
		} elseif (is_array($dataStruct[$key])) {
			$this->unsetArrayPath($dataStruct[$key], $ref);
		}
	}

	/**
	 * Function to clean up "old" stuff in the currentMappingInfo array. Basically it will remove EVERYTHING which is not known according to the input Data Structure
	 *
	 * @param array &$currentMappingInfo Current Mapping info (passed by reference)
	 * @param array $dataStruct Data Structure
	 *
	 * @return void
	 */
	public function cleanUpMappingInfoAccordingToDS(&$currentMappingInfo, $dataStruct) {
		if (is_array($currentMappingInfo)) {
			foreach ($currentMappingInfo as $key => $value) {
				if (!isset($dataStruct[$key])) {
					unset($currentMappingInfo[$key]);
				} else {
					if (is_array($dataStruct[$key]['el'])) {
						$this->cleanUpMappingInfoAccordingToDS($currentMappingInfo[$key]['el'], $dataStruct[$key]['el']);
					}
				}
			}
		}
	}

	/**
	 * Generates $this->storageFolders with available sysFolders linked to as storageFolders for the user
	 *
	 * @return void Modification in $this->storageFolders array
	 */
	public function findingStorageFolderIds() {
		global $TYPO3_DB;

		// Init:
		$readPerms = \Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->getPagePermsClause(1);
		$this->storageFolders = array();

		// Looking up all references to a storage folder:
		$res = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTquery(
			'uid,storage_pid',
			'pages',
			'storage_pid>0' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('pages')
		);
		while (FALSE !== ($row = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
			if (\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->isInWebMount($row['storage_pid'], $readPerms)) {
				$storageFolder = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $row['storage_pid'], 'uid,title');
				if ($storageFolder['uid']) {
					$this->storageFolders[$storageFolder['uid']] = $storageFolder['title'];
				}
			}
		}

		// Looking up all root-pages and check if there's a tx_templavoila.storagePid setting present
		$res = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTquery(
			'pid,root',
			'sys_template',
			'root=1' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('sys_template')
		);
		while (FALSE !== ($row = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
			$tsCconfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig($row['pid'], 'tx_templavoila');
			if (
				isset($tsCconfig['properties']['storagePid']) &&
				\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->isInWebMount($tsCconfig['properties']['storagePid'], $readPerms)
			) {
				$storageFolder = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $tsCconfig['properties']['storagePid'], 'uid,title');
				if ($storageFolder['uid']) {
					$this->storageFolders[$storageFolder['uid']] = $storageFolder['title'];
				}
			}
		}

		// Compopsing select list:
		$sysFolderPIDs = array_keys($this->storageFolders);
		$sysFolderPIDs[] = 0;
		$this->storageFolders_pidList = implode(',', $sysFolderPIDs);
	}







	/*****************************************
	 *
	 * DISPLAY mode
	 *
	 *****************************************/

	/**
	 * Outputs the display of a marked-up HTML file in the IFRAME
	 *
	 * @return void Exits before return
	 * @see makeIframeForVisual()
	 */
	public function main_display() {

		// Setting GPvars:
		$this->displayFile = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('file');
		$this->show = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('show');
		$this->preview = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('preview');
		$this->limitTags = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('limitTags');
		$this->path = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('path');

		// Checking if the displayFile parameter is set:
		if (@is_file($this->displayFile) && \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->displayFile)) { // FUTURE: grabbing URLS?: 		.... || substr($this->displayFile,0,7)=='http://'
			$content = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($this->displayFile);
			if ($content) {
				$relPathFix = $GLOBALS['BACK_PATH'] . '../' . dirname(substr($this->displayFile, strlen(PATH_site))) . '/';

				if ($this->preview) { // In preview mode, merge preview data into the template:
					// Add preview data to file:
					$content = $this->displayFileContentWithPreview($content, $relPathFix);
				} else {
					// Markup file:
					$content = $this->displayFileContentWithMarkup($content, $this->path, $relPathFix, $this->limitTags);
				}
				// Output content:
				echo $content;
			} else {
				$this->displayFrameError(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('errorNoContentInFile') . ': <em>' . htmlspecialchars($this->displayFile) . '</em>');
			}
		} else {
			$this->displayFrameError(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('errorNoFileToDisplay'));
		}

		// Exit since a full page has been outputted now.
		exit;
	}

	/**
	 * This will mark up the part of the HTML file which is pointed to by $path
	 *
	 * @param string $content The file content as a string
	 * @param string $path The "HTML-path" to split by
	 * @param string $relPathFix The rel-path string to fix images/links with.
	 * @param string $limitTags List of tags to show
	 *
	 * @return string
	 * @see main_display()
	 */
	public function displayFileContentWithMarkup($content, $path, $relPathFix, $limitTags) {
		$markupObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Extension\\Templavoila\\Domain\\Model\\HtmlMarkup');
		$markupObj->gnyfImgAdd = $this->show ? '' : 'onclick="return parent.updPath(\'###PATH###\');"';
		$markupObj->pathPrefix = $path ? $path . '|' : '';
		$markupObj->onlyElements = $limitTags;

#		$markupObj->setTagsFromXML($content);

		$cParts = $markupObj->splitByPath($content, $path);
		if (is_array($cParts)) {
			$cParts[1] = $markupObj->markupHTMLcontent(
				$cParts[1],
				$GLOBALS['BACK_PATH'],
				$relPathFix,
				implode(',', array_keys($markupObj->tags)),
				$this->MOD_SETTINGS['displayMode']
			);
			$cParts[0] = $markupObj->passthroughHTMLcontent($cParts[0], $relPathFix, $this->MOD_SETTINGS['displayMode']);
			$cParts[2] = $markupObj->passthroughHTMLcontent($cParts[2], $relPathFix, $this->MOD_SETTINGS['displayMode']);
			if (trim($cParts[0])) {
				$cParts[1] = '<a name="_MARKED_UP_ELEMENT"></a>' . $cParts[1];
			}

			$markup = implode('', $cParts);
			$styleBlock = '<style type="text/css">' . self::$gnyfStyleBlock . '</style>';
			if (preg_match('/<\/head/i', $markup)) {
				$finalMarkup = preg_replace('/(<\/head)/i', $styleBlock . '\1', $markup);
			} else {
				$finalMarkup = $styleBlock . $markup;
			}

			return $finalMarkup;
		}
		$this->displayFrameError($cParts);

		return '';
	}

	/**
	 * This will add preview data to the HTML file used as a template according to the currentMappingInfo
	 *
	 * @param string $content The file content as a string
	 * @param string $relPathFix The rel-path string to fix images/links with.
	 *
	 * @return string
	 * @see main_display()
	 */
	public function displayFileContentWithPreview($content, $relPathFix) {

		// Getting session data to get currentMapping info:
		$sesDat = \Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->getSessionData($this->sessionKey);
		$currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : array();

		// Init mark up object.
		$this->markupObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Extension\\Templavoila\\Domain\\Model\\HtmlMarkup');
		$this->markupObj->htmlParse = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Html\\HtmlParser');

		// Splitting content, adding a random token for the part to be previewed:
		$contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($content, $currentMappingInfo);
		$token = md5(microtime());
		$content = $this->markupObj->mergeSampleDataIntoTemplateStructure($sesDat['dataStruct'], $contentSplittedByMapping, $token);

		// Exploding by that token and traverse content:
		$pp = explode($token, $content);
		foreach ($pp as $kk => $vv) {
			$pp[$kk] = $this->markupObj->passthroughHTMLcontent($vv, $relPathFix, $this->MOD_SETTINGS['displayMode'], $kk == 1 ? 'font-size:11px; color:#000066;' : '');
		}

		// Adding a anchor point (will work in most cases unless put into a table/tr tag etc).
		if (trim($pp[0])) {
			$pp[1] = '<a name="_MARKED_UP_ELEMENT"></a>' . $pp[1];
		}
		// Implode content and return it:
		$markup = implode('', $pp);
		$styleBlock = '<style type="text/css">' . self::$gnyfStyleBlock . '</style>';
		if (preg_match('/<\/head/i', $markup)) {
			$finalMarkup = preg_replace('/(<\/head)/i', $styleBlock . '\1', $markup);
		} else {
			$finalMarkup = $styleBlock . $markup;
		}

		return $finalMarkup;
	}

	/**
	 * Outputs a simple HTML page with an error message
	 *
	 * @param string Error message for output in <h2> tags
	 *
	 * @return void Echos out an HTML page.
	 */
	public function displayFrameError($error) {
		echo '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
	<title>Untitled</title>
</head>

<body bgcolor="#eeeeee">
<h2>ERROR: ' . $error . '</h2>
</body>
</html>
			';
	}

	/**
	 * Wrapper function for context sensitive help - for downwards compatibility with TYPO3 prior 3.7.x
	 *
	 * @param string $table Table name ('_MOD_'+module name)
	 * @param string $field Field name (CSH locallang main key)
	 * @param string $BACK_PATH Back path
	 * @param string $wrap Wrap code for icon-mode, splitted by "|". Not used for full-text mode.
	 * @param boolean $onlyIconMode If set, the full text will never be shown (only icon). Useful for places where it will break the page if the table with full text is shown.
	 * @param string $styleAttrib Additional style-attribute content for wrapping table (full text mode only)
	 *
	 * @return string HTML content for help text
	 */
	public function cshItem($table, $field, $BACK_PATH, $wrap = '', $onlyIconMode = FALSE, $styleAttrib = '') {
		if (is_callable(array('\TYPO3\CMS\Backend\Utility\BackendUtility', 'cshItem'))) {
			return \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem($table, $field, $BACK_PATH, $wrap, $onlyIconMode, $styleAttrib);
		}

		return '';
	}

	/**
	 * @param string $formElementName
	 *
	 * @return string
	 */
	public function lipsumLink($formElementName) {
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('lorem_ipsum')) {
			$LRobj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_loremipsum_wiz');
			$LRobj->backPath = $this->doc->backPath;

			$PA = array(
				'fieldChangeFunc' => array(),
				'formName' => 'pageform',
				'itemName' => $formElementName . '[]',
				'params' => array(
#					'type' => 'header',
					'type' => 'description',
					'add' => 1,
					'endSequence' => '46,32',
				)
			);

			return $LRobj->main($PA, 'ID:templavoila');
		}

		return '';
	}

	/**
	 * @param array $currentMappingInfo_head
	 * @param mixed $html_header
	 *
	 * @return mixed
	 */
	public function buildCachedMappingInfo_head($currentMappingInfo_head, $html_header) {
		$h_currentMappingInfo = array();
		if (is_array($currentMappingInfo_head['headElementPaths'])) {
			foreach ($currentMappingInfo_head['headElementPaths'] as $kk => $vv) {
				$h_currentMappingInfo['el_' . $kk]['MAP_EL'] = $vv;
			}
		}

		return $this->markupObj->splitContentToMappingInfo($html_header, $h_currentMappingInfo);
	}

	/**
	 * Checks if link points to local marker or not and sets prefix accordingly.
	 *
	 * @param string $relPathFix Prefix
	 * @param string $fileContent Content
	 * @param string $uniqueMarker Marker inside links
	 *
	 * @return string Content
	 */
	public function fixPrefixForLinks($relPathFix, $fileContent, $uniqueMarker) {
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
	/**
	 * @param string $file
	 * @param boolean $raw
	 *
	 * @return string
	 */
	function md5_file($file, $raw = FALSE) {
		return md5(file_get_contents($file), $raw);
	}
}

// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_templavoila_cm1');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
