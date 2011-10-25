<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Tolleiv Nietsch <templavoila@tolleiv.de>
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

class tx_templavoila_staticds_check {

	/**
	 * @param $params
	 * @param $tsObj
	 * @return string
	 */
	public function displayMessage(&$params, &$tsObj) {

		if (!$this->staticDsIsEnabled() || $this->datastructureDbCount() == 0) {
			return;
		}

		if (tx_templavoila_div::convertVersionNumberToInteger(TYPO3_version) < 4005000) {
			$link = 'index.php?&amp;id=0&amp;CMD[showExt]=templavoila&amp;SET[singleDetails]=updateModule';
		} else {
			$link = 'mod.php?&amp;id=0&amp;M=tools_em&amp;CMD[showExt]=templavoila&amp;SET[singleDetails]=updateModule';
		}


		$out = '
		<div style="position:absolute;top:10px;right:10px; width:300px;">
			<div class="typo3-message message-information">
				<div class="message-header">' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/locallang.xml:extconf.staticWizard.header') . '</div>
				<div class="message-body">
					' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/locallang.xml:extconf.staticWizard.message') . '<br />
					<a style="text-decoration:underline;" href="' . $link . '">
					' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/locallang.xml:extconf.staticWizard.link') . '</a>
				</div>
			</div>
		</div>
		';

		return $out;
	}

	/**
	 * @return
	 */
	protected function staticDsIsEnabled() {
		$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);
		return $conf['staticDS.']['enable'];
	}

	/**
	 * @return int
	 */
	protected function datastructureDbCount() {
		return $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'tx_templavoila_datastructure', 'deleted=0');
	}
}

?>