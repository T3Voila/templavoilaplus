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
use Tvp\TemplaVoilaPlus\Domain\Model\Place;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigurationService implements SingletonInterface
{
    /** @var bool Is true when singleton was initialized. Internal use only */
    private $isInitialized = false;

    /** @var array Global extension configuration */
    private $extConfig = [];

    /** @var array Is a register for available renderer */
    private $availableRenderer = [];

    /** @var array Is a register for available handlers */
    private $availableHandler = [];

    /** @var array Is a register for changes to the NewContentElementWizard */
    private $newContentElementWizardConfiguration = [
        'overwrites' => [],
        'simpleView' => [],
    ];

    /** @var array Settings for the Form extension */
    protected $formSettings;

    public function __construct()
    {
        if (version_compare(TYPO3_version, '9.0.0', '>=')) {
            try {
                $this->extConfig = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
                )->get('templavoilaplus');
            } catch (
                \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
                | \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException $e
            ) {
                $this->extConfig = [];
            }
        } else {
            if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoilaplus'])) {
                $this->extConfig = $this->removeDotsFromArrayKeysRecursive(
                    unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoilaplus'], ['allowed_classes' => false])
                );
                if (!is_array($this->extConfig)) {
                    $this->extConfig = [];
                }
            }
        }
    }

    /**
     * Taken from TYPO3 v9 TYPO3\CMS\Install\Controller\LayoutController
     * @deprecated Will be removed with TV+ 9
     */
    private function removeDotsFromArrayKeysRecursive(array $settings): array
    {
        $settingsWithoutDots = [];
        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                $settingsWithoutDots[rtrim($key, '.')] = $this->removeDotsFromArrayKeysRecursive($value);
            } else {
                $settingsWithoutDots[$key] = $value;
            }
        }
        return $settingsWithoutDots;
    }

    private function initialize()
    {
        if (!$this->isInitialized) {
            $this->isInitialized = true;

            \Tvp\TemplaVoilaPlus\Utility\ExtensionUtility::handleAllExtensions();
        }
    }

    public function getPlacesService(): PlacesService
    {
        $this->initialize();

        /** @var PlacesService */
        $placesService = GeneralUtility::makeInstance(PlacesService::class);
        return $placesService;
    }

    public function getExtensionConfig(): array
    {
        return $this->extConfig;
    }

    // Helper function to register new Places
    public function registerPlace(
        string $identifier,
        string $name,
        /** @TODO */
        $scope,
        string $configurationHandlerIdentifier,
        string $loadSaveHandlerIdentifier,
        string $entryPoint,
        int $indentation
    ) {
        /** @var PlacesService */
        $placesService = GeneralUtility::makeInstance(PlacesService::class);

        if ($placesService->doesIdentifierExists($identifier)) {
            throw new \Exception('The identifier "' . $identifier . '" is already registered for Places');
        }
        // $this->checkHandler $configurationHandlerIdentifier
        // $this->checkHandler $loadSaveHandlerIdentifier

        $placesService->registerPlace(
            new Place($identifier, $name, $scope, $configurationHandlerIdentifier, $loadSaveHandlerIdentifier, $entryPoint, $indentation)
        );
    }

    public function registerHandler(
        string $identifier,
        string $name,
        string $handlerClass,
        string $implementorsInterface
    ) {
        $this->mustExistsAndImplements($handlerClass, $implementorsInterface);

        if (isset($this->availableHandler[$identifier])) {
            throw new \Exception('The identifier "' . $identifier . '" is already registered for Handler');
        }

        $this->availableHandler[$identifier] = [
            'name' => $name,
            'class' => $handlerClass,
        ];
    }

    public function getHandler(string $handlerIdentifier)
    {
        $this->initialize();
        if (!isset($this->availableHandler[$handlerIdentifier])) {
            throw new \Exception('Handler with identifier "' . $handlerIdentifier . '" do not exist');
        }
        return GeneralUtility::makeInstance($this->availableHandler[$handlerIdentifier]['class']);
    }

    public function getAvailableHandlers()
    {
        $this->initialize();
        return $this->availableHandler;
    }

    private function mustExistsAndImplements(string $class, string $implements): bool
    {
        $interfaces = @class_implements($class);

        if ($interfaces === false) {
            throw new \Exception('Class "' . $class . '" not found');
        }

        $parents = @class_parents($class);
        if (!isset($interfaces[$implements]) && !isset($parents[$implements])) {
            throw new \Exception('Class "' . $class . '" do not implement "' . $implements . '"');
        }

        return true;
    }

    public function getNewContentElementWizardConfiguration(): array
    {
        $this->initialize();
        return $this->newContentElementWizardConfiguration;
    }

    public function setNewContentElementWizardConfiguration(array $newContentElementWizardConfiguration): void
    {
        $this->newContentElementWizardConfiguration = $newContentElementWizardConfiguration;
    }
}
