<?php
namespace Extension\Templavoila\Service\ClickMenu;

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
 * Class which will add menu items to click menus for the extension TemplaVoila
 *
 * @author Kasper Skaarhoj <kasper@typo3.com>
 * @coauthor Robert Lemke <robert@typo3.org>
 */
class MainClickMenu {

	/**
	 * Main function, adding items to the click menu array.
	 *
	 * @param object &$backRef Reference to the parent object of the clickmenu class which calls this function
	 * @param array $menuItems The current array of menu items - you have to add or remove items to this array in this function. Thats the point...
	 * @param string $table The database table OR filename
	 * @param integer $uid For database tables, the UID
	 *
	 * @return array The modified menu array.
	 */
	public function main(&$backRef, $menuItems, $table, $uid) {
		$localItems = array();
		if (!$backRef->cmLevel) {
			$LL = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->includeLLFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('templavoila') . 'Resources/Private/Language/locallang.xlf', 0);

			// Adding link for Mapping tool:
			if (\Extension\Templavoila\Domain\Model\File::is_file($table)) {
				if (\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->isAdmin()) {
					if (\Extension\Templavoila\Domain\Model\File::is_xmlFile($table)) {
						$url = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'cm1/index.php?file=' . rawurlencode($table);
						$localItems[] = $backRef->linkItem(
							\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLLL('cm1_title', $LL, 1),
							$backRef->excludeIcon('<img src="' . $backRef->backPath . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'cm1/cm_icon.gif" width="15" height="12" border="0" align="top" alt="" />'),
							$backRef->urlRefForCM($url, 'returnUrl'),
							1 // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
						);
					}
				}
			} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('tx_templavoila_tmplobj,tx_templavoila_datastructure,tx_templavoila_content', $table)) {
				$url = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'cm1/index.php?table=' . rawurlencode($table) . '&uid=' . $uid . '&_reload_from=1';
				$localItems[] = $backRef->linkItem(
					\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLLL('cm1_title', $LL, 1),
					$backRef->excludeIcon('<img src="' . $backRef->backPath . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'cm1/cm_icon.gif" width="15" height="12" border="0" align="top" alt="" />'),
					$backRef->urlRefForCM($url, 'returnUrl'),
					1 // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
				);
			}

			$isTVelement = ('tt_content' == $table && $backRef->rec['CType'] == 'templavoila_pi1' || 'pages' == $table) && $backRef->rec['tx_templavoila_flex'];

			// Adding link for "View: Sub elements":
			if ($table == 'tt_content' && $isTVelement) {
				$localItems = array();

				$url = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'mod1/index.php?id=' . intval($backRef->rec['pid']) .
					'&altRoot[table]=' . rawurlencode($table) .
					'&altRoot[uid]=' . $uid .
					'&altRoot[field_flex]=tx_templavoila_flex';

				$localItems[] = $backRef->linkItem(
					\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLLL('cm1_viewsubelements', $LL, 1),
					$backRef->excludeIcon('<img src="' . $backRef->backPath . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'cm1/cm_icon.gif" width="15" height="12" border="0" align="top" alt="" />'),
					$backRef->urlRefForCM($url, 'returnUrl'),
					1 // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
				);
			}

			// Adding link for "View: Flexform XML" (admin only):
			if (\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->isAdmin() && $isTVelement) {
				$url = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'cm2/index.php?' .
					'&viewRec[table]=' . rawurlencode($table) .
					'&viewRec[uid]=' . $uid .
					'&viewRec[field_flex]=tx_templavoila_flex';

				$localItems[] = $backRef->linkItem(
					\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLLL('cm1_viewflexformxml', $LL, 1),
					$backRef->excludeIcon('<img src="' . $backRef->backPath . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'cm2/cm_icon.gif" width="15" height="12" border="0" align="top" alt="" />'),
					$backRef->urlRefForCM($url, 'returnUrl'),
					1 // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
				);
			}

			// Adding link for "View: DS/TO" (admin only):
			if (\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->isAdmin() && $isTVelement) {

				if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($backRef->rec['tx_templavoila_ds'])) {
					$url = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'cm1/index.php?' .
						'table=tx_templavoila_datastructure&uid=' . $backRef->rec['tx_templavoila_ds'];

					$localItems[] = $backRef->linkItem(
						\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLLL('cm_viewdsto', $LL, 1) . ' [' . $backRef->rec['tx_templavoila_ds'] . '/' . $backRef->rec['tx_templavoila_to'] . ']',
						$backRef->excludeIcon('<img src="' . $backRef->backPath . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'cm2/cm_icon.gif" width="15" height="12" border="0" align="top" alt="" />'),
						$backRef->urlRefForCM($url, 'returnUrl'),
						1 // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
					);
				}
			}
		} else {
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('subname') == 'tx_templavoila_cm1_pagesusingthiselement') {
				$menuItems = array();
				$url = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'mod1/index.php?id=';

				// Generate a list of pages where this element is also being used:
				$res = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTquery('*', 'tx_templavoila_elementreferences', 'uid=' . $backRef->rec['uid']);
				if ($res) {
					while (FALSE != ($referenceRecord = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
						$pageRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $referenceRecord['pid']);
						$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', $pageRecord);
						// @todo: Display language flag icon and jump to correct language
						if (is_array($pageRecord)) {
							$menuItems[] = $backRef->linkItem(
								$icon,
								\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle('pages', $pageRecord, 1),
								$backRef->urlRefForCM($url . $pageRecord['uid'], 'returnUrl'),
								1 // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
							);
						}
					}
				}
			}
		}

		// Simply merges the two arrays together and returns ...
		if (count($localItems)) {
			$menuItems = array_merge($menuItems, $localItems);
		}

		return $menuItems;
	}
}
