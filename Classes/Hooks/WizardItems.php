<?php
namespace Ppi\TemplaVoilaPlus\Hooks;

use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

class WizardItems implements NewContentElementWizardHookInterface
{
    /**
     * Processes the items of the new content element wizard
     * and inserts necessary default values for items created within a grid
     *
     * @param array $wizardItems The array containing the current status of the wizard item list before rendering
     * @param NewContentElementController $parentObject The parent object that triggered this hook
     */
    public function manipulateWizardItems(&$wizardItems, &$parentObject)
    {
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);

        $addingItems = [
            'fce' => [
                'header' => $this->getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/BackendLayout.xlf:fce'),
            ],
        ];
        $apiObj = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Service\ApiService::class);

        // Flexible content elements:
        $positionPid = $parentObject->id;
        $storageFolderPID = $apiObj->getStorageFolderPid($positionPid);

        $toRepo = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Domain\Repository\TemplateRepository::class);
        $toList = $toRepo->getTemplatesByStoragePidAndScope($storageFolderPID, \Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure::SCOPE_FCE);
        foreach ($toList as $toObj) {
            if ($toObj->hasParentTemplate() && $toObj->getRendertype() !== '') {
                continue;
            }
            $iconIdentifier = '';

            /** @var \Ppi\TemplaVoilaPlus\Domain\Model\Template $toObj */
            if ($toObj->isPermittedForUser()) {
                $tmpFilename = $toObj->getIcon();

                // Create own iconIdentifier
                if ($tmpFilename && @is_file(GeneralUtility::getFileAbsFileName($tmpFilename))) {
                    $iconIdentifier = 'fce_' . $toObj->getKey();
                    $iconRegistry->registerIcon($iconIdentifier, BitmapIconProvider::class, [
                        'source' => GeneralUtility::resolveBackPath($tmpFilename)
                    ]);
                }

                $addingItems['fce_' . $toObj->getKey()] = [
                    'iconIdentifier' => ($iconIdentifier?: 'extensions-templavoila-default-preview-icon'),
                    'description' => $toObj->getDescription()
                        ? $this->getLanguageService()->sL($toObj->getDescription())
                        : TemplaVoilaUtility::getLanguageService()->getLL('template_nodescriptionavailable'),
                    'title' => $toObj->getLabel(),
                    'params' => $this->getDsDefaultValues($toObj)
                ];
            }
        }

        // Insert FCE area before forms or plugins or at last.
        $key_indices = array_flip(array_keys($wizardItems));
        if (isset($wizardItems['forms'])) {
            $offset = $key_indices['forms'];
        } elseif (isset($wizardItems['plugins'])) {
            $offset = $key_indices['plugins'];
        } else {
            $offset = -1;
        }
        $wizardItems = array_slice($wizardItems, 0, $offset, true) + $addingItems + array_slice($wizardItems, $offset, null, true);
    }

    /**
     * Process the default-value settings
     *
     * @param \Ppi\TemplaVoilaPlus\Domain\Model\Template $toObj LocalProcessing as array
     *
     * @return string additional URL arguments with configured default values
     */
    public function getDsDefaultValues(\Ppi\TemplaVoilaPlus\Domain\Model\Template $toObj)
    {
        $dsStructure = $toObj->getLocalDataprotArray();

        $dsValues = '&defVals[tt_content][CType]=templavoilaplus_pi1'
            . '&defVals[tt_content][tx_templavoilaplus_ds]=' . $toObj->getDatastructure()->getKey()
            . '&defVals[tt_content][tx_templavoilaplus_to]=' . $toObj->getKey();

        if (is_array($dsStructure) && is_array($dsStructure['meta']['default']['TCEForms'])) {
            foreach ($dsStructure['meta']['default']['TCEForms'] as $field => $value) {
                $dsValues .= '&defVals[tt_content][' . $field . ']=' . $value;
            }
        }

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
