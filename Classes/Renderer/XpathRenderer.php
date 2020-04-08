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

    public function renderTemplate(TemplateYamlConfiguration $templateConfiguration, array $processedValues, array $row): string
    {
        $dom = new \DOMDocument();
        $dom->loadHTML($templateConfiguration->getTemplateFile()->getContents());
        $xpath = new \DOMXPath($dom);

        $mapping = $templateConfiguration->getMapping();

        $entries = $xpath->query($mapping['xpath']);

        if ($entries->count() === 1) {
            $node = $entries->item(0);
            if (isset($mapping['container']) && is_array($mapping['container'])) {
                $node = $this->processContainer($node, $mapping['container']);
            }

            return $this->getHtml($dom, $node, $mapping['type']);
        }

        return '';
    }

    protected function processContainer($node, $mapping)
    {
        foreach ($mapping as $fieldName => $entry) {
            $node = $this->processValue($node, $fieldName, $entry);
        }
        return $node;
    }

    protected function processValue($node, $fieldName, $entry)
    {
        return $node;
    }

    protected function getHtml($dom, $node, $type)
    {
        $contentOfNode = '';

        if ($type === 'OUTER') {
            $contentOfNode = $dom->saveHTML($node);
        } else {
            $node = $this->changeName($node, 'toBeDeletedMarker');
            $contentOfNode = $dom->saveHTML($node);
            // @TODO mb_eregi_replace?
            // @TODO Also remove whitespaces?
            $contentOfNode = str_replace(['<toBeDeletedMarker>', '</toBeDeletedMarker>'], '', $contentOfNode);
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
