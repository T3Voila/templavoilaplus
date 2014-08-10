<?php
/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * New content elements wizard for templavoila
 *
 * Originally based on the CE wizard / cms extension by Kasper Skaarhoj <kasper@typo3.com>
 * XHTML compatible.
 *
 * @author Robert Lemke <robert@typo3.org>
 * @coauthor    Kasper Skaarhoj <kasper@typo3.com>
 */

unset($MCONF);
require(dirname(__FILE__) . '/conf.php');
require($BACK_PATH . 'init.php');

// Unset MCONF/MLANG since all we wanted was back path etc. for this particular script.
unset($MCONF);
unset($MLANG);

// Merging locallang files/arrays:
$LANG->includeLLFile('EXT:lang/locallang_misc.xlf');
$LOCAL_LANG_orig = $LOCAL_LANG;
$LANG->includeLLFile('EXT:templavoila/mod1/locallang_db_new_content_el.xlf');
$LOCAL_LANG = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($LOCAL_LANG_orig, $LOCAL_LANG);

// Exits if 'cms' extension is not loaded:
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms', 1);

/**
 * Script Class for the New Content element wizard
 *
 * @author Robert Lemke <robert@typo3.org>
 */
class tx_templavoila_dbnewcontentel {

	/**
	 * @var string
	 */
	protected $defVals;

	/**
	 * @var array
	 */
	protected $config;

	/**
	 * @var \Extension\Templavoila\Service\ApiService
	 */
	protected $apiObj;

	/**
	 * @var array
	 */
	protected $elementWrapper;

	/**
	 * @var array
	 */
	protected $elementWrapperForTabs;

	/**
	 * @var string
	 */
	protected $onClickEvent;

	/**
	 * @var integer
	 */
	protected $perms_clause;

	/**
	 * @var array
	 */
	protected $pageinfo;

	/**
	 * @var array
	 */
	protected $MOD_MENU;

	/**
	 * @var array
	 */
	protected $MCONF;

	/**
	 * @var string
	 */
	protected $backPath;

	/**
	 * Page id
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Parameters for the new record
	 *
	 * @var string
	 */
	public $parentRecord;

	/**
	 * Array with alternative table, uid and flex-form field (see index.php in module for details, same thing there.)
	 *
	 * @var array
	 */
	public $altRoot;

	/**
	 * Internal backend template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public $doc;

	/**
	 * Templavoila extension configuration
	 *
	 * @var array
	 */
	protected $extConf;

	/**
	 * Includes a list of files to include between init() and main() - see init()
	 *
	 * @var array
	 */
	public $include_once = array();

	/**
	 * Used to accumulate the content of the module.
	 *
	 * @var string
	 */
	public $content;

	/**
	 * @var boolean
	 */
	public $access;

	/**
	 * (GPvar "returnUrl") Return URL if the script is supplied with that.
	 *
	 * @var string
	 */
	public $returnUrl = '';

	/**
	 * Initialize internal variables.
	 *
	 * @return void
	 */
	public function init() {
		global $BACK_PATH, $TBE_MODULES_EXT;

		// Setting class files to include:
		if (is_array($TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses'])) {
			$this->include_once = array_merge($this->include_once, $TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']);
		}

		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);

		// Setting internal vars:
		$this->id = (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');
		$this->parentRecord = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('parentRecord');
		$this->altRoot = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('altRoot');
		$this->defVals = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('defVals');
		$this->returnUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('returnUrl'));

		// Starting the document template object:
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('template');
		$this->doc->docType = 'xhtml_trans';
		$this->doc->backPath = $BACK_PATH;
		$this->doc->setModuleTemplate('EXT:templavoila/Resources/templates/mod1_new_content.html');
		$this->doc->bodyTagId = 'typo3-mod-php';
		$this->doc->divClass = '';
		$this->doc->JScode = '';

		$this->doc->getPageRenderer()->loadPrototype();

		$this->doc->loadJavascriptLib('sysext/backend/Resources/Public/JavaScript/tabmenu.js');

		$this->doc->form = '<form action="" name="editForm">';

		$tsconfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig($this->id, 'templavoila.wizards.newContentElement');
		$this->config = $tsconfig['properties'];

		// Getting the current page and receiving access information (used in main())
		$perms_clause = \Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->getPagePermsClause(1);
		$pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->id, $perms_clause);
		$this->access = is_array($pageinfo) ? TRUE : FALSE;

		$this->apiObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Extension\\Templavoila\\Service\\ApiService');

		// If no parent record was specified, find one:
		if (!$this->parentRecord) {
			$mainContentAreaFieldName = $this->apiObj->ds_getFieldNameByColumnPosition($this->id, 0);
			if ($mainContentAreaFieldName != FALSE) {
				$this->parentRecord = 'pages:' . $this->id . ':sDEF:lDEF:' . $mainContentAreaFieldName . ':vDEF:0';
			}
		}
	}

