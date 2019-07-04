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
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigurationService implements SingletonInterface
{
    private $extConfig = [];
    private $dataStructurePlaces = [];

    public function __construct()
    {
        if (version_compare(TYPO3_version, '9.0.0', '>=')) {
            $this->extConfig = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['templavoilaplus'];
        } else {
            $this->extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoilaplus']);
        }
    }

    public function getExtensionConfig()
    {
        return $this->extConfig;
    }

    public function getDataStructurePlaces(): array
    {
        return $this->dataStructurePlaces;
    }

    public function registerDataStructurePlace($uuid, $name, $path, $scope)
    {
        // @TODO Check if path is inside FAL and add danger hint!
        $path = GeneralUtility::getFileAbsFileName($path);
        if (!is_dir($path) || !is_readable($path)) {
            throw new \Exception('path ' . $path . 'not exists or readable');
        }
        if (isset($this->dataStructurePlaces[$uuid])) {
            throw new \Exception('uuid already exists');
        }

        $this->dataStructurePlaces[$uuid] = [
            'name' => $name,
            'path' => $path,
            'scope' => $scope, // Caution scope should be the table name in a future release
        ];
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
}
