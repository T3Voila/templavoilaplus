<?php

namespace Tvp\TemplaVoilaPlus\Service\UserFunc;

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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class being included by UserAuthGroup using a hook
 */
class Access implements SingletonInterface
{
    /**
     * Checks if user is allowed to modify FCE.
     *
     * @param array $params Parameters
     * @param BackendUserAuthentication $backendUser Parent object
     *
     * @return bool <code>true</code> if change is allowed
     */
    public function recordEditAccessInternals($params, $backendUser)
    {
        if (
            $params['table'] == 'tt_content'
            && is_array($params['idOrRow'] ?? null)
            && $params['idOrRow']['CType'] == 'templavoilaplus_pi1'
        ) {
            $originalBackendUser = $backendUser;
            if (!$backendUser) {
                $backendUser = $this->getBackendUser();
            }
            if ($backendUser->isAdmin()) {
                return true;
            }

            if (!1 == 1) {
                /** @TODO Implementing access restrictions to the mappings? */
                $error = 'access_noAaccess';
            } else {
                return true;
            }
            if ($originalBackendUser) {
                $this->getLanguageService()->init($originalBackendUser->uc['lang']);
                $originalBackendUser->errorMsg = $this->getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_access.xlf:' . $error);
            }

            return false;
        }

        return true;
    }

    /**
     * Checks user's access to given database object
     *
     * @param string $table Table name
     * @param int $uid UID of the record
     * @param BackendUserAuthentication $backendUser BE user object
     *
     * @return bool <code>true</code> if access is allowed
     */
    public function checkObjectAccess($table, $uid, $backendUser)
    {
        if (!$backendUser) {
            $backendUser = $this->getBackendUser();
        }
        if (!$backendUser->isAdmin()) {
            $prefLen = strlen($table) + 1;
            foreach ($backendUser->userGroups as $group) {
                $items = GeneralUtility::trimExplode(',', $group['tx_templavoilaplus_access'], 1);
                foreach ($items as $ref) {
                    if (strstr($ref, $table)) {
                        if ($uid == (int)substr($ref, $prefLen)) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    public function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    public function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
