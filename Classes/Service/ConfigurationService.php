<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Service;

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
use TYPO3\CMS\Core\SingletonInterface;

class ConfigurationService implements SingletonInterface
{
    private $extConfig = [];

    public function __construct()
    {
        if (version_compare(TYPO3_version, '9.0.0', '>=')) {
            $this->extConfig = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['templavoilaplus'];
        } else {
            $extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoilaplus']);
        }
    }

    public function getDataStructurePlaces(): array
    {
        if ($this->isStaticDataStructureEnabled()) {
            $conf = $this->getDataStructurePlacesFromEmConfiguration();
        }

        return $conf;
    }

    public function isStaticDataStructureEnabled(): bool
    {
        if (version_compare(TYPO3_version, '9.0.0', '>=')) {
            if ($this->extConfig['staticDS']['enable']) {
                return true;
            }
        } else {
            if ($this->extConfig['staticDS.']['enable']) {
                return true;
            }
        }

        return false;
    }

    public function getDataStructurePlacesFromEmConfiguration(): array
    {
        $confPathDot = '';
        if (version_compare(TYPO3_version, '9.0.0', '<=')) {
            $confPathDot = '.';
        }

        return [
            'fce' => [
                'name' => 'FCE',
                'path' => $this->extConfig['staticDS' . $confPathDot]['path_fce'],
                'type' => 'fce',
            ],
            'page' => [
                'name' => 'PAGE',
                'path' => $this->extConfig['staticDS' . $confPathDot]['path_page'],
                'type' => 'page',
            ],
        ];
    }
}
