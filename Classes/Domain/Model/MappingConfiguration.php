<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Domain\Model;

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
     * @var array
     */
    protected $mappingToTemplate = [];

    /**
     * Retrieve the DS configuration identifier
     *
     * @return string
     */
    public function getCombinedDataStructureIdentifier()
    {
        return $this->combinedDataStructureIdentifier;
    }

    public function setCombinedDataStructureIdentifier($combinedDataStructureIdentifier)
    {
        $this->combinedDataStructureIdentifier = $combinedDataStructureIdentifier;
    }

    /**
     * Retrieve the templateConfiguration identifier
     *
     * @return string
     */
    public function getCombinedTemplateConfigurationIdentifier()
    {
        return $this->combinedTemplateConfigurationIdentifier;
    }

    public function setCombinedTemplateConfigurationIdentifier($combinedTemplateConfigurationIdentifier)
    {
        $this->combinedTemplateConfigurationIdentifier = $combinedTemplateConfigurationIdentifier;
    }

    /**
     * Retrieve the mapping from ds/row to templateConfiguration
     *
     * @return string
     */
    public function getMappingToTemplate()
    {
        return $this->mappingToTemplate;
    }

    public function setMappingToTemplate(array $mappingToTemplate)
    {
        $this->mappingToTemplate = $mappingToTemplate;
    }
}
