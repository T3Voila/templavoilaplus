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

        $allOldDatabaseElementsFound = $tableDatastructureFound && $tableTemplateFound && $columnPagesDatastructureFound && $columnPagesTemplateFound && $columnContentDatastructureFound && $columnContentTemplateFound;

        $columnPagesMapFound = $this->doesColumnExists('pages', 'tx_templavoilaplus_map');
        $columnContentMapFound = $this->doesColumnExists('tt_content', 'tx_templavoilaplus_map');

        $allNewDatabaseElementsFound = $columnPagesMapFound && $columnContentMapFound;


        // Check for storage_pid's to determine how much extensions we need to generate and/or need mapping into Site Management
        $storagePidsAreFine = false;

        $allPossiblePids = $this->getAllPossibleStoragePidsFromTmplobj();
        if (!isset($allPossiblePids[-1])) {
            $storagePidsAreFine = true;
        }

        // Search for all DS configurations
        $useStaticDS = false;
        if ($this->extConf['staticDS']['enable'])
        {
            // Load all DS from path
            $useStaticDS = true;
            $allDs = $this->getAllDsFromStatic();
        } else {
            // Load DS from DB
            /** @TODO Implement */
        }

        // Search for all TO configurations
        $allTo = $this->getAllToFromDB();

        $allDsToValid = false;
        list($validationErrors, $validatedDs, $validatedToWithDs) = $this->checkAllDsToValid($allDs, $allTo);
        if (count($validationErrors) === 0) {
            $allDsToValid = true;
        }


        // Check database if the found ds/to are in usage, give the possibility to delete them?

        $allChecksAreFine = $allOldDatabaseElementsFound && $allNewDatabaseElementsFound && $storagePidsAreFine && $allDsToValid;

        $this->fluid->assignMultiple([
            'allOldDatabaseElementsFound' => $allOldDatabaseElementsFound,
            'allNewDatabaseElementsFound' => $allNewDatabaseElementsFound,
            'storagePidsAreFine' => $storagePidsAreFine,
            'useStaticDS' => $useStaticDS,
            'allDsToValid' => $allDsToValid,
            'validationErrors' => $validationErrors,
            'validatedDs' => $validatedDs,
            'validatedToWithDs' => $validatedToWithDs,
            'allChecksAreFine' => $allChecksAreFine,
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

    protected function getAllDsFromStatic(): array
    {
        $allDs = [];

        $systemPath = $this->getSystemPath();

        $paths = array_unique(array('fce' => $this->extConf['staticDS']['path_fce'], 'page' => $this->extConf['staticDS']['path_page']));
        foreach ($paths as $type => $path) {
            $absolutePath = GeneralUtility::getFileAbsFileName($path);
            $files = GeneralUtility::getFilesInDir($absolutePath, 'xml', true);
            // if all files are in the same folder, don't resolve the scope by path type
            if (count($paths) == 1) {
                $type = false;
            }
            foreach ($files as $filePath) {
                $staticDataStructure = [];
                $pathInfo = pathinfo($filePath);

                $staticDataStructure['title'] = $pathInfo['filename'];
                $staticDataStructure['path'] = substr($filePath, strlen($systemPath));
                $iconPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.gif';
                if (file_exists($iconPath)) {
                    $staticDataStructure['icon'] = substr($iconPath, strlen($systemPath));
                }

                if (($type !== false && $type === 'fce') || strpos($pathInfo['filename'], '(fce)') !== false) {
                    $staticDataStructure['scope'] = \Ppi\TemplaVoilaPlus\Domain\Model\Scope::SCOPE_FCE;
                } else {
                    $staticDataStructure['scope'] = \Ppi\TemplaVoilaPlus\Domain\Model\Scope::SCOPE_PAGE;
                }

                $allDs[] = $staticDataStructure;
            }
        }
        return $allDs;
    }

    protected function getAllToFromDB(): array
    {
        $allTo = [];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_templavoilaplus_tmplobj');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $result = $queryBuilder
            ->select('*')
            ->from('tx_templavoilaplus_tmplobj')
            ->orderBy('pid')
            ->execute()
            ->fetchAll();

        return $result;
    }

    protected function checkAllDsToValid(array $allDs, array $allTo): array
    {
        $validatedToWithDs = [];
        $validatedDs = [];
        $validationErrors = [];

        $systemPath = $this->getSystemPath();

        libxml_use_internal_errors(true);

        foreach ($allDs as $ds) {
            /** @TODO Implement NonStaticDs */
            if (is_file($systemPath . $ds['path']) && is_readable($systemPath . $ds['path'])) {
                $ds['haveTo'] = 0;
                $ds['valid'] = false;
                $result = simplexml_load_file($systemPath . $ds['path']);
                if ($result === false) {
                    $errors = libxml_get_errors();
                    $validationErrors[] = 'Cannot verify XML of DS with title "' . $ds['title'] . '" Error is: ' . reset($errors)->message;
                } else {
                    $ds['valid'] = true;
                }
            } else {
                $validationErrors[] = 'Cannot verify DS with title "' . $ds['title'] . '", as file "' . $ds['path'] . '" could not be found or isn\'t readable';
            }
            $validatedDs[$ds['path']] = $ds;
        }

        foreach ($allTo as $to) {
            $to['valid'] = false;
            if (
                (!empty($to['datastructure']) && isset($validatedDs[$to['datastructure']]))
                || $to['parent'] > 0
            ) {
                if ($to['parent'] === 0) {
                    $validatedDs[$to['datastructure']]['haveTo']++;
                }

                $templatefile = GeneralUtility::getFileAbsFileName($to['fileref']);
                if (is_file($templatefile) && is_readable($templatefile)) {
                    if (!empty($to['templatemapping']) && !is_array(unserialize($to['templatemapping']))) {
                        $validationErrors[] = 'Cannot verify TO with title "' . $to['title'] . '" and uid "' . $to['uid'] . '", as mapping is broken.';
                    } else {
                        $to['valid'] = true;
                        $to['DS'] = $validatedDs[$to['datastructure']]; /** @TODO If parent then from parent! Check if parent exists */
                    }
                } else {
                    $validationErrors[] = 'Cannot verify TO with title "' . $to['title'] . '" and uid "' . $to['uid'] . '", as template file "' . $to['fileref'] . '" could not be found.';
                }
            } else {
                $validationErrors[] = 'Cannot verify TO with title "' . $to['title'] . '" and uid "' . $to['uid'] . '", as DS could not be found.';
            }
            $validatedToWithDs[$to['uid']] = $to;
        }

        foreach ($validatedToWithDs as $key => $validTo) {
            if ($validTo['parent'] > 0) {
                if (isset($validatedToWithDs[$validTo['parent']])) {
                    $validatedToWithDs[$validTo['parent']]['childTO'][] = $validTo;
                    unset($validatedToWithDs[$key]);
                } else {
                    $validatedToWithDs[$key]['valid'] = false;
                    $validationErrors[] = 'Cannot verify TO with title "' . $validTo['title'] . '" and uid "' . $validTo['uid'] . '", as parent Template Object could not be found.';
                }
            }
        }

        foreach ($validatedDs as $key => $ds) {
            if ($ds['haveTo'] === 0) {
                $validatedDs[$key]['valid'] = false;
                $validationErrors[] = 'Cannot verify DS with title "' . $ds['title'] . '" it has no Template Object data';
            }
        }

        return [$validationErrors, $validatedDs, $validatedToWithDs];
    }

    protected function getSystemPath(): string
    {
        $systemPath = '/';

        if (version_compare(TYPO3_version, '9.2.0', '>=')) {
            $systemPath = \TYPO3\CMS\Core\Core\Environment::getPublicPath();
        } else {
            $systemPath = rtrim(PATH_site, '/');
        }
        return $systemPath . '/';
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

    protected function getAllPossibleStoragePidsFromTmplobj(): array
    {
        $foundPids = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_templavoilaplus_tmplobj');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $result = $queryBuilder
            ->select('pid')
            ->from('tx_templavoilaplus_tmplobj')
            ->groupBy('pid')
            ->orderBy('pid')
            ->execute()
            ->fetchAll();

        foreach($result as $row) {
            $foundPids[$row['pid']] = $row['pid'];
        }

        return $foundPids;
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
