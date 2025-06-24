<?php

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

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller to show the switch dialog.
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class UpdateController extends Update\AbstractUpdateController
{
    /**
     * List all available configurations for templates
     */
    public function infoAction(): ResponseInterface
    {
        $this->moduleTemplate->assign('pageTitle', 'TemplaVoilà! Plus - Update Scripts');
        $this->moduleTemplate->assign('hasServerMigrationFile', $this->hasServerMigrationFile());
        $this->moduleTemplate->assign('isMigrationPossible', $this->isMigrationPossible());

        return $this->moduleTemplate->renderResponse('info');
    }

    protected function hasServerMigrationFile(): bool
    {
        $registeredExtensions = \Tvp\TemplaVoilaPlus\Utility\ExtensionUtility::getRegisteredExtensions();
        foreach ($registeredExtensions as $extensionKey => $path) {
            if (is_file($path . '/ServerMigration.json')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Only basic check, more inside TemplaVoilaPlus8Controller
     */
    protected function isMigrationPossible(): bool
    {
        $columns = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_templavoilaplus_datastructure')
            ->getSchemaManager()
            ->listTableColumns('tx_templavoilaplus_datastructure');
        if (count($columns) !== 0) {
            return true;
        }
        return false;
    }
}
