<?php
namespace Extension\Templavoila\Module\Mod1;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Extension\Templavoila\Utility\TemplaVoilaUtility;

/**
 * Submodule 'Sidebar' for the templavoila page module
 *
 * Note: This class is closely bound to the page module class and uses many variables and functions directly. After major modifications of
 *       the page module all functions of this sidebar should be checked to make sure that they still work.
 *
 * @author Robert Lemke <robert@typo3.org>
 */
class Sidebar implements SingletonInterface
{
    /**
     * A pointer to the parent object, that is the templavoila page module script. Set by calling the method init() of this class.
     *
     * @var \tx_templavoila_module1
     */
    public $pObj;

    /**
     * A reference to the doc object of the parent object.
     *
     * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    public $doc;

    /**
     * Contains menuitems for the dynamic sidebar (associative array indexed by item key)
     *
     * @var array
     */
    public $sideBarItems = array();

    /**
     * Initializes the side bar object. The calling class must make sure that the right locallang files are already loaded.
     * This method is usually called by the templavoila page module.
     *
     * @param \tx_templavoila_module1 $pObj Reference to the parent object ($this)
     *
     * @return void
     */
    public function init($pObj)
    {
        // Make local reference to some important variables:
        $this->pObj = $pObj;
        $this->moduleTemplate = $this->pObj->moduleTemplate;

        $hideIfEmpty = $pObj->modTSconfig['properties']['showTabsIfEmpty'] ? false : true;

        // Register the locally available sidebar items. Additional items may be added by other extensions.
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('version') && !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')
            && TemplaVoilaUtility::getBackendUser()->check('modules', 'web_txversionM1')
        ) {
            $this->sideBarItems['versioning'] = array(
                'object' => &$this,
                'method' => 'renderItem_versioning',
                'label' => TemplaVoilaUtility::getLanguageService()->getLL('versioning'),
                'priority' => 60,
                'hideIfEmpty' => $hideIfEmpty,
            );
        }

        $this->sideBarItems['headerFields'] = array(
            'object' => &$this,
            'method' => 'renderItem_headerFields',
            'label' => TemplaVoilaUtility::getLanguageService()->getLL('pagerelatedinformation'),
            'priority' => 50,
            'hideIfEmpty' => $hideIfEmpty,
        );

