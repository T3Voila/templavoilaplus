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
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

class DoktypeSpacerHandler extends AbstractDoktypeHandler
{
    /**
     * Displays the edit page screen if the currently selected page is of the doktype "Mount Point"
     *
     * @param PageLayoutController $controller
     * @param array $pageRecord The current page record
     *
     * @return string HTML output from this submodule
     */
    public function handle(PageLayoutController $controller, array $pageRecord)
    {
        self::addLocalizationInformationForPage($controller, $pageRecord);
        $controller->addFlashMessage(
            TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/PageLayout.xlf:infoDoktypeSpacerCannotEdit'),
            TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/PageLayout.xlf:titleDoktypeSpacer'),
            ContextualFeedbackSeverity::INFO
        );

        return '';
    }
}
