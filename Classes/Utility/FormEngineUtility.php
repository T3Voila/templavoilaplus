<?php

namespace Tvp\TemplaVoilaPlus\Utility;

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

class FormEngineUtility
{
    public static function replaceInFormDataGroups($replacements)
    {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup'] as $group => $_) {
            static::replaceinFormDataGroup($replacements, $group);
        }
    }

    public static function replaceinFormDataGroup(array $replacements, $group)
    {
        $groupProvider = $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup'][$group];

        // First replace keys
        foreach ($replacements as $from => $to) {
            if (isset($groupProvider[$from])) {
                $groupProvider[$to] = $groupProvider[$from];
                unset($groupProvider[$from]);
            }
        }

        // Second replace items
        array_walk_recursive(
            $groupProvider,
            function (&$item, $key, $replacements) {
                if (isset($replacements[$item])) {
                    $item = $replacements[$item];
                }
            },
            $replacements
        );

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup'][$group] = $groupProvider;
    }

    /**
     * In TYPO3 8 LTS TcaFlexFetch was merged into TcaFlexPrepare, so we need to add our TcaFlexFetch back,
     * as we support also TYPO3 7 LTS. We can merge our side if we start TV+ 8
     * Call after replaceInFormDataGroups!
     *
     * @TODO remove with TV+ 8
     */
    public static function addTcaFlexFetch()
    {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup'] as $group => &$groupConfig) {
            if (
                !isset($groupConfig[\Tvp\TemplaVoilaPlus\Form\FormDataProvider\TcaFlexFetch::class])
                && isset($groupConfig[\Tvp\TemplaVoilaPlus\Form\FormDataProvider\TcaFlexPrepare::class])
            ) {
                $groupConfig[\Tvp\TemplaVoilaPlus\Form\FormDataProvider\TcaFlexFetch::class]
                    = $groupConfig[\Tvp\TemplaVoilaPlus\Form\FormDataProvider\TcaFlexPrepare::class];
                $groupConfig[\Tvp\TemplaVoilaPlus\Form\FormDataProvider\TcaFlexPrepare::class] = [
                    'depends' => [\Tvp\TemplaVoilaPlus\Form\FormDataProvider\TcaFlexFetch::class],
                ];
            }
        }
    }
}
