<?php

namespace Tvp\TemplaVoilaPlus\Hooks;

use Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\ExtendedNewContentElementController;
use Tvp\TemplaVoilaPlus\Service\ConfigurationService;
use Tvp\TemplaVoilaPlus\Service\ItemsProcFunc;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class WizardItems implements NewContentElementWizardHookInterface
{
    /**
     * Modifies WizardItems of the NewContentElementWizard array
     *
     * @param array $wizardItems Array of Wizard Items
     * @param \TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController $parentObject Parent object New Content element wizard
     */
    public function manipulateWizardItems(&$wizardItems, &$parentObject)
    {
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
                    $pageId = 0;
                    if ($parentObject instanceof ExtendedNewContentElementController) {
                        $pageId = $parentObject->getPageId();
                    } elseif ((int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id') > 0) {
                        $pageId = (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');
                    }

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
                    $fceWizardItems['fce_' . $combinedMappingIdentifier] = [
                        'iconIdentifier' => ($iconIdentifier ?: 'extensions-templavoila-template-default'),
                        'description' => /** @TODO $mappingConfiguration->getDescription() ?? */
                            TemplaVoilaUtility::getLanguageService()->getLL('template_nodescriptionavailable'),
                        'title' => $mappingConfiguration->getName(),
                        'params' => $this->getDataHandlerDefaultValues($combinedMappingIdentifier),
                    ];
                }
            }
        }
        $wizardItems = $fceWizardItems + $wizardItems;
        $wizardItems = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies($wizardItems);
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
        $tvpPageTsConfig = $pageTsConfig['mod.']['web_txtemplavoilaplusLayout.'];
        $fcePageTsConfig = $pageTsConfig['mod.']['wizards.']['newContentElement.']['wizardItems.']['fce.'];
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
    public function getDataHandlerDefaultValues(string $combinedMappingIdentifier)
    {
        $dsValues = '&defVals[tt_content][CType]=templavoilaplus_pi1'
            . '&defVals[tt_content][tx_templavoilaplus_map]=' . $combinedMappingIdentifier;

        /** @TODO We should push the DataStructure TCEForms defaults into the values? Or is this processed automagically already? */
//         $dsStructure = $toObj->getLocalDataprotArray();
//
//         if (is_array($dsStructure) && is_array($dsStructure['meta']['default']['TCEForms'])) {
//             foreach ($dsStructure['meta']['default']['TCEForms'] as $field => $value) {
//                 $dsValues .= '&defVals[tt_content][' . $field . ']=' . $value;
//             }
//         }

        return $dsValues;
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
