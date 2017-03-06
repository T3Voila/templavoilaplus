<?php
namespace Ppi\TemplaVoilaPlus\Controller\Preview;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Textpic controller
 */
class TextpicController extends TextController
{

    /**
     * @var string
     */
    protected $previewField = 'bodytext';

    /**
     * @param array $row
     * @param string $table
     * @param string $output
     * @param bool $alreadyRendered
     * @param object $ref
     *
     * @return string
     */
    public function render_previewContent($row, $table, $output, $alreadyRendered, &$ref)
    {

        $this->parentObj = $ref;

        $uploadDir = $GLOBALS['TCA']['tt_content']['columns']['image']['config']['internal_type'] == 'file_reference' ? '' : null;

        $thumbnail = '<strong>' . $this->getLanguageService()->sL(BackendUtility::getItemLabel('tt_content', 'image'), 1) . '</strong><br />';
        $thumbnail .= BackendUtility::thumbCode($row, 'tt_content', 'image', '', '', $uploadDir);

        $label = $this->getPreviewLabel();
        $data = $this->getPreviewData($row);

        if ($ref->currentElementBelongsToCurrentPage) {
            $text = $ref->link_edit('<strong>' . $label . '</strong> ' . $data, 'tt_content', $row['uid']);
        } else {
            $text = '<strong>' . $label . '</strong> ' . $data;
        }

        return '
		<table>
			<tr>
				<td valign="top">' . $text . '</td>
				<td valign="top">' . $thumbnail . '</td>
			</tr>
		</table>';
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
