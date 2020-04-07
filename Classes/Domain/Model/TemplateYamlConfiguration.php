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
    protected $label = '';

    /**
     * @param \TYPO3\CMS\Core\Resource\File $file
     */
    public function __construct(\TYPO3\CMS\Core\Resource\File $file)
    {
        $this->file = $file;

        // @TODO This shouldn't be here
        $yamlFileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
        $configuration = $yamlFileLoader->load($file->getForLocalProcessing(false));

        if (!isset($configuration['tvp-template'])) {
            throw new \Exception('No TemplaVoilà! Plus template configuration');
        }

        if (isset($configuration['tvp-template']['label'])) {
            $this->setLabel($configuration['tvp-template']['label']);
        }
    }

    /**
     * Retrieve the label of the datastructure
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

    public function getIdentifier()
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

    public function getTemplateFile()
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
