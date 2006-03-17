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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   55: class tx_templavoila_mod1_localization
 *   69:     function init(&$pObj)
 *   90:     function sidebar_renderItem(&$pObj)
 *  115:     function sidebar_renderItem_renderTranslationInfoCheckbox()
 *  138:     function sidebar_renderItem_renderLanguageSelectorbox()
 *  186:     function sidebar_renderItem_renderNewTranslationSelectorbox()
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
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
		$this->pObj->sideBarObj->addItem('localization', $this, 'sidebar_renderItem', $LANG->getLL('localization', 1),60);
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

		$output = '
			<table border="0" cellpadding="0" cellspacing="1" width="100%" class="lrPadding">
				<tr class="bgColor4-20">
					<th colspan="2">&nbsp;</th>
				</tr>
				'.
			#	$this->sidebar_renderItem_renderTranslationInfoCheckbox().
				$this->sidebar_renderItem_renderLanguageSelectorbox().
				$this->sidebar_renderItem_renderNewTranslationSelectorbox().
				'
			</table>
		';
		return $output;
	}

	/**
	 * Renders the HTML code for a checkbox which switches display of localization (translation)
	 * information on and off.
	 *
	 * @return	mixed		HTML code for the checkbox or FALSE if no language is available.
	 * @access	protected
	 * @obsolete	OBSOLETE - can be removed when it is confirmed that this feature is not used. when so, remove this funciton, all calls to it plus occurencies of "SET[showTranslationInfo]" in TemplaVoila code.	
	 */
	function sidebar_renderItem_renderTranslationInfoCheckbox() {
		global $LANG;

		$availableLanguagesArr = $this->pObj->translatedLanguagesArr;
		if (count($availableLanguagesArr) <= 1) return FALSE;

		$output = '
			<tr class="bgColor4">
				<td width="1%" nowrap="nowrap">'.$LANG->getLL ('sidebar_renderitem_showtranslationinformation', 1).':</td>
				<td>'.t3lib_BEfunc::getFuncCheck($this->pObj->id,'SET[showTranslationInfo]',$this->pObj->MOD_SETTINGS['showTranslationInfo'],'','').'</td>
			</tr>
		';

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
		$newLanguagesArr = $this->pObj->getAvailableLanguages(0, true, false);
		if (count($availableLanguagesArr) <= 1) return FALSE;

		$optionsArr = array ();
		foreach ($availableLanguagesArr as $languageArr) {
			unset($newLanguagesArr[$languageArr['uid']]);	// Remove this language from possible new translation languages array (PNTLA ;-)

			if ($languageArr['uid']<=0 || $BE_USER->checkLanguageAccess($languageArr['uid']))	{

				$flag = ($languageArr['flagIcon'] != '' ? $languageArr['flagIcon'] : $BACK_PATH . 'gfx/flags/unknown.gif');
				$style = isset ($languageArr['flagIcon']) ? 'background-image: url(' . $flag . '); background-repeat: no-repeat; padding-left: 22px;' : '';
				$optionsArr [] = '<option style="'.$style.'" value="'.$languageArr['uid'].'"'.($this->pObj->MOD_SETTINGS['language'] == $languageArr['uid'] ? ' selected="selected"' : '').'>'.htmlspecialchars($languageArr['title']).'</option>';

					// Link to editing of language header:
				$availableTranslationsFlags .= '<a href="index.php?'.$this->pObj->link_getParameters().'&editPageLanguageOverlay='.$languageArr['uid'].'"><img src="' . $flag . '" title="Edit '.htmlspecialchars($languageArr['title']).'" alt="" /></a> ';
			}
		}

		$link = '\'index.php?'.$this->pObj->link_getParameters().'&SET[language]=\'+this.options[this.selectedIndex].value';

		$output.= '
			<tr class="bgColor4">
				<td width="1%" nowrap="nowrap">
					'. t3lib_BEfunc::cshItem('_MOD_web_txtemplavoilaM1', 'selectlanguageversion', $this->doc->backPath) .'
					'.$LANG->getLL ('selectlanguageversion', 1).':
				</td>
				<td><select onchange="document.location='.$link.'">'.implode ('', $optionsArr).'</select></td>
			</tr>
			<tr class="bgColor4">
				<td width="1%" nowrap="nowrap">
					'. t3lib_BEfunc::cshItem('_MOD_web_txtemplavoilaM1', 'editlanguageversion', $this->doc->backPath) .'
					'.$LANG->getLL ('editlanguageversion', 1).':
				</td>
				<td>
					'.$availableTranslationsFlags.'
				</td>
			</tr>
		';

		if ($this->pObj->rootElementLangMode !== 'disable') {
			$output.= '
				<tr class="bgColor4">
					<td width="1%" nowrap="nowrap">
						'. t3lib_BEfunc::cshItem('_MOD_web_txtemplavoilaM1', 'pagelocalizationmode', $this->doc->backPath) .'						
						'.$LANG->getLL('pageLocalizationMode', 1).': 
					</td>
					<td><em>'.$LANG->getLL('pageLocalizationMode_'.$this->pObj->rootElementLangMode, 1).'</em></td>
				</tr>
			';				
		} 
		
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

		$newLanguagesArr = $this->pObj->getAvailableLanguages(0, true, false);
		if (count($newLanguagesArr) < 1) return FALSE;

		$translatedLanguagesArr = $this->pObj->getAvailableLanguages($this->pObj->id);
		$optionsArr = array ('<option value=""></option>');
		foreach ($newLanguagesArr as $language) {
			if ($BE_USER->checkLanguageAccess($language['uid']) && !isset($translatedLanguagesArr[$language['uid']])) {
				$style = isset ($language['flagIcon']) ? 'background-image: url('.$language['flagIcon'].'); background-repeat: no-repeat; padding-top: 0px; padding-left: 22px;' : '';
				$optionsArr [] = '<option style="'.$style.'" name="createNewPageTranslation" value="'.$language['uid'].'">'.htmlspecialchars($language['title']).'</option>';
			}
		}

		if (count($optionsArr) > 1) {
			$link = 'index.php?'.$this->pObj->link_getParameters().'&createNewPageTranslation=\'+this.options[this.selectedIndex].value+\'&pid='.$this->pObj->id;
			$output = '
				<tr class="bgColor4">
					<td width="1%" nowrap="nowrap">'.$LANG->getLL('createnewtranslation',1).':</td>
					<td style="padding:4px;"><select onChange="document.location=\''.$link.'\'">'.implode ('', $optionsArr).'</select></td>
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