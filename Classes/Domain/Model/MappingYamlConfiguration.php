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

use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Class to provide unique access to MappingYamlConfiguration
 */
class MappingYamlConfiguration
{
    /**
     * @var \TYPO3\CMS\Core\Resource\File
     */
    protected $file;

    /**
     * @var string
     */
    protected $identifier = '';

    /**
     * @var string
     */
    protected $label = '';

    /**
     * @var string
     */
    protected $combinedTemplateConfigurationIdentifier = '';

    /**
     * @var array
     */
    protected $mappingToTemplate = [];

    /**
     * @param \TYPO3\CMS\Core\Resource\File $file
     */
    public function __construct(\TYPO3\CMS\Core\Resource\File $file, $identifier)
    {
        $this->file = $file;
        $this->identifier = $identifier;

        // @TODO This shouldn't be here
        $yamlFileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
        $configuration = $yamlFileLoader->load($file->getForLocalProcessing(false));

        if (!isset($configuration['tvp-mapping'])) {
            throw new \Exception('No TemplaVoilà! Plus mapping configuration');
        }

        if (isset($configuration['tvp-mapping']['meta']['label'])) {
            $this->setLabel($configuration['tvp-mapping']['meta']['label']);
        }
        if (isset($configuration['tvp-mapping']['combinedTemplateConfigurationIdentifier'])) {
            $this->setCombinedTemplateConfigurationIdentifier($configuration['tvp-mapping']['combinedTemplateConfigurationIdentifier']);
        }
        if (isset($configuration['tvp-mapping']['mappingToTemplate']) && is_array($configuration['tvp-mapping']['mappingToTemplate'])) {
            $this->setMappingToTemplate($configuration['tvp-mapping']['mappingToTemplate']);
        }
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Retrieve the label of the template
     *
     * @return string
     */
    public function getLabel()
    {
        return TemplaVoilaUtility::getLanguageService()->sL($this->label);
    }

    public function setLabel(string $label)
    {
        $this->label = $label;
    }

    /**
     * Retrieve the mapping from ds/row to templateConfiguration
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
     * Retrieve the mapping of the template
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


    public function getHandler()
    {
        return new \Ppi\TemplaVoilaPlus\Handler\Mapping\DefaultMappingHandler($this);
    }


    public function getFileIdentifier()
    {
        return $this->file->getIdentifier();
    }

    /**
     * Retrieve the filereference of the template
     *
     * @return integer
     */
    public function getTstamp()
    {
        return $this->file->getProperty('modification_date');
    }

    /**
     * Retrieve the filereference of the template
     *
     * @return integer
     */
    public function getCrdate()
    {
        return $this->file->getProperty('creation_date');
    }
}
