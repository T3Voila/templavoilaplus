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
use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\MappingConfiguration;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

class MappingConfigurationHandler extends AbstractConfigurationHandler
{
    public static $identifier = 'TVP\ConfigurationHandler\MappingConfiguration';

    public function createConfigurationFromConfigurationArray(array $configuration, string $identifier, SplFileInfo $file): AbstractConfiguration
    {
        $mappingConfiguration = new MappingConfiguration($identifier, $this->place, $this, $file);

        if (!isset($configuration['tvp-mapping'])) {
            throw new \Exception('No TemplaVoilà! Plus mapping configuration');
        }

        if (isset($configuration['tvp-mapping']['meta']['name'])) {
            if (strpos(trim($configuration['tvp-mapping']['meta']['name']), 'LLL:') === 0) {
                $mappingConfiguration->setName(TemplaVoilaUtility::getLanguageService()->sL($configuration['tvp-mapping']['meta']['name']) ?? $configuration['tvp-mapping']['meta']['name']);
            } else {
                $mappingConfiguration->setName($configuration['tvp-mapping']['meta']['name']);
            }
        } else {
            $mappingConfiguration->setName($file->getFilename());
        }
        if (isset($configuration['tvp-mapping']['combinedDataStructureIdentifier'])) {
            $mappingConfiguration->setCombinedDataStructureIdentifier($configuration['tvp-mapping']['combinedDataStructureIdentifier']);
        }
        if (isset($configuration['tvp-mapping']['combinedTemplateConfigurationIdentifier'])) {
            $mappingConfiguration->setCombinedTemplateConfigurationIdentifier($configuration['tvp-mapping']['combinedTemplateConfigurationIdentifier']);
        }
        if (isset($configuration['tvp-mapping']['combinedBackendLayoutConfigurationIdentifier'])) {
            $mappingConfiguration->setCombinedBackendLayoutConfigurationIdentifier($configuration['tvp-mapping']['combinedBackendLayoutConfigurationIdentifier']);
        }
        if (isset($configuration['tvp-mapping']['mappingToTemplate']) && is_array($configuration['tvp-mapping']['mappingToTemplate'])) {
            $mappingConfiguration->setMappingToTemplate($configuration['tvp-mapping']['mappingToTemplate']);
        }
        if (isset($configuration['tvp-mapping']['childTemplate']) && is_array($configuration['tvp-mapping']['childTemplate'])) {
            foreach ($configuration['tvp-mapping']['childTemplate'] as $childName => $childConfiguration) {
                $childMappingConfiguration = $this->createConfigurationFromConfigurationArray(['tvp-mapping' => $childConfiguration], '', $file);
                $mappingConfiguration->addChildMappingConfiguration($childName, $childMappingConfiguration);
            }
        }
        if (isset($configuration['tvp-mapping']['childSelector']) && is_array($configuration['tvp-mapping']['childSelector'])) {
            foreach ($configuration['tvp-mapping']['childSelector'] as $childSelectorName => $childSelectorConfiguration) {
                $mappingConfiguration->addChildSelector($childName, $childSelectorConfiguration);
            }
        }

        $mappingConfiguration->setDescription($configuration['tvp-mapping']['meta']['description'] ?? '');
        $mappingConfiguration->setIconIdentifier($configuration['tvp-mapping']['meta']['iconIdentifier'] ?? '');
        return $mappingConfiguration;
    }

    public function saveConfiguration(AbstractConfiguration $configuration): void
    {
        throw new \Exception('Not Yet Implemented');
    }
}
