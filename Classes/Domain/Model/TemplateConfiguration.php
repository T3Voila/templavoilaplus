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
 * Class to provide unique access to TemplateConfiguration
 */
class TemplateConfiguration extends AbstractConfiguration
{
    /**
     * @var string
     */
    protected $rendererName = '';

    /**
     * @var array
     */
    protected $header = [];

    /**
     * @var array
     */
    protected $mapping = [];

    /**
     * Retrieve the name of the renderer of the template
     *
     * @TODO Should this be named identifier?
     * @return string
     */
    public function getRendererName(): string
    {
        return $this->rendererName;
    }

    public function setRendererName(string $rendererName)
    {
        $this->rendererName = $rendererName;
    }

    /**
     * Retrieve the header of the template
     *
     * @return string
     */
    public function getHeader()
    {
        return $this->header;
    }

    public function setHeader(array $header)
    {
        $this->header = $header;
    }

    /**
     * Retrieve the mapping of the template
     *
     * @return string
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    public function setMapping(array $mapping)
    {
        $this->mapping = $mapping;
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

    /**
     * @TODO This is a stupid idea
     */
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
