<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012  Kay Strobach (typo3@kay-strobach.de)
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
 * This wizard renames a field in pages.tx_templavoila_flex, to avoid
 * a remapping
 *
 * $Id$
 *
 * @author     Kay Strobach <typo3@kay-strobach.de>
 */
class tx_templavoila_renameFieldInPageFlexWizard extends t3lib_extobjbase {

	/**
	 * @return string
	 */
	public function main() {
		if ($GLOBALS['BE_USER']->isAdmin()) {
			if (intval($this->pObj->id) > 0) {
				return $this->showForm() . $this->executeCommand();
			} else {
				// should never happen, as function module catches this already,
				// but save is save ;)
				return 'Please select a page from the tree';
			}
		} else {
			$message = new t3lib_FlashMessage(
				'Module only available for admins.',
				'',
				t3lib_FlashMessage::ERROR
			);

			return $message->render();
		}
	}

	/**
	 * @param integer $uid
	 *
	 * @return array
	 */
	protected function getAllSubPages($uid) {
		$completeRecords = t3lib_BEfunc::getRecordsByField('pages', 'pid', $uid);
		$return = array($uid);
		if (count($completeRecords) > 0) {
			foreach ($completeRecords as $record) {
				$return = array_merge($return, $this->getAllSubPages($record['uid']));
			}
		}

		return $return;
	}

	/**
	 * @return string
	 */
	protected function executeCommand() {
		if (t3lib_div::_GP('executeRename') == 1) {
			$buffer = '';
			if (t3lib_div::_GP('sourceField') === t3lib_div::_GP('destinationField')) {
				$message = new t3lib_FlashMessage(
					'Renaming a field to itself is senseless, execution aborted.',
					'',
					t3lib_FlashMessage::ERROR
				);

				return $message->render();
			}
			$escapedSource = $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . t3lib_div::_GP('sourceField') . '%', 'pages');
			$escapedDest = $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . t3lib_div::_GP('destinationField') . '%', 'pages');

