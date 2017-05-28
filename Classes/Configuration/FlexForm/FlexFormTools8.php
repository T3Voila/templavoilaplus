<?php
namespace Ppi\TemplaVoilaPlus\Configuration\FlexForm;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains functions for manipulating flex form data
 */
class FlexFormTools8 extends FlexFormTools
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
        // if there is a solution we can adapt it accordingly
        try {
            return parent::getDataStructureIdentifier($fieldTca, $tableName, $fieldName, $row);
        } catch (\TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowException $e) {
        } catch (\TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowLoopException $e) {
        } catch (\TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowRootException $e) {
        } catch (\TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidPointerFieldValueException $e) {
        } catch (\TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException $e) {
        }
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
        } else {
            return parent::parseDataStructureByIdentifier($identifier);
        }
    }
}
