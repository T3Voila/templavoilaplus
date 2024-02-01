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

use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\MappingConfiguration;
use Tvp\TemplaVoilaPlus\Domain\Repository\Localization\LocalizationRepository;
use Tvp\TemplaVoilaPlus\Utility\RecordFalUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;

/**
 * @TODO
 * -Interface
 * -Registration
 * -More needed besides this Default one?
 * - It's hardwired still
 */
class DefaultMappingHandler
{
    public function process(MappingConfiguration $mappingConfiguration, array $flexformData, string $table, array $row): array
    {
        $processedMapping = [];
        $containerInstructions = $mappingConfiguration->getMappingToTemplate();

        /** @TODO $table, $row are more global vars, they are given from function to function */

        $processedMapping = $this->processContainer($flexformData, $table, $row, $containerInstructions);

        return $processedMapping;
    }

    // phpcs:disable Generic.Metrics.CyclomaticComplexity
    public function valueProcessing(array $instructions, array $flexformData, string $table, array $row)
    {
        // phpcs:enable
        $oldCurrentFieldRegister = $GLOBALS['TSFE']->register['tx_templavoilaplus_pi1.current_field'] ?? null;
        $processedValue = '';

        if (isset($instructions['value'])) {
            $processedValue = $instructions['value'];
        }

        if (isset($instructions['dataPath'])) {
            $GLOBALS['TSFE']->register['tx_templavoilaplus_pi1.current_field'] = $instructions['dataPath'];
        }

        switch ($instructions['dataType'] ?? null) {
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
                [$name, $conf] = $this->getTypoScriptParser()->getVal($instructions['dataPath'], $GLOBALS['TSFE']->tmpl->setup);
                $processedValue = $this->getContentObjectRenderer($flexformData, $processedValue, $table, $row)->cObjGetSingle($name, $conf, 'TemplaVoila_ProcObjPath--' . str_replace('.', '*', $instructions['dataPath']) . '.');
                break;
            default:
                // No dataType given, so no data management
        }

        /** @TODO Need to support multiple processings */
        switch ($instructions['valueProcessing'] ?? '') {
            case 'typoScript':
                $processedValue = $this->processTypoScript($flexformData, $processedValue, $table, $row, $instructions['valueProcessing.typoScript'] ?? '');
                break;
            case 'dataProcessing':
                $processedValue = $this->processDataProcessing($flexformData, $processedValue, $table, $row, $instructions['valueProcessing.dataProcessing'] ?? '');
                break;
            case 'typoScriptConstants':
                break;
            case 'repeatable':
                if (is_array($processedValue)) {
                    $processedValue = $this->processRepeatable($processedValue, $table, $row, $instructions['container']);
                } else {
                    $processedValue = [];
                }
                break;
            case 'container':
                if (is_array($processedValue)) {
                    $processedValue = $this->processContainer($processedValue, $table, $row, $instructions['container']);
                }
                break;
            case 'rowList':
                $processedValue = $this->processRowList($processedValue, $table, $row);
                break;
            case 'stdWrap':
                break;
            default:
                // No valueProcessing given, so no value manipulation
        }
        if ($oldCurrentFieldRegister) {
            $GLOBALS['TSFE']->register['tx_templavoilaplus_pi1.current_field'] = $oldCurrentFieldRegister;
        } else {
            unset($GLOBALS['TSFE']->register['tx_templavoilaplus_pi1.current_field']);
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

        // Add parent rec data into the TSFE register
        /** @TODO On every value which gets processed? Not really? */
        $restoreData = $this->registerTypoScriptParentRec($row);

        // Copy current global TypoScript configuration except numerical objects:
        /** @TODO On every value which gets processed? Not really? */
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
            /** @TODO On every value which gets processed? Not really? */
            $this->restoreTypoScriptParentRec($restoreData);
        }

        return $processedValue;
    }

    protected function processDataProcessing(array $flexformData, $processedValue, string $table, array $row, string $theTypoScript): array
    {
        /** @var TypoScriptParser $tsparserObj */
        $tsparserObj = $this->getTypoScriptParser();
        /** @var ContentObjectRenderer $cObj */
        $cObj = $this->getContentObjectRenderer($flexformData, $processedValue, $table, $row);

        $tsparserObj->parse($theTypoScript);
        $dataProcessor = GeneralUtility::makeInstance(ContentDataProcessor::class);
        $processedValue = $dataProcessor->process($cObj, ['dataProcessing.' => $tsparserObj->setup], $flexformData + $row);

        if (isset($processedValue['_processedValue_']) && is_array($processedValue['_processedValue_'])) {
            return $processedValue['_processedValue_'];
        }

        return [];
    }

    protected function processRepeatable(array $flexformData, string $table, array $row, array $containerInstructions): array
    {
        $postprocessedValue = [];
        foreach ($flexformData as $key => $preProcessedValue) {
            $postprocessedValue[$key] = $this->processContainer($preProcessedValue, $table, $row, $containerInstructions);
        }
        return $postprocessedValue;
    }

    protected function processContainer(array $flexformData, string $table, array $row, array $containerInstructions): array
    {
        $postprocessedValue = [];
        foreach ($containerInstructions as $templateFieldName => $instructions) {
            $postprocessedValue[$templateFieldName] = $this->valueProcessing($instructions, $flexformData, $table, $row);
        }
        return $postprocessedValue;
    }

    protected function processRowList(string $flexformData, string $table, array $row): array
    {
        $postprocessedValue = [];
        $childrenUids = explode(',', $flexformData);
        foreach ($childrenUids as $uid) {
            $record = LocalizationRepository::getLanguageOverlayRecord($table, $uid);
            // hidden records are returned as null
            if ($record) {
                $recordWithFal = RecordFalUtility::addFalReferencesToRecord($table, $record);
                $postprocessedValue[] = $recordWithFal;
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
        $cObj->start($flexformData + $row, '_NO_TABLE');
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
            $tkey = 'tx_templavoilaplus_pi1.parentRec.' . $dkey;
            $restoreData['registerKeys'][] = $tkey;
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
