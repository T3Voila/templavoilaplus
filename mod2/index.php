<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003-2006 Kasper Sk�rh�j <kasper@typo3.com>
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
 * Module 'TemplaVoila' for the 'templavoila' extension.
 *
 * $Id$
 *
 * @author   Kasper Sk�rh�j <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  101: class tx_templavoila_module2 extends t3lib_SCbase
 *  125:     function menuConfig()
 *  144:     function main()
 *  203:     function printContent()
 *
 *              SECTION: Rendering module content:
 *  227:     function renderModuleContent()
 *  264:     function renderModuleContent_searchForTODS()
 *  326:     function renderModuleContent_mainView()
 *  460:     function renderDSlisting($dsScopeArray, &$toRecords,$scope)
 *  563:     function renderDataStructureDisplay($dsR, $toIdArray, $scope)
 *  718:     function renderTODisplay($toObj, &$toRecords, $scope, $children=0)
 *  902:     function findRecordsWhereTOUsed($toObj,$scope)
 * 1041:     function findDSUsageWithImproperTOs($dsID, $toIdArray, $scope)
 * 1158:     function findRecordsWhereUsed_pid($pid)
 * 1174:     function completeTemplateFileList()
 * 1271:     function setErrorLog($scope,$type,$HTML)
 * 1282:     function getErrorLog($scope)
 * 1308:     function DSdetails($DSstring)
 *
 *              SECTION: Wizard for new site
 * 1381:     function renderNewSiteWizard_overview()
 * 1442:     function renderNewSiteWizard_run()
 * 1491:     function wizard_checkMissingExtensions()
 * 1527:     function wizard_checkConfiguration()
 * 1537:     function wizard_checkDirectory()
 * 1557:     function wizard_step1()
 * 1620:     function wizard_step2()
 * 1669:     function wizard_step3()
 * 1778:     function wizard_step4()
 * 1800:     function wizard_step5($menuField)
 * 2039:     function wizard_step6()
 * 2060:     function getImportObj()
 * 2078:     function syntaxHLTypoScript($v)
 * 2094:     function makeWrap($cfg)
 * 2110:     function getMenuDefaultCode($field)
 * 2122:     function saveMenuCode()
 * 2160:     function getBackgroundColor($filePath)
 *
 * TOTAL FUNCTIONS: 33
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


	// Initialize module
unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:templavoila/mod2/locallang.xml');
require_once (PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);    // This checks permissions and exits if the users has no permission for entry.


require_once (t3lib_extMgm::extPath('templavoila') . 'classes/class.tx_templavoila_div.php');


/**
 * Module 'TemplaVoila' for the 'templavoila' extension.
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package		TYPO3
 * @subpackage	tx_templavoila
 */
class tx_templavoila_module2 extends t3lib_SCbase {

		// External static:
	var $importPageUid = 0;	// Import as first page in root!


	var $wizardData = array();	// Session data during wizard

	var $pageinfo;
	var $modTSconfig;
	var $extKey = 'templavoila';			// Extension key of this module

	var $tFileList=array();
	var $errorsWarnings=array();

	var $extConf;							// holds the extconf configuration

	var $cm1Link = '../cm1/index.php';


