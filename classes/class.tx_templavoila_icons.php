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
		'actions-system-list-open' => 	array('file' => 'gfx/list.gif'),
		'apps-pagetree-page-shortcut' => array('file' => 'gfx/shortcut.gif'),
		'actions-move-up' => 			array('file' => 'gfx/pilup.gif'),
		'actions-move-down' =>			array('file' => 'gfx/pildown.gif'),
		'actions-view-table-expand' => 	array('file' => 'gfx/plusbullet_list.gif'),
		'actions-view-table-collapse' => array('file' => 'gfx/minusbullet_list.gif'),
		'status-dialog-ok' => 			array('file' => 'gfx/icon_ok2.gif'),
		'status-dialog-warning' => 		array('file' => 'gfx/icon_warning2.gif'),
		'status-dialog-error' => 		array('file' => 'gfx/icon_fatalerror.gif'),
		'status-dialog-information' => 	array('file' => 'gfx/info.gif'),
		'status-dialog-notification' => 	array('file' => 'gfx/icon_note.gif'),
	);

	static $useOldIcons = null;
	static $useOldFlags = null;
	static $reInit = true;

	public static function init() {

		if(self::$useOldIcons !== null && self::$reInit == false) {
			return;
		}
		if(tx_templavoila_div::convertVersionNumberToInteger(TYPO3_version) < 4004000 || self::$useOldIcons) {
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
		if(tx_templavoila_div::convertVersionNumberToInteger(TYPO3_version) < 4005000 || self::$useOldFlags) {
			self::$useOldFlags = true;
		} else {
			self::$useOldFlags = false;
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
			$title = isset($options['title']) ? ' title="' . $options['title'] .'"': '';
			$wHattribs = isset(self::$oldIcons[$iconName]['attributes']) ? self::$oldIcons[$iconName]['attributes'] : '';
			return '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],self::$oldIcons[$iconName]['file'], $wHattribs) . $alt . $title . ' style="text-align:center; vertical-align: middle; border:0;" />';
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

		$title = htmlspecialchars(t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordIconAltText($row, $table), 50));
		if (self::$useOldIcons) {
			return '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], t3lib_iconWorks::getIcon($table, $row),'').' border="0" title="' . $title . '" style="text-align:center; vertical-align: top; border:0;" alt="" />';
		} else {
			if (!isset($options['title'])) {
				$options['title'] = $title;
			}
			return t3lib_iconWorks::getSpriteIconForRecord($table, $row, $options);
		}
	}

	/**
	 *
	 * @param string $lang
	 * @return string
	 */
	public static function getFlagIconForLanguage($flagName, $options = array()) {

		if (self::$useOldFlags === null) {
			self::init();
		}
		$flag = null;
		if (!strlen($flagName)) {
			$flagName = 'unknown';
		}

		if (self::$useOldFlags) {

			if ($flagName == 'unknown') {
				$flagName = $flagName . '.gif';
			} elseif($flagName == 'multiple') {
				$flagName = 'multi-language.gif';
			}
			$alt = isset($options['alt']) ? ' alt="' . $options['alt'] . '"' : ' alt=""';
			$title = isset($options['title']) ? ' title="' . $options['title'] . '"' : '';
			$flag = '<img src="' . self::getFlagIconFileForLanguage($flagName) . '"'. $title . $alt .'/>';
		} else {
			$flag = t3lib_iconWorks::getSpriteIcon('flags-' . $flagName, $options);
		}
		return $flag;
	}

	/**
	 *
	 * @param string $lang
	 * @return string
	 */
	public static function getFlagIconFileForLanguage($flagName) {

		if (self::$useOldFlags === null) {
			self::init();
		}
		$flag = null;
		if (!strlen($flagName)) {
			$flagName = 'unknown';
		}
		if (self::$useOldFlags) {
			$flagAbsPath = t3lib_div::getFileAbsFileName($GLOBALS['TCA']['sys_language']['columns']['flag']['config']['fileFolder']);
			$flagIconPath = $GLOBALS['BACK_PATH'] . '../' . substr($flagAbsPath, strlen(PATH_site));
			if (is_file($flagAbsPath . $flagName)) {
				$flag = $flagIconPath . $flagName;
			}
		} else {
				// same dirty trick as for #17286 in Core
			if(is_file(t3lib_div::getFileAbsFileName('EXT:t3skin/images/flags/'. $flagName . '.png', FALSE))) {
					// resolving extpath on its own because otherwise this might not return a relative path
				$flag = $GLOBALS['BACK_PATH'] . t3lib_extMgm::extRelPath('t3skin') . '/images/flags/' . $flagName . '.png';
			}
		}
		return $flag;
	}
}
?>
