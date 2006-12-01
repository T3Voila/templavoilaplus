<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Nicolas Cerisier and Kasper Skaarhoj
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
 * Conversion of localization mode in Data Structures
 * Is integrated into the extdeveval extension framework
 *
 * @author	Nicolas Cerisier <>
 * @author	Kasper Skaarhoj
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   58: class tx_templavoila_extdeveval
 *   69:     function init()
 *   77:     function main()
 *  103:     function renderMenuOfDataStructures()
 *  136:     function getDataStructures()
 *  180:     function DSlanguageMode($DSstring)
 *  203:     function renderConversionView($dsIdForConversion)
 *
 * TOTAL FUNCTIONS: 6
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_t3lib.'class.t3lib_flexformtools.php');

/**
 * Conversion of localization mode in Data Structures
 *
 * @author	Nicolas Cerisier <>
 * @author	Kasper Skaarhoj
 * @package tc_templavoila
 */
class tx_templavoila_extdeveval {

	var $typeNames = array(
		1 => 'Page DS',
		2 => 'Flexible Content Element DS',
		0 => 'Unspecified DS',
	);
	
	
		// Internal:
	var $newFlexFormData = array();
	
	var $language;
	
	

	/**
	 * Initialization (none needed)
	 * 
	 * @return	void
	 */
	function init()	{
	}

	/**
	 * The main function in the class
	 *
	 * @return	string		HTML content
	 */
	function main()	{
/*
		- Select data structure
		- Search all TCA defined tables for flexform fields with this data structure
		for all records using this DS:
		- When found, parse content of field according to CURRENT language setting of DS
		- Perform conversion and update record
		end:
		- update DS language setting!
*/

			// Look for a selected data structure:
		$dsIdForConversion = t3lib_div::_GP('dsId');
		
			// Select output:
		if (!$dsIdForConversion)	{
			$output = $this->renderMenuOfDataStructures();
		} else {
			$output = $this->renderConversionView($dsIdForConversion);
		}

		return $output;
	}

	/**
	 * Rendering menu of available data structures to work on
	 *
	 * @return	string		HTML content
	 */
	function renderMenuOfDataStructures()	{
		
			// Get data structures we should display
		$arrayOfDS = $this->getDataStructures();

			// For each category (page/content element/unknown) we display :
		foreach($arrayOfDS as $type => $DSarray){
			$output.='<h3>'.htmlspecialchars($this->typeNames[$type]).'</h3>';

				// Show DS records for this category:
			$table = '
				<tr class="bgColor5 tableheader">
					<td>Title:</td>
					<td>Language Mode:</td>
					<td>ID:</td>
				</tr>';
			foreach($DSarray as $DSrec)	{
				$DSid = $DSrec['_STATIC'] ? $DSrec['path'] : $DSrec['uid'];
				$table.= '
						<tr class="bgColor4">
							<td><a href="index.php?dsId='.rawurlencode($DSid).'">'.htmlspecialchars($DSrec['title']).'</a></td>
							<td>'.htmlspecialchars($DSrec['_languageMode']).'</td>
							<td>'.htmlspecialchars($DSid).'</td>
						</tr>';
			}

			$output.='<table border="1" cellpadding="1" cellspacing="1">'.$table.'</table>';
		}

		return $output;
	}

