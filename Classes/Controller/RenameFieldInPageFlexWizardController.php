<?php
namespace Extension\Templavoila\Controller;

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
 * @author Kay Strobach <typo3@kay-strobach.de>
 */
class RenameFieldInPageFlexWizardController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {

	/**
	 * @return string
	 */
	public function main() {
		if (\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->isAdmin()) {
			if (intval($this->pObj->id) > 0) {
				return $this->showForm() . $this->executeCommand();
			} else {
				// should never happen, as function module catches this already,
				// but save is save ;)
				return 'Please select a page from the tree';
			}
		} else {
			$message = new \TYPO3\CMS\Core\Messaging\FlashMessage(
				'Module only available for admins.',
				'',
				\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
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
		$completeRecords = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField('pages', 'pid', $uid);
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
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('executeRename') == 1) {
			$buffer = '';
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('sourceField') === \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('destinationField')) {
				$message = new \TYPO3\CMS\Core\Messaging\FlashMessage(
					'Renaming a field to itself is senseless, execution aborted.',
					'',
					\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
				);

				return $message->render();
			}
			$escapedSource = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->fullQuoteStr('%' . \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('sourceField') . '%', 'pages');
			$escapedDest = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->fullQuoteStr('%' . \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('destinationField') . '%', 'pages');

			$condition = 'tx_templavoila_flex LIKE ' . $escapedSource
				. ' AND NOT tx_templavoila_flex LIKE ' . $escapedDest . ' '
				. ' AND uid IN ('
				. implode(',', $this->getAllSubPages($this->pObj->id)) . ')';

			$rows = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTgetRows(
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
				$message = new \TYPO3\CMS\Core\Messaging\FlashMessage($mbuffer, '', \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
				$buffer .= $message->render();
				unset($mbuffer);
				//really do it
				if (!\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('simulateField')) {
					$escapedSource = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->fullQuoteStr(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('sourceField'), 'pages');
					$escapedDest = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->fullQuoteStr(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('destinationField'), 'pages');
					\Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->admin_query('
						UPDATE pages
						SET tx_templavoila_flex = REPLACE(tx_templavoila_flex, ' . $escapedSource . ', ' . $escapedDest . ')
						WHERE ' . $condition . '
					');
					$message = new \TYPO3\CMS\Core\Messaging\FlashMessage('DONE', '', \TYPO3\CMS\Core\Messaging\FlashMessage::OK);
					$buffer .= $message->render();
				}
			} else {
				$message = new \TYPO3\CMS\Core\Messaging\FlashMessage('Nothing to do, can´t find something to replace.', '', \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
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
		$message = new \TYPO3\CMS\Core\Messaging\FlashMessage(
			'This action can affect ' . count($this->getAllSubPages($this->pObj->id)) . ' pages, please ensure, you know what you do!, Please backup your TYPO3 Installation before running that wizard.',
			'',
			\TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
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
			$value = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP($name);
		}
		switch ($type) {
			case 'checkbox':
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP($name) || $value) {
					$checked = 'checked';
				} else {
					$checked = '';
				}

				return '<div id="form-line-0">'
				. '<label for="' . $name . '" style="width:200px;display:block;float:left;">' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL('LLL:EXT:templavoila/Resources/Private/Language/locallang.xml:field_' . $name) . '</label>'
				. '<input type="checkbox" id="' . $name . '" name="' . $name . '" ' . $checked . ' value="1">'
				. '</div>';
				break;
			case 'submit':
				return '<div id="form-line-0">'
				. '<input type="submit" id="' . $name . '" name="' . $name . '" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL('LLL:EXT:templavoila/Resources/Private/Language/locallang.xml:field_' . $name) . '">'
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
				. '<label style="width:200px;display:block;float:left;" for="' . $name . '">' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL('LLL:EXT:templavoila/Resources/Private/Language/locallang.xml:field_' . $name) . '</label>'
				. '<select id="' . $name . '" name="' . $name . '">' . $buffer . '</select>'
				. '</div>';
				break;
			case 'text':
			default:
				return '<div id="form-line-0">'
				. '<label for="' . $name . '">' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL('LLL:EXT:templavoila/Resources/Private/Language/locallang.xml:field_' . $name) . '</label>'
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

		return $this->pObj->doc->scriptID . '?' . \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl(
			'',
			$urlParams
		);
	}

	/**
	 * @return mixed
	 */
	protected function getKnownPageDS() {
		$dsRepo = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Extension\\Templavoila\\Domain\\Repository\\DataStructureRepository');

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
