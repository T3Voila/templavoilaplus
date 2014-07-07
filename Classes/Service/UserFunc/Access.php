<?php
namespace Extension\Templavoila\Service\UserFunc;

/**
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
 * Class being included by UserAuthGroup using a hook
 *
 * @author Dmitry Dulepov <dmitry@typo3.org>
 */
class Access {

	/**
	 * Checks if user is allowed to modify FCE.
	 *
	 * @param array $params Parameters
	 * @param object $ref Parent object
	 *
	 * @return boolean <code>true</code> if change is allowed
	 */
	public function recordEditAccessInternals($params, $ref) {
		if ($params['table'] == 'tt_content' && is_array($params['idOrRow']) && $params['idOrRow']['CType'] == 'templavoila_pi1') {
			if (!$ref) {
				$user = & \Extension\Templavoila\Utility\GeneralUtility::getBackendUser();
			} else {
				$user = & $ref;
			}
			if ($user->isAdmin()) {
				return TRUE;
			}

			if (!$this->checkObjectAccess('tx_templavoila_datastructure', $params['idOrRow']['tx_templavoila_ds'], $ref)) {
				$error = 'access_noDSaccess';
			} elseif (!$this->checkObjectAccess('tx_templavoila_tmplobj', $params['idOrRow']['tx_templavoila_to'], $ref)) {
				$error = 'access_noTOaccess';
			} else {
				return TRUE;
			}
			if ($ref) {
				\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->init($user->uc['lang']);
				$ref->errorMsg = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL('LLL:EXT:templavoila/Resources/Private/Language/locallang_access.xml:' . $error);
			}

			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Checks user's access to given database object
	 *
	 * @param string $table Table name
	 * @param int $uid UID of the record
	 * @param object $be_user BE user object
	 *
	 * @return boolean <code>true</code> if access is allowed
	 */
	public function checkObjectAccess($table, $uid, $be_user) {
		if (!$be_user) {
			$be_user = \Extension\Templavoila\Utility\GeneralUtility::getBackendUser();
		}
		if (!$be_user->isAdmin()) {
			$prefLen = strlen($table) + 1;
			foreach ($be_user->userGroups as $group) {
				$items = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $group['tx_templavoila_access'], 1);
				foreach ($items as $ref) {
					if (strstr($ref, $table)) {
						if ($uid == intval(substr($ref, $prefLen))) {
							return FALSE;
						}
					}
				}
			}
		}

		return TRUE;
	}
}
