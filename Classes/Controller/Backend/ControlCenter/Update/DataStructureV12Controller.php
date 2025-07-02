<?php

namespace Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\Update;

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

use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Controller to migrate/update the DataStructure for TYPO3 v12 LTS
 *
 * @author Alexander Opitz <opitz.alexander@googlemail.com>
 */
class DataStructureV12Controller extends AbstractUpdateController
{
    protected $errors = [];

    protected function stepStartAction()
    {
        return $this->moduleTemplate->renderResponse('stepStart');
    }

    protected function stepFinalAction()
    {
        /** @var DataStructureUpdateHandler */
        $handler = GeneralUtility::makeInstance(DataStructureUpdateHandler::class);
        $count = $handler->updateAllDs(
            [],
            [
                [$this, 'migrateInternalTypeFolderToTypeFolder'],
            ]
        );

        $this->moduleTemplate->assignMultiple([
            'count' => $count,
            'hasErrors' => !empty($this->errors),
            'errors' => $this->errors,
        ]);
        return $this->moduleTemplate->renderResponse('stepFinal');
    }

    /**
     * Migrates [config][internal_type] = 'folder' to [config][type] = 'folder'.
     * Also removes [config][internal_type] completely, if present.
     */
    public function migrateInternalTypeFolderToTypeFolder(array &$element): bool
    {
        $changed = false;
        if (($element['config']['type'] ?? '') === 'group' && isset($element['config']['internal_type'])) {
            if ($element['config']['internal_type'] === 'folder') {
                $element['config']['type'] = 'folder';
            }
            unset($element['config']['internal_type']);
            $changed = true;
        }
        return $changed;
    }
}
