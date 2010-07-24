<?php
/***************************************************************
* Copyright notice
*
* (c)  2010 Tolleiv Nietsch <nietsch@aoemedia.de>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * Class which adds an additional layer for icon creation
 *
 */
final class tx_templavoila_icons {

	static $oldIcons = array(
		'actions-view-go-back'	=> 		array('file' => 'gfx/goback.gif', 'attributes' => 'width="14" height="14"'),
		'actions-document-new' => 		array('file' =>  'gfx/new_el.gif'),
		'actions-insert-record' => 		array('file' => 'gfx/insert3.gif'),
		'actions-edit-hide'=> 			array('file' => 'gfx/button_hide.gif'),
		'actions-edit-unhide'=> 		array('file' => 'gfx/button_unhide.gif'),
		'actions-document-view' => 		array('file' => 'gfx/zoom.gif', 'attributes' => 'width="12" height="12"'),
		'actions-system-list-open' => 	array('file' => 'gfx/list.gif', 'attributes' => 'width="11" height="11"'),
		'actions-document-history-open' => array('file' => 'gfx/history2.gif', 'attributes' => 'width="13" height="12"'),
		'actions-page-move' => 			array('file' => 'gfx/move_page.gif', 'attributes' => 'width="11" height="12"'),
		'actions-page-new' => 			array('file' => 'gfx/new_page.gif', 'attributes' => 'width="13" height="12"'),
		'actions-document-open' => 		array('file' => 'gfx/edit2.gif', 'attributes' => 'width="11" height="12"'),
		'actions-edit-delete' =>		array('file' => 'gfx/deletedok.gif'),
		'actions-edit-copy' => 			array('file' => 'gfx/clip_copy.gif'),
		'actions-edit-copy-release' => 	array('file' => 'gfx/clip_copy_h.gif'),
		'actions-edit-cut' => 			array('file' => 'gfx/clip_cut.gif'),
		'actions-edit-cut-release' => 	array('file' => 'gfx/clip_cut_h.gif'),
		'actions-system-list-open' => 	array('file' => 'mod/web/list/list.gif'),
		'apps-pagetree-page-shortcut' => array('file' => 'gfx/shortcut.gif'),
		'actions-move-up' => 			array('file' => 'gfx/pilup.gif'),
		'actions-move-down' =>			array('file' => 'gfx/pildown.gif'),
		'actions-view-table-expand' => 	array('file' => 'gfx/plusbullet_list.gif'),
		'actions-view-table-collapse' => array('file' => 'gfx/minusbullet_list.gif'),
	);

	static $useOldIcons = null;
	static $reInit = true;

	public static function init() {

		if(self::$useOldIcons !== null && self::$reInit == false) {
			return;
		}

		if(version_compare(TYPO3_version,'4.4','<') || self::$useOldIcons) {
			self::$useOldIcons = true;
			if (is_array($GLOBALS['TBE_STYLES']['spritemanager']['singleIcons'])) {
				foreach($GLOBALS['TBE_STYLES']['spritemanager']['singleIcons'] as $name => $file) {
					if (!isset(self::$oldIcons[$name])) {
						self::$oldIcons[$name] = array('file' => $file);
					}
				}
			}
		} else {
			self::$useOldIcons = false;
		}
		self::$reInit = true;
	}

	/**
	 *
	 * @param string $iconName
	 * @param array $options
	 * @param array $overlays
	 * @return string
	 */
	public static function getIcon($iconName, $options = array(), $overlays = array()) {

		self::init();

		if (self::$useOldIcons) {
			$alt = isset($options['alt']) ? ' alt="' . $options['alt'] .'"': ' alt=""';
			$title = isset($options['title']) ? ' alt="' . $options['title'] .'"': '';
			$wHattribs = isset(self::$oldIcons[$iconName]['attributes']) ? self::$oldIcons[$iconName]['attributes'] : '';
			return '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],self::$oldIcons[$iconName]['file'], $wHattribs) . $alt . $title . '  style="text-align:center; vertical-align: middle; border:0;" />';
		} else {
			return t3lib_iconWorks::getSpriteIcon($iconName, $options, $overlays);
		}
	}

	/**
	 *
	 * @param string $table
	 * @param array $row
	 * @param array $options
	 * @return string
	 */
	public static function getIconForRecord($table, $row, $options = array()) {

		if (self::$useOldIcons === null) {
			self::init();
		}
		if (self::$useOldIcons) {

			if($table == 'pages') {
				$title = htmlspecialchars(t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle('pages', $row), 50));
			} else {
				$title = '[' . $table . ':'.$row['uid'];
			}

			return '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], t3lib_iconWorks::getIcon($tabke, $row),'').' border="0" title="' . $title . '" alt="" />';
		} else {
			return t3lib_iconWorks::getSpriteIconForRecord($table, $row, $options);
		}
	}

}
?>
