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
use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\BackendLayoutConfiguration;

class BackendLayoutConfigurationHandler extends AbstractConfigurationHandler
{
    public static $identifier = 'TVP\ConfigurationHandler\BackendLayoutConfiguration';

    public function createConfigurationFromConfigurationArray(array $configuration, string $identifier, SplFileInfo $file): AbstractConfiguration
    {
        $backendLayoutConfiguration = new BackendLayoutConfiguration($identifier, $this->place, $this, $file);

        if (!isset($configuration['tvp-beLayout'])) {
            throw new \Exception('No TemplaVoilà! Plus BackendLayout configuration');
        }

        if (isset($configuration['tvp-beLayout']['meta']['name'])) {
            $backendLayoutConfiguration->setName($configuration['tvp-beLayout']['meta']['name']);
        } else {
            $backendLayoutConfiguration->setName($file->getFilename());
        }
        if (isset($configuration['tvp-beLayout']['meta']['renderer'])) {
            /** @TODO Check before setting */
            $backendLayoutConfiguration->setRenderHandlerIdentifier($configuration['tvp-beLayout']['meta']['renderer']);
        }
        if (isset($configuration['tvp-beLayout']['meta']['template'])) {
            /**
             * @TODO Check before setting
             * @TODO Relative to Place or configuration file? Support Absolute or 'EXT:' (insecure?)
             */
            $backendLayoutConfiguration->setTemplateFileName($configuration['tvp-beLayout']['meta']['template']);
        }
        if (isset($configuration['tvp-beLayout']['meta']['design'])) {
            $backendLayoutConfiguration->setDesign($configuration['tvp-beLayout']['meta']['design']);
        }
        if (isset($configuration['tvp-beLayout']['options']) && is_array($configuration['tvp-beLayout']['options'])) {
            $backendLayoutConfiguration->setOptions($configuration['tvp-beLayout']['options']);
        }

        return $backendLayoutConfiguration;
    }

    public function saveConfiguration(AbstractConfiguration $configuration): void
    {
        throw new \Exception('Not Yet Implemented');
    }
}
