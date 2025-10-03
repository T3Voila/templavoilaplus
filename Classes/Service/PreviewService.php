<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Service;

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
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Preview\StandardPreviewRendererResolver;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumn;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PreviewService
{
    public function buildPreviewInTree(array $pageRow, array $nodeTree): array
    {
        $backendLayout = BackendLayout::create('TV+', 'TV+', []);
        $pageLayoutContext = GeneralUtility::makeInstance(PageLayoutContext::class, $pageRow, $backendLayout);

        $nodeTree['node']['rendering']['preview'] = $this->buildNodePreview($pageRow, $nodeTree['node'], $pageLayoutContext);

        if (!empty($nodeTree['node']['childNodes'] ?? [])) {
            $childNodes = $nodeTree['node']['childNodes'];
            $sheets = $nodeTree['node']['datastructure']['sheets'] ?? [];

            foreach ($sheets as $sheetName => $sheetConfig) {
                $childNodes[$sheetName]['lDEF'] = $this->buildFieldTreePreview($pageRow, $sheetConfig['ROOT']['el'], $childNodes[$sheetName]['lDEF']);
            }

            $nodeTree['node']['childNodes'] = $childNodes;
        }

        foreach ($nodeTree['node']['localization'] as $languageKey => $localizations) {
            $preview = $this->buildNodePreview($pageRow, $localizations, $pageLayoutContext);
            $nodeTree['node']['localization'][$languageKey]['rendering']['preview'] = $preview;
            // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($preview);
        }

        return $nodeTree;
    }

    public function buildFieldTreePreview(array $pageRow, array $sheetConfig, array $sheetNodes): array
    {
        foreach ($sheetConfig as $fieldName => $fieldConfig) {
            if (($fieldConfig['type'] ?? '') == 'array') {
                if (($fieldConfig['section'] ?? '') == 1) {
                    foreach ($sheetNodes[$fieldName] as $id => $sectionNodes) {
                        $sheetNodes[$fieldName][$id] = $this->buildFieldTreePreview($pageRow, $fieldConfig['el'], $sheetNodes[$fieldName][$id]);
                    }
                } else {
                    $sheetNodes[$fieldName] = $this->buildFieldTreePreview($pageRow, $fieldConfig['el'], $sheetNodes[$fieldName]);
                }
            } elseif (isset($sheetNodes[$fieldName]['vDEF'])) {
                foreach ($sheetNodes[$fieldName]['vDEF'] as $position => $node) {
                    $sheetNodes[$fieldName]['vDEF'][$position] = $this->buildPreviewInTree($pageRow, $node);
                }
            }
        }

        return $sheetNodes;
    }

    public function buildNodePreview(array $pageRow, array $node, PageLayoutContext $pageLayoutContext): ?string
    {
        $columnObject = GeneralUtility::makeInstance(GridColumn::class, $pageLayoutContext, []);
        $columnItem = GeneralUtility::makeInstance(GridColumnItem::class, $pageLayoutContext, $columnObject, $node['raw']['entity']);

        try {
            $previewRenderer = GeneralUtility::makeInstance(StandardPreviewRendererResolver::class)
                ->resolveRendererFor(
                    $node['raw']['table'],
                    $node['raw']['entity'],
                    (int)$pageRow['uid']
                );
        } catch (\RuntimeException $e) {
            return null;
        }
        $previewHeader = $previewRenderer->renderPageModulePreviewHeader($columnItem);

        // Dispatch event to allow listeners adding an alternative content type
        // specific preview or to manipulate the content elements' record data.
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
            new PageContentPreviewRenderingEvent($node['raw']['table'], $node['raw']['entity'], $pageLayoutContext)
        );

        // Update the modified record data
        $node['raw']['entity'] = $event->getRecord();

        // Get specific preview from listeners. In case non was added,
        // fall back to the standard preview rendering workflow.
        $previewContent = $event->getPreviewContent();
        if ($previewContent === null) {
            $previewContent = $previewRenderer->renderPageModulePreviewContent($columnItem);
        }

        // Remove the links from StandardContentPreviewRenderer::linkEditContent
        $previewHeader = preg_replace('/<a[^>]*>(.*?)<\/a>/', '$1', $previewHeader);
        $previewContent = preg_replace('/<a[^>]*>(.*?)<\/a>/', '$1', $previewContent);

        return $previewHeader . $previewContent . $columnItem->getFooterInfo();
    }
}
