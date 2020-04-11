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
            $processedMapping[$templateFieldName] = $this->valueProcessing($instructions, $flexformData, $row);
        }

        return $processedMapping;
    }

    protected function valueProcessing(array $instructions, array $flexformData, array $row)
    {
        $processedValue = '';

        if (isset($instructions['value'])) {
            $processedValue = $instructions['value'];
        }

        switch ($instructions['dataType']) {
            case 'row':
                if (isset($row[$instructions['dataPath']])) {
                    $processedValue = (string) $row[$instructions['dataPath']];
                }
                break;
            case 'flexform':
                if (isset($flexformData[$instructions['dataPath']])) {
                    $processedValue = (string) $flexformData[$instructions['dataPath']];
                }
                break;
            case 'typoscriptObjectPath':
                list($name, $conf) = $this->getTypoScriptParser()->getVal($instructions['dataPath'], $GLOBALS['TSFE']->tmpl->setup);
                $processedValue = $this->getContentObjectRenderer($processedValue, $row)->cObjGetSingle($name, $conf, 'TemplaVoila_ProcObjPath--' . str_replace('.', '*', $instructions['dataPath']) . '.');
                break;
            default:
                // No dataType given, so no data management
        }

        /** @TODO Need to support multiple processings */
        switch ($instructions['valueProcessing']) {
            case 'typoScript':
                /** @var TypoScriptParser $tsparserObj */
                $tsparserObj = $this->getTypoScriptParser();
                /** @var ContentObjectRenderer $cObj */
                $cObj = $this->getContentObjectRenderer($processedValue, $row);
                $tsparserObj->parse($instructions['valueProcessing.typoScript']);
                $processedValue = $cObj->cObjGet($tsparserObj->setup, 'TemplaVoila_Proc.');
                break;
            case 'typoScriptConstants':
                break;
            case 'stdWrap':
                break;
            default:
                // No valueProcessing given, so no value manipulation
        }

        return $processedValue;
    }

    protected function getTypoScriptParser(): TypoScriptParser
    {
        /** @var TypoScriptParser $tsparserObj */
        $tsparserObj = GeneralUtility::makeInstance(TypoScriptParser::class);
        return $tsparserObj;
    }

    protected function getContentObjectRenderer($processedValue, $row): ContentObjectRenderer
    {
        /** @var ContentObjectRenderer $cObj */
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $cObj->setParent($row, '');
        $cObj->setCurrentVal($processedValue);

        return $cObj;
    }
}
