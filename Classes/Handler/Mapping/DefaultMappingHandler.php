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

use Psr\Http\Message\ServerRequestInterface;
use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\MappingConfiguration;
use Tvp\TemplaVoilaPlus\Domain\Repository\Localization\LocalizationRepository;
use Tvp\TemplaVoilaPlus\Utility\RecordFalUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
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
    private array $feTypoScript = [];

    public function __construct(ServerRequestInterface $request)
    {
        $this->feTypoScript = $request->getAttribute('frontend.typoscript')->getSetupArray();
    }

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
                $path = str_getcsv($instructions['dataPath'], '.', '"', '');
                if (count($path) === 1 && isset($flexformData['sDEF'])) {
                    array_unshift($path, 'sDEF');
                }
                if (ArrayUtility::isValidPath($flexformData, $path)) {
                    $processedValue = ArrayUtility::getValueByPath($flexformData, $path);
                }
                break;
            case 'typoscriptObjectPath':
                [$name, $conf] = $this->getVal($instructions['dataPath'], $this->feTypoScript);
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
        //$tsparserObj = $this->getTypoScriptParser();

        $tsStringFactory = $this->getTypoScriptStringFactory();
        /** @var ContentObjectRenderer $cObj */
        $cObj = $this->getContentObjectRenderer($flexformData, $processedValue, $table, $row);
        $restoreData = [];

        // Add parent rec data into the TSFE register
        /** @TODO On every value which gets processed? Not really? */
        $restoreData = $this->registerTypoScriptParentRec($row);

        $tsRootNode = $tsStringFactory->parseFromStringWithIncludes('tvp-A', $theTypoScript);
        $tsComplete = $this->feTypoScript + $tsRootNode->toArray();
        $processedValue = $cObj->cObjGet($tsRootNode->toArray(), 'TemplaVoila_Proc.');
        if (count($restoreData)) {
            /** @TODO On every value which gets processed? Not really? */
            $this->restoreTypoScriptParentRec($restoreData);
        }

        return $processedValue;
    }

    protected function processDataProcessing(array $flexformData, $processedValue, string $table, array $row, string $theTypoScript)
    {
        $tsStringFactory = $this->getTypoScriptStringFactory();
        /** @var ContentObjectRenderer $cObj */
        $cObj = $this->getContentObjectRenderer($flexformData, $processedValue, $table, $row);

        $tsRootNode = $tsStringFactory->parseFromStringWithIncludes('tvp-', $theTypoScript);
        $dataProcessor = GeneralUtility::makeInstance(ContentDataProcessor::class);
        $processedValue = $dataProcessor->process($cObj, ['dataProcessing.' => $tsRootNode->toArray()], $flexformData + $row);

        if (isset($processedValue['_processedValue_'])) {
            return $processedValue['_processedValue_'];
        }

        return [];
    }

    protected function processRepeatable(array $flexformData, string $table, array $row, array $containerInstructions): array
    {
        $postprocessedValue = [];
        foreach ($flexformData as $templateFieldName => $preProcessedValue) {
            $processedValue = $this->processContainer($preProcessedValue, $table, $row, $containerInstructions);
            $postprocessedValue = ArrayUtility::setValueByPath($postprocessedValue, $templateFieldName, $processedValue, '.');
        }
        return $postprocessedValue;
    }

    protected function processContainer(array $flexformData, string $table, array $row, array $containerInstructions): array
    {
        $postprocessedValue = [];
        foreach ($containerInstructions as $templateFieldName => $instructions) {
            $processedValue = $this->valueProcessing($instructions, $flexformData, $table, $row);
            $postprocessedValue = ArrayUtility::setValueByPath($postprocessedValue, $templateFieldName, $processedValue, '.');
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

    protected function getTypoScriptStringFactory(): TypoScriptStringFactory
    {
        return GeneralUtility::makeInstance(TypoScriptStringFactory::class);
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

    /**
     * Taken from TYPO3 v12 TypoScriptParser
     *
     * Get a value/property pair for an object path in TypoScript, eg. "myobject.myvalue.mysubproperty".
     * Here: Used by the "copy" operator, <
     *
     * @param string $string Object path for which to get the value
     * @param array $setup Global setup code if $string points to a global object path. But if string is prefixed with "." then its the local setup array.
     * @return array An array with keys 0/1 being value/property respectively
     */
    public function getVal($string, $setup): array
    {
        $retArr = [
            0 => '',
            1 => [],
        ];
        if ((string)$string === '') {
            return $retArr;
        }

        [$key, $remainingKey] = $this->parseNextKeySegment($string);
        $subKey = $key . '.';
        if ($remainingKey === '') {
            $retArr[0] = $setup[$key] ?? $retArr[0];
            $retArr[1] = $setup[$subKey] ?? $retArr[1];
            return $retArr;
        }
        if (isset($setup[$subKey])) {
            return $this->getVal($remainingKey, $setup[$subKey]);
        }

        return $retArr;
    }

    public function parseNextKeySegment(string $string): array
    {
        $path = str_getcsv($string, '.', '"', '\\');
        $key = array_shift($path);

        return [$key, implode('.', $path)];
    }

}
