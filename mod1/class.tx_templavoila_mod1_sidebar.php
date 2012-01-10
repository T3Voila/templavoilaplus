<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004, 2005  Robert Lemke (robert@typo3.org)
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
 * Submodule 'sidebar' for the templavoila page module
 *
 * $Id$
 *
 * @author     Robert Lemke <robert@typo3.org>
 */

/**
 * Submodule 'Sidebar' for the templavoila page module
 *
 * Note: This class is closely bound to the page module class and uses many variables and functions directly. After major modifications of
 *       the page module all functions of this sidebar should be checked to make sure that they still work.
 *
 * @author		Robert Lemke <robert@typo3.org>
 * @package		TYPO3
 * @subpackage	tx_templavoila
 */
class tx_templavoila_mod1_sidebar {

		// References to the page module object
	var $pObj;										// A pointer to the parent object, that is the templavoila page module script. Set by calling the method init() of this class.
	var $doc;										// A reference to the doc object of the parent object.
	var $extKey;									// A reference to extension key of the parent object.

		// Public variables
	var $position = 'toptabs';						// The visual position of the navigation bar. Possible values: toptabs, toprows, left

		// Local variables
	var $sideBarWidth = 180;						// More or less a constant: The side bar's total width in position "left"
	var $sideBarItems = array ();					// Contains menuitems for the dynamic sidebar (associative array indexed by item key)

	/**
	 * Initializes the side bar object. The calling class must make sure that the right locallang files are already loaded.
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

		$hideIfEmpty = $pObj->modTSconfig['properties']['showTabsIfEmpty'] ? FALSE : TRUE;

			// Register the locally available sidebar items. Additional items may be added by other extensions.
		if (t3lib_extMgm::isLoaded('version') && !t3lib_extMgm::isLoaded('workspaces')
			&& $GLOBALS['BE_USER']->check('modules', 'web_txversionM1')
		) {
			$this->sideBarItems['versioning'] = array(
				'object' => &$this,
				'method' => 'renderItem_versioning',
				'label' => $LANG->getLL('versioning'),
				'priority' => 60,
				'hideIfEmpty' => $hideIfEmpty,
			);
		}

		$this->sideBarItems['headerFields'] = array (
			'object' => &$this,
			'method' => 'renderItem_headerFields',
			'label' => $LANG->getLL('pagerelatedinformation'),
			'priority' => 50,
			'hideIfEmpty' => $hideIfEmpty,
		);

		$this->sideBarItems['advancedFunctions'] = array (
			'object' => &$this,
			'method' => 'renderItem_advancedFunctions',
			'label' => $LANG->getLL('advancedfunctions'),
			'priority' => 20,
			'hideIfEmpty' => $hideIfEmpty,
		);
	}

	/**
	 * Adds an item to the sidebar. You are encouraged to use this function from your own extension to extend the sidebar
	 * with new features. See the parameter descriptions for more details.
	 *
	 * @param	string		$itemKey: A unique identifier for your sidebar item. Generally use your extension key to make sure it is unique (eg. 'tx_templavoila_sidebar_item1').
	 * @param	object		$object: A reference to the instantiated class containing the method which renders the sidebar item (usually $this).
	 * @param	string		$method: Name of the method within your instantiated class which renders the sidebar item. Case sensitive!
	 * @param	string		$label: The label which will be shown for your item in the sidebar menu. Make sure that this label is localized!
	 * @param	integer		$priority: An integer between 0 and 100. The higher the value, the higher the item will be displayed in the sidebar. Default is 50
	 * @return	void
	 * @access	public
	 */
	function addItem($itemKey, &$object, $method, $label, $priority = 50, $hideIfEmpty = false) {
		$hideIfEmpty = $pObj->modTSconfig['properties']['showTabsIfEmpty'] ? FALSE : $hideIfEmpty;
		$this->sideBarItems[$itemKey] = array (
			'object' => $object,
			'method' => $method,
			'label' => $label,
			'priority' => $priority,
			'hideIfEmpty' => $hideIfEmpty
		);
	}

	/**
	 * Removes a certain item from the sidebar.
	 *
	 * @param	string		$itemKey: The key identifying the sidebar item.
	 * @return	void
	 * @access	public
	 */
	function removeItem($itemKey) {
		unset ($this->sideBarItems[$itemKey]);
	}

