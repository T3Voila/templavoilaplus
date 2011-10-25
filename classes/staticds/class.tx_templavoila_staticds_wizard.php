<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2009 Steffen Kamper (info@sk-typo3.de)
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
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
 * Class for userFuncs within the Extension Manager.
 *
 * @author	Steffen Kamper  <info@sk-typo3.de>
 */
class tx_templavoila_staticds_wizard {

	/**
	 * Step for the wizard. Can be manipulated by internal function
	 *
	 * @var int $step
	 */
	protected $step = 0;

	/**
	 *
	 * @param		array		Parameter array.  Contains fieldName and fieldValue.
	 * @param		object		Instance of the class t3lib_tsStyleConfig
	 */
	public function staticDsWizard() {
		$this->step = t3lib_div::_GP('dsWizardDoIt') ? intval(t3lib_div::_GP('dsWizardStep')) : 0;
		$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);

		$title = $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.title.' . $this->step);
		$description = $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.description.' . $this->step);
		$out = '<h2>' . htmlspecialchars($title) . '</h2>';

		$controls = '';

		switch ($this->step) {
			case 1:
				$ok = array(TRUE, TRUE);
				if (t3lib_div::_GP('dsWizardDoIt')) {

					if (!isset($conf['staticDS.']['path_fce']) || !strlen($conf['staticDS.']['path_fce'])) {
						$ok[0] = FALSE;
						$description .= sprintf('||' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.dircheck.notset'), 'staticDS.path_fce');
					} else {
						$ok[0] = $this->checkDirectory($conf['staticDS.']['path_fce']);
						if ($ok[0]) {
							$description .= sprintf('||' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.dircheck.ok'), htmlspecialchars($conf['staticDS.']['path_fce']));
						} else {
							$description .= sprintf('||' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.dircheck.notok'), htmlspecialchars($conf['staticDS.']['path_fce']));
						}
					}

					if (!isset($conf['staticDS.']['path_page']) || !strlen($conf['staticDS.']['path_page'])) {
						$ok[0] = FALSE;
						$description .= sprintf('||' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.dircheck.notset'), 'staticDS.path_page');
					} else {
						$ok[1] = $this->checkDirectory($conf['staticDS.']['path_page']);
						if ($ok[1]) {
							$description .= sprintf('|' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.dircheck.ok'), htmlspecialchars($conf['staticDS.']['path_page']));
						} else {
							$description .= sprintf('|' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.dircheck.notok'), htmlspecialchars($conf['staticDS.']['path_page']));
						}
					}
					if ($ok == array(TRUE, TRUE)) {
						$controls .= $this->getDsRecords($conf['staticDS.']);
					}
				}
				if ($ok == array(TRUE, TRUE) && $this->step < 3) {
					$submitText = $conf['staticDS.']['enable']
							? $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.submit3')
							: $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.submit2');
					$controls .= '<br /><input type="hidden" name="dsWizardStep" value="1" />
					<input type="submit" name="dsWizardDoIt" value="' . $submitText . '" />';
				}
				break;
			default:
				$controls .= '<input type="hidden" name="dsWizardStep" value="1" />
				<input type="submit" name="dsWizardDoIt" value="' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.submit1') . '" />';
				break;
		}


		$out .= '<p style="margin-bottom: 10px;">' . str_replace('|', '<br />', $description) . '</p>' .
				'<p style="margin-top: 5px;">' . $controls . '</p>';

		return '<form action="#" method="POST">' . $out . '</form>';
	}

	/**
	 *
	 * @param	string		 $path
	 * @return	boolean		TRUE if directory exists and is writable or could be created
	 */
	protected function checkDirectory($path) {
		$status = FALSE;
		$path = $path . (substr($path, -1) == '/' ? '' : '/');
		if (@is_writable(PATH_site . $path)) {
			$status = TRUE;
		}
		if (!is_dir(PATH_site . $path)) {
			$errors = t3lib_div::mkdir_deep(PATH_site, $path);
			if ($errors === NULL) {
				$status = TRUE;
			}
		}
		return $status;
	}

	/**
	 *
	 * @param	array	$conf
	 */
	protected function getDsRecords($conf) {
		$updateMessage = '';
		$writeDsIds = array();
		$writeIds = t3lib_div::_GP('staticDSwizard');
		$options = t3lib_div::_GP('staticDSwizardoptions');
		$checkAll = t3lib_div::_GP('sdw-checkall');

		if (count($writeIds)) {
			$writeDsIds = array_keys($writeIds);
		}
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_templavoila_datastructure',
			'deleted=0',
			'',
			'scope, title'
		);
		$out = '<table id="staticDSwizard_getdsrecords"><thead>
			<tr class="bgColor5">
				<td style="vertical-align:middle;">' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.uid') . '</td>
				<td style="vertical-align:middle;">' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.pid') . '</td>
				<td style="vertical-align:middle;">' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.title') . '</td>
				<td style="vertical-align:middle;">' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.scope') . '</td>
				<td style="vertical-align:middle;">' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.usage') . '</td>
			<td>
				<label for="sdw-checkall">' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.selectall') . '</label>
				<input type="checkbox" class="checkbox" id="sdw-checkall" name="sdw-checkall" onclick="$$(\'.staticDScheck\').each(function(e){e.checked=$(\'sdw-checkall\').checked;});" value="1" ' . ($checkAll
				? 'checked="checked"' : '') . ' /></td>
		</tr></thead><tbody>';
		foreach ($rows as $row) {
			$dirPath = PATH_site . ($row['scope'] == 2 ? $conf['path_fce'] : $conf['path_page']);
			$dirPath = $dirPath . (substr($dirPath, -1) == '/' ? '' : '/');
			$title = preg_replace('|[/,\."\']+|', '_', $row['title']);
			$path = $dirPath . $title . ' (' . ($row['scope'] == 1 ? 'page' : 'fce') . ').xml';
			$outPath = substr($path, strlen(PATH_site));

			$usage = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'count(*)',
				'tx_templavoila_tmplobj',
				'datastructure=' . $row['uid'] . t3lib_BEfunc::BEenableFields('tx_templavoila_tmplobj')
			);
			if (count($writeDsIds) && in_array($row['uid'], $writeDsIds)) {
				t3lib_div::writeFile($path, $row['dataprot']);
				if ($row['previewicon']) {
					copy(PATH_site . 'uploads/tx_templavoila/' . $row['previewicon'], $dirPath . $title . ' (' . ($row['scope'] == 1
							? 'page' : 'fce') . ').gif');
				}
				if ($options['updateRecords']) {
					// remove DS records
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
						'tx_templavoila_datastructure',
						'uid="' . $row['uid'] . '"',
						array('deleted' => 1)
					);
					// update TO records
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
						'tx_templavoila_tmplobj',
						'datastructure="' . $row['uid'] . '"',
						array('datastructure' => $outPath)
					);
					// update page records
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
						'pages',
						'tx_templavoila_ds="' . $row['uid'] . '"',
						array('tx_templavoila_ds' => $outPath)
					);
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
						'pages',
						'tx_templavoila_next_ds="' . $row['uid'] . '"',
						array('tx_templavoila_next_ds' => $outPath)
					);
					// update tt_content records
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
						'tt_content',
						'tx_templavoila_ds="' . $row['uid'] . '"',
						array('tx_templavoila_ds' => $outPath)
					);
					// delete DS records
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_templavoila_datastructure', 'uid=' . $row['uid'], array('deleted' => 1));
					$updateMessage = $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.updated');
					$this->step = 3;
				}


			}
			$out .= '<tr class="bgColor' . ($row['scope'] == 1 ? 3 : 6) . '">
			<td style="text-align: center;padding: 0,3px;">' . $row['uid'] . '</td>
			<td style="text-align: center;padding: 0,3px;">' . $row['pid'] . '</td>
			<td style="padding: 0,3px;">' . htmlspecialchars($row['title']) . '</td>
			<td style="padding: 0,3px;">' . ($row['scope'] == 1 ? 'Page' : 'FCE') . '</td>
			<td style="text-align: center;padding: 0,3px;">' . $usage[0]['count(*)'] . '</td>';
			if (count($writeDsIds) && in_array($row['uid'], $writeDsIds)) {
				$out .= '<td class="nobr" style="text-align: right;padding: 0,3px;">written to "' . $outPath . '"</td>';
			} else {
				$out .= '<td class="nobr" style="text-align: right;padding: 0,3px;"><input type="checkbox" class="checkbox staticDScheck" name="staticDSwizard[' . $row['uid'] . ']" value="1" /></td>';
			}
			$out .= '</tr>';
		}
		$out .= '</tbody></table>';

		if ($conf['enable']) {
			if ($updateMessage) {
				$out .= '<p>' . $updateMessage . '</p><p><strong>' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.clearcache') . '</strong></p>';
			} else {
				$out .= '<h4>' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.description2.1') . '</h4>';
				$out .= '<p>
				<input type="checkbox" class="checkbox" name="staticDSwizardoptions[updateRecords]" id="sdw-updateRecords" value="1" />
				<label for="sdw-updateRecords">' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/res1/language/template_conf.xml:staticDS.wizard.updaterecords') . '</label><br />
				</p>';
			}
		}
		return $out;
	}

	/**
	 * @return int
	 */
	protected function datastructureDbCount() {
		return $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'tx_templavoila_datastructure', 'deleted=0');
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/class.tx_templavoila_staticds_wizard.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/classes/class.tx_templavoila_staticds_wizard.php']);
}
?>