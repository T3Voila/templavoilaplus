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
 * Controller to migrate/update the DataStructure
 * @TODO We need more migrations, see TcaMigration in TYPO3 Core
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class DataStructureV8Controller extends AbstractUpdateController
{
    protected $errors = [];

    protected function stepStartAction()
    {
    }

    protected function stepFinalAction()
    {
        $handler = GeneralUtility::makeInstance(DataStructureUpdateHandler::class);
        $count = $handler->updateAllDs(
            [],
            [
                [$this, 'migrateDefaultExtrasRteTransFormOptions'],
                [$this, 'migrateLastPiecesOfDefaultExtras'],
                [$this, 'cleanupEmptyDefaultExtraFields'],
            ]
        );

        $this->view->assignMultiple([
            'countStatic' => $countStatic,
            'count' => $count,
            'errors' => $this->errors,
        ]);
    }

    /**
     * Migrate defaultExtras "richtext:rte_transform[mode=ts_css]" and similar stuff like
     * "richtext:rte_transform[mode=ts_css]" to "richtext:rte_transform"
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateDefaultExtrasRteTransFormOptions
     *
     * @param array $element The field element TCA
     * @return bool True if changed otherwise false
     */
    public function migrateDefaultExtrasRteTransFormOptions(array &$element)
    {
        $changed = false;
        if (
            isset($element['TCEforms']['defaultExtras']) // if defaultExtras is set
        ) {
            $defaultExtrasArray = GeneralUtility::trimExplode(':', $element['TCEforms']['defaultExtras'], true);
            foreach ($defaultExtrasArray as $part => $defaultExtrasField) {
                if (substr($defaultExtrasField, 0, 8) === 'richtext') {
                    $element['TCEforms']['config']['enableRichtext'] = true;
                    $element['TCEforms']['config']['richtextConfiguration'] = 'default';
                    unset($defaultExtrasArray[$part]);
                    $changed = true;
                } elseif (substr($defaultExtrasField, 0, 13) === 'rte_transform') {
                    unset($defaultExtrasArray[$part]);
                    $changed = true;
                }
            }
        }

        if ($changed) {
            $element['TCEforms']['defaultExtras'] = implode(':', $defaultExtrasArray);
        }

        return $changed;
    }

    /**
     * Migrate defaultExtras "nowrap", "enable-tab", "fixed-font". Then drop all
     * remaining "defaultExtras", there shouldn't exist anymore.
     * From TYPO3 8 LTS
     *   TYPO3\CMS\Core\Migrations\TcaMigration::migrateLastPiecesOfDefaultExtras
     *
     * @param array $tca
     * @return array
     */
    public function migrateLastPiecesOfDefaultExtras(array &$element)
    {
        $changed = false;
        if (
            isset($element['TCEforms']['defaultExtras']) // if defaultExtras is set
        ) {
            $defaultExtrasArray = GeneralUtility::trimExplode(':', $element['TCEforms']['defaultExtras'], true);
            foreach ($defaultExtrasArray as $part => $defaultExtrasSetting) {
                if ($defaultExtrasSetting === 'rte_only') {
                    // Not supported anymore
                    unset($defaultExtrasArray[$part]);
                } elseif ($defaultExtrasSetting === 'nowrap') {
                    $element['TCEforms']['config']['wrap'] = 'off';
                } elseif ($defaultExtrasSetting === 'enable-tab') {
                    $element['TCEforms']['config']['enableTabulator'] = true;
                } elseif ($defaultExtrasSetting === 'fixed-font') {
                    $element['TCEforms']['config']['fixedFont'] = true;
                } else {
                    $this->errors[] = 'The defaultExtras setting \'' . $defaultExtrasSetting . '\' is unknown and has been dropped.';
                }
                unset($defaultExtrasArray[$part]);
                $changed = true;
            }
        }

        if ($changed) {
            $element['TCEforms']['defaultExtras'] = implode(':', $defaultExtrasArray);
        }

        return $changed;
    }

    /**
     * removes defaultExtra element, if empty
     *
     * @param array $element The field element TCA
     * @return bool True if changed otherwise false
     */
    public function cleanupEmptyDefaultExtraFields(array &$element)
    {
        $changed = false;

        if (
            isset($element['TCEforms']['defaultExtras']) // if defaultExtras is set
            && empty($element['TCEforms']['defaultExtras'])  // but is empty
        ) {
            unset($element['TCEforms']['defaultExtras']);
            $changed = true;
        }

        return $changed;
    }
}
