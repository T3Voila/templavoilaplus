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
<<<<<<< HEAD
        
<<<<<<< HEAD
<<<<<<< HEAD
=======

>>>>>>> b596a8ab ([LINT] remove code sniff errors)
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addRequireJsConfiguration(
=======
		$pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
		$pageRenderer->addRequireJsConfiguration(
>>>>>>> 885d9325 ([BUGFIX] fixes #502)
=======
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addRequireJsConfiguration(
>>>>>>> 541a3b72 (correct whitespaces)
            [
                'map' => [
                    '*' => ['TYPO3/CMS/Backend/FormEngineLinkBrowserAdapter' => PathUtility::getRelativePathTo(ExtensionManagementUtility::extPath('templavoilaplus')) . '/Resources/Public/JavaScript/FormEngineLinkBrowserAdapter']
                ]
            ]
        );
    }
}