			$condition = 'tx_templavoila_flex LIKE ' . $escapedSource
				. ' AND NOT tx_templavoila_flex LIKE ' . $escapedDest . ' '
				. ' AND uid IN ('
				. implode(',', $this->getAllSubPages($this->pObj->id)) . ')';

			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'uid, title',
				'pages',
				$condition
			);
			if (count($rows) > 0) {
				// build message for simulation
				$mbuffer = 'Affects ' . count($rows) . ': <ul>';
				foreach ($rows as $row) {
					$mbuffer .= '<li>' . htmlspecialchars($row['title']) . ' (uid: ' . intval($row['uid']) . ')</li>';
				}
				$mbuffer .= '</ul>';
				$message = new t3lib_FlashMessage($mbuffer, '', t3lib_FlashMessage::INFO);
				$buffer .= $message->render();
				unset($mbuffer);
				//really do it
				if (!t3lib_div::_GP('simulateField')) {
					$escapedSource = $GLOBALS['TYPO3_DB']->fullQuoteStr(t3lib_div::_GP('sourceField'), 'pages');
					$escapedDest = $GLOBALS['TYPO3_DB']->fullQuoteStr(t3lib_div::_GP('destinationField'), 'pages');
					$GLOBALS['TYPO3_DB']->admin_query('
						UPDATE pages
						SET tx_templavoila_flex = REPLACE(tx_templavoila_flex, ' . $escapedSource . ', ' . $escapedDest . ')
						WHERE ' . $condition . '
					');
					$message = new t3lib_FlashMessage('DONE', '', t3lib_FlashMessage::OK);
					$buffer .= $message->render();
				}
			} else {
				$message = new t3lib_FlashMessage('Nothing to do, can´t find something to replace.', '', t3lib_FlashMessage::ERROR);
				$buffer .= $message->render();
			}

			return $buffer;
			#
		}
	}

	/**
	 * @return string
	 */
	protected function showForm() {
		$message = new t3lib_FlashMessage(
			'This action can affect ' . count($this->getAllSubPages($this->pObj->id)) . ' pages, please ensure, you know what you do!, Please backup your TYPO3 Installation before running that wizard.',
			'',
			t3lib_FlashMessage::WARNING
		);
		$buffer = $message->render();
		unset($message);
		$buffer .= '<form action="' . $this->getLinkModuleRoot() . '"><div id="formFieldContainer">';
		$options = $this->getDSFieldOptionCode();
		$buffer .= $this->addFormField('sourceField', NULL, 'select_optgroup', $options);
		$buffer .= $this->addFormField('destinationField', NULL, 'select_optgroup', $options);
		$buffer .= $this->addFormField('simulateField', 1, 'checkbox');
		$buffer .= $this->addFormField('executeRename', 1, 'hidden');
		$buffer .= $this->addFormField('submit', NULL, 'submit');
		$buffer .= '</div></form>';
		$this->getKnownPageDS();

		return $buffer;
	}

	/**
	 * @param $name
	 * @param string $value
	 * @param string $type
	 * @param array $options
	 *
	 * @return string
	 */
	protected function addFormField($name, $value = '', $type = 'text', $options = array()) {
		if ($value === NULL) {
			$value = t3lib_div::_GP($name);
		}
		switch ($type) {
			case 'checkbox':
				if (t3lib_div::_GP($name) || $value) {
					$checked = 'checked';
				} else {
					$checked = '';
				}

				return '<div id="form-line-0">'
				. '<label for="' . $name . '" style="width:200px;display:block;float:left;">' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/locallang.xml:field_' . $name) . '</label>'
				. '<input type="checkbox" id="' . $name . '" name="' . $name . '" ' . $checked . ' value="1">'
				. '</div>';
				break;
			case 'submit':
				return '<div id="form-line-0">'
				. '<input type="submit" id="' . $name . '" name="' . $name . '" value="' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/locallang.xml:field_' . $name) . '">'
				. '</div>';
				break;
			case 'hidden':
				return '<input type="hidden" id="' . $name . '" name="' . $name . '" value="' . htmlspecialchars($value) . '">';
				break;
			case 'select_optgroup':
				$buffer = '';
				foreach ($options as $optgroup => $options) {
					$buffer .= '<optgroup label="' . $optgroup . '">';
					foreach ($options as $option) {
						if ($value === $option) {
							$buffer .= '<option selected>' . htmlspecialchars($option) . '</option>';
						} else {
							$buffer .= '<option>' . htmlspecialchars($option) . '</option>';
						}
					}
					$buffer .= '</optgroup>';
				}

				return '<div id="form-line-0">'
				. '<label style="width:200px;display:block;float:left;" for="' . $name . '">' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/locallang.xml:field_' . $name) . '</label>'
				. '<select id="' . $name . '" name="' . $name . '">' . $buffer . '</select>'
				. '</div>';
				break;
			case 'text':
			default:
				return '<div id="form-line-0">'
				. '<label for="' . $name . '">' . $GLOBALS['LANG']->sL('LLL:EXT:templavoila/locallang.xml:field_' . $name) . '</label>'
				. '<input type="text" id="' . $name . '" name="' . $name . '" value="' . htmlspecialchars($value) . '">'
				. '</div>';
		}
	}

	/**
	 * @return string
	 */
	protected function getLinkModuleRoot() {
		$urlParams = $this->pObj->MOD_SETTINGS;
		$urlParams['id'] = $this->pObj->id;

		return $this->pObj->doc->scriptID . '?' . t3lib_div::implodeArrayForUrl(
			'',
			$urlParams
		);
	}

	/**
	 * @return mixed
	 */
	protected function getKnownPageDS() {
		$dsRepo = t3lib_div::makeInstance('tx_templavoila_datastructureRepository');

		return $dsRepo->getDatastructuresByScope(1);
	}

	/**
	 * @return array
	 */
	protected function getDSFieldOptionCode() {
		$dsList = $this->getKnownPageDS();
		$return = array();
		foreach ($dsList as $ds) {
			$return[$ds->getLabel()] = array();
			$t = $ds->getDataprotArray();
			foreach (array_keys($t['ROOT']['el']) as $field) {
				$return[$ds->getLabel()][] = $field;
			}
		}

		return $return;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/func_wizards/class.tx_templavoila_renamefieldinpageflexwizard.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/func_wizards/class.tx_templavoila_renamefieldinpageflexwizard.php']);
}

?>
