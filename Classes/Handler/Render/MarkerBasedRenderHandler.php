<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Handler\Render;

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

use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Tvp\TemplaVoilaPlus\Domain\Model\TemplateConfiguration;

class MarkerBasedRenderHandler implements RenderHandlerInterface
{
    public static $identifier = 'TVP\Renderer\MarkerBased';

    public function renderTemplate(TemplateConfiguration $templateConfiguration, array $processedValues, array $row): string
    {
        /** @var MarkerBasedTemplateService */
        $markerBasedTemplateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);

        $path = GeneralUtility::getFileAbsFileName($templateConfiguration->getPlace()->getEntryPoint());
        $content = file_get_contents($path . '/' . $templateConfiguration->getTemplateFileName());

        $content = $markerBasedTemplateService->substituteMarkerArray($content, $processedValues, '###|###', false, false);

        return $content;
    }

    public function processHeaderInformation(TemplateConfiguration $templateConfiguration)
    {
    }
}
