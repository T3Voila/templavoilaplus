<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004 Robert Lemke (rl@robertlemke.de)
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
 * Class 'tx_templavoila_tcemain' for the templavoila extension.
 *
 * $Id$
 *
 * @author     Robert Lemke <rl@robertlemke.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   57: class tx_templavoila_tcemain
 *
 *              SECTION: Public API (called by hook handler)
 *   77:     function processDatamap_postProcessFieldArray ($status, $table, $id, &$fieldArray, &$reference)
 *
 * TOTAL FUNCTIONS: 1
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

	// Include class for management of the relations inside the FlexForm XML:
require_once (t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_xmlrelhndl.php');

/**
 * Class being included by TCEmain using a hook
 *
 * @author	Robert Lemke <rl@robertlemke.de>
 * @package TYPO3
 * @subpackage templavoila
 */
class tx_templavoila_tcemain {

	/********************************************
	 *
	 * Public API (called by hook handler)
	 *
	 ********************************************/

	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain). We use it to check if a element reference
	 * has changed and update the table tx_templavoila_elementreferences accordingly
	 *
	 * @param	string		$status: The TCEmain operation status, fx. 'update'
	 * @param	string		$table: The table TCEmain is currently processing
	 * @param	string		$id: The records id (if any)
	 * @param	array		$fieldArray: The field names and their values to be processed
	 * @param	object		$reference: Reference to the parent object (TCEmain)
	 * @return	void
	 * @access public
	 */
	function processDatamap_postProcessFieldArray ($status, $table, $id, &$fieldArray, &$reference) {
		global $TYPO3_DB;

			// If the references for content element changed at the current page, save that information into the reference table:
		if ($status == 'update' && $table == 'pages' && isset ($fieldArray['tx_templavoila_flex'])) {
			$elementsOnThisPage = array ();

				// Getting value of the field containing the relations:
			$xmlContent = t3lib_div::xml2array($fieldArray['tx_templavoila_flex']);

				// And extract all content element uids and their context from the XML structure:
			if (is_array ($xmlContent['data'])) {
				foreach ($xmlContent['data'] as $currentSheet => $subArr) {
					if (is_array ($subArr)) {
						foreach ($subArr as $currentLanguage => $subSubArr) {
							if (is_array ($subSubArr)) {
								foreach ($subSubArr as $currentField => $subSubSubArr) {
									if (is_array ($subSubSubArr)) {
										foreach ($subSubSubArr as $currentValueKey => $uidList) {
											$uidsArr = t3lib_div::trimExplode (',', $uidList);
											if (is_array ($uidsArr)) {
												foreach ($uidsArr as $uid) {
													if (intval($uid)) {
														$elementsOnThisPage[] = array (
															'uid' => $uid,
															'skey' => $currentSheet,
															'lkey' => $currentLanguage,
															'vkey' => $currentValueKey,
														);
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}

				// Delete all reference information for this page ...
			$TYPO3_DB->exec_DELETEquery ('tx_templavoila_elementreferences', 'pid='.intval($id));

				// ... and create new information based on the current field array:
			foreach ($elementsOnThisPage as $elementArr) {
				$row = $elementArr;
				$row['pid'] = $id;
				$TYPO3_DB->exec_INSERTquery ('tx_templavoila_elementreferences', $row);
			}
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rlmp_tvnotes/class.templavoila_tcemain.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rlmp_tvnotes/class.tx_templavoila_tcemain.php']);
}

?>