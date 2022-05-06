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

use Tvp\TemplaVoilaPlus\Domain\Model\MappingConfiguration;

class MappingConfigurationHandler extends AbstractConfigurationHandler
{
    public static $identifier = 'TVP\ConfigurationHandler\MappingConfiguration';

    public function createConfigurationFromConfigurationArray($configuration, $identifier, $possibleName): MappingConfiguration
    {
        $mappingConfiguration = new MappingConfiguration($this->place, $identifier);
        $mappingConfiguration->setName($possibleName);

        if (!isset($configuration['tvp-mapping'])) {
            throw new \Exception('No TemplaVoilà! Plus mapping configuration');
        }

        if (isset($configuration['tvp-mapping']['meta']['name'])) {
            $mappingConfiguration->setName($configuration['tvp-mapping']['meta']['name']);
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

        return $mappingConfiguration;
    }
}
