<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Renderer;

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
use Ppi\TemplaVoilaPlus\Domain\Model\TemplateYamlConfiguration;

class XpathRenderer implements RendererInterface
{
    public const NAME = 'templavoilaplus_xpath';

    protected $domDocument;
    protected $domXpath;

    public function renderTemplate(TemplateYamlConfiguration $templateConfiguration, array $processedValues, array $row): string
    {
        $this->domDocument = new \DOMDocument();
        libxml_use_internal_errors(true);
        $this->domDocument->loadHTML($templateConfiguration->getTemplateFile()->getContents());
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

    public function processHeaderInformation(TemplateYamlConfiguration $templateConfiguration)
    {
        $headerConfiguration = $templateConfiguration->getHeader();

        /** @var \TYPO3\CMS\Core\Page\PageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);

        if (isset($headerConfiguration['css']) && is_array($headerConfiguration['css'])) {
            foreach ($headerConfiguration['css'] as $cssConfiguration) {
                $pageRenderer->addCssFile($cssConfiguration['href'], $cssConfiguration['rel'], $cssConfiguration['media']);
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

                $tmpDoc = new \DOMDocument();
                $tmpDoc->loadHTML($processedValues[$fieldName]);

                // loadHTML automagically adds the html and body tag so our childs to add are inside body
                /** @TODO This handling can be configured with libXML option LIBXML_HTML_NOIMPLIED */
                if ($tmpDoc->getElementsByTagName('body')->item(0)->hasChildNodes()) {
                    foreach ($tmpDoc->getElementsByTagName('body')->item(0)->childNodes as $importNode) {
                        $importNode = $this->domDocument->importNode($importNode, true);
                        $processingNode->appendChild($importNode);
                    }
                }
            } elseif ($entry['type'] === 'OUTER') {
                $processingNode->parentNode->replaceChild(
                    $this->domDocument->createTextNode((string) $processedValues[$fieldName]),
                    $processingNode
                );

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

    protected function getHtml($node, $type)
    {
        $contentOfNode = '';

        if ($type === 'INNER') {
            $contentOfNode = $this->domDocument->saveXML($node);
        } elseif ($type === 'OUTER') {
            $children = $node->childNodes;
            foreach ($node->childNodes as $childNode) {
                $contentOfNode .= $this->domDocument->saveXML($childNode);
            }
        }
        return $contentOfNode;
    }
}
