<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2003 Kasper Skårhøj (kasper@typo3.com)
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
 * @author    Kasper Skårhøj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   63: class tx_templavoila_pi1 extends tslib_pibase 
 *   79:     function main($content,$conf)    
 *   91:     function main_page($content,$conf)    
 *  121:     function initVars($conf)	
 *  133:     function renderElement($row,$table)	
 *  191:     function processDataValues(&$dataValues,$DSelements,$valueKey='vDEF')	
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


 







require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_htmlmarkup.php'); 	

/**
 * Plugin 'Flexible Content' for the 'templavoila' extension.
 * 
 * @author    Kasper Skårhøj <kasper@typo3.com>
 */
class tx_templavoila_pi1 extends tslib_pibase {
    var $prefixId = 'tx_templavoila_pi1';        // Same as class name
    var $scriptRelPath = 'pi1/class.tx_templavoila_pi1.php';    // Path to this script relative to the extension dir.
    var $extKey = 'templavoila';    // The extension key.
    
	var $inheritValueFromDefault=1;		// If set, children-translations will take the value from the default if "false" (zero or blank)

	
	
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
		if ($GLOBALS['TT']->LR) $GLOBALS['TT']->push('Get DS, TO and data');
			// Get data structure:
			$srcPointer = $row['tx_templavoila_ds'];
			if (t3lib_div::testInt($srcPointer))	{	// If integer, then its a record we will look up:
				$DSrec = $GLOBALS['TSFE']->sys_page->checkRecord('tx_templavoila_datastructure',$srcPointer);
				$DS = t3lib_div::xml2array($DSrec['dataprot']);
			} else {	// Otherwise expect it to be a file:
				$file = t3lib_div::getFileAbsFileName($srcPointer);
				if ($file && @is_file($file))	{
					$DS = t3lib_div::xml2array(t3lib_div::getUrl($file));
				}
			}
			
			$langChildren = $DS['meta']['langChildren'] ? 1 : 0;
			$langDisabled = $DS['meta']['langDisable'] ? 1 : 0;
			
			list ($dataStruct, $sheet) = t3lib_div::resolveSheetDefInDS($DS,'sDEF');
	
				// Data:
			$data = t3lib_div::xml2array($row['tx_templavoila_flex']);
				
			$lKey = ($GLOBALS['TSFE']->sys_language_isocode && !$langDisabled && !$langChildren) ? 'l'.$GLOBALS['TSFE']->sys_language_isocode : 'lDEF';
			$dataValues = $data['data']['sDEF'][$lKey];



				// Init mark up object.
			$this->markupObj = t3lib_div::makeInstance('tx_templavoila_htmlmarkup');
			$this->markupObj->htmlParse = t3lib_div::makeInstance('t3lib_parsehtml');

				// Get template record:
			$TOrec = $this->markupObj->getTemplateRecord($row['tx_templavoila_to'], t3lib_div::GPvar('print')?1:0, $GLOBALS['TSFE']->sys_language_uid);
			$TO = unserialize($TOrec['templatemapping']);
			$TOproc = t3lib_div::xml2array($TOrec['localprocessing']);
			if (!is_array($TOproc))	$TOproc=array();
			
		if ($GLOBALS['TT']->LR) $GLOBALS['TT']->pull();


		if ($GLOBALS['TT']->LR) $GLOBALS['TT']->push('Processing data');
			$vKey = ($GLOBALS['TSFE']->sys_language_isocode && !$langDisabled && $langChildren) ? 'v'.$GLOBALS['TSFE']->sys_language_isocode : 'vDEF';
			$this->processDataValues($dataValues,$DS['ROOT']['el'],$TOproc['ROOT']['el'],$vKey);
		if ($GLOBALS['TT']->LR) $GLOBALS['TT']->pull();

		if ($GLOBALS['TT']->LR) $GLOBALS['TT']->push('Merge data and TO');

			$content = $this->markupObj->mergeFormDataIntoTemplateStructure($dataValues,$TO['MappingData_cached'],'',$vKey);
		if ($GLOBALS['TT']->LR) $GLOBALS['TT']->pull();
		
