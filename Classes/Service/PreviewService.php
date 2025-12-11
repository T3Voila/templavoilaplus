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
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Preview\StandardPreviewRendererResolver;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumn;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Backend\View\Drawing\DrawingConfiguration;
use TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Backend\View\PageViewMode;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PreviewService
{
    public function buildPreviewInTree(array $pageRow, array $nodeTree, ServerRequestInterface $request, array $tsConfig): array
    {
        $backendLayout = BackendLayout::create('TV+', 'TV+', []);

        if (version_compare((string) new Typo3Version(), '13.0', '>')) {
            $drawingConfiguration = DrawingConfiguration::create($backendLayout, $tsConfig, PageViewMode::LayoutView);
            $pageLayoutContext = GeneralUtility::makeInstance(PageLayoutContext::class, $pageRow, $backendLayout, $request->getAttribute('site'), $drawingConfiguration, $request);
        } else {
            $pageLayoutContext = GeneralUtility::makeInstance(PageLayoutContext::class, $pageRow, $backendLayout);
        }

        $nodeTree['node']['rendering']['preview'] = $this->buildNodePreview($pageRow, $nodeTree['node'], $pageLayoutContext);

        if (!empty($nodeTree['node']['childNodes'] ?? [])) {
            $childNodes = $nodeTree['node']['childNodes'];
            $sheets = $nodeTree['node']['datastructure']['sheets'] ?? [];

            foreach ($sheets as $sheetName => $sheetConfig) {
                $childNodes[$sheetName]['lDEF'] = $this->buildFieldTreePreview($pageRow, $sheetConfig['ROOT']['el'], $childNodes[$sheetName]['lDEF'], $request, $tsConfig);
            }

            $nodeTree['node']['childNodes'] = $childNodes;
        }

        foreach ($nodeTree['node']['localization'] as $languageKey => $localizations) {
            $preview = $this->buildNodePreview($pageRow, $localizations, $pageLayoutContext);
            $nodeTree['node']['localization'][$languageKey]['rendering']['preview'] = $preview;
        }
        return $nodeTree;
    }

    public function buildFieldTreePreview(array $pageRow, array $sheetConfig, array $sheetNodes, ServerRequestInterface $request, array $tsConfig): array
    {
        foreach ($sheetConfig as $fieldName => $fieldConfig) {
            if (($fieldConfig['type'] ?? '') == 'array') {
                if (isset($sheetNodes[$fieldName])) {
                    if (($fieldConfig['section'] ?? '') == 1) {
                        foreach ($sheetNodes[$fieldName] as $id => $sectionNodes) {
                            $sheetNodes[$fieldName][$id] = $this->buildFieldTreePreview($pageRow, $fieldConfig['el'], $sheetNodes[$fieldName][$id], $request, $tsConfig);
                        }
                    } else {
                        $sheetNodes[$fieldName] = $this->buildFieldTreePreview($pageRow, $fieldConfig['el'], $sheetNodes[$fieldName], $request, $tsConfig);
                    }
                }
            } elseif (isset($sheetNodes[$fieldName]['vDEF'])) {
                foreach ($sheetNodes[$fieldName]['vDEF'] as $position => $node) {
                    $sheetNodes[$fieldName]['vDEF'][$position] = $this->buildPreviewInTree($pageRow, $node, $request, $tsConfig);
                }
            }
        }

        return $sheetNodes;
    }

    public function buildNodePreview(array $pageRow, array $node, PageLayoutContext $pageLayoutContext): ?string
    {
        $columnObject = GeneralUtility::makeInstance(GridColumn::class, $pageLayoutContext, []);
        $columnItem = GeneralUtility::makeInstance(GridColumnItem::class, $pageLayoutContext, $columnObject, $node['raw']['entity']);

        $table = $node['raw']['table'];
        $row = $node['raw']['entity'];

        // StandardPreviewRendererResolver cannot handle type fields in the format localField:foreignField
        if (isset($GLOBALS['TCA'][$table]['ctrl']['type']) && str_contains($GLOBALS['TCA'][$table]['ctrl']['type'], ':')) {
            return null;
        }
        if ($table === 'tt_content' && $row['CType'] === 'templavoilaplus_pi1') {
            return null;
        }

        try {
            $previewRenderer = GeneralUtility::makeInstance(StandardPreviewRendererResolver::class)
                ->resolveRendererFor(
                    $table,
                    $row,
                    (int)$pageRow['uid']
                );
        } catch (\RuntimeException $e) {
            return null;
        }
        $previewHeader = $previewRenderer->renderPageModulePreviewHeader($columnItem);

        if (version_compare((string) new Typo3Version(), '13.0', '>')) {
            /** @param recordType not supported yet */
            $event = new PageContentPreviewRenderingEvent($table, '', $row, $pageLayoutContext);
        } else {
            $event = new PageContentPreviewRenderingEvent($table, $row, $pageLayoutContext);
        }
        // Dispatch event to allow listeners adding an alternative content type
        // specific preview or to manipulate the content elements' record data.
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
            $event
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
        $previewHeader = preg_replace('/<a[^>]*>(.*?)<\/a>/s', '$1', $previewHeader);
        $previewContent = preg_replace('/<a[^>]*>(.*?)<\/a>/s', '$1', $previewContent);

        return $previewHeader . $previewContent . $columnItem->getFooterInfo();
    }
}
