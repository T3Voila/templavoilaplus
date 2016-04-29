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
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Menu controller
 */
class MenuController extends TextController {

	/**
	 * @var string
	 */
	protected $previewField = 'menu_type';

	/**
	 * @param array $row
	 *
	 * @return string
	 */
	protected function getPreviewData($row) {
		return $this->getLanguageService()->sL(BackendUtility::getLabelFromItemlist('tt_content', $this->previewField, $row[$this->previewField]));
	}

	/**
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}
}
