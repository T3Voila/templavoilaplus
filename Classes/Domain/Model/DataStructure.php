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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Class to provide unique access to datastructure
 */
class DataStructure extends AbstractDataStructure
{
    /**
     * @var array
     */
    protected $row;

    /**
     * @var string
     */
    protected $sortbyField;

    /**
     * @param integer $uid
     */
    public function __construct($uid)
    {
        // getting the DS for the DB and make sure the workspace-overlay is performed (done internally)
        if (TYPO3_MODE === 'FE') {
            $this->row = $GLOBALS['TSFE']->sys_page->checkRecord('tx_templavoilaplus_datastructure', $uid);
        } else {
            $this->row = BackendUtility::getRecordWSOL('tx_templavoilaplus_datastructure', $uid);
        }

        $this->setLabel($this->row['title']);
        $this->setScope($this->row['scope']);
        // path relative to typo3 maindir
        $this->setIcon('uploads/tx_templavoilaplus/' . $this->row['previewicon']);
        $this->setSortbyField($GLOBALS['TCA']['tx_templavoilaplus_datastructure']['ctrl']['sortby']);
    }

    /**
     * @return string;
     */
    public function getStoragePids()
    {
        return $this->row['pid'];
    }

    /**
     * @return string - numeric string
     */
    public function getKey()
    {
        return $this->row['uid'];
    }

    /**
     * Provides the datastructure configuration as XML
     *
     * @return string
     */
    public function getDataprotXML()
    {
        return $this->row['dataprot'];
    }

    /**
     * Determine whether the current user has permission to create elements based on this
     * datastructure or not
     *
     * @param array $parentRow
     * @param array $removeItems
     * @param bool $showAdminAll If user is admin and this is true, then it is always permitted
     *
     * @return boolean
     */
    public function isPermittedForUser($parentRow = array(), $removeItems = array(), $showAdminAll = true)
    {
        if ($showAdminAll && TemplaVoilaUtility::getBackendUser()->isAdmin()) {
            return true;
        } else {
            if (in_array($this->getKey(), $removeItems)) {
                return false;
            }
        }
        $permission = true;
        $denyItems = TemplaVoilaUtility::getDenyListForUser();

        $currentSetting = $parentRow['tx_templavoilaplus_ds'];
        if ($this->getScope() == static::SCOPE_PAGE) {
            $inheritSetting = $parentRow['tx_templavoilaplus_next_ds'];
        } else {
            $inheritSetting = -1;
        }

        $key = 'tx_templavoilaplus_datastructure:' . $this->getKey();
        if (in_array($key, $denyItems) &&
            $key != $currentSetting &&
            $key != $inheritSetting
        ) {
            $permission = false;
        }

        return $permission;
    }

    /**
     * Retrieve the filereference of the template
     *
     * @return string
     */
    public function getTstamp()
    {
        return $this->row['tstamp'];
    }

    /**
     * Retrieve the filereference of the template
     *
     * @return string
     */
    public function getCrdate()
    {
        return $this->row['crdate'];
    }

    /**
     * Retrieve the filereference of the template
     *
     * @return string
     */
    public function getCruser()
    {
        return $this->row['cruser_id'];
    }

    /**
     * @param void
     *
     * @return mixed
     */
    public function getBeLayout()
    {
        $beLayout = false;
        if ($this->row['belayout']) {
            $beLayout = GeneralUtility::getURL(GeneralUtility::getFileAbsFileName($this->row['belayout']));
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
}
