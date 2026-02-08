<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Service;

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
use Tvp\TemplaVoilaPlus\Exception\ProcessingException;
use Tvp\TemplaVoilaPlus\Domain\Repository\Localization\LocalizationRepository;
use Tvp\TemplaVoilaPlus\Exception\ConfigurationException;
use Tvp\TemplaVoilaPlus\Exception\InvalidIdentifierException;
use Tvp\TemplaVoilaPlus\Exception\MissingPlacesException;
use Tvp\TemplaVoilaPlus\Utility\ApiHelperUtility;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException as CoreInvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * ONLY FOR TEMPORARY USE
 * NO API!
 */
class ProcessingService
{
    /** @var FlexFormTools */
    protected $flexFormTools;

    /** @TODO: previously undefined members, needed for 8.1 compat */
    protected $rootTable;
    protected $modifyReferencesInLiveWS;

    public function __construct()
    {
        $this->flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
    }

    /**
     * Returns the content tree (based on the data structure) for a certain page or a flexible content element. In case of a page it will contain all the references
     * to content elements (and some more information) and in case of a FCE, references to its sub-elements.
     *
     * @param string $table Table which contains the (XML) data structure. Only records from table 'pages' or flexible content elements from 'tt_content' are handled
     * @param array $row Record of the root element where the tree starts (Possibly overlaid with workspace content)
     * @param array $parentPointer @TODO Move this in a model?
     * @param int $basePid The uid of the page this node should belong
     *
     * @return array The content tree
     */
    public function getNodeWithTree(string $table, array $row, array $parentPointer = [], int $basePid = 0, array &$usedElements = []): array
    {
        if ($basePid === 0) {
            if ($table === 'pages') {
                $basePid = (int)$row['uid'];
            } else {
                $basePid = (int)$row['pid'];
            }
        }

        /** @TODO The parentPointer is not a pointer to owns parent it is more a pointer to themself with parent relation */
        if (empty($parentPointer)) {
            $parentPointer = [
                'table' => $table,
                'uid' => $row['uid'],
            ];
        }

        $node = $this->getNodeFromRow($table, $row, $parentPointer, $basePid, $usedElements);
        $node['datastructure'] = $this->getDatastructureForNode($node);
        $node['flexform'] = $this->getFlexformForNode($node);

        // $langChildren = (int)$tree['ds_meta']['langChildren'];
        // $langDisable = (int)$tree['ds_meta']['langDisable'];

        // Load sheet informations

        $node['localization'] = $this->getLocalizationForNode($node);

        $node['localizationActions'] = $this->getLocalizationActionsForMissingLocalizations($node, $basePid);

        // Get node childs:
        $node['childNodes'] = $this->getNodeChilds($node, $basePid, $usedElements);

        // Return result:
        return [
            'node' => $node,
            'usedElements' => $usedElements
        ];
    }

    /**
     * Returns the node (probably page) and its localization information or localizationActions.
     *
     * @param string $table Table which contains the (XML) data structure. Only records from table 'pages' or flexible content elements from 'tt_content' are handled
     * @param array $row Record of the root element where the tree starts (Possibly overlaid with workspace content)
     *
     * @return array The content tree
     */
    public function getNodeWithLocalization(string $table, array $row): array
    {
        $basePid = (int)$row['uid'];
        $parentPointer = [
            'table' => $table,
            'uid' => $row['uid'],
        ];

        $node = $this->getNodeFromRow($table, $row, $parentPointer, $basePid);
        $node['localization'] = $this->getLocalizationForNode($node);
        $node['localizationActions'] = $this->getLocalizationActionsForMissingLocalizations($node, $basePid);

        // Return result:
        return $node;
    }

