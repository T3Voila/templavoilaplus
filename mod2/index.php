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
 *   73: class tx_templavoila_module2 extends t3lib_SCbase
 *   84:     function menuConfig()
 *  104:     function main()
 *  162:     function printContent()
 *
 *              SECTION: Rendering module content:
 *  186:     function renderModuleContent()
 *  294:     function renderDSlisting($dsScopeArray, $scopePointer,&$toRecords)
 *  334:     function renderDataStructureDisplay($dsR)
 *  426:     function renderTODisplay($toObj, &$toRecords, $children=0)
 *
 * TOTAL FUNCTIONS: 7
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
			'set_details' => ''
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
				$lostTOs.= $this->renderTODisplay($toObj, $toRecords, 1);
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
	function renderDSlisting($dsScopeArray, &$toRecords, $scope)	{
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
							$TOcontent.= '<a name="to-'.$toObj['uid'].'"></a>'.
										$this->renderTODisplay($toObj, $toRecords, $scope);
							$indexTO.='<li><a href="#to-'.$toObj['uid'].'">'.htmlspecialchars($toObj['title']).'</a></li>';
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
				$content.= '<a name="ds-'.md5($dsID).'"></a>'.
							$this->renderDataStructureDisplay($dsR, $toIdArray, $scope);
				$index.='<li><a href="#ds-'.md5($dsID).'">'.htmlspecialchars($dsR['title']?$dsR['title']:$dsR['path']).'</a></li>';
				if ($indexTO)	{
					$index.='<ul>'.$indexTO.'</ul>';
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
						<ul>'.$index.'</ul>'.$content;
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
					<td>'.t3lib_div::formatSize(strlen($dsR['dataprot'])).'</td>
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
					<td>'.$fileRef.'</td>
				</tr>'.($this->MOD_SETTINGS['set_details'] ? '
				<tr class="bgColor4">
					<td>Template Status:</td>
					<td>'.$this->findDSUsageWithImproperTOs($dsID, $toIdArray, $scope).'</td>
				</tr>' : '').'
			</table>
			';
		}

			// Return content
		return $content;
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

		if ($fileMtime && $toObj['fileref_mtime'])	{
			if ($toObj['fileref_mtime'] != $fileMtime)	{
				$mappingStatus = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/icon_warning2.gif','width="18" height="16"').' alt="" class="absmiddle" />Template file was updated since last mapping ('.t3lib_BEfunc::datetime($toObj['tstamp']).') and you might need to remap the Template Object!';
				$this->setErrorLog($scope,'warning',$mappingStatus.' (TO: "'.$toObj['title'].'")');
			} else {
				$mappingStatus = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/icon_ok2.gif','width="18" height="16"').' alt="" class="absmiddle" />Mapping Up-to-date.';
			}
			$mappingStatus.='<br/><a href="'.htmlspecialchars($linkUrl).'">[ Update mapping ]</a>';
		} elseif (!$fileref_mtime) {
			$mappingStatus = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/icon_fatalerror.gif','width="18" height="16"').' alt="" class="absmiddle" />Not mapped yet!';
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

		if (!$children)	{
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
					<td>'.$this->findRecordsWhereTOUsed($toObj,$scope).'</td>
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
				$TOchildrenContent.= $this->renderTODisplay($childToObj, $toRecords, $scope, 1);

					// Unset it so we can eventually see what is left:
				unset($toRecords[$toObj['uid']][$toIndex]);
			}
			$content.='<div style="margin-left: 102px;">'.$TOchildrenContent.'</div>';
		}

			// Return content
		return $content;
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
						' AND tx_templavoila_to='.intval($toObj['uid']).
						' AND tx_templavoila_ds="'.$GLOBALS['TYPO3_DB']->quoteStr($toObj['datastructure'],'tt_content').'"'.
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
				$outputString = 'Used in '.(count($output)-1).' Elements:<table border="0" cellspacing="1" cellpadding="1" class="lrPadding">'.implode('',$output).'</table>';
			} else {
				$outputString = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/icon_warning2.gif','width="18" height="16"').' alt="" class="absmiddle" />No usage!';
				$this->setErrorLog($scope,'warning',$outputString.' (TO: "'.$toObj['title'].'")');
			}
		}

		return $outputString;
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