	/**
	 * Renders the sidebar and all its items.
	 *
	 * @return	string		HTML
	 * @access	public
	 */
	function render() {
		if (is_array ($this->sideBarItems) && count ($this->sideBarItems)) {
			uasort ($this->sideBarItems, array ($this, 'sortItemsCompare'));

				// sort and order the visible tabs
			$tablist = $this->pObj->modTSconfig['properties']['tabList'];
			if ($tablist) {
				$tabs = t3lib_div::trimExplode(',', $tablist);
				$finalSideBarItems = array();
				foreach ($tabs as $itemKey) {
					if (isset($this->sideBarItems[$itemKey])) {
						$finalSideBarItems[$itemKey] = $this->sideBarItems[$itemKey];
					}
				}
				$this->sideBarItems = $finalSideBarItems;
			}


				// Render content of each sidebar item:
			$index = 0;
			$numSortedSideBarItems = array();
			foreach ($this->sideBarItems as $itemKey => $sideBarItem) {
				$content = trim($sideBarItem['object']->{$sideBarItem['method']}($this->pObj));
				if (!$sideBarItem['hideIfEmpty'] || $content != '') {
					$numSortedSideBarItems[$index] = $this->sideBarItems[$itemKey];
					$numSortedSideBarItems[$index]['content'] = $content;
					$index++;
				}
			}

				// Create the whole sidebar:
			switch ($this->position) {
				case 'left':
					$minusIcon = t3lib_iconWorks::getSpriteIcon('actions-view-table-collapse');
					$plusIcon = t3lib_iconWorks::getSpriteIcon('actions-view-table-expand');
					$sideBar = '
						<!-- TemplaVoila Sidebar (left) begin -->

						<div id="tx_templavoila_mod1_sidebar-bar" style="height: 100%; width: '.$this->sideBarWidth.'px; margin: 0 4px 0 0; display:none;" class="bgColor-10">
							<div style="text-align:right;"><a href="#" onClick="tx_templavoila_mod1_sidebar_toggle();">' . $minusIcon . '</a></div>
							'.$this->doc->getDynTabMenu($numSortedSideBarItems, 'TEMPLAVOILA:pagemodule:sidebar', 1, true).'
						</div>
						<div id="tx_templavoila_mod1_sidebar-showbutton" style="height: 100%; width: 18px; margin: 0 4px 0 0; display:block; " class="bgColor-10">
							<a href="#" onClick="tx_templavoila_mod1_sidebar_toggle();">' . $plusIcon . '</a>
						</div>

						<script type="text/javascript">
						/*<![CDATA[*/

							tx_templavoila_mod1_sidebar_activate();

						/*]]>*/
						</script>

						<!-- TemplaVoila Sidebar end -->
					';
				break;

				case 'toprows':
					$sideBar = '
						<!-- TemplaVoila Sidebar (top) begin -->

						<div id="tx_templavoila_mod1_sidebar-bar" style="width:100%; margin-bottom: 10px;" class="bgColor-10">
							'.$this->doc->getDynTabMenu($numSortedSideBarItems, 'TEMPLAVOILA:pagemodule:sidebar', 1, true).'
						</div>

						<!-- TemplaVoila Sidebar end -->
					';
				break;

				case 'toptabs':
					$sideBar = '
						<!-- TemplaVoila Sidebar (top) begin -->

						<div id="tx_templavoila_mod1_sidebar-bar" style="width:100%;" class="bgColor-10">
							'.$this->doc->getDynTabMenu($numSortedSideBarItems, 'TEMPLAVOILA:pagemodule:sidebar', 1, FALSE, 100, 0, TRUE).'
						</div>

						<!-- TemplaVoila Sidebar end -->
					';
				break;

				default:
					$sideBar = '
						<!-- TemplaVoila Sidebar ERROR: Invalid position -->
					';
				break;
			}
			return $sideBar;
		}
		return FALSE;
	}





	/********************************************
	 *
	 * Render functions for the sidebar items
	 *
	 ********************************************/

