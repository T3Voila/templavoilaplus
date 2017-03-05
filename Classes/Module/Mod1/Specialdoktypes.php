<?php
namespace Extension\Templavoila\Module\Mod1;

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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Submodule 'clipboard' for the templavoila page module
 *
 * @author Robert Lemke <robert@typo3.org>
 * @package TYPO3
 * @subpackage tx_templavoila
 * @todo This class wants to be refactored because there's quite some redundancy in it. But that's not urgent ...
 */
class Specialdoktypes implements SingletonInterface
{
    /**
     * A pointer to the parent object, that is the templavoila page module script. Set by calling the method init() of this class.
     *
     * @var \tx_templavoila_module1
     */
    public $pObj;

    /**
     * Does some basic initialization
     *
     * @param \tx_templavoila_module1 $pObj Reference to the parent object ($this)
     *
     * @return void
     * @access public
     */
    public function init($pObj)
    {
        // Make local reference to some important variables:
        $this->pObj = $pObj;
    }

    /**
     * Displays the edit page screen if the currently selected page is of the doktype "External URL"
     *
     * @param array $pageRecord The current page record
     *
     * @return string HTML output from this submodule or false if this submodule doesn't feel responsible
     */
    public function renderDoktype_3($pageRecord)
    {
        switch ($pageRecord['urltype']) {
            case 2:
                $url = 'ftp://' . $pageRecord['url'];
                break;
            case 3:
                $url = 'mailto:' . $pageRecord['url'];
                break;
            case 4:
                $url = 'https://' . $pageRecord['url'];
                break;
            default:
                // Check if URI scheme already present. We support only Internet-specific notation,
                // others are not relevant for us (see http://www.ietf.org/rfc/rfc3986.txt for details)
                if (preg_match('/^[a-z]+[a-z0-9\+\.\-]*:\/\//i', $pageRecord['url'])) {
                    // Do not add any other scheme
                    $url = $pageRecord['url'];
                    break;
                }
            // fall through
            case 1:
                $url = 'http://' . $pageRecord['url'];
                break;
        }

        // check if there is a notice on this URL type
        $notice = $this->getLanguageService()->getLL('cannotedit_externalurl_' . $pageRecord['urltype'], true);
        if (!$notice) {
            $notice = $this->getLanguageService()->getLL('cannotedit_externalurl_1', true);
        }

        $urlInfo = ' <br /><br /><strong><a href="' . $url . '" target="_new">' . htmlspecialchars(sprintf($this->getLanguageService()->getLL('jumptoexternalurl'), $url)) . '</a></strong>';

        $this->pObj->getModuleTemplate()->addFlashMessage(
            $notice,
            '',
            \TYPO3\CMS\Core\Messaging\FlashMessage::INFO
        );

        return $urlInfo;
    }

    /**
     * Displays the edit page screen if the currently selected page is of the doktype "Shortcut"
     *
     * @param array $pageRecord The current page record
     *
     * @return string HTML output from this submodule or false if this submodule doesn't feel responsible
     */
    public function renderDoktype_4($pageRecord)
    {
        $jumpToShortcutSourceLink = '';
        if ((int)$pageRecord['shortcut_mode'] == 0) {
            $shortcutSourcePageRecord = BackendUtility::getRecordWSOL('pages', $pageRecord['shortcut']);
            $jumpToShortcutSourceLink = $this->pObj->buildButtonFromUrl(
                $this->pObj->getBaseUrl(['id' => $pageRecord['shortcut']]),
                $this->getLanguageService()->getLL('jumptoshortcutdestination', true),
                'apps-pagetree-page-shortcut'
            );
        }

        $this->pObj->getModuleTemplate()->addFlashMessage(
            sprintf($this->getLanguageService()->getLL('cannotedit_shortcut_' . (int)$pageRecord['shortcut_mode']), $shortcutSourcePageRecord['title']),
            '',
            \TYPO3\CMS\Core\Messaging\FlashMessage::INFO
        );

        return $jumpToShortcutSourceLink;
    }

    /**
     * Displays the edit page screen if the currently selected page is of the doktype "Mount Point"
     *
     * @param array $pageRecord The current page record
     *
     * @return boolean|string HTML output from this submodule or false if this submodule doesn't feel responsible
     * @access protected
     */
    public function renderDoktype_7($pageRecord)
    {
        if (!$pageRecord['mount_pid_ol']) {
            return false;
        }

        $mountSourcePageRecord = BackendUtility::getRecordWSOL('pages', $pageRecord['mount_pid']);
        $mountSourceIcon = $this->pObj->getIconFactory()->getSpriteIconForRecord('pages', $mountSourcePageRecord, Icon::SIZE_SMALL);
        $mountSourceButton = BackendUtility::wrapClickMenuOnIcon(
            $mountSourceIcon,
            'pages',
            $mountSourcePageRecord['uid'],
            true,
            '',
            'new,copy,cut,pasteinto,pasteafter,delete'
        );

        $mountSourceLink = '<br /><br />
            <a href="index.php?id=' . $pageRecord['mount_pid'] . '">' . htmlspecialchars($this->getLanguageService()->getLL('jumptomountsourcepage')) . '</a>
        ';

        $this->pObj->getModuleTemplate()->addFlashMessage(
            sprintf($this->getLanguageService()->getLL('cannotedit_doktypemountpoint'), $mountSourceButton . $mountSourcePageRecord['title']),
            '',
            \TYPO3\CMS\Core\Messaging\FlashMessage::INFO
        );

        return '<strong>' . $mountSourceLink . '</strong>';
    }

    /**
     * Displays the edit page screen if the currently selected page is of the doktype "Sysfolder"
     *
     * @param array $pageRecord The current page record
     *
     * @return string HTML output from this submodule or false if this submodule doesn't feel responsible
     */
    public function renderDoktype_254($pageRecord)
    {
        if ($this->userHasAccessToListModule()) {
            $listModuleURL = BackendUtility::getModuleUrl('web_list', array('id' => (int)$this->pObj->id), '');
            $onClick = "top.nextLoadModuleUrl='" . $listModuleURL . "';top.fsMod.recentIds['web']=" . (int)$this->pObj->id . ";top.goToModule('web_list',1);";
            $listModuleLink = '<br /><br />' .
                $this->pObj->getIconFactory()->getIcon('actions-system-list-open', Icon::SIZE_SMALL) .
                '<strong><a href="#" onClick="' . $onClick . '">' . $this->getLanguageService()->getLL('editpage_sysfolder_switchtolistview') . '</a></strong>
            ';
        } else {
            $listModuleLink = $this->getLanguageService()->getLL('editpage_sysfolder_listview_noaccess');
        }

        $this->pObj->getModuleTemplate()->addFlashMessage(
            $this->getLanguageService()->getLL('editpage_sysfolder_intro'),
            '',
            \TYPO3\CMS\Core\Messaging\FlashMessage::INFO
        );
        return $listModuleLink;
    }

    /**
     * Returns true if the logged in BE user has access to the list module.
     *
     * @return boolean
     * @access protected
     */
    public function userHasAccessToListModule()
    {
        if (!BackendUtility::isModuleSetInTBE_MODULES('web_list')) {
            return false;
        }
        return $this->getBackendUser()->isAdmin() || $this->getBackendUser()->check('modules', 'web_list');
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
