<?php

namespace Tvp\TemplaVoilaPlus\Utility;

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

use Psr\Log\LoggerAwareInterface;
use Tvp\TemplaVoilaPlus\Domain\Repository\Localization\LocalizationRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\PseudoSiteFinder;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class with static functions for templavoila.
 *
 * @author Steffen Kamper  <info@sk-typo3.de>
 */
final class TemplaVoilaUtility
{
    private static $connectionPool;

    public static function getConnectionPool(): ConnectionPool
    {
        if (static::$connectionPool === null) {
            static::$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        }

        return static::$connectionPool;
    }

    /**
     * @param string $tableName
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    public static function getQueryBuilderForTable($tableName): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    public static function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    public static function getLanguageService()
    {
        if (isset($GLOBALS['LANG'])) {
            return $GLOBALS['LANG'];
        }
        if (version_compare(TYPO3_version, '9.3.0', '>=')) {
            return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Localization\LanguageService::class);
        }
        return GeneralUtility::makeInstance(\TYPO3\CMS\Lang\LanguageService::class);
    }

    /**
     * @return string Path to the core language files as changed with TYPO3 8.5.0
     */
    public static function getCoreLangPath()
    {
        if (version_compare(TYPO3_version, '9.3.0', '>=')) {
            return 'core/Resources/Private/Language/';
        }
        return 'lang/Resources/Private/Language/';
    }

    /**
     * Returns an array of available languages (to use for FlexForms)
     *
     * @param int $id If zero, the query will select all sys_language records from root level. If set to another value, the query will select all sys_language records that has a pages_language_overlay record on that page (and is not hidden, unless you are admin user)
     * @param bool $onlyIsoCoded If set, only languages which are paired witch have a ISO code set (via core or static_info_tables)
     * @param bool $setDefault If set, an array entry for a default language is added (uid 0).
     * @param bool $setMulti If set, an array entry for "multiple languages" is added (uid -1)
     * @param array $modSharedTSconfig
     *
     * @return array
     */
    public static function getAvailableLanguages($id = 0, $setDefault = true, $setMulti = false, array $modSharedTSconfig = [])
    {
        if (!$modSharedTSconfig) {
            $pageTsConfig = BackendUtility::getPagesTSconfig($id);
            // @TODO Get rid of this properties key
            $modSharedTSconfig['properties'] = $pageTsConfig['mod.']['SHARED.'];
        }
        $useStaticInfoTables = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('static_info_tables');

        $languages = [];

        if ($setDefault) {
            $languages[0] = [
                'uid' => 0,
                'title' => !empty($modSharedTSconfig['properties']['defaultLanguageLabel'])
                    ? $modSharedTSconfig['properties']['defaultLanguageLabel']
                    : static::getLanguageService()->getLL('defaultLanguage'),
                'ISOcode' => 'DEF',
                'flagIcon' => !empty($modSharedTSconfig['properties']['defaultLanguageFlag'])
                    ? 'flags-' . $modSharedTSconfig['properties']['defaultLanguageFlag']
                    : 'mimetypes-x-sys_language',
            ];
        }

        if ($setMulti) {
            $languages[-1] = [
                'uid' => -1,
                'title' => static::getLanguageService()->getLL('multipleLanguages'),
                'ISOcode' => 'DEF',
                'flagIcon' => 'flags-multiple',
            ];
        }

        $languageRecords = [];
        $useSysLanguageRecords = false;
        if (version_compare(TYPO3_version, '9.0.0', '>=')) {
            $languageRecords = self::getUseableLanguages($id);

            if (empty($languageRecords)) {
                // Since 9.0 we do not have pages_language_overlay anymore
                $useSysLanguageRecords = true;
                $languageRecords = static::getSysLanguageRows9($id);
            }
        } else {
            $useSysLanguageRecords = true;
            $languageRecords = static::getSysLanguageRows8($id);
        }

        if ($useSysLanguageRecords) {
            foreach ($languageRecords as $languageRecord) {
                $languages[$languageRecord['uid']] = $languageRecord;
                $languages[$languageRecord['uid']]['ISOcode'] = strtoupper($languageRecord['language_isocode']);

                // @TODO This should probably resolve language_isocode too and throw a deprecation if not filled
                if ($languageRecord['static_lang_isocode'] && $useStaticInfoTables) {
                    $staticLangRow = BackendUtility::getRecord('static_languages', $languageRecord['static_lang_isocode'], 'lg_iso_2');
                    if ($staticLangRow['lg_iso_2']) {
                        $languages[$languageRecord['uid']]['ISOcode'] = $staticLangRow['lg_iso_2'];
                    }
                }
                if ($languageRecord['flag'] !== '') {
                    $languages[$languageRecord['uid']]['flagIcon'] = 'flags-' . $languageRecord['flag'];
                }

                if (!isset($languages[$languageRecord['uid']]['ISOcode'])) {
                    unset($languages[$languageRecord['uid']]);
                }
            }
        } else {
            if (isset($languages[0])) {
                // If default Language already is set
                // Take title and flag from default language
                $languages[0]['title'] = $languageRecords[0]['title'];
                $languages[0]['flagIcon'] = $languageRecords[0]['flagIcon'];
                unset($languageRecords[0]);
            }
            $languages += $languageRecords;
        }

        if (isset($modSharedTSconfig['properties']['disableLanguages'])) {
            $disableLanguages = GeneralUtility::trimExplode(',', $modSharedTSconfig['properties']['disableLanguages'], 1);
            foreach ($disableLanguages as $disableLanguage) {
                // $disableLanguage is the uid of a sys_language
                unset($languages[$disableLanguage]);
            }
        }

        return $languages;
    }

