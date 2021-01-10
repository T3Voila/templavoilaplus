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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Tvp\TemplaVoilaPlus\Service\ConfigurationService;

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
            $path = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey)  . 'Configuration/TVP';
            if (is_dir($path)) {
                self::$registeredExtensions[$extensionKey] = $path;
            }
        }
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
            self::loadDataStructurePlaces($path);
            self::loadTemplatePlaces($path);
            self::loadBackendLayoutPlaces($path);

            // Last one, as it contain references to the other ones
            self::loadMappingPlaces($path);
        }
    }

    /**
     * Loads the DataStructurePlaces.php inside the extension path
     * @param string $path
     * @internal
     * @return void
     */
    protected static function loadDataStructurePlaces($path)
    {
        static::loadPlaces(
            $path . '/DataStructurePlaces.php',
            \Tvp\TemplaVoilaPlus\Handler\Configuration\DataStructureConfigurationHandler::$identifier
        );
    }

    /**
     * Loads the MappingPlaces.php inside the extension path
     * @param string $path
     * @internal
     * @return void
     */
    protected static function loadMappingPlaces($path)
    {
        static::loadPlaces(
            $path . '/MappingPlaces.php',
            \Tvp\TemplaVoilaPlus\Handler\Configuration\MappingConfigurationHandler::$identifier
        );
    }

    /**
     * Loads the TemplatePlaces.php inside the extension path
     * @param string $path
     * @internal
     * @return void
     */
    protected static function loadTemplatePlaces($path)
    {
        static::loadPlaces(
            $path . '/TemplatePlaces.php',
            \Tvp\TemplaVoilaPlus\Handler\Configuration\TemplateConfigurationHandler::$identifier
        );
    }

    /**
     * Loads the TemplatePlaces.php inside the extension path
     * @param string $path
     * @internal
     * @return void
     */
    protected static function loadBackendLayoutPlaces($path)
    {
        static::loadPlaces(
            $path . '/BackendLayoutPlaces.php',
            \Tvp\TemplaVoilaPlus\Handler\Configuration\BackendLayoutConfigurationHandler::$identifier
        );
    }
    /**
     * Loads the places inside the extension files
     * @param string $pathAndFilename
     * @param string $defaultConfigurationHandlerIdentifier
     * @internal
     * @return void
     */
    protected static function loadPlaces(string $pathAndFilename, string $defaultConfigurationHandlerIdentifier)
    {
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placeConfigurations = self::getFileContentArray($pathAndFilename);
        foreach ($placeConfigurations as $identifier => $placeConfiguration) {
            $configurationService->registerPlace(
                $identifier,
                $placeConfiguration['name'],
                $placeConfiguration['scope'],
                $placeConfiguration['configurationHandler'] ?: $defaultConfigurationHandlerIdentifier,
                $placeConfiguration['loadSaveHandler'],
                $placeConfiguration['path']
            );
        }
    }

    /**
     * Loads the Extending.php inside the extension path and registers dataStructureHandler
     * @param string $path
     * @internal
     * @return void
     */
    protected static function loadExtending($path)
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

    protected static function registerHandler(array $handlerConfigurations, string $implementorsInterface)
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
     * @param string $path
     * @internal
     * @return void
     */
    protected static function loadNewContentElementWizardConfiguration($path)
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
    protected static function getFileContentArray($file): array
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
