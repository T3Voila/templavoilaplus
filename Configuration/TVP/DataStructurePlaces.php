<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Configuration;

use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure;
use Ppi\TemplaVoilaPlus\Service\ConfigurationService;

if (!class_exists(DataStructurePlaces::class)) {
    class DataStructurePlaces
    {
        public static function getDataStructurePlaces(): array
        {
            $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);

            if ($configurationService->isStaticDataStructureEnabled()) {
                $confPathDot = '';
                if (version_compare(TYPO3_version, '9.0.0', '<=')) {
                    $confPathDot = '.';
                }

                return [
                    'page' => [
                        'name' => 'PAGE',
                        'path' => $configurationService->getExtensionConfig()['staticDS' . $confPathDot]['path_page'],
                        'scope' => AbstractDataStructure::SCOPE_PAGE,
                        'handler' => \Ppi\TemplaVoilaPlus\DataStructureHandler\FlexFormHandler::NAME,
                    ],
                    'fce' => [
                        'name' => 'FCE',
                        'path' => $configurationService->getExtensionConfig()['staticDS' . $confPathDot]['path_fce'],
                        'scope' => AbstractDataStructure::SCOPE_FCE,
                        'handler' => \Ppi\TemplaVoilaPlus\DataStructureHandler\FlexFormHandler::NAME,
                    ],
                ];
            }

            return [];
        }
    }
}

return DataStructurePlaces::getDataStructurePlaces();
