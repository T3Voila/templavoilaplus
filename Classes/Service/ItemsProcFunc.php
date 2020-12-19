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

use TYPO3\CMS\Core\Utility\GeneralUtility;

class ItemsProcFunc
{

    /**
     * @param array $params Parameters to the itemsProcFunc
     * @param \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems $pObj Calling object
     *
     * @return void
     */
    public function mapItems(array $params, \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems $pObj)
    {
        $scope = $this->getScope($params);

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placesService = $configurationService->getPlacesService();

        $mappingPlaces = $placesService->getAvailablePlacesUsingConfigurationHandlerIdentifier(
            \Tvp\TemplaVoilaPlus\Handler\Configuration\MappingConfigurationHandler::$identifier
        );
        $placesService->loadConfigurationsByPlaces($mappingPlaces);

        // @TODO Do we have a better way for the emptyness? In tt_content this should be hindered?
        $params['items'] = [
            ['', ''],
        ];

        foreach ($mappingPlaces as $mappingPlace) {
            if ($mappingPlace->getScope() === $scope) {
                $mappingConfigurations = $mappingPlace->getConfigurations();

                foreach ($mappingConfigurations as $mappingConfiguration) {
                    $params['items'][] = [
                        $mappingConfiguration['configuration']->getName(),
                        $mappingPlace->getIdentifier() . ':' . $mappingConfiguration['configuration']->getIdentifier()
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
     * @return string|integer
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
}
