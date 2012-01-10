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
	 * Displays the edit page screen if the currently selected page is of the doktype "External URL"
	 *
	 * @param	array		$pageRecord: The current page record
	 * @return	mixed		HTML output from this submodule or FALSE if this submodule doesn't feel responsible
	 * @access	public
	 */
	function renderDoktype_3($pageRecord)    {
		global $LANG, $BE_USER, $TYPO3_CONF_VARS;

			// Prepare the record icon including a content sensitive menu link wrapped around it:
		$recordIcon = t3lib_iconWorks::getSpriteIconForRecord('pages', $pageRecord);
		$iconEdit = t3lib_iconWorks::getSpriteIcon('actions-document-open', array('title' => htmlspecialchars($LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:editPage'))));
		$editButton = $this->pObj->link_edit($iconEdit, 'pages', $pageRecord['uid']);

		switch ($pageRecord['urltype']) {
			case 2:
				$url = 'ftp://' . $pageRecord['url'];
			break;
			case 3:
				$url = 'mailto:' . $pageRecord['url'];
			break;
			case 4:
				$url = 'https://' . $pageRecord['url'];
			break;
			default:
					// Check if URI scheme already present. We support only Internet-specific notation,
					// others are not relevant for us (see http://www.ietf.org/rfc/rfc3986.txt for details)
				if (preg_match('/^[a-z]+[a-z0-9\+\.\-]*:\/\//i', $pageRecord['url'])) {
					// Do not add any other scheme
					$url = $pageRecord['url'];
					break;
				}
				// fall through
			case 1:
				$url = 'http://' . $pageRecord['url'];
			break;
		}

			// check if there is a notice on this URL type
		$notice = $LANG->getLL('cannotedit_externalurl_' . $pageRecord['urltype'], '', 1);
		if (!$notice) {
			$notice = $LANG->getLL('cannotedit_externalurl_1', '', 1);
		}

		$urlInfo = ' <br /><br /><strong><a href="' . $url . '" target="_new">' . htmlspecialchars(sprintf($LANG->getLL ('jumptoexternalurl'), $url)) . '</a></strong>';
		$flashMessage = t3lib_div::makeInstance(
			't3lib_FlashMessage',
			$notice,
			'',
			t3lib_FlashMessage::INFO
		);
		$content = $flashMessage->render() . $urlInfo;
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
		$recordIcon = t3lib_iconWorks::getSpriteIconForRecord('pages', $pageRecord);
		$recordButton = $this->doc->wrapClickMenuOnIcon($recordIcon, 'pages', $pageRecord['uid'], 1, '&callingScriptId='.rawurlencode($this->doc->scriptID), 'new,copy,cut,pasteinto,pasteafter,delete');
		$iconEdit = t3lib_iconWorks::getSpriteIcon('actions-document-open', array('title' => htmlspecialchars($LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:editPage'))));
		$editButton = $this->pObj->link_edit($iconEdit, 'pages', $pageRecord['uid']);

		if (intval($pageRecord['shortcut_mode']) == 0) {
			$shortcutSourcePageRecord = t3lib_beFunc::getRecordWSOL('pages', $pageRecord['shortcut']);
			$jumpToShortcutSourceLink = '<strong><a href="index.php?id='.$pageRecord['shortcut'].'">'.
										t3lib_iconWorks::getSpriteIcon('apps-pagetree-page-shortcut').
										$LANG->getLL ('jumptoshortcutdestination', '',1).'</a></strong>';
		}

		$flashMessage = t3lib_div::makeInstance(
			't3lib_FlashMessage',
			sprintf ($LANG->getLL ('cannotedit_shortcut_'.intval($pageRecord['shortcut_mode'])), $shortcutSourcePageRecord['title']),
			'',
			t3lib_FlashMessage::INFO
		);
		$content = $flashMessage->render() . $jumpToShortcutSourceLink;
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
		$recordIcon = t3lib_iconWorks::getSpriteIconForRecord('pages', $pageRecord);
		$recordIcon = $this->doc->wrapClickMenuOnIcon($recordIcon, 'pages', $this->id, 1, '&amp;callingScriptId='.rawurlencode($this->doc->scriptID));
		$iconEdit = t3lib_iconWorks::getSpriteIcon('actions-document-open', array('title' => htmlspecialchars($LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:editPage'))));
		$editButton = $this->pObj->link_edit($iconEdit, 'pages', $pageRecord['uid']);

		$mountSourcePageRecord = t3lib_beFunc::getRecordWSOL('pages', $pageRecord['mount_pid']);
		$mountSourceIcon = t3lib_iconWorks::getSpriteIconForRecord('pages', $mountSourcePageRecord);
		$mountSourceButton = $this->doc->wrapClickMenuOnIcon($mountSourceIcon, 'pages', $mountSourcePageRecord['uid'], 1, '&callingScriptId='.rawurlencode($this->doc->scriptID), 'new,copy,cut,pasteinto,pasteafter,delete');

		$mountSourceLink = '<br /><br />
			<a href="index.php?id='.$pageRecord['mount_pid'].'">'.htmlspecialchars($LANG->getLL ('jumptomountsourcepage')).'</a>
		';

		$flashMessage = t3lib_div::makeInstance(
			't3lib_FlashMessage',
			sprintf ($LANG->getLL ('cannotedit_doktypemountpoint'), $mountSourceButton . $mountSourcePageRecord['title']),
			'',
			t3lib_FlashMessage::INFO
		);
		$content = $flashMessage->render() . '<strong>' . $mountSourceLink . '</strong>';
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
		$recordIcon = t3lib_iconWorks::getSpriteIconForRecord('pages', $pageRecord);
		$iconEdit = t3lib_iconWorks::getSpriteIcon('actions-document-open', array('title' => htmlspecialchars($LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:editPage'))));
		$editButton = $this->pObj->link_edit($iconEdit, 'pages', $pageRecord['uid']);

		if ($this->userHasAccessToListModule()) {
			if (tx_templavoila_div::convertVersionNumberToInteger(TYPO3_version) < 4005000) {
				$listModuleURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir . 'db_list.php?id='.intval($this->pObj->id);
			} else {
				$listModuleURL = t3lib_BEfunc::getModuleUrl('web_list', array ('id' => intval($this->pObj->id)), '');
			}
			$onClick = "top.nextLoadModuleUrl='".$listModuleURL."';top.fsMod.recentIds['web']=".intval($this->pObj->id).";top.goToModule('web_list',1);";
			$listModuleLink = '<br /><br />'.
				t3lib_iconWorks::getSpriteIcon('actions-system-list-open').
				'<strong><a href="#" onClick="'.$onClick.'">'.$LANG->getLL('editpage_sysfolder_switchtolistview','',1).'</a></strong>
			';
		} else {
			$listModuleLink = $LANG->getLL('editpage_sysfolder_listview_noaccess','',1);
		}

		$flashMessage = t3lib_div::makeInstance(
			't3lib_FlashMessage',
			$LANG->getLL('editpage_sysfolder_intro', '', 1),
			'',
			t3lib_FlashMessage::INFO
		);
		$content = $flashMessage->render() . $listModuleLink;
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