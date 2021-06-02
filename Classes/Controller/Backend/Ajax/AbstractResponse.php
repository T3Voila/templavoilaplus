<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Controller\Backend\Ajax;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

abstract class AbstractResponse
{
    /**
     * @param string $templateFile Name of the template file
     * @return StandaloneView
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     */
    protected function getFluidTemplateObject(string $templateFile): StandaloneView
    {
        /** @var StandaloneView */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateFile));
        $view->getRequest()->setControllerExtensionName('Backend');

        $view->setPartialRootPaths([
            10 => '/var/www/html/typo3conf/ext/templavoilaplus/Resources/Private/Partials/'
        ]);
        $view->getLayoutRootPaths([
            10 => '/var/www/html/typo3conf/ext/templavoilaplus/Resources/Private/Layouts/'
        ]);

        return $view;
    }
}
