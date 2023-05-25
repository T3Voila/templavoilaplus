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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
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

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, self::getBackendUserAuthentication()->workspace ?? 0));

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
            $l10nRecord = BackendUtility::getRecordLocalization($table, $uid, $language)[0] ?? null;
            // sadly $l10nRecord doesn't allow additionalWhere, so we check for hidden afterwards
            if ($l10nRecord && $l10nRecord[$hiddenField] === 0) {
                return $l10nRecord;
            }
        }
        return $baseRecord;
    }

    public static function getCurrentLanguage()
    {
        if (version_compare(TYPO3_version, '9.4.0', '<=')) {
            return $GLOBALS['TSFE']->sys_language_uid;
        }
        return GeneralUtility::makeInstance(Context::class)->getAspect('language')->getId();
    }
}
