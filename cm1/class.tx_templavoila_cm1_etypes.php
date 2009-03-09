<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003, 2004, 2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Submodule 'eTypes' for the mapping module
 *
 * $Id: index.php 17597 2009-03-08 17:59:14Z steffenk $
 *
 * @author		Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @co-author	Robert Lemke <robert@typo3.org>
 * @co-author	Steffen kamper <info@sk-typo3.de>
 */
 
class tx_templavoila_cm1_eTypes {
	var $pObj;
	var $eTypeArray;
	
	function init($pObj) {
		$this->pObj = $pObj;
	}
	
	
	/**
	 * When mapping HTML files to DS the field types are selected amount some presets - this function converts these presets into the actual settings needed in the DS
	 * Typically called like: ->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'],$contentSplittedByMapping['sub']['ROOT']);
	 * Notice: this function is used to preview XML also. In this case it is always called with $scope=0, so XML for 'ce' type will not contain wrap with TYPO3SEARCH_xxx. Currently there is no way to avoid it.
	 *
	 * @param	array		$elArray: Data Structure, passed by reference!
	 * @param	array		$v_sub: Actual template content splitted by Data Structure
	 * @param	int		$scope: Scope as defined in tx_templavoila_datastructure.scope
	 * @return	void		Note: The result is directly written in $elArray
	 * @see renderFile()
	 */
	function substEtypeWithRealStuff(&$elArray,$v_sub=array(),$scope = 0)	{

		$eTypeCECounter = 0;

		t3lib_div::loadTCA('tt_content');
        
			// Traverse array
		foreach($elArray as $key => $value)	{      
				// this MUST not ever enter the XMLs (it will break TV)
			if ($elArray[$key]['type'] == 'section') {
				$elArray[$key]['type'] = 'array';
				$elArray[$key]['section'] = '1';
			} else {
				$elArray[$key]['section'] = '0';
			}
			
			// put these into array-form for preset-completition
			if (!is_array($elArray[$key]['tx_templavoila']['TypoScript_constants'])) {
				$elArray[$key]['tx_templavoila']['TypoScript_constants'] = $this->pObj->unflattenarray($elArray[$key]['tx_templavoila']['TypoScript_constants']);
			}
			if (!is_array($elArray[$key]['TCEforms']['config'])) {
				$elArray[$key]['TCEforms']['config'] = $this->pObj->unflattenarray($elArray[$key]['TCEforms']['config']);
			}
			

			/* ---------------------------------------------------------------------- */
				// this is too much different to preserve any previous information
			$reset = isset($elArray[$key]['tx_templavoila']['eType_before']) &&
					($elArray[$key]['tx_templavoila']['eType_before'] !=
					$elArray[$key]['tx_templavoila']['eType']);

			unset($elArray[$key]['tx_templavoila']['eType_before']);
		//	unset($elArray[$key]['tx_templavoila']['proc']);

			/* ---------------------------------------------------------------------- */
			if (is_array ($elArray[$key]['tx_templavoila']['sample_data'])) {
				foreach ($elArray[$key]['tx_templavoila']['sample_data'] as $tmpKey => $tmpValue) {
					$elArray[$key]['tx_templavoila']['sample_data'][$tmpKey] = htmlspecialchars($tmpValue);
				}
			} else {
				$elArray[$key]['tx_templavoila']['sample_data']= htmlspecialchars($elArray[$key]['tx_templavoila']['sample_data']);
			}
            
			/* ---------------------------------------------------------------------- */
			if ($elArray[$key]['type']=='array')	{	// If array, then unset:
				unset($elArray[$key]['tx_templavoila']['sample_data']);
			} else {	// Only non-arrays can have configuration (that is elements and attributes)

					// Getting some information about the HTML content (eg. images width/height if applicable)
				$contentInfo = $this->substEtypeWithRealStuff_contentInfo(trim($v_sub['cArray'][$key]));

					// Based on the eType (the preset type) we make configuration settings.
					// If a user function was registered, use that instead of our own handlers:
				if (isset ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['cm1']['eTypesConfGen'][$elArray[$key]['tx_templavoila']['eType']])) {
					$_params = array (
						'key' => $key,
						'elArray' => &$elArray,
						'contentInfo' => $contentInfo,
					);

					$bef = $elArray[$key]['tx_templavoila']['TypoScript'];

					t3lib_div::callUserFunction($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['cm1']['eTypesConfGen'][$elArray[$key]['tx_templavoila']['eType']], $_params, $this,'');

					if (!$reset && trim($bef))
						$elArray[$key]['tx_templavoila']['TypoScript'] = $bef;
				} else {
					switch($elArray[$key]['tx_templavoila']['eType'])	{
						case 'text':
							/* preserve previous config, if of the right kind */
							if (($reset = $reset || ($elArray[$key]['TCEforms']['config']['type'] != 'text'))) {
								$elArray[$key]['TCEforms']['label']=$elArray[$key]['tx_templavoila']['title'];
							$elArray[$key]['TCEforms']['config'] = array(
								'type' => 'text',
								'cols' => '48',
								'rows' => '5',
							);
							}

							/* preserve previous config, if explicitly set */
							if (!isset($elArray[$key]['tx_templavoila']['proc']['HSC']))
							$elArray[$key]['tx_templavoila']['proc']['HSC']=1;
						break;
						case 'rte':
							/* preserve previous config, if of the right kind */
							if (($reset = $reset || ($elArray[$key]['TCEforms']['config']['type'] != 'text'))) {
								$elArray[$key]['TCEforms']['label']=$elArray[$key]['tx_templavoila']['title'];
							$elArray[$key]['TCEforms']['config'] = array(
								'type' => 'text',
								'cols' => '48',
								'rows' => '5',
								'softref' => (isset($GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['softref']) ?
												$GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['softref'] :
												'typolink_tag,images,email[subst],url'),
							);
							}

							/* preserve previous config, if explicitly set */
							if (!$elArray[$key]['TCEforms']['defaultExtras'])
							$elArray[$key]['TCEforms']['defaultExtras'] = 'richtext:rte_transform[flag=rte_enabled|mode=ts_css]';
							/* preserve previous config, if explicitly set */
							if (!isset($elArray[$key]['TCEforms']['proc']['HSC']))
							$elArray[$key]['tx_templavoila']['proc']['HSC']=0;

							/* preserve previous config, if of the right kind */
							if ($reset || !trim($elArray[$key]['tx_templavoila']['TypoScript'])) {
							$elArray[$key]['tx_templavoila']['TypoScript'] = '
	10 = TEXT
	10.current = 1
	10.parseFunc = < lib.parseFunc_RTE
					';			// Proper alignment (at least for the first level)
										}
						break;
						case 'image':
						case 'imagefixed':
							/* preserve previous config, if of the right kind */
							if (($reset = $reset || ($elArray[$key]['TCEforms']['config']['type'] != 'group'))) {
								$elArray[$key]['TCEforms']['label']=$elArray[$key]['tx_templavoila']['title'];
							$elArray[$key]['TCEforms']['config'] = array(
								'type' => 'group',
								'internal_type' => 'file',
								'allowed' => 'gif,png,jpg,jpeg',
								'max_size' => '1000',
								'uploadfolder' => 'uploads/tx_templavoila',
								'show_thumbs' => '1',
								'size' => '1',
								'maxitems' => '1',
								'minitems' => '0'
							);
							}

							$maxW = $contentInfo['img']['width'] ? $contentInfo['img']['width'] : 200;
							$maxH = $contentInfo['img']['height'] ? $contentInfo['img']['height'] : 150;
 							$typoScriptImageObject = ($elArray[$key]['type'] == 'attr') ? 'IMG_RESOURCE' : 'IMAGE';

							/* preserve previous config, if of the right kind */
							if ($reset || !trim($elArray[$key]['tx_templavoila']['TypoScript'])) {
							if ($elArray[$key]['tx_templavoila']['eType']=='image')	{
								$elArray[$key]['tx_templavoila']['TypoScript'] = '
	10 = '.$typoScriptImageObject.'
	10.file.import = uploads/tx_templavoila/
	10.file.import.current = 1
	10.file.import.listNum = 0
	10.file.maxW = '.$maxW.'
					';			// Proper alignment (at least for the first level)
							} else {
								$elArray[$key]['tx_templavoila']['TypoScript'] = '
	10 = '.$typoScriptImageObject.'
	10.file.XY = '.$maxW.','.$maxH.'
#	10.file.format = jpg
#	10.file.quality = 80
		10.file.import = uploads/tx_templavoila/
		10.file.import.current = 1
		10.file.import.listNum = 0
		10.file.maxW = '.$maxW.'
		10.file.minW = '.$maxW.'
		10.file.maxH = '.$maxH.'
		10.file.minH = '.$maxH.'
					';			// Proper alignment (at least for the first level)
	}
							}

								// Finding link-fields on same level and set the image to be linked by that TypoLink:
							$elArrayKeys = array_keys($elArray);
							foreach($elArrayKeys as $theKey)	{
								if ($elArray[$theKey]['tx_templavoila']['eType']=='link')	{
									$elArray[$key]['tx_templavoila']['TypoScript'].= '
	10.stdWrap.typolink.parameter.field = '.$theKey.'
					';			// Proper alignment (at least for the first level)
									break;
								}
							}
						break;
						case 'link':
							/* preserve previous config, if of the right kind */
							if (($reset = $reset || ($elArray[$key]['TCEforms']['config']['type'] != 'input'))) {
								$elArray[$key]['TCEforms']['label']=$elArray[$key]['tx_templavoila']['title'];
							$elArray[$key]['TCEforms']['config'] = array(
								'type' => 'input',
								'size' => '15',
								'max' => '256',
								'checkbox' => '',
								'eval' => 'trim',
								'wizards' => Array(
									'_PADDING' => 2,
									'link' => Array(
										'type' => 'popup',
										'title' => 'Link',
										'icon' => 'link_popup.gif',
										'script' => 'browse_links.php?mode=wizard',
										'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
									)
								)
							);
							}

							/* preserve previous config, if of the right kind */
							if ($reset || !trim($elArray[$key]['tx_templavoila']['TypoScript'])) {
							if ($elArray[$key]['type'] == 'attr') {
								$elArray[$key]['tx_templavoila']['TypoScript'] = '
	10 = TEXT
	10.typolink.parameter.current = 1
	10.typolink.returnLast = url
					';			// Proper alignment (at least for the first level)
									/* preserve previous config, if explicitly set */
									if (!isset($elArray[$key]['TCEforms']['proc']['HSC']))
								$elArray[$key]['tx_templavoila']['proc']['HSC']=1;
							}
							else {
								$elArray[$key]['tx_templavoila']['TypoScript'] = '
	10 = TEXT
	10.typolink.parameter.current = 1
					';			// Proper alignment (at least for the first level)
							}
							}
						break;
						case 'ce':    
							/* preserve previous config, if of the right kind */
							if (($reset || ($elArray[$key]['TCEforms']['config']['type'] != 'group'))) {
								$elArray[$key]['TCEforms']['label']=$elArray[$key]['tx_templavoila']['title'];
								$elArray[$key]['TCEforms']['config'] = array(
									'type' => 'group',
									'internal_type' => 'db',
									'allowed' => 'tt_content',
									'size' => '5',
									'maxitems' => '200',
									'minitems' => '0',
									'multiple' => '1',
									'show_thumbs' => '1'
								);
							}

							/* preserve previous config, if of the right kind */
							if ($reset || !trim($elArray[$key]['tx_templavoila']['TypoScript'])) {
							$elArray[$key]['tx_templavoila']['TypoScript'] = '
	10= RECORDS
	10.source.current=1
	10.tables = tt_content' . ($scope == 1 ? '
	10.wrap = <!--TYPO3SEARCH_begin--> | <!--TYPO3SEARCH_end-->' : '') . '
					';			// Proper alignment (at least for the first level)
							}

							$elArray[$key]['tx_templavoila']['oldStyleColumnNumber'] = $eTypeCECounter;
							$eTypeCECounter++;
						break;
						case 'int':
							/* preserve previous config, if of the right kind */
							if (($reset = $reset || ($elArray[$key]['TCEforms']['config']['type'] != 'input'))) {
								$elArray[$key]['TCEforms']['label']=$elArray[$key]['tx_templavoila']['title'];
							$elArray[$key]['TCEforms']['config'] = array(
								'type' => 'input',
								'size' => '4',
								'max' => '4',
								'eval' => 'int',
								'checkbox' => '0',
								'range' => Array (
									'upper' => '999',
									'lower' => '25'
								),
								'default' => 0
							);
							}
						break;
						case 'select':
							/* preserve previous config, if of the right kind */
							if (($reset = $reset || ($elArray[$key]['TCEforms']['config']['type'] != 'select'))) {
								$elArray[$key]['TCEforms']['label']=$elArray[$key]['tx_templavoila']['title'];
							$elArray[$key]['TCEforms']['config'] = array(
								'type' => 'select',
								'items' => Array (
									Array('', ''),
									Array('Value 1', 'Value 1'),
									Array('Value 2', 'Value 2'),
									Array('Value 3', 'Value 3'),
								),
								'default' => '0'
							);
							}
						break;
						case 'input':
						case 'input_h':
						case 'input_g':
							/* preserve previous config, if of the right kind */
							if (($reset = $reset || ($elArray[$key]['TCEforms']['config']['type'] != 'input'))) {
								$elArray[$key]['TCEforms']['label']=$elArray[$key]['tx_templavoila']['title'];
							$elArray[$key]['TCEforms']['config'] = array(
								'type' => 'input',
								'size' => '48',
								'eval' => 'trim',
							);
							}

							if ($elArray[$key]['tx_templavoila']['eType']=='input_h')	{	// Text-Header
									// Finding link-fields on same level and set the image to be linked by that TypoLink:
								$elArrayKeys = array_keys($elArray);
								foreach($elArrayKeys as $theKey)	{
									if ($elArray[$theKey]['tx_templavoila']['eType']=='link')	{
										$elArray[$key]['tx_templavoila']['TypoScript'] = '
	10 = TEXT
	10.current = 1
	10.typolink.parameter.field = '.$theKey.'
										';
									}
								}
							} elseif ($elArray[$key]['tx_templavoila']['eType']=='input_g')	{	// Graphical-Header

								$maxW = $contentInfo['img']['width'] ? $contentInfo['img']['width'] : 200;
								$maxH = $contentInfo['img']['height'] ? $contentInfo['img']['height'] : 20;

								$elArray[$key]['tx_templavoila']['TypoScript'] = '
	10 = IMAGE
	10.file = GIFBUILDER
	10.file {
	  XY = '.$maxW.','.$maxH.'
	  backColor = #999999

	  10 = TEXT
	  10.text.current = 1
	  10.text.case = upper
	  10.fontColor = #FFCC00
	  10.fontFile =  t3lib/fonts/vera.ttf
	  10.niceText = 0
	  10.offset = 0,14
	  10.fontSize = 14
	}
								';
							} else {	// Normal output.
								$elArray[$key]['tx_templavoila']['proc']['HSC']=1;
							}

							if ($reset)
								unset($elArray[$key]['tx_templavoila']['TypoScript']);
						break;
						case 'TypoScriptObject':
							unset($elArray[$key]['tx_templavoila']['TypoScript_constants']);
							unset($elArray[$key]['tx_templavoila']['TypoScript']);

							unset($elArray[$key]['TCEforms']['config']);

							/* preserve previous config, if of the right kind */
							if (($reset = $reset || ($elArray[$key]['tx_templavoila']['TypoScriptObjPath'] == ''))) {
							$elArray[$key]['tx_templavoila']['TypoScriptObjPath'] =
								($elArray[$key]['tx_templavoila']['eType_EXTRA']['objPath'] ?
									$elArray[$key]['tx_templavoila']['eType_EXTRA']['objPath'] :
									($elArray[$key]['tx_templavoila']['TypoScriptObjPath'] ?
										$elArray[$key]['tx_templavoila']['TypoScriptObjPath'] : ''));
							}
						break;
						case 'none':
							unset($elArray[$key]['TCEforms']['config']);
						break;
					}
				}	// End switch else
				if ($elArray[$key]['tx_templavoila']['eType'] != 'TypoScriptObject') {
					if (isset($elArray[$key]['tx_templavoila']['TypoScriptObjPath'])) {
						unset($elArray[$key]['tx_templavoila']['TypoScriptObjPath']);
					}
				}
				else if (isset($elArray[$key]['tx_templavoila']['eType_EXTRA']['objPath'])) {
					unset($elArray[$key]['tx_templavoila']['eType_EXTRA']['objPath']);
					if (count($elArray[$key]['tx_templavoila']['eType_EXTRA']) == 0) {
						unset($elArray[$key]['tx_templavoila']['eType_EXTRA']);
					}
				}

					// Setting TCEforms title for element if configuration is found:
				if (!is_array($elArray[$key]['TCEforms']['config'])) {
					unset($elArray[$key]['TCEforms']);
				}
			}

				// Apart from converting eType to configuration, we also clean up other aspects:
			if (!$elArray[$key]['type'])
				unset($elArray[$key]['type']);
			if (!$elArray[$key]['section'])
				unset($elArray[$key]['section']);
			else {
				unset($elArray[$key]['tx_templavoila']['TypoScript_constants']);
				unset($elArray[$key]['tx_templavoila']['TypoScript']);
				unset($elArray[$key]['tx_templavoila']['proc']);
				unset($elArray[$key]['TCEforms']);
			}

			if (!$elArray[$key]['tx_templavoila']['description'])
				unset($elArray[$key]['tx_templavoila']['description']);
			if (!$elArray[$key]['tx_templavoila']['tags'])
				unset($elArray[$key]['tx_templavoila']['tags']);
			if (!$elArray[$key]['tx_templavoila']['TypoScript_constants'])
				unset($elArray[$key]['tx_templavoila']['TypoScript_constants']);
			if (!$elArray[$key]['TCEforms']['defaultExtras'])
				unset($elArray[$key]['TCEforms']['defaultExtras']);

				// Run this function recursively if needed:
			if (is_array($elArray[$key]['el']))	{
				$this->substEtypeWithRealStuff($elArray[$key]['el'],$v_sub['sub'][$key],$scope);
			}
		}	// End loop
	}

	/**
	 * Analyzes the input content for various stuff which can be used to generate the DS.
	 * Basically this tries to intelligently guess some settings.
	 *
	 * @param	string		HTML Content string
	 * @return	array		Configuration
	 * @see substEtypeWithRealStuff()
	 */
	function substEtypeWithRealStuff_contentInfo($content)	{
		if ($content)	{
			if (substr($content,0,4)=='<img')	{
				$attrib = t3lib_div::get_tag_attributes($content);
				if ((!$attrib['width'] || !$attrib['height']) && $attrib['src'])	{
					$pathWithNoDots = t3lib_div::resolveBackPath($attrib['src']);
					$filePath = t3lib_div::getFileAbsFileName($pathWithNoDots);
					if ($filePath && @is_file($filePath))	{
						$imgInfo = @getimagesize($filePath);

						if (!$attrib['width'])	$attrib['width']=$imgInfo[0];
						if (!$attrib['height'])	$attrib['height']=$imgInfo[1];
					}
				}
				return array('img'=>$attrib);
			}
		}
		return false;
	}

    
	
}  

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/cm1/class.tx_templavoila_cm1_etypes.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/cm1/class.tx_templavoila_cm1_etypes.php']);
}

?>
