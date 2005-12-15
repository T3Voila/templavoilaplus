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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   66: class tx_templavoila_mod1_sidebar
 *   88:     function init(&$pObj)
 *  133:     function addItem($itemKey, &$object, $method, $label, $priority=50)
 *  149:     function removeItem($itemKey)
 *  159:     function render()
 *
 *              SECTION: Render functions for the sidebar items
 *  252:     function renderItem_headerFields (&$pObj)
 *  309:     function renderItem_versioning(&$pObj)
 *  387:     function renderItem_advancedFunctions(&$pObj)
 *
 *              SECTION: Helper functions
 *  433:     function getJScode()
 *  475:     function sortItemsCompare($a, $b)
 *
 * TOTAL FUNCTIONS: 9
 * (This index is automatically created/updated by the extension "extdeveval")
 *
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
	 * @access public
	 */
	function init(&$pObj) {
		global $LANG;

			// Make local reference to some important variables:
		$this->pObj =& $pObj;
		$this->doc =& $this->pObj->doc;
		$this->extKey =& $this->pObj->extKey;

			// Register the locally available sidebar items. Additional items may be added by other extensions.
		if (t3lib_extMgm::isLoaded('version'))	{
			$this->sideBarItems['versioning'] = array (
				'object' => &$this,
				'method' => 'renderItem_versioning',
				'label' => $LANG->getLL('versioning'),
				'priority' => 60,
			);
		}

		$this->sideBarItems['headerFields'] = array (
			'object' => &$this,
			'method' => 'renderItem_headerFields',
			'label' => $LANG->getLL('pagerelatedinformation'),
			'priority' => 50,
		);

		$this->sideBarItems['advancedFunctions'] = array (
			'object' => &$this,
			'method' => 'renderItem_advancedFunctions',
			'label' => $LANG->getLL('advancedfunctions'),
			'priority' => 20,
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
	 * @access public
	 */
	function addItem($itemKey, &$object, $method, $label, $priority=50) {
		$this->sideBarItems[$itemKey] = array (
			'object' => $object,
			'method' => $method,
			'label' => $label,
			'priority' => $priority,
		);
	}

	/**
	 * Removes a certain item from the sidebar.
	 *
	 * @param	string		$itemKey: The key identifying the sidebar item.
	 * @return	void
	 * @access public
	 */
	function removeItem($itemKey) {
		unset ($this->sideBarItems[$itemKey]);
	}

	/**
	 * Renders the sidebar and all its items.
	 *
	 * @return	string		HTML
	 * @access public
	 */
	function render() {
		if (is_array ($this->sideBarItems) && count ($this->sideBarItems)) {
			uasort ($this->sideBarItems, array ($this, 'sortItemsCompare'));

				// Render content of each sidebar item:
			$index = 0;
			$numSortedSideBarItems = array();
			foreach ($this->sideBarItems as $itemKey => $sideBarItem) {
				$numSortedSideBarItems[$index] = $this->sideBarItems[$itemKey];
				$numSortedSideBarItems[$index]['content'] = $sideBarItem['object']->{$sideBarItem['method']}($this->pObj);
				$index++;
			}

				// Create the whole sidebar:
			switch ($this->position) {
				case 'left':
					$sideBar = '
						<!-- TemplaVoila Sidebar (left) begin -->

						<div id="tx_templavoila_mod1_sidebar-bar" style="height: 100%; width: '.$this->sideBarWidth.'px; margin: 0 4px 0 0; display:none;" class="bgColor-10">
							<div style="text-align:right;"><a href="#" onClick="tx_templavoila_mod1_sidebar_toggle();"><img '.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/minusbullet_list.gif','').' title="" alt="" /></a></div>
							'.$this->doc->getDynTabMenu($numSortedSideBarItems, 'TEMPLAVOILA:pagemodule:sidebar', 1, true).'
						</div>
						<div id="tx_templavoila_mod1_sidebar-showbutton" style="height: 100%; width: 18px; margin: 0 4px 0 0; display:block; " class="bgColor-10">
							<a href="#" onClick="tx_templavoila_mod1_sidebar_toggle();"><img '.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/plusbullet_list.gif','').' title="" alt="" /></a>
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

						<div id="tx_templavoila_mod1_sidebar-bar" style="width:100%; border-bottom: 1px solid black; margin-bottom: 10px;" class="bgColor-10">
							'.$this->doc->getDynTabMenu($numSortedSideBarItems, 'TEMPLAVOILA:pagemodule:sidebar', 1, FALSE, 100, 0, TRUE).'
						</div>

						<!-- TemplaVoila Sidebar end -->
					';
				break;

				default:
					$sidebar = '
						<!-- TemplaVoila Sidebar ERROR: Invalid position -->
					';
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
	 * @access private
	 */
	function renderItem_headerFields (&$pObj) {
		global $LANG;

		$output = '';

		if (is_array ($pObj->currentDataStructureArr['pages']['ROOT']['tx_templavoila']['pageModule'])) {
			$headerTablesAndFieldNames = t3lib_div::trimExplode(chr(10),str_replace(chr(13),'', $pObj->currentDataStructureArr['pages']['ROOT']['tx_templavoila']['pageModule']['displayHeaderFields']),1);
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
						'value' => t3lib_BEfunc::getProcessedValue('pages', $field, $pObj->currentPageRecord[$field],200)
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
	 * @access public
	 */
	function renderItem_versioning(&$pObj) {

		if ($pObj->id>0) {

				// Get Current page record:
			$curPage = t3lib_BEfunc::getRecord('pages',$pObj->id);
				// If the selected page is not online, find the right ID
			$onlineId = ($curPage['pid']==-1 ? $curPage['t3ver_oid'] : $pObj->id);
				// Select all versions of online version:
			$versions = t3lib_BEfunc::selectVersionsOfRecord('pages', $onlineId, 'uid,pid,t3ver_label,t3ver_oid,t3ver_id');

				// If more than one was found...:
			if (count($versions)>1)	{

					// Create selector box entries:
				$opt = array();
				foreach($versions as $vRow)	{
					$opt[] = '<option value="'.htmlspecialchars(t3lib_div::linkThisScript(array('id'=>$vRow['uid']))).'"'.($pObj->id==$vRow['uid']?' selected="selected"':'').'>'.
							htmlspecialchars($vRow['t3ver_label'].' [v#'.$vRow['t3ver_id'].']'.($vRow['uid']==$onlineId ? ' =>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:ver.online').'<=':'')).
							'</option>';
				}

					// Add management link:
				$opt[] = '<option value="'.htmlspecialchars(t3lib_div::linkThisScript(array('id'=>$pObj->id))).'">---</option>';
				$opt[] = '<option value="'.htmlspecialchars($pObj->doc->backPath.t3lib_extMgm::extRelPath('version').'cm1/index.php?table=pages&uid='.$onlineId).'">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:ver.mgm',1).'</option>';

					// Create onchange handler:
				$onChange = "document.location=this.options[this.selectedIndex].value;";

					// Controls:
				if ($pObj->id==$onlineId)	{
					$controls = '<img'.t3lib_iconWorks::skinImg($pObj->doc->backPath,'gfx/blinkarrow_left.gif','width="5" height="9"').' class="absmiddle" alt="" /> <b>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:ver.online',1).'</b>';
				} else {
					$controls = '<a href="'.$pObj->doc->issueCommand('&cmd[pages]['.$onlineId.'][version][swapWith]='.$pObj->id.'&cmd[pages]['.$onlineId.'][version][action]=swap&cmd[pages]['.$onlineId.'][version][swapContent]=1',t3lib_div::linkThisScript(array('id'=>$onlineId))).'">'.
							'<img'.t3lib_iconWorks::skinImg($pObj->doc->backPath,'gfx/insert2.gif','width="14" height="14"').' style="margin-right: 2px;" class="absmiddle" alt="" title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:ver.swapPage',1).'" />'.
							'<b>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:ver.swap',1).'</b></a>';
				}

					// Write out HTML code:
				switch ($this->position) {
					case 'left':
						return '
							<table border="0" cellpadding="0" cellspacing="1" width="100%" class="lrPadding">
								<tr class="bgColor4-20">
									<td colspan="2">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:ver.selVer',1).'</td>
								</tr>
								<tr class="bgColor4">
									<td><select onchange="'.htmlspecialchars($onChange).'">'.implode('',$opt).'</select></td>
								</tr>
								<tr class="bgColor4">
									<td>'.$controls.'</td>
								</tr>
							</table>
						';

					default:
						return '
							<table border="0" cellpadding="0" cellspacing="1" width="100%" class="lrPadding">
								<tr class="bgColor4-20">
									<td colspan="2">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:ver.selVer',1).'</td>
								</tr>
								<tr class="bgColor4">
									<td><select onchange="'.htmlspecialchars($onChange).'">'.implode('',$opt).'</select> '.$controls.'</td>
								</tr>
							</table>
						';
				}
			}
		}
	}

	/**
	 * Renders the "advanced functions" sidebar item.
	 *
	 * @param	object		&$pObj: Reference to the page object (the templavoila page module)
	 * @return	string		HTML output
	 * @access public
	 */
	function renderItem_advancedFunctions(&$pObj) {
		global $LANG;

		$tableRows = array ('
			<tr class="bgColor4-20">
				<th colspan="2">&nbsp;</th>
			</tr>
		');

			// Render checkbox for showing hidden elements:
		$tableRows[] = '
			<tr class="bgColor4">
				<td width="1%" nowrap="nowrap">'.$LANG->getLL('sidebar_advancedfunctions_labelshowhidden', 1).':</td>
				<td>'.t3lib_BEfunc::getFuncCheck($pObj->id,'SET[tt_content_showHidden]',$pObj->MOD_SETTINGS['tt_content_showHidden'],'','').'</td>
			</tr>
		';

			// Render cache menu:
		if ($pObj->id>0) {
			$cshItem = t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'TCEforms_cacheSelector', $GLOBALS['BACK_PATH'],'', TRUE);
			$cacheMenu = $this->doc->clearCacheMenu(intval($this->id), FALSE);
			$tableRows[] = '
				<tr class="bgColor4">
					<td nowrap="nowrap">'.$cshItem.' '.$LANG->getLL('sidebar_advancedfunctions_labelcachefunctions', 1).':</td>
					<td>'.$cacheMenu.'</td>
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
	 * @access private
	 */
	function sortItemsCompare($a, $b) {
		return ($a['priority'] < $b['priority']);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/class.tx_templavoila_mod1_sidebar.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/class.tx_templavoila_mod1_sidebar.php']);
}

?>