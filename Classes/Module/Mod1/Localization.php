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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Submodule 'localization' for the templavoila page module
 *
 * @author Robert Lemke <robert@typo3.org>
 */
class Localization implements SingletonInterface
{
    /**
     * @var string
     */
    protected $extKey;

    /**
     * @var array
     */
    protected $MOD_SETTINGS;

    /**
     * A pointer to the parent object, that is the templavoila page module script. Set by calling the method init() of this class.
     *
     * @var \tx_templavoila_module1
     */
    public $pObj; //

    /**
     * A reference to the doc object of the parent object.
     *
     * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    public $doc;

    /**
     * Initializes the sub module object. The calling class must make sure that the right locallang files are already loaded.
     * This method is usually called by the templavoila page module.
     *
     * @param \tx_templavoila_module1 $pObj Reference to the parent object ($this)
     *
     * @return void
     * @access public
     */
    public function init(&$pObj)
    {
        // Make local reference to some important variables:
        $this->pObj =& $pObj;
        $this->doc =& $this->pObj->doc;
        $this->extKey =& $this->pObj->extKey;
        $this->MOD_SETTINGS =& $this->pObj->MOD_SETTINGS;

        // Add a localization tab to the sidebar:
        $this->pObj->sideBarObj->addItem(
            'localization',
            $this,
            'sidebar_renderItem',
            \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('localization', true),
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
        $output = (!$iOutput ? '' : '
            <table border="0" cellpadding="0" cellspacing="1" width="100%" class="lrPadding">
                <tr class="bgColor4-20">
                    <th colspan="3">&nbsp;</th>
                </tr>
                ' .
            $iOutput .
            '
        </table>
    ');

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
        foreach ($availableLanguagesArr as $languageArr) {
            unset($newLanguagesArr[$languageArr['uid']]); // Remove this language from possible new translation languages array (PNTLA ;-)

            if ($languageArr['uid'] <= 0 || \Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->checkLanguageAccess($languageArr['uid'])) {
                $grayedOut = $languageArr['PLO_hidden'] ? ' style="Filter: alpha(opacity=25); -moz-opacity: 0.25; opacity: 0.25"' : '';

                $flag = \Extension\Templavoila\Utility\IconUtility::getFlagIconFileForLanguage($languageArr['flagIcon']);
                $style = isset ($languageArr['flagIcon']) ? 'background-image: url(' . $flag . '); background-size: 16px auto; background-position: left center; background-repeat: no-repeat; padding-left: 22px;' : '';
                $optionsArr [] = '<option style="' . $style . '" value="' . $languageArr['uid'] . '"' . ($this->pObj->MOD_SETTINGS['language'] == $languageArr['uid'] ? ' selected="selected"' : '') . '>' . htmlspecialchars($languageArr['title']) . '</option>';

                // Link to editing of language header:
                $availableTranslationsFlags .= '<a href="'
                    . BackendUtility::getModuleUrl(
                        'web_txtemplavoilaM1',
                        $this->pObj->getLinkParameters(['editPageLanguageOverlay' => $languageArr['uid']])
                    ) . '" style="margin-right:4px">' .
                    '<span ' . $grayedOut . '>' .
                    \Extension\Templavoila\Utility\IconUtility::getFlagIconForLanguage($languageArr['flagIcon'], array('title' => $languageArr['title'], 'alt' => $languageArr['title'])) .
                    '</span></a>';
            }
        }

        $link = '\'index.php?' . $this->pObj->link_getParameters() . '&SET[language]=\'+this.options[this.selectedIndex].value';

        $output = '
            <tr class="bgColor4">
                <td width="20">
                    ' . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'selectlanguageversion', $this->doc->backPath) . '
                </td><td width="200" style="vertical-align:middle;">
                    ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('selectlanguageversion', true) . ':
                </td>
                <td style="vertical-align:middle;"><select onchange="document.location=' . htmlspecialchars($link) . '">' . implode('', $optionsArr) . '</select></td>
            </tr>
        ';

        if ($this->pObj->currentLanguageUid >= 0 && (($this->pObj->rootElementLangMode === 'disable') || ($this->pObj->rootElementLangParadigm === 'bound'))) {
            $options = array();
            $options[] = \TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->pObj->modTSconfig['properties']['disableDisplayMode'], 'default') ? '' : '<option value=""' . ($this->pObj->MOD_SETTINGS['langDisplayMode'] === '' ? ' selected="selected"' : '') . '>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL('LLL:EXT:lang/locallang_general.xlf:LGL.default_value') . '</option>';
            $options[] = \TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->pObj->modTSconfig['properties']['disableDisplayMode'], 'selectedLanguage') ? '' : '<option value="selectedLanguage"' . ($this->pObj->MOD_SETTINGS['langDisplayMode'] === 'selectedLanguage' ? ' selected="selected"' : '') . '>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('pageLocalizationDisplayMode_selectedLanguage') . '</option>';
            $options[] = \TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->pObj->modTSconfig['properties']['disableDisplayMode'], 'onlyLocalized') ? '' : '<option value="onlyLocalized"' . ($this->pObj->MOD_SETTINGS['langDisplayMode'] === 'onlyLocalized' ? ' selected="selected"' : '') . '>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('pageLocalizationDisplayMode_onlyLocalized') . '</option>';
            $link = '\'index.php?' . $this->pObj->link_getParameters() . '&SET[langDisplayMode]=\'+this.options[this.selectedIndex].value';
            if (count($options)) {
                $output .= '
                    <tr class="bgColor4">
                        <td width="20">
                            ' . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'pagelocalizationdisplaymode', $this->doc->backPath) . '
                        </td><td width="200" style="vertical-align:middle;">
                            ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('pageLocalizationDisplayMode', true) . ':
                        </td>
                        <td style="vertical-align:middle;">
                            <select onchange="document.location=' . htmlspecialchars($link) . '">
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
                        ' . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'pagelocalizationmode', $this->doc->backPath) . '
                    </td><td width="200" style="vertical-align:middle;">
                        ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('pageLocalizationMode', true) . ':
                    </td>
                    <td style="vertical-align:middle;"><em>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('pageLocalizationMode_' . $this->pObj->rootElementLangMode, true) . ($this->pObj->rootElementLangParadigm != 'free' ? (' / ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('pageLocalizationParadigm_' . $this->pObj->rootElementLangParadigm)) : '') . '</em></td>
                </tr>
            ';
        }

        // enable/disable structure inheritance - see #7082 for details
        $adminOnlySetting = isset($this->pObj->modTSconfig['properties']['adminOnlyPageStructureInheritance']) ? $this->pObj->modTSconfig['properties']['adminOnlyPageStructureInheritance'] : 'strict';
        if ((\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->isAdmin() || $adminOnlySetting === 'false') && $this->pObj->rootElementLangMode == 'inheritance') {
            $link = '\'index.php?' . $this->pObj->link_getParameters() . '&SET[disablePageStructureInheritance]=' . ($this->pObj->MOD_SETTINGS['disablePageStructureInheritance'] == '1' ? '0' : '1') . '\'';
            $output .= '
                <tr class="bgColor4">
                    <td  width="20">
                        ' . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'disablePageStructureInheritance', $this->doc->backPath) . '
                    </td><td width="200" style="vertical-align:middle;">
                        ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('pageLocalizationMode_inheritance.disableInheritance', true) . ':
                    </td>
                    <td style="vertical-align:middle;">
                        <input type="checkbox" onchange="document.location=' . $link . '" ' . ($this->pObj->MOD_SETTINGS['disablePageStructureInheritance'] == '1' ? ' checked="checked"' : '') . '/>
                    </td>
                </tr>
            ';
        }

        $output .= '
            <tr class="bgColor4">
                <td  width="20">
                    ' . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'editlanguageversion', $this->doc->backPath) . '
                </td><td width="200" style="vertical-align:middle;">
                    ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('editlanguageversion', true) . ':
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
        if (!\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->isPSet($this->pObj->calcPerms, 'pages', 'edit')) {
            return false;
        }

        $newLanguagesArr = $this->pObj->getAvailableLanguages(0, true, false);
        if (count($newLanguagesArr) < 1) {
            return false;
        }

        $translatedLanguagesArr = $this->pObj->getAvailableLanguages($this->pObj->id);
        $optionsArr = array('<option value=""></option>');
        foreach ($newLanguagesArr as $language) {
            if (\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->checkLanguageAccess($language['uid']) && !isset($translatedLanguagesArr[$language['uid']])) {
                $flag = \Extension\Templavoila\Utility\IconUtility::getFlagIconFileForLanguage($language['flagIcon']);
                $style = isset ($language['flagIcon']) ? 'background-image: url(' . $flag . '); background-repeat: no-repeat; padding-top: 0px; padding-left: 22px;' : '';
                $optionsArr [] = '<option style="' . $style . '" name="createNewPageTranslation" value="' . $language['uid'] . '">' . htmlspecialchars($language['title']) . '</option>';
            }
        }

        $output = '';
        if (count($optionsArr) > 1) {
            $linkParam = $this->pObj->rootElementTable == 'pages' ? '&doktype=' . $this->pObj->rootElementRecord['doktype'] : '';
            $link = 'index.php?' . $this->pObj->link_getParameters() . '&createNewPageTranslation=\'+this.options[this.selectedIndex].value+\'&pid=' . $this->pObj->id . $linkParam;
            $output = '
                <tr class="bgColor4">
                    <td width="20">
                        ' . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'createnewtranslation', $this->doc->backPath) . '
                    </td><td width="200" style="vertical-align:middle;">
                        ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('createnewtranslation', true) . ':
                    </td>
                    <td style="vertical-align:middle;"><select onChange="document.location=\'' . htmlspecialchars($link) . '\'">' . implode('', $optionsArr) . '</select></td>
                </tr>
            ';
        }

        return $output;
    }
}
