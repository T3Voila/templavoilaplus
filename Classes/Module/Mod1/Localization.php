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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Extension\Templavoila\Utility\TemplaVoilaUtility;

/**
 * Submodule 'localization' for the templavoila page module
 *
 * @author Robert Lemke <robert@typo3.org>
 */
class Localization implements SingletonInterface
{
    /**
     * A pointer to the parent object, that is the templavoila page module script. Set by calling the method init() of this class.
     *
     * @var \tx_templavoila_module1
     */
    public $pObj; //

    /**
     * Initializes the sub module object. The calling class must make sure that the right locallang files are already loaded.
     * This method is usually called by the templavoila page module.
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

        // Add a localization tab to the sidebar:
        $this->pObj->sideBarObj->addItem(
            'localization',
            $this,
            'sidebar_renderItem',
            TemplaVoilaUtility::getLanguageService()->getLL('localization', true),
            60,
            false
        );
    }

    /**
     * Renders the localization menu item. It contains the language selector, the create new translation button and other settings
     * related to localization.
     *
     * @param \tx_templavoila_module1 $pObj Reference to the sidebar's parent object (the page module). Not used here, we use our own reference, $this->pObj.
     *
     * @return string HTML output
     * @access public
     */
    public function sidebar_renderItem(&$pObj)
    {
        $iOutput = $this->sidebar_renderItem_renderLanguageSelectorbox() .
            $this->sidebar_renderItem_renderNewTranslationSelectorbox();
        $output = (!$iOutput
            ? ''
            : '<table border="0" cellpadding="0" cellspacing="1" width="100%" class="lrPadding">'
                . $iOutput
                . '</table>'
        );

        return $output;
    }

