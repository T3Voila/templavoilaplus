<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Configuration\FlexForm;

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
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureIdentifierInitializedEvent;
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use Tvp\TemplaVoilaPlus\Exception\ConfigurationException;
use Tvp\TemplaVoilaPlus\Exception\FlexFormInvalidPointerFieldException;
use Tvp\TemplaVoilaPlus\Exception\MissingPlacesException;
use Tvp\TemplaVoilaPlus\Utility\ApiHelperUtility;

class ParsingModifyEventListener
{
    // phpcs:disable Generic.Metrics.CyclomaticComplexity
    public function initializeDataStructureIdentifier(BeforeFlexFormDataStructureIdentifierInitializedEvent $event): void
    {
        // phpcs:enable
        $fieldTca = $event->getFieldTca();
        $row = $event->getRow();
        $fieldName = $event->getFieldName();
        $tableName = $event->getTableName();

        $identifier = [
            'type' => 'combinedMappingIdentifier',
        ];

        if (($fieldTca['config']['ds_pointerType'] ?? '') !== 'combinedMappingIdentifier') {
            return;
        }
        $finalPointerFieldName = $fieldTca['config']['ds_pointerField'];
        $pointerFieldName = $finalPointerFieldName;

        // The user may not have rights to edit this field so use empty now
        // Will validate later on, if there is a parent available which have something set
        $pointerValue = $row[$pointerFieldName] ?? '';

        // If set, this is typically set to "pid"
        $parentFieldName = $fieldTca['config']['ds_pointerField_searchParent'] ?? null;
        $pointerSubFieldName = $fieldTca['config']['ds_pointerField_searchParent_subField'] ?? null;
        if (!$pointerValue && $parentFieldName) {
            // Fetch rootline until a valid pointer value is found
            $handledUids = [];
            while (!$pointerValue) {
                $uidOfHandle = $row['uid'] ?? 'new_' . $row['t3_origuid'];
                $handledUids[$uidOfHandle] = 1;

                $parentUid = (int) $row[$parentFieldName];
                if ($parentUid === 0) {
                    // We are on TreeRootElement => Leave
                    $pointerValue = '';
                    break;
                }

                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
                $queryBuilder->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                $queryBuilder->select('uid', $parentFieldName, $pointerFieldName);
                if (!empty($pointerSubFieldName)) {
                    $queryBuilder->addSelect($pointerSubFieldName);
                }
                $row = $queryBuilder->from($tableName)
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($row[$parentFieldName], Connection::PARAM_INT)
                        )
                    )
                    ->executeQuery()
                    ->fetchAssociative();
                if ($row === false) {
                    throw new FlexFormInvalidPointerFieldException(
                        'The data structure for field "' . $fieldName . '" in table "' . $tableName . '" has to be looked up'
                        . ' in field "' . $pointerFieldName . '". That field had no valid value, so a lookup in parent record'
                        . ' with uid "' . $parentUid . '" was done. However, this row does not exist or was deleted.',
                        1463833794
                    );
                }
                if (isset($handledUids[$row[$parentFieldName]])) {
                    // Row has been fetched before already -> loop detected!
                    throw new FlexFormInvalidPointerFieldException(
                        'The data structure for field "' . $fieldName . '" in table "' . $tableName . '" has to be looked up'
                        . ' in field "' . $pointerFieldName . '". That field had no valid value, so a lookup in parent record'
                        . ' with uid "' . $row[$parentFieldName] . '" was done. A loop of records was detected, the tree is broken.',
                        1464110956
                    );
                }
                BackendUtility::workspaceOL($tableName, $row);
                // New pointer value: This is the "subField" value if given, else the field value
                // ds_pointerField_searchParent_subField is the "template on next level" structure from templavoila
                if ($pointerSubFieldName && $row[$pointerSubFieldName]) {
                    $finalPointerFieldName = $pointerSubFieldName;
                    $pointerValue = $row[$pointerSubFieldName];
                } else {
                    $pointerValue = $row[$pointerFieldName];
                }
                if (!$pointerValue && ((int)$row[$parentFieldName] === 0 || $row[$parentFieldName] === null)) {
                    // If on root level and still no valid pointer found -> exception
                    throw new FlexFormInvalidPointerFieldException(
                        'The data structure for field "' . $fieldName . '" in table "' . $tableName . '" has to be looked up'
                        . ' in field "' . $pointerFieldName . '". That field had no valid value, so a lookup in parent record'
                        . ' with uid "' . $row[$parentFieldName] . '" was done. Root node with uid "' . $row['uid'] . '"'
                        . ' was fetched and still no valid pointer field value was found.',
                        1464112555
                    );
                }
            }
        }

        $event->setIdentifier([
            'type' => 'combinedMappingIdentifier',
            'cobinedIdentifier' => $pointerValue,
        ]);
    }

    public function setDataStructure(BeforeFlexFormDataStructureParsedEvent $event): void
    {
        $identifier = $event->getIdentifier();
        if (($identifier['type'] ?? '') === 'combinedMappingIdentifier') {
            $dataStructure = [
                'sheets' => [
                    'sDEF' => [
                        'ROOT' => [
                            'el' => [
                            ],
                        ],
                    ],
                ],
            ];

            if ($identifier['cobinedIdentifier'] !== '') {
                try {
                    $mappingConfiguration = ApiHelperUtility::getMappingConfiguration($identifier['cobinedIdentifier']);
                    $dataConfiguration = ApiHelperUtility::getDataStructure($mappingConfiguration->getCombinedDataStructureIdentifier());
                    $dataStructure = $dataConfiguration->getDataStructure();
                } catch (ConfigurationException | MissingPlacesException | \TypeError $e) {
                    $dataStructure['error'] = $e->getMessage();
                    /** @TODO Do logging, if we cannot found the Mapping or DS? */
                }
            }

            $event->setDataStructure($dataStructure);
        }
    }
}
