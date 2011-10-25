<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2009 Steffen Kamper (info@sk-typo3.de)
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
 * Class for userFuncs within the Extension Manager.
 *
 * @author	Steffen Kamper  <info@sk-typo3.de>
 */
class tx_templavoila_staticds_tools {

	/**
	 *
	 * @param unknown_type $conf
	 */
	public static function readStaticDsFilesIntoArray($conf) {
		$paths = array_unique(array('fce' => $conf['staticDS.']['path_fce'], 'page' => $conf['staticDS.']['path_page']));
		foreach ($paths as $type => $path) {
			$absolutePath = t3lib_div::getFileAbsFileName($path);
			$files = t3lib_div::getFilesInDir($absolutePath, 'xml', true);
			// if all files are in the same folder, don't resolve the scope by path type
			if (count($paths) == 1) {
				$type = FALSE;
			}
			foreach ($files as $filePath) {
				$staticDataStructure = array();
				$pathInfo = pathinfo($filePath);

				$staticDataStructure['title'] = $pathInfo['filename'];
				$staticDataStructure['path'] = substr($filePath, strlen(PATH_site));
				$iconPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.gif';
				if (file_exists($iconPath)) {
					$staticDataStructure['icon'] = substr($iconPath, strlen(PATH_site));
				}

				if (($type !== FALSE && $type === 'fce') || strpos($pathInfo['filename'], '(fce)') !== FALSE) {
					$staticDataStructure['scope'] = tx_templavoila_datastructure::SCOPE_FCE;
				} else {
					$staticDataStructure['scope'] = tx_templavoila_datastructure::SCOPE_PAGE;
				}

				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['staticDataStructures'][] = $staticDataStructure;
			}
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/class.tx_templavoila_staticds_tools.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/class.tx_templavoila_staticds_tools.php']);
}
?>