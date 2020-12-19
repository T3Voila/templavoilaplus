<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Service;

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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use Tvp\TemplaVoilaPlus\Domain\Model\Place;
use Tvp\TemplaVoilaPlus\Handler\Configuration\ConfigurationHandlerInterface;
use Tvp\TemplaVoilaPlus\Handler\LoadSave\LoadSaveHandlerInterface;

/**
 * @TODO Better name? Is this a Service? A Factory? Registry?
 * @TODO Also an implementation for Handler so ConfigurationService can stay clean?
 * Should only be get from ConfigurationService
 */
class PlacesService implements SingletonInterface
{
    private $availablePlaces = [];
    private $availablePlacesByConfigurationHandlerIdentifier = [];

    // Places
    public function getAvailablePlaces(): array
    {
        return $this->availablePlaces;
    }

    public function getAvailablePlacesUsingConfigurationHandlerIdentifier($configurationHandlerIdentifier): array
    {
        return ($this->availablePlacesByConfigurationHandlerIdentifier[$configurationHandlerIdentifier] ?: []);
    }

    public function getPlace(string $placeIdentifier, string $configurationHandlerIdentifier): Place
    {
        if (!isset($this->availablePlaces[$placeIdentifier])) {
            throw new \Exception('The Place with identifier "' . $placeIdentifier . '" is not available.');
        }

        /** @var \Tvp\TemplaVoilaPlus\Domain\Model\Place */
        $place = $this->availablePlaces[$placeIdentifier];
        if ($place->getConfigurationHandlerIdentifier() !== $configurationHandlerIdentifier) {
            throw new \Exception('The Place with identifier "' . $placeIdentifier . '" do not have the requested configuration handler "' . $configurationHandlerIdentifier . '"');
        }

        return $place;
    }

    public function doesIdentifierExists(string $placeIdentifier)
    {
        return isset($this->availablePlaces[$placeIdentifier]);
    }

    public function registerPlace(Place $place): void
    {
        if (isset($this->availablePlaces[$place->getIdentifier()])) {
            throw new \Exception('A place with identifier "' . $place->getIdentifier() . '" is already registered.');
        }

        $this->availablePlaces[$place->getIdentifier()] = $place;
        $this->availablePlacesByConfigurationHandlerIdentifier[$place->getConfigurationHandlerIdentifier()][$place->getIdentifier()] = $place;
    }

    public function loadConfigurationsByPlaces(array $places)
    {
        foreach ($places as $place) {
            $this->loadConfigurationsByPlace($place);
        }
    }

    public function loadConfigurationsByPlace(Place $place)
    {
        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);

        /** @var LoadSaveHandlerInterface */
        $loadSaveHandler = $configurationService->getHandler($place->getLoadSaveHandlerIdentifier());
        $loadSaveHandler->setPlace($place);

        /** @var ConfigurationHandlerInterface */
        $configurationHandler = $configurationService->getHandler($place->getConfigurationHandlerIdentifier());
        $configurationHandler->setPlace($place);
        $configurationHandler->setLoadSaveHandler($loadSaveHandler);
        $configurationHandler->loadConfigurations();
    }

    public function reorderPlacesByScope(array $places): array
    {
        $placesByScope = [];
        foreach ($places as $idenmtifier => $place) {
            $placesByScope[$place->getScope()][] = $place;
        }

        return $placesByScope;
    }
}
