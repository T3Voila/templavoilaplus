<?php
namespace Ppi\TemplaVoilaPlus\Utility;

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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;

/**
 * Class with static functions for templavoila.
 *
 * @author Steffen Kamper  <info@sk-typo3.de>
 */
final class TemplaVoilaUtility
{

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    public static function getDatabaseConnection()
    {
        if (version_compare(TYPO3_version, '9.0.0', '>=')
            && !ExtensionManagementUtility::isLoaded('typo3db_legacy')
        ) {
            throw new \TYPO3\CMS\Core\Exception(
                'Since TYPO3 9.0.0 you need to install the typo3db_legacy extension or TemplaVoilà! Plus 8.0.0 or newer.'
            );
        }
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    public static function getBackendUser()
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
        } else {
            return GeneralUtility::makeInstance(\TYPO3\CMS\Lang\LanguageService::class);
        }
    }

    /**
     * @return string Path to the core language files as changed with TYPO3 8.5.0
     */
    public static function getCoreLangPath()
    {
        if (version_compare(TYPO3_version, '8.5.0', '>=')) {
            return 'lang/Resources/Private/Language/';
        } else {
            return 'lang/';
        }
    }
    /**
     * Returns an array of available languages (to use for FlexForms)
     *
     * @param integer $id If zero, the query will select all sys_language records from root level. If set to another value, the query will select all sys_language records that has a pages_language_overlay record on that page (and is not hidden, unless you are admin user)
     * @param boolean $onlyIsoCoded If set, only languages which are paired witch have a ISO code set (via core or static_info_tables)
     * @param boolean $setDefault If set, an array entry for a default language is added (uid 0).
     * @param boolean $setMulti If set, an array entry for "multiple languages" is added (uid -1)
     * @param array $modSharedTSconfig
     *
     * @return array
     * @access protected
     */
    public static function getAvailableLanguages($id = 0, $setDefault = true, $setMulti = false, array $modSharedTSconfig = [])
    {
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
                    : 'mimetypes-x-sys_language'
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
        if (version_compare(TYPO3_version, '9.0.0', '>=')) {
            // Since 9.0 we do not have pages_language_overlay anymore
            $languageRecords = static::getSysLanguageRows9($id);
        } elseif (version_compare(TYPO3_version, '8.4.0', '>=')) {
            // Since 8.2 we have Doctrine and since 8.4 sorting is done by an own field and not title
            $languageRecords = static::getSysLanguageRows8($id);
        } else {
            $languageRecords = static::getSysLanguageRows7($id);
        }

        foreach ($languageRecords as $languageRecord) {
            $languages[$languageRecord['uid']] = $languageRecord;
            $languages[$languageRecord['uid']]['ISOcode'] = strtoupper($languageRecord['language_isocode']);

            // @todo: this should probably resolve language_isocode too and throw a deprecation if not filled
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

            if (isset($modSharedTSconfig['properties']['disableLanguages'])) {
                $disableLanguages = GeneralUtility::trimExplode(',', $modSharedTSconfig['properties']['disableLanguages'], 1);
                foreach ($disableLanguages as $disableLanguage) {
                    // $disableLanguage is the uid of a sys_language
                    unset($languages[$disableLanguage]);
                }
            }
        }

        return $languages;
    }

    private static function getSysLanguageRows7($id = 0)
    {
        $excludeHidden = static::getBackendUser()->isAdmin() ? '' : 'sys_language.hidden=0';

        if ($id) {
            $languageRecords = self::getDatabaseConnection()->exec_SELECTgetRows(
                'DISTINCT sys_language.*',
                'pages_language_overlay,sys_language',
                'pages_language_overlay.sys_language_uid=sys_language.uid AND pages_language_overlay.pid=' . (int)$id
                    . ' AND pages_language_overlay.deleted=0' . ($excludeHidden ? ' AND ' . $excludeHidden : ''),
                '',
                'sys_language.title'
            );
        } else {
            $languageRecords = self::getDatabaseConnection()->exec_SELECTgetRows(
                'sys_language.*',
                'sys_language',
                $excludeHidden,
                '',
                'sys_language.title'
            );
        }

        return $languageRecords;
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
        $denyItems = array();
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
     * @param integer $pid the suppoed source-pid
     * @param integer $recursion recursion limiter
     * @param array &$references array containing a list of the actual references
     *
     * @return boolean true if there are other references for this element
     */
    public static function getElementForeignReferences($element, $pid, $recursion = 99, &$references = null)
    {
        if (!$recursion) {
            return false;
        }
        if (!is_array($references)) {
            $references = [];
        }
        $refrows = static::getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            'sys_refindex',
            'ref_table=' . static::getDatabaseConnection()->fullQuoteStr($element['table'], 'sys_refindex') .
            ' AND ref_uid=' . (int)$element['uid'] .
            ' AND deleted=0'
        );

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
                                    'uid' => $ref['recuid']
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
     * @param integer $pid the suppoed source-pid
     * @param integer $recursion recursion limiter
     * @param array &$references array containing a list of the actual references
     *
     * @return boolean true if there are other references for this element
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
        if (version_compare(TYPO3_version, '8.5.0', '>=')) {
            $flexFormTools = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class);
            $dataStructureIdentifier = $flexFormTools->getDataStructureIdentifier(
                [ 'config' => $conf ],
                $table,
                $fieldName,
                $row
            );
            return $flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);
        }

        return BackendUtility::getFlexFormDS($conf, $row, $table, $fieldName);
    }
}
