<?php
namespace Tvp\TemplaVoilaPlus\Command;

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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Backend\Utility\BackendUtility;

use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Cleaner module: Finding unused content elements on pages.
 * User function called from tx_lowlevel_cleaner_core configured in ext_localconf.php
 * See system extension, lowlevel!
 */
class UnusedContentElementCommand8 extends Command
{
    /**
     * @var array
     */
    protected $resultArray;

    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this
            ->setDescription('Find unused content elements on pages.')
            ->setHelp('Traversing page tree and finding content elements which are not used on pages and seems to have no references to them - hence is probably "lost" and could be deleted.

Automatic Repair:
- Silently deleting the content elements
- Run repair multiple times until no more unused elements remain.')
            ->addOption(
                'pid',
                'p',
                InputOption::VALUE_REQUIRED,
                'Setting start page in page tree. Default is the page tree root, 0 (zero)'
            )
            ->addOption(
                'depth',
                'd',
                InputOption::VALUE_REQUIRED,
                'Setting traversal depth. 0 (zero) will only analyze start page (see --pid), 1 will traverse one level of subpages etc.'
            )
            ->addOption(
                'excludePageIdList',
                null,
                InputOption::VALUE_REQUIRED,
                'Specifies page ids to exclude from the processing.'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, the records will not be updated, but only show the output which records would have been updated.'
            );
    }

    /**
     * Executes the command to find and update records with FlexForms where the values do not match the datastructures
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();

        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());


        $startingPoint = 0;
        if ($input->hasOption('pid') && MathUtility::canBeInterpretedAsInteger($input->getOption('pid'))) {
            $startingPoint = MathUtility::forceIntegerInRange((int)$input->getOption('pid'), 0);
        }

        $depth = 1000;
        if ($input->hasOption('depth') && MathUtility::canBeInterpretedAsInteger($input->getOption('depth'))) {
            $depth = MathUtility::forceIntegerInRange((int)$input->getOption('depth'), 0);
        }

        $excludePageIdList = [];
        if ($input->hasOption('excludePageIdList') && MathUtility::canBeInterpretedAsInteger($input->getOption('excludePageIdList'))) {
            $excludePageIdList = GeneralUtility::trimExplode(',', $input->getOption('excludePageIdList'), true);
        }

        // Type unsafe comparison and explicit boolean setting on purpose
        $dryRun = $input->hasOption('dry-run') && $input->getOption('dry-run') != false ? true : false;

        // Initialize TemplaVoila API class:
        $this->apiService = GeneralUtility::makeInstance(\Tvp\TemplaVoilaPlus\Service\ApiService::class, 'pages');

        $unusedCes = $this->findAllUnusedCes($io, $startingPoint, $depth, $excludePageIdList);

        if (!$io->isQuiet()) {
            $io->note(
                'Found ' . count($unusedCes['all_unused']) . ' unused CE records at all.' . "\n"
                . 'Found ' . count($unusedCes['deleteMe']) . ' unused CE records which can be deleted.'
            );
        }

        if (!empty($unusedCes['deleteMe'])) {
            $io->section('Cleanup process starting now.' . ($dryRun ? ' (Not deleting now, just a dry run)' : ''));

            // Clean up the records now
            $this->cleanUnusedCesRecords($io, $unusedCes['deleteMe'], $dryRun, $io);

            $io->success('All done!');
        } else {
            $io->success('Nothing to do - You\'re all set!');
        }
    }

    /**
     * Recursive traversal of page tree
     *
     * @param SymfonyStyle $io
     * @param int $pageId Page root id
     * @param int $depth Depth
     * @param array $excludePageIdList
     * @param array $unusedCes the list of all previously found flexform fields
     * @return array
     */
    protected function findAllUnusedCes(SymfonyStyle $io, $pageId, $depth, array $excludePageIdList, array $row = [], array $unusedCes = []): array
    {
        if ($pageId > 0 && count($row) && !in_array($pageId, $excludePageIdList)) {
            $unusedCes = $this->findAllUnusedCesOnPage($io, $pageId, $row, $unusedCes);
        }

        // Find subpages
        if ($depth > 0) {
            $depth--;
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages');

            $queryBuilder->getRestrictions()
                ->removeAll();

            $result = $queryBuilder
                ->select('*')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT))
                )
                ->orderBy('sorting')
                ->execute();

            while ($row = $result->fetch()) {
                $unusedCes = $this->findAllUnusedCes($io, $row['uid'], $depth, $excludePageIdList, $row, $unusedCes);
            }
        }
        // We do not check versions intentionally
        return $unusedCes;
    }

    /**
     * Call back function for page tree traversal!
     *
     * @param SymfonyStyle $io
     * @param integer $uid UID of record in processing
     * @param array $unusedCes the unused Ces
     * @return array the updated list of dirty FlexForm fields
     */
    public function findAllUnusedCesOnPage(SymfonyStyle $io, $uid, array $row, array $unusedCes = []): array
    {
        // Fetch the content structure of page:
        $contentTreeData = $this->apiService->getContentTree('pages', $row);
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
            $unusedCes['all_unused'][$row['uid']] = array($row['header'], count($refrows));
            if ($io->isVerbose()) {
                $io->write('tt_content:' . $row['uid'] . ' was not used on page...');
            }

            if (!count($refrows)) {
                if ($isATranslationChild) {
                    if ($io->isVerbose()) {
                        $io->writeln(' but is a translation of a non-deleted records and so do not delete...');
                    }
                } else {
                    $unusedCes['deleteMe'][$row['uid']] = $row['uid'];
                    if ($io->isVerbose()) {
                        $io->writeln(' and can be DELETED');
                    }
                }
            } else {
                if ($io->isVerbose()) {
                    $io->writeln(' but is referenced to (' . count($refrows) . ') so do not delete...');
                }
            }
        }

        return $unusedCes;
    }


    /**
     * Will run auto-fix on the result array. Echos status during processing.
     *
     * @param SymfonyStyle $io
     * @param array $records Result array from main() function
     * @param bool $dryRun
     */
    protected function cleanUnusedCesRecords(SymfonyStyle $io, array $records, bool $dryRun)
    {
        foreach ($records as $uid) {
            $io->write('<comment>Deleting "tt_content:' . $uid . '": </comment>');
            if (!$dryRun) {
                // Execute CMD array:
                /** @var DataHandler $tce */
                $tce = GeneralUtility::makeInstance(DataHandler::class);
                $tce->start(array(), array());
                $tce->deleteAction('tt_content', $uid);

                // Return errors if any:
                if (count($tce->errorLog)) {
                    $io->writeln('<error>  ERROR from "TCEmain":' . chr(10) . 'TCEmain:' . implode(chr(10) . 'TCEmain:', $tce->errorLog) . '</error>');
                } else {
                    $io->writeln('<info>DONE</info>');
                }
            } else {
                $io->writeln('<info>DRY RUN</info>');
            }
        }
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return TemplaVoilaUtility::getDatabaseConnection();
    }
}