    /**
     * Renders the HTML code for a selectorbox for selecting the language version of the current page.
     *
     * @return boolean|string HTML code for the selectorbox or false if no language is available.
     * @access protected
     */
    public function sidebar_renderItem_renderLanguageSelectorbox()
    {
        $availableLanguagesArr = $this->pObj->translatedLanguagesArr;
        $availableTranslationsFlags = '';
        $newLanguagesArr = $this->pObj->getAvailableLanguages(0, true, false);
        if (count($availableLanguagesArr) <= 1) {
            return false;
        }

        $optionsArr = array();
        foreach ($availableLanguagesArr as $language) {
            unset($newLanguagesArr[$language['uid']]); // Remove this language from possible new translation languages array (PNTLA ;-)

            if ($language['uid'] <= 0 || TemplaVoilaUtility::getBackendUser()->checkLanguageAccess($language['uid'])) {
                $grayedOut = $language['PLO_hidden'] ? ' style="Filter: alpha(opacity=25); -moz-opacity: 0.25; opacity: 0.25"' : '';

                $flag = \Extension\Templavoila\Utility\IconUtility::getFlagIconFileForLanguage($language['flagIcon']);
                $style = isset ($language['flagIcon']) ? 'background-image: url(' . $flag . '); background-size: 16px auto; background-position: left center; background-repeat: no-repeat; padding-left: 22px;' : '';
                $optionsArr [] = '<option style="' . $style . '" value="' . $language['uid'] . '"' . ($this->pObj->MOD_SETTINGS['language'] == $language['uid'] ? ' selected="selected"' : '') . '>' . htmlspecialchars($language['title']) . '</option>';

                // Link to editing of language header:
                $availableTranslationsFlags .= '<a href="'
                    . BackendUtility::getModuleUrl(
                        'web_txtemplavoilaM1',
                        $this->pObj->getLinkParameters(['editPageLanguageOverlay' => $language['uid']])
                    ) . '" style="margin-right:4px">' .
                    '<span ' . $grayedOut . '>' .
                    \Extension\Templavoila\Utility\IconUtility::getFlagIconForLanguage($language['flagIcon'], array('title' => $language['title'], 'alt' => $language['title'])) .
                    '</span></a>';
            }
        }

        $baseLink = $this->pObj->getBaseUrl();

        $output = '
            <tr class="bgColor4">
                <td width="20">
                    ' . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'selectlanguageversion') . '
                </td><td width="200" style="vertical-align:middle;">
                    ' . TemplaVoilaUtility::getLanguageService()->getLL('selectlanguageversion', true) . ':
                </td>
                <td style="vertical-align:middle;"><select onchange="document.location=\'' . $baseLink . '&amp;SET[language]=\'+this.options[this.selectedIndex].value">' . implode('', $optionsArr) . '</select></td>
            </tr>
        ';

        if ($this->pObj->currentLanguageUid >= 0 && (($this->pObj->rootElementLangMode === 'disable') || ($this->pObj->rootElementLangParadigm === 'bound'))) {
            $options = array();
            $options[] = GeneralUtility::inList($this->pObj->modTSconfig['properties']['disableDisplayMode'], 'default') ? '' : '<option value=""' . ($this->pObj->MOD_SETTINGS['langDisplayMode'] === '' ? ' selected="selected"' : '') . '>' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:lang/locallang_general.xlf:LGL.default_value') . '</option>';
            $options[] = GeneralUtility::inList($this->pObj->modTSconfig['properties']['disableDisplayMode'], 'selectedLanguage') ? '' : '<option value="selectedLanguage"' . ($this->pObj->MOD_SETTINGS['langDisplayMode'] === 'selectedLanguage' ? ' selected="selected"' : '') . '>' . TemplaVoilaUtility::getLanguageService()->getLL('pageLocalizationDisplayMode_selectedLanguage') . '</option>';
            $options[] = GeneralUtility::inList($this->pObj->modTSconfig['properties']['disableDisplayMode'], 'onlyLocalized') ? '' : '<option value="onlyLocalized"' . ($this->pObj->MOD_SETTINGS['langDisplayMode'] === 'onlyLocalized' ? ' selected="selected"' : '') . '>' . TemplaVoilaUtility::getLanguageService()->getLL('pageLocalizationDisplayMode_onlyLocalized') . '</option>';
            if (count($options)) {
                $output .= '
                    <tr class="bgColor4">
                        <td width="20">
                            ' . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'pagelocalizationdisplaymode') . '
                        </td><td width="200" style="vertical-align:middle;">
                            ' . TemplaVoilaUtility::getLanguageService()->getLL('pageLocalizationDisplayMode', true) . ':
                        </td>
                        <td style="vertical-align:middle;">
                            <select onchange="document.location=\'' . $baseLink . '&SET[langDisplayMode]=\'+this.options[this.selectedIndex].value">
                                ' . implode(chr(10), $options) . '
                            </select>
                        </td>
                    </tr>
                ';
            }
        }

        if ($this->pObj->rootElementLangMode !== 'disable') {
            $output .= '
                <tr class="bgColor4">
                    <td  width="20">
                        ' . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'pagelocalizationmode') . '
                    </td><td width="200" style="vertical-align:middle;">
                        ' . TemplaVoilaUtility::getLanguageService()->getLL('pageLocalizationMode', true) . ':
                    </td>
                    <td style="vertical-align:middle;"><em>' . TemplaVoilaUtility::getLanguageService()->getLL('pageLocalizationMode_' . $this->pObj->rootElementLangMode, true) . ($this->pObj->rootElementLangParadigm != 'free' ? (' / ' . TemplaVoilaUtility::getLanguageService()->getLL('pageLocalizationParadigm_' . $this->pObj->rootElementLangParadigm)) : '') . '</em></td>
                </tr>
            ';
        }

        // enable/disable structure inheritance - see #7082 for details
        $adminOnlySetting = isset($this->pObj->modTSconfig['properties']['adminOnlyPageStructureInheritance']) ? $this->pObj->modTSconfig['properties']['adminOnlyPageStructureInheritance'] : 'strict';
        if ((TemplaVoilaUtility::getBackendUser()->isAdmin() || $adminOnlySetting === 'false') && $this->pObj->rootElementLangMode == 'inheritance') {
            $output .= '
                <tr class="bgColor4">
                    <td  width="20">
                        ' . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'disablePageStructureInheritance') . '
                    </td><td width="200" style="vertical-align:middle;">
                        ' . TemplaVoilaUtility::getLanguageService()->getLL('pageLocalizationMode_inheritance.disableInheritance', true) . ':
                    </td>
                    <td style="vertical-align:middle;">
                        <input type="checkbox" onchange="document.location=\'' . $baseLink . '&SET[disablePageStructureInheritance]=\'+(this.checked ? 1 : 0)" ' . ($this->pObj->MOD_SETTINGS['disablePageStructureInheritance'] == '1' ? ' checked="checked"' : '') . '/>
                    </td>
                </tr>
            ';
        }

        $output .= '
            <tr class="bgColor4">
                <td  width="20">
                    ' . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'editlanguageversion') . '
                </td><td width="200" style="vertical-align:middle;">
                    ' . TemplaVoilaUtility::getLanguageService()->getLL('editlanguageversion', true) . ':
                </td>
                <td style="vertical-align:middle;">
                    ' . $availableTranslationsFlags . '
                </td>
            </tr>
        ';

        return $output;
    }

