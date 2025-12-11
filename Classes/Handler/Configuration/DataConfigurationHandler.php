<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Handler\Configuration;

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

use Symfony\Component\Finder\SplFileInfo;
use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\AbstractConfiguration;
use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\DataConfiguration;
use TYPO3\CMS\Core\Information\Typo3Version;

class DataConfigurationHandler extends AbstractConfigurationHandler
{
    public static $identifier = 'TVP\ConfigurationHandler\DataConfiguration';

    public function createConfigurationFromConfigurationArray(array $dataStructure, string $identifier, SplFileInfo $file): AbstractConfiguration
    {
        $dataConfiguration = new DataConfiguration($identifier, $this->place, $this, $file);

        $options = $this->place->getOptions();

        // Read title from XML file and set, if not empty or ROOT
        if (
            !empty($dataStructure['meta']['title'])
            && $dataStructure['meta']['title'] !== 'ROOT'
        ) {
            $dataConfiguration->setName($dataStructure['meta']['title']);
        } else {
            $dataConfiguration->setName($file->getFilename());
        }

        /** @TODO setIcon */
        $dataConfiguration->setDataStructure($dataStructure);

        return $dataConfiguration;
    }

    public function saveConfiguration(AbstractConfiguration $configuration): void
    {
        if ($configuration instanceof DataConfiguration) {
            $this->loadSaveHandler->save($configuration->getFile(), $configuration->getDataStructure());
        } else {
            throw new \Exception('Configuration of wrong type');
        }
    }
}
