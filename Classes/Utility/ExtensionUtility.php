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
    public static function handleExtension($extensionKey)
    {
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extensionKey)) {
            $path = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey)  . 'Configuration/TVP';
            self::loadDataSourcePlaces($path);
            self::loadTemplatePlaces($path);
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
                $dataSourcePlace['scope']
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
                $contentPlace['type'],
                $contentPlace['scope']
            );
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
