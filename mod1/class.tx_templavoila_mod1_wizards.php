<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2006  Robert Lemke (robert@typo3.org)
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
 * Submodule 'wizards' for the templavoila page module
 *
 * $Id$
 *
 * @author     Robert Lemke <robert@typo3.org>
 */

/**
 * Submodule 'Wizards' for the templavoila page module
 *
 * Note: This class is closely bound to the page module class and uses many variables and functions directly. After major modifications of
 *       the page module all functions of this wizard class should be checked to make sure that they still work.
 *
 * @author		Robert Lemke <robert@typo3.org>
 * @package		TYPO3
 * @subpackage	tx_templavoila
 */

require_once(PATH_t3lib.'class.t3lib_tceforms.php');

class tx_templavoila_mod1_wizards {

		// References to the page module object
	var $pObj;										// A pointer to the parent object, that is the templavoila page module script. Set by calling the method init() of this class.
	var $doc;										// A reference to the doc object of the parent object.
	var $extKey;									// A reference to extension key of the parent object.
	var $TCAdefaultOverride;						// Config of TCAdefaults

		// Local variables

	/**
	 * Initializes the wizards object. The calling class must make sure that the right locallang files are already loaded.
	 * This method is usually called by the templavoila page module.
	 *
	 * @param	$pObj:		Reference to the parent object ($this)
	 * @return	void
	 */
	function init(&$pObj) {
			// Make local reference to some important variables:
		$this->pObj =& $pObj;
		$this->doc =& $this->pObj->doc;
		$this->extKey =& $this->pObj->extKey;
		$this->apiObj =& $this->pObj->apiObj;
	}





	/********************************************
	 *
	 * Wizards render functions
	 *
	 ********************************************/

