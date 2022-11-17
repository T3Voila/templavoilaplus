<?php

namespace Tvp\TemplaVoilaPlus\Utility;

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

use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class RecordFalUtility
{
    /**
     * @param $table string table of the record
     * @param $row array complete record
     *
     * @return array the FAL fields which were images before are now arrays of FileReference
     */
    public static function addFalReferencesToRecord(string $table, array $row): array
    {
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        foreach (self::getFalFields($table) as $field) {
            $row[$field] = $fileRepository->findByRelation($table, $field, $row['uid']);
        }
        return $row;
    }

    public static function getFalFields(string $table)
    {
        $fields = [];
        if (isset($GLOBALS['TCA'][$table]['columns'])) {
            foreach ($GLOBALS['TCA'][$table]['columns'] as $columnName => $columnConfig) {
                if (
                    isset($columnConfig['config']['foreign_table'])
                    && $columnConfig['config']['foreign_table'] === 'sys_file_reference'
                ) {
                    $fields[] = $columnName;
                }
            }
        }
        return $fields;
    }
}
