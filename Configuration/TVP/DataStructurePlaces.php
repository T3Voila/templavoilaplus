<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Configuration;

use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Domain\Model\Scope;
use Ppi\TemplaVoilaPlus\Service\ConfigurationService;

if (!class_exists(DataStructurePlaces::class)) {
    class DataStructurePlaces
    {
        public static function getDataStructurePlaces(): array
        {
            $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
            $confPathDot = '';
            if (version_compare(TYPO3_version, '9.0.0', '<=')) {
                $confPathDot = '.';
            }
            $extensionConfig = $configurationService->getExtensionConfig();

            if (!empty($extensionConfig['staticDS' . $confPathDot]['path_page']) && !empty($extensionConfig['staticDS' . $confPathDot]['path_fce'])) {

                return [
                    'page' => [
                        'name' => 'PAGE',
                        'path' => $extensionConfig['staticDS' . $confPathDot]['path_page'],
                        'scope' => Scope::SCOPE_PAGE,
                        'loadSaveHandler' => \Ppi\TemplaVoilaPlus\Handler\LoadSave\XmlLoadSaveHandler::$identifier,
                    ],
                    'fce' => [
                        'name' => 'FCE',
                        'path' => $extensionConfig['staticDS' . $confPathDot]['path_fce'],
                        'scope' => Scope::SCOPE_FCE,
                        'loadSaveHandler' => \Ppi\TemplaVoilaPlus\Handler\LoadSave\XmlLoadSaveHandler::$identifier,
                    ],
                ];
            }

            return [];
        }
    }
}

return DataStructurePlaces::getDataStructurePlaces();
