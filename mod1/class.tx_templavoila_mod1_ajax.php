<?php
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
 * Class 'tx_templavoila_mod1_ajax' for the 'templavoila' extension.
 *
 * @author Nikolas Hagelstein <lists@shr-now.de>
 */
class tx_templavoila_mod1_ajax {

	/**
	 * @var \Extension\Templavoila\Service\ApiService
	 */
	private $apiObj;

	/**
	 * @return \tx_templavoila_mod1_ajax
	 */
	public function __construct() {
		$this->apiObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Extension\Templavoila\Service\ApiService::class);
	}

	/**
	 * Performs a move action for the requested element
	 *
	 * @param array $params
	 * @param object $ajaxObj
	 *
	 * @return void
	 */
	public function moveRecord($params, &$ajaxObj) {

		$sourcePointer = $this->apiObj->flexform_getPointerFromString(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('source'));

		$destinationPointer = $this->apiObj->flexform_getPointerFromString(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('destination'));

		$this->apiObj->moveElement($sourcePointer, $destinationPointer);
	}
}
