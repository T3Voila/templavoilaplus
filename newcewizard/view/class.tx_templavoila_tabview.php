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


/**
 * This class implements a view for all tabs to provide common formatting
 * for elements.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_templavoila
 */
class tx_templavoila_tabView {

	/**
	 * TODO Fill this comment
	 *
	 * @var	string
	 */
	protected	$altRoot;

	/**
	 * Default values
	 * TODO Fill type
	 *
	 * @var	mixed
	 */
	protected	$defVals;

	/**
	 * Document class
	 *
	 * @var	template
	 */
	protected	$doc;

	protected	$enableFavoritesLink = false;

	/**
	 * A list of model elements (instances of tx_templavoila_contentElementDescriptor)
	 *
	 * @var	array
	 */
	protected	$modelList;

	/**
	 * Parent record as string (like 'pages:10:.....')
	 *
	 * @var	string
	 */
	protected	$parentRecord;

	/**
	 * Parent object
	 *
	 * @var tx_templavoila_newcewizard
	 */
	protected	$pObj;

	/**
	 * Creates an instance of this class
	 *
	 * @param	array	$modelList	Model list
	 */
	public function __construct(array $modelList, tx_templavoila_newcewizard &$pObj) {
		$this->pObj = $pObj;

		$this->id = $pObj->getId(t3lib_div::_GP('id'));
		$this->altRoot = t3lib_div::_GP('altRoot');
		$this->defVals = t3lib_div::_GP('defVals');
		$this->parentRecord = $this->getParentRecord();

		$this->modelList = $modelList;
		$this->doc = &$pObj->getDoc();
	}

	/**
	 * Renders the list.
	 *
	 * @return	string	Generated HTML
	 */
	public function render() {
		$content = '';
		if (count($this->modelList) > 0) {
			$columnCount = 2;
			$width = intval(100/$columnCount);
			$content .= '<table class="ce-elements">';

			$defVals = t3lib_div::implodeArrayForUrl('defVals', is_array($this->defVals) ? $this->defVals : array());
			$indexPhpLink = t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . '/' .
				t3lib_extMgm::siteRelPath('templavoila') . 'mod1/index.php' .
				'?id=' . $this->pObj->getId() .
				(is_array($this->altRoot) ? t3lib_div::implodeArrayForUrl('altRoot',$this->altRoot) : '') .
				'&createNewRecord=' . rawurlencode($this->parentRecord) . $defVals;
			$favTitle = htmlspecialchars($GLOBALS['LANG']->getLL('newcewizard.add_favorite'));
			$favLink = '';

			for ($i = 0; $i < count($this->modelList); $i++) {
				if (($i % $columnCount) == 0) {
					$content .= '<tr>';
				}

				$model = $this->modelList[$i];
				/* @var $model tx_templavoila_contentElementDescriptor */
				$serialized = base64_encode(serialize($model));
				$link = $indexPhpLink . $model->getParameters() . '&amp;ser=' . rawurlencode($serialized);
				$image = t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], $model->getImage(), '', 1);
				$description = trim($model->getDescription());
				if ($this->enableFavoritesLink) {
					$favLink = '<a href="javascript:void(0)" title="' . $favTitle . '">' .
						'<img ' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],
							'../' . t3lib_extMgm::siteRelPath('templavoila') .
							'newcewizard/images/add_favorite.png') . ' vspace="5" alt="' .
							$favTitle . '" />' .
						'</a>';
				}
				$content .= '<td width="' . $width . '%" class="ce-cell">' .
					'<table><tr><td width="35" align="center"><a href="' . $link . '">' .
						'<img src="' . htmlspecialchars($image) . '" alt="" /></a>' .
						$favLink .
						'</td><td>' .
						'<div class="title">' .
							'<a href="' . $link . '">' .
							htmlspecialchars($model->getTitle()) .
							'</a>' .
						'</div><div class="desc">' .
							htmlspecialchars($description) .
						'</div>' .
					'</td></tr></table></td>';

				if (($i % $columnCount) == ($columnCount - 1)) {
					$content .= '</tr>';
				}
			}
			$nonFishedColumns = intval(count($this->modelList)/$columnCount);
			if ($nonFishedColumns > 0) {
				for ($i = 0; $i < $columnCount - $nonFishedColumns; $i++) {
					$content .= '</td><td> ';
				}
				$content .= '</td></tr>';
			}
			$content .= '</table>';
		}
		return $content;
	}

	/**
	 * Creates parent record
	 *
	 * @return	string	Parent record
	 */
	protected function getParentRecord() {
		$parentRecord = t3lib_div::_GP('parentRecord');
		if (!$parentRecord) {
			$mainContentAreaFieldName = $this->pObj->getApiObj()->ds_getFieldNameByColumnPosition($this->id, 0);
			if ($mainContentAreaFieldName != false) {
				$parentRecord = 'pages:' . $this->id . ':sDEF:lDEF:' .
					$mainContentAreaFieldName . ':vDEF:0';
			}
		}
		return $parentRecord;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/class.tx_templavoila_tabbase.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/class.tx_templavoila_tabbase.php']);
}

?>
