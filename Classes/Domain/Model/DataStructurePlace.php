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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

use Ppi\TemplaVoilaPlus\Exception\DataStructureException;
use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Class to provide unique access to datastructure
 */
class DataStructurePlace
{
    /**
     * @var integer
     */
    protected $scope = 0;

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $pathAbsolute;

    /**
     * @var array
     */
    protected $dataStructures;

    public function __construct($uuid, $name, $scope, $pathAbsolute)
    {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->scope = $scope;
        $this->pathAbsolute = $pathAbsolute;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function getName()
    {
        return TemplaVoilaUtility::getLanguageService()->sL($this->name);
    }

    public function getPathAbsolute()
    {
        return $this->pathAbsolute;
    }

    public function getPathRelative()
    {

        return PathUtility::stripPathSitePrefix($this->pathAbsolute);
    }

    public function getDataStructures(): array
    {
        $this->initializeDataStructures();
        return $this->dataStructures;
    }

    public function getDataStructure($identifier): AbstractDataStructure
    {
        $this->initializeDataStructures();
        return $this->dataStructures[$identifier];
    }

    protected function initializeDataStructures()
    {
        if ($this->dataStructures === null) {
            $this->dataStructures = [];
            $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();

            $filter = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter::class);
            $filter->setAllowedFileExtensions('xml');

            $folder = $resourceFactory->retrieveFileOrFolderObject($this->pathAbsolute);
            $folder->setFileAndFolderNameFilters([[$filter, 'filterFileList']]);

            $files = $folder->getFiles(0, 0, \TYPO3\CMS\Core\Resource\Folder::FILTER_MODE_USE_OWN_FILTERS, true);

            foreach($files as $file) {
                $this->dataStructures[$file->getIdentifier()] = new XmlFileDataStructure($file, $this->scope);
            }
        }
    }
}
