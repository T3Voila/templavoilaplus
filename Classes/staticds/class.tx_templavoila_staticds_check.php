<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Tolleiv Nietsch <tolleiv.nietsch@typo3.org>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Static DS check
 */
class tx_templavoila_staticds_check {

	/**
	 * Display message
	 *
	 * @param array $params
	 * @param \TYPO3\CMS\Extensionmanager\ViewHelpers\Form\TypoScriptConstantsViewHelper $tsObj
	 * @return string
	 */
	public function displayMessage(&$params, &$tsObj) {
		if (!$this->staticDsIsEnabled() || $this->datastructureDbCount() === 0) {
			return;
		}

		$link = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl(
			'tools_ExtensionmanagerExtensionmanager',
			array(
				'tx_extensionmanager_tools_extensionmanagerextensionmanager[extensionKey]' => 'templavoila',
				'tx_extensionmanager_tools_extensionmanagerextensionmanager[action]' => 'show',
				'tx_extensionmanager_tools_extensionmanagerextensionmanager[controller]' => 'UpdateScript'
			)
		);

		return '
		<div style="position:absolute;top:10px;right:10px; width:300px;">
			<div class="typo3-message message-information">
				<div class="message-header">' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL('LLL:EXT:templavoila/Resources/Private/Language/locallang.xml:extconf.staticWizard.header') . '</div>
				<div class="message-body">
					' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL('LLL:EXT:templavoila/Resources/Private/Language/locallang.xml:extconf.staticWizard.message') . '<br />
					<a style="text-decoration:underline;" href="' . $link . '">
					' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL('LLL:EXT:templavoila/Resources/Private/Language/locallang.xml:extconf.staticWizard.link') . '</a>
				</div>
			</div>
		</div>
		';
	}

	/**
	 * Is static DS enabled?
	 *
	 * @return bool
	 */
	protected function staticDsIsEnabled() {
		$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);
		return (bool)$conf['staticDS.']['enable'];
	}

	/**
	 * Get data structure count
	 *
	 * @return int
	 */
	protected function datastructureDbCount() {
		return \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTcountRows(
			'*',
			'tx_templavoila_datastructure',
			'deleted=0'
		);
	}
}
