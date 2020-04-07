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
     * @var array
     */
    protected $dataStructureArray = [];

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

    public function getDataStructureArray(): array
    {
        return $this->dataStructureArray;
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
     * @return string
     */
    public function getIcon()
    {
        return '';
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
     * Provides the datastructure configuration as array
     *
     * @return array
     */
    public function getDataStructureAsArray($dataStructureXml)
    {
        $dataStructureArray = [];

        if (strlen($dataStructureXml) > 1) {
            $dataStructureArray = GeneralUtility::xml2array($dataStructureXml);
            if (!is_array($dataStructureArray)) {
                throw new DataStructureException(
                    'XML of DS "' . $this->getLabel() . '" cant\'t be read, we get following error: ' . $dataStructureArray
                );
            }
        }

        return $dataStructureArray;
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
}
