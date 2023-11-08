<?php

namespace Tvp\TemplaVoilaPlus\Updates;

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

use Tvp\TemplaVoilaPlus\Service\ConfigurationService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Contains the update class for filling the basic repository record of the extension manager
 */
class Typo3Lts9Update implements UpgradeWizardInterface
{
    /**
     * holds the extconf configuration
     *
     * @var array
     */
    protected $extConfig = [];

    public function __construct()
    {
        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $this->extConfig = $configurationService->getExtensionConfig();
    }

    public function getIdentifier(): string
    {
        return self::class;
    }

    public function getTitle(): string
    {
        return 'Updates TemplaVoilà! Plus for using with TYPO3 v9 LTS or newer';
    }

    public function updateNecessary(): bool
    {
        $result = false;

        if ($this->extConfig['staticDS']['enable']) {
            // If static DS is in use we need to migrate the file pointer
            $result = true;
        }
        $result = true;

        return $result;
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function getDescription(): string
    {
        return 'This fixes staticDS entries so follow up upgrade functions of TYPO3 core can work.';
    }

    /**
     * Performs the database update.
     *
     * @return bool Whether it worked (TRUE) or not (FALSE)
     */
    public function executeUpdate(): bool
    {
        if ($this->extConfig['staticDS']['enable']) {
            $this->migrateStaticDsFilePointer();
        }

        return true;
    }

    protected function migrateStaticDsFilePointer()
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        // pages
        $queryBuilder = $connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder->select('tx_templavoilaplus_ds', 'tx_templavoilaplus_next_ds')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->neq(
                        'tx_templavoilaplus_ds',
                        $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->neq(
                        'tx_templavoilaplus_next_ds',
                        $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)
                    )
                )
            )
            ->groupBy('tx_templavoilaplus_ds', 'tx_templavoilaplus_next_ds')
            ->execute();

        $toFix = [];
        while ($row = $statement->fetch()) {
            if (
                !empty($row['tx_templavoilaplus_ds'])
                && !isset($toFix[$row['tx_templavoilaplus_ds']])
                && !StringUtility::beginsWith($row['tx_templavoilaplus_ds'], 'FILE:')
            ) {
                $toFix[$row['tx_templavoilaplus_ds']] = 'FILE:' . $row['tx_templavoilaplus_ds'];
            }
            if (
                !empty($row['tx_templavoilaplus_next_ds'])
                && !isset($toFix[$row['tx_templavoilaplus_next_ds']])
                && !StringUtility::beginsWith($row['tx_templavoilaplus_next_ds'], 'FILE:')
            ) {
                $toFix[$row['tx_templavoilaplus_next_ds']] = 'FILE:' . $row['tx_templavoilaplus_next_ds'];
            }
        }

        foreach ($toFix as $from => $to) {
            $queryBuilder = $connectionPool->getQueryBuilderForTable('pages');
            $queryBuilder->update('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        'tx_templavoilaplus_ds',
                        $queryBuilder->createNamedParameter($from, \PDO::PARAM_STR)
                    )
                )
                ->set('tx_templavoilaplus_ds', $to);

            $queryBuilder->execute();

            $queryBuilder = $connectionPool->getQueryBuilderForTable('pages');
            $queryBuilder->update('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        'tx_templavoilaplus_next_ds',
                        $queryBuilder->createNamedParameter($from, \PDO::PARAM_STR)
                    )
                )
                ->set('tx_templavoilaplus_next_ds', $to);

            $queryBuilder->execute();
        }

        // tt_content
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder->select('tx_templavoilaplus_ds')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->neq(
                    'tx_templavoilaplus_ds',
                    $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)
                )
            )
            ->execute();

        $toFix = [];
        while ($row = $statement->fetch()) {
            if (
                !empty($row['tx_templavoilaplus_ds'])
                && !isset($toFix[$row['tx_templavoilaplus_ds']])
                && !StringUtility::beginsWith($row['tx_templavoilaplus_ds'], 'FILE:')
            ) {
                $toFix[$row['tx_templavoilaplus_ds']] = 'FILE:' . $row['tx_templavoilaplus_ds'];
            }
        }

        foreach ($toFix as $from => $to) {
            $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
            $queryBuilder->update('tt_content')
                ->where(
                    $queryBuilder->expr()->eq(
                        'tx_templavoilaplus_ds',
                        $queryBuilder->createNamedParameter($from, \PDO::PARAM_STR)
                    )
                )
                ->set('tx_templavoilaplus_ds', $to);

            $queryBuilder->execute();
        }
    }
}
