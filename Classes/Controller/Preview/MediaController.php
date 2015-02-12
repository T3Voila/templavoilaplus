<?php
namespace Extension\Templavoila\Controller\Preview;

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
 * Media controller
 */
class MediaController {

	/**
	 * @var string
	 */
	protected $previewField = 'media';

	/**
	 * @param array $row
	 * @param string $table
	 * @param string $output
	 * @param boolean $alreadyRendered
	 * @param object $ref
	 *
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
	 * @param array $row
	 *
	 * @return string
	 */
	protected function getPreviewData($row) {
		$data = '';
		if (is_array($row) && $row['pi_flexform']) {
			$flexform = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($row['pi_flexform']);
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
		return \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL(\TYPO3\CMS\Backend\Utility\BackendUtility::getLabelFromItemlist('tt_content', 'CType', $this->previewField));
	}
}