    public function getUnusedElements(array $pageRow, array $usedElements): array
    {
        $table = 'tt_content';
        $l10n_parent_field = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? 'l10n_parent';

        if (isset($usedElements[$table]) && is_array($usedElements[$table])) {
            // Get all page elements not in usedElements
            $usedUids = array_keys($usedElements[$table]);
        } else {
            $usedUids = [];
        }

        /** @TODO Move into Repository? */
        /** @var QueryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        // set table and where clause
        // as we want unused elements here instead of a complex case discussion which language mode it is, just filter
        // to standalone elements, defined as "has no lang parent". Thus, it would show -1=all_lang as well as default
        // lang or free-translation mode elements as only those can be used directly.
        $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter((int)$pageRow['uid'], Connection::PARAM_INT)),
                $queryBuilder->expr()->eq($l10n_parent_field, $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            );
        if (!empty($usedUids)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->notIn('uid', $usedUids)
            );
        }

        $row = $queryBuilder->executeQuery()->fetchAllAssociative();

        return $row;
    }

    public function getNodeFromRow(string $table, array $row, array $parentPointer = [], int $basePid = 0, array &$usedElements = [])
    {
        $title = BackendUtility::getRecordTitle($table, $row);

        $onPid = ($table === 'pages' ? (int)$row['uid'] : (int)$row['pid']);
        $parentPointerString = $this->getParentPointerAsString($parentPointer);
        $combinedBackendLayoutConfigurationIdentifier = '';
        $backendLayoutConfiguration = null;

        $mappingConfiguration = $this->getMappingConfiguration($table, $row);
        if ($mappingConfiguration) {
            $combinedBackendLayoutConfigurationIdentifier = $mappingConfiguration->getCombinedBackendLayoutConfigurationIdentifier();
            if ($combinedBackendLayoutConfigurationIdentifier !== '') {
                $backendLayoutConfiguration = ApiHelperUtility::getBackendLayoutConfiguration($combinedBackendLayoutConfigurationIdentifier);
            }
        }

        if (isset($usedElements[$table][$row['uid']])) {
            $usedElements[$table][$row['uid']]['count']++;
        } else {
            $usedElements[$table][$row['uid']]['count'] = 1;
        }
        $usedElements[$table][$row['uid']]['parentPointers'][] = $parentPointerString;

        if (isset($parentPointer['table']) && $parentPointer['table'] === 'pages') {
            $pageTsConfig = BackendUtility::getPagesTSconfig($parentPointer['uid']);
            $additionalRecordDataColumns = $pageTsConfig['mod.']['web_txtemplavoilaplusLayout.']['additionalRecordData.'][$table] ?? null;
            if ($additionalRecordDataColumns) {
                $additionalRecordData = ' ';
                foreach (explode(',', $additionalRecordDataColumns) as $additionalRecordDataColumn) {
                    $additionalRecordData .= 'data-' . strtolower($additionalRecordDataColumn) . '="' . ($row[$additionalRecordDataColumn] ?? '') . '" ';
                }
            }
        }

        $node = [
            'raw' => [
                'entity' => $row,
                'table' => $table,
            ],
            'rendering' => [
                'shortTitle' => GeneralUtility::fixed_lgd_cs($title, 50),
                'fullTitle' => $title,
                'hintTitle' => BackendUtility::getRecordIconAltText($row, $table),
                'description' => ($row[$GLOBALS['TCA'][$table]['ctrl']['descriptionColumn'] ?? null] ?? ''),
                'belongsToCurrentPage' => ($basePid === $onPid),
                'countUsedOnPage' => $usedElements[$table][$row['uid']]['count'],
                'errorNoMapping' => ($table === 'tt_content' && $row['CType'] === 'templavoilaplus_pi1' && !$row['tx_templavoilaplus_map']),
                'parentPointer' => $parentPointerString,
                'beLayout' => $combinedBackendLayoutConfigurationIdentifier,
                'beLayoutDesign' => ($backendLayoutConfiguration ? $backendLayoutConfiguration->isDesign() : false),
                'md5' => md5($parentPointerString . '/' . $table . ':' . $row['uid']),
                'additionalRecordData' => $additionalRecordData ?? ''
            ],
        ];

        return $node;
    }

    public function getMappingConfiguration(string $table, array $row): ?MappingConfiguration
    {
        $map = ($row['tx_templavoilaplus_map'] ?? null);
        $mappingConfiguration = null;

        // find mappingConfiguration in root line if current page doesn't have one
        if (!$map && $table === 'pages') {
            $apiService = GeneralUtility::makeInstance(ApiService::class, 'pages');
            $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $row['uid'])->get();
            $map = $apiService->getMapIdentifierFromRootline($rootLine);
        }
        if ($map) {
            try {
                $mappingConfiguration = ApiHelperUtility::getMappingConfiguration($map);
            } catch (ConfigurationException | MissingPlacesException | InvalidIdentifierException | \TypeError $e) {
                $mappingConfiguration = null;
            }
        }
        return $mappingConfiguration;
    }

    public function getDatastructureForNode(array $node): array
    {
        $table = $node['raw']['table'];
        $row = $node['raw']['entity'];

        $rawDataStructure = [];

        /** @TODO At the moment, concentrating only on this parts, but more could be possible */
        if ($table === 'pages' || $table === $this->rootTable || ($table === 'tt_content' && $row['CType'] === 'templavoilaplus_pi1')) {
            $dataStructureIdentifier = $this->flexFormTools->getDataStructureIdentifier(
                $GLOBALS['TCA'][$table]['columns']['tx_templavoilaplus_flex'],
                $table,
                'tx_templavoilaplus_flex',
                $row
            );

            /** @TODO Runtime Cache? */
            try {
                $rawDataStructure = $this->flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);
            } catch (CoreInvalidIdentifierException $e) {
                $rawDataStructure = ['error' => $e->getMessage()];
            } catch (\RuntimeException $e) {
                $rawDataStructure = ['error' => $e->getMessage()];
            }

            $rawDataStructure['identifier'] = $dataStructureIdentifier;
        }

