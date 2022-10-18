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

use Tvp\TemplaVoilaPlus\Exception\ProcessingException;
use Tvp\TemplaVoilaPlus\Service\ProcessingService;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

abstract class AbstractResponse
{
    /**
     * @param string $templateFile Name of the template file
     * @return StandaloneView
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     */
    protected function getFluidTemplateObject(string $templateFile, array $settings = []): StandaloneView
    {
        /** @var StandaloneView */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateFile));
        $view->getRequest()->setControllerExtensionName('Backend');

        $view->setPartialRootPaths([
            10 => 'EXT:templavoilaplus/Resources/Private/Partials/',
        ]);
        $view->setLayoutRootPaths([
            10 => 'EXT:templavoilaplus/Resources/Private/Layouts/',
        ]);

        $view->assign('settings', $settings);

        return $view;
    }

    protected function record2html(string $table, int $uid, string $parentPointer = null): string
    {
        $row = BackendUtility::getRecord($table, $uid);

        if (!$row) {
            throw new ProcessingException(sprintf('Trying to render %s:%u, but record not available', $table, $uid));
        }

        /** @var ProcessingService */
        $processingService = GeneralUtility::makeInstance(ProcessingService::class);
        $nodeTree = $processingService->getNodeWithTree($table, $row);
        if ($parentPointer) {
            $nodeTree['node']['rendering']['parentPointer'] = $parentPointer;
        }

        $view = $this->getFluidTemplateObject('EXT:templavoilaplus/Resources/Private/Templates/Backend/Ajax/InsertNode.html', $this->getSettings());
        $view->assign('nodeTree', $nodeTree);

        return $view->render();
    }

    protected function getSettings()
    {
        /** @TODO better handle this with an configuration object */
        /** @TODO Duplicated more or less from PageLayoutController */
        return [
            'configuration' => [
                'allAvailableLanguages' => TemplaVoilaUtility::getAvailableLanguages(0, true, true, []),
                'lllFile' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/PageLayout.xlf',
                'userSettings' => TemplaVoilaUtility::getBackendUser()->uc['templavoilaplus'] ?? [],
                'is8orNewer' => version_compare(TYPO3_version, '8.0.0', '>=') ? true : false,
                'is9orNewer' => version_compare(TYPO3_version, '9.0.0', '>=') ? true : false,
                'is10orNewer' => version_compare(TYPO3_version, '10.0.0', '>=') ? true : false,
                'is11orNewer' => version_compare(TYPO3_version, '11.0.0', '>=') ? true : false,
                'is12orNewer' => version_compare(TYPO3_version, '12.0.0', '>=') ? true : false,
                'TCA' => $GLOBALS['TCA'],
            ],
        ];
    }
}
