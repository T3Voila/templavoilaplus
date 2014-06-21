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
 * Ext update class
 */
class ext_update {

	/**
	 * Main function, returning the HTML content of the module
	 *
	 * @return string HTML
	 */
	public function main() {
		/** @var $dsWizard tx_templavoila_staticds_wizard */
		$dsWizard = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_templavoila_staticds_wizard');
		return $dsWizard->staticDsWizard();
	}

	/**
	 * Checks how many rows are found and returns true if there are any
	 * (this function is called from the extension manager)
	 *
	 * @param string $what What should be updated
	 *
	 * @return boolean
	 */
	public function access($what = 'all') {
		return \Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->isAdmin();
	}
}
