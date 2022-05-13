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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $node['childNodes']  = $this->getNodeChilds($node, $basePid, $usedElements);

        // Return result:
        // contentElementUsage set to unset var??
        return [
            'node' => $node,
            'contentElementUsage' => $tt_content_elementRegister
        ];
    }

    public function getNodeFromRow(string $table, array $row, array $parentPointer = [], int $basePid = 0, array &$usedElements = [])
    {
        $title = BackendUtility::getRecordTitle($table, $row);

        $onPid = ($table === 'pages' ? (int)$row['uid'] : (int)$row['pid']);
        $parentPointerString = $this->getParentPointerAsString($parentPointer);

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
                'partial' => 'Backend/Handler/DoktypeDefaultHandler/' . ($table === 'pages' ? 'Page' : 'Content') . 'Element',
                'belongsToCurrentPage' => ($basePid === $onPid),
                'countUsedOnPage' => $usedElements[$table][$row['uid']],
                'parentPointer' => $parentPointerString,
                'md5' => md5($parentPointerString . '/' . $table . ':' . $row['uid']),
            ],
        ];

        return $node;
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
            } catch (InvalidIdentifierException $e) {
                $rawDataStructure = ['error' => $e->getMessage()];
            } catch (\RuntimeException $e) {
                $rawDataStructure = ['error' => $e->getMessage()];
            }

            $rawDataStructure['identifier'] = $dataStructureIdentifier;
        }

        return $rawDataStructure;
    }

    public function getFlexformForNode(array $node): array
    {
        $flexform = GeneralUtility::xml2array($node['raw']['entity']['tx_templavoilaplus_flex']);
        if (!is_array($flexform)) {
            return [];
        }

        return $flexform;
    }

    public function getLocalizationForNode(array $node): array
    {
        $localization = [];
        $table = $node['raw']['table'];
        $row = $node['raw']['entity'];

        $tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];

        $localizationRepository = GeneralUtility::makeInstance(\Tvp\TemplaVoilaPlus\Domain\Repository\Localization\LocalizationRepository::class);

        $records = $localizationRepository->fetchRecordLocalizations($table, $row['uid']);
        /** @TODO WSOL? */
        foreach ($records as $record) {
            $localization[$record[$tcaCtrl['languageField']]] = $this->getNodeFromRow($table, $record);
        }

        return $localization;
    }

    public function getNodeChilds(array $node, int $basePid, array &$usedElements): array
    {
        $childs = [];

        if (
            !isset($node['datastructure']['sheets'])
            || !is_array($node['datastructure']['sheets'])
        ) {
            return $childs;
        }

        $lKeys = ['lDEF'];
        $vKeys = ['vDEF'];
        // Traverse each sheet in the FlexForm Structure:
        foreach ($node['datastructure']['sheets'] as $sheetKey => $sheetData) {
            // Traverse the sheet's elements:
            if (is_array($sheetData) && is_array($sheetData['ROOT']['el'])) {
                foreach ($sheetData['ROOT']['el'] as $fieldKey => $fieldData) {
                    // If the current field points to other content elements, process them:
                    if (
                        $fieldData['TCEforms']['config']['type'] == 'group' &&
                        $fieldData['TCEforms']['config']['internal_type'] == 'db' &&
                        $fieldData['TCEforms']['config']['allowed'] == 'tt_content'
                    ) {
                        foreach ($lKeys as $lKey) {
                            foreach ($vKeys as $vKey) {
                                $listOfSubElementUids = $node['flexform']['data'][$sheetKey][$lKey][$fieldKey][$vKey];
                                if ($listOfSubElementUids) {
                                    $parentPointer = $this->createParentPointer($node, $sheetKey, $fieldKey, $lKey, $vKey);
                                    $childs[$sheetKey][$lKey][$fieldKey][$vKey] = $this->getNodesFromListWithTree($listOfSubElementUids, $parentPointer, $basePid, $usedElements);
                                } else {
                                    $childs[$sheetKey][$lKey][$fieldKey][$vKey] = [];
                                }
                            }
                        }
                    } elseif ($fieldData['type'] != 'array' && $fieldData['TCEforms']['config']) {
                        // If generally there are non-container fields, register them:
                        $childs['contentFields'][$sheetKey][$fieldKey] = $fieldKey;
                    }
                }
            }
        }

        return $childs;
    }

    public function getNodesFromListWithTree(string $listOfNodes, array $parentPointer, int $basePid, array &$usedElements): array
    {
        $nodes = [];

        // Get records:
        /** @var RelationHandler $dbAnalysis */
        $dbAnalysis = GeneralUtility::makeInstance(RelationHandler::class);
        $dbAnalysis->start($listOfNodes, 'tt_content');

        // Traverse records:
        // Note: key in $dbAnalysis->itemArray is not a valid counter! It is in 'tt_content_xx' format!
        $counter = 1;
        foreach ($dbAnalysis->itemArray as $position => $recIdent) {
            $idStr = 'tt_content:' . $recIdent['id'];

            $contentRow = BackendUtility::getRecordWSOL('tt_content', $recIdent['id']);

            $parentPointer['position'] = $position;

            // Only do it if the element referenced was not deleted! - or hidden :-)
            if (is_array($contentRow)) {
                $nodes[$idStr] = $this->getNodeWithTree('tt_content', $contentRow, $parentPointer, $basePid, $usedElements);
            }
        }

        return $nodes;
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
            $flexformPointerString
                = $parentPointer['table'] . ':' .
                $parentPointer['uid'] . ':' .
                $parentPointer['sheet'] . ':' .
                $parentPointer['sLang'] . ':' .
                $parentPointer['field'] . ':' .
                $parentPointer['vLang'] . ':' .
                $parentPointer['position'];
            if (isset($parentPointer['targetCheckUid'])) { /** @TODO Whats that? */
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
}