	/**
	 * Creates the screen for "new page wizard"
	 *
	 * @param	integer		$positionPid: Can be positive and negative depending of where the new page is going: Negative always points to a position AFTER the page having the abs. value of the positionId. Positive numbers means to create as the first subpage to another page.
	 * @return	string		Content for the screen output.
	 * @todo				Check required field(s), support t3d
	 */
    function renderWizard_createNewPage ($positionPid) {
		global $LANG, $BE_USER, $TYPO3_CONF_VARS;

			// Get default TCA values specific for the page and user
		$temp = t3lib_BEfunc::getModTSconfig(abs($positionPid) , 'TCAdefaults');
		if (isset($temp['properties'])) {
			$this->TCAdefaultOverride = $temp['properties'];
		}

			// The user already submitted the create page form:
		if (t3lib_div::_GP('doCreate') || isset($this->TCAdefaultOverride['pages.']['tx_templavoila_to'])) {

				// Check if the HTTP_REFERER is valid
			$refInfo = parse_url(t3lib_div::getIndpEnv('HTTP_REFERER'));
			$httpHost = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
			if ($httpHost == $refInfo['host'] || t3lib_div::_GP('vC') == $BE_USER->veriCode() || $TYPO3_CONF_VARS['SYS']['doNotCheckReferer'])	{

					// Create new page
				$newID = $this->createPage (t3lib_div::_GP('data'), $positionPid);
				if ($newID > 0) {

						// Get TSconfig for a different selection of fields in the editing form
					$TSconfig = t3lib_BEfunc::getModTSconfig($newID, 'mod.web_txtemplavoilaM1.createPageWizard.fieldNames');
					$fieldNames = trim(isset ($TSconfig['value']) ? $TSconfig['value'] : 'hidden,title,alias');
					$columnsOnly = '';
					if ($fieldNames !== '*') {
						$columnsOnly = '&columnsOnly='.rawurlencode($fieldNames);
					}

						// Create parameters and finally run the classic page module's edit form for the new page:
					$params = '&edit[pages]['.$newID.']=edit' . $columnsOnly;
					$returnUrl = rawurlencode(t3lib_div::getIndpEnv('SCRIPT_NAME').'?id='.$newID.'&updatePageTree=1');

					header('Location: '.t3lib_div::locationHeaderUrl($this->doc->backPath.'alt_doc.php?returnUrl='.$returnUrl.$params));
					exit();
				} else { debug('Error: Could not create page!'); }
			} else { debug('Error: Referer host did not match with server host.'); }
		}

			// Based on t3d/xml templates:
		if (false != ($templateFile = t3lib_div::_GP('templateFile'))) {

			if (t3lib_div::getFileAbsFileName($templateFile) && @is_file($templateFile))	{

					// First, find positive PID for import of the page:
				$importPID = t3lib_BEfunc::getTSconfig_pidValue('pages','',$positionPid);

					// Initialize the import object:
				$import = $this->getImportObject();
				if ($import->loadFile($templateFile, 1))	{
						// Find the original page id:
					$origPageId = key($import->dat['header']['pagetree']);

						// Perform import of content
					$import->importData($importPID);

						// Find the new page id (root page):
					$newID = $import->import_mapId['pages'][$origPageId];

					if ($newID)	{
							// If the page was destined to be inserted after another page, move it now:
						if ($positionPid<0)	{
							$cmd = array();
							$cmd['pages'][$newID]['move'] = $positionPid;
							$tceObject = $import->getNewTCE();
							$tceObject->start(array(),$cmd);
							$tceObject->process_cmdmap();
						}

						// PLAIN COPY FROM ABOVE - BEGIN
							// Get TSconfig for a different selection of fields in the editing form
						$TSconfig = t3lib_BEfunc::getModTSconfig($newID, 'tx_templavoila.mod1.createPageWizard.fieldNames');
						$fieldNames = isset ($TSconfig['value']) ? $TSconfig['value'] : 'hidden,title,alias';

							// Create parameters and finally run the classic page module's edit form for the new page:
						$params = '&edit[pages]['.$newID.']=edit&columnsOnly='.rawurlencode($fieldNames);
						$returnUrl = rawurlencode(t3lib_div::getIndpEnv('SCRIPT_NAME').'?id='.$newID.'&updatePageTree=1');

						header('Location: '.t3lib_div::locationHeaderUrl($this->doc->backPath.'alt_doc.php?returnUrl='.$returnUrl.$params));
						exit();

						// PLAIN COPY FROM ABOVE - END
					} else { debug('Error: Could not create page!'); }
				}
			}
		}
			// Start assembling the HTML output

		$this->doc->form='<form action="'.htmlspecialchars('index.php?id='.$this->pObj->id).'" method="post" autocomplete="off" enctype="'.$TYPO3_CONF_VARS['SYS']['form_enctype'].'" onsubmit="return TBE_EDITOR_checkSubmit(1);">';
 		$this->doc->divClass = '';
		$this->doc->getTabMenu(0,'_',0,array(''=>''));

			// init tceforms for javascript printing
		$tceforms = t3lib_div::makeInstance('t3lib_TCEforms');
		$tceforms->initDefaultBEMode();
		$tceforms->backPath = $GLOBALS['BACK_PATH'];
		$tceforms->doSaveFieldName = 'doSave';

			// Setting up the context sensitive menu:
		$CMparts = $this->doc->getContextMenuCode();
		$this->doc->JScode.= $CMparts[0] . $tceforms->printNeededJSFunctions_top();
		$this->doc->bodyTagAdditions = $CMparts[1];
		$this->doc->postCode.= $CMparts[2] . $tceforms->printNeededJSFunctions();

			// fix due to #13762
		$this->doc->inDocStyles .= '.c-inputButton{ cursor:pointer; }';

		$content.=$this->doc->header($LANG->sL('LLL:EXT:lang/locallang_core.xml:db_new.php.pagetitle'));
		$content.=$this->doc->startPage($LANG->getLL ('createnewpage_title'));

			// Add template selectors
		$tmplSelectorCode = '';
		$tmplSelector = $this->renderTemplateSelector ($positionPid,'tmplobj');
		if ($tmplSelector) {
#			$tmplSelectorCode.='<em>'.$LANG->getLL ('createnewpage_templateobject_createemptypage').'</em>';
			$tmplSelectorCode.=$this->doc->spacer(5);
			$tmplSelectorCode.=$tmplSelector;
			$tmplSelectorCode.=$this->doc->spacer(10);
		}

		$tmplSelector = $this->renderTemplateSelector ($positionPid,'t3d');
		if ($tmplSelector) {
			#$tmplSelectorCode.='<em>'.$LANG->getLL ('createnewpage_templateobject_createpagewithdefaultcontent').'</em>';
			$tmplSelectorCode.=$this->doc->spacer(5);
			$tmplSelectorCode.=$tmplSelector;
			$tmplSelectorCode.=$this->doc->spacer(10);
		}

		if ($tmplSelectorCode) {
			$content.='<h3>'.htmlspecialchars($LANG->getLL ('createnewpage_selecttemplate')).'</h3>';
			$content.=$LANG->getLL ('createnewpage_templateobject_description');
			$content.=$this->doc->spacer(10);
			$content.=$tmplSelectorCode;
		}

		$content .= '<input type="hidden" name="positionPid" value="'.$positionPid.'" />';
		$content .= '<input type="hidden" name="doCreate" value="1" />';
		$content .= '<input type="hidden" name="cmd" value="crPage" />';

		$content .= $this->doc->endPage();
		return $content;
	}





