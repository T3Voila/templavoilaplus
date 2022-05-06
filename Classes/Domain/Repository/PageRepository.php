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

/**
 * Repository for record localizations
 */
class PageRepository extends \TYPO3\CMS\Core\Domain\Repository\PageRepository
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

        return $queryBuilder->execute()->fetchAll();
    }
}
