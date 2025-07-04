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
use Tvp\TemplaVoilaPlus\Handler\Render\RenderHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class FluidRenderHandler implements RenderHandlerInterface
{
    public static $identifier = 'TVP\Renderer\Fluid';

    public function renderTemplate(TemplateConfiguration $templateConfiguration, array $processedValues, array $row, ServerRequestInterface $request): string
    {
        /** @var StandaloneView */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $path = GeneralUtility::getFileAbsFileName($templateConfiguration->getPlace()->getEntryPoint());
        $options = $templateConfiguration->getOptions();

        $view->setRequest($request);
        /** @TODO Check if template file otherwise bad error messages will happen */
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateConfiguration->getTemplateFileName()));

        if (isset($options['layoutRootPaths'])) {
            $view->setLayoutRootPaths($options['layoutRootPaths']);
        }
        if (isset($options['partialRootPaths'])) {
            $view->setPartialRootPaths($options['partialRootPaths']);
        }

        $parentRec = [];
        if (isset($GLOBALS['TSFE']->register) && is_array($GLOBALS['TSFE']->register)) {
            foreach ($GLOBALS['TSFE']->register as $key => $value) {
                if (strpos($key, 'tx_templavoilaplus_pi1.parentRec.') === 0) {
                    $parentRec[substr($key, strlen('tx_templavoilaplus_pi1.parentRec.'))] = $value;
                }
            }
        }
        $view->assign('parentRec', $parentRec);
        $view->assign('current_field', $GLOBALS['TSFE']->register['tx_templavoilaplus_pi1.current_field'] ?? null);

        /** @TODO process remapping instructions before */
        $view->assignMultiple($processedValues);
        $view->assign('data', $row);

        try {
            return $view->render() ?? '';
        } catch (\Exception $e) {
            /** @TODO Error message */
            return $e->getMessage();
        }
        return '';
    }

    /**
     * @TODO Move this in an AbstractRenderHandler class
     */
    public function processHeaderInformation(TemplateConfiguration $templateConfiguration)
    {
        $headerConfiguration = $templateConfiguration->getHeader();

        /** @var \TYPO3\CMS\Core\Page\PageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);

        // Meta
        if (isset($headerConfiguration['meta']) && is_array($headerConfiguration['meta'])) {
            foreach ($headerConfiguration['meta'] as $metaName => $metaConfiguration) {
                $pageRenderer->setMetaTag('name', $metaName, $metaConfiguration['content']);
            }
        }

        // CSS
        if (isset($headerConfiguration['css']) && is_array($headerConfiguration['css'])) {
            foreach ($headerConfiguration['css'] as $cssConfiguration) {
                $pageRenderer->addCssFile($cssConfiguration['href'], $cssConfiguration['rel'], $cssConfiguration['media']);
            }
        }
var_dump($headerConfiguration);die();
        // Javascript
        if (isset($headerConfiguration['javascript']) && is_array($headerConfiguration['javascript'])) {
            foreach ($headerConfiguration['javascript'] as $jsConfiguration) {
                $pageRenderer->addJsFile($jsConfiguration['src']);
            }
        }
    }
}
