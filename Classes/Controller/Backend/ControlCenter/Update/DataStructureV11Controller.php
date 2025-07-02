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
 * Controller to migrate/update the DataStructure for TYPO3 v11 LTS
 *
 * @author Alexander Opitz <opitz@extrameile-gehen.de>
 */
class DataStructureV11Controller extends AbstractUpdateController
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
                [$this, 'migrateSpecialLanguagesToTcaTypeLanguage'],
                [$this, 'removeShowRemovedLocalizationRecords'],
                [$this, 'migrateFileFolderConfiguration'],
                [$this, 'migrateLevelLinksPosition'],
                [$this, 'migrateRootUidToStartingPoints'],
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
     * Replaces $TCA[$mytable][columns][field][config][special] = 'languages' with
     * $TCA[$mytable][columns][field][config][type] = 'language'
     */
    public function migrateSpecialLanguagesToTcaTypeLanguage(array &$element): bool
    {
        $changed = false;
        if (
            (string)($element['TCEforms']['config']['type'] ?? '') === 'select'
            && (string)($element['TCEforms']['config']['special'] ?? '') === 'languages'
        ) {
            $element['TCEforms']['config'] = [
                'type' => 'language',
            ];
            $changed = true;
        }
        return $changed;
    }

    public function removeShowRemovedLocalizationRecords(array &$element): bool
    {
        $changed = false;
        if (
            (string)($element['TCEforms']['config']['type'] ?? '') === 'inline'
            && isset($element['TCEforms']['config']['appearance']['showRemovedLocalizationRecords'])
        ) {
            unset($element['TCEforms']['config']['appearance']['showRemovedLocalizationRecords']);
            $changed = true;
        }
        return $changed;
    }

    /**
     * Moves the "fileFolder" configuration of TCA columns type=select
     * into sub array "fileFolderConfig", while renaming those options.
     */
    public function migrateFileFolderConfiguration(array &$element): bool
    {
        $changed = false;
        if (
            (string)($element['TCEforms']['config']['type'] ?? '') === 'select'
            && isset($element['TCEforms']['config']['fileFolder'])
        ) {
            $element['TCEforms']['config']['fileFolderConfig'] = [
                'folder' => $element['TCEforms']['config']['fileFolder'],
            ];
            unset($element['TCEforms']['config']['fileFolder']);
            if (isset($element['TCEforms']['config']['fileFolder_extList'])) {
                $element['TCEforms']['config']['fileFolderConfig']['allowedExtensions'] = $element['TCEforms']['config']['fileFolder_extList'];
                unset($element['TCEforms']['config']['fileFolder_extList']);
            }
            if (isset($element['TCEforms']['config']['fileFolder_recursions'])) {
                $element['TCEforms']['config']['fileFolderConfig']['depth'] = $element['TCEforms']['config']['fileFolder_recursions'];
                unset($element['TCEforms']['config']['fileFolder_recursions']);
            }
            $changed = true;
        }

        return $changed;
    }

    /**
     * The [appearance][levelLinksPosition] option can be used
     * to select the position of the level links. This option
     * was previously misused to disable all those links by
     * setting it to "none". Since all of those links can be
     * disabled by a dedicated option, e.g. showNewRecordLink,
     * this wizard sets those options to false and unsets the
     * invalid levelLinksPosition value.
     */
    public function migrateLevelLinksPosition(array &$element): bool
    {
        $changed = false;
        if (
            (string)($element['TCEforms']['config']['type'] ?? '') === 'inline'
            && (string)($element['TCEforms']['config']['appearance']['levelLinksPosition'] ?? '') === 'none'
        ) {
            unset($element['TCEforms']['config']['appearance']['levelLinksPosition']);
            $element['TCEforms']['config']['appearance']['showAllLocalizationLink'] = false;
            $element['TCEforms']['config']['appearance']['showSynchronizationLink'] = false;
            $element['TCEforms']['config']['appearance']['showNewRecordLink'] = false;
            $changed = true;
        }

        return $changed;
    }

    /**
     * If a column has [treeConfig][rootUid] defined, migrate to [treeConfig][startingPoints] on the same level.
     */
    public function migrateRootUidToStartingPoints(array &$element): bool
    {
        $changed = false;
        if (
            (int)($element['TCEforms']['config']['treeConfig']['rootUid'] ?? 0) !== 0
            && in_array((string)($element['TCEforms']['config']['type'] ?? ''), ['select', 'category'], true)
        ) {
            $element['TCEforms']['config']['treeConfig']['startingPoints'] = (string)(int)($element['TCEforms']['config']['treeConfig']['rootUid']);
            unset($element['TCEforms']['config']['treeConfig']['rootUid']);
            $changed = true;
        }

        return $changed;
    }
}