	/********************************************
	 *
	 * Wizard related helper functions
	 *
	 ********************************************/

	/**
	 * Renders the template selector.
	 *
	 * @param	integer		Position id. Can be positive and negative depending of where the new page is going: Negative always points to a position AFTER the page having the abs. value of the positionId. Positive numbers means to create as the first subpage to another page.
	 * @param	string		$templateType: The template type, 'tmplobj' or 't3d'
	 * @return	string		HTML output containing a table with the template selector
	 */
	function renderTemplateSelector ($positionPid, $templateType='tmplobj') {
		global $LANG, $TYPO3_DB;

		$storageFolderPID = $this->apiObj->getStorageFolderPid($positionPid);
		$tmplHTML = array();
		$defaultIcon = $this->doc->backPath . '../' . t3lib_extMgm::siteRelPath($this->extKey) . 'res1/default_previewicon.gif';

			// look for TCEFORM.pages.tx_templavoila_ds.removeItems / TCEFORM.pages.tx_templavoila_to.removeItems
		$disallowedPageTemplateItems = $this->getDisallowedTSconfigItemsByFieldName ($positionPid, 'tx_templavoila_ds');
		$disallowedDesignTemplateItems = $this->getDisallowedTSconfigItemsByFieldName ($positionPid, 'tx_templavoila_to');

		switch ($templateType) {
			case 'tmplobj':
					// Create the "Default template" entry
					//Fetch Default TO
				$fakeRow = array('uid' => abs($positionPid));
				$defaultTO = $this->pObj->apiObj->getContentTree_fetchPageTemplateObject($fakeRow);

					// Create the "Default template" entry
				if ($defaultTO['previewicon']) {
					$previewIconFilename = (@is_file(PATH_site . 'uploads/tx_templavoila/' . $defaultTO['previewicon'])) ? ($GLOBALS['BACK_PATH'] . '../' . 'uploads/tx_templavoila/' . $defaultTO['previewicon']) : $defaultIcon;
				} else {
					$previewIconFilename = $defaultIcon;
				}

				$previewIcon = '<input type="image" class="c-inputButton" name="i0" value="0" src="' . $previewIconFilename . '" title="" />';
				$description = $defaultTO['description'] ? htmlspecialchars($defaultTO['description']) : $LANG->getLL ('template_descriptiondefault', 1);
				$tmplHTML [] = '<table style="float:left; width: 100%;" valign="top">
				<tr>
					<td colspan="2" nowrap="nowrap">
						<h3 class="bgColor3-20">' . htmlspecialchars($LANG->getLL ('template_titleInherit')) . '</h3>
					</td>
				</tr><tr>
					<td valign="top">' . $previewIcon . '</td>
					<td width="120" valign="top">
						<p><h4>' . htmlspecialchars($LANG->sL($defaultTO['title'])) . '</h4>' . $LANG->sL($description) . '</p>
					</td>
				</tr>
				</table>';

				$dsRepo = t3lib_div::makeInstance('tx_templavoila_datastructureRepository');
				$toRepo = t3lib_div::makeInstance('tx_templavoila_templateRepository');
				$dsList = $dsRepo->getDatastructuresByStoragePidAndScope($storageFolderPID, tx_templavoila_datastructure::SCOPE_PAGE);
				foreach($dsList as $dsObj) {
					if (t3lib_div::inList($disallowedPageTemplateItems, $dsObj->getKey()) ||
						!$dsObj->isPermittedForUser()
					) {
						continue;
					}

					$toList = $toRepo->getTemplatesByDatastructure($dsObj, $storageFolderPID);
					foreach ($toList as $toObj) {
						if ($toObj->getKey() === $defaultTO['uid'] ||
							!$toObj->isPermittedForUser() ||
							t3lib_div::inList($disallowedDesignTemplateItems, $toObj->getKey())
						) {
							continue;
						}

						$tmpFilename = $toObj->getIcon();
						$previewIconFilename = (@is_file(PATH_site . substr($tmpFilename, 3))) ? ($GLOBALS['BACK_PATH'] . $tmpFilename) : $defaultIcon;
							// Note: we cannot use value of image input element because MSIE replaces this value with mouse coordinates! Thus on click we set value to a hidden field. See http://bugs.typo3.org/view.php?id=3376
						$previewIcon = '<input type="image" class="c-inputButton" name="i' .$row['uid'] . '" onclick="document.getElementById(\'data_tx_templavoila_to\').value=' . $toObj->getKey() . '" src="'.$previewIconFilename.'" title="" />';
						$description = $toObj->getDescription() ? htmlspecialchars($toObj->getDescription()) : $LANG->getLL ('template_nodescriptionavailable');
						$tmplHTML [] = '<table style="width: 100%;" valign="top"><tr><td colspan="2" nowrap="nowrap"><h3 class="bgColor3-20">' . htmlspecialchars($toObj->getLabel()) . '</h3></td></tr>'.
							'<tr><td valign="top">' . $previewIcon . '</td><td width="120" valign="top"><p>' . $LANG->sL($description) . '</p></td></tr></table>';
			 		}
				}
				$tmplHTML[] = '<input type="hidden" id="data_tx_templavoila_to" name="data[tx_templavoila_to]" value="0" />';
				break;

			case 't3d':
				if (t3lib_extMgm::isLoaded('impexp'))	{

						// Read template files from a certain folder. I suggest this is configurable in some way. But here it is hardcoded for initial tests.
					$templateFolder = PATH_site.$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'].'/export/templates/';
					$files = t3lib_div::getFilesInDir($templateFolder,'t3d,xml',1,1);

						// Traverse the files found:
					foreach($files as $absPath)	{
							// Initialize the import object:
						$import = $this->getImportObject();
						if ($import->loadFile($absPath))	{
							if (is_array($import->dat['header']['pagetree']))	{	// This means there are pages in the file, we like that...:

									// Page tree:
								reset($import->dat['header']['pagetree']);
								$pageTree = current($import->dat['header']['pagetree']);


									// Thumbnail icon:
								if (is_array($import->dat['header']['thumbnail']))	{
									$pI = pathinfo($import->dat['header']['thumbnail']['filename']);
									if (t3lib_div::inList('gif,jpg,png,jpeg',strtolower($pI['extension'])))	{

											// Construct filename and write it:
										$fileName = PATH_site.
													'typo3temp/importthumb_'.t3lib_div::shortMD5($absPath).'.'.$pI['extension'];
										t3lib_div::writeFile($fileName, $import->dat['header']['thumbnail']['content']);

											// Check that the image really is an image and not a malicious PHP script...
										if (getimagesize($fileName))	{
												// Create icon tag:
											$iconTag = '<img src="'.$this->doc->backPath.'../'.substr($fileName,strlen(PATH_site)).'" '.$import->dat['header']['thumbnail']['imgInfo'][3].' vspace="5" style="border: solid black 1px;" alt="" />';
										} else {
											t3lib_div::unlink_tempfile($fileName);
											$iconTag = '';
										}
									}
								}

								$aTagB = '<a href="'.htmlspecialchars(t3lib_div::linkThisScript(array('templateFile' => $absPath))).'">';
								$aTagE = '</a>';
								$tmplHTML [] = '<table style="float:left; width: 100%;" valign="top"><tr><td colspan="2" nowrap="nowrap">
					<h3 class="bgColor3-20">'.$aTagB.htmlspecialchars($import->dat['header']['meta']['title'] ? $import->dat['header']['meta']['title'] : basename($absPath)).$aTagE.'</h3></td></tr>
					<tr><td valign="top">'.$aTagB.$iconTag.$aTagE.'</td><td valign="top"><p>'.htmlspecialchars($import->dat['header']['meta']['description']).'</p>
						<em>Levels: '.(count($pageTree)>1 ? 'Deep structure' : 'Single page').'<br/>
						File: '.basename($absPath).'</em></td></tr></table>';

							}
						}
					}
				}
				break;

		}

		if (is_array($tmplHTML) && count($tmplHTML)) {
			$counter = 0;
			$content .= '<table>';
			foreach ($tmplHTML as $single) {
				$content .= ($counter ? '':'<tr>').'<td valign="top">'.$single.'</td>'.($counter ? '</tr>':'');
				$counter ++;
				if ($counter > 1) { $counter = 0; }
			}
			$content .= '</table>';
		}

		return $content;
	}