    /**
     * Renders the HTML code for a selectorbox for selecting a new translation language for the current
     * page (create a new "Alternative Page Header".
     *
     * @return boolean|string HTML code for the selectorbox or false if no new translation can be created.
     * @access protected
     */
    public function sidebar_renderItem_renderNewTranslationSelectorbox()
    {
        if (!TemplaVoilaUtility::getBackendUser()->isPSet($this->pObj->calcPerms, 'pages', 'edit')) {
            return false;
        }

        $newLanguagesArr = $this->pObj->getAvailableLanguages(0, true, false);
        if (count($newLanguagesArr) < 1) {
            return false;
        }

        $translatedLanguagesArr = $this->pObj->getAvailableLanguages($this->pObj->id);
        $optionsArr = array('<option value=""></option>');
        foreach ($newLanguagesArr as $language) {
            if (TemplaVoilaUtility::getBackendUser()->checkLanguageAccess($language['uid']) && !isset($translatedLanguagesArr[$language['uid']])) {
                $flag = \Extension\Templavoila\Utility\IconUtility::getFlagIconFileForLanguage($language['flagIcon']);
                $style = isset ($language['flagIcon']) ? 'background-image: url(' . $flag . '); background-size: 16px auto; background-position: left center; background-repeat: no-repeat; padding-top: 0px; padding-left: 22px;' : '';
                $optionsArr [] = '<option style="' . $style . '" name="createNewPageTranslation" value="' . $language['uid'] . '">' . htmlspecialchars($language['title']) . '</option>';
            }
        }

        $output = '';
        if (count($optionsArr) > 1) {
            $linkParam = ['pid' => $this->pObj->id];
            if ($this->pObj->rootElementTable === 'pages') {
                $linkParam['doktype'] = $this->pObj->rootElementRecord['doktype'];
            }
            $link = $this->pObj->getBaseUrl($linkParam)
                . '&createNewPageTranslation=\'+this.options[this.selectedIndex].value';
            $output = '
                <tr class="bgColor4">
                    <td width="20">
                        ' . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'createnewtranslation') . '
                    </td><td width="200" style="vertical-align:middle;">
                        ' . TemplaVoilaUtility::getLanguageService()->getLL('createnewtranslation', true) . ':
                    </td>
                    <td style="vertical-align:middle;"><select onChange="document.location=\'' . $link . '">' . implode('', $optionsArr) . '</select></td>
                </tr>
            ';
        }

        return $output;
    }
}
