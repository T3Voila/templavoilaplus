<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Domain\Model\Configuration;

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

/**
 * Class to provide unique access to MappingConfiguration
 */
class MappingConfiguration extends AbstractConfiguration
{
    /** @var string */
    protected $combinedDataStructureIdentifier = '';

    /** @var string */
    protected $combinedTemplateConfigurationIdentifier = '';

    /** @var string */
    protected $combinedBackendLayoutConfigurationIdentifier = '';

    /** @var array */
    protected $mappingToTemplate = [];

    /** @var array [string => MappingConfiguration] */
    protected $childMappingConfigurations = [];

    /** @var array [string => array] */
    protected $childSelectors = [];

    /**
     * Retrieve the DS configuration identifier
     */
    public function getCombinedDataStructureIdentifier(): string
    {
        return $this->combinedDataStructureIdentifier;
    }

    public function setCombinedDataStructureIdentifier($combinedDataStructureIdentifier): void
    {
        $this->combinedDataStructureIdentifier = $combinedDataStructureIdentifier;
    }

    /**
     * Retrieve the templateConfiguration identifier
     */
    public function getCombinedTemplateConfigurationIdentifier(): string
    {
        return $this->combinedTemplateConfigurationIdentifier;
    }

    public function setCombinedTemplateConfigurationIdentifier($combinedTemplateConfigurationIdentifier): void
    {
        $this->combinedTemplateConfigurationIdentifier = $combinedTemplateConfigurationIdentifier;
    }

    /**
     * Retrieve the backendLayoutConfiguration identifier
     */
    public function getCombinedBackendLayoutConfigurationIdentifier(): string
    {
        return $this->combinedBackendLayoutConfigurationIdentifier;
    }

    public function setCombinedBackendLayoutConfigurationIdentifier($combinedBackendLayoutConfigurationIdentifier): void
    {
        $this->combinedBackendLayoutConfigurationIdentifier = $combinedBackendLayoutConfigurationIdentifier;
    }

    /**
     * Retrieve the mapping from ds/row to templateConfiguration
     */
    public function getMappingToTemplate(): array
    {
        return $this->mappingToTemplate;
    }

    public function setMappingToTemplate(array $mappingToTemplate): void
    {
        $this->mappingToTemplate = $mappingToTemplate;
    }

    /**
     * Retrieve all child mapping configurations
     */
    public function getChildMappingConfigurations(): array
    {
        return $this->childMappingConfigurations;
    }

    /**
     * Retrieve a child mapping configuration
     */
    public function getChildMappingConfiguration(string $childName): ?MappingConfiguration
    {
        return $this->childMappingConfigurations[$childName] ?? null;
    }
    /**
     * Adds or overwrites a child mapping configuration
     */
    public function addChildMappingConfiguration(string $childName, MappingConfiguration $configuration): void
    {
        $this->childMappingConfigurations[$childName] = $configuration;
    }

    /**
     * Retrieve all child mapping configurations
     */
    public function getChildSelectors(): array
    {
        return $this->childSelectors;
    }

    /**
     * Retrieve a child mapping configuration
     */
    public function getChildSelector(string $childSelectorName): ?array
    {
        return $this->childSelectors[$childSelectorName] ?? null;
    }
    /**
     * Adds or overwrites a child mapping configuration
     */
    public function addChildSelector(string $childSelectorName, array $configuration): void
    {
        $this->childSelectors[$childSelectorName] = $configuration;
    }
}