	/**
	 * Performs the neccessary steps to creates a new page
	 *
	 * @param	array		$pageArray: array containing the fields for the new page
	 * @param	integer		$positionPid: location within the page tree (parent id)
	 * @return	integer		uid of the new page record
	 */
	function createPage($pageArray,$positionPid)	{
		$dataArr = array();
		$dataArr['pages']['NEW'] = $pageArray;
		$dataArr['pages']['NEW']['pid'] = $positionPid;
		if (is_null($dataArr['pages']['NEW']['hidden'])) {
			$dataArr['pages']['NEW']['hidden'] = 0;
		}
		unset($dataArr['pages']['NEW']['uid']);

			// If no data structure is set, try to find one by using the template object
		if ($dataArr['pages']['NEW']['tx_templavoila_to'] && !$dataArr['pages']['NEW']['tx_templavoila_ds']) {
			$templateObjectRow = t3lib_BEfunc::getRecordWSOL('tx_templavoila_tmplobj',$dataArr['pages']['NEW']['tx_templavoila_to'],'uid,pid,datastructure');
			$dataArr['pages']['NEW']['tx_templavoila_ds'] = $templateObjectRow['datastructure'];
		}

		$tce = t3lib_div::makeInstance('t3lib_TCEmain');

		if (is_array($this->TCAdefaultOverride))	{
			$tce->setDefaultsFromUserTS($this->TCAdefaultOverride);
		}

		$tce->stripslashes_values=0;
		$tce->start($dataArr,array());
		$tce->process_datamap();

		return $tce->substNEWwithIDs['NEW'];
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getImportObject()	{
		global $TYPO3_CONF_VARS;

		require_once (t3lib_extMgm::extPath('impexp').'class.tx_impexp.php');
		$import = t3lib_div::makeInstance('tx_impexp');
		$import->init();

		return $import;
	}

	/**
	 * Create sql condition for given table to limit records according to user access.
	 *
	 * @param	string	$table	Table nme to fetch records from
	 * @return	string	Condition or empty string
	 */
	function buildRecordWhere($table) {
		$result = array();
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$prefLen = strlen($table) + 1;
			foreach($GLOBALS['BE_USER']->userGroups as $group) {
				$items = t3lib_div::trimExplode(',', $group['tx_templavoila_access'], 1);
				foreach ($items as $ref) {
					if (strstr($ref, $table)) {
						$result[] = intval(substr($ref, $prefLen));
					}
				}
			}
		}
		return (count($result) > 0 ? ' AND ' . $table . '.uid NOT IN (' . implode(',', $result) . ') ' : '');
	}


