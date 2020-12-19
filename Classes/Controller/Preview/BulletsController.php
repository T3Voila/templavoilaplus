<?php
namespace Tvp\TemplaVoilaPlus\Controller\Preview;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Bullets controller
 */
class BulletsController extends TextController
{

    /**
     * @var string
     */
    protected $previewField = 'bodytext';

    /**
     * @param array $row
     *
     * @return string
     */
    protected function getPreviewData($row)
    {
        if (isset($this->parentObj->modTSconfig['properties']['previewDataMaxLen'])) {
            $max = (int)$this->parentObj->modTSconfig['properties']['previewDataMaxLen'];
        } else {
            $max = 2000;
        }
        $htmlBullets = '';
        $bulletsArr = explode("\n", $this->preparePreviewData($row['bodytext']));
        if (is_array($bulletsArr)) {
            foreach ($bulletsArr as $listItem) {
                $processedItem = GeneralUtility::fixed_lgd_cs(trim(strip_tags($listItem)), $max);
                $max -= strlen($processedItem);
                $htmlBullets .= '<li>' . htmlspecialchars($processedItem) . '</li>';
                if (!$max) {
                    break;
                }
            }
        }

        return '<ul>' . $htmlBullets . '</ul>';
    }
}
