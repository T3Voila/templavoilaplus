<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Controller\Backend\Handler;

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

use Tvp\TemplaVoilaPlus\Controller\Backend\PageLayoutController;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DoktypeSysfolderHandler extends AbstractDoktypeHandler
{
    /**
     * Displays the edit page screen if the currently selected page is of the doktype "Sysfolder"
     *
     * @param PageLayoutController $controller
     * @param array $pageRecord The current page record
     *
     * @return string HTML output from this submodule
     */
    public function handle(PageLayoutController $controller, array $pageRecord): string
    {
        self::addLocalizationInformationForPage($controller, $pageRecord);
        $listModuleUrl = '';
        if ($this->userHasAccessToListModule()) {
            /** @var \TYPO3\CMS\Backend\Routing\UriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $listModuleUrl = (string)$uriBuilder->buildUriFromRoute('web_list', ['id' => $pageRecord['uid']]);
        }

        $controller->addFlashMessage(
            TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/PageLayout.xlf:infoDoktypeSysfolderCannotEdit'),
            TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/PageLayout.xlf:titleDoktypeSysfolder'),
            ContextualFeedbackSeverity::INFO,
            false,
            [[
                'url' => (string)$listModuleUrl,
                'label' => TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/PageLayout.xlf:hintDoktypeSysfolderOpen'),
                'icon' => 'actions-system-list-open',
            ]]
        );

        return '';
    }

    /**
     * Returns true if the logged in BE user has access to the list module.
     *
     * @return bool
     */
    protected function userHasAccessToListModule()
    {
        $moduleProvider = GeneralUtility::makeInstance(ModuleProvider::class);
        return $moduleProvider->accessGranted('web_list', TemplaVoilaUtility::getBackendUser());
    }
}
