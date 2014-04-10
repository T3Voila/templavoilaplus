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
 * Submodule 'records' for the templavoila page module
 *
 * $Id$
 *
 * @author     Dmitry Dulepov <dmitry@typo3.org>
 */

/**
 * Submodule 'records' for the templavoila page module
 *
 * @author        Dmitry Dulepov <dmitry@typo3.org>
 * @package        TYPO3
 * @subpackage    tx_templavoila
 */
class tx_templavoila_mod1_records {

	var $pObj; // Reference to parent module
	var $tables;

	var $calcPerms;

	var $dblist;

	/**
	 * Initializes sidebar object. Checks if there any tables to display and
	 * adds sidebar item if there are any.
	 *
	 * @param    object $pObj Parent object
	 *
	 * @return    void
	 */
	function init(&$pObj) {
		$this->pObj = & $pObj;

		$this->tables = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->pObj->modTSconfig['properties']['recordDisplay_tables'], TRUE);
		if ($this->tables) {
			// Get permissions
			$this->calcPerms = \Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->calcPerms(\TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->pObj->id, $this->pObj->perms_clause));
			foreach ($this->tables as $table) {
				if ($this->canDisplayTable($table)) {
					// At least one displayable table found!
					$this->pObj->sideBarObj->addItem('records', $this, 'sidebar_renderRecords', \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('records'), 25);
					break;
				}
			}
		}
	}

	/**
	 * Displays a list of local content elements on the page which were NOT used in the hierarchical structure of the page.
	 *
	 * @param    $pObj :        Reference to the parent object ($this)
	 *
	 * @return    string        HTML output
	 * @access protected
	 */
	function sidebar_renderRecords() {
		$content = '<table border="0" cellpadding="0" cellspacing="1" class="lrPadding" width="100%">';
		$content .= '<tr class="bgColor4-20"><th colspan="3">&nbsp;</th></tr>';

		// Render table selector
		$content .= $this->renderTableSelector();
		$content .= $this->renderRecords();
		$content .= '</table>';

		return $content;
	}

	/**
	 * Renders table selector.
	 *
	 * @return    string        Genrated content
	 */
	function renderTableSelector() {
		$content = '<tr class="bgColor4">';
		$content .= '<td width="20">&nbsp;</td>'; //space for csh icon
		$content .= '<td width="200">' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('displayRecordsFrom') . '</td><td>';

		$link = '\'index.php?' . $this->pObj->link_getParameters() . '&SET[recordsView_start]=0&SET[recordsView_table]=\'+this.options[this.selectedIndex].value';
		$content .= '<select onchange="document.location.href=' . $link . '">';
		$content .= '<option value=""' . ($this->pObj->MOD_SETTINGS['recordsView_table'] == '' ? ' selected="selected"' : '') . '></options>';
		foreach ($this->tables as $table) {
			$t = htmlspecialchars($table);
			if ($this->canDisplayTable($table)) {
				$title = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sl($GLOBALS['TCA'][$table]['ctrl']['title']);
				$content .= '<option value="' . $t . '"' .
					($this->pObj->MOD_SETTINGS['recordsView_table'] == $table ? ' selected="selected"' : '') .
					'>' . $title . ' (' . $t . ')' . '</option>';
			}
		}
		$content .= '</select>';

		if ($this->pObj->MOD_SETTINGS['recordsView_table']) {
			$backpath = '../../../../typo3/';
			$table = $this->pObj->MOD_SETTINGS['recordsView_table'];
			$params = '&edit[' . $table . '][' . $this->pObj->id . ']=new';
			$content .= '&nbsp;&nbsp;';
			$content .= '<a href="#" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($params, $backpath, -1)) . '">';
			$content .= \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new', array('title' => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('createnewrecord')));
			$content .= '</a>';
		}

		if (!in_array($this->pObj->MOD_SETTINGS['recordsView_table'], $this->tables)) {
			unset($this->pObj->MOD_SETTINGS['recordsView_table']);
			unset($this->pObj->MOD_SETTINGS['recordsView_start']);
		}

		$content .= '</td></tr><tr class="bgColor4"><td colspan="2"></td></tr>';

		return $content;
	}

	/**
	 * Renders record list.
	 *
	 * @return    void
	 */
	function renderRecords() {
		$table = $this->pObj->MOD_SETTINGS['recordsView_table'];
		$content = '';
		if ($table) {
			$this->initDbList($table);
			$this->dblist->generateList();
			$content = '<tr class="bgColor4"><td colspan="3" style="padding: 0 0 3px 3px">' . $this->dblist->HTMLcode . '</td></tr>';
		}

		return $content;
	}

	/**
	 * Checks if table can be displayed to the current user.
	 *
	 * @param    string $table Table name
	 *
	 * @return    boolean        <code>true</code> if table can be displayed.
	 */
	function canDisplayTable($table) {
		return ($table != 'pages' && $table != 'tt_content' && isset($GLOBALS['TCA'][$table]) && \Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->check('tables_select', $table));
	}

	/**
	 * Initializes List classes.
	 *
	 * @param    string $table Table name to show
	 *
	 * @return    void
	 */
	function initDbList($table) {
		// Initialize the dblist object:
		$this->dblist = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_templavoila_mod1_recordlist');
		$this->dblist->backPath = $this->pObj->doc->backPath;
		$this->dblist->calcPerms = $this->calcPerms;
		$this->dblist->thumbs = \Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->uc['thumbnailsByDefault'];
		$this->dblist->returnUrl = $GLOBALS['BACK_PATH'] . TYPO3_MOD_PATH . 'index.php?' . $this->pObj->link_getParameters();
		$this->dblist->allFields = TRUE;
		$this->dblist->localizationView = TRUE;
		$this->dblist->showClipboard = FALSE;
		$this->dblist->disableSingleTableView = TRUE;
		$this->dblist->listOnlyInSingleTableMode = FALSE;
//		$this->dblist->clickTitleMode = $this->modTSconfig['properties']['clickTitleMode'];
		$this->dblist->alternateBgColors = (isset($this->pObj->MOD_SETTINGS['recordsView_alternateBgColors']) ? intval($this->pObj->MOD_SETTINGS['recordsView_alternateBgColors']) : FALSE);
		$this->dblist->allowedNewTables = array($table);
		$this->dblist->newWizards = FALSE;
		$this->dblist->tableList = $table;
		$this->dblist->itemsLimitPerTable = ($GLOBALS['TCA'][$table]['interface']['maxDBListItems'] ?
			$GLOBALS['TCA'][$table]['interface']['maxDBListItems'] :
			(intval($this->pObj->modTSconfig['properties']['recordDisplay_maxItems']) ?
				intval($this->pObj->modTSconfig['properties']['recordDisplay_maxItems']) : 10));
		$this->dblist->start($this->pObj);
	}
}
