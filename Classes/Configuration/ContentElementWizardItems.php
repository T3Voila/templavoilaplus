<?php

namespace Tvp\TemplaVoilaPlus\Configuration;

use Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\ExtendedNewContentElementController;
use Tvp\TemplaVoilaPlus\Service\ConfigurationService;
use Tvp\TemplaVoilaPlus\Service\ItemsProcFunc;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use TYPO3\CMS\Backend\Controller\Event\ModifyNewContentElementWizardItemsEvent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContentElementWizardItems
{
    public function processEvent(ModifyNewContentElementWizardItemsEvent $event): void
    {
        $wizardItems = $event->getWizardItems();

        $fceWizardItems = [
            'fce' => [
                'header' => $this->getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/PageLayout.xlf:newContentElementWizard.fce'),
                'after' => 'common',
            ],
        ];

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $placesService = $configurationService->getPlacesService();

        $mappingPlaces = $placesService->getAvailablePlacesUsingConfigurationHandlerIdentifier(
            \Tvp\TemplaVoilaPlus\Handler\Configuration\MappingConfigurationHandler::$identifier
        );
        $placesService->loadConfigurationsByPlaces($mappingPlaces);


        foreach ($mappingPlaces as $mappingPlace) {
            if ($mappingPlace->getScope() === \Tvp\TemplaVoilaPlus\Domain\Model\Scope::SCOPE_FCE) {
                $mappingConfigurations = $mappingPlace->getConfigurations();
                foreach ($mappingConfigurations as $mappingConfiguration) {
                    $combinedMappingIdentifier = $mappingPlace->getIdentifier() . ':' . $mappingConfiguration->getIdentifier();
                    $wizardLabel = 'fce_' . $combinedMappingIdentifier;

                    // try to get pid, either from out Controller if available or from URL
                    $pageId = ($event->getPageInfo()['uid'] ?? 0);

                    // if pid is available check PageTSconfig, if pid unavailable or pageTSconfig forbids: skip this fce
                    if (
                        $pageId > 0
                        && !$this->isWizardItemAvailable(
                            $pageId,
                            $combinedMappingIdentifier,
                            $wizardLabel
                        )
                    ) {
                        continue;
                    }
                    /** @TODO Var missing? */
                    $iconIdentifier = null;
                    $fceWizardItems['fce_' . $combinedMappingIdentifier] = [
                        /* @TODO $iconIdentifier = $iconIdentifier->getIconIdentifier() */
                        'iconIdentifier' => ($iconIdentifier ?? 'extensions-templavoila-template-default'),
                        'description' => /** @TODO $mappingConfiguration->getDescription() ?? */
                            TemplaVoilaUtility::getLanguageService()->getLL('template_nodescriptionavailable'),
                        'title' => $mappingConfiguration->getName(),
                        'tt_content_defValues' => $this->getDataHandlerDefaultValues($combinedMappingIdentifier),
                    ];
                }
            }
        }
        $wizardItems = $fceWizardItems + $wizardItems;
        $wizardItems = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies($wizardItems);

        $event->setWizardItems($wizardItems);
    }

    /**
     * @param int $currentPageId
     * @param string $combinedMappingIdentifier
     * @param string $wizardLabel
     *
     * @return bool true if the wizard item should be available
     */
    protected function isWizardItemAvailable(int $currentPageId, string $combinedMappingIdentifier, string $wizardLabel): bool
    {
        $pageTsConfig = BackendUtility::getPagesTSconfig($currentPageId);
        $tvpPageTsConfig = ($pageTsConfig['mod.']['web_txtemplavoilaplusLayout.'] ?? []);
        $fcePageTsConfig = ($pageTsConfig['mod.']['wizards.']['newContentElement.']['wizardItems.']['fce.'] ?? []);
        if (ItemsProcFunc::isMappingPlaceVisible($tvpPageTsConfig, $combinedMappingIdentifier)) {
            if (isset($fcePageTsConfig['show']) && $fcePageTsConfig['show']) {
                return $fcePageTsConfig['show'] === '*'
                    || in_array($wizardLabel, explode(',', $fcePageTsConfig['show']), false)
                    || in_array($combinedMappingIdentifier, explode(',', $fcePageTsConfig['show']), false);
            }
            return true;
        }
        return false;
    }

    /**
     * Process the default-value settings
     *
     * @param string $combinedMappingIdentifier
     * @return string additional URL arguments with configured default values for DataHandler/TCEForms
     */
    public function getDataHandlerDefaultValues(string $combinedMappingIdentifier): array
    {
        $ttcontentDefVals = [
            'CType' => 'templavoilaplus_pi1',
            'tx_templavoilaplus_map' => $combinedMappingIdentifier,
        ];

        /** @TODO We should push the DataStructure TCEForms defaults into the values? Or is this processed automagically already? */
//         $dsStructure = $toObj->getLocalDataprotArray();
//
//         if (is_array($dsStructure) && is_array($dsStructure['meta']['default']['TCEForms'])) {
//             foreach ($dsStructure['meta']['default']['TCEForms'] as $field => $value) {
//                 $dsValues .= '&defVals[tt_content][' . $field . ']=' . $value;
//             }
//         }

        return $ttcontentDefVals;
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
