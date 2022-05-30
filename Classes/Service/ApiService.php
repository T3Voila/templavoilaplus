<?php

namespace Tvp\TemplaVoilaPlus\Service;

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

use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Public API class for proper handling of content elements and other useful TemplaVoila related functions
 */
class ApiService
{
    /**
     * @var string
     */
    protected $rootTable;

    /**
     * @var bool
     */
    public $debug = false;

    /**
     * ->loadWebsiteLanguages() will set this to content of sys_language
     * @var array
     */
    protected $allSystemWebsiteLanguages = [];

    /**
     * @var bool
     */
    protected $modifyReferencesInLiveWS = false;

    /**
     * @var array
     */
    protected $cachedModWebTSconfig = [];

    /**
     * @param string $rootTable Usually the root table is "pages" but another table can be specified (eg. "tt_content")
     */
    public function __construct($rootTable = 'pages')
    {
        $this->rootTable = $rootTable;
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /******************************************************
     *
     * Element manipulation functions (public)
     *
     ******************************************************/

    /**
     * Creates a new content element record and sets the necessary references to connect
     * it to the parent element.
     *
     * @param array $destinationPointer Flexform pointer defining the parent location of the new element. Position refers to the element _after_ which the new element should be inserted. Position == 0 means before the first element.
     * @param array $elementRow Array of field keys and values for the new content element record
     *
     * @return mixed The UID of the newly created record or FALSE if operation was not successful
     */
    public function insertElement($destinationPointer, $elementRow)
    {
        if ($this->debug) {
            GeneralUtility::devLog('API: insertElement()', 'templavoilaplus', 0, ['destinationPointer' => $destinationPointer, 'elementRow' => $elementRow]);
        }

        $destinationPointer = $this->flexform_getValidPointer($destinationPointer);
        if (!$destinationPointer) {
            if ($this->debug) {
                GeneralUtility::devLog('API#insertElement: flexform_getValidPointer() failed', 'templavoilaplus', 0);
            }

            return false;
        }

        $newRecordUid = $this->insertElement_createRecord($destinationPointer, $elementRow);
        if ($newRecordUid === false) {
            if ($this->debug) {
                GeneralUtility::devLog('API#insertElement: insertElement_createRecord() failed', 'templavoilaplus', 0);
            }

            return false;
        }

        $result = $this->insertElement_setElementReferences($destinationPointer, $newRecordUid);
        if ($result === false) {
            if ($this->debug) {
                GeneralUtility::devLog('API#insertElement: insertElement_setElementReferences() failed', 'templavoilaplus', 0);
            }

            return false;
        }

        return $newRecordUid;
    }

    /**
     * Sub function of insertElement: creates a new tt_content record in the database.
     *
     * @param array $destinationPointer flexform pointer to the parent element of the new record
     * @param array $row The record data to insert into the database
     *
     * @return mixed The UID of the newly created record or FALSE if operation was not successful
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function insertElement_createRecord($destinationPointer, $row)
    {
        //phpcs:enable
        if ($this->debug) {
            GeneralUtility::devLog('API: insertElement_createRecord()', 'templavoilaplus', 0, ['destinationPointer' => $destinationPointer, 'row' => $row]);
        }

        $parentRecord = BackendUtility::getRecordWSOL($destinationPointer['table'], $destinationPointer['uid'], 'uid,pid,t3ver_oid,tx_templavoilaplus_flex');

        if ($destinationPointer['position'] > 0) {
            $currentReferencesArr = $this->flexform_getElementReferencesFromXML($parentRecord['tx_templavoilaplus_flex'], $destinationPointer);
            /**
             * @TODO check why $currentReferencesArr isn't used
             */
        }
        $newRecordPid = ($destinationPointer['table'] == 'pages' ? ($parentRecord['pid'] == -1 ? $parentRecord['t3ver_oid'] : $parentRecord['uid']) : $parentRecord['pid']);

        $dataArr = [];
        $dataArr['tt_content']['NEW'] = $row;
        $dataArr['tt_content']['NEW']['pid'] = $newRecordPid;
        unset($dataArr['tt_content']['NEW']['uid']);

        // If the destination is not the default language, try to set the old-style sys_language_uid field accordingly
        if ($destinationPointer['sLang'] != 'lDEF' || $destinationPointer['vLang'] != 'vDEF') {
            $languageKey = $destinationPointer['vLang'] != 'vDEF' ? $destinationPointer['vLang'] : $destinationPointer['sLang'];
            $staticLanguageRows = BackendUtility::getRecordsByField('static_languages', 'lg_iso_2', substr($languageKey, 1));
            if (isset($staticLanguageRows[0]['uid'])) {
                $languageRecord = BackendUtility::getRecordRaw('sys_language', 'static_lang_isocode=' . (int)$staticLanguageRows[0]['uid']);
                if (isset($languageRecord['uid'])) {
                    $dataArr['tt_content']['NEW']['sys_language_uid'] = $languageRecord['uid'];
                }
            }
        }

        // Instantiate TCEmain and create the record:
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        /* @var $tce DataHandler */

        // set default TCA values specific for the page and user
        $TCAdefaultOverride['properties'] = BackendUtility::getPagesTSconfig($newRecordPid)['TCAdefaults'] ?? [];
        if (is_array($TCAdefaultOverride['properties'])) {
            $tce->setDefaultsFromUserTS($TCAdefaultOverride['properties']);
        }

        $tce->stripslashes_values = 0;
        $flagWasSet = $this->getTCEmainRunningFlag();
        $this->setTCEmainRunningFlag(true);

        if ($this->debug) {
            GeneralUtility::devLog('API: insertElement_createRecord()', 'templavoilaplus', 0, ['dataArr' => $dataArr]);
        }

        $tce->start($dataArr, []);
        $tce->process_datamap();
        if ($this->debug && count($tce->errorLog)) {
            GeneralUtility::devLog('API: insertElement_createRecord(): tcemain failed', 'templavoilaplus', 0, ['errorLog' => $tce->errorLog]);
        }
        $newUid = $tce->substNEWwithIDs['NEW'];
        if (!$flagWasSet) {
            $this->setTCEmainRunningFlag(false);
        }

