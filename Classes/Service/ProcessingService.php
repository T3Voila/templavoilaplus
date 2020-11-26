<?php
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
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * ONLY FOR TEMPORARY USE
 * NO API!
 */
class ProcessingService
{
    /** @var FlexFormTools */
    protected $flexFormTools = null;

    /** @TODO This makes the Service statefull!! */
    /** @var int */
    protected $basePid = null;

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
     *
     * @return array The content tree
     */
    public function getNodeWithTree(string $table, array $row): array
    {
        if ($this->basePid === null) {
            if ($table === 'pages') {
                $this->basePid = (int) $row['uid'];
            } else {
                $this->basePid = (int) $row['pid'];
            }
        }

        $node = $this->getNodeFromRow($table, $row);
        $node['datastructure'] = $this->getDatastructureForNode($node);
        $node['flexform'] = $this->getFlexformForNode($node);

        // $langChildren = (int)$tree['ds_meta']['langChildren'];
        // $langDisable = (int)$tree['ds_meta']['langDisable'];

        // Load sheet informations

        // Load language informations? getContentTree_getLocalizationInfoForElement ?

        // Get node childs:
        $node['childNodes']  = $this->getNodeChilds($node);

        // Return result:
        return [
            'node' => $node,
            'contentElementUsage' => $tt_content_elementRegister // ?
        ];
    }

    public function getNodeFromRow(string $table, array $row)
    {
        $title = BackendUtility::getRecordTitle($table, $row);

        $onPid = ($table === 'pages' ? (int) $row['uid'] : (int) $row['pid']);

        $node = [
            'raw' => [
                'entity' => $row,
                'table' => $table,
            ],
            'rendering' => [
                'shortTitle' => GeneralUtility::fixed_lgd_cs($title, 50),
                'fullTitle' => $title,
                'hintTitle' => BackendUtility::getRecordIconAltText($row, $table),
                'partial' => 'Backend/Handler/DoktypeDefaultHandler/PageElement',
                'belongsToCurrentPage' => ($this->basePid === $onPid),
            ],
        ];

        return $node;
    }

    public function getDatastructureForNode(array $node): array
    {
        $table = $node['raw']['table'];
        $row = $node['raw']['entity'];

        /** @TODO At the moment, concentrating only on this parts, but more could be possible */
        if ($table == 'pages' || $table == $this->rootTable || ($table == 'tt_content' && $row['CType'] == 'templavoilaplus_pi1')) {
            $dataStructureIdentifier = $this->flexFormTools->getDataStructureIdentifier(
                $GLOBALS['TCA'][$table]['columns']['tx_templavoilaplus_flex'],
                $table,
                'tx_templavoilaplus_flex',
                $row
            );

            /** @TODO Runtime Cache? */
            $rawDataStructure = $this->flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);

            if (is_array($rawDataStructure)) {
                $rawDataStructure['identifier'] = $dataStructureIdentifier;
                return $rawDataStructure;
            }
        }

        return [];
    }

    public function getFlexformForNode(array $node): array
    {
            $flexform = GeneralUtility::xml2array($node['raw']['entity']['tx_templavoilaplus_flex']);
            if (!is_array($flexform)) {
                return [];
            }

            return $flexform;
    }

    public function getNodeChilds(array $node): array
    {
        $childs = [];

        $lKeys = ['lDEF'];
        $vKeys = ['vDEF'];

        // Traverse each sheet in the FlexForm Structure:
        foreach ($node['datastructure']['sheets'] as $sheetKey => $sheetData) {

            // Traverse the sheet's elements:
            if (is_array($sheetData) && is_array($sheetData['ROOT']['el'])) {
                foreach ($sheetData['ROOT']['el'] as $fieldKey => $fieldData) {
                    // If the current field points to other content elements, process them:
                    if ($fieldData['TCEforms']['config']['type'] == 'group' &&
                        $fieldData['TCEforms']['config']['internal_type'] == 'db' &&
                        $fieldData['TCEforms']['config']['allowed'] == 'tt_content'
                    ) {
                        foreach ($lKeys as $lKey) {
                            foreach ($vKeys as $vKey) {
                                $listOfSubElementUids = $node['flexform']['data'][$sheetKey][$lKey][$fieldKey][$vKey];
//                                 $tree['depth'] = $depth;
                                if ($listOfSubElementUids) {
                                    $childs[$sheetKey][$lKey][$fieldKey][$vKey] = $this->getNodesFromListWithTree($listOfSubElementUids);
                                }
                            }
                        }
                    } elseif ($fieldData['type'] != 'array' && $fieldData['TCEforms']['config']) { // If generally there are non-container fields, register them:
                        $childs['contentFields'][$sheetKey][] = $fieldKey;
                    }
                }
            }
        }

        return $childs;
    }

    public function getNodesFromListWithTree(string $listOfNodes): array
    {
        $nodes = [];

        // Get records:
        /** @var RelationHandler $dbAnalysis */
        $dbAnalysis = GeneralUtility::makeInstance(RelationHandler::class);
        $dbAnalysis->start($listOfNodes, 'tt_content');

        // Traverse records:
        $counter = 1; // Note: key in $dbAnalysis->itemArray is not a valid counter! It is in 'tt_content_xx' format!
        foreach ($dbAnalysis->itemArray as $recIdent) {
            $idStr = 'tt_content:' . $recIdent['id'];

            $contentRow = BackendUtility::getRecordWSOL('tt_content', $recIdent['id']);

            if (is_array($contentRow)) {
                $nodes[$idStr] = $this->getNodeWithTree('tt_content', $contentRow);
//                 $tt_content_elementRegister[$recIdent['id']]++;
//                 $subTree['el'][$idStr] = $this->getContentTree_element('tt_content', $nextSubRecord, $tt_content_elementRegister, $prevRecList . ',' . $idStr, $depth + 1);
//                 $subTree['el'][$idStr]['el']['index'] = $counter;
//                 $subTree['el'][$idStr]['el']['isHidden'] = $GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['disabled'] && $nextSubRecord[$GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['disabled']];
//                 $subTree['el_list'][$counter] = $idStr;
            } else {
                # ERROR: The element referenced was deleted! - or hidden :-)
            }
        }

        return $nodes;
    }
}

