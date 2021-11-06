<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Controller\Backend\Handler;

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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Tvp\TemplaVoilaPlus\Controller\Backend\PageLayoutController;
use Tvp\TemplaVoilaPlus\Service\ApiService;
use Tvp\TemplaVoilaPlus\Service\ConfigurationService;
use Tvp\TemplaVoilaPlus\Utility\ApiHelperUtility;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use Tvp\TemplaVoilaPlus\Service\ProcessingService;

class DoktypeDefaultHandler
{
    /**
     * Displays the content of the page on the doktype "Default"/"BE_User_Section"
     *
     * @param PageLayoutController $controller
     * @param array $pageRecord The current page record
     *
     * @return string HTML output from this submodule
     */
    public function handle(PageLayoutController $controller, array $pageRecord)
    {
        /** @var ApiService */
        $apiService = GeneralUtility::makeInstance(ApiService::class, 'pages');
        /** @var ProcessingService */
        $processingService = GeneralUtility::makeInstance(ProcessingService::class);

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);

        if (isset($controller->getModSharedTSconfig()['properties']['useLiveWorkspaceForReferenceListUpdates'])) {
            $apiService->modifyReferencesInLiveWS(true);
        }

        /** @TODO This loading will be later done again for the FlexFormTools::getDataStructureIdentifierFromRecord() which is stupid IMHO */
        $combinedMappingConfigurationIdentifier = $pageRecord['tx_templavoilaplus_map'];
        // Find DS and Template in root line IF there is no Data Structure set for the current page:
        if (!$combinedMappingConfigurationIdentifier) {
            $rootLine = $apiService->getBackendRootline($pageRecord['uid']);
            $combinedMappingConfigurationIdentifier = $apiService->getMapIdentifierFromRootline($rootLine);
        }

        if (!$combinedMappingConfigurationIdentifier) {
            $controller->getView()->getModuleTemplate()->addFlashMessage(
                'No mapping configuration found for this page. Please edit the page properties and select one.',
                'No mapping configuration found',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR,
                false
            );
            if (!empty($pageRecord['tx_templavoilaplus_ds']) || !empty($pageRecord['tx_templavoilaplus_next_ds'])) {
                $controller->getView()->getModuleTemplate()->addFlashMessage(
                    'Older configuration found, did you upgrade to "TemplaVoilà! Plus 8" but forgot to run the upgrade scripts?',
                    'Did you forgot to Upgrade',
                    \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING,
                    false
                );
            }
        } else {
            try {
                $mappingConfiguration = ApiHelperUtility::getMappingConfiguration($combinedMappingConfigurationIdentifier);
                $combinedBackendLayoutConfigurationIdentifier = $mappingConfiguration->getCombinedBackendLayoutConfigurationIdentifier();

            /** @TODO Use a default beLayout thing instead of the double rendering in the template yet */
//             if ($combinedBackendLayoutConfigurationIdentifier === '') {
//                 $combinedBackendLayoutConfigurationIdentifier = 'TVP\BackendLayout:DefaultPage.tvp.yaml';
//             }

                $controller->getView()->assign(
                    'doktypeDefault',
                    [
                        'nodeTree' => $processingService->getNodeWithTree('pages', $pageRecord),
                        'beLayout' => $combinedBackendLayoutConfigurationIdentifier,
                    ]
                );
                $controller->addContentPartial('body', 'Backend/Handler/DoktypeDefaultHandler'); // @TODO Add them automagically in controller to harden naming?
            } catch (\Exception $e) {
                $controller->getView()->getModuleTemplate()->addFlashMessage(
                    'The page have a Layout defined, which seams missing on this system. The error was: ' . $e->getMessage(),
                    'Template Configuration not loadable',
                    \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR,
                    false
                );
            }
        }

        return '';
    }
}