    /**
     * This presents a generic interface to get the localization config for a specific page
     * Basically it should cover fallbacktype and fallbacks (site handling era).
     *
     * It sums up info about system, site and page as e.g. the page could marked as no-fallback
     * although the site is configured differently. In order to make this as flexible as possible
     * it just configures the typical defaults and enriches them step by step with actual LTS-specific
     * info
     *
     * @param int $pageId
     * @param int $languageId
     *
     * @return LanguageAspect|null
     * @see LanguageAspectFactory::createFromSiteLanguage()
     *
     */
    public static function fetchLanguageAspect(int $pageId, int $languageId = 0)
    {
        // pages.l18n_cfg consideration, it uses a bitmask field
        $row = BackendUtility::getRecordWSOL('pages', $pageId, 'l18n_cfg');
        $PAGES_L18NCFG_HIDEDEFAULT = 0b0001; /* 1 */
        $PAGES_L18NCFG_HIDEIFNOTTRANSLATED = 0b0010; /* 2 */
        $fallbackTypeOverride = null;
        // There is a global conf var hidePagesIfNotTranslatedByDefault which changes the behaviour
        // of HIDEIFNOTTRANSLATED, we only need the override if that is not set (because else it means mixed/default)
        if (($row['l18n_cfg'] & $PAGES_L18NCFG_HIDEIFNOTTRANSLATED) && (!$GLOBALS['TYPO3_CONF_VARS']['FE']['hidePagesIfNotTranslatedByDefault'])) {
            $fallbackTypeOverride = 'strict';
        }
        if ($row['l18n_cfg'] & $PAGES_L18NCFG_HIDEDEFAULT) {
            $fallbackTypeOverride = 'free';
        }

        // site handling exists (>=9LTS)
        if (class_exists(SiteFinder::class)) {
            try {
                $currentSite = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId);
                if ($currentSite) {
                    $currentSiteLanguage = $currentSite->getLanguageById($languageId);

                    $languageId = $currentSiteLanguage->getLanguageId();
                    $fallbackType = $fallbackTypeOverride ?? $currentSiteLanguage->getFallbackType();
                    $fallbackOrder = $currentSiteLanguage->getFallbackLanguageIds();
                    $fallbackOrder[] = 'pageNotFound';
                    switch ($fallbackType) {
                        // Fall back to other language, if the page does not exist in the requested language
                        // But always fetch only records of this specific (available) language
                        case 'free':
                            $overlayType = LanguageAspect::OVERLAYS_OFF;
                            break;

                        // Fall back to other language, if the page does not exist in the requested language
                        // Do overlays, and keep the ones that are not translated
                        case 'fallback':
                            $overlayType = LanguageAspect::OVERLAYS_MIXED;
                            break;

                        // Same as "fallback" but remove the records that are not translated
                        case 'strict':
                            $overlayType = LanguageAspect::OVERLAYS_ON_WITH_FLOATING;
                            break;

                        // Ignore, fallback to default language
                        default:
                            $fallbackOrder = [0];
                            $overlayType = LanguageAspect::OVERLAYS_OFF;
                    }

                    return GeneralUtility::makeInstance(LanguageAspect::class, $languageId, $languageId, $overlayType, $fallbackOrder);
                }
            } catch (SiteNotFoundException | \InvalidArgumentException $e) {
                // if site not found, then there is no language config, e.g. pid=0 or root sysfolders for stuff
                // languageId should always be valid argument, except for '-1'
                return null;
            }
        }

        // before site handling exists (<=8LTS)
        // sane defaults: current language should be shown in mixed mode (default with translation overlay)
        // this is the recommended configuration
        // s. https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Context/Index.html#language-aspect
        $languageAspect['id'] = $languageId;
        $languageAspect['contentId'] = $languageId;
        $languageAspect['fallbackChain'] = 0;
        $languageAspect['overlayType'] = 'mixed';