	function init() {
		parent::init();

		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);
	}

	/**
	 * Preparing menu content
	 *
	 * @return	void
	 */
	function menuConfig()	{
		$this->MOD_MENU = array(
#			'set_showDSxml' => '',
			'set_details' => '',
			'set_unusedDs' => '',
			'wiz_step' => ''
		);

			// page/be_user TSconfig settings and blinding of menu-items
		$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->id,'mod.'.$this->MCONF['name']);

			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);
	}

	/**
	 * Main function of the module.
	 *
	 * @return	void		Nothing.
	 */
	function main()    {
		global $BE_USER,$LANG,$BACK_PATH;

			// Access check!
			// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->docType= 'xhtml_trans';
		$this->doc->backPath = $BACK_PATH;
		$this->doc->setModuleTemplate('EXT:templavoila/resources/templates/mod2_default.html');
		$this->doc->bodyTagId = 'typo3-mod-php';
		$this->doc->divClass = '';
		$this->doc->form='<form action="'.htmlspecialchars('index.php?id='.$this->id).'" method="post" autocomplete="off">';


		if ($access)    {
				// Draw the header.

				// Add custom styles
			$this->doc->styleSheetFile2 = t3lib_extMgm::extRelPath($this->extKey)."mod2/styles.css";

				// Adding classic jumpToUrl function, needed for the function menu.
				// Also, the id in the parent frameset is configured.
			$this->doc->JScode=$this->doc->wrapScriptTags('
				function jumpToUrl(URL)	{ //
					document.location = URL;
					return false;
				}
				function setHighlight(id)	{	//
					if (top.fsMod) {
						top.fsMod.recentIds["web"]=id;
						top.fsMod.navFrameHighlightedID["web"]="pages"+id+"_"+top.fsMod.currentBank;	// For highlighting

						if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav)	{
							top.content.nav_frame.refresh_nav();
						}
					}
				}
			');

			if(tx_templavoila_div::convertVersionNumberToInteger(TYPO3_version) < 4005000) {
				$this->doc->getDynTabMenuJScode();
			} else {
				$this->doc->loadJavascriptLib('js/tabmenu.js');
			}


			$this->renderModuleContent();

				// Setting up support for context menus (when clicking the items icon)
			$CMparts = $this->doc->getContextMenuCode();
			$this->doc->bodyTagAdditions = $CMparts[1];
			$this->doc->JScode.= $CMparts[0];
			$this->doc->postCode.= $CMparts[2];

		} else {
			$flashMessage = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('noaccess'),
				'',
				t3lib_FlashMessage::ERROR
			);
			$this->content = $flashMessage->render();
		}
			// Place content inside template
		$content  = $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
		$content .= $this->doc->moduleBody(
			$this->pageinfo,
			$this->getDocHeaderButtons(),
			array('CONTENT' => $this->content)
		);
		$content .= $this->doc->endPage();

			// Replace content with templated content
		$this->content = $content;
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent()    {
		echo $this->content;
	}


	/**
	 * Gets the buttons that shall be rendered in the docHeader.
	 *
	 * @return	array		Available buttons for the docHeader
	 */
	protected function getDocHeaderButtons() {
		$buttons = array(
			'csh'      => t3lib_BEfunc::cshItem('_MOD_web_txtemplavoilaM2', '', $this->backPath),
			'shortcut' => $this->getShortcutButton(),
		);
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
			$result = $this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
		}

		return $result;
	}







	/******************************
	 *
	 * Rendering module content:
	 *
	 *******************************/

	/**
	 * Renders module content:
	 *
	 * @return	void
	 */
	function renderModuleContent()	{

		if ($this->MOD_SETTINGS['wiz_step'])	{	// Run wizard instead of showing overview.
			$this->renderNewSiteWizard_run();
		} else {

				// Select all Data Structures in the PID and put into an array:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'count(*)',
						'tx_templavoila_datastructure',
						'pid='.intval($this->id).t3lib_BEfunc::deleteClause('tx_templavoila_datastructure')
					);
			list($countDS) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
			$GLOBALS['TYPO3_DB']->sql_free_result($res);

				// Select all Template Records in PID:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'count(*)',
						'tx_templavoila_tmplobj',
						'pid='.intval($this->id).t3lib_BEfunc::deleteClause('tx_templavoila_tmplobj')
					);
			list($countTO) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
			$GLOBALS['TYPO3_DB']->sql_free_result($res);

				// If there are TO/DS, render the module as usual, otherwise do something else...:
			if ($countTO || $countDS)	{
				$this->renderModuleContent_mainView();
			} else {
				$this->renderModuleContent_searchForTODS();
				$this->renderNewSiteWizard_overview();
			}
		}
	}

	/**
	 * Renders module content, overview of pages with DS/TO on.
	 *
	 * @return	void
	 */
	function renderModuleContent_searchForTODS()	{
		global $LANG;

		$dsRepo = t3lib_div::makeInstance('tx_templavoila_datastructureRepository');
		$toRepo = t3lib_div::makeInstance('tx_templavoila_templateRepository');
		$list = $toRepo->getTemplateStoragePids();

			// Traverse the pages found and list in a table:
		$tRows = array();
		$tRows[] = '
			<tr class="bgColor5 tableheader">
				<td>' . $LANG->getLL('storagefolders', 1) . '</td>
				<td>' . $LANG->getLL('datastructures', 1) . '</td>
				<td>' . $LANG->getLL('templateobjects', 1) . '</td>
			</tr>';

		if (is_array($list))	{
			foreach($list as $pid) {
				$path = $this->findRecordsWhereUsed_pid($pid);
				if ($path)	{
					$tRows[] = '
						<tr class="bgColor4">
							<td><a href="index.php?id=' . $pid . '" onclick="setHighlight(' . $pid . ')">' .
							t3lib_iconWorks::getSpriteIconForRecord('pages', t3lib_BEfunc::getRecord('pages', $pid)).
							htmlspecialchars($path).'</a></td>
							<td>' . $dsRepo->getDatastructureCountForPid($pid) . '</td>
							<td>' . $toRepo->getTemplateCountForPid($pid) . '</td>
						</tr>';
				}
			}

				// Create overview
			$outputString = $LANG->getLL('description_pagesWithCertainDsTo');
			$outputString .= '<br /><table border="0" cellpadding="1" cellspacing="1" class="typo3-dblist">'.implode('',$tRows).'</table>';

				// Add output:
			$this->content.= $this->doc->section($LANG->getLL('title'),$outputString,0,1);
		}
	}

	/**
	 * Renders module content main view:
	 *
	 * @return	void
	 */
	function renderModuleContent_mainView()	{
		global $LANG;

			// Traverse scopes of data structures display template records belonging to them:
			// Each scope is places in its own tab in the tab menu:
		$dsScopes = array(
			tx_templavoila_datastructure::SCOPE_PAGE,
			tx_templavoila_datastructure::SCOPE_FCE,
			tx_templavoila_datastructure::SCOPE_UNKNOWN
		);

		$toIdArray = $parts = array();
		foreach($dsScopes as $scopePointer)	{

				// Create listing for a DS:
			list($content,$dsCount,$toCount,$toIdArrayTmp) = $this->renderDSlisting($scopePointer);
			$toIdArray = array_merge($toIdArrayTmp, $toIdArray);
			$scopeIcon = '';

				// Label for the tab:
			switch((string)$scopePointer)	{
				case tx_templavoila_datastructure::SCOPE_PAGE:
					$label = $LANG->getLL('pagetemplates');
					$scopeIcon = t3lib_iconWorks::getSpriteIconForRecord('pages', array());
				break;
				case tx_templavoila_datastructure::SCOPE_FCE:
					$label = $LANG->getLL('fces');
					$scopeIcon = t3lib_iconWorks::getSpriteIconForRecord('tt_content', array());
				break;
				case tx_templavoila_datastructure::SCOPE_UNKNOWN:
					$label = $LANG->getLL('other');
				break;
				default:
					$label = sprintf($LANG->getLL('unknown'), $scopePointer);
				break;
			}

				// Error/Warning log:
			$errStat = $this->getErrorLog($scopePointer);

				// Add parts for Tab menu:
			$parts[] = array(
				'label' => $label,
				'icon' => $scopeIcon,
				'content' => $content,
				'linkTitle' => 'DS/TO = '.$dsCount.'/'.$toCount,
				'stateIcon' => $errStat['iconCode']
			);
		}

			// Find lost Template Objects and add them to a TAB if any are found:
		$lostTOs = '';
		$lostTOCount = 0;

		$toRepo = t3lib_div::makeInstance('tx_templavoila_templateRepository');
		$toList = $toRepo->getAll($this->id);
		foreach($toList as $toObj)	{
			if(!in_array($toObj->getKey(), $toIdArray)) {
				$rTODres = $this->renderTODisplay($toObj, -1, 1);
				$lostTOs.= $rTODres['HTML'];
				$lostTOCount++;
			}
		}
		if ($lostTOs) {
				// Add parts for Tab menu:
			$parts[] = array(
				'label' => sprintf($LANG->getLL('losttos', 1), $lostTOCount),
				'content' => $lostTOs
			);
		}

			// Complete Template File List
		$parts[] = array(
			'label' => $LANG->getLL('templatefiles', 1),
			'content' => $this->completeTemplateFileList()
		);

			// Errors:
		if (false !== ($errStat = $this->getErrorLog('_ALL')))	{
			$parts[] = array(
				'label' => 'Errors ('.$errStat['count'].')',
				'content' => $errStat['content'],
				'stateIcon' => $errStat['iconCode']
			);
		}

			// Create setting handlers:
		$settings = '<p>'.
				t3lib_BEfunc::getFuncCheck('','SET[set_details]',$this->MOD_SETTINGS['set_details'],'',t3lib_div::implodeArrayForUrl('',$_GET,'',1,1)).' Show Details &nbsp;&nbsp;&nbsp;'.
				t3lib_BEfunc::getFuncCheck('','SET[set_unusedDs]',$this->MOD_SETTINGS['set_unusedDs'],'',t3lib_div::implodeArrayForUrl('',$_GET,'',1,1)).' Show unused datastructures &nbsp;&nbsp;&nbsp;'.
			'</p>';

			// Add output:
		$this->content.=$this->doc->section($LANG->getLL('title'),
			$settings.
			$this->doc->getDynTabMenu($parts,'TEMPLAVOILA:templateOverviewModule:'.$this->id, 0,0,300)
		,0,1);
	}

	/**
	 * Renders Data Structures from $dsScopeArray
	 *
	 * @param	[type]		$scope: ...
	 * @return	array		Returns array with three elements: 0: content, 1: number of DS shown, 2: number of root-level template objects shown.
	 */
	function renderDSlisting($scope)	{

		$currentPid = intval(t3lib_div::_GP('id'));
		$dsRepo = t3lib_div::makeInstance('tx_templavoila_datastructureRepository');
		$toRepo = t3lib_div::makeInstance('tx_templavoila_templateRepository');

		if ($this->MOD_SETTINGS['set_unusedDs']) {
			$dsList = $dsRepo->getDatastructuresByScope($scope);
		} else {
			$dsList = $dsRepo->getDatastructuresByStoragePidAndScope($currentPid, $scope);
		}

		$dsCount=0;
		$toCount=0;
		$content='';
		$index='';
		$toIdArray = array(-1);

			// Traverse data structures to list:
		if (count($dsList))	{
			foreach($dsList as $dsObj)	{

					// Traverse template objects which are not children of anything:
				$TOcontent = '';
				$indexTO = '';

				$toList = $toRepo->getTemplatesByDatastructure($dsObj, $currentPid);

				$newPid = intval(t3lib_div::_GP('id'));
				$newFileRef = '';
				$newTitle = $dsObj->getLabel() . ' [TEMPLATE]';
				if (count($toList))	{
					foreach($toList as $toObj)	{
						$toIdArray[] = $toObj->getKey();
						if ($toObj->hasParentTemplate()) {
							continue;
						}
						$rTODres = $this->renderTODisplay($toObj, $scope);
						$TOcontent.= '<a name="to-' . $toObj->getKey() . '"></a>'.$rTODres['HTML'];
						$indexTO.='
							<tr class="bgColor4">
								<td>&nbsp;&nbsp;&nbsp;</td>
								<td><a href="#to-' . $toObj->getKey() . '">' . htmlspecialchars($toObj->getLabel()) . $toObj->hasParentTemplate() . '</a></td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td align="center">'.$rTODres['mappingStatus'].'</td>
								<td align="center">'.$rTODres['usage'].'</td>
							</tr>';
						$toCount++;

						$newPid=-$toObj->getKey();
						$newFileRef = $toObj->getFileref();
						$newTitle = $toObj->getLabel() . ' [ALT]';
					}
				}
					// New-TO link:
				$TOcontent.= '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick(
							'&edit[tx_templavoila_tmplobj]['.$newPid.']=new'.
							'&defVals[tx_templavoila_tmplobj][datastructure]='.rawurlencode($dsObj->getKey()).
							'&defVals[tx_templavoila_tmplobj][title]='.rawurlencode($newTitle).
							'&defVals[tx_templavoila_tmplobj][fileref]='.rawurlencode($newFileRef)
							,$this->doc->backPath)).'">' . t3lib_iconWorks::getSpriteIcon('actions-document-new') . $GLOBALS['LANG']->getLL('createnewto', 1) . '</a>';

					// Render data structure display
				$rDSDres = $this->renderDataStructureDisplay($dsObj, $scope, $toIdArray);
				$content.= '<a name="ds-' . md5($dsObj->getKey()) . '"></a>' . $rDSDres['HTML'];
				$index.='
					<tr class="bgColor4-20">
						<td colspan="2"><a href="#ds-'.md5($dsObj->getKey()).'">'.htmlspecialchars($dsObj->getLabel()).'</a></td>
						<td align="center">'.$rDSDres['languageMode'].'</td>
						<td>'.$rDSDres['container'].'</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
					</tr>';
				if ($indexTO)	{
					$index.=$indexTO;
				}
				$dsCount++;

					// Wrap TO elements in a div-tag and add to content:
				if ($TOcontent)	{
					$content.='<div style="margin-left: 102px;">'.$TOcontent.'</div>';
				}
			}
		}

		if ($index)	{
			$content = '<h4>' . $GLOBALS['LANG']->getLL('overview', 1) . '</h4>
						<table border="0" cellpadding="0" cellspacing="1">
							<tr class="bgColor5 tableheader">
								<td colspan="2">' . $GLOBALS['LANG']->getLL('dstotitle', 1) . '</td>
								<td>' . $GLOBALS['LANG']->getLL('localization', 1) . '</td>
								<td>' . $GLOBALS['LANG']->getLL('containerstatus', 1) . '</td>
								<td>' . $GLOBALS['LANG']->getLL('mappingstatus', 1) . '</td>
								<td>' . $GLOBALS['LANG']->getLL('usagecount', 1) . '</td>
							</tr>
						'.$index.'
						</table>'.
						$content;
		}

		return array($content,$dsCount,$toCount,$toIdArray);
	}

	/**
	 * Rendering a single data structures information
	 *
	 * @param	array		Data Structure information
	 * @param	array		Array with TO found for this ds
	 * @param	integer		Scope.
	 * @return	string		HTML content
	 */
	function renderDataStructureDisplay(tx_templavoila_datastructure $dsObj, $scope, $toIdArray)	{

		$tableAttribs = ' border="0" cellpadding="1" cellspacing="1" width="98%" style="margin-top: 10px;" class="lrPadding"';

		$XMLinfo = array();
		if ($this->MOD_SETTINGS['set_details'])	{
			$XMLinfo = $this->DSdetails($dsObj->getDataprotXML());
		}

		if ($dsObj->isFilebased()) {
			$onClick = 'document.location=\'' . $this->doc->backPath . 'file_edit.php?target=' . rawurlencode(PATH_site . $dsObj->getKey()) . '&returnUrl=' . rawurlencode(t3lib_div::sanitizeLocalUrl(t3lib_div::getIndpEnv('REQUEST_URI'))) . '\';';
			$dsIcon = '<a href="#" onclick="' . htmlspecialchars($onClick) . '"><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/fileicons/xml.gif','width="18" height="16"').' alt="" title="' . $dsObj->getKey() . '" class="absmiddle" /></a>';
		} else {
			$dsIcon = t3lib_iconWorks::getSpriteIconForRecord('tx_templavoila_datastructure' ,array(), array('title' => $dsObj->getKey()));
			$dsIcon = $this->doc->wrapClickMenuOnIcon($dsIcon, 'tx_templavoila_datastructure', $dsObj->getKey(), 1, '&callingScriptId='.rawurlencode($this->doc->scriptID));
		}

			// Preview icon:
		if ($dsObj->getIcon())	{
			if (isset($this->modTSconfig['properties']['dsPreviewIconThumb']) && $this->modTSconfig['properties']['dsPreviewIconThumb'] != '0') {
				$path = realpath(dirname(__FILE__) . '/' . preg_replace('/\w+\/\.\.\//', '', $GLOBALS['BACK_PATH'] . $dsObj->getIcon()));
				$path = str_replace(realpath(PATH_site) . '/', PATH_site, $path);
				if($path == FALSE) {
					$previewIcon = $GLOBALS['LANG']->getLL('noicon', 1);
				} else {
					$previewIcon = t3lib_BEfunc::getThumbNail($this->doc->backPath . 'thumbs.php', $path,
						'hspace="5" vspace="5" border="1"',
						strpos($this->modTSconfig['properties']['dsPreviewIconThumb'], 'x') ? $this->modTSconfig['properties']['dsPreviewIconThumb'] : '');
				}
			} else {
				$previewIcon = '<img src="' . $this->doc->backPath . $dsObj->getIcon() . '" alt="" />';
			}
		} else {
			$previewIcon = $GLOBALS['LANG']->getLL('noicon', 1);
		}

			// Links:
		if ($dsObj->isFilebased()) {
			$editLink = $editDataprotLink = '';
			$dsTitle = $dsObj->getLabel();
		} else {
			$editLink = $lpXML.= '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[tx_templavoila_datastructure]['.$dsObj->getKey().']=edit',$this->doc->backPath)).'">' . t3lib_iconWorks::getSpriteIcon('actions-document-open') .'</a>';
			$editDataprotLink =  '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[tx_templavoila_datastructure]['.$dsObj->getKey().']=edit&columnsOnly=dataprot',$this->doc->backPath)).'">' . t3lib_iconWorks::getSpriteIcon('actions-document-open') . '</a>';
			$dsTitle = '<a href="'.htmlspecialchars('../cm1/index.php?table=tx_templavoila_datastructure&uid=' . $dsObj->getKey() . '&id=' . $this->id . '&returnUrl=' . rawurlencode( t3lib_div::sanitizeLocalUrl(t3lib_div::getIndpEnv('REQUEST_URI')))) . '">' . htmlspecialchars($dsObj->getLabel()) . '</a>';
		}

			// Compile info table:
		$content.='
		<table'.$tableAttribs.'>
			<tr class="bgColor5">
				<td colspan="3" style="border-top: 1px solid black;">'.
					$dsIcon.
					$dsTitle.
					$editLink.
					'</td>
			</tr>
			<tr class="bgColor4">
				<td rowspan="'.($this->MOD_SETTINGS['set_details'] ? 4 : 2).'" style="width: 100px; text-align: center;">'.$previewIcon.'</td>
				' .
				($this->MOD_SETTINGS['set_details'] ? '<td style="width:200px">' . $GLOBALS['LANG']->getLL('templatestatus', 1) . '</td>
				<td>' . $this->findDSUsageWithImproperTOs($dsObj, $scope, $toIdArray) . '</td>' : '' ) .
			'</tr>
			<tr class="bgColor4">
				<td>' . $GLOBALS['LANG']->getLL('globalprocessing_xml') . '</td>
				<td>
					'.$lpXML.($dsObj->getDataprotXML() ?
					t3lib_div::formatSize(strlen($dsObj->getDataprotXML())).' bytes'.
					($this->MOD_SETTINGS['set_details'] ? '<hr/>'.$XMLinfo['HTML'] : '') : '').'
				</td>
			</tr>'.($this->MOD_SETTINGS['set_details'] ? '
			<tr class="bgColor4">
				<td>' . $GLOBALS['LANG']->getLL('created', 1) . '</td>
				<td>' . t3lib_BEfunc::datetime($dsObj->getCrdate()) . ' ' . $GLOBALS['LANG']->getLL('byuser', 1) . ' [' . $dsObj->getCruser() . ']</td>
			</tr>
			<tr class="bgColor4">
				<td>' . $GLOBALS['LANG']->getLL('updated', 1) . '</td>
				<td>'.t3lib_BEfunc::datetime($dsObj->getTstamp()).'</td>
			</tr>' : '').'
		</table>
		';

			// Format XML if requested (renders VERY VERY slow)
		if ($this->MOD_SETTINGS['set_showDSxml'])	{
			if ($dsObj->getDataprotXML())	{
				require_once(PATH_t3lib.'class.t3lib_syntaxhl.php');
				$hlObj = t3lib_div::makeInstance('t3lib_syntaxhl');
				$content.='<pre>'.str_replace(chr(9),'&nbsp;&nbsp;&nbsp;',$hlObj->highLight_DS($dsObj->getDataprotXML())).'</pre>';
			}
			$lpXML.= $editDataprotLink;
		}

		if ($this->MOD_SETTINGS['set_details'])	{
			if ($XMLinfo['referenceFields'])	{
				$containerMode = $GLOBALS['LANG']->getLL('yes', 1);
				if ($XMLinfo['languageMode']==='Separate')	{
					$containerMode .= ' ' . $this->doc->icons(3) . $GLOBALS['LANG']->getLL('containerwithseparatelocalization', 1);
				} elseif ($XMLinfo['languageMode']==='Inheritance')	{
					$containerMode .= ' '.$this->doc->icons(2);
					if ($XMLinfo['inputFields'])	{
						$containerMode .= $GLOBALS['LANG']->getLL('mixofcontentandref', 1);
					} else {
						$containerMode .= $GLOBALS['LANG']->getLL('nocontentfields', 1);
					}
				}
			} else {
				$containerMode = 'No';
			}

			$containerMode.=' (ARI='.$XMLinfo['rootelements'].'/'.$XMLinfo['referenceFields'].'/'.$XMLinfo['inputFields'].')';
		}

			// Return content
		return array(
			'HTML' => $content,
			'languageMode' => $XMLinfo['languageMode'],
			'container' => $containerMode
		);
	}

	/**
	 * Render display of a Template Object
	 *
	 * @param	array		Template Object record to render
	 * @param	array		Array of all Template Objects (passed by reference. From here records are unset)
	 * @param	integer		Scope of DS
	 * @param	boolean		If set, the function is asked to render children to template objects (and should not call it self recursively again).
	 * @return	string		HTML content
	 */
	function renderTODisplay($toObj, $scope, $children=0)	{

			// Put together the records icon including content sensitive menu link wrapped around it:
		$recordIcon = t3lib_iconWorks::getSpriteIconForRecord('tx_templavoila_tmplobj', array(), array('title' => $toObj->getKey()));
		$recordIcon = $this->doc->wrapClickMenuOnIcon($recordIcon, 'tx_templavoila_tmplobj', $toObj->getKey(), 1, '&callingScriptId='.rawurlencode($this->doc->scriptID));

			// Preview icon:
		if ($toObj->getIcon())	{
			if (isset($this->modTSconfig['properties']['toPreviewIconThumb']) && $this->modTSconfig['properties']['toPreviewIconThumb'] != '0') {
					$path = realpath(dirname(__FILE__) . '/' . preg_replace('/\w+\/\.\.\//', '', $GLOBALS['BACK_PATH'] . $toObj->getIcon()));
					$path = str_replace(realpath(PATH_site) . '/', PATH_site, $path);
					if($path == FALSE) {
						$icon = $GLOBALS['LANG']->getLL('noicon', 1);
					} else {
						$icon = t3lib_BEfunc::getThumbNail($this->doc->backPath . 'thumbs.php', $path,
							'hspace="5" vspace="5" border="1"',
							strpos($this->modTSconfig['properties']['toPreviewIconThumb'], 'x') ? $this->modTSconfig['properties']['toPreviewIconThumb'] : '');
					}
				} else {
					$icon = '<img src="' . $this->doc->backPath . $toObj->getIcon() . '" alt="" />';
				}
		} else {
			$icon = $GLOBALS['LANG']->getLL('noicon', 1);
		}

			// Mapping status / link:
		$linkUrl = '../cm1/index.php?table=tx_templavoila_tmplobj&uid=' . $toObj->getKey() . '&_reload_from=1&id=' . $this->id . '&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));

		$fileReference = t3lib_div::getFileAbsFileName($toObj->getFileref());
		if (@is_file($fileReference))	{
			$this->tFileList[$fileReference]++;
			$fileRef = '<a href="'.htmlspecialchars($this->doc->backPath.'../'.substr($fileReference,strlen(PATH_site))).'" target="_blank">'.htmlspecialchars($toObj->getFileref()).'</a>';
			$fileMsg = '';
			$fileMtime = filemtime($fileReference);
		} else {
			$fileRef = htmlspecialchars($toObj->getFileref());
			$fileMsg = '<div class="typo3-red">ERROR: File not found</div>';
			$fileMtime = 0;
		}

		$mappingStatus = $mappingStatus_index = '';
		if ($fileMtime && $toObj->getFilerefMtime()) {
			if ($toObj->getFilerefMD5() != '') {
				$modified = (@md5_file($fileReference) != $toObj->getFilerefMD5());
			} else {
				$modified = ($toObj->getFilerefMtime() != $fileMtime);
			}
			if ($modified)	{
				$mappingStatus = $mappingStatus_index = t3lib_iconWorks::getSpriteIcon('status-dialog-warning');
				$mappingStatus.= sprintf($GLOBALS['LANG']->getLL('towasupdated', 1), t3lib_BEfunc::datetime($toObj->getTstamp()));
				$this->setErrorLog($scope, 'warning', sprintf($GLOBALS['LANG']->getLL('warning_mappingstatus', 1), $mappingStatus, $toObj->getLabel()));
			} else {
				$mappingStatus = $mappingStatus_index = t3lib_iconWorks::getSpriteIcon('status-dialog-ok');
				$mappingStatus.= $GLOBALS['LANG']->getLL('mapping_uptodate', 1);
			}
			$mappingStatus .= '<br/><input type="button" onclick="jumpToUrl(\'' . htmlspecialchars($linkUrl) . '\');" value="' . $GLOBALS['LANG']->getLL('update_mapping', 1) . '" />';
		} elseif (!$fileMtime) {
			$mappingStatus = $mappingStatus_index = t3lib_iconWorks::getSpriteIcon('status-dialog-error');
			$mappingStatus.= $GLOBALS['LANG']->getLL('notmapped', 1);
			$this->setErrorLog($scope, 'fatal', sprintf($GLOBALS['LANG']->getLL('warning_mappingstatus', 1), $mappingStatus, $toObj->getLabel()));

			$mappingStatus .= $GLOBALS['LANG']->getLL('updatemapping_info');
			$mappingStatus .= '<br/><input type="button" onclick="jumpToUrl(\'' . htmlspecialchars($linkUrl) . '\');" value="' . $GLOBALS['LANG']->getLL('map', 1) . '" />';
		} else {
			$mappingStatus = '';
			$mappingStatus .= '<input type="button" onclick="jumpToUrl(\'' . htmlspecialchars($linkUrl) . '\');" value="' . $GLOBALS['LANG']->getLL('remap', 1) . '" />';
			$mappingStatus .= '&nbsp;<input type="button" onclick="jumpToUrl(\'' . htmlspecialchars($linkUrl . '&_preview=1') . '\');" value="' . $GLOBALS['LANG']->getLL('preview', 1) . '" />';
		}

		if ($this->MOD_SETTINGS['set_details'])	{
			$XMLinfo = $this->DSdetails($toObj->getLocalDataprotXML(TRUE));
		}

			// Format XML if requested
		if ($this->MOD_SETTINGS['set_details'])	{
			if ($toObj->getLocalDataprotXML(TRUE))	{
				require_once(PATH_t3lib.'class.t3lib_syntaxhl.php');
				$hlObj = t3lib_div::makeInstance('t3lib_syntaxhl');
				$lpXML = '<pre>'.str_replace(chr(9),'&nbsp;&nbsp;&nbsp;',$hlObj->highLight_DS($toObj->getLocalDataprotXML(TRUE))).'</pre>';
			} else $lpXML = '';
		}
		$lpXML.= '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[tx_templavoila_tmplobj]['.$toObj->getKey().']=edit&columnsOnly=localprocessing',$this->doc->backPath)).'">' . t3lib_iconWorks::getSpriteIcon('actions-document-open') . '</a>';

			// Compile info table:
		$tableAttribs = ' border="0" cellpadding="1" cellspacing="1" width="98%" style="margin-top: 3px;" class="lrPadding"';

			// Links:
		$toTitle = '<a href="' . htmlspecialchars($linkUrl) . '">' . htmlspecialchars($GLOBALS['LANG']->sL($toObj->getLabel())) . '</a>';
		$editLink = '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[tx_templavoila_tmplobj]['.$toObj->getKey().']=edit',$this->doc->backPath)).'">' . t3lib_iconWorks::getSpriteIcon('actions-document-open') . '</a>';

		$fRWTOUres = array();

		if (!$children)	{
			if ($this->MOD_SETTINGS['set_details'])	{
				$fRWTOUres = $this->findRecordsWhereTOUsed($toObj,$scope);
			}

			$content.='
			<table'.$tableAttribs.'>
				<tr class="bgColor4-20">
					<td colspan="3">'.
						$recordIcon.
						$toTitle.
						$editLink.
						'</td>
				</tr>
				<tr class="bgColor4">
					<td rowspan="'.($this->MOD_SETTINGS['set_details'] ? 7 : 4).'" style="width: 100px; text-align: center;">'.$icon.'</td>
					<td style="width:200px;">' . $GLOBALS['LANG']->getLL('filereference', 1) . ':</td>
					<td>'.$fileRef.$fileMsg.'</td>
				</tr>
				<tr class="bgColor4">
					<td>' . $GLOBALS['LANG']->getLL('description', 1) . ':</td>
					<td>'.htmlspecialchars($toObj->getDescription()).'</td>
				</tr>
				<tr class="bgColor4">
					<td>' . $GLOBALS['LANG']->getLL('mappingstatus', 1) . ':</td>
					<td>'.$mappingStatus.'</td>
				</tr>
				<tr class="bgColor4">
					<td>' . $GLOBALS['LANG']->getLL('localprocessing_xml') . ':</td>
					<td>
						'.$lpXML.($toObj->getLocalDataprotXML(TRUE) ?
						t3lib_div::formatSize(strlen($toObj->getLocalDataprotXML(TRUE))).' bytes'.
						($this->MOD_SETTINGS['set_details'] ? '<hr/>'.$XMLinfo['HTML'] : '') : '').'
					</td>
				</tr>'.($this->MOD_SETTINGS['set_details'] ? '
				<tr class="bgColor4">
					<td>' . $GLOBALS['LANG']->getLL('usedby', 1) . ':</td>
					<td>'.$fRWTOUres['HTML'].'</td>
				</tr>
				<tr class="bgColor4">
					<td>' . $GLOBALS['LANG']->getLL('created', 1) . ':</td>
					<td>' . t3lib_BEfunc::datetime($toObj->getCrdate()) . ' ' . $GLOBALS['LANG']->getLL('byuser', 1) . ' [' . $toObj->getCruser() . ']</td>
				</tr>
				<tr class="bgColor4">
					<td>' . $GLOBALS['LANG']->getLL('updated', 1) . ':</td>
					<td>'.t3lib_BEfunc::datetime($toObj->getTstamp()).'</td>
				</tr>' : '').'
			</table>
			';
		} else {
			$content.='
			<table'.$tableAttribs.'>
				<tr class="bgColor4-20">
					<td colspan="3">'.
						$recordIcon.
						$toTitle.
						$editLink.
						'</td>
				</tr>
				<tr class="bgColor4">
					<td style="width:200px;">' . $GLOBALS['LANG']->getLL('filereference', 1) . ':</td>
					<td>'.$fileRef.$fileMsg.'</td>
				</tr>
				<tr class="bgColor4">
					<td>' . $GLOBALS['LANG']->getLL('mappingstatus', 1) . ':</td>
					<td>'.$mappingStatus.'</td>
				</tr>
				<tr class="bgColor4">
					<td>' . $GLOBALS['LANG']->getLL('rendertype', 1) . ':</td>
					<td>' . $this->getProcessedValue('tx_templavoila_tmplobj', 'rendertype', $toObj->getRendertype()) . '</td>
				</tr>
				<tr class="bgColor4">
					<td>' . $GLOBALS['LANG']->getLL('language', 1) . ':</td>
					<td>' . $this->getProcessedValue('tx_templavoila_tmplobj', 'sys_language_uid', $toObj->getSyslang()) . '</td>
				</tr>
				<tr class="bgColor4">
					<td>' . $GLOBALS['LANG']->getLL('localprocessing_xml') . ':</td>
					<td>
						'.$lpXML.($toObj->getLocalDataprotXML(TRUE) ?
						t3lib_div::formatSize(strlen($toObj->getLocalDataprotXML(TRUE))).' bytes'.
						($this->MOD_SETTINGS['set_details'] ? '<hr/>'.$XMLinfo['HTML'] : '') : '').'
					</td>
				</tr>'.($this->MOD_SETTINGS['set_details'] ? '
				<tr class="bgColor4">
					<td>' . $GLOBALS['LANG']->getLL('created', 1) . ':</td>
					<td>'.t3lib_BEfunc::datetime($toObj->getCrdate()) . ' ' . $GLOBALS['LANG']->getLL('byuser', 1) . ' [' . $toObj->getCruser() . ']</td>
				</tr>
				<tr class="bgColor4">
					<td>' . $GLOBALS['LANG']->getLL('updated', 1) . ':</td>
					<td>'.t3lib_BEfunc::datetime($toObj->getTstamp()).'</td>
				</tr>' : '').'
			</table>
			';
		}

			// Traverse template objects which are not children of anything:
		if(!$childRen) {
			$toRepo = t3lib_div::makeInstance('tx_templavoila_templateRepository');
			$toChildren = $toRepo->getTemplatesByParentTemplate($toObj);
		} else {
			$toChildren = array();
		}

		if (!$children && count($toChildren))	{
			$TOchildrenContent = '';
			foreach($toChildren as $toChild)	{
				$rTODres = $this->renderTODisplay($toChild, $scope, 1);
				$TOchildrenContent.= $rTODres['HTML'];
			}
			$content.='<div style="margin-left: 102px;">'.$TOchildrenContent.'</div>';
		}

			// Return content
		return array('HTML' => $content, 'mappingStatus' => $mappingStatus_index, 'usage' => $fRWTOUres['usage']);
	}

	/**
	 * Creates listings of pages / content elements where template objects are used.
	 *
	 * @param	array		Template Object record
	 * @param	integer		Scope value. 1) page,  2) content elements
	 * @return	string		HTML table listing usages.
	 */
	function findRecordsWhereTOUsed($toObj,$scope)	{

		$output = array();

		switch ($scope)	{
			case 1:	// PAGES:
					// Header:
				$output[]='
							<tr class="bgColor5 tableheader">
								<td>' . $GLOBALS['LANG']->getLL('toused_pid', 1) . ':</td>
								<td>' . $GLOBALS['LANG']->getLL('toused_title', 1) . ':</td>
								<td>' . $GLOBALS['LANG']->getLL('toused_path', 1) . ':</td>
								<td>' . $GLOBALS['LANG']->getLL('toused_workspace', 1) . ':</td>
							</tr>';

					// Main templates:
				$dsKey = $toObj->getDatastructure()->getKey();
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'uid,title,pid,t3ver_wsid,t3ver_id',
					'pages',
					'(
						(tx_templavoila_to='.intval($toObj->getKey()).' AND tx_templavoila_ds='.$GLOBALS['TYPO3_DB']->fullQuoteStr($dsKey,'pages').') OR
						(tx_templavoila_next_to='.intval($toObj->getKey()).' AND tx_templavoila_next_ds='.$GLOBALS['TYPO3_DB']->fullQuoteStr($dsKey,'pages').')
					)'.
						t3lib_BEfunc::deleteClause('pages')
				);

				while(false !== ($pRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)))	{
					$path = $this->findRecordsWhereUsed_pid($pRow['uid']);
					if ($path)	{
						$output[]='
							<tr class="bgColor4-20">
								<td nowrap="nowrap">'.
									'<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[pages]['.$pRow['uid'].']=edit',$this->doc->backPath)).'" title="Edit">'.
									htmlspecialchars($pRow['uid']).
									'</a></td>
								<td nowrap="nowrap">'.
									htmlspecialchars($pRow['title']).
									'</td>
								<td nowrap="nowrap">'.
									'<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($pRow['uid'],$this->doc->backPath).'return false;').'" title="View">'.
									htmlspecialchars($path).
									'</a></td>
								<td nowrap="nowrap">'.
									htmlspecialchars($pRow['pid']==-1 ? 'Offline version 1.'.$pRow['t3ver_id'].', WS: '.$pRow['t3ver_wsid'] : 'LIVE!').
									'</td>
							</tr>';
					} else {
						$output[]='
							<tr class="bgColor4-20">
								<td nowrap="nowrap">'.
									htmlspecialchars($pRow['uid']).
									'</td>
								<td><em>' . $GLOBALS['LANG']->getLL('noaccess', 1) . '</em></td>
								<td>-</td>
								<td>-</td>
							</tr>';
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			break;
			case 2:

					// Select Flexible Content Elements:
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'uid,header,pid,t3ver_wsid,t3ver_id',
					'tt_content',
					'CType='.$GLOBALS['TYPO3_DB']->fullQuoteStr('templavoila_pi1','tt_content').
						' AND tx_templavoila_to='.intval($toObj->getKey()).
						' AND tx_templavoila_ds='.$GLOBALS['TYPO3_DB']->fullQuoteStr($toObj->getDatastructure()->getKey(),'tt_content').
						t3lib_BEfunc::deleteClause('tt_content'),
					'',
					'pid'
				);

					// Header:
				$output[]='
							<tr class="bgColor5 tableheader">
								<td>' . $GLOBALS['LANG']->getLL('toused_uid', 1) . ':</td>
								<td>' . $GLOBALS['LANG']->getLL('toused_header', 1) . ':</td>
								<td>' . $GLOBALS['LANG']->getLL('toused_path', 1) . ':</td>
								<td>' . $GLOBALS['LANG']->getLL('toused_workspace', 1) . ':</td>
							</tr>';

					// Elements:
				while(false !== ($pRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)))	{
					$path = $this->findRecordsWhereUsed_pid($pRow['pid']);
					if ($path)	{
						$output[]='
							<tr class="bgColor4-20">
								<td nowrap="nowrap">'.
									'<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[tt_content]['.$pRow['uid'].']=edit',$this->doc->backPath)).'" title="Edit">'.
									htmlspecialchars($pRow['uid']).
									'</a></td>
								<td nowrap="nowrap">'.
									htmlspecialchars($pRow['header']).
									'</td>
								<td nowrap="nowrap">'.
									'<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($pRow['pid'],$this->doc->backPath).'return false;').'" title="View page">'.
									htmlspecialchars($path).
									'</a></td>
								<td nowrap="nowrap">'.
									htmlspecialchars($pRow['pid']==-1 ? 'Offline version 1.'.$pRow['t3ver_id'].', WS: '.$pRow['t3ver_wsid'] : 'LIVE!').
									'</td>
							</tr>';
					} else {
						$output[]='
							<tr class="bgColor4-20">
								<td nowrap="nowrap">'.
									htmlspecialchars($pRow['uid']).
									'</td>
								<td><em>' . $GLOBALS['LANG']->getLL('noaccess', 1) . '</em></td>
								<td>-</td>
								<td>-</td>
							</tr>';
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			break;
		}

			// Create final output table:
		if (count($output))	{
			if (count($output)>1)	{
				$outputString = sprintf($GLOBALS['LANG']->getLL('toused_usedin', 1), count($output)-1) . '
					<table border="0" cellspacing="1" cellpadding="1" class="lrPadding">'
					. implode('', $output) . '
				</table>';
			} else {
				$outputString = t3lib_iconWorks::getSpriteIcon('status-dialog-warning') . 'No usage!';
				$this->setErrorLog($scope, 'warning', sprintf($GLOBALS['LANG']->getLL('warning_mappingstatus', 1), $outputString , $toObj->getLabel()));
			}
		}

		return array('HTML' => $outputString, 'usage'=>count($output)-1);
	}

	/**
	 * Creates listings of pages / content elements where NO or WRONG template objects are used.
	 *
	 * @param	array		Data Structure ID
	 * @param	integer		Scope value. 1) page,  2) content elements
	 * @param	array		Array with numerical toIDs. Must be integers and never be empty. You can always put in "-1" as dummy element.
	 * @return	string		HTML table listing usages.
	 */
	function findDSUsageWithImproperTOs($dsObj, $scope, $toIdArray)	{

		$output = array();

		switch ($scope)	{
			case 1:	//
					// Header:
				$output[]='
							<tr class="bgColor5 tableheader">
								<td>' . $GLOBALS['LANG']->getLL('toused_title', 1) . ':</td>
								<td>' . $GLOBALS['LANG']->getLL('toused_path', 1) . ':</td>
							</tr>';

					// Main templates:
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'uid,title,pid',
					'pages',
					'(
						(tx_templavoila_to NOT IN ('.implode(',',$toIdArray).') AND tx_templavoila_ds='.$GLOBALS['TYPO3_DB']->fullQuoteStr($dsObj->getKey(),'pages').') OR
						(tx_templavoila_next_to NOT IN ('.implode(',',$toIdArray).') AND tx_templavoila_next_ds='.$GLOBALS['TYPO3_DB']->fullQuoteStr($dsObj->getKey(),'pages').')
					)'.
						t3lib_BEfunc::deleteClause('pages')
				);

				while(false !== ($pRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)))	{
					$path = $this->findRecordsWhereUsed_pid($pRow['uid']);
					if ($path)	{
						$output[]='
							<tr class="bgColor4-20">
								<td nowrap="nowrap">'.
									'<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[pages]['.$pRow['uid'].']=edit',$this->doc->backPath)).'">'.
									htmlspecialchars($pRow['title']).
									'</a></td>
								<td nowrap="nowrap">'.
									'<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($pRow['uid'],$this->doc->backPath).'return false;').'">'.
									htmlspecialchars($path).
									'</a></td>
							</tr>';
					} else {
						$output[]='
							<tr class="bgColor4-20">
								<td><em>' . $GLOBALS['LANG']->getLL('noaccess', 1) . '</em></td>
								<td>-</td>
							</tr>';
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			break;
			case 2:

					// Select Flexible Content Elements:
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'uid,header,pid',
					'tt_content',
					'CType='.$GLOBALS['TYPO3_DB']->fullQuoteStr('templavoila_pi1','tt_content').
						' AND tx_templavoila_to NOT IN ('.implode(',',$toIdArray).')'.
						' AND tx_templavoila_ds='.$GLOBALS['TYPO3_DB']->fullQuoteStr($dsObj->getKey(),'tt_content').
						t3lib_BEfunc::deleteClause('tt_content'),
					'',
					'pid'
				);

					// Header:
				$output[]='
							<tr class="bgColor5 tableheader">
								<td>' . $GLOBALS['LANG']->getLL('toused_header', 1) . ':</td>
								<td>' . $GLOBALS['LANG']->getLL('toused_path', 1) . ':</td>
							</tr>';

					// Elements:
				while(false !== ($pRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)))	{
					$path = $this->findRecordsWhereUsed_pid($pRow['pid']);
					if ($path)	{
						$output[]='
							<tr class="bgColor4-20">
								<td nowrap="nowrap">'.
									'<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[tt_content]['.$pRow['uid'].']=edit',$this->doc->backPath)).'" title="Edit">'.
									htmlspecialchars($pRow['header']).
									'</a></td>
								<td nowrap="nowrap">'.
									'<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($pRow['pid'],$this->doc->backPath).'return false;').'" title="View page">'.
									htmlspecialchars($path).
									'</a></td>
							</tr>';
					} else {
						$output[]='
							<tr class="bgColor4-20">
								<td><em>' . $GLOBALS['LANG']->getLL('noaccess', 1) . '</em></td>
								<td>-</td>
							</tr>';
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			break;
		}

			// Create final output table:
		if (count($output))	{
			if (count($output)>1)	{
				$outputString = t3lib_iconWorks::getSpriteIcon('status-dialog-error').
								sprintf($GLOBALS['LANG']->getLL('invalidtemplatevalues', 1), count($output) - 1);
				$this->setErrorLog($scope,'fatal',$outputString);

				$outputString.='<table border="0" cellspacing="1" cellpadding="1" class="lrPadding">'.implode('',$output).'</table>';
			} else {
				$outputString = t3lib_iconWorks::getSpriteIcon('status-dialog-ok') .
				$GLOBALS['LANG']->getLL('noerrorsfound', 1);
			}
		}

		return $outputString;
	}

	/**
	 * Checks if a PID value is accessible and if so returns the path for the page.
	 * Processing is cached so many calls to the function are OK.
	 *
	 * @param	integer		Page id for check
	 * @return	string		Page path of PID if accessible. otherwise zero.
	 */
	function findRecordsWhereUsed_pid($pid)	{
		if (!isset($this->pidCache[$pid]))	{
			$this->pidCache[$pid] = array();

			$pageinfo = t3lib_BEfunc::readPageAccess($pid,$this->perms_clause);
			$this->pidCache[$pid]['path'] = $pageinfo['_thePath'];
		}

		return $this->pidCache[$pid]['path'];
	}

	/**
	 * Creates a list of all template files used in TOs
	 *
	 * @return	string		HTML table
	 */
	function completeTemplateFileList()	{
	    $output = '';
		if (is_array($this->tFileList))	{
			$output='';

				// USED FILES:
			$tRows = array();
			$tRows[] = '
				<tr class="bgColor5 tableheader">
					<td>' . $GLOBALS['LANG']->getLL('file', 1) . '</td>
					<td align="center">' . $GLOBALS['LANG']->getLL('usagecount', 1) . '</td>
					<td>' . $GLOBALS['LANG']->getLL('newdsto', 1) . '</td>
				</tr>';

			$i = 0;
			foreach($this->tFileList as $tFile => $count)	{
				$tRows[] = '
					<tr class="' . ($i++ % 2 == 0 ? 'bgColor4' : 'bgColor6') . '">
						<td>'.
							'<a href="'.htmlspecialchars($this->doc->backPath.'../'.substr($tFile,strlen(PATH_site))).'" target="_blank">'.
							t3lib_iconWorks::getSpriteIcon('actions-document-view') . ' ' . htmlspecialchars(substr($tFile,strlen(PATH_site))) .
							'</a></td>
						<td align="center">'.$count.'</td>
						<td>'.
							'<a href="'.htmlspecialchars($this->cm1Link . '?id=' . $this->id . '&file=' . rawurlencode($tFile)) . '&mapElPath=%5BROOT%5D">'.
							t3lib_iconWorks::getSpriteIcon('actions-document-new') . ' ' . htmlspecialchars('Create...') .
							'</a></td>
					</tr>';
			}

			if (count($tRows)>1)	{
				$output.= '
				<h3>' . $GLOBALS['LANG']->getLL('usedfiles', 1) . ':</h3>
				<table border="0" cellpadding="1" cellspacing="1" class="typo3-dblist">
					'.implode('',$tRows).'
				</table>
				';
			}

			$files = $this->getTemplateFiles();

				// TEMPLATE ARCHIVE:
			if (count($files)) {

				$tRows = array();
				$tRows[] = '
					<tr class="bgColor5 tableheader">
						<td>' . $GLOBALS['LANG']->getLL('file', 1) . '</td>
						<td align="center">' . $GLOBALS['LANG']->getLL('usagecount', 1) . '</td>
						<td>' . $GLOBALS['LANG']->getLL('newdsto', 1) . '</td>
					</tr>';

				$i = 0;
				foreach($files as $tFile)	{
					$tRows[] = '
						<tr class="' . ($i++ % 2 == 0 ? 'bgColor4' : 'bgColor6') . '">
							<td>'.
								'<a href="'.htmlspecialchars($this->doc->backPath.'../'.substr($tFile,strlen(PATH_site))).'" target="_blank">'.
								t3lib_iconWorks::getSpriteIcon('actions-document-view') . ' ' . htmlspecialchars(substr($tFile, strlen(PATH_site))) .
								'</a></td>
							<td align="center">'.($this->tFileList[$tFile]?$this->tFileList[$tFile]:'-').'</td>
							<td>'.
								'<a href="' . htmlspecialchars($this->cm1Link . '?id=' . $this->id . '&file=' . rawurlencode($tFile)) . '&mapElPath=%5BROOT%5D">' .
								t3lib_iconWorks::getSpriteIcon('actions-document-new') . ' ' . htmlspecialchars('Create...') .
								'</a></td>
						</tr>';
				}

				if (count($tRows)>1)	{
					$output.= '
					<h3>' . $GLOBALS['LANG']->getLL('templatearchive', 1) . ':</h3>
					<table border="0" cellpadding="1" cellspacing="1" class="typo3-dblist">
						'.implode('',$tRows).'
					</table>
					';
				}
			}
		}

		return $output;
	}

	/**
	 * Get the processed value analog to t3lib_beFunc::getProcessedValue
	 * but take additional TSconfig values into account
	 *
	 * @param  $table
	 * @param  $typeField
	 * @param  $typeValue
	 * @return
	 */
	protected function getProcessedValue($table, $typeField, $typeValue) {
		$value =  t3lib_beFunc::getProcessedValue($table, $typeField, $typeValue);
		if (!$value) {
			$TSConfig = t3lib_beFunc::getPagesTSconfig($this->id);
			if (isset($TSConfig['TCEFORM.'][$table . '.'][$typeField . '.']['addItems.'][$typeValue])) {
				$value = $TSConfig['TCEFORM.'][$table . '.'][$typeField . '.']['addItems.'][$typeValue];
			}
		}

		return $value;
	}

	/**
	 * Stores errors/warnings inside the class.
	 *
	 * @param	string		Scope string, 1=page, 2=ce, _ALL= all errors
	 * @param	string		"fatal" or "warning"
	 * @param	string		HTML content for the error.
	 * @return	void
	 * @see getErrorLog()
	 */
	function setErrorLog($scope,$type,$HTML)	{
		$this->errorsWarnings['_ALL'][$type][] = $this->errorsWarnings[$scope][$type][] = $HTML;
	}

	/**
	 * Returns status for a single scope
	 *
	 * @param	string		Scope string
	 * @return	array		Array with content
	 * @see setErrorLog()
	 */
	function getErrorLog($scope)	{
	    $errStat = false;
		if (is_array($this->errorsWarnings[$scope]))	{
			$errStat = array();

			if (is_array($this->errorsWarnings[$scope]['warning']))	{
				$errStat['count'] = count($this->errorsWarnings[$scope]['warning']);
				$errStat['content'] = '<h3>' . $GLOBALS['LANG']->getLL('warnings', 1) . '</h3>'.implode('<hr/>',$this->errorsWarnings[$scope]['warning']);
				$errStat['iconCode'] = 2;
			}

			if (is_array($this->errorsWarnings[$scope]['fatal']))	{
				$errStat['count'] = count($this->errorsWarnings[$scope]['fatal']).($errStat['count'] ? '/'.$errStat['count']:'');
				$errStat['content'].= '<h3>' . $GLOBALS['LANG']->getLL('fatalerrors', 1) . '</h3>'.implode('<hr/>',$this->errorsWarnings[$scope]['fatal']);
				$errStat['iconCode'] = 3;
			}
		}
		return $errStat;
	}

	/**
	 * Shows a graphical summary of a array-tree, which suppose was a XML
	 * (but don't need to). This function works recursively.
	 *
	 * @param	[type]		$DStree: an array holding the DSs defined structure
	 * @return	[type]		HTML showing an overview of the DS-structure
	 */
	function renderDSdetails($DStree) {
		$HTML = '';

		if (is_array($DStree) && (count($DStree) > 0)) {
			$HTML .= '<dl class="DS-details">';

			foreach ($DStree as $elm => $def) {
				if (!is_array($def)) {
					$HTML .= '<p>' . t3lib_iconWorks::getSpriteIcon('status-dialog-error') . sprintf($GLOBALS['LANG']->getLL('invaliddatastructure_xmlbroken', 1), $elm) . '</p>';
					break;
				}

				$HTML .= '<dt>';
				$HTML .= ($elm == "meta" ? $GLOBALS['LANG']->getLL('configuration', 1) : $def['tx_templavoila']['title'] . ' ('. $elm . ')');
				$HTML .= '</dt>';
				$HTML .= '<dd>';

				/* this is the configuration-entry ------------------------------ */
				if ($elm == "meta") {
					/* The basic XML-structure of an meta-entry is:
					 *
					 * <meta>
					 * 	<langDisable>		-> no localization
					 * 	<langChildren>		-> no localization for children
					 * 	<sheetSelector>		-> a php-function for selecting "sDef"
					 * </meta>
					 */

					/* it would also be possible to use the 'list-style-image'-property
					 * for the flags, which would be more sensible to IE-bugs though
					 */
					$conf = '';
					if (isset($def['langDisable'])) $conf .= '<li>' .
						(($def['langDisable'] == 1)
? t3lib_iconWorks::getSpriteIcon('status-dialog-error')
: t3lib_iconWorks::getSpriteIcon('status-dialog-ok')
						) . ' ' . $GLOBALS['LANG']->getLL('fceislocalized', 1) . '</li>';
					if (isset($def['langChildren'])) $conf .= '<li>' .
						(($def['langChildren'] == 1)
? t3lib_iconWorks::getSpriteIcon('status-dialog-ok')
: t3lib_iconWorks::getSpriteIcon('status-dialog-error')
						) . ' ' . $GLOBALS['LANG']->getLL('fceinlineislocalized', 1) . '</li>';
					if (isset($def['sheetSelector'])) $conf .= '<li>' .
						(($def['sheetSelector'] != '')
? t3lib_iconWorks::getSpriteIcon('status-dialog-ok')
: t3lib_iconWorks::getSpriteIcon('status-dialog-error')
						) . ' custom sheet-selector' .
						(($def['sheetSelector'] != '')
? ' [<em>' . $def['sheetSelector'] . '</em>]'
: ''
						) . '</li>';

					if ($conf != '')
						$HTML .= '<ul class="DS-config">' . $conf . '</ul>';
				}
				/* this a container for repetitive elements --------------------- */
				else if (isset($def['section']) && ($def['section'] == 1)) {
					$HTML .= '<p>[..., ..., ...]</p>';
				}
				/* this a container for cellections of elements ----------------- */
				else if (isset($def['type']) && ($def['type'] == "array")) {
					$HTML .= '<p>[...]</p>';
				}
				/* this a regular entry ----------------------------------------- */
				else {
					/* The basic XML-structure of an entry is:
					 *
					 * <element>
					 * 	<tx_templavoila>	-> entries with informational character belonging to this entry
					 * 	<TCEforms>		-> entries being used for TCE-construction
					 * 	<type + el + section>	-> subsequent hierarchical construction
					 *	<langOverlayMode>	-> ??? (is it the language-key?)
					 * </element>
					 */
					if (($tv = $def['tx_templavoila'])) {
						/* The basic XML-structure of an tx_templavoila-entry is:
						 *
						 * <tx_templavoila>
						 * 	<title>			-> Human readable title of the element
						 * 	<description>		-> A description explaining the elements function
						 * 	<sample_data>		-> Some sample-data (can't contain HTML)
						 * 	<eType>			-> The preset-type of the element, used to switch use/content of TCEforms/TypoScriptObjPath
						 * 	<oldStyleColumnNumber>	-> for distributing the fields across the tt_content column-positions
						 * 	<proc>			-> define post-processes for this element's value
						 *		<int>		-> this element's value will be cast to an integer (if exist)
						 *		<HSC>		-> this element's value will convert special chars to HTML-entities (if exist)
						 *		<stdWrap>	-> an implicit stdWrap for this element, "stdWrap { ...inside... }"
						 * 	</proc>
						 *	<TypoScript_constants>	-> an array of constants that will be substituted in the <TypoScript>-element
						 * 	<TypoScript>		->
						 * 	<TypoScriptObjPath>	->
						 * </tx_templavoila>
						 */

						if (isset($tv['description']) && ($tv['description'] != ''))
							$HTML .= '<p>"' . $tv['description'] . '"</p>';

						/* it would also be possible to use the 'list-style-image'-property
						 * for the flags, which would be more sensible to IE-bugs though
						 */
						$proc = '';
						if (isset($tv['proc']) && isset($tv['proc']['int'])) $proc .= '<li>' .
						(($tv['proc']['int'] == 1)
? t3lib_iconWorks::getSpriteIcon('status-dialog-ok')
: t3lib_iconWorks::getSpriteIcon('status-dialog-error')
						) . ' ' . $GLOBALS['LANG']->getLL('casttointeger', 1) . '</li>';
						if (isset($tv['proc']) && isset($tv['proc']['HSC'])) $proc .= '<li>' .
						(($tv['proc']['HSC'] == 1)
? t3lib_iconWorks::getSpriteIcon('status-dialog-ok')
: t3lib_iconWorks::getSpriteIcon('status-dialog-error')
						) . ' ' . $GLOBALS['LANG']->getLL('hsced', 1) .
						(($tv['proc']['HSC'] == 1)
? ' ' . $GLOBALS['LANG']->getLL('hsc_on', 1)
: ' ' . $GLOBALS['LANG']->getLL('hsc_off', 1)
						) . '</li>';
						if (isset($tv['proc']) && isset($tv['proc']['stdWrap'])) $proc .= '<li>' .
						(($tv['proc']['stdWrap'] != '')
? t3lib_iconWorks::getSpriteIcon('status-dialog-ok')
: t3lib_iconWorks::getSpriteIcon('status-dialog-error')
						) . ' ' . $GLOBALS['LANG']->getLL('stdwrap', 1) . '</li>';

						if ($proc != '')
							$HTML .= '<ul class="DS-proc">' . $proc . '</ul>';
								//TODO: get the registered eTypes and use the labels
						switch ($tv['eType']) {
							case "input":            $preset = 'Plain input field';             $tco = false; break;
							case "input_h":          $preset = 'Header field';                  $tco = false; break;
							case "input_g":          $preset = 'Header field, Graphical';       $tco = false; break;
							case "text":             $preset = 'Text area for bodytext';        $tco = false; break;
							case "rte":              $preset = 'Rich text editor for bodytext'; $tco = false; break;
							case "link":             $preset = 'Link field';                    $tco = false; break;
							case "int":              $preset = 'Integer value';                 $tco = false; break;
							case "image":            $preset = 'Image field';                   $tco = false; break;
							case "imagefixed":       $preset = 'Image field, fixed W+H';        $tco = false; break;
							case "select":           $preset = 'Selector box';                  $tco = false; break;
							case "ce":               $preset = 'Content Elements';              $tco = true;  break;
							case "TypoScriptObject": $preset = 'TypoScript Object Path';        $tco = true;  break;

							case "none":             $preset = 'None';                          $tco = true;  break;
							default:                 $preset = 'Custom [' . $tv['eType'] . ']'; $tco = true;  break;
						}

						switch ($tv['oldStyleColumnNumber']) {
							case 0:  $column = 'Normal [0]';                                   break;
							case 1:  $column = 'Left [1]';                                     break;
							case 2:  $column = 'Right [2]';                                    break;
							case 3:  $column = 'Border [3]';                                   break;
							default: $column = 'Custom [' . $tv['oldStyleColumnNumber'] . ']'; break;
						}

						$notes = '';
						if (($tv['eType'] != "TypoScriptObject") && isset($tv['TypoScriptObjPath']))
							$notes .= '<li>' . $GLOBALS['LANG']->getLL('redundant', 1) . ' &lt;TypoScriptObjPath&gt;-entry</li>';
						if (($tv['eType'] == "TypoScriptObject") && isset($tv['TypoScript']))
							$notes .= '<li>' . $GLOBALS['LANG']->getLL('redundant', 1) . ' &lt;TypoScript&gt;-entry</li>';
						if ((($tv['eType'] == "TypoScriptObject") || !isset($tv['TypoScript'])) && isset($tv['TypoScript_constants']))
							$notes .= '<li>' . $GLOBALS['LANG']->getLL('redundant', 1) . ' &lt;TypoScript_constants&gt;-' . $GLOBALS['LANG']->getLL('entry', 1) . '</li>';
						if (isset($tv['proc']) && isset($tv['proc']['int']) && ($tv['proc']['int'] == 1) && isset($tv['proc']['HSC']))
							$notes .= '<li>' . $GLOBALS['LANG']->getLL('redundant', 1) . ' &lt;proc&gt;&lt;HSC&gt;-' . $GLOBALS['LANG']->getLL('redundant', 1) . '</li>';
						if (isset($tv['TypoScriptObjPath']) && preg_match('/[^a-zA-Z0-9\.\:_]/', $tv['TypoScriptObjPath']))
							$notes .= '<li><strong>&lt;TypoScriptObjPath&gt;-' . $GLOBALS['LANG']->getLL('illegalcharacters', 1) . '</strong></li>';

						$tsstats = '';
						if (isset($tv['TypoScript_constants']))
							$tsstats .= '<li>' . sprintf($GLOBALS['LANG']->getLL('dsdetails_tsconstants', 1), count($tv['TypoScript_constants'])) . '</li>';
						if (isset($tv['TypoScript']))
							$tsstats .= '<li>' . sprintf($GLOBALS['LANG']->getLL('dsdetails_tslines', 1), (1 + strlen($tv['TypoScript']) - strlen(str_replace("\n", "", $tv['TypoScript'])))) . '</li>';
						if (isset($tv['TypoScriptObjPath']))
							$tsstats .= '<li>' . sprintf($GLOBALS['LANG']->getLL('dsdetails_tsutilize', 1), '<em>' . $tv['TypoScriptObjPath'] . '</em>') . '</li>';

						$HTML .= '<dl class="DS-infos">';
						$HTML .= '<dt>' . $GLOBALS['LANG']->getLL('dsdetails_preset', 1) . ':</dt>';
						$HTML .= '<dd>' . $preset . '</dd>';
						$HTML .= '<dt>' . $GLOBALS['LANG']->getLL('dsdetails_column', 1) . ':</dt>';
						$HTML .= '<dd>' . $column . '</dd>';
						if ($tsstats != '') {
							$HTML .= '<dt>' . $GLOBALS['LANG']->getLL('dsdetails_ts', 1) . ':</dt>';
							$HTML .= '<dd><ul class="DS-stats">' . $tsstats . '</ul></dd>';
						}
						if ($notes != '') {
							$HTML .= '<dt>' . $GLOBALS['LANG']->getLL('dsdetails_notes', 1) . ':</dt>';
							$HTML .= '<dd><ul class="DS-notes">' . $notes . '</ul></dd>';
						}
						$HTML .= '</dl>';
					}
					else {
						$HTML .= '<p>' . $GLOBALS['LANG']->getLL('dsdetails_nobasicdefinitions', 1) . '</p>';
					}

					if (($tf = $def['TCEforms'])) {
						/* The basic XML-structure of an TCEforms-entry is:
						 *
						 * <TCEforms>
						 * 	<label>			-> TCE-label for the BE
						 * 	<config>		-> TCE-configuration array
						 * </TCEforms>
						 */
					}
					else if (!$tco) {
						$HTML .= '<p>' . $GLOBALS['LANG']->getLL('dsdetails_notceformdefinitions', 1) . '</p>';
					}
				}

				/* there are some childs to process ----------------------------- */
				if (isset($def['type']) && ($def['type'] == "array")) {

					if (isset($def['section']))
						;
					if (isset($def['el']))
						$HTML .= $this->renderDSdetails($def['el']);
				}

				$HTML .= '</dd>';
			}

			$HTML .= '</dl>';
		}
		else
			$HTML .= '<p>' . t3lib_iconWorks::getSpriteIcon('status-dialog-warning') . ' The element has no children!</p>';

		return $HTML;
	}

	/**
	 * Show meta data part of Data Structure
	 *
	 * @param	[type]		$DSstring: ...
	 * @return	[type]		...
	 */
	function DSdetails($DSstring)	{
		$DScontent = t3lib_div::xml2array($DSstring);

		$inputFields = 0;
		$referenceFields = 0;
		$rootelements = 0;
		if (is_array ($DScontent) && is_array($DScontent['ROOT']['el']))	{
			foreach($DScontent['ROOT']['el'] as $elKey => $elCfg)	{
				$rootelements++;
				if (isset($elCfg['TCEforms']))	{

						// Assuming that a reference field for content elements is recognized like this, increment counter. Otherwise assume input field of some sort.
					if ($elCfg['TCEforms']['config']['type']==='group' && $elCfg['TCEforms']['config']['allowed']==='tt_content')	{
						$referenceFields++;
					} else {
						$inputFields++;
					}
				}
				if (isset($elCfg['el'])) $elCfg['el'] = '...';
				unset($elCfg['tx_templavoila']['sample_data']);
				unset($elCfg['tx_templavoila']['tags']);
				unset($elCfg['tx_templavoila']['eType']);

				if (tx_templavoila_div::convertVersionNumberToInteger(TYPO3_version) < 4005000){
					$rootElementsHTML.='<b>'.$elCfg['tx_templavoila']['title'].'</b>'.t3lib_div::view_array($elCfg);
				} else {
					$rootElementsHTML.='<b>'.$elCfg['tx_templavoila']['title'].'</b>'.t3lib_utility_Debug::viewArray($elCfg);
				}
			}
		}

	/*	$DScontent = array('meta' => $DScontent['meta']);	*/

		$languageMode = '';
		if (is_array($DScontent['meta'])) {
		if ($DScontent['meta']['langDisable'])	{
			$languageMode = 'Disabled';
		} elseif ($DScontent['meta']['langChildren']) {
			$languageMode = 'Inheritance';
		} else {
			$languageMode = 'Separate';
		}
		}

		return array(
			'HTML' => /*t3lib_div::view_array($DScontent).'Language Mode => "'.$languageMode.'"<hr/>
						Root Elements = '.$rootelements.', hereof ref/input fields = '.($referenceFields.'/'.$inputFields).'<hr/>
						'.$rootElementsHTML*/ $this->renderDSdetails($DScontent),
			'languageMode' => $languageMode,
			'rootelements' => $rootelements,
			'inputFields' => $inputFields,
			'referenceFields' => $referenceFields
		);
	}















	/******************************
	 *
	 * Wizard for new site
	 *
	 *****************************/

	/**
	 * Wizard overview page - before the wizard is started.
	 *
	 * @return	void
	 */
	function renderNewSiteWizard_overview()	{
		global $BE_USER, $LANG;

		if ($this->modTSconfig['properties']['hideNewSiteWizard']) {
			return;
		}

		if ($BE_USER->isAdmin())	{

				// Introduction:
			$outputString.= nl2br(sprintf($LANG->getLL('newsitewizard_intro', 1), implode('", "', $this->getTemplatePaths(true, false))));

				// Checks:
			$missingExt = $this->wizard_checkMissingExtensions();
			$missingConf = $this->wizard_checkConfiguration();
			$missingDir = $this->wizard_checkDirectory();
			if (!$missingExt && !$missingConf)	{
				$outputString.= '
				<br/>
				<br/>
				<input type="submit" value="' . $LANG->getLL('newsitewizard_startnow', 1) . '" onclick="'.htmlspecialchars('document.location=\'index.php?SET[wiz_step]=1\'; return false;').'" />';
			} else {
				$outputString.= '<br/><br/>' . $LANG->getLL('newsitewizard_problem');

			}

				// Add output:
			$this->content.= $this->doc->section($LANG->getLL('wiz_title'),$outputString,0,1);

				// Missing extension warning:
			if ($missingExt)	{
				$msg = t3lib_div::makeInstance('t3lib_FlashMessage', $missingExt, $LANG->getLL('newsitewizard_missingext'), t3lib_FlashMessage::ERROR);
				$this->content .= $msg->render();
			}

				// Missing configuration warning:
			if ($missingConf)	{
				$msg = t3lib_div::makeInstance('t3lib_FlashMessage', $LANG->getLL('newsitewizard_missingconf_description'), $LANG->getLL('newsitewizard_missingconf'), t3lib_FlashMessage::ERROR);
				$this->content .= $msg->render();
			}

				// Missing directory warning:
			if ($missingDir)	{
				$this->content.= $this->doc->section($LANG->getLL('newsitewizard_missingdir'), $missingDir, 0, 1, 3);
			}
		}
	}

	/**
	 * Running the wizard. Basically branching out to sub functions.
	 * Also gets and saves session data in $this->wizardData
	 *
	 * @return	void
	 */
	function renderNewSiteWizard_run()	{
		global $BE_USER, $LANG;

			// Getting session data:
		$this->wizardData = $BE_USER->getSessionData('tx_templavoila_wizard');

		if ($BE_USER->isAdmin())	{

			$outputString = '';

			switch($this->MOD_SETTINGS['wiz_step'])	{
				case 1:
					$this->wizard_step1();
				break;
				case 2:
					$this->wizard_step2();
				break;
				case 3:
					$this->wizard_step3();
				break;
				case 4:
					$this->wizard_step4();
				break;
				case 5:
					$this->wizard_step5('field_menu');
				break;
				case 5.1:
					$this->wizard_step5('field_submenu');
				break;
				case 6:
					$this->wizard_step6();
				break;
			}

			$outputString.= '<hr/><input type="submit" value="' . $LANG->getLL('newsitewizard_cancel', 1) . '" onclick="' . htmlspecialchars('document.location=\'index.php?SET[wiz_step]=0\'; return false;') . '" />';

				// Add output:
			$this->content.= $this->doc->section('',$outputString,0,1);
		}

			// Save session data:
		$BE_USER->setAndSaveSessionData('tx_templavoila_wizard',$this->wizardData);
	}

	/**
	 * Pre-checking for extensions
	 *
	 * @return	string		If string is returned, an error occured.
	 */
	function wizard_checkMissingExtensions()	{

		$outputString .= $GLOBALS['LANG']->getLL('newsitewizard_missingext_description', 1);

			// Create extension status:
		$checkExtensions = explode(',','css_styled_content,impexp');
		$missingExtensions = FALSE;

		$tRows = array();
		$tRows[] = '<tr class="tableheader bgColor5">
			<td>' . $GLOBALS['LANG']->getLL('newsitewizard_missingext_extkey', 1) . '</td>
			<td>' . $GLOBALS['LANG']->getLL('newsitewizard_missingext_installed', 1) . '</td>
		</tr>';

		foreach($checkExtensions as $extKey)	{
			$tRows[] = '<tr class="bgColor4">
				<td>'.$extKey.'</td>
				<td align="center">'.(t3lib_extMgm::isLoaded($extKey) ? $GLOBALS['LANG']->getLL('newsitewizard_missingext_yes', 1) : '<span class="typo3-red">' . $GLOBALS['LANG']->getLL('newsitewizard_missingext_no', 1) . '</span>').'</td>
			</tr>';

			if (!t3lib_extMgm::isLoaded($extKey))	$missingExtensions = TRUE;
		}

		$outputString.='<table border="0" cellpadding="1" cellspacing="1">'.implode('',$tRows).'</table>';

			// If no extensions are missing, simply go to step two:
		if ($missingExtensions)		{
			return $outputString;
		}
	}

	/**
	 * Pre-checking for TemplaVoila configuration
	 *
	 * @return	string		If string is returned, an error occured.
	 */
	function wizard_checkConfiguration()	{
		$TVconfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);
		return !is_array($TVconfig);
	}

	/**
	 * Pre-checking for directory of extensions.
	 *
	 * @return	string		If string is returned, an error occured.
	 */
	function wizard_checkDirectory()	{
		$paths = $this->getTemplatePaths(true);
		if(empty($paths)) {
			return nl2br(sprintf($GLOBALS['LANG']->getLL('newsitewizard_missingdir_instruction'), implode(' or ', $this->getTemplatePaths(true, false)), $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']));
		}
		return false;
	}

	/**
	 * Wizard Step 1: Selecting template file.
	 *
	 * @return	void
	 */
	function wizard_step1()	{
		$paths = $this->getTemplatePaths();
		$files = $this->getTemplateFiles();
		if (!empty($paths) && !empty($files))	{

			$this->wizardData = array();
			$pathArr = t3lib_div::removePrefixPathFromList($paths, PATH_site);
			$outputString .= sprintf($GLOBALS['LANG']->getLL('newsitewizard_firststep'), implode('", "', $pathArr)). '<br/>';

				// Get all HTML files:
			$fileArr = t3lib_div::removePrefixPathFromList($files, PATH_site);

				// Prepare header:
			$tRows = array();
			$tRows[] = '<tr class="tableheader bgColor5">
				<td>' . $GLOBALS['LANG']->getLL('toused_path', 1) . ':</td>
				<td>' . $GLOBALS['LANG']->getLL('usage', 1) . ':</td>
				<td>' . $GLOBALS['LANG']->getLL('action', 1) . ':</td>
			</tr>';

				// Traverse available template files:
			foreach($fileArr as $file)	{

					// Has been used:
				$tosForTemplate = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'uid',
					'tx_templavoila_tmplobj',
					'fileref='.$GLOBALS['TYPO3_DB']->fullQuoteStr($file, 'tx_templavoila_tmplobj').
						t3lib_BEfunc::deleteClause('tx_templavoila_tmplobj')
					);

					// Preview link
				$onClick = 'vHWin=window.open(\''.$this->doc->backPath.'../'.$file.'\',\'tvTemplatePreview\',\'status=1,menubar=1,scrollbars=1,location=1\');vHWin.focus();return false;';

					// Make row:
				$tRows[] = '<tr class="bgColor4">
					<td>' . htmlspecialchars($file) . '</td>
					<td>' . (count($tosForTemplate) ? sprintf($GLOBALS['LANG']->getLL('newsitewizard_usedtimes', 1),  count($tosForTemplate)) : $GLOBALS['LANG']->getLL('newsitewizard_notused', 1)) . '</td>
					<td>'.
						'<a href="#" onclick="'.htmlspecialchars($onClick).'">' . $GLOBALS['LANG']->getLL('newsitewizard_preview', 1) . '</a> '.
						'<a href="'.htmlspecialchars('index.php?SET[wiz_step]=2&CFG[file]='.rawurlencode($file)).'">' . $GLOBALS['LANG']->getLL('newsitewizard_choose', 1) . '</a> '.
						'</td>
				</tr>';
			}
			$outputString.= '<table border="0" cellpadding="1" cellspacing="1" class="lrPadding">'.implode('',$tRows).'</table>';

				// Refresh button:
			$outputString.= '<br/><input type="submit" value="' . $GLOBALS['LANG']->getLL('refresh', 1) . '" onclick="'.htmlspecialchars('document.location=\'index.php?SET[wiz_step]=1\'; return false;').'" />';

				// Add output:
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('newsitewizard_selecttemplate', 1), $outputString, 0, 1);

		} else {
			$this->content .= $this->doc->section('TemplaVoila wizard error', $GLOBALS['LANG']->getLL('newsitewizard_errornodir', 1), 0, 1);
		}
	}

	/**
	 * Step 2: Enter default values:
	 *
	 * @return	void
	 */
	function wizard_step2()	{

			// Save session data with filename:
		$cfg = t3lib_div::_GET('CFG');
		if ($cfg['file'] && t3lib_div::getFileAbsFileName($cfg['file']))	{
			$this->wizardData['file'] = $cfg['file'];
		}

			// Show selected template file:
		if ($this->wizardData['file'])	{
			$outputString.= htmlspecialchars(sprintf($GLOBALS['LANG']->getLL('newsitewizard_templateselected'), $this->wizardData['file']));
			$outputString.= '<br/><iframe src="'.htmlspecialchars($this->doc->backPath.'../'.$this->wizardData['file']).'" width="640" height="300"></iframe>';

				// Enter default data:
			$outputString.='
				<br/><br/><br/>
				' . $GLOBALS['LANG']->getLL('newsitewizard_step2next', 1) . '
				<br/>
	<br/>
				<b>' . $GLOBALS['LANG']->getLL('newsitewizard_step2_name', 1) . ':</b><br/>
				' . $GLOBALS['LANG']->getLL('newsitewizard_step2_required', 1) . '<br/>
				' . $GLOBALS['LANG']->getLL('newsitewizard_step2_valuename', 1) . '<br/>
				<input type="text" name="CFG[sitetitle]" value="'.htmlspecialchars($this->wizardData['sitetitle']).'" /><br/>
	<br/>
				<b>' . $GLOBALS['LANG']->getLL('newsitewizard_step2_url', 1) . ':</b><br/>
				' . $GLOBALS['LANG']->getLL('newsitewizard_step2_optional', 1) . '<br/>
				' . $GLOBALS['LANG']->getLL('newsitewizard_step2_valueurl', 1) . '<br/>
				<input type="text" name="CFG[siteurl]" value="'.htmlspecialchars($this->wizardData['siteurl']).'" /><br/>
	<br/>
				<b>' . $GLOBALS['LANG']->getLL('newsitewizard_step2_editor', 1) . ':</b><br/>
				' . $GLOBALS['LANG']->getLL('newsitewizard_step2_required', 1) . '<br/>
				' . $GLOBALS['LANG']->getLL('newsitewizard_step2_username', 1) . '<br/>
				<input type="text" name="CFG[username]" value="'.htmlspecialchars($this->wizardData['username']).'" /><br/>
	<br/>
				<input type="hidden" name="SET[wiz_step]" value="3" />
				<input type="submit" name="_create_site" value="' . $GLOBALS['LANG']->getLL('newsitewizard_step2_createnewsite', 1) . '" />
			';
		} else {
			$outputString .= $GLOBALS['LANG']->getLL('newsitewizard_step2_notemplatefound', 1);
		}

			// Add output:
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('newsitewizard_step2', 1), $outputString, 0, 1);
	}

	/**
	 * Step 3: Begin template mapping
	 *
	 * @return	void
	 */
	function wizard_step3()	{

			// Save session data with filename:
		$cfg = t3lib_div::_POST('CFG');
		if (isset($cfg['sitetitle']))	{
			$this->wizardData['sitetitle'] = trim($cfg['sitetitle']);
		}
		if (isset($cfg['siteurl']))	{
			$this->wizardData['siteurl'] = trim($cfg['siteurl']);
		}
		if (isset($cfg['username']))	{
			$this->wizardData['username'] = trim($cfg['username']);
		}

			// If the create-site button WAS clicked:
		if (t3lib_div::_POST('_create_site'))	{

				// Show selected template file:
			if ($this->wizardData['file'] && $this->wizardData['sitetitle'] && $this->wizardData['username'])	{

					// DO import:
				$import = $this->getImportObj();
				if (isset($this->modTSconfig['properties']['newTvSiteFile'])) {
					$inFile = PATH_site . $this->modTSconfig['properties']['newTVsiteTemplate'];
				} else {
					$inFile = t3lib_extMgm::extPath('templavoila') . 'mod2/new_tv_site.xml';
				}
				if (@is_file($inFile) && $import->loadFile($inFile,1))	{

					$import->importData($this->importPageUid);

						// Update various fields (the index values, eg. the "1" in "$import->import_mapId['pages'][1]]..." are the UIDs of the original records from the import file!)
					$data = array();
					$data['pages'][t3lib_BEfunc::wsMapId('pages',$import->import_mapId['pages'][1])]['title'] = $this->wizardData['sitetitle'];
					$data['sys_template'][t3lib_BEfunc::wsMapId('sys_template',$import->import_mapId['sys_template'][1])]['title'] = $GLOBALS['LANG']->getLL('newsitewizard_maintemplate', 1) . ' ' . $this->wizardData['sitetitle'];
					$data['sys_template'][t3lib_BEfunc::wsMapId('sys_template',$import->import_mapId['sys_template'][1])]['sitetitle'] = $this->wizardData['sitetitle'];
					$data['tx_templavoila_tmplobj'][t3lib_BEfunc::wsMapId('tx_templavoila_tmplobj',$import->import_mapId['tx_templavoila_tmplobj'][1])]['fileref'] = $this->wizardData['file'];
					$data['tx_templavoila_tmplobj'][t3lib_BEfunc::wsMapId('tx_templavoila_tmplobj',$import->import_mapId['tx_templavoila_tmplobj'][1])]['templatemapping'] = serialize(
						array(
							'MappingInfo' => array(
								'ROOT' => array(
									'MAP_EL' => 'body[1]/INNER'
								)
							),
							'MappingInfo_head' => array(
								'headElementPaths' => array('link[1]','link[2]','link[3]','style[1]','style[2]','style[3]'),
								'addBodyTag' => 1
							)
						)
					);

						// Update user settings
					$newUserID = t3lib_BEfunc::wsMapId('be_users',$import->import_mapId['be_users'][2]);
					$newGroupID = t3lib_BEfunc::wsMapId('be_groups',$import->import_mapId['be_groups'][1]);

					$data['be_users'][$newUserID]['username'] = $this->wizardData['username'];
					$data['be_groups'][$newGroupID]['title'] = $this->wizardData['username'];

					foreach($import->import_mapId['pages'] as $newID)	{
						$data['pages'][$newID]['perms_userid'] = $newUserID;
						$data['pages'][$newID]['perms_groupid'] = $newGroupID;
					}

						// Set URL if applicable:
					if (strlen($this->wizardData['siteurl']))	{
						$data['sys_domain']['NEW']['pid'] = t3lib_BEfunc::wsMapId('pages',$import->import_mapId['pages'][1]);
						$data['sys_domain']['NEW']['domainName'] = $this->wizardData['siteurl'];
					}

						// Execute changes:
					$tce = t3lib_div::makeInstance('t3lib_TCEmain');
					$tce->stripslashes_values = 0;
					$tce->dontProcessTransformations = 1;
					$tce->start($data,Array());
					$tce->process_datamap();

						// Setting environment:
					$this->wizardData['rootPageId'] = $import->import_mapId['pages'][1];
					$this->wizardData['templateObjectId'] = t3lib_BEfunc::wsMapId('tx_templavoila_tmplobj',$import->import_mapId['tx_templavoila_tmplobj'][1]);
					$this->wizardData['typoScriptTemplateID'] = t3lib_BEfunc::wsMapId('sys_template',$import->import_mapId['sys_template'][1]);

					t3lib_BEfunc::setUpdateSignal('updatePageTree');

					$outputString .= $GLOBALS['LANG']->getLL('newsitewizard_maintemplate', 1) . '<hr/>';
				}
			} else {
				$outputString .= $GLOBALS['LANG']->getLL('newsitewizard_maintemplate', 1);
			}
		}

			// If a template Object id was found, continue with mapping:
		if ($this->wizardData['templateObjectId'])	{
			$url = '../cm1/index.php?table=tx_templavoila_tmplobj&uid='.$this->wizardData['templateObjectId'].'&SET[selectHeaderContent]=0&_reload_from=1&id=' . $this->id . '&returnUrl='.rawurlencode('../mod2/index.php?SET[wiz_step]=4');

			$outputString.= $GLOBALS['LANG']->getLL('newsitewizard_step3ready') . '
				<br/>
				<br/>
				<img src="mapbody_animation.gif" style="border: 2px black solid;" alt=""><br/>
				<br/>
				<br/><input type="submit" value="' . $GLOBALS['LANG']->getLL('newsitewizard_startmapping', 1) . '" onclick="'.htmlspecialchars('document.location=\''.$url.'\'; return false;').'" />
			';
		}

			// Add output:
		$this->content.= $this->doc->section($GLOBALS['LANG']->getLL('newsitewizard_beginmapping', 1), $outputString, 0, 1);
	}

	/**
	 * Step 4: Select HTML header parts.
	 *
	 * @return	void
	 */
	function wizard_step4()	{
		$url = '../cm1/index.php?table=tx_templavoila_tmplobj&uid='.$this->wizardData['templateObjectId'].'&SET[selectHeaderContent]=1&_reload_from=1&id=' . $this->id . '&returnUrl='.rawurlencode('../mod2/index.php?SET[wiz_step]=5');
		$outputString .= $GLOBALS['LANG']->getLL('newsitewizard_headerinclude') . '
			<br/>
			<img src="maphead_animation.gif" style="border: 2px black solid;" alt=""><br/>
			<br/>
			<br/><input type="submit" value="' . $GLOBALS['LANG']->getLL('newsitewizard_headerselect') . '" onclick="'.htmlspecialchars('document.location=\''.$url.'\'; return false;').'" />
			';

			// Add output:
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('newsitewizard_step4'), $outputString, 0, 1);
	}

	/**
	 * Step 5: Create dynamic menu
	 *
	 * @param	string		Type of menu (main or sub), values: "field_menu" or "field_submenu"
	 * @return	void
	 */
	function wizard_step5($menuField)	{

		$menuPart = $this->getMenuDefaultCode($menuField);
		$menuType = $menuField === 'field_menu' ? 'mainMenu' : 'subMenu';
		$menuTypeText = $menuField === 'field_menu' ? 'main menu' : 'sub menu';
		$menuTypeLetter = $menuField === 'field_menu' ? 'a' : 'b';
		$menuTypeNextStep = $menuField === 'field_menu' ? 5.1 : 6;
		$menuTypeEntryLevel = $menuField === 'field_menu' ? 0 : 1;

		$this->saveMenuCode();

		if (strlen($menuPart))	{

				// Main message:
			$outputString.=  sprintf($GLOBALS['LANG']->getLL('newsitewizard_basicsshouldwork', 1), $menuTypeText, $menuType, $menuTypeText);

				// Start up HTML parser:
			require_once(PATH_t3lib.'class.t3lib_parsehtml.php');
			$htmlParser = t3lib_div::makeinstance('t3lib_parsehtml');

				// Parse into blocks
			$parts = $htmlParser->splitIntoBlock('td,tr,table,a,div,span,ol,ul,li,p,h1,h2,h3,h4,h5',$menuPart,1);

				// If it turns out to be only a single large block we expect it to be a container for the menu item. Therefore we will parse the next level and expect that to be menu items:
			if (count($parts)==3)	{
				$totalWrap = array();
				$totalWrap['before'] = $parts[0].$htmlParser->getFirstTag($parts[1]);
				$totalWrap['after'] = '</'.strtolower($htmlParser->getFirstTagName($parts[1])).'>'.$parts[2];

				$parts = $htmlParser->splitIntoBlock('td,tr,table,a,div,span,ol,ul,li,p,h1,h2,h3,h4,h5',$htmlParser->removeFirstAndLastTag($parts[1]),1);
			} else {
				$totalWrap = array();
			}

			$menuPart_HTML = trim($totalWrap['before']).chr(10).implode(chr(10),$parts).chr(10).trim($totalWrap['after']);

				// Traverse expected menu items:
			$menuWraps = array();
			$GMENU = FALSE;
			$mouseOver = FALSE;
			$key = '';

			foreach($parts as $k => $value)	{
				if ($k%2)	{	// Only expecting inner elements to be of use:

					$linkTag = $htmlParser->splitIntoBlock('a',$value,1);
					if ($linkTag[1])	{
						$newValue = array();
						$attribs = $htmlParser->get_tag_attributes($htmlParser->getFirstTag($linkTag[1]),1);
						$newValue['A-class'] = $attribs[0]['class'];
						if ($attribs[0]['onmouseover'] && $attribs[0]['onmouseout'])	$mouseOver = TRUE;

							// Check if the complete content is an image - then make GMENU!
						$linkContent = trim($htmlParser->removeFirstAndLastTag($linkTag[1]));
						if (preg_match('/^<img[^>]*>$/i',$linkContent))	{
							$GMENU = TRUE;
							$attribs = $htmlParser->get_tag_attributes($linkContent,1);
							$newValue['I-class'] = $attribs[0]['class'];
							$newValue['I-width'] = $attribs[0]['width'];
							$newValue['I-height'] = $attribs[0]['height'];

							$filePath = t3lib_div::getFileAbsFileName(t3lib_div::resolveBackPath(PATH_site.$attribs[0]['src']));
							if (@is_file($filePath))	{
								$newValue['backColorGuess'] = $this->getBackgroundColor($filePath);
							} else $newValue['backColorGuess'] = '';

							if ($attribs[0]['onmouseover'] && $attribs[0]['onmouseout'])	$mouseOver = TRUE;
						}

						$linkTag[1] = '|';
						$newValue['wrap'] = preg_replace('/['.chr(10).chr(13).']*/','',implode('',$linkTag));

						$md5Base = $newValue;
						unset($md5Base['I-width']);
						unset($md5Base['I-height']);
						$md5Base = serialize($md5Base);
						$md5Base = preg_replace('/name=["\'][^"\']*["\']/','',$md5Base);
						$md5Base = preg_replace('/id=["\'][^"\']*["\']/','',$md5Base);
						$md5Base = preg_replace('/\s/','',$md5Base);
						$key = md5($md5Base);

						if (!isset($menuWraps[$key]))	{	// Only if not yet set, set it (so it only gets set once and the first time!)
							$menuWraps[$key] = $newValue;
						} else {	// To prevent from writing values in the "} elseif ($key) {" below, we clear the key:
							$key = '';
						}
					} elseif ($key) {

							// Add this to the previous wrap:
						$menuWraps[$key]['bulletwrap'].= str_replace('|','&#'.ord('|').';',preg_replace('/['.chr(10).chr(13).']*/','',$value));
					}
				}
			}

				// Construct TypoScript for the menu:
			reset($menuWraps);
			if (count($menuWraps)==1)	{
				$menu_normal = current($menuWraps);
				$menu_active = next($menuWraps);
			} else { 	// If more than two, then the first is the active one.
				$menu_active = current($menuWraps);
				$menu_normal = next($menuWraps);
			}

#debug($menuWraps);
#debug($mouseOver);
			if ($GMENU)	{
				$typoScript = '
lib.'.$menuType.' = HMENU
lib.'.$menuType.'.entryLevel = '.$menuTypeEntryLevel.'
'.(count($totalWrap) ? 'lib.'.$menuType.'.wrap = '.preg_replace('/['.chr(10).chr(13).']/','',implode('|',$totalWrap)) : '').'
lib.'.$menuType.'.1 = GMENU
lib.'.$menuType.'.1.NO.wrap = '.$this->makeWrap($menu_normal).
	($menu_normal['I-class'] ? '
lib.'.$menuType.'.1.NO.imgParams = class="'.htmlspecialchars($menu_normal['I-class']).'" ' : '').'
lib.'.$menuType.'.1.NO {
	XY = '.($menu_normal['I-width']?$menu_normal['I-width']:150).','.($menu_normal['I-height']?$menu_normal['I-height']:25).'
	backColor = '.($menu_normal['backColorGuess'] ? $menu_normal['backColorGuess'] : '#FFFFFF').'
	10 = TEXT
	10.text.field = title // nav_title
	10.fontColor = #333333
	10.fontSize = 12
	10.offset = 15,15
	10.fontFace = t3lib/fonts/nimbus.ttf
}
	';

				if ($mouseOver)	{
					$typoScript.= '
lib.'.$menuType.'.1.RO < lib.'.$menuType.'.1.NO
lib.'.$menuType.'.1.RO = 1
lib.'.$menuType.'.1.RO {
	backColor = '.t3lib_div::modifyHTMLColorAll(($menu_normal['backColorGuess'] ? $menu_normal['backColorGuess'] : '#FFFFFF'),-20).'
	10.fontColor = red
}
			';

				}
				if (is_array($menu_active))	{
					$typoScript.= '
lib.'.$menuType.'.1.ACT < lib.'.$menuType.'.1.NO
lib.'.$menuType.'.1.ACT = 1
lib.'.$menuType.'.1.ACT.wrap = '.$this->makeWrap($menu_active).
	($menu_active['I-class'] ? '
lib.'.$menuType.'.1.ACT.imgParams = class="'.htmlspecialchars($menu_active['I-class']).'" ' : '').'
lib.'.$menuType.'.1.ACT {
	backColor = '.($menu_active['backColorGuess'] ? $menu_active['backColorGuess'] : '#FFFFFF').'
}
			';
				}

			} else {
				$typoScript = '
lib.'.$menuType.' = HMENU
lib.'.$menuType.'.entryLevel = '.$menuTypeEntryLevel.'
'.(count($totalWrap) ? 'lib.'.$menuType.'.wrap = '.preg_replace('/['.chr(10).chr(13).']/','',implode('|',$totalWrap)) : '').'
lib.'.$menuType.'.1 = TMENU
lib.'.$menuType.'.1.NO {
	allWrap = '.$this->makeWrap($menu_normal).
	($menu_normal['A-class'] ? '
	ATagParams = class="'.htmlspecialchars($menu_normal['A-class']).'"' : '').'
}
	';

				if (is_array($menu_active))	{
					$typoScript.= '
lib.'.$menuType.'.1.ACT = 1
lib.'.$menuType.'.1.ACT {
	allWrap = '.$this->makeWrap($menu_active).
	($menu_active['A-class'] ? '
	ATagParams = class="'.htmlspecialchars($menu_active['A-class']).'"' : '').'
}
			';
				}
			}


				// Output:

				// HTML defaults:
			$outputString.='
			<br/>
			<br/>
			' . $GLOBALS['LANG']->getLL('newsitewizard_menuhtmlcode', 1) . '
			<hr/>
			<pre>'.htmlspecialchars($menuPart_HTML).'</pre>
			<hr/>
			<br/>';


			if (trim($menu_normal['wrap']) != '|')	{
				$outputString .= sprintf($GLOBALS['LANG']->getLL('newsitewizard_menuenc', 1), htmlspecialchars(str_replace('|', ' ... ', $menu_normal['wrap'])));
			} else {
				$outputString .= $GLOBALS['LANG']->getLL('newsitewizard_menunoa', 1);
			}
			if (count($totalWrap))	{
				$outputString .= sprintf($GLOBALS['LANG']->getLL('newsitewizard_menuwrap', 1), htmlspecialchars(str_replace('|', ' ... ', implode('|', $totalWrap))));
			}
			if ($menu_normal['bulletwrap'])	{
				$outputString .= sprintf($GLOBALS['LANG']->getLL('newsitewizard_menudiv', 1), htmlspecialchars($menu_normal['bulletwrap']));
			}
			if ($GMENU)	{
				$outputString .= $GLOBALS['LANG']->getLL('newsitewizard_menuimg', 1);
			}
			if ($mouseOver)	{
				$outputString .= $GLOBALS['LANG']->getLL('newsitewizard_menumouseover', 1);
			}

			$outputString .= '<br/><br/>';
			$outputString .= $GLOBALS['LANG']->getLL('newsitewizard_menuts', 1) . '
			<br/><br/>';
			$outputString.='<hr/>'.$this->syntaxHLTypoScript($typoScript).'<hr/><br/>';


			$outputString .= $GLOBALS['LANG']->getLL('newsitewizard_menufinetune', 1);
			$outputString .= '<textarea name="CFG[menuCode]"'.$GLOBALS['TBE_TEMPLATE']->formWidthText().' rows="10">'.t3lib_div::formatForTextarea($typoScript).'</textarea><br/><br/>';
			$outputString .= '<input type="hidden" name="SET[wiz_step]" value="'.$menuTypeNextStep.'" />';
			$outputString .= '<input type="submit" name="_" value="' . sprintf($GLOBALS['LANG']->getLL('newsitewizard_menuwritets', 1), $menuTypeText) . '" />';
		} else {
			$outputString.= sprintf($GLOBALS['LANG']->getLL('newsitewizard_menufinished', 1), $menuTypeText) . '<br />';
			$outputString.='<input type="hidden" name="SET[wiz_step]" value="'.$menuTypeNextStep.'" />';
			$outputString.='<input type="submit" name="_" value="' . $GLOBALS['LANG']->getLL('newsitewizard_menunext', 1) . '" />';
		}

			// Add output:
		$this->content.= $this->doc->section(sprintf($GLOBALS['LANG']->getLL('newsitewizard_step5', 1), $menuTypeLetter), $outputString, 0, 1);

	}

	/**
	 * Step 6: Done.
	 *
	 * @return	void
	 */
	function wizard_step6()	{

		$this->saveMenuCode();


		$outputString.= $GLOBALS['LANG']->getLL('newsitewizard_sitecreated') . '

		<br/>
		<br/>
		<input type="submit" value="' . $GLOBALS['LANG']->getLL('newsitewizard_finish', 1) . '" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($this->wizardData['rootPageId'],$this->doc->backPath).'document.location=\'index.php?SET[wiz_step]=0\'; return false;').'" />
		';

			// Add output:
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('newsitewizard_done', 1), $outputString, 0, 1);
	}

	/**
	 * Initialize the import-engine
	 *
	 * @return	object		Returns object ready to import the import-file used to create the basic site!
	 */
	function getImportObj()	{
		global $TYPO3_CONF_VARS;

		require_once(t3lib_extMgm::extPath('impexp').'class.tx_impexp.php');

		$import = t3lib_div::makeInstance('tx_impexp');
		$import->init(0,'import');
		$import->enableLogging = TRUE;

		return $import;
	}

	/**
	 * Syntax Highlighting of TypoScript code
	 *
	 * @param	string		String of TypoScript code
	 * @return	string		HTML content with it highlighted.
	 */
	function syntaxHLTypoScript($v)	{
		require_once(PATH_t3lib.'class.t3lib_tsparser_ext.php');

		$tsparser = t3lib_div::makeInstance('t3lib_TSparser');
		$tsparser->lineNumberOffset=0;
		$TScontent = $tsparser->doSyntaxHighlight(trim($v).chr(10),'',1);

		return $TScontent;
	}

	/**
	 * Produce WRAP value
	 *
	 * @param	array		menuItemSuggestion configuration
	 * @return	string		Wrap for TypoScript
	 */
	function makeWrap($cfg)	{
		if (!$cfg['bulletwrap'])	{
			$wrap = $cfg['wrap'];
		} else {
			$wrap = $cfg['wrap'].'  |*|  '.$cfg['bulletwrap'].$cfg['wrap'];
		}

		return preg_replace('/['.chr(10).chr(13).chr(9).']/','',$wrap);
	}

	/**
	 * Returns the code that the menu was mapped to in the HTML
	 *
	 * @param	string		"Field" from Data structure, either "field_menu" or "field_submenu"
	 * @return	string
	 */
	function getMenuDefaultCode($field)	{
			// Select template record and extract menu HTML content
		$toRec = t3lib_BEfunc::getRecordWSOL('tx_templavoila_tmplobj',$this->wizardData['templateObjectId']);
		$tMapping = unserialize($toRec['templatemapping']);
		return $tMapping['MappingData_cached']['cArray'][$field];
	}

	/**
	 * Saves the menu TypoScript code
	 *
	 * @return	void
	 */
	function saveMenuCode()	{

			// Save menu code to template record:
		$cfg = t3lib_div::_POST('CFG');
		if (isset($cfg['menuCode']))	{

				// Get template record:
			$TSrecord = t3lib_BEfunc::getRecord('sys_template',$this->wizardData['typoScriptTemplateID']);
			if (is_array($TSrecord))	{
				$data['sys_template'][$TSrecord['uid']]['config'] = '

## Menu [Begin]
'.trim($cfg['menuCode']).'
## Menu [End]



'.$TSrecord['config'];

					// Execute changes:
				global $TYPO3_CONF_VARS;

				require_once(PATH_t3lib.'class.t3lib_tcemain.php');
				$tce = t3lib_div::makeInstance('t3lib_TCEmain');
				$tce->stripslashes_values = 0;
				$tce->dontProcessTransformations = 1;
				$tce->start($data,Array());
				$tce->process_datamap();
			}
		}
	}

	/**
	 * Tries to fetch the background color of a GIF or PNG image.
	 *
	 * @param	string		Filepath (absolute) of the image (must exist)
	 * @return	string		HTML hex color code, if any.
	 */
	function getBackgroundColor($filePath)	{

		if (substr($filePath,-4)=='.gif' && function_exists('imagecreatefromgif'))	{
			$im = @imagecreatefromgif($filePath);
		} elseif (substr($filePath,-4)=='.png' && function_exists('imagecreatefrompng'))	{
			$im = @imagecreatefrompng($filePath);
		}

		if ($im)	{
			$values = imagecolorsforindex($im, imagecolorat($im, 3, 3));
			$color = '#'.substr('00'.dechex($values['red']),-2).
						substr('00'.dechex($values['green']),-2).
						substr('00'.dechex($values['blue']),-2);
			return $color;
		}
		return false;
	}

	/**
	 * Find and check all template paths
	 *
	 * @param	boolean		if true returned paths are relative
	 * @param	boolean		if true the patchs are checked
	 * @return	array		all relevant template paths
	 */
	protected function getTemplatePaths($relative = false, $check = true) {
		$templatePaths = array();
		if (strlen($this->modTSconfig['properties']['templatePath'])) {
			$paths = t3lib_div::trimExplode(',', $this->modTSconfig['properties']['templatePath'], true);
		} else {
			$paths = array ('templates');
		}

		$prefix = t3lib_div::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']);
		if (count($paths) > 0 && is_array($GLOBALS['FILEMOUNTS']))	{
			foreach($GLOBALS['FILEMOUNTS'] as $mountCfg)	{
					// look in paths if it's part of mounted path
				$isPart = false;
				foreach ($paths as $path) {
					if (t3lib_div::isFirstPartOfStr($prefix . $path, $mountCfg['path']) &&
						is_dir($prefix . $path)) {
						$templatePaths[] = ($relative ? $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] : $prefix) . $path;
					} else if (!$check) {
						$templatePaths[] = ($relative ? $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] : $prefix) . $path;
					}
				}
			}
		}
		return $templatePaths;
	}

	/**
	 * Find and check all templates within the template paths
	 *
	 * @return	array		all relevant templates
	 */
	protected function getTemplateFiles() {
		$paths = $this->getTemplatePaths();
		$files = array();
		foreach ($paths as $path) {
			$files = array_merge(t3lib_div::getAllFilesAndFoldersInPath(array(), $prefix . $path . ((substr($path, -1) != '/') ? '/' : ''), 'html,htm,tmpl', 0), $files);
		}
		return $files;
	}
}

if (!function_exists('md5_file')) {
	function md5_file($file, $raw = false) {
		return md5(file_get_contents($file), $raw);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod2/index.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod2/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('tx_templavoila_module2');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
