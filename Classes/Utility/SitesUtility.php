<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Utility;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SitesUtility implements SingletonInterface
{
    private static $configurationCache = [];

    public static function getSitesConfiguration(int $pageId): array
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $site = $siteFinder->getSiteByPageId($pageId);

        return $site->getConfiguration();
    }
}
