<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * templavoila module cm2
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  116: class tx_templavoila_cm2 extends t3lib_SCbase
 *  170:     function menuConfig()
 *  191:     function main()
 *  217:     function printContent()
 *
 *              SECTION: MODULE mode
 *  246:     function main_mode()
 *  320:     function renderFile()
 *  543:     function renderDSO()
 *  649:     function renderTO()
 *  809:     function renderTO_editProcessing(&$dataStruct,$row,$theFile)
 *
 *              SECTION: Mapper functions
 * 1018:     function renderHeaderSelection($displayFile,$currentHeaderMappingInfo,$showBodyTag,$htmlAfterDSTable='')
 * 1082:     function renderTemplateMapper($displayFile,$path,$dataStruct=array(),$currentMappingInfo=array(),$htmlAfterDSTable='')
 * 1245:     function drawDataStructureMap($dataStruct,$mappingMode=0,$currentMappingInfo=array(),$pathLevels=array(),$optDat=array(),$contentSplittedByMapping=array(),$level=0,$tRows=array(),$formPrefix='',$path='',$mapOK=1)
 * 1458:     function drawDataStructureMap_editItem($formPrefix,$key,$value,$level)
 *
 *              SECTION: Helper-functions for File-based DS/TO creation
 * 1578:     function substEtypeWithRealStuff(&$elArray,$v_sub=array())
 * 1806:     function substEtypeWithRealStuff_contentInfo($content)
 *
 *              SECTION: Various helper functions
 * 1852:     function getDataStructFromDSO($datString,$file='')
 * 1868:     function linkForDisplayOfPath($title,$path)
 * 1888:     function linkThisScript($array)
 * 1910:     function makeIframeForVisual($file,$path,$limitTags,$showOnly,$preview=0)
 * 1926:     function explodeMappingToTagsStr($mappingToTags,$unsetAll=0)
 * 1944:     function unsetArrayPath(&$dataStruct,$ref)
 * 1961:     function cleanUpMappingInfoAccordingToDS(&$currentMappingInfo,$dataStruct)
 *
 *              SECTION: DISPLAY mode
 * 1993:     function main_display()
 * 2038:     function displayFileContentWithMarkup($content,$path,$relPathFix,$limitTags)
 * 2072:     function displayFileContentWithPreview($content,$relPathFix)
 * 2108:     function displayFrameError($error)
 *
 * TOTAL FUNCTIONS: 25
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:templavoila/cm2/locallang.php');
require_once (PATH_t3lib.'class.t3lib_scbase.php');






/**
 * Class for displaying color-marked-up version of FlexForm XML content.
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_templavoila
 */
class tx_templavoila_cm2 extends t3lib_SCbase {

		// External, static:
	var $option_linenumbers = TRUE;		// Showing linenumbers if true.

		// Internal, GPvars:
	var $viewTable = array();		// Array with tablename, uid and fieldname

	/**
	 * Main function, drawing marked up XML.
	 *
	 * @return	void
	 */
	function main()	{
		global $LANG,$BACK_PATH;

			// Check admin: If this is changed some day to other than admin users we HAVE to check if there is read access to the record being selected!
		if (!$GLOBALS['BE_USER']->isAdmin())	die('no access.');

			// Draw the header.
		$this->doc = t3lib_div::makeInstance('noDoc');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->docType = 'xhtml_trans';


		$this->content.=$this->doc->startPage($LANG->getLL('title'));
		$this->content.=$this->doc->header($LANG->getLL('title'));
		$this->content.=$this->doc->spacer(5);

			// XML code:
		$this->viewTable = t3lib_div::_GP('viewRec');
		$record = t3lib_BEfunc::getRecord($this->viewTable['table'], $this->viewTable['uid']);	// Selecting record based on table/uid since adding the field might impose a SQL-injection problem; at least the field name would have to be checked first.
		if (is_array($record))	{
			$xmlContentMarkedUp = $this->markUpXML($record[$this->viewTable['field_flex']]);
			$this->content.=$this->doc->section('',$xmlContentMarkedUp,0,1);
		}

			// Add spacer:
		$this->content.=$this->doc->spacer(10);
	}

	/**
	 * Prints module content.
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content.=$this->doc->middle();
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Mark up XML content
	 *
	 * @param	string	XML input
	 * @return	string	HTML formatted output, marked up in colors
	 */
	function markUpXML($str)	{
		require_once(PATH_t3lib.'class.t3lib_syntaxhl.php');

			// Make instance of syntax highlight class:
		$hlObj = t3lib_div::makeInstance('t3lib_syntaxhl');

			// Check which document type, if applicable:
		if (strstr(substr($str,0,100),'<T3DataStructure'))	{
			$title = 'Syntax highlighting <T3DataStructure> XML:';
			$formattedContent = $hlObj->highLight_DS($str);
		} elseif (strstr(substr($str,0,100),'<T3FlexForms'))	{
			$title = 'Syntax highlighting <T3FlexForms> XML:';
			$formattedContent = $hlObj->highLight_FF($str);
		} else {
			$title = 'Unknown format:';
			$formattedContent = '<span style="font-style: italic; color: #666666;">'.htmlspecialchars($str).'</span>';
		}

			// Check line number display:
		if ($this->option_linenumbers)	{
			$lines = explode(chr(10),$formattedContent);
			foreach($lines as $k => $v)	{
				$lines[$k] = '<span style="color: black; font-weight:normal;">'.str_pad($k+1,4,' ',STR_PAD_LEFT).':</span> '.$v;
			}
			$formattedContent = implode(chr(10),$lines);
		}

			// Output:
		return '
			<h3>'.htmlspecialchars($title).'</h3>
			<pre class="ts-hl">'.$formattedContent.'</pre>
			';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/cm2/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/cm2/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('tx_templavoila_cm2');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
