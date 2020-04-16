<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Utility;

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

use Ppi\TemplaVoilaPlus\Service\ConfigurationService;

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
        // Extending TV+
        foreach (self::$registeredExtensions as $extensionKey => $path) {
            self::loadExtending($path);
        }
        // Temnplating TV+
        foreach (self::$registeredExtensions as $extensionKey => $path) {
            self::loadDataStructurePlaces($path);
            self::loadTemplatePlaces($path);
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
            \Ppi\TemplaVoilaPlus\Handler\Configuration\DataStructureConfigurationHandler::$identifier
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
            \Ppi\TemplaVoilaPlus\Handler\Configuration\MappingConfigurationHandler::$identifier
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
            \Ppi\TemplaVoilaPlus\Handler\Configuration\TemplateConfigurationHandler::$identifier
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
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $extending = self::getFileContentArray($path . '/Extending.php');
        if (isset($extending['renderHandler'])) {
            self::registerHandler(
                $extending['renderHandler'],
                \Ppi\TemplaVoilaPlus\Handler\Render\RenderHandlerInterface::class
            );
        }
        if (isset($extending['configurationHandler'])) {
            self::registerHandler(
                $extending['configurationHandler'],
                \Ppi\TemplaVoilaPlus\Handler\Configuration\ConfigurationHandlerInterface::class
            );
        }
        if (isset($extending['loadSaveHandler'])) {
            self::registerHandler(
                $extending['loadSaveHandler'],
                \Ppi\TemplaVoilaPlus\Handler\LoadSave\LoadSaveHandlerInterface::class
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
