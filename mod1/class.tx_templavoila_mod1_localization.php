<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2006  Robert Lemke (robert@typo3.org)
*  All rights reserved
*
*  script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Submodule 'localization' for the templavoila page module
 *
 * $Id$
 *
 * @author     Robert Lemke <robert@typo3.org>
 */

/**
 * Submodule 'localization' for the templavoila page module
 *
 * @author		Robert Lemke <robert@typo3.org>
 * @package		TYPO3
 * @subpackage	tx_templavoila
 */
class tx_templavoila_mod1_localization {

		// References to the page module object
	var $pObj;										// A pointer to the parent object, that is the templavoila page module script. Set by calling the method init() of this class.
	var $doc;										// A reference to the doc object of the parent object.

	/**
	 * Initializes the sub module object. The calling class must make sure that the right locallang files are already loaded.
	 * This method is usually called by the templavoila page module.
	 *
	 * @param	$pObj:		Reference to the parent object ($this)
	 * @return	void
	 * @access	public
	 */
	function init(&$pObj) {
		global $LANG;

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
			$LANG->getLL('localization', 1),
			60,
			false
		);
	}

	/**
	 * Renders the localization menu item. It contains the language selector, the create new translation button and other settings
	 * related to localization.
	 *
	 * @param	$pObj:		Reference to the sidebar's parent object (the page module). Not used here, we use our own reference, $this->pObj.
	 * @return	string		HTML output
	 * @access	public
	 */
	function sidebar_renderItem(&$pObj) {
		global $LANG;

		$iOutput = $this->sidebar_renderItem_renderLanguageSelectorbox().
				$this->sidebar_renderItem_renderNewTranslationSelectorbox();
		$output = (!$iOutput ? '' : '
			<table border="0" cellpadding="0" cellspacing="1" width="100%" class="lrPadding">
				<tr class="bgColor4-20">
					<th colspan="3">&nbsp;</th>
				</tr>
				'.
				$iOutput .
				'
			</table>
		');
		return $output;
	}

	/**
	 * Renders the HTML code for a selectorbox for selecting the language version of the current
	 * page.
	 *
	 * @return	mixed		HTML code for the selectorbox or FALSE if no language is available.
	 * @access	protected
	 */
	function sidebar_renderItem_renderLanguageSelectorbox() {
		global $LANG, $BE_USER, $BACK_PATH;

		$availableLanguagesArr = $this->pObj->translatedLanguagesArr;
		$availableTranslationsFlags = '';
		$newLanguagesArr = $this->pObj->getAvailableLanguages(0, true, false);
		if (count($availableLanguagesArr) <= 1) return FALSE;

		$optionsArr = array ();
		foreach ($availableLanguagesArr as $languageArr) {
			unset($newLanguagesArr[$languageArr['uid']]);	// Remove this language from possible new translation languages array (PNTLA ;-)

			if ($languageArr['uid']<=0 || $BE_USER->checkLanguageAccess($languageArr['uid']))	{
				$grayedOut = $languageArr['PLO_hidden'] ? ' style="Filter: alpha(opacity=25); -moz-opacity: 0.25; opacity: 0.25"' : '';

				$flag = tx_templavoila_icons::getFlagIconFileForLanguage($languageArr['flagIcon']);
				$style = isset ($languageArr['flagIcon']) ? 'background-image: url(' . $flag . '); background-repeat: no-repeat; padding-left: 22px;' : '';
				$optionsArr [] = '<option style="'.$style.'" value="'.$languageArr['uid'].'"'.($this->pObj->MOD_SETTINGS['language'] == $languageArr['uid'] ? ' selected="selected"' : '').'>'.htmlspecialchars($languageArr['title']).'</option>';

					// Link to editing of language header:
				$availableTranslationsFlags .= '<a href="index.php?' .
					htmlspecialchars($this->pObj->link_getParameters() . '&editPageLanguageOverlay=' . $languageArr['uid']) . '">' .
					'<span ' . $grayedOut . '>' .
		 			tx_templavoila_icons::getFlagIconForLanguage($languageArr['flagIcon'], array('title' => $languageArr['title'], 'alt' => $languageArr['title'])) .
					'</span></a>';
			}
		}

		$link = '\'index.php?' . $this->pObj->link_getParameters() . '&SET[language]=\'+this.options[this.selectedIndex].value';

		$output = '
			<tr class="bgColor4">
				<td width="20">
					'. t3lib_BEfunc::cshItem('_MOD_web_txtemplavoilaM1', 'selectlanguageversion', $this->doc->backPath) .'
				</td><td width="200" style="vertical-align:middle;">
					'.$LANG->getLL ('selectlanguageversion', 1).':
				</td>
				<td style="vertical-align:middle;"><select onchange="document.location=' . htmlspecialchars($link) . '">' . implode ('', $optionsArr) . '</select></td>
			</tr>
		';

		if ($this->pObj->currentLanguageUid>=0 && (($this->pObj->rootElementLangMode === 'disable') || ($this->pObj->rootElementLangParadigm === 'bound'))) {
			$options = array();
			$options[] = t3lib_div::inList($this->pObj->modTSconfig['properties']['disableDisplayMode'], 'default')?'':'<option value=""'.($this->pObj->MOD_SETTINGS['langDisplayMode']===''?' selected="selected"':'').'>'.$LANG->sL('LLL:EXT:lang/locallang_general.xml:LGL.default_value').'</option>';
			$options[] = t3lib_div::inList($this->pObj->modTSconfig['properties']['disableDisplayMode'], 'selectedLanguage')?'':'<option value="selectedLanguage"'.($this->pObj->MOD_SETTINGS['langDisplayMode']==='selectedLanguage'?' selected="selected"':'').'>'.$LANG->getLL('pageLocalizationDisplayMode_selectedLanguage').'</option>';
			$options[] = t3lib_div::inList($this->pObj->modTSconfig['properties']['disableDisplayMode'], 'onlyLocalized')?'':'<option value="onlyLocalized"'.($this->pObj->MOD_SETTINGS['langDisplayMode']==='onlyLocalized'?' selected="selected"':'').'>'.$LANG->getLL('pageLocalizationDisplayMode_onlyLocalized').'</option>';
			$link = '\'index.php?' . $this->pObj->link_getParameters() . '&SET[langDisplayMode]=\'+this.options[this.selectedIndex].value';
			if (count($options))	{
				$output.= '
					<tr class="bgColor4">
						<td width="20">
							'. t3lib_BEfunc::cshItem('_MOD_web_txtemplavoilaM1', 'pagelocalizationdisplaymode', $this->doc->backPath) .'
						</td><td width="200" style="vertical-align:middle;">
							'.$LANG->getLL('pageLocalizationDisplayMode', 1).':
						</td>
						<td style="vertical-align:middle;">
							<select onchange="document.location=' . htmlspecialchars($link) . '">
								'.implode(chr(10), $options).'
							</select>
						</td>
					</tr>
				';
			}
		}

		if ($this->pObj->rootElementLangMode !== 'disable') {
			$output.= '
				<tr class="bgColor4">
					<td  width="20">
						'. t3lib_BEfunc::cshItem('_MOD_web_txtemplavoilaM1', 'pagelocalizationmode', $this->doc->backPath) .'
					</td><td width="200" style="vertical-align:middle;">
						'.$LANG->getLL('pageLocalizationMode', 1).':
					</td>
					<td style="vertical-align:middle;"><em>'.$LANG->getLL('pageLocalizationMode_'.$this->pObj->rootElementLangMode, 1).($this->pObj->rootElementLangParadigm!='free'?(' / '.$LANG->getLL('pageLocalizationParadigm_'.$this->pObj->rootElementLangParadigm)):'').'</em></td>
				</tr>
			';
		}

			// enable/disable structure inheritance - see #7082 for details
		$adminOnlySetting = isset($this->pObj->modTSconfig['properties']['adminOnlyPageStructureInheritance']) ? $this->pObj->modTSconfig['properties']['adminOnlyPageStructureInheritance'] : 'strict';
		if (($GLOBALS['BE_USER']->isAdmin() || $adminOnlySetting === 'false') && $this->pObj->rootElementLangMode=='inheritance') {
			$link = '\'index.php?'.$this->pObj->link_getParameters().'&SET[disablePageStructureInheritance]='.($this->pObj->MOD_SETTINGS['disablePageStructureInheritance']=='1'?'0':'1').'\'';
			$output.= '
				<tr class="bgColor4">
					<td  width="20">
						'. t3lib_BEfunc::cshItem('_MOD_web_txtemplavoilaM1', 'disablePageStructureInheritance', $this->doc->backPath) .'
					</td><td width="200" style="vertical-align:middle;">
						'.$LANG->getLL ('pageLocalizationMode_inheritance.disableInheritance', 1).':
					</td>
					<td style="vertical-align:middle;">
						<input type="checkbox" onchange="document.location='.$link.'" '.($this->pObj->MOD_SETTINGS['disablePageStructureInheritance']=='1'?' checked="checked"':'').'/>
					</td>
				</tr>
			';
		}

		$output .= '
			<tr class="bgColor4">
				<td  width="20">
					'. t3lib_BEfunc::cshItem('_MOD_web_txtemplavoilaM1', 'editlanguageversion', $this->doc->backPath) .'
				</td><td width="200" style="vertical-align:middle;">
					'.$LANG->getLL ('editlanguageversion', 1).':
				</td>
				<td style="vertical-align:middle;">
					'.$availableTranslationsFlags.'
				</td>
			</tr>
		';

		return $output;
	}

	/**
	 * Renders the HTML code for a selectorbox for selecting a new translation language for the current
	 * page (create a new "Alternative Page Header".
	 *
	 * @return	mixed		HTML code for the selectorbox or FALSE if no new translation can be created.
	 * @access	protected
	 */
	function sidebar_renderItem_renderNewTranslationSelectorbox() {
		global $LANG, $BE_USER;

		if (!$GLOBALS['BE_USER']->isPSet($this->pObj->calcPerms, 'pages', 'edit')) {
			return false;
		}

		$newLanguagesArr = $this->pObj->getAvailableLanguages(0, true, false);
		if (count($newLanguagesArr) < 1) return FALSE;

		$translatedLanguagesArr = $this->pObj->getAvailableLanguages($this->pObj->id);
		$optionsArr = array ('<option value=""></option>');
		foreach ($newLanguagesArr as $language) {
			if ($BE_USER->checkLanguageAccess($language['uid']) && !isset($translatedLanguagesArr[$language['uid']])) {
				$flag = tx_templavoila_icons::getFlagIconFileForLanguage($language['flagIcon']);
				$style = isset ($language['flagIcon']) ? 'background-image: url(' . $flag . '); background-repeat: no-repeat; padding-top: 0px; padding-left: 22px;' : '';
				$optionsArr [] = '<option style="'.$style.'" name="createNewPageTranslation" value="'.$language['uid'].'">'.htmlspecialchars($language['title']).'</option>';
			}
		}

		if (count($optionsArr) > 1) {
			$linkParam = $this->pObj->rootElementTable == 'pages' ? '&doktype=' . $this->pObj->rootElementRecord['doktype'] : '';
			$link = 'index.php?'.$this->pObj->link_getParameters().'&createNewPageTranslation=\'+this.options[this.selectedIndex].value+\'&pid='.$this->pObj->id . $linkParam;
			$output = '
				<tr class="bgColor4">
					<td width="20">
						'. t3lib_BEfunc::cshItem('_MOD_web_txtemplavoilaM1', 'createnewtranslation', $this->doc->backPath) .'
					</td><td width="200" style="vertical-align:middle;">
						'.$LANG->getLL('createnewtranslation',1).':
					</td>
					<td style="vertical-align:middle;"><select onChange="document.location=\'' . htmlspecialchars($link) . '\'">' . implode ('', $optionsArr) . '</select></td>
				</tr>
			';
		}

		return $output;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/class.tx_templavoila_mod1_localization.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/class.tx_templavoila_mod1_localization.php']);
}

?>