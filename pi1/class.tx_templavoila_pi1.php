<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003, 2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * Plugin 'Flexible Content' for the 'templavoila' extension.
 *
 * $Id$
 *
 * @author    Kasper Skaarhoj <kasper@typo3.com>
 * @coauthor  Robert Lemke <robert@typo3.org>
 */

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_htmlmarkup.php');
require_once(PATH_t3lib . 'class.t3lib_flexformtools.php');

/**
 * Plugin 'Flexible Content' for the 'templavoila' extension.
 *
 * @author    Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_templavoila
 */
class tx_templavoila_pi1 extends tslib_pibase {
	var $prefixId = 'tx_templavoila_pi1';        // Same as class name
	var $scriptRelPath = 'pi1/class.tx_templavoila_pi1.php';    // Path to this script relative to the extension dir.
	var $extKey = 'templavoila';    // The extension key.

	var $inheritValueFromDefault=1;		// If set, children-translations will take the value from the default if "false" (zero or blank)

	static $enablePageRenderer = TRUE;

	/**
	 * Markup object
	 *
	 * @var tx_templavoila_htmlmarkup
	 */
	var $markupObj;

	/**
	 * Main function for rendering of Flexible Content elements of TemplaVoila
	 *
	 * @param	string		Standard content input. Ignore.
	 * @param	array		TypoScript array for the plugin.
	 * @return	string		HTML content for the Flexible Content elements.
	 */
    function main($content,$conf)    {
		$this->initVars($conf);

		return $this->renderElement($this->cObj->data, 'tt_content');
    }

