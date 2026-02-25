<?php

namespace Tvp\TemplaVoilaPlus\Service;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ItemsProcFunc
{
    /**
     * @param array $params Parameters to the itemsProcFunc
     * @param object $pObj Calling object
     */
    public function mapItems(array $params, object $pObj)
    {
        $scope = $this->getScope($params);

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placesService = $configurationService->getPlacesService();

        $mappingPlaces = $placesService->getAvailablePlacesUsingConfigurationHandlerIdentifier(
            \Tvp\TemplaVoilaPlus\Handler\Configuration\MappingConfigurationHandler::$identifier
        );
        $placesService->loadConfigurationsByPlaces($mappingPlaces);

        // @TODO Do we have a better way for the emptiness? In tt_content this should be hindered?
        $params['items'] = [
            ['', ''],
        ];

        $currentPageId = (int)($params['table'] === 'pages' ? $params['row']['uid'] : $params['row']['pid']);
        $pageTsConfig = BackendUtility::getPagesTSconfig($currentPageId);
        $tvpPageTsConfig = $pageTsConfig['mod.']['web_txtemplavoilaplusLayout.'];

        foreach ($mappingPlaces as $mappingPlace) {
            if ($mappingPlace->getScope() === $scope && static::isMappingPlaceAllowed($tvpPageTsConfig, $currentPageId, $mappingPlace->getIdentifier())) {
                $mappingConfigurations = $mappingPlace->getConfigurations();

                foreach ($mappingConfigurations as $mappingConfiguration) {
                    $params['items'][] = [
                        $mappingConfiguration->getName(),
                        $mappingPlace->getIdentifier() . ':' . $mappingConfiguration->getIdentifier(),
                        // @TODO Icon file
                    ];
                }
            }
        }
    }

    /**
     * Determine scope from current TCA configuration
     * @TODO Redefine Scope
     *
     * @param array $params
     *
     * @return string|int
     */
    protected function getScope(array $params)
    {
        switch ($params['table']) {
            case 'pages':
                $scope = \Tvp\TemplaVoilaPlus\Domain\Model\Scope::SCOPE_PAGE;
                break;
            case 'tt_content':
                $scope = \Tvp\TemplaVoilaPlus\Domain\Model\Scope::SCOPE_FCE;
                break;
            default:
                $scope = $params['table'];
        }
        return $scope;
    }

    /**
     * @param array $tvpPageTsConfig
     * @param string $mappingPlace
     *
     * @return bool
     */
    public static function isMappingPlaceAllowed(array $tvpPageTsConfig, int $pageId, string $mappingPlaceIdentifier): bool
    {
        $sitesConfiguration = \Tvp\TemplaVoilaPlus\Utility\SitesUtility::getSitesConfiguration($pageId);
        $allowedPlaces = $sitesConfiguration['templavoilaplus_allowed_places'] ?? '';

        if ($allowedPlaces !== '') {
            $allowedPlacesIdentifiers = GeneralUtility::trimExplode(',', $allowedPlaces, true);

            $result = self::checkIfAllowed($allowedPlacesIdentifiers, $mappingPlaceIdentifier);
            if ($result === false) {
                return false;
            }
        }

        $allowedPlacesIdentifiers = $tvpPageTsConfig['filterMaps.'] ?? $tvpPageTsConfig['filterMaps'] ?? '';

        if ($allowedPlacesIdentifiers !== '') {
            if (!is_array($allowedPlacesIdentifiers)) {
                $allowedPlacesIdentifiers = [$allowedPlacesIdentifiers];
            }
            return self::checkIfAllowed($allowedPlacesIdentifiers, $mappingPlaceIdentifier);
        }

        return true;
    }

    protected static function checkIfAllowed(array $allowedPlacesIdentifiers, string $mappingPlaceIdentifier): bool
    {
        /**
         * @TODO Maybe use array_find if PHP > 8.4
         */
        foreach ($allowedPlacesIdentifiers as $allowedPlaceIdentifier) {
            if (strpos($mappingPlaceIdentifier, $allowedPlaceIdentifier) !== false) {
                return true;
            }
        }

        return false;
    }
}
