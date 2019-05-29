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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Class to provide unique access to static datastructure
 *
 * @author Tolleiv Nietsch <tolleiv.nietsch@typo3.org>
 */
class StaticDataStructure extends AbstractDataStructure
{
    /**
     * @var string
     */
    protected $filename;

    /**
     * @var string
     */
    protected $xmlContent = null;

    /**
     * @throws \InvalidArgumentException
     *
     * @param integer $key
     */
    public function __construct($key)
    {
        $conf = \Ppi\TemplaVoilaPlus\Domain\Repository\DataStructureRepository::getStaticDatastructureConfiguration();

        if (!isset($conf[$key])) {
            throw new \InvalidArgumentException(
                'Argument was supposed to be an existing datastructure',
                1283192644
            );
        }

        $this->filename = $conf[$key]['path'];

        $this->setLabel($conf[$key]['title']);
        $this->setScope($conf[$key]['scope']);

        if (isset($conf[$key]['icon'])) {
            // path relative to typo3 maindir
            $this->setIcon('../' . $conf[$key]['icon']);
        }

        // Read title from XML file and set, if not empty or ROOT
        $dsXml = $this->getDataprotXML();
        $dsStructure = GeneralUtility::xml2array($dsXml);

        if (!empty($dsStructure['meta']['title'])
            && $dsStructure['meta']['title'] !== 'ROOT'
        ) {
            $this->setLabel($dsStructure['meta']['title']);
        }
    }

    /**
     * @return string;
     */
    public function getStoragePids()
    {
        $pids = array();
        // @TODO Move into TsRepository
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_templavoilaplus_tmplobj');
        $toList = $queryBuilder
            ->select('pid')
            ->from('tx_templavoilaplus_tmplobj')
            ->where(
                $queryBuilder->expr()->eq('datastructure', $queryBuilder->createNamedParameter($this->filename))
            )
            ->execute()
            ->fetchAll();

        foreach ($toList as $toRow) {
            $pids[$toRow['pid']] = 1;
        }

        return implode(',', array_keys($pids));
    }

    /**
     * @return string - the filename
     */
    public function getKey()
    {
        return $this->filename;
    }

    /**
     * Provides the datastructure configuration as XML
     *
     * @return string
     */
    public function getDataprotXML()
    {
        if ($this->xmlContent === null) {
            $file = GeneralUtility::getFileAbsFileName($this->filename);
            if (is_readable($file)) {
                $this->xmlContent = file_get_contents($file);
            } else {
                // @todo find out if that happens and whether there's a "useful" reaction for that
            }
        }

        return $this->xmlContent;
    }

    /**
     * Determine whether the current user has permission to create elements based on this
     * datastructure or not - not really useable for static datastructure but relevant for
     * the overall system
     *
     * @param mixed $parentRow
     * @param mixed $removeItems
     * @param bool $showAdminAll If user is admin and this is true, then it is always permitted
     *
     * @return boolean
     */
    public function isPermittedForUser($parentRow = array(), $removeItems = array(), $showAdminAll = true)
    {
        return true;
    }

    /**
     * Enables to determine whether this element is based on a record or on a file
     * Required for view-related tasks (edit-icons)
     *
     * @return boolean
     */
    public function isFilebased()
    {
        return true;
    }

    /**
     * Retrieve the filereference of the template
     *
     * @return integer
     */
    public function getTstamp()
    {
        $file = GeneralUtility::getFileAbsFileName($this->filename);
        if (is_readable($file)) {
            $tstamp = filemtime($file);
        } else {
            $tstamp = 0;
        }

        return $tstamp;
    }

    /**
     * Retrieve the filereference of the template
     *
     * @return integer
     */
    public function getCrdate()
    {
        $file = GeneralUtility::getFileAbsFileName($this->filename);
        if (is_readable($file)) {
            $tstamp = filectime($file);
        } else {
            $tstamp = 0;
        }

        return $tstamp;
    }

    /**
     * Retrieve the filereference of the template
     *
     * @return integer
     */
    public function getCruser()
    {
        return 0;
    }

    /**
     * @param void
     *
     * @return mixed
     */
    public function getBeLayout()
    {
        $beLayout = false;
        $file = substr(GeneralUtility::getFileAbsFileName($this->filename), 0, -3) . 'html';
        if (file_exists($file)) {
            $beLayout = GeneralUtility::getUrl($file);
        }

        return $beLayout;
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
