<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Utility;

use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\AbstractConfiguration;
use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\BackendLayoutConfiguration;
use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\DataConfiguration;
use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\MappingConfiguration;
use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\TemplateConfiguration;
use Tvp\TemplaVoilaPlus\Exception\InvalidIdentifierException;
use Tvp\TemplaVoilaPlus\Service\ConfigurationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper class for easy Api access but all of this is not stable yet.
 */
class ApiHelperUtility
{
    public static function getDataStructure($combinedDataConfigurationIdentifier): DataConfiguration
    {
        return self::getConfiguration(
            $combinedDataConfigurationIdentifier,
            \Tvp\TemplaVoilaPlus\Handler\Configuration\DataConfigurationHandler::$identifier
        );
    }

    public static function getMappingConfiguration(string $combinedMappingConfigurationIdentifier): MappingConfiguration
    {
        return self::getConfiguration(
            $combinedMappingConfigurationIdentifier,
            \Tvp\TemplaVoilaPlus\Handler\Configuration\MappingConfigurationHandler::$identifier
        );
    }

    public static function getOverloadedMappingConfiguration(MappingConfiguration $mappingConfiguration, array $childsSelection): MappingConfiguration
    {
        $resultingMappingConfiguration = clone $mappingConfiguration;

        foreach ($childsSelection as $selectedChild) {
            $childConfiguration = $mappingConfiguration->getChildMappingConfiguration($selectedChild);
            if ($childConfiguration !== null) {
                self::mergeMappingConfiguration($resultingMappingConfiguration, $childConfiguration);
            }
        }

        return $resultingMappingConfiguration;
    }

    public static function mergeMappingConfiguration(MappingConfiguration $mappingConfiguration, MappingConfiguration $mappingConfigurationOverwrite): void
    {
        // Overwrite combinedTemplateConfigurationIdentifier if set
        $newCombinedTemplateIdentifier = $mappingConfigurationOverwrite->getCombinedTemplateConfigurationIdentifier();
        if ($newCombinedTemplateIdentifier !== '') {
            $mappingConfiguration->setCombinedTemplateConfigurationIdentifier($newCombinedTemplateIdentifier);
        }

        // Merge fieldConfiguration
        $mappingConfiguration->setMappingToTemplate(
            array_merge_recursive(
                $mappingConfiguration->getMappingToTemplate(),
                $mappingConfigurationOverwrite->getMappingToTemplate()
            )
        );
    }

    public static function getTemplateConfiguration($combinedTemplateConfigurationIdentifier): TemplateConfiguration
    {
        return self::getConfiguration(
            $combinedTemplateConfigurationIdentifier,
            \Tvp\TemplaVoilaPlus\Handler\Configuration\TemplateConfigurationHandler::$identifier
        );
    }

    public static function getBackendLayoutConfiguration($combinedBackendLayoutConfigurationIdentifier): BackendLayoutConfiguration
    {
        return self::getConfiguration(
            $combinedBackendLayoutConfigurationIdentifier,
            \Tvp\TemplaVoilaPlus\Handler\Configuration\BackendLayoutConfigurationHandler::$identifier
        );
    }

    public static function getConfiguration(string $combinedConfigurationIdentifier, string $handlerIdentifier): AbstractConfiguration
    {
        if (strpos($combinedConfigurationIdentifier, ':') === false) {
            throw new InvalidIdentifierException('The combined identifier "' . $combinedConfigurationIdentifier . '" does not have the right format of "<place>:<identifier>"');
        }
        [$placeIdentifier, $configurationIdentifier] = explode(':', $combinedConfigurationIdentifier);

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placeService = $configurationService->getPlacesService();
        $place = $placeService->getPlace($placeIdentifier, $handlerIdentifier);

        return $place->getConfiguration($configurationIdentifier);
    }
}
