<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Configuration;

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
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SiteConfiguration
{
    public function getThemeItems(array &$fieldDefinition): void
    {
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placesService = $configurationService->getPlacesService();

        $mappingPlaces = $placesService->getAvailablePlacesUsingConfigurationHandlerIdentifier(
            \Tvp\TemplaVoilaPlus\Handler\Configuration\MappingConfigurationHandler::$identifier
        );

        foreach ($mappingPlaces as $mappingPlace) {
            $package = $packageManager->getPackage($mappingPlace->getExtensionKey());

            $icon = ExtensionManagementUtility::getExtensionIcon(
                $packageManager->getPackage($mappingPlace->getExtensionKey())->getPackagePath()
            );

            $fieldDefinition['items'][$mappingPlace->getIdentifier()] = [
                'label' => $mappingPlace->getName(),
                'value' => $mappingPlace->getIdentifier(),
                'icon' => ($icon ? 'EXT:' . $mappingPlace->getExtensionKey() . '/' . $icon : ''),
                // @see https://forge.typo3.org/issues/96461 we cannot manipulate itemGroups
                'group' => $package->getPackageMetaData()->getTitle(),
                'tempTitles' => [],
            ];
        }
    }
}
