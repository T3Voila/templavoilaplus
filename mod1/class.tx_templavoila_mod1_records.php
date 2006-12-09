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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */

/**
 * Submodule 'clipboard' for the templavoila page module
 *
 * @author		Dmitry Dulepov <dmitry@typo3.org>
 * @package		TYPO3
 * @subpackage	tx_templavoila
 */
class tx_templavoila_mod1_records {

	var	$pObj;	// Reference to parent module
	var	$tables;
	var $calcPerms;

	function init(&$pObj) {
		$this->pObj =& $pObj;

		$this->tables = t3lib_div::trimExplode(',', $this->pObj->modTSconfig['properties']['recordDisplay_tables'], true);
		if ($this->tables) {
			// Get permissions
			$this->calcPerms = $GLOBALS['BE_USER']->calcPerms(t3lib_BEfunc::readPageAccess($this->pObj->id, $this->pObj->perms_clause));
			// Add a list of non-used elements to the sidebar:
			$this->pObj->sideBarObj->addItem('records', $this, 'sidebar_renderRecords', $GLOBALS['LANG']->getLL('records'), 30);
		}
	}


	/**
	 * Displays a list of local content elements on the page which were NOT used in the hierarchical structure of the page.
	 *
	 * @param	$pObj:		Reference to the parent object ($this)
	 * @return	string		HTML output
	 * @access	protected
	 */
	function sidebar_renderRecords() {
		$content = '<table border="0" cellpadding="0" cellspacing="1" class="lrPadding" width="100%">';
		$content .= '<tr class="bgColor4-20"><th colspan="2">&nbsp;</th></tr>';

		// Render table selector
		$content .= $this->renderTableSelector();
		$content .= $this->renderRecords();
		$content .= '</table>';

		return $content;
	}

	function renderTableSelector() {
		$content = '<tr class="bgColor4"><td width="1%" nowrap="nowrap">';
		$content .= $GLOBALS['LANG']->getLL('displayRecordsFrom');
		$content .= '</td><td>';
		$link = '\'index.php?'.$this->pObj->link_getParameters().'&SET[recordsView_start]=0&SET[recordsView_table]=\'+this.options[this.selectedIndex].value';
		$content .= '<select onchange="document.location.href=' . $link . '">';
		$content .= '<option value=""' . ($this->pObj->MOD_SETTINGS['recordsView_table'] == '' ? ' selected="selected"' : '') . '></options>';
		foreach ($this->tables as $table) {
			$t = htmlspecialchars($table);
			t3lib_div::loadTCA($table);
			if ($table != 'pages' && $table != 'tt_content' && isset($GLOBALS['TCA'][$table])) {
				$title = $GLOBALS['LANG']->sl($GLOBALS['TCA'][$table]['ctrl']['title']);
				$content .= '<option value="' . $t . '"' . ($this->pObj->MOD_SETTINGS['recordsView_table'] == $table ? ' selected="selected"' : '') . '>' . $title . ' (' . $t . ')' . '</options>';
			}
		}
		if (!in_array($this->pObj->MOD_SETTINGS['recordsView_table'], $this->tables)) {
			unset($this->pObj->MOD_SETTINGS['recordsView_table']);
			unset($this->pObj->MOD_SETTINGS['recordsView_start']);
		} 
		$content .= '</select></td></tr>';
		return $content;
	}
	
