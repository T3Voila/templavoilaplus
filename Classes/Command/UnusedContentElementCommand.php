<?php
namespace Extension\Templavoila\Command;

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

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Cleaner module: Finding unused content elements on pages.
 * User function called from tx_lowlevel_cleaner_core configured in ext_localconf.php
 * See system extension, lowlevel!
 */
class UnusedContentElementCommand extends \TYPO3\CMS\Lowlevel\CleanerCommand
{

    /**
     * @var array
     */
    protected $resultArray;

    /**
     * @var boolean
     */
    public $checkRefIndex = true;

    /**
     * @var boolean
     */
    public $genTree_traverseDeleted = false;

    /**
     * @var boolean
     */
    public $genTree_traverseVersions = false;

    /**
     * @var array
     */
    protected $excludePageIdList = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Setting up help:
        $this->cli_options[] = array('--echotree level', 'When "level" is set to 1 or higher you will see the page of the page tree outputted as it is traversed. A value of 2 for "level" will show even more information.');
        $this->cli_options[] = array('--pid id', 'Setting start page in page tree. Default is the page tree root, 0 (zero)');
        $this->cli_options[] = array('--depth int', 'Setting traversal depth. 0 (zero) will only analyse start page (see --pid), 1 will traverse one level of subpages etc.');
        $this->cli_options[] = array('--excludePageIdList commalist', 'Specifies page ids to exclude from the processing.');

        $this->cli_help['name'] = 'tx_templavoila_unusedce -- Find unused content elements on pages';
        $this->cli_help['description'] = trim('
Traversing page tree and finding content elements which are not used on pages and seems to have no references to them - hence is probably "lost" and could be deleted.

Automatic Repair:
- Silently deleting the content elements
- Run repair multiple times until no more unused elements remain.
');

        $this->cli_help['examples'] = '';
    }

    /**
     * Main function
     *
     * @return array
     */
    public function main()
    {
        $resultArray = array(
            'message' => $this->cli_help['name'] . chr(10) . chr(10) . $this->cli_help['description'],
            'headers' => array(
                'all_unused' => array('List of all unused content elements', 'All elements means elements which are not used on that specific page. However, they could be referenced from another record. That is indicated by index "1" which is the number of references leading to the element.', 1),
                'deleteMe' => array('List of elements that can be deleted', 'This is all elements which had no references to them and hence should be OK to delete right away.', 2),
            ),
            'all_unused' => array(),
            'deleteMe' => array(),
        );

        $startingPoint = $this->cli_isArg('--pid') ? MathUtility::forceIntegerInRange($this->cli_argValue('--pid'), 0) : 0;
        $depth = $this->cli_isArg('--depth') ? MathUtility::forceIntegerInRange($this->cli_argValue('--depth'), 0) : 1000;
        $this->excludePageIdList = $this->cli_isArg('--excludePageIdList') ? GeneralUtility::intExplode(',', $this->cli_argValue('--excludePageIdList')) : array();

        $this->resultArray = & $resultArray;
        $this->genTree($startingPoint, $depth, (int) $this->cli_argValue('--echotree'), 'main_parseTreeCallBack');

        ksort($resultArray['all_unused']);
        ksort($resultArray['deleteMe']);

        return $resultArray;
    }

