<?php
namespace Extension\Templavoila\Service\ItemProcFunc;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class/Function which manipulates the item-array for table/field tx_templavoila_tmplobj_datastructure.
 *
 * @author Kasper Skaarhoj <kasper@typo3.com>
 */
class StaticDataStructuresHandler
{
    /**
     * @var array
     */
    protected $conf;

    /**
     * @var string
     */
    public $prefix = 'Static: ';

    /**
     * @var string
     */
    public $iconPath = '../uploads/tx_templavoila/';

    /**
     * Adds static data structures to selector box items arrays.
     * Adds ALL available structures
     *
     * @param array &$params Array of items passed by reference.
     * @param object &$pObj The parent object (\TYPO3\CMS\Backend\Form\FormEngine / \TYPO3\CMS\Backend\Form\DataPreprocessor depending on context)
     *
     * @return void
     */
    public function main(&$params, &$pObj)
    {
        $removeDSItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'ds');

        $dsRepo = GeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\DataStructureRepository::class);
        $dsList = $dsRepo->getAll();

        $params['items'] = array(
            array(
                '', ''
            )
        );

        foreach ($dsList as $dsObj) {
            /** @var \Extension\Templavoila\Domain\Model\AbstractDataStructure $dsObj */
            if ($dsObj->isPermittedForUser($params['row'], $removeDSItems)) {
                $params['items'][] = array(
                    $dsObj->getLabel(),
                    $dsObj->getKey(),
                    $dsObj->getIcon()
                );
            }
        }
    }

    /**
     * Adds Template Object records to selector box for Content Elements of the "Plugin" type.
     *
     * @param array &$params Array of items passed by reference.
     * @param \TYPO3\CMS\Backend\Form\FormEngine|\TYPO3\CMS\Backend\Form\DataPreprocessor $pObj The parent object (\TYPO3\CMS\Backend\Form\FormEngine / \TYPO3\CMS\Backend\Form\DataPreprocessor depending on context)
     *
     * @return void
     */
    public function pi_templates(&$params, $pObj)
    {
        // Find the template data structure that belongs to this plugin:
        $piKey = $params['row']['list_type'];
        $templateRef = $GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoila_cm1']['piKey2DSMap'][$piKey]; // This should be a value of a Data Structure.
        $storagePid = $this->getStoragePid($params);

        if ($templateRef && $storagePid) {

            // Select all Template Object Records from storage folder, which are parent records and which has the data structure for the plugin:
            $res = $this->getDatabaseConnection()->exec_SELECTquery(
                'title,uid,previewicon',
                'tx_templavoila_tmplobj',
                'tx_templavoila_tmplobj.pid=' . $storagePid . ' AND tx_templavoila_tmplobj.datastructure=' .  $this->getDatabaseConnection()->fullQuoteStr($templateRef, 'tx_templavoila_tmplobj') . ' AND tx_templavoila_tmplobj.parent=0',
                '',
                'tx_templavoila_tmplobj.title'
            );

            // Traverse these and add them. Icons are set too if applicable.
            while (false != ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res))) {
                if ($row['previewicon']) {
                    $icon = '../' . $GLOBALS['TCA']['tx_templavoila_tmplobj']['columns']['previewicon']['config']['uploadfolder'] . '/' . $row['previewicon'];
                } else {
                    $icon = '';
                }
                $params['items'][] = array($this->getLanguageService()->sL($row['title']), $row['uid'], $icon);
            }
        }
    }

    /**
     * Creates the DS selector box. This function takes into account TS
     * config override of the GRSP.
     *
     * @param array $params Parameters to the itemsProcFunc
     * @param \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems $pObj Calling object
     *
     * @return void
     */
    public function dataSourceItemsProcFunc(array &$params, \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems &$pObj)
    {
        $storagePid = $this->getStoragePid($params);
        $scope = $this->getScope($params);

        $removeDSItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'ds');

        $dsRepo = GeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\DataStructureRepository::class);
        $dsList = $dsRepo->getDatastructuresByStoragePidAndScope($storagePid, $scope);

        $params['items'] = array(
            array(
                '', ''
            )
        );

        foreach ($dsList as $dsObj) {
            /** @var \Extension\Templavoila\Domain\Model\AbstractDataStructure $dsObj */
            if ($dsObj->isPermittedForUser($params['row'], $removeDSItems)) {
                $params['items'][] = array(
                    $dsObj->getLabel(),
                    $dsObj->getKey(),
                    $dsObj->getIcon()
                );
            }
        }
    }

    /**
     * Adds items to the template object selector according to the current
     * extension mode.
     *
     * @param array $params Parameters for itemProcFunc
     * @param \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems $pObj Calling class
     *
     * @return void
     */
    public function templateObjectItemsProcFunc(array &$params, \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems &$pObj)
    {
        $this->conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);

        if ($this->conf['enable.']['selectDataStructure']) {
            $this->templateObjectItemsProcFuncForCurrentDS($params, $pObj);
        } else {
            $this->templateObjectItemsProcFuncForAllDSes($params, $pObj);
        }
    }

    /**
     * Adds items to the template object selector according to the scope and
     * storage folder of the current page/element.
     *
     * @param array $params Parameters for itemProcFunc
     * @param \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems $pObj Calling class
     *
     * @return void
     */
    protected function templateObjectItemsProcFuncForCurrentDS(array &$params, \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems &$pObj)
    {
        // Get DS

        $fieldName = $params['field'] == 'tx_templavoila_next_to' ? 'tx_templavoila_next_ds' : 'tx_templavoila_ds';
        $dataSource = $params['row'][$fieldName][0];

        $storagePid = $this->getStoragePid($params);

        $removeTOItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'to');

        $dsRepo = GeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\DataStructureRepository::class);
        $toRepo = GeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\TemplateRepository::class);

        try {
            $ds = $dsRepo->getDatastructureByUidOrFilename($dataSource);
            if (strlen($dataSource)) {
                $toList = $toRepo->getTemplatesByDatastructure($ds, $storagePid);
                foreach ($toList as $toObj) {
                    /** @var \Extension\Templavoila\Domain\Model\Template $toObj */
                    if (!$toObj->hasParent() && $toObj->isPermittedForUser($params['table'], $removeTOItems)) {
                        $params['items'][] = array(
                            $toObj->getLabel(),
                            $toObj->getKey(),
                            '../' . $toObj->getIcon()
                        );
                    }
                }
            }
        } catch (\InvalidArgumentException $e) {
            // we didn't find the DS which we were looking for therefore an empty list is returned
        }
    }

    /**
     * Adds items to the template object selector according to the scope and
     * storage folder of the current page/element.
     *
     * @param array $params Parameters for itemProcFunc
     * @param \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems $pObj Calling class
     *
     * @return void
     */
    protected function templateObjectItemsProcFuncForAllDSes(array &$params, \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems &$pObj)
    {
        $storagePid = $this->getStoragePid($params);
        $scope = $this->getScope($params);

        $removeDSItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'ds');
        $removeTOItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'to');

        $dsRepo = GeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\DataStructureRepository::class);
        $toRepo = GeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\TemplateRepository::class);
        $dsList = $dsRepo->getDatastructuresByStoragePidAndScope($storagePid, $scope);

        foreach ($dsList as $dsObj) {
            /** @var \Extension\Templavoila\Domain\Model\AbstractDataStructure $dsObj */
            if (!$dsObj->isPermittedForUser($params['row'], $removeDSItems)) {
                continue;
            }
            $curDS = array();
            $curDS[] = array(
                $dsObj->getLabel(),
                '--div--'
            );

            $toList = $toRepo->getTemplatesByDatastructure($dsObj, $storagePid);
            foreach ($toList as $toObj) {
                /** @var \Extension\Templavoila\Domain\Model\Template $toObj */
                if (!$toObj->hasParent() && $toObj->isPermittedForUser($params['row'], $removeTOItems)) {
                    $curDS[] = array(
                        $toObj->getLabel(),
                        $toObj->getKey(),
                        '..' . $toObj->getIcon()
                    );
                }
            }
            if (count($curDS) > 1) {
                $params['items'] = array_merge($params['items'], $curDS);
            }
        }
    }

    /**
     * Retrieves DS/TO storage pid for the current page. This function expectes
     * to be called from the itemsProcFunc only!
     *
     * @param array $params Parameters as come to the itemsProcFunc
     *
     * @return integer Storage pid
     */
    public function getStoragePid(array $params)
    {
        /**
         * // Get default first
         * $tsConfig = & $pObj->cachedTSconfig[$params['table'] . ':' . $params['row']['uid']];
         * $storagePid = (int)$tsConfig['_STORAGE_PID'];
         *
         * StoragePid no longer available since CMS 7
         * Could be done via BackendUtility::getTCEFORM_TSconfig($params['table'], $params['row']);
         * But field 'storage_pid' is no longer part of BEgetRootLine method
         */

        // Check for alternative storage folder
        $field = $params['table'] == 'pages' ? 'uid' : 'pid';

        $modTSConfig = BackendUtility::getModTSconfig($params['row'][$field], 'tx_templavoila.storagePid');
        if (is_array($modTSConfig) && MathUtility::canBeInterpretedAsInteger($modTSConfig['value'])) {
            $storagePid = (int)$modTSConfig['value'];
        } else {
            // @TODO Deprecate this part, configuration in pageTS should be enough
            $rootLine = $this->BEgetRootLine($params['row'][$field], '', true);
            foreach ($rootLine as $rC) {
                if (!empty($rC['storage_pid'])) {
                    $storagePid = (int)$rC['storage_pid'];
                    break;
                }
            }
        }

        return $storagePid;
    }

    /**
     * From TYPO3 CMS 7.6.9
     * Returns what is called the 'RootLine'. That is an array with information about the page records from a page id ($uid) and back to the root.
     * By default deleted pages are filtered.
     * This RootLine will follow the tree all the way to the root. This is opposite to another kind of root line known from the frontend where the rootline stops when a root-template is found.
     *
     * @param int $uid Page id for which to create the root line.
     * @param string $clause Clause can be used to select other criteria. It would typically be where-clauses that stops the process if we meet a page, the user has no reading access to.
     * @param bool $workspaceOL If true, version overlay is applied. This must be requested specifically because it is usually only wanted when the rootline is used for visual output while for permission checking you want the raw thing!
     * @return array Root line array, all the way to the page tree root (or as far as $clause allows!)
     */
    public function BEgetRootLine($uid, $clause = '', $workspaceOL = false)
    {
        static $BEgetRootLine_cache = array();
        $output = array();
        $pid = $uid;
        $ident = $pid . '-' . $clause . '-' . $workspaceOL;
        if (is_array($BEgetRootLine_cache[$ident])) {
            $output = $BEgetRootLine_cache[$ident];
        } else {
            $loopCheck = 100;
            $theRowArray = array();
            while ($uid != 0 && $loopCheck) {
                $loopCheck--;
                $row = $this->getPageForRootline($uid, $clause, $workspaceOL);
                if (is_array($row)) {
                    $uid = $row['pid'];
                    $theRowArray[] = $row;
                } else {
                    break;
                }
            }
            $c = count($theRowArray);
            foreach ($theRowArray as $val) {
                $c--;
                $output[$c] = array(
                    'storage_pid' => $val['storage_pid'],
                );
            }
            $BEgetRootLine_cache[$ident] = $output;
        }
        return $output;
    }

    /**
     * From TYPO3 CMS 7.6.9
     * Gets the cached page record for the rootline
     *
     * @param int $uid Page id for which to create the root line.
     * @param string $clause Clause can be used to select other criteria. It would typically be where-clauses that stops the process if we meet a page, the user has no reading access to.
     * @param bool $workspaceOL If true, version overlay is applied. This must be requested specifically because it is usually only wanted when the rootline is used for visual output while for permission checking you want the raw thing!
     * @return array Cached page record for the rootline
     * @see BEgetRootLine
     */
    protected function getPageForRootline($uid, $clause, $workspaceOL)
    {
        $db = $this->getDatabaseConnection();
        $res = $db->exec_SELECTquery('pid,uid,storage_pid,t3ver_oid,t3ver_wsid', 'pages', 'uid=' . (int)$uid . ' ' . BackendUtility::deleteClause('pages') . ' ' . $clause);
        $row = $db->sql_fetch_assoc($res);
        if ($row) {
            $newLocation = false;
            if ($workspaceOL) {
                BackendUtility::workspaceOL('pages', $row);
                $newLocation = BackendUtility::getMovePlaceholder('pages', $row['uid'], 'pid');
            }
            if (is_array($row)) {
                if ($newLocation !== false) {
                    $row['pid'] = $newLocation['pid'];
                } else {
                    BackendUtility::fixVersioningPid('pages', $row);
                }
            }
        }
        $db->sql_free_result($res);
        return $row;
    }

    /**
     * Determine scope from current paramset
     *
     * @param array $params
     *
     * @return integer
     */
    protected function getScope(array $params)
    {
        switch ($params['table']) {
            case 'pages':
                $scope = \Extension\Templavoila\Domain\Model\AbstractDataStructure::SCOPE_PAGE;
            break;
            case 'tt_content':
                $scope = \Extension\Templavoila\Domain\Model\AbstractDataStructure::SCOPE_FCE;
            break;
            default:
                $scope = \Extension\Templavoila\Domain\Model\AbstractDataStructure::SCOPE_UNKNOWN;
        }
        return $scope;
    }

    /**
     * Find relevant removeItems blocks for a certain field with the given paramst
     *
     * @param array $params
     * @param string $field
     *
     * @return array
     */
    protected function getRemoveItems($params, $field)
    {
        $pid = $params['row'][$params['table'] == 'pages' ? 'uid' : 'pid'];
        $modTSConfig = BackendUtility::getModTSconfig($pid, 'TCEFORM.' . $params['table'] . '.' . $field . '.removeItems');

        return GeneralUtility::trimExplode(',', $modTSConfig['value'], true);
    }


    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
