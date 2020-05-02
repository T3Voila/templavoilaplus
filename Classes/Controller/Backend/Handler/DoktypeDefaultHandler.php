<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Controller\Backend\Handler;

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

use Ppi\TemplaVoilaPlus\Controller\Backend\PageLayoutController;
use Ppi\TemplaVoilaPlus\Service\ApiService;
use Ppi\TemplaVoilaPlus\Service\ConfigurationService;
use Ppi\TemplaVoilaPlus\Utility\ApiHelperUtility;
use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

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

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);

        if (isset($controller->getModSharedTSconfig()['properties']['useLiveWorkspaceForReferenceListUpdates'])) {
            $apiService->modifyReferencesInLiveWS(true);
        }

        // Find DS and Template in root line IF there is no Data Structure set for the current page:
        if (!$pageRecord['tx_templavoilaplus_map']) {
            $rootLine = $apiService->getBackendRootline($pageRecord['uid']);
            $pageRecord['tx_templavoilaplus_map'] = $apiService->getMapIdentifierFromRootline($rootLine);
        }

        if (!$pageRecord['tx_templavoilaplus_map']) {
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
            $mappingConfiguration = ApiHelperUtility::getMappingConfiguration($pageRecord['tx_templavoilaplus_map']);
            $combinedBackendLayoutConfigurationIdentifier = $mappingConfiguration->getCombinedBackendLayoutConfigurationIdentifier();

//             if ($combinedBackendLayoutConfigurationIdentifier === '') {
//                 $combinedBackendLayoutConfigurationIdentifier = 'TVP\BackendLayout:DefaultPage.tvp.yaml';
//             }

            $controller->getView()->assign(
                'doktypeDefault',
                [
                    'treeData' => $apiService->getContentTree('pages', $pageRecord),
                    'beLayout' => $combinedBackendLayoutConfigurationIdentifier,
                ]
            );


            $controller->addContentPartial('body', 'Backend/Handler/DoktypeDefaultHandler'); // @TODO Add them automagically in controller to harden naming?
        }

        return '';
    }
}
