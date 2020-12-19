<?php
namespace Tvp\TemplaVoilaPlus\Hooks;

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

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

        $apiObj = GeneralUtility::makeInstance(\Tvp\TemplaVoilaPlus\Service\ApiService::class);

        // Flexible content elements:
        $positionPid = (int)GeneralUtility::_GP('id'); // No access to parent, but parent also get it only from _GP
        $storageFolderPID = $apiObj->getStorageFolderPid($positionPid);

        $toRepo = GeneralUtility::makeInstance(\Tvp\TemplaVoilaPlus\Domain\Repository\TemplateRepository::class);
        $toList = $toRepo->getTemplatesByStoragePidAndScope($storageFolderPID, \Tvp\TemplaVoilaPlus\Domain\Model\AbstractDataStructure::SCOPE_FCE);

        foreach ($toList as $toObj) {
            if ($toObj->hasParentTemplate() && $toObj->getRendertype() !== '') {
                continue;
            }
            $iconIdentifier = '';

            /** @var \Tvp\TemplaVoilaPlus\Domain\Model\Template $toObj */
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
     * @param \Tvp\TemplaVoilaPlus\Domain\Model\Template $toObj LocalProcessing as array
     *
     * @return string additional URL arguments with configured default values
     */
    public function getDsDefaultValues(\Tvp\TemplaVoilaPlus\Domain\Model\Template $toObj)
    {
        $dsStructure = $toObj->getLocalDataprotArray();

        $dsValues = '&defVals[tt_content][CType]=templavoilaplus_pi1'
            . '&defVals[tt_content][tx_templavoilaplus_ds]='
            . (!is_numeric($toObj->getDatastructure()->getKey()) ? 'FILE:' : '') . $toObj->getDatastructure()->getKey()
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
