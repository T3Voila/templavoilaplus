<?php
namespace Ppi\TemplaVoilaPlus\Hooks;

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

class WizardItems
{
    /**
     * Adds the indexed_search pi1 wizard icon
     *
     * @param array $wizardItems Input array with wizard items for plugins
     * @return array Modified input array, having the item for indexed_search pi1 added.
     */
    public function proc($wizardItems)
    {
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);

        $apiObj = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Service\ApiService::class);

        // Flexible content elements:
        $positionPid = (int)GeneralUtility::_GP('id'); // No access to parent, but parent also get it only from _GP
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

                $wizardItems['fce_' . $toObj->getKey()] = [
                    'iconIdentifier' => ($iconIdentifier?: 'extensions-templavoila-template-default'),
                    'description' => $toObj->getDescription()
                        ? $this->getLanguageService()->sL($toObj->getDescription())
                        : TemplaVoilaUtility::getLanguageService()->getLL('template_nodescriptionavailable'),
                    'title' => $toObj->getLabel(),
                    'params' => $this->getDsDefaultValues($toObj)
                ];
            }
        }

        return $wizardItems;
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

        // We need to add "FILE:" for static DS sincd 8.5.0
        // See also /templavoilaplus/Classes/Service/ItemProcFunc/StaticDataStructuresHandler.php
        $is85OrNewer = version_compare(TYPO3_version, '8.5.0', '>=') ? true : false;

        $dsValues = '&defVals[tt_content][CType]=templavoilaplus_pi1'
            . '&defVals[tt_content][tx_templavoilaplus_ds]='
            . ($is85OrNewer && !is_numeric($toObj->getDatastructure()->getKey()) ? 'FILE:' : '') . $toObj->getDatastructure()->getKey()
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