	/**
	 * Creating the module output.
	 *
	 * @throws \UnexpectedValueException
	 *
	 * @return void
	 * @todo provide position mapping if no position is given already. Like the columns selector but for our cascading element style ...
	 */
	public function main() {
		if ($this->id && $this->access) {

			// Creating content
			$this->content = $this->doc->header(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newContentElement'));
			$this->content .= $this->doc->spacer(5);

			$elRow = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('pages', $this->id);
			$header = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', $elRow);
			$header .= \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle('pages', $elRow, 1);
			$this->content .= $this->doc->section('', $header, 0, 1);
			$this->content .= $this->doc->spacer(10);

			// Wizard
			$wizardItems = $this->getWizardItems();

			// Wrapper for wizards
			$this->elementWrapper['sectionHeader'] = array('<h3 class="bgColor5">', '</h3>');
			$this->elementWrapper['section'] = array('<table border="0" cellpadding="1" cellspacing="2">', '</table>');
			$this->elementWrapper['wizard'] = array('<tr>', '</tr>');
			$this->elementWrapper['wizardPart'] = array('<td>', '</td>');
			// copy wrapper for tabs
			$this->elementWrapperForTabs = $this->elementWrapper;

			// Hook for manipulating wizardItems, wrapper, onClickEvent etc.
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['templavoila']['db_new_content_el']['wizardItemsHook'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['templavoila']['db_new_content_el']['wizardItemsHook'] as $classData) {
					$hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classData);

					if (!($hookObject instanceof \TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface)) {
						throw new UnexpectedValueException('$hookObject must implement interface cms_newContentElementWizardItemsHook', 1227834741);
					}

					$hookObject->manipulateWizardItems($wizardItems, $this);
				}
			}

			if ($this->config['renderMode'] == 'tabs' && $this->elementWrapperForTabs != $this->elementWrapper) {
				// restore wrapper for tabs if they are overwritten in hook
				$this->elementWrapper = $this->elementWrapperForTabs;
			}

			// add document inline javascript
			$this->doc->JScode = $this->doc->wrapScriptTags('
				function goToalt_doc()	{	//
					' . $this->onClickEvent . '
				}

				Event.observe(window, \'load\', function() {
					if(top.refreshMenu) {
						top.refreshMenu();
					} else {
						top.TYPO3ModuleMenu.refreshMenu();
					}

					if(top.shortcutFrame) {
						top.shortcutFrame.refreshShortcuts();
					}
				});
			');

			// Traverse items for the wizard.
			// An item is either a header or an item rendered with a title/description and icon:
			$menuItems = array();
			foreach ($wizardItems as $k => $wInfo) {
				/**
				 * @todo: Find out what exactly happens here. The whole loop feels strange
				 */
				if ($wInfo['header']) {
					$menuItems[] = array('label' => htmlspecialchars($wInfo['header']), 'content' => $this->elementWrapper['section'][0]);
					$key = count($menuItems) - 1;
				} else {
					$content = '';
					// href URI for icon/title:
					$newRecordLink = 'index.php?' . $this->linkParams() . '&createNewRecord=' . rawurlencode($this->parentRecord) . $wInfo['params'];

					$content .= $this->elementWrapper['wizardPart'][0] . '<a href="' . htmlspecialchars($newRecordLink) . '">
						<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->doc->backPath, $wInfo['icon'], '') . ' alt="" /></a>' . $this->elementWrapper['wizardPart'][1];

					// Title + description:
					$content .= $this->elementWrapper['wizardPart'][0] . '<a href="' . htmlspecialchars($newRecordLink) . '"><strong>' . htmlspecialchars($wInfo['title']) . '</strong><br />' . nl2br(htmlspecialchars(trim($wInfo['description']))) . '</a>' . $this->elementWrapper['wizardPart'][1];

					// Finally, put it together in a container:
					$menuItems[$key]['content'] .= $this->elementWrapper['wizard'][0] . $content . $this->elementWrapper['wizard'][1];
				}
			}
			// add closing section-tag
			foreach ($menuItems as $key => $val) {
				$menuItems[$key]['content'] .= $this->elementWrapper['section'][1];
			}

