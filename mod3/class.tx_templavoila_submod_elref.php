<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004  Robert Lemke (rl@robertlemke.de)
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
 * Submodule 'element references' for the templavoila tools module.
 *
 * $Id$
 *
 * @author   Robert Lemke <rl@robertlemke.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   53: class tx_templavoila_submod_elref
 *   63:     function main(&$parentObj)
 *  143:     function rebuildIndexTable ($lastProcessed, $startTime)
 *  180:     function indexElementsFromPage ($pageRecord)
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

/**
 * Submodule 'element references' for the templavoila tools module.
 *
 * @author		Robert Lemke <rl@robertlemke.de>
 * @package		TYPO3
 * @subpackage	tx_templavoila
 */
class tx_templavoila_submod_elref {

	var $totalPages;							// contains the number of total pages being processed, for statistical purposes only.

	/**
	 * Main function of the module.
	 *
	 * @param	[type]		$$parentObj: ...
	 * @return	void		Nothing.
	 */
	function main(&$parentObj)    {
		global $BACK_PATH;

		$content = '';

		if (t3lib_div::GPvar ('tx_templavoila_submod_elref_doit') && !t3lib_div::GPvar ('tx_templavoila_submod_elref_cancel')) {
			$lastProcessed = t3lib_div::GPvar('tx_templavoila_submod_elref_lastProcessed');
			$initialStartTime = intval(t3lib_div::GPvar('tx_templavoila_submod_elref_startTime')) ? intval(t3lib_div::GPvar('tx_templavoila_submod_elref_startTime')) : time();
			$elapsedTime = time() - $initialStartTime;
			$startTime = time();

				// Make sure that the "Now rebuilding ..." screen get's displayed before the first page is being processed:
			if (isset($lastProcessed)) {
				$lastProcessed = $this->rebuildIndexTable ($lastProcessed, $startTime);
			} else {
				$lastProcessed = 0;
			}

			if (intval ($this->totalPages) != 0) {
				$percentDone = intval(($lastProcessed == -1 ? $this->totalPages : $lastProcessed) / $this->totalPages * 100);
			} else {
				$percentDone = 0;
			}
			$content .= '
				'.($lastProcessed != -1 ? 'Now rebuilding the index table, please wait ...' : 'Done.') .'<br />
				<br />
				<table cellspacing="0" cellpadding="0">
					<tr>
						<td><strong>Elapsed time:</strong></td>
						<td colspan="3">'.$elapsedTime.' second(s)</td><td>&nbsp;</td>
					</tr>
					<tr>
						<td><strong>Pages processed:</strong>&nbsp;</td>
						<td style="width:100px;">'.($lastProcessed == -1 ? $this->totalPages : $lastProcessed).' / '.$this->totalPages.'</td>
						<td style="width:100px; border: 1px solid black;"><div style="float: left; width:'.$percentDone.'px; background-color:green;">&nbsp;</div><div style="float:left; width:'.(100 - $percentDone).'px; background-color:'.$parentObj->doc->bgColor2.';">&nbsp;</div></td>
						<td> '.$percentDone.' %</td>
					</tr>
				</table>
				<br />
				<input type="hidden" name="tx_templavoila_submod_elref_doit" value="yeah" />
				<input type="hidden" name="tx_templavoila_submod_elref_startTime" value="'.$initialStartTime.'" />
				<input type="hidden" name="tx_templavoila_submod_elref_lastProcessed" value="'.$lastProcessed.'" />
			';

			if ($lastProcessed != -1) {
				$content .= '<input type="submit" name="tx_templavoila_submod_elref_cancel" value="Cancel" onclick="window.stop(); " />';
				$content .= $parentObj->doc->wrapScriptTags('
					document.forms[0].submit();
				');
			} else {
				$content .= '<a href="index.php">Return to main menu</a>';
			}

		} else {
			$content .= '
				In the templavoila page module exists an information wizard displaying on which pages a specific content element is
				used (ie. from where it was referenced). This information is created whenever you create, move or delete a content
				element or a whole page and cached in an indexing table.<br />
				<br />
				If you think that, for some reason, this information is not valid anymore, you can rebuild the whole index table by using
				this little tool.<br />
				<br />
				<img '.t3lib_iconWorks::skinImg ($BACK_PATH, '../t3lib/gfx/icon_note.gif').' align="absmiddle" /> <strong>Note:</strong>
				Depending on the amount of content elements being used in your website this process might take quite a while!</br />
				<br />
				<br />
				<input name="tx_templavoila_submod_elref_doit" type="submit" value="Rebuild index table" />
			';
		}

		return $parentObj->doc->section('Element reference indexing', $content,0,1);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$lastProcessed: ...
	 * @param	[type]		$startTime: ...
	 * @return	[type]		...
	 */
	function rebuildIndexTable ($lastProcessed, $startTime) {
		global $TYPO3_DB;

		if ($lastProcessed == 0) {
			$res = $TYPO3_DB->exec_DELETEquery('tx_templavoila_elementreferences', '1');
		}


			// Get the total amount of page we'll have to process:
		$res = $TYPO3_DB->exec_SELECTquery('uid', 'pages', '1'.t3lib_beFunc::deleteClause('pages'));
		$totalPages = $this->totalPages = $TYPO3_DB->sql_num_rows ($res);

			// Fetch all page records except those we already have processed:
		$newLastProcessed = $lastProcessed + 1;
		$res = $TYPO3_DB->exec_SELECTquery('uid, tx_templavoila_flex', 'pages', '1'.t3lib_beFunc::deleteClause('pages'),	'',	'',	$newLastProcessed.','.$totalPages);

			// Process as many pages as possible during the specified time span:
		while (time() - $startTime < 5 && $newLastProcessed <= $totalPages) {
			$pageRecord = $TYPO3_DB->sql_fetch_assoc ($res);
			$this->indexElementsFromPage ($pageRecord);
			$newLastProcessed ++;
		}

		if ($newLastProcessed > $totalPages) {
			return -1;
		}

		return $newLastProcessed;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$pageRecord: ...
	 * @return	[type]		...
	 */
	function indexElementsFromPage ($pageRecord) {
		global $TYPO3_DB;

		$elementsOnThisPage = array ();

		if (is_array ($pageRecord)) {
				// Getting value of the field containing the relations:
			$xmlContent = t3lib_div::xml2array($pageRecord['tx_templavoila_flex']);

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

				// create new information based on the current field array:
			foreach ($elementsOnThisPage as $elementArr) {
				$row = $elementArr;
				$row['pid'] = $pageRecord['uid'];
				$TYPO3_DB->exec_INSERTquery ('tx_templavoila_elementreferences', $row);
			}
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod3/class.tx_templavoila_submodelref.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod3/class.tx_templavoila_submodelref.php']);
}

?>