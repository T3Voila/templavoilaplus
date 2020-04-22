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
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ArrayUtility;
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
            $allDs = [];
        }

        // Search for all TO configurations
        $allTo = $this->getAllToFromDB();

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

        $this->fluid->assignMultiple([
            'allOldDatabaseElementsFound' => $allOldDatabaseElementsFound,
            'allNewDatabaseElementsFound' => $allNewDatabaseElementsFound,
            'storagePidsAreFine' => $storagePidsAreFine,
            'useStaticDS' => $useStaticDS,
            'allDsToValid' => $allDsToValid,
            'validationDsToErrors' => $validationDsToErrors,
            'validatedDs' => $validatedDs,
            'validatedToWithDs' => $validatedToWithDs,
            'allPagesContentValid' => $allPagesContentValid,
            'validationPagesContentErrors' => $validationPagesContentErrors,
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
            $ds['countUsage'] = 0;
            $ds['valid'] = false;

            /** @TODO Implement NonStaticDs */
            if (is_file($systemPath . $ds['path']) && is_readable($systemPath . $ds['path'])) {
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

        // PAGES
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $result = $queryBuilder
            ->count('uid')
            ->addSelect('uid', 'tx_templavoilaplus_to', 'tx_templavoilaplus_next_to')
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

        foreach($result as $row) {
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

        foreach($result as $row) {
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
    protected function step2()
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
            $active = (isset($activePackages[$key])? true : false);
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

        $this->fluid->assignMultiple([
            'terListHint' => (count($allTerExtensionKeys) === 0 ? true : false),
            'packagesQualified' => $packagesQualified,
        ]);
    }

    protected function getAllTerExtensionKeys():array
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
    protected function step3()
    {
        $selection = $_POST['selection'];

        if ($selection === '_new_') {
            return '3NewExtension';
        }
        if (!empty($selection)) {
            return '3ExistingExtension';
        }

        return '2'; // Return to step 2
        // Create files and folders
    }

    protected function step3NewExtension()
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

        $this->fluid->assignMultiple([
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

    protected function step3ExistingExtension()
    {
        $errors = [];

        $selection = $_POST['selection'];

        $errors[] = 'Using an existing extension isn\'t supported yet.';

        $this->fluid->assignMultiple([
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
    protected function step4()
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
                if (GeneralUtility::mkdir($publicExtensionDirectory)) {
                    // Create ext_emconf.php
                    $emConfConfig = [
                        'title' => $extensionName,
                        'description' => '',
                        'version' => '0.1.0',
                        'state' => 'alpha',
                        'author' => $author,
                        'author_email' => '',
                        'author_company' => $authorCompany,
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

                    // Create extension registration in ext_localconf.php which is removed later
                } else {
                    throw new \Exception('Could not create extension path "' . $publicExtensionDirectory . '"');
                }
            }

            // Create Configuration/TVP if needed (clear if overwrite mode)
            // Create/Update Places configuration files
            // Create new Resources directories
            // Read old data, convert and write to new places
            // Hold the mapping information as json
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }
        $this->fluid->assignMultiple([
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

    protected function getPackagePaths($extensionKey)
    {
        $packageBasePath = '';
        if (version_compare(TYPO3_version, '9.4.0', '>=')) {
            $packageBasePath = \TYPO3\CMS\Core\Core\Environment::getExtensionsPath();
        } else {
            $packageBasePath = PATH_typo3conf . 'ext';
        }

        return $packageBasePath . '/' . $extensionKey;
    }

    /**
     * Register the generated extensions
     * Update the map field with the configuration (depending on ds/to)
     */
    protected function step5()
    {
        // Register extensions
        // Read mapping information from json
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
