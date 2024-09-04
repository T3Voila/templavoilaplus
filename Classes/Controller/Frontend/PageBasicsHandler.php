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
                $cssConfiguration['rel'] = (string) ($cssConfiguration['rel'] ?? 'stylesheet');
                $cssConfiguration['media'] = (string) ($cssConfiguration['media'] ?? 'all');
                $cssConfiguration['title'] = (string) ($cssConfiguration['title'] ?? 'all');
                $cssConfiguration['compress'] = (bool) ($cssConfiguration['compress'] ?? true);
                $cssConfiguration['forceOnTop'] = (bool) ($cssConfiguration['forceOnTop'] ?? false);
                $cssConfiguration['allWrap'] = (string) ($cssConfiguration['allWrap'] ?? '');
                $cssConfiguration['excludeFromConcatenation'] = (bool) ($cssConfiguration['excludeFromConcatenation'] ?? false);
                $cssConfiguration['splitChar'] = (string) ($cssConfiguration['splitChar'] ?? '|');
                $cssConfiguration['tagAttributes'] = (array) ($cssConfiguration['tagAttributes'] ?? []);
                $pageRenderer->addCssFile(
                    $cssConfiguration['href'],
                    $cssConfiguration['rel'],
                    $cssConfiguration['media'],
                    $cssConfiguration['title'],
                    $cssConfiguration['compress'],
                    $cssConfiguration['forceOnTop'],
                    $cssConfiguration['allWrap'],
                    $cssConfiguration['excludeFromConcatenation'],
                    $cssConfiguration['splitChar'],
                    false,
                    $cssConfiguration['tagAttributes']
                );
            }
        }
        // Javascript
        if (isset($headerConfiguration['javascript']) && is_array($headerConfiguration['javascript'])) {
            foreach ($headerConfiguration['javascript'] as $jsConfiguration) {
                $jsConfiguration['type'] = (string) ($jsConfiguration['type'] ?? 'text/javascript');
                $jsConfiguration['compress'] = (bool) ($jsConfiguration['compress'] ?? true);
                $jsConfiguration['forceOnTop'] = (bool) ($jsConfiguration['forceOnTop'] ?? false);
                $jsConfiguration['allWrap'] = (string) ($jsConfiguration['allWrap'] ?? '');
                $jsConfiguration['excludeFromConcatenation'] = (bool) ($jsConfiguration['excludeFromConcatenation'] ?? false);
                $jsConfiguration['splitChar'] = (string) ($jsConfiguration['splitChar'] ?? '|');
                $jsConfiguration['async'] = (bool) ($jsConfiguration['async'] ?? false);
                $jsConfiguration['sri'] = (string) ($jsConfiguration['sri'] ?? '');
                $jsConfiguration['defer'] = (bool) ($jsConfiguration['defer'] ?? false);
                $jsConfiguration['cors'] = ($jsConfiguration['cors'] ?? '');
                $jsConfiguration['noModule'] = (bool) ($jsConfiguration['noModule'] ?? false);
                $jsConfiguration['tagAttributes'] = (array) ($jsConfiguration['tagAttributes'] ?? []);
                $pageRenderer->addJsFile(
                    $jsConfiguration['src'],
                    $jsConfiguration['type'],
                    $jsConfiguration['compress'],
                    $jsConfiguration['forceOnTop'],
                    $jsConfiguration['allWrap'],
                    $jsConfiguration['excludeFromConcatenation'],
                    $jsConfiguration['splitChar'],
                    $jsConfiguration['async'],
                    $jsConfiguration['sri'],
                    $jsConfiguration['defer'],
                    $jsConfiguration['noModule'],
                    $jsConfiguration['tagAttributes']
                );
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
