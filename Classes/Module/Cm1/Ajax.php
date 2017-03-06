<?php
namespace Ppi\TemplaVoilaPlus\Module\Cm1;

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

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Ajax class for displaying content form a file
 */
class Ajax
{
    /**
     * Return the content of the current "displayFile"
     *
     * @param array $params
     * @param object $ajaxObj
     *
     * @return void
     */
    public function getDisplayFileContent($params, &$ajaxObj)
    {
        $session = TemplaVoilaUtility::getBackendUser()->getSessionData(GeneralUtility::_GP('key'));
        echo GeneralUtility::getUrl(GeneralUtility::getFileAbsFileName($session['displayFile']));
    }
}
