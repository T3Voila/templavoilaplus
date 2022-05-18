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
 * Controller to migrate/update the DataStructure for TYPO3 v10 LTS
 *
 * @author Alexander Opitz <opitz@extrameile-gehen.de>
 */
class DataStructureV11Controller extends AbstractUpdateController
{
    protected $errors = [];

    protected function stepStartAction()
    {
    }

    protected function stepFinalAction()
    {
        /** @var DataStructureUpdateHandler */
        $handler = GeneralUtility::makeInstance(DataStructureUpdateHandler::class);
        $count = $handler->updateAllDs(
            [],
            [
                [$this, 'migrateColumnsConfig'],
                [$this, 'migrateLocalizeChildrenAtParentLocalization'],
                [$this, 'removeEnableMultiSelectFilterTextfieldConfiguration'],
            ]
        );

        $this->view->assignMultiple([
            'countStatic' => $countStatic,
            'count' => $count,
            'errors' => $this->errors,
        ]);
    }

    /**
     * Find columns fields that don't have a 'config' section at all, add
     * ['config']['type'] = 'none'; for those to enforce config
     */
    public function migrateColumnsConfig(array &$element): bool
    {
        $changed = false;
        if ((!isset($element['TCEforms']['config']) || !is_array($element['TCEforms']['config'])) && !isset($element['TCEforms']['type'])) {
            $element['TCEforms']['config'] = [
                'type' => 'none',
            ];
            $changed = true;
        }
        return $changed;
    }

    /**
     * Option $TCA[$table]['columns'][$columnName]['config']['behaviour']['localizeChildrenAtParentLocalization']
     * is always on, so this option can be removed.
     */
    public function migrateLocalizeChildrenAtParentLocalization(array &$element): bool
    {
        $changed = false;
        if (isset($element['TCEforms']['config']['behaviour']['localizeChildrenAtParentLocalization'])) {
            unset($element['TCEforms']['config']['behaviour']['localizeChildrenAtParentLocalization']);
            $changed = true;
        }
        return $changed;
    }

    /**
     * Removes configuration removeEnableMultiSelectFilterTextfield
     */
    public function removeEnableMultiSelectFilterTextfieldConfiguration(array &$element): bool
    {
        $changed = false;
        if (isset($element['TCEforms']['config']['enableMultiSelectFilterTextfield'])) {
            unset($element['TCEforms']['config']['enableMultiSelectFilterTextfield']);
            $changed = true;
        }
        return $changed;
    }
}
