<?php
namespace Ppi\TemplaVoilaPlus\Service\ItemProcFunc;

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

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Class/Function which manipulates the item-array for table/field tx_templavoilaplus_tmplobj_datastructure.
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
        $showAdminAll = $this->getShowAdminAllItems($params, substr($params['field'], 0, -2) . 'ds');

        $dsRepo = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Domain\Repository\DataStructureRepository::class);
        $dsList = $dsRepo->getAll();

        $params['items'] = [
            ['', ''],
        ];

        foreach ($dsList as $dsObj) {
            /** @var \Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure $dsObj */
            if ($dsObj->isPermittedForUser($params['row'], $removeDSItems, $showAdminAll)) {
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
        $templateRef = $GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoilaplus_cm1']['piKey2DSMap'][$piKey]; // This should be a value of a Data Structure.
        $storagePid = $this->getStoragePid($params);

        if ($templateRef && $storagePid) {
            // Select all Template Object Records from storage folder, which are parent records and which has the data structure for the plugin:
            $res = $this->getDatabaseConnection()->exec_SELECTquery(
                'title,uid,previewicon',
                'tx_templavoilaplus_tmplobj',
                'tx_templavoilaplus_tmplobj.pid=' . $storagePid . ' AND tx_templavoilaplus_tmplobj.datastructure=' .  $this->getDatabaseConnection()->fullQuoteStr($templateRef, 'tx_templavoilaplus_tmplobj') . ' AND tx_templavoilaplus_tmplobj.parent=0',
                '',
                'tx_templavoilaplus_tmplobj.title'
            );

            // Traverse these and add them. Icons are set too if applicable.
            while (false != ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res))) {
                if ($row['previewicon']) {
                    $icon = '../' . $GLOBALS['TCA']['tx_templavoilaplus_tmplobj']['columns']['previewicon']['config']['uploadfolder'] . '/' . $row['previewicon'];
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

        $dsRepo = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Domain\Repository\DataStructureRepository::class);
        $dsList = $dsRepo->getDatastructuresByStoragePidAndScope($storagePid, $scope);

        $params['items'] = [
            ['', ''],
        ];

        foreach ($dsList as $dsObj) {
            /** @var \Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure $dsObj */
            if ($dsObj->isPermittedForUser($params['row'], $removeDSItems, $showAdminAll)) {
                $params['items'][] = array(
                    $dsObj->getLabel(),
                    (!is_numeric($dsObj->getKey()) ? 'FILE:' : '') . $dsObj->getKey(),
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
        $this->conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoilaplus']);

        $this->templateObjectItemsProcFuncForAllDSes($params, $pObj);
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

        $dsRepo = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Domain\Repository\DataStructureRepository::class);
        $dsList = $dsRepo->getDatastructuresByStoragePidAndScope($storagePid, $scope);

        foreach ($dsList as $dsObj) {
            /** @var \Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure $dsObj */
            if (!$dsObj->isPermittedForUser($params['row'], $removeDSItems, $showAdminAll)) {
                continue;
            }
            $curDS = [
                'field' => $params['field'],
                'row' => $params['row'],
                'items' => [[
                    $dsObj->getLabel(),
                    '--div--'
                ]],
            ];

            $this->addToItems($curDS, $dsObj, $storagePid);

            if (count($curDS) > 1) {
                $params['items'] = array_merge($params['items'], $curDS['items']);
            }
        }
    }

    /**
     * Adds selectable TOs as items into the list (depending on dsObj)
     *
     * @param array $params Parameters for itemProcFunc
     * @param int $storagePid
     *
     * @return void
     */
    protected function addToItems(array &$params, $dsObj, $storagePid)
    {
        $removeTOItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'to');
        $toRepo = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Domain\Repository\TemplateRepository::class);

        $defaultIcon = 'EXT:templavoilaplus/Resources/Public/Icons/TemplateFce48.png';
        if ($dsObje->getScope === \Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure::SCOPE_PAGE) {
            $defaultIcon = 'EXT:templavoilaplus/Resources/Public/Icons/TemplatePage48.png';
        }

        $toList = $toRepo->getTemplatesByDatastructure($dsObj, $storagePid);
        foreach ($toList as $toObj) {
            /** @var \Ppi\TemplaVoilaPlus\Domain\Model\Template $toObj */
            if (!$toObj->hasParent() && $toObj->isPermittedForUser($params['row'], $removeTOItems, $showAdminAll)) {
                $params['items'][] = [
                    $toObj->getLabel(),
                    $toObj->getKey(),
                    $toObj->getIcon() ? $toObj->getIcon() : $defaultIcon,
                ];
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
        // Check for alternative storage folder
        $field = $params['table'] == 'pages' ? 'uid' : 'pid';

        $pageTsConfig = BackendUtility::getPagesTSconfig($params['row'][$field]);
        $storagePid = $pageTsConfig['tx_templavoilaplus.']['storagePid'] ? : false;

        if (MathUtility::canBeInterpretedAsInteger($storagePid)) {
            return (int)$storagePid;
        }

        return 0;
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
                $scope = \Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure::SCOPE_PAGE;
                break;
            case 'tt_content':
                $scope = \Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure::SCOPE_FCE;
                break;
            default:
                $scope = \Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure::SCOPE_UNKNOWN;
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

        $pageTsConfig = BackendUtility::getPagesTSconfig($pid);
        $removeItems = $pageTsConfig['TCEFORM.'][$params['table'] . '.'][$field . '.']['removeItems'] ? :'';

        return GeneralUtility::trimExplode(',', $removeItems, true);
    }


    /**
     * Find relevant removeItems blocks for a certain field with the given paramst
     *
     * @param array $params
     * @param string $field
     *
     * @return bool
     */
    protected function getShowAdminAllItems($params, $field)
    {
        $pid = $params['row'][$params['table'] == 'pages' ? 'uid' : 'pid'];
        $pageTsConfig = BackendUtility::getPagesTSconfig($pid);
        $showAdminAllItems = $pageTsConfig['TCEFORM.'][$params['table'] . '.'][$field . '.']['showAdminAllItems'] ? :'';

        return (bool) $showAdminAllItems;
    }



    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return TemplaVoilaUtility::getDatabaseConnection();
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
