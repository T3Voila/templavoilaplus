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
        return $GLOBALS['LANG'];
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
     * @return array
     */
    public static function getDenyListForUser()
    {
        $denyItems = array();
        foreach (static::getBackendUser()->userGroups as $group) {
            $groupDenyItems = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $group['tx_templavoilaplus_access'], true);
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
            $references = array();
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
                        $references[$ref['tablename']][$ref['recuid']] = self::hasElementForeignReferences(array('table' => $ref['tablename'], 'uid' => $ref['recuid']), $pid, $recursion - 1, $references);
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
            $foreignRefs = count($references['pages']) || count($references['pages_language_overlay']);
        }

        return $foreignRefs;
    }
}
