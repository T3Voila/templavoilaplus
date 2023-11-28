<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Domain\Repository\Localization;

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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for record localizations
 */
class LocalizationRepository
{
    /**
     * Fetch all available languages
     *
     * @param string $table
     * @param int $uid
     *
     * @return array
     */
    public static function fetchRecordLocalizations(string $table, int $uid): array
    {
        $result = [];

        if (BackendUtility::isTableLocalizable($table)) {
            $tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];
            if (self::getBackendUserAuthentication()) {
                $workspace = self::getBackendUserAuthentication()->workspace ?? 0;
            } else {
                $workspace = 0;
            }
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $workspace));

            $queryBuilder->select('*')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq(
                        $tcaCtrl['transOrigPointerField'],
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->gt($tcaCtrl['languageField'], 0)
                );

            $result = $queryBuilder->execute()->fetchAll();
        }

        return $result;
    }

    /**
     * @return BackendUserAuthentication|null
     */
    protected static function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'] ?? null;
    }

    public static function getLanguageOverlayRecord($table, $uid, $language = null)
    {
        $hiddenField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'] ?? 'hidden';
        if ($language === null) {
            $language = self::getCurrentLanguage();
        }
        $baseRecord = BackendUtility::getRecordWSOL($table, $uid, '*', ' AND ' . $hiddenField . '=0');
        if ($language > 0 && $baseRecord) {
            $l10nRecord = static::getRecordLocalization($table, $uid, $language)[0] ?? null;
            // sadly $l10nRecord doesn't allow additionalWhere, so we check for hidden afterwards
            if ($l10nRecord && $l10nRecord[$hiddenField] === 0) {
                return $l10nRecord;
            }
        }
        return $baseRecord;
    }

    public static function getCurrentLanguage()
    {
        return GeneralUtility::makeInstance(Context::class)->getAspect('language')->getId();
    }
    /**
     * Fetches the localization for a given record.
     *
     * @param string $table Table name present in $GLOBALS['TCA']
     * @param int $uid The uid of the record
     * @param int $language The uid of the language record in sys_language
     * @param string $andWhereClause Optional additional WHERE clause (default: '')
     * @return mixed Multidimensional array with selected records, empty array if none exists and FALSE if table is not localizable
     */
    public static function getRecordLocalization($table, $uid, $language, $andWhereClause = '')
    {
        $recordLocalization = false;

        if (self::isTableLocalizable($table)) {
            $tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];
            if (self::getBackendUserAuthentication()) {
                $workspace = self::getBackendUserAuthentication()->workspace ?? 0;
            } else {
                $workspace = 0;
            }

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $workspace));

            $queryBuilder->select('*')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq(
                        $tcaCtrl['translationSource'] ?? $tcaCtrl['transOrigPointerField'],
                        $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        $tcaCtrl['languageField'],
                        $queryBuilder->createNamedParameter((int)$language, Connection::PARAM_INT)
                    )
                )
                ->setMaxResults(1);

            if ($andWhereClause) {
                $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($andWhereClause));
            }

            $recordLocalization = $queryBuilder->executeQuery()->fetchAllAssociative();
        }

        return $recordLocalization;
    }

    /**
     * Determines whether a table is localizable and has the languageField and transOrigPointerField set in $GLOBALS['TCA'].
     *
     * @param string $table The table to check
     * @return bool Whether a table is localizable
     */
    public static function isTableLocalizable($table): bool
    {
        $isLocalizable = false;
        if (isset($GLOBALS['TCA'][$table]['ctrl']) && is_array($GLOBALS['TCA'][$table]['ctrl'])) {
            $tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];
            $isLocalizable = isset($tcaCtrl['languageField']) && $tcaCtrl['languageField'] && isset($tcaCtrl['transOrigPointerField']) && $tcaCtrl['transOrigPointerField'];
        }
        return $isLocalizable;
    }
}
