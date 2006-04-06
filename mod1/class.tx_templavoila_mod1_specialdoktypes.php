<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2006  Robert Lemke (robert@typo3.org)
*  All rights reserved
*
*  script is part of the TYPO3 project. The TYPO3 project is
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
 * Submodule 'special doktypes' for the templavoila page module
 *
 * $Id$
 *
 * @author     Robert Lemke <robert@typo3.org>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   58: class tx_templavoila_mod1_specialdoktypes
 *   71:     function init(&$pObj)
 *   88:     function renderDoktype_2($pageRecord)
 *  139:     function renderDoktype_3($pageRecord)
 *  188:     function renderDoktype_4($pageRecord)
 *  234:     function renderDoktype_7($pageRecord)
 *  282:     function renderDoktype_254($pageRecord)
 *  332:     function userHasAccessToListModule()
 *
 * TOTAL FUNCTIONS: 7
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

/**
 * Submodule 'clipboard' for the templavoila page module
 *
 * @author		Robert Lemke <robert@typo3.org>
 * @package		TYPO3
 * @subpackage	tx_templavoila
 * @todo		This class wants to be refactored because there's quite some redundancy in it. But that's not urgent ...
 */
class tx_templavoila_mod1_specialdoktypes {

		// References to the page module object
	var $pObj;										// A pointer to the parent object, that is the templavoila page module script. Set by calling the method init() of this class.
	var $doc;										// A reference to the doc object of the parent object.

	/**
	 * Does some basic initialization
	 *
	 * @param	$pObj:		Reference to the parent object ($this)
	 * @return	void
	 * @access	public
	 */
	function init(&$pObj) {
		global $LANG, $BE_USER, $BACK_PATH;

			// Make local reference to some important variables:
		$this->pObj =& $pObj;
		$this->doc =& $this->pObj->doc;
		$this->extKey =& $this->pObj->extKey;
		$this->MOD_SETTINGS =& $this->pObj->MOD_SETTINGS;
	}

	/**
	 * Displays the edit page screen if the currently selected page is of the doktype "Advanced"
	 *
	 * @param	array		$pageRecord: The current page record
	 * @return	mixed		HTML output from this submodule or FALSE if this submodule doesn't feel responsible
	 * @access	public
	 */
	function renderDoktype_2($pageRecord)    {
		global $LANG, $BE_USER, $TYPO3_CONF_VARS;

		if (intval($pageRecord['content_from_pid'])) {

				// Prepare the record icon including a content sensitive menu link wrapped around it:
			$pageTitle = htmlspecialchars(t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle('pages', $pageRecord), 50));
			$recordIcon = $recordIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath, t3lib_iconWorks::getIcon('pages', $pageRecord), '').' style="text-align: center; vertical-align: middle;" width="18" height="16" border="0" title="'.$pageTitle.'" alt="" />';
			$editButton = $this->pObj->link_edit('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','').' title="'.htmlspecialchars($LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:editPage')).'" alt="" style="text-align: center; vertical-align: middle; border:0;" />', 'pages', $pageRecord['uid']);

			$sourcePageRecord = t3lib_beFunc::getRecordWSOL('pages', $pageRecord['content_from_pid']);
			$sourceIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath, t3lib_iconWorks::getIcon('pages', $sourcePageRecord), '').' style="text-align: center; vertical-align: middle;" width="18" height="16" border="0" title="'.$sourcePageRecord['title'].'" alt="" />';
			$sourceButton = $this->doc->wrapClickMenuOnIcon($sourceIcon, 'pages', $sourcePageRecord['uid'], 1, '&callingScriptId='.rawurlencode($this->doc->scriptID), 'new,copy,cut,pasteinto,pasteafter,delete');

			$sourceLink = '
				<a href="index.php?id='.$pageRecord['content_from_pid'].'">'.htmlspecialchars($LANG->getLL ('jumptocontentfrompidpage')).'</a>
			';

			$content = '
				<table border="0" cellpadding="2" cellspacing="0" style="border: 1px solid black; margin-bottom:5px; width:100%">
					<tr style="background-color: '.$this->doc->bgColor2.';">
						<td nowrap="nowrap" colspan="2">
							'.$recordIcon.$editButton.'
							</a>
							'.htmlspecialchars($pageRecord['title']).'
						</td>
					</tr>
					<tr>
						<td style="width:80%;">
						'.htmlspecialchars(sprintf ($LANG->getLL ('cannotedit_contentfrompid'), $sourcePageRecord['title'])).'<br /><br />
						'.$sourceButton.'<strong>'.$sourceLink.'<strong>

