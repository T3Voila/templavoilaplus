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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Controller to migrate/update the DataStructure
 * @TODO We need more migrations, see TcaMigration in TYPO3 Core
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class DataStructureV8UpdateController extends StepUpdateController
{
    protected $errors = [];

    protected function stepStart()
    {
    }

    protected function stepFinal()
    {

        // Pages
        $toFix = [];
        $rows = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTgetRows(
            'tx_templavoilaplus_ds,tx_templavoilaplus_next_ds',
            'pages',
            'tx_templavoilaplus_ds != "" OR tx_templavoilaplus_next_ds != ""',
            'tx_templavoilaplus_ds,tx_templavoilaplus_next_ds'
        );

        foreach ($rows as $row) {
            if (!empty($row['tx_templavoilaplus_ds'])
                && !isset($toFix[$row['tx_templavoilaplus_ds']])
                && !StringUtility::beginsWith($row['tx_templavoilaplus_ds'], 'FILE:')
            ) {
                $toFix[$row['tx_templavoilaplus_ds']] = 'FILE:' . $row['tx_templavoilaplus_ds'];
            }
            if (!empty($row['tx_templavoilaplus_next_ds'])
                && !isset($toFix[$row['tx_templavoilaplus_next_ds']])
                && !StringUtility::beginsWith($row['tx_templavoilaplus_next_ds'], 'FILE:')
            ) {
                $toFix[$row['tx_templavoilaplus_next_ds']] = 'FILE:' . $row['tx_templavoilaplus_next_ds'];
            }
        }

        foreach ($toFix as $from => $to) {
            TemplaVoilaUtility::getDatabaseConnection()->exec_UPDATEquery(
                'pages',
                'tx_templavoilaplus_ds=' . TemplaVoilaUtility::getDatabaseConnection()->fullQuoteStr($from, 'pages'),
                array('tx_templavoilaplus_ds' => $to)
            );
            TemplaVoilaUtility::getDatabaseConnection()->exec_UPDATEquery(
                'pages',
                'tx_templavoilaplus_next_ds=' . TemplaVoilaUtility::getDatabaseConnection()->fullQuoteStr($from, 'pages'),
                array('tx_templavoilaplus_next_ds' => $to)
            );
        }

        $count = count($toFix);

        // tt_content
        $toFix = [];
        $rows = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTgetRows(
            'tx_templavoilaplus_ds',
            'tt_content',
            'tx_templavoilaplus_ds != ""',
            'tx_templavoilaplus_ds'
        );

        foreach ($rows as $row) {
            if (!empty($row['tx_templavoilaplus_ds'])
                && !isset($toFix[$row['tx_templavoilaplus_ds']])
                && !StringUtility::beginsWith($row['tx_templavoilaplus_ds'], 'FILE:')
            ) {
                $toFix[$row['tx_templavoilaplus_ds']] = 'FILE:' . $row['tx_templavoilaplus_ds'];
            }
        }

        foreach ($toFix as $from => $to) {
            TemplaVoilaUtility::getDatabaseConnection()->exec_UPDATEquery(
                'tt_content',
                'tx_templavoilaplus_ds=' . TemplaVoilaUtility::getDatabaseConnection()->fullQuoteStr($from, 'tt_content'),
                array('tx_templavoilaplus_ds' => $to)
            );
        }

        $count += count($toFix);

        $this->fluid->assignMultiple([
            'count' => $count,
            'errors' => $this->errors,
        ]);
    }
}
