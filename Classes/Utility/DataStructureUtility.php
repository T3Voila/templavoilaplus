<?php

declare(strict_types=1);

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

use Tvp\TemplaVoilaPlus\Service\ConfigurationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class with static functions for data structures templavoila.
 *
 * @author Alexander Opitz <opitz@pluspol-interactive.de>
 */
final class DataStructureUtility
{
    public static function array2xml(array $dataStructure, int $indentation = -1)
    {
        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $conf = $configurationService->getExtensionConfig();

        if ($indentation === -1 && isset($conf['ds']['indentation'])) {
            $indentation = (int)$conf['ds']['indentation'];
        }

        return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>'
        . LF
        . GeneralUtility::array2xml(
            $dataStructure,
            '',
            0,
            'T3DataStructure',
            $indentation,
            [
                'useCDATA' => 1,
                'disableTypeAttrib' => 2,
            ]
        )
        . LF;
    }
}
