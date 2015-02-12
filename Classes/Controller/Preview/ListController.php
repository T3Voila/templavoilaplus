<?php
namespace Extension\Templavoila\Controller\Preview;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * List controller
 */
class ListController extends TextController {

	/**
	 * @var string
	 */
	protected $previewField = 'list_type';

	/**
	 * @param array $row
	 *
	 * @return string
	 */
	protected function getPreviewData($row) {

		$extraInfo = $this->getExtraInfo($row);

		$info = htmlspecialchars(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL(\TYPO3\CMS\Backend\Utility\BackendUtility::getLabelFromItemlist('tt_content', 'list_type', $row['list_type'])));
		$info .= $extraInfo ? ' &ndash; ' . $extraInfo : '';

		return $info;
	}

	/**
	 * @param array $row
	 *
	 * @return string
	 */
	protected function getExtraInfo($row) {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$row['list_type']])) {
			$hookArr = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$row['list_type']];
		} elseif (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['_DEFAULT'])) {
			$hookArr = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['_DEFAULT'];
		} else {
			$hookArr = array();
		}

		$extraInfo = '';
		if (count($hookArr) > 0) {
			$_params = array('pObj' => &$this, 'row' => $row, 'infoArr' => array());
			foreach ($hookArr as $_funcRef) {
				$extraInfo .= \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}

		return $extraInfo ? $extraInfo : '';
	}
}
