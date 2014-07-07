<?php
namespace Extension\Templavoila\Service\UserFunc;

/**
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
 * Class 'tx_templavoila_label' for the 'templavoila' extension.
 *
 * This library contains several functions for displaying the labels in the list view.
 *
 * @author  Michael Klapper <michael.klapper@aoemedia.de>
 */
class Label {

	/**
	 * Retrive the label for TCAFORM title attribute.
	 *
	 * @param array $params Current record array
	 * @param object
	 *
	 * @return void
	 */
	public function getLabel(&$params, &$pObj) {
		$params['title'] = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL($params['row']['title']);
	}
}