	function renderRecords() {
		$table = $this->pObj->MOD_SETTINGS['recordsView_table'];
		if ($table) {
			$content = '<tr class="bgColor4"><td colspan="2">';

			// select record count
			$where = 'pid=' . $this->pObj->rootElementUid_pidForContent . t3lib_BEfunc::deleteClause($table) . t3lib_BEfunc::versioningPlaceholderClause($table);
			$ar = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('COUNT(*) AS count', $table, $where);
			$count = $ar[0]['count'];
			$startPos = intval($this->pObj->MOD_SETTINGS['recordsView_start']);
			$maxItems = ($GLOBALS['TCA'][$table]['interface']['maxDBListItems'] ?
						$GLOBALS['TCA'][$table]['interface']['maxDBListItems'] : 10);
			// create table name
			if ($this->calcPerms & 16) {
				$content .= '<a href="#" onclick="' .
						t3lib_BEfunc::editOnClick('&edit[' . $table . '][' . $this->pObj->rootElementUid_pidForContent . ']=new', $this->pObj->doc->backPath) .
						'"><img'.t3lib_iconWorks::skinImg($this->pObj->doc->backPath, 'gfx/new_el.gif','').' title="'.htmlspecialchars($GLOBALS['LANG']->getLL('createnewrecord')).'" alt="" style="text-align: center; vertical-align: middle; border:0; margin-right: 5px; margin-bottom: 3px; " />' .
						'</a>';
			}
			$content .= '<strong>' . $GLOBALS['LANG']->sl($GLOBALS['TCA'][$table]['ctrl']['title']) . '</strong>';
			if ($count > $maxItems) {
				$content .= ' (' .
					sprintf($GLOBALS['LANG']->getLL('displayingRecords'), $startPos + 1, min($count, $startPos + $maxItems), $count) .
					')';
			}
			$content .= ':<br />';

			// select records
			$titleFields = ($GLOBALS['TCA'][$table]['ctrl']['label_alt'] ?
							$GLOBALS['TCA'][$table]['ctrl']['label_alt'] :
							$GLOBALS['TCA'][$table]['ctrl']['label']);
			$sort = ($GLOBALS['TCA'][$table]['ctrl']['sortby'] ?
						$GLOBALS['TCA'][$table]['ctrl']['sortby'] :
						$GLOBALS['TCA'][$table]['ctrl']['default_sortby']);
			$ar = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,' . $titleFields, $table, $where, '',
							 $GLOBALS['TCA'][$table]['ctrl']['sortby'], $startPos . ',' . $maxItems);
			$fields = t3lib_div::trimExplode(',', $titleFields, true);
			foreach($ar as $rec) {
				$labels = array();
				foreach ($fields as $field) {
					if ($rec[$field]) {
						$labels[] = $rec[$field];
					}
				}

				if (($table == 'pages' ? $this->calcPerms&2 : $this->calcPerms&16)) {
					$content .= $this->pObj->link_edit('<img'.t3lib_iconWorks::skinImg($this->pObj->doc->backPath,'gfx/edit2.gif','').' title="'.htmlspecialchars(/*$GLOBALS['LANG']->getLL('editrecord')*/'[' . $table . ':' . $rec['uid'] . ']').'" alt="" style="text-align: center; vertical-align: middle; border:0;" />', $table, $rec['uid']);
				}
				if (($table == 'pages' ? $this->calcPerms&4 : $this->calcPerms&16)) {
					$content .= '<a href="#" onclick="'.htmlspecialchars('if (confirm('.$GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL('deleteRecordMsg')).'))' .
							'jumpToUrl(\'' . $this->pObj->doc->issueCommand('&cmd[' . $table . '][' . $rec['uid'] . '][delete]=1', '') . '\')') . '">' .
							'<img'.t3lib_iconWorks::skinImg($this->pObj->doc->backPath, 'gfx/garbage.gif','').' title="'.htmlspecialchars($GLOBALS['LANG']->getLL('deleteRecord2')).'" alt="" style="text-align: center; vertical-align: middle; border:0;" />' .
							'</a>';
				}
				$content .= '&nbsp;' . t3lib_div::fixed_lgd_cs(implode(', ', $labels), 35) . '<br />';
			}
			// Next & Prev buttons
			$onclick = 'document.location.href=\'index.php?'.$this->pObj->link_getParameters().'&SET[recordsView_table]=' . $table . '&SET[recordsView_start]=%d\'';
			if ($startPos != 0) {
				$content .= '<input type="button" value="' .
					sprintf($GLOBALS['LANG']->getLL('prevRecords'), $maxItems) .
					'" onclick="' . sprintf($onclick, max(0, $startPos - $maxItems)) . '" /> ';
			}
			if ($startPos + $maxItems < $count) {
				$content .= '<input type="button" value="' .
					sprintf($GLOBALS['LANG']->getLL('nextRecords'), $maxItems) .
					'" onclick="' . sprintf($onclick, min($count, $startPos + $maxItems)) . '" />';
			}
			$content .= '<br />';
		}
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/class.tx_templavoila_mod1_records.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/class.tx_templavoila_mod1_records.php']);
}

?>