        $this->sideBarItems['advancedFunctions'] = array(
            'object' => &$this,
            'method' => 'renderItem_advancedFunctions',
            'label' => TemplaVoilaUtility::getLanguageService()->getLL('advancedfunctions'),
            'priority' => 20,
            'hideIfEmpty' => $hideIfEmpty,
        );
    }

    /**
     * Adds an item to the sidebar. You are encouraged to use this function from your own extension to extend the sidebar
     * with new features. See the parameter descriptions for more details.
     *
     * @param string $itemKey A unique identifier for your sidebar item. Generally use your extension key to make sure it is unique (eg. 'tx_templavoila_sidebar_item1').
     * @param object &$object A reference to the instantiated class containing the method which renders the sidebar item (usually $this).
     * @param string $method Name of the method within your instantiated class which renders the sidebar item. Case sensitive!
     * @param string $label The label which will be shown for your item in the sidebar menu. Make sure that this label is localized!
     * @param integer $priority An integer between 0 and 100. The higher the value, the higher the item will be displayed in the sidebar. Default is 50
     * @param boolean $hideIfEmpty
     *
     * @return void
     */
    public function addItem($itemKey, $object, $method, $label, $priority = 50, $hideIfEmpty = false)
    {
        $hideIfEmpty = $pObj->modTSconfig['properties']['showTabsIfEmpty'] ? false : $hideIfEmpty;
        $this->sideBarItems[$itemKey] = array(
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
     * @param string $itemKey The key identifying the sidebar item.
     *
     * @return void
     */
    public function removeItem($itemKey)
    {
        unset ($this->sideBarItems[$itemKey]);
    }

    /**
     * Renders the sidebar and all its items.
     *
     * @return string HTML
     */
    public function render()
    {
        if (is_array($this->sideBarItems) && count($this->sideBarItems)) {
            uasort($this->sideBarItems, array($this, 'sortItemsCompare'));

            // sort and order the visible tabs
            $tablist = $this->pObj->modTSconfig['properties']['tabList'];
            if ($tablist) {
                $tabs = GeneralUtility::trimExplode(',', $tablist);
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
            $sideBar = '
                <!-- TemplaVoila Sidebar (top) begin -->

                <div id="tx_templavoila_mod1_sidebar-bar" style="width:100%;" class="bgColor-10">
                    ' . $this->moduleTemplate->getDynamicTabMenu($numSortedSideBarItems, 'TEMPLAVOILA:pagemodule:sidebar', 0, false, true, false) . '
                </div>

                <!-- TemplaVoila Sidebar end -->
            ';

            return $sideBar;
        }

        return false;
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
     * @param \tx_templavoila_module1 $pObj Reference to the parent object ($this)
     *
     * @return string HTML output
     * @access private
     */
    public function renderItem_headerFields($pObj)
    {
        global $TCA;

        $output = '';
        if ($pObj->rootElementTable != 'pages') {
            return '';
        }

        $conf = $TCA['pages']['columns']['tx_templavoila_flex']['config'];

        $dataStructureArr = \TYPO3\CMS\Backend\Utility\BackendUtility::getFlexFormDS($conf, $pObj->rootElementRecord, 'pages');

        if (is_array($dataStructureArr) && is_array($dataStructureArr['ROOT']['tx_templavoila']['pageModule'])) {
            $headerTablesAndFieldNames = GeneralUtility::trimExplode(chr(10), str_replace(chr(13), '', $dataStructureArr['ROOT']['tx_templavoila']['pageModule']['displayHeaderFields']), 1);
            if (is_array($headerTablesAndFieldNames)) {
                $fieldNames = array();
                $headerFieldRows = array();
                $headerFields = array();

                foreach ($headerTablesAndFieldNames as $tableAndFieldName) {
                    list ($table, $field) = explode('.', $tableAndFieldName);
                    $fieldNames[$table][] = $field;
                    $headerFields[] = array(
                        'table' => $table,
                        'field' => $field,
                        'label' => TemplaVoilaUtility::getLanguageService()->sL(\TYPO3\CMS\Backend\Utility\BackendUtility::getItemLabel('pages', $field)),
                        'value' => \TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValue('pages', $field, $pObj->rootElementRecord[$field], 200)
                    );
                }
                if (count($headerFields)) {
                    foreach ($headerFields as $headerFieldArr) {
                        if ($headerFieldArr['table'] == 'pages') {
                            $onClick = \TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick('&edit[pages][' . $pObj->id . ']=edit&columnsOnly=' . implode(',', $fieldNames['pages']));
                            $linkedValue = '<a style="text-decoration: none;" href="#" onclick="' . htmlspecialchars($onClick) . '">' . htmlspecialchars($headerFieldArr['value']) . '</a>';
                            $linkedLabel = '<a style="text-decoration: none;" href="#" onclick="' . htmlspecialchars($onClick) . '">' . htmlspecialchars($headerFieldArr['label']) . '</a>';
                            $headerFieldRows[] = '
                                <tr>
                                    <td class="bgColor4-20" style="width: 10%; vertical-align:top">' . $linkedLabel . '</td><td class="bgColor4" style="vertical-align:top"><em>' . $linkedValue . '</em></td>
                                </tr>
                            ';
                        }
                    }
                    $output = '
                        <table border="0" cellpadding="0" cellspacing="1" width="100%" class="lrPadding">
                            <tr>
                                <td colspan="2" class="bgColor4-20">' . TemplaVoilaUtility::getLanguageService()->getLL('pagerelatedinformation') . ':</td>
                            </tr>
                            ' . implode('', $headerFieldRows) . '
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
     * @param \tx_templavoila_module1 $pObj Reference to the page object (the templavoila page module)
     *
     * @return string HTML output
     */
    public function renderItem_versioning($pObj)
    {
        if ($pObj->id > 0) {
            $versionSelector = trim($pObj->doc->getVersionSelector($pObj->id));
            if (!$versionSelector) {
                $onClick = 'jumpToUrl(\'' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('version') . 'cm1/index.php?table=pages&uid=' . $pObj->id . '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')) . '\')';
                $versionSelector = '<input type="button" value="' . TemplaVoilaUtility::getLanguageService()->getLL('sidebar_versionSelector_createVersion', true) . '" onclick="' . htmlspecialchars($onClick) . '" />';
            }
            $tableRows = [];

            $tableRows[] = '
            <tr class="bgColor4">
                <td width="20">
                    &nbsp;
                </td>
                <td colspan="9">' . $versionSelector . '</td>
            </tr>
            ';

            return '<table border="0" cellpadding="0" cellspacing="1" class="lrPadding" width="100%">' . implode('', $tableRows) . '</table>';
        }

        return '';
    }

    /**
     * Renders the "advanced functions" sidebar item.
     *
     * @param \tx_templavoila_module1 &$pObj Reference to the page object (the templavoila page module)
     *
     * @return string HTML output
     */
    public function renderItem_advancedFunctions($pObj)
    {
        $tableRows = [];

        // Render checkbox for showing hidden elements:
        $tableRows[] = '
            <tr class="bgColor4">
                <td width="20">
                    ' . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'advancedfunctions_showhiddenelements') . '
                </td><td width="200">
                    ' . TemplaVoilaUtility::getLanguageService()->getLL('sidebar_advancedfunctions_labelshowhidden', true) . ':
                </td>
                <td>' . \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($pObj->id, 'SET[tt_content_showHidden]', $pObj->MOD_SETTINGS['tt_content_showHidden'] !== '0', 'index.php', '') . '</td>
            </tr>
        ';

        // Render checkbox for showing outline:
        if (TemplaVoilaUtility::getBackendUser()->isAdmin() || $this->pObj->modTSconfig['properties']['enableOutlineForNonAdmin']) {
            $tableRows[] = '
                <tr class="bgColor4">
                    <td width="20">
                        ' . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'advancedfunctions_showoutline') . '
                    </td><td width="200">
                        ' . TemplaVoilaUtility::getLanguageService()->getLL('sidebar_advancedfunctions_labelshowoutline', true) . ':
                    </td>
                    <td>' . \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($pObj->id, 'SET[showOutline]', $pObj->MOD_SETTINGS['showOutline'], 'index.php', '') . '</td>
                </tr>
            ';
        }

        return (count($tableRows)) ? '<table border="0" cellpadding="0" cellspacing="1" class="lrPadding" width="100%">' . implode('', $tableRows) . '</table>' : '';
    }

    /********************************************
     *
     * Helper functions
     *
     ********************************************/

    /**
     * Comparison callback function for sidebar items sorting
     *
     * @param array $a Array A
     * @param array $b Array B
     *
     * @return boolean
     * @access private
     */
    public function sortItemsCompare($a, $b)
    {
        return ($a['priority'] < $b['priority']);
    }
}
