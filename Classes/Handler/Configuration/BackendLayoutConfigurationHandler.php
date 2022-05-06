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

use Tvp\TemplaVoilaPlus\Domain\Model\BackendLayoutConfiguration;
use Tvp\TemplaVoilaPlus\Domain\Model\Place;

class BackendLayoutConfigurationHandler extends AbstractConfigurationHandler
{
    public static $identifier = 'TVP\ConfigurationHandler\BackendLayoutConfiguration';

    public function createConfigurationFromConfigurationArray($configuration, $identifier, $possibleName): BackendLayoutConfiguration
    {
        $templateConfiguration = new BackendLayoutConfiguration($this->place, $identifier);
        $templateConfiguration->setName($possibleName);

        if (!isset($configuration['tvp-beLayout'])) {
            throw new \Exception('No TemplaVoilà! Plus BackendLayout configuration');
        }

        if (isset($configuration['tvp-beLayout']['meta']['name'])) {
            $templateConfiguration->setName($configuration['tvp-beLayout']['meta']['name']);
        }
        if (isset($configuration['tvp-beLayout']['meta']['renderer'])) {
            /** @TODO Check before setting */
            $templateConfiguration->setRenderHandlerIdentifier($configuration['tvp-beLayout']['meta']['renderer']);
        }
        if (isset($configuration['tvp-beLayout']['meta']['template'])) {
            /**
             * @TODO Check before setting
             * @TODO Relative to Place or configuration file? Support Absolute or 'EXT:' (insecure?)
             */
            $templateConfiguration->setTemplateFileName($configuration['tvp-beLayout']['meta']['template']);
        }

        return $templateConfiguration;
    }
}
