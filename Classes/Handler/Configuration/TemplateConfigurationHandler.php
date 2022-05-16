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
use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\TemplateConfiguration;

class TemplateConfigurationHandler extends AbstractConfigurationHandler
{
    public static $identifier = 'TVP\ConfigurationHandler\TemplateConfiguration';

    public function createConfigurationFromConfigurationArray(array $configuration, string $identifier, SplFileInfo $file): TemplateConfiguration
    {
        $templateConfiguration = new TemplateConfiguration($identifier, $this->place, $this, $file);

        if (!isset($configuration['tvp-template'])) {
            throw new \Exception('No TemplaVoilà! Plus template configuration');
        }

        if (isset($configuration['tvp-template']['meta']['name'])) {
            $templateConfiguration->setName($configuration['tvp-template']['meta']['name']);
        } else {
            $templateConfiguration->setName($file->getFilename());
        }
        if (isset($configuration['tvp-template']['meta']['renderer'])) {
            /** @TODO Check before setting */
            $templateConfiguration->setRenderHandlerIdentifier($configuration['tvp-template']['meta']['renderer']);
        }
        if (isset($configuration['tvp-template']['meta']['template'])) {
            /**
             * @TODO Check before setting
             * @TODO Relative to Place or configuration file? Support Absolute or 'EXT:' (insecure?)
             */
            $templateConfiguration->setTemplateFileName($configuration['tvp-template']['meta']['template']);
        }
        if (isset($configuration['tvp-template']['header']) && is_array($configuration['tvp-template']['header'])) {
            $templateConfiguration->setHeader($configuration['tvp-template']['header']);
        }
        if (isset($configuration['tvp-template']['mapping']) && is_array($configuration['tvp-template']['mapping'])) {
            $templateConfiguration->setMapping($configuration['tvp-template']['mapping']);
        }

        return $templateConfiguration;
    }

    public function saveConfiguration(AbstractConfiguration $configuration): void
    {
        throw new \Exception('Not Yet Implemented');
    }
}
