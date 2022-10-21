<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Domain\Repository;

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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

// phpcs:disable
if (version_compare(TYPO3_version, '10.0.0', '>=')) {
    class CorePageRepository extends \TYPO3\CMS\Core\Domain\Repository\PageRepository
    {
    }
} else {
    class CorePageRepository extends \TYPO3\CMS\Frontend\Page\PageRepository
    {
    }
}
// phpcs:enable

/**
 * Repository for record localizations
 */
class PageRepository extends CorePageRepository
{
    /**
     * Get all pages where the content of a page $pageId is also shown on
     *
     * @param int $pageId
     */
    public function getPagesUsingContentFrom(int $pageId): array
    {
        // Taken from TYPO3 cores PageLayoutController
        // But save for workspace and language overlay?
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder
            ->select('*')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('content_from_pid', $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)));

        $pages = $queryBuilder->execute()->fetchAll();

        if (version_compare(TYPO3_version, '9.0.0', '>=')) {
            if ($pages) {
                foreach ($pages as $key => $page) {
                    // check if the page is a translation of another page
                    // and has languageSynchronization enabled for content_for_pid
                    if (
                        $page[$GLOBALS['TCA']['pages']['ctrl']['languageField']] > 0
                        && $page[$GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField']] > 0
                        && $page['l10n_state']
                        && json_decode($page['l10n_state'], true)['content_from_pid']
                        && json_decode($page['l10n_state'], true)['content_from_pid'] == 'parent'
                    ) {
                        unset($pages[$key]);
                    }
                }
            }
        }

        return $pages;
    }
}
