<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter;

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

use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Tvp\TemplaVoilaPlus\Service\ConfigurationService;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Abstract Controller for Update Scripts
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class AbstractUpdateController extends ActionController
{
    /**
     * Default View Container
     *
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * We define BackendTemplateView above so we will get it.
     *
     * @var BackendTemplateView
     * @api
     */
    protected $view = null;

    /**
     * @var int the id of current page
     */
    protected $pageId = 0;

    /**
     * Initialize action
     */
    protected function initializeAction()
    {
        TemplaVoilaUtility::getLanguageService()->includeLLFile(
            'EXT:templavoilaplus/Resources/Private/Language/Backend/ControlCenter/Update.xlf'
        );
    }

    /**
     * holds the extconf configuration
     *
     * @var array
     */
    protected $extConf;

    public function __construct()
    {
//         $this->fluid = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
//         $this->fluid->setPartialRootPaths([
//             GeneralUtility::getFileAbsFileName('EXT:templavoilaplus/Resources/Private/Partials/Backend')
//         ]);
//         $this->fluid->setTemplateRootPaths([
//             GeneralUtility::getFileAbsFileName('EXT:templavoilaplus/Resources/Private/Templates/Backend/')
//         ]);
//         $classPartsName = explode('\\', get_class($this));
//         $this->setTemplate('Update/' . substr(array_pop($classPartsName), 0, -16));

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $this->extConf = $configurationService->getExtensionConfig();
    }

    /**
     * @return string The HTML to be shown.
     */
    public function assignDefault()
    {
        $this->view->assignMultiple([
            'is8orNewer' => version_compare(TYPO3_version, '8.0.0', '>=') ? true : false,
            'is9orNewer' => version_compare(TYPO3_version, '9.0.0', '>=') ? true : false,
            'is10orNewer' => version_compare(TYPO3_version, '10.0.0', '>=') ? true : false,
            'is11orNewer' => version_compare(TYPO3_version, '11.0.0', '>=') ? true : false,
            'typo3Version' => TYPO3_version,
            'tvpVersion' => ExtensionManagementUtility::getExtensionVersion('templavoilaplus'),
            'useStaticDS' => ($this->extConf['staticDS']['enable']),
        ]);
    }
}