        return $rawDataStructure;
    }

    public function getDatastructureForPointer(array $pointer): array
    {
        return $this->getDatastructureForNode([
            'raw' => [
                'table' => $pointer['table'],
                'entity' => $pointer['foundRecord'],
            ]
        ]);
    }

    public function getFlexformForNode(array $node): array
    {
        $emptyFlexform = $this->getEmptyFlexformForNode($node);
        if (($node['raw']['entity']['tx_templavoilaplus_flex'] ?? null) === null) {
            return $emptyFlexform;
        }

        $flexform = GeneralUtility::xml2array($node['raw']['entity']['tx_templavoilaplus_flex']);
        if (!is_array($flexform)) {
            return $emptyFlexform;
        }

        $flexform = array_replace_recursive($emptyFlexform, $flexform);

        return $flexform;
    }

    public function getEmptyFlexformForNode(array $node): array
    {
        /** @TODO We need this dynamically */
        $lKeys = ['lDEF'];
        $vKeys = ['vDEF'];

        $emptyFlexform = ['data' => []];

        if (
            isset($node['datastructure']['sheets'])
            && is_array($node['datastructure']['sheets'])
        ) {
            foreach ($node['datastructure']['sheets'] as $sheetKey => $sheetData) {
                foreach ($lKeys as $lKey) {
                    foreach ($sheetData['ROOT']['el'] as $fieldKey => $fieldConfig) {
                        foreach ($vKeys as $vKey) {
                            // Sections and repeatables shouldn't be deep filled
                            if (isset($fieldConfig['type']) && $fieldConfig['type'] === 'array') {
                                $emptyFlexform['data'][$sheetKey][$lKey][$fieldKey][$vKey] = [];
                            } else {
                                $emptyFlexform['data'][$sheetKey][$lKey][$fieldKey][$vKey] = '';
                            }
                        }
                    }
                }
            }
        }

        return $emptyFlexform;
    }

    public function getLocalizationForNode(array $node): array
    {
        $localization = [];
        $table = $node['raw']['table'];
        $row = $node['raw']['entity'];

        $tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];

        $records = LocalizationRepository::fetchRecordLocalizations($table, (int)$row['uid']);
        /** @TODO WSOL? */
        foreach ($records as $record) {
            $localization[$record[$tcaCtrl['languageField']]] = $this->getNodeFromRow($table, $record);
        }

        return $localization;
    }

    public function getLocalizationActionsForMissingLocalizations(array $node, int $pid): array
    {
        $localizationActions = [];
        $existingLocalizations = array_keys($node['localization']);
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $availableLanguages = TemplaVoilaUtility::getAvailableLanguages($pid);
        $availableLanguageKeys = array_keys($availableLanguages);
        foreach ($availableLanguageKeys as $languageId) {
            if ($languageId > 0 && !in_array($languageId, $existingLocalizations)) {
                $params = '&cmd[' . $node['raw']['table'] . '][' . $node['raw']['entity']['uid'] . '][localize]=' . $languageId;
                $localizationActions[$languageId]['actionUrl'] = (string)$uriBuilder->buildUriFromRoute(
                    'tce_db',
                    [
                        'cmd' => [
                            $node['raw']['table'] => [
                                $node['raw']['entity']['uid'] => [
                                    'localize' => $languageId,
                                ],
                            ],
                        ],
                        'redirect' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
                    ]
                );
            }
        }
        return $localizationActions;
    }

    public function getNodeChilds(array $node, int $basePid, array &$usedElements): array
    {
        $childs = [];
        /** @TODO We need this dynamically */
        $lKeys = ['lDEF'];

        if (
            !isset($node['datastructure']['sheets'])
            || !is_array($node['datastructure']['sheets'])
            || !isset($node['flexform']['data'])
        ) {
            return $childs;
        }
        // Traverse each sheet in the FlexForm Structure:
        foreach ($node['datastructure']['sheets'] as $sheetKey => $sheetData) {
            // Traverse the sheet's elements:
            if (is_array($sheetData) && is_array($sheetData['ROOT']['el'])) {
                foreach ($lKeys as $lKey) {
                    if (!isset($node['flexform']['data'][$sheetKey][$lKey]) || !is_array($node['flexform']['data'][$sheetKey][$lKey])) {
                        $node['flexform']['data'][$sheetKey][$lKey] = [];
                    }
                    $childs[$sheetKey][$lKey] = $this->getNodeChildsFromElements($node, $sheetKey, '', $sheetData['ROOT']['el'], $lKey, $node['flexform']['data'][$sheetKey][$lKey], $basePid, $usedElements);
                }
            }
        }

        return $childs;
    }

    protected function getNodeChildsFromElements(array $baseNode, string $baseSheetKey, string $baseFieldKey, array $elements, string $lKey, array $values, int $basePid, array &$usedElements): array
    {
        $childs = [];
        /** @TODO We need this dynamically */
        $vKeys = ['vDEF'];

        foreach ($elements as $fieldKey => $fieldConfig) {
            $fieldComleteKey = $baseFieldKey . '#' . $fieldKey;
            if (($fieldConfig['type'] ?? '') === 'array') {
                if ($fieldConfig['section'] ?? '' === 1) {
                    if (is_array($values[$fieldKey]['el'] ?? null)) {
                        foreach ($values[$fieldKey]['el'] as $key => $fieldValue) {
                            $childs[$fieldKey][$key] = $this->getNodeChildsFromElements($baseNode, $baseSheetKey, $fieldComleteKey . '#el#' . $key, $fieldConfig['el'], $lKey, $fieldValue, $basePid, $usedElements);
                        }
                    }
                } else {
                    $childs[$fieldKey] = $this->getNodeChildsFromElements($baseNode, $baseSheetKey, $fieldComleteKey . '#el', $fieldConfig['el'], $lKey, $values[$fieldKey]['el'], $basePid, $usedElements);
                }
            } else {
                // If the current field points to another table
                if (
                    isset($fieldConfig['config']['type'])
                ) {
                    switch ($fieldConfig['config']['type']) {
                        case 'group':
                            /** @TODO allowed can be multiple tables */
                            $table = $fieldConfig['config']['allowed'];
                            foreach ($vKeys as $vKey) {
                                $listOfSubElementUids = ($values[$fieldKey][$vKey] ?? null);
                                if ($listOfSubElementUids) {
                                    $parentPointer = $this->createParentPointer($baseNode, $baseSheetKey, $fieldComleteKey, $lKey, $vKey);
                                    $childs[$fieldKey][$vKey] = $this->getNodesFromListWithTree($listOfSubElementUids, $parentPointer, $basePid, $table, $usedElements);
                                } else {
                                    $childs[$fieldKey][$vKey] = [];
                                }
                            }
                            break;
                        case 'file':
                            $listOfSubElementUids = (string) $baseNode['raw']['entity']['uid'];
                            $parentPointer = $this->createParentPointer($baseNode, $baseSheetKey, $fieldComleteKey, $lKey, 'vDEF');
                            $childs[$fieldKey]['vDEF'] = $this->getNodesFromListWithTree('', $parentPointer, 0, 'sys_file_reference', $usedElements, '', $baseNode['raw']['entity']['uid'], $baseNode['raw']['table'], $fieldConfig['config']);
                            break;
                        default:
                            // Empty as we have no default extra processing
                    }
                }
            }
            /** @TODO What does this do?
            elseif ($fieldConfig['type'] !== 'array' && $fieldConfig['TCEforms']['config']) {
                // If generally there are non-container fields, register them:
                $childs['contentFields'][$sheetKey][$fieldKey] = $fieldKey;
            }
            */
        }

        return $childs;
    }

    public function findRecordsSourcePointer(array $row): string
    {
        $pageRow = BackendUtility::getRecordWSOL('pages', $row['pid']);
        $parentPointer = [];
        $usedElements = [];
        $baseNode = $this->getNodeWithTree('pages', $pageRow, $parentPointer, (int) $row['pid'], $usedElements);

        if (isset($usedElements['tt_content'][$row['uid']])) {
            return $usedElements['tt_content'][$row['uid']]['parentPointers'][0];
        }

        return 'tt_content:' . $row['uid'];
    }

    public function getNodesFromListWithTree(string $listOfNodes, array $parentPointer, int $basePid, string $table, array &$usedElements, $MMtable = '', $MMuid = 0, string $currentTable = '', array $config = []): array
    {
        $nodes = [];

        // Get records:
        /** @var RelationHandler $dbAnalysis */
        $dbAnalysis = GeneralUtility::makeInstance(RelationHandler::class);

        $dbAnalysis->start($listOfNodes, $table, $MMtable, $MMuid, $currentTable, $config);

        // Traverse records:
        // Note: key in $dbAnalysis->itemArray is not a valid counter! It is in 'tt_content_xx' format!
        $counter = 1;
        foreach ($dbAnalysis->itemArray as $position => $recIdent) {
            $contentRow = BackendUtility::getRecordWSOL($table, $recIdent['id']);
            $parentPointer['position'] = $position;

            // Only do it if the element referenced was not deleted! - or hidden :-)
            if (is_array($contentRow)) {
                $nodes[] = $this->getNodeWithTree($table, $contentRow, $parentPointer, $basePid, $usedElements);
            }
        }

        return $nodes;
    }

    /**
     * Creates a new content element record and sets the necessary references to connect it to the parent element.
     * @TODO Also for non tt_content elements?
     *
     * @param string $destinationPointerString Flexform pointer defining the parent location of the new element. Position refers to the element _after_ which the new element should be inserted. Position == 0 means before the first element.
     * @param array $elementRow Array of field keys and values for the new content element record
     * @return mixed The UID of the newly created record or FALSE if operation was not successful
     * @throws ProcessingException
     */
    public function insertElement(string $destinationPointerString, array $elementRow)
    {
        // Check and get all information about the destination position:
        $destinationPointer = $this->getValidPointer($destinationPointerString, true);
        if (!$destinationPointer) {
            return false;
        }

        // Create record
        $destinationRecord = $destinationPointer['foundRecord'];
        $newRecordPid = ($destinationPointer['table'] == 'pages' ? ($destinationRecord['pid'] == -1 ? $destinationRecord['t3ver_oid'] : $destinationRecord['uid']) : $destinationRecord['pid']);

        $dataArr = [];
        $dataArr['tt_content']['NEW'] = $elementRow;
        $dataArr['tt_content']['NEW']['pid'] = $newRecordPid;
        unset($dataArr['tt_content']['NEW']['uid']);

        /** @var DataHandler */
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $tce->start($dataArr, []);
        $tce->process_datamap();
        $elementUid = $tce->substNEWwithIDs['NEW'];

        if (count($tce->errorLog)) {
            throw new ProcessingException('Could not insert element: ' . $tce->errorLog[0], 1679526931767);
        }

        if (!$elementUid) {
            throw new ProcessingException('Could not insert element.', 1679526931768);
        }

        // insert record into destination
        $newReferences = $this->insertElementReferenceIntoList($destinationPointer['foundFieldReferences']['references'], $destinationPointer['position'], $elementUid);
        $this->storeElementReferencesListInRecord($newReferences, $destinationPointer);

        return $elementUid;
    }

    /**
     * Moves an element specified by the source pointer to the location specified by destination pointer.
     *
     * @TODO Only pointers to TCEform of type groups allowed, move inside sections should also be done
     *
     * @param string $sourcePointerString flexform pointer pointing to the element which shall be moved
     * @param string $destinationPointerString flexform pointer to the new location
     *
     * @return boolean TRUE if operation was successfully, otherwise false
     * @throws ProcessingException
     */
    public function moveElement(string $sourcePointerString, string $destinationPointerString): bool
    {
        try {
            // Check and get all information about the source position:
            $sourcePointer = $this->getValidPointer($sourcePointerString);
            // Check and get all information about the destination position:
            $destinationPointer = $this->getValidPointer($destinationPointerString, true);
        } catch (\Exception $e) {
            throw new ProcessingException(
                sprintf('Error moving elements: %s', $e->getMessage()),
                1666475603708
            );
        }
        if (!$sourcePointer) {
            throw new ProcessingException(
                sprintf('Error moving elements: sourcePointer %s not valid.', $sourcePointerString),
                1666475603708
            );
        }
        if (!$destinationPointer) {
            throw new ProcessingException(
                sprintf('Error moving elements: destinationPointer %s not valid.', $destinationPointerString),
                1666475603709
            );
        }
        // Destination can't be pure table, needs to be a pointer field
        if (!isset($destinationPointer['position'])) {
            return false;
        }

        // Points to an non used element, it is more like add reference and move pid
        if (!isset($sourcePointer['position'])) {
            $elementTable = $sourcePointer['table'];
            $elementUid = (int) $sourcePointer['uid'];
        } else {
            $elementTable = $sourcePointer['foundFieldReferences']['referenceTable'];
            $elementUid = (int) $sourcePointer['foundFieldReferences']['references'][$sourcePointer['position']];
        }

        if ($elementTable !== $destinationPointer['foundFieldReferences']['referenceTable']) {
            // Can't move an element from one to another table type
            return false;
        }

        $elementsAreWithinTheSameParentElement = (
            $sourcePointer['table'] == $destinationPointer['table'] &&
            $sourcePointer['uid'] == $destinationPointer['uid']
        );


        // Move the element within the same parent element:
        if ($elementsAreWithinTheSameParentElement) {
            $elementsAreWithinTheSameParentField = (
                $sourcePointer['sheet'] == $destinationPointer['sheet'] &&
                $sourcePointer['sLang'] == $destinationPointer['sLang'] &&
                $sourcePointer['field'] == $destinationPointer['field'] &&
                $sourcePointer['vLang'] == $destinationPointer['vLang']
            );
            if ($elementsAreWithinTheSameParentField) {
                $newPosition = $destinationPointer['position'];
                $newReferences = $this->removeElementReferenceFromList($sourcePointer['foundFieldReferences']['references'], $sourcePointer['position']);
                $newReferences = $this->insertElementReferenceIntoList($newReferences, $newPosition, $elementUid);
                $this->storeElementReferencesListInRecord($newReferences, $destinationPointer);
            } else {
                $newReferences = $this->removeElementReferenceFromList($sourcePointer['foundFieldReferences']['references'], $sourcePointer['position']);
                $this->storeElementReferencesListInRecord($newReferences, $sourcePointer);
                $newReferences = $this->insertElementReferenceIntoList($destinationPointer['foundFieldReferences']['references'], $destinationPointer['position'], $elementUid);
                $this->storeElementReferencesListInRecord($newReferences, $destinationPointer);
            }
        } else {
            // Move the element to a different parent element:
            if (isset($sourcePointer['foundFieldReferences']['references'])) {
                // Unlink on source only if field reference
                $newReferences = $this->removeElementReferenceFromList($sourcePointer['foundFieldReferences']['references'], $sourcePointer['position']);
                $this->storeElementReferencesListInRecord($newReferences, $sourcePointer);
            }
            $newReferences = $this->insertElementReferenceIntoList($destinationPointer['foundFieldReferences']['references'], $destinationPointer['position'], $elementUid);
            $this->storeElementReferencesListInRecord($newReferences, $destinationPointer);

            // Update pid if we move tt_content over pages
            if ($elementTable === 'tt_content') {
                $sourcePid = (int) ($sourcePointer['table'] == 'pages' ? $sourcePointer['foundRecord']['uid'] : $sourcePointer['foundRecord']['pid']);
                $destinationPid = (int) ($destinationPointer['table'] == 'pages' ? $destinationPointer['foundRecord']['uid'] : $destinationPointer['foundRecord']['pid']);

                if ($sourcePid !== $destinationPid) {
                    $cmdArray = [];
                    $cmdArray['tt_content'][$elementUid]['move'] = $destinationPid;

                    // Move childs if there any
                    $parentPointer = [];
                    $usedElements = [];
                    // Need complete row here
                    $row = BackendUtility::getRecordWSOL($sourcePointer['table'], $sourcePointer['foundRecord']['uid']);
                    $baseNode = $this->getNodeWithTree($sourcePointer['table'], $row, $parentPointer, $sourcePid, $usedElements);
                    foreach ($usedElements['tt_content'] as $uid => $_unused) {
                        $cmdArray['tt_content'][$uid]['move'] = $destinationPid;
                    }

                    $backup = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoilaplus_api']['apiIsRunningTCEmain'] ?? false;
                    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoilaplus_api']['apiIsRunningTCEmain'] = true;
                    $tce = GeneralUtility::makeInstance(DataHandler::class);
                    $tce->start([], $cmdArray);
                    $tce->process_cmdmap();
                    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoilaplus_api']['apiIsRunningTCEmain'] = $backup;
                }
            }
        }

        return true;
    }

    /**
     * Removes a reference to the element (= unlinks) specified by the source pointer AND deletes the
     * record.
     *
     * @param string $sourcePointerString flexform pointer pointing to the element which shall be deleted
     * @return boolean TRUE if operation was successfully, otherwise false
     */
    public function deleteElement(string $sourcePointerString): bool
    {
        // Check and get all information about the source position:
        $sourcePointer = $this->getValidPointer($sourcePointerString);
        if (!$sourcePointer) {
            return false;
        }

        // Unlink
        $newReferences = $this->removeElementReferenceFromList($sourcePointer['foundFieldReferences']['references'], $sourcePointer['position']);
        $this->storeElementReferencesListInRecord($newReferences, $sourcePointer);

        // Delete
        $cmdArray = [];
        $cmdArray['tt_content'][$sourcePointer['foundRecord']['uid']]['delete'] = 1;
        // Element UID should always be that of the online version here...

        /** @var DataHandler */
        $tce = GeneralUtility::makeInstance(DataHandler::class);

        $tce->start([], $cmdArray);
        $tce->process_cmdmap();

        return true;
    }

    /**
     * Copy an element into given location
     *
     * @param string $destinationPointerString flexform pointer to the new location
     * @param string $table The table from which we copy, should be tt_content!
     * @param int $sourceElementUid The elements uid which should be copied
     * @return mixed The UID of the newly created record or FALSE if operation was not successful
     * @throws ProcessingException
     */
    public function copyElement(string $destinationPointerString, string $sourceElementTable, int $sourceElementUid, bool $neverHideAtCopy = false)
    {
        // Check and get all information about the source position:
        $destinationPointer = $this->getValidPointer($destinationPointerString, true);
        if (!$destinationPointer) {
            throw new ProcessingException('Copy action has missing or invalid destinationPointer:' . $destinationPointerString);
        }
        // Only tt_content yet
        if ($sourceElementTable !== 'tt_content') {
            throw new ProcessingException('Copy action only implemented for content elements');
        }

        $destinationRecord = $destinationPointer['foundRecord'];
        $newRecordPid = ($destinationPointer['table'] == 'pages' ? ($destinationRecord['pid'] == -1 ? $destinationRecord['t3ver_oid'] : $destinationRecord['uid']) : $destinationRecord['pid']);

        // Copy
        $cmdArray = [];
        $cmdArray[$sourceElementTable][$sourceElementUid]['copy'] = $newRecordPid;

        /** @var DataHandler */
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $tce->neverHideAtCopy = $neverHideAtCopy;
        $tce->start([], $cmdArray);
        $tce->process_cmdmap();

        $newElementUid = $tce->copyMappingArray_merged[$sourceElementTable][$sourceElementUid];

        // Insert new uid into reference
        $newReferences = $this->insertElementReferenceIntoList($destinationPointer['foundFieldReferences']['references'], $destinationPointer['position'], $newElementUid);
        $this->storeElementReferencesListInRecord($newReferences, $destinationPointer);

        return $newElementUid;
    }

    public function makeLocalCopy(string $sourcePointerString, int $targetPid)
    {
        // Check and get all information about the source position:
        $sourcePointer = $this->getValidPointer($sourcePointerString);

        if (!$sourcePointer) {
            throw new ProcessingException(
                sprintf('Error make local copy: sourcePointer %s not valid.', $sourcePointerString),
                1666475603708
            );
        }
        if (!isset($sourcePointer['foundFieldReferences'])) {
            throw new ProcessingException(
                sprintf('Error make local copy: sourcePointer %s is themself inside a reference.', $sourcePointerString),
                1666475603708
            );
        }
        if ($sourcePointer['foundRecord']['pid'] !== $targetPid) {
            throw new ProcessingException(
                sprintf('Possible parent isn\'t on target page (%s), maybe itself is a reference.', $targetPid),
                1666475603708
            );
        }

        $sourceElementTable = $sourcePointer['foundFieldReferences']['referenceTable'];
        $sourceElementUid = $sourcePointer['foundFieldReferences']['references'][$sourcePointer['position']];

        $newElementUid = $this->copyElement($sourcePointerString, $sourceElementTable, $sourceElementUid, true);

        // pointer to the element which should be unlinked is now one position higher
        $sourcePointer['position'] += 1;
        $sourcePointerString = $this->getParentPointerAsString($sourcePointer);
        $this->unlinkElement($sourcePointerString);

        return $newElementUid;
    }

    /**
     * Reference an element into given location
     *
     * @param string $destinationPointerString flexform pointer to the new location
     * @param string $table The table from which we copy, should be tt_content!
     * @param int $sourceElementUid The elements uid which should be copied
     * @return mixed The UID of the newly created record or FALSE if operation was not successful
     */
    public function referenceElement(string $destinationPointerString, string $sourceElementTable, int $sourceElementUid)
    {
        // Check and get all information about the source position:
        $destinationPointer = $this->getValidPointer($destinationPointerString, true);
        if (!$destinationPointer) {
            return false;
        }
        // Only tt_content yet
        if ($sourceElementTable !== 'tt_content') {
            return false;
        }

        // Insert new uid into reference
        $newReferences = $this->insertElementReferenceIntoList($destinationPointer['foundFieldReferences']['references'], $destinationPointer['position'], $sourceElementUid);
        $this->storeElementReferencesListInRecord($newReferences, $destinationPointer);

        return $sourceElementUid;
    }

    /**
     * Removes a reference to the element (= unlinks) specified by the source pointer.
     *
     * @param string $sourcePointerString flexform pointer pointing to the reference which shall be removed
     * @return boolean TRUE if operation was successfully, otherwise false
     */
    public function unlinkElement(string $sourcePointerString): bool
    {

        // Check and get all information about the source position:
        $sourcePointer = $this->getValidPointer($sourcePointerString);
        if (!$sourcePointer) {
            return false;
        }

        // Unlink
        $newReferences = $this->removeElementReferenceFromList($sourcePointer['foundFieldReferences']['references'], $sourcePointer['position']);
        $this->storeElementReferencesListInRecord($newReferences, $sourcePointer);

        return true;
    }

    /**
     * Creates a new reference list (as an array) with the $elementUid inserted into the given reference list
     *
     * @param array $currentReferencesArr Array of tt_content uids from a current reference list
     * @param int $position Position where the new reference should be inserted: 0 = before the first element, 1 = after the first, 2 = after the second etc., -1 = insert as last element
     * @param int $elementUid UID of a tt_content element
     *
     * @return array Array with an updated reference list
     */
    public function insertElementReferenceIntoList(array $currentReferencesArr, int $position, int $elementUid): array
    {
        array_splice($currentReferencesArr, $position, 0, [$elementUid]);

        return $currentReferencesArr;
    }

    /**
     * Removes the element specified by $position from the given list of references and returns
     * the updated list. (the list is passed and return as an array)
     *
     * @param array $currentReferencesArr Array of tt_content uids from a current reference list
     * @param int $position Position of the element reference which should be removed. 1 = first element, 2 = second element etc.
     *
     * @return array Array with an updated reference list
     */
    public function removeElementReferenceFromList(array $currentReferencesArr, int $position): array
    {
        array_splice($currentReferencesArr, $position, 1);

        return $currentReferencesArr;
    }

    /**
     * Updates the XML structure with the new list of references to records.
     *
     * @param array $referencesArr The array of uids (references list) to store in the record
     * @param array $destinationPointer Flexform pointer to the location where the references list should be stored.
     */
    public function storeElementReferencesListInRecord(array $referencesArr, array $destinationPointer)
    {
        $dataArr = [];
        $uid = BackendUtility::wsMapId($destinationPointer['table'], $destinationPointer['uid']);
        $containerHasWorkspaceVersion = false;
        if ($uid != $destinationPointer['uid']) {
            $containerHasWorkspaceVersion = true;
        }

        $fieldPart = [
            $destinationPointer['vLang'] => implode(',', $referencesArr)
        ];
        $sLangPart = [];

        $sLangPart = ArrayUtility::setValueByPath($sLangPart, $destinationPointer['field'], $fieldPart, '#');
        $dataArr[$destinationPointer['table']][$uid]['tx_templavoilaplus_flex']['data'][$destinationPointer['sheet']][$destinationPointer['sLang']] = $sLangPart;

        $flagWasSet = $this->getTCEmainRunningFlag();
        $this->setTCEmainRunningFlag(true);
        /** @var DataHandler $tce */
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $tce->start($dataArr, []);

        /**
         * Set workspace to 0 because:
         * 1) we want shadow-records to be placed in the Live-Workspace
         * 2) there's no need to create a new version of the parent-record
         * 3) try to avoid issues if the same list is modified in different workspaces at the same time
         */
        if ($this->modifyReferencesInLiveWS && !$containerHasWorkspaceVersion) {
            if ($tce->BE_USER->groupData['allowed_languages']) {
                //force access to default language - since references needs to be stored in default langauage always
                $tce->BE_USER->groupData['allowed_languages'] .= ',0';
            }
            $tce->BE_USER->workspace = 0;
        }
        $tce->process_datamap();
        if (!$flagWasSet) {
            $this->setTCEmainRunningFlag(false);
        }
    }

    /**
     * Checks if a flexform pointer points to a valid location, ie. the sheets,  fields etc. exist in the target data
     * structure. If it is valid, the pointer array will be returned.
     *
     * This method take workspaces into account (by using workspace flexform data if available) but it does NOT (and should not!) remap UIDs!
     *
     * @param string $pointerString A flexform pointer referring to the record or flexform part.
     * @return array|null The valid flexform pointer array or NULL if it was not valid
     */
    protected function getValidPointer(string $pointerString, bool $newPositionPossible = false): ?array
    {
        $flexformPointer = $this->getPointerFromString($pointerString);
        if (!isset($GLOBALS['TCA'][$flexformPointer['table']])) {
            return null;
        }
        /** @TODO Does it have a flex field and which one is it? */
        //getMapIdentifierFromRootline ?
        $minimumFields = 'uid,pid,tx_templavoilaplus_next_map,tx_templavoilaplus_map,tx_templavoilaplus_flex';
        if ($flexformPointer['table'] === 'tt_content') {
            $minimumFields = 'uid,pid,tx_templavoilaplus_map,tx_templavoilaplus_flex,CType';
        }

        $pointerRecord = BackendUtility::getRecordWSOL($flexformPointer['table'], $flexformPointer['uid'], $minimumFields);
        if (!$pointerRecord) {
            return null;
        }
        $flexformPointer['foundRecord'] = $pointerRecord;

        // Only point to a record and no field/position
        if (!isset($flexformPointer['position'])) {
            return $flexformPointer;
        }

        if ($flexformPointer['position'] < 0) {
            return null;
        }

        // Now we need the DS from record
        $dataStructure = $this->getDatastructureForPointer($flexformPointer);

        $flexformPointer['foundDataStructure'] = $dataStructure;

        /** @TODO Does it have a flex field and which one is it? */
        if ($pointerRecord['tx_templavoilaplus_flex'] === null) {
            $pointerRecord['tx_templavoilaplus_flex'] = '';
        }

        $elementReferences = $this->getElementReferencesFromXml($pointerRecord['tx_templavoilaplus_flex'], $flexformPointer);

        if ($elementReferences === null) {
            if ($flexformPointer['position'] == 0) {
                return $flexformPointer;
            }
            return null;
        }

        // position should between 0 and count of existing elements for possible adding elements
        $maxPosition = count($elementReferences['references']);
        if (!$newPositionPossible) {
            // We are starting from 0, so max is count elements - 1
            $maxPosition--;
        }
        if ($flexformPointer['position'] > $maxPosition) {
            return null;
        }
        $flexformPointer['foundFieldReferences'] = $elementReferences;

        /** @TODO Check md5 of flexform/record? Move may not a Flexform field*/

        return $flexformPointer;
    }

    /**
     * Takes FlexForm XML content in and based on the flexform pointer it will find a list of references, parse them
     * and return them as an array of uids of the table. This function automatically checks if the records
     * really exist and are not marked as deleted - those who are will be filtered out.
     *
     * @param string $flexformXml XML content of a flexform field
     * @param array $flexformPointer Pointing to a field in the XML structure to get the list of element references from.
     *
     * @return array|null Array with field references which holds uids as array and referenceTable which holds the tablename or NULL if an error occurred (eg. flexformXML was no valid XML)
     */
    public function getElementReferencesFromXml($flexformXml, $flexformPointer): ?array
    {
        // Getting value of the field containing the relations:
        $flexform = GeneralUtility::xml2array($flexformXml);
        if (!is_array($flexform) && strlen($flexformXml) > 0) {
            return null;
        }

        $fieldPointerPath = explode('#', $flexformPointer['field']);

        $baseDataStructure = $flexformPointer['foundDataStructure']['sheets'][$flexformPointer['sheet']]['ROOT']['el'];

        // Find field config
        $lastWasSection = false;
        foreach ($fieldPointerPath as $fieldName) {
            if ($lastWasSection) {
                $lastWasSection = false;
                continue;
            }
            if ($fieldName !== 'el' || $baseDataStructure['type'] === 'array') {
                if (isset($baseDataStructure['section']) && $baseDataStructure['section']) {
                    $lastWasSection = true;
                }
                if (isset($baseDataStructure[$fieldName])) {
                    $baseDataStructure = $baseDataStructure[$fieldName];
                } else {
                    $baseDataStructure = null;
                    break;
                }
            }
        }
        if (!is_array($baseDataStructure) || ($baseDataStructure['config']['type'] ?? '') !== 'group') {
            return null;
        }
        $innerTable = $baseDataStructure['config']['allowed'];

        $listOfUIDs = '';
        if (is_array($flexform) && is_array($flexform['data'])) {
            $sLangPart = ($flexform['data'][$flexformPointer['sheet']][$flexformPointer['sLang']] ?? []);

            try {
                $fieldPart = ArrayUtility::getValueByPath($sLangPart, $fieldPointerPath);
                $listOfUIDs = $fieldPart[$flexformPointer['vLang']] ?? '';
            } catch (\TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException) {
                $fieldPart = null;
                $listOfUIDs = '';
            }
        }

        $arrayOfUIDs = GeneralUtility::intExplode(',', $listOfUIDs);

        // Getting the relation uids out and use only tt_content records which are not deleted:
        $dbAnalysis = GeneralUtility::makeInstance(RelationHandler::class);

        $dbAnalysis->start($listOfUIDs, $innerTable);
        $dbAnalysis->getFromDB();

        $elementReferencesArr = [];
        $counter = 0;
        foreach ($arrayOfUIDs as $uid) {
            if (isset($dbAnalysis->results[$innerTable][$uid]) && is_array($dbAnalysis->results[$innerTable][$uid])) {
                $elementReferencesArr[$counter] = $uid;
                $counter++;
            }
        }

        return [
            'references' => $elementReferencesArr,
            'referenceTable' => $innerTable,
        ];
    }

    /**
     * Converts a string of the format "table:uid:sheet:sLang:#field:vLang:position" into a flexform pointer array.
     *
     * FUTURE Versions will use another pointer string here, more like table:uid:dbRowField:sheet:sLang:#flexformfield:vLang:position:md5
     *
     * @param string $pointerString A string of the format "table:uid:sheet:sLang:#field:vLang:position:md5".
     *
     * @return array A flexform pointer array which can be used with the functions in tx_templavoilaplus_api
     * @throws ProcessingException
     */
    public function getPointerFromString(string $pointerString): array
    {
        if (!$pointerString) {
            throw new ProcessingException(sprintf('Invalid pointer string: "%s"', $pointerString), 1666475964956);
        }
        $locationArr = explode(':', $pointerString);

        if (count($locationArr) == 2) {
            $flexformPointer = [
                'table' => $locationArr[0],
                'uid' => $locationArr[1],
            ];
        } else {
            $flexformPointer = [
                'table' => $locationArr[0],
                'uid' => $locationArr[1],
                'sheet' => $locationArr[2],
                'sLang' => $locationArr[3],
                'field' => substr($locationArr[4], 1), /* Remove first "#" char */
                'vLang' => $locationArr[5],
                'position' => (int)$locationArr[6],
            ];
        }

        return $flexformPointer;
    }

    /**
     * Converts a flexform pointer array to a string of the format "table:uid:sheet:sLang:field:vLang:position/targettable:targetuid"
     *
     * @TODO Fix naming parentPointer vs flexformPointer, move into own class @see getPointerFromString
     * NOTE: "targettable" currently must be tt_content
     *
     * @param array $parentPointer A valid flexform pointer array
     *
     * @return string A string of the format "table:uid:sheet:sLang:field:vLang:position". The string might additionally contain "/table:uid" which is used to check the target record of the pointer.
     */
    public function getParentPointerAsString(?array $parentPointer): string
    {
        if (isset($parentPointer['sheet'])) {
            $flexformPointerString = sprintf(
                '%s:%s:%s:%s:#%s:%s:%s',
                $parentPointer['table'],
                $parentPointer['uid'],
                $parentPointer['sheet'],
                $parentPointer['sLang'],
                $parentPointer['field'],
                $parentPointer['vLang'],
                $parentPointer['position']
            );
            if (isset($parentPointer['targetCheckUid'])) {
                /** @TODO Whats that? */
                $flexformPointerString .= '/tt_content:' . $parentPointer['targetCheckUid'];
            }
        } elseif (isset($parentPointer['table']) && isset($parentPointer['uid'])) {
            $flexformPointerString = $parentPointer['table'] . ':' . $parentPointer['uid'];
        } else {
            $flexformPointerString = '';
        }

        return $flexformPointerString;
    }

    protected function createParentPointer(array $node, string $sheetKey, string $fieldComleteKey, string $lKey, string $vKey): array
    {
        $fieldComleteKey = ltrim($fieldComleteKey, '#');
        return [
            'table' => $node['raw']['table'],
            'uid' => $node['raw']['entity']['uid'],
            'sheet' => $sheetKey,
            'sLang' => $lKey,
            'field' => $fieldComleteKey,
            'vLang' => $vKey,
            'position' => 0,
        ];
    }

    /******************************************************
     *
     * Miscellaneous functions (protected)
     *
     ******************************************************/

    /**
     * Sets a flag to tell the TemplaVoila TCEmain userfunctions if this API has called a TCEmain
     * function. If this flag is set, the TemplaVoila TCEmain userfunctions will be skipped to
     * avoid infinite loops and other bad effects.
     *
     * @param bool $flag If TRUE, our user functions will be omitted
     */
    protected function setTCEmainRunningFlag(bool $flag): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoilaplus_api']['apiIsRunningTCEmain'] = $flag;
    }

    /**
     * Returns the current flag which tells TemplaVoila TCEmain userfunctions if this API has called a TCEmain
     * function. If this flag is set, the TemplaVoila TCEmain userfunctions will be skipped to
     * avoid infinite loops and other bad effects.
     *
     * @return bool TRUE if flag is set, otherwise FALSE;
     */
    public function getTCEmainRunningFlag(): bool
    {
        return isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoilaplus_api']['apiIsRunningTCEmain'])
            && $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoilaplus_api']['apiIsRunningTCEmain'];
    }
}
