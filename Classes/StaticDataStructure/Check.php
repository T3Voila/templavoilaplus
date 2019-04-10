<?php
namespace Ppi\TemplaVoilaPlus\StaticDataStructure;

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

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Static DS check
 */
class Check
{
    /**
     * Display message
     *
     * @param array $params
     * @param \TYPO3\CMS\Extensionmanager\ViewHelpers\Form\TypoScriptConstantsViewHelper $tsObj
     * @return string
     */
    public function displayMessage(&$params, &$tsObj)
    {
        if (!$this->staticDsIsEnabled()) {
            return TemplaVoilaUtility::getLanguageService()->sL(
                'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang.xlf:extconf.staticWizard.messageNoMigration'
            );
        }

        if ($this->datastructureDbCount() === 0) {
            return TemplaVoilaUtility::getLanguageService()->sL(
                'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang.xlf:extconf.staticWizard.messageMigrationDone'
            );
        }

        $link = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl(
            'tools_ExtensionmanagerExtensionmanager',
            array(
                'tx_extensionmanager_tools_extensionmanagerextensionmanager[extensionKey]' => 'templavoilaplus',
                'tx_extensionmanager_tools_extensionmanagerextensionmanager[action]' => 'show',
                'tx_extensionmanager_tools_extensionmanagerextensionmanager[controller]' => 'UpdateScript'
            )
        );

        return '
        <div style="position:absolute;top:10px;right:10px; width:300px;z-index:500">
            <div class="typo3-message message-information">
                <div class="message-header">' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/locallang.xlf:extconf.staticWizard.header') . '</div>
                <div class="message-body">
                    ' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/locallang.xlf:extconf.staticWizard.messageMigration') . '<br />
                    <a style="text-decoration:underline;" href="' . $link . '">
                    ' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/locallang.xlf:extconf.staticWizard.link') . '</a>
                </div>
            </div>
        </div>
        ';

        return 'Use the Update button in the Extension manager to run the staticDS migration tool.';
    }

    /**
     * Is static DS enabled?
     *
     * @return boolean
     */
    protected function staticDsIsEnabled()
    {
        $conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoilaplus']);
        return (bool)$conf['staticDS.']['enable'];
    }

    /**
     * Get data structure count
     *
     * @return integer
     */
    protected function datastructureDbCount()
    {
        return TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTcountRows(
            '*',
            'tx_templavoilaplus_datastructure',
            'deleted=0'
        );
    }
}