	/**
	 * Renders the header fields menu item.
	 * It iss possible to define a list of fields (currently only from the pages table) which should appear
	 * as a header above the content zones while editing the content of a page. This function renders those fields.
	 * The fields to be displayed are defined in the page's datastructure.
	 *
	 * @param	$pObj:		Reference to the parent object ($this)
	 * @return	string		HTML output
	 * @access	private
	 */
	function renderItem_headerFields (&$pObj) {
		global $LANG, $TCA;

		$output = '';
		if ($pObj->rootElementTable != 'pages') return '';

		t3lib_div::loadTCA('pages');
		$conf = $TCA['pages']['columns']['tx_templavoila_flex']['config'];

		$dataStructureArr = t3lib_BEfunc::getFlexFormDS($conf, $pObj->rootElementRecord, 'pages');

		if (is_array($dataStructureArr) && is_array ($dataStructureArr['ROOT']['tx_templavoila']['pageModule'])) {
			$headerTablesAndFieldNames = t3lib_div::trimExplode(chr(10),str_replace(chr(13),'', $dataStructureArr['ROOT']['tx_templavoila']['pageModule']['displayHeaderFields']),1);
			if (is_array ($headerTablesAndFieldNames)) {
				$fieldNames = array();
				$headerFieldRows = array();
				$headerFields = array();

				foreach ($headerTablesAndFieldNames as $tableAndFieldName) {
					list ($table, $field) = explode ('.',$tableAndFieldName);
					$fieldNames[$table][] = $field;
					$headerFields[] = array (
						'table' => $table,
						'field' => $field,
						'label' => $LANG->sL(t3lib_BEfunc::getItemLabel('pages',$field)),
						'value' => t3lib_BEfunc::getProcessedValue('pages', $field, $pObj->rootElementRecord[$field],200)
					);
				}
				if (count($headerFields)) {
					foreach ($headerFields as $headerFieldArr) {
						if ($headerFieldArr['table'] == 'pages') {
							$onClick = t3lib_BEfunc::editOnClick('&edit[pages]['.$pObj->id.']=edit&columnsOnly='.implode (',',$fieldNames['pages']),$this->doc->backPath);
							$linkedValue = '<a style="text-decoration: none;" href="#" onclick="'.htmlspecialchars($onClick).'">'.htmlspecialchars($headerFieldArr['value']).'</a>';
							$linkedLabel = '<a style="text-decoration: none;" href="#" onclick="'.htmlspecialchars($onClick).'">'.htmlspecialchars($headerFieldArr['label']).'</a>';
							$headerFieldRows[] = '
								<tr>
									<td class="bgColor4-20" style="width: 10%; vertical-align:top">'.$linkedLabel.'</td><td class="bgColor4" style="vertical-align:top"><em>'.$linkedValue.'</em></td>
								</tr>
							';
						}
					}
					$output = '
						<table border="0" cellpadding="0" cellspacing="1" width="100%" class="lrPadding">
							<tr>
								<td colspan="2" class="bgColor4-20">'.$LANG->getLL('pagerelatedinformation').':</td>
							</tr>
							'.implode('',$headerFieldRows).'
						</table>
					';
				}
			}
		}

		return $output;
	}

	/**
	 * Renders the versioning sidebar item. Basically this is a copy from the template class.
	 *
	 * @param	object		&$pObj: Reference to the page object (the templavoila page module)
	 * @return	string		HTML output
	 * @access	public
	 */
	function renderItem_versioning(&$pObj) {
		if ($pObj->id > 0) {
			$versionSelector = trim($pObj->doc->getVersionSelector($pObj->id));
			if (!$versionSelector) {
				$onClick = 'jumpToUrl(\'' . $GLOBALS['BACK_PATH'] . t3lib_extMgm::extRelPath('version') . 'cm1/index.php?table=pages&uid=' . $pObj->id . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) . '\')';
				$versionSelector = '<input type="button" value="' . $GLOBALS['LANG']->getLL('sidebar_versionSelector_createVersion', 1) . '" onclick="' . htmlspecialchars($onClick) . '" />';
			}
			$tableRows = array ('
				<tr class="bgColor4-20">
					<th colspan="3">&nbsp;</th>
				</tr>
			');

			$tableRows[] = '
			<tr class="bgColor4">
				<td width="20">
					&nbsp;
				</td>
				<td colspan="9">' . $versionSelector . '</td>
			</tr>
			';

			return '<table border="0" cellpadding="0" cellspacing="1" class="lrPadding" width="100%">' . implode ('', $tableRows) . '</table>';
		}
	}

