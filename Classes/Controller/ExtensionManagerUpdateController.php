<?php
namespace Ppi\TemplaVoilaPlus\Controller;

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

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Managing all possible update scripts inside TemplaVoilà
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class ExtensionManagerUpdateController
{
    /**
     * Main function, returning the HTML content of the module
     *
     * @return string The HTML to be shown.
     */
    public function run()
    {
        switch (GeneralUtility::_GP('update')) {
            case 'StaticData':
                $controller = GeneralUtility::makeInstance(Update\StaticDataUpdateController::class);
                break;
            case 'DataStructure':
                $controller = GeneralUtility::makeInstance(Update\DataStructureUpdateController::class);
                break;
            default:
                $controller = GeneralUtility::makeInstance(Update\SwitchUpdateController::class);
                break;
        }
        return $controller->run();

    }
    
    /**
     * Checks if backend user is an administrator
     * (this function is called from the extension manager ext_update)
     *
     * @return boolean
     */
    public function shouldBeShown()
    {
        return TemplaVoilaUtility::getBackendUser()->isAdmin();
    }
}