	/**
	 * Main function for rendering records from system tables (like fe_users) using TemplaVoila. Function creates fake flexform, ds and to fields for the record and calls {@link #renderElement($row,$table) renderElement} for processing.
	 *
	 * <strong>This is still undocumented and unsupported! Do not use unless you are ready to risk!</strong>.
	 *
	 * Example TS for listing FE users:
	 * <code><pre>
	 * lib.members = CONTENT
	 * lib.members {
	 * 	select {
	 * 		pidInList = {$styles.content.loginform.pid}
	 * 		orderBy = tx_lglalv_mysorting,uid
	 * 	}
	 * 	table = fe_users
	 * 	renderObj = USER
	 * 	renderObj {
	 * 		userFunc = tx_templavoila_pi1->main_record
	 *		ds = 2
	 * 		to = 4
	 * 		table = fe_users
	 *	}
	 * }
	 * </pre/></code>
	 * This example lists all frontend users using DS with DS=2 and TO=4.
	 *
	 * Required configuration options (in <code>$conf</code>):
	 * <ul>
	 * 	<li><code>ds</code> - DS UID to use
	 * 	<li><code>to</code> - TO UID to use
	 * 	<li><code>table</code> - table of the record
	 * </ul>
	 *
	 * @param string $content Unused
	 * @param array $conf Configuration (see above for entries)
	 * @return string Generated content
	 * @todo Create a new content element with this functionality and DS/TO selector?
	 * @todo Create TS element with this functionality?
	 * @todo Support sheet selector?
	 */
    function main_record($content, $conf) {
		$this->initVars($conf);

		// Make a copy of the data, do not spoil original!
		$data = $this->cObj->data;

		// setup ds/to
		$data['tx_templavoila_ds'] = $conf['ds'];
		$data['tx_templavoila_to'] = $conf['to'];

		$dsRepo = t3lib_div::makeInstance('tx_templavoila_datastructureRepository');

		// prepare fake flexform
		$values = array();
		foreach ($data as $k => $v) {
			// Make correct language identifiers here!
			if ($GLOBALS['TSFE']->sys_language_isocode) {

				try {
					$dsObj = $dsRepo->getDatastructureByUidOrFilename($data['tx_templavoila_ds']);
					$DS = $dsObj->getDataprotArray();
				} catch (InvalidArgumentException $e) {
					$DS = null;
				}
				if (is_array($DS)) {
					$langChildren = $DS['meta']['langChildren'] ? 1 : 0;
					$langDisabled = $DS['meta']['langDisable'] ? 1 : 0;
					$lKey = (!$langDisabled && !$langChildren) ? 'l'.$GLOBALS['TSFE']->sys_language_isocode : 'lDEF';
					$vKey = (!$langDisabled && $langChildren) ? 'v'.$GLOBALS['TSFE']->sys_language_isocode : 'vDEF';
				}
				else {
					return $this->formatError('
						Couldn\'t find a Data Structure set with uid/file='.$conf['ds'].'
						Please put correct DS and TO into your TS setup first.');
				}
			}
			else {
				$lKey = 'lDEF'; $vKey = 'vDEF';
			}
		    $values['data']['sDEF'][$lKey][$k][$vKey] = $v;
		}
        $ff = t3lib_div::makeInstance('t3lib_flexformtools');
        $data['tx_templavoila_flex'] = $ff->flexArray2xml($values);

		return $this->renderElement($data, $conf['table']);
    }

	/**
	 * Main function for rendering of Page Templates of TemplaVoila
	 *
	 * @param	string		Standard content input. Ignore.
	 * @param	array		TypoScript array for the plugin.
	 * @return	string		HTML content for the Page Template elements.
	 */
    function main_page($content,$conf)    {
		$this->initVars($conf);

			// Current page record which we MIGHT manipulate a little:
		$pageRecord = $GLOBALS['TSFE']->page;

			// Find DS and Template in root line IF there is no Data Structure set for the current page:
		if (!$pageRecord['tx_templavoila_ds'])	{
			foreach($GLOBALS['TSFE']->tmpl->rootLine as $pRec)	{
				if ($pageRecord['uid'] != $pRec['uid'])	{
					if ($pRec['tx_templavoila_next_ds'])	{	// If there is a next-level DS:
						$pageRecord['tx_templavoila_ds'] = $pRec['tx_templavoila_next_ds'];
						$pageRecord['tx_templavoila_to'] = $pRec['tx_templavoila_next_to'];
					} elseif ($pRec['tx_templavoila_ds'])	{	// Otherwise try the NORMAL DS:
						$pageRecord['tx_templavoila_ds'] = $pRec['tx_templavoila_ds'];
						$pageRecord['tx_templavoila_to'] = $pRec['tx_templavoila_to'];
					}
				} else break;
			}
		}

			// "Show content from this page instead" support. Note: using current DS/TO!
		if ($pageRecord['content_from_pid']) {
		    $ds = $pageRecord['tx_templavoila_ds'];
		    $to = $pageRecord['tx_templavoila_to'];
		    $pageRecord = $GLOBALS['TSFE']->sys_page->getPage($pageRecord['content_from_pid']);
		    $pageRecord['tx_templavoila_ds'] = $ds;
		    $pageRecord['tx_templavoila_to'] = $to;
		}

		return $this->renderElement($pageRecord, 'pages');
    }

	/**
	 * Will set up various stuff in the class based on input TypoScript
	 *
	 * @param	array		TypoScript options
	 * @return	void
	 */
	function initVars($conf)	{
		$this->inheritValueFromDefault = $conf['dontInheritValueFromDefault'] ? 0 : 1;
			// naming choosen to fit the regular TYPO3 integrators needs ;)
		self::$enablePageRenderer = isset($conf['advancedHeaderInclusion']) ? $conf['advancedHeaderInclusion'] : self::$enablePageRenderer;
		$this->conf=$conf;
	}

	/**
	 * Common function for rendering of the Flexible Content / Page Templates.
	 * For Page Templates the input row may be manipulated to contain the proper reference to a data structure (pages can have those inherited which content elements cannot).
	 *
	 * @param	array		Current data record, either a tt_content element or page record.
	 * @param	string		Table name, either "pages" or "tt_content".
	 * @return	string		HTML output.
	 */
	function renderElement($row,$table)	{
		global $TYPO3_CONF_VARS;

			// First prepare user defined objects (if any) for hooks which extend this function:
		$hookObjectsArr = array();
		if (is_array ($TYPO3_CONF_VARS['EXTCONF']['templavoila']['pi1']['renderElementClass'])) {
			foreach ($TYPO3_CONF_VARS['EXTCONF']['templavoila']['pi1']['renderElementClass'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}

			// Hook: renderElement_preProcessRow
		foreach($hookObjectsArr as $hookObj)	{
			if (method_exists ($hookObj, 'renderElement_preProcessRow')) {
				$hookObj->renderElement_preProcessRow($row, $table, $this);
			}
		}

		$dsRepo = t3lib_div::makeInstance('tx_templavoila_datastructureRepository');
		try {
			$dsObj = $dsRepo->getDatastructureByUidOrFilename($row['tx_templavoila_ds']);
			$DS = $dsObj->getDataprotArray();
		} catch (InvalidArgumentException $e) {
			$DS = null;
		}

			// If a Data Structure was found:
		if (is_array($DS))	{

				// Sheet Selector:
			if ($DS['meta']['sheetSelector'])	{
					// <meta><sheetSelector> could be something like "EXT:user_extension/class.user_extension_selectsheet.php:&amp;user_extension_selectsheet"
				$sheetSelector = &t3lib_div::getUserObj($DS['meta']['sheetSelector']);
				$renderSheet = $sheetSelector->selectSheet();
			} else {
				$renderSheet = 'sDEF';
			}

				// Initialize:
			$langChildren = $DS['meta']['langChildren'] ? 1 : 0;
			$langDisabled = $DS['meta']['langDisable'] ? 1 : 0;
			list ($dataStruct, $sheet, $singleSheet) = t3lib_div::resolveSheetDefInDS($DS,$renderSheet);

				// Data from FlexForm field:
			$data = t3lib_div::xml2array($row['tx_templavoila_flex']);

			$lKey = ($GLOBALS['TSFE']->sys_language_isocode && !$langDisabled && !$langChildren) ? 'l'.$GLOBALS['TSFE']->sys_language_isocode : 'lDEF';

				/* Hook to modify language key - e.g. used for EXT:languagevisibility */
			foreach($hookObjectsArr as $hookObj)	{
				if (method_exists ($hookObj, 'renderElement_preProcessLanguageKey')) {
					$lKey = $hookObj->renderElement_preProcessLanguageKey($row, $table, $lKey, $langDisabled, $langChildren, $this);
				}
			}

			$dataValues = is_array($data['data']) ? $data['data'][$sheet][$lKey] : '';
			if (!is_array($dataValues))	$dataValues = array();

				// Init mark up object.
			$this->markupObj = t3lib_div::makeInstance('tx_templavoila_htmlmarkup');
			$this->markupObj->htmlParse = t3lib_div::makeInstance('t3lib_parsehtml');

				// Get template record:
			if ($row['tx_templavoila_to'])	{

					// Initialize rendering type:
				if ($this->conf['childTemplate'])	{
					$renderType = $this->conf['childTemplate'];
					if (substr($renderType, 0, 9) == 'USERFUNC:') {
						$conf = array(
							'conf' => is_array($this->conf['childTemplate.']) ? $this->conf['childTemplate.'] : array(),
							'toRecord' => $row
						);
						$renderType = t3lib_div::callUserFunction(substr($renderType, 9), $conf, $this);
					}
				} else {	// Default:
					$renderType = t3lib_div::_GP('print') ? 'print' : '';
				}

					// Get Template Object record:
				$TOrec = $this->markupObj->getTemplateRecord($row['tx_templavoila_to'], $renderType, $GLOBALS['TSFE']->sys_language_uid);
				if (is_array($TOrec))	{

						// Get mapping information from Template Record:
					$TO = unserialize($TOrec['templatemapping']);
					if (is_array($TO))	{

							// Get local processing:
						$TOproc = array();
						if ($TOrec['localprocessing']) {
							$TOproc = t3lib_div::xml2array($TOrec['localprocessing']);
							if (!is_array($TOproc))	{
								// Must be a error!
								// TODO log to TT the content of $TOproc (it is a error message now)
								$TOproc = array();
							}
						}
							// Processing the data array:
						if ($GLOBALS['TT']->LR) $GLOBALS['TT']->push('Processing data');
							$vKey = ($GLOBALS['TSFE']->sys_language_isocode && !$langDisabled && $langChildren) ? 'v'.$GLOBALS['TSFE']->sys_language_isocode : 'vDEF';

								/* Hook to modify value key - e.g. used for EXT:languagevisibility */
							foreach($hookObjectsArr as $hookObj)	{
								if (method_exists ($hookObj, 'renderElement_preProcessValueKey')) {
									$vKey = $hookObj->renderElement_preProcessValueKey($row, $table, $vKey, $langDisabled, $langChildren, $this);
								}
							}

							$TOlocalProc = $singleSheet ? $TOproc['ROOT']['el'] : $TOproc['sheets'][$sheet]['ROOT']['el'];
								// Store the original data values before the get processed.
							$originalDataValues = $dataValues;
							$this->processDataValues($dataValues,$dataStruct['ROOT']['el'],$TOlocalProc,$vKey, ($this->conf['renderUnmapped'] !== 'false' ? TRUE : $TO['MappingInfo']['ROOT']['el']));

								// Hook: renderElement_postProcessDataValues
							foreach ($hookObjectsArr as $hookObj) {
								if (method_exists($hookObj, 'renderElement_postProcessDataValues')) {
									$flexformData = array(
										'table' => $table,
										'row'   => $row,
										'sheet' => $renderSheet,
										'sLang' => $lKey,
										'vLang' => $vKey
									);
									$hookObj->renderElement_postProcessDataValues($DS, $dataValues, $originalDataValues, $flexformData);
								}
							}

						if ($GLOBALS['TT']->LR) $GLOBALS['TT']->pull();

							// Merge the processed data into the cached template structure:
						if ($GLOBALS['TT']->LR) $GLOBALS['TT']->push('Merge data and TO');
								// Getting the cached mapping data out (if sheets, then default to "sDEF" if no mapping exists for the specified sheet!)
							$mappingDataBody = $singleSheet ? $TO['MappingData_cached'] : (is_array($TO['MappingData_cached']['sub'][$sheet]) ? $TO['MappingData_cached']['sub'][$sheet] : $TO['MappingData_cached']['sub']['sDEF']);
							$content = $this->markupObj->mergeFormDataIntoTemplateStructure($dataValues,$mappingDataBody,'',$vKey);

							$this->markupObj->setHeaderBodyParts($TO['MappingInfo_head'],$TO['MappingData_head_cached'],$TO['BodyTag_cached'], self::$enablePageRenderer);

						if ($GLOBALS['TT']->LR) $GLOBALS['TT']->pull();

							// Edit icon (frontend editing):
						$eIconf = array('styleAttribute'=>'position:absolute;');
						if ($table=='pages')	$eIconf['beforeLastTag']=-1;	// For "pages", set icon in top, not after.
						$content = $this->pi_getEditIcon($content,'tx_templavoila_flex','Edit element',$row,$table,$eIconf);

							// Visual identification aids:

						$feedit = is_object($GLOBALS['BE_USER']) && method_exists($GLOBALS['BE_USER'], 'isFrontendEditingActive') && $GLOBALS['BE_USER']->isFrontendEditingActive();

						if ($GLOBALS['TSFE']->fePreview && $GLOBALS['TSFE']->beUserLogin && !$GLOBALS['TSFE']->workspacePreview && !$this->conf['disableExplosivePreview'] && !$feedit)	{
							$content = $this->visualID($content, $srcPointer, $DSrec, $TOrec, $row, $table);
						}
					} else {
						$content = $this->formatError('Template Object could not be unserialized successfully.
							Are you sure you saved mapping information into Template Object with UID "'.$row['tx_templavoila_to'].'"?');
					}
				} else {
					$content = $this->formatError('Couldn\'t find Template Object with UID "'.$row['tx_templavoila_to'].'".
						Please make sure a Template Object is accessible.');
				}
			} else {
				$content = $this->formatError('You haven\'t selected a Template Object yet for table/uid "'.$table.'/'.$row['uid'].'".
					Without a Template Object TemplaVoila cannot map the XML content into HTML.
					Please select a Template Object now.');
			}
		} else {
			$content = $this->formatError('
				Couldn\'t find a Data Structure set for table/row "'.$table.':'.$row['uid'].'".
				Please select a Data Structure and Template Object first.');
		}

		return $content;
	}

	/**
	 * Performing pre-processing of the data array.
	 * This will transform the data in the data array according to various rules before the data is merged with the template HTML
	 * Notice that $dataValues is changed internally as a reference so the function returns no content but internally changes the passed variable for $dataValues.
	 *
	 * @param	array		The data values from the XML file (converted to array). Passed by reference.
	 * @param	array		The data structure definition which the data in the dataValues array reflects.
	 * @param	array		The local XML processing information found in associated Template Objects (TO)
	 * @param	string		Value key
	 * @param	mixed		Mapping information
	 * @return	void
	 */
	function processDataValues(&$dataValues,$DSelements,$TOelements,$valueKey='vDEF', $mappingInfo=TRUE)	{
		if (is_array($DSelements) && is_array($dataValues))	{

				// Create local processing information array:
			$LP = array();
			foreach($DSelements as $key => $dsConf)	{
				if ($mappingInfo === TRUE || array_key_exists($key, $mappingInfo)) {
					if ($DSelements[$key]['type']!='array')	{	// For all non-arrays:
							// Set base configuration:
						$LP[$key] = $DSelements[$key]['tx_templavoila'];
							// Overlaying local processing:
						if (is_array($TOelements[$key]['tx_templavoila']))	{
							if (is_array($LP[$key]))	{
								$LP[$key] = t3lib_div::array_merge_recursive_overrule($LP[$key],$TOelements[$key]['tx_templavoila']);
							} else {
								$LP[$key] = $TOelements[$key]['tx_templavoila'];
							}
						}
					}
				}
			}

				// Prepare a fake data record for cObj (important to do now before processing takes place):
			$dataRecord=array();
			foreach($dataValues as $key => $values)	{
				$dataRecord[$key] = $this->inheritValue($dataValues[$key],$valueKey,$LP[$key]['langOverlayMode']);
			}

            // Check if information about parent record should be set. Note: we do not push/pop registers here because it may break LOAD_REGISTER/RESTORE_REGISTER data transfer between FCEs!
            $savedParentInfo = array();
            $registerKeys = array();
            if (is_array($this->cObj->data)) {

                $tArray = $this->cObj->data;
                ksort($tArray);
                $checksum = md5(serialize($tArray));

                $sameParent = false;
                if (isset($GLOBALS['TSFE']->register['tx_templavoila_pi1.parentRec.__SERIAL'])) {
                    $sameParent = ($checksum === $GLOBALS['TSFE']->register['tx_templavoila_pi1.parentRec.__SERIAL']);
                }

                if (!$sameParent) {
                    // Step 1: save previous parent records from registers. This happens when pi1 is called for FCEs on a page.
                    $unsetKeys = array ();
                    foreach ($GLOBALS['TSFE']->register as $dkey => $dvalue) {
						if (preg_match('/^tx_templavoila_pi1\.parentRec\./', $dkey)) {
							$savedParentInfo[$dkey] = $dvalue;
							$unsetKeys[] = $dkey;
						}
						if (preg_match('/^tx_templavoila_pi1\.(nested_fields|current_field)/', $dkey)) {
							$savedParentInfo[$dkey] = $dvalue;
						}
                    }

                    // Step 2: unset previous parent info
                    foreach ($unsetKeys as $dkey) {
						unset ($GLOBALS['TSFE']->register[$dkey]);
                    }
                    unset($unsetKeys); // free memory

                    // Step 3: set new parent record to register
                    $registerKeys = array ();
                    foreach ($this->cObj->data as $dkey => $dvalue) {
						$registerKeys[] = $tkey = 'tx_templavoila_pi1.parentRec.' . $dkey;
						$GLOBALS['TSFE']->register[$tkey] = $dvalue;
                    }

                    // Step 4: update checksum
                    $GLOBALS['TSFE']->register['tx_templavoila_pi1.parentRec.__SERIAL'] = $checksum;
                    $registerKeys[] = 'tx_templavoila_pi1.parentRec.__SERIAL';
                }
            }

				// For each DS element:
			foreach($DSelements as $key => $dsConf)	{
					// Store key of DS element and the parents being handled in global register
				if (isset($savedParentInfo['nested_fields'])) {
					$GLOBALS['TSFE']->register['tx_templavoila_pi1.nested_fields'] = $savedParentInfo['nested_fields'] . ',' . $key;
				} else {
					$GLOBALS['TSFE']->register['tx_templavoila_pi1.nested_fields'] = $key;
				}
				$GLOBALS['TSFE']->register['tx_templavoila_pi1.current_field'] = $key;

						// Array/Section:
				if ($DSelements[$key]['type']=='array')	{
					/* no DS-childs: bail out
					 * no EL-childs: progress (they may all be TypoScript elements without visual representation)
					 */
					if (is_array($DSelements[$key]['el'])/* &&
					    is_array($TOelements[$key]['el'])*/) {
						if (!isset($dataValues[$key]['el']))
							$dataValues[$key]['el'] = array();

						if ($DSelements[$key]['section'] && is_array($dataValues[$key]['el']))	{
							$registerCounter = 1;
							foreach($dataValues[$key]['el'] as $ik => $el)	{
								$GLOBALS['TSFE']->register["tx_templavoila_pi1.sectionPos"] = $registerCounter;
								$GLOBALS['TSFE']->register["tx_templavoila_pi1.sectionCount"] = count($dataValues[$key]['el']);
								$GLOBALS['TSFE']->register["tx_templavoila_pi1.sectionIsFirstItem"] = ($registerCounter == 1);
								$GLOBALS['TSFE']->register["tx_templavoila_pi1.sectionIsLastItem"] = count($dataValues[$key]['el']) == $registerCounter;
								$registerCounter++;
								if (is_array($el))	{
									$theKey = key($el);
									if (is_array($dataValues[$key]['el'][$ik][$theKey]['el']))	{
										$this->processDataValues($dataValues[$key]['el'][$ik][$theKey]['el'],$DSelements[$key]['el'][$theKey]['el'],$TOelements[$key]['el'][$theKey]['el'],$valueKey);

											// If what was an array is returned as a non-array (eg. string "__REMOVE") then unset the whole thing:
										if (!is_array($dataValues[$key]['el'][$ik][$theKey]['el']))	{
											unset($dataValues[$key]['el'][$ik]);
										}
									}
								}
							}
						} else {
							$this->processDataValues($dataValues[$key]['el'],$DSelements[$key]['el'],$TOelements[$key]['el'],$valueKey);
						}
					}
				} else {

						// Language inheritance:
					if ($valueKey!='vDEF')	{
						$dataValues[$key][$valueKey] = $this->inheritValue($dataValues[$key],$valueKey,$LP[$key]['langOverlayMode']);

							// The value "__REMOVE" will trigger removal of the item!
						if (is_array($dataValues[$key][$valueKey]) && !strcmp($dataValues[$key][$valueKey]['ERROR'],'__REMOVE'))	{
							$dataValues = '__REMOVE';
							return;
						}
					}

					$tsparserObj = t3lib_div::makeInstance('t3lib_TSparser');

					$cObj =t3lib_div::makeInstance('tslib_cObj');
					$cObj->setParent($this->cObj->data,$this->cObj->currentRecord);
					$cObj->start($dataRecord,'_NO_TABLE');

					$cObj->setCurrentVal($dataValues[$key][$valueKey]);

	                    // Render localized labels for 'select' elements:
                    if ($DSelements[$key]['TCEforms']['config']['type'] == 'select') {
						if (substr($dataValues[$key][$valueKey], 0, 4) == 'LLL:') {
							$tempLangVal = $GLOBALS['TSFE']->sL($dataValues[$key][$valueKey]);
							if ($tempLangVal != '') {
								$dataValues[$key][$valueKey] = $tempLangVal;
							}
							unset($tempLangVal);
						}
                    }

						// TypoScript / TypoScriptObjPath:
					if (trim($LP[$key]['TypoScript']) || trim($LP[$key]['TypoScriptObjPath']))	{

						if (trim($LP[$key]['TypoScript']))	{

								// If constants were found locally/internally in DS/TO:
							if (is_array($LP[$key]['TypoScript_constants']))	{
								foreach($LP[$key]['TypoScript_constants'] as $constant => $value)	{

										// First, see if the constant is itself a constant referring back to TypoScript Setup Object Tree:
									if (substr(trim($value),0,2)=='{$' && substr(trim($value),-1)=='}')	{
										$objPath = substr(trim($value),2,-1);

											// If no value for this object path reference was found, get value:
										if (!isset($GLOBALS['TSFE']->applicationData['tx_templavoila']['TO_constantCache'][$objPath]))	{
												// Get value from object path:
											$cF = t3lib_div::makeInstance('t3lib_TSparser');
											list($objPathValue) = $cF->getVal($objPath,$GLOBALS['TSFE']->tmpl->setup);
												// Set value in cache table:
											$GLOBALS['TSFE']->applicationData['tx_templavoila']['TO_constantCache'][$objPath].=''.$objPathValue;
										}
											// Setting value to the value of the TypoScript Setup object path referred to:
										$value = $GLOBALS['TSFE']->applicationData['tx_templavoila']['TO_constantCache'][$objPath];
									}

										// Substitute constant:
									$LP[$key]['TypoScript'] = str_replace('{$'.$constant.'}',$value,$LP[$key]['TypoScript']);
								}
							}

								// If constants were found in Plugin configuration, "plugins.tx_templavoila_pi1.TSconst":
							if (is_array($this->conf['TSconst.']))	{
								foreach($this->conf['TSconst.'] as $constant => $value)	{
									if (!is_array($value))	{
											// Substitute constant:
										$LP[$key]['TypoScript'] = str_replace('{$TSconst.'.$constant.'}',$value,$LP[$key]['TypoScript']);
									}
								}
							}

								// Copy current global TypoScript configuration except numerical objects:
							if (is_array($GLOBALS['TSFE']->tmpl->setup)) {
								foreach ($GLOBALS['TSFE']->tmpl->setup as $tsObjectKey => $tsObjectValue) {
									if ($tsObjectKey !== intval($tsObjectKey)) {
										$tsparserObj->setup[$tsObjectKey] = $tsObjectValue;
									}
								}
							}

							$tsparserObj->parse($LP[$key]['TypoScript']);
							$dataValues[$key][$valueKey] = $cObj->cObjGet($tsparserObj->setup,'TemplaVoila_Proc.');
						}
						if (trim($LP[$key]['TypoScriptObjPath']))	{
							list($name, $conf) = $tsparserObj->getVal(trim($LP[$key]['TypoScriptObjPath']),$GLOBALS['TSFE']->tmpl->setup);
							$dataValues[$key][$valueKey] = $cObj->cObjGetSingle($name,$conf,'TemplaVoila_ProcObjPath--'.str_replace('.','*',$LP[$key]['TypoScriptObjPath']).'.');
						}
					}

						// Various local quick-processing options:
					$pOptions = $LP[$key]['proc'];
					if (is_array($pOptions))	{
						if ($pOptions['int'])		$dataValues[$key][$valueKey] = intval($dataValues[$key][$valueKey]);
							// HSC of all values by default:
						if ($pOptions['HSC'])		$dataValues[$key][$valueKey] = htmlspecialchars($dataValues[$key][$valueKey]);
						if (trim($pOptions['stdWrap']))		{
							$tsparserObj = t3lib_div::makeInstance('t3lib_TSparser');
								// BUG HERE: should convert array to TypoScript...
							$tsparserObj->parse($pOptions['stdWrap']);
							$dataValues[$key][$valueKey] = $cObj->stdWrap($dataValues[$key][$valueKey],$tsparserObj->setup);
						}
					}
				}
			}

            // Unset curent parent record info
            foreach ($registerKeys as $dkey) {
                unset($GLOBALS['TSFE']->register[$dkey]);
            }

            // Restore previous parent record info if necessary
            foreach ($savedParentInfo as $dkey => $dvalue) {
                $GLOBALS['TSFE']->register[$dkey] = $dvalue;
            }
        }
    }

	/**
	 * Processing of language fallback values (inheritance/overlaying)
	 * You never need to call this function when "$valueKey" is "vDEF"
	 *
	 * @param	array		Array where the values for language and default might be in as keys for "vDEF" and "vXXX"
	 * @param	string		Language key, "vXXX"
	 * @param	string		Overriding overlay mode from local processing in Data Structure / TO.
	 * @return	string		The value
	 */
	function inheritValue($dV,$valueKey,$overlayMode='')	{
#debug(array($dV['vDEF'],$valueKey,$overlayMode,$this->inheritValueFromDefault),'inheritValue()');
		if ($valueKey!='vDEF')	{

				// Consider overlay modes:
			switch((string)$overlayMode)	{
				case 'ifFalse':	// Normal inheritance based on whether the value evaluates false or not (zero or blank string)
				return trim($dV[$valueKey]) ? $dV[$valueKey] : $dV['vDEF'];

				case 'ifBlank':	// Only if the value is truely blank!
				return strcmp(trim($dV[$valueKey]),'') ? $dV[$valueKey] : $dV['vDEF'];

				case 'never':
				return $dV[$valueKey];	// Always return its own value

				case 'removeIfBlank':
					if (!strcmp(trim($dV[$valueKey]),''))	{
						return array('ERROR' => '__REMOVE');
					}
				default:

						// If none of the overlay modes matched, simply use the default:
					if ($this->inheritValueFromDefault)	{
						return trim($dV[$valueKey]) ? $dV[$valueKey] : $dV['vDEF'];
					}
				break;
			}
		}

			// Default is to just return the value:
		return $dV[$valueKey];
	}

	/**
	 * Creates an error message for frontend output
	 *
	 * @param	[type]		$string: ...
	 * @return	string		Error message output
	 * @string	string		Error message input
	 */
	function formatError($string)	{

			// Set no-cache since the error message shouldn't be cached of course...
		$GLOBALS['TSFE']->set_no_cache();

		if (intval($this->conf['disableErrorMessages'])) return '';
			//
		$output = '
			<!-- TemplaVoila ERROR message: -->
			<div class="tx_templavoila_pi1-error" style="
					border: 2px red solid;
					background-color: yellow;
					color: black;
					text-align: center;
					padding: 20px 20px 20px 20px;
					margin: 20px 20px 20px 20px;
					">'.
				'<strong>TemplaVoila ERROR:</strong><br /><br />'.nl2br(htmlspecialchars(trim($string))).
				'</div>';
		return $output;
	}

	/**
	 * Creates a visual response to the TemplaVoila blocks on the page.
	 *
	 * @param	[type]		$content: ...
	 * @param	[type]		$srcPointer: ...
	 * @param	[type]		$DSrec: ...
	 * @param	[type]		$TOrec: ...
	 * @param	[type]		$row: ...
	 * @param	[type]		$table: ...
	 * @return	[type]		...
	 */
	function visualID($content,$srcPointer,$DSrec,$TOrec,$row,$table)	{

			// Create table rows:
		$tRows = array();

		switch ($table)	{
			case 'pages':
				$tRows[] = '<tr style="background-color: #ABBBB4;">
						<td colspan="2"><b>Page:</b> '.htmlspecialchars(t3lib_div::fixed_lgd_cs($row['title'],30)).' <em>[UID:'.$row['uid'].']</em></td>
					</tr>';
			break;
			case 'tt_content':
				$tRows[] = '<tr style="background-color: #ABBBB4;">
						<td colspan="2"><b>Flexible Content:</b> '.htmlspecialchars(t3lib_div::fixed_lgd_cs($row['header'],30)).' <em>[UID:'.$row['uid'].']</em></td>
					</tr>';
			break;
			default:
				$tRows[] = '<tr style="background-color: #ABBBB4;">
						<td colspan="2">Table "'.$table.'" <em>[UID:'.$row['uid'].']</em></td>
					</tr>';
			break;
		}

			// Draw data structure:
		if (is_numeric($srcPointer))	{
			$tRows[] = '<tr>
					<td valign="top"><b>Data Structure:</b></td>
					<td>'.htmlspecialchars(t3lib_div::fixed_lgd_cs($DSrec['title'],30)).' <em>[UID:'.$srcPointer.']</em>'.
						($DSrec['previewicon'] ? '<br/><img src="uploads/tx_templavoila/'.$DSrec['previewicon'].'" alt="" />' : '').
						'</td>
				</tr>';
		} else {
			$tRows[] = '<tr>
					<td valign="top"><b>Data Structure:</b></td>
					<td>'.htmlspecialchars($srcPointer).'</td>
				</tr>';
		}

			// Template Object:
		$tRows[] = '<tr>
				<td valign="top"><b>Template Object:</b></td>
				<td>'.htmlspecialchars(t3lib_div::fixed_lgd_cs($TOrec['title'],30)).' <em>[UID:'.$TOrec['uid'].']</em>'.
					($TOrec['previewicon'] ? '<br/><img src="uploads/tx_templavoila/'.$TOrec['previewicon'].'" alt="" />' : '').
					'</td>
			</tr>';
		if ($TOrec['description'])	{
			$tRows[] = '<tr>
					<td valign="top" nowrap="nowrap">&nbsp; &nbsp; &nbsp; Description:</td>
					<td>'.htmlspecialchars($TOrec['description']).'</td>
				</tr>';
		}
		$tRows[] = '<tr>
				<td valign="top" nowrap="nowrap">&nbsp; &nbsp; &nbsp; Template File:</td>
				<td>'.htmlspecialchars($TOrec['fileref']).'</td>
			</tr>';
		$tRows[] = '<tr>
				<td valign="top" nowrap="nowrap">&nbsp; &nbsp; &nbsp; Render type:</td>
				<td>'.htmlspecialchars($TOrec['rendertype'] ? $TOrec['rendertype'] : 'Normal').'</td>
			</tr>';
		$tRows[] = '<tr>
				<td valign="top" nowrap="nowrap">&nbsp; &nbsp; &nbsp; Language:</td>
				<td>'.htmlspecialchars($TOrec['sys_language_uid'] ? $TOrec['sys_language_uid'] : 'Default').'</td>
			</tr>';
		$tRows[] = '<tr>
				<td valign="top" nowrap="nowrap">&nbsp; &nbsp; &nbsp; Local Proc.:</td>
				<td>'.htmlspecialchars($TOrec['localprocessing'] ? 'Yes' : '-').'</td>
			</tr>';

			// Compile information table:
		$infoArray = '<table style="border:1px solid black; background-color: #D9D5C9; font-family: verdana,arial; font-size: 10px;" border="0" cellspacing="1" cellpadding="1">
						'.implode('',$tRows).'
						</table>';

			// Compile information:
		$id = 'templavoila-preview-'.t3lib_div::shortMD5(microtime());
		$content = '<div style="text-align: left; position: absolute; display:none; filter: alpha(Opacity=90);" id="'.$id.'">
						'.$infoArray.'
					</div>
					<div id="'.$id.'-wrapper" style=""
						onmouseover="
							document.getElementById(\''.$id.'\').style.display=\'block\';
							document.getElementById(\''.$id.'-wrapper\').attributes.getNamedItem(\'style\').nodeValue = \'border: 2px dashed #333366;\';
								"
						onmouseout="
							document.getElementById(\''.$id.'\').style.display=\'none\';
							document.getElementById(\''.$id.'-wrapper\').attributes.getNamedItem(\'style\').nodeValue = \'\';
								">'.
						$content.
					'</div>';

		return $content	;
	}

	/**
	 * Render section index for TV
	 *
	 * @param  $content
	 * @param  $conf config of tt_content.menu.20.3
	 * @return string rendered section index
	 */
	public function tvSectionIndex($content, $conf) {

		$ceField = $this->cObj->stdWrap($conf['indexField'], $conf['indexField.']);
		$pids = isset($conf['select.']['pidInList.'])
			? trim($this->cObj->stdWrap($conf['select.']['pidInList'], $conf['select.']['pidInList.']))
			: trim($conf['select.']['pidInList']);
		$contentIds = array();
		if ($pids) {
			$pageIds = t3lib_div::trimExplode(',', $pids);
			foreach($pageIds as $pageId) {
				$page = $GLOBALS['TSFE']->sys_page->checkRecord('pages', $pageId);
				if (isset($page) && isset($page['tx_templavoila_flex'])) {
					$flex = array();
					$this->cObj->readFlexformIntoConf($page['tx_templavoila_flex'], $flex);
					$contentIds = array_merge($contentIds, t3lib_div::trimExplode(',', $flex[$ceField]));

				}
			}
		} else {
			$flex = array();
			$this->cObj->readFlexformIntoConf($GLOBALS['TSFE']->page['tx_templavoila_flex'], $flex);
			$contentIds = array_merge($contentIds, t3lib_div::trimExplode(',', $flex[$ceField]));
		}

		if (count($contentIds) > 0) {
			if ($conf['select.']['where']) {
				$conf['select.']['where'] .= ' AND UID IN(' . implode(',', $contentIds)  . ')';
			} else {
				$conf['select.']['where'] = 'UID IN(' . implode(',', $contentIds) . ')';
			}
		}

			// tiny trink to include the section index element itself too
		$GLOBALS['TSFE']->recordRegister[$GLOBALS['TSFE']->currentRecord] = -1;
		return $this->cObj->cObjGetSingle('CONTENT', $conf);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/pi1/class.tx_templavoila_pi1.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/pi1/class.tx_templavoila_pi1.php']);
}
?>