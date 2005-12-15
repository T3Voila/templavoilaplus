<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003, 2004, 2005 Kasper Skaarhoj (kasper@typo3.com)
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
 * @author		Kasper Skaarhoj <kasper@typo3.com>
 * @co-author	Robert Lemke <robert@typo3.org>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   67: class tx_templavoila_cm2 extends t3lib_SCbase
 *   80:     function main()
 *  113:     function printContent()
 *  125:     function markUpXML($str)
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:templavoila/cm2/locallang.xml');
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
	 * @param	string		XML input
	 * @return	string		HTML formatted output, marked up in colors
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
