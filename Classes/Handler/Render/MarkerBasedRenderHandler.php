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

use Psr\Http\Message\ServerRequestInterface;
use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\TemplateConfiguration;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MarkerBasedRenderHandler implements RenderHandlerInterface
{
    public static $identifier = 'TVP\Renderer\MarkerBased';

    public function renderTemplate(TemplateConfiguration $templateConfiguration, array $processedValues, array $row, ServerRequestInterface $request): string
    {
        /** @var MarkerBasedTemplateService */
        $markerBasedTemplateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);

        $path = GeneralUtility::getFileAbsFileName($templateConfiguration->getPlace()->getEntryPoint());
        $content = file_get_contents($path . '/' . $templateConfiguration->getTemplateFileName());

        if ($processedValues['__SUBPART__']) {
            $subPartContent = $markerBasedTemplateService->getSubpart($content, '###' . $processedValues['__SUBPART__'] . '###');
            if ($subPartContent !== '') {
                $content = $subPartContent;
            }
        }
        if (isset($processedValues['settings']) && is_array($processedValues['settings'])) {
            unset($processedValues['settings']);
        }
        $content = $markerBasedTemplateService->substituteMarkerArray($content, $processedValues, '###|###', false, true);

        return $content;
    }
}
