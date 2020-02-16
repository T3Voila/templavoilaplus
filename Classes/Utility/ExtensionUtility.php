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

    public static function registerExtension($extensionKey)
    {
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extensionKey)) {
            $path = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey)  . 'Configuration/TVP';
            if (is_dir($path)) {
                self::$registeredExtensions[$extensionKey] = $extensionKey;
            }
        }
    }

    public static function handleAllExtensions()
    {
        // Extending TV+
        foreach (self::$registeredExtensions as $extensionKey) {
            $path = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey)  . 'Configuration/TVP';
            self::loadExtending($path);
        }
        // Temnplating TV+
        foreach (self::$registeredExtensions as $extensionKey) {
            $path = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey)  . 'Configuration/TVP';
        self::loadDataSourcePlaces($path);
        self::loadTemplatePlaces($path);
        self::loadMappingPlaces($path);
        }
    }

    protected static function loadDataSourcePlaces($path)
    {
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $dataSourcePlaces = self::getFileContentArray($path . '/DataSourcePlaces.php');
        foreach ($dataSourcePlaces as $uuid => $dataSourcePlace) {
            $configurationService->registerDataStructurePlace(
                $uuid,
                $dataSourcePlace['name'],
                $dataSourcePlace['path'],
                $dataSourcePlace['scope'],
                $dataSourcePlace['handler'],
            );
        }
    }

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

    protected static function getFileContentArray($file)
    {
        if (is_file($file) && is_readable($file)) {
            $content = require($file);
            if (is_array($content)) {
                return $content;
            }
        }

        return [];
    }
}
