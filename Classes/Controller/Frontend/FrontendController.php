<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Controller\Frontend;

use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\DataConfiguration;
use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\MappingConfiguration;
use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\TemplateConfiguration;
use Tvp\TemplaVoilaPlus\Exception\ContentElementWithoutMapException;
use Tvp\TemplaVoilaPlus\Service\ApiService;
use Tvp\TemplaVoilaPlus\Service\ConfigurationService;
use Tvp\TemplaVoilaPlus\Utility\ApiHelperUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\DebugUtility;
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
     * @var Logger
     */
    public $logger;

    public function __construct()
    {
        AbstractPlugin::__construct();
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(self::class);
    }

    /**
     * @param string $content Standard content input. Ignore.
     * @param array $conf TypoScript array for the plugin.
     *
     * @return string HTML content for the Flexible Content elements.
     * @deprecated Starting with v8 you should call renderPage directly via TypoScript
     *             page.10.userFunc = Tvp\TemplaVoilaPlus\Controller\Frontend\FrontendController->renderPage
     */
    // phpcs:disable
    public function main_page($content, $conf)
    {
        // phpcs:enable
        trigger_error(
            'Deprecated TypoScript page userFunc for EXT:templavoilaplus ' .
            '"Ppi\\TemplaVoilaPlus\\Controller\\FrontendController->main_page" was found, ' .
            'please change to "Tvp\TemplaVoilaPlus\Controller\Frontend\FrontendController->renderPage"',
            E_USER_DEPRECATED
        );
        return $this->renderPage($content, $conf);
    }

    /**
     * Main function for rendering of Flexible Content elements of TemplaVoila
     *
     * @param string $content Standard content input. Ignore.
     * @param array $conf TypoScript array for the plugin.
     *
     * @return string HTML content for the Flexible Content elements.
     * @throws \InvalidArgumentException
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

        return $this->renderElement($pageRecord, 'pages', $conf);
    }

    public function renderContent($content, $conf)
    {
        return $this->renderElement($this->cObj->data, 'tt_content', $conf);
    }

    /**
     * Common function for rendering of the Flexible Content / Page Templates.
     * For Page Templates the input row may be manipulated to contain the proper reference to a data structure (pages can have those inherited which content elements cannot).
     *
     * @param array $row Current data record, either a tt_content element or page record.
     * @param string $table Table name, either "pages" or "tt_content".
     *
     * @return string HTML output.
     * @throws ContentElementWithoutMapException
     * @throws \Exception
     *
     */
    public function renderElement($row, $table, array $conf)
    {
        try {
            // pages where checked for empty map already, but not tt_content
            if (!$row['tx_templavoilaplus_map'] && $table == 'tt_content') {
                throw new ContentElementWithoutMapException(
                    sprintf(
                        'Tried to render an element which has no TemplaVoilà! Plus ' .
                        'template set (tx_templavoilaplus_map is empty):' .
                        ' %s:%s (%s)',
                        $table,
                        $row['uid'],
                        "'" . $row['header'] . "'"
                    ),
                    1652213570746
                );
            }
            $mappingConfiguration = ApiHelperUtility::getMappingConfiguration($row['tx_templavoilaplus_map']);

            // First getDS from mapping configuration, there is no child overwrite here
            $dataStructure = ApiHelperUtility::getDataStructure($mappingConfiguration->getCombinedDataStructureIdentifier());

            // getDSdata from flexform field with DS
            $flexformData = [];
            if (!empty($row['tx_templavoilaplus_flex'])) {
                $flexformData = GeneralUtility::xml2array($row['tx_templavoilaplus_flex']);
            }
            if (is_string($flexformData)) {
                throw new \Exception('Could not load flex data: "' . $flexformData . '"');
            }
            $flexformValues = $this->getFlexformData($dataStructure, $flexformData);

            /** @TODO
             * $flexformValues vs. $flexformData? All followup functions use the $flexformData wording
             * Also the function getFlexformData sounds like it would return data not value.
             * Needs to be clarified and renamed/corrected also the getDSdata comment.
             */

            // Second Look for child selection and overload the base mappingConfiguration
            $childsSelection = $this->getChildsSelection($conf, $mappingConfiguration, $flexformValues, $table, $row);
            $mappingConfiguration = ApiHelperUtility::getOverloadedMappingConfiguration($mappingConfiguration, $childsSelection);

            // getTemplateConfiguration from MappingConfiguration
            $templateConfiguration = ApiHelperUtility::getTemplateConfiguration($mappingConfiguration->getCombinedTemplateConfigurationIdentifier());

            // Run TypoScript over DSdata and include TypoScript vars while mapping into TemplateData
            /** @TODO Do we need flexibility here? */
            /** @var \Tvp\TemplaVoilaPlus\Handler\Mapping\DefaultMappingHandler */
            $mappingHandler = GeneralUtility::makeInstance(\Tvp\TemplaVoilaPlus\Handler\Mapping\DefaultMappingHandler::class);
            $processedValues = $mappingHandler->process($mappingConfiguration, $flexformValues, $table, $row);

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
            // only log if $table is tt_content and exception is for tt_content, because elso it will be logged twice
            if ($e instanceof ContentElementWithoutMapException && $table == 'tt_content') {
                $this->logger->warning(
                    'Tried to render element {table}:{uid} on page {pid}, but something went wrong: #{exceptionCode}: {exceptionMessage}.',
                    [
                        'table' => $table,
                        'uid' => $row['uid'],
                        'pid' => $GLOBALS['TSFE']->id,
                        'exceptionCode' => $e->getCode(),
                        'exceptionMessage' => $e->getMessage()
                    ]
                );
            }
            if ($this->checkFeDebugging()) {
                // if FE has debugging enabled throw the exception
                throw $e;
            }
            // if FE has debugging disabled return nothing for the element to not break output.
            return '';
        }
    }

    public function checkFeDebugging(): bool
    {
        if (
            (isset($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']) && $GLOBALS['TYPO3_CONF_VARS']['FE']['debug'])
            || (isset($GLOBALS['TSFE']->tmpl->setup['config.']['debug']) && $GLOBALS['TSFE']->tmpl->setup['config.']['debug'])
        ) {
            return true;
        }
        return false;
    }

    public function getFlexformData(DataConfiguration $dataStructure, array $flexformData)
    {
        $flexformValues = [];

        /** @TODO sheet selection */
        $sheet = 'sDEF';

        /** @TODO This is only correct, if there are no sheets defined */
        /** We should look forward to define at minimum the sDEF default sheet */
        $dataStruct = $dataStructure->getDataStructure();

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

    public function processDataValues(array $dataValues, array $DSelements, $valueKey = 'vDEF'): array
    {
        $processedDataValues = [];

        foreach ($DSelements as $fieldName => $dsConf) {
            if (isset($dsConf['type']) && $dsConf['type'] === 'array' && is_array($dsConf['el'])) {
                if (isset($dsConf['section']) && $dsConf['section'] === '1') {
                    if (isset($dataValues[$fieldName]['el']) && is_array($dataValues[$fieldName]['el'])) {
                        foreach ($dataValues[$fieldName]['el'] as $key => $repeatableValue) {
                            $processedDataValues[$fieldName][$key] = $this->processDataValues($repeatableValue, $dsConf['el'], $valueKey);
                        }
                    } else {
                        $processedDataValues[$fieldName][$key] = [];
                    }
                } else {
                    $processedDataValues[$fieldName] = $this->processDataValues($dataValues[$fieldName]['el'], $dsConf['el'], $valueKey);
                }
            } else {
                $processedDataValues[$fieldName] = $dataValues[$fieldName][$valueKey] ?? '';
            }
        }

        return $processedDataValues;
    }

    private function getChildsSelection(array $tsConf, MappingConfiguration $mappingConfiguration, array $flexformData, string $table, array $row): array
    {
        $childSelection = [];

        if ($tsConf['childTemplate'] ?? null) {
            $renderType = $tsConf['childTemplate'];
            if (substr($renderType, 0, 9) === 'USERFUNC:') {
                $conf = [
                    'conf' => is_array($tsConf['childTemplate.']) ? $tsConf['childTemplate.'] : [],
                    'toRecord' => $row,
                ];
                $renderType = GeneralUtility::callUserFunction(substr($renderType, 9), $conf, $this);
            }
            $childSelection[] = $renderType;
        }
        if (GeneralUtility::_GP('print')) {
            $childSelection[] = 'print';
        }

        $childSelectors = $mappingConfiguration->getChildSelectors();
        if (!empty($childSelectors)) {
            /** @var \Tvp\TemplaVoilaPlus\Handler\Mapping\DefaultMappingHandler */
            $mappingHandler = GeneralUtility::makeInstance(\Tvp\TemplaVoilaPlus\Handler\Mapping\DefaultMappingHandler::class);

            foreach ($childSelectors as $childSelectorConfig) {
                $childSelectionValue = $mappingHandler->valueProcessing($childSelectorConfig, $flexformData, $table, $row);
                if (!empty($childSelectionValue)) {
                    $childSelection[] = $childSelectionValue;
                }
            }
        }


        return $childSelection;
    }
}
