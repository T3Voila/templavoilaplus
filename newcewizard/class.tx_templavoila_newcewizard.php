<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Dmitry Dulepov <dmitry@typo3.org>
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * $Id$
 */

unset($MCONF);
require ('conf.php');
require ($BACK_PATH . 'init.php');
require ($BACK_PATH . 'template.php');

// Unset MCONF/MLANG since all we wanted was back path etc. for this particular script.
unset($MCONF);
unset($MLANG);

require_once(t3lib_extMgm::extPath('templavoila', 'class.tx_templavoila_api.php'));
require_once(t3lib_extMgm::extPath('templavoila', 'newcewizard/tabs/class.tx_templavoila_basetab.php'));
require_once(t3lib_extMgm::extPath('templavoila', 'newcewizard/model/class.tx_templavoila_contentelementdescriptor.php'));
require_once(t3lib_extMgm::extPath('templavoila', 'newcewizard/view/class.tx_templavoila_tabview.php'));

/**
 * This class contains a new content element wizard for TemplaVoila
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_templavoia
 */
class tx_templavoila_newcewizard {

	/**
	 * TemplaVoila API object
	 *
	 * @var	tx_templavoila_api
	 */
	protected	$apiObj;

	/**
	 * Accumulated content
	 *
	 * @var	string
	 */
	protected	$content = '';

	/**
	 * Document
	 *
	 * @var	mediumDoc
	 */
	protected	$doc;

	/**
	 * List of functions to create tabs
	 *
	 * @var	array
	 */
	protected $tabList = array();

	/**
	 * Indicates whether current user has page access
	 *
	 * @var	boolean
	 */
	protected	$hasPageAccess;

	/**
	 * Current page id
	 *
	 * @var	int
	 */
	protected	$id;

	/**
	 * Additional include files. XCLASS modules can add files here
	 *
	 * @var	array
	 */
	protected	$includeFiles = array();

	/**
	 * Produces the output of this module.
	 *
	 * @return	string	Content
	 */
	public function main() {
		$this->init();
		$this->createContent();
		echo $this->content;
	}

	/**
	 * Retrieves request 'defVals'parameters
	 *
	 * @return	string	Default values
	 */
	public function getDefVals() {
		return $this->defVals;
	}

	/**
	 * Retrieves document
	 *
	 * @return	template
	 */
	public function getDoc() {
		return $this->doc;
	}

	/**
	 * Retrieves current page id
	 *
	 * @return	int	Page id
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Retrieves the API object instance.
	 *
	 * @return	tx_templavoila_api	The API object
	 */
	public function getApiObj() {
		return $this->apiObj;
	}

	/**
	 * Standard initialization of the module
	 *
	 * @return	void
	 */
	protected function init() {
		$this->setVariables();
		$this->createDocument();
		$this->includeFiles();
		$this->createTabList();

		$this->apiObj = t3lib_div::makeInstance('tx_templavoila_api');

		$GLOBALS['LANG']->includeLLFile('EXT:templavoila/newcewizard/locallang.xml');
		$lang = $GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_misc.xml', false);
		$GLOBALS['LOCAL_LANG'] = t3lib_div::array_merge_recursive_overrule($lang, $GLOBALS['LOCAL_LANG']);
		$lang = $GLOBALS['LANG']->includeLLFile('EXT:templavoila/mod1/locallang_db_new_content_el.xml', false);
		$GLOBALS['LOCAL_LANG'] = t3lib_div::array_merge_recursive_overrule($lang, $GLOBALS['LOCAL_LANG']);
	}

	/**
	 * Sets internal variables. This function is called from the init() method
	 *
	 * @return	void
	 */
	protected function setVariables() {
		$this->id = intval(t3lib_div::_GP('id'));

		$pageinfo = t3lib_BEfunc::readPageAccess($this->id, $GLOBALS['BE_USER']->getPagePermsClause(1));
		$this->hasPageAccess = is_array($pageinfo);
	}

