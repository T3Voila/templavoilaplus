<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003-2004 Kasper Skårhøj <kasper@typo3.com>
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
 * @author   Kasper Skårhøj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  100: class tx_templavoila_module2 extends t3lib_SCbase
 *  124:     function menuConfig()
 *  145:     function main()
 *  204:     function printContent()
 *
 *              SECTION: Rendering module content:
 *  228:     function renderModuleContent()
 *  265:     function renderModuleContent_searchForTODS()
 *  326:     function renderModuleContent_mainView()
 *  456:     function renderDSlisting($dsScopeArray, &$toRecords)
 *  534:     function renderDataStructureDisplay($dsR, $toIdArray, $scope)
 *  653:     function renderTODisplay($toObj, &$toRecords, $scope, $children=0)
 *  826:     function findRecordsWhereTOUsed($toObj,$scope)
 *  941:     function findDSUsageWithImproperTOs($dsID, $toIdArray, $scope)
 * 1058:     function findRecordsWhereUsed_pid($pid)
 * 1074:     function completeTemplateFileList()
 * 1172:     function setErrorLog($scope,$type,$HTML)
 * 1183:     function getErrorLog($scope)
 *
 *              SECTION: Wizard for new site
 * 1229:     function renderNewSiteWizard_overview()
 * 1290:     function renderNewSiteWizard_run()
 * 1339:     function wizard_checkMissingExtensions()
 * 1375:     function wizard_checkConfiguration()
 * 1396:     function wizard_checkDirectory()
 * 1415:     function wizard_step1()
 * 1477:     function wizard_step2()
 * 1526:     function wizard_step3()
 * 1632:     function wizard_step4()
 * 1654:     function wizard_step5($menuField)
 * 1889:     function wizard_step6()
 * 1910:     function getImportObj()
 * 1928:     function syntaxHLTypoScript($v)
 * 1946:     function makeWrap($cfg)
 * 1960:     function getMenuDefaultCode($field)
 * 1972:     function saveMenuCode()
 * 2012:     function getBackgroundColor($filePath)
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
$LANG->includeLLFile('EXT:templavoila/mod2/locallang.php');
require_once (PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);    // This checks permissions and exits if the users has no permission for entry.





/**
 * Module 'TemplaVoila' for the 'templavoila' extension.
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package		TYPO3
 * @subpackage	tx_templavoila
 */
class tx_templavoila_module2 extends t3lib_SCbase {

		// External static:
	var $templatesDir = 'fileadmin/templates/';
	var $importPageUid = 0;	// Import as first page in root!


	var $wizardData = array();	// Session data during wizard

	var $pageinfo;
	var $modTSconfig;
	var $extKey = 'templavoila';			// Extension key of this module

	var $tFileList=array();
	var $errorsWarnings=array();




