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
        // Check for existence of fields tx_templavoilaplus_ds/tx_templavoilaplus_to on pages/tt_content
        // Check of existence of table tx_templavoila_datastructure and tx_templavoila_tmplobj
        // Check for configuration staticDS = 1 and content of the configured paths
        // Check for storage_pid's to determine how much extensions we need to generate and/or need mapping into Site Management
        // Check database if the found ds/to are in usage, give the possibility to delete them?
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
