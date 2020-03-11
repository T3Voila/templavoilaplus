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
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $dataStructurePlaces = self::getFileContentArray($path . '/DataStructurePlaces.php');
        foreach ($dataStructurePlaces as $uuid => $dataStructurePlace) {
            $configurationService->registerDataStructurePlace(
                $uuid,
                $dataStructurePlace['name'],
                $dataStructurePlace['path'],
                $dataStructurePlace['scope'],
                $dataStructurePlace['handler']
            );
        }
    }

    /**
     * Loads the TemplatePlaces.php inside the extension path
     * @param string $path
     * @internal
     * @return void
     */
    protected static function loadTemplatePlaces($path)
    {
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $templatePlaces = self::getFileContentArray($path . '/TemplatePlaces.php');
        foreach ($templatePlaces as $uuid => $contentPlace) {
            $configurationService->registerTemplatePlace(
                $uuid,
                $contentPlace['name'],
                $contentPlace['path'],
                $contentPlace['renderer'],
                $contentPlace['scope']
            );
        }
    }

    /**
     * Loads the MappingPlaces.php inside the extension path
     * @param string $path
     * @internal
     * @return void
     */
    protected static function loadMappingPlaces($path)
    {
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $mappingPlaces = self::getFileContentArray($path . '/MappingPlaces.php');
        foreach ($mappingPlaces as $uuid => $mappingPlace) {
            $configurationService->registerMappingPlace(
                $uuid,
                $mappingPlace['name'],
                $mappingPlace['path']
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
        if (isset($extending['renderer'])) {
            foreach ($extending['renderer'] as $uuid => $renderer) {
                $configurationService->registerRenderer(
                    $uuid,
                    $renderer['name'],
                    $renderer['class']
                );
            }
        }
        if (isset($extending['dataStructureHandler'])) {
            foreach ($extending['dataStructureHandler'] as $uuid => $dataStructureHandler) {
                $configurationService->registerDataStructureHandler(
                    $uuid,
                    $dataStructureHandler['name'],
                    $dataStructureHandler['class']
                );
            }
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
