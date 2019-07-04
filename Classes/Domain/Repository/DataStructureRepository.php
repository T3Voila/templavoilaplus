<?php
namespace Ppi\TemplaVoilaPlus\Domain\Repository;

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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

use Ppi\TemplaVoilaPlus\Service\ConfigurationService;
use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Class to provide unique access to datastructure
 *
 * @author Tolleiv Nietsch <tolleiv.nietsch@typo3.org>
 */
class DataStructureRepository implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var boolean
     */
    static protected $staticDsInitComplete = false;

    /**
     * Retrieve a single datastructure by uid or xml-file path
     *
     * @param integer $uidOrFile
     *
     * @throws \InvalidArgumentException
     *
     * @return \Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure
     */
    public function getDatastructureByUidOrFilename($uidOrFile)
    {
        if ((int)$uidOrFile > 0) {
            $className = \Ppi\TemplaVoilaPlus\Domain\Model\DataStructure::class;
        } else {
            if (StringUtility::beginsWith($uidOrFile, 'FILE:')) {
                $uidOrFile = substr($uidOrFile, 5);
            }
            if (($staticKey = $this->validateStaticDS($uidOrFile)) !== false) {
                $uidOrFile = $staticKey;
                $className = \Ppi\TemplaVoilaPlus\Domain\Model\StaticDataStructure::class;
            } else {
                throw new \InvalidArgumentException(
                    'Argument was supposed to be either a uid or a filename',
                    1273409810
                );
            }
        }

        $ds = GeneralUtility::makeInstance($className, $uidOrFile);

        return $ds;
    }

    /**
     * Retrieve a collection (array) of tx_templavoilaplus_datastructure objects
     *
     * @param integer $pid
     *
     * @return array
     */
    public function getDatastructuresByStoragePid($pid)
    {
        $dscollection = array();
        $confArr = self::getStaticDatastructureConfiguration();
        if (count($confArr)) {
            foreach ($confArr as $conf) {
                $ds = $this->getDatastructureByUidOrFilename($conf['path']);
                $pids = $ds->getStoragePids();
                if ($pids == '' || GeneralUtility::inList($pids, $pid)) {
                    $dscollection[] = $ds;
                }
            }
        }

        if (!self::isStaticDsEnabled()) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_templavoilaplus_datastructure');
            $dsRows = $queryBuilder
                ->select('uid')
                ->from('tx_templavoilaplus_datastructure')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT))
                )
                ->execute()
                ->fetchAll();

            foreach ($dsRows as $ds) {
                $dscollection[] = $this->getDatastructureByUidOrFilename($ds['uid']);
            }
        }
        usort($dscollection, array($this, 'sortDatastructures'));

        return $dscollection;
    }

    /**
     * Retrieve a collection (array) of tx_templavoilaplus_datastructure objects
     *
     * @param integer $pid
     * @param integer $scope
     *
     * @return array
     */
    public function getDatastructuresByStoragePidAndScope($pid, $scope)
    {
        $dscollection = array();
        $confArr = self::getStaticDatastructureConfiguration();

        if (count($confArr)) {
            foreach ($confArr as $conf) {
                if ($conf['scope'] === $scope) {
                    $ds = $this->getDatastructureByUidOrFilename($conf['path']);
                    $pids = $ds->getStoragePids();
                    if ($pids == '' || GeneralUtility::inList($pids, $pid)) {
                        $dscollection[] = $ds;
                    }
                }
            }
        }

        if (!self::isStaticDsEnabled()) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_templavoilaplus_datastructure');
            $dsRows = $queryBuilder
                ->select('uid')
                ->from('tx_templavoilaplus_datastructure')
                ->where(
                    $queryBuilder->expr()->eq('scope', $scope),
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT))
                )
                ->execute()
                ->fetchAll();

            foreach ($dsRows as $ds) {
                $dscollection[] = $this->getDatastructureByUidOrFilename($ds['uid']);
            }
        }
        usort($dscollection, array($this, 'sortDatastructures'));

        return $dscollection;
    }

    /**
     * Retrieve a collection (array) of tx_templavoilaplus_datastructure objects
     *
     * @param integer $scope
     *
     * @return array
     */
    public function getDatastructuresByScope($scope)
    {
        $dscollection = array();
        $confArr = self::getStaticDatastructureConfiguration();
        if (count($confArr)) {
            foreach ($confArr as $conf) {
                if ($conf['scope'] == $scope) {
                    $ds = $this->getDatastructureByUidOrFilename($conf['path']);
                    $dscollection[] = $ds;
                }
            }
        }

        if (!self::isStaticDsEnabled()) {
            $dsRows = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTgetRows(
                'uid',
                'tx_templavoilaplus_datastructure',
                'scope=' . (int)$scope
                . BackendUtility::deleteClause('tx_templavoilaplus_datastructure')
                . ' AND pid!=-1 '
                . BackendUtility::versioningPlaceholderClause('tx_templavoilaplus_datastructure')
            );
            foreach ($dsRows as $ds) {
                $dscollection[] = $this->getDatastructureByUidOrFilename($ds['uid']);
            }
        }
        usort($dscollection, array($this, 'sortDatastructures'));

        return $dscollection;
    }

    /**
     * Retrieve a collection (array) of tx_templavoilaplus_datastructure objects
     *
     * @return array
     */
    public function getAll()
    {
        $dscollection = array();
        $confArr = self::getStaticDatastructureConfiguration();
        if (count($confArr)) {
            foreach ($confArr as $conf) {
                $ds = $this->getDatastructureByUidOrFilename($conf['path']);
                $dscollection[] = $ds;
            }
        }

        if (!self::isStaticDsEnabled()) {
            $dsRows = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTgetRows(
                'uid',
                'tx_templavoilaplus_datastructure',
                '1=1'
                . BackendUtility::deleteClause('tx_templavoilaplus_datastructure')
                . ' AND pid!=-1 '
                . BackendUtility::versioningPlaceholderClause('tx_templavoilaplus_datastructure')
            );
            foreach ($dsRows as $ds) {
                $dscollection[] = $this->getDatastructureByUidOrFilename($ds['uid']);
            }
        }
        usort($dscollection, array($this, 'sortDatastructures'));

        return $dscollection;
    }

    /**
     * @param string $file
     *
     * @return mixed
     */
    protected function validateStaticDS($file)
    {
        $confArr = self::getStaticDatastructureConfiguration();
        $confKey = false;
        if (count($confArr)) {
            $fileAbsName = GeneralUtility::getFileAbsFileName($file);
            foreach ($confArr as $key => $conf) {
                if (GeneralUtility::getFileAbsFileName($conf['path']) == $fileAbsName) {
                    $confKey = $key;
                    break;
                }
            }
        }

        return $confKey;
    }

    /**
     * @return boolean
     */
    protected function isStaticDsEnabled()
    {
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        return $configurationService->isStaticDataStructureEnabled();
    }

    /**
     * @return array
     */
    public static function getStaticDatastructureConfiguration()
    {
        $config = array();
        if (!self::$staticDsInitComplete) {
            if (self::isStaticDsEnabled()) {
                self::readStaticDsFilesIntoArray();
            }
            self::$staticDsInitComplete = true;
        }
        if (isset($GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoilaplus_cm1']['staticDataStructures'])
            && is_array($GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoilaplus_cm1']['staticDataStructures'])
        ) {
            $config = $GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoilaplus_cm1']['staticDataStructures'];
        }

        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['staticDataStructures'])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['staticDataStructures'])
        ) {
            $config = array_merge($config, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['staticDataStructures']);
        }

        $finalConfig = array();
        foreach ($config as $cfg) {
            $key = md5($cfg['path'] . $cfg['title'] . $cfg['scope']);
            $finalConfig[$key] = $cfg;
        }

        return array_values($finalConfig);
    }

    /**
     * Sorts datastructure alphabetically
     *
     * @param \Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure $obj1
     * @param \Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure $obj2
     *
     * @return integer Result of the comparison (see strcmp())
     * @see usort()
     * @see strcmp()
     */
    public function sortDatastructures($obj1, $obj2)
    {
        return strcmp(strtolower($obj1->getSortingFieldValue()), strtolower($obj2->getSortingFieldValue()));
    }

    /**
     * @param integer $pid
     *
     * @return integer
     */
    public function getDatastructureCountForPid($pid)
    {
        $dsCnt = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTgetRows(
            'DISTINCT datastructure',
            'tx_templavoilaplus_tmplobj',
            'pid=' . (int)$pid . BackendUtility::deleteClause('tx_templavoilaplus_tmplobj'),
            'datastructure'
        );
        array_unique($dsCnt);

        return count($dsCnt);
    }

    protected static function readStaticDsFilesIntoArray()
    {
        $systemPath = '/';

        if (version_compare(TYPO3_version, '9.2.0', '>=')) {
            $systemPath = Environment::getPublicPath();
        } else {
            $systemPath = rtrim(PATH_side, '/');
        }
        $systemPath .= '/';

        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $paths = $configurationService->getDataStructurePlaces();

        foreach ($paths as $type => $pathConfig) {
            $absolutePath = GeneralUtility::getFileAbsFileName($pathConfig['path']);
            $files = GeneralUtility::getFilesInDir($absolutePath, 'xml', true);

            foreach ($files as $filePath) {
                $staticDataStructure = array();
                $pathInfo = pathinfo($filePath);

                $staticDataStructure['title'] = $pathInfo['filename'];
                $staticDataStructure['path'] = substr($filePath, strlen($systemPath));
                $iconPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.gif';
                if (file_exists($iconPath)) {
                    $staticDataStructure['icon'] = substr($iconPath, strlen($systemPath));
                }
                $staticDataStructure['scope'] = $pathConfig['scope'];

                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['staticDataStructures'][] = $staticDataStructure;
            }
        }
    }
}
