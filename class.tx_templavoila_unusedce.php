<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Cleaner module: Finding unused content elements on pages.
 * User function called from tx_lowlevel_cleaner_core configured in ext_localconf.php
 * See system extension, lowlevel!
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   57: class tx_lowlevel_cleanflexform extends tx_lowlevel_cleaner_core
 *   64:     function tx_lowlevel_cleanflexform()
 *   89:     function main()
 *  117:     function main_parseTreeCallBack(&$pObj,$tableName,$uid,$echoLevel,$versionSwapmode,$rootIsVersion)
 *  148:     function main_autoFix($resultArray, $dryrun=TRUE)
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */





	// Include TemplaVoila API
require_once (t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_api.php');


/**
 * Finding unused content elements
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_lowlevel
 */
class tx_templavoila_unusedce extends tx_lowlevel_cleaner_core {

	var $checkRefIndex = TRUE;

	var $genTree_traverseDeleted = FALSE;
	var $genTree_traverseVersions = FALSE;


	/**
	 * Constructor
	 *
	 * @return	void
	 */
	function tx_templavoila_unusedce()	{
		parent::tx_lowlevel_cleaner_core();

			// Setting up help:
		$this->cli_options[] = array('--echotree level', 'When "level" is set to 1 or higher you will see the page of the page tree outputted as it is traversed. A value of 2 for "level" will show even more information.');
		$this->cli_options[] = array('--pid id', 'Setting start page in page tree. Default is the page tree root, 0 (zero)');
		$this->cli_options[] = array('--depth int', 'Setting traversal depth. 0 (zero) will only analyse start page (see --pid), 1 will traverse one level of subpages etc.');

		$this->cli_help['name'] = 'tx_templavoila_unusedce -- Find unused content elements on pages';
		$this->cli_help['description'] = trim('
Traversing page tree and finding content elements which are not used on pages and seems to have no references to them - hence is probably "lost" and could be deleted.

Automatic Repair:
- Silently deleting the content elements
- Run repair multiple times until no more unused elements remain.
');

		$this->cli_help['examples'] = '';
	}

	/**
	 *
	 *
	 * @return	array
	 */
	function main() {
		global $TYPO3_DB;

			// Initialize result array:
		$resultArray = array(
			'message' => $this->cli_help['name'].chr(10).chr(10).$this->cli_help['description'],
			'headers' => array(
				'all_unused' => array('List of all unused content elements','All elements means elements which are not used on that specific page. However, they could be referenced from another record. That is indicated by index "1" which is the number of references leading to the element.',1),
				'deleteMe' => array('List of elements that can be deleted','This is all elements which had no references to them and hence should be OK to delete right away.',2),
			),
			'all_unused' => array(),
			'deleteMe' => array(),
		);

		$startingPoint = $this->cli_isArg('--pid') ? t3lib_div::intInRange($this->cli_argValue('--pid'),0) : 0;
		$depth = $this->cli_isArg('--depth') ? t3lib_div::intInRange($this->cli_argValue('--depth'),0) : 1000;

		$this->resultArray = &$resultArray;
		$this->genTree($startingPoint,$depth,(int)$this->cli_argValue('--echotree'),'main_parseTreeCallBack');

		ksort($resultArray['all_unused']);
		ksort($resultArray['deleteMe']);

		return $resultArray;
	}

	/**
	 * Call back function for page tree traversal!
	 *
	 * @param	string		Table name
	 * @param	integer		UID of record in processing
	 * @param	integer		Echo level  (see calling function
	 * @param	string		Version swap mode on that level (see calling function
	 * @param	integer		Is root version (see calling function
	 * @return	void
	 */
	function main_parseTreeCallBack($tableName,$uid,$echoLevel,$versionSwapmode,$rootIsVersion)	{

		if ($tableName=='pages' && $uid>0)	{
			if (!$versionSwapmode)	{

					// Initialize TemplaVoila API class:
				$apiClassName = t3lib_div::makeInstanceClassName('tx_templavoila_api');
				$apiObj = new $apiClassName ('pages');

					// Fetch the content structure of page:
				$contentTreeData = $apiObj->getContentTree('pages', t3lib_BEfunc::getRecordRaw('pages','uid='.intval($uid)));
				if ($contentTreeData['tree']['ds_is_found'])	{
					$usedUids = array_keys($contentTreeData['contentElementUsage']);
					$usedUids[] = 0;

						// Look up all content elements that are NOT used on this page...
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery (
						'uid, header',
						'tt_content',
						'pid='.intval($uid).' '.
							'AND uid NOT IN ('.implode(',',$usedUids).') '.
							'AND t3ver_state!=1'.
							t3lib_BEfunc::deleteClause('tt_content').
							t3lib_BEfunc::versioningPlaceholderClause('tt_content'),
						'',
						'uid'
					);

						// Traverse, for each find references if any and register them.
					while(false !== ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)))	{

							// Look up references to elements:
						$refrows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
							'*',
							'sys_refindex',
							'ref_table='.$GLOBALS['TYPO3_DB']->fullQuoteStr('tt_content','sys_refindex').
								' AND ref_uid='.intval($row['uid']).
								' AND deleted=0'
						);

							// Register elements etc:
						$this->resultArray['all_unused'][$row['uid']] = array($row['header'],count($refrows));
						if ($echoLevel>2) echo chr(10).'			[tx_templavoila_unusedce:] tt_content:'.$row['uid'].' was not used on page...';
						if (!count($refrows))	{
							$this->resultArray['deleteMe'][$row['uid']] = $row['uid'];
							if ($echoLevel>2) echo ' and can be DELETED';
						} else {
							if ($echoLevel>2) echo ' but is referenced to ('.count($refrows).') so do not delete...';
						}
					}
				} else {
					if ($echoLevel>2) echo chr(10).'			[tx_templavoila_unusedce:] Did not check page - did not have a Data Structure set.';
				}
			} else {
				if ($echoLevel>2) echo chr(10).'			[tx_templavoila_unusedce:] Did not check page - was on offline page.';
			}
		}
	}

	/**
	 * Mandatory autofix function
	 * Will run auto-fix on the result array. Echos status during processing.
	 *
	 * @param	array		Result array from main() function
	 * @return	void
	 */
	function main_autoFix($resultArray)	{
		foreach($resultArray['deleteMe'] as $uid)	{
			echo 'Deleting "tt_content:'.$uid.'": ';
			if ($bypass = $this->cli_noExecutionCheck('tt_content:'.$uid))	{
				echo $bypass;
			} else {

					// Execute CMD array:
				$tce = t3lib_div::makeInstance('t3lib_TCEmain');
				$tce->stripslashes_values = FALSE;
				$tce->start(array(),array());
				$tce->deleteAction('tt_content', $uid);

					// Return errors if any:
				if (count($tce->errorLog))	{
					echo '	ERROR from "TCEmain":'.chr(10).'TCEmain:'.implode(chr(10).'TCEmain:',$tce->errorLog);
				} else echo 'DONE';
			}
			echo chr(10);
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_unusedce.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_unusedce.php']);
}

?>