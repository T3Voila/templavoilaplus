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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Class to provide unique access to xml datastructure files
 */
class XmlFileDataStructure extends AbstractDataStructure
{
    /**
     * @var \TYPO3\CMS\Core\Resource\File
     */
    protected $file;

    /**
     * @var string
     */
    protected $xmlContent = null;

    protected $dataStructureArray = [];

    /**
     * @param \TYPO3\CMS\Core\Resource\File $file
     * @param string $scope
     */
    public function __construct(\TYPO3\CMS\Core\Resource\File $file, $scope)
    {
        $this->file = $file;

        $this->setLabel($file->getName());
        $this->setScope($scope);

        // @TODO Set iconfile if found
        // Old way was $iconPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.gif';
        // $this->setIcon('../' . $iconPath);

        $this->dataStructureArray = $this->getDataStructureAsArray($this->file->getContents());

        // Read title from XML file and set, if not empty or ROOT
        if (!empty($this->dataStructureArray['meta']['title'])
            && $this->dataStructureArray['meta']['title'] !== 'ROOT'
        ) {
            $this->setLabel($this->dataStructureArray['meta']['title']);
        }
    }

    public function getIdentifier()
    {
        return $this->file->getIdentifier();
    }

    // @TODO Needed anymore?
    public function getKey() {} // this is identifier
    public function getStoragePids() {}
    public function getDataprotXML() {}
    public function isPermittedForUser($parentRow = array(), $removeItems = array(), $showAdminAll = true) {return true;}
    public function getCruser() {}

    // @TODO Into abstract?
    public function getDataStructureArray(): array
    {
        return $this->dataStructureArray;
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
     * @param void
     *
     * @return mixed
     */
    public function getBeLayout()
    {
        return false;
    }

    /**
     * @param void
     *
     * @return string
     */
    public function getSortingFieldValue()
    {
        return $this->getLabel(); // required to resolve LLL texts
    }
}
