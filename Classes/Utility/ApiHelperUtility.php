<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

use Ppi\TemplaVoilaPlus\Domain\Model\DataStructure;
use Ppi\TemplaVoilaPlus\Domain\Model\MappingConfiguration;
use Ppi\TemplaVoilaPlus\Domain\Model\TemplateConfiguration;
use Ppi\TemplaVoilaPlus\Service\ConfigurationService;
use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Helper class for easy Api access but all of this is not stable yet.
 */
class ApiHelperUtility
{
    public static function getDataStructure($combinedDataStructureIdentifier): DataStructure
    {
        list($placeIdentifier, $dataStructureIdentifier) = explode(':', $combinedDataStructureIdentifier);

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placeService = $configurationService->getPlacesService();
        $dataStructurePlace = $placeService->getPlace(
            $placeIdentifier,
            \Ppi\TemplaVoilaPlus\Handler\Configuration\DataStructureConfigurationHandler::$identifier
        );
        return $dataStructurePlace->getConfiguration($dataStructureIdentifier);
    }

    public static function getMappingConfiguration($combinedMapConfigurationIdentifier): MappingConfiguration
    {
        list($placeIdentifier, $mappingConfigurationIdentifier) = explode(':', $combinedMapConfigurationIdentifier);

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placeService = $configurationService->getPlacesService();
        $mappingPlace = $placeService->getPlace(
            $placeIdentifier,
            \Ppi\TemplaVoilaPlus\Handler\Configuration\MappingConfigurationHandler::$identifier
        );

        return $mappingPlace->getConfiguration($mappingConfigurationIdentifier);
    }

    public static function getTemplateConfiguration($combinedTemplateConfigurationIdentifier): TemplateConfiguration
    {
        list($placeIdentifier, $templateConfigurationIdentifier) = explode(':', $combinedTemplateConfigurationIdentifier);

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placeService = $configurationService->getPlacesService();
        $templatePlace = $placeService->getPlace(
            $placeIdentifier,
            \Ppi\TemplaVoilaPlus\Handler\Configuration\TemplateConfigurationHandler::$identifier
        );
        return $templatePlace->getConfiguration($templateConfigurationIdentifier);
    }
}
