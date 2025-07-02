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

use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\DataConfiguration;
use Tvp\TemplaVoilaPlus\Domain\Model\Place;
use Tvp\TemplaVoilaPlus\Service\ConfigurationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handles Updates in DataStructure via Callbacks
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class DataStructureUpdateHandler
{
    public function updateAllDs(array $rootCallbacks, array $elementCallbacks)
    {
        $count = 0;

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placesService = $configurationService->getPlacesService();

        $dataStructurePlaces = $placesService->getAvailablePlacesUsingConfigurationHandlerIdentifier(
            \Tvp\TemplaVoilaPlus\Handler\Configuration\DataConfigurationHandler::$identifier
        );
        $placesService->loadConfigurationsByPlaces($dataStructurePlaces);


        foreach ($dataStructurePlaces as $idenmtifier => $dataStructurePlace) {
            foreach ($dataStructurePlace->getConfigurations() as $dataConfiguration) {
                if ($this->updateDs($dataConfiguration, $dataStructurePlace, $rootCallbacks, $elementCallbacks)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public function updateDs(DataConfiguration $dataConfiguration, Place $dataStructurePlace, array $rootCallbacks, array $elementCallbacks): bool
    {
        /** @var DataStructure */
        $dataStructure = $dataConfiguration->getDataStructure();

        $changed = $this->processUpdate($dataStructure, $rootCallbacks, $elementCallbacks);
        if ($changed) {
            $dataConfiguration->setDataStructure($dataStructure);
            $dataStructurePlace->setConfiguration($dataConfiguration->getIdentifier(), $dataConfiguration);

            return true;
        }
        return false;
    }

    public function processUpdate(
        array &$data,
        array $rootCallbacks,
        array $elementCallbacks
    ) {
        $changed = false;

        if (empty($data) || !isset($data['sheets'])) {
            return false;
        }

        foreach ($rootCallbacks as $callback) {
            if (is_callable($callback)) {
                $changed = $callback($data) || $changed;
            } else {
                throw new \Exception('Callback function "' . $callback[1] . '" not available. Cann\'t update DataStructure.');
            }
        }

        foreach($data['sheets'] as $sheetName => &$sheetData) {
            if (isset($sheetData['ROOT']['el']) && is_array($sheetData['ROOT']['el'])) {
                foreach ($sheetData['ROOT']['el'] as &$element) {
                    $changed = $this->fixPerElement($element, $elementCallbacks) || $changed;
                }
            }
        }
        return $changed;
    }

    protected function fixPerElement(array &$element, array $elementCallbacks)
    {
        $changed = false;

        foreach ($elementCallbacks as $callback) {
            if (is_callable($callback)) {
                $changed = $callback($element) || $changed;
            } else {
                throw new \Exception('Callback function "' . $callback[1] . '" not available. Can\'t update DataStructure.');
            }
        }

        if (isset($element['type']) && $element['type'] === 'array') {
            if (is_array($element['el'])) {
                foreach ($element['el'] as &$subElement) {
                    $changed = $this->fixPerElement($subElement, $elementCallbacks) || $changed;
                }
            }
        }

        return $changed;
    }
}
