<?php
namespace Extension\Templavoila\Utility\StaticDataStructure;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Class for userFuncs within the Extension Manager.
 *
 * @author Steffen Kamper  <info@sk-typo3.de>
 */
class ToolsUtility {

	/**
	 * @param array $conf
	 */
	static public function readStaticDsFilesIntoArray($conf) {
		$paths = array_unique(array('fce' => $conf['staticDS.']['path_fce'], 'page' => $conf['staticDS.']['path_page']));
		foreach ($paths as $type => $path) {
			$absolutePath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($path);
			$files = \TYPO3\CMS\Core\Utility\GeneralUtility::getFilesInDir($absolutePath, 'xml', TRUE);
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
					$staticDataStructure['scope'] = \Extension\Templavoila\Domain\Model\AbstractDataStructure::SCOPE_FCE;
				} else {
					$staticDataStructure['scope'] = \Extension\Templavoila\Domain\Model\AbstractDataStructure::SCOPE_PAGE;
				}

				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['staticDataStructures'][] = $staticDataStructure;
			}
		}
	}
}
