<?php

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller to show the switch dialog.
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class UpdateController extends AbstractUpdateController
{

    /**
     * List all available configurations for templates
     */
    public function infoAction()
    {
//         $this->registerDocheaderButtons();
//         $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation([]);
//         $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());

        $this->view->assign('pageTitle', 'TemplaVoilà! Plus - Update Scripts');
    }
}
