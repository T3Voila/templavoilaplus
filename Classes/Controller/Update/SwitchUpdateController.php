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
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Controller to show the switch dialog.
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class SwitchUpdateController extends AbstractUpdateController
{
    /**
     * @return string The HTML to be shown.
     */
    public function run()
    {
        $this->fluid->assignMultiple([
            'isMigrationPossible' => $this->isMigrationPossible(),
        ]);
        return parent::run();
    }

    /**
     * Check if old table exists and have non deleted content, which we could migrate
     *
     * @return bool
     */
    public function isMigrationPossible()
    {
        $table = 'tx_templavoila_tmplobj';
        $tableExistsResult = TemplaVoilaUtility::getDatabaseConnection()->sql_query('SHOW TABLES LIKE "' . $table . '"');
        if ($tableExistsResult->num_rows) {
            $count = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTcountRows(
                '*',
                $table,
                '1=1 ' . BackendUtility::deleteClause($table)
            );
            if ($count) {
                return true;
            }
        }
        return false;
    }
}
