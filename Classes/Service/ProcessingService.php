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
use Tvp\TemplaVoilaPlus\Exception\ConfigurationException;
use Tvp\TemplaVoilaPlus\Exception\InvalidIdentifierException;
use Tvp\TemplaVoilaPlus\Exception\MissingPlacesException;
use Tvp\TemplaVoilaPlus\Utility\ApiHelperUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException as CoreInvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
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

        // Get node childs:
        $node['childNodes'] = $this->getNodeChilds($node, $basePid, $usedElements);

        // Return result:
        return [
            'node' => $node,
            'usedElements' => $usedElements
        ];
    }

    public function getUnusedElements(array $pageRow, array $usedElements): array
    {
        $table = 'tt_content';

        // Get all page elements not in usedElements
        $usedUids = array_keys($usedElements[$table]);

        /** @TODO Move into Repository? */
        /** @var QueryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        // set table and where clause
        $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->notIn('uid', $usedUids),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter((int)$pageRow['uid'], \PDO::PARAM_INT))
            );

        $row = $queryBuilder->execute()->fetchAllAssociative();

        return $row;
    }

    public function getNodeFromRow(string $table, array $row, array $parentPointer = [], int $basePid = 0, array &$usedElements = [])
    {
        $title = BackendUtility::getRecordTitle($table, $row);

        $onPid = ($table === 'pages' ? (int)$row['uid'] : (int)$row['pid']);
        $parentPointerString = $this->getParentPointerAsString($parentPointer);
        $combinedBackendLayoutConfigurationIdentifier = '';

        $mappingConfiguration = $this->getMappingConfiguration($table, $row);
        if ($mappingConfiguration) {
            $combinedBackendLayoutConfigurationIdentifier = $mappingConfiguration->getCombinedBackendLayoutConfigurationIdentifier();
            if ($combinedBackendLayoutConfigurationIdentifier !== '') {
                $backendLayoutConfiguration = ApiHelperUtility::getBackendLayoutConfiguration($combinedBackendLayoutConfigurationIdentifier);
            }
        }

        if (isset($usedElements[$table][$row['uid']])) {
            $usedElements[$table][$row['uid']]++;
        } else {
            $usedElements[$table][$row['uid']] = 1;
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
                'description' => ($row[$GLOBALS['TCA'][$table]['ctrl']['descriptionColumn']] ?? ''),
                'belongsToCurrentPage' => ($basePid === $onPid),
                'countUsedOnPage' => $usedElements[$table][$row['uid']],
                'parentPointer' => $parentPointerString,
                'beLayout' => $combinedBackendLayoutConfigurationIdentifier,
                'beLayoutDesign' => ($backendLayoutConfiguration ? $backendLayoutConfiguration->isDesign() : false),
                'md5' => md5($parentPointerString . '/' . $table . ':' . $row['uid']),
            ],
        ];

        return $node;
    }

    public function getMappingConfiguration(string $table, array $row): ?MappingConfiguration
    {
        $map = $row['tx_templavoilaplus_map'];
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
        if ($table == 'pages' || $table == $this->rootTable || ($table == 'tt_content' && $row['CType'] == 'templavoilaplus_pi1')) {
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
        if ($node['raw']['entity']['tx_templavoilaplus_flex'] === null) {
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
                            if ($fieldConfig['type'] == 'array') {
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

        $localizationRepository = GeneralUtility::makeInstance(\Tvp\TemplaVoilaPlus\Domain\Repository\Localization\LocalizationRepository::class);

        $records = $localizationRepository->fetchRecordLocalizations($table, (int)$row['uid']);
        /** @TODO WSOL? */
        foreach ($records as $record) {
            $localization[$record[$tcaCtrl['languageField']]] = $this->getNodeFromRow($table, $record);
        }

        return $localization;
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
                    $childs[$sheetKey][$lKey] = $this->getNodeChildsFromElements($sheetData['ROOT']['el'], $lKey, $node['flexform']['data'][$sheetKey][$lKey], $basePid, $usedElements);
                }
            }
        }

        return $childs;
    }

    protected function getNodeChildsFromElements(array $elements, string $lKey, array $values, int $basePid, array &$usedElements): array
    {
        $childs = [];
        /** @TODO We need this dynamically */
        $vKeys = ['vDEF'];

        foreach ($elements as $fieldKey => $fieldConfig) {
            if ($fieldConfig['type'] == 'array') {
                if ($fieldConfig['section']) {
                    if (isset($values[$fieldKey]['el'])) {
                        foreach ($values[$fieldKey]['el'] as $key => $fieldValue) {
                            $childs[$fieldKey][$key] = $this->getNodeChildsFromElements($fieldConfig['el'], $lKey, $fieldValue, $basePid, $usedElements);
                        }
                    }
                } else {
                    $childs[$fieldKey] = $this->getNodeChildsFromElements($fieldConfig['el'], $lKey, $values[$fieldKey]['el'], $basePid, $usedElements);
                }
            } else {
                // If the current field points to another table, process it if not sys_file or sys_file_reference:
                if (
                    $fieldConfig['TCEforms']['config']['type'] === 'group'
                    && $fieldConfig['TCEforms']['config']['internal_type'] === 'db'
                ) {
                    /** @TODO allowed can be multiple tables */
                    $table = $fieldConfig['TCEforms']['config']['allowed'];
                    foreach ($vKeys as $vKey) {
                        $listOfSubElementUids = $values[$fieldKey][$vKey];
                        if ($listOfSubElementUids) {
//                             $parentPointer = $this->createParentPointer($node, $sheetKey, $fieldKey, $lKey, $vKey);
                            $parentPointer = [];
                            $childs[$fieldKey][$vKey] = $this->getNodesFromListWithTree($listOfSubElementUids, $parentPointer, $basePid, $table, $usedElements);
                        } else {
                            $childs[$fieldKey][$vKey] = [];
                        }
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

    public function getNodesFromListWithTree(string $listOfNodes, array $parentPointer, int $basePid, string $table, array &$usedElements): array
    {
        $nodes = [];

        // Get records:
        /** @var RelationHandler $dbAnalysis */
        $dbAnalysis = GeneralUtility::makeInstance(RelationHandler::class);

        $dbAnalysis->start($listOfNodes, $table);

        // Traverse records:
        // Note: key in $dbAnalysis->itemArray is not a valid counter! It is in 'tt_content_xx' format!
        $counter = 1;
        foreach ($dbAnalysis->itemArray as $position => $recIdent) {
            $idStr = $table . ':' . $recIdent['id'];

            $contentRow = BackendUtility::getRecordWSOL($table, $recIdent['id']);

            $parentPointer['position'] = $position;

            // Only do it if the element referenced was not deleted! - or hidden :-)
            if (is_array($contentRow)) {
                $nodes[$idStr] = $this->getNodeWithTree($table, $contentRow, $parentPointer, $basePid, $usedElements);
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
     */
    public function insertElement(string $destinationPointerString, array $elementRow)
    {
        if ($this->debug) {
            GeneralUtility::devLog('API: insertElement()', 'templavoilaplus', 0, ['destinationPointer' => $destinationPointerString, 'elementRow' => $elementRow]);
        }

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

        if (!$elementUid) {
            return false;
        }

        // insert record into destination
        $newReferences = $this->insertElementReferenceIntoList($destinationPointer['foundFieldReferences'], $destinationPointer['position'], $elementUid);
        $this->storeElementReferencesListInRecord($newReferences, $destinationPointer);

        return $elementUid;
    }

    /**
     * Moves an element specified by the source pointer to the location specified by destination pointer.
     * @TODO Only pointers to TCEform of type groups allowed, move inside sections should also be done
     *
     * @param string $sourcePointerString flexform pointer pointing to the element which shall be moved
     * @param string $destinationPointerString flexform pointer to the new location
     * @return boolean TRUE if operation was successfully, otherwise false
     */
    public function moveElement(string $sourcePointerString, string $destinationPointerString): bool
    {
        if ($this->debug) {
            GeneralUtility::devLog('API: moveElement()', 'templavoilaplus', 0, ['sourcePointer' => $sourcePointerString, 'destinationPointer' => $destinationPointerString]);
        }

        // Check and get all information about the source position:
        $sourcePointer = $this->getValidPointer($sourcePointerString);
        // Check and get all information about the destination position:
        $destinationPointer = $this->getValidPointer($destinationPointerString, true);
        if (!$sourcePointer || !$destinationPointer) {
            return false;
        }
        $elementsAreWithinTheSameParentElement = (
            $sourcePointer['table'] == $destinationPointer['table'] &&
            $sourcePointer['uid'] == $destinationPointer['uid']
        );

        $elementUid = $sourcePointer['foundFieldReferences'][$sourcePointer['position']];

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
                $newReferences = $this->removeElementReferenceFromList($sourcePointer['foundFieldReferences'], $sourcePointer['position']);
                $newReferences = $this->insertElementReferenceIntoList($newReferences, $newPosition, $elementUid);
                $this->storeElementReferencesListInRecord($newReferences, $destinationPointer);
            } else {
                $newReferences = $this->removeElementReferenceFromList($sourcePointer['foundFieldReferences'], $sourcePointer['position']);
                $this->storeElementReferencesListInRecord($newReferences, $sourcePointer);
                $newReferences = $this->insertElementReferenceIntoList($destinationPointer['foundFieldReferences'], $destinationPointer['position'], $elementUid);
                $this->storeElementReferencesListInRecord($newReferences, $destinationPointer);
            }
        } else {
            // Move the element to a different parent element:
            $newReferences = $this->removeElementReferenceFromList($sourcePointer['foundFieldReferences'], $sourcePointer['position']);
            $this->storeElementReferencesListInRecord($newReferences, $sourcePointer);
            $newReferences = $this->insertElementReferenceIntoList($destinationPointer['foundFieldReferences'], $destinationPointer['position'], $elementUid);
            $this->storeElementReferencesListInRecord($newReferences, $destinationPointer);

            /** @TODO Move over pages should reset the PID of the element (only for tt_content?) */
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
        if ($this->debug) {
            GeneralUtility::devLog('API: deleteElement()', 'templavoilaplus', 0, ['sourcePointer' => $sourcePointerString]);
        }

        // Check and get all information about the source position:
        $sourcePointer = $this->getValidPointer($sourcePointerString);
        if (!$sourcePointer) {
            return false;
        }

        // Unlink
        $newReferences = $this->removeElementReferenceFromList($sourcePointer['foundFieldReferences'], $sourcePointer['position']);
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
     * Removes a reference to the element (= unlinks) specified by the source pointer.
     *
     * @param string $sourcePointerString flexform pointer pointing to the reference which shall be removed
     * @return boolean TRUE if operation was successfully, otherwise false
     */
    public function unlinkElement(string $sourcePointerString): bool
    {
        if ($this->debug) {
            GeneralUtility::devLog('API: unlinkElement()', 'templavoilaplus', 0, ['sourcePointer' => $sourcePointer]);
        }

        // Check and get all information about the source position:
        $sourcePointer = $this->getValidPointer($sourcePointerString);
        if (!$sourcePointer) {
            return false;
        }

        // Unlink
        $newReferences = $this->removeElementReferenceFromList($sourcePointer['foundFieldReferences'], $sourcePointer['position']);
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
        if ($this->debug) {
            GeneralUtility::devLog('API: storeElementReferencesListInRecord()', 'templavoilaplus', 0, ['referencesArr' => $referencesArr, 'destinationPointer' => $destinationPointer]);
        }

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
            if ($this->debug) {
                GeneralUtility::devLog('flexform_getValidPointer: Table "' . $flexformPointer['table'] . '" is not in the list of allowed tables!', 'TemplaVoilà!+ API', 2);
            }

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
            if ($this->debug) {
                GeneralUtility::devLog('flexform_getValidPointer: Pointer destination record not found!', 'TemplaVoilà!+ API', 2, $flexformPointer);
            }

            return null;
        }
        $flexformPointer['foundRecord'] = $pointerRecord;

        if ($flexformPointer['position'] < 0) {
            if ($this->debug) {
                GeneralUtility::devLog('flexform_getValidPointer: The position must be positive!', 'TemplaVoilà!+ API', 2, $flexformPointer);
            }

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

        // position should between 0 and count of existing elements for possible adding elements
        $maxPosition = count($elementReferences);
        if (!$newPositionPossible) {
            // We are starting from 0, so max is count elements - 1
            $maxPosition--;
        }
        if ($flexformPointer['position'] > $maxPosition) {
            if ($this->debug) {
                GeneralUtility::devLog('flexform_getValidPointer: The position in the specified flexform pointer does not exist!', 'TemplaVoila API', 2, $flexformPointer);
            }

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
     * @return array|null Numerical array tt_content uids or NULL if an error occurred (eg. flexformXML was no valid XML)
     */
    public function getElementReferencesFromXml($flexformXml, $flexformPointer): ?array
    {
        // Getting value of the field containing the relations:
        $flexform = GeneralUtility::xml2array($flexformXml);
        if (!is_array($flexform) && strlen($flexformXml) > 0) {
            if ($this->debug) {
                GeneralUtility::devLog('getElementReferencesFromXml: flexformXML seems to be no valid XML. Parser error message: ' . $flexform, 'TemplaVoila API', 2, $flexformXml);
            }

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
                if ($baseDataStructure['section']) {
                    $lastWasSection = true;
                }
                $baseDataStructure = $baseDataStructure[$fieldName];
            }
        }
        if (!is_array($baseDataStructure) && !is_array($baseDataStructure['TCEforms']) && !is_array($baseDataStructure['TCEforms']['config']) && $baseDataStructure['TCEforms']['config']['type'] === 'group') {
            if ($this->debug) {
                GeneralUtility::devLog('getElementReferencesFromXml: Field has no group configuration: ', 'TemplaVoila API', 2);
            }

            return null;
        }
        $innerTable = $baseDataStructure['TCEforms']['config']['allowed'];

        $listOfUIDs = '';
        if (is_array($flexform) && is_array($flexform['data'])) {
            $sLangPart = $flexform['data'][$flexformPointer['sheet']][$flexformPointer['sLang']];
            $fieldPart = ArrayUtility::getValueByPath($sLangPart, $fieldPointerPath);
            $listOfUIDs = $fieldPart[$flexformPointer['vLang']];
        }

        $arrayOfUIDs = GeneralUtility::intExplode(',', $listOfUIDs);

        // Getting the relation uids out and use only tt_content records which are not deleted:
        $dbAnalysis = GeneralUtility::makeInstance(RelationHandler::class);

        $dbAnalysis->start($listOfUIDs, $innerTable);
        $dbAnalysis->getFromDB();

        $elementReferencesArr = [];
        $counter = 0;
        foreach ($arrayOfUIDs as $uid) {
            if (is_array($dbAnalysis->results[$innerTable][$uid])) {
                $elementReferencesArr[$counter] = $uid;
                $counter++;
            }
        }

        return $elementReferencesArr;
    }

    /**
     * Converts a string of the format "table:uid:sheet:sLang:#field:vLang:position" into a flexform pointer array.
     *
     * FUTURE Versions will use another pointer string here, more like table:uid:dbRowField:sheet:sLang:#flexformfield:vLang:position:md5
     *
     * @param string $pointer A string of the format "table:uid:sheet:sLang:#field:vLang:position:md5".
     * @return array A flexform pointer array which can be used with the functions in tx_templavoilaplus_api
     */
    public function getPointerFromString(string $pointerString): array
    {
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
     * @TODO Fix naming parentPointer vs flexformPointer, move into own class @see flexform_getPointerFromString flexform_getStringFromPointer in ApiService
     * NOTE: "targettable" currently must be tt_content
     *
     * @param array $parentPointer A valid flexform pointer array
     *
     * @return string A string of the format "table:uid:sheet:sLang:field:vLang:position". The string might additionally contain "/table:uid" which is used to check the target record of the pointer.
     */
    protected function getParentPointerAsString(array $parentPointer): string
    {
        if (isset($parentPointer['sheet'])) {
            $flexformPointerString = sprintf(
                '%s:%s:%s:%s:%s:%s:%s',
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
        } else {
            $flexformPointerString = $parentPointer['table'] . ':' . $parentPointer['uid'];
        }

        return $flexformPointerString;
    }

    protected function createParentPointer(array $node, string $sheetKey, string $fieldKey, string $lKey, string $vKey): array
    {
        return [
            'table' => $node['raw']['table'],
            'uid' => $node['raw']['entity']['uid'],
            'sheet' => $sheetKey,
            'sLang' => $lKey,
            'field' => $fieldKey,
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
        return $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoilaplus_api']['apiIsRunningTCEmain'] ? true : false;
    }
}
