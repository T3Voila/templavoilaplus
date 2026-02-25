<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Utility;

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
use Tvp\TemplaVoilaPlus\Service\ConfigurationService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExtensionUtility implements SingletonInterface
{
    private static $registeredExtensions = [];

    /**
     * Register an extension as an extension which includes TV+ configuration or extends the TV+ functionality.
     * This is only a helper for the moment and will be removed after caching extension data is implemented.
     * So we do not determine all this on every request.
     *
     * @TODO Remove after implementing a cache
     */
    public static function registerExtension($extensionKey)
    {
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extensionKey)) {
            $path = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey) . 'Configuration/TVP';
            if (is_dir($path)) {
                self::$registeredExtensions[$extensionKey] = $path;
            }
        }
    }

    /**
     * @internal
     */
    public static function getRegisteredExtensions(): array
    {
        return self::$registeredExtensions;
    }

    /**
     * @internal
     */
    public static function handleAllExtensions()
    {
        foreach (self::$registeredExtensions as $extensionKey => $path) {
            // Extending TV+
            self::loadExtending($path);
            // Overwrite NewContentElementWizard
            self::loadNewContentElementWizardConfiguration($path);
        }
        // Temnplating TV+
        foreach (self::$registeredExtensions as $extensionKey => $path) {
            self::loadDataStructurePlaces($extensionKey, $path);
            self::loadTemplatePlaces($extensionKey,$path);
            self::loadBackendLayoutPlaces($extensionKey, $path);

            // Last one, as it contain references to the other ones
            self::loadMappingPlaces($extensionKey, $path);
        }
    }

    /**
     * Loads the DataStructurePlaces.php inside the extension path
     * @internal
     */
    protected static function loadDataStructurePlaces(string $extensionKey, string $path): void
    {
        static::loadPlaces(
            $extensionKey,
            $path . '/DataStructurePlaces.php',
            \Tvp\TemplaVoilaPlus\Handler\Configuration\DataConfigurationHandler::$identifier
        );
    }

    /**
     * Loads the MappingPlaces.php inside the extension path
     * @internal
     */
    protected static function loadMappingPlaces(string $extensionKey, string $path): void
    {
        static::loadPlaces(
            $extensionKey,
            $path . '/MappingPlaces.php',
            \Tvp\TemplaVoilaPlus\Handler\Configuration\MappingConfigurationHandler::$identifier
        );
    }

    /**
     * Loads the TemplatePlaces.php inside the extension path
     * @internal
     */
    protected static function loadTemplatePlaces(string $extensionKey, string $path): void
    {
        static::loadPlaces(
            $extensionKey,
            $path . '/TemplatePlaces.php',
            \Tvp\TemplaVoilaPlus\Handler\Configuration\TemplateConfigurationHandler::$identifier
        );
    }

    /**
     * Loads the TemplatePlaces.php inside the extension path
     * @internal
     */
    protected static function loadBackendLayoutPlaces(string $extensionKey, string $path): void
    {
        static::loadPlaces(
            $extensionKey,
            $path . '/BackendLayoutPlaces.php',
            \Tvp\TemplaVoilaPlus\Handler\Configuration\BackendLayoutConfigurationHandler::$identifier
        );
    }
    /**
     * Loads the places inside the extension files
     * @internal
     */
    protected static function loadPlaces(
        string $extensionKey,
        string $pathAndFilename,
        string $defaultConfigurationHandlerIdentifier
    ): void
    {
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placeConfigurations = self::getFileContentArray($pathAndFilename);
        foreach ($placeConfigurations as $identifier => $placeConfiguration) {
            $configurationService->registerPlace(
                $identifier,
                $placeConfiguration['name'],
                $extensionKey,
                $placeConfiguration['scope'] ?? '',
                $placeConfiguration['configurationHandler'] ?? $defaultConfigurationHandlerIdentifier,
                $placeConfiguration['loadSaveHandler'],
                $placeConfiguration['path'],
                (int)($placeConfiguration['indentation'] ?? 4)
            );
        }
    }

    /**
     * Loads the Extending.php inside the extension path and registers dataStructureHandler
     * @param string $path
     * @internal
     */
    protected static function loadExtending(string $path): void
    {
        $extending = self::getFileContentArray($path . '/Extending.php');
        if (isset($extending['renderHandler'])) {
            self::registerHandler(
                $extending['renderHandler'],
                \Tvp\TemplaVoilaPlus\Handler\Render\RenderHandlerInterface::class
            );
        }
        if (isset($extending['configurationHandler'])) {
            self::registerHandler(
                $extending['configurationHandler'],
                \Tvp\TemplaVoilaPlus\Handler\Configuration\ConfigurationHandlerInterface::class
            );
        }
        if (isset($extending['loadSaveHandler'])) {
            self::registerHandler(
                $extending['loadSaveHandler'],
                \Tvp\TemplaVoilaPlus\Handler\LoadSave\LoadSaveHandlerInterface::class
            );
        }
    }

    protected static function registerHandler(array $handlerConfigurations, string $implementorsInterface): void
    {
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        foreach ($handlerConfigurations as $identifier => $handlerConfiguration) {
            $configurationService->registerHandler(
                $identifier,
                $handlerConfiguration['name'],
                $handlerConfiguration['handlerClass'],
                $implementorsInterface
            );
        }
    }

    /**
     * Loads the Extending.php inside the extension path and registers dataStructureHandler
     * @internal
     */
    protected static function loadNewContentElementWizardConfiguration(string $path): void
    {
        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $configNew = self::getFileContentArray($path . '/NewContentElementWizard.php');
        $configExisting = $configurationService->getNewContentElementWizardConfiguration();

        if (isset($configNew['overwrites'])) {
            $configExisting['overwrites'] = array_merge_recursive(
                $configExisting['overwrites'],
                $configNew['overwrites']
            );
        }
        if (isset($configNew['simpleView'])) {
            $configExisting['simpleView'] = array_merge_recursive(
                $configExisting['simpleView'],
                $configNew['simpleView']
            );
        }

        $configurationService->setNewContentElementWizardConfiguration($configExisting);
    }

    /**
     * Includes the given file with require (if exists and readable) and exspects an array returned
     * @param string $file Absolute path and filename
     * @internal
     */
    protected static function getFileContentArray(string $file): array
    {
        if (is_file($file) && is_readable($file)) {
            $content = require $file;
            if (is_array($content)) {
                return $content;
            }
        }

        return [];
    }
}
