<?php
namespace Ppi\TemplaVoilaPlus\Controller\Update;

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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

use Ppi\TemplaVoilaPlus\Domain\Repository\DataStructureRepository;

/**
 * Controller to migrate/update from old TemplaVoila
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class OldTemplavoilaUpdateController extends StepUpdateController
{
    protected $errors = [];

    protected function stepStart()
    {
        $data = [
            'old' => [
                'ds' => $this->getCountDs(true),
                'to' => $this->getCountTo(true),
            ],
            'new' => [
                'ds' => $this->getCountDs(),
                'to' => $this->getCountTo(),
            ],
        ];

        if ($data['old']['ds'] === false || $data['old']['to'] === false) {
            $this->errors[] = 'Old TemplaVoilà tables not found. Did you already remove them?';
        }
        if ($data['new']['ds'] === false || $data['new']['to'] === false) {
            $this->errors[] = 'New TemplaVoilà Plus 7.x tables not found. Did you install plugin correctly?';
        }
        if (ExtensionManagementUtility::isLoaded('templavoila')) {
            $this->errors[] = 'Old TemplaVoilà is loaded, please unload this extension before (but do not delete the tables from database).';
        }

        $data['errors'] = $this->errors;
        $this->fluid->assignMultiple($data);
    }

    protected function stepFinal()
    {
        if ($this->migrateConfiguration() === false) {
            $this->errors[] = 'Configuration couldn\'t be migrated. Maybe your LocalConfiguration.php isn\'t writeable.';
        }
        if ($this->migrateDsTo() === false) {
            $this->errors[] = 'Error while copy data from old to new database tables. Maybe you started from an older TemplaVoilà version?';
        }
        if (($migratedTtContent = $this->migrateTtContent()) === false) {
            $this->errors[] = 'Error while migrate tt_content. Maybe you started from an older TemplaVoilà version?';
        }
        if (($migratedCType = $this->migrateCType()) === false) {
            $this->errors[] = 'Error while migrate CType.';
        }
        if (($migratedPages = $this->migratePages()) === false) {
            $this->errors[] = 'Error while migrate pages. Maybe you started from an older TemplaVoilà version?';
        }
        if (($migratedGroups = $this->migrateGroups()) === false) {
            $this->errors[] = 'Error while migrate groups. Maybe you started from an older TemplaVoilà version?';
        }
        if (($migratedUserRights = $this->migrateUserRights()) === false) {
            $this->errors[] = 'Error while migrate user rights.';
        }
        if (($migratedGroupRights = $this->migrateGroupRights()) === false) {
            $this->errors[] = 'Error while migrate group rights.';
        }
        if (($migrateGroupTableSelect = $this->migrateGroupTableSelect()) === false) {
            $this->errors[] = 'Error while migrate group table select.';
        }
        if (($migrateGroupTableModify = $this->migrateGroupTableModify()) === false) {
            $this->errors[] = 'Error while migrate group table modify.';
        }
        if (($migrateGroupNonExcludeFields = $this->migrateGroupNonExcludeFields()) === false) {
            $this->errors[] = 'Error while migrate group non exclude fields.';
        }
        if (($migratedDsData = $this->migrateDataStructureData()) === false) {
            $this->errors[] = 'Error while migrate data of data structures.';
        }
        if (($migratedToData = $this->migrateTemplateObjectLocalProcessing()) === false) {
            $this->errors[] = 'Error while migrate local processing data of template objects.';
        }
        if (($migratedUploadFiles = $this->migrateFiles()) === false) {
            $this->errors[] = 'Error while copy files from uploads/tx_templavoila to uploads/tx_templavoilaplus.';
        }

        $this->fluid->assignMultiple([
            'migratedDs' => $this->getCountDs(),
            'migratedTo' => $this->getCountTo(),
            'migratedTtContent' => $migratedTtContent,
            'migratedCType' => $migratedCType,
            'migratedPages' => $migratedPages,
            'migratedGroups' => $migratedGroups,
            'migratedUserRights' => $migratedUserRights,
            'migratedGroupRights' => $migratedGroupRights,
            'migrateGroupTableSelect' => $migrateGroupTableSelect,
            'migrateGroupTableModify' => $migrateGroupTableModify,
            'migrateGroupNonExcludeFields' => $migrateGroupNonExcludeFields,
            'migratedDsData' => $migratedDsData,
            'migratedToData' => $migratedToData,
            'migratedUploadFiles' => $migratedUploadFiles,
            'errors' => $this->errors,
        ]);
    }

    // Counts
    private function getCountDs($old = false)
    {
        $table = 'tx_templavoilaplus_datastructure';
        if ($old) {
            $table = 'tx_templavoila_datastructure';
        }

        return $this->getCountTable($table);
    }


    private function getCountTo($old = false)
    {
        $table = 'tx_templavoilaplus_tmplobj';
        if ($old) {
            $table = 'tx_templavoila_tmplobj';
        }

        return $this->getCountTable($table);
    }

    private function getCountTable($table)
    {
        return $this->getDatabaseConnection()->exec_SELECTcountRows(
            '*',
            $table,
            '1=1 ' . BackendUtility::deleteClause($table)
        );
    }

    // Migrations
    private function migrateConfiguration()
    {
        $oldconfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);
        if (is_array($oldconfig) && count($oldconfig) > 1) {
            // Config available so migrate
            $newconfig = serialize($oldconfig);
            $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoilaplus'] = $newconfig;
            $configurationManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class);
            return $configurationManager->setLocalConfigurationValueByPath('EXT/extConf/templavoilaplus', $newconfig);
        }

        return true;
    }

    private function migrateDsTo()
    {
        $fieldsDs = 'uid,pid,t3ver_oid,t3ver_id,t3ver_wsid,t3ver_label,t3ver_state,t3ver_stage,t3ver_count,'
            . 't3ver_tstamp,t3_origuid,tstamp,crdate,cruser_id,deleted,sorting,title,dataprot,'
            . 'scope,previewicon,belayout';
        $fieldsTo = 'uid,pid,t3ver_oid,t3ver_id,t3ver_wsid,t3ver_label,t3ver_state,t3ver_stage,t3ver_count,'
            . 't3ver_tstamp,t3_origuid,tstamp,crdate,cruser_id,fileref_mtime,deleted,sorting,title,'
            . 'datastructure,fileref,templatemapping,previewicon,description,rendertype,sys_language_uid,parent,'
            . 'rendertype_ref,localprocessing,fileref_md5,belayout';

        return $this->getDatabaseConnection()->exec_TRUNCATEquery('tx_templavoilaplus_datastructure')
            && $this->getDatabaseConnection()->exec_TRUNCATEquery('tx_templavoilaplus_tmplobj')
            && $this->getDatabaseConnection()->sql_query(
                'INSERT INTO tx_templavoilaplus_datastructure (' . $fieldsDs . ')'
                . ' SELECT ' . $fieldsDs . ' FROM tx_templavoila_datastructure WHERE 1=1 '
                . BackendUtility::deleteClause('tx_templavoila_datastructure')
            )
            && $this->getDatabaseConnection()->sql_query(
                'INSERT INTO tx_templavoilaplus_tmplobj (' . $fieldsTo . ')'
                . ' SELECT ' . $fieldsTo . ' FROM tx_templavoila_tmplobj WHERE 1=1 '
                . BackendUtility::deleteClause('tx_templavoila_tmplobj')
            );
    }

    private function migrateTtContent()
    {
        $result = $this->getDatabaseConnection()->sql_query(
            'update tt_content set
                tx_templavoilaplus_ds = tx_templavoila_ds,
                tx_templavoilaplus_to = tx_templavoila_to,
                tx_templavoilaplus_flex = tx_templavoila_flex,
                tx_templavoilaplus_pito = tx_templavoila_pito'
        );

        if ($result) {
            return $this->getDatabaseConnection()->sql_affected_rows();
        }

        return false;
    }

    private function migrateCType()
    {
        $result = $this->getDatabaseConnection()->sql_query(
            'update tt_content set CType = "templavoilaplus_pi1" where CType = "templavoila_pi1"'
        );

        if ($result) {
            return $this->getDatabaseConnection()->sql_affected_rows();
        }

        return false;
    }

    private function migratePages()
    {
        $result = $this->getDatabaseConnection()->sql_query(
            'update pages set
                tx_templavoilaplus_ds = tx_templavoila_ds,
                tx_templavoilaplus_to = tx_templavoila_to,
                tx_templavoilaplus_next_ds = tx_templavoila_next_ds,
                tx_templavoilaplus_next_to = tx_templavoila_next_to,
                tx_templavoilaplus_flex = tx_templavoila_flex'
        );

        if ($result) {
            return $this->getDatabaseConnection()->sql_affected_rows();
        }

        return false;
    }

    private function migrateGroups()
    {
        $result = $this->getDatabaseConnection()->sql_query(
            'update be_groups set
                tx_templavoilaplus_access = tx_templavoila_access'
        );

        if ($result) {
            return $this->getDatabaseConnection()->sql_affected_rows();
        }

        return false;
    }

    private function migrateUserRights()
    {
        return $this->migrateModuleRights('be_users', 'userMods');
    }

    private function migrateGroupRights()
    {
        return $this->migrateModuleRights('be_groups', 'groupMods');
    }

    private function migrateModuleRights($table, $field)
    {
        return $this->migrateTableFieldSet(
            $table,
            $field,
            [
                'web_txtemplavoilaM1' => 'web_txtemplavoilaplusLayout',
                'web_txtemplavoilaM2' => 'web_txtemplavoilaplusCenter',
            ]
        );
    }

    private function migrateGroupTableSelect()
    {
        return $this->migrateTableRights('be_groups', 'tables_select');
    }

    private function migrateGroupTableModify()
    {
        return $this->migrateTableRights('be_groups', 'tables_modify');
    }

    private function migrateGroupNonExcludeFields()
    {
        return $this->migrateTableFieldSet(
            'be_groups',
            'non_exclude_fields',
            [
                'pages:tx_templavoila_ds' => 'pages:tx_templavoilaplus_ds',
                'pages:tx_templavoila_to' => 'pages:tx_templavoilaplus_to',
                'pages:tx_templavoila_next_ds' => 'pages:tx_templavoilaplus_next_ds',
                'pages:tx_templavoila_next_to' => 'pages:tx_templavoilaplus_next_to',
                'pages:tx_templavoila_flex' => 'pages:tx_templavoilaplus_flex',
                'tt_content:tx_templavoila_ds' => 'tt_content:tx_templavoilaplus_ds',
                'tt_content:tx_templavoila_to' => 'tt_content:tx_templavoilaplus_to',
                'tt_content:tx_templavoila_flex' => 'tt_content:tx_templavoilaplus_flex',
                'tt_content:tx_templavoila_pito' => 'tt_content:tx_templavoilaplus_pito',
                'tx_templavoila_datastructure:belayout' => 'tx_templavoilaplus_datastructure:belayout',
                'tx_templavoila_datastructure:dataprot' => 'tx_templavoilaplus_datastructure:dataprot',
                'tx_templavoila_datastructure:scope' => 'tx_templavoilaplus_datastructure:scope',
                'tx_templavoila_tmplobj:belayout' => 'tx_templavoilaplus_tmplobj:belayout',
                'tx_templavoila_tmplobj:datastructure' => 'tx_templavoilaplus_tmplobj:datastructure',
                'tx_templavoila_tmplobj:fileref' => 'tx_templavoilaplus_tmplobj:fileref',
                'tx_templavoila_tmplobj:localprocessing' => 'tx_templavoilaplus_tmplobj:localprocessing',
                'tx_templavoila_tmplobj:parent' => 'tx_templavoilaplus_tmplobj:parent',
                'tx_templavoila_tmplobj:rendertype' => 'tx_templavoilaplus_tmplobj:rendertype',
                'tx_templavoila_tmplobj:rendertype_ref' => 'tx_templavoilaplus_tmplobj:rendertype_ref',
                'tx_templavoila_tmplobj:sys_language_uid' => 'tx_templavoilaplus_tmplobj:sys_language_uid',            ]
        );
    }

    private function migrateTableRights($table, $field)
    {
        return $this->migrateTableFieldSet(
            $table,
            $field,
            [
                'tx_templavoila_datastructure' => 'tx_templavoilaplus_datastructure',
                'tx_templavoila_tmplobj' => 'tx_templavoilaplus_tmplobj',
            ]
        );
    }

    private function migrateTableFieldSet($table, $field, $value2value)
    {
        $where = [];

        // Get all entries which need convertation
        foreach ($value2value as $oldVal => $unused) {
            $where[] = 'FIND_IN_SET("' . $oldVal . '", ' . $field . ')';
        }
        $result = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid,' . $field,
            $table,
            implode(' || ', $where)
        );

        // Convert all found rows
        if (is_array($result)) {
            foreach ($result as $row) {
                // replace web_txtemplavoilaM2 with web_txtemplavoilaplus
                $mods = GeneralUtility::trimExplode(',', $row[$field], true);
                foreach ($mods as $key => $mod) {
                    if (isset($value2value[$mod])) {
                        $mods[$key] = $value2value[$mod];
                    }
                }
                $values = [
                    $field => implode(',', $mods),
                ];
                $this->getDatabaseConnection()->exec_UPDATEquery($table, 'uid = ' . (int) $row['uid'], $values);
            }
            return count($result);
        }

        return false;
    }

    // Convert DS <T3DataStructure><ROOT><tx_templavoila>
    // Convert DS <T3DataStructure><ROOT><el><field..><tx_templavoila>
    private function migrateDataStructureData()
    {
        $handler = GeneralUtility::makeInstance(DataStructureUpdateHandler::class);
        return $handler->updateAllDs(
            [
                [$this, 'migrateRootData'],
            ],
            [
                [$this, 'migrateElementData'],
            ]
        );
    }

    // Convert DS <T3DataStructure><ROOT><tx_templavoila>
    // Convert DS <T3DataStructure><ROOT><el><field..><tx_templavoila>
    private function migrateTemplateObjectLocalProcessing()
    {
        $handler = GeneralUtility::makeInstance(DataStructureUpdateHandler::class);
        return $handler->updateAllToLocals(
            [
                [$this, 'migrateRootData'],
            ],
            [
                [$this, 'migrateElementData'],
            ]
        );
    }

    public function migrateRootData(&$data)
    {
        if (isset($data['ROOT']['tx_templavoila'])) {
            $this->changeArrayKey($data['ROOT'], 'tx_templavoila', 'tx_templavoilaplus');
            return true;
        }
        return false;
    }

    public function migrateElementData(&$element)
    {
        if (isset($element['tx_templavoila'])) {
            $this->changeArrayKey($element, 'tx_templavoila', 'tx_templavoilaplus');
            return true;
        }
        return false;
    }

    public function migrateFiles()
    {
        $pathOld = PATH_site . 'uploads/tx_templavoila/';
        $pathNew = PATH_site . 'uploads/tx_templavoilaplus/';

        // Check or create new directory existence
        if (!is_dir($pathNew)) {
            if (!GeneralUtility::mkdir($pathNew)) {
                $this->errors[] = "Could not create directory: " . $pathNew;
                return false;
            }
        }
        // Check writeability
        if (!is_writable($pathNew)) {
            $this->errors[] = "Could not write into new directory: " . $pathNew;
            return false;
        }
        // Check or old directory existence
        if (!is_dir($pathOld)) {
            $this->errors[] = "Could not found old upload directory: " . $pathOld;
            return false;
        }
        // Check readability
        if (!is_readable($pathOld)) {
            $this->errors[] = "Could not read from old upload directory: " . $pathOld;
            return false;
        }
        try {
            GeneralUtility::copyDirectory($pathOld, $pathNew);
        } catch (\Exception $e) {
            $this->errors[] = "Could not copy files: " . $e->getMessage();
            return false;
        }
        $iterator = new \FilesystemIterator($pathNew, \FilesystemIterator::SKIP_DOTS);
        return iterator_count($iterator);
    }

    public function changeArrayKey(array &$array, $keyOld, $keyNew)
    {
        $keys = array_keys($array);
        $pos = array_search($keyOld, $keys);
        $keys[$pos] = $keyNew;

        $array = array_combine($keys, $array);
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    private function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
