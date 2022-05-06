<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Controller\Frontend;

use Tvp\TemplaVoilaPlus\Domain\Model\DataStructure;
use Tvp\TemplaVoilaPlus\Domain\Model\MappingConfiguration;
use Tvp\TemplaVoilaPlus\Domain\Model\TemplateConfiguration;
use Tvp\TemplaVoilaPlus\Service\ApiService;
use Tvp\TemplaVoilaPlus\Service\ConfigurationService;
use Tvp\TemplaVoilaPlus\Utility\ApiHelperUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

class FrontendController extends AbstractPlugin
{
    /**
     * Same as class name
     * @TODO Rename?
     *
     * @var string
     */
    public $prefixId = 'tx_templavoilaplus_pi1';

    /**
     * The extension key.
     *
     * @var string
     */
    public $extKey = 'templavoilaplus';

    /**
     * Main function for rendering of Flexible Content elements of TemplaVoila
     *
     * @param string $content Standard content input. Ignore.
     * @param array $conf TypoScript array for the plugin.
     *
     * @return string HTML content for the Flexible Content elements.
     */
    public function renderPage($content, $conf)
    {
        /** @var ApiService */
        $apiService = GeneralUtility::makeInstance(ApiService::class, 'pages');

        // Current page record which we MIGHT manipulate a little:
        $pageRecord = $GLOBALS['TSFE']->page;
        $originalUid = $pageRecord['uid'];

        // replace record and rootline if content_from_pid is used
        if ($pageRecord['content_from_pid']) {
            $oldMap = $pageRecord['tx_templavoilaplus_map'];
            // we only support direct content_from_pid not chained content_from_pid
            $pageRecord = BackendUtility::getRecordWSOL('pages', $pageRecord['content_from_pid']);

            // restore the old map, as this eases lookup
            // or perhaps we want a specific different map here
            $pageRecord['tx_templavoilaplus_map'] = $oldMap;
        }

        // Find DS and Template in root line IF there is no Data Structure set for the current page:
        if (!$pageRecord['tx_templavoilaplus_map']) {
            $pageRecord['tx_templavoilaplus_map'] = $apiService->getMapIdentifierFromRootline(
                $GLOBALS['TSFE']->rootLine
            );
        }

        if (!$pageRecord['tx_templavoilaplus_map']) {
            $rootLine = [];
            foreach ($GLOBALS['TSFE']->rootLine as $rootLineElement) {
                $rootLine[] = $rootLineElement['uid'];
            }
            throw new \InvalidArgumentException(
                sprintf(
                    'Tried to render page %s%s, but neither this page nor the parents (%s) have an TemplaVoilà! Plus ' .
                    'template set (tx_templavoilaplus_map is empty).',
                    $pageRecord['uid'],
                    $pageRecord['uid'] === $originalUid ? '' : ' (as page ' . $originalUid . ' defined this as content_from_pid)',
                    implode(',', $rootLine)
                ),
                1651146497916
            );
        }

        return $this->renderElement($pageRecord, 'pages');
    }

    public function renderContent($content, $conf)
    {
        return $this->renderElement($this->cObj->data, 'tt_content');
    }

    /**
     * Common function for rendering of the Flexible Content / Page Templates.
     * For Page Templates the input row may be manipulated to contain the proper reference to a data structure (pages can have those inherited which content elements cannot).
     *
     * @param array $row Current data record, either a tt_content element or page record.
     * @param string $table Table name, either "pages" or "tt_content".
     *
     * @throws \RuntimeException|\Exception
     *
     * @return string HTML output.
     */
    public function renderElement($row, $table)
    {
        try {
            $mappingConfiguration = ApiHelperUtility::getMappingConfiguration($row['tx_templavoilaplus_map']);
            // getDS from Mapping
            $dataStructure = ApiHelperUtility::getDataStructure($mappingConfiguration->getCombinedDataStructureIdentifier());

            // getTemplateConfiguration from MappingConfiguration
            $templateConfiguration = ApiHelperUtility::getTemplateConfiguration($mappingConfiguration->getCombinedTemplateConfigurationIdentifier());

            // getDSdata from flexform field with DS
            $flexformData = [];
            if (!empty($row['tx_templavoilaplus_flex'])) {
                $flexformData = GeneralUtility::xml2array($row['tx_templavoilaplus_flex']);
            }
            if (is_string($flexformData)) {
                throw new \Exception('Could not load flex data: "' . $flexformData . '"');
            }
            $flexformValues = $this->getFlexformData($dataStructure, $flexformData);

            // Run TypoScript over DSdata and include TypoScript vars while mapping into TemplateData
            /** @TODO Do we need flexibility here? */
            /** @var \Tvp\TemplaVoilaPlus\Handler\Mapping\DefaultMappingHandler */
            $mappingHandler = GeneralUtility::makeInstance(\Tvp\TemplaVoilaPlus\Handler\Mapping\DefaultMappingHandler::class, $mappingConfiguration);
            $processedValues = $mappingHandler->process($flexformValues, $table, $row);

            // get renderer from templateConfiguration
            /** @var ConfigurationService */
            $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
            $renderHandlerIdentifier = $templateConfiguration->getRenderHandlerIdentifier();
            $renderer = $configurationService->getHandler($renderHandlerIdentifier);

            // Manipulate header data
            // @TODO The renderer? Not realy or?
            $renderer->processHeaderInformation($templateConfiguration);

            // give TemplateData to renderer and return result
            return $renderer->renderTemplate($templateConfiguration, $processedValues, $row);
        } catch (\Exception $e) {
            // Do not break FE rendering
            // @TODO Logging
            return '';
        }
    }

    public function getFlexformData(DataStructure $dataStructure, array $flexformData)
    {
        $flexformValues = [];

        /** @TODO sheet selection */
        $sheet = 'sDEF';

        /** @TODO This is only correct, if there are no sheets defined */
        /** We should look forward to define at minimum the sDEF default sheet */
        $dataStruct = $dataStructure->getDataStructureArray();

        /** @TODO Language selection */
        $lKey = 'lDEF';
        $vKey = 'vDEF';

        $flexformLkeyValues = [];
        if (isset($flexformData['data'][$sheet][$lKey]) && is_array($flexformData['data'][$sheet][$lKey])) {
            $flexformLkeyValues = $flexformData['data'][$sheet][$lKey];
        }

        $flexformValues = $this->processDataValues($flexformLkeyValues, $dataStruct['ROOT']['el'], $vKey);

        return $flexformValues;
    }

    public function processDataValues(array $dataValues, array $DSelements, $valueKey = 'vDEF')
    {
        $processedDataValues = [];

        foreach ($DSelements as $fieldName => $dsConf) {
            if (isset($dsConf['type']) && $dsConf['type'] === 'array' && is_array($dsConf['el'])) {
                if (isset($dsConf['section']) && $dsConf['section'] === '1') {
                    foreach ($dataValues[$fieldName]['el'] as $key => $repeatableValue) {
                        $processedDataValues[$fieldName][$key] = $this->processDataValues($repeatableValue, $dsConf['el'], $valueKey);
                    }
                } else {
                    $processedDataValues[$fieldName] = $this->processDataValues($dataValues[$fieldName]['el'], $dsConf['el'], $valueKey);
                }
            } else {
                $processedDataValues[$fieldName] = $dataValues[$fieldName][$valueKey];
            }
        }

        return $processedDataValues;
    }
}
