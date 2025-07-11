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

use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowLoopException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowRootException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidPointerFieldValueException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidSinglePointerFieldException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidTcaException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Contains functions for manipulating flex form data
 */
class FlexFormTools8 extends \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools
{
    /**
     * The method locates a specific data structure from given TCA and row combination
     * and returns an identifier string that can be handed around, and can be resolved
     * to a single data structure later without giving $row and $tca data again.
     *
     * Note: The returned syntax is meant to only specify the target location of the data structure.
     * It SHOULD NOT be abused and enriched with data from the record that is dealt with. For
     * instance, it is now allowed to add source record specific date like the uid or the pid!
     * If that is done, it is up to the hook consumer to take care of possible side effects, eg. if
     * the data handler copies or moves records around and those references change.
     *
     * This method gets: Source data that influences the target location of a data structure
     * This method returns: Target specification of the data structure
     *
     * This method is "paired" with method getFlexFormDataStructureByIdentifier() that
     * will resolve the returned syntax again and returns the data structure itself.
     *
     * Both methods can be extended via hooks to return and accept additional
     * identifier strings if needed, and to transmit further information within the identifier strings.
     *
     * Note that the TCA for data structure definitions MUST NOT be overridden by
     * 'columnsOverrides' or by parent TCA in an inline relation! This would create a huge mess.
     *
     * Note: This method and the resolving methods belowe are well unit tested and document all
     * nasty details this way.
     *
     * @param array $fieldTca Full TCA of the field in question that has type=flex set
     * @param string $tableName The table name of the TCA field
     * @param string $fieldName The field name
     * @param array $row The data row
     * @return string Identifier string
     * @throws \RuntimeException If TCA is misconfigured
     */
    public function getDataStructureIdentifier(array $fieldTca, string $tableName, string $fieldName, array $row): string
    {
        // @TODO See https://forge.typo3.org/issues/79101
        // Needed for C&P
        // if there is a solution we can adapt it accordingly
        try {
            return parent::getDataStructureIdentifier($fieldTca, $tableName, $fieldName, $row);
            // phpcs:disable
        } catch (InvalidParentRowException $e) {
        } catch (InvalidParentRowLoopException $e) {
        } catch (InvalidParentRowRootException $e) {
        } catch (InvalidPointerFieldValueException $e) {
        } catch (InvalidSinglePointerFieldException $e) {
        } catch (InvalidIdentifierException $e) {
        }
        // phpcs:enable
        return '';
    }

    /**
     * Parse a data structure identified by $identifier to the final data structure array.
     * This method is called after getDataStructureIdentifier(), finds the data structure
     * and returns it.
     *
     * Hooks allow to manipulate the find logic and to post process the data structure array.
     *
     * Note that the TCA for data structure definitions MUST NOT be overridden by
     * 'columnsOverrides' or by parent TCA in an inline relation! This would create a huge mess.
     *
     * After the data structure definition is found, the method resolves:
     * * FILE:EXT: prefix of the data structure itself - the ds is in a file
     * * FILE:EXT: prefix for sheets - if single sheets are in files
     * * EXT: prefix for sheets - if single sheets are in files (slightly different b/w compat syntax)
     * * Create an sDEF sheet if the data structure has non, yet.
     *
     * After that method is run, the data structure is fully resolved to an array,
     * and same base normalization is done: If the ds did not contain a sheet,
     * it will have one afterwards as "sDEF"
     *
     * This method gets: Target specification of the data structure.
     * This method returns: The normalized data structure parsed to an array.
     *
     * Read the unit tests for nasty details.
     *
     * @param string $identifier String to find the data structure location
     * @return array Parsed and normalized data structure
     * @throws InvalidIdentifierException
     */
    public function parseDataStructureByIdentifier(string $identifier): array
    {
        // @TODO See https://forge.typo3.org/issues/79101
        // if there is a solution we can adapt it accordingly
        if ($identifier === '') {
            return [
                'sheets' => [
                    'sDEF' => [
                        'ROOT' => [
                            'el' => [],
                        ],
                    ],
                ],
            ];
        }
        return parent::parseDataStructureByIdentifier($identifier);
    }

    public function prepareFlexform(array $dataStructure): array
    {
        $dataStructure = $this->ensureDefaultSheet($dataStructure);
        $dataStructure = $this->resolveFileDirectives($dataStructure);

        return $dataStructure;
    }