						</td>
						<td>&nbsp;</td>
					</tr>
				</table>
			';
			return $content;
		}

		return FALSE;
	}

	/**
	 * Displays the edit page screen if the currently selected page is of the doktype "External URL"
	 *
	 * @param	array		$pageRecord: The current page record
	 * @return	mixed		HTML output from this submodule or FALSE if this submodule doesn't feel responsible
	 * @access	public
	 */
	function renderDoktype_3($pageRecord)    {
		global $LANG, $BE_USER, $TYPO3_CONF_VARS;

			// Prepare the record icon including a content sensitive menu link wrapped around it:
		$pageTitle = htmlspecialchars(t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle('pages', $pageRecord), 50));
		$recordIcon = $recordIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath, t3lib_iconWorks::getIcon('pages', $pageRecord), '').' style="text-align: center; vertical-align: middle;" width="18" height="16" border="0" title="'.$pageTitle.'" alt="" />';
		$editButton = $this->pObj->link_edit('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','').' title="'.htmlspecialchars($LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:editPage')).'" alt="" style="text-align: center; vertical-align: middle; border:0;" />', 'pages', $pageRecord['uid']);

		switch ($pageRecord['urltype']) {
			case 2 :
				$url = 'ftp://'.$pageRecord['url'];
			break;
			case 3:
				$url = 'mailto:'.$pageRecord['url'];
			break;
			default:
			case 1 :
				$url = 'http://'.$pageRecord['url'];
			break;
		}
		$content = 
			$this->doc->icons(1).	
			$LANG->getLL ('cannotedit_externalurl_'.$pageRecord['urltype'],'',1).
			' <strong><a href="'.$url.'" target="_new">'.htmlspecialchars(sprintf($LANG->getLL ('jumptoexternalurl'), $url)).'</a></strong>'
		;
		return $content;
	}

	/**
	 * Displays the edit page screen if the currently selected page is of the doktype "Shortcut"
	 *
	 * @param	array		$pageRecord: The current page record
	 * @return	mixed		HTML output from this submodule or FALSE if this submodule doesn't feel responsible
	 * @access	public
	 */
	function renderDoktype_4($pageRecord)    {
		global $LANG, $BE_USER, $TYPO3_CONF_VARS;

			// Prepare the record icon including a content sensitive menu link wrapped around it:
		$pageTitle = htmlspecialchars(t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle('pages', $pageRecord), 50));
		$recordIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath, t3lib_iconWorks::getIcon('pages', $pageRecord), '').' style="text-align: center; vertical-align: middle;" width="18" height="16" border="0" title="'.$pageTitle.'" alt="" />';
		$recordButton = $this->doc->wrapClickMenuOnIcon($recordIcon, 'pages', $pageRecord['uid'], 1, '&callingScriptId='.rawurlencode($this->doc->scriptID), 'new,copy,cut,pasteinto,pasteafter,delete');
		$editButton = $this->pObj->link_edit('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','').' title="'.htmlspecialchars($LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:editPage')).'" alt="" style="text-align: center; vertical-align: middle; border:0;" />', 'pages', $pageRecord['uid']);

		if (intval($pageRecord['shortcut_mode']) == 0) {
			$shortcutSourcePageRecord = t3lib_beFunc::getRecordWSOL('pages', $pageRecord['shortcut']);
			$jumpToShortcutSourceLink = '<strong><a href="index.php?id='.$pageRecord['shortcut'].'">'.$LANG->getLL ('jumptoshortcutdestination', '',1).'</a></strong>';
		}

		$content = 
			$this->doc->icons(1).
			htmlspecialchars(sprintf ($LANG->getLL ('cannotedit_shortcut_'.intval($pageRecord['shortcut_mode'])), $shortcutSourcePageRecord['title'])).
			$jumpToShortcutSourceLink
		;
		return $content;
	}

	/**
	 * Displays the edit page screen if the currently selected page is of the doktype "Mount Point"
	 *
	 * @param	array		$pageRecord: The current page record
	 * @return	mixed		HTML output from this submodule or FALSE if this submodule doesn't feel responsible
	 * @access	protected
	 */
	function renderDoktype_7($pageRecord)    {
		global $LANG, $BE_USER, $TYPO3_CONF_VARS;

		if (!$pageRecord['mount_pid_ol']) return FALSE;

			// Put together the records icon including content sensitive menu link wrapped around it:
		$recordIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/i/pages_mountpoint.gif','').' style="text-align: center; vertical-align: middle;" width="18" height="16" border="0" title="'.htmlspecialchars('[pages]').'" alt="" />';
		$recordIcon = $this->doc->wrapClickMenuOnIcon($recordIcon, 'pages', $this->id, 1, '&amp;callingScriptId='.rawurlencode($this->doc->scriptID));

		$editButton = $this->pObj->link_edit('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','').' title="'.htmlspecialchars($LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:editPage')).'" alt="" style="text-align: center; vertical-align: middle; border:0;" />', 'pages', $pageRecord['uid']);

		$mountSourcePageRecord = t3lib_beFunc::getRecordWSOL('pages', $pageRecord['mount_pid']);
		$mountSourceIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath, t3lib_iconWorks::getIcon('pages', $mountSourcePageRecord), '').' style="text-align: center; vertical-align: middle;" width="18" height="16" border="0" title="'.$mountSourcePageRecord['title'].'" alt="" />';
		$mountSourceButton = $this->doc->wrapClickMenuOnIcon($mountSourceIcon, 'pages', $mountSourcePageRecord['uid'], 1, '&callingScriptId='.rawurlencode($this->doc->scriptID), 'new,copy,cut,pasteinto,pasteafter,delete');

		$mountSourceLink = '
			<a href="index.php?id='.$pageRecord['mount_pid'].'">'.htmlspecialchars($LANG->getLL ('jumptomountsourcepage')).'</a>
		';

		$content = 
			$this->doc->icons(1).
			htmlspecialchars(sprintf ($LANG->getLL ('cannotedit_doktypemountpoint'), $mountSourcePageRecord['title'])).
			$mountSourceButton.'<strong>'.$mountSourceLink.'</strong>
		';
		return $content;
	}

	/**
	 * Displays the edit page screen if the currently selected page is of the doktype "Sysfolder"
	 *
	 * @param	array		$pageRecord: The current page record
	 * @return	mixed		HTML output from this submodule or FALSE if this submodule doesn't feel responsible
	 * @access	public
	 */
	function renderDoktype_254($pageRecord) {
		global $LANG, $BE_USER, $TYPO3_CONF_VARS;

			// Prepare the record icon including a content sensitive menu link wrapped around it:
		$pageTitle = htmlspecialchars(t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle('pages', $pageRecord), 50));
		$recordIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath, t3lib_iconWorks::getIcon('pages', $pageRecord), '').' style="text-align: center; vertical-align: middle;" width="18" height="16" border="0" title="'.$pageTitle.'" alt="" />';

		$editButton = $this->pObj->link_edit('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','').' title="'.htmlspecialchars($LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:editPage')).'" alt="" style="text-align: center; vertical-align: middle; border:0;" />', 'pages', $pageRecord['uid']);

		if ($this->userHasAccessToListModule ()) {
			$listModuleURL = $this->doc->backPath.'db_list.php?id='.intval($this->pObj->id);
			$onClick = "top.nextLoadModuleUrl='".$listModuleURL."';top.fsMod.recentIds['web']=".intval($this->pObj->id).";top.goToModule('web_list',1);";
			$listModuleLink = '
				<img'.t3lib_iconWorks::skinImg($this->doc->backPath, 'mod/web/list/list.gif', '').' style="text-align:center; vertical-align: middle; border:0;" />
				<strong><a href="#" onClick="'.$onClick.'">'.$LANG->getLL('editpage_sysfolder_switchtolistview','',1).'</a></strong>
			';
		} else {
			$listModuleLink = $LANG->getLL('editpage_sysfolder_listview_noaccess','',1);
		}

		$content = 
			$this->doc->icons(1).		
			$LANG->getLL('editpage_sysfolder_intro','',1).
			$listModuleLink
		;
		return $content;
	}


	/**
	 * Returns TRUE if the logged in BE user has access to the list module.
	 *
	 * @return	boolean		TRUE or FALSE
	 * @access	protected
	 */
	function userHasAccessToListModule() {
		global $BE_USER;

		if (!t3lib_BEfunc::isModuleSetInTBE_MODULES('web_list')) return FALSE;
		if ($BE_USER->isAdmin()) return TRUE;
		return $BE_USER->check('modules', 'web_list');
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/class.tx_templavoila_mod1_specialdoktypes.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/class.tx_templavoila_mod1_specialdoktypes.php']);
}

?>