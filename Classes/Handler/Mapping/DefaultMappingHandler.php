<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Handler\Mapping;

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

use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * @TODO
 * -Interface
 * -Registration
 * -More needed besides this Default one?
 * - Its hard wired till yet
 */
class DefaultMappingHandler
{
    protected $mappingConfiguration;

    public function __construct($mappingConfiguration)
    {
        $this->mappingConfiguration = $mappingConfiguration;
    }

    public function process($flexformData, $row): array
    {
        $processedMapping = [];
        $mappingToTemplate = $this->mappingConfiguration->getMappingToTemplate();

        foreach($mappingToTemplate as $templateFieldName => $instructions) {
            $processedValue = '';
            if ($instructions['fieldType'] === 'row') {
                if (isset($row[$instructions['fieldName']])) {
                    $processedValue = (string) $row[$instructions['fieldName']];
                }
            }
            if ($instructions['fieldType'] === 'typoscriptObjectPath') {
                /** @var TypoScriptParser $tsparserObj */
                $tsparserObj = GeneralUtility::makeInstance(TypoScriptParser::class);
                /** @var ContentObjectRenderer $cObj */
                $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);

                list($name, $conf) = $tsparserObj->getVal($instructions['fieldName'], $GLOBALS['TSFE']->tmpl->setup);
                $processedValue = $cObj->cObjGetSingle($name, $conf, 'TemplaVoila_ProcObjPath--' . str_replace('.', '*', $instructions['fieldName']) . '.');
            }
            $processedMapping[$templateFieldName] = $processedValue;
        }

        return $processedMapping;
    }
}