	/**
	 * Preparing menu content
	 *
	 * @return	void
	 */
	function menuConfig()	{
		global $LANG;

		$this->MOD_MENU = array(
#			'set_showDSxml' => '',
			'set_details' => '',
			'wiz_step' => ''
		);

			// page/be_user TSconfig settings and blinding of menu-items
		$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->id,'mod.'.$this->MCONF['name']);

			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::GPvar('SET'), $this->MCONF['name']);
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

		if ($access)    {

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
			').$this->doc->getDynTabMenuJScode();

				// Setting up support for context menus (when clicking the items icon)
			$CMparts = $this->doc->getContextMenuCode();
			$this->doc->bodyTagAdditions = $CMparts[1];
			$this->doc->JScode.= $CMparts[0];
			$this->doc->postCode.= $CMparts[2];

			$this->content.=$this->doc->startPage($LANG->getLL('title'));

				// Rendering module content
			$this->renderModuleContent();

			if ($BE_USER->mayMakeShortcut()) {
				$this->content.='<br /><br />'.$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']);
			}
		} else {	// No access or no current uid:

				// Draw the header.
			$this->doc = t3lib_div::makeInstance('noDoc');
			$this->doc->docType= 'xhtml_trans';
			$this->doc->backPath = $BACK_PATH;
			$this->doc->divClass = '';
			$this->doc->form='<form action="'.htmlspecialchars('index.php?id='.$this->id).'" method="post" autocomplete="off">';
			$this->content.=$this->doc->startPage($LANG->getLL('title'));
		}
		$this->content.=$this->doc->endPage();
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent()    {
		echo $this->content;
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

				// Select all Template Records in PID:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'count(*)',
						'tx_templavoila_tmplobj',
						'pid='.intval($this->id).t3lib_BEfunc::deleteClause('tx_templavoila_tmplobj')
					);
			list($countTO) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

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

			// Select all Data Structures in the PID and put into an array:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'pid,count(*)',
					'tx_templavoila_datastructure',
					'1'.t3lib_BEfunc::deleteClause('tx_templavoila_datastructure'),
					'pid'
				);
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$list[$row['pid']]['DS'] = $row['count(*)'];
		}

			// Select all Template Records in PID:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'pid,count(*)',
					'tx_templavoila_tmplobj',
					'1'.t3lib_BEfunc::deleteClause('tx_templavoila_tmplobj'),
					'pid'
				);
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$list[$row['pid']]['TO'] = $row['count(*)'];
		}


			// Traverse the pages found and list in a table:
		$tRows = array();
		$tRows[] = '
			<tr class="bgColor5 tableheader">
				<td>Page header</td>
				<td>Data Structures:</td>
				<td>Template Objects:</td>
			</tr>';

		if (is_array($list))	{
			foreach($list as $pid => $stat)	{
				$path = $this->findRecordsWhereUsed_pid($pid);
				if ($path)	{
					$tRows[] = '
						<tr class="bgColor4">
							<td><a href="index.php?id='.$pid.'">'.htmlspecialchars($path).'</a></td>
							<td>'.htmlspecialchars($stat['DS']).'</td>
							<td>'.htmlspecialchars($stat['TO']).'</td>
						</tr>';
				}
			}
		}

			// Create overview
		$outputString = '<table border="0" cellpadding="1" cellspacing="1" class="lrPadding">'.implode('',$tRows).'</table>';

			// Add output:
		$this->content.= $this->doc->section($LANG->getLL('title'),$outputString,0,1);
	}

	/**
	 * Renders module content main view:
	 *
	 * @return	void
	 */
	function renderModuleContent_mainView()	{
		global $LANG;

			// Select all Data Structures in the PID and put into an array:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',
					'tx_templavoila_datastructure',
					'pid='.intval($this->id).t3lib_BEfunc::deleteClause('tx_templavoila_datastructure'),
					'',
					'title'
				);
		$dsRecords = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$dsRecords[$row['scope']][] = $row;
		}

			// Select all static Data Structures and add to array:
		if (is_array($GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoila_cm1']['staticDataStructures']))	{
			foreach($GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoila_cm1']['staticDataStructures'] as $staticDS)	{
				$staticDS['_STATIC'] = 1;
				$dsRecords[$staticDS['scope']][] = $staticDS;
			}
		}

			// Select all Template Records in PID:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'cruser_id,crdate,tstamp,uid,title, parent, fileref, sys_language_uid, datastructure, rendertype,localprocessing, previewicon,description,fileref_mtime',
					'tx_templavoila_tmplobj',
					'pid='.intval($this->id).t3lib_BEfunc::deleteClause('tx_templavoila_tmplobj'),
					'',
					'title'
				);
		$toRecords = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$toRecords[$row['parent']][] = $row;
		}

			// Traverse scopes of data structures display template records belonging to them:
			// Each scope is places in its own tab in the tab menu:
		$dsScopes = array_unique(array_merge(array(1,2,0),array_keys($dsRecords)));
		$parts = array();
		foreach($dsScopes as $scopePointer)	{

				// Create listing for a DS:
			list($content,$dsCount,$toCount) = $this->renderDSlisting($dsRecords[$scopePointer],$toRecords,$scopePointer);
			$scopeIcon = '';

				// Label for the tab:
			switch((string)$scopePointer)	{
				case '1':
					$label = 'Page templates';
					$scopeIcon = t3lib_iconWorks::getIconImage('pages',array(),$this->doc->backPath,'class="absmiddle"');
				break;
				case '2':
					$label = 'Flexible CE';
					$scopeIcon = t3lib_iconWorks::getIconImage('tt_content',array(),$this->doc->backPath,'class="absmiddle"');
				break;
				case '0':
					$label = 'Other';
				break;
				default:
					$label = 'Unknown "'.$scopePointer.'"';
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
		foreach($toRecords as $TOcategories)	{
			foreach($TOcategories as $toObj)	{
				$rTODres = $this->renderTODisplay($toObj, $toRecords, 1);
				$lostTOs.= $rTODres['HTML'];
				$lostTOCount++;
			}
		}
		if ($lostTOs) {
				// Add parts for Tab menu:
			$parts[] = array(
				'label' => 'Lost TOs ['.$lostTOCount.']',
				'content' => $lostTOs
			);
		}

			// Complete Template File List
		$parts[] = array(
			'label' => 'Template Files',
			'content' => $this->completeTemplateFileList()
		);

			// Errors:
		if ($errStat = $this->getErrorLog('_ALL'))	{
			$parts[] = array(
				'label' => 'Errors ('.$errStat['count'].')',
				'content' => $errStat['content'],
				'stateIcon' => $errStat['iconCode']
			);
		}

			// Create setting handlers:
		$settings = '<p>'.
				t3lib_BEfunc::getFuncCheck('','SET[set_details]',$this->MOD_SETTINGS['set_details'],'',t3lib_div::implodeArrayForUrl('',$GLOBALS['HTTP_GET_VARS'],'',1,1)).' Show Details &nbsp;&nbsp;&nbsp;'.
				#t3lib_BEfunc::getFuncCheck('','SET[set_showDSxml]',$this->MOD_SETTINGS['set_showDSxml'],'',t3lib_div::implodeArrayForUrl('',$GLOBALS['HTTP_GET_VARS'],'',1,1)).' Show DS XML'.
			'</p>';

			// Add output:
		$this->content.=$this->doc->section($LANG->getLL('title'),
			$settings.
			$this->doc->getDynTabMenu($parts,'TEMPLAVOILA:templateModule:'.$this->id, 0,0,300)
		,0,1);
	}

	/**
	 * Renders Data Structures from $dsScopeArray
	 *
	 * @param	array		Data Structures in a numeric array
	 * @param	array		Array of template objects (passed by reference).
	 * @return	array		Returns array with three elements: 0: content, 1: number of DS shown, 2: number of root-level template objects shown.
	 */
	function renderDSlisting($dsScopeArray, &$toRecords,$scope)	{
		$dsCount=0;
		$toCount=0;
		$content='';
		$index='';

			// Traverse data structures to list:
		if (is_array($dsScopeArray))	{
			foreach($dsScopeArray as $dsR)	{

					// Set relation ID of data structure used by template objects:
				$dsID = $dsR['_STATIC'] ? $dsR['path'] : $dsR['uid'];

					// Traverse template objects which are not children of anything:
				$TOcontent = '';
				$indexTO = '';
				$toIdArray = array(-1);
				if (is_array($toRecords[0]))	{
					$newPid = $dsR['pid'];
					$newFileRef = '';
					$newTitle = $dsR['title'].' [TEMPLATE]';
					foreach($toRecords[0] as $toIndex => $toObj)	{
						if (!strcmp($toObj['datastructure'], $dsID))	{	// If the relation ID matches, render the template object:
							$rTODres = $this->renderTODisplay($toObj, $toRecords, $scope);
							$TOcontent.= '<a name="to-'.$toObj['uid'].'"></a>'.$rTODres['HTML'];
							$indexTO.='
								<tr class="bgColor4">
									<td>&nbsp;&nbsp;&nbsp;</td>
									<td><a href="#to-'.$toObj['uid'].'">'.htmlspecialchars($toObj['title']).'</a></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td align="center">'.$rTODres['mappingStatus'].'</td>
									<td align="center">'.$rTODres['usage'].'</td>
								</tr>';
							$toCount++;
								// Unset it so we can eventually see what is left:
							unset($toRecords[0][$toIndex]);

							$newPid=-$toObj['uid'];
							$newFileRef = $toObj['fileref'];
							$newTitle = $toObj['title'].' [ALT]';
							$toIdArray[] = $toObj['uid'];
						}
					}

						// New-TO link:
					$TOcontent.= '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick(
								'&edit[tx_templavoila_tmplobj]['.$newPid.']=new'.
								'&defVals[tx_templavoila_tmplobj][datastructure]='.rawurlencode($dsID).
								'&defVals[tx_templavoila_tmplobj][title]='.rawurlencode($newTitle).
								'&defVals[tx_templavoila_tmplobj][fileref]='.rawurlencode($newFileRef)
								,$this->doc->backPath)).'"><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/new_el.gif','width="11" height="12"').' alt="" class="absmiddle" />Create new Template Object</a>';
				}

					// Render data structure display
				$rDSDres = $this->renderDataStructureDisplay($dsR, $toIdArray, $scope);
				$content.= '<a name="ds-'.md5($dsID).'"></a>'.$rDSDres['HTML'];
				$index.='
					<tr class="bgColor4-20">
						<td colspan="2"><a href="#ds-'.md5($dsID).'">'.htmlspecialchars($dsR['title']?$dsR['title']:$dsR['path']).'</a></td>
						<td align="center">'.$rDSDres['languageMode'].'</td>
						<td align="center">'.$rDSDres['container'].'</td>
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
			$content = '<h4>Overview:</h4>
						<table border="0" cellpadding="0" cellspacing="1">
							<tr class="bgColor5 tableheader">
								<td colspan="2">DS/TO Title:</td>
								<td>Localization:</td>
								<td>Container status:</td>
								<td>Mapping status:</td>
								<td>Usage Count:</td>
							</tr>
						'.$index.'
						</table>'.
						$content;
		}

		return array($content,$dsCount,$toCount);
	}

	/**
	 * Rendering a single data structures information
	 *
	 * @param	array		Data Structure information
	 * @param	array		Array with TO found for this ds
	 * @param	integer		Scope.
	 * @return	string		HTML content
	 */
	function renderDataStructureDisplay($dsR, $toIdArray, $scope)	{

		$tableAttribs = ' border="0" cellpadding="1" cellspacing="1" width="98%" style="margin-top: 10px;" class="lrPadding"';

		$XMLinfo = array();
		$dsID = $dsR['_STATIC'] ? $dsR['path'] : $dsR['uid'];

			// If ds was a true record:
		if (!$dsR['_STATIC'])	{
				// Record icon:
				// Put together the records icon including content sensitive menu link wrapped around it:
			$recordIcon = t3lib_iconWorks::getIconImage('tx_templavoila_datastructure',$dsR,$this->doc->backPath,'class="absmiddle"');
			$recordIcon = $this->doc->wrapClickMenuOnIcon($recordIcon, 'tx_templavoila_datastructure', $dsR['uid'], 1, '&callingScriptId='.rawurlencode($this->doc->scriptID));

				// Preview icon:
			if ($dsR['previewicon'])	{
				$icon = '<img src="'.$this->doc->backPath.'../uploads/tx_templavoila/'.$dsR['previewicon'].'" alt="" />';
			} else {
				$icon = '[No icon]';
			}

				// Links:
			$editLink = $lpXML.= '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[tx_templavoila_datastructure]['.$dsR['uid'].']=edit',$this->doc->backPath)).'"><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','width="11" height="12"').' alt="" class="absmiddle" /></a>';
			$dsTitle = '<a href="'.htmlspecialchars('../cm1/index.php?table=tx_templavoila_datastructure&uid='.$dsR['uid'].'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))).'">'.htmlspecialchars($dsR['title']).'</a>';

			if ($this->MOD_SETTINGS['set_details'])	{
				$XMLinfo = $this->DSdetails($dsR['dataprot']);
			}

				// Compile info table:
			$content.='
			<table'.$tableAttribs.'>
				<tr class="bgColor5">
					<td colspan="3" style="border-top: 1px solid black;">'.
						$recordIcon.
						$dsTitle.
						$editLink.
						'</td>
				</tr>
				<tr class="bgColor4">
					<td rowspan="'.($this->MOD_SETTINGS['set_details'] ? 4 : 1).'" style="width: 100px; text-align: center;">'.$icon.'</td>
					<td>XML:</td>
					<td>'.t3lib_div::formatSize(strlen($dsR['dataprot'])).
						($this->MOD_SETTINGS['set_details'] ? '<hr/>'.$XMLinfo['HTML'] : '').
						'</td>
				</tr>'.($this->MOD_SETTINGS['set_details'] ? '
				<tr class="bgColor4">
					<td>Template Status:</td>
					<td>'.$this->findDSUsageWithImproperTOs($dsID, $toIdArray, $scope).'</td>
				</tr>
				<tr class="bgColor4">
					<td>Created:</td>
					<td>'.t3lib_BEfunc::datetime($dsR['crdate']).' by user ['.$dsR['cruser_id'].']</td>
				</tr>
				<tr class="bgColor4">
					<td>Updated:</td>
					<td>'.t3lib_BEfunc::datetime($dsR['tstamp']).'</td>
				</tr>' : '').'
			</table>
			';

				// Format XML if requested (renders VERY VERY slow)
			if ($this->MOD_SETTINGS['set_showDSxml'])	{
				if ($dsR['dataprot'])	{
					require_once(PATH_t3lib.'class.t3lib_syntaxhl.php');
					$hlObj = t3lib_div::makeInstance('t3lib_syntaxhl');
					$content.='<pre>'.str_replace(chr(9),'&nbsp;&nbsp;&nbsp;',$hlObj->highLight_DS($dsR['dataprot'])).'</pre>';
				}
				$lpXML.= '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[tx_templavoila_datastructure]['.$dsR['uid'].']=edit&columnsOnly=dataprot',$this->doc->backPath)).'"><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','width="11" height="12"').' alt="" class="absmiddle" /></a>';
			}
		} else {	// DS was a file:

				// XML file icon:
			$recordIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/fileicons/xml.gif','width="18" height="16"').' alt="" class="absmiddle" />';

				// Preview icon:
			if ($dsR['icon'] && $iconPath = t3lib_div::getFileAbsFileName($dsR['icon']))	{
				$icon = '<img src="'.$this->doc->backPath.'../'.substr($iconPath,strlen(PATH_site)).'" alt="" />';
			} else {
				$icon = '[No icon]';
			}

			$fileReference = t3lib_div::getFileAbsFileName($dsR['path']);
			if (@is_file($fileReference))	{
				$fileRef = '<a href="'.htmlspecialchars($this->doc->backPath.'../'.substr($fileReference,strlen(PATH_site))).'" target="_blank">'.
							htmlspecialchars($dsR['path']).
							'</a>';

				if ($this->MOD_SETTINGS['set_details'])	{
					$XMLinfo = $this->DSdetails(t3lib_div::getUrl($fileReference));
				}
			} else {
				$fileRef = htmlspecialchars($dsR['path']).' [File not found!]';
			}

				// Compile table:
			$content.='
			<table'.$tableAttribs.'>
				<tr class="bgColor2">
					<td colspan="3" style="border-top: 1px solid black;">'.
						$recordIcon.
						htmlspecialchars($dsR['title']).
						'</td>
				</tr>
				<tr class="bgColor4">
					<td rowspan="'.($this->MOD_SETTINGS['set_details'] ? 2 : 1).'" style="width: 100px; text-align: center;">'.$icon.'</td>
					<td>XML file:</td>
					<td>'.$fileRef.
						($this->MOD_SETTINGS['set_details'] ? '<hr/>'.$XMLinfo['HTML'] : '').'</td>
				</tr>'.($this->MOD_SETTINGS['set_details'] ? '
				<tr class="bgColor4">
					<td>Template Status:</td>
					<td>'.$this->findDSUsageWithImproperTOs($dsID, $toIdArray, $scope).'</td>
				</tr>' : '').'
			</table>
			';
		}

		if ($this->MOD_SETTINGS['set_details'])	{
			if ($XMLinfo['referenceFields'])	{
				$containerMode = 'Yes';
				if ($XMLinfo['languageMode']==='Separate')	{
					$containerMode.= ' '.$this->doc->icons(3).'Container element with separate localization!';
				} elseif ($XMLinfo['languageMode']==='Inheritance')	{
					$containerMode.= ' '.$this->doc->icons(2);
					if ($XMLinfo['inputFields'])	{
						$containerMode.= 'Mix of content and references, OK, but be careful!';
					} else {
						$containerMode.= htmlspecialchars('No content fields, recommended to set "<langDisable>" = 1');
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
	function renderTODisplay($toObj, &$toRecords, $scope, $children=0)	{

			// Put together the records icon including content sensitive menu link wrapped around it:
		$recordIcon = t3lib_iconWorks::getIconImage('tx_templavoila_tmplobj',$toObj,$this->doc->backPath,'class="absmiddle"');
		$recordIcon = $this->doc->wrapClickMenuOnIcon($recordIcon, 'tx_templavoila_tmplobj', $toObj['uid'], 1, '&callingScriptId='.rawurlencode($this->doc->scriptID));

			// Preview icon:
		if ($toObj['previewicon'])	{
			$icon = '<img src="'.$this->doc->backPath.'../uploads/tx_templavoila/'.$toObj['previewicon'].'" alt="" />';
		} else {
			$icon = '[No icon]';
		}

			// Mapping status / link:
		$linkUrl = '../cm1/index.php?table=tx_templavoila_tmplobj&uid='.$toObj['uid'].'&_reload_from=1&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));

		$fileReference = t3lib_div::getFileAbsFileName($toObj['fileref']);
		if (@is_file($fileReference))	{
			$this->tFileList[$fileReference]++;
			$fileRef = '<a href="'.htmlspecialchars($this->doc->backPath.'../'.substr($fileReference,strlen(PATH_site))).'" target="_blank">'.htmlspecialchars($toObj['fileref']).'</a>';
			$fileMsg = '';
			$fileMtime = filemtime($fileReference);
		} else {
			$fileRef = htmlspecialchars($toObj['fileref']);
			$fileMsg = '<div class="typo3-red">ERROR: File not found</div>';
			$fileMtime = 0;
		}

		$mappingStatus = $mappingStatus_index = '';
		if ($fileMtime && $toObj['fileref_mtime'])	{
			if ($toObj['fileref_mtime'] != $fileMtime)	{
				$mappingStatus = $mappingStatus_index = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/icon_warning2.gif','width="18" height="16"').' alt="" class="absmiddle" />';
				$mappingStatus.= 'Template file was updated since last mapping ('.t3lib_BEfunc::datetime($toObj['tstamp']).') and you might need to remap the Template Object!';
				$this->setErrorLog($scope,'warning',$mappingStatus.' (TO: "'.$toObj['title'].'")');
			} else {
				$mappingStatus = $mappingStatus_index = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/icon_ok2.gif','width="18" height="16"').' alt="" class="absmiddle" />';
				$mappingStatus.= 'Mapping Up-to-date.';
			}
			$mappingStatus.='<br/><a href="'.htmlspecialchars($linkUrl).'">[ Update mapping ]</a>';
		} elseif (!$fileref_mtime) {
			$mappingStatus = $mappingStatus_index = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/icon_fatalerror.gif','width="18" height="16"').' alt="" class="absmiddle" />';
			$mappingStatus.= 'Not mapped yet!';
			$this->setErrorLog($scope,'fatal',$mappingStatus.' (TO: "'.$toObj['title'].'")');

			$mappingStatus.=' - <em>(It might also mean that the TO was mapped with an older version of TemplaVoila - then just go and save the mapping again at this will be updated.)</em>';
			$mappingStatus.='<br/><a href="'.htmlspecialchars($linkUrl).'">[ Map ]</a>';
		} else {
			$mappingStatus = '';
			$mappingStatus.='<a href="'.htmlspecialchars($linkUrl).'">[ Remap ]</a>';
			$mappingStatus.='<a href="'.htmlspecialchars($linkUrl.'&_preview=1').'">[ Preview ]</a>';
		}

			// Format XML if requested
		if ($this->MOD_SETTINGS['set_details'])	{
			if ($toObj['localprocessing'])	{
				require_once(PATH_t3lib.'class.t3lib_syntaxhl.php');
				$hlObj = t3lib_div::makeInstance('t3lib_syntaxhl');
				$lpXML = '<pre>'.str_replace(chr(9),'&nbsp;&nbsp;&nbsp;',$hlObj->highLight_DS($toObj['localprocessing'])).'</pre>';
			} else $lpXML = '';
		} else {
			$lpXML = $toObj['localprocessing'] ? t3lib_div::formatSize(strlen($toObj['localprocessing'])).'bytes' : '';
		}
		$lpXML.= '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[tx_templavoila_tmplobj]['.$toObj['uid'].']=edit&columnsOnly=localprocessing',$this->doc->backPath)).'"><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','width="11" height="12"').' alt="" class="absmiddle" /></a>';

			// Compile info table:
		$tableAttribs = ' border="0" cellpadding="1" cellspacing="1" width="98%" style="margin-top: 3px;" class="lrPadding"';

			// Links:
		$editLink = '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[tx_templavoila_tmplobj]['.$toObj['uid'].']=edit',$this->doc->backPath)).'"><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','width="11" height="12"').' alt="" class="absmiddle" /></a>';
		$toTitle = '<a href="'.htmlspecialchars($linkUrl).'">'.htmlspecialchars($toObj['title']).'</a>';

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
					<td>File reference:</td>
					<td>'.$fileRef.$fileMsg.'</td>
				</tr>
				<tr class="bgColor4">
					<td>Description:</td>
					<td>'.htmlspecialchars($toObj['description']).'</td>
				</tr>
				<tr class="bgColor4">
					<td>Mapping status:</td>
					<td>'.$mappingStatus.'</td>
				</tr>
				<tr class="bgColor4">
					<td>Local Processing:</td>
					<td>'.$lpXML.'</td>
				</tr>'.($this->MOD_SETTINGS['set_details'] ? '
				<tr class="bgColor4">
					<td>Used by:</td>
					<td>'.$fRWTOUres['HTML'].'</td>
				</tr>
				<tr class="bgColor4">
					<td>Created:</td>
					<td>'.t3lib_BEfunc::datetime($toObj['crdate']).' by user ['.$toObj['cruser_id'].']</td>
				</tr>
				<tr class="bgColor4">
					<td>Updated:</td>
					<td>'.t3lib_BEfunc::datetime($toObj['tstamp']).'</td>
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
					<td>File reference:</td>
					<td>'.$fileRef.$fileMsg.'</td>
				</tr>
				<tr class="bgColor4">
					<td>Mapping status:</td>
					<td>'.$mappingStatus.'</td>
				</tr>
				<tr class="bgColor4">
					<td>Render Type:</td>
					<td>'.t3lib_BEfunc::getProcessedValue('tx_templavoila_tmplobj','rendertype',$toObj['rendertype']).'</td>
				</tr>
				<tr class="bgColor4">
					<td>Language:</td>
					<td>'.t3lib_BEfunc::getProcessedValue('tx_templavoila_tmplobj','sys_language_uid',$toObj['sys_language_uid']).'</td>
				</tr>
				<tr class="bgColor4">
					<td>Local Processing:</td>
					<td>'.$lpXML.'</td>
				</tr>'.($this->MOD_SETTINGS['set_details'] ? '
				<tr class="bgColor4">
					<td>Created:</td>
					<td>'.t3lib_BEfunc::datetime($toObj['crdate']).' by user ['.$toObj['cruser_id'].']</td>
				</tr>
				<tr class="bgColor4">
					<td>Updated:</td>
					<td>'.t3lib_BEfunc::datetime($toObj['tstamp']).'</td>
				</tr>' : '').'
			</table>
			';
		}

			// Traverse template objects which are not children of anything:
		if (!$children && is_array($toRecords[$toObj['uid']]))	{
			$TOchildrenContent = '';
			foreach($toRecords[$toObj['uid']] as $toIndex => $childToObj)	{
				$rTODres = $this->renderTODisplay($childToObj, $toRecords, $scope, 1);
				$TOchildrenContent.= $rTODres['HTML'];

					// Unset it so we can eventually see what is left:
				unset($toRecords[$toObj['uid']][$toIndex]);
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
								<td>Page ID:</td>
								<td>Title:</td>
								<td>Path:</td>
								<td>Workspace:</td>
							</tr>';

					// Main templates:
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'uid,title,pid,t3ver_wsid,t3ver_id',
					'pages',
					'(
						(tx_templavoila_to='.intval($toObj['uid']).' AND tx_templavoila_ds="'.$GLOBALS['TYPO3_DB']->quoteStr($toObj['datastructure'],'pages').'") OR
						(tx_templavoila_next_to='.intval($toObj['uid']).' AND tx_templavoila_next_ds="'.$GLOBALS['TYPO3_DB']->quoteStr($toObj['datastructure'],'pages').'")
					)'.
						t3lib_BEfunc::deleteClause('pages')
				);

				while($pRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
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
								<td><em>No access</em></td>
								<td>-</td>
								<td>-</td>
							</tr>';
					}
				}
			break;
			case 2:

					// Select Flexible Content Elements:
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'uid,header,pid,t3ver_wsid,t3ver_id',
					'tt_content',
					'CType="'.$GLOBALS['TYPO3_DB']->quoteStr('templavoila_pi1','tt_content').'"'.
						' AND tx_templavoila_to='.intval($toObj['uid']).
						' AND tx_templavoila_ds="'.$GLOBALS['TYPO3_DB']->quoteStr($toObj['datastructure'],'tt_content').'"'.
						t3lib_BEfunc::deleteClause('tt_content'),
					'',
					'pid'
				);

					// Header:
				$output[]='
							<tr class="bgColor5 tableheader">
								<td>UID:</td>
								<td>Header:</td>
								<td>Path:</td>
								<td>Workspace:</td>
							</tr>';

					// Elements:
				while($pRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
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
								<td><em>No access</em></td>
								<td>-</td>
								<td>-</td>
							</tr>';
					}
				}
			break;
		}

			// Create final output table:
		if (count($output))	{
			if (count($output)>1)	{
				$outputString = 'Used in '.(count($output)-1).' Elements:<table border="0" cellspacing="1" cellpadding="1" class="lrPadding">'.implode('',$output).'</table>';
			} else {
				$outputString = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/icon_warning2.gif','width="18" height="16"').' alt="" class="absmiddle" />No usage!';
				$this->setErrorLog($scope,'warning',$outputString.' (TO: "'.$toObj['title'].'")');
			}
		}

		return array('HTML' => $outputString, 'usage'=>count($output)-1);
	}

	/**
	 * Creates listings of pages / content elements where NO or WRONG template objects are used.
	 *
	 * @param	array		Data Structure ID
	 * @param	array		Array with numerical toIDs. Must be integers and never be empty. You can always put in "-1" as dummy element.
	 * @param	integer		Scope value. 1) page,  2) content elements
	 * @return	string		HTML table listing usages.
	 */
	function findDSUsageWithImproperTOs($dsID, $toIdArray, $scope)	{

		$output = array();

		switch ($scope)	{
			case 1:	//
					// Header:
				$output[]='
							<tr class="bgColor5 tableheader">
								<td>Title:</td>
								<td>Path:</td>
							</tr>';

					// Main templates:
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'uid,title,pid',
					'pages',
					'(
						(tx_templavoila_to NOT IN ('.implode(',',$toIdArray).') AND tx_templavoila_ds="'.$GLOBALS['TYPO3_DB']->quoteStr($dsID,'pages').'") OR
						(tx_templavoila_next_to NOT IN ('.implode(',',$toIdArray).') AND tx_templavoila_next_ds="'.$GLOBALS['TYPO3_DB']->quoteStr($dsID,'pages').'")
					)'.
						t3lib_BEfunc::deleteClause('pages')
				);

				while($pRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
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
								<td><em>No access</em></td>
								<td>-</td>
							</tr>';
					}
				}
			break;
			case 2:

					// Select Flexible Content Elements:
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'uid,header,pid',
					'tt_content',
					'CType="'.$GLOBALS['TYPO3_DB']->quoteStr('templavoila_pi1','tt_content').'"'.
						' AND tx_templavoila_to NOT IN ('.implode(',',$toIdArray).')'.
						' AND tx_templavoila_ds="'.$GLOBALS['TYPO3_DB']->quoteStr($dsID,'tt_content').'"'.
						t3lib_BEfunc::deleteClause('tt_content'),
					'',
					'pid'
				);

					// Header:
				$output[]='
							<tr class="bgColor5 tableheader">
								<td>Header:</td>
								<td>Path:</td>
							</tr>';

					// Elements:
				while($pRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
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
								<td><em>No access</em></td>
								<td>-</td>
							</tr>';
					}
				}
			break;
		}

			// Create final output table:
		if (count($output))	{
			if (count($output)>1)	{
				$outputString = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/icon_fatalerror.gif','width="18" height="16"').' alt="" class="absmiddle" />'.
								'Invalid template values in '.(count($output)-1).' elements:';
				$this->setErrorLog($scope,'fatal',$outputString);

				$outputString.='<table border="0" cellspacing="1" cellpadding="1" class="lrPadding">'.implode('',$output).'</table>';
			} else {
				$outputString = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/icon_ok2.gif','width="18" height="16"').' alt="" class="absmiddle" />No errors found!';
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
		if (is_array($this->tFileList))	{

			$output='';

				// USED FILES:
			$tRows = array();
			$tRows[] = '
				<tr class="bgColor5 tableheader">
					<td>File</td>
					<td>Usage count:</td>
					<td>New DS/TO?</td>
				</tr>';
			foreach($this->tFileList as $tFile => $count)	{

				$tRows[] = '
					<tr class="bgColor4">
						<td>'.
							'<a href="'.htmlspecialchars($this->doc->backPath.'../'.substr($tFile,strlen(PATH_site))).'" target="_blank">'.
							htmlspecialchars(substr($tFile,strlen(PATH_site))).
							'</a></td>
						<td align="center">'.$count.'</td>
						<td>'.
							'<a href="'.htmlspecialchars('../cm1/index.php?file='.rawurlencode($tFile)).'&mapElPath=%5BROOT%5D">'.
							htmlspecialchars('Create...').
							'</a></td>
					</tr>';
			}

			if (count($tRows)>1)	{
				$output.= '
				<h3>Used files:</h3>
				<table border="0" cellpadding="1" cellspacing="1" class="lrPadding">
					'.implode('',$tRows).'
				</table>
				';
			}

				// TEMPLATE ARCHIVE:
			if ($this->modTSconfig['properties']['templatePath'])	{
				$path = t3lib_div::getFileAbsFileName('fileadmin/'.$this->modTSconfig['properties']['templatePath']);
				if (@is_dir($path) && is_array($GLOBALS['FILEMOUNTS']))	{
					foreach($GLOBALS['FILEMOUNTS'] as $mountCfg)	{
						if (t3lib_div::isFirstPartOfStr($path,$mountCfg['path']))	{

							$files = t3lib_div::getFilesInDir($path,'html,htm,tmpl',1);

								// USED FILES:
							$tRows = array();
							$tRows[] = '
								<tr class="bgColor5 tableheader">
									<td>File</td>
									<td>Usage count:</td>
									<td>New DS/TO?</td>
								</tr>';
							foreach($files as $tFile)	{

								$tRows[] = '
									<tr class="bgColor4">
										<td>'.
											'<a href="'.htmlspecialchars($this->doc->backPath.'../'.substr($tFile,strlen(PATH_site))).'" target="_blank">'.
											htmlspecialchars(substr($tFile,strlen(PATH_site))).
											'</a></td>
										<td align="center">'.($this->tFileList[$tFile]?$this->tFileList[$tFile]:'-').'</td>
										<td>'.
											'<a href="'.htmlspecialchars('../cm1/index.php?file='.rawurlencode($tFile)).'&mapElPath=%5BROOT%5D">'.
											htmlspecialchars('Create...').
											'</a></td>
									</tr>';
							}

							if (count($tRows)>1)	{
								$output.= '
								<h3>Template Archive:</h3>
								<table border="0" cellpadding="1" cellspacing="1" class="lrPadding">
									'.implode('',$tRows).'
								</table>
								';
							}
						}
					}
				}
			}


			return $output;
		}
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
		if (is_array($this->errorsWarnings[$scope]))	{
			$errStat = array();

			if (is_array($this->errorsWarnings[$scope]['warning']))	{
				$errStat['count'] = count($this->errorsWarnings[$scope]['warning']);
				$errStat['content'] = '<h3>Warnings</h3>'.implode('<hr/>',$this->errorsWarnings[$scope]['warning']);
				$errStat['iconCode'] = 2;
			}

			if (is_array($this->errorsWarnings[$scope]['fatal']))	{
				$errStat['count'] = count($this->errorsWarnings[$scope]['fatal']).($errStat['count'] ? '/'.$errStat['count']:'');
				$errStat['content'].= '<h3>Fatal errors</h3>'.implode('<hr/>',$this->errorsWarnings[$scope]['fatal']);
				$errStat['iconCode'] = 3;
			}

			return $errStat;
		}
	}

	/**
	 * Show meta data part of Data Structure
	 */
	function DSdetails($DSstring)	{
		$DScontent = t3lib_div::xml2array($DSstring);

		$inputFields = 0;
		$referenceFields = 0;
		$rootelements = 0;
		if (is_array($DScontent['ROOT']['el']))	{
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
				$rootElementsHTML.='<b>'.$elCfg['tx_templavoila']['title'].'</b>'.t3lib_div::view_array($elCfg);
			}
		}

		$DScontent = array('meta' => $DScontent['meta']);

		$languageMode = '';
		if ($DScontent['meta']['langDisable'])	{
			$languageMode = 'Disabled';
		} elseif ($DScontent['meta']['langChildren']) {
			$languageMode = 'Inheritance';
		} else {
			$languageMode = 'Separate';
		}

		return array(
			'HTML' => t3lib_div::view_array($DScontent).'Language Mode => "'.$languageMode.'"<hr/>
						Root Elements = '.$rootelements.', hereof ref/input fields = '.($referenceFields.'/'.$inputFields).'<hr/>
						'.$rootElementsHTML,
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

		if ($BE_USER->isAdmin())	{

				// Introduction:
			$outputString.= nl2br(htmlspecialchars(trim('
			If you want to start a new website based on the TemplaVoila template engine you can start this wizard which will set up all the boring initial stuff for you.
			You will be taken through these steps:
			- Creation of a new website root, storage folder, sample pages.
			- Creation of the main TemplaVoila template, including mapping of one content area and a main menu.
			- Creation of a backend user and group to manage only that website.

			You should prepare an HTML template before you begin the wizard; simply make a design in HTML and place the HTML file including graphics and stylesheets in a subfolder of "fileadmin/templates/" relative to the websites root directory.
			Tip about menus: If you include a main menu in the template, try to place the whole menu inside a container (like <div>, <table> or <tr>) and encapsulate each menu item in a block tag (like <tr>, <td> or <div>). Use A-tags for the links. If you want different designs for normal and active menu elements, design the first menu item as "Active" and the second (and rest) as "Normal", then the wizard might be able to capture the right configuration.
			Tip about stylesheets: The content elements from TYPO3 will be outputted in regular HTML tags like <p>, <h1> to <h6>, <ol> etc. You will prepare yourself well if your stylesheet in the HTML template provides good styles for these standard elements from the start. Then you will have less finetuning to do later.
			')));

				// Checks:
			$missingExt = $this->wizard_checkMissingExtensions();
			$missingConf = $this->wizard_checkConfiguration();
			$missingDir = $this->wizard_checkDirectory();
			if (!$missingExt && !$missingConf)	{
				$outputString.= '
				<br/>
				<br/>
				<input type="submit" value="Start wizard now!" onclick="'.htmlspecialchars('document.location=\'index.php?SET[wiz_step]=1\'; return false;').'" />';
			} else {
				$outputString.= '
				<br/>
				<br/>
				<i>There are some technical problems you have to solve before you can start the wizard! Please see below for details. Solve these problems first and come back.</i>';

			}

				// Add output:
			$this->content.= $this->doc->section($LANG->getLL('wiz_title'),$outputString,0,1);

				// Missing extension warning:
			if ($missingExt)	{
				$this->content.= $this->doc->section('Missing extension!',$missingExt,0,1,3);
			}

				// Missing configuration warning:
			if ($missingConf)	{
				$this->content.= $this->doc->section('Missing configuration!',$missingConf,0,1,3);
			}

				// Missing directory warning:
			if ($missingDir)	{
				$this->content.= $this->doc->section('Missing directory!',$missingDir,0,1,3);
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

			$outputString.= '<hr/><input type="submit" value="Cancel wizard" onclick="'.htmlspecialchars('document.location=\'index.php?SET[wiz_step]=0\'; return false;').'" />';

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

		$outputString.='Before the wizard can run some extensions are required to be installed. Below you will see the which extensions are required and which are not available at this moment. Please go to the Extension Manager and install these first.';

			// Create extension status:
		$checkExtensions = explode(',','css_styled_content,impexp');
		$missingExtensions = FALSE;

		$tRows = array();
		$tRows[] = '<tr class="tableheader bgColor5">
			<td>Extension Key:</td>
			<td>Installed?</td>
		</tr>';

		foreach($checkExtensions as $extKey)	{
			$tRows[] = '<tr class="bgColor4">
				<td>'.$extKey.'</td>
				<td align="center">'.(t3lib_extMgm::isLoaded($extKey) ? 'Yes' : '<span class="typo3-red">No!</span>').'</td>
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

		if (!$TVconfig['enable.']['pageTemplateSelector'])	{
			return
				nl2br('You must enable the page template selector for TemplaVoila! In order to do so, follow these directions:

			- Go to the Extensions Manager
			- Click the title of the extension "TemplaVoila!" in the section "Loaded extensions"
			- In the configuration form, select the checkbox named "Enable Page Template Selector"
			- Return to this wizard
			');
		}
	}

	/**
	 * Pre-checking for directory of extensions.
	 *
	 * @return	string		If string is returned, an error occured.
	 */
	function wizard_checkDirectory()	{

		if (!@is_dir(PATH_site.$this->templatesDir))	{
			return
				nl2br('The directory "'.$this->templatesDir.'" (relative to the website root) does not exist! This is where you must place your HTML templates. Please create that directory now. In order to do so, follow these directions:

			- Go to the module File > Filelist
			- Click the icon of the "fileadmin/" root and select "Create" from the context menu.
			- Enter the name "templates" of the folder and press the "Create" button.
			- Return to this wizard
			');
		}
	}

	/**
	 * Wizard Step 1: Selecting template file.
	 *
	 * @return	void
	 */
	function wizard_step1()	{

		$this->wizardData = array();

		$outputString.=nl2br('The first step is to select the HTML file you want to base the new website design on. Below you see a list of HTML files found in the folder "'.$this->templatesDir.'". Click the "Preview"-link to see what the file looks like and when the right template is found, just click the "Choose as template"-link in order to proceed.
			If the list of files is empty you must now copy the HTML file you want to use as a template into the template folder. When you have done that, press the refresh button to refresh the list.
<br/>');

		if (@is_dir(PATH_site.$this->templatesDir))	{

				// Get all HTML files:
			$fileArr = t3lib_div::getAllFilesAndFoldersInPath(array(),PATH_site.$this->templatesDir,'html,htm',0,1);
			$fileArr = t3lib_div::removePrefixPathFromList($fileArr,PATH_site);

				// Prepare header:
			$tRows = array();
			$tRows[] = '<tr class="tableheader bgColor5">
				<td>Path:</td>
				<td>Usage:</td>
				<td>Action:</td>
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
					<td>'.htmlspecialchars($file).'</td>
					<td>'.(count($tosForTemplate) ? 'Used '.count($tosForTemplate).' times' : 'Not used yet').'</td>
					<td>'.
						'<a href="#" onclick="'.htmlspecialchars($onClick).'">[Preview first]</a> '.
						'<a href="'.htmlspecialchars('index.php?SET[wiz_step]=2&CFG[file]='.rawurlencode($file)).'">[Choose as Template]</a> '.
						'</td>
				</tr>';
			}
			$outputString.= '<table border="0" cellpadding="1" cellspacing="1" class="lrPadding">'.implode('',$tRows).'</table>';

				// Refresh button:
			$outputString.= '<br/><input type="submit" value="Refresh" onclick="'.htmlspecialchars('document.location=\'index.php?SET[wiz_step]=1\'; return false;').'" />';

				// Add output:
			$this->content.= $this->doc->section('Step 1: Select the template HTML file',$outputString,0,1);

		} else die(PATH_site.$this->templatesDir.' was no dir!');
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
			$outputString.= nl2br('The template file "'.htmlspecialchars($this->wizardData['file']).'" is now selected: ');
			$outputString.= '<br/><iframe src="'.htmlspecialchars($this->doc->backPath.'../'.$this->wizardData['file']).'" width="640" height="300"></iframe>';

				// Enter default data:
			$outputString.='
				<br/><br/><br/>
				Next, you should enter default values for the new website. With this basic set of information we are ready to create the initial website structure!<br/>
	<br/>
				<b>Name of the site:</b><br/>
				(Required)<br/>
				This value is shown in the browsers title bar and will be the default name of the first page in the page tree.<br/>
				<input type="text" name="CFG[sitetitle]" value="'.htmlspecialchars($this->wizardData['sitetitle']).'" /><br/>
	<br/>
				<b>URL of the website:</b><br/>
				(Optional)<br/>
				If you know the URL of the website already please enter it here, eg. "www.mydomain.com".<br/>
				<input type="text" name="CFG[siteurl]" value="'.htmlspecialchars($this->wizardData['siteurl']).'" /><br/>
	<br/>
				<b>Editor username</b><br/>
				(Required)<br/>
				Enter the username of a new backend user/group who will be able to edit the pages on the new website. (Password will be "password" by default, make sure to change that!)<br/>
				<input type="text" name="CFG[username]" value="'.htmlspecialchars($this->wizardData['username']).'" /><br/>
	<br/>
				<input type="hidden" name="SET[wiz_step]" value="3" />
				<input type="submit" name="_create_site" value="Create new site" />
			';
		} else {
			$outputString.= 'No template file found!?';
		}

			// Add output:
		$this->content.= $this->doc->section('Step 2: Enter default values for new site',$outputString,0,1);
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
				$inFile = t3lib_extMgm::extPath('templavoila').'mod2/new_tv_site.xml';
				if (@is_file($inFile) && $import->loadFile($inFile,1))	{

					$import->importData($this->importPageUid);

						// Update various fields (the index values, eg. the "1" in "$import->import_mapId['pages'][1]]..." are the UIDs of the original records from the import file!)
					$data = array();
					$data['pages'][$import->import_mapId['pages'][1]]['title'] = $this->wizardData['sitetitle'];
					$data['sys_template'][$import->import_mapId['sys_template'][1]]['title'] = 'Main template: '.$this->wizardData['sitetitle'];
					$data['sys_template'][$import->import_mapId['sys_template'][1]]['sitetitle'] = $this->wizardData['sitetitle'];
					$data['tx_templavoila_tmplobj'][$import->import_mapId['tx_templavoila_tmplobj'][1]]['fileref'] = $this->wizardData['file'];
					$data['tx_templavoila_tmplobj'][$import->import_mapId['tx_templavoila_tmplobj'][1]]['templatemapping'] = serialize(
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
					$data['be_users'][$import->import_mapId['be_users'][2]]['username'] = $this->wizardData['username'];
					$data['be_groups'][$import->import_mapId['be_groups'][1]]['title'] = $this->wizardData['username'];

					foreach($import->import_mapId['pages'] as $newID)	{
						$data['pages'][$newID]['perms_userid'] = $import->import_mapId['be_users'][2];
						$data['pages'][$newID]['perms_groupid'] = $import->import_mapId['be_groups'][1];
					}

						// Set URL if applicable:
					if (strlen($this->wizardData['siteurl']))	{
						$data['sys_domain']['NEW']['pid'] = $import->import_mapId['pages'][1];
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
					$this->wizardData['templateObjectId'] = $import->import_mapId['tx_templavoila_tmplobj'][1];
					$this->wizardData['typoScriptTemplateID'] = $import->import_mapId['sys_template'][1];

					t3lib_BEfunc::getSetUpdateSignal('updatePageTree');

					$outputString.= 'New site has been created and adapted. <hr/>';
				}
			} else {
				$outputString.= 'Error happened: Either you did not specify a website name or username in the previous form!';
			}
		}

			// If a template Object id was found, continue with mapping:
		if ($this->wizardData['templateObjectId'])	{
			$url = '../cm1/index.php?table=tx_templavoila_tmplobj&uid='.$this->wizardData['templateObjectId'].'&SET[selectHeaderContent]=0&_reload_from=1&returnUrl='.rawurlencode('../mod2/index.php?SET[wiz_step]=4');

			$outputString.= '
				You are now ready to point out at which position in the HTML code to insert the TYPO3 generated page content and the main menu. This process is called "mapping".<br/>
				The process of mapping is shown with this little animation. Please study it closely to understand the flow, then click the button below to start the mapping process on your own. Complete the mapping process by pressing "Save and Return".<br/>
				<br/>
				<img src="mapbody_animation.gif" style="border: 2px black solid;" alt=""><br/>
				<br/>
				<br/><input type="submit" value="Start the mapping process" onclick="'.htmlspecialchars('document.location=\''.$url.'\'; return false;').'" />
			';
		}

			// Add output:
		$this->content.= $this->doc->section('Step 3: Begin mapping',$outputString,0,1);
	}

	/**
	 * Step 4: Select HTML header parts.
	 *
	 * @return	void
	 */
	function wizard_step4()	{
		$url = '../cm1/index.php?table=tx_templavoila_tmplobj&uid='.$this->wizardData['templateObjectId'].'&SET[selectHeaderContent]=1&_reload_from=1&returnUrl='.rawurlencode('../mod2/index.php?SET[wiz_step]=5');
		$outputString.= '
			Finally you also have to select which parts of the HTML header you want to include. For instance it is important that you select all sections with CSS styles in order to preserve the correct visual appearance of your website.<br/>
			You can also select the body-tag of the template if you want to use the original body-tag.<br/>
			This animations shows an example of this process:
			<br/>
			<img src="maphead_animation.gif" style="border: 2px black solid;" alt=""><br/>
			<br/>
			<br/><input type="submit" value="Select HTML header parts" onclick="'.htmlspecialchars('document.location=\''.$url.'\'; return false;').'" />
			';

			// Add output:
		$this->content.= $this->doc->section('Step 4: Select HTML header parts',$outputString,0,1);
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
			$outputString.= '
				The basics of your website should be working now. However the '.$menuTypeText.' still needs to be configured so that TYPO3 automatically generates a menu reflecting the pages in the page tree. This process involves configuration of the TypoScript object path, "lib.'.$menuType.'". This is a technical job which requires that you know about TypoScript if you want it 100% customized.<br/>
				To assist you getting started with the '.$menuTypeText.' this wizard will try to analyse the menu found inside the template file. If the menu was created of a series of repetitive block tags containing A-tags then there is a good chance this will succeed. You can see the result below.
			';

				// Start up HTML parser:
			global $TYPO3_CONF_VARS;
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
						if (eregi('^<img[^>]*>$',$linkContent))	{
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
						$newValue['wrap'] = ereg_replace('['.chr(10).chr(13).']*','',implode('',$linkTag));

						$md5Base = $newValue;
						unset($md5Base['I-width']);
						unset($md5Base['I-height']);
						$md5Base = serialize($md5Base);
						$md5Base = ereg_replace('name=["\'][^"\']*["\']','',$md5Base);
						$md5Base = ereg_replace('id=["\'][^"\']*["\']','',$md5Base);
						$md5Base = ereg_replace('[:space:]','',$md5Base);
						$key = md5($md5Base);

						if (!isset($menuWraps[$key]))	{	// Only if not yet set, set it (so it only gets set once and the first time!)
							$menuWraps[$key] = $newValue;
						} else {	// To prevent from writing values in the "} elseif ($key) {" below, we clear the key:
							$key = '';
						}
					} elseif ($key) {

							// Add this to the previous wrap:
						$menuWraps[$key]['bulletwrap'].= str_replace('|','&#'.ord('|').';',ereg_replace('['.chr(10).chr(13).']*','',$value));
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
'.(count($totalWrap) ? 'lib.'.$menuType.'.wrap = '.ereg_replace('['.chr(10).chr(13).']','',implode('|',$totalWrap)) : '').'
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
'.(count($totalWrap) ? 'lib.'.$menuType.'.wrap = '.ereg_replace('['.chr(10).chr(13).']','',implode('|',$totalWrap)) : '').'
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
			Here is the HTML code from the Template that encapsulated the menu:
			<hr/>
			<pre>'.htmlspecialchars($menuPart_HTML).'</pre>
			<hr/>
			<br/>';


			if (trim($menu_normal['wrap']) != '|')	{
				$outputString.= 'It seems that the menu consists of menu items encapsulated with "'.htmlspecialchars(str_replace('|',' ... ',$menu_normal['wrap'])).'". ';
			} else {
				$outputString.= 'It seems that the menu consists of menu items not wrapped in any block tags except A-tags. ';
			}
			if (count($totalWrap))	{
				$outputString.='It also seems that the whole menu is wrapped in this tag: "'.htmlspecialchars(str_replace('|',' ... ',implode('|',$totalWrap))).'". ';
			}
			if ($menu_normal['bulletwrap'])	{
				$outputString.='Between the menu elements there seems to be a visual division element with this HTML code: "'.htmlspecialchars($menu_normal['bulletwrap']).'". That will be added between each element as well. ';
			}
			if ($GMENU)	{
				$outputString.='The menu items were detected to be images - TYPO3 will try to generate graphical menu items automatically (GMENU). You will need to customize the look of these before it will match the originals! ';
			}
			if ($mouseOver)	{
				$outputString.='It seems like a mouseover functionality has been applied previously, so roll-over effect has been applied as well.  ';
			}

			$outputString.='<br/><br/>';
			$outputString.='Based on this analysis, this TypoScript configuration for the menu is suggested:
			<br/><br/>';
			$outputString.='<hr/>'.$this->syntaxHLTypoScript($typoScript).'<hr/><br/>';


			$outputString.='You can fine tune the configuration here before it is saved:<br/>';
			$outputString.='<textarea name="CFG[menuCode]"'.$GLOBALS['TBE_TEMPLATE']->formWidthText().' rows="10">'.t3lib_div::formatForTextarea($typoScript).'</textarea><br/><br/>';
			$outputString.='<input type="hidden" name="SET[wiz_step]" value="'.$menuTypeNextStep.'" />';
			$outputString.='<input type="submit" name="_" value="Write '.$menuTypeText.' TypoScript code" />';
		} else {
			$outputString.= '
				The basics of your website should be working now. It seems like you did not map the '.$menuTypeText.' to any element, so the menu configuration process will be skipped.<br/>
			';
			$outputString.='<input type="hidden" name="SET[wiz_step]" value="'.$menuTypeNextStep.'" />';
			$outputString.='<input type="submit" name="_" value="Next..." />';
		}

			// Add output:
		$this->content.= $this->doc->section('Step 5'.$menuTypeLetter.': Trying to create dynamic menu',$outputString,0,1);

	}

	/**
	 * Step 6: Done.
	 *
	 * @return	void
	 */
	function wizard_step6()	{

		$this->saveMenuCode();


		$outputString.= '<b>Congratulations!</b> You have completed the initial creation of a new website in TYPO3 based on the TemplaVoila engine. After you click the "Finish" button you can go to the Web>Page module to edit your pages!

		<br/>
		<br/>
		<input type="submit" value="Finish Wizard!" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($this->wizardData['rootPageId'],$this->doc->backPath).'document.location=\'index.php?SET[wiz_step]=0\'; return false;').'" />
		';

			// Add output:
		$this->content.= $this->doc->section('Step 6: Done',$outputString,0,1);
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
		global $TYPO3_CONF_VARS;

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

		return ereg_replace('['.chr(10).chr(13).chr(9).']','',$wrap);
	}

	/**
	 * Returns the code that the menu was mapped to in the HTML
	 *
	 * @param	string		"Field" from Data structure, either "field_menu" or "field_submenu"
	 * @return	string
	 */
	function getMenuDefaultCode($field)	{
			// Select template record and extract menu HTML content
		$toRec = t3lib_BEfunc::getRecord('tx_templavoila_tmplobj',$this->wizardData['templateObjectId']);
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