    /**
     * Call back function for page tree traversal!
     *
     * @param string $tableName Table name
     * @param integer $uid UID of record in processing
     * @param integer $echoLevel Echo level  (see calling function
     * @param string $versionSwapmode Version swap mode on that level (see calling function
     * @param integer $rootIsVersion Is root version (see calling function
     *
     * @return void
     */
    public function main_parseTreeCallBack($tableName, $uid, $echoLevel, $versionSwapmode, $rootIsVersion)
    {
        if ($tableName === 'pages' && $uid > 0 && !in_array($uid, $this->excludePageIdList)) {
            if (!$versionSwapmode) {
                // Initialize TemplaVoila API class:
                $apiObj = GeneralUtility::makeInstance(\Extension\Templavoila\Service\ApiService::class, 'pages');

                // Fetch the content structure of page:
                $contentTreeData = $apiObj->getContentTree('pages', BackendUtility::getRecordRaw('pages', 'uid=' . (int)$uid));
                if ($contentTreeData['tree']['ds_is_found']) {
                    $usedUids = array_keys($contentTreeData['contentElementUsage']);
                    $usedUids[] = 0;

                    // Look up all content elements that are NOT used on this page...
                    $res = $this->getDatabaseConnection()->exec_SELECTquery(
                        'uid, header',
                        'tt_content',
                        'pid=' . (int)$uid . ' ' .
                        'AND uid NOT IN (' . implode(',', $usedUids) . ') ' .
                        'AND t3ver_state!=1 ' .
                        'AND t3ver_state!=3 ' .
                        BackendUtility::deleteClause('tt_content') .
                        BackendUtility::versioningPlaceholderClause('tt_content'),
                        '',
                        'uid'
                    );

                    // Traverse, for each find references if any and register them.
                    while (false !== ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res))) {
                        // Look up references to elements:
                        $refrows = $this->getDatabaseConnection()->exec_SELECTgetRows(
                            'hash',
                            'sys_refindex',
                            'ref_table=' . $this->getDatabaseConnection()->fullQuoteStr('tt_content', 'sys_refindex') .
                            ' AND ref_uid=' . (int)$row['uid'] .
                            ' AND deleted=0'
                        );

                        // Look up TRANSLATION references FROM this element to another content element:
                        $isATranslationChild = false;
                        $refrows_From = $this->getDatabaseConnection()->exec_SELECTgetRows(
                            'ref_uid',
                            'sys_refindex',
                            'tablename=' . $this->getDatabaseConnection()->fullQuoteStr('tt_content', 'sys_refindex') .
                            ' AND recuid=' . (int)$row['uid'] .
                            ' AND field=' . $this->getDatabaseConnection()->fullQuoteStr('l18n_parent', 'sys_refindex')
                        );
                        // Check if that other record is deleted or not:
                        if ($refrows_From[0] && $refrows_From[0]['ref_uid']) {
                            $isATranslationChild = BackendUtility::getRecord('tt_content', $refrows_From[0]['ref_uid'], 'uid') ? true : false;
                        }

                        // Register elements etc:
                        $this->resultArray['all_unused'][$row['uid']] = array($row['header'], count($refrows));
                        if ($echoLevel > 2) {
                            echo chr(10) . '			[tx_templavoila_unusedce:] tt_content:' . $row['uid'] . ' was not used on page...';
                        }
                        if (!count($refrows)) {
                            if ($isATranslationChild) {
                                if ($echoLevel > 2) {
                                    echo ' but is a translation of a non-deleted records and so do not delete...';
                                }
                            } else {
                                $this->resultArray['deleteMe'][$row['uid']] = $row['uid'];
                                if ($echoLevel > 2) {
                                    echo ' and can be DELETED';
                                }
                            }
                        } else {
                            if ($echoLevel > 2) {
                                echo ' but is referenced to (' . count($refrows) . ') so do not delete...';
                            }
                        }
                    }
                } else {
                    if ($echoLevel > 2) {
                        echo chr(10) . '			[tx_templavoila_unusedce:] Did not check page - did not have a Data Structure set.';
                    }
                }
            } else {
                if ($echoLevel > 2) {
                    echo chr(10) . '			[tx_templavoila_unusedce:] Did not check page - was on offline page.';
                }
            }
        }
    }

    /**
     * Mandatory autofix function
     * Will run auto-fix on the result array. Echos status during processing.
     *
     * @param array $resultArray Result array from main() function
     *
     * @return void
     */
    public function main_autoFix($resultArray)
    {
        foreach ($resultArray['deleteMe'] as $uid) {
            echo 'Deleting "tt_content:' . $uid . '": ';
            if ($bypass = $this->cli_noExecutionCheck('tt_content:' . $uid)) {
                echo $bypass;
            } else {
                // Execute CMD array:
                /** @var DataHandler $tce */
                $tce = GeneralUtility::makeInstance(DataHandler::class);
                $tce->start(array(), array());
                $tce->deleteAction('tt_content', $uid);

                // Return errors if any:
                if (count($tce->errorLog)) {
                    echo '	ERROR from "TCEmain":' . chr(10) . 'TCEmain:' . implode(chr(10) . 'TCEmain:', $tce->errorLog);
                } else {
                    echo 'DONE';
                }
            }
            echo chr(10);
        }
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
