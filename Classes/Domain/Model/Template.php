<?php
namespace Extension\Templavoila\Domain\Model;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class to provide unique access to template
 *
 * @author Tolleiv Nietsch <tolleiv.nietsch@typo3.org>
 */
class Template
{
    /**
     * @var array
     */
    protected $row;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $iconFile;

    /**
     * @var string
     */
    protected $fileref;

    /**
     * @var integer
     */
    protected $fileref_mtime;

    /**
     * @var string
     */
    protected $fileref_md5;

    /**
     * @var string
     */
    protected $sortbyField;

    /**
     * @var integer
     */
    protected $parent;

    /**
     * @param integer $uid
     */
    public function __construct($uid)
    {
        $this->row = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $uid);

        $this->setLabel($this->row['title']);
        $this->setDescription($this->row['description']);
        $this->setIcon($this->row['previewicon']);
        $this->setFileref($this->row['fileref']);
        $this->setFilerefMtime($this->row['fileref_mtime']);
        $this->setFilerefMD5($this->row['fileref_md5']);
        $this->setSortbyField($GLOBALS['TCA']['tx_templavoila_tmplobj']['ctrl']['sortby']);
        $this->setParent($this->row['parent']);
    }

    /**
     * Retrieve the label of the template
     *
     * @return string
     */
    public function getLabel()
    {
        return \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL($this->label);
    }

    /**
     *
     * @param string $str
     *
     * @return void
     */
    protected function setLabel($str)
    {
        $this->label = $str;
    }

    /**
     * Retrieve the description of the template
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $str
     *
     * @return void
     */
    protected function setDescription($str)
    {
        $this->description = $str;
    }

    /**
     * Determine the icon and append the path - relative to the TYPO3 main folder
     *
     * @return string
     */
    public function getIcon()
    {
        $icon = '';
        if ($this->iconFile) {
            $icon = PATH_site . 'uploads/tx_templavoila/' . $this->iconFile;
        }

        return $icon;
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
     * Retrieve the filereference of the template
     *
     * @return string
     */
    public function getFileref()
    {
        return $this->fileref;
    }

    /**
     *
     * @param string $str
     *
     * @return void
     */
    protected function setFileref($str)
    {
        $this->fileref = $str;
    }

    /**
     * Retrieve the filereference of the template
     *
     * @return integer
     */
    public function getFilerefMtime()
    {
        return $this->fileref_mtime;
    }

    /**
     * @param integer $str
     *
     * @return void
     */
    protected function setFilerefMtime($str)
    {
        $this->fileref_mtime = $str;
    }

    /**
     * Retrieve the filereference of the template
     *
     * @return string
     */
    public function getFilerefMD5()
    {
        return $this->fileref_md5;
    }

    /**
     * @param string $str
     *
     * @return void
     */
    protected function setFilerefMD5($str)
    {
        $this->fileref_md5 = $str;
    }

    /**
     * @return string - numeric string
     */
    public function getKey()
    {
        return $this->row['uid'];
    }

    /**
     * Retrieve the timestamp of the template
     *
     * @return string
     */
    public function getTstamp()
    {
        return $this->row['tstamp'];
    }

    /**
     * Retrieve the creation date of the template
     *
     * @return string
     */
    public function getCrdate()
    {
        return $this->row['crdate'];
    }

    /**
     * Retrieve the creation user of the template
     *
     * @return string
     */
    public function getCruser()
    {
        return $this->row['cruser_id'];
    }

    /**
     * Retrieve the rendertype of the template
     *
     * @return string
     */
    public function getRendertype()
    {
        return $this->row['rendertype'];
    }

    /**
     * Retrieve the system language of the template
     *
     * @return integer
     */
    public function getSyslang()
    {
        return $this->row['sys_language_uid'];
    }

    /**
     * Check if this is a subtemplate or not
     *
     * @return boolean
     */
    public function hasParentTemplate()
    {
        return $this->row['parent'] != 0;
    }

    /**
     * Determine whether the current user has permission to create elements based on this
     * template or not
     *
     * @param mixed $parentRow
     * @param mixed $removeItems
     *
     * @return boolean
     */
    public function isPermittedForUser($parentRow = array(), $removeItems = array())
    {
        if (\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->isAdmin()) {
            return true;
        } else {
            if (in_array($this->getKey(), $removeItems)) {
                return false;
            }
        }
        $permission = true;
        $denyItems = \Extension\Templavoila\Utility\GeneralUtility::getDenyListForUser();

        if (isset($parentRow['tx_templavoila_to'])) {
            $currentSetting = $parentRow['tx_templavoila_to'];
        } else {
            $currentSetting = $this->getKey();
        }

        if (isset($parentRow['tx_templavoila_next_to']) &&
            $this->getScope() == \Extension\Templavoila\Domain\Model\AbstractDataStructure::SCOPE_PAGE
        ) {
            $inheritSetting = $parentRow['tx_templavoila_next_to'];
        } else {
            $inheritSetting = -1;
        }

        $key = 'tx_templavoila_tmplobj_' . $this->getKey();
        if (in_array($key, $denyItems) &&
            $key != $currentSetting &&
            $key != $inheritSetting
        ) {
            $permission = false;
        }

        return $permission;
    }

    /**
     * @return \Extension\Templavoila\Domain\Model\AbstractDataStructure
     */
    public function getDatastructure()
    {
        $dsRepo = GeneralUtility::makeInstance(\Extension\Templavoila\Domain\Repository\DataStructureRepository::class);

        return $dsRepo->getDatastructureByUidOrFilename($this->row['datastructure']);
    }

    /**
     * @return integer
     */
    protected function getScope()
    {
        return $this->getDatastructure()->getScope();
    }

    /**
     * @param boolean $skipDsDataprot
     *
     * @return string
     */
    public function getLocalDataprotXML($skipDsDataprot = false)
    {
        return GeneralUtility::array2xml_cs($this->getLocalDataprotArray($skipDsDataprot), 'T3DataStructure', array('useCDATA' => 1));
    }

    /**
     * @param boolean $skipDsDataprot
     *
     * @return array
     */
    public function getLocalDataprotArray($skipDsDataprot = false)
    {
        if (!$skipDsDataprot) {
            $dataprot = $this->getDatastructure()->getDataprotArray();
        } else {
            $dataprot = array();
        }
        $toDataprot = GeneralUtility::xml2array($this->row['localprocessing']);

        if (is_array($toDataprot)) {
            ArrayUtility::mergeRecursiveWithOverrule($dataprot, $toDataprot);
        }

        return $dataprot;
    }

    /**
     * Fetch the the field value based on the given XPath expression.
     *
     * @param string $fieldName XPath expression to look up for an value.
     *
     * @throws \UnexpectedValueException
     *
     * @return string
     */
    public function getLocalDataprotValueByXpath($fieldName)
    {
        $doc = new \DOMDocument;
        $doc->preserveWhiteSpace = false;
        $doc->loadXML($this->getLocalDataprotXML());
        $xpath = new \DOMXPath($doc);
        $entries = $xpath->query($fieldName);

        if ($entries->length < 1) {
            throw new \UnexpectedValueException('Nothing found for XPath: "' . $fieldName . '"!');
        }

        return $entries->item(0)->nodeValue;
    }

    /**
     * @return mixed
     */
    public function getBeLayout()
    {
        if ($this->row['belayout']) {
            $beLayout = GeneralUtility::getURL(GeneralUtility::getFileAbsFileName($this->row['belayout']));
        } else {
            $beLayout = $this->getDatastructure()->getBeLayout();
        }

        return $beLayout;
    }

    /**
     * @param string $fieldname
     *
     * @return void
     */
    protected function setSortbyField($fieldname)
    {
        if (isset($this->row[$fieldname])) {
            $this->sortbyField = $fieldname;
        } elseif (!$this->sortbyField) {
            $this->sortbyField = 'sorting';
        }
    }

    /**
     * @return string
     */
    public function getSortingFieldValue()
    {
        if ($this->sortbyField == 'title') {
            $fieldVal = $this->getLabel(); // required to resolve LLL texts
        } elseif ($this->sortbyField == 'sorting') {
            $fieldVal = str_pad($this->row[$this->sortbyField], 15, "0", STR_PAD_LEFT);
        } else {
            $fieldVal = $this->row[$this->sortbyField];
        }

        return $fieldVal;
    }

    /**
     * @param integer $parent
     *
     * @return void
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return integer
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return boolean
     */
    public function hasParent()
    {
        return $this->parent > 0;
    }
}