        // @TODO sys_language_mode strict and free support.
        // it would be necessary to get the $legacyLanguageConfig for  a specific languageID - I think that is in fact
        // impossible. Fallback to langId=0 could work or we keep it the current way, because we actually might not need
        // it anyways
        //
        // if ($legacyLanguageConfig['sys_language_mode'] == 'strict') {
        //     $languageAspect['overlayType'] = 'includeFloating';
        // }
        // if ($legacyLanguageConfig['sys_language_mode'] == 'strict' && $fallbackTypeOverride == 'strict') {
        //     $languageAspect['fallbackChain'] = [];
        // }
        // if ($legacyLanguageConfig['sys_language_mode'] == 'free') {
        //     $languageAspect['overlayType'] = 'off;
        // }

        return ($languageAspect);
    }

    /*
     * Returns an array of existing page translations (including hidden, including default=0)
     *
     * @param int $id If zero, the query will select all sys_language records from root level. If set to another value, the query will select all sys_language records that has a pages_language_overlay record on that page (and is not hidden, unless you are admin user)
     * @param bool $onlyIsoCoded If set, only languages which are paired witch have a ISO code set (via core or static_info_tables)
     * @param bool $setDefault If set, an array entry for a default language is added (uid 0).
     * @param bool $setMulti If set, an array entry for "multiple languages" is added (uid -1)
     * @param array $modSharedTSconfig
     *
     * @return array
     */
    public static function getExistingPageLanguages($id = 0, $setDefault = true, $setMulti = false, array $modSharedTSconfig = [])
    {
        $languages = static::getAvailableLanguages($id, $setDefault, $setMulti, $modSharedTSconfig);
        $resultingLanguages = [];
        $resultingLanguages[0] = $languages[0]; // stick to default lang here; TODO: free mode without 0 translation
        if ($id > 0) {
            $existingLanguages = LocalizationRepository::fetchRecordLocalizations('pages', $id);
            foreach ($existingLanguages as $existingLanguage) {
                $existingLanguageId = $existingLanguage[$GLOBALS['TCA']['pages']['ctrl']['languageField']];
                if (isset($languages[$existingLanguageId])) {
                    $resultingLanguages[$existingLanguageId] = $languages[$existingLanguageId];
                }
            }
        }
        return $resultingLanguages;
    }

    public static function getUseableLanguages(int $pageId = 0)
    {
        $foundLanguages = [];
        $useableLanguages = [];

        /** @var SiteFinder */
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);

        $backendUserAuth = self::getBackendUserAuthentication();

        if ($pageId === 0) {
            // Used for e.g. filelist, where there is no site selected
            // This also means that there is no "-1" (All Languages) selectable.
            $sites = $siteFinder->getAllSites();
            foreach ($sites as $site) {
                if ($backendUserAuth) {
                    $foundLanguages += $site->getAvailableLanguages($backendUserAuth);
                } else {
                    $foundLanguages += $site->getAllLanguages();
                }
            }
        } else {
            try {
                $site = $siteFinder->getSiteByPageId((int)$pageId);
            } catch (\TYPO3\CMS\Core\Exception\SiteNotFoundException $e) {
                if (class_exists(PseudoSiteFinder::class)) {
                    $pseudoSiteFinder = GeneralUtility::makeInstance(PseudoSiteFinder::class);
                    $site = $pseudoSiteFinder->getSiteByPageId($pageId);
                } else {
                    $site = GeneralUtility::makeInstance(NullSite::class);
                }
            }
            if ($backendUserAuth) {
                $foundLanguages = $site->getAvailableLanguages($backendUserAuth);
            } else {
                $foundLanguages = $site->getAllLanguages();
            }
        }

        foreach ($foundLanguages as $language) {
            $languageId = $language->getLanguageId();
            $useableLanguages[$languageId] = [
                'uid' => $languageId,
                'title' => $language->getTitle(),
                'ISOcode' => $language->getTwoLetterIsoCode(),
                'flagIcon' => $language->getFlagIdentifier(),
            ];
        }

        ksort($useableLanguages);
        return $useableLanguages;
    }

    // Partielly taken from \TYPO3\CMS\Backend\Controller\EditDocumentController::getLanguages
    private static function getSysLanguageRows8($id = 0)
    {
        // add the additional languages from database records
        $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getQueryBuilderForTable('sys_language');
        $queryBuilder = $queryBuilder
            ->select('s.*')
            ->from('sys_language', 's')
            ->orderBy('s.sorting');

        if ($id) {
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

            if (!static::getBackendUser()->isAdmin()) {
                $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(HiddenRestriction::class));
            }

            // Add join with pages_language_overlay table to only show active languages
            $queryBuilder->from('pages_language_overlay', 'o')
                ->where(
                    $queryBuilder->expr()->eq('o.sys_language_uid', $queryBuilder->quoteIdentifier('s.uid')),
                    $queryBuilder->expr()->eq('o.pid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
                );
        }

        $languageRecords = $queryBuilder
            ->execute()
            ->fetchAll();

        return $languageRecords;
    }

    // Partielly taken from \TYPO3\CMS\Backend\Controller\EditDocumentController::getLanguages
    private static function getSysLanguageRows9($id = 0)
    {
        // add the additional languages from database records
        $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getQueryBuilderForTable('sys_language');
        $queryBuilder = $queryBuilder
            ->select('s.*')
            ->from('sys_language', 's')
            ->orderBy('s.sorting');

        if ($id) {
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

            if (!static::getBackendUser()->isAdmin()) {
                $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(HiddenRestriction::class));
            }

            // Add join with pages translations to only show active languages
            $queryBuilder->from('pages', 'o')
                ->where(
                    $queryBuilder->expr()->eq('o.' . $GLOBALS['TCA']['pages']['ctrl']['languageField'], $queryBuilder->quoteIdentifier('s.uid')),
                    $queryBuilder->expr()->eq('o.' . $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'], $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
                );
        }

        $languageRecords = $queryBuilder
            ->execute()
            ->fetchAll();

        return $languageRecords;
    }

    /**
     * @return array
     */
    public static function getDenyListForUser()
    {
        $denyItems = [];
        foreach (static::getBackendUser()->userGroups as $group) {
            $groupDenyItems = GeneralUtility::trimExplode(',', $group['tx_templavoilaplus_access'], true);
            $denyItems = array_merge($denyItems, $groupDenyItems);
        }

        return $denyItems;
    }

    /**
     * Get a list of referencing elements other than the given pid.
     *
     * @param array $element array with tablename and uid for a element
     * @param int $pid the suppoed source-pid
     * @param int $recursion recursion limiter
     * @param array &$references array containing a list of the actual references
     *
     * @return bool true if there are other references for this element
     */
    public static function getElementForeignReferences($element, $pid, $recursion = 99, &$references = null)
    {
        if (!$recursion) {
            return false;
        }
        if (!is_array($references)) {
            $references = [];
        }

        $selectArray = [
            'ref_table' => $element['table'],
            'ref_uid' => (int)$element['uid'],
        ];

        // No deleted field anymore in sys_refindex https://forge.typo3.org/issues/93029
        if (version_compare(TYPO3_version, '11.0.0', '<=')) {
            $selectArray['deleted'] = 0;
        }

        $refrows = self::getConnectionPool()
            ->getConnectionForTable('sys_refindex')
            ->select(
                ['*'],
                'sys_refindex',
                $selectArray
            )
            ->fetchAll();

        if (is_array($refrows)) {
            foreach ($refrows as $ref) {
                if (strcmp($ref['tablename'], 'pages') === 0) {
                    $references[$ref['tablename']][$ref['recuid']] = true;
                } else {
                    if (!isset($references[$ref['tablename']][$ref['recuid']])) {
                        // initialize with false to avoid recursion without affecting inner OR combinations
                        $references[$ref['tablename']][$ref['recuid']] = false;
                        $references[$ref['tablename']][$ref['recuid']]
                            = self::hasElementForeignReferences(
                                [
                                    'table' => $ref['tablename'],
                                    'uid' => $ref['recuid'],
                                ],
                                $pid,
                                $recursion - 1,
                                $references
                            );
                    }
                }
            }
        }

        unset($references['pages'][$pid]);

        return $references;
    }

    /**
     * Checks if a element is referenced from other pages / elements on other pages than his own.
     *
     * @param array $element array with tablename and uid for a element
     * @param int $pid the suppoed source-pid
     * @param int $recursion recursion limiter
     * @param array &$references array containing a list of the actual references
     *
     * @return bool true if there are other references for this element
     */
    public static function hasElementForeignReferences($element, $pid, $recursion = 99, &$references = null)
    {
        $references = self::getElementForeignReferences($element, $pid, $recursion, $references);
        $foreignRefs = false;
        if (is_array($references)) {
            unset($references['pages'][$pid]);
            $foreignRefs = isset($references['pages']) && count($references['pages'])
                || isset($references['pages_language_overlay']) && count($references['pages_language_overlay']);
        }

        return $foreignRefs;
    }

    public static function getFlexFormDS($conf, $row, $table, $fieldName = '')
    {
        $flexFormTools = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class);
        $dataStructureIdentifier = $flexFormTools->getDataStructureIdentifier(
            [ 'config' => $conf ],
            $table,
            $fieldName,
            $row
        );
        return $flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);
    }

    /**
     * @return BackendUserAuthentication|null
     */
    protected static function getBackendUserAuthentication(): ?\TYPO3\CMS\Core\Authentication\BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
