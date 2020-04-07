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
 * Class to provide unique access to TemplateYamlConfiguration
 */
class TemplateYamlConfiguration
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
    protected $rendererName = '';

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

        if (!isset($configuration['tvp-template'])) {
            throw new \Exception('No TemplaVoilà! Plus template configuration');
        }

        if (isset($configuration['tvp-template']['label'])) {
            $this->setLabel($configuration['tvp-template']['label']);
        }
        if (isset($configuration['tvp-template']['renderer'])) {
            $this->setRendererName($configuration['tvp-template']['renderer']);
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
     * Retrieve the name of the renderer of the template
     *
     * @TODO Should this be named identifier?
     * @return string
     */
    public function getRendererName()
    {
        return $this->rendererName;
    }

    public function setRendererName(string $rendererName)
    {
        $this->rendererName = $rendererName;
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

    public function getTemplateFile(): FileInterface
    {
        // Remove the .tvp.yaml file extension
        $fileName = mb_substr($this->file->getForLocalProcessing(false), 0, -9);
        $fileInfo = pathinfo($fileName);

        $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();

        $filter = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter::class);
        $filter->setAllowedFileExtensions('html,htm,tmpl');

        $folder = $resourceFactory->retrieveFileOrFolderObject($fileInfo['dirname']);
        $folder->setFileAndFolderNameFilters([[$filter, 'filterFileList']]);
        $files = $folder->getFiles(0, 0, \TYPO3\CMS\Core\Resource\Folder::FILTER_MODE_USE_OWN_FILTERS, true);

        foreach ($files as $file) {
            if ($file->getNameWithoutExtension() === $fileInfo['basename']) {
                return $file;
                break 1;
            }
        }

        throw new \Exception('Template file for TemplateConfiguration not found');
    }
}