		$content = $this->pi_getEditIcon($content,'tx_templavoila_flex','Edit element',$row,$table);
		return $content;	
	}

	/**
	 * Performing pre-processing of the data array. 
	 * This will transform the data in the data array according to various rules before the data is merged with the template HTML
	 * Notice that $dataValues is changed internally as a reference so the function returns no content but internally changes the passed variable for $dataValues.
	 * 
	 * @param	array		The data values from the XML file (converted to array). Passed by reference.
	 * @param	array		The data structure definition which the data in the dataValues array reflects.
	 * @param	string		Value key
	 * @return	void		
	 */
	function processDataValues(&$dataValues,$DSelements,$TOelements,$valueKey='vDEF')	{
		if (is_array($DSelements))	{
		
				// Prepare a fake data record for cObj:
			$dataRecord=array();
			if (is_array($dataValues))	{
				foreach($dataValues as $key => $values)	{
					if ($this->inheritValueFromDefault)	{
						$dataRecord[$key]=($dataValues[$key][$valueKey]?$dataValues[$key][$valueKey]:$dataValues[$key]['vDEF']);
					} else {
						$dataRecord[$key]=$dataValues[$key][$valueKey];
					}
				}
			}
			
				// For each DS element:
			foreach($DSelements as $key => $dsConf)	{
					
						// Array/Section:
				if ($DSelements[$key]['type']=='array')	{
					if (is_array($dataValues[$key]['el']))	{
						if ($DSelements[$key]['section'])	{
							foreach($dataValues[$key]['el'] as $ik => $el)	{
								$theKey = key($el);
								if (is_array($dataValues[$key]['el'][$ik][$theKey]['el']))	{
									$this->processDataValues($dataValues[$key]['el'][$ik][$theKey]['el'],$DSelements[$key]['el'][$theKey]['el'],$TOelements[$key]['el'][$theKey]['el'],$valueKey);
								}
							}
						} else {
							if (!isset($dataValues[$key]['el']))	$dataValues[$key]['el']=array();
							$this->processDataValues($dataValues[$key]['el'],$DSelements[$key]['el'],$TOelements[$key]['el'],$valueKey);
						}
					}
				} else {
						// Language inheritance:
					if ($this->inheritValueFromDefault && !$dataValues[$key][$valueKey])	{
						$dataValues[$key][$valueKey] = $dataValues[$key]['vDEF'];
					}					


					if (is_array($TOelements[$key]['tx_templavoila']))	{
						if (is_array($DSelements[$key]['tx_templavoila']))	{
							$DSelements[$key]['tx_templavoila'] = t3lib_div::array_merge_recursive_overrule($DSelements[$key]['tx_templavoila'],$TOelements[$key]['tx_templavoila']);
						} else $DSelements[$key]['tx_templavoila'] = $TOelements[$key]['tx_templavoila'];
#debug($DSelements[$key]['tx_templavoila']);						
					}
					
					
						// TypoScript / TypoScriptObjPath:
					if (trim($DSelements[$key]['tx_templavoila']['TypoScript']) || trim($DSelements[$key]['tx_templavoila']['TypoScriptObjPath']))	{
						$tsparserObj = t3lib_div::makeInstance('t3lib_TSparser');

						$cObj =t3lib_div::makeInstance('tslib_cObj');
						$cObj->setParent($this->cObj->data,$this->cObj->currentRecord);
						$cObj->start($dataRecord,'_NO_TABLE');

						$cObj->setCurrentVal($dataValues[$key][$valueKey]);
						
						if (trim($DSelements[$key]['tx_templavoila']['TypoScript']))	{
							if (is_array($DSelements[$key]['tx_templavoila']['TypoScript_constants']))	{
								foreach($DSelements[$key]['tx_templavoila']['TypoScript_constants'] as $constant => $value)	{
									$DSelements[$key]['tx_templavoila']['TypoScript'] = str_replace('{$'.$constant.'}',$value,$DSelements[$key]['tx_templavoila']['TypoScript']);
								}
							}
#debug(array($DSelements[$key]['tx_templavoila']['TypoScript']));

								// Setting "lib." and "plugin." TypoScript - maybe we should set it all except numeric keys?
							$tsparserObj->setup['lib.'] = $GLOBALS['TSFE']->tmpl->setup['lib.'];
							$tsparserObj->setup['plugin.'] = $GLOBALS['TSFE']->tmpl->setup['plugin.'];

							$tsparserObj->parse($DSelements[$key]['tx_templavoila']['TypoScript']);
							$dataValues[$key][$valueKey] = $cObj->cObjGet($tsparserObj->setup,'TemplaVoila_Proc.');
						}
						if (trim($DSelements[$key]['tx_templavoila']['TypoScriptObjPath']))	{
							list($name, $conf) = $tsparserObj->getVal(trim($DSelements[$key]['tx_templavoila']['TypoScriptObjPath']),$GLOBALS['TSFE']->tmpl->setup);
							$dataValues[$key][$valueKey] = $cObj->cObjGetSingle($name,$conf,'TemplaVoila_ProcObjPath.');
						} 
					}
					
						// Various local quick-processing options:
					$pOptions = $DSelements[$key]['tx_templavoila']['proc'];	
					if (is_array($pOptions))	{
						if ($pOptions['int'])		$dataValues[$key][$valueKey] = intval($dataValues[$key][$valueKey]);
							// HSC of all values by default:
						if ($pOptions['HSC'])		$dataValues[$key][$valueKey] = htmlspecialchars($dataValues[$key][$valueKey]);
						if (trim($pOptions['stdWrap']))		{
							$tsparserObj = t3lib_div::makeInstance('t3lib_TSparser');
							$tsparserObj->parse($pOptions['stdWrap']);
							$dataValues[$key][$valueKey] = $cObj->stdWrap($dataValues[$key][$valueKey],$tsparserObj->setup);
						}
					}					
				}
			}
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/pi1/class.tx_templavoila_pi1.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/pi1/class.tx_templavoila_pi1.php']);
}
?>
