<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Form;

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

use TYPO3\CMS\Core\Core\Event\BootCompletedEvent;
use TYPO3\CMS\Core\Information\Typo3Version;

class RegisterFormEngine
{
    public function processEvent(BootCompletedEvent $event)
    {
        $this->registerHookFormEngine();
    }

    public function registerHookFormEngine()
    {
        if (version_compare((string) new Typo3Version(), '13.0', '>')) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class]['className']
                = \Tvp\TemplaVoilaPlus\Configuration\FlexForm\FlexFormTools13::class;
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class]['className']
                = \Tvp\TemplaVoilaPlus\Configuration\FlexForm\FlexFormTools12::class;
        }
    }
}
