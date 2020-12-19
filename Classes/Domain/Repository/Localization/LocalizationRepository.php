<?php
declare(strict_types = 1);
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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for record localizations
 */
class LocalizationRepository extends \TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository
{
    /**
     * Fetch all available languages
     *
     * @param int $pageId
     * @return array
     */
    public function fetchRecordLocalizations(string $table, int $uid): array
    {
        $result = [];

        if (BackendUtility::isTableLocalizable($table)) {
            $tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];

            $queryBuilder = $this->getQueryBuilderWithWorkspaceRestriction($table);

            $constraints = [
                $queryBuilder->expr()->eq(
                    $table . '.' . $tcaCtrl['languageField'],
                    $queryBuilder->quoteIdentifier('sys_language.uid')
                ),
                $queryBuilder->expr()->eq(
                    $table . '.' . $tcaCtrl['origUid'],
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            ];
            $constraints += $this->getAllowedLanguageConstraintsForBackendUser();

            $queryBuilder->select($table . '.*')
                ->from($table)
                ->from('sys_language')
                ->where(...$constraints)
                ->groupBy('sys_language.uid', 'sys_language.sorting')
                ->orderBy('sys_language.sorting');

            $result = $queryBuilder->execute()->fetchAll();
        }

        return $result;
    }
}
