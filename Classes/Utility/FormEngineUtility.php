<?php
namespace Extension\Templavoila\Utility;

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
    static public function replaceInTcaDatabaseRecord(array $replacements)
    {
        $tcaDatabaseRecord = $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'];

        // First replace keys
        foreach ($replacements as $from => $to) {
            $tcaDatabaseRecord[$to] = $tcaDatabaseRecord[$from];
            unset($tcaDatabaseRecord[$from]);
        }

        // Second replace items
        array_walk_recursive(
            $tcaDatabaseRecord,
            function(&$item, $key, $replacements) {
                if (isset($replacements[$item])) {
                    $item = $replacements[$item];
                }
            },
            $replacements
        );

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'] = $tcaDatabaseRecord;
    }
}