			// Add the wizard table to the content, wrapped in tabs:
			if ($this->config['renderMode'] == 'tabs') {
				$this->doc->inDocStylesArray[] = '
					.typo3-dyntabmenu-divs { background-color: #fafafa; border: 1px solid #000; width: 680px; }
					.typo3-dyntabmenu-divs table { margin: 15px; }
					.typo3-dyntabmenu-divs table td { padding: 3px; }
				';
				$code = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('sel1', 1) . '<br /><br />' . $this->doc->getDynTabMenu($menuItems, 'new-content-element-wizard', FALSE, FALSE, 100);
			} else {
				$code = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('sel1', 1) . '<br /><br />';
				foreach ($menuItems as $section) {
					$code .= $this->elementWrapper['sectionHeader'][0] . $section['label'] . $this->elementWrapper['sectionHeader'][1] . $section['content'];
				}
			}

			$this->content .= $this->doc->section(!$this->onClickEvent ? \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('1_selectType') : '', $code, 0, 1);
		} else { // In case of no access:
			$this->content = $this->doc->header(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newContentElement'));
		}

		$this->pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->id, $this->perms_clause);
		$docHeaderButtons = $this->getDocHeaderButtons();
		$docContent = array(
			'CSH' => $docHeaderButtons['csh'],
			'CONTENT' => $this->content
		);

		$content = $this->doc->startPage(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newContentElement'));
		$content .= $this->doc->moduleBody(
			$this->pageinfo,
			$docHeaderButtons,
			$docContent
		);
		$content .= $this->doc->endPage();

		// Replace content with templated content
		$this->content = $content;
	}

	/**
	 * Gets the buttons that shall be rendered in the docHeader.
	 *
	 * @return array Available buttons for the docHeader
	 */
	protected function getDocHeaderButtons() {
		$buttons = array(
			'csh' => \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_web_txtemplavoilaCM1', '', $this->backPath),
			'back' => '',
			'shortcut' => $this->getShortcutButton(),
		);

		// Back
		if ($this->returnUrl) {
			$backIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-view-go-back');
			$buttons['back'] = '<a href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisUrl($this->returnUrl)) . '" class="typo3-goBack" title="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.goBack', TRUE) . '">' .
				$backIcon .
				'</a>';
		}

		return $buttons;
	}

	/**
	 * Gets the button to set a new shortcut in the backend (if current user is allowed to).
	 *
	 * @return string HTML representiation of the shortcut button
	 */
	protected function getShortcutButton() {
		$result = '';
		$menu = is_array($this->MOD_MENU) ? $this->MOD_MENU : array();
		if (\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->mayMakeShortcut()) {
			$result = $this->doc->makeShortcutIcon('id', implode(',', array_keys($menu)), $this->MCONF['name']);
		}

		return $result;
	}

	/**
	 * Print out the accumulated content:
	 *
	 * @return void
	 */
	public function printContent() {
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}

	/**
	 * @return string
	 */
	public function linkParams() {
		$output = 'id=' . $this->id . (is_array($this->altRoot) ? \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('altRoot', $this->altRoot) : '');

		return $output;
	}

	/***************************
	 *
	 * OTHER FUNCTIONS:
	 *
	 ***************************/

	/**
	 * Returns the content of wizardArray() function...
	 *
	 * @return array Returns the content of wizardArray() function...
	 */
	public function getWizardItems() {
		return $this->wizardArray();
	}

	/**
	 * Returns the array of elements in the wizard display.
	 * For the plugin section there is support for adding elements there from a global variable.
	 *
	 * @return array
	 */
	public function wizardArray() {

		$wizards = array();
		if (is_array($this->config)) {
			$wizards = $this->config['wizardItems.'];
		}
		$pluginWizards = $this->wizard_appendWizards($wizards['elements.']);
		$fceWizards = $this->wizard_renderFCEs($wizards['elements.']);
		$appendWizards = array_merge((array) $fceWizards, (array) $pluginWizards);

		$wizardItems = array();

		if (is_array($wizards)) {
			foreach ($wizards as $groupKey => $wizardGroup) {
				$groupKey = preg_replace('/\.$/', '', $groupKey);
				$showItems = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $wizardGroup['show'], TRUE);
				$showAll = (strcmp($wizardGroup['show'], '*') ? FALSE : TRUE);
				$groupItems = array();

				if (is_array($appendWizards[$groupKey . '.']['elements.'])) {
					$wizardElements = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule((array) $wizardGroup['elements.'], $appendWizards[$groupKey . '.']['elements.']);
				} else {
					$wizardElements = $wizardGroup['elements.'];
				}

				if (is_array($wizardElements)) {
					foreach ($wizardElements as $itemKey => $itemConf) {
						$itemKey = preg_replace('/\.$/', '', $itemKey);
						if ($showAll || in_array($itemKey, $showItems)) {
							$tmpItem = $this->wizard_getItem($groupKey, $itemKey, $itemConf);
							if ($tmpItem) {
								$groupItems[$groupKey . '_' . $itemKey] = $tmpItem;
							}
						}
					}
				}
				if (count($groupItems)) {
					$wizardItems[$groupKey] = $this->wizard_getGroupHeader($groupKey, $wizardGroup);
					$wizardItems = array_merge($wizardItems, $groupItems);
				}
			}
		}