	/**
	 * Extract the disallowed TCAFORM field values of $fieldName given field
	 *
	 * @param	integer		$parentPageId
	 * @param	string		field name of TCAFORM
	 * @access	private
	 * @return	string		comma seperated list of integer
	 */
	function getDisallowedTSconfigItemsByFieldName($positionPid, $fieldName) {

		$disallowPageTemplateItems = '';
		$disallowPageTemplateList = array();

			// Negative PID values is pointing to a page on the same level as the current.
		if ($positionPid < 0) {
			$pidRow = t3lib_BEfunc::getRecordWSOL('pages', abs($positionPid), 'pid');
			$parentPageId = $pidRow['pid'];
		} else {
			$parentPageId = $positionPid;
		}

			// Get PageTSconfig for reduce the output of selectded template structs
		$disallowPageTemplateStruct = t3lib_BEfunc::getModTSconfig(abs($parentPageId), 'TCEFORM.pages.' . $fieldName);

		if ( isset($disallowPageTemplateStruct['properties']['removeItems']) ) {
			$disallowedPageTemplateList = $disallowPageTemplateStruct['properties']['removeItems'];
		}

		$tmp_disallowedPageTemplateItems = array_unique(t3lib_div::intExplode(',', t3lib_div::expandList($disallowedPageTemplateList), TRUE));

		return ( count($tmp_disallowedPageTemplateItems) ) ? implode(',', $tmp_disallowedPageTemplateItems) : '0';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/class.tx_templavoila_mod1_wizards.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/class.tx_templavoila_mod1_wizards.php']);
}

?>