	/**
	 * Retrieve data structures
	 *
	 * @return	array
	 */
	function getDataStructures()	{

			// Select all Data Structures in the PID and put into an array:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_templavoila_datastructure',
			'pid>=0'.
				t3lib_BEfunc::deleteClause('tx_templavoila_datastructure'),
			'',
			'title'
		);
		$dsRecords = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$row['_languageMode'] = $this->DSlanguageMode($row['dataprot']);
			if ($row['_languageMode']!='Disabled')	{
				$dsRecords[$row['scope']][] = $row;
			}
		}

			// Select all static Data Structures and add to array:
		if (is_array($GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoila_cm1']['staticDataStructures']))	{
			foreach($GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoila_cm1']['staticDataStructures'] as $staticDS)	{
				$staticDS['_STATIC'] = 1;
				$fileReference = t3lib_div::getFileAbsFileName($staticDS['path']);
				if (@is_file($fileReference))	{
					$staticDS['_languageMode'] = $this->DSlanguageMode(t3lib_div::getUrl($fileReference));
				} else {
					$staticDS['_languageMode'] = 'ERROR: File not found';
				}
				if ($row['_languageMode']!='Disabled')	{
					$dsRecords[$staticDS['scope']][] = $staticDS;
				}
			}
		}

		return $dsRecords;
	}

	/**
	 * Extract Language mode from DataStructure XML
	 *
	 * @param	array		Data Structure XML
	 * @return	string		Type keyword
	 */
	function DSlanguageMode($DSstring)	{

		$DScontent = t3lib_div::xml2array($DSstring);
		$DScontent = array('meta' => $DScontent['meta']);

		$languageMode = '';
		if ($DScontent['meta']['langDisable'])	{
			$languageMode = 'Disabled';
		} elseif ($DScontent['meta']['langChildren']) {
			$languageMode = 'Inheritance';
		} else {
			$languageMode = 'Separate';
		}

		return $languageMode;
	}

	/**
	 * Rendering view for converting
	 *
	 * @param	string		Data Structure id (either uid or string pointing to XML file)
	 * @return	string		HTML
	 */
	function renderConversionView($dsIdForConversion)	{
		global $TCA;

		$output = '';

		// Language Mode For DS
		$dbQuery = "SELECT dataprot,title
						FROM tx_templavoila_datastructure WHERE uid=".(int) $dsIdForConversion;
		if($dbRes = $GLOBALS['TYPO3_DB']->sql_query($dbQuery)){
			if(mysql_num_rows($dbRes) == 1){
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbRes);
				$language = $this->DSlanguageMode($row['dataprot']);
				$DStitle = $row['title'];
			}
		}
		
		// Define which method you should use 
		if($language == 'Inheritance'){
			$traverseMethod = 'traverseFlexFormXMLData_callBackFunction_Inheritance';
		} elseif ($language == 'Separate') {
			$traverseMethod = 'traverseFlexFormXMLData_callBackFunction_Separate';
		} elseif ($language == 'Disabled') {
			$traverseMethod = 'traverseFlexFormXMLData_callBackFunction_Disabled';
		}
						
		// If POST, then update in database
		if(is_array($SET = t3lib_div::_POST('SET'))){
			
			if(in_array($SET['ds']['table'], array_keys($TCA)))
				$setTable = $SET['ds']['table'];
			
			$setField = $SET['ds']['field'];
			foreach($SET['content'] as $key => $val){
				if($val){
					$dbQuery = "SELECT * FROM {$setTable} WHERE uid='".(int) $key."' ";
					if($dbRes = $GLOBALS['TYPO3_DB']->sql_query($dbQuery)){
						if(mysql_num_rows($dbRes) == 1){
							$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbRes);
							
							$this->newFlexFormData = array();
							$flexObj = t3lib_div::makeInstance('t3lib_flexformtools');
							$flexObj->reNumberIndexesOfSectionData = TRUE;
							$flexObj->traverseFlexFormXMLData($setTable,$setField,$row,$this,$traverseMethod);
							$dbQuery = "UPDATE $setTable 
											SET $setField='".addslashes($flexObj->flexArray2Xml($this->newFlexFormData))."'
											WHERE uid='".(int) $key."' ";
							
							if(!$dbRes = $GLOBALS['TYPO3_DB']->sql_query($dbQuery)){
								print mysql_error();
							} 
						}
					}
				} 
			}
		}
		
			// First, find all flexform fields where we could find relations to data structures:
		$fieldsToCheck = array();
		foreach($TCA as $table => $tmp)	{
			t3lib_div::loadTCA($table);

			foreach($TCA[$table]['columns'] as $fieldName => $config)	{
				if ($config['config']['type'] == 'flex' && $config['config']['ds_pointerField'] && $config['config']['ds_tableField']=='tx_templavoila_datastructure:dataprot')	{
					$fieldsToCheck[] = array($table,$fieldName,$config['config']['ds_pointerField'],$config['config']['ds_pointerField_searchParent']);
				}
			}
		}

			// For each field, look up records:
		foreach($fieldsToCheck as $tableFieldPair)	{
			list($table, $field, $dsPointerField,$searchParentFlag) = $tableFieldPair;

				// Select all Data Structures in the PID and put into an array:
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
				$table,
				$dsPointerField.'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($dsIdForConversion,$table),	// Finds "deleted"-flagged records as well since an undelete action would otherwise invalidate it. Maybe this is not too important and we can enable filtering out deleted records if its boring to fix the problem for those...
				'','','','uid'
			);
				// If searchParent feature is used we cannot convert if the data structure is used anywhere for this field! So we must check:
			if (count($rows) && $searchParentFlag)	{
				$output .= 'Sorry, but I found a flexform field ("'.$table.':'.$field.'") with "ds_pointerField_searchParent" set which means I cannot safely find all records to convert. It was used in uids: '.implode(',',array_keys($rows)).'. Please consider to abort the process.';
			}

			if (count($rows)) {
				foreach($rows as $row) 	{
					
						// Set up result array:
					$this->newFlexFormData = array();

						// Create and call iterator object:
					$flexObj = t3lib_div::makeInstance('t3lib_flexformtools');
					$flexObj->reNumberIndexesOfSectionData = TRUE;
					$flexObj->traverseFlexFormXMLData($table,$field,$row,$this,$traverseMethod);

				/*			
					debug(t3lib_div::xml2array($row[$field]),'Old: '.$language);
					debug($this->newFlexFormData,'New: '.$language);
debug(array($flexObj->flexArray2Xml($this->newFlexFormData)));
				*/	
					
					if(t3lib_div::xml2array($row[$field]) === $this->newFlexFormData){
						$checked = NULL;
						$bgColor = 'bgColor3';						
					} else {
						$checked = 'checked';
						$bgColor = 'bgColor4';
				}
					
					// then add a new line
					$htmlTABLE .= '	<tr class="'.$bgColor.'" >
										<td><input type="checkbox" name="SET[content]['.$row['uid'].']" value="'.(isset($checked) ? 1 : 0).'" '.$checked.'></td>
										<td>'.$row['uid'].'</td>
										<td>'.$row['pid'].'</td>
										<td>'.(!empty($row['header']) ? $row['header'] : 'no header').'</td>
									</tr>';
				}
				
				// build the Table
				$htmlTABLE = '	<tr class="bgColor5 tableheader">
								<td> </td>
								<td>UID:</td>
								<td>PID:</td>
								<td>HEADER:</td>
							</tr>'.$htmlTABLE;
				$output.='<h3>'.count($rows).' Content(s) using the structure "'.$DStitle.'" with the following mode : "'.$language.'"</h3>';
				$output.='	<form method="POST" action="" >
								<table border="1" cellpadding="1" cellspacing="1">'.$htmlTABLE.'</table>
								<b>
									!!! WARNING !!!<br/>
									Please check all contents and note that the update will change the XML description of your contents. <br/>
								</b>
								<br/>
								<input type="hidden" name="SET[ds][field]" value="'.$field.'" />
								<input type="hidden" name="SET[ds][table]" value="'.$table.'" />
								<input type="submit" value="update contents" name="submit" />
							</form>';
			}
		}

		return $output;
	}

	function traverseFlexFormXMLData_callBackFunction_Inheritance($dsArr, $data, $PA, $path, &$pObj)	{
		$pathArray = explode('/',$path);
		$langId = $pathArray[ sizeof($pathArray)-1 ] ;
		if(substr($langId,0,1) == 'v'){
			if( in_array($langId = substr($langId,1), array_values($PA['vKeys']) ) && $langId != 'DEF'){
				$oldPattern = array("/lDEF/","/v$langId/");
				$newPattern = array("l$langId","vDEF");
				$path = preg_replace($oldPattern,$newPattern,$path);
			}
		}
		// Just setting value in our own result array, basically replicating the structure:
		$pObj->setArrayValueByPath($path,$this->newFlexFormData,$data);
	}

	function traverseFlexFormXMLData_callBackFunction_Separate($dsArr, $data, $PA, $path, &$pObj)	{
		$pathArray = explode('/',$path);
		$langId = $pathArray[2] ;
		if(substr($langId,0,1) == 'l'){
			if( $PA['lKey'] == $langId ){
				
				$langId = substr($langId,1);
				$oldPattern = array("/vDEF/","/l$langId/");
				$newPattern = array("v$langId","lDEF");
				$path = preg_replace($oldPattern,$newPattern,$path);
			}
		}
		// Just setting value in our own result array, basically replicating the structure:
		$pObj->setArrayValueByPath($path,$this->newFlexFormData,$data);
	}
	
	function traverseFlexFormXMLData_callBackFunction_Disabled($dsArr, $data, $PA, $path, &$pObj)	{
		// Just setting value in our own result array, basically replicating the structure:
		$pObj->setArrayValueByPath($path."/kqsper",$this->newFlexFormData,$data);
		debug('TODO');
	}
		
	/**
	 * Call back function for t3lib_flexformtools class
	 * 
	 * @param	array		Data structure for the current value
	 * @param	mixed		Current value
	 * @param	array		Additional configuration used in calling function
	 * @param	string		Path of value in DS structure
	 * @param	object		Object reference to caller
	 * @return	void
	 */	
	/*function traverseFlexFormXMLData_callBackFunction($dsArr, $data, $PA, $path, &$pObj)	{
			// Just setting value in our own result array, basically replicating the structure:
		$pObj->setArrayValueByPath($path."/kqsper",$this->newFlexFormData,$data);
		debug($this->language);
	
	}*/
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_extdeveval.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_extdeveval.php']);
}
?>