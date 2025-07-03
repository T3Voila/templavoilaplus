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
                [$this, 'migrateRequiredFlag'],
                [$this, 'migrateNullFlag'],
                [$this, 'migrateEmailFlagToEmailType'],
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

    /**
     * Migrates [config][eval] = 'required' to [config][required] = true and removes 'required' from [config][eval].
     * If [config][eval] becomes empty, it will be removed completely.
     */
    public function migrateRequiredFlag(array &$element): bool
    {
        $changed = false;
        if (GeneralUtility::inList($element['config']['eval'] ?? '', 'required')) {
            $evalList = GeneralUtility::trimExplode(',', $element['config']['eval'], true);
            // Remove "required" from $evalList
            $evalList = array_filter($evalList, static function (string $eval) {
                return $eval !== 'required';
            });
            if ($evalList !== []) {
                // Write back filtered 'eval'
                $element['config']['eval'] = implode(',', $evalList);
            } else {
                // 'eval' is empty, remove whole configuration
                unset($element['config']['eval']);
            }

            $element['config']['required'] = true;
        }
        return $changed;
    }

    /**
     * Migrates [config][eval] = 'null' to [config][nullable] = true and removes 'null' from [config][eval].
     * If [config][eval] becomes empty, it will be removed completely.
     */
    public function migrateNullFlag(array &$element): bool
    {
        $changed = false;
        if (GeneralUtility::inList($element['config']['eval'] ?? '', 'null')) {
            $evalList = GeneralUtility::trimExplode(',', $element['config']['eval'], true);
            // Remove "null" from $evalList
            $evalList = array_filter($evalList, static function (string $eval) {
                return $eval !== 'null';
            });
            if ($evalList !== []) {
                // Write back filtered 'eval'
                $element['config']['eval'] = implode(',', $evalList);
            } else {
                // 'eval' is empty, remove whole configuration
                unset($element['config']['eval']);
            }

            $element['config']['nullable'] = true;
        }
        return $changed;
    }

    /**
     * Migrates [config][eval] = 'email' to [config][type] = 'email' and removes 'email' from [config][eval].
     * If [config][eval] contains 'trim', it will also be removed. If [config][eval] becomes empty, the option
     * will be removed completely.
     */
    public function migrateEmailFlagToEmailType(array &$element): bool
    {
        $changed = false;
        if (($element['config']['type'] ?? '') === 'input'
            && GeneralUtility::inList($element['config']['eval'] ?? '', 'email')
        ) {
            // Set the TCA type to "email"
            $element['config']['type'] = 'email';

            // Unset "max"
            unset($element['config']['max']);

            $evalList = GeneralUtility::trimExplode(',', $element['config']['eval'], true);
            $evalList = array_filter($evalList, static function (string $eval) {
                // Remove anything except "unique" and "uniqueInPid" from eval
                return in_array($eval, ['unique', 'uniqueInPid'], true);
            });

            if ($evalList !== []) {
                // Write back filtered 'eval'
                $element['config']['eval'] = implode(',', $evalList);
            } else {
                // 'eval' is empty, remove whole configuration
                unset($element['config']['eval']);
            }

        }
        return $changed;
    }
}
