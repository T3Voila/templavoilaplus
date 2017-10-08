<?php
namespace Ppi\TemplaVoilaPlus\Service\ClickMenu;

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
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Class which will add menu items to click menus for the extension TemplaVoila
 *
 * @author Kasper Skaarhoj <kasper@typo3.com>
 * @coauthor Robert Lemke <robert@typo3.org>
 * @deprecated Will be removed with TemplaVoilà! Plus 8
 */
class MainClickMenu
{
    /**
     * Main function, adding items to the click menu array.
     *
     * @param object $clickMenu Reference to the parent object of the clickmenu class which calls this function
     * @param array $menuItems The current array of menu items - you have to add or remove items to this array in this function. Thats the point...
     * @param string $table The database table OR filename
     * @param integer $uid For database tables, the UID
     *
     * @return array The modified menu array.
     */
    public function main(\TYPO3\CMS\Backend\ClickMenu\ClickMenu $clickMenu, $menuItems, $table, $uid)
    {
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $localItems = array();

        if ($clickMenu->cmLevel === 0) {
            $LL = $this->getLanguageService()->includeLLFile(
                'EXT:templavoilaplus/Resources/Private/Language/locallang.xlf',
                false
            );

            // Adding link for Mapping tool:
            if (\Ppi\TemplaVoilaPlus\Domain\Model\File::is_file($table)
                && $this->getBackendUser()->isAdmin()
                && \Ppi\TemplaVoilaPlus\Domain\Model\File::is_xmlFile($table)
            ) {
                $localItems[] = $clickMenu->linkItem(
                    $this->getLanguageService()->getLLL('cm1_title', $LL, true),
                    $iconFactory->getIcon('extensions-templavoila-templavoila-logo', Icon::SIZE_SMALL)->render(),
                    $clickMenu->urlRefForCM(
                        BackendUtility::getModuleUrl(
                            'templavoilaplus_mapping',
                            [
                                'file' => $table,
                            ]
                        ),
                        'returnUrl'
                    ),
                    true // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
                );
            } elseif ($table === 'tx_templavoilaplus_tmplobj'
                || $table === 'tx_templavoilaplus_datastructure'
            ) {
                $localItems[] = $clickMenu->linkItem(
                    $this->getLanguageService()->getLLL('cm1_title', $LL, true),
                    $iconFactory->getIcon('extensions-templavoila-templavoila-logo', Icon::SIZE_SMALL)->render(),
                    $clickMenu->urlRefForCM(
                        BackendUtility::getModuleUrl(
                            'templavoilaplus_mapping',
                            [
                                'table' => $table,
                                'uid' => $uid,
                                '_reload_from' => 1,
                            ]
                        ),
                        'returnUrl'
                    ),
                    true // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
                );
            }

            $isTVelement = ('tt_content' === $table && $clickMenu->rec['CType'] === 'templavoilaplus_pi1' || 'pages' === $table) && $clickMenu->rec['tx_templavoilaplus_flex'];

            // Adding link for "View: Sub elements":
            if ($table === 'tt_content' && $isTVelement) {
                $localItems = array();

                $localItems[] = $clickMenu->linkItem(
                    $this->getLanguageService()->getLLL('cm1_viewsubelements', $LL, true),
                    $iconFactory->getIcon('extensions-templavoila-templavoila-logo', Icon::SIZE_SMALL)->render(),
                    $clickMenu->urlRefForCM(
                        BackendUtility::getModuleUrl(
                            'web_txtemplavoilaplusLayout',
                            [
                                'id' => (int)$clickMenu->rec['pid'],
                                'altRoot' => [
                                    'table' => $table,
                                    'uid' => $uid,
                                    'field_flex' => 'tx_templavoilaplus_flex',
                                ],
                            ]
                        ),
                        'returnUrl'
                    ),
                    true // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
                );
            }

            // Adding link for "View: Flexform XML" (admin only):
            if ($this->getBackendUser()->isAdmin() && $isTVelement) {
                $localItems[] = $clickMenu->linkItem(
                    $this->getLanguageService()->getLLL('cm1_viewflexformxml', $LL, true),
                    $iconFactory->getIcon('extensions-templavoila-templavoila-logo', Icon::SIZE_SMALL)->render(),
                    $clickMenu->urlRefForCM(
                        BackendUtility::getModuleUrl(
                            'templavoilaplus_flexform_cleaner',
                            [
                                'id' => (int)$clickMenu->rec['pid'],
                                'viewRec' => [
                                    'table' => $table,
                                    'uid' => $uid,
                                    'field_flex' => 'tx_templavoilaplus_flex',
                                ],
                            ]
                        ),
                        'returnUrl'
                    ),
                    true // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
                );

                // Adding link for "View: DS/TO" (admin only):
                if (MathUtility::canBeInterpretedAsInteger($clickMenu->rec['tx_templavoilaplus_ds'])) {
                    $localItems[] = $clickMenu->linkItem(
                        $this->getLanguageService()->getLLL('cm_viewdsto', $LL, true) . ' [' . $clickMenu->rec['tx_templavoilaplus_ds'] . '/' . $clickMenu->rec['tx_templavoilaplus_to'] . ']',
                        $iconFactory->getIcon('extensions-templavoila-templavoila-logo', Icon::SIZE_SMALL)->render(),
                        $clickMenu->urlRefForCM(
                            BackendUtility::getModuleUrl(
                                'templavoilaplus_mapping',
                                [
                                    'uid' => $clickMenu->rec['tx_templavoilaplus_ds'],
                                    'table' => 'tx_templavoilaplus_datastructure',
                                ]
                            ),
                            'returnUrl'
                        ),
                        true // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
                    );
                }
            }
        } else {
            // @TODO check where this code is used.
            if (GeneralUtility::_GP('subname') === 'tx_templavoilaplus_cm1_pagesusingthiselement') {
                $menuItems = array();

                // Generate a list of pages where this element is also being used:
                $referenceRecords = TemplaVoilaUtility::getDatabaseConnection()->getDatabaseConnection()->exec_SELECTgetRows(
                    '*',
                    'tx_templavoilaplus_elementreferences',
                    'uid=' . (int)$clickMenu->rec['uid']
                );
                foreach ($referenceRecords as $referenceRecord) {
                    $pageRecord = BackendUtility::getRecord('pages', $referenceRecord['pid']);
                    // @todo: Display language flag icon and jump to correct language
                    if ($pageRecord !== null) {
                        $menuItems[] = $clickMenu->linkItem(
                            $iconFactory->getIconForRecord('pages', $pageRecord, Icon::SIZE_SMALL)->render(),
                            BackendUtility::getRecordTitle('pages', $pageRecord, true),
                            $clickMenu->urlRefForCM(
                                BackendUtility::getModuleUrl(
                                    'web_txtemplavoilaplusLayout',
                                    [
                                        'id' => $pageRecord['uid'],
                                    ]
                                ),
                                'returnUrl'
                            ),
                            true // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
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
