<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2003 Kasper Skårhøj (kasper@typo3.com)
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
 * templavoila module cm1
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   92: class tx_templavoila_cm1 extends t3lib_SCbase 
 *  114:     function menuConfig()    
 *  132:     function main()	
 *  152:     function printContent()	
 *
 *              SECTION: MODULE content generation
 *  179:     function main_mode()	
 *  202:     function jumpToUrl(URL)	
 *  206:     function updPath(inPath)	
 *  228:     function moduleContent()	
 *  445:     function renderDSO()	
 *  513:     function renderTO()	
 *  654:     function renderTemplateMapper($displayFile,$path,$dataStruct=array(),$currentMappingInfo=array())	
 *  779:     function drawDataStructureMap($dataStruct,$mappingMode=0,$currentMappingInfo=array(),$pathLevels=array(),$optDat=array(),$contentSplittedByMapping=array(),$level=0,$tRows=array(),$formPrefix='',$path='',$mapOK=1)	
 * 1012:     function substEtypeWithRealStuff(&$elArray,$v_sub=array())	
 * 1232:     function substEtypeWithRealStuff_contentInfo($content)	
 * 1269:     function getDataStructFromDSO($datString,$file='')	
 * 1285:     function linkForDisplayOfPath($title,$path)	
 * 1303:     function linkThisScript($array)	
 * 1325:     function makeIframeForVisual($file,$path,$limitTags,$showOnly,$preview=0)	
 * 1341:     function explodeMappingToTagsStr($mappingToTags,$unsetAll=0)	
 *
 *              SECTION: DISPLAY
 * 1370:     function main_display()	
 * 1436:     function displayFileContentWithMarkup($content,$path,$relPathFix,$limitTags)	
 *
 * TOTAL FUNCTIONS: 20
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);	
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:templavoila/cm1/locallang.php');
require_once (PATH_t3lib.'class.t3lib_scbase.php');

	
require_once(t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_htmlmarkup.php'); 	
require_once(PATH_t3lib.'class.t3lib_tcemain.php');	











/**
 * Class for controlling the TemplaVoila module.
 * 
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */
class tx_templavoila_cm1 extends t3lib_SCbase {
		// Static:
	var $theDisplayMode = 'explode';

		// Internal, dynamic:
	var $displayFile = '';		// The file to display, if file is referenced directly from filelist module
	var $displayTable = '';		// The table from which to display element (data source object, template object or content object)
	var $displayUid = '';		// The UID to display.
	var $displayPath = '';		// The path to display from the current file.
	var $displayMode = '';		// The display mode.
	var $markupFile = '';		// Used to store the name of the file to mark up with a given path.
	var $elNames = array();
	var $markupObj = '';
		
	
	
	
	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 * 
	 * @return	[type]		...
	 */
	function menuConfig()    {
	    global $LANG;
	    $this->MOD_MENU = Array (
            'displayMode' => array	(
				'' => 'Default',
				'explode' => 'Explode',
				'borders' => 'Borders',
				'source' => 'Source',
			)
        );
        parent::menuConfig();
    }

	/**
	 * Main function, distributes the load between the frameset, module and display modes.
	 * 
	 * @return	void		
	 */
	function main()	{
			// Looking for "&mode", which defines if we draw a frameset (default), the module (mod) or display (display)
		$mode = t3lib_div::GPvar('mode');
		
		switch((string)$mode)	{
			case 'display':
				$this->main_display();
			break;
			default:
				$this->main_mode();
			break;
		}
	}

	/**
	 * Prints module content. 
	 * Is only used in case of &mode = "mod" since both "display" mode and frameset is outputted + exiting before this is called.
	 * 
	 * @return	void		
	 */
	function printContent()	{
		global $SOBE;

		$this->content.=$this->doc->middle();
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}









	/*****************************************
	 *
	 * MODULE content generation
	 *
	 *****************************************/

	/**
	 * Main function of the MODULE. Write the content to $this->content
	 * 
	 * @return	void		
	 */
	function main_mode()	{
		global $LANG,$BACK_PATH;
		
			// Draw the header.
		$this->doc = t3lib_div::makeInstance('noDoc');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->docType='xhtml_trans';

			// Getting parameters which might have been sent:
		$this->displayFile = t3lib_div::GPvar('file');
		$this->displayTable = t3lib_div::GPvar('table');
		$this->displayUid = t3lib_div::GPvar('uid');
		$this->displayPath = t3lib_div::GPvar('htmlPath');
		$this->displayMode = t3lib_div::GPvar('displayMode');
		
			// Setting up form-wrapper:
		$this->doc->form='<form action="'.$this->linkThisScript(array()).'" method="post" name="pageform">';

			// JavaScript
		$this->doc->JScode = '
			<script type="text/javascript">
				/*<![CDATA[*/
				script_ended = 0;
				function jumpToUrl(URL)	{
					document.location = URL;
				}
					// ..
				function updPath(inPath)	{
					document.location = "'.t3lib_div::linkThisScript(array('htmlPath'=>'','doMappingOfPath'=>1)).'&htmlPath="+top.rawurlencode(inPath);
				}
				/*]]>*/
			</script>
		';

		$this->content.=$this->doc->startPage($LANG->getLL('title'));
		$this->content.=$this->doc->header($LANG->getLL('title'));
		$this->content.=$this->doc->spacer(5);

		// Render content:
		$this->moduleContent();

		$this->content.=$this->doc->spacer(10);
	}

	/**
	 * Creates module content.
	 * 
	 * @return	void		
	 */
	function moduleContent()	{

#		$this->displayFile='';
			// 
		if ($this->displayFile)	{	// Browsing file directly, possibly creating a template/data object records.
			if ((@is_file($this->displayFile) && t3lib_div::getFileAbsFileName($this->displayFile)) || substr($this->displayFile,0,7)=='http://')		{
					
					$this->editDataStruct=1;
					$content='';

						// Get session data:							
					if (!t3lib_div::GPvar('_clear'))	{
						$sesDat = $GLOBALS['BE_USER']->getSessionData($this->MCONF['name'].'_mappingInfo');
					} else $GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',array());

					if (t3lib_div::GPvar('_load_ds_xml') && (t3lib_div::GPvar('_load_ds_xml_content') || t3lib_div::GPvar('_load_ds_xml_to')))	{
						$to_uid = t3lib_div::GPvar('_load_ds_xml_to');
						if ($to_uid)	{
							$toREC = t3lib_BEfunc::getRecord('tx_templavoila_tmplobj',$to_uid);
							$tM = unserialize($toREC['templatemapping']);
							$sesDat=array();
							$sesDat['currentMappingInfo'] = $tM['MappingInfo'];
							$dsREC = t3lib_BEfunc::getRecord('tx_templavoila_datastructure',$toREC['datastructure']);
							
							$ds=t3lib_div::xml2array($dsREC['dataprot']);
							$sesDat['dataStruct']['ROOT']=$sesDat['autoDS']['ROOT']=$ds['ROOT'];
							$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
						} else {
							$ds = t3lib_div::xml2array(t3lib_div::GPvar('_load_ds_xml_content'));
							$sesDat=array();
							$sesDat['dataStruct']['ROOT']=$sesDat['autoDS']['ROOT']=$ds['ROOT'];
							$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
						}
					}
					
					$dataStruct = is_array($sesDat['autoDS']) ? $sesDat['autoDS'] : array(
							'meta' => array(
								'langChildren' => 1,
								'langDisable' => 1
							),
							'ROOT' => array (
								'tx_templavoila' => array (
									'title' => 'ROOT',
									'description' => 'Select the HTML element on the page which you want to be the overall container for the template/grab.',
								),
								'type' => 'array',
								'el' => array()
							)
						);
					$currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : array();
					$this->cleanUpMappingInfoAccordingToDS($currentMappingInfo,$dataStruct);
					
					$inputData = t3lib_div::GPvar('dataMappingForm',1);
					if (t3lib_div::GPvar('_save_data_mapping') && is_array($inputData))	{
						$sesDat['currentMappingInfo'] = $currentMappingInfo = t3lib_div::array_merge_recursive_overrule($currentMappingInfo,$inputData);
						$sesDat['dataStruct'] = $dataStruct;
						$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
					}

					
					if (t3lib_div::GPvar('_updateDS'))	{
						$inDS = t3lib_div::GPvar('autoDS',1);
						if (is_array($inDS))	{
							$dataStruct = $sesDat['autoDS'] = t3lib_div::array_merge_recursive_overrule($dataStruct,$inDS);
							$sesDat['dataStruct'] = $dataStruct;
							$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);							
						}
					}
					
					if (t3lib_div::GPvar('DS_element_DELETE'))	{
						$ref = explode('][',substr(t3lib_div::GPvar('DS_element_DELETE'),1,-1));
						$this->unsetArrayPath($dataStruct,$ref);

							$sesDat['dataStruct'] = $sesDat['autoDS'] = $dataStruct;
							$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);							
					}
					
					if (t3lib_div::GPvar('_showXMLDS') || t3lib_div::GPvar('_saveDSandTO') || t3lib_div::GPvar('_updateDSandTO'))	{
							// Template mapping prepared:
						$templatemapping=array();
						$templatemapping['MappingInfo']=$currentMappingInfo;
							// Getting cached data:
						reset($dataStruct);
						#$firstKey = key($dataStruct);
						$firstKey='ROOT';
						if ($firstKey)	{
							$fileContent = t3lib_div::getUrl($this->displayFile);
							$htmlParse = t3lib_div::makeInstance('t3lib_parsehtml');
							$relPathFix=dirname(substr($this->displayFile,strlen(PATH_site))).'/';
								$fileContent = $htmlParse->prefixResourcePath($relPathFix,$fileContent);
							$this->markupObj = t3lib_div::makeInstance('tx_templavoila_htmlmarkup');
							$contentSplittedByMapping=$this->markupObj->splitContentToMappingInfo($fileContent,$currentMappingInfo);
							$templatemapping['MappingData_cached']=$contentSplittedByMapping['sub'][$firstKey];
						}
					}

					if (t3lib_div::GPvar('_saveDSandTO'))	{

							// DS:
						$dataArr=array();
						$dataArr['tx_templavoila_datastructure']['NEW']['pid']=2;
						$dataArr['tx_templavoila_datastructure']['NEW']['title']=t3lib_div::GPvar('_saveDSandTO_title');
						$dataArr['tx_templavoila_datastructure']['NEW']['scope']=t3lib_div::GPvar('_saveDSandTO_type');
						$storeDataStruct=$dataStruct;
						if (is_array($storeDataStruct['ROOT']['el']))		$this->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'],$contentSplittedByMapping['sub']['ROOT']);
#debug($storeDataStruct);
						$dataArr['tx_templavoila_datastructure']['NEW']['dataprot']=t3lib_div::array2xml($storeDataStruct,'',0,'T3DataStructure');

						$tce = t3lib_div::makeInstance("t3lib_TCEmain");
						$tce->stripslashes_values=0;
						$tce->start($dataArr,array());
						$tce->process_datamap();
						
						if ($tce->substNEWwithIDs['NEW'])	{


							$dataArr=array();
							$dataArr['tx_templavoila_tmplobj']['NEW']['pid']=2;
							$dataArr['tx_templavoila_tmplobj']['NEW']['title']=t3lib_div::GPvar('_saveDSandTO_title').' [Template]';
							$dataArr['tx_templavoila_tmplobj']['NEW']['datastructure']=intval($tce->substNEWwithIDs['NEW']);
							$dataArr['tx_templavoila_tmplobj']['NEW']['fileref']=substr($this->displayFile,strlen(PATH_site));
							$dataArr['tx_templavoila_tmplobj']['NEW']['templatemapping']=serialize($templatemapping);
#debug($templatemapping);	
							$tce = t3lib_div::makeInstance("t3lib_TCEmain");
							$tce->stripslashes_values=0;
							$tce->start($dataArr,array());
							$tce->process_datamap();
						}

								// WHAT ABOUT slashing of the input values!!!!??? That should be done!
						unset($tce);
						$content.='<strong>SAVED...</strong><br>';
					}
					
					if (t3lib_div::GPvar('_updateDSandTO'))	{
						$toREC = t3lib_BEfunc::getRecord('tx_templavoila_tmplobj',t3lib_div::GPvar('_saveDSandTO_TOuid'));
						$dsREC = t3lib_BEfunc::getRecord('tx_templavoila_datastructure',$toREC['datastructure']);

						if ($toREC['uid'] && $dsREC['uid'])	{
							
								// DS:
							$dataArr=array();
							$storeDataStruct=$dataStruct;
							if (is_array($storeDataStruct['ROOT']['el']))		$this->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'],$contentSplittedByMapping['sub']['ROOT']);
							$dataArr['tx_templavoila_datastructure'][$dsREC['uid']]['dataprot']=t3lib_div::array2xml($storeDataStruct,'',0,'T3DataStructure');
	
							$tce = t3lib_div::makeInstance("t3lib_TCEmain");
							$tce->stripslashes_values=0;
							$tce->start($dataArr,array());
							$tce->process_datamap();
							
								// TO:
							$dataArr=array();
							$dataArr['tx_templavoila_tmplobj'][$toREC['uid']]['fileref']=substr($this->displayFile,strlen(PATH_site));
							$dataArr['tx_templavoila_tmplobj'][$toREC['uid']]['templatemapping']=serialize($templatemapping);
	
							$tce = t3lib_div::makeInstance("t3lib_TCEmain");
							$tce->stripslashes_values=0;
							$tce->start($dataArr,array());
							$tce->process_datamap();
	
									// WHAT ABOUT slashing of the input values!!!!??? That should be done!
							unset($tce);
							$content.='<strong>UPDATED...</strong><br>';
						}
					}
					
					
					if (t3lib_div::GPvar('_showXMLDS'))	{
						$storeDataStruct=$dataStruct;
						if (is_array($storeDataStruct['ROOT']['el']))		$this->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'],$contentSplittedByMapping['sub']['ROOT']);
						$content.='<h3>XML configuration:</h3><pre>'.htmlspecialchars(t3lib_div::array2xml($storeDataStruct,'',0,'T3DataStructure')).'</pre>';
					}
					
					
					
					

					$content.='<h3>Load DS XML</h3>';
					$content.='<textarea cols="" rows="" name="_load_ds_xml_content"></textarea><br>';
					
					$opt=array();
					$opt[]='<option value="0"></option>';
					$query = 'SELECT * FROM tx_templavoila_tmplobj WHERE datastructure>0 '.t3lib_BEfunc::deleteClause('tx_templavoila_tmplobj').' ORDER BY title';
					$res = mysql(TYPO3_db,$query);
					while($row = mysql_fetch_assoc($res))	{
						$opt[]='<option value="'.htmlspecialchars($row['uid']).'">'.htmlspecialchars($row['title']).'</option>';
					}
					$content.='<select name="_load_ds_xml_to">'.implode('',$opt).'</select><br>';
					$content.='<input type="submit" name="_load_ds_xml" value="LOAD">';
					

					
					
					
					$content.='<h3>Creating data structure / Mapping to template:</h3>';
					$content.='<hr><strong>Create new Data Structure and Template Object:</strong><br>
					Title: <input type="text" name="_saveDSandTO_title"><br>
					Type: <select name="_saveDSandTO_type">
								<option></option>
								<option value="1">Page Template</option>
								<option value="2">Content Element</option>
							</select><br>
					<input type="submit" name="_saveDSandTO" value="Create DS and TO"> 
					<hr>
					Alternatively, save to existing template record:<br>
					<select name="_saveDSandTO_TOuid">'.implode('',$opt).'</select><br>
					<input type="submit" name="_updateDSandTO" value="Update TO/DS">
					<hr>
					<input type="submit" name="_showXMLDS" value="Show XML"> 
					<input type="submit" name="_clear" value="Clear current mappings"> 
					<input type="submit" name="_DO_NOTHING" value="Refresh..."> 
					<input type="submit" name="_preview" value="PREVIEW">
					';
					
					unset($dataStruct['meta']);			
					$content.= $this->renderTemplateMapper($this->displayFile,$this->displayPath,$dataStruct,$currentMappingInfo);
				}

			$this->content.=$this->doc->section('Browsing file...',$content,0,1);
		} elseif ($this->displayTable=='tx_templavoila_datastructure') {	// Data source display
			$this->renderDSO();
		} elseif ($this->displayTable=='tx_templavoila_tmplobj') {	// Data source display
			$this->renderTO();
		}
	}
	
	/**
	 * Renders the display of Data Structure Objects.
	 * 
	 * @return	void		
	 */
	function renderDSO()	{
		if (intval($this->displayUid)>0)	{
			$row = t3lib_BEfunc::getRecord('tx_templavoila_datastructure',$this->displayUid);
			if (is_array($row))	{
					// Get title and icon:
				$icon = t3lib_iconworks::getIconImage('tx_templavoila_datastructure',$row,$GLOBALS['BACK_PATH'],' align="top" title="UID: '.$this->displayUid.'"');
				$title = t3lib_BEfunc::getRecordTitle('tx_templavoila_datastructure',$row,1);
				$content.=$icon.$title.'<br />';
				
					// Get data source (temporary solution here!! Converting PHP array):
				$dataStruct = $this->getDataStructFromDSO($row['dataprot']);
				if (is_array($dataStruct))	{
						// Showing data structure:
					$tRows = $this->drawDataStructureMap($dataStruct);
					$content.='
					<h4>Data Structure Found in Object:</h4>
					<table border="0" cellspacing="2" cellpadding="2">
								<tr bgcolor="'.$this->doc->bgColor5.'">
									<td><strong>Data Element:</strong></td>
									<td><strong>FieldName:</strong></td>
									<td><strong>Mapping instructions:</strong><br /><img src="clear.gif" width="200" height="1" alt="" /></td>
									<td><strong>Rules:</strong></td>
								</tr>
					'.implode('',$tRows).'
					</table>';
				} else {
					$content.='<h4>ERROR: No Data Source was defined in the record... (Must be PHP array defined as $dataStruct)</h4>';
				}
				
					// Get Template Objects pointing to this data source (NOT checking for the PID!)
				$query = 'SELECT * FROM tx_templavoila_tmplobj WHERE datastructure='.intval($row['uid']).t3lib_BEfunc::deleteClause('tx_templavoila_tmplobj');
				$res = mysql(TYPO3_db,$query);
				$tRows=array();
				$tRows[]='<tr bgcolor="'.$this->doc->bgColor5.'">
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td><strong>Title:</strong></td>
					<td><strong>File reference:</strong></td>
					<td><strong>Mapping Data Lgd:</strong></td>
				</tr>';
				$TOicon = t3lib_iconworks::getIconImage('tx_templavoila_tmplobj',array(),$GLOBALS['BACK_PATH']);
				while($TO_Row = mysql_fetch_assoc($res))	{
					$tRows[]='<tr bgcolor="'.$this->doc->bgColor4.'">
						<td>'.$TOicon.'</td>
						<td><a href="#" onclick="'.t3lib_BEfunc::editOnClick('&edit[tx_templavoila_tmplobj]['.$TO_Row['uid'].']=edit',$GLOBALS['BACK_PATH']).'"><img src="'.$GLOBALS['BACK_PATH'].'gfx/edit2.gif" width="11" height="12" border="0" alt=""></a></td>
						<td><a href="index.php?table=tx_templavoila_tmplobj&uid='.$TO_Row['uid'].'&_reload_from=1">'.t3lib_BEfunc::getRecordTitle('tx_templavoila_tmplobj',$TO_Row,1).'</a></td>
						<td nowrap="nowrap">'.htmlspecialchars($TO_Row['fileref']).' <strong>'.(!t3lib_div::getFileAbsFileName($TO_Row['fileref'],1)?'(NOT FOUND!)':'(OK)').'</strong></td>
						<td>'.strlen($TO_Row['templatemapping']).'</td>
					</tr>';
				}
				
				$content.='
					<h4>Template Objects using this data source:</h4>
					<table border="0" cellpadding="2" cellspacing="2">'.implode('',$tRows).'</table>';
				
			} else {
				$content.='ERROR: No Data Source Record with the UID '.$this->displayUid;
			}
			$this->content.=$this->doc->section('Data Source Object',$content,0,1);
		} else {
			$this->content.=$this->doc->section('Data Source Object ERROR','No UID was found pointing to a Data Source Object record.',0,1,3);
		}
	}
	
	/**
	 * Renders the display of template objects.
	 * 
	 * @return	void		
	 */
	function renderTO()	{
		if (intval($this->displayUid)>0)	{
			$row = t3lib_BEfunc::getRecord('tx_templavoila_tmplobj',$this->displayUid);
			if (is_array($row))	{
#debug($GLOBALS['HTTP_GET_VARS']);
					// Get title and icon:
				$icon = t3lib_iconworks::getIconImage('tx_templavoila_tmplobj',$row,$GLOBALS['BACK_PATH'],' align="top" title="UID: '.$this->displayUid.'"');
				$title = t3lib_BEfunc::getRecordTitle('tx_templavoila_tmplobj',$row,1);
				$content.=$icon.$title.'<br />';
				
					// Find the file:
				$theFile = t3lib_div::getFileAbsFileName($row['fileref'],1);
				if ($theFile && @is_file($theFile))	{
					$content.='File: '.$theFile;
				
						// Finding Data Source Record:
					$DSOfile='';	
					$dsValue = $row['datastructure'];
					if ($row['parent'])	{
						$parentRec = t3lib_BEfunc::getRecord('tx_templavoila_tmplobj',$row['parent'],'datastructure');
						$dsValue=$parentRec['datastructure'];
					}
					
					if (t3lib_div::testInt($dsValue))	{
						$DS_row = t3lib_BEfunc::getRecord('tx_templavoila_datastructure',$dsValue);
					} else {
						$DSOfile = t3lib_div::getFileAbsFileName($dsValue);
					}
					if (is_array($DS_row) || @is_file($DSOfile))	{
							
							// Get main DS array:
						if (is_array($DS_row))	{
								// Get title and icon:
							$icon = t3lib_iconworks::getIconImage('tx_templavoila_datastructure',$DS_row,$GLOBALS['BACK_PATH'],' align="top" title="UID: '.$DS_row['uid'].'"');
							$title = t3lib_BEfunc::getRecordTitle('tx_templavoila_datastructure',$DS_row,1);
							$content.='<h4>Data Source Record found:</h4>'.$icon.$title;

							$onCl = 'index.php?file='.rawurlencode($theFile).'&_load_ds_xml=1&_load_ds_xml_to='.$row['uid'];
							$content.='<a href="'.htmlspecialchars($onCl).'"> [Modify DS / TO]</a><br/>';
							
							$dataStruct = $this->getDataStructFromDSO($DS_row['dataprot']);
						} else {
							$content.='<h4>Data Source from FILE:</h4>'.$DSOfile.'<br />';
							$dataStruct = $this->getDataStructFromDSO('',$DSOfile);
						}
							
						if (is_array($dataStruct))	{
							unset($dataStruct['meta']);						
						
								// If that array contains sheets, then traverse them:
							if (is_array($dataStruct['sheets']))	{
								$dSheets = t3lib_div::resolveAllSheetsInDS($dataStruct);
								$dataStruct=array(
									'ROOT' => array (
										'tx_templavoila' => array (
											'title' => 'ROOT of multitemplate',
											'description' => 'Select the ROOT container for this template project. Probably just select a body-tag or some other HTML element which encapsulates ALL sub templates!',
										),
										'type' => 'array',
										'el' => array()
									)
								);
								foreach($dSheets['sheets'] as $nKey => $lDS)	{
									if (is_array($lDS['ROOT']))	{
										$dataStruct['ROOT']['el'][$nKey]=$lDS['ROOT'];
									}
								}
							}

								// Getting data from tmplobj
							$templatemapping = unserialize($row['templatemapping']);
							if (!is_array($templatemapping))	$templatemapping=array();

								// Get session data:							
							$sesDat = $GLOBALS['BE_USER']->getSessionData($this->MCONF['name'].'_mappingInfo');
							if (t3lib_div::GPvar('_reload_from') || t3lib_div::GPvar('_clear'))	{
								$currentMappingInfo = is_array($templatemapping['MappingInfo'])&&!t3lib_div::GPvar('_clear') ? $templatemapping['MappingInfo'] : array();
								$this->cleanUpMappingInfoAccordingToDS($currentMappingInfo,$dataStruct);
								$sesDat['currentMappingInfo'] = $currentMappingInfo;
								$sesDat['dataStruct'] = $dataStruct;
								$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
							} else {
								$currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : array();
								$this->cleanUpMappingInfoAccordingToDS($currentMappingInfo,$dataStruct);
								$inputData = t3lib_div::GPvar('dataMappingForm',1);
								if (t3lib_div::GPvar('_save_data_mapping') && is_array($inputData))	{
									$sesDat['currentMappingInfo'] = $currentMappingInfo = t3lib_div::array_merge_recursive_overrule($currentMappingInfo,$inputData);
									$GLOBALS['BE_USER']->setAndSaveSessionData($this->MCONF['name'].'_mappingInfo',$sesDat);
								}
							}
							
								// SAVE to template object
							if (t3lib_div::GPvar('_save_to'))	{
								$dataArr=array();
								$templatemapping['MappingInfo']=$currentMappingInfo;
									// Getting cached data:
								reset($dataStruct);
								$firstKey = key($dataStruct);
								if ($firstKey)	{
									$fileContent = t3lib_div::getUrl($theFile);
									$htmlParse = t3lib_div::makeInstance('t3lib_parsehtml');
									$relPathFix=dirname(substr($theFile,strlen(PATH_site))).'/';
 									$fileContent = $htmlParse->prefixResourcePath($relPathFix,$fileContent);
									$this->markupObj = t3lib_div::makeInstance('tx_templavoila_htmlmarkup');
									$contentSplittedByMapping=$this->markupObj->splitContentToMappingInfo($fileContent,$currentMappingInfo);
									$templatemapping['MappingData_cached']=$contentSplittedByMapping['sub'][$firstKey];
								}
								
								$dataArr['tx_templavoila_tmplobj'][$row['uid']]['templatemapping'] = serialize($templatemapping);
								$tce = t3lib_div::makeInstance("t3lib_TCEmain");
								$tce->stripslashes_values=0;
								$tce->start($dataArr,array());
								$tce->process_datamap();
								unset($tce);
								$content.='<strong>SAVED...</strong><br>';
								$row = t3lib_BEfunc::getRecord('tx_templavoila_tmplobj',$this->displayUid);
								$templatemapping = unserialize($row['templatemapping']);
							}

							$content.='<input type="submit" name="_clear" value="CLEAR ALL">';

							if (serialize($templatemapping['MappingInfo']) != serialize($currentMappingInfo))	{
								$content.='<input type="submit" name="_save_to" value="SAVE to Template Object">';
								$content.='<input type="submit" name="_reload_from" value="RELOAD from Template Object">';
								$content.='<br><strong>(Changes has been made.)</strong>';
							}
							$content.='<input type="submit" name="_preview" value="PREVIEW">';
							$content.='<h3>MAPPER:</h3>'.$this->renderTemplateMapper($theFile,$this->displayPath,$dataStruct,$currentMappingInfo);
						} else $content.='ERROR: No Data Source Record could be found with UID "'.$dsValue.'"';
					} else $content.='ERROR: No Data Source Record could be found with UID "'.$dsValue.'"';
				} else $content.='ERROR: The file "'.$row['fileref'].'" could not be found!';
			} else $content.='ERROR: No Template Object Record with the UID '.$this->displayUid;
			$this->content.=$this->doc->section('Template Object',$content,0,1);
		} else {
			$this->content.=$this->doc->section('Template Object ERROR','No UID was found pointing to a Template Object record.',0,1,3);
		}
	}

	/**
	 * Creates the template mapper table + form for either direct file mapping or Template Object
	 * 
	 * @param	string		The abs file name to read
	 * @param	string		The HTML-path to follow. Eg. 'td#content table[1] tr[1] / INNER | img[0]' or so. Normally comes from clicking a tag-image in the display frame.
	 * @param	array		The data Structure to map to
	 * @param	array		The current mapping information
	 * @return	string		HTML table.
	 */
	function renderTemplateMapper($displayFile,$path,$dataStruct=array(),$currentMappingInfo=array())	{
			// Get file content
		$this->markupFile = $displayFile;
		$fileContent = t3lib_div::getUrl($this->markupFile);
#		$content.='<h3>File:</h3>';
#		$content.='<p>'.$this->markupFile.' (Length: '.strlen($fileContent).' bytes)</p>';
					
			// Init mark up object.
		$this->markupObj = t3lib_div::makeInstance('tx_templavoila_htmlmarkup');
		
			// Load splitted content from currentMappingInfo array (used to show us which elements maps to some real content).
		$contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($fileContent,$currentMappingInfo);
#debug($contentSplittedByMapping);
#debug(t3lib_div::xml2tree(t3lib_div::getUrl(PATH_site.'fileadmin/_temp_/fco.xml')));

			// Show path:
		$pathRendered=t3lib_div::trimExplode('|',$path,1);
		$acc=array();
		foreach($pathRendered as $k => $v)	{
			$acc[]=$v;
			$pathRendered[$k]=$this->linkForDisplayOfPath($v,implode('|',$acc));
		}
		array_unshift($pathRendered,$this->linkForDisplayOfPath('[ROOT]',''));
#		$content.='<h3>Current path given:</h3>';
#		$content.='<p>'.implode(' | ',$pathRendered).'</p>';
			
			// Get attributes of the extracted content:
		$attrDat=array();
		$contentFromPath = $this->markupObj->splitByPath($fileContent,$path);	// ,'td#content table[1] tr[1]','td#content table[1]','map#cdf / INNER','td#content table[2] tr[1] td[1] table[1] tr[4] td.bckgd1[2] table[1] tr[1] td[1] table[1] tr[1] td.bold1px[1] img[1] / RANGE:img[2]'
		$firstTag = $this->markupObj->htmlParse->getFirstTag($contentFromPath[1]);
		list($attrDat) = $this->markupObj->htmlParse->get_tag_attributes($firstTag,1);

			// Make options:
		$pathLevels = $this->markupObj->splitPath($path);
		$lastEl = end($pathLevels);

		$optDat=array();
		$optDat[$lastEl['path']]='OUTER (Include tag)';
		$optDat[$lastEl['path'].'/INNER']='INNER (Exclude tag)';

			// Tags, which will trigger "INNER" to be listed on top (because it is almost always INNER-mapping that is needed)
		if (t3lib_div::inList('body,span,h1,h2,h3,h4,h5,h6,div,td,p,b,i,u,a',$lastEl['el']))	{
			$optDat =array_reverse($optDat);
		}

				
		$sameLevelElements = $this->markupObj->elParentLevel[$lastEl['parent']];
		if (is_array($sameLevelElements))	{
			$startFound=0;
			foreach($sameLevelElements as $rEl) 	{
				if ($startFound)	{
					$optDat[$lastEl['path'].'/RANGE:'.$rEl]='RANGE to "'.$rEl.'"';
				}
				if (trim($lastEl['parent'].' '.$rEl)==$lastEl['path'])	$startFound=1;
			}
		}
		
		if (is_array($attrDat))	{
			foreach($attrDat as $attrK => $v)	{
				$optDat[$lastEl['path'].'/ATTR:'.$attrK]='ATTRIBUTE "'.$attrK.'" (= '.t3lib_div::fixed_lgd($v,15).')';
			}
		}
		
		$PREVIEW = t3lib_div::GPvar('_preview');

			// MAKE form:
		$content.='
			<table border="0" cellspacing="2" cellpadding="2">
			<tr bgcolor="'.$this->doc->bgColor5.'">
				<td nowrap="nowrap"><strong>Data Element:</strong></td>
				<td nowrap="nowrap"><strong>Fieldname:</strong></td>
				<td nowrap="nowrap"><strong>'.(!$PREVIEW?'Mapping instructions:':'SAMPLE DATA:').'</strong><br /><img src="clear.gif" width="200" height="1" alt="" /></td>
				<td nowrap="nowrap"><strong>HTML-path:</strong></td>
				<td nowrap="nowrap"><strong>CMD:</strong></td>
				<td nowrap="nowrap"><strong>Rules:</strong></td>
			</tr>
			'.implode('',$this->drawDataStructureMap($dataStruct,1,$currentMappingInfo,$pathLevels,$optDat,$contentSplittedByMapping)).'</table>
			';
		
			// Make mapping window:
		$htmlPath = t3lib_div::GPvar('htmlPath');
		$mapElPath = t3lib_div::GPvar('mapElPath');
		$doMap = t3lib_div::GPvar('doMappingOfPath');
		$showPath = t3lib_div::GPvar('showPathOnly');
		$mappingToTags = t3lib_div::GPvar('mappingToTags');
		$limitTags = implode(',',array_keys($this->explodeMappingToTagsStr($mappingToTags,1)));
		if (($mapElPath && !$doMap) || $showPath || t3lib_div::GPvar('_preview'))	{
			$content.=
			'<h3>Visual Mapping Window:</h3>
			<p><strong>File:</strong> '.htmlspecialchars($displayFile).'</p>'.
			'<p>Display mode: '.t3lib_BEfunc::getFuncMenu('',"SET[displayMode]",$this->MOD_SETTINGS["displayMode"],$this->MOD_MENU["displayMode"],'',t3lib_div::implodeArrayForUrl('',$GLOBALS['HTTP_GET_VARS'],'',1,1)).'</p>';
			if (t3lib_div::GPvar('_preview'))	{
				$content.='<br />
					<p>Now previewing Data Structure sample data merged into the mapped tags:</p>'.
				$this->makeIframeForVisual($displayFile,'','',0,1);
			} else {
				$content.=($showPath ?
					'<br />
					<p>Now showing path "'.htmlspecialchars($htmlPath).'"</p>'	
						:
					'<br />
					<p>Mapping for '.$this->elNames[$mapElPath]['tx_templavoila']['title'].'</p>
					<p>Limiting to tags: <em>'.($limitTags?strtoupper($limitTags):'(ALL TAGS)').'</em></p>
					<p>Instructions: <em>'.$this->elNames[$mapElPath]['tx_templavoila']['description'].'</em></p>
					').
				$this->makeIframeForVisual($displayFile,$htmlPath,$limitTags,$doMap);
			}
		}
		
		return $content;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$dataStruct: ...
	 * @param	[type]		$currentMappingInfo: ...
	 * @param	[type]		$pathLevels: ...
	 * @param	[type]		$optDat: ...
	 * @param	[type]		$contentSplittedByMapping: ...
	 * @param	[type]		$level: ...
	 * @param	[type]		$tRows: ...
	 * @param	[type]		$formPrefix: ...
	 * @param	[type]		$path: ...
	 * @param	[type]		$path: ...
	 * @param	[type]		$mapOK: ...
	 * @return	[type]		...
	 */
	function drawDataStructureMap($dataStruct,$mappingMode=0,$currentMappingInfo=array(),$pathLevels=array(),$optDat=array(),$contentSplittedByMapping=array(),$level=0,$tRows=array(),$formPrefix='',$path='',$mapOK=1)	{
		
		$PREVIEW = t3lib_div::GPvar('_preview');
		
			// Data Structure array must be ... and array of course...
		if (is_array($dataStruct))	{
			foreach($dataStruct as $key => $value)	{
				if (is_array($value))	{	// The value of each entry must be an array.

						// ********************
						// Making the row:
						// ********************
					$rowCells=array();
					$bgColor = $this->doc->bgColor4;
						
						// Icon:
					if ($value['type']=='array')	{
						if (!$value['section'])	{
							$t='co';
							$tt='Container: ';
						} else {
							$t='sc';
							$tt='Sections: ';
						}
					} elseif ($value['type']=='attr')	{
						$t='at';
						$tt='Attribute: ';
					} else {
						$t='el';
						$tt='Element: ';
					}
					$icon = '<img src="item_'.$t.'.gif" width="24" height="16" border="0" alt="" title="'.$tt.$key.'" style="margin-right: 5px;" align="absmiddle" />';
					$this->elNames[$formPrefix.'['.$key.']']['tx_templavoila']['title'] = $icon.'<strong>'.htmlspecialchars(t3lib_div::fixed_lgd($value['tx_templavoila']['title'],30)).'</strong>';
					$rowCells['title'] = '<img src="clear.gif" width="'.($level*16).'" height="1" alt="" />'.$this->elNames[$formPrefix.'['.$key.']']['tx_templavoila']['title'];
						// Description:
					$this->elNames[$formPrefix.'['.$key.']']['tx_templavoila']['description'] = $rowCells['description'] = htmlspecialchars($value['tx_templavoila']['description']);

					if ($mappingMode)	{
							// HTML-path:
						$isMapOK = 0;
						if ($currentMappingInfo[$key]['MAP_EL'])	{
							if (isset($contentSplittedByMapping['cArray'][$key]))	{
								$cF = implode(chr(10),t3lib_div::trimExplode(chr(10),$contentSplittedByMapping['cArray'][$key],1));
								if (strlen($cF)>200)	{
									$cF = t3lib_div::fixed_lgd($cF,90).' '.t3lib_div::fixed_lgd_pre($cF,90);
								}
								list($pI) = $this->markupObj->splitPath($currentMappingInfo[$key]['MAP_EL']);
								$rowCells['htmlPath'] = '<img src="'.$GLOBALS['BACK_PATH'].'gfx/icon_ok2.gif" width="18" height="16" border="0" alt="" title="'.htmlspecialchars($cF?'Content found ('.strlen($contentSplittedByMapping['cArray'][$key]).' chars):'.chr(10).chr(10).$cF:'Content empty.').'" align="absmiddle" />'.
														'<img src="../html_tags/'.$pI['el'].'.gif" height="9" border="0" alt="" hspace="3" align="absmiddle" title="'.htmlspecialchars($currentMappingInfo[$key]['MAP_EL']).'">'.
														($pI['modifier'] ? $pI['modifier'].($pI['modifier_value']?':'.$pI['modifier_value']:''):'');
#														htmlspecialchars($currentMappingInfo[$key]['MAP_EL']);
								$rowCells['htmlPath'] = '<a href="'.$this->linkThisScript(array('htmlPath'=>$path.($path?'|':'').ereg_replace('\/[^ ]*$','',$currentMappingInfo[$key]['MAP_EL']),'showPathOnly'=>1)).'">'.$rowCells['htmlPath'].'</a>';
	
									// CMD links:
								$rowCells['cmdLinks'] = '<a href="'.$this->linkThisScript(array('mapElPath'=>$formPrefix.'['.$key.']','htmlPath'=>$path,'mappingToTags'=>$value['tx_templavoila']['tags'])).'">REMAP</a>';
								$rowCells['cmdLinks'].= '/<a href="'.$this->linkThisScript(array('mapElPath'=>$formPrefix.'['.$key.']','htmlPath'=>$path.($path?'|':'').$pI['path'],'doMappingOfPath'=>1)).'">CH_MODE</a>';
								$isMapOK=1;
							} else {
								$rowCells['htmlPath'] = '<img src="'.$GLOBALS['BACK_PATH'].'gfx/icon_warning.gif" width="18" height="16" border="0" alt="" title="No content found!" align="absmiddle" />'.htmlspecialchars($currentMappingInfo[$key]['MAP_EL']);
							}
						} else $rowCells['htmlPath'] = '&nbsp;';
	
							// CMD links:
						$mapElPath = t3lib_div::GPvar('mapElPath');
						if ($mapElPath == $formPrefix.'['.$key.']')	{
							if (t3lib_div::GPvar('doMappingOfPath'))	{
									// Creating option tags:
								$lastLevel = end($pathLevels);
								$tagsMapping = $this->explodeMappingToTagsStr($value['tx_templavoila']['tags']);
								$mapDat = is_array($tagsMapping[$lastLevel['el']]) ? $tagsMapping[$lastLevel['el']] : $tagsMapping['*'];
								unset($mapDat['']);
								if (is_array($mapDat) && !count($mapDat))	unset($mapDat);

								$didSetSel=0;
								$opt=array();
#								$opt[]='<option value="">[Select mapping mode!]</option>';
								foreach($optDat as $k => $v)	{
									list($pI) = $this->markupObj->splitPath($k);
#debug($pI);
#debug($mapDat);
									if (($value['type']=='attr' && $pI['modifier']=='ATTR') || ($value['type']!='attr' && $pI['modifier']!='ATTR'))	{
										if (
												(!$this->markupObj->tags[$lastLevel['el']]['single'] || $pI['modifier']!='INNER') && 
												(!is_array($mapDat) || ($pI['modifier']!='ATTR' && isset($mapDat[strtolower($pI['modifier']?$pI['modifier']:'outer')])) || ($pI['modifier']=='ATTR' && (isset($mapDat['attr']['*']) || isset($mapDat['attr'][$pI['modifier_value']]))))
												
											)	{

											if($k==$currentMappingInfo[$key]['MAP_EL'])	{
												$sel = ' selected="selected"';
												$didSetSel=1;
											} else {
												$sel = '';
											}
											$opt[]='<option value="'.htmlspecialchars($k).'"'.$sel.'>'.htmlspecialchars($v).'</option>';
										}
									}
								}
								if (!$didSetSel && $currentMappingInfo[$key]['MAP_EL'])	{		// IF no element was selected AND there is a value in the array $currentMappingInfo then we add an element holding this value!
#									$opt[]='<option value="'.htmlspecialchars($currentMappingInfo[$key]['MAP_EL']).'" selected="selected">'.htmlspecialchars('[ - CURRENT - ]').'</option>';
								}
									// Finally, put together the selector box:
								$rowCells['cmdLinks'] = '<img src="../html_tags/'.$lastLevel['el'].'.gif" height="9" border="0" alt="" align="absmiddle" title="'.htmlspecialchars($lastLevel['path']).'"><br/><select name="dataMappingForm'.$formPrefix.'['.$key.'][MAP_EL]">'.implode('',$opt).'</select><br><input type="submit" name="_save_data_mapping" value="Save"><input type="submit" name="_" value="Cancel">';
							} else {
								$rowCells['cmdLinks'] = '<span style="color:red;">Now, select element in window below...</span>';
							}
						} elseif (!$rowCells['cmdLinks'] && $mapOK && $value['type']!='no_map') {
							$rowCells['cmdLinks'] = '<a href="'.$this->linkThisScript(array('mapElPath'=>$formPrefix.'['.$key.']','htmlPath'=>$path,'mappingToTags'=>$value['tx_templavoila']['tags'])).'">MAP...</a>';
						}
					}

						// TAg rules:
					$rowCells['tagRules']=implode('<br />',t3lib_div::trimExplode(',',strtolower($value['tx_templavoila']['tags']),1));
					if (!$rowCells['tagRules'])	$rowCells['tagRules']='(ALL)';
					
					if ($this->editDataStruct)	{
						$editAddCol = '<a href="'.$this->linkThisScript(array('DS_element'=>$formPrefix.'['.$key.']')).'">'.
						'<img src="'.$GLOBALS['BACK_PATH'].'gfx/edit2.gif" width="11" height="12" hspace="2" border="0" alt="" title="Edit entry" />'.
						'</a>';
						$editAddCol.= '<a href="'.$this->linkThisScript(array('DS_element_DELETE'=>$formPrefix.'['.$key.']')).'">'.
						'<img src="'.$GLOBALS['BACK_PATH'].'gfx/garbage.gif" width="11" height="12" hspace="2" border="0" alt="" title="DELETE entry" onclick=" return confirm(\'Are you sure to delete this data structure entry?\');" />'.
						'</a>';
						$editAddCol = '<td>'.$editAddCol.'</td>';
					} else {
						$editAddCol = '';
					}

						// Put row together
					$tRows[]='<tr bgcolor="'.$bgColor.'">
						<td nowrap="nowrap" valign="top">'.$rowCells['title'].'</td>
						<td nowrap="nowrap" valign="top">'.$key.'</td>
						<td>'.(!$PREVIEW?$rowCells['description']:(is_array($value['tx_templavoila']['sample_data'])?t3lib_div::view_array($value['tx_templavoila']['sample_data']):'--')).'</td>
						'.($mappingMode 
								? 
							'<td nowrap="nowrap">'.$rowCells['htmlPath'].'</td>
							<td nowrap="nowrap">'.$rowCells['cmdLinks'].'</td>' 
								:
							''
						).'
						<td>'.$rowCells['tagRules'].'</td>
						'.$editAddCol.'
					</tr>';
					
					$addEditRows='';
					$placeBefore=0;
					if ($this->editDataStruct)	{
						if (t3lib_div::GPvar('DS_element') == $formPrefix.'['.$key.']')	{
#debug(t3lib_div::GPvar('DS_element'));
							$autokey='';
							if (t3lib_div::GPvar('DS_cmd')=='add')	{
								if (trim(t3lib_div::GPvar('fieldName'))!='[Enter new fieldname]' && trim(t3lib_div::GPvar('fieldName'))!='field_')	{
									$autokey = strtolower(ereg_replace('[^[:alnum:]_]','',trim(t3lib_div::GPvar('fieldName'))));
#debug($autokey);
#debug(array_keys($value['el']));
									if (isset($value['el'][$autokey]))	{
										$autokey.='_'.substr(md5(microtime()),0,2);
									}
								} else {
									$autokey='field_'.substr(md5(microtime()),0,6);
								}
								$formFieldName = 'autoDS'.$formPrefix.'['.$key.'][el]['.$autokey.']';
								$insertDataArray=array();
							} else {
								$formFieldName = 'autoDS'.$formPrefix.'['.$key.']';
								$insertDataArray=$value;
								$placeBefore=1;
							}

							$form = '
								Mapping Type:<br>
								<select name="'.$formFieldName.'[type]">
									<option value="">Element</option>
									<option value="array"'.($insertDataArray['type']=='array' ? ' selected="selected"' : '').'>Container for elements</option>
									<option value="attr"'.($insertDataArray['type']=='attr' ? ' selected="selected"' : '').'>Attribute</option>
									<option value="no_map"'.($insertDataArray['type']=='no_map' ? ' selected="selected"' : '').'>[Not mapped]</option>
								</select><br>
								<input type="hidden" name="'.$formFieldName.'[section]" value="0">
								'.(!$autokey && $insertDataArray['type']=='array' ? 
									'<input type="checkbox" value="1" name="'.$formFieldName.'[section]"'.($insertDataArray['section']?' checked="checked"':'').'> Make this container a SECTION!<br />' :
									''
								).'
								Title:<br>
								<input type="text" size="80" name="'.$formFieldName.'[tx_templavoila][title]" value="'.htmlspecialchars($insertDataArray['tx_templavoila']['title']).'"><br>
								Mapping instructions:<br>
								<input type="text" size="80" name="'.$formFieldName.'[tx_templavoila][description]" value="'.htmlspecialchars($insertDataArray['tx_templavoila']['description']).'"><br>

								'.($insertDataArray['type']!='array' ? '
								Sample Data:<br>
								<input type="text" size="80" name="'.$formFieldName.'[tx_templavoila][sample_data][]" value="'.htmlspecialchars($insertDataArray['tx_templavoila']['sample_data'][0]).'"><br>

								Editing Type:<br>
								<select name="'.$formFieldName.'[tx_templavoila][eType]">
									<option value="input"'.($insertDataArray['tx_templavoila']['eType']=='input' ? ' selected="selected"' : '').'>Plain input field</option>
									<option value="input_h"'.($insertDataArray['tx_templavoila']['eType']=='input_h' ? ' selected="selected"' : '').'>Header field</option>
									<option value="input_g"'.($insertDataArray['tx_templavoila']['eType']=='input_g' ? ' selected="selected"' : '').'>Header field, Graphical</option>
									<option value="text"'.($insertDataArray['tx_templavoila']['eType']=='text' ? ' selected="selected"' : '').'>Text area for bodytext</option>
									<option value="link"'.($insertDataArray['tx_templavoila']['eType']=='link' ? ' selected="selected"' : '').'>Link field</option>
									<option value="int"'.($insertDataArray['tx_templavoila']['eType']=='int' ? ' selected="selected"' : '').'>Integer value</option>
									<option value="image"'.($insertDataArray['tx_templavoila']['eType']=='image' ? ' selected="selected"' : '').'>Image field</option>
									<option value="imagefixed"'.($insertDataArray['tx_templavoila']['eType']=='imagefixed' ? ' selected="selected"' : '').'>Image field, fixed W+H</option>
									<option value="ce"'.($insertDataArray['tx_templavoila']['eType']=='ce' ? ' selected="selected"' : '').'>Content Elements</option>
									<option value="select"'.($insertDataArray['tx_templavoila']['eType']=='select' ? ' selected="selected"' : '').'>Selector box</option>
									<option value="none"'.($insertDataArray['tx_templavoila']['eType']=='none' ? ' selected="selected"' : '').'>[ NONE ]</option>
									<option value="TypoScriptObject"'.($insertDataArray['tx_templavoila']['eType']=='TypoScriptObject' ? ' selected="selected"' : '').'>TypoScript Object Path</option>
								</select><br>

								[Advanced] Mapping rules:<br>
								<input type="text" size="80" name="'.$formFieldName.'[tx_templavoila][tags]" value="'.htmlspecialchars($insertDataArray['tx_templavoila']['tags']).'"><br>

								' :'').'

								<input type="submit" name="_updateDS" value="Add">
<!--								<input type="submit" name="'.$formFieldName.'" value="Delete (!)">  -->
								<input type="submit" name="_" value="Cancel"><br>
							';
							$addEditRows='<tr bgcolor="'.$bgColor.'">
								<td nowrap="nowrap" valign="top">'.
								(t3lib_div::GPvar('DS_cmd')=='add' ? '<img src="clear.gif" width="'.(($level+1)*16).'" height="1" alt="" /><strong>NEW FIELD:</strong> '.$autokey : '').
								'</td>
								<td colspan="5">'.$form.'</td>
							</tr>';							
						} elseif (!t3lib_div::GPvar('DS_element') && $value['type']=='array') {
							$addEditRows='<tr bgcolor="'.$bgColor.'">
								<td colspan="6"><img src="clear.gif" width="'.(($level+1)*16).'" height="1" alt="" />'.
								'<input type="text" name="'.md5($formPrefix.'['.$key.']').'" value="[Enter new fieldname]" onfocus="if (this.value==\'[Enter new fieldname]\'){this.value=\'field_\';}">'.
								'<input type="submit" name="_" value="Add" onclick="document.location=\''.$this->linkThisScript(array('DS_element'=>$formPrefix.'['.$key.']','DS_cmd'=>'add')).'&amp;fieldName=\'+document.pageform[\''.md5($formPrefix.'['.$key.']').'\'].value; return false;">'.
								'</td>
							</tr>';							
						}
					}

					if ($addEditRows && $placeBefore)	{
						$tRows[]= $addEditRows;
					}

						// Recursive call:
					if ($value['type']=='array')	{
						$tRows = $this->drawDataStructureMap(
							$value['el'],
							$mappingMode,
							$currentMappingInfo[$key]['el'],
							$pathLevels,
							$optDat,
							$contentSplittedByMapping['sub'][$key],
							$level+1,
							$tRows,
							$formPrefix.'['.$key.'][el]',
							$path.($path?'|':'').$currentMappingInfo[$key]['MAP_EL'],
							$isMapOK
						);
					}
					
					if ($addEditRows && !$placeBefore)	{
						$tRows[]= $addEditRows;
					}

				}
			}
		}

		return $tRows;		
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$$elArray: ...
	 * @param	[type]		$v_sub: ...
	 * @return	[type]		...
	 */
	function substEtypeWithRealStuff(&$elArray,$v_sub=array())	{
		foreach($elArray as $key => $value)	{

		if ($elArray[$key]['type']!='array')	{
			
			$contentInfo = $this->substEtypeWithRealStuff_contentInfo(trim($v_sub['cArray'][$key]));
#			debug($contentInfo);
		
			switch($elArray[$key]['tx_templavoila']['eType'])	{
				case 'text':
					$elArray[$key]['TCEforms']['config'] = array(
						'type' => 'text',
						'cols' => '48',
						'rows' => '5',
					);
					$elArray[$key]['tx_templavoila']['proc']['HSC']=1;
				break;
				case 'image':
				case 'imagefixed':
					$elArray[$key]['TCEforms']['config'] = array(
						'type' => 'group',
						'internal_type' => 'file',
						'allowed' => 'gif,png,jpg,jpeg',
						'max_size' => '1000',
						'uploadfolder' => 'uploads/tx_templavoila',
						'show_thumbs' => '1',
						'size' => '1',
						'maxitems' => '1',
						'minitems' => '0'
					);
					
					$maxW = $contentInfo['img']['width'] ? $contentInfo['img']['width'] : 200;
					$maxH = $contentInfo['img']['height'] ? $contentInfo['img']['height'] : 150;
					
					if ($elArray[$key]['tx_templavoila']['eType']=='image')	{
						$elArray[$key]['tx_templavoila']['TypoScript'] = '
10 = IMAGE
10.file.import = uploads/tx_templavoila/
10.file.import.current = 1
10.file.import.listNum = 0
10.file.maxW = '.$maxW.'
					';
					} else {
						$elArray[$key]['tx_templavoila']['TypoScript'] = '
10 = IMAGE
10.file = GIFBUILDER
10.file {
	XY = '.$maxW.','.$maxH.'
	10 = IMAGE
	10.file.import = uploads/tx_templavoila/
	10.file.import.current = 1
	10.file.import.listNum = 0
	10.file.maxW = '.$maxW.'
	10.file.minW = '.$maxW.'
	10.file.maxH = '.$maxH.'
	10.file.minH = '.$maxH.'
}
					';					
					}
					
						// Finding link-fields on same level and set the image to be linked by that TypoLink:
					$elArrayKeys = array_keys($elArray);
					foreach($elArrayKeys as $theKey)	{
						if ($elArray[$theKey]['tx_templavoila']['eType']=='link')	{
							$elArray[$key]['tx_templavoila']['TypoScript'].= '
10.stdWrap.typolink.parameter.field = '.$theKey.'
							';
						}
					}
				break;
				case 'link':
					$elArray[$key]['TCEforms']['config'] = array(
						'type' => 'input',		
						'size' => '15',
						'max' => '256',
						'checkbox' => '',
						'eval' => 'trim',
						'wizards' => Array(
							'_PADDING' => 2,
							'link' => Array(
								'type' => 'popup',
								'title' => 'Link',
								'icon' => 'link_popup.gif',
								'script' => 'browse_links.php?mode=wizard',
								'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
							)
						)
					);
					
					$elArray[$key]['tx_templavoila']['TypoScript'] = '
10 = TEXT
10.typolink.parameter.current = 1
10.typolink.returnLast = url
					';
					$elArray[$key]['tx_templavoila']['proc']['HSC']=1;
				break;
				case 'ce':
					$elArray[$key]['TCEforms']['config'] = array(
						'type' => 'group',
						'internal_type' => 'db',
						'allowed' => 'tt_content',
						'size' => '5',
						'maxitems' => '200',
						'minitems' => '0',
						'show_thumbs' => '1'
					);
					$elArray[$key]['tx_templavoila']['TypoScript'] = '
10= RECORDS
10.source.current=1
10.tables = tt_content
					';
				break;
				case 'int':
					$elArray[$key]['TCEforms']['config'] = array(
						'type' => 'input',	
						'size' => '4',
						'max' => '4',
						'eval' => 'int',
						'checkbox' => '0',
						'range' => Array (
							'upper' => '999',
							'lower' => '25'
						),
						'default' => 0
					);
				break;
				case 'select':
					$elArray[$key]['TCEforms']['config'] = array(
						'type' => 'select',		
						'items' => Array (	
							Array('', ''),
							Array('Value 1', 'Value 1'),
							Array('Value 2', 'Value 2'),
							Array('Value 3', 'Value 3'),
						),
						'default' => '0'
					);
				break;
				case 'input':
				case 'input_h':
				case 'input_g':
					$elArray[$key]['TCEforms']['config'] = array(
						'type' => 'input',
						'size' => '48',
						'eval' => 'trim',
					);
					
					if ($elArray[$key]['tx_templavoila']['eType']=='input_h')	{
							// Finding link-fields on same level and set the image to be linked by that TypoLink:
						$elArrayKeys = array_keys($elArray);
						foreach($elArrayKeys as $theKey)	{
							if ($elArray[$theKey]['tx_templavoila']['eType']=='link')	{
								$elArray[$key]['tx_templavoila']['TypoScript'] = '
10 = TEXT
10.current = 1
10.typolink.parameter.field = '.$theKey.'
								';
							}
						}					
					} elseif ($elArray[$key]['tx_templavoila']['eType']=='input_g')	{

					$maxW = $contentInfo['img']['width'] ? $contentInfo['img']['width'] : 200;
					$maxH = $contentInfo['img']['height'] ? $contentInfo['img']['height'] : 20;
					
								$elArray[$key]['tx_templavoila']['TypoScript'] = '
10 = IMAGE
10.file = GIFBUILDER
10.file {				  
  XY = '.$maxW.','.$maxH.'
  backColor = #999999

  10 = TEXT
  10.text.current = 1
  10.text.case = upper
  10.fontColor = #FFCC00
  10.fontFile =  t3lib/fonts/vera.ttf
  10.niceText = 0
  10.offset = 0,14
  10.fontSize = 14				  
}
								';
					
					} else {
						$elArray[$key]['tx_templavoila']['proc']['HSC']=1;
					}
				break;
				case 'TypoScriptObject':
					unset($elArray[$key]['TCEforms']['config']);

					$elArray[$key]['tx_templavoila']['TypoScriptObjPath'] = 'lib.myObject';
				break;
				case 'none':
					unset($elArray[$key]['TCEforms']['config']);
				break;
			}
			
			if (is_array($elArray[$key]['TCEforms']['config']))	{
				$elArray[$key]['TCEforms']['label']=$elArray[$key]['tx_templavoila']['title'];
			}

		} else {
			unset($elArray[$key]['tx_templavoila']['sample_data']);
		}
		if (!$elArray[$key]['section'])	unset($elArray[$key]['section']);
		if (!$elArray[$key]['type'])	unset($elArray[$key]['type']);
		if (!$elArray[$key]['tx_templavoila']['description'])	unset($elArray[$key]['tx_templavoila']['description']);
		if (!$elArray[$key]['tx_templavoila']['tags'])	unset($elArray[$key]['tx_templavoila']['tags']);
		
			if (is_array($elArray[$key]['el']))	{
				$this->substEtypeWithRealStuff($elArray[$key]['el'],$v_sub['sub'][$key]);
			}
		}
	}

	/**
	 * Analyzes the input content for various stuff which can be used to generate the DS.
	 * 
	 * @param	[type]		$content: ...
	 * @return	[type]		...
	 */
	function substEtypeWithRealStuff_contentInfo($content)	{
		if ($content)	{
			if (substr($content,0,4)=='<img')	{
				$attrib = t3lib_div::get_tag_attributes($content);
				if ((!$attrib['width'] || !$attrib['height']) && $attrib['src'])	{
					$pathWithNoDots = t3lib_div::resolveBackPath($attrib['src']);
					$filePath = t3lib_div::getFileAbsFileName($pathWithNoDots);
					if ($filePath && @is_file($filePath))	{
						$imgInfo = @getimagesize($filePath);
						
						if (!$attrib['width'])	$attrib['width']=$imgInfo[0];
						if (!$attrib['height'])	$attrib['height']=$imgInfo[1];
						#debug($imgInfo);
					}
				}
				return array('img'=>$attrib);
			}
		}
	}











	/**
	 * Returns data structure from the $datString
	 * 
	 * @param	[type]		$datString: ...
	 * @param	[type]		$file: ...
	 * @return	mixed		The variable $dataStruct. Should be array. If string, then no structures was found and the function returns the XML parser error.
	 */
	function getDataStructFromDSO($datString,$file='')	{
		if ($file)	{
			$dataStruct = t3lib_div::xml2array(t3lib_div::getUrl($file));
		} else {
			$dataStruct = t3lib_div::xml2array($datString);
		}
		return $dataStruct;
	}

	/**
	 * Creating a link to the display frame for display of the "HTML-path" given as $path
	 * 
	 * @param	string		The text to link
	 * @param	string		The path string ("HTML-path")
	 * @return	string		HTML link, pointing to the display frame.
	 */
	function linkForDisplayOfPath($title,$path)	{
		$theArray=array(
			'file' => $this->markupFile,
			'path' => $path,
			'mode' => 'display'
		);
		$p = t3lib_div::implodeArrayForUrl('',$theArray);	
		
		$content.='<strong><a href="'.htmlspecialchars('index.php?'.$p).'" target="display">'.$title.'</a></strong>';
		return $content;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$array: ...
	 * @return	[type]		...
	 */
	function linkThisScript($array)	{
		$theArray=array(
			'file' => $this->displayFile,
			'table' => $this->displayTable,
			'uid' => $this->displayUid,
			'displayMode' => $this->displayMode,
		);
		$p = t3lib_div::implodeArrayForUrl('',array_merge($theArray,$array),'',1);	
		
		return htmlspecialchars('index.php?'.$p);
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$file: ...
	 * @param	[type]		$path: ...
	 * @param	[type]		$limitTags: ...
	 * @param	[type]		$showOnly: ...
	 * @param	[type]		$preview: ...
	 * @return	[type]		...
	 */
	function makeIframeForVisual($file,$path,$limitTags,$showOnly,$preview=0)	{
		$url = 'index.php?mode=display'.
				'&file='.rawurlencode($file).
				'&path='.rawurlencode($path).
				'&preview='.($preview?1:0).
				($showOnly?'&show=1':'&limitTags='.rawurlencode($limitTags));
		return '<iframe width="100%" height="500" src="'.htmlspecialchars($url).'#_MARKED_UP_ELEMENT" style="border: 1xpx solid black;"></iframe>';
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$mappingToTags: ...
	 * @param	[type]		$unsetAll: ...
	 * @return	[type]		...
	 */
	function explodeMappingToTagsStr($mappingToTags,$unsetAll=0)	{
		$elements = t3lib_div::trimExplode(',',strtolower($mappingToTags));
		$output=array();
		foreach($elements as $v)	{
			$subparts = t3lib_div::trimExplode(':',$v);
			$output[$subparts[0]][$subparts[1]][($subparts[2]?$subparts[2]:'*')]=1;
		}
		if ($unsetAll)	unset($output['*']);
		return $output;
	}
	
	function unsetArrayPath(&$dataStruct,$ref)	{
		$key = array_shift($ref);

		if (!count($ref))	{
			unset($dataStruct[$key]);
		} elseif (is_array($dataStruct[$key]))	{
			$this->unsetArrayPath($dataStruct[$key],$ref);
		}
	}
	
	function cleanUpMappingInfoAccordingToDS(&$currentMappingInfo,$dataStruct)	{	
		if (is_array($currentMappingInfo))	{
			foreach($currentMappingInfo as $key => $value)	{
				if (!isset($dataStruct[$key]))	{
					unset($currentMappingInfo[$key]);
				} else {
					if (is_array($dataStruct[$key]['el']))	{
						$this->cleanUpMappingInfoAccordingToDS($currentMappingInfo[$key]['el'],$dataStruct[$key]['el']);
					}
				}
			}
		}
/*
		debug(array(
			$currentMappingInfo,$dataStruct
		));*/
	}
	






	/*****************************************
	 *
	 * DISPLAY
	 *
	 *****************************************/
	 
	/**
	 * Outputs the display for the display frame...
	 * 
	 * @return	void		Exits...
	 */
	function main_display()	{
		$displayFile = t3lib_div::GPvar('file');
		$this->theDisplayMode=$this->MOD_SETTINGS['displayMode'];
				
		if (@is_file($displayFile) && t3lib_div::getFileAbsFileName($displayFile))		{	// FUTURE: grabbing URLS?: 		.... || substr($displayFile,0,7)=='http://'
			$content = t3lib_div::getUrl($displayFile);
			if ($content)	{
				$relPathFix = $GLOBALS['BACK_PATH'].'../'.dirname(substr($displayFile,strlen(PATH_site))).'/';
				
				if (t3lib_div::GPvar('preview'))	{
					$sesDat = $GLOBALS['BE_USER']->getSessionData($this->MCONF['name'].'_mappingInfo');
					$currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : array();

						// Init mark up object.
					$this->markupObj = t3lib_div::makeInstance('tx_templavoila_htmlmarkup');
					$this->markupObj->htmlParse = t3lib_div::makeInstance('t3lib_parsehtml');
					
					$contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($content,$currentMappingInfo);
					$token = md5(microtime());

#debug($sesDat['dataStruct']);

					$content = $this->markupObj->mergeSampleDataIntoTemplateStructure($sesDat['dataStruct'],$contentSplittedByMapping,$token);
					$pp = explode($token,$content);
					foreach($pp as $kk => $vv)	{
						$pp[$kk] = $this->markupObj->passthroughHTMLcontent($vv,$relPathFix,$this->theDisplayMode,$kk==1?'font-size:11px; color:#000066;':'');
					}
					if (trim($pp[0]))	{
						$pp[1]='<a name="_MARKED_UP_ELEMENT"></a>'.$pp[1];
					}					
					$content = implode('',$pp);
					echo $content;
				} else {
					$path = t3lib_div::GPvar('path');
					$content = $this->displayFileContentWithMarkup($content,$path,$relPathFix,t3lib_div::GPvar('limitTags'));
					echo $content;
				}
			} else echo '<strong>ERROR:</strong> No content found in file reference: <em>'.htmlspecialchars($displayFile).'</em>';
		} else {
			echo '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
	<title>Untitled</title>
</head>

<body bgcolor="#eeeeee">
<h2>No file to display... awaiting parameters.</h2>

</body>
</html>
			';
		}
		exit;
	}
	
	/**
	 * This will mark up the part of the HTML file which is pointed to by $path
	 * 
	 * @param	string		The file content as a string
	 * @param	string		The "HTML-path" to split by
	 * @param	string		The rel-path string to fix images/links with.
	 * @param	[type]		$limitTags: ...
	 * @return	void		Exits...
	 */
	function displayFileContentWithMarkup($content,$path,$relPathFix,$limitTags)	{
		$markupObj = t3lib_div::makeInstance('tx_templavoila_htmlmarkup');
		$markupObj->gnyfImgAdd = t3lib_div::GPvar('show')?'':'onclick="return parent.updPath(\'###PATH###\');"';		
		$markupObj->pathPrefix = $path?$path.'|':'';
		$markupObj->onlyElements = $limitTags;
		
		$cParts = $markupObj->splitByPath($content,$path);
		if (is_array($cParts))	{
			$cParts[1] = $markupObj->markupHTMLcontent(
							$cParts[1],
							$GLOBALS['BACK_PATH'],
							$relPathFix,
							implode(',',array_keys($markupObj->tags)),
							$this->theDisplayMode
						);
			$cParts[0] = $markupObj->passthroughHTMLcontent($cParts[0],$relPathFix,$this->theDisplayMode);
			$cParts[2] = $markupObj->passthroughHTMLcontent($cParts[2],$relPathFix,$this->theDisplayMode);
			if (trim($cParts[0]))	{
				$cParts[1]='<a name="_MARKED_UP_ELEMENT"></a>'.$cParts[1];
			}
			return implode('',$cParts);
		} else {
			echo '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
	<title>Untitled</title>
</head>

<body bgcolor="#eeeeee">
<h2>ERROR: '.$cParts.'</h2>
</body>
</html>
			';			
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/cm1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/cm1/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('tx_templavoila_cm1');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>