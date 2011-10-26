<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2011 Felix Nagel (info@felixnagel.com)
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

class tx_templavoila_preview_type_media {

	protected $previewField = 'media';

	/**
	 *
	 * @param array $row
	 * @param string $table
	 * @param string $output
	 * @param boolean $alreadyRendered
	 * @param object $ref
	 * @return string
	 */
	public function render_previewContent($row, $table, $output, $alreadyRendered, &$ref) {
		$label = $this->getPreviewLabel();
		$data = $this->getPreviewData($row);

		if ($ref->currentElementBelongsToCurrentPage) {
			return $ref->link_edit('<strong>' . $label . '</strong> ' . $data, 'tt_content', $row['uid']);
		} else {
			return '<strong>' . $label . '</strong> ' . $data;
		}
	}

	/**
	 *
	 * @param array $row
	 * @return string
	 */
	protected function getPreviewData($row) {
		$data = '';
		if (is_array($row) && $row['pi_flexform']) {
			$flexform = t3lib_div::xml2array($row['pi_flexform']);
			if (isset($flexform['data']['sDEF']['lDEF']['mmFile']['vDEF'])) {
				$data = '<span>' . $flexform['data']['sDEF']['lDEF']['mmFile']['vDEF'] . '</span>';
			}
		}
		return $data;
	}

	/**
	 * @return string
	 */
	protected function getPreviewLabel() {
		return $GLOBALS['LANG']->sL(t3lib_BEfunc::getLabelFromItemlist('tt_content', 'CType', $this->previewField));
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/preview/class.tx_templavoila_preview_type_media.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/preview/class.tx_templavoila_preview_type_media.php']);
}

?>
