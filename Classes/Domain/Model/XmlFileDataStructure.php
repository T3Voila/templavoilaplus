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
    /** @TODO file and identifier handling could be moved in an abstract for all configurations
    /**
     * @var \TYPO3\CMS\Core\Resource\File
     */
    protected $file;

    /**
     * @var string
     */
    protected $identifier = '';

    /**
     * @param \TYPO3\CMS\Core\Resource\File $file
     */
    public function __construct(\TYPO3\CMS\Core\Resource\File $file, $identifier)
    {
        $this->file = $file;
        $this->identifier = $identifier;

        // @TODO setIcon
        $this->setLabel($file->getName());
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
        return $this->identifier;
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
     * @param void
     *
     * @return mixed
     */
    public function getBeLayout()
    {
        return false;
    }

    public function getCruser() {}
}
