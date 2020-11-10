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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * ONLY FOR TEMPORARY USE
 * NO API!
 */
class ProcessingService
{
    /** @var IconFactory */
    protected $iconFactory = null;

    /** @var FlexFormTools */
    protected $flexFormTools = null;

    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
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
        $node = $this->getNodeFromRow($table, $row);
        $node['datastructure'] = $this->getDatastructureForNode($node);
        $node['flexform'] = $this->getFlexformForNode($node);

        // $langChildren = (int)$tree['ds_meta']['langChildren'];
        // $langDisable = (int)$tree['ds_meta']['langDisable'];

        // Load sheet informations

        // Load language informations? getContentTree_getLocalizationInfoForElement ?

        // Get node childs:
        $node['childs']  = $this->getNodeChilds($table, $row, $tt_content_elementRegister);

        // Return result:
        return [
            'node' => $node,
            'contentElementUsage' => $tt_content_elementRegister // ?
        ];
    }

    public function getNodeFromRow(string $table, array $row)
    {
        $title = BackendUtility::getRecordTitle($table, $row);
        $node = [
            'raw' => [
                'fullRow' => $row,
                'table' => $table,
            ],
            'prepared' => [
                'shortTitle' => GeneralUtility::fixed_lgd_cs($title, 50),
                'fullTitle' => $title,
                'hintTitle' => BackendUtility::getRecordIconAltText($row, $table),
                'iconTag' => $this->iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render(),
            ],
        ];

        return $node;
    }

    public function getDatastructureForNode(array $node): array
    {
        $table = $node['raw']['table'];
        $row = $node['raw']['fullRow'];

        /** @TODO At the moment, concentrating only on this parts, but more could be possible */
        if ($table == 'pages' || $table == $this->rootTable || ($table == 'tt_content' && $row['CType'] == 'templavoilaplus_pi1')) {
            $dataStructureIdentifier = $this->flexFormTools->getDataStructureIdentifier(
                $GLOBALS['TCA'][$table]['columns']['tx_templavoilaplus_flex'],
                $table,
                'tx_templavoilaplus_flex',
                $row
            );

            /** @TODO Runtime Cache? */
            $rawDataStructureArr = $this->flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);

            if (is_array($rawDataStructureArr)) {
                return $rawDataStructureArr;
            }
        }

        return [];
    }

    public function getFlexformForNode(array $node): array
    {
            $flexform = GeneralUtility::xml2array($row['tx_templavoilaplus_flex']);
            if (!is_array($flexform)) {
                return [];
            }

            return $flexform;
    }

    public function getNodeChilds()
    {
    }
}