    /**
     * The data structure is located in a record. This method resolves the record and
     * returns an array to identify that record.
     *
     * The example setup below looks in current row for a tx_templavoila_ds value. If not found,
     * it will search the rootline (the table is a tree, typically pages) until a value in
     * tx_templavoila_next_ds or tx_templavoila_ds is found. That value should then be an
     * integer, that points to a record in tx_templavoila_datastructure, and then the data
     * structure is found in field dataprot:
     *
     * fieldTca = [
     *     'config' => [
     *         'type' => 'flex',
     *         'ds_pointerField' => 'tx_templavoila_ds',
     *         'ds_pointerField_searchParent' => 'pid',
     *         'ds_pointerField_searchParent_subField' => 'tx_templavoila_next_ds',
     *         'ds_tableField' => 'tx_templavoila_datastructure:dataprot',
     *     ]
     * ]
     *
     * More simple scenario without tree traversal and having a valid data structure directly
     * located in field theFlexDataStructureField.
     *
     * fieldTca = [
     *     'config' => [
     *         'type' => 'flex',
     *         'ds_pointerField' => 'theFlexDataStructureField',
     *     ]
     * ]
     *
     * Example return array:
     * [
     *     'type' => 'record',
     *     'tableName' => 'tx_templavoila_datastructure',
     *     'uid' => 42,
     *     'fieldName' => 'dataprot',
     * ];
     *
     * @param array $fieldTca Full TCA of the field in question that has type=flex set
     * @param string $tableName The table name of the TCA field
     * @param string $fieldName The field name
     * @param array $row The data row
     * @return array Identifier as array, see example above
     * @throws InvalidParentRowException
     * @throws InvalidParentRowLoopException
     * @throws InvalidParentRowRootException
     * @throws InvalidPointerFieldValueException
     * @throws InvalidTcaException
     */
    protected function getDataStructureIdentifierFromRecord(array $fieldTca, string $tableName, string $fieldName, array $row): array
    {
        $finalPointerFieldName = $fieldTca['config']['ds_pointerField'];
        $pointerFieldName = $finalPointerFieldName;
        if (!array_key_exists($pointerFieldName, $row)) {
            // The user may not have rights to edit this field so set it to empty
            // Will validate later on, if there is a parent available which have something set
            $row[$pointerFieldName] = '';
        }
        $pointerValue = $row[$pointerFieldName];
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
                $queryStatement = $queryBuilder->from($tableName)
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($row[$parentFieldName], \PDO::PARAM_INT)
                        )
                    )
                    ->executeQuery();
                $rowCount = $queryBuilder
                    ->count('uid')
                    ->executeQuery()
                    ->fetchOne(0);
                if ($rowCount !== 1) {
                    throw new InvalidParentRowException(
                        'The data structure for field "' . $fieldName . '" in table "' . $tableName . '" has to be looked up'
                        . ' in field "' . $pointerFieldName . '". That field had no valid value, so a lookup in parent record'
                        . ' with uid "' . $row[$parentFieldName] . '" was done. However, this row does not exist or was deleted.',
                        1463833794
                    );
                }
                $row = $queryStatement->fetch();
                if (isset($handledUids[$row[$parentFieldName]])) {
                    // Row has been fetched before already -> loop detected!
                    throw new InvalidParentRowLoopException(
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
                    throw new InvalidParentRowRootException(
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
            throw new InvalidPointerFieldValueException(
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
            $dataStructureIdentifier = [
                'type' => $pointerType,
                'tableName' => $foreignTableName,
                'uid' => (int)$pointerValue,
                'fieldName' => $foreignFieldName,
            ];
        } else {
            // See https://github.com/pluspol-interactive/templavoilaplus/issues/160
            // If a new(copied) element already contains a pointer we do not search inside parent (see above)
            // But row['uid'] would be 0 so this will fail in DataHandlers checkValueForFlex validation.
            // As the original should have the same config we use the uid of the original.
            $uid = isset($row['uid']) ? (int)$row['uid'] : (int)$row['t3_origuid'];

            // See https://github.com/pluspol-interactive/templavoilaplus/issues/226
            // If we are in WS mode, uid points to the original record and not to the one in WS
            // The FlexFormTools parseDataStructureByIdentifier do not use WS while loading records.
            // Collides with the copy issue?
            $uid = isset($row['_ORIG_uid']) ? (int)$row['_ORIG_uid'] : $uid;

            $dataStructureIdentifier = [
                'type' => $pointerType,
                'tableName' => $tableName,
                'uid' => $uid,
                'fieldName' => $finalPointerFieldName,
            ];
        }
        return $dataStructureIdentifier;
    }
}
