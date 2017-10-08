<?php
namespace Ppi\TemplaVoilaPlus\Hooks;

use TYPO3\CMS\Backend\Controller\BackendController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class adds TemplaVoilà! Plus related JavaScript InlineSettings to the backend
 */
class BackendControllerHook
{
    /**
     * Adds TemplaVoilà! Plus specific JavaScript InlineSettings
     *
     * @param array $configuration
     * @param BackendController $backendController
     */
    public function addInlineSettings(array $configuration, BackendController $backendController)
    {
        $this->getPageRenderer()->addInlineSettingArray(
            'TemplaVoilaPlus',
            [
                'layoutModuleUrl' => BackendUtility::getModuleUrl('web_txtemplavoilaplusLayout'),
                'mappingModuleUrl' => BackendUtility::getModuleUrl('templavoilaplus_mapping'),
                'dislplayModuleUrl' => BackendUtility::getModuleUrl('templavoilaplus_template_disply'),
                'newSiteWizardModuleUrl' => BackendUtility::getModuleUrl('templavoilaplus_new_site_wizard'),
                'flexformCleanerModuleUrl' => BackendUtility::getModuleUrl('templavoilaplus_flexform_cleaner'),
            ]
        );
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
