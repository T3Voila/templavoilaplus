<?php
namespace Ppi\TemplaVoilaPlus\Tree\View;

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
 * Local position map class when creating new Content Elements
 */
class ContentCreationPagePositionMap extends \TYPO3\CMS\Backend\Tree\View\ContentCreationPagePositionMap
{
    /**
     * Create on-click event value.
     *
     * @param array $row The record.
     * @param string $vv Column position value.
     * @param int $moveUid Move uid
     * @param int $pid PID value.
     * @param int $sys_lang System language
     * @return string
     */
    public function onClickInsertRecord($row, $vv, $moveUid, $pid, $sys_lang = 0)
    {
        $parentRecord = GeneralUtility::_GP('parentRecord');
        if (!$parentRecord) {
            return parent::onClickInsertRecord($row, $vv, $moveUid, $pid, $sys_lang);
        }
        $location = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl(
            'web_txtemplavoilaplusLayout',
            [
                'id' => $pid,
                'createNewRecord' => GeneralUtility::_GP('parentRecord'),
            ]
        );
        return 'window.location.href=' . GeneralUtility::quoteJSvalue($location) . '+document.editForm.defValues.value; return false;';
    }
}
