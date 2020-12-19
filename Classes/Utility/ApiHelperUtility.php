<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;
use Tvp\TemplaVoilaPlus\Domain\Model\DataStructure;
use Tvp\TemplaVoilaPlus\Domain\Model\AbstractConfiguration;
use Tvp\TemplaVoilaPlus\Domain\Model\BackendLayoutConfiguration;
use Tvp\TemplaVoilaPlus\Domain\Model\MappingConfiguration;
use Tvp\TemplaVoilaPlus\Domain\Model\TemplateConfiguration;
use Tvp\TemplaVoilaPlus\Service\ConfigurationService;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Helper class for easy Api access but all of this is not stable yet.
 */
class ApiHelperUtility
{
    public static function getDataStructure($combinedDataStructureIdentifier): DataStructure
    {
        return self::getConfiguration(
            $combinedDataStructureIdentifier,
            \Tvp\TemplaVoilaPlus\Handler\Configuration\DataStructureConfigurationHandler::$identifier
        );
    }

    public static function getMappingConfiguration($combinedMappingConfigurationIdentifier): MappingConfiguration
    {
        return self::getConfiguration(
            $combinedMappingConfigurationIdentifier,
            \Tvp\TemplaVoilaPlus\Handler\Configuration\MappingConfigurationHandler::$identifier
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
        list($placeIdentifier, $configurationIdentifier) = explode(':', $combinedConfigurationIdentifier);

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placeService = $configurationService->getPlacesService();
        $place = $placeService->getPlace($placeIdentifier, $handlerIdentifier);

        return $place->getConfiguration($configurationIdentifier);
    }
}