	/**
	 * Renders the "advanced functions" sidebar item.
	 *
	 * @param	object		&$pObj: Reference to the page object (the templavoila page module)
	 * @return	string		HTML output
	 * @access	public
	 */
	function renderItem_advancedFunctions(&$pObj) {
		global $LANG;

		$tableRows = array ('
			<tr class="bgColor4-20">
				<th colspan="3">&nbsp;</th>
			</tr>
		');

			// Render checkbox for showing hidden elements:
		$tableRows[] = '
			<tr class="bgColor4">
				<td width="20">
					'. t3lib_BEfunc::cshItem('_MOD_web_txtemplavoilaM1', 'advancedfunctions_showhiddenelements', $this->doc->backPath) .'
				</td><td width="200">
					' . $LANG->getLL('sidebar_advancedfunctions_labelshowhidden', 1) . ':
				</td>
				<td>' . t3lib_BEfunc::getFuncCheck($pObj->id, 'SET[tt_content_showHidden]', $pObj->MOD_SETTINGS['tt_content_showHidden'] !== '0', '', '') . '</td>
			</tr>
		';

			// Render checkbox for showing outline:
		if ($GLOBALS['BE_USER']->isAdmin() || $this->pObj->modTSconfig['properties']['enableOutlineForNonAdmin'])	{
			$tableRows[] = '
				<tr class="bgColor4">
					<td width="20">
						'. t3lib_BEfunc::cshItem('_MOD_web_txtemplavoilaM1', 'advancedfunctions_showoutline', $this->doc->backPath) .'
					</td><td width="200">
						' . $LANG->getLL('sidebar_advancedfunctions_labelshowoutline', 1) . ':
					</td>
					<td>'.t3lib_BEfunc::getFuncCheck($pObj->id,'SET[showOutline]',$pObj->MOD_SETTINGS['showOutline'],'','').'</td>
				</tr>
			';
		}

		return (count ($tableRows)) ? '<table border="0" cellpadding="0" cellspacing="1" class="lrPadding" width="100%">'.implode ('', $tableRows).'</table>' : '';
	}




	/********************************************
	 *
	 * Helper functions
	 *
	 ********************************************/

	/**
	 * Returns sidebar JS code.
	 *
	 * @return	string		JavaScript section for the HTML header.
	 */
	function getJScode()	{
		if ($this->position == 'left') {
			return '
				<script type="text/javascript">
				/*<![CDATA[*/

					function tx_templavoila_mod1_sidebar_activate ()	{	//
						if (top.tx_templavoila_mod1_sidebar_visible) {
							document.getElementById("tx_templavoila_mod1_sidebar-bar").style.display="none";
							document.getElementById("tx_templavoila_mod1_sidebar-showbutton").style.display="block";
						} else {
							document.getElementById("tx_templavoila_mod1_sidebar-bar").style.display="block";
							document.getElementById("tx_templavoila_mod1_sidebar-showbutton").style.display="none";
						}
					}

					function tx_templavoila_mod1_sidebar_toggle ()	{	//
						if (top.tx_templavoila_mod1_sidebar_visible) {
							top.tx_templavoila_mod1_sidebar_visible = false;
							this.tx_templavoila_mod1_sidebar_activate();
						} else {
							top.tx_templavoila_mod1_sidebar_visible = true;
							this.tx_templavoila_mod1_sidebar_activate();
						}
					}

				/*]]>*/
				</script>
			';
		} else {
			return '';
		}
	}

	/**
	 * Comparison callback function for sidebar items sorting
	 *
	 * @param	array		$a: Array A
	 * @param	array		$b: Array B
	 * @return	boolean
	 * @access	private
	 */
	function sortItemsCompare($a, $b) {
		return ($a['priority'] < $b['priority']);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/class.tx_templavoila_mod1_sidebar.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/class.tx_templavoila_mod1_sidebar.php']);
}

?>