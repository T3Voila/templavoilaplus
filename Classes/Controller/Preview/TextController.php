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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Text controller
 */
class TextController
{

    /**
     * @var string
     */
    protected $previewField = 'bodytext';

    /**
     * @var mixed
     */
    protected $parentObj;

    /**
     * @param array $row
     * @param string $table
     * @param string $output
     * @param boolean $alreadyRendered
     * @param object $ref
     *
     * @return string
     */
    public function render_previewContent($row, $table, $output, $alreadyRendered, &$ref)
    {
        $this->parentObj = $ref;
        $label = $this->getPreviewLabel();
        $data = $this->getPreviewData($row);
        if ($ref->currentElementBelongsToCurrentPage) {
            return $ref->link_edit('<strong>' . $label . '</strong> ' . $data, 'tt_content', $row['uid']);
        } else {
            return '<strong>' . $label . '</strong> ' . $data;
        }
    }

    /**
     * @return string
     */
    protected function getPreviewLabel()
    {
        return $this->getLanguageService()->sL(BackendUtility::getItemLabel('tt_content', $this->previewField), 1);
    }

    /**
     * @param array $row
     *
     * @return string
     */
    protected function getPreviewData($row)
    {
        return $this->preparePreviewData($row[$this->previewField]);
    }

    /**
     * Performs a cleanup of the field values before they're passed into the preview
     *
     * @param string $str input usually taken from bodytext or any other field
     * @param integer $max some items might not need to cover the full maximum
     * @param boolean $stripTags HTML-blocks usually keep their tags
     *
     * @return string the properly prepared string
     */
    protected function preparePreviewData($str, $max = null, $stripTags = true)
    {
        //Enable to omit that parameter
        if ($max === null) {
            if (isset($this->parentObj->modTSconfig['properties']['previewDataMaxLen'])) {
                $max = (int)$this->parentObj->modTSconfig['properties']['previewDataMaxLen'];
            } else {
                $max = 2000;
            }
        }
        if ($stripTags) {
            //remove tags but avoid that the output is concatinated without spaces (#8375)
            $newStr = strip_tags(preg_replace('/(\S)<\//', '\1 </', $str));
        } else {
            $newStr = $str;
        }

        if (isset($this->parentObj->modTSconfig['properties']['previewDataMaxWordLen'])) {
            $wordLen = (int)$this->parentObj->modTSconfig['properties']['previewDataMaxWordLen'];
        } else {
            $wordLen = 75;
        }

        if ($wordLen) {
            $newStr = preg_replace('/(\S{' . $wordLen . '})/', '\1 ', $newStr);
        }

        return htmlspecialchars(GeneralUtility::fixed_lgd_cs(trim($newStr), $max));
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
