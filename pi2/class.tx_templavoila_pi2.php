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
 * Plugin 'Data Source' for the 'templavoila' extension.
 *
 * $Id$
 *
 * @todo	Waiting for an author - most likely Robert Lemke, since he is somehow passionate about this idea of data sources
 *
 * @author    Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   59: class tx_templavoila_pi2 extends tslib_pibase 
 *   71:     function main($content,$conf)    
 *
 * TOTAL FUNCTIONS: 1
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */





require_once(PATH_tslib.'class.tslib_pibase.php');

/**
 * Plugin 'Data Source' for the 'templavoila' extension.
 * 
 * @author    Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_templavoila
 */
class tx_templavoila_pi2 extends tslib_pibase {
    var $prefixId = 'tx_templavoila_pi2';        // Same as class name
    var $scriptRelPath = 'pi2/class.tx_templavoila_pi2.php';    // Path to this script relative to the extension dir.
	var $extKey = 'templavoila';    // The extension key.
	
	/**
	 * [Put your description here]
	 * 
	 * @param	[type]		$content: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
    function main($content,$conf)    {
        return '
		
		<h3>Include a Data Source?</h3>
		<p>Robert Lemke has some ideas for this. He will probably be in charge of writing this part of the plugin (which is obviously not written yet...).</p>
		
		
		';
    }
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/pi2/class.tx_templavoila_pi2.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/pi2/class.tx_templavoila_pi2.php']);
}
?>
