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

use TYPO3\CMS\Backend\Utility\IconUtility;
use Extension\Templavoila\Utility\GeneralUtility;

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
		$extensionRelativePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila');
		if (!$backRef->cmLevel) {
			$LL = GeneralUtility::getLanguageService()->includeLLFile(
				'EXT:templavoila/Resources/Private/Language/locallang.xlf',
				FALSE
			);

			// Adding link for Mapping tool:
			if (
				\Extension\Templavoila\Domain\Model\File::is_file($table)
				&& GeneralUtility::getBackendUser()->isAdmin()
				&& \Extension\Templavoila\Domain\Model\File::is_xmlFile($table)
			) {
				$url = $extensionRelativePath . 'cm1/index.php?file=' . rawurlencode($table);
				$localItems[] = $backRef->linkItem(
					GeneralUtility::getLanguageService()->getLLL('cm1_title', $LL, TRUE),
					$backRef->excludeIcon(IconUtility::getSpriteIcon('extensions-templavoila-templavoila-logo-small')),
					$backRef->urlRefForCM($url, 'returnUrl'),
					TRUE // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
				);
			} elseif (
				$table === 'tx_templavoila_tmplobj'
				|| $table === 'tx_templavoila_datastructure'
				|| $table === 'tx_templavoila_content'
			) {
				$url = $extensionRelativePath . 'cm1/index.php?table=' . rawurlencode($table) . '&uid=' . $uid . '&_reload_from=1';
				$localItems[] = $backRef->linkItem(
					GeneralUtility::getLanguageService()->getLLL('cm1_title', $LL, TRUE),
					$backRef->excludeIcon(IconUtility::getSpriteIcon('extensions-templavoila-templavoila-logo-small')),
					$backRef->urlRefForCM($url, 'returnUrl'),
					TRUE // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
				);
			}

			$isTVelement = ('tt_content' === $table && $backRef->rec['CType'] === 'templavoila_pi1' || 'pages' === $table) && $backRef->rec['tx_templavoila_flex'];

			// Adding link for "View: Sub elements":
			if ($table === 'tt_content' && $isTVelement) {
				$localItems = array();

				$url = $extensionRelativePath . 'mod1/index.php?id=' . (int)$backRef->rec['pid'] .
					'&altRoot[table]=' . rawurlencode($table) .
					'&altRoot[uid]=' . $uid .
					'&altRoot[field_flex]=tx_templavoila_flex';

				$localItems[] = $backRef->linkItem(
					GeneralUtility::getLanguageService()->getLLL('cm1_viewsubelements', $LL, TRUE),
					$backRef->excludeIcon(IconUtility::getSpriteIcon('extensions-templavoila-templavoila-logo-small')),
					$backRef->urlRefForCM($url, 'returnUrl'),
					TRUE // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
				);
			}

			// Adding link for "View: Flexform XML" (admin only):
			if (GeneralUtility::getBackendUser()->isAdmin() && $isTVelement) {
				$url = $extensionRelativePath . 'cm2/index.php?' .
					'&viewRec[table]=' . rawurlencode($table) .
					'&viewRec[uid]=' . $uid .
					'&viewRec[field_flex]=tx_templavoila_flex';

				$localItems[] = $backRef->linkItem(
					GeneralUtility::getLanguageService()->getLLL('cm1_viewflexformxml', $LL, TRUE),
					$backRef->excludeIcon(IconUtility::getSpriteIcon('extensions-templavoila-templavoila-logo-small')),
					$backRef->urlRefForCM($url, 'returnUrl'),
					TRUE // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
				);

				// Adding link for "View: DS/TO" (admin only):
				if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($backRef->rec['tx_templavoila_ds'])) {
					$url = $extensionRelativePath . 'cm1/index.php?table=tx_templavoila_datastructure&uid=' . $backRef->rec['tx_templavoila_ds'];

					$localItems[] = $backRef->linkItem(
						GeneralUtility::getLanguageService()->getLLL('cm_viewdsto', $LL, TRUE) . ' [' . $backRef->rec['tx_templavoila_ds'] . '/' . $backRef->rec['tx_templavoila_to'] . ']',
						$backRef->excludeIcon(IconUtility::getSpriteIcon('extensions-templavoila-templavoila-logo-small')),
						$backRef->urlRefForCM($url, 'returnUrl'),
						TRUE // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
					);
				}
			}
		} else {
			// @TODO check where this code is used.
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('subname') === 'tx_templavoila_cm1_pagesusingthiselement') {
				$menuItems = array();
				$url = $extensionRelativePath . 'mod1/index.php?id=';

				// Generate a list of pages where this element is also being used:
				$referenceRecords = GeneralUtility::getDatabaseConnection()->exec_SELECTgetRows(
					'*',
					'tx_templavoila_elementreferences',
					'uid=' . (int)$backRef->rec['uid']
				);
				foreach ($referenceRecords as $referenceRecord) {
					$pageRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $referenceRecord['pid']);
					// @todo: Display language flag icon and jump to correct language
					if ($pageRecord !== NULL) {
						$icon = IconUtility::getSpriteIconForRecord('pages', $pageRecord);
						$menuItems[] = $backRef->linkItem(
							$icon,
							\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle('pages', $pageRecord, TRUE),
							$backRef->urlRefForCM($url . $pageRecord['uid'], 'returnUrl'),
							TRUE // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
						);
					}
				}
			}
		}

		// Simply merges the two arrays together and returns ...
		if (!empty($localItems)) {
			$menuItems = array_merge($menuItems, $localItems);
		}

		return $menuItems;
	}
}
