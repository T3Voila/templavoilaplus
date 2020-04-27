<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Handler\Render;

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

/** @TODO Missing Base class */
use Ppi\TemplaVoilaPlus\Domain\Model\TemplateConfiguration;

class XpathRenderHandler implements RenderHandlerInterface
{
    static public $identifier = 'TVP\Renderer\XPath';

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
                $node = $this->processContainer($node, $mapping['container'], $processedValues);
            }

            return $this->getHtml($node, $mapping['type']);
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
                    $pageRenderer->setMetaTag('name', $metaName,  $metaConfiguration['content']);
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

    protected function processContainer($node, $mapping, $processedValues)
    {
        foreach ($mapping as $fieldName => $entry) {
            $node = $this->processValue($node, $fieldName, $entry, $processedValues);
        }
        return $node;
    }

    protected function processValue($node, $fieldName, $entry, $processedValues)
    {
        $result = $this->domXpath->query($entry['xpath'], $node);

        if ($result && $result->count() > 0) {
            $processingNode = $result->item(0);

            if ($entry['type'] === 'ATTRIB') {
                $processingNode->setAttribute($entry['attribName'], (string) $processedValues[$fieldName]);
            } elseif ($entry['type'] === 'INNER') {
                while ($processingNode->hasChildNodes()) {
                    $processingNode->removeChild($processingNode->firstChild);
                }
                $processingNode->appendChild(
                    $this->domDocument->createTextNode((string) $processedValues[$fieldName])
                );
            } elseif ($entry['type'] === 'INNERCHILD') {
                while ($processingNode->hasChildNodes()) {
                    $processingNode->removeChild($processingNode->firstChild);
                }

                if ($processedValues[$fieldName]) {
                    $tmpDoc = new \DOMDocument();
                    /** Add own tag to prevent automagical adding of <p> Tag around Tagless content */
                    /** Use LIBXML_HTML_NOIMPLIED and LIBXML_HTML_NODEFDTD so we don't get confused by extra added doctype, html and body nodes */
                    $tmpDoc->loadHTML('<?xml encoding="utf-8" ?><__TEMPLAVOILAPLUS__>' . $processedValues[$fieldName] . '</__TEMPLAVOILAPLUS__>', $this->libXmlConfig);

                    /** lastChild is our own added Tag from above */
                    foreach ($tmpDoc->lastChild->childNodes as $importNode) {
                        $importNode = $this->domDocument->importNode($importNode, true);
                        $processingNode->appendChild($importNode);
                    }
                }
            } elseif ($entry['type'] === 'OUTER') {
                $processingNode->parentNode->replaceChild(
                    $this->domDocument->createTextNode((string) $processedValues[$fieldName]),
                    $processingNode
                );
            } elseif ($entry['type'] === 'OUTERCHILD') {
                if ($processedValues[$fieldName]) {
                    $tmpDoc = new \DOMDocument();
                    /** Add own tag to prevent automagical adding of <p> Tag around Tagless content */
                    /** Use LIBXML_HTML_NOIMPLIED and LIBXML_HTML_NODEFDTD so we don't get confused by extra added doctype, html and body nodes */
                    $tmpDoc->loadHTML('<?xml encoding="utf-8" ?><__TEMPLAVOILAPLUS__>' . $processedValues[$fieldName] . '</__TEMPLAVOILAPLUS__>', $this->libXmlConfig);

                    $isFirst = true;

                    /** lastChild is our own added Tag from above */
                    foreach ($tmpDoc->lastChild->childNodes as $importNode) {
                        $importNode = $this->domDocument->importNode($importNode, true);
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
            }
        } else {
            // @TODO Only in debug? Would be uncool to have such messages live
            if ($result === false) {
                var_dump('XPath: "' . $entry['xpath'] . '" is invalid');
            } else {
                var_dump('No result for XPath: "' . $entry['xpath'] . '"');
            }
        }

        return $node;
    }

    protected function  getHtml($node, $type)
    {
        $contentOfNode = '';

        if ($type === 'INNER') {
            $contentOfNode = $this->domDocument->saveHTML($node);
        } elseif ($type === 'OUTER') {
            $children = $node->childNodes;
            foreach ($node->childNodes as $childNode) {
                $contentOfNode .= $this->domDocument->saveHTML($childNode);
            }
        }

        return $contentOfNode;
    }
}
