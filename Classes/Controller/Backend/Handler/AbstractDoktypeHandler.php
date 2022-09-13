<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Controller\Backend\Handler;

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

use Tvp\TemplaVoilaPlus\Controller\Backend\PageLayoutController;
use Tvp\TemplaVoilaPlus\Exception\ConfigurationException;
use Tvp\TemplaVoilaPlus\Service\ApiService;
use Tvp\TemplaVoilaPlus\Service\ConfigurationService;
use Tvp\TemplaVoilaPlus\Service\ProcessingService;
use Tvp\TemplaVoilaPlus\Utility\ApiHelperUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractDoktypeHandler
{
    public static function addLocalizationInformationForPage(PageLayoutController $controller, array $pageRecord)
    {
        /** @var ProcessingService */
        $processingService = GeneralUtility::makeInstance(ProcessingService::class);

        $nodeTree = $processingService->getNodeWithLocalization('pages', $pageRecord);
        $controller->getView()->assign('node', $nodeTree);
    }
}
