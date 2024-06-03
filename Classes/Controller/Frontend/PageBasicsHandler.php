<?php

declare(strict_types=1);

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

namespace Tvp\TemplaVoilaPlus\Controller\Frontend;

use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\TemplateConfiguration;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class PageBasicsHandler
{
    public static function processConfiguration(TemplateConfiguration $templateConfiguration, TypoScriptFrontendController $controller)
    {
        $headerConfiguration = $templateConfiguration->getHeader();

        /** @var PageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

        // Meta
        if (isset($headerConfiguration['meta']) && is_array($headerConfiguration['meta'])) {
            foreach ($headerConfiguration['meta'] as $metaName => $metaConfiguration) {
                $pageRenderer->setMetaTag('name', $metaName, $metaConfiguration['content']);
            }
        }

        // CSS
        if (isset($headerConfiguration['css']) && is_array($headerConfiguration['css'])) {
            foreach ($headerConfiguration['css'] as $cssConfiguration) {
                $cssConfiguration['rel'] = ($cssConfiguration['rel'] ?? 'stylesheet');
                $cssConfiguration['media'] = ($cssConfiguration['media'] ?? 'all');
                $cssConfiguration['compress'] = (bool) ($cssConfiguration['compress'] ?? true);
                $cssConfiguration['forceOnTop'] = (bool) ($cssConfiguration['forceOnTop'] ?? false);
                $pageRenderer->addCssFile($cssConfiguration['href'], $cssConfiguration['rel'], $cssConfiguration['media'], '', $cssConfiguration['compress'], $cssConfiguration['forceOnTop']);
            }
        }
        // Javascript
        if (isset($headerConfiguration['javascript']) && is_array($headerConfiguration['javascript'])) {
            foreach ($headerConfiguration['javascript'] as $jsConfiguration) {
                $jsConfiguration['type'] = ($jsConfiguration['type'] ?? 'text/javascript');
                $jsConfiguration['compress'] = (bool) ($jsConfiguration['compress'] ?? true);
                $jsConfiguration['forceOnTop'] = (bool) ($jsConfiguration['forceOnTop'] ?? false);
                $pageRenderer->addJsFile($jsConfiguration['src'], $jsConfiguration['type'], $jsConfiguration['compress'], $jsConfiguration['forceOnTop']);
            }
        }

        $bodyConfiguration = $templateConfiguration->getBody();

        if (isset($bodyConfiguration['disableBodyTag'])) {
            $controller->config['config']['disableBodyTag'] = filter_var($bodyConfiguration['disableBodyTag'], FILTER_VALIDATE_BOOL);
        }
        if (isset($bodyConfiguration['bodyTag']) && is_string($bodyConfiguration['bodyTag'])) {
            $controller->pSetup['bodyTag'] = ($bodyConfiguration['bodyTag']);
        }
        if (isset($bodyConfiguration['bodyTagAdd']) && is_string($bodyConfiguration['bodyTagAdd'])) {
            $controller->pSetup['bodyTagAdd'] = ($bodyConfiguration['bodyTagAdd']);
        }
    }
}
