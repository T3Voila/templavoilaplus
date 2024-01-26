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
use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\TemplateConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class XpathRenderHandler implements RenderHandlerInterface
{
    public static $identifier = 'TVP\Renderer\XPath';

    protected $libXmlConfig = LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOENT | LIBXML_NONET;

    protected $domDocument;
    protected $domXpath;

    public function renderTemplate(TemplateConfiguration $templateConfiguration, array $processedValues, array $row): string
    {
        $this->domDocument = new \DOMDocument();
        libxml_use_internal_errors(true);
        /** @TODO Support non file here? The place do not need to be file based! */
        $path = GeneralUtility::getFileAbsFileName($templateConfiguration->getPlace()->getEntryPoint());
        $this->domDocument->loadHTMLFile($path . '/' . $templateConfiguration->getTemplateFileName(), $this->libXmlConfig);
        $this->domXpath = new \DOMXPath($this->domDocument);

        $mapping = $templateConfiguration->getMapping();

        $entries = $this->domXpath->query($mapping['xpath']);

        if ($entries->count() === 1) {
            $node = $entries->item(0);
            if (isset($mapping['container']) && is_array($mapping['container'])) {
                $this->processContainer($node, $mapping['container'], $processedValues, 'box');
            }

            return $this->getHtml($node, $mapping['mappingType']);
        }

        return '';
    }

    public function processHeaderInformation(TemplateConfiguration $templateConfiguration)
    {
        $headerConfiguration = $templateConfiguration->getHeader();

        /** @var \TYPO3\CMS\Core\Page\PageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);

        // Meta
        if (isset($headerConfiguration['meta']) && is_array($headerConfiguration['meta'])) {
            foreach ($headerConfiguration['meta'] as $metaName => $metaConfiguration) {
                if (version_compare(TYPO3_version, '9.3.0', '>=')) {
                    $pageRenderer->setMetaTag('name', $metaName, $metaConfiguration['content']);
                } else {
                    $pageRenderer->addMetaTag('<meta name="' . $metaName . '" content="' . $metaConfiguration['content'] . '">');
                }
            }
        }

        // CSS
        if (isset($headerConfiguration['css']) && is_array($headerConfiguration['css'])) {
            foreach ($headerConfiguration['css'] as $cssConfiguration) {
                $pageRenderer->addCssFile($cssConfiguration['href'], $cssConfiguration['rel'], $cssConfiguration['media']);
            }
        }
        // Javascript
        if (isset($headerConfiguration['javascript']) && is_array($headerConfiguration['javascript'])) {
            foreach ($headerConfiguration['javascript'] as $jsConfiguration) {
                $pageRenderer->addJsFile($jsConfiguration['src']);
            }
        }
    }

    protected function processContainer($node, $mappingConfiguration, $processedValues, $containerType, $mappingType = 'inner')
    {
        switch ($containerType) {
            case 'repeatable':
                $outerCloneNode = $node->cloneNode(true);
                $plainCloneNode = $node->cloneNode(false);
                foreach ($processedValues as $processedContainerValues) {
                    // For every entry we need a clean original node, so they can be appended (inner) or replaced (outer) afterwards
                    $cloneNode = $outerCloneNode->cloneNode(true);

                    $processingNodes = $this->getProcessingNodes($cloneNode, $mappingConfiguration);
                    $this->processValues($processingNodes, $mappingConfiguration, $processedContainerValues);

                    switch ($mappingType) {
                        case 'outer':
                            $cloneNode = $node->ownerDocument->importNode($cloneNode);
                            $node->parentNode->insertBefore($cloneNode, $node);
                            break;
                        case 'inner':
                        default:
                            foreach ($cloneNode->childNodes as $processedCloneNode) {
                                $processedCloneNode = $this->domDocument->importNode($processedCloneNode, true);
                                $plainCloneNode->appendChild($processedCloneNode->cloneNode(true));
                            }

                            break;
                    }
                }

                switch ($mappingType) {
                    case 'outer':
                        $node->parentNode->removeChild($node);
                        break;
                    case 'inner':
                    default:
                        $node->parentNode->replaceChild($plainCloneNode, $node);
                        break;
                }

                break;
            case 'box':
            default:
                // Default is box
                // Process directly, we are only one so no need of replacement processes afterwards
                $toProcessNode = $node;
                if ($mappingType === 'outer') {
                    $toProcessNode = $node->parentNode;
                }
                $processingNodes = $this->getProcessingNodes($toProcessNode, $mappingConfiguration);
                $this->processValues($processingNodes, $mappingConfiguration, $processedValues);
                break;
        }
    }

    protected function getProcessingNodes($parentNode, array $mappingConfiguration): array
    {
        $processingNodes = [];

        foreach ($mappingConfiguration as $fieldName => $fieldMappingConfiguration) {
            $result = $this->domXpath->query($fieldMappingConfiguration['xpath'], $parentNode);
            if ($result && $result->count() > 0) {
                $processingNodes[$fieldName] = $result->item(0);
            } else {
                /** @TODO Only in debug? Would be uncool to have such messages live */
                if ($result === false) {
                    var_dump('XPath: "' . $fieldMappingConfiguration['xpath'] . '" is invalid');
                } else {
                    var_dump('No result for XPath: "' . $fieldMappingConfiguration['xpath'] . '"');
                }
            }
        }

        return $processingNodes;
    }

    protected function processValues(array $processingNodes, array $mappingConfiguration, array $processedValues)
    {
        foreach ($processingNodes as $fieldName => $processingNode) {
            if (isset($mappingConfiguration[$fieldName])) {
                $this->processValue($processingNode, $fieldName, $mappingConfiguration[$fieldName], $processedValues);
            }
        }
    }

    protected function processValue($processingNode, $fieldName, array $mappingConfiguration, array $processedValues)
    {
        switch ($mappingConfiguration['mappingType'] ?? null) {
            case 'attrib':
                $processingNode->setAttribute($mappingConfiguration['attribName'], (string)$processedValues[$fieldName]);
                break;
            case 'inner':
                $this->processValueInner($mappingConfiguration, $processingNode, $processedValues, $fieldName);
                break;
            case 'outer':
                $this->processValueOuter($mappingConfiguration, $processingNode, $processedValues, $fieldName);
                break;
            default:
                /** @TODO Log error? */
        }
    }

    protected function processValueInner(array $mappingConfiguration, \DOMNode $processingNode, array $processedValues, string $fieldName)
    {
        if (isset($mappingConfiguration['container']) && is_array($mappingConfiguration['container'])) {
            $this->processContainer($processingNode, $mappingConfiguration['container'], $processedValues[$fieldName], $mappingConfiguration['containerType'], 'inner');
            return;
        }

        while ($processingNode->hasChildNodes()) {
            $processingNode->removeChild($processingNode->firstChild);
        }

        if (
            empty($processedValues[$fieldName])
            && isset($mappingConfiguration['removeIfEmpty'])
            && $mappingConfiguration['removeIfEmpty']
        ) {
            $processingNode->parentNode->removeChild($processingNode);
        }

        switch ($mappingConfiguration['valueType'] ?? null) {
            case 'html':
                if ($processedValues[$fieldName]) {
                    $tmpDoc = new \DOMDocument();
                    /** Add own tag to prevent automagical adding of <p> Tag around Tagless content */
                    /** Use LIBXML_HTML_NOIMPLIED and LIBXML_HTML_NODEFDTD so we don't get confused by extra added doctype, html and body nodes */
                    $tmpDoc->loadHTML('<?xml encoding="utf-8" ?><templavoilapluscontentwrap>' . $processedValues[$fieldName] . '</templavoilapluscontentwrap>', $this->libXmlConfig);

                    /** lastChild is our own added Tag from above */
                    foreach ($tmpDoc->lastChild->childNodes as $importNode) {
                        $importNode = $this->domDocument->importNode($importNode, true);
                        $processingNode->appendChild($importNode);
                    }
                }
                break;
            case 'plain':
            default:
                // Default is plain
                $processingNode->appendChild(
                    $this->domDocument->createTextNode((string)$processedValues[$fieldName])
                );
                break;
        }
    }

    protected function processValueOuter(array $mappingConfiguration, \DOMNode $processingNode, array $processedValues, string $fieldName): void
    {
        if (isset($mappingConfiguration['container']) && is_array($mappingConfiguration['container'])) {
            $this->processContainer($processingNode, $mappingConfiguration['container'], $processedValues[$fieldName], $mappingConfiguration['containerType'], 'outer');
            return;
        }

        if ($processingNode->parentNode === null) {
            // Template mapping wrong?
            return;
        }

        switch ($mappingConfiguration['valueType'] ?? null) {
            case 'html':
                if ($processedValues[$fieldName]) {
                    $tmpDoc = new \DOMDocument();
                    /** Add own tag to prevent automagical adding of <p> Tag around Tagless content */
                    /** Use LIBXML_HTML_NOIMPLIED and LIBXML_HTML_NODEFDTD so we don't get confused by extra added doctype, html and body nodes */
                    $tmpDoc->loadHTML('<?xml encoding="utf-8" ?><templavoilapluscontentwrap>' . $processedValues[$fieldName] . '</templavoilapluscontentwrap>', $this->libXmlConfig);

                    $isFirst = true;

                    /** lastChild is our own added Tag from above */
                    foreach ($tmpDoc->lastChild->childNodes as $importNode) {
                        $importNode = $processingNode->ownerDocument->importNode($importNode, true);
                        if ($isFirst) {
                            // In the first run we replace like we do in normal OUTER
                            $isFirst = false;
                            $processingNode->parentNode->replaceChild($importNode, $processingNode);
                        } else {
                            // In all following runs we add all nodes after our last node
                            // There is no insertAfter, so we need to test for nextSibling to insert before or append
                            if ($processingNode->nextSibling) {
                                $processingNode->parentNode->insertBefore($importNode, $processingNode->nextSibling);
                            } else {
                                $processingNode->parentNode->appendChild($importNode);
                            }
                        }
                        // in every following run we do the operation relative to last imported node
                        $processingNode = $importNode;
                    }
                } else {
                    $processingNode->parentNode->removeChild($processingNode);
                }
                break;
            case 'plain':
            default:
                // Default is plain
                $processingNode->parentNode->replaceChild(
                    $processingNode->ownerDocument->createTextNode((string)$processedValues[$fieldName]),
                    $processingNode
                );
        }
    }

    protected function getHtml($node, $type)
    {
        $contentOfNode = '';

        switch ($type) {
            case 'outer':
                $contentOfNode = $this->domDocument->saveHTML($node);
                break;
            case 'inner':
                $children = $node->childNodes;
                foreach ($node->childNodes as $childNode) {
                    $contentOfNode .= $this->domDocument->saveHTML($childNode);
                }
                break;
            default:
                /** @TODO Log error? */
        }
        return $contentOfNode;
    }
}
