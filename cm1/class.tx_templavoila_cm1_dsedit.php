<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003, 2004, 2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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
 * Submodule 'dsEdit' for the mapping module
 *
 * $Id: index.php 17597 2009-03-08 17:59:14Z steffenk $
 *
 * @author		Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @co-author	Robert Lemke <robert@typo3.org>
 * @co-author	Steffen kamper <info@sk-typo3.de>
 */

class tx_templavoila_cm1_dsEdit {
	var $pObj;


	function init($pObj) {
		$this->pObj = $pObj;
	}

	/**
	 * Creates the editing row for a Data Structure element - when DS's are build...
	 *
	 * @param	string		Form element prefix
	 * @param	string		Key for form element
	 * @param	array		Values for element
	 * @param	integer		Indentation level
	 * @param	array		Array containing mapping links and commands
	 * @return	array		Two values, first is addEditRows (string HTML content), second is boolean whether to place row before or after.
	 */
	function drawDataStructureMap_editItem($formPrefix, $key, $value, $level, $rowCells) {

			// Init:
		$addEditRows='';
		$placeBefore=0;

			// If editing command is set:
		if ($this->pObj->editDataStruct)	{
			if ($this->pObj->DS_element == $formPrefix.'['.$key.']')	{	// If the editing-command points to this element:

					// Initialize, detecting either "add" or "edit" (default) mode:
				$autokey='';
				if ($this->pObj->DS_cmd=='add')	{
					if (trim($this->pObj->fieldName)!='[' . htmlspecialchars($GLOBALS['LANG']->getLL('mapEnterNewFieldname')) . ']' && trim($this->pObj->fieldName)!='field_')	{
						$autokey = strtolower(preg_replace('/[^a-z0-9_]/i', '', trim($this->pObj->fieldName)));
						if (isset($value['el'][$autokey]))	{
							$autokey .= '_' . substr(md5(microtime()), 0, 2);
						}
					} else {
						$autokey = 'field_' . substr(md5(microtime()), 0, 6);
					}

						// new entries are more offset
					$level = $level + 1;

					$formFieldName = 'autoDS'.$formPrefix.'['.$key.'][el]['.$autokey.']';
					$insertDataArray=array();
				} else {
					$placeBefore = 1;

					$formFieldName = 'autoDS'.$formPrefix.'['.$key.']';
					$insertDataArray=$value;
				}

				/* put these into array-form for preset-completition */
				$insertDataArray['tx_templavoila']['TypoScript_constants'] =
					$this->pObj->unflattenarray($insertDataArray['tx_templavoila']['TypoScript_constants']);
				$insertDataArray['TCEforms']['config'] =
					$this->pObj->unflattenarray($insertDataArray['TCEforms']['config']);

				/* do the preset-completition */
				$real = array($key => &$insertDataArray);
				$this->pObj->eTypes->substEtypeWithRealStuff($real);

				/* ... */
				if (($insertDataArray['type'] == 'array') &&
					($insertDataArray['section']))
					$insertDataArray['type'] = 'section';


					// Create form:
				/* The basic XML-structure of an tx_templavoila-entry is:
				 *
				 * <tx_templavoila>
				 * 	<title>			-> Human readable title of the element
				 * 	<description>		-> A description explaining the elements function
				 * 	<sample_data>		-> Some sample-data (can't contain HTML)
				 * 	<eType>			-> The preset-type of the element, used to switch use/content of TCEforms/TypoScriptObjPath
				 * 	<oldStyleColumnNumber>	-> for distributing the fields across the tt_content column-positions
				 * </tx_templavoila>
				 */
				$form = '
				<dl id="dsel-general" class="DS-config">
					<!-- always present options +++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
					<dt><label>' . $GLOBALS['LANG']->getLL('renderDSO_title') . ':</label></dt>
					<dd><input type="text" size="80" name="' . $formFieldName . '[tx_templavoila][title]" value="' . htmlspecialchars($insertDataArray['tx_templavoila']['title']) . '" /></dd>

					<dt><label>' . $GLOBALS['LANG']->getLL('renderDSO_mappingInstructions') . ':</label></dt>
					<dd><input type="text" size="80" name="' . $formFieldName . '[tx_templavoila][description]" value="' . htmlspecialchars($insertDataArray['tx_templavoila']['description']) . '" /></dd>

					' . (($insertDataArray['type'] != 'array') &&
					($insertDataArray['type'] != 'section') ? '
					<!-- non-array options ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
					<dt><label>' . $GLOBALS['LANG']->getLL('mapSampleData') . ':</label></dt>
					<dd><textarea cols="80" rows="5" name="' . $formFieldName . '[tx_templavoila][sample_data][]">' . htmlspecialchars($insertDataArray['tx_templavoila']['sample_data'][0]) . '</textarea>
					' . $this->pObj->lipsumLink($formFieldName . '[tx_templavoila][sample_data]') . '</dd>

					<dt><label>' . $GLOBALS['LANG']->getLL('mapElementPreset') . ':</label></dt>
					<dd><select name="' . $formFieldName . '[tx_templavoila][eType]">
						<optgroup class="c-divider" label="' . $GLOBALS['LANG']->getLL('mapPresetGroups_tceFields') . '">
							<option value="input"' . ($insertDataArray['tx_templavoila']['eType']=='input' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_plainInput') . '</option>
							<option value="input_h"' . ($insertDataArray['tx_templavoila']['eType']=='input_h' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_headerField') . '</option>
							<option value="input_g"' . ($insertDataArray['tx_templavoila']['eType']=='input_g' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_gHederField') . '</option>
							<option value="text"' . ($insertDataArray['tx_templavoila']['eType']=='text' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_textarea') . '</option>
							<option value="rte"' . ($insertDataArray['tx_templavoila']['eType']=='rte' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_rte') . '</option>
							<option value="link"' . ($insertDataArray['tx_templavoila']['eType']=='link' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_linkField') . '</option>
							<option value="int"' . ($insertDataArray['tx_templavoila']['eType']=='int' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_integer') . '</option>
							<option value="image"' . ($insertDataArray['tx_templavoila']['eType']=='image' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_image') . '</option>
							<option value="imagefixed"' . ($insertDataArray['tx_templavoila']['eType']=='imagefixed' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_imageFixed') . '</option>
							<option value="select"' . ($insertDataArray['tx_templavoila']['eType']=='select' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_select') . '</option>
							<option value="ce"'. ($insertDataArray['tx_templavoila']['eType']=='ce' ? ' selected="selected"' : '') . '>' . sprintf($GLOBALS['LANG']->getLL('mapPresets_ce'), $insertDataArray['tx_templavoila']['oldStyleColumnNumber'] ? $insertDataArray['tx_templavoila']['oldStyleColumnNumber'] : $GLOBALS['LANG']->getLL('toBeDefined')) . '</option>
						</optgroup>
						<optgroup class="c-divider" label="' . $GLOBALS['LANG']->getLL('mapPresetGroups_ts') . '">
							<option value="TypoScriptObject"'.($insertDataArray['tx_templavoila']['eType']=='TypoScriptObject' ? ' selected="selected"' : '').'>' . $GLOBALS['LANG']->getLL('mapPresets_TSobjectPath') . '</option>
							<option value="none"'. ($insertDataArray['tx_templavoila']['eType']=='none' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_none') . '</option>
						</optgroup>
						<optgroup class="c-divider" label="' . $GLOBALS['LANG']->getLL('mapPresetGroups_other') . '">
							<option value="custom"'. ($insertDataArray['tx_templavoila']['eType']=='custom' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('mapPresets_customTCA') . '</option>
						</optgroup>
					</select><input type="hidden"
						name="'.$formFieldName.'[tx_templavoila][eType_before]"
						value="'.$insertDataArray['tx_templavoila']['eType'].'" /></dd>
					' :'').'

					<dt><label>Mapping rules:</label></dt>
					<dd><input type="text" size="80" name="'.$formFieldName.'[tx_templavoila][tags]" value="'.htmlspecialchars($insertDataArray['tx_templavoila']['tags']).'" /></dd>
				</dl>';

			/*	// The dam-tv-connector will substitute the text above, that's §$%*%&"$%, but well anyway, let's not break it
				if (count($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['cm1']['eTypesExtraFormFields']) > 0) {
				$form .= '
						<optgroup class="c-divider" label="Extra Elements">';
					foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['cm1']['eTypesExtraFormFields'] as $key => $value) {
							<option value="'.$key.'"'.($insertDataArray['tx_templavoila']['eType']==$key ? ' selected="selected"' : '').'>'.$key.'</option>
					}
				$form .= '
						</optgroup>';
				}	*/

				if (($insertDataArray['type'] != 'array') &&
					($insertDataArray['type'] != 'section')) {
					/* The Typoscript-related XML-structure of an tx_templavoila-entry is:
					 *
					 * <tx_templavoila>
					 *	<TypoScript_constants>	-> an array of constants that will be substituted in the <TypoScript>-element
					 * 	<TypoScript>		->
					 * </tx_templavoila>
					 */
					if ($insertDataArray['tx_templavoila']['eType'] != 'TypoScriptObject')
					$form .= '
					<dl id="dsel-ts" class="DS-config">
						<dt><label>' . $GLOBALS['LANG']->getLL('mapTSconstants') . ':</label></dt>
						<dd><textarea class="xml" cols="80" rows="10" name="'.$formFieldName.'[tx_templavoila][TypoScript_constants]">'.htmlspecialchars($this->pObj->flattenarray($insertDataArray['tx_templavoila']['TypoScript_constants'])).'</textarea></dd>
						<dt><label>' . $GLOBALS['LANG']->getLL('mapTScode') . ':</label></dt>
						<dd><textarea class="code" cols="80" rows="10" name="'.$formFieldName.'[tx_templavoila][TypoScript]">'.htmlspecialchars($insertDataArray['tx_templavoila']['TypoScript']).'</textarea></dd>
					</dl>';

					/* The Typoscript-related XML-structure of an tx_templavoila-entry is:
					 *
					 * <tx_templavoila>
					 * 	<TypoScriptObjPath>	->
					 * </tx_templavoila>
					 */
					if (($extra = $this->drawDataStructureMap_editItem_editTypeExtra(
						$insertDataArray['tx_templavoila']['eType'],
						$formFieldName.'[tx_templavoila][eType_EXTRA]',
						($insertDataArray['tx_templavoila']['eType_EXTRA'] ?	// Use eType_EXTRA only if it is set (could be modified, etc), otherwise use TypoScriptObjPath!
							$insertDataArray['tx_templavoila']['eType_EXTRA'] :
								($insertDataArray['tx_templavoila']['TypoScriptObjPath'] ?
								array('objPath' => $insertDataArray['tx_templavoila']['TypoScriptObjPath']) : ''))
						)))
					$form .= '
					<dl id="dsel-extra" class="DS-config">
						<dt>' . $GLOBALS['LANG']->getLL('mapExtraOptions') . '</dt>
						<dd>'.$extra.'</dd>
					</dl>';

					/* The process-related XML-structure of an tx_templavoila-entry is:
					 *
					 * <tx_templavoila>
					 * 	<proc>			-> define post-processes for this element's value
					 *		<int>		-> this element's value will be cast to an integer (if exist)
					 *		<HSC>		-> this element's value will convert special chars to HTML-entities (if exist)
					 *		<stdWrap>	-> an implicit stdWrap for this element, "stdWrap { ...inside... }"
					 * 	</proc>
					 * </tx_templavoila>
					 */
					$form .= '
					<dl id="dsel-proc" class="DS-config">
						<dt>' . $GLOBALS['LANG']->getLL('mapPostProcesses') . ':</dt>
						<dd>
							<input type="checkbox" class="checkbox" id="tv_proc_int" name="' . $formFieldName . '[tx_templavoila][proc][int]" value="1" ' . ($insertDataArray['tx_templavoila']['proc']['int'] ? 'checked="checked"' : '') . ' />
							<label for="tv_proc_int">' . $GLOBALS['LANG']->getLL('mapPPcastInteger') . '</label><br />
							<input type="checkbox" class="checkbox" id="tv_proc_hsc" name="' . $formFieldName . '[tx_templavoila][proc][HSC]" value="1" ' . ($insertDataArray['tx_templavoila']['proc']['HSC'] ? 'checked="checked"' : '') . ' />
							<label for="tv_proc_hsc">' . $GLOBALS['LANG']->getLL('mapPPhsc') . '</label>
						</dd>

						<dt><label>' . $GLOBALS['LANG']->getLL('mapCustomStdWrap') . ':</label></dt>
						<dd><textarea class="code" cols="80" rows="10" name="'.$formFieldName.'[tx_templavoila][proc][stdWrap]">'.htmlspecialchars($insertDataArray['tx_templavoila']['proc']['stdWrap']).'</textarea></dd>
					</dl>';

					/* The basic XML-structure of an TCEforms-entry is:
					 *
					 * <TCEforms>
					 * 	<label>			-> TCE-label for the BE
					 * 	<config>		-> TCE-configuration array
					 * </TCEforms>
					 */
					if ($insertDataArray['tx_templavoila']['eType'] != 'TypoScriptObject')
					$form .= '
					<dl id="dsel-tce" class="DS-config">
						<dt><label>' . $GLOBALS['LANG']->getLL('mapTCElabel') . ':</label></dt>
						<dd><input type="text" size="80" name="'.$formFieldName.'[TCEforms][label]" value="'.htmlspecialchars($insertDataArray['TCEforms']['label']).'" /></dd>

						<dt><label>' . $GLOBALS['LANG']->getLL('mapTCEconf') . ':</label></dt>
						<dd><textarea class="xml" cols="80" rows="10" name="'.$formFieldName.'[TCEforms][config]">'.htmlspecialchars($this->pObj->flattenarray($insertDataArray['TCEforms']['config'])).'</textarea></dd>

						<dt><label>' . $GLOBALS['LANG']->getLL('mapTCEextras') . ':</label></dt>
						<dd><input type="text" size="80" name="'.$formFieldName.'[TCEforms][defaultExtras]" value="'.htmlspecialchars($insertDataArray['TCEforms']['defaultExtras']).'" /></dd>
					</dl>';
				}

				$formSubmit = '
					<input type="hidden" name="DS_element" value="'.htmlspecialchars($this->pObj->DS_cmd=='add' ? $this->pObj->DS_element.'[el]['.$autokey.']' : $this->pObj->DS_element).'" />
					<input type="submit" name="_updateDS" value="'.($this->pObj->DS_cmd=='add' ? $GLOBALS['LANG']->getLL('buttonAdd') : $GLOBALS['LANG']->getLL('buttonUpdate')).'" />
					<!--	<input type="submit" name="'.$formFieldName.'" value="' . $GLOBALS['LANG']->getLL('buttonDelete') . ' (!)" />  -->
					<input type="submit" name="_" value="'.($this->pObj->DS_cmd=='add' ? $GLOBALS['LANG']->getLL('buttonCancel') : $GLOBALS['LANG']->getLL('buttonCancelClose')).'" onclick="document.location=\''.$this->pObj->linkThisScript().'\'; return false;" />
				';


				/* The basic XML-structure of an entry is:
				 *
				 * <element>
				 * 	<tx_templavoila>	-> entries with informational character belonging to this entry
				 * 	<TCEforms>		-> entries being used for TCE-construction
				 * 	<type + el + section>	-> subsequent hierarchical construction
				 *	<langOverlayMode>	-> ??? (is it the language-key?)
				 * </element>
				 */

					// Icons:
				$info = $this->pObj->dsTypeInfo($insertDataArray);

				// Find "select" style. This is necessary because Safari
				// does not support paddings in select elements but supports
				// backgrounds. The rest is text over background.
				$selectStyle = 'margin: 4px 0; width: 150px !important; display: block;';
				$userAgent = t3lib_div::getIndpEnv('HTTP_USER_AGENT');
				if (strpos($userAgent, 'WebKit') === false) {
					// Not Safai (Can't have "padding" for select elements in Safari)
					$selectStyle .= 'padding: 1px 1px 1px 30px; background: 0 50% url(' . $info[3] . ') no-repeat;';
				}

				$addEditRows='<tr class="tv-edit-row">
					<td valign="top" style="padding: 0.5em; padding-left: '.(($level)*16+3).'px" nowrap="nowrap" rowspan="2">
						<select style="' . $selectStyle . '" title="Mapping Type" name="'.$formFieldName.'[type]">
							<optgroup class="c-divider" label="' . $GLOBALS['LANG']->getLL('mapElContainers') . '">
								<option style="padding: 1px 1px 1px 30px; background: 0 50% url(' . $this->pObj->dsTypes['sc'][3] . ') no-repeat;" value="section"'. ($insertDataArray['type']=='section' ? ' selected="selected"' : '').'>' . $GLOBALS['LANG']->getLL('mapSection') . '</option>
								<option style="padding: 1px 1px 1px 30px; background: 0 50% url(' . $this->pObj->dsTypes['co'][3] . ') no-repeat;" value="array"'.   ($insertDataArray['type']=='array'   ? ' selected="selected"' : '').'>' . $GLOBALS['LANG']->getLL('mapContainer') . '</option>
							</optgroup>
							<optgroup class="c-divider" label="' . $GLOBALS['LANG']->getLL('mapElElements') . '">
								<option style="padding: 1px 1px 1px 30px; background: 0 50% url(' . $this->pObj->dsTypes['el'][3] . ') no-repeat;" value=""'.        ($insertDataArray['type']==''        ? ' selected="selected"' : '').'>' . $GLOBALS['LANG']->getLL('mapElement') . '</option>
								<option style="padding: 1px 1px 1px 30px; background: 0 50% url(' . $this->pObj->dsTypes['at'][3] . ') no-repeat;" value="attr"'.    ($insertDataArray['type']=='attr'    ? ' selected="selected"' : '').'>' . $GLOBALS['LANG']->getLL('mapAttribute') . '</option>
							</optgroup>
							<optgroup class="c-divider" label="' . $GLOBALS['LANG']->getLL('mapPresetGroups_other') . '">
								<option style="padding: 1px 1px 1px 30px; background: 0 50% url(' . $this->pObj->dsTypes['no'][3] . ') no-repeat;" value="no_map"'.  ($insertDataArray['type']=='no_map'  ? ' selected="selected"' : '').'>' . $GLOBALS['LANG']->getLL('mapNotMapped') . '</option>
							</optgroup>
						</select>
						<div style="margin: 0.25em;">' .
							($this->pObj->DS_cmd=='add' ? $autokey . ' <strong>(new)</strong>:<br />' : $key) .
						'</div>
						<input id="dsel-act" type="hidden" name="dsel_act" />
						<ul id="dsel-menu" class="DS-tree">
							<li><a id="dssel-general" class="active" href="#" onclick="" title="' . $GLOBALS['LANG']->getLL('mapEditConfiguration') . '">' . $GLOBALS['LANG']->getLL('mapConfiguration') . '</a>
								<ul>
									<li><a id="dssel-proc" href="#" title="' . $GLOBALS['LANG']->getLL('mapEditDataProcessing') . '">' . $GLOBALS['LANG']->getLL('mapDataProcessing') . '</a></li>
									<li><a id="dssel-ts" href="#" title="' . $GLOBALS['LANG']->getLL('mapEditTyposcript') . '">' . $GLOBALS['LANG']->getLL('mapTyposcript') . '</a></li>
									<li class="last-child"><a id="dssel-extra" href="#" title="' . $GLOBALS['LANG']->getLL('mapEditExtra') . '">' . $GLOBALS['LANG']->getLL('mapExtra') . '</a></li>
								</ul>
							</li>
							<li class="last-child"><a id="dssel-tce" href="#" title="' . $GLOBALS['LANG']->getLL('mapEditTCEform') . '">' . $GLOBALS['LANG']->getLL('mapTCEform') . '</a></li>
						</ul>
						' . $this->pObj->cshItem('xMOD_tx_templavoila', 'mapping_editform', $this->pObj->doc->backPath, '', FALSE, 'margin-bottom: 0px;') . '
					</td>

					<td valign="top" style="padding: 0.5em;" colspan="2" rowspan="2">
						'.$form.'
						<script type="text/javascript">
							var dsel_act = "' . (t3lib_div::_GP('dsel_act') ? t3lib_div::_GP('dsel_act') : 'general') . '";
							var dsel_menu = [
								{"id" : "general",		"avail" : true,	"label" : "' . $GLOBALS['LANG']->getLL('mapConfiguration') . '",	"title" : "' . $GLOBALS['LANG']->getLL('mapEditConfiguration') . '",	"childs" : [
									{"id" : "ts",		"avail" : true,	"label" : "' . $GLOBALS['LANG']->getLL('mapDataProcessing') . '",	"title" : "' . $GLOBALS['LANG']->getLL('mapEditDataProcessing') . '"},
									{"id" : "extra",	"avail" : true,	"label" : "' . $GLOBALS['LANG']->getLL('mapTyposcript') . '",		"title" : "' . $GLOBALS['LANG']->getLL('mapEditTyposcript') . '"},
									{"id" : "proc",		"avail" : true,	"label" : "' . $GLOBALS['LANG']->getLL('mapExtra') . '",	"title" : "' . $GLOBALS['LANG']->getLL('mapEditExtra') . '"}]},
								{"id" : "tce",			"avail" : true,	"label" : "' . $GLOBALS['LANG']->getLL('mapTCEform') . '",		"title" : "' . $GLOBALS['LANG']->getLL('mapEditTCEform') . '"}
							];

							function dsel_menu_construct(dsul, dsmn) {
								if (dsul) {
									while (dsul.childNodes.length)
										dsul.removeChild(dsul.childNodes[0]);
									for (var el = 0, pos = 0; el < dsmn.length; el++) {
										var tab = document.getElementById("dsel-" + dsmn[el]["id"]);
										var stl = "none";
										if (tab) { if (dsmn[el]["avail"]) {
											var tx = document.createTextNode(dsmn[el]["label"]);
											var ac = document.createElement("a"); ac.appendChild(tx);
											var li = document.createElement("li"); li.appendChild(ac);
											ac.title = dsmn[el]["title"]; ac.href = "#dsel-menu"; ac.rel = dsmn[el]["id"];
											ac.className = (dsel_act == dsmn[el]["id"] ? "active" : "");
											ac.onclick = function() { dsel_act = this.rel; dsel_menu_reset(); };
											if (dsmn[el]["childs"]) {
												var ul = document.createElement("ul");
												dsel_menu_construct(ul, dsmn[el]["childs"]);
												li.appendChild(ul);
											}
											dsul.appendChild(li);
											stl = (dsel_act == dsmn[el]["id"] ? "" : "none");
										} tab.style.display = stl; }
									}
									if (dsul.lastChild)
										dsul.lastChild.className = "last-child";
								}
							}

							function dsel_menu_reset() {
								dsel_menu_construct(document.getElementById("dsel-menu"), dsel_menu);
								document.getElementById("dsel-act").value = dsel_act;
							}

							dsel_menu_reset();
						</script>
					</td>
					<td>' . ($this->pObj->DS_cmd=='add' ? '' : $rowCells['htmlPath']) . '</td>
					<td>' . ($this->pObj->DS_cmd=='add' ? '' : $rowCells['cmdLinks']) . '</td>
					<td>' . ($this->pObj->DS_cmd=='add' ? '' : $rowCells['tagRules']) . '</td>
					<td colspan="2"></td>
				</tr>
				<tr class="tv-edit-row">
					<td class="edit-ds-actioncontrols" colspan="4">
					' . $formSubmit . '
					</td>
				</tr>';
			} elseif (!$this->pObj->DS_element && ($value['type']=='array' || $value['type']=='section') && !$this->pObj->mapElPath) {
				$addEditRows='<tr class="bgColor4">
					<td colspan="7"><img src="clear.gif" width="'.(($level+1)*16).'" height="1" alt="" />'.
					'<input type="text" name="'.md5($formPrefix.'['.$key.']').'" value="[' . htmlspecialchars($GLOBALS['LANG']->getLL('mapEnterNewFieldname')) . ']" onfocus="if (this.value==\'[' . $GLOBALS['LANG']->getLL('mapEnterNewFieldname') . ']\'){this.value=\'field_\';}" />'.
					'<input type="submit" name="_" value="Add" onclick="document.location=\''.$this->pObj->linkThisScript(array('DS_element'=>$formPrefix.'['.$key.']','DS_cmd'=>'add')).'&amp;fieldName=\'+document.pageform[\''.md5($formPrefix.'['.$key.']').'\'].value; return false;" />'.
					$this->pObj->cshItem('xMOD_tx_templavoila','mapping_addfield',$this->pObj->doc->backPath,'',FALSE,'margin-bottom: 0px;').
					'</td>
				</tr>';
			}
		}

			// Return edit row:
		return array($addEditRows,$placeBefore);
	}

	/**
	 * Renders extra form fields for configuration of the Editing Types.
	 *
	 * @param	string		Editing Type string
	 * @param	string		Form field name prefix
	 * @param	array		Current values for the form field name prefix.
	 * @return	string		HTML with extra form fields
	 * @access	private
	 * @see drawDataStructureMap_editItem()
	 */
	function drawDataStructureMap_editItem_editTypeExtra($type, $formFieldName, $curValue)	{
			// If a user function was registered, use that instead of our own handlers:
		if (isset ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['cm1']['eTypesExtraFormFields'][$type])) {
			$_params = array (
				'type' => $type,
				'formFieldName' => $formFieldName,
				'curValue' => $curValue,
			);
			$output = t3lib_div::callUserFunction($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['cm1']['eTypesExtraFormFields'][$type], $_params, $this);
		} else {
			switch($type)	{
				case 'TypoScriptObject':
					$output = '
						<table border="0" cellpadding="2" cellspacing="0">
							<tr>
								<td>' . $GLOBALS['LANG']->getLL('mapObjectPath') . ':</td>
								<td><input type="text" name="'.$formFieldName.'[objPath]" value="'.htmlspecialchars($curValue['objPath'] ? $curValue['objPath'] : 'lib.myObject').'" /></td>
							</tr>
						</table>';
				break;
			}
		}
		return $output;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/cm1/class.tx_templavoila_cm1_dsedit.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/cm1/class.tx_templavoila_cm1_dsedit.php']);
}

?>
