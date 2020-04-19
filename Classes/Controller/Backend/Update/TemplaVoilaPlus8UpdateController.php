<?php
namespace Ppi\TemplaVoilaPlus\Controller\Backend\Update;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller to migrate/update from TV+ 7 to TV+ 8
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class TemplaVoilaPlus8UpdateController extends StepUpdateController
{
    protected $errors = [];

    /**
     * Introduction
     */
    protected function stepStart()
    {
        // Check sys_registry if we already run?
        // Not realy, this must be done in the SwitchUpdateController who will disable our button
        // So we could provide a way to start this task again.
    }

    /**
     * Static DS or Database
     */
    protected function step1()
    {
        $tableDatastructureFound = $this->doesTableExists('tx_templavoilaplus_datastructure');
        $tableTemplateFound = $this->doesTableExists('tx_templavoilaplus_tmplobj');
        $columnPagesDatastructureFound = $this->doesColumnExists('pages', 'tx_templavoilaplus_ds');
        $columnPagesTemplateFound = $this->doesColumnExists('pages', 'tx_templavoilaplus_to');
        $columnContentDatastructureFound = $this->doesColumnExists('tt_content', 'tx_templavoilaplus_ds');
        $columnContentTemplateFound = $this->doesColumnExists('tt_content', 'tx_templavoilaplus_to');

        $allDatabaseElementsFound = $tableDatastructureFound && $tableTemplateFound && $columnPagesDatastructureFound && $columnPagesTemplateFound && $columnContentDatastructureFound && $columnContentTemplateFound;

        // Check for configuration staticDS = 1 and content of the configured paths
        // Check for storage_pid's to determine how much extensions we need to generate and/or need mapping into Site Management
        // Check database if the found ds/to are in usage, give the possibility to delete them?
        $this->fluid->assignMultiple([
            'allDatabaseElementsFound' => $allDatabaseElementsFound,
        ]);
    }

    protected function doesTableExists(string $tablename): bool
    {
        $tableExists = false;
        $columns = $this->getColumnsFromTable($tablename);

        if (count($columns) !== 0) {
            $tableExists = true;
        }

        return $tableExists;
    }

    protected function doesColumnExists(string $tablename, string $columnName): bool
    {
        $columnExists = false;
        $columns = $this->getColumnsFromTable($tablename);

        if (isset($columns[$columnName])) {
            $columnExists = true;
        }

        return $columnExists;
    }

    protected function getColumnsFromTable(string $tablename): array
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($tablename)
            ->getSchemaManager()
            ->listTableColumns($tablename);
    }

    /**
     * Find extension names / possible theme extensions / create own theme extension
     */
    protected function step2()
    {
        // Check extension names and provide form to create new extension or better
    }

    /**
     * Build new extension (or replace existing one) or multiple for multiple designs
     * Or add them to Site Management directories (if support is implemented)
     * The place may depend if you use composer installed TYPO3 or package based TYPO3
     */
    protected function step3()
    {
        // Create files and folders
    }

    /**
     * Register the generated extensions
     * Update the map field with the configuration (depending on ds/to)
     */
    protected function step4()
    {
        // Register extensions
        // Update pages
        // Update tt_content
    }

    protected function stepFinal()
    {
        // Write into sys_registry
    }

    protected function stepTODO()
    {
        $this->fluid->assignMultiple([
            'storagePidConversationNeeded' => $this->storagePidConversationNeeded(),
        ]);
    }

    protected function storagePidConversationNeeded(): bool
    {
        $columns = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages')
            ->getSchemaManager()
            ->listTableColumns('pages');

        if (isset($columns['storage_pid'])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages');
            $queryBuilder
                ->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $count = $queryBuilder
                ->count('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->gt('storage_pid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
                )
                ->execute()
                ->fetchColumn(0);

            if ($count) {
                return true;
            }
        }

        return false;
    }
}