<?php
namespace Ppi\TemplaVoilaPlus\Xclass;

/*
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

use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Script Class for the New Content element wizard
 */
class NewContentElementController extends \TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController
{
    /**
     * Creating the module output.
     *
     * @throws \UnexpectedValueException
     * @return void
     */
    public function main()
    {
        $lang = $this->getLanguageService();
        $this->content .= '<form action="" name="editForm" id="NewContentElementController"><input type="hidden" name="defValues" value="" />';
        if ($this->id && $this->access) {
            // Init position map object:
            $posMap = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\View\ContentCreationPagePositionMap::class);
            $posMap->cur_sys_language = $this->sys_language;
            // If a column is pre-set:
            if ($this->colPos !== null) {
                if ($this->uid_pid < 0) {
                    $row = [];
                    $row['uid'] = abs($this->uid_pid);
                } else {
                    $row = '';
                }
                $this->onClickEvent = $posMap->onClickInsertRecord(
                    $row,
                    $this->colPos,
                    '',
                    $this->uid_pid,
                    $this->sys_language
                );
            } else {
                $this->onClickEvent = '';
            }
            // ***************************
            // Creating content
            // ***************************
            $this->content .= '<h1>' . $lang->getLL('newContentElement') . '</h1>';
            // Wizard
            $wizardItems = $this->wizardArray();
            // Wrapper for wizards
            $this->elementWrapper['section'] = ['', ''];
            // Hook for manipulating wizardItems, wrapper, onClickEvent etc.
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'] as $classData) {
                    $hookObject = GeneralUtility::getUserObj($classData);
                    if (!$hookObject instanceof NewContentElementWizardHookInterface) {
                        throw new \UnexpectedValueException(
                            $classData . ' must implement interface ' . NewContentElementWizardHookInterface::class,
                            1227834741
                        );
                    }
                    $hookObject->manipulateWizardItems($wizardItems, $this);
                }
            }
            // Add document inline javascript
            $this->moduleTemplate->addJavaScriptCode(
                'NewContentElementWizardInlineJavascript',
                '
				function goToalt_doc() {
					' . $this->onClickEvent . '
				}'
            );

            $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);

            // Traverse items for the wizard.
            // An item is either a header or an item rendered with a radio button and title/description and icon:
            $cc = ($key = 0);
            $menuItems = [];
            foreach ($wizardItems as $k => $wInfo) {
                if ($wInfo['header']) {
                    $menuItems[] = [
                        'label' => htmlspecialchars($wInfo['header']),
                        'content' => $this->elementWrapper['section'][0]
                    ];
                    $key = count($menuItems) - 1;
                } else {
                    $content = '';

                    if (!$this->onClickEvent) {
                        // Radio button:
                        $oC = 'document.editForm.defValues.value=unescape(decodeURI(' . GeneralUtility::quoteJSvalue(rawurlencode($wInfo['params'])) . '));goToalt_doc();window.location.hash=\'#sel2\';';
                        $content .= '<div class="media-left"><input type="radio" name="tempB" value="' . htmlspecialchars($k) . '" onclick="' . htmlspecialchars($oC) . '" /></div>';
                        // Onclick action for icon/title:
                        $aOnClick = 'document.getElementsByName(\'tempB\')[' . $cc . '].checked=1;' . $oC . 'return false;';
                    } else {
                        $aOnClick = "document.editForm.defValues.value=unescape(decodeURI('" . rawurlencode($wInfo['params']) . "'));goToalt_doc();";
                    }

                    if (isset($wInfo['icon'])) {
                        GeneralUtility::deprecationLog('The PageTS-Config: mod.wizards.newContentElement.wizardItems.*.elements.*.icon'
                            . ' is deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8.'
                            . ' Register your icon in IconRegistry::registerIcon and use the new setting:'
                            . ' mod.wizards.newContentElement.wizardItems.*.elements.*.iconIdentifier');
                        $wInfo['iconIdentifier'] = 'content-' . $k;
                        $icon = $wInfo['icon'];
                        if (StringUtility::beginsWith($icon, '../typo3conf/ext/')) {
                            $icon = str_replace('../typo3conf/ext/', 'EXT:', $icon);
                        }
                        if (!StringUtility::beginsWith($icon, 'EXT:') && strpos($icon, '/') !== false) {
                            $icon = TYPO3_mainDir . GeneralUtility::resolveBackPath($wInfo['icon']);
                        }
                        $iconRegistry->registerIcon($wInfo['iconIdentifier'], BitmapIconProvider::class, [
                            'source' => $icon
                        ]);
                    }
                    $icon = $this->moduleTemplate->getIconFactory()->getIcon($wInfo['iconIdentifier'])->render();
                    $menuItems[$key]['content'] .= '
						<div class="media">
							<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">
								' . $content . '
								<div class="media-left">
									' . $icon . '
								</div>
								<div class="media-body">
									<strong>' . htmlspecialchars($wInfo['title']) . '</strong>' .
                                    '<br />' .
                                    nl2br(htmlspecialchars(trim($wInfo['description']))) .
                                '</div>
							</a>
						</div>';
                    $cc++;
                }
            }
            // Add closing section-tag
            foreach ($menuItems as $key => $val) {
                $menuItems[$key]['content'] .= $this->elementWrapper['section'][1];
            }
            // Add the wizard table to the content, wrapped in tabs
            $code = '<p>' . $lang->getLL('sel1', 1) . '</p>' . $this->moduleTemplate->getDynamicTabMenu(
                $menuItems,
                'new-content-element-wizard'
            );

            $this->content .= !$this->onClickEvent ? '<h2>' . $lang->getLL('1_selectType', true) . '</h2>' : '';
            $this->content .= '<div>' . $code . '</div>';

            // If the user must also select a column:
            if (!$this->onClickEvent) {
                // Add anchor "sel2"
                $this->content .= '<div><a name="sel2"></a></div>';
                // Select position
                $code = '<p>' . $lang->getLL('sel2', 1) . '</p>';

                // Load SHARED page-TSconfig settings and retrieve column list from there, if applicable:
                $colPosArray = GeneralUtility::callUserFunction(
                    BackendLayoutView::class . '->getColPosListItemsParsed',
                    $this->id,
                    $this
                );
                $colPosIds = array_column($colPosArray, 1);
                // Removing duplicates, if any
                $colPosList = implode(',', array_unique(array_map('intval', $colPosIds)));
                // Finally, add the content of the column selector to the content:
                $code .= $posMap->printContentElementColumns($this->id, 0, $colPosList, 1, $this->R_URI);
                $this->content .= '<h2>' . $lang->getLL('2_selectPosition', true) . '</h2><div>' . $code . '</div>';
            }
        } else {
            // In case of no access:
            $this->content = '';
            $this->content .= '<h1>' . $lang->getLL('newContentElement') . '</h1>';
        }
        $this->content .= '</form>';
        // Setting up the buttons and markers for docheader
        $this->getButtons();
    }
}
