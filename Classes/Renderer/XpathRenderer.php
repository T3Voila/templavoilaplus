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

                $template = $this->domDocument->createDocumentFragment();
                $template->appendXML((string) $processedValues[$fieldName]);
                if ($template->hasChildNodes()) {
                    $processingNode->appendChild($template);
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

    protected function changeName($node, $name)
    {
        $nodeReplacement = $node->ownerDocument->createElement($name);

        foreach ($node->childNodes as $child) {
            $nodeReplacement->appendChild($child->cloneNode(true));
        }

        foreach ($node->attributes as $attrName => $attrNode) {
            $nodeReplacement->setAttribute($attrName, $attrNode);
        }
        $node->parentNode->replaceChild($nodeReplacement, $node);

        return $nodeReplacement;
    }
}
