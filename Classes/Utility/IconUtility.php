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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class IconUtility
{
    public static function getRecordIconIdentifier($table, $uid, $fallbackIconIdentifier)
    {
        /** @var IconRegistry $iconRegistry */
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        $defaultIcon = $iconRegistry->getDefaultIconIdentifier();

        $row = BackendUtility::getRecordWSOL($table, $uid);
        $iconIdentifier = is_array($row) ? self::mapRecordTypeToIconIdentifier($table, $row) : $defaultIcon;

        return $iconIdentifier === $defaultIcon ? $fallbackIconIdentifier : $iconIdentifier;
    }

    /**
     * taken from 11LTS IconFactory::mapRecordTypeToIconIdentifier(), marked as internal there, will be protected
     *
     * This helper functions looks up the column that is used for the type of the chosen TCA table and then fetches the
     * corresponding iconName based on the chosen icon class in this TCA.
     * The TCA looks up
     * - [ctrl][typeicon_column]
     * -
     * This method solely takes care of the type of this record, not any statuses used for overlays.
     *
     * see EXT:core/Configuration/TCA/pages.php for an example with the TCA table "pages"
     *
     * @param string $table The TCA table
     * @param array $row The selected record
     *
     * @return string The icon identifier string for the icon of that DB record
     */
    // phpcs:disable Generic.Metrics.CyclomaticComplexity
    protected static function mapRecordTypeToIconIdentifier($table, array $row)
    {
        // phpcs:enable
        $recordType = [];
        $ref = null;

        if (isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_column'])) {
            $column = $GLOBALS['TCA'][$table]['ctrl']['typeicon_column'];
            if (isset($row[$column])) {
                // even if not properly documented the value of the typeicon_column in a record could be
                // an array (multiselect) in typeicon_classes a key could consist of a comma-separated string "foo,bar"
                // but mostly it should be only one entry in that array
                if (is_array($row[$column])) {
                    $recordType[1] = implode(',', $row[$column]);
                } else {
                    $recordType[1] = $row[$column];
                }
            } else {
                $recordType[1] = 'default';
            }
            // Workaround to give nav_hide pages a complete different icon
            // Although it's not a separate doctype
            // and to give root-pages an own icon
            if ($table === 'pages') {
                if (($row['nav_hide'] ?? 0) > 0) {
                    $recordType[2] = self::getRecordTypeForPageType(
                        $recordType[1],
                        'hideinmenu',
                        $table
                    );
                }
                if (($row['is_siteroot'] ?? 0) > 0) {
                    $recordType[3] = self::getRecordTypeForPageType(
                        $recordType[1],
                        'root',
                        $table
                    );
                }
                if (!empty($row['module'])) {
                    if (is_array($row['module'])) {
                        // field 'module' is configured as type 'select' in the TCA,
                        // so the value may have already been converted to an array
                        $moduleSuffix = reset($row['module']);
                    } else {
                        $moduleSuffix = $row['module'];
                    }
                    $recordType[4] = 'contains-' . $moduleSuffix;
                }
                if (($row['content_from_pid'] ?? 0) > 0) {
                    if ($row['is_siteroot'] ?? false) {
                        $recordType[4] = self::getRecordTypeForPageType(
                            $recordType[1],
                            'contentFromPid-root',
                            $table
                        );
                    } else {
                        $suffix = (int)$row['nav_hide'] === 0 ? 'contentFromPid' : 'contentFromPid-hideinmenu';
                        $recordType[4] = self::getRecordTypeForPageType($recordType[1], $suffix, $table);
                    }
                }
            }
            if (
                isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'])
                && is_array($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'])
            ) {
                foreach ($recordType as $key => $type) {
                    if (isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'][$type])) {
                        $recordType[$key] = $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'][$type];
                    } else {
                        unset($recordType[$key]);
                    }
                }
                $recordType[0] = $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['default'] ?? '';
                if (
                    isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['mask'])
                    && isset($row[$column]) && is_string($row[$column])
                ) {
                    $recordType[5] = str_replace(
                        '###TYPE###',
                        $row[$column] ?? '',
                        $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['mask']
                    );
                }
                if (isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['userFunc'])) {
                    $parameters = ['row' => $row];
                    $recordType[6] = GeneralUtility::callUserFunction(
                        $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['userFunc'],
                        $parameters,
                        $ref
                    );
                }
            } else {
                foreach ($recordType as &$type) {
                    $type = 'tcarecords-' . $table . '-' . $type;
                }
                unset($type);
                $recordType[0] = 'tcarecords-' . $table . '-default';
            }
        } elseif (
            isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'])
            && is_array($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'])
        ) {
            $recordType[0] = $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['default'];
        } else {
            $recordType[0] = 'tcarecords-' . $table . '-default';
        }

        if (($row['CType'] ?? '') === 'list' && ($row['list_type'] ?? '') !== '') {
            $pluginIcon = self::getIconForPlugin($row['list_type']);
            if ($pluginIcon) {
                $recordType[7] = $pluginIcon;
            }
        }

        krsort($recordType);

        /** @var IconRegistry $iconRegistry */
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        foreach ($recordType as $iconName) {
            if ($iconRegistry->isRegistered($iconName)) {
                return $iconName;
            }
        }

        return $iconRegistry->getDefaultIconIdentifier();
    }

    /**
     * taken from 11LTS IconFactory
     *
     * Returns recordType for icon based on a typeName and a suffix.
     * Fallback to page as typeName if resulting type is not configured.
     *
     * @param string $typeName
     * @param string $suffix
     * @param string $table
     *
     * @return string
     */
    protected static function getRecordTypeForPageType(string $typeName, string $suffix, string $table): string
    {
        $recordType = $typeName . '-' . $suffix;

        // Check if typeicon class exists. If not fallback to page as typeName
        if (!isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'][$recordType])) {
            $recordType = 'page-' . $suffix;
        }
        return $recordType;
    }

    /**
     * taken from 11LTS IconFactory
     * Returns a possible configured icon for the given plugin name
     *
     * @param string $pluginName
     *
     * @return string|null
     */
    protected static function getIconForPlugin(string $pluginName): ?string
    {
        $result = null;
        $items = $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'];
        foreach ($items as $item) {
            if ($item[1] === $pluginName) {
                $result = $item[2];
                break;
            }
        }

        return $result;
    }
}
