<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Controller\Backend;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Backend\Controller\LinkBrowserController as CoreLinkBrowserController;

class LinkBrowserController extends CoreLinkBrowserController
{

    protected function initDocumentTemplate()
    {
        parent::initDocumentTemplate();
        
		$pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
		$pageRenderer->addRequireJsConfiguration(
            [
                'map' => [
                    '*' => ['TYPO3/CMS/Backend/FormEngineLinkBrowserAdapter' => PathUtility::getRelativePathTo(ExtensionManagementUtility::extPath('templavoilaplus')) . '/Resources/Public/JavaScript/FormEngineLinkBrowserAdapter']
                ]
            ]
        );
	}
}
