<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\Update;

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

use Psr\Http\Message\ResponseInterface;
use Tvp\TemplaVoilaPlus\Service\ConfigurationService;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Abstract Controller for Update Scripts
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class AbstractUpdateController extends ActionController
{
    /** @var int the id of current page */
    protected $pageId = 0;


    /** @var array holds the extconf configuration */
    protected $extConf = [];


    /** @var ModuleTemplateFactory  */
    protected $moduleTemplateFactory;

    protected $moduleTemplate;

    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->moduleTemplateFactory = $moduleTemplateFactory;

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $this->extConf = $configurationService->getExtensionConfig();
    }

    protected function initializeView(\TYPO3Fluid\Fluid\View\ViewInterface $view)
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->moduleTemplate->getDocHeaderComponent()->disable();
        $this->moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue());

        $this->assignDefault();
    }

    public function assignDefault()
    {
        $typo3Version = new \TYPO3\CMS\Core\Information\Typo3Version();
        $this->moduleTemplate->assignMultiple([
            'is11orNewer' => version_compare($typo3Version->getVersion(), '11.0.0', '>=') ? true : false,
            'is12orNewer' => version_compare($typo3Version->getVersion(), '12.0.0', '>=') ? true : false,
            'is13orNewer' => version_compare($typo3Version->getVersion(), '13.0.0', '>=') ? true : false,
            'is14orNewer' => version_compare($typo3Version->getVersion(), '14.0.0', '>=') ? true : false,
            'typo3Version' => $typo3Version->getVersion(),
            'tvpVersion' => ExtensionManagementUtility::getExtensionVersion('templavoilaplus'),
            'useStaticDS' => ($this->extConf['staticDS']['enable']),
        ]);
    }
}