	/**
	 * Creates a document
	 *
	 * @return	void
	 */
	protected function createDocument() {
		$this->doc = t3lib_div::makeInstance('bigDoc');
		$this->doc->docType= 'xhtml_trans';
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->JScode = '';
		$this->doc->form = '<form action="" name="editForm">' .
			'<input type="hidden" name="id" value="' . $this->id . '" />';
		$this->doc->inDocStyles .= '
			body {
				padding: 10px 0 0 10px;
			}
			h2 {
				font-variant: small-caps;
				margin-bottom: 10px;
			}
			.ce-elements, .str-content {
				margin-top: 10px;
				width: 100%;
			}
			.str-content {
				padding: 3px 3px;
			}
			.ce-elements tr:first-child td.ce-cell {
				border-top: 1px solid #404040;
			}
			td.ce-cell {
				border-bottom: 1px solid #404040;
				clear: left;
				overflow: hidden;
				padding: 3px 3px;
			}
			table.ce-elements tr td .title {
				font-variant: small-caps;
				font-weight: bold;
				margin-bottom: 5px;
			}
			table.ce-elements tr td .desc {
				font-size: 80%;
			}
			div.typo3-bigDoc {
				width: 575px;
			}
			#newcewizard-hint {
				margin-bottom: 5px;
			}
		';
	}

	/**
	 * Includes necessary 3rd party files.
	 *
	 * @return	void
	 */
	protected function includeFiles() {
		global	$LANG, $T3_SERVICES, $T3_VAR, $TYPO3_CONF_VARS;

		// Setting class files to include:
		if (is_array($GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses']))	{
			$this->includeFiles = array_merge($this->includeFiles, $GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses']);
		}
		foreach ($this->includeFiles as $file) {
			include_once($file);
		}
	}

	/**
	 * Creates content for the new content element wizard
	 *
	 * @return	void
	 */
	protected function createContent() {
		// Create content top
		if (t3lib_extMgm::isLoaded('t3skin')) {
			// Fix padding for t3skin in disabled tabs
			$this->doc->inDocStyles .= '
				table.typo3-dyntabmenu td.disabled, table.typo3-dyntabmenu td.disabled_over, table.typo3-dyntabmenu td.disabled:hover { padding-left: 10px; }
			';
		}
		$this->doc->JScode .= $this->doc->getDynTabMenuJScode();
		$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL('newcewizard.title'));
		$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('newcewizard.title'));
		$this->content .= $this->doc->spacer(5);

		if ($this->id == 0) {
			// No page id or "Globe" level
			$this->content .= '<img' .
				t3lib_iconWorks::skinImg($this->doc->backPath,
				'gfx/icon_fatalerror.gif','') . ' alt="" /> ' .
				$GLOBALS['LANG']->getLL('newcewizard.select_a_page');
		}
		elseif (!$this->hasPageAccess) {
			$this->content .= '<img' .
				t3lib_iconWorks::skinImg($this->doc->backPath,
				'gfx/icon_fatalerror.gif','') . ' alt="" /> ' .
				$GLOBALS['LANG']->getLL('newcewizard.no_page_access');
		}
		else {
			$this->createHint();
			$this->createTabs();
		}
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Creates a hint about using this wizard
	 *
	 * @return	void
	 */
	protected function createHint() {
		if (t3lib_div::_GP('no_newcewizard_hint')) {
			$GLOBALS['BE_USER']->uc['no_newcewizard_hint'] = true;
			$GLOBALS['BE_USER']->writeUC();
		}
		if (!$GLOBALS['BE_USER']->uc['no_newcewizard_hint']) {
			$this->content .= '<div id="newcewizard-hint">' .
				'<table cellspacing="5"><tr valign="top"><td class="bgColor5" width="5"> </td><td>' .
					'<img ' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],
						'gfx/zoom2.gif') . ' vspace="5" alt="" />' .
					'</td><td>' .
					$GLOBALS['LANG']->getLL('newcewizard.hint') .
					'<br /><br />' .
					'<input type="checkbox" name="no_newcewizard_hint" value="1" onchange="' .
					'document.forms[0].submit()" /> ' .
					$GLOBALS['LANG']->getLL('newcewizard.hint_disable') .
				'</td></tr></table>' .
				'</div>';
		}
	}

	/**
	 * Creates tabs in the new content element wizard
	 *
	 * @return	void
	 */
	protected function createTabs() {
		$tabItems = array();
		$selectElTab = false;
		$defaultTab = 1; $currentTab = 1;
		foreach ($this->tabList as $tabId => $tabDef) {
			// Include file if necessary
			if (isset($tabDef['file'])) {
				include_once(t3lib_div::getFileAbsFileName($tabDef['file']));
			}
			if (class_exists($tabDef['class'])) {
				// Create class and get content
				if(version_compare(TYPO3_version,'4.3.0','<')) {
					$className = t3lib_div::makeInstanceClassName($tabDef['class']);
					$tabInstance = new $className($this);
				} else {
					$tabInstance = t3lib_div::makeInstance($tabDef['class'],$this);
				}
				if ($tabInstance instanceof tx_templavoila_baseTab) {
					/* @var $tabInstance tx_templavoila_basetab */
					$tabContent = trim($tabInstance->getTabContent());

					// If content is not empty, add title
					if (strval($tabContent) != '') {
						if (substr($tabDef, 0, 4) == 'LLL:') {
							$title = $GLOBALS['LANG']->sL($tabDef['title']);
						}
						else {
							$title = $GLOBALS['LANG']->getLL($tabDef['title']);
						}
						$tabContent = preg_replace('/\s{2,/', ' ', $tabContent);
						$tabItems[] = array(
							'label' => $title,
							'content' => $tabContent
						);

						if ($selectElTab) {
							$defaultTab = $currentTab;
							$selectElTab = false;
						}
						elseif ($tabId == 'recent') {
							$selectElTab = ($tabInstance->getElementCount() == 0);
						}
						$currentTab++;
					}
				}
			}
		}

		// Create tabs
		$content = $this->doc->getDynTabMenu($tabItems, 'templavoila_newcewizard', 0, false, 100, 1, true, $defaultTab);
		$this->content .= $this->doc->section('', $content, 0, 1);
	}

	/**
	 * Fills in $this->tabList with default items
	 *
	 * @return	void
	 */
	protected function createTabList() {
		$this->tabList += array(
/*
			'favorites' => array(
				'title' => 'newcewizard.favorites',
				'file' => 'EXT:templavoila/newcewizard/tabs/class.tx_templavoila_favoritestab.php',
				'class' =>  'tx_templavoila_favoritestab'
			),
*/
			'recent' => array(
				'title' => 'newcewizard.recent',
				'file' => 'EXT:templavoila/newcewizard/tabs/class.tx_templavoila_recenttab.php',
				'class' =>  'tx_templavoila_recenttab'
			),
			'standard' => array(
				'title' => 'newcewizard.standard',
				'file' => 'EXT:templavoila/newcewizard/tabs/class.tx_templavoila_standardelementstab.php',
				'class' =>  'tx_templavoila_standardelementstab'
			),
			'menus' => array(
				'title' => 'newcewizard.menus',
				'file' => 'EXT:templavoila/newcewizard/tabs/class.tx_templavoila_menutab.php',
				'class' =>  'tx_templavoila_menutab'
			),
			'forms' => array(
				'title' => 'newcewizard.forms',
				'file' => 'EXT:templavoila/newcewizard/tabs/class.tx_templavoila_formstab.php',
				'class' =>  'tx_templavoila_formstab'
			),
			'fce' => array(
				'title' => 'newcewizard.fce',
				'file' => 'EXT:templavoila/newcewizard/tabs/class.tx_templavoila_fcetab.php',
				'class' =>  'tx_templavoila_fcetab'
			),
			'plugins' => array(
				'title' => 'newcewizard.plugins',
				'file' => 'EXT:templavoila/newcewizard/tabs/class.tx_templavoila_pluginstab.php',
				'class' =>  'tx_templavoila_pluginstab'
			)
		);

		// Call hooks
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['newcewizard']['tabs'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['newcewizard']['tabs'] as $userFunc) {
				$params = array(
					'pObj' => &$this,
					'tabs' => &$this->tabList
				);
				t3lib_div::callUserFunction($userFunc, $params, $this);
			}
		}

		// TODO Check TSConfig and remove unwanted tabs
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/class.tx_templavoila_newcewizard.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/class.tx_templavoila_newcewizard.php']);
}

$SOBE = t3lib_div::makeInstance('tx_templavoila_newcewizard');
$SOBE->main();

?>