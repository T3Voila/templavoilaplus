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

use \TYPO3\CMS\Core\Core\Event\BootCompletedEvent;

class RegisterFormEngine
{
    public function processEvent(BootCompletedEvent $event)
    {
        $this->registerFormEngineContainer();
        $this->registerFormEngineProviders();
        $this->registerHookFormEngine();
    }

    public function registerFormEngineContainer()
    {
        // Register language aware flex form handling in FormEngine
        // Register render elements
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1443361297] = [
            'nodeName' => 'flex',
            'priority' => 40,
            'class' => \Tvp\TemplaVoilaPlus\Form\Container\FlexFormEntryContainer::class,
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1443361298] = [
            'nodeName' => 'flexFormNoTabsContainer',
            'priority' => 40,
            'class' => \Tvp\TemplaVoilaPlus\Form\Container\FlexFormNoTabsContainer::class,
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1443361299] = [
            'nodeName' => 'flexFormTabsContainer',
            'priority' => 40,
            'class' => \Tvp\TemplaVoilaPlus\Form\Container\FlexFormTabsContainer::class,
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1443361300] = [
            'nodeName' => 'flexFormElementContainer',
            'priority' => 40,
            'class' => \Tvp\TemplaVoilaPlus\Form\Container\FlexFormElementContainer::class,
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1443361301] = [
            'nodeName' => 'flexFormSectionContainer',
            'priority' => 40,
            'class' => \Tvp\TemplaVoilaPlus\Form\Container\FlexFormSectionContainer::class,
        ];
    }

    public function registerFormEngineProviders()
    {
        // Unregister stock TcaFlex* data provider and substitute with own data provider at the same dependency position
        \Tvp\TemplaVoilaPlus\Utility\FormEngineUtility::replaceInFormDataGroups(
            [
                \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class
                    => \Tvp\TemplaVoilaPlus\Form\FormDataProvider\TcaFlexProcess::class,
                \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexFetch::class
                    => \Tvp\TemplaVoilaPlus\Form\FormDataProvider\TcaFlexFetch::class,
                \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class
                    => \Tvp\TemplaVoilaPlus\Form\FormDataProvider\TcaFlexPrepare::class,
            ]
        );
        \Tvp\TemplaVoilaPlus\Utility\FormEngineUtility::replaceInFormDataGroups(
            [
                \TYPO3\CMS\Backend\Form\FormDataProvider\EvaluateDisplayConditions::class
                    => \Tvp\TemplaVoilaPlus\Form\FormDataProvider\EvaluateDisplayConditions::class,
            ]
        );
        // In TYPO3 8 there is no TcaFlexFetch, so readd it.
        // @TODO Remerge Core
        \Tvp\TemplaVoilaPlus\Utility\FormEngineUtility::addTcaFlexFetch();
    }

    public function registerHookFormEngine()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class]['className']
            = \Tvp\TemplaVoilaPlus\Configuration\FlexForm\FlexFormTools8::class;
    }
}
