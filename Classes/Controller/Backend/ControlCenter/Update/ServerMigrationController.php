<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\Update;

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

use Tvp\TemplaVoilaPlus\Utility\DataStructureUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller to migrate a server system on which the theme extension was deployed
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class ServerMigrationController extends AbstractUpdateController
{
    protected $errors = [];

    /**
     * Introduction
     */
    protected function stepStartAction()
    {
        $tableDatastructureFound = $this->doesTableExists('tx_templavoilaplus_datastructure');
        $tableTemplateFound = $this->doesTableExists('tx_templavoilaplus_tmplobj');
        $columnPagesDatastructureFound = $this->doesColumnExists('pages', 'tx_templavoilaplus_ds');
        $columnPagesDatastructureNextFound = $this->doesColumnExists('pages', 'tx_templavoilaplus_next_ds');
        $columnPagesTemplateFound = $this->doesColumnExists('pages', 'tx_templavoilaplus_to');
        $columnPagesTemplateNextFound = $this->doesColumnExists('pages', 'tx_templavoilaplus_next_to');
        $columnContentDatastructureFound = $this->doesColumnExists('tt_content', 'tx_templavoilaplus_ds');
        $columnContentTemplateFound = $this->doesColumnExists('tt_content', 'tx_templavoilaplus_to');

        $allOldDatabaseElementsFound = $tableDatastructureFound && $tableTemplateFound
            && $columnPagesDatastructureFound && $columnPagesDatastructureNextFound
            && $columnPagesTemplateFound && $columnPagesTemplateNextFound
            && $columnContentDatastructureFound && $columnContentTemplateFound;

        $columnPagesMapFound = $this->doesColumnExists('pages', 'tx_templavoilaplus_map');
        $columnPagesMapNextFound = $this->doesColumnExists('pages', 'tx_templavoilaplus_next_map');
        $columnContentMapFound = $this->doesColumnExists('tt_content', 'tx_templavoilaplus_map');

        $allNewDatabaseElementsFound = $columnPagesMapFound && $columnContentMapFound && $columnPagesMapNextFound;
        $covertingInstructions = $this->loadServerMigrationFile();

        $allChecksAreFine = $allOldDatabaseElementsFound && $allNewDatabaseElementsFound && !empty($covertingInstructions);

        $this->view->assignMultiple([
            'allOldDatabaseElementsFound' => $allOldDatabaseElementsFound,
            'allNewDatabaseElementsFound' => $allNewDatabaseElementsFound,
            'convertInstructionsLoaded' => !empty($covertingInstructions),
            'allChecksAreFine' => $allChecksAreFine,
            'covertingInstructions' => $covertingInstructions,
            'covertingInstructionsJson' => json_encode($covertingInstructions),
        ]);
    }

    protected function loadServerMigrationFile(): array
    {
        $registeredExtensions = \Tvp\TemplaVoilaPlus\Utility\ExtensionUtility::getRegisteredExtensions();
        foreach ($registeredExtensions as $extensionKey => $path) {
            if (is_file($path . '/ServerMigration.json')) {
                $migrationDataJson = file_get_contents($path . '/ServerMigration.json');
                // Remove lines starting with #
                $migrationDataJson = preg_replace('/^#.*$/m', '', $migrationDataJson);
                $migrationData = json_decode($migrationDataJson, true);
                return (is_array($migrationData) ? $migrationData : []);
            }
        }
        return [];
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
}
