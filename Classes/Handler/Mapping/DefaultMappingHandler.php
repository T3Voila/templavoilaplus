<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Handler\Mapping;

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

    public function process($flexformData, $table, $row): array
    {
        $processedMapping = [];
        $containerInstructions = $this->mappingConfiguration->getMappingToTemplate();

        /** @TODO $table, $row are more globale vars, they are given from function to function */

        $processedMapping = $this->processContainer($flexformData, $table, $row, $containerInstructions);

        return $processedMapping;
    }

    protected function valueProcessing(array $instructions, array $flexformData, string $table, array $row)
    {
        $processedValue = '';

        if (isset($instructions['value'])) {
            $processedValue = $instructions['value'];
        }

        switch ($instructions['dataType']) {
            case 'row':
                if (isset($row[$instructions['dataPath']])) {
                    $processedValue = $row[$instructions['dataPath']] ?? '';
                }
                break;
            case 'flexform':
                if (isset($flexformData[$instructions['dataPath']])) {
                    $processedValue = $flexformData[$instructions['dataPath']] ?? '';
                }
                break;
            case 'typoscriptObjectPath':
                list($name, $conf) = $this->getTypoScriptParser()->getVal($instructions['dataPath'], $GLOBALS['TSFE']->tmpl->setup);
                $processedValue = $this->getContentObjectRenderer($flexformData, $processedValue, $table, $row)->cObjGetSingle($name, $conf, 'TemplaVoila_ProcObjPath--' . str_replace('.', '*', $instructions['dataPath']) . '.');
                break;
            default:
                // No dataType given, so no data management
        }

        /** @TODO Need to support multiple processings */
        switch ($instructions['valueProcessing']) {
            case 'typoScript':
                $processedValue = $this->processTypoScript($flexformData, $processedValue, $table, $row, $instructions['valueProcessing.typoScript'] ?? '');
                break;
            case 'typoScriptConstants':
                break;
            case 'repeatable':
                $processedValue = $this->processRepeatable($processedValue, $table, $row, $instructions['container']);
                break;
            case 'container':
                $processedValue = $this->processContainer($processedValue, $table, $row, $instructions['container']);
                break;
            case 'stdWrap':
                break;
            default:
                // No valueProcessing given, so no value manipulation
        }

        return $processedValue;
    }

    protected function processTypoScript(array $flexformData, $processedValue, string $table, array $row, string $theTypoScript): string
    {
        /** @var TypoScriptParser $tsparserObj */
        $tsparserObj = $this->getTypoScriptParser();
        /** @var ContentObjectRenderer $cObj */
        $cObj = $this->getContentObjectRenderer($flexformData, $processedValue, $table, $row);
        $restoreData = [];

        // add into TSFE register the parent rec data
        /** @TODO On every value which gets processed? Not realy? */
        $restoreData = $this->registerTypoScriptParentRec($row);

        // Copy current global TypoScript configuration except numerical objects:
        /** @TODO On every value which gets processed? Not realy? */
        if (is_array($GLOBALS['TSFE']->tmpl->setup)) {
            foreach ($GLOBALS['TSFE']->tmpl->setup as $tsObjectKey => $tsObjectValue) {
                if ($tsObjectKey !== (int)$tsObjectKey) {
                    $tsparserObj->setup[$tsObjectKey] = $tsObjectValue;
                }
            }
        }

        $tsparserObj->parse($theTypoScript);
        $processedValue = $cObj->cObjGet($tsparserObj->setup, 'TemplaVoila_Proc.');

        if (count($restoreData)) {
            /** @TODO On every value which gets processed? Not realy? */
            $this->restoreTypoScriptParentRec($restoreData);
        }

        return $processedValue;
    }

    protected function processRepeatable(array $flexformData, string $table, array $row, array $containerInstructions): array
    {
        $postprocessedValue = [];
        if (is_array($flexformData)) {
            foreach ($flexformData as $key => $preProcessedValue) {
                $postprocessedValue[$key] = $this->processContainer($preProcessedValue, $table, $row, $containerInstructions);
            }
        }
        return $postprocessedValue;
    }

    protected function processContainer(array $flexformData, string $table, array $row, array $containerInstructions): array
    {
        $postprocessedValue = [];
        if (is_array($containerInstructions)) {
            foreach ($containerInstructions as $templateFieldName => $instructions) {
                $postprocessedValue[$templateFieldName] = $this->valueProcessing($instructions, $flexformData, $table, $row);
            }
        }
        return $postprocessedValue;
    }

    protected function getTypoScriptParser(): TypoScriptParser
    {
        /** @var TypoScriptParser $tsparserObj */
        $tsparserObj = GeneralUtility::makeInstance(TypoScriptParser::class);
        return $tsparserObj;
    }

    protected function getContentObjectRenderer(array $flexformData, $processedValue, string $table, array $row): ContentObjectRenderer
    {
        /** @var ContentObjectRenderer $cObj */
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $cObj->setParent($row, $table . ':' . $row['uid']);
        $cObj->start($flexformData, '_NO_TABLE');
        $cObj->setCurrentVal($processedValue);

        return $cObj;
    }

    protected function registerTypoScriptParentRec(array $row): array
    {
        $restoreData = [
            'saveKeys' => [],
            'registerKeys' => [],
        ];

        // Step 1: save previous parent records from registers. This happens when pi1 is called for FCEs on a page.
        foreach ($GLOBALS['TSFE']->register as $dkey => $dvalue) {
            if (preg_match('/^tx_templavoilaplus_pi1\.parentRec\./', $dkey)) {
                $restoreData['saveKeys'][$dkey] = $dvalue;
                // Step 2: unset previous parent info
                unset($GLOBALS['TSFE']->register[$dkey]);
            }
        }

        // Step 3: set new parent record to register
        foreach ($row as $dkey => $dvalue) {
            $restoreData['registerKeys'][] = $tkey = 'tx_templavoilaplus_pi1.parentRec.' . $dkey;
            $GLOBALS['TSFE']->register[$tkey] = $dvalue;
        }

        return $restoreData;
    }


    protected function restoreTypoScriptParentRec(array $restoreData)
    {
        // Unset curent parent record info
        foreach ($restoreData['registerKeys'] as $dkey) {
            unset($GLOBALS['TSFE']->register[$dkey]);
        }

        // Restore previous parent record info if necessary
        foreach ($restoreData['saveKeys'] as $dkey => $dvalue) {
            $GLOBALS['TSFE']->register[$dkey] = $dvalue;
        }
    }
}
