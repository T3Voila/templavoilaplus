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
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidSinglePointerFieldException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidTcaException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use Tvp\TemplaVoilaPlus\Exception\FlexFormInvalidPointerFieldException;

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
                        . ' with uid "' . $row[$parentFieldName] . '" was done. However, this row does not exist or was deleted.',
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
        if (!$pointerValue) {
            // Still no valid pointer value -> exception, This still can be a data integrity issue, so throw a catchable exception
            throw new FlexFormInvalidPointerFieldException(
                'No data structure for field "' . $fieldName . '" in table "' . $tableName . '" found, no "ds" array'
                . ' configured and data structure could be found by resolving parents. This is probably a TCA misconfiguration.',
                1464114011
            );
        }

        // Implement pointerType for TV+ Mapping
        $pointerType = 'record';
        if (isset($fieldTca['config']['ds_pointerType'])) {
            $pointerType = $fieldTca['config']['ds_pointerType'];
        }

        // Ok, finally we have the field value. This is now either a data structure directly, or a pointer to a file,
        // or the value can be interpreted as integer (is an uid) and "ds_tableField" is set, so this is the table, uid and field
        // where the final data structure can be found.
        if (MathUtility::canBeInterpretedAsInteger($pointerValue)) {
            if (!isset($fieldTca['config']['ds_tableField'])) {
                throw new InvalidTcaException(
                    'Invalid data structure pointer for field "' . $fieldName . '" in table "' . $tableName . '", the value'
                    . 'resolved to "' . $pointerValue . '", . which is an integer, so "ds_tableField" must be configured',
                    1464115639
                );
            }
            if (substr_count($fieldTca['config']['ds_tableField'], ':') !== 1) {
                // ds_tableField must be of the form "table:field"
                throw new InvalidTcaException(
                    'Invalid TCA configuration for field "' . $fieldName . '" in table "' . $tableName . '". The setting'
                    . '"ds_tableField" must be of the form "tableName:fieldName"',
                    1464116002
                );
            }
            [$foreignTableName, $foreignFieldName] = GeneralUtility::trimExplode(':', $fieldTca['config']['ds_tableField']);

            $event->setIdentifier([
                'type' => $pointerType,
                'tableName' => $foreignTableName,
                'uid' => (int)$pointerValue,
                'fieldName' => $foreignFieldName,
            ]);
        } else {
            // See https://github.com/pluspol-interactive/templavoilaplus/issues/160
            // If a new(copied) element already contains a pointer we do not search inside parent (see above)
            // But row['uid'] would be 0 so this will fail in DataHandlers checkValueForFlex validation.
            // As the original should have the same config we use the uid of the original.
            $uid = (int) ($row['uid'] ?? ($row['t3_origuid'] ?? -1));

            // See https://github.com/pluspol-interactive/templavoilaplus/issues/226
            // If we are in WS mode, uid points to the original record and not to the one in WS
            // The FlexFormTools parseDataStructureByIdentifier do not use WS while loading records.
            // Collides with the copy issue?
            $uid = (int) ($row['_ORIG_uid'] ?? $uid);

            $event->setIdentifier([
                'type' => $pointerType,
                'tableName' => $tableName,
                'uid' => $uid,
                'fieldName' => $finalPointerFieldName,
            ]);
        }
    }

    public function setDataStructure(BeforeFlexFormDataStructureParsedEvent $event): void
    {
        $identifier = $event->getIdentifier();
        if (($identifier['type'] ?? '') === 'combinedMappingIdentifier') {
            $dataStructureIdentifier = new DataStructureIdentifierHook();
            $event->setDataStructure(
                $dataStructureIdentifier->parseDataStructureByIdentifierPreProcess($identifier)
            );
        }
    }
}
