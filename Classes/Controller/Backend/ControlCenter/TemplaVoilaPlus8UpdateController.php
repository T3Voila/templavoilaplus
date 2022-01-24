<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter;

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
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Tvp\TemplaVoilaPlus\Utility\DataStructureUtility;

/**
 * Controller to migrate/update from TV+ 7 to TV+ 8
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class TemplaVoilaPlus8UpdateController extends AbstractUpdateController
{
    protected $errors = [];

    /**
     * Introduction
     */
    protected function stepStartAction()
    {
        // Check sys_registry if we already run?
        // Not realy, this must be done in the SwitchUpdateController who will disable our button
        // So we could provide a way to start this task again.
    }

    /**
     * Static DS or Database
     */
    protected function step1Action()
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


        // Check for storage_pid's to determine how much extensions we need to generate and/or need mapping into
        // Site Management
        $storagePidsAreFine = false;
        $useStaticDS = false;

        $allPossiblePids = $allDs = $allTo = [];

        if ($allOldDatabaseElementsFound) {
            $allPossiblePids = $this->getAllPossibleStoragePidsFromTmplobj();

            if (!isset($allPossiblePids[-1])) {
                $storagePidsAreFine = true;
            }

            // Search for all DS configurations
            $useStaticDS = $this->getUseStaticDs();
            $allDs = $this->getAllDs($useStaticDS);

            // Search for all TO configurations
            $allTo = $this->getAllToFromDB();
        }

        $allDsToValid = false;
        list($validationDsToErrors, $validatedDs, $validatedToWithDs) = $this->checkAllDsToValid($allDs, $allTo);
        if (count($validationDsToErrors) === 0) {
            $allDsToValid = true;
        }

        // Check database if the found ds/to are in usage, give the possibility to delete them?
        $allPagesContentValid = false;
        list($validationPagesContentErrors, $validatedToWithDs) = $this->checkAllPageContentForTo($validatedToWithDs);
        if (count($validationPagesContentErrors) === 0) {
            $allPagesContentValid = true;
        }

        $allChecksAreFine = $allOldDatabaseElementsFound && $allNewDatabaseElementsFound && $storagePidsAreFine && $allDsToValid && $allPagesContentValid;


        $indentation = 0;
        if (isset($this->extConf['ds']['indentation'])) {
            $indentation = (int)$this->extConf['ds']['indentation'];
        }

        $this->view->assignMultiple([
            'allOldDatabaseElementsFound' => $allOldDatabaseElementsFound,
            'allNewDatabaseElementsFound' => $allNewDatabaseElementsFound,
            'storagePidsAreFine' => $storagePidsAreFine,
            'useStaticDS' => $useStaticDS,
            'staticDsInExtension' => (bool) (isset($GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoilaplus_cm1']['staticDataStructures']) || isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['staticDataStructures'])),
            'staticDsPaths' => implode(', ', $this->getStaticDsPaths()),
            'allDsToValid' => $allDsToValid,
            'validationDsToErrors' => $validationDsToErrors,
            'validatedDs' => $validatedDs,
            'validatedToWithDs' => $validatedToWithDs,
            'allPagesContentValid' => $allPagesContentValid,
            'validationPagesContentErrors' => $validationPagesContentErrors,
            'allChecksAreFine' => $allChecksAreFine,
            'indentation' => $indentation,
        ]);
    }

    protected function getUseStaticDs(): bool
    {
        if ($this->extConf['staticDS']['enable']) {
            return true;
        }
        return false;
    }

    protected function getStaticDsPaths(): array
    {
        return array_unique([
            'fce' => $this->extConf['staticDS']['path_fce'],
            'page' => $this->extConf['staticDS']['path_page'],
        ]);
    }

    protected function getAllDs(): array
    {
        $allDs = [];
        if ($this->getUseStaticDs()) {
            // Load all DS from path
            $allDs = $this->getAllDsFromStatic();
        }

        // Load DS from DB
        $allDs = array_merge($allDs, $this->getAllDsFromDatabase());

        return $allDs;
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

        // Read config from "Template Extensions"
        if (
            isset($GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoilaplus_cm1']['staticDataStructures'])
            && is_array($GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoilaplus_cm1']['staticDataStructures'])
        ) {
            $allDs = $GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoilaplus_cm1']['staticDataStructures'];
        }

        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['staticDataStructures'])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['staticDataStructures'])
        ) {
            $allDs = array_merge($allDs, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['staticDataStructures']);
        }

        // Read XML data from "Template Extensions" config
        foreach ($allDs as $key => $dataStructure) {
            if (is_file($systemPath . $dataStructure['path']) && is_readable($systemPath . $dataStructure['path'])) {
                $allDs[$key]['xml'] = file_get_contents($systemPath . $dataStructure['path']);
            }
        }

        // Read files from defined static DataStructure paths
        $paths = $this->getStaticDsPaths();
        foreach ($paths as $type => $path) {
            $absolutePath = GeneralUtility::getFileAbsFileName($path);
            $files = GeneralUtility::getFilesInDir($absolutePath, 'xml', true);
            // if all files are in the same folder, don't resolve the scope by path type
            if (count($paths) == 1) {
                $type = false;
            }
            foreach ($files as $filePath) {
                $pathInfo = pathinfo($filePath);

                $dataStructure = [
                    'staticDS' => true,
                    'title' => $pathInfo['filename'],
                    'path' => substr($filePath, strlen($systemPath)),
                    'xml' => '',
                    'scope' => '',
                    'icon' => '',
                ];

                if (is_file($systemPath . $dataStructure['path']) && is_readable($systemPath . $dataStructure['path'])) {
                    $dataStructure['xml'] = file_get_contents($systemPath . $dataStructure['path']);
                }

                $iconPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.gif';
                if (file_exists($iconPath)) {
                    $dataStructure['icon'] = substr($iconPath, strlen($systemPath));
                }

                if (($type !== false && $type === 'fce') || strpos($pathInfo['filename'], '(fce)') !== false) {
                    $dataStructure['scope'] = \Tvp\TemplaVoilaPlus\Domain\Model\Scope::SCOPE_FCE;
                } else {
                    $dataStructure['scope'] = \Tvp\TemplaVoilaPlus\Domain\Model\Scope::SCOPE_PAGE;
                }

                $allDs[] = $dataStructure;
            }
        }

         return $allDs;
    }

    protected function getAllDsFromDatabase(): array
    {
        $allDs = [];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_templavoilaplus_datastructure');
        $queryBuilder
            ->getRestrictions()
            ->removeAll();

        $result = $queryBuilder
            ->select('*')
            ->from('tx_templavoilaplus_datastructure')
            ->where(
                $queryBuilder->expr()->neq('deleted', $queryBuilder->createNamedParameter(1, \PDO::PARAM_BOOL))
            )
            ->orderBy('pid')
            ->execute()
            ->fetchAll();

        foreach ($result as $row) {
            $dataStructure = [
                'staticDS' => false,
                'title' => $row['title'],
                'path' => (string) $row['uid'],
                'xml' => $row['dataprot'],
                'scope' => $row['scope'],
                'icon' => $row['previewicon'],
                'belayout' => $row['belayout'], // This should be in TO or is that only there for staticDS??
            ];

            $allDs[] = $dataStructure;
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
            ->removeAll();

        $result = $queryBuilder
            ->select('*')
            ->from('tx_templavoilaplus_tmplobj')
            ->where(
                $queryBuilder->expr()->neq('deleted', $queryBuilder->createNamedParameter(1, \PDO::PARAM_BOOL))
            )
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

        libxml_use_internal_errors(true);

        foreach ($allDs as $ds) {
            $ds['countUsage'] = 0;
            $ds['valid'] = false;

            if (!empty($ds['xml'])) {
                $result = simplexml_load_string($ds['xml']);
                if ($result === false) {
                    $errors = libxml_get_errors();
                    $validationErrors[] = 'Cannot verify XML of DS with title "' . $ds['title'] . '" Error is: ' . reset($errors)->message;
                } else {
                    $ds['valid'] = true;
                }
            } else {
                $validationErrors[] = 'Cannot verify DS with title "' . $ds['title'] . '", as uid/file "' . $ds['path'] . '" could not be found or isn\'t readable';
            }
            $validatedDs[$ds['path']] = $ds;
        }

        foreach ($allTo as $to) {
            $to['countUsage'] = 0;
            $to['valid'] = false;

            if (
                (!empty($to['datastructure']) && isset($validatedDs[$to['datastructure']]))
                || $to['parent'] > 0
            ) {
                if ($to['parent'] === 0) {
                    $validatedDs[$to['datastructure']]['countUsage']++;
                }

                $templatefile = GeneralUtility::getFileAbsFileName($to['fileref']);
                if (is_file($templatefile) && is_readable($templatefile)) {
                    if (empty($to['templatemapping'])) {
                        $validationErrors[] = 'Cannot verify TO with title "' . $to['title'] . '" and uid "' . $to['uid'] . '", as mapping is broken.';
                    } else {
                        $mappingInformation = unserialize($to['templatemapping']);
                        if (isset($mappingInformation['MappingInfo']['ROOT'])) {
                            $to['valid'] = true;
                            $to['DS'] = $validatedDs[$to['datastructure']]; /** @TODO If parent then from parent! Check if parent exists */
                        } else {
                            $validationErrors[] = 'Cannot verify TO with title "' . $to['title'] . '" and uid "' . $to['uid'] . '", as mapping seams not existing.';
                        }
                    }
                } else {
                    $validationErrors[] = 'Cannot verify TO with title "' . $to['title'] . '" and uid "' . $to['uid'] . '", as template file "' . $to['fileref'] . '" could not be found.';
                }
            } else {
                $validationErrors[] = 'Cannot verify TO with title "' . $to['title'] . '" and uid "' . $to['uid'] . '", as DataStructure "' . $to['datastructure'] . '" could not be found.';
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
            if ($ds['countUsage'] === 0) {
                $validatedDs[$key]['valid'] = false;
                $validationErrors[] = 'Cannot verify DS with title "' . $ds['title'] . '" it has no Template Object data';
            }
        }

        return [$validationErrors, $validatedDs, $validatedToWithDs];
    }

    protected function checkAllPageContentForTo(array $validatedToWithDs): array
    {
        $validationErrors = [];

        if (count($validatedToWithDs) === 0) {
            return [$validationErrors, $validatedToWithDs];
        }

        // PAGES
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $result = $queryBuilder
            ->count('uid')
            ->addSelectLiteral('min(uid) as uid')
            ->addSelect('tx_templavoilaplus_to', 'tx_templavoilaplus_next_to')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->neq('tx_templavoilaplus_to', $queryBuilder->createNamedParameter('', \PDO::PARAM_STR))
            )
            ->orWhere(
                $queryBuilder->expr()->neq('tx_templavoilaplus_next_to', $queryBuilder->createNamedParameter('', \PDO::PARAM_STR))
            )
            ->groupBy('tx_templavoilaplus_to', 'tx_templavoilaplus_next_to')
            ->execute()
            ->fetchAll();

        foreach ($result as $row) {
            if ($row['tx_templavoilaplus_to'] != 0) {
                if (isset($validatedToWithDs[$row['tx_templavoilaplus_to']])) {
                    $validatedToWithDs[$row['tx_templavoilaplus_to']]['countUsage'] += $row['COUNT(`uid`)'];
                } else {
                    $validationErrors[] = 'There are pages which use an non existent Template Object with uid "' . $row['tx_templavoilaplus_to'] . '" like page with page uid: "' . $row['uid'] . '"';
                }
            }
            if ($row['tx_templavoilaplus_next_to'] != 0) {
                if (isset($validatedToWithDs[$row['tx_templavoilaplus_next_to']])) {
                    $validatedToWithDs[$row['tx_templavoilaplus_next_to']]['countUsage'] += $row['COUNT(`uid`)'];
                } else {
                    $validationErrors[] = 'There are pages which use an non existent Template Object with uid "' . $row['tx_templavoilaplus_next_to'] . '" for subpages like page with page uid: "' . $row['uid'] . '"';
                }
            }
        }

        // TT_CONTENT
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tt_content');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $result = $queryBuilder
            ->count('uid')
            ->addSelect('uid', 'tx_templavoilaplus_to')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->neq('tx_templavoilaplus_to', $queryBuilder->createNamedParameter('', \PDO::PARAM_STR))
            )
            ->groupBy('tx_templavoilaplus_to')
            ->execute()
            ->fetchAll();

        foreach ($result as $row) {
            if ($row['tx_templavoilaplus_to'] != 0) {
                if (isset($validatedToWithDs[$row['tx_templavoilaplus_to']])) {
                    $validatedToWithDs[$row['tx_templavoilaplus_to']]['countUsage'] += $row['COUNT(`uid`)'];
                } else {
                    $validationErrors[] = 'There are content elements which use an non existent Template Object with uid "' . $row['tx_templavoilaplus_to'] . '" like content element with uid: "' . $row['uid'] . '"';
                }
            }
        }

        return [$validationErrors, $validatedToWithDs];
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
    protected function step2Action()
    {
        $packagesQualified = [];
        $showAll = (bool) $_POST['showAll'];

        /** @var PackageManager */
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        $allAvailablePackages = $packageManager->getAvailablePackages();
        $activePackages = $packageManager->getActivePackages();
        $allTerExtensionKeys = $this->getAllTerExtensionKeys();

        ksort($allAvailablePackages);

        foreach ($allAvailablePackages as $key => $package) {
            $qualify = 0;
            $why = [];
            $active = (isset($activePackages[$key]) ? true : false);
            if ($active) {
                $qualify += 1;
            }

            if ($package->getValueFromComposerManifest('type') === 'typo3-cms-framework') {
                $qualify -= 100;
                $why[] = 'TYPO3 Core Framework';
            } elseif ($package->isProtected()) {
                $qualify -= 100;
                $why[] = 'Protected Package';
            } elseif (isset($allTerExtensionKeys[$key])) {
                $qualify -= 10;
                $why[] = 'Existing TER Package';
            } elseif (!is_dir($package->getPackagePath()) || !is_writeable($package->getPackagePath())) {
                $qualify -= 100;
                $why[] = 'Path not writable';
            } elseif (file_exists($package->getPackagePath() . '/Configuration/TVP')) {
                // Already includes TVP configuration so maybe possible theme/config extension
                $qualify += 10;
            }
            if (stripos($package->getPackageKey(), 'config') || stripos($package->getPackageMetaData()->getDescription(), 'config')) {
                $qualify += 10;
            }
            if (stripos($package->getPackageKey(), 'theme') || stripos($package->getPackageMetaData()->getDescription(), 'theme')) {
                $qualify += 10;
            }

            if ($showAll || $qualify >= 0) {
                $packagesQualified[$key] = [
                    'package' => $package,
                    'active' => $active,
                    'qualify' => $qualify,
                    'why' => implode(', ', $why),
                ];
            }
        }

        $this->view->assignMultiple([
            'terListHint' => (count($allTerExtensionKeys) === 0 ? true : false),
            'packagesQualified' => $packagesQualified,
        ]);
    }

    protected function getAllTerExtensionKeys(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_extensionmanager_domain_model_extension');

        $allTerExtensions = $queryBuilder
            ->select('extension_key')
            ->from('tx_extensionmanager_domain_model_extension')
            ->where($queryBuilder->expr()->eq(
                'current_version',
                $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
            ))
            ->execute()
            ->fetchAll();

        return array_column($allTerExtensions, 'extension_key', 'extension_key');
    }

    /**
     * Validate an existing extension (writable, already theme extension, overwrite or add)
     * or verify extension key and collect information for the new Extension
     */
    protected function step3Action()
    {
        $selection = $_POST['selection'];

        if ($selection === '_new_') {
            $this->forward('step3NewExtension');
        }
        if (!empty($selection)) {
            $this->forward('step3ExistingExtension');
        }

        $this->forward('step2'); // Return to step 2
    }

    protected function step3NewExtensionAction()
    {
        $errors = [];

        /** @var PackageManager */
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        $allAvailablePackages = $packageManager->getAvailablePackages();
        $allTerExtensionKeys = $this->getAllTerExtensionKeys();

        // check extensionKey
        $newExtensionKey = strtolower($_POST['newExtensionKey']);
        $selection = $_POST['selection'];
        $vendorName = $_POST['vendorName'];
        $extensionName = $_POST['extensionName'];
        $author = $_POST['author'];
        $authorCompany = $_POST['authorCompany'];

        $possibleValues = explode('_', $newExtensionKey);

        // Try to determine possible values
        if (count($possibleValues) > 1) {
            $possibleVendorName = array_shift($possibleValues);
            if (strlen($possibleVendorName) < 4) {
                $possibleVendorName = strtoupper($possibleVendorName);
            } else {
                $possibleVendorName = ucfirst($possibleVendorName);
            }
        }

        $possibleExtensionName = implode(' ', array_map('ucfirst', $possibleValues));
        if (empty($vendorName) && !empty($possibleVendorName)) {
            $vendorName = $possibleVendorName;
        }
        if (empty($extensionName) && !empty($possibleExtensionName)) {
            $extensionName = $possibleExtensionName;
        }

        // Source taken from ExtensionValidator from the TYPO3 extension extension_builder
        /*
         * Character test
         * Allowed characters are: a-z (lowercase), 0-9 and '_' (underscore)
         */
        if (!preg_match('/^[a-z0-9_]*$/', $newExtensionKey)) {
            $errors[] = 'Allowed characters are: a-z (lowercase), 0-9 and \'_\' (underscore)';
        }

        /*
         * Start character
         * Extension keys cannot start or end with 0-9 and '_' (underscore)
         */
        if (preg_match('/^[0-9_]/', $newExtensionKey)) {
            $errors[] = 'Extension keys cannot start or end with 0-9 and "_" (underscore)';
        }

        /*
         * Extension key length
         * An extension key must have minimum 3, maximum 30 characters (not counting underscores)
         */
        $keyLengthTest = str_replace('_', '', $newExtensionKey);
        if (strlen($keyLengthTest) < 3 || strlen($keyLengthTest) > 30) {
            $errors[] = 'An extension key must have minimum 3, maximum 30 characters (not counting underscores)';
        }

        /*
         * Reserved prefixes
         * The key must not being with one of the following prefixes: tx,pages,tt_,sys_,ts_language_,csh_
         */
        if (preg_match('/^(tx|pages_|tt_|sys_|ts_language_|csh_)/', $newExtensionKey)) {
            $errors[] = 'The key must not being with one of the following prefixes: tx,pages,tt_,sys_,ts_language_,csh_';
        }

        if (isset($allTerExtensionKeys[$newExtensionKey])) {
            $errors[] = 'Do not use an extension name from the TER list';
        }

        if (isset($allAvailablePackages[$newExtensionKey])) {
            $errors[] = 'Extension already exists on system, select it in step 2 directly';
        }

        $this->view->assignMultiple([
            'terListHint' => (count($allTerExtensionKeys) === 0 ? true : false),
            'errors' => $errors,
            'hasError' => (count($errors) ? true : false),
            'newExtensionKey' => $newExtensionKey,
            'selection' => $selection,
            'vendorName' => $vendorName,
            'extensionName' => $extensionName,
            'author' => $author,
            'authorCompany' => $authorCompany,
        ]);
    }

    protected function step3ExistingExtensionAction()
    {
        $errors = [];

        $selection = $_POST['selection'];

        $errors[] = 'Using an existing extension isn\'t supported yet.';

        $this->view->assignMultiple([
            'errors' => $errors,
            'hasError' => (count($errors) ? true : false),
            'selection' => $selection,
        ]);
    }

    /**
     * Build new extension (or replace existing one) or multiple for multiple designs
     * Or add them to Site Management directories (if support is implemented)
     * The place may depend if you use composer installed TYPO3 or package based TYPO3
     */
    protected function step4Action()
    {
        $errors = [];

        /** @var PackageManager */
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);

        $selection = $_POST['selection'];
        $overwrite = $_POST['overwrite'];
        $newExtensionKey = '';

        try {
            // Create new extension directory and base extension files
            if ($selection === '_new_') {
                $newExtensionKey = $_POST['newExtensionKey'];
                $vendorName = $_POST['vendorName'] ?? 'MyVendor';
                $extensionName = $_POST['extensionName'] ?? 'MyName';
                $author = $_POST['author'];
                $authorCompany = $_POST['authorCompany'];

                $publicExtensionDirectory = $this->getPackagePaths($newExtensionKey);

                if (file_exists($publicExtensionDirectory)) {
                    if ($overwrite) {
                        // Cleanup from preview run?
                        if (!GeneralUtility::rmdir($publicExtensionDirectory, true)) {
                            throw new \Exception('Could not clean up extension path "' . $publicExtensionDirectory . '"');
                        }
                    } else {
                        throw new \Exception('Directory already exists with extension path "' . $publicExtensionDirectory . '"');
                    }
                }

                /** @TODO With TYPO3 v9 we could support composer pathes which gets symlinks */
                /** @See extension_builder */
                if (!GeneralUtility::mkdir($publicExtensionDirectory)) {
                    throw new \Exception('Could not create extension path "' . $publicExtensionDirectory . '"');
                }
                $fileDescription
                    = '/**' . "\n"
                    . '  * Autogenerated by TV+ Update Script' . "\n"
                    . '  */' . "\n";

                // Create ext_emconf.php
                $emConfConfig = [
                    'title' => $extensionName,
                    'description' => '',
                    'version' => '0.1.0',
                    'state' => 'alpha',
                    'author' => $author,
                    'author_email' => '',
                    'author_company' => $authorCompany,
                    'clearCacheOnLoad' => true,
                    'constraints' => [
                        'depends' => [
                            'typo3' => '8.7.0-10.4.99',
                            'templavoilaplus' => '8.0.0-8.99.99',
                        ],
                    ],
                ];
                $emConfContent = "<?php\n$fileDescription\n\$EM_CONF['$newExtensionKey'] = " . ArrayUtility::arrayExport($emConfConfig) . ";\n";
                GeneralUtility::writeFile($publicExtensionDirectory . '/ext_emconf.php', $emConfContent, true);

                $composerInfo = [
                    'name' => $vendorName . '/' . $newExtensionKey,
                    'type' => 'typo3-cms-extension',
                    'description' => 'My Theme Extension',
                    'require' => [
                        'typo3/cms-core' => '^8.7.0 || ^9.5.0 || ^10.4.0',
                        'templavoilaplus/templavoilaplus' => '~8.0.0',
                    ],
                    'replace' => [
                        $vendorName . '/' . $newExtensionKey => 'self.version',
                    ],
                ];

                if ($author) {
                    $composerInfo['authors'][] = [
                        'name' => $author,
                        'email' => '',
                        'role' => 'Developer',
                    ];
                }

                // Create composer.json
                GeneralUtility::writeFile($publicExtensionDirectory . '/composer.json', json_encode($composerInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

                // Create extension registration in ext_localconf.php
                /** @TODO Remove later */
                $extLocalconf = "<?php\n$fileDescription\ndefined('TYPO3_MODE') or die();\n\n// @TODO This line can be removed after cache is implemented\n\Tvp\TemplaVoilaPlus\Utility\ExtensionUtility::registerExtension('$newExtensionKey');";
                GeneralUtility::writeFile($publicExtensionDirectory . '/ext_localconf.php', $extLocalconf . "\n");

                // Load package by package manager
                if (!$packageManager->isPackageAvailable($newExtensionKey)) {
                    throw new \Exception('We couldn\'t register our new extension, but we don\'t know why.');
                }

                $selection = $newExtensionKey;
            }
            // Done creating new extension, following is like updating existing extension

            $publicExtensionDirectory = $this->getPackagePaths($selection);
            $package = $packageManager->getPackage($selection);

            /** @TODO UpperCamelCase conversion? */
            $packageName = $package->getValueFromComposerManifest('name');
            // Yes, we get no emConf information from package, so read it from emConf directly
            $packageTitle = $this->getPackageTitle($selection);

            $innerPathes = [
                'configuration' => '/Configuration/TVP',
                'ds' => [
                    'unknown' => '/Resources/Private/TVP/DataStructure/',
                    'page' => '/Resources/Private/TVP/DataStructure/Pages',
                    'fce' => '/Resources/Private/TVP/DataStructure/Fces',
                ],
                'mappingConfiguration' => [
                    'unknown' => '/Resources/Private/TVP/MappingConfiguration',
                    'page' => '/Resources/Private/TVP/MappingConfiguration/Pages',
                    'fce' => '/Resources/Private/TVP/MappingConfiguration/Fces',
                ],
                'templateConfiguration' => [
                    'unknown' => '/Resources/Private/TVP/TemplateConfiguration',
                    'page' => '/Resources/Private/TVP/TemplateConfiguration/Pages',
                    'fce' => '/Resources/Private/TVP/TemplateConfiguration/Fces',
                ],
                'templates' => '/Resources/Private/TVP/Template',
                'backendLayout' => '/Resources/Private/TVP/BackendLayout',
            ];

            // Create path if needed
            $this->createPaths($publicExtensionDirectory, $innerPathes);

            // Generate Place Configuration for DataStructure
            $dataStructurePlacesConfig = [
                $packageName . '/Page/DataStructure' => [
                    'name' => $packageTitle . ' Pages',
                    'path' => 'EXT:' . $selection . $innerPathes['ds']['page'],
                    'scope' => new UnquotedString(\Tvp\TemplaVoilaPlus\Domain\Model\Scope::class . '::SCOPE_PAGE'),
                    'loadSaveHandler' => new UnquotedString(\Tvp\TemplaVoilaPlus\Handler\LoadSave\XmlLoadSaveHandler::class . '::$identifier'),
                ],
                $packageName . '/FCE/DataStructure' => [
                    'name' => $packageTitle . ' FCEs',
                    'path' => 'EXT:' . $selection . $innerPathes['ds']['fce'],
                    'scope' => new UnquotedString(\Tvp\TemplaVoilaPlus\Domain\Model\Scope::class . '::SCOPE_FCE'),
                    'loadSaveHandler' => new UnquotedString(\Tvp\TemplaVoilaPlus\Handler\LoadSave\XmlLoadSaveHandler::class . '::$identifier'),
                ],
            ];

            // Generate Place Configuration for Mapping
            $mappingConfigurationPlacesConfig = [
                $packageName . '/Page/MappingConfiguration' => [
                    'name' => $packageTitle . ' Pages',
                    'path' => 'EXT:' . $selection . $innerPathes['mappingConfiguration']['page'],
                    'scope' => new UnquotedString(\Tvp\TemplaVoilaPlus\Domain\Model\Scope::class . '::SCOPE_PAGE'),
                    'loadSaveHandler' => new UnquotedString(\Tvp\TemplaVoilaPlus\Handler\LoadSave\YamlLoadSaveHandler::class . '::$identifier'),
                ],
                $packageName . '/FCE/MappingConfiguration' => [
                    'name' => $packageTitle . ' FCEs',
                    'path' => 'EXT:' . $selection . $innerPathes['mappingConfiguration']['fce'],
                    'scope' => new UnquotedString(\Tvp\TemplaVoilaPlus\Domain\Model\Scope::class . '::SCOPE_FCE'),
                    'loadSaveHandler' => new UnquotedString(\Tvp\TemplaVoilaPlus\Handler\LoadSave\YamlLoadSaveHandler::class . '::$identifier'),
                ],
            ];

            // Generate Place Configuration for Template
            $templateConfigurationPlacesConfig = [
                $packageName . '/Page/TemplateConfiguration' => [
                    'name' => $packageTitle . ' Pages',
                    'path' => 'EXT:' . $selection . $innerPathes['templateConfiguration']['page'],
                    'scope' => new UnquotedString(\Tvp\TemplaVoilaPlus\Domain\Model\Scope::class . '::SCOPE_PAGE'),
                    'loadSaveHandler' => new UnquotedString(\Tvp\TemplaVoilaPlus\Handler\LoadSave\YamlLoadSaveHandler::class . '::$identifier'),
                ],
                $packageName . '/FCE/TemplateConfiguration' => [
                    'name' => $packageTitle . ' FCEs',
                    'path' => 'EXT:' . $selection . $innerPathes['templateConfiguration']['fce'],
                    'scope' => new UnquotedString(\Tvp\TemplaVoilaPlus\Domain\Model\Scope::class . '::SCOPE_FCE'),
                    'loadSaveHandler' => new UnquotedString(\Tvp\TemplaVoilaPlus\Handler\LoadSave\YamlLoadSaveHandler::class . '::$identifier'),
                ],
            ];

            // Generate Place Configuration for BElayout
            $beLayoutConfigurationPlacesConfig = [
                $packageName . '/BackendLayoutConfiguration' => [
                    'name' => $packageTitle . ' BackendLayouts',
                    'path' => 'EXT:' . $selection . $innerPathes['backendLayout'],
                    'loadSaveHandler' => new UnquotedString(\Tvp\TemplaVoilaPlus\Handler\LoadSave\MarkerBasedFileLoadSaveHandler::class . '::$identifier'),
                ],
            ];

            // Create/Update Places configuration files
            $dataStructurePlaces = "<?php\ndeclare(strict_types=1);\n\nreturn " . $this->arrayExport($dataStructurePlacesConfig) . ";\n";
            GeneralUtility::writeFile($publicExtensionDirectory . $innerPathes['configuration'] . '/DataStructurePlaces.php', $dataStructurePlaces, true);

            $mappingConfigurationPlaces = "<?php\ndeclare(strict_types=1);\n\nreturn " . $this->arrayExport($mappingConfigurationPlacesConfig) . ";\n";
            GeneralUtility::writeFile($publicExtensionDirectory . $innerPathes['configuration'] . '/MappingPlaces.php', $mappingConfigurationPlaces, true);

            $templateConfigurationPlaces = "<?php\ndeclare(strict_types=1);\n\nreturn " . $this->arrayExport($templateConfigurationPlacesConfig) . ";\n";
            GeneralUtility::writeFile($publicExtensionDirectory . $innerPathes['configuration'] . '/TemplatePlaces.php', $templateConfigurationPlaces, true);

            $beLayoutConfigurationPlaces = "<?php\ndeclare(strict_types=1);\n\nreturn " . $this->arrayExport($beLayoutConfigurationPlacesConfig) . ";\n";
            GeneralUtility::writeFile($publicExtensionDirectory . $innerPathes['configuration'] . '/BackendLayoutPlaces.php', $beLayoutConfigurationPlaces, true);

            $ds = $this->getAllDs();
            /** @TODO Support for multiple storage_pids */
            $to = $this->getAllToFromDB();

            // Read old data, convert and write to new places
            $covertingInstructions = $this->convertAllDsTo($ds, $to, $packageName, $publicExtensionDirectory, $innerPathes);
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }
        $this->view->assignMultiple([
            'errors' => $errors,
            'hasError' => (count($errors) ? true : false),
            'newExtensionKey' => $newExtensionKey,
            'selection' => $selection,
            'vendorName' => $vendorName,
            'extensionName' => $extensionName,
            'author' => $author,
            'authorCompany' => $authorCompany,
            'covertingInstructions' => $covertingInstructions,
            'covertingInstructionsJson' => json_encode($covertingInstructions),
        ]);
    }

    protected function convertAllDsTo(array $allDs, array $allTos, string $packageName, string $publicExtensionDirectory, array $innerPathes): array
    {
        $systemPath = $this->getSystemPath();
        $covertingInstructions = [];
        $copiedTemplateFiles = [];
        $copiedBackendLayoutFiles = [];
        $convertedDS = [];
        $filenameUsed = [];

        /** Move childTemplates/rendertypes into their parents as child */
        // Step 1: filter out all childTos
        $childTos = [];
        foreach ($allTos as $key => $to) {
            if ($to['parent'] > 0) {
                $childTos[$to['parent']][] = $to;
                unset($allTos[$key]);
            }
        }
        // Step 2: Put them onto the parents
        if (count($childTos)) {
            foreach ($allTos as $key => $to) {
                if (isset($childTos[$to['uid']])) {
                    $allTos[$key]['childTO'] = $childTos[$to['uid']];
                }
            }
        }

        // Change the logic the other way arround, we need to itterate over the TOs
        // and then convert the dependend DS files as we need their data for the mappings
        foreach ($allTos as $to) {
            $resultingFileName = $this->copyFile($to['fileref'], $copiedTemplateFiles, $publicExtensionDirectory, $innerPathes['templates']);

            $yamlFileName = pathinfo($resultingFileName, PATHINFO_FILENAME) . '.tvp.yaml';

            // Prevent double usage of configuration files but name them like the templates
            if ($filenameUsed[$yamlFileName]) {
                $filenameUsed[$yamlFileName]++;
                $yamlFileName = pathinfo($resultingFileName, PATHINFO_FILENAME) . $filenameUsed[$yamlFileName] . '.tvp.yaml';
            } else {
                $filenameUsed[$yamlFileName] = 1;
            }

            list($mappingConfiguration, $scopeName, $scopePath) = $this->convertDsToForOneTo($allDs, $to, $copiedBackendLayoutFiles, $convertedDS, $packageName, $publicExtensionDirectory, $innerPathes, $resultingFileName, $yamlFileName);

            if (isset($to['childTO'])) {
                foreach ($to['childTO'] as $childTo) {
                    if ($childTo['fileref'] !== $to['fileref']) {
                        $resultingFileName = $this->copyFile($childTo['fileref'], $copiedTemplateFiles, $publicExtensionDirectory, $innerPathes['templates'], $childTo['rendertype']);
                    }

                    // Add rendertype name to filename before .tvp.yaml
                    $yamlChildFileName = substr($yamlFileName, 0, -strlen('.tvp.yaml')) . '_' . $childTo['rendertype'] . '.tvp.yaml';

                    // childTemplates should use the DS from parent, as they are only processed in FE
                    // So set it to parent datastructure to take the field processing into account.
                    // Clean this up afterwards
                    if ($childTo['datastructure'] === '') {
                        $childTo['datastructure'] = $to['datastructure'];
                    }
                    // No scopePath/Name from child convert call used
                    list($childMappingConfiguration) = $this->convertDsToForOneTo($allDs, $childTo, $copiedBackendLayoutFiles, $convertedDS, $packageName, $publicExtensionDirectory, $innerPathes, $resultingFileName, $yamlChildFileName);
                    $this->cleanupChildMappingConfiguration($mappingConfiguration, $childMappingConfiguration);
                    $mappingConfiguration['tvp-mapping']['childTemplate'][$childTo['rendertype']] = $childMappingConfiguration['tvp-mapping'];
                }
            }

            $covertingInstructions[] = [
                'fromTo' => $to['uid'],
                'toMap' => $packageName . $scopePath . '/MappingConfiguration:' . $yamlFileName,
            ];

            GeneralUtility::writeFile(
                $publicExtensionDirectory . $innerPathes['mappingConfiguration'][$scopeName] . '/' . $yamlFileName,
                \Symfony\Component\Yaml\Yaml::dump($mappingConfiguration, 100, 4, \Symfony\Component\Yaml\Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK) // No inline style please
            );
        }

//         foreach ($allDs as $ds) {
//
//             // Cleanup meta part
//             /** @TODO Move langDisable/langChildren/langDatabaseOverlay/noEditOnCreation(?)/default[] into Mapping(?)
//              * Or should noEditOnCreation(?)/default[] better stay here? Needs a look inside core
//              */
//             $title = $dataStructure['meta']['title'] ?? '';
//             unset($dataStructure['meta']);
//             $dataStructure['meta']['title'] = $title;
//
//         }
        return $covertingInstructions;
    }

    function convertDsToForOneTo(array $allDs, array $to, array &$copiedBackendLayoutFiles, array &$convertedDS, string $packageName, string $publicExtensionDirectory, array $innerPathes, string $templateFileName, string $yamlFileName): array
    {
        $convertedDsConfig = $this->getAndConvertDsForTo($allDs, $to, $convertedDS, $packageName, $publicExtensionDirectory, $innerPathes);

        $templateMappingInfo = $this->convertTemplateMappingInformation(
            unserialize($to['templatemapping'])['MappingInfo'],
            $publicExtensionDirectory . $innerPathes['templates'] . '/' . $templateFileName
        );

        // We need everytime the original to create $mappingToTemplateInfo correctly

        // We support DS with direct ['ROOT'] but not with ['sheets'][$sheetname]['ROOT']
        // @see #318
        $dataStructureRoot = $convertedDsConfig['datastructureOriginal']['ROOT'] ?: [];

        if ($to['localprocessing'] !== null && $to['localprocessing'] !== '') {
            $localprocessing = GeneralUtility::xml2array($to['localprocessing']);

            if (isset($localprocessing['ROOT'])) {
                $dataStructureRoot = array_replace_recursive(
                    $dataStructureRoot,
                    $localprocessing['ROOT']
                );
            }
        }
        $mappingToTemplateInfo = $this->convertDsTo2mappingInformation($dataStructureRoot, $templateMappingInfo['ROOT'], $to);

        // This gurentees that only one time we write this new content of DS
        $this->saveNewDs($convertedDsConfig);

        $templateConfiguration = [
            'tvp-template' => [
                'meta' => [
                    'name' => $to['title'],
                    'renderer' => \Tvp\TemplaVoilaPlus\Handler\Render\XpathRenderHandler::$identifier,
                    'template' => '../../Template/' . $templateFileName,
                ],
                'mapping' => $templateMappingInfo['ROOT'],
            ],
        ];

        $mappingConfiguration = [
            'tvp-mapping' => [
                'meta' => [
                    'name' => $to['title'],
                ],
                'combinedDataStructureIdentifier' => $convertedDsConfig['referencePath'], // Is empty string if no DS is needed
                'combinedTemplateConfigurationIdentifier' => $packageName . $convertedDsConfig['scopePath'] . '/TemplateConfiguration:' . $yamlFileName,
            ],
        ];

        /**
         * @TODO in staticDS it was also possible that we had a filenamen with same name but with .html as ending which included the belayout
         * Add this to the getAllDsFromStatic function.
         * No Support for beLayout content inside DS-XML or TO-Table only filenames (as this is what only worked in TV+).
         */
        if (!empty($to['belayout']) || !empty($convertedDsConfig['data']['belayout'])) {
            $beLayout = $to['belayout'];
            if (empty($beLayout)) {
                // Old, in nonStaticDS time this was in the DS record
                $beLayout = $convertedDsConfig['data']['belayout'];
            }
            $backendLayoutFileName = $this->copyFile($beLayout, $copiedBackendLayoutFiles, $publicExtensionDirectory, $innerPathes['backendLayout']);
            $mappingConfiguration['tvp-mapping']['combinedBackendLayoutConfigurationIdentifier'] = $packageName . '/BackendLayoutConfiguration:' . $backendLayoutFileName;
        }

        $mappingConfiguration['tvp-mapping']['mappingToTemplate'] = $mappingToTemplateInfo;

        GeneralUtility::writeFile(
            $publicExtensionDirectory . $innerPathes['templateConfiguration'][$convertedDsConfig['scopeName']] . '/' . $yamlFileName,
            \Symfony\Component\Yaml\Yaml::dump($templateConfiguration, 100, 4, \Symfony\Component\Yaml\Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK) // No inline style please
        );

        return [$mappingConfiguration, $convertedDsConfig['scopeName'], $convertedDsConfig['scopePath']];
    }

    protected function cleanupChildMappingConfiguration(array $parent, array &$child)
    {
        // child templates can not have own data structure
        unset($child['tvp-mapping']['combinedDataStructureIdentifier']);
        // child templates can not have own belayout
        unset($child['tvp-mapping']['combinedBackendLayoutConfigurationIdentifier']);

        // Now check every field for differences
        foreach ($parent['tvp-mapping']['mappingToTemplate'] as $fieldName => $parentFieldConfig) {
            $childFieldConfig = $child['tvp-mapping']['mappingToTemplate'][$fieldName];

            // TRUE if $a and $b have the same key/value pairs in the same order and of the same types.
            if ($parentFieldConfig === $childFieldConfig) {
                unset($child['tvp-mapping']['mappingToTemplate'][$fieldName]);
            } else {
                // Or check every field config parameter
                foreach ($parentFieldConfig as $parentFieldConfigParam => $parentFieldConfigValue) {
                    if (isset($childFieldConfig[$parentFieldConfigParam])
                        && $childFieldConfig[$parentFieldConfigParam] === $parentFieldConfigValue
                    ) {
                        unset($child['tvp-mapping']['mappingToTemplate'][$fieldName][$parentFieldConfigParam]);
                    }
                }
            }
        }

        if (count($child['tvp-mapping']['mappingToTemplate']) === 0) {
            unset($child['tvp-mapping']['mappingToTemplate']);
        }
    }

    protected function makeCleanFileName(string $fileName): string
    {
        // Take sanitizer from local driver
        /** @var \TYPO3\CMS\Core\Resource\Driver\LocalDriver */
        $localdriver = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Driver\LocalDriver::class);

        // After sanitizing remove double underscores and trim underscore
        return trim(
            preg_replace('/__/', '_', $localdriver->sanitizeFileName($fileName)),
            '_'
        );
    }

    protected function convertTemplateMappingInformation(array $mappingInformation, string $templateFile, $domDocument = null, $baseNode = null): array
    {
        $converted = [];

        libxml_use_internal_errors(true);
        $libXmlConfig = LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOENT | LIBXML_NONET;

        if ($domDocument === null) {
            $domDocument = new \DOMDocument();
            $domDocument->loadHTMLFile($templateFile, $libXmlConfig);
        }

        /** @TODO Check the errors if they are fatal
        $errors = libxml_get_errors();
        foreach ($errors as $error)
        {
        }*/

        libxml_clear_errors();

        /** @TODO Read error messages and write into a hint array for user output but do not break */
        $domXpath = new \DOMXPath($domDocument);

        foreach ($mappingInformation as $fieldName => $mappingField) {
            list($xPath, $mappingType) = explode('/', $mappingField['MAP_EL']);

            $convertedXPath = $this->convertXPath($xPath);

            $result = $domXpath->query($convertedXPath, $baseNode);

            if ($result === false) {
                throw new \Exception('The old mapping path "' . $xPath . '" could not be converted');
            }

            if ($result->count() === 0) {
                $convertedXPath = '//' . $convertedXPath;
                $result = $domXpath->query($convertedXPath, $baseNode);
                if ($result->count() === 0) {
                    /** @TODO Add to a hint array, what is wrong but do not stop converting */
//                     throw new \Exception('The old mapping path "' . $xPath . '" converted to XPath "' . $convertedXPath . '" could not be found in template file "' . $templateFile . '"');
                }
            }

            // Convert ATTRIB:HTMLElementsAttributeName (fe: ATTR:id)
            $attributeName = '';
            if ($mappingType && strpos($mappingType, 'ATTR:') === 0) {
                $attributeName = substr($mappingType, 5);
                $mappingType = 'attrib';
            }
            // MappingType is lower case and should be set
            $mappingType = $mappingType ? strtolower($mappingType) : 'outer';
            $converted[$fieldName] = [
                'xpath' => $convertedXPath,
                'mappingType' => $mappingType,
            ];
            if ($attributeName) {
                $converted[$fieldName]['attribName'] = $attributeName;
            }
            if (isset($mappingField['el']) && is_array($mappingField['el']) && count($mappingField['el']) > 0) {
                $innerBaseNode = $baseNode;
                if ($mappingType === 'inner') {
                    $innerBaseNode = $result->item(0);
                }
                $converted[$fieldName]['container'] = $this->convertTemplateMappingInformation($mappingField['el'], $templateFile, $domDocument, $innerBaseNode);
            }
        }

        return $converted;
    }

    /**
     * We are also updating the dsXml, as we remove TypoScript points!
     */
    protected function convertDsTo2mappingInformation(array $dsRoot, array &$templateMappingInfo, array $to): array
    {
        $mappingToTemplate = [];

        foreach ($dsRoot['el'] as $fieldName => $dsElement) {
            $fieldConfig = [];
            $useHtmlValue = false;

            if (isset($dsElement['TCEforms']['label'])) {
                $fieldConfig += [
                    'title' => $dsElement['TCEforms']['label'],
                ];
            }

            if (isset($dsElement['tx_templavoilaplus']['description'])) {
                $fieldConfig += [
                    'description' => $dsElement['tx_templavoilaplus']['description'],
                ];
            }

            if ($dsElement['tx_templavoilaplus']['eType'] === 'TypoScriptObject') {
                // TSObject shouldn't reside inside DataStructure, so move completely
                $fieldConfig += [
                    'dataType' => 'typoscriptObjectPath',
                    'dataPath' => $dsElement['tx_templavoilaplus']['TypoScriptObjPath'],
                ];

                if (
                    !isset($dsElement['tx_templavoilaplus']['proc']['HSC'])
                    || $dsElement['tx_templavoilaplus']['proc']['HSC'] != '1'
                ) {
                    $useHtmlValue = true;
                }
            } elseif ($dsElement['tx_templavoilaplus']['eType'] === 'none' && !isset($dsElement['TCEforms']['config'])) {
                // Blind TypoScript element, nothing todo here, already done
            } else {
                // Respect EType_extra??
                // Respect proc ?
                $fieldConfig += [
                    'dataType' => 'flexform',
                    'dataPath' => $fieldName,
                ];
            }

            $typoScript = trim($dsElement['tx_templavoilaplus']['TypoScript'] ?? '');
            if ($typoScript !== '') {
                $fieldConfig += [
                    'valueProcessing' => 'typoScript',
                    'valueProcessing.typoScript' => $this->cleanTypoScript($typoScript),
                ];

                if (
                    !isset($dsElement['tx_templavoilaplus']['proc']['HSC'])
                    || $dsElement['tx_templavoilaplus']['proc']['HSC'] != '1'
                ) {
                    $useHtmlValue = true;
                }
            }

            // Section and repeatables
            if ($dsElement['type'] === 'array' || (isset($dsElement['section']) && $dsElement['section'] == 1)) {
                if (isset($dsElement['section']) && $dsElement['section'] == 1) {
                    $fieldConfig['valueProcessing'] = 'repeatable';
                    $templateMappingInfo['container'][$fieldName]['containerType'] = 'repeatable';
                } else {
                    $fieldConfig['valueProcessing'] = 'container';
                    $templateMappingInfo['container'][$fieldName]['containerType'] = 'box';
                }
                $fieldConfig['container'] = $this->convertDsTo2mappingInformation($dsElement, $templateMappingInfo['container'][$fieldName], $to);
            }

            if ($useHtmlValue) {
                switch ($templateMappingInfo['container'][$fieldName]['mappingType']) {
                    case 'outer':
                    case 'inner':
                        $templateMappingInfo['container'][$fieldName]['valueType'] = 'html';
                        break;
                    default:
                        // Nothing, as there is no childing for ATTRIB
                }
            }

            $mappingToTemplate[$fieldName] = $fieldConfig;
        }

        return $mappingToTemplate;
    }

    protected function cleanTypoScript(string $typoScript): string
    {
        // Convert from different line breaks to system line breaks and trim whitespaces
        $typoScriptSplit = preg_split('/\r\n|\r|\n/', $typoScript);
        $typoScriptSplit = array_map('trim', $typoScriptSplit);

        return implode(PHP_EOL, $typoScriptSplit);
    }

    protected function convertXPath(string $xpath): string
    {
        $xPathPartsConverted = [];

        $xpathParts = GeneralUtility::trimExplode(' ', $xpath, true);

        foreach ($xpathParts as $xPathPart) {
            // Regular expression to match tag.className#idName[number]
            // Is there a better way? Using now
            // * for tag "All but not . and not # and not ["
            // * for class "point and then all but not # and not ["
            // * for idName "point and then all but not ["
            // * for number "[ number ]"
            preg_match('/([^.^#^[]*)(\.[^#^[]*)?(#[^[]*)?(\[\d+\])?/', $xPathPart, $matches);

            // Convert multi class select from "className1~~~className2" to "className1 className2""
            $matches[2] = str_replace('~~~', ' ', $matches[2]);

            // and convert to XPath tag[class=className][id=idName][number]
            // Not all parts need to exist.
            $xPathPartsConverted[] = $matches[1]
                . ($matches[2] !== '' ? '[@class="' . ltrim($matches[2], '.') . '"]' : '')
                . ($matches[3] !== '' ? '[@id="' . ltrim($matches[3], '#') . '"]' : '')
                . $matches[4];
        }

        return implode('/', $xPathPartsConverted);
    }

    protected function getAndConvertDsForTo(array $allDs, array $to, array &$convertedDS, string $packageName, string $publicExtensionDirectory, array $innerPathes): array
    {
        if ($to['datastructure'] === '') {
            return [];
        }
        if (!isset($convertedDS[$to['datastructure']])) {
            $ds = $this->getDsForTo($allDs, $to);

            $dataStructure = GeneralUtility::xml2array($ds['xml']);

            $dsXmlFileName = 'a.xml';

            if ($ds['staticDS']) {
                $dsXmlFileName = basename($ds['path']);
            } else {
                $dsXmlFileName = $this->makeCleanFileName($ds['title']) . '.xml';

                // Mostly the DS from database have no title inside XML so update this field
                // Works like writeXmlWithTitle from old StaticDataUpdateController
                if (
                    empty($dataStructure['ROOT']['tx_templavoilaplus']['title'])
                    || $dataStructure['ROOT']['tx_templavoilaplus']['title'] === 'ROOT'
                    && (empty($dataStructure['meta']['title'])
                        || $dataStructure['meta']['title'] === 'ROOT'
                    )
                ) {
                    $dataStructure['meta']['title'] = $ds['title'];
                }
            }

            switch ($ds['scope']) {
                case \Tvp\TemplaVoilaPlus\Domain\Model\Scope::SCOPE_PAGE:
                    $scopePath = '/Page';
                    $scopeName = 'page';
                    break;
                case \Tvp\TemplaVoilaPlus\Domain\Model\Scope::SCOPE_FCE:
                    $scopePath = '/FCE';
                    $scopeName = 'fce';
                    break;
                default:
                    $scopePath = '';
                    $scopeName = 'unknown';
            }

            $dataStructureCleaned = $dataStructure;
            $dataStructureCleaned['ROOT'] = $this->cleanupDataStructureRoot($dataStructure['ROOT']);

            /**
             * There is a cleanup of DS inside convertDsTo2mappingInformation
             * So we can't save here, needs to be done later
             */
            $convertedDsConfig = [
                'scopeName' => $scopeName,
                'scopePath' => $scopePath,
                'savePath' => $publicExtensionDirectory . $innerPathes['ds'][$scopeName] . '/' . $dsXmlFileName,
                'referencePath' => $packageName . $scopePath . '/DataStructure:' . $dsXmlFileName,
                'datastructureOriginal' => $dataStructure,
                'dataStructureCleaned' => $dataStructureCleaned,
                'data' => $ds,
            ];

            $this->saveNewDs($convertedDsConfig);

            $convertedDS[$to['datastructure']] = $convertedDsConfig;
        }

        return $convertedDS[$to['datastructure']];
    }


    /**
     * We are also updating the dsXml, as we remove TypoScript points!
     */
    protected function cleanupDataStructureRoot(array $dsRoot): array
    {
        if (isset($dsRoot['el'])) {
            foreach ($dsRoot['el'] as $fieldName => $dsElement) {

                if ($dsElement['tx_templavoilaplus']['eType'] === 'TypoScriptObject') {
                    unset($dsRoot['el'][$fieldName]);
                } elseif ($dsElement['tx_templavoilaplus']['eType'] === 'none' && !isset($dsElement['TCEforms']['config'])) {
                    // Blind TypoScript element, nothing todo here, already done
                    unset($dsRoot['el'][$fieldName]);
                } else {
                    unset($dsRoot['el'][$fieldName]['tx_templavoilaplus']);
                }

                // Section and repeatables
                if ($dsElement['type'] === 'array' || (isset($dsElement['section']) && $dsElement['section'] == 1)) {
                    $dsRoot['el'][$fieldName] = $this->cleanupDataStructureRoot($dsElement);
                    if (!isset($dsRoot['el'][$fieldName]['el'])) {
                        unset($dsRoot['el'][$fieldName]);
                    }
                }
            }
            if (count($dsRoot['el']) === 0) {
                unset($dsRoot['el']);
            }
        }

        return $dsRoot;
    }

    protected function saveNewDs(array &$convertedDs): void
    {
        if (isset($convertedDs['dataStructureCleaned']['ROOT'])
            && isset($convertedDs['dataStructureCleaned']['ROOT']['el'])
            && count($convertedDs['dataStructureCleaned']['ROOT']['el']) > 0
        ) {
            /** DS is only needed, if we have have a fields configuration */
            GeneralUtility::writeFile(
                $convertedDs['savePath'],
                DataStructureUtility::array2xml($convertedDs['dataStructureCleaned'])
            );
        } else {
            unset($convertedDs['referencePath']);
        }
    }

    protected function getDsForTo(array $allDs, array $to): array
    {
        foreach ($allDs as $ds) {
            if ($ds['path'] === $to['datastructure']) {
                return $ds;
            }
        }
        throw new \Exception('DataStructure "' . $to['datastructure'] . '" not found for Template Object with uid "' . $to['uid'] . '"');
    }

    protected function copyFile(string $readPathAndFilename, array &$copiedTemplateFiles, string $publicExtensionDirectory, string $subPath, string $extraPart = ''): string
    {
        $source = GeneralUtility::getFileAbsFileName($readPathAndFilename);

        if (isset($copiedTemplateFiles[$source])) {
            return $copiedTemplateFiles[$source];
        }

        $filename = basename($readPathAndFilename);

        if ($extraPart) {
            $filenameInfo = pathinfo($filename);
            $filename = $filenameInfo['filename'] . '_' . $extraPart . '.' . $filenameInfo['extension'];
        }

        $destination = $publicExtensionDirectory . $subPath . '/';

        if (file_exists($destination . $filename)) {
            /** @TODO Implement me */
//             $destination = $this->getUniqueFilename($destination, $filename);
            throw new \Exception('Doubled file names arent implemented yet: "' . $destination . $filename . '"');
        }

        $result = @copy($source, $destination . $filename);
        if ($result) {
            GeneralUtility::fixPermissions($destination . $filename);
        }

        $copiedTemplateFiles[$source] = $filename;

        return  $filename;
    }

    protected function createPaths(string $publicExtensionDirectory, array $innerSubPaths)
    {
        foreach ($innerSubPaths as $subPath) {
            if (is_array($subPath)) {
                $this->createPaths($publicExtensionDirectory, $subPath);
            } else {
                $this->createPath($publicExtensionDirectory, $subPath);
            }
        }
    }

    protected function createPath(string $publicExtensionDirectory, string $subPath)
    {
        if (!file_exists($publicExtensionDirectory . $subPath)) {
            GeneralUtility::mkdir_deep($publicExtensionDirectory . $subPath);
        }
    }

    protected function getPackagePaths($extensionKey): string
    {
        $packageBasePath = '';
        if (version_compare(TYPO3_version, '9.4.0', '>=')) {
            $packageBasePath = \TYPO3\CMS\Core\Core\Environment::getExtensionsPath();
        } else {
            $packageBasePath = PATH_typo3conf . 'ext';
        }

        return $packageBasePath . '/' . $extensionKey;
    }

    protected function getPackageTitle($extensionKey): string
    {
        $title = '';
        $path = $this->getPackagePaths($extensionKey);
        $file = $path . '/ext_emconf.php';
        $EM_CONF = null;
        if (file_exists($file)) {
            include $file;
            if (is_array($EM_CONF[$extensionKey]) && isset($EM_CONF[$extensionKey]['title'])) {
                $title = $EM_CONF[$extensionKey]['title'];
            }
        }

        return $title;
    }

    /**
     * Register the generated extensions
     * Update the map field with the configuration (depending on ds/to)
     */
    protected function step5Action()
    {
        $errors = [];

        $selection = $_POST['selection'];

        // Register extensions
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager */
        $objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        /** @var \TYPO3\CMS\Extensionmanager\Utility\InstallUtility */
        $installUtility = $objectManager->get(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class);

        try {
            $installUtility->install($selection);
        } catch (\UnexpectedValueException $e) {
            $errors[] = 'Error while installing Extension. Please do this by your own. Original message from extension manager is: "' . $e->getMessage() . '"';
        }

        // Read mapping information from json
        $covertingInstructions = json_decode($_POST['covertingInstructionsJson'], true);
        // Update pages
        // Update tt_content

        // Also updating deleted ones, so history function should work
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder */
        $queryBuilderPages = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilderPages->getRestrictions()->removeAll();
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder */
        $queryBuilderContent = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilderContent->getRestrictions()->removeAll();

        foreach ($covertingInstructions as $instruction) {
            $queryBuilderPagesClone = clone $queryBuilderPages;
            $result = $queryBuilderPagesClone
                ->update('pages')
                ->where(
                    $queryBuilderPagesClone->expr()->eq('tx_templavoilaplus_to', $queryBuilderPagesClone->createNamedParameter($instruction['fromTo'], \PDO::PARAM_INT))
                )
                ->set('tx_templavoilaplus_map', $instruction['toMap'])
                ->execute();

            $queryBuilderPagesClone = clone $queryBuilderPages;
            $result = $queryBuilderPagesClone
                ->update('pages')
                ->where(
                    $queryBuilderPagesClone->expr()->eq('tx_templavoilaplus_next_to', $queryBuilderPagesClone->createNamedParameter($instruction['fromTo'], \PDO::PARAM_INT))
                )
                ->set('tx_templavoilaplus_next_map', $instruction['toMap'])
                ->execute();

            $queryBuilderContentClone = clone $queryBuilderContent;
            $result = $queryBuilderContentClone
                ->update('tt_content')
                ->where(
                    $queryBuilderContentClone->expr()->eq('tx_templavoilaplus_to', $queryBuilderContentClone->createNamedParameter($instruction['fromTo'], \PDO::PARAM_INT))
                )
                ->set('tx_templavoilaplus_map', $instruction['toMap'])
                ->execute();
        }

        $this->view->assignMultiple([
            'errors' => $errors,
            'hasError' => (count($errors) ? true : false),
        ]);

        // Write into sys_registry including which storage_pid we already did
    }

    protected function stepFinalAction()
    {
    }


    /**
     * Taken from TYPO3 Core ArrayUtility and expanded for our special object of non quoted string
     * Exports an array as string.
     * Similar to var_export(), but representation follows the PSR-2 and TYPO3 core CGL.
     *
     * See unit tests for detailed examples
     *
     * @param array $array Array to export
     * @param int $level Internal level used for recursion, do *not* set from outside!
     * @return string String representation of array
     * @throws \RuntimeException
     */
    public static function arrayExport(array $array = [], $level = 0)
    {
        $lines = '[' . LF;
        $level++;
        $writeKeyIndex = false;
        $expectedKeyIndex = 0;
        foreach ($array as $key => $value) {
            if ($key === $expectedKeyIndex) {
                $expectedKeyIndex++;
            } else {
                // Found a non integer or non consecutive key, so we can break here
                $writeKeyIndex = true;
                break;
            }
        }
        foreach ($array as $key => $value) {
            // Indention
            $lines .= str_repeat('    ', $level);
            if ($writeKeyIndex) {
                // Numeric / string keys
                $lines .= is_int($key) ? $key . ' => ' : '\'' . $key . '\' => ';
            }
            if (is_array($value)) {
                if (!empty($value)) {
                    $lines .= self::arrayExport($value, $level);
                } else {
                    $lines .= '[],' . LF;
                }
            } elseif (is_int($value) || is_float($value)) {
                $lines .= $value . ',' . LF;
            } elseif (is_null($value)) {
                $lines .= 'null' . ',' . LF;
            } elseif (is_bool($value)) {
                $lines .= $value ? 'true' : 'false';
                $lines .= ',' . LF;
            } elseif (is_string($value)) {
                // Quote \ to \\
                $stringContent = str_replace('\\', '\\\\', $value);
                // Quote ' to \'
                $stringContent = str_replace('\'', '\\\'', $stringContent);
                $lines .= '\'' . $stringContent . '\'' . ',' . LF;
            } elseif ($value instanceof UnquotedString) {
                $lines .= (string) $value . ',' . LF;
            } else {
                throw new \RuntimeException('Objects are not supported', 1342294987);
            }
        }
        $lines .= str_repeat('    ', ($level - 1)) . ']' . ($level - 1 == 0 ? '' : ',' . LF);
        return $lines;
    }

    protected function stepTODO()
    {
        $this->view->assignMultiple([
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

        foreach ($result as $row) {
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

class UnquotedString
{
    private $value = '';
    public function __construct(string $value)
    {
        $this->value = $value;
    }
    public function __toString(): string
    {
        return $this->value;
    }
}