		// Remove elements where preset values are not allowed:
		$this->removeInvalidElements($wizardItems);

		return $wizardItems;
	}

	/**
	 * Get wizard array for plugins
	 *
	 * @param array $wizardElements
	 *
	 * @return array $returnElements
	 */
	public function wizard_appendWizards($wizardElements) {
		if (!is_array($wizardElements)) {
			$wizardElements = array();
		}
		// plugins
		if (is_array($GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses'])) {
			foreach ($GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses'] as $class => $path) {
				$modObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($class);
				$wizardElements = $modObj->proc($wizardElements);
			}
		}
		$returnElements = array();
		foreach ($wizardElements as $key => $wizardItem) {
			preg_match('/^[a-zA-Z0-9]+_/', $key, $group);
			$wizardGroup = $group[0] ? substr($group[0], 0, -1) . '.' : $key;
			$returnElements[$wizardGroup]['elements.'][substr($key, strlen($wizardGroup)) . '.'] = $wizardItem;
		}

		return $returnElements;
	}

	/**
	 * Get wizard array for FCEs
	 *
	 * @param array $wizardElements
	 *
	 * @return array $returnElements
	 */
	public function wizard_renderFCEs($wizardElements = array()) {
		$returnElements = array();

		// Flexible content elements:
		$positionPid = $this->id;
		$storageFolderPID = $this->apiObj->getStorageFolderPid($positionPid);

		$toRepo = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Extension\\Templavoila\\Domain\\Repository\\TemplateRepository');
		$toList = $toRepo->getTemplatesByStoragePidAndScope($storageFolderPID, \Extension\Templavoila\Domain\Model\AbstractDataStructure::SCOPE_FCE);
		foreach ($toList as $toObj) {
			/** @var \Extension\Templavoila\Domain\Model\Template $toObj */
			if ($toObj->isPermittedForUser()) {
				$tmpFilename = $toObj->getIcon();
				$returnElements['fce.']['elements.']['fce_' . $toObj->getKey() . '.'] = array(
					'icon' => (@is_file(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(substr($tmpFilename, 3)))) ? $tmpFilename : ('../' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('templavoila') . 'Resources/Public/Image/default_previewicon.gif'),
					'description' => $toObj->getDescription() ? htmlspecialchars($toObj->getDescription()) : \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('template_nodescriptionavailable'),
					'title' => $toObj->getLabel(),
					'params' => $this->getDsDefaultValues($toObj)
				);
			}
		}

		return $returnElements;
	}

	/**
	 * @param string $groupKey
	 * @param string $itemKey
	 * @param array $itemConf
	 *
	 * @return mixed
	 */
	public function wizard_getItem($groupKey, $itemKey, $itemConf) {
		$itemConf['title'] = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL($itemConf['title']);
		$itemConf['description'] = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL($itemConf['description']);
		$itemConf['tt_content_defValues'] = $itemConf['tt_content_defValues.'];
		unset($itemConf['tt_content_defValues.']);

		return $itemConf;
	}

	/**
	 * @param string $groupKey
	 * @param array $wizardGroup
	 *
	 * @return array
	 */
	public function wizard_getGroupHeader($groupKey, $wizardGroup) {
		return array('header' => \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->sL($wizardGroup['header']));
	}

	/**
	 * Checks the array for elements which might contain unallowed default values and will unset them!
	 * Looks for the "tt_content_defValues" key in each element and if found it will traverse that array as fieldname / value pairs and check. The values will be added to the "params" key of the array (which should probably be unset or empty by default).
	 *
	 * @param array &$wizardItems Wizard items, passed by reference
	 *
	 * @return void
	 */
	public function removeInvalidElements(&$wizardItems) {
		global $TCA;

		// Get TCEFORM from TSconfig of current page
		$TCEFORM_TSconfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getTCEFORM_TSconfig('tt_content', array('pid' => $this->id));
		$removeItems = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $TCEFORM_TSconfig['CType']['removeItems'], 1);

		$headersUsed = Array();
		// Traverse wizard items:
		foreach ($wizardItems as $key => $cfg) {

			// Exploding parameter string, if any (old style)
			if ($wizardItems[$key]['params']) {
				// Explode GET vars recursively
				$tempGetVars = \TYPO3\CMS\Core\Utility\GeneralUtility::explodeUrl2Array($wizardItems[$key]['params'], TRUE);
				// If tt_content values are set, merge them into the tt_content_defValues array, unset them from $tempGetVars and re-implode $tempGetVars into the param string (in case remaining parameters are around).
				if (is_array($tempGetVars['defVals']['tt_content'])) {
					$wizardItems[$key]['tt_content_defValues'] = array_merge(is_array($wizardItems[$key]['tt_content_defValues']) ? $wizardItems[$key]['tt_content_defValues'] : array(), $tempGetVars['defVals']['tt_content']);
					unset($tempGetVars['defVals']['tt_content']);
					$wizardItems[$key]['params'] = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $tempGetVars);
				}
			}

			// If tt_content_defValues are defined...:
			if (is_array($wizardItems[$key]['tt_content_defValues'])) {

				// Traverse field values:
				foreach ($wizardItems[$key]['tt_content_defValues'] as $fN => $fV) {
					if (is_array($TCA['tt_content']['columns'][$fN])) {
						// Get information about if the field value is OK:
						$config = & $TCA['tt_content']['columns'][$fN]['config'];
						$authModeDeny = $config['type'] == 'select' && $config['authMode'] && !\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->checkAuthMode('tt_content', $fN, $fV, $config['authMode']);

						if ($authModeDeny || in_array($fV, $removeItems)) {
							// Remove element all together:
							unset($wizardItems[$key]);
							break;
						} else {
							// Add the parameter:
							$wizardItems[$key]['params'] .= '&defVals[tt_content][' . $fN . ']=' . rawurlencode($fV);
							$tmp = explode('_', $key);
							$headersUsed[$tmp[0]] = $tmp[0];
						}
					}
				}
			}
		}

		// Remove headers without elements
		foreach ($wizardItems as $key => $cfg) {
			list ($itemCategory, $dummy) = explode('_', $key);
			if (!isset($headersUsed[$itemCategory])) {
				unset($wizardItems[$key]);
			}
		}
	}

	/**
	 * Create sql condition for given table to limit records according to user access.
	 *
	 * @param string $table Table nme to fetch records from
	 *
	 * @return string Condition or empty string
	 */
	public function buildRecordWhere($table) {
		$result = array();
		if (!\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->isAdmin()) {
			$prefLen = strlen($table) + 1;
			foreach (\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->userGroups as $group) {
				$items = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $group['tx_templavoila_access'], 1);
				foreach ($items as $ref) {
					if (strstr($ref, $table)) {
						$result[] = (int)substr($ref, $prefLen);
					}
				}
			}
		}

		return (count($result) > 0 ? ' AND uid NOT IN (' . implode(',', $result) . ') ' : '');
	}

	/**
	 * Process the default-value settings
	 *
	 * @param \Extension\Templavoila\Domain\Model\Template $toObj LocalProcessing as array
	 *
	 * @return string additional URL arguments with configured default values
	 */
	public function getDsDefaultValues(\Extension\Templavoila\Domain\Model\Template $toObj) {

		$dsStructure = $toObj->getLocalDataprotArray();

		$dsValues = '&defVals[tt_content][CType]=templavoila_pi1'
			. '&defVals[tt_content][tx_templavoila_ds]=' . $toObj->getDatastructure()->getKey()
			. '&defVals[tt_content][tx_templavoila_to]=' . $toObj->getKey();

		if (is_array($dsStructure) && is_array($dsStructure['meta']['default']['TCEForms'])) {
			foreach ($dsStructure['meta']['default']['TCEForms'] as $field => $value) {
				$dsValues .= '&defVals[tt_content][' . $field . ']=' . $value;
			}
		}

		return $dsValues;
	}
}

// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_templavoila_dbnewcontentel');
$SOBE->init();

// Include files?
foreach ($SOBE->include_once as $INC_FILE) {
	include_once($INC_FILE);
}

$SOBE->main();
$SOBE->printContent();
