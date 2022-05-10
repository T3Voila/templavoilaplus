<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Domain\Model;

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
    /**
     * @var string
     */
    protected $combinedDataStructureIdentifier = '';

    /**
     * @var string
     */
    protected $combinedTemplateConfigurationIdentifier = '';

    /**
     * @var string
     */
    protected $combinedBackendLayoutConfigurationIdentifier = '';

    /**
     * @var array
     */
    protected $mappingToTemplate = [];

    /**
     * @var array
     */
    protected $childs = [];

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
     * Retrieve all child configurations
     */
    public function getChilds(): array
    {
        return $this->childs;
    }

    /**
     * Retrieve a child configuration
     */
    public function getChild(string $childName): ?MappingConfiguration
    {
        return $this->childs[$childName] ?? null;
    }
    /**
     * Adds or overwrites a child configuration
     */
    public function addChild(string $childName, MappingConfiguration $configuration): void
    {
        $this->childs[$childName] = $configuration;
    }
}