        return (int)$newUid ? (int)$newUid : false;
    }

    /**
     * Sub function of insertElement: sets the references in the parent element for a newly created tt_content
     * record.
     *
     * @param array $destinationPointer Flexform pointer defining the parent element of the new element. Position refers to the element _after_ which the new element should be inserted. Position == 0 means before the first element.
     * @param int $uid UID of the tt_content record
     *
     * @return bool
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function insertElement_setElementReferences($destinationPointer, $uid)
    {
        // phpcs:enable
        if ($this->debug) {
            GeneralUtility::devLog('API: insertElement_setElementReferences()', 'templavoilaplus', 0, ['destinationPointer' => $destinationPointer, 'uid' => $uid]);
        }

        $parentRecord = BackendUtility::getRecordWSOL($destinationPointer['table'], $destinationPointer['uid'], 'uid,pid,tx_templavoilaplus_flex');
        if (!is_array($parentRecord)) {
            return false;
        }

        $currentReferencesArr = $this->flexform_getElementReferencesFromXML($parentRecord['tx_templavoilaplus_flex'], $destinationPointer);
        $newReferencesArr = $this->flexform_insertElementReferenceIntoList($currentReferencesArr, $destinationPointer['position'], $uid);
        $this->flexform_storeElementReferencesListInRecord($newReferencesArr, $destinationPointer);

        return true;
    }

    /**
     * Makes a true copy of an element specified by the source pointer to the location specified by
     * destination pointer. By default, it also copies all sub elements but can be disabled so sub elements
     * are not copied but referenced.
     *
     * @param array $sourcePointer flexform pointer pointing to the element which shall be copied
     * @param array $destinationPointer flexform pointer to the location for the copy
     * @param bool $copySubElements If set to TRUE, also all sub elements will be truly copied
     *
     * @return mixed UID of the created copy, otherwise FALSE
     */
    public function copyElement($sourcePointer, $destinationPointer, $copySubElements = true)
    {
        if ($this->debug) {
            GeneralUtility::devLog('API: copyElement()', 'templavoilaplus', 0, ['sourcePointer' => $sourcePointer, 'destinationPointer' => $destinationPointer, 'copySubElements' => $copySubElements]);
        }

        return $this->process($copySubElements ? 'copyrecursively' : 'copy', $sourcePointer, $destinationPointer);
    }

    /**
     * Makes a true copy of a tt_content element specified by the source pointer to the same location but with
     * the language specified by "languageKey". The new element will refer to the source element as the
     * original version.
     *
     * Note: This function is only used with "translationParadigm = free" (see Page Module TSconfig). In the
     *       "bound" paradigm, TCEmain is called directly because localized elements won't be referenced
     *
     * @param array $sourcePointer flexform pointer pointing to the element which shall be localized
     * @param string $languageKey A two letter ISO language key (eg. 'EN')
     *
     * @return mixed UID of the created copy, otherwise FALSE
     */
    public function localizeElement($sourcePointer, $languageKey)
    {
        if ($this->debug) {
            GeneralUtility::devLog('API: localizeElement()', 'templavoilaplus', 0, ['sourcePointer' => $sourcePointer, 'languageKey' => $languageKey]);
        }

        $sourceElementRecord = $this->flexform_getRecordByPointer($sourcePointer);
        $parentPageRecord = BackendUtility::getRecordWSOL('pages', $sourceElementRecord['pid']);
        $rawPageDataStructureArr = TemplaVoilaUtility::getFlexFormDS($GLOBALS['TCA']['pages']['columns']['tx_templavoilaplus_flex']['config'], $parentPageRecord, 'pages');

        if (!is_array($rawPageDataStructureArr)) {
            return false;
        }
        if ($rawPageDataStructureArr['meta']['langDisable'] == 1) {
            if ($this->debug) {
                GeneralUtility::devLog('API: localizeElement(): Cannot localize element because localization is disabled for the active page datastructure!', 'templavoilaplus', 0);
            }

            return false;
        }

        // Build destination pointer:
        $destinationPointer = $sourcePointer;
        $destinationPointer['sLang'] = $rawPageDataStructureArr['meta']['langChildren'] == 1 ? 'lDEF' : 'l' . $languageKey;
        $destinationPointer['vLang'] = $rawPageDataStructureArr['meta']['langChildren'] == 1 ? 'v' . $languageKey : 'vDEF';
        $destinationPointer['position'] = -1;
        $destinationPointer['_languageKey'] = $languageKey;

        $newElementUid = $this->process('localize', $sourcePointer, $destinationPointer);

        return $newElementUid;
    }

    /**
     * Creates a reference to the element specified by the source pointer at the location specified by
     * destination pointer.
     *
     * @param array $sourcePointer flexform pointer pointing to the reference target
     * @param array $destinationPointer flexform pointer to the location where the reference should be stored
     *
     * @return boolean TRUE if operation was successfully, otherwise false
     */
    public function referenceElement($sourcePointer, $destinationPointer)
    {
        if ($this->debug) {
            GeneralUtility::devLog('API: referenceElement()', 'templavoilaplus', 0, ['sourcePointer' => $sourcePointer, 'destinationPointer' => $destinationPointer]);
        }

        return $this->process('reference', $sourcePointer, $destinationPointer);
    }

    /**
     * Creates a reference to the tt_content record specified by $uid. Basically does the same
     * like referenceElement() but doesn't use a sourcePointer to find the reference target.
     *
     * Use this function in those situations when no flexform pointer exists, for example if
     * you want a reference an element which has not yet been referenced anywhere else.
     *
     * @param int $uid UID of the tt_content element which shall be referenced
     * @param array $destinationPointer flexform pointer to the location where the reference should be stored
     *
     * @return boolean TRUE if operation was successfully, otherwise false
     */
    public function referenceElementByUid($uid, $destinationPointer)
    {
        if ($this->debug) {
            GeneralUtility::devLog('API: referenceElementByUid()', 'templavoilaplus', 0, ['uid' => $uid, 'destinationPointer' => $destinationPointer]);
        }
        $sourcePointer = [
            'table' => 'tt_content',
            'uid' => (int)$uid,
        ];

        return $this->process('referencebyuid', $sourcePointer, $destinationPointer);
    }

    /**
     * Removes a reference to the element (= unlinks) specified by the source pointer.
     *
     * @param array $sourcePointer flexform pointer pointing to the reference which shall be removed
     *
     * @return boolean TRUE if operation was successfully, otherwise false
     */
    public function unlinkElement($sourcePointer)
    {
        if ($this->debug) {
            GeneralUtility::devLog('API: unlinkElement()', 'templavoilaplus', 0, ['sourcePointer' => $sourcePointer]);
        }

        return $this->process('unlink', $sourcePointer);
    }

    /**
     * Removes a reference to the element (= unlinks) specified by the source pointer AND deletes the
     * record.
     *
     * @param array $sourcePointer flexform pointer pointing to the element which shall be deleted
     *
     * @return boolean TRUE if operation was successfully, otherwise false
     */
    public function deleteElement($sourcePointer)
    {
        if ($this->debug) {
            GeneralUtility::devLog('API: deleteElement()', 'templavoilaplus', 0, ['sourcePointer' => $sourcePointer]);
        }

        return $this->process('delete', $sourcePointer);
    }

    /******************************************************
     *
     * Processing functions (protected)
     *
     ******************************************************/

    /**
     * This method does the actually processing for the methods moveElement, copyElement etc.
     *
     * @param string $mode Kind of processing
     * @param array $sourcePointer flexform pointer pointing to the element which will be processed. If "sheet", "sLang" etc. are set, it describes the position by specifying the (future) parent. If not, it describes the element directly with "table" and "uid".
     * @param mixed $destinationPointer flexform pointer to the destination location (if necessary)
     * @param boolean $onlyHandleReferences If set, the record itself won't be moved, deleted etc. but only the references are set correctly. Use this feature if you are sure that the record has been handled before (eg. by TCEmain)
     *
     * @return mixed TRUE or something else (depends on operation) if operation was successful, otherwise FALSE
     */
    public function process($mode, $sourcePointer, $destinationPointer = null, bool $onlyHandleReferences = false)
    {
        $destinationReferencesArr = [];
        $destinationParentRecord = [];

        // Check and get all information about the source position:
        $sourcePointer = $this->flexform_getValidPointer($sourcePointer);
        if (!$sourcePointer) {
            return false;
        }
        $sourceParentRecord = BackendUtility::getRecordWSOL($sourcePointer['table'], $sourcePointer['uid'], 'uid,pid,tx_templavoilaplus_flex');
        if (!is_array($sourceParentRecord)) {
            if ($this->debug) {
                GeneralUtility::devLog('process: Parent record of the element specified by source pointer does not exist!', 2, $sourcePointer);
            }

            return false;
        }

        $sourceReferencesArr = $this->flexform_getElementReferencesFromXML($sourceParentRecord['tx_templavoilaplus_flex'], $sourcePointer);

        // Check and get all information about the destination position:
        if (!is_null($destinationPointer)) {
            $destinationPointer = $this->flexform_getValidPointer($destinationPointer);
            if (!$destinationPointer) {
                return false;
            }
            $destinationParentRecord = BackendUtility::getRecordWSOL($destinationPointer['table'], $destinationPointer['uid'], 'uid,pid,tx_templavoilaplus_flex');
            if (!is_array($destinationParentRecord)) {
                if ($this->debug) {
                    GeneralUtility::devLog('process: Parent record of the element specified by destination pointer does not exist!', 2, $destinationPointer);
                }

                return false;
            }
            if ($destinationParentRecord['pid'] < 0 && $destinationPointer['table'] != 'pages') {
                if ($this->debug) {
                    GeneralUtility::devLog('process: The destination pointer must always point to a live record, not an offline version!', 2, $destinationPointer);
                }

                return false;
            }
            $destinationReferencesArr = $this->flexform_getElementReferencesFromXML($destinationParentRecord['tx_templavoilaplus_flex'], $destinationPointer);
        }

        // Get information about the element to be processed:
        if (isset($sourcePointer['sheet'])) {
            $sourceElementRecord = BackendUtility::getRecordWSOL('tt_content', $sourceReferencesArr[$sourcePointer['position']], '*');
        } else {
            $sourceElementRecord = BackendUtility::getRecordWSOL('tt_content', $sourcePointer['uid'], '*');
        }

        switch ($mode) {
            case 'copy':
                $result = $this->process_copy($sourceElementRecord['uid'], $destinationPointer, $destinationReferencesArr, $destinationParentRecord);
                break;
            case 'copyrecursively':
                $result = $this->process_copyRecursively($sourceElementRecord['uid'], $destinationPointer, $destinationReferencesArr, $destinationParentRecord);
                break;
            case 'localize':
                $result = $this->process_localize($sourceElementRecord['uid'], $destinationPointer, $destinationReferencesArr);
                break;
            case 'reference':
                $result = $this->process_reference($destinationPointer, $destinationReferencesArr, $sourceElementRecord['uid']);
                break;
            case 'referencebyuid':
                $result = $this->process_reference($destinationPointer, $destinationReferencesArr, $sourcePointer['uid']);
                break;
            case 'unlink':
                $result = $this->process_unlink($sourcePointer, $sourceReferencesArr);
                break;
            case 'delete':
                $result = $this->process_delete($sourcePointer, $sourceReferencesArr, $sourceElementRecord['uid']);
                break;
            default:
                $result = false;
                break;
        }

        return $result;
    }

    /**
     * Makes a copy of the specified element and only points to the sub elements with references.
     *
     * @param int $sourceElementUid UID of the element to be copied
     * @param array $destinationPointer flexform pointer to the destination location
     * @param array $destinationReferencesArr Current list of the parent destination's element references
     * @param array $destinationParentRecord Database record of the destination location (either from table 'pages' or 'tt_content')
     *
     * @return mixed The UID of the newly created copy or FALSE if an error occurred.
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function process_copy($sourceElementUid, $destinationPointer, $destinationReferencesArr, $destinationParentRecord)
    {
        // phpcs:enable
        $destinationPID = $destinationPointer['table'] == 'pages' ? $destinationParentRecord['uid'] : $destinationParentRecord['pid'];

        // Initialize TCEmain and create configuration for copying the specified record
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $cmdArray = [];
        $cmdArray['tt_content'][$sourceElementUid]['copy'] = $destinationPID;

        // Execute the copy process and finally insert the reference for the element to the destination:
        $flagWasSet = $this->getTCEmainRunningFlag();
        $this->setTCEmainRunningFlag(true);
        $tce->start([], $cmdArray);
        $tce->process_cmdmap();
        $newElementUid = $tce->copyMappingArray_merged['tt_content'][$sourceElementUid];
        if (!$flagWasSet) {
            $this->setTCEmainRunningFlag(false);
        }

        $newDestinationReferencesArr = $this->flexform_insertElementReferenceIntoList($destinationReferencesArr, $destinationPointer['position'], $newElementUid);
        $this->flexform_storeElementReferencesListInRecord($newDestinationReferencesArr, $destinationPointer);

        return $newElementUid;
    }

    /**
     * Makes a true copy of the specified element and all sub elements and sets the element references of the parent element
     * accordingly.
     *
     * @param int $sourceElementUid UID of the element to be copied
     * @param array $destinationPointer flexform pointer to the destination location
     * @param array $destinationReferencesArr Current list of the parent destination's element references
     * @param array $destinationParentRecord Database record of the destination location (either from table 'pages' or 'tt_content')
     *
     * @return mixed The UID of the newly created copy or FALSE if an error occurred.
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function process_copyRecursively($sourceElementUid, $destinationPointer, $destinationReferencesArr, $destinationParentRecord)
    {
        // phpcs:enable

        // Determine the PID of the new location and get uids of all sub elements of the element to be copied:
        $dummyArr = [];
        $destinationPID = $destinationPointer['table'] == 'pages' ? $destinationParentRecord['uid'] : $destinationParentRecord['pid'];
        $subElementUids = $this->flexform_getListOfSubElementUidsRecursively('tt_content', $sourceElementUid, $dummyArr);

        // Initialize TCEmain and create configuration for copying the specified record (the parent element) and all sub elements:
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $cmdArray = [];
        $cmdArray['tt_content'][$sourceElementUid]['copy'] = $destinationPID;

        foreach ($subElementUids as $subElementUid) {
            $cmdArray['tt_content'][$subElementUid]['copy'] = $destinationPID;
        }

        // Execute the copy process and finally insert the reference for the parent element to the paste destination:
        $flagWasSet = $this->getTCEmainRunningFlag();
        $this->setTCEmainRunningFlag(true);
        $tce->start([], $cmdArray);
        $tce->process_cmdmap();
        if (!$flagWasSet) {
            $this->setTCEmainRunningFlag(false);
        }
        $newElementUid = $tce->copyMappingArray_merged['tt_content'][$sourceElementUid];

        $newDestinationReferencesArr = $this->flexform_insertElementReferenceIntoList($destinationReferencesArr, $destinationPointer['position'], $newElementUid);
        $this->flexform_storeElementReferencesListInRecord($newDestinationReferencesArr, $destinationPointer);

        return $newElementUid;
    }

    /**
     * Localizes the specified element and only points to the sub elements with references.
     *
     * @param int $sourceElementUid UID of the element to be copied
     * @param array $destinationPointer flexform pointer to the destination location
     * @param array $destinationReferencesArr Database record of the destination location (either from table 'pages' or 'tt_content')
     *
     * @return mixed The UID of the newly created copy or FALSE if an error occurred.
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function process_localize($sourceElementUid, $destinationPointer, $destinationReferencesArr)
    {
        // phpcs:enable

        // Determine language record UID of the language we localize to:
        $staticLanguageRows = BackendUtility::getRecordsByField('static_languages', 'lg_iso_2', $destinationPointer['_languageKey']);
        if (is_array($staticLanguageRows) && isset($staticLanguageRows[0]['uid'])) {
            $languageRecords = BackendUtility::getRecordsByField('sys_language', 'static_lang_isocode', $staticLanguageRows[0]['uid']);
        } else {
            $languageRecords = [];
        }

        if (is_array($languageRecords) && isset($languageRecords[0]['uid'])) {
            $destinationLanguageUid = $languageRecords[0]['uid'];
        } else {
            if ($this->debug) {
                GeneralUtility::devLog('API: process_localize(): Cannot localize element because sys_language record can not be found !', 'templavoilaplus', 2);
            }

            return false;
        }

        // Initialize TCEmain and create configuration for localizing the specified record
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $cmdArray = [];
        $cmdArray['tt_content'][$sourceElementUid]['localize'] = $destinationLanguageUid;

        // Execute the copy process and finally insert the reference for the element to the destination:
        $flagWasSet = $this->getTCEmainRunningFlag();
        $this->setTCEmainRunningFlag(true);
        $tce->start([], $cmdArray);
        $tce->process_cmdmap();
        $newElementUid = $tce->copyMappingArray_merged['tt_content'][$sourceElementUid];
        if (!$flagWasSet) {
            $this->setTCEmainRunningFlag(false);
        }

        $newDestinationReferencesArr = $this->flexform_insertElementReferenceIntoList($destinationReferencesArr, $destinationPointer['position'], $newElementUid);
        $this->flexform_storeElementReferencesListInRecord($newDestinationReferencesArr, $destinationPointer);

        return $newElementUid;
    }

    /**
     * Creates a reference which points to the specified element.
     *
     * @param array $destinationPointer flexform pointer to the location where the reference should be stored
     * @param array $destinationReferencesArr Current list of the parent destination's element references
     * @param int $elementUid UID of the tt_content element to be referenced
     *
     * @return bool TRUE if the operation was successful or FALSE if an error occurred.
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function process_reference($destinationPointer, $destinationReferencesArr, $elementUid)
    {
        // phpcs:enable
        $newDestinationReferencesArr = $this->flexform_insertElementReferenceIntoList($destinationReferencesArr, $destinationPointer['position'], $elementUid);
        $this->flexform_storeElementReferencesListInRecord($newDestinationReferencesArr, $destinationPointer);

        return true;
    }

    /**
     * Removes the specified reference
     *
     * @param array $sourcePointer flexform pointer pointing to the reference which shall be removed
     * @param array $sourceReferencesArr Current list of the parent source's element references
     *
     * @return bool TRUE if the operation was successful, otherwise FALSE
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function process_unlink($sourcePointer, $sourceReferencesArr)
    {
        // phpcs:enable
        $newSourceReferencesArr = $this->flexform_removeElementReferenceFromList($sourceReferencesArr, $sourcePointer['position']);
        $this->flexform_storeElementReferencesListInRecord($newSourceReferencesArr, $sourcePointer);

        return true;
    }

    /**
     * Removes the specified reference and truly deletes the record
     *
     * @param array $sourcePointer flexform pointer pointing to the element which will be the target of the reference
     * @param array $sourceReferencesArr Current list of the parent source's element references
     * @param int $elementUid UID of the tt_content element to be deleted
     *
     * @return bool TRUE if the operation was successful, otherwise FALSE
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function process_delete($sourcePointer, $sourceReferencesArr, $elementUid)
    {
        // phpcs:enable
        if (!$this->process_unlink($sourcePointer, $sourceReferencesArr)) {
            return false;
        }

        $cmdArray = [];
        $cmdArray['tt_content'][$elementUid]['delete'] = 1;
        // Element UID should always be that of the online version here...

        // Store:
        $flagWasSet = $this->getTCEmainRunningFlag();
        $this->setTCEmainRunningFlag(true);
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $tce->stripslashes_values = 0;
        $tce->start([], $cmdArray);
        $tce->process_cmdmap();
        if (!$flagWasSet) {
            $this->setTCEmainRunningFlag(false);
        }

        return true;
    }

    /******************************************************
     *
     * Flexform helper functions (public)
     *
     ******************************************************/

    /**
     * Checks if a flexform pointer points to a valid location, ie. the sheets,
     * fields etc. exist in the target data structure. If it is valid, the pointer
     * array will be returned.
     *
     * If 'targetCheckUid' is set, the uid of the record which is referenced by
     * the pointer will be checked against it.
     *
     * This method take workspaces into account (by using workspace flexform data if available) but it does NOT (and should not!) remap UIDs!
     *
     * @param mixed $flexformPointer A flexform pointer referring to the content element. Although an array is preferred, you may also pass a string which will be converted automatically by flexform_getPointerFromString()
     *
     * @return mixed The valid flexform pointer array or FALSE if it was not valid
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function flexform_getValidPointer($flexformPointer)
    {
        // phpcs:enable
        if (is_string($flexformPointer)) {
            $flexformPointer = $this->flexform_getPointerFromString($flexformPointer);
        }

        if (!GeneralUtility::inList($this->rootTable . ',tt_content', $flexformPointer['table'])) {
            if ($this->debug) {
                GeneralUtility::devLog('flexform_getValidPointer: Table "' . $flexformPointer['table'] . '" is not in the list of allowed tables!', 'TemplaVoila API', 2, $this->rootTable . ',tt_content');
            }

            return false;
        }
        $destinationRecord = BackendUtility::getRecordWSOL($flexformPointer['table'], $flexformPointer['uid'], 'uid,pid,tx_templavoilaplus_flex');
        if (!$destinationRecord) {
            if ($this->debug) {
                GeneralUtility::devLog('flexform_getValidPointer: Pointer destination record not found!', 'TemplaVoila API', 2, $flexformPointer);
            }

            return false;
        }

        if ($flexformPointer['position'] < 0) {
            if ($this->debug) {
                GeneralUtility::devLog('flexform_getValidPointer: The position must be positive!', 'TemplaVoila API', 2, $flexformPointer);
            }

            return false;
        }

        $elementReferencesArr = $this->flexform_getElementReferencesFromXML($destinationRecord['tx_templavoilaplus_flex'], $flexformPointer);
        // position should between 0 and count of existing elements
        /** @TODO Differentiate between real existing positions and real positions + 1 for adding? */
        if ($flexformPointer['position'] > count($elementReferencesArr)) {
            if ($this->debug) {
                GeneralUtility::devLog('flexform_getValidPointer: The position in the specified flexform pointer does not exist!', 'TemplaVoila API', 2, $flexformPointer);
            }

            return false;
        }
        if (isset($flexformPointer['targetCheckUid']) && $elementReferencesArr[$flexformPointer['position']] != $flexformPointer['targetCheckUid']) {
            if ($this->debug) {
                GeneralUtility::devLog('flexform_getValidPointer: The target record uid does not match the targetCheckUid!', 'TemplaVoila API', 2, [$flexformPointer, $elementReferencesArr]);
            }

            return false;
        }

        return $flexformPointer;
    }

    /**
     * Converts a string of the format "table:uid:sheet:sLang:field:vLang:position/targettable:targetuid" into a flexform
     * pointer array.
     *
     * NOTE: "targettable" currently must be tt_content
     *
     * @param string $flexformPointerString A string of the format "table:uid:sheet:sLang:#field:vLang:position". The string may additionally contain "/table:uid" which is used to check the target record of the pointer
     *
     * @return array A flexform pointer array which can be used with the functions in tx_templavoilaplus_api
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function flexform_getPointerFromString($flexformPointerString)
    {
        // phpcs:enable
        $tmpArr = explode('/', $flexformPointerString);
        $locationString = $tmpArr[0];
        $targetCheckString = $tmpArr[1];

        $locationArr = explode(':', $locationString);
        $targetCheckArr = explode(':', $targetCheckString);

        if (count($targetCheckArr) == 2) {
            $flexformPointer = [
                'table' => $locationArr[0],
                'uid' => $locationArr[1],
            ];
        } else {
            $flexformPointer = [
                'table' => $locationArr[0],
                'uid' => $locationArr[1],
                'sheet' => $locationArr[2],
                'sLang' => $locationArr[3],
                'field' => substr($locationArr[4], 1), // Remove first "#" char
                'vLang' => $locationArr[5],
                'position' => $locationArr[6],
                'targetCheckUid' => $targetCheckArr[1],
            ];
        }

        return $flexformPointer;
    }

    /**
     * Converts a flexform pointer array to a string of the format "table:uid:sheet:sLang:field:vLang:position/targettable:targetuid"
     *
     * NOTE: "targettable" currently must be tt_content
     *
     * @param array $flexformPointer A valid flexform pointer array
     *
     * @return boolean|string A string of the format "table:uid:sheet:sLang:#field:vLang:position". The string might additionally contain "/table:uid", which is used to check the target record of the pointer. If an error occurs: FALSE
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function flexform_getStringFromPointer($flexformPointer)
    {
        // phpcs:enable
        if (!is_array($flexformPointer)) {
            return false;
        }

        if (isset($flexformPointer['sheet'])) {
            $flexformPointerString
                = $flexformPointer['table']
                . ':' . $flexformPointer['uid']
                . ':' . $flexformPointer['sheet']
                . ':' . $flexformPointer['sLang']
                . ':#' . $flexformPointer['field'] // Add "#" on first
                . ':' . $flexformPointer['vLang']
                . ':' . $flexformPointer['position'];
            if (isset($flexformPointer['targetCheckUid'])) {
                $flexformPointerString .= '/tt_content:' . $flexformPointer['targetCheckUid'];
            }
        } else {
            $flexformPointerString = $flexformPointer['table'] . ':' . $flexformPointer['uid'];
        }

        return $flexformPointerString;
    }

    /**
     * Returns a tt_content record specified by a flexform pointer. The flexform pointer may be an
     * array or a string. As always with flexform pointers, if only "table" and "uid" are set, it
     * specifies the record directly, but if sheet, sLang etc. are set, it specifies the location
     * from the perspective of the parent element.
     *
     * @param mixed $flexformPointer A flexform pointer referring to the content element. Although an array is preferred, you may also pass a string, which will be converted automatically by flexform_getPointerFromString()
     *
     * @return mixed The record row or FALSE if not successful
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function flexform_getRecordByPointer($flexformPointer)
    {
        // phpcs:enable
        if (is_string($flexformPointer)) {
            $flexformPointer = $this->flexform_getPointerFromString($flexformPointer);
        }
        $flexformPointer = $this->flexform_getValidPointer($flexformPointer);
        if (!$flexformPointer) {
            return false;
        }

        if (isset($flexformPointer['sheet'])) {
            $parentRecord = BackendUtility::getRecordWSOL($flexformPointer['table'], $flexformPointer['uid'], 'uid,tx_templavoilaplus_flex');
            if (!$parentRecord) {
                return false;
            }
            $elementReferencesArr = $this->flexform_getElementReferencesFromXML($parentRecord['tx_templavoilaplus_flex'], $flexformPointer);
            // This should work, because both flexFormPointer and tx_templavoilaplus_flex will be based on any workspace overlaid record.
            return BackendUtility::getRecordWSOL('tt_content', $elementReferencesArr[$flexformPointer['position']]);
        }
        return BackendUtility::getRecordWSOL('tt_content', $flexformPointer['uid']);
    }

    /**
     * Returns an array of flexform pointers pointing to all occurrences of a tt_content record with uid $recordUid
     * on the page with uid $pageUid.
     *
     * @param int $elementUid UID of a tt_content record
     * @param int $pageUid UID of the page to search in
     *
     * @return array Array of flexform pointers
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function flexform_getPointersByRecord($elementUid, $pageUid)
    {
        // phpcs:enable
        $dummyArr = [];
        $flexformPointersArr = $this->flexform_getFlexformPointersToSubElementsRecursively('pages', $pageUid, $dummyArr);

        $resultPointersArr = [];
        if (is_array($flexformPointersArr)) {
            foreach ($flexformPointersArr as $flexformPointerArr) {
                if ($flexformPointerArr['targetCheckUid'] == $elementUid) {
                    $resultPointersArr[] = $flexformPointerArr;
                }
            }
        }

        return $resultPointersArr;
    }

    /**
     * Takes FlexForm XML content in and based on the flexform pointer it will find a list of references, parse them
     * and return them as an array of tt_content uids. This function automatically checks if the tt_content records
     * really exist and are not marked as deleted - those who are will be filtered out.
     *
     * @param string $flexformXML XML content of a flexform field
     * @param array $flexformPointer Pointing to a field in the XML structure to get the list of element references from.
     *
     * @return mixed Numerical array tt_content uids or FALSE if an error occurred (eg. flexformXML was no valid XML)
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function flexform_getElementReferencesFromXML($flexformXML, $flexformPointer)
    {
        // phpcs:enable

        // Getting value of the field containing the relations:
        $flexformXMLArr = GeneralUtility::xml2array($flexformXML);
        if (!is_array($flexformXMLArr) && strlen($flexformXML) > 0) {
            if ($this->debug) {
                GeneralUtility::devLog('flexform_getReferencesToElementsFromXML: flexformXML seems to be no valid XML. Parser error message: ' . $flexformXMLArr, 'TemplaVoila API', 2, $flexformXML);
            }

            return false;
        }

        $listOfUIDs = '';
        if (is_array($flexformXMLArr) && is_array($flexformXMLArr['data'])) {
            $sLangPart = $flexformXMLArr['data'][$flexformPointer['sheet']][$flexformPointer['sLang']];
            /** @var \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools */
            $flexFormTools = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class);
            $fieldPart = $flexFormTools->getArrayValueByPath(explode('#', $flexformPointer['field']), $sLangPart);
            $listOfUIDs = $fieldPart[$flexformPointer['vLang']];
        }

        $arrayOfUIDs = GeneralUtility::intExplode(',', $listOfUIDs);

        // Getting the relation uids out and use only tt_content records which are not deleted:
        $dbAnalysis = GeneralUtility::makeInstance(RelationHandler::class);

        /** @TODO tt_content? No, we do not know this here, need to look inside group config */
        $dbAnalysis->start($listOfUIDs, 'tt_content');
        $dbAnalysis->getFromDB();

        $elementReferencesArr = [];
        $counter = 0;
        foreach ($arrayOfUIDs as $uid) {
            if (is_array($dbAnalysis->results['tt_content'][$uid])) {
                $elementReferencesArr[$counter] = $uid;
                $counter++;
            }
        }

        return $elementReferencesArr;
    }

    /**
     * Returns an array of uids of all sub elements of the element specified by $table and $uid.
     *
     * @param string $table Name of the table of the parent element ('pages' or 'tt_content')
     * @param int $uid UID of the parent element
     * @param array $recordUids Array of record UIDs - used internally, don't touch (but pass an empty array)
     * @param integer $recursionDepth Tracks the current level of recursion - used internally, don't touch.
     *
     * @return array Array of record UIDs
     */
    // phpcs:disable Generic.Metrics.NestingLevel
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function flexform_getListOfSubElementUidsRecursively($table, $uid, &$recordUids, $recursionDepth = 0)
    {
        // phpcs:enable
        if (!is_array($recordUids)) {
            $recordUids = [];
        }
        $parentRecord = BackendUtility::getRecordWSOL($table, $uid, 'uid,pid,tx_templavoilaplus_map,tx_templavoilaplus_flex');

        if (!$parentRecord) {
            return $recordUids;
        }

        $flexFieldArr = GeneralUtility::xml2array($parentRecord['tx_templavoilaplus_flex']);
        $expandedDataStructure = $this->ds_getExpandedDataStructure($table, $parentRecord);

        if (is_array($flexFieldArr) && is_array($flexFieldArr['data'])) {
            foreach ($flexFieldArr['data'] as $sheetKey => $languagesArr) {
                if (is_array($languagesArr)) {
                    foreach ($languagesArr as $fieldsArr) {
                        if (is_array($fieldsArr)) {
                            foreach ($fieldsArr as $fieldName => $valuesArr) {
                                if (is_array($valuesArr)) {
                                    foreach ($valuesArr as $value) {
                                        if ($expandedDataStructure[$sheetKey]['ROOT']['el'][$fieldName]['tx_templavoilaplus']['eType'] == 'ce') {
                                            $valueItems = GeneralUtility::intExplode(',', $value);
                                            if (is_array($valueItems)) {
                                                foreach ($valueItems as $subElementUid) {
                                                    if ($subElementUid > 0) {
                                                        $recordUids[] = $subElementUid;
                                                        if ($recursionDepth < 100) {
                                                            $this->flexform_getListOfSubElementUidsRecursively('tt_content', $subElementUid, $recordUids, $recursionDepth + 1);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $recordUids;
    }

    /**
     * Returns an array of flexform pointers to all sub elements of the element specified by $table and $uid.
     *
     * @param string $table Name of the table of the parent element ('pages' or 'tt_content')
     * @param int $uid UID of the parent element
     * @param array $flexformPointers Array of flexform pointers - used internally, don't touch
     * @param integer $recursionDepth Tracks the current level of recursion - used internally, don't touch.
     *
     * @return array Array of flexform pointers
     */
    // phpcs:disable Generic.Metrics.NestingLevel
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function flexform_getFlexformPointersToSubElementsRecursively($table, $uid, &$flexformPointers, $recursionDepth = 0)
    {
        // phpcs:enable
        if (!is_array($flexformPointers)) {
            $flexformPointers = [];
        }
        $parentRecord = BackendUtility::getRecordWSOL($table, $uid, 'uid,pid,tx_templavoilaplus_flex,tx_templavoilaplus_map');

        if ($parentRecord === null) {
            return $flexformPointers;
        }

        $flexFieldArr = GeneralUtility::xml2array($parentRecord['tx_templavoilaplus_flex']);
        $expandedDataStructure = $this->ds_getExpandedDataStructure($table, $parentRecord);

        if (is_array($flexFieldArr) && is_array($flexFieldArr['data'])) {
            foreach ($flexFieldArr['data'] as $sheetKey => $languagesArr) {
                if (is_array($languagesArr)) {
                    foreach ($languagesArr as $languageKey => $fieldsArr) {
                        if (is_array($fieldsArr)) {
                            foreach ($fieldsArr as $fieldName => $valuesArr) {
                                if (is_array($valuesArr)) {
                                    foreach ($valuesArr as $valueName => $value) {
                                        if ($expandedDataStructure[$sheetKey]['ROOT']['el'][$fieldName]['tx_templavoilaplus']['eType'] == 'ce') { /** @TODO What the hell? */
                                            $valueItems = GeneralUtility::intExplode(',', $value);
                                            if (is_array($valueItems)) {
                                                $position = 1;
                                                foreach ($valueItems as $subElementUid) {
                                                    if ($subElementUid > 0) {
                                                        $flexformPointers[] = [
                                                            'table' => $table,
                                                            'uid' => $uid,
                                                            'sheet' => $sheetKey,
                                                            'sLang' => $languageKey,
                                                            'field' => $fieldName, /** @TODO What is with sections/arrays? */
                                                            'vLang' => $valueName,
                                                            'position' => $position,
                                                            'targetCheckUid' => $subElementUid,
                                                        ];
                                                        if ($recursionDepth < 100) {
                                                            $this->flexform_getFlexformPointersToSubElementsRecursively('tt_content', $subElementUid, $flexformPointers, $recursionDepth + 1);
                                                        }
                                                        $position++;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $flexformPointers;
    }

    /******************************************************
     *
     * Flexform helper functions (protected)
     *
     ******************************************************/

    /**
     * Creates a new reference list (as an array) with the $elementUid inserted into the given reference list
     *
     * @param array $currentReferencesArr Array of tt_content uids from a current reference list
     * @param int $position Position where the new reference should be inserted: 0 = before the first element, 1 = after the first, 2 = after the second etc., -1 = insert as last element
     * @param int $elementUid UID of a tt_content element
     *
     * @return array Array with an updated reference list
     * @see flexform_getElementReferencesFromXML(), flexform_removeElementReferenceFromList()
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function flexform_insertElementReferenceIntoList($currentReferencesArr, $position, $elementUid)
    {
        // phpcs:enable
        array_splice($currentReferencesArr, $position, 0, [$elementUid]);

        return $currentReferencesArr;
    }

    /**
     * Removes the element specified by $position from the given list of references and returns
     * the updated list. (the list is passed and return as an array)
     *
     * @param array $currentReferencesArr Array of tt_content uids from a current reference list
     * @param int $position Position of the element reference which should be removed. 1 = first element, 2 = second element etc.
     *
     * @return array Array with an updated reference list
     * @see flexform_getElementReferencesFromXML(), flexform_insertElementReferenceIntoList()
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function flexform_removeElementReferenceFromList($currentReferencesArr, $position)
    {
        // phpcs:enable
        unset($currentReferencesArr[$position]);

        $newReferencesArr = [];
        $counter = 1;
        foreach ($currentReferencesArr as $uid) {
            $newReferencesArr[$counter] = $uid;
            $counter++;
        }

        return $newReferencesArr;
    }

    /**
     * Updates the XML structure with the new list of references to tt_content records.
     *
     * @param array $referencesArr The array of tt_content uids (references list) to store in the record
     * @param array $destinationPointer Flexform pointer to the location where the references list should be stored.
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function flexform_storeElementReferencesListInRecord($referencesArr, $destinationPointer)
    {
        // phpcs:enable
        if ($this->debug) {
            GeneralUtility::devLog('API: flexform_storeElementReferencesListInRecord()', 'templavoilaplus', 0, ['referencesArr' => $referencesArr, 'destinationPointer' => $destinationPointer]);
        }

        $dataArr = [];
        $uid = BackendUtility::wsMapId($destinationPointer['table'], $destinationPointer['uid']);
        $containerHasWorkspaceVersion = false;
        if ($uid != $destinationPointer['uid']) {
            $containerHasWorkspaceVersion = true;
        }

        $fieldPart = [
            $destinationPointer['vLang'] => implode(',', $referencesArr)
        ];
        $sLangPart = [];

        /** @var \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools */
        $flexFormTools = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class);
        $flexFormTools->setArrayValueByPath(explode('#', $destinationPointer['field']), $sLangPart, $fieldPart);
        $dataArr[$destinationPointer['table']][$uid]['tx_templavoilaplus_flex']['data'][$destinationPointer['sheet']][$destinationPointer['sLang']] = $sLangPart;

        $flagWasSet = $this->getTCEmainRunningFlag();
        $this->setTCEmainRunningFlag(true);
        /** @var DataHandler $tce */
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $tce->start($dataArr, []);

        /**
         * Set workspace to 0 because:
         * 1) we want shadow-records to be placed in the Live-Workspace
         * 2) there's no need to create a new version of the parent-record
         * 3) try to avoid issues if the same list is modified in different workspaces at the same time
         */
        if ($this->modifyReferencesInLiveWS && !$containerHasWorkspaceVersion) {
            if ($tce->BE_USER->groupData['allowed_languages']) {
                //force access to default language - since references needs to be stored in default langauage always
                $tce->BE_USER->groupData['allowed_languages'] .= ',0';
            }
            $tce->BE_USER->workspace = 0;
        }
        $tce->process_datamap();
        if (!$flagWasSet) {
            $this->setTCEmainRunningFlag(false);
        }
    }

    /******************************************************
     *
     * Data structure helper functions (public)
     *
     ******************************************************/

    /**
     * Maps old-style tt_content column positions (0 = Normal, 1 = Left etc.) to data structure field names.
     *
     * If the fields are configured by using the "oldStyleColumnNumber" tag, the correct field name will be returned
     * by using this information. If no configuration was found for the given column position, the field name of
     * the "Normal" column will be returned. If the "Normal" column is not defined either, the field name of the
     * first field of eType "ce" will be delivered.
     *
     * If all that fails, this function returns FALSE.
     *
     * @param int $contextPageUid The (current) page uid, used to determine which page datastructure is selected
     * @param int $columnPosition Column number to search a field for
     *
     * @return mixed Either the field name relating to the given column number or FALSE if all fall back methods failed and no suitable field could be found.
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function ds_getFieldNameByColumnPosition($contextPageUid, $columnPosition)
    {
        // phpcs:enable
        $foundFieldName = false;
        $columnsAndFieldNamesArr = [];
        $fieldNameOfFirstCEField = null;

        $pageRow = BackendUtility::getRecordWSOL('pages', $contextPageUid);
        if (!is_array($pageRow)) {
            return false;
        }

        $dataStructureArr = $this->ds_getExpandedDataStructure('pages', $pageRow);

        // Traverse the data structure and search for oldStyleColumnNumber configurations:
        if (is_array($dataStructureArr)) {
            foreach ($dataStructureArr as $sheetDataStructureArr) {
                if (is_array($sheetDataStructureArr['ROOT']['el'])) {
                    foreach ($sheetDataStructureArr['ROOT']['el'] as $fieldName => $fieldConfiguration) {
                        if (is_array($fieldConfiguration)) {
                            if (isset($fieldConfiguration['tx_templavoilaplus']['oldStyleColumnNumber'])) {
                                $columnNumber = $fieldConfiguration['tx_templavoilaplus']['oldStyleColumnNumber'];
                                if (!isset($columnsAndFieldNamesArr[$columnNumber])) {
                                    $columnsAndFieldNamesArr[$columnNumber] = $fieldName;
                                }
                            }
                            if ($fieldConfiguration['tx_templavoilaplus']['eType'] == 'ce' && !isset($fieldNameOfFirstCEField)) {
                                $fieldNameOfFirstCEField = $fieldName;
                            }
                        }
                    }
                }
            }
        }

        // Let's see what we have found:
        if (isset($columnsAndFieldNamesArr[$columnPosition])) {
            $foundFieldName = $columnsAndFieldNamesArr[$columnPosition];
        } elseif (isset($columnsAndFieldNamesArr[0])) {
            $foundFieldName = $columnsAndFieldNamesArr[0];
        } elseif (isset($fieldNameOfFirstCEField)) {
            $foundFieldName = $fieldNameOfFirstCEField;
        }

        return $foundFieldName;
    }

    /**
     * Maps data structure field names to old-style tt_content column positions (0 = Normal, 1 = Left etc.)
     *
     * If the fields are configured by using the "oldStyleColumnNumber" tag, the correct column number will be returned
     * by using this information. If no configuration was found for the given field, 0 will be returned.
     *
     * Reverse function of ds_getFieldNameByColumnPosition()
     *
     * @param int $contextPageUid The (current) page uid, used to determine which page datastructure is selected
     * @param string $fieldName Field name in the data structure we are searching the column number for
     *
     * @return int The column number as used in the "colpos" field in tt_content
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function ds_getColumnPositionByFieldName($contextPageUid, $fieldName)
    {
        // phpcs:enable
        $pageRow = BackendUtility::getRecordWSOL('pages', $contextPageUid);
        if (is_array($pageRow)) {
            $dataStructureArr = $this->ds_getExpandedDataStructure('pages', $pageRow);

            // Traverse the data structure and search for oldStyleColumnNumber configurations:
            if (is_array($dataStructureArr)) {
                foreach ($dataStructureArr as $sheetDataStructureArr) {
                    if (is_array($sheetDataStructureArr['ROOT']['el'])) {
                        if (is_array($sheetDataStructureArr['ROOT']['el'][$fieldName])) {
                            if (isset($sheetDataStructureArr['ROOT']['el'][$fieldName]['tx_templavoilaplus']['oldStyleColumnNumber'])) {
                                return (int)$sheetDataStructureArr['ROOT']['el'][$fieldName]['tx_templavoilaplus']['oldStyleColumnNumber'];
                            }
                        }
                    }
                }
            }
        }

        return 0;
    }

    /**
     * Returns the data structure for a flexform field ("tx_templavoilaplus_flex") from $table (by using $row). The DS will
     * be expanded, ie. you can be sure that it is structured by sheets even if only one sheet exists.
     *
     * @param string $table The table name, usually "pages" or "tt_content"
     * @param array $row The data row (used to get DS if DS is dependent on the data in the record)
     *
     * @return array The data structure, expanded for all sheets inside.
     */
    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function ds_getExpandedDataStructure($table, array $row)
    {
        // phpcs:enable
        $dataStructureArr = [];

        $flexFormTools = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class);
        $dataStructureIdentifier = $flexFormTools->getDataStructureIdentifier(
            $GLOBALS['TCA'][$table]['columns']['tx_templavoilaplus_flex'],
            $table,
            'tx_templavoilaplus_flex',
            $row
        );
        $dataStructureArr = $flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);
        $expandedDataStructureArr = $dataStructureArr['sheets'];

        return $expandedDataStructureArr;
    }

    /******************************************************
     *
     * Miscellaneous functions (protected)
     *
     ******************************************************/

    /**
     * Sets a flag to tell the TemplaVoila TCEmain userfunctions if this API has called a TCEmain
     * function. If this flag is set, the TemplaVoila TCEmain userfunctions will be skipped to
     * avoid infinite loops and other bad effects.
     *
     * @param bool $flag If TRUE, our user functions will be omitted
     */
    public function setTCEmainRunningFlag($flag)
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoilaplus_api']['apiIsRunningTCEmain'] = $flag;
    }

    /**
     * Returns the current flag which tells TemplaVoila TCEmain userfunctions if this API has called a TCEmain
     * function. If this flag is set, the TemplaVoila TCEmain userfunctions will be skipped to
     * avoid infinite loops and other bad effects.
     *
     * @return bool TRUE if flag is set, otherwise FALSE;
     */
    public function getTCEmainRunningFlag()
    {
        return $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoilaplus_api']['apiIsRunningTCEmain'] ? true : false;
    }

    /**
     * Reading all languages in sys_language and setting ->allSystemWebsiteLanguages with this information (with a little more as well)
     */
    public function loadWebsiteLanguages()
    {
        $languages = TemplaVoilaUtility::getAvailableLanguages();

        foreach ($languages as $language) {
            $this->allSystemWebsiteLanguages['all_lKeys'][] = 'l' . $language['ISOcode'];
            $this->allSystemWebsiteLanguages['all_vKeys'][] = 'v' . $language['ISOcode'];
        }
        // @TODO We have a better and static_lang fixed version in the TemplaVoilaUtility
        // We do not need 2 versions of this, get both together in one place
        // Empty while rework
//         $this->allSystemWebsiteLanguages = array();
//         $this->allSystemWebsiteLanguages['all_lKeys'][] = 'lDEF';
//         $this->allSystemWebsiteLanguages['all_vKeys'][] = 'vDEF';
//
//         // Select all website languages:
//         $this->allSystemWebsiteLanguages['rows'] = $this->getDatabaseConnection()->exec_SELECTgetRows(
//             'sys_language.*',
//             'sys_language',
//             '1=1' . BackendUtility::deleteClause('sys_language'),
//             '',
//             'uid',
//             '',
//             'uid'
//         );
//
//         // Traverse and set ISO codes if found:
//         foreach ($this->allSystemWebsiteLanguages['rows'] as $row) {
//             if ($row['static_lang_isocode']) {
//                 $staticLangRow = BackendUtility::getRecord('static_languages', $row['static_lang_isocode'], 'lg_iso_2');
//                 if ($staticLangRow['lg_iso_2']) {
//                     $this->allSystemWebsiteLanguages['rows'][$row['uid']]['_ISOcode'] = $staticLangRow['lg_iso_2'];
//                     $this->allSystemWebsiteLanguages['all_lKeys'][] = 'l' . $staticLangRow['lg_iso_2'];
//                     $this->allSystemWebsiteLanguages['all_vKeys'][] = 'v' . $staticLangRow['lg_iso_2'];
//                 }
//             }
//         }
    }

    /**
     * @param bool $enable
     */
    public function modifyReferencesInLiveWS($enable = true)
    {
        $this->modifyReferencesInLiveWS = $enable;
    }

    /**
     * Retrieves the mod.web_txtemplavoilaplusLayout for a page and stores it in an instance variable.
     * This function is e.g. used to determine, if localization is enabled for FCEs.
     * Since they can be stored on different pages, different modTSconfigs might be needed.
     *
     * @param int $pageId The page id to get the modTSconfig for
     *
     * @return array The fetched modTSconfig for the web module
     */
    public function getModWebTSconfig($pageId)
    {
        if (!isset($this->cachedModWebTSconfig[$pageId])) {
            $modTSconfig = BackendUtility::getModTSconfig($pageId, 'mod.web_txtemplavoilaplusLayout');
            $this->cachedModWebTSconfig[$pageId] = $modTSconfig;
        }

        return $this->cachedModWebTSconfig[$pageId];
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    public function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * NEW PARTS OR ACCEPTED
     */
    /**
     * @return string|null
     */
    public function getMapIdentifierFromRootline(array $rootline)
    {
        $mapBackupIdentifier = null;

        $isFirst = true;
        // Find in rootline upwards
        foreach ($rootline as $key => $pageRecord) {
            if ($isFirst) {
                $isFirst = false;
                continue;
            }

            if ($pageRecord['tx_templavoilaplus_next_map']) {
                // If there is a next-level MAP:
                return $pageRecord['tx_templavoilaplus_next_map'];
            }
            if ($pageRecord['tx_templavoilaplus_map'] && !$mapBackupIdentifier) {
                // Otherwise try the NORMAL MAP as backup
                $mapBackupIdentifier = $pageRecord['tx_templavoilaplus_map'];
            }
        }

        return $mapBackupIdentifier;
    }

    public function getBackendRootline($uid): array
    {
        $rootLine = BackendUtility::BEgetRootLine($uid, '', true);
        foreach ($rootLine as $key => $rootLineRecord) {
            $rootLine[$key] = BackendUtility::getRecordWSOL('pages', $rootLineRecord['uid']);
        }
        return $rootLine;
    }
}
