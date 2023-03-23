<?php

namespace Tvp\TemplaVoilaPlus\Hooks;

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
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $this->getPageRenderer()->addInlineSettingArray(
            'TemplaVoilaPlus',
            [
                'layoutModuleUrl' => $uriBuilder->buildUriFromRoute('web_TemplaVoilaPlusLayout'),
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
