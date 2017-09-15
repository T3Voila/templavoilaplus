<?php
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

use Ppi\TemplaVoilaPlus\Exception\DataStructureException;
use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Class to provide unique access to datastructure
 */
abstract class AbstractDataStructure
{

    /**
     * @var integer
     */
    const SCOPE_UNKNOWN = 0;

    /**
     * @var integer
     */
    const SCOPE_PAGE = 1;

    /**
     * @var integer
     */
    const SCOPE_FCE = 2;

    /**
     * @var integer
     */
    protected $scope = self::SCOPE_UNKNOWN;

    /**
     * @var string
     */
    protected $label = '';

    /**
     * @var string
     */
    protected $iconFile = '';

    /**
     * Retrieve the label of the datastructure
     *
     * @return string
     */
    public function getLabel()
    {
        return TemplaVoilaUtility::getLanguageService()->sL($this->label);
    }

    /**
     * @param string $str
     *
     * @return void
     */
    protected function setLabel($str)
    {
        $this->label = $str;
    }

    /**
     * Retrieve the label of the datastructure
     *
     * @return integer
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param integer $scope
     *
     * @return void
     */
    protected function setScope($scope)
    {
        if ($scope == self::SCOPE_PAGE || $scope == self::SCOPE_FCE) {
            $this->scope = $scope;
        } else {
            $this->scope = self::SCOPE_UNKNOWN;
        }
    }

    /**
     * However the datastructure is identifiable (uid or filepath
     * This method deliver the relevant key
     *
     * @return string
     */
    abstract public function getKey();

    /**
     * Determine the icon and append the path
     * assuming that the path for the iconFile is relative to the TYPO3 main folder
     *
     * @return string
     */
    public function getIcon()
    {
        //regex is used to check if there's a filename within the iconFile string
        return preg_replace('/^.*\/([^\/]+\.(gif|png))?$/i', '\1', $this->iconFile) ? $this->iconFile : '';
    }

    /**
     * @param string $filename
     *
     * @return void
     */
    protected function setIcon($filename)
    {
        $this->iconFile = $filename;
    }

    /**
     * Determine relevant storage pids for this element,
     * usually one uid but in certain situations this might contain multiple uids (see staticds)
     *
     * @return string
     */
    abstract public function getStoragePids();

    /**
     * Provides the datastructure configuration as XML
     *
     * @return string
     */
    abstract public function getDataprotXML();

    /**
     * Provides the datastructure configuration as array
     *
     * @return array
     */
    public function getDataprotArray()
    {
        $arr = [];
        $ds = $this->getDataprotXML();

        if (strlen($ds) > 1) {
            $arr = GeneralUtility::xml2array($ds);
            if (!is_array($arr)) {
                throw new DataStructureException(
                    'XML of DS "' . $this->getLabel() . '" cant\'t be read, we get following error: ' . $arr
                );
            }
        }

        return $arr;
    }

    /**
     * Determine whether the current user has permission to create elements based on this
     * datastructure or not
     *
     * @param array $parentRow
     * @param array $removeItems
     *
     * @return boolean
     */
    abstract public function isPermittedForUser($parentRow = array(), $removeItems = array());

    /**
     * Enables to determine whether this element is based on a record or on a file
     * Required for view-related tasks (edit-icons)
     *
     * @return boolean
     */
    public function isFilebased()
    {
        return false;
    }

    /**
     * Retrieve the filereference of the template
     *
     * @return string
     */
    abstract public function getTstamp();

    /**
     * Retrieve the filereference of the template
     *
     * @return string
     */
    abstract public function getCrdate();

    /**
     * Retrieve the filereference of the template
     *
     * @return string
     */
    abstract public function getCruser();

    /**
     * @param void
     *
     * @return mixed
     */
    abstract public function getBeLayout();

    /**
     * @param void
     *
     * @return string
     */
    abstract public function getSortingFieldValue();
}
