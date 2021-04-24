<?php
namespace Ppi\TemplaVoilaPlus\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use Ppi\TemplaVoilaPlus\Utility\DataStructureUtility;
use Ppi\TemplaVoilaPlus\Utility\FileUtility;

$GLOBALS['LANG']->includeLLFile(
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('templavoilaplus') . 'Resources/Private/Language/BackendTemplateMapping.xlf'
);

/**
 * Class for controlling the TemplaVoila module.
 *
 * @author Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @co-author Robert Lemke <robert@typo3.org>
 */
class BackendTemplateMappingController extends \TYPO3\CMS\Backend\Module\BaseScriptClass
{
    /**
     * @var string
     */
    protected $DS_element_DELETE;

    /**
     * @var string
     */
    protected $sessionKey;

    /**
     * @var array
     */
    public $head_markUpTags = [
        // Block elements:
        'title' => [],
        'script' => [],
        'style' => [],
        // Single elements:
        'link' => ['single' => 1],
        'meta' => ['single' => 1],
    ];

    /**
     * Extension key of this module
     *
     * @var string
     */
    public $extKey = 'templavoilaplus';

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'templavoilaplus_mapping';

    /**
     * @var array
     */
    public $dsTypes;

    /**
     * Used to store the name of the file to mark up with a given path.
     *
     * @var string
     */
    public $markupFile = '';

    /**
     * @var \Ppi\TemplaVoilaPlus\Domain\Model\HtmlMarkup
     */
    public $markupObj;

    /**
     * @var array
     */
    public $elNames = [];

    /**
     * Setting whether we are editing a data structure or not.
     *
     * @var integer
     */
    public $editDataStruct = 0;

    /**
     * Storage folders as key(uid) / value (title) pairs.
     *
     * @var array
     */
    public $storageFolders = [];

    /**
     * The storageFolders pids imploded to a comma list including "0"
     *
     * @var integer
     */
    public $storageFolders_pidList = 0;

    /**
     * Looking for "&mode", which defines if we draw a frameset (default), the module (mod) or display (display)
     *
     * @var string
     */
    public $mode;

    /**
     * (GPvar "file", shared with DISPLAY mode!) The file to display, if file is referenced directly from filelist module. Takes precedence over displayTable/displayUid
     *
     * @var string
     */
    public $displayFile = '';

    /**
     * (GPvar "table") The table from which to display element (Data Structure object [tx_templavoilaplus_datastructure], template object [tx_templavoilaplus_tmplobj])
     *
     * @var string
     */
    public $displayTable = '';

    /**
     * (GPvar "uid") The UID to display (from ->displayTable)
     *
     * @var string
     */
    public $displayUid = '';

    /**
     * (GPvar "htmlPath") The "HTML-path" to display from the current file
     *
     * @var string
     */
    public $displayPath = '';

    /**
     * (GPvar "returnUrl") Return URL if the script is supplied with that.
     *
     * @var string
     */
    public $returnUrl = ''; //

    /**
     * @var boolean
     */
    public $_preview;

    /**
     * @var string
     */
    public $mapElPath;

    /**
     * @var boolean
     */
    public $doMappingOfPath;

    /**
     * @var boolean
     */
    public $showPathOnly;

    /**
     * @var string
     */
    public $mappingToTags;

    /**
     * @var string
     */
    public $DS_element;

    /**
     * @var string
     */
    public $DS_cmd;

    /**
     * @var string
     */
    public $fieldName;

    /**
     * @var boolean
     */
    public $_load_ds_xml_content;

    /**
     * @var boolean
     */
    public $_load_ds_xml_to;

    /**
     * @var integer
     */
    public $_saveDSandTO_TOuid;

    /**
     * @var string
     */
    public $_saveDSandTO_title;

    /**
     * @var string
     */
    public $_saveDSandTO_type;

    /**
     * @var integer
     */
    public $_saveDSandTO_pid;

    /**
     * instance of class Ppi\TemplaVoilaPlus\Module\Cm1\DsEdit
     *
     * @var \Ppi\TemplaVoilaPlus\Module\Cm1\DsEdit
     */
    public $dsEdit;

    /**
     * instance of class Ppi\TemplaVoilaPlus\Module\Cm1\ETypes
     *
     * @var \Ppi\TemplaVoilaPlus\Module\Cm1\ETypes
     */
    public $eTypes;

    /**
     * holds the extconf configuration
     *
     * @var array
     */
    public $extConf;

    /**
     * Boolean; if true DS records are file based
     *
     * @var boolean
     */
    public $staticDS = false;

    /**
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->moduleTemplate = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\ModuleTemplate::class);
        $this->iconFactory = $this->moduleTemplate->getIconFactory();
        $this->buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoilaplus']);
    }

    /**
     * Preparing menu content
     *
     * @return void
     */
    public function menuConfig()
    {
        $this->MOD_MENU = [
            'displayMode' => [
                'explode' => 'Mode: Exploded Visual',
                'source' => 'Mode: HTML Source ',
            ],
            'showDSxml' => ''
        ];

        // page/be_user TSconfig settings and blinding of menu-items
        $this->modTSconfig = BackendUtility::getModTSconfig($this->id, 'mod.' . $this->moduleName);

        // CLEANSE SETTINGS
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), $this->moduleName);
    }


    /**
     * Returns an abbrevation and a description for a given element-type.
     * Silently convert array to section or container for mapping handling
     * Will be converted back to array in convertDsTypeBack()
     * so saved FlexForm.xml is correct.
     *
     * @param array $conf DS config of the field.
     *
     * @return array
     */
    public function dsTypeInfo($conf)
    {
        if ($conf['type'] !== null && isset($this->dsTypes[$conf['type']])) {
            if ($conf['type'] == 'array') {
                if (!$conf['section']) {
                    return $this->dsTypes['container'];
                }
            }
            return $this->dsTypes[$conf['type']];
        }

        return $this->dsTypes['element'];
    }

    public function convertDsTypeBack(&$elArray)
    {
        // Traverse array
        foreach ($elArray as $key => $value) {
            // this MUST not ever enter the XMLs (it will break TV)
            if ($elArray[$key]['type'] == 'section' || $elArray[$key]['section']) {
                $elArray[$key]['type'] = 'array';
                $elArray[$key]['section'] = '1';
            } elseif ($elArray[$key]['type'] == 'container') {
                $elArray[$key]['type'] = 'array';
                $elArray[$key]['section'] = '0';
            } else {
                $elArray[$key]['section'] = '0';
            }
            // Run this function recursively if needed:
            if (is_array($elArray[$key]['el'])) {
                $this->convertDsTypeBack($elArray[$key]['el']);
            }
        }
    }

    /*******************************************
     *
     * Main functions
     *
     *******************************************/

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->init();
        $this->main();
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Main function, distributes the load between the module and display modes.
     * "Display" mode is when the exploded template file is shown in an IFRAME
     *
     * @return void
     */
    public function main()
    {
        // Initialize ds_edit
        $this->dsEdit = GeneralUtility::getUserObj(\Ppi\TemplaVoilaPlus\Module\Cm1\DsEdit::class, '');
        $this->dsEdit->init($this);

        // Initialize eTypes
        $this->eTypes = GeneralUtility::getUserObj(\Ppi\TemplaVoilaPlus\Module\Cm1\ETypes::class, '');
        $this->eTypes->init($this);

        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoilaplus']);
        $this->staticDS = ($this->extConf['staticDS.']['enable']);

        // Setting GPvars:
        // It can be, that we get a storeg:file link from clickmenu
        $this->displayFile = null;
        if (!empty(GeneralUtility::_GP('file')) && FileUtility::haveTemplateAccess(GeneralUtility::_GP('file'))) {
            $this->displayFile = \Ppi\TemplaVoilaPlus\Domain\Model\File::filename(GeneralUtility::_GP('file'));
        }

        $this->displayTable = GeneralUtility::_GP('table');
        $this->displayUid = GeneralUtility::_GP('uid');

        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));

        // Access check!
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $pageInfoArr = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $access = is_array($pageInfoArr);

        if ($access) {
            // Add custom styles
            if (version_compare(TYPO3_version, '8.3.0', '>=')) {
                // Since TYPO3 8.3.0 EXT:extname/... is supported.
                $this->getPageRenderer()->addCssFile(
                    'EXT:' . $this->extKey . '/Resources/Public/StyleSheet/cm1_default.css'
                );
                $this->getPageRenderer()->addCssFile(
                    'EXT:' . $this->extKey . '/Resources/Public/StyleSheet/HtmlMarkup.css'
                );
            } else {
                $this->getPageRenderer()->addCssFile(
                    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($this->extKey) . 'Resources/Public/StyleSheet/cm1_default.css'
                );
                $this->getPageRenderer()->addCssFile(
                    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($this->extKey) . 'Resources/Public/StyleSheet/HtmlMarkup.css'
                );
            }

            // Adding classic jumpToUrl function, needed for the function menu.
            // And some more functions
            $this->moduleTemplate->addJavaScriptCode('templavoilaplus_function', '
                script_ended = 0;
                function jumpToUrl(URL)    {    //
                    document.location = URL;
                }
                function updPath(inPath)    {    //
                    document.location = "' . GeneralUtility::linkThisScript(array('htmlPath' => '', 'doMappingOfPath' => 1)) . '&htmlPath="+top.rawurlencode(inPath);
                }

                function openValidator(key) {
                    new TYPO3.jQuery.ajax({
                        url: TYPO3.settings.ajaxUrls[\'templavoilaplus_displayFileContent\'],
                        type: \'get\',
                        cache: false,
                        data: {
                            \'key\': key,
                        },
                        success: function(result) {
                            var valform = new Element(\'form\',{method: \'post\', target:\'_blank\', action: \'http://validator.w3.org/check#validate_by_input\'});
                            valform.insert(new Element(\'input\',{name: \'fragment\', value:response.responseText, type: \'hidden\'}));$(document.body).insert(valform);
                            valform.submit();
                        }
                    });
                }
            ');

            $this->main_mode();
        } else {
            $this->moduleTemplate->addFlashMessage(
                TemplaVoilaUtility::getLanguageService()->getLL('noaccess'),
                TemplaVoilaUtility::getLanguageService()->getLL('title'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::INFO
            );
        }

        $title = TemplaVoilaUtility::getLanguageService()->getLL('mappingTitle');
        $header = $this->moduleTemplate->header($title);
        $this->moduleTemplate->setTitle($title);

        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageInfoArr);
        $this->setDocHeaderButtons(!isset($pageInfoArr['uid']));

        $this->moduleTemplate->setForm('<form action="' . $this->linkThisScript([]) . '" method="post" name="pageform">');

        if ($this->content) {
            $this->moduleTemplate->setContent($header . $this->content);
        }
    }

    /**
     * Gets the buttons that shall be rendered in the docHeader.
     *
     * @return array Available buttons for the docHeader
     */
    protected function setDocHeaderButtons()
    {
        $this->addCshButton('');
        $this->addShortcutButton();
        $this->addBackButton();
    }

    /**
     * Adds csh icon to the right document header button bar
     */
    public function addCshButton($fieldName)
    {
        $contextSensitiveHelpButton = $this->buttonBar->makeHelpButton()
            ->setModuleName('_MOD_' . $this->moduleName)
            ->setFieldName($fieldName);
        $this->buttonBar->addButton($contextSensitiveHelpButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * Adds shortcut icon to the right document header button bar
     */
    public function addShortcutButton()
    {
        $shortcutButton = $this->buttonBar->makeShortcutButton()
            ->setModuleName($this->moduleName)
            ->setGetVariables(
                [
                    'id',
                    'uid',
                ]
            )
            ->setSetVariables(array_keys($this->MOD_MENU));
        $this->buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    public function addBackButton()
    {
        if ($this->returnUrl) {
            $url = $this->returnUrl;
        } else {
            // @TODO Go back to ControlCenter if we are on "start page"
            $url = BackendUtility::getModuleUrl(
                'templavoilaplus_mapping',
                [
                    'id' => $this->id,
                    'file' => $this->displayFile,
                    'table' => $this->displayTable,
                    'uid' => $this->displayUid,
                ]
            );
        }
        $backButton = $this->buttonBar->makeLinkButton()
            ->setHref($url)
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:' . TemplaVoilaUtility::getCoreLangPath() . 'locallang_core.xlf:rm.closeDoc'))
            ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
        $this->buttonBar->addButton($backButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
    }

    /**
     * Makes a context-free xml-string from an array.
     *
     * @param array $array
     * @param string $pfx
     *
     * @return string
     */
    public function flattenarray($array, $pfx = '')
    {
        if (!is_array($array)) {
            if (is_string($array)) {
                return $array;
            } else {
                return '';
            }
        }

        return str_replace("<>\n", '', str_replace("</>", '', GeneralUtility::array2xml($array, '', -1, '', 0, array('useCDATA' => 1))));
    }

    /**
     * Makes an array from a context-free xml-string.
     *
     * @param string $string
     *
     * @return array
     */
    public function unflattenarray($string)
    {
        if (!is_string($string) || !trim($string)) {
            if (is_array($string)) {
                return $string;
            } else {
                return [];
            }
        }

        return GeneralUtility::xml2array('<grouped>' . $string . '</grouped>');
    }

    /**
     * Merges two arrays recursively and "binary safe" (integer keys are overridden as well), overruling similar values in the first array ($arr0) with the values of the second array ($arr1)
     * In case of identical keys, ie. keeping the values of the second.
     * Usage: 0
     *
     * @param array $arr0 First array
     * @param array $arr1 Second array, overruling the first array
     * @param integer $notAddKeys If set, keys that are NOT found in $arr0 (first array) will not be set. Thus only existing value can/will be overruled from second array.
     * @param boolean $includeEmtpyValues If set, values from $arr1 will overrule if they are empty or zero. Default: true
     * @param boolean $kill If set, anything will override arrays in $arr0
     *
     * @return array Resulting array where $arr1 values has overruled $arr0 values
     */
    public function array_merge_recursive_overrule($arr0, $arr1, $notAddKeys = 0, $includeEmtpyValues = true, $kill = true)
    {
        foreach ($arr1 as $key => $val) {
            if (is_array($arr0[$key])) {
                if (is_array($arr1[$key])) {
                    $arr0[$key] = $this->array_merge_recursive_overrule($arr0[$key], $arr1[$key], $notAddKeys, $includeEmtpyValues, $kill);
                } else {
                    if ($kill) {
                        if ($includeEmtpyValues || $val) {
                            $arr0[$key] = $val;
                        }
                    }
                }
            } else {
                if ($notAddKeys) {
                    if (isset($arr0[$key])) {
                        if ($includeEmtpyValues || $val) {
                            $arr0[$key] = $val;
                        }
                    }
                } else {
                    if ($includeEmtpyValues || $val) {
                        $arr0[$key] = $val;
                    }
                }
            }
        }
        reset($arr0);

        return $arr0;
    }

    /*****************************************
     *
     * MODULE mode
     *
     *****************************************/

    /**
     * Main function of the MODULE. Write the content to $this->content
     * There are three main modes:
     * - Based on a file reference, creating/modifying a DS/TO
     * - Based on a Template Object uid, remapping
     * - Based on a Data Structure uid, selecting a Template Object to map.
     *
     * @return void
     */
    public function main_mode()
    {
        // General GPvars for module mode:
        $this->displayPath = GeneralUtility::_GP('htmlPath');

        // GPvars specific to the DS listing/table and mapping features:
        $this->_preview = GeneralUtility::_GP('_preview');
        $this->mapElPath = GeneralUtility::_GP('mapElPath');
        $this->doMappingOfPath = GeneralUtility::_GP('doMappingOfPath');
        $this->showPathOnly = GeneralUtility::_GP('showPathOnly');
        $this->mappingToTags = GeneralUtility::_GP('mappingToTags');
        $this->DS_element = GeneralUtility::_GP('DS_element');
        $this->DS_cmd = GeneralUtility::_GP('DS_cmd');
        $this->fieldName = GeneralUtility::_GP('fieldName');

        // GPvars specific for DS creation from a file.
        $this->_load_ds_xml_content = GeneralUtility::_GP('_load_ds_xml_content');
        $this->_load_ds_xml_to = GeneralUtility::_GP('_load_ds_xml_to');
        $this->_saveDSandTO_TOuid = GeneralUtility::_GP('_saveDSandTO_TOuid');
        $this->_saveDSandTO_title = GeneralUtility::_GP('_saveDSandTO_title');
        $this->_saveDSandTO_type = GeneralUtility::_GP('_saveDSandTO_type');
        $this->_saveDSandTO_pid = GeneralUtility::_GP('_saveDSandTO_pid');
        $this->DS_element_DELETE = GeneralUtility::_GP('DS_element_DELETE');

        // Finding Storage folder:
        $this->findingStorageFolderIds();

        // dsType configuration
        $this->dsTypes = [
            'section' => [
                'id' => 'sc',
                'name' => 'section',
                'title' => TemplaVoilaUtility::getLanguageService()->getLL('dsTypes_section'),
                'mapping' => TemplaVoilaUtility::getLanguageService()->getLL('mapSection'),
            ],
            'array' => [
                'id' => 'sc',
                'name' => 'array',
                'title' => TemplaVoilaUtility::getLanguageService()->getLL('dsTypes_section'),
                'mapping' => '',
            ],
            'container' => [
                'id' => 'co',
                'name' => 'container',
                'title' => TemplaVoilaUtility::getLanguageService()->getLL('dsTypes_container'),
                'mapping' => TemplaVoilaUtility::getLanguageService()->getLL('mapContainer'),
            ],
            'attr' => [
                'id' => 'at',
                'name' => 'attr',
                'title' => TemplaVoilaUtility::getLanguageService()->getLL('dsTypes_attribute'),
                'mapping' => TemplaVoilaUtility::getLanguageService()->getLL('mapAttribute'),
            ],
            'element' => [
                'id' => 'el',
                'name' => 'element',
                'title' => TemplaVoilaUtility::getLanguageService()->getLL('dsTypes_element'),
                'mapping' => TemplaVoilaUtility::getLanguageService()->getLL('mapElement'),
            ],
            'no_map' => [
                'id' => 'no',
                'name' => 'no_map',
                'title' => TemplaVoilaUtility::getLanguageService()->getLL('dsTypes_notmapped'),
                'mapping' => TemplaVoilaUtility::getLanguageService()->getLL('mapNotMapped'),
            ],
        ];

        // Render content, depending on input values:
        if ($this->displayFile) { // Browsing file directly, possibly creating a template/data object records.
            $this->renderFile();
        } elseif ($this->displayTable == 'tx_templavoilaplus_datastructure') { // Data source display
            $this->renderDSO();
        } elseif ($this->displayTable == 'tx_templavoilaplus_tmplobj') { // Data source display
            $this->renderTO();
        }
    }


    /**
     * Renders the display of DS/TO creation directly from a file
     *
     * @return void
     */
    public function renderFile()
    {
        if (@is_file($this->displayFile) && GeneralUtility::getFileAbsFileName($this->displayFile)) {
            // Converting GPvars into a "cmd" value:
            $cmd = '';
            if (GeneralUtility::_GP('_load_ds_xml')) { // Loading DS from XML or TO uid
                $cmd = 'load_ds_xml';
            } elseif (GeneralUtility::_GP('_clear')) { // Resetting mapping/DS
                $cmd = 'clear';
            } elseif (GeneralUtility::_GP('_saveDSandTO')) { // Saving DS and TO to records.
                if (!strlen(trim($this->_saveDSandTO_title))) {
                    $cmd = 'saveScreen';
                    $this->moduleTemplate->addFlashMessage(
                        TemplaVoilaUtility::getLanguageService()->getLL('errorNoToTitleDefined'),
                        TemplaVoilaUtility::getLanguageService()->getLL('error'),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                    );
                } else {
                    $cmd = 'saveDSandTO';
                }
            } elseif (GeneralUtility::_GP('_updateDSandTO')) { // Updating DS and TO
                $cmd = 'updateDSandTO';
            } elseif (GeneralUtility::_GP('_showXMLDS')) { // Showing current DS as XML
                $cmd = 'showXMLDS';
            } elseif (GeneralUtility::_GP('_preview')) { // Previewing mappings
                $cmd = 'preview';
            } elseif (GeneralUtility::_GP('_save_data_mapping')) { // Saving mapping to Session
                $cmd = 'save_data_mapping';
            } elseif (GeneralUtility::_GP('_updateDS')) {
                $cmd = 'updateDS';
            } elseif (GeneralUtility::_GP('DS_element_DELETE')) {
                $cmd = 'DS_element_DELETE';
            } elseif (GeneralUtility::_GP('_saveScreen')) {
                $cmd = 'saveScreen';
            } elseif (GeneralUtility::_GP('_loadScreen')) {
                $cmd = 'loadScreen';
            } elseif (GeneralUtility::_GP('_save')) {
                $cmd = 'saveUpdatedDSandTO';
            } elseif (GeneralUtility::_GP('_saveExit')) {
                $cmd = 'saveUpdatedDSandTOandExit';
            }

            // Init settings:
            $this->editDataStruct = 1; // Edit DS...
            $content = '';

            // Checking Storage Folder PID:
            if (!count($this->storageFolders)) {
                $this->moduleTemplate->addFlashMessage(
                    TemplaVoilaUtility::getLanguageService()->getLL('errorNoStorageFolder'),
                    TemplaVoilaUtility::getLanguageService()->getLL('error'),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                );
            }

            // Session data
            $this->sessionKey = $this->MCONF['name'] . '_mappingInfo:' . $this->_load_ds_xml_to;
            if ($cmd == 'clear') { // Reset session data:
                $sesDat = array('displayFile' => $this->displayFile, 'TO' => $this->_load_ds_xml_to, 'DS' => $this->displayUid);
                TemplaVoilaUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
            } else { // Get session data:
                $sesDat = TemplaVoilaUtility::getBackendUser()->getSessionData($this->sessionKey);
            }
            if ($this->_load_ds_xml_to) {
                $toREC = BackendUtility::getRecordWSOL('tx_templavoilaplus_tmplobj', $this->_load_ds_xml_to);
                if ($this->staticDS) {
                    $dsREC['dataprot'] = GeneralUtility::getURL(GeneralUtility::getFileAbsFileName($toREC['datastructure']));
                } else {
                    $dsREC = BackendUtility::getRecordWSOL('tx_templavoilaplus_datastructure', $toREC['datastructure']);
                }
            }

            // Loading DS from either XML or a Template Object (containing reference to DS)
            if ($cmd == 'load_ds_xml' && ($this->_load_ds_xml_content || $this->_load_ds_xml_to)) {
                $to_uid = $this->_load_ds_xml_to;
                if ($to_uid) {
                    $tM = unserialize($toREC['templatemapping']);
                    $sesDat = array('displayFile' => $this->displayFile, 'TO' => $this->_load_ds_xml_to, 'DS' => $this->displayUid);
                    $sesDat['currentMappingInfo'] = $tM['MappingInfo'];
                    $sesDat['currentMappingInfo_head'] = $tM['MappingInfo_head'];
                    $ds = GeneralUtility::xml2array($dsREC['dataprot']);
                    $sesDat['dataStruct'] = $sesDat['autoDS'] = $ds; // Just set $ds, not only its ROOT! Otherwise <meta> will be lost.
                    TemplaVoilaUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                } else {
                    $ds = GeneralUtility::xml2array($this->_load_ds_xml_content);
                    $sesDat = array('displayFile' => $this->displayFile, 'TO' => $this->_load_ds_xml_to, 'DS' => $this->displayUid);
                    $sesDat['dataStruct'] = $sesDat['autoDS'] = $ds;
                    TemplaVoilaUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                }
            }

            // Setting Data Structure to value from session data - unless it does not exist in which case a default structure is created.
            $dataStruct = is_array($sesDat['autoDS']) ? $sesDat['autoDS'] : array(
                'meta' => array(
                    'langDisable' => '1',
                ),
                'ROOT' => array(
                    'tx_templavoilaplus' => array(
                        'title' => 'ROOT',
                        'description' => TemplaVoilaUtility::getLanguageService()->getLL('rootDescription'),
                    ),
                    'type' => 'array',
                    'el' => array()
                )
            );

            // Setting Current Mapping information to session variable content OR blank if none exists.
            $currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : [];
            $this->cleanUpMappingInfoAccordingToDS($currentMappingInfo, $dataStruct); // This will clean up the Current Mapping info to match the Data Structure.

            // CMD switch:
            switch ($cmd) {
                // Saving incoming Mapping Data to session data:
                case 'save_data_mapping':
                    $inputData = GeneralUtility::_GP('dataMappingForm', 1);
                    if (is_array($inputData)) {
                        $sesDat['currentMappingInfo'] = $currentMappingInfo = $this->array_merge_recursive_overrule($currentMappingInfo, $inputData);
                        $sesDat['dataStruct'] = $dataStruct;
                        TemplaVoilaUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                    }
                    break;
                // Saving incoming Data Structure settings to session data:
                case 'updateDS':
                    $inDS = GeneralUtility::_GP('autoDS', 1);
                    if (is_array($inDS)) {
                        $sesDat['dataStruct'] = $sesDat['autoDS'] = $dataStruct = $this->array_merge_recursive_overrule($dataStruct, $inDS);
                        TemplaVoilaUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                    }
                    break;
                // If DS element is requested for deletion, remove it and update session data:
                case 'DS_element_DELETE':
                    $ref = explode('][', substr($this->DS_element_DELETE, 1, -1));
                    $this->unsetArrayPath($dataStruct, $ref);
                    $sesDat['dataStruct'] = $sesDat['autoDS'] = $dataStruct;
                    TemplaVoilaUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                    break;
            }

            // Creating $templatemapping array with cached mapping content:
            if (GeneralUtility::inList('showXMLDS,saveDSandTO,updateDSandTO,saveUpdatedDSandTO,saveUpdatedDSandTOandExit', $cmd)) {
                // Template mapping prepared:
                $templatemapping = [];
                $templatemapping['MappingInfo'] = $currentMappingInfo;
                if (isset($sesDat['currentMappingInfo_head'])) {
                    $templatemapping['MappingInfo_head'] = $sesDat['currentMappingInfo_head'];
                }

                // Getting cached data:
                reset($dataStruct);
                $fileContent = GeneralUtility::getUrl($this->displayFile);
                $relPathFix = dirname(substr($this->displayFile, strlen(PATH_site))) . '/';

                // @TODO We have this init multiple in this class => BAD
                // @TODO We have this loading 3 times in this class => BAD
                // Init mark up object.
                $this->markupObj = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Domain\Model\HtmlMarkup::class);
                $this->markupObj->init();

                $fileContent = $this->markupObj->htmlParse->prefixResourcePath($relPathFix, $fileContent);
                $contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($fileContent, $currentMappingInfo);
                $templatemapping['MappingData_cached'] = $contentSplittedByMapping['sub']['ROOT'];

                // Get <head>...</head> from template:
                $splitByHeader = $this->markupObj->htmlParse->splitIntoBlock('head', $fileContent);
                // There should be only one head tag
                $html_header = $this->markupObj->htmlParse->removeFirstAndLastTag($splitByHeader[1]);

                $this->markupObj->tags = $this->head_markUpTags; // Set up the markupObject to process only header-section tags:

                if (isset($templatemapping['MappingInfo_head'])) {
                    $h_currentMappingInfo = [];
                    $currentMappingInfo_head = $templatemapping['MappingInfo_head'];
                    if (is_array($currentMappingInfo_head['headElementPaths'])) {
                        foreach ($currentMappingInfo_head['headElementPaths'] as $kk => $vv) {
                            $h_currentMappingInfo['el_' . $kk]['MAP_EL'] = $vv;
                        }
                    }

                    $contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($html_header, $h_currentMappingInfo);
                    $templatemapping['MappingData_head_cached'] = $contentSplittedByMapping;

                    // Get <body> tag:
                    $reg = '';
                    preg_match('/<body[^>]*>/i', $fileContent, $reg);
                    $templatemapping['BodyTag_cached'] = $currentMappingInfo_head['addBodyTag'] ? $reg[0] : '';
                }

                if ($cmd != 'showXMLDS') {
                    // Set default flags to <meta> tag
                    if (!isset($dataStruct['meta']) || !is_array($dataStruct['meta'])) {
                        if (isset($dataStruct['meta'])) {
                            unset($dataStruct['meta']);
                        }
                        // Make sure <meta> goes at the beginning of data structure.
                        // This is not critical for typo3 but simply convinient to
                        // people who used to see it at the beginning.
                        $dataStruct = array_merge(array('meta' => array()), $dataStruct);
                    }
                    if ($this->_saveDSandTO_type == 1) {
                        // If we save a page template, set langDisable to 1 as per localization guide
                        if (!isset($dataStruct['meta']['langDisable'])) {
                            $dataStruct['meta']['langDisable'] = '1';
                        }
                    } else {
                        // FCE defaults to inheritance
                        if (!isset($dataStruct['meta']['langDisable'])) {
                            $dataStruct['meta']['langDisable'] = '0';
                            $dataStruct['meta']['langChildren'] = '1';
                        }
                    }
                }
            }

            // CMD switch:
            switch ($cmd) {
                // If it is requested to save the current DS and mapping information to a DS and TO record, then...:
                case 'saveDSandTO':
                    // Init TCEmain object and store:
                    $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                    $tce->stripslashes_values = 0;

                    // DS:

                    // Modifying data structure with conversion of preset values for field types to actual settings:
                    $storeDataStruct = $dataStruct;
                    if (is_array($storeDataStruct['ROOT']['el'])) {
                        $this->convertDsTypeBack($storeDataStruct['ROOT']['el']);
                        $this->eTypes->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'], $contentSplittedByMapping['sub']['ROOT'], $dataArr['tx_templavoilaplus_datastructure']['NEW']['scope']);
                    }
                    $dataProtXML = DataStructureUtility::array2xml($storeDataStruct);

                    if ($this->staticDS) {
                        $title = preg_replace('|[/,\."\']+|', '_', $this->_saveDSandTO_title) . ' (' . ($this->_saveDSandTO_type == 1 ? 'page' : 'fce') . ').xml';
                        $path = GeneralUtility::getFileAbsFileName($this->_saveDSandTO_type == 2 ? $this->extConf['staticDS.']['path_fce'] : $this->extConf['staticDS.']['path_page']) . $title;
                        GeneralUtility::writeFile($path, $dataProtXML);
                        $newID = substr($path, strlen(PATH_site));
                    } else {
                        $dataArr = [];
                        $dataArr['tx_templavoilaplus_datastructure']['NEW']['pid'] = (int)$this->_saveDSandTO_pid;
                        $dataArr['tx_templavoilaplus_datastructure']['NEW']['title'] = $this->_saveDSandTO_title;
                        $dataArr['tx_templavoilaplus_datastructure']['NEW']['scope'] = $this->_saveDSandTO_type;
                        $dataArr['tx_templavoilaplus_datastructure']['NEW']['dataprot'] = $dataProtXML;

                        // start data processing
                        $tce->start($dataArr, array());
                        $tce->process_datamap();
                        $newID = (int)$tce->substNEWwithIDs['NEW'];
                    }

                    // If that succeeded, create the TO as well:
                    if ($newID) {
                        $dataArr = [];
                        $dataArr['tx_templavoilaplus_tmplobj']['NEW']['pid'] = (int)$this->_saveDSandTO_pid;
                        $dataArr['tx_templavoilaplus_tmplobj']['NEW']['title'] = $this->_saveDSandTO_title . ' [Template]';
                        $dataArr['tx_templavoilaplus_tmplobj']['NEW']['datastructure'] = $newID;
                        $dataArr['tx_templavoilaplus_tmplobj']['NEW']['fileref'] = substr($this->displayFile, strlen(PATH_site));
                        $dataArr['tx_templavoilaplus_tmplobj']['NEW']['templatemapping'] = serialize($templatemapping);
                        $dataArr['tx_templavoilaplus_tmplobj']['NEW']['fileref_mtime'] = @filemtime($this->displayFile);
                        $dataArr['tx_templavoilaplus_tmplobj']['NEW']['fileref_md5'] = @md5_file($this->displayFile);

                        // Init TCEmain object and store:
                        $tce->start($dataArr, array());
                        $tce->process_datamap();
                        $newToID = (int)$tce->substNEWwithIDs['NEW'];
                        if ($newToID) {
                            $this->moduleTemplate->addFlashMessage(
                                sprintf(
                                    TemplaVoilaUtility::getLanguageService()->getLL('msgDSTOSaved'),
                                    $dataArr['tx_templavoilaplus_tmplobj']['NEW']['datastructure'],
                                    $tce->substNEWwithIDs['NEW'],
                                    $this->_saveDSandTO_pid
                                ),
                                '',
                                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
                            );
                        } else {
                            $this->moduleTemplate->addFlashMessage(
                                sprintf(
                                    TemplaVoilaUtility::getLanguageService()->getLL('errorTONotSaved'),
                                    $dataArr['tx_templavoilaplus_tmplobj']['NEW']['datastructure']
                                ),
                                TemplaVoilaUtility::getLanguageService()->getLL('error'),
                                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                            );
                        }
                    } else {
                        $this->moduleTemplate->addFlashMessage(
                            TemplaVoilaUtility::getLanguageService()->getLL('errorTONotCreated'),
                            TemplaVoilaUtility::getLanguageService()->getLL('error'),
                            \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                        );
                    }

                    unset($tce);
                    if ($newID && $newToID) {
                        //redirect to edit view
                        $this->redirectToModifyDSTO($newToID, $newID);
                        exit;
                    } else {
                        // Clear cached header info because saveDSandTO always resets headers
                        $sesDat['currentMappingInfo_head'] = '';
                        TemplaVoilaUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                    }
                    break;
                // Updating DS and TO records:
                case 'updateDSandTO':
                case 'saveUpdatedDSandTO':
                case 'saveUpdatedDSandTOandExit':
                    if ($cmd == 'updateDSandTO') {
                        // Looking up the records by their uids:
                        $toREC = BackendUtility::getRecordWSOL('tx_templavoilaplus_tmplobj', $this->_saveDSandTO_TOuid);
                    } else {
                        $toREC = BackendUtility::getRecordWSOL('tx_templavoilaplus_tmplobj', $this->_load_ds_xml_to);
                    }
                    if ($this->staticDS) {
                        $dsREC['uid'] = $toREC['datastructure'];
                    } else {
                        $dsREC = BackendUtility::getRecordWSOL('tx_templavoilaplus_datastructure', $toREC['datastructure']);
                    }

                    // If they are found, continue:
                    if ($toREC['uid'] && $dsREC['uid']) {
                        // Init TCEmain object and store:
                        $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                        $tce->stripslashes_values = 0;

                        // Modifying data structure with conversion of preset values for field types to actual settings:
                        $storeDataStruct = $dataStruct;
                        if (is_array($storeDataStruct['ROOT']['el'])) {
                            $this->convertDsTypeBack($storeDataStruct['ROOT']['el']);
                            $this->eTypes->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'], $contentSplittedByMapping['sub']['ROOT'], $dsREC['scope']);
                        }
                        $dataProtXML = DataStructureUtility::array2xml($storeDataStruct);

                        // DS:
                        if ($this->staticDS) {
                            $path = PATH_site . $dsREC['uid'];
                            GeneralUtility::writeFile($path, $dataProtXML);
                        } else {
                            $dataArr = [];
                            $dataArr['tx_templavoilaplus_datastructure'][$dsREC['uid']]['dataprot'] = $dataProtXML;

                            // process data
                            $tce->start($dataArr, array());
                            $tce->process_datamap();
                        }

                        // TO:
                        $TOuid = BackendUtility::wsMapId('tx_templavoilaplus_tmplobj', $toREC['uid']);
                        $dataArr = [];
                        $dataArr['tx_templavoilaplus_tmplobj'][$TOuid]['fileref'] = substr($this->displayFile, strlen(PATH_site));
                        $dataArr['tx_templavoilaplus_tmplobj'][$TOuid]['templatemapping'] = serialize($templatemapping);
                        $dataArr['tx_templavoilaplus_tmplobj'][$TOuid]['fileref_mtime'] = @filemtime($this->displayFile);
                        $dataArr['tx_templavoilaplus_tmplobj'][$TOuid]['fileref_md5'] = @md5_file($this->displayFile);

                        $tce->start($dataArr, array());
                        $tce->process_datamap();

                        unset($tce);

                        $this->moduleTemplate->addFlashMessage(
                            sprintf(
                                TemplaVoilaUtility::getLanguageService()->getLL('msgDSTOUpdated'),
                                $dsREC['uid'],
                                $toREC['uid']
                            ),
                            '',
                            \TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE
                        );

                        if ($cmd == 'updateDSandTO') {
                            if (!$this->_load_ds_xml_to) {
                                //new created was saved to existing DS/TO, redirect to edit view
                                $this->redirectToModifyDSTO($toREC['uid'], $dsREC['uid']);
                                exit;
                            } else {
                                // Clear cached header info because updateDSandTO always resets headers
                                $sesDat['currentMappingInfo_head'] = '';
                                TemplaVoilaUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                            }
                        } elseif ($cmd == 'saveUpdatedDSandTOandExit') {
                            header('Location:' . GeneralUtility::locationHeaderUrl($this->returnUrl));
                        }
                    }
                    break;
            }

            // Header:
            $tRows = [];
            $relFilePath = substr($this->displayFile, strlen(PATH_site));
            $onCl = 'return top.openUrlInWindow(\'' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $relFilePath . '\',\'FileView\');';
            $tRows[] = '
                <tr>
                    <td class="bgColor5" rowspan="2">' . $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_file', '|') . '</td>
                    <td class="bgColor5" rowspan="2"><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('templateFile') . ':</strong></td>
                    <td class="bgColor4"><a href="#" onclick="' . htmlspecialchars($onCl) . '">' . htmlspecialchars($relFilePath) . '</a></td>
                </tr>
                 <tr>
                    <td class="bgColor4">
                        <a href="#" onclick ="openValidator(\'' . $this->sessionKey . '\');return false;">'
                            . $this->iconFactory->getIcon('extensions-templavoila-htmlvalidate', Icon::SIZE_SMALL)->render()
                            . ' ' . TemplaVoilaUtility::getLanguageService()->getLL('validateTpl')
                            . '
                        </a>
                    </td>
                </tr>
                <tr>
                    <td class="bgColor5">&nbsp;</td>
                    <td class="bgColor5"><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('templateObject') . ':</strong></td>
                    <td class="bgColor4">' . ($toREC ? htmlspecialchars(TemplaVoilaUtility::getLanguageService()->sL($toREC['title'])) : TemplaVoilaUtility::getLanguageService()->getLL('mappingNEW')) . '</td>
                </tr>';
            if ($this->staticDS) {
                $onClick = 'return top.openUrlInWindow(\'' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $toREC['datastructure'] . '\',\'FileView\');';
                $tRows[] = '
                <tr>
                    <td class="bgColor5">&nbsp;</td>
                    <td class="bgColor5"><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('renderDSO_XML') . ':</strong></td>
                    <td class="bgColor4"><a href="#" onclick="' . htmlspecialchars($onClick) . '">' . htmlspecialchars($toREC['datastructure']) . '</a></td>
                </tr>';
            } else {
                $tRows[] = '
                <tr>
                    <td class="bgColor5">&nbsp;</td>
                    <td class="bgColor5"><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('renderTO_dsRecord') . ':</strong></td>
                    <td class="bgColor4">' . ($dsREC ? htmlspecialchars(TemplaVoilaUtility::getLanguageService()->sL($dsREC['title'])) : TemplaVoilaUtility::getLanguageService()->getLL('mappingNEW')) . '</td>
                </tr>';
            }

            // Write header of page:
            $content .= '<!-- Create Data Structure Header: -->
                <table border="0" cellpadding="2" cellspacing="1" id="c-toHeader">
                    ' . implode('', $tRows) . '
                </table><br />
            ';

            // Generate selector box options:
            // Storage Folders for elements:
            $sf_opt = [];
            $res = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTquery(
                '*',
                'pages',
                'uid IN (' . $this->storageFolders_pidList . ')' . BackendUtility::deleteClause('pages'),
                '',
                'title'
            );
            while (false !== ($row = TemplaVoilaUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
                $sf_opt[] = '<option value="' . htmlspecialchars($row['uid']) . '">' . htmlspecialchars($row['title'] . ' (UID:' . $row['uid'] . ')') . '</option>';
            }

            // Template Object records:
            $opt = [];
            $opt[] = '<option value="0"></option>';
            if ($this->staticDS) {
                $res = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTquery(
                    '*, CASE WHEN LOCATE(' . TemplaVoilaUtility::getDatabaseConnection()->fullQuoteStr('(fce)', 'tx_templavoilaplus_tmplobj') . ', datastructure)>0 THEN 2 ELSE 1 END AS scope',
                    'tx_templavoilaplus_tmplobj',
                    'pid IN (' . $this->storageFolders_pidList . ') AND datastructure!=' . TemplaVoilaUtility::getDatabaseConnection()->fullQuoteStr('', 'tx_templavoilaplus_tmplobj') .
                    BackendUtility::deleteClause('tx_templavoilaplus_tmplobj') .
                    BackendUtility::versioningPlaceholderClause('tx_templavoilaplus_tmplobj'),
                    '',
                    'scope,title'
                );
            } else {
                $res = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTquery(
                    'tx_templavoilaplus_tmplobj.*,tx_templavoilaplus_datastructure.scope',
                    'tx_templavoilaplus_tmplobj LEFT JOIN tx_templavoilaplus_datastructure ON tx_templavoilaplus_datastructure.uid=tx_templavoilaplus_tmplobj.datastructure',
                    'tx_templavoilaplus_tmplobj.pid IN (' . $this->storageFolders_pidList . ') AND tx_templavoilaplus_tmplobj.datastructure>0 ' .
                    BackendUtility::deleteClause('tx_templavoilaplus_tmplobj') .
                    BackendUtility::versioningPlaceholderClause('tx_templavoilaplus_tmplobj'),
                    '',
                    'tx_templavoilaplus_datastructure.scope, tx_templavoilaplus_tmplobj.pid, tx_templavoilaplus_tmplobj.title'
                );
            }
            $storageFolderPid = 0;
            $optGroupOpen = false;
            while (false !== ($row = TemplaVoilaUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
                $scope = $row['scope'];
                unset($row['scope']);
                BackendUtility::workspaceOL('tx_templavoilaplus_tmplobj', $row);
                if ($storageFolderPid != $row['pid']) {
                    $storageFolderPid = $row['pid'];
                    if ($optGroupOpen) {
                        $opt[] = '</optgroup>';
                    }
                    $opt[] = '<optgroup label="' . htmlspecialchars($this->storageFolders[$storageFolderPid] . ' (PID: ' . $storageFolderPid . ')') . '">';
                    $optGroupOpen = true;
                }
                $opt[] = '<option value="' . htmlspecialchars($row['uid']) . '" ' .
                    ($scope == 1 ? 'class="pagetemplate">' : 'class="fce">') .
                    htmlspecialchars(TemplaVoilaUtility::getLanguageService()->sL($row['title']) . ' (UID:' . $row['uid'] . ')') . '</option>';
            }
            if ($optGroupOpen) {
                $opt[] = '</optgroup>';
            }

            // Module Interface output begin:
            switch ($cmd) {
                // Show XML DS
                case 'showXMLDS':
                    // Make instance of syntax highlight class:
                    $hlObj = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Service\SyntaxHighlightingService::class);

                    $storeDataStruct = $dataStruct;
                    if (is_array($storeDataStruct['ROOT']['el'])) {
                        $this->eTypes->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'], $contentSplittedByMapping['sub']['ROOT']);
                    }
                    $dataStructureXML = DataStructureUtility::array2xml($storeDataStruct);

                    $content .= '
                        <input type="submit" name="_DO_NOTHING" value="Go back" title="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonGoBack') . '" />
                        <h3>' . TemplaVoilaUtility::getLanguageService()->getLL('titleXmlConfiguration') . ':</h3>
                        ' . $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_file_showXMLDS', '|<br/>') . '
                        <pre>' . $hlObj->highLight_DS($dataStructureXML) . '</pre>';
                    break;
                case 'loadScreen':
                    $content .= '
                        <h3>' . TemplaVoilaUtility::getLanguageService()->getLL('titleLoadDSXml') . '</h3>
                        ' . $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_file_loadDSXML', '|<br/>') . '
                        <p>' . TemplaVoilaUtility::getLanguageService()->getLL('selectTOrecrdToLoadDSFrom') . ':</p>
                        <select name="_load_ds_xml_to">' . implode('', $opt) . '</select>
                        <br />
                        <p>' . TemplaVoilaUtility::getLanguageService()->getLL('pasteDSXml') . ':</p>
                        <textarea rows="15" name="_load_ds_xml_content" wrap="off" style="width:98%;"></textarea>
                        <br />
                        <input type="submit" name="_load_ds_xml" value="' . TemplaVoilaUtility::getLanguageService()->getLL('loadDSXml') . '" />
                        <input type="submit" name="_" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonCancel') . '" />
                        ';
                    break;
                case 'saveScreen':
                    $content .= '
                        <h3>' . TemplaVoilaUtility::getLanguageService()->getLL('createDSTO') . ':</h3>
                        ' . $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_file_createDSTO', '|<br/>') . '
                        <table border="0" cellpadding="2" cellspacing="2" class="dso_table">
                            <tr>
                                <td class="bgColor5"><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('titleDSTO') . ':</strong></td>
                                <td class="bgColor4"><input type="text" name="_saveDSandTO_title" /></td>
                            </tr>
                            <tr>
                                <td class="bgColor5"><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('templateType') . ':</strong></td>
                                <td class="bgColor4">
                                    <select name="_saveDSandTO_type">
                                        <option value="1">' . TemplaVoilaUtility::getLanguageService()->getLL('pageTemplate') . '</option>
                                        <option value="2">' . TemplaVoilaUtility::getLanguageService()->getLL('contentElement') . '</option>
                                        <option value="0">' . TemplaVoilaUtility::getLanguageService()->getLL('undefined') . '</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="bgColor5"><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('storeInPID') . ':</strong></td>
                                <td class="bgColor4">
                                    <select name="_saveDSandTO_pid">
                                        ' . implode('
                                        ', $sf_opt) . '
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <input type="submit" name="_saveDSandTO" value="' . $GLOBALS['LANG']->getLL('createDSTOshort') . '" />
                        <input type="submit" name="_" value="' . $GLOBALS['LANG']->getLL('buttonCancel') . '" />



                        <h3>' . $GLOBALS['LANG']->getLL('updateDSTO') . ':</h3>
                        <table border="0" cellpadding="2" cellspacing="2">
                            <tr>
                                <td class="bgColor5"><strong>' . $GLOBALS['LANG']->getLL('selectTO') . ':</strong></td>
                                <td class="bgColor4">
                                    <select name="_saveDSandTO_TOuid">
                                        ' . implode('
                                        ', $opt) . '
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <input type="submit" name="_updateDSandTO" value="UPDATE TO (and DS)" onclick="return confirm(' . GeneralUtility::quoteJSvalue(TemplaVoilaUtility::getLanguageService()->getLL('saveDSTOconfirm')) . ');" />
                        <input type="submit" name="_" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonCancel') . '" />
                        ';
                    break;
                default:
                    // Creating menu:
                    $menuItems = [];
                    $menuItems[] = '<input type="submit" name="_showXMLDS" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonShowXML') . '" title="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonTitle_showXML') . '" />';
                    $menuItems[] = '<input type="submit" name="_clear" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonClearAll') . '" title="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonTitle_clearAll') . '" /> ';
                    $menuItems[] = '<input type="submit" name="_preview" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonPreview') . '" title="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonTitle_preview') . '" />';
                    if (is_array($toREC) && is_array($dsREC)) {
                        $menuItems[] = '<input type="submit" name="_save" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonSave') . '" title="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonTitle_save') . '" />';
                        $menuItems[] = '<input type="submit" name="_saveExit" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonSaveExit') . '" title="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonTitle_saveExit') . '" />';
                    }
                    $menuItems[] = '<input type="submit" name="_saveScreen" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonSaveAs') . '" title="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonTitle_saveAs') . '" />';
                    $menuItems[] = '<input type="submit" name="_loadScreen" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonLoad') . '" title="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonTitle_load') . '" />';
                    $menuItems[] = '<input type="submit" name="_DO_NOTHING" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonRefresh') . '" title="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonTitle_refresh') . '" />';

                    $menuContent = '<!-- Menu for creation Data Structures / Template Objects -->
                        <table border="0" cellpadding="2" cellspacing="2" id="c-toMenu">
                            <tr class="bgColor5">
                                <td>' . implode('</td>
                                <td>', $menuItems) . '</td>
                            </tr>
                        </table>';

                    $content .= '<!-- Data Structure creation table: -->'
                        . '<h3>' . $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_file', '|') . TemplaVoilaUtility::getLanguageService()->getLL('buildingDS') . ':</h3>'
                        . $this->renderTemplateMapper($this->displayFile, $this->displayPath, $dataStruct, $currentMappingInfo, $menuContent);
                    break;
            }
        }

        $this->content .= $content;
    }

    /**
     * Renders the display of Data Structure Objects.
     *
     * @return void
     */
    public function renderDSO()
    {
        if ((int)$this->displayUid > 0) { // TODO: static ds support
            $row = BackendUtility::getRecordWSOL('tx_templavoilaplus_datastructure', $this->displayUid);
            if (is_array($row)) {
                // Get title and icon:
                $icon = $this->iconFactory->getIconForRecord('tx_templavoilaplus_datastructure', $row, Icon::SIZE_SMALL)->render();
                $title = BackendUtility::getRecordTitle('tx_templavoilaplus_datastructure', $row, 1);
                $content .= BackendUtility::wrapClickMenuOnIcon($icon, 'tx_templavoilaplus_datastructure', $row['uid'], true) .
                    '<strong>' . $title . '</strong><br />';

                // Get Data Structure:
                $origDataStruct = $dataStruct = $this->getDataStructFromDSO($row['dataprot']);

                if (is_array($dataStruct)) {
                    // Showing Data Structure:
                    $tRows = $this->drawDataStructureMap($dataStruct);
                    $content .= '

                    <!--
                        Data Structure content:
                    -->
                    <div id="c-ds">
                        <h4>' . TemplaVoilaUtility::getLanguageService()->getLL('renderDSO_dataStructure') . ':</h4>
                        <table border="0" cellspacing="2" cellpadding="2" class="dso_table">
                                    <tr class="bgColor5">
                                        <td nowrap="nowrap"><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('renderDSO_dataElement') . ':</strong>' .
                        $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_head_dataElement') .
                        '</td>
                    <td nowrap="nowrap"><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('renderDSO_mappingInstructions') . ':</strong>' .
                        $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_head_mapping_instructions') .
                        '</td>
                    <td nowrap="nowrap"><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('renderDSO_rules') . ':</strong>' .
                        $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_head_Rules') .
                        '</td>
                </tr>
    ' . implode('', $tRows) . '
                        </table>
                    </div>';

                    // CSH
                    $content .= $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_ds');
                } else {
                    $content .= '<h4>' . TemplaVoilaUtility::getLanguageService()->getLL('error') . ': ' . TemplaVoilaUtility::getLanguageService()->getLL('noDSDefined') . '</h4>';
                }

                // Get Template Objects pointing to this Data Structure
                $res = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTquery(
                    '*',
                    'tx_templavoilaplus_tmplobj',
                    'pid IN (' . $this->storageFolders_pidList . ') AND datastructure=' . (int)$row['uid'] .
                    BackendUtility::deleteClause('tx_templavoilaplus_tmplobj') .
                    BackendUtility::versioningPlaceholderClause('tx_templavoilaplus_tmplobj')
                );
                $tRows = [];
                $tRows[] = '
                            <tr class="bgColor5">
                                <td><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('renderDSO_uid') . ':</strong></td>
                                <td><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('renderDSO_title') . ':</strong></td>
                                <td><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('renderDSO_fileRef') . ':</strong></td>
                                <td><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('renderDSO_dataLgd') . ':</strong></td>
                            </tr>';
                $TOicon = $this->iconFactory->getIconForRecord('tx_templavoilaplus_tmplobj', [], Icon::SIZE_SMALL)->render();

                // Listing Template Objects with links:
                while (false !== ($TO_Row = TemplaVoilaUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
                    BackendUtility::workspaceOL('tx_templavoilaplus_tmplobj', $TO_Row);
                    $tRows[] = '
                            <tr class="bgColor4">
                                <td>[' . $TO_Row['uid'] . ']</td>
                                <td nowrap="nowrap">' . BackendUtility::wrapClickMenuOnIcon($TOicon, 'tx_templavoilaplus_tmplobj', $TO_Row['uid'], true) .
                        '<a href="' . htmlspecialchars('index.php?table=tx_templavoilaplus_tmplobj&uid=' . $TO_Row['uid'] . '&_reload_from=1') . '">' .
                        BackendUtility::getRecordTitle('tx_templavoilaplus_tmplobj', $TO_Row, 1) . '</a>' .
                        '</td>
                    <td nowrap="nowrap">' . htmlspecialchars($TO_Row['fileref']) . ' <strong>' .
                        (!GeneralUtility::getFileAbsFileName($TO_Row['fileref'], 1) ? TemplaVoilaUtility::getLanguageService()->getLL('renderDSO_notFound') : TemplaVoilaUtility::getLanguageService()->getLL('renderDSO_ok')) . '</strong></td>
                                <td>' . strlen($TO_Row['templatemapping']) . '</td>
                            </tr>';
                }

                $content .= '

                    <!--
                        Template Objects attached to Data Structure Record:
                    -->
                    <div id="c-to">
                        <h4>' . TemplaVoilaUtility::getLanguageService()->getLL('renderDSO_usedTO') . ':</h4>
                        <table border="0" cellpadding="2" cellspacing="2" class="dso_table">
                        ' . implode('', $tRows) . '
                        </table>
                    </div>';

                // CSH
                $content .= $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_ds_to');

                // Display XML of data structure:
                if (is_array($dataStruct)) {
                    // Make instance of syntax highlight class:
                    $hlObj = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Service\SyntaxHighlightingService::class);

                    $dataStructureXML = DataStructureUtility::array2xml($origDataStruct);
                    $content .= '

                    <!--
                        Data Structure XML:
                    -->
                    <br />
                    <div id="c-dsxml">
                        <h3>' . TemplaVoilaUtility::getLanguageService()->getLL('renderDSO_XML') . ':</h3>
                        ' . $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_ds_showXML') . '
                        <p>' . BackendUtility::getFuncCheck('', 'SET[showDSxml]', $this->MOD_SETTINGS['showDSxml'], '', GeneralUtility::implodeArrayForUrl('', $_GET, '', 1, 1)) . ' Show XML</p>
                        <pre>' .
                        ($this->MOD_SETTINGS['showDSxml'] ? $hlObj->highLight_DS($dataStructureXML) : '') . '
                        </pre>
                    </div>
                    ';
                }
            } else {
                $content .= sprintf(TemplaVoilaUtility::getLanguageService()->getLL('errorNoDSrecord'), $this->displayUid);
            }
            $this->content .= '<h2>' . TemplaVoilaUtility::getLanguageService()->getLL('renderDSO_DSO') . '</h2>' . $content;
        } else {
            $this->content .= '<h2>Error:' . TemplaVoilaUtility::getLanguageService()->getLL('errorInDSO') . '</h2>'
                . TemplaVoilaUtility::getLanguageService()->getLL('renderDSO_noUid');
        }
    }

    /**
     * Renders the display of Template Objects.
     *
     * @return void
     */
    public function renderTO()
    {
        $error = null;

        if ((int)$this->displayUid > 0) {
            $row = BackendUtility::getRecordWSOL('tx_templavoilaplus_tmplobj', $this->displayUid);

            if (is_array($row)) {
                $tRows = [];
                $tRows[] =
                    '<thead>'
                        . '<th colspan="2"><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('renderTO_toDetails') . ':</strong>'
                        . $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_to')
                        . '</th></thead>';

                // Get title and icon:
                $icon = $this->iconFactory->getIconForRecord('tx_templavoilaplus_tmplobj', $row, Icon::SIZE_SMALL)->render();

                $title = BackendUtility::getRecordTitle('tx_templavoilaplus_tmplobj', $row);
                $title = BackendUtility::getRecordTitlePrep(TemplaVoilaUtility::getLanguageService()->sL($title));
                $tRows[] =
                    '<tr>
                        <td>' . TemplaVoilaUtility::getLanguageService()->getLL('templateObject') . ':</td>
                        <td>' . BackendUtility::wrapClickMenuOnIcon($icon, 'tx_templavoilaplus_tmplobj', $row['uid']) . ' ' . $title . '</td>
                    </tr>';

                // Session data
                $sessionKey = $this->MCONF['name'] . '_validatorInfo:' . $row['uid'];
                $sesDat = array('displayFile' => $row['fileref']);
                TemplaVoilaUtility::getBackendUser()->setAndSaveSessionData($sessionKey, $sesDat);

                // Find the file:
                $theFile = GeneralUtility::getFileAbsFileName($row['fileref'], 1);
                if ($theFile && FileUtility::haveTemplateAccess($row['fileref'])) {
                    $relFilePath = substr($theFile, strlen($theFile));
                    $onCl = 'return top.openUrlInWindow(\'' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $relFilePath . '\',\'FileView\');';
                    $tRows[] = '
                        <tr>
                            <td>' . TemplaVoilaUtility::getLanguageService()->getLL('templateFile') . ':</td>
                            <td><a href="#" onclick="' . htmlspecialchars($onCl) . '">'
                                . $this->iconFactory->getIconForFileExtension(
                                    pathinfo($theFile, PATHINFO_EXTENSION),
                                    Icon::SIZE_SMALL
                                )->render()
                                . ' ' . htmlspecialchars($relFilePath)
                                . '</a><br>
                                <a href="#" onclick ="openValidator(\'' . $sessionKey . '\');return false;">'
                                    . $this->iconFactory->getIcon('extensions-templavoila-htmlvalidate', Icon::SIZE_SMALL)->render()
                                    . ' ' . TemplaVoilaUtility::getLanguageService()->getLL('validateTpl')
                                    . '
                                </a>
                            </td>
                        </tr>';

                    // Finding Data Structure Record:
                    $DSOfile = '';
                    $dsValue = $row['datastructure'];
                    if ($row['parent']) {
                        $parentRec = BackendUtility::getRecordWSOL('tx_templavoilaplus_tmplobj', $row['parent'], 'datastructure');
                        $dsValue = $parentRec['datastructure'];
                    }

                    if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($dsValue)) {
                        $DS_row = BackendUtility::getRecordWSOL('tx_templavoilaplus_datastructure', $dsValue);
                    } else {
                        $DSOfile = GeneralUtility::getFileAbsFileName($dsValue);
                    }
                    $onClickUpdateDSTO = '';
                    if (is_array($DS_row) || @is_file($DSOfile)) {
                        // Get main DS array:
                        if (is_array($DS_row)) {
                            // Get title and icon:
                            $icon = $this->iconFactory->getIconForRecord('tx_templavoilaplus_datastructure', $DS_row, Icon::SIZE_SMALL)->render();
                            $title = BackendUtility::getRecordTitle('tx_templavoilaplus_datastructure', $DS_row);
                            $title = BackendUtility::getRecordTitlePrep(TemplaVoilaUtility::getLanguageService()->sL($title));

                            $tRows[] =
                                '<tr>
                                    <td>' . TemplaVoilaUtility::getLanguageService()->getLL('renderTO_dsRecord') . ':</td>
                                    <td>' . BackendUtility::wrapClickMenuOnIcon($icon, 'tx_templavoilaplus_datastructure', $DS_row['uid']) . ' ' . $title . '</td>
                                </tr>';

                            // Link to updating DS/TO:
                            $onClickUpdateDSTO = $this->getUrlToModifyDSTO($theFile, $row['uid'], $DS_row['uid'], $this->returnUrl);
                            // Read Data Structure:
                            $dataStruct = $this->getDataStructFromDSO($DS_row['dataprot']);
                        } else {
                            // Show filepath of external XML file:
                            $relFilePath = substr($DSOfile, strlen(PATH_site));
                            $onCl = 'return top.openUrlInWindow(\'' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $relFilePath . '\',\'FileView\');';
                            $tRows[] =
                                '<tr>
                                    <td>' . TemplaVoilaUtility::getLanguageService()->getLL('renderTO_dsFile') . ':</td>
                                    <td><a href="#" onclick="' . htmlspecialchars($onCl) . '">'
                                    . $this->iconFactory->getIconForFileExtension(
                                        pathinfo($relFilePath, PATHINFO_EXTENSION),
                                        Icon::SIZE_SMALL
                                    )->render()
                                    . ' ' . htmlspecialchars($relFilePath) . '</a></td>
                                </tr>';
                            $onClickUpdateDSTO = $this->getUrlToModifyDSTO($theFile, $row['uid'], $DSOfile, $this->returnUrl);

                            // Read Data Structure:
                            $dataStruct = $this->getDataStructFromDSO(GeneralUtility::getUrl($DSOfile));
                        }

                        $onClMsg = '
                            if (confirm(' . GeneralUtility::quoteJSvalue(TemplaVoilaUtility::getLanguageService()->getLL('renderTO_updateWarningConfirm')) . ')) {
                                document.location=\'' . $onClickUpdateDSTO . '\';
                            }
                            return false;
                            ';
                        $tRows[] =
                            '<tr class="danger">
                                <td>&nbsp;</td>
                                <td><input type="submit" name="_" value="' . TemplaVoilaUtility::getLanguageService()->getLL('renderTO_editDSTO') . '" onclick="' . htmlspecialchars($onClMsg) . '"/>'
                                . $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_to_modifyDSTO')
                                . '</td></tr>';

                        // Write header of page:
                        $content =
                            '<!-- Template Object Header: -->
                            <h3>' . TemplaVoilaUtility::getLanguageService()->getLL('renderTO_toInfo') . ':</h3>
                            <table class="table table-hover">
                                ' . implode('', $tRows) . '
                            </table>';

                        // If there is a valid data structure, draw table:
                        if (is_array($dataStruct)) {
                            // Working on Header and Body of HTML source:

                            // -- Processing the header editing --
                            list($editContent, $currentHeaderMappingInfo) = $this->renderTO_editProcessing($dataStruct, $row, $theFile, 1);

                            // Determine if DS is a template record and if it is a page template:
                            $showBodyTag = !is_array($DS_row) || $DS_row['scope'] == 1 ? true : false;

                            $parts = [];
                            $parts[] = [
                                'label' => TemplaVoilaUtility::getLanguageService()->getLL('tabTODetails'),
                                'content' => $content
                            ];

                            // -- Processing the head editing
                            $headerContent =
                                '<!-- HTML header parts selection: -->'
                                . '<h3>'
                                . TemplaVoilaUtility::getLanguageService()->getLL('mappingHeadParts') . ': '
                                . $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_to_headerParts')
                                . '</h3>'
                                . $this->renderHeaderSelection($theFile, $currentHeaderMappingInfo, $showBodyTag, $editContent);

                            $parts[] = [
                                'label' => TemplaVoilaUtility::getLanguageService()->getLL('tabHeadParts'),
                                'content' => $headerContent
                            ];

                            // -- Processing the body editing --
                            list($editContent, $currentMappingInfo) = $this->renderTO_editProcessing($dataStruct, $row, $theFile, 0);

                            $bodyContent =
                                '<!-- Data Structure mapping table: -->'
                                . '<h3>' . TemplaVoilaUtility::getLanguageService()->getLL('mappingBodyParts')
                                . ':</h3>'
                                . $this->renderTemplateMapper($theFile, $this->displayPath, $dataStruct, $currentMappingInfo, $editContent);

                            $parts[] = [
                                'label' => TemplaVoilaUtility::getLanguageService()->getLL('tabBodyParts'),
                                'content' => $bodyContent
                            ];
                            $this->content .= $this->moduleTemplate->getDynamicTabMenu($parts, 'TEMPLAVOILA:templateModule:' . $this->id, 1, 0, 300);
                        } else {
                            $error = sprintf(TemplaVoilaUtility::getLanguageService()->getLL('errorNoDSfound'), $dsValue);
                        }
                    } else {
                        $error = sprintf(TemplaVoilaUtility::getLanguageService()->getLL('errorNoDSfound'), $dsValue);
                    }
                } else {
                    $error = sprintf(TemplaVoilaUtility::getLanguageService()->getLL('errorFileNotFound'), $row['fileref']);
                }
            } else {
                $error = sprintf(TemplaVoilaUtility::getLanguageService()->getLL('errorNoTOfound'), $this->displayUid);
            }
        } else {
            $error = TemplaVoilaUtility::getLanguageService()->getLL('errorNoUidFound');
        }

        if ($error) {
            $this->moduleTemplate->addFlashMessage(
                $error,
                TemplaVoilaUtility::getLanguageService()->getLL('templateObject') . ' '
                . TemplaVoilaUtility::getLanguageService()->getLL('error'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
        }
    }

    /**
     * Process editing of a TO for renderTO() function
     *
     * @param array &$dataStruct Data Structure. Passed by reference; The sheets found inside will be resolved if found!
     * @param array $row TO record row
     * @param string Template file path (absolute)
     * @param integer $headerPart Process the headerPart instead of the bodyPart
     *
     * @return array Array with two keys (0/1) with a) content and b) currentMappingInfo which is retrieved inside (currentMappingInfo will be different based on whether "head" or "body" content is "mapped")
     * @see renderTO()
     * @TODO Is called twice for viewing tabs header parts and body mapping: As it also does processing, it does all processing twice.
     */
    public function renderTO_editProcessing(&$dataStruct, $row, $theFile, $headerPart = 0)
    {

        // Converting GPvars into a "cmd" value:
        $cmd = '';
        if (GeneralUtility::_GP('_reload_from')) { // Reverting to old values in TO
            $cmd = 'reload_from';
        } elseif (GeneralUtility::_GP('_clear')) { // Resetting mapping
            $cmd = 'clear';
        } elseif (GeneralUtility::_GP('_save_data_mapping')) { // Saving to Session
            $cmd = 'save_data_mapping';
        } elseif (GeneralUtility::_GP('_save_to') || GeneralUtility::_GP('_save_to_return')) { // Saving to Template Object
            $cmd = 'save_to';
        }

        // Getting data from tmplobj
        $templatemapping = unserialize($row['templatemapping']);
        if (!is_array($templatemapping)) {
            $templatemapping = [];
        }

        // If that array contains sheets, then traverse them:
        if (is_array($dataStruct['sheets'])) {
            $dSheets = TemplaVoilaUtility::resolveAllSheetsInDS($dataStruct);
            $dataStruct = array(
                'ROOT' => array(
                    'tx_templavoilaplus' => array(
                        'title' => TemplaVoilaUtility::getLanguageService()->getLL('rootMultiTemplate_title'),
                        'description' => TemplaVoilaUtility::getLanguageService()->getLL('rootMultiTemplate_description'),
                    ),
                    'type' => 'array',
                    'el' => array()
                )
            );
            foreach ($dSheets['sheets'] as $nKey => $lDS) {
                if (is_array($lDS['ROOT'])) {
                    $dataStruct['ROOT']['el'][$nKey] = $lDS['ROOT'];
                }
            }
        }

        // Get session data:
        $sesDat = TemplaVoilaUtility::getBackendUser()->getSessionData($this->sessionKey);

        // Set current mapping info arrays:
        $currentMappingInfo_head = is_array($sesDat['currentMappingInfo_head']) ? $sesDat['currentMappingInfo_head'] : [];
        $currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : [];
        $this->cleanUpMappingInfoAccordingToDS($currentMappingInfo, $dataStruct);

        // Perform processing for head
        // GPvars, incoming data
        $checkboxElement = GeneralUtility::_GP('checkboxElement', 1);
        $addBodyTag = GeneralUtility::_GP('addBodyTag');

        // Update session data:
        if ($cmd == 'reload_from' || $cmd == 'clear') {
            $currentMappingInfo_head = is_array($templatemapping['MappingInfo_head']) && $cmd != 'clear' ? $templatemapping['MappingInfo_head'] : [];
            $sesDat['currentMappingInfo_head'] = $currentMappingInfo_head;
            TemplaVoilaUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
        } else {
            if ($cmd == 'save_data_mapping' || $cmd == 'save_to') {
                $sesDat['currentMappingInfo_head'] = $currentMappingInfo_head = array(
                    'headElementPaths' => $checkboxElement,
                    'addBodyTag' => $addBodyTag ? 1 : 0
                );
                TemplaVoilaUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
            }
        }

        // Perform processing for  body
        // GPvars, incoming data
        $inputData = GeneralUtility::_GP('dataMappingForm', 1);

        // Update session data:
        if ($cmd == 'reload_from' || $cmd == 'clear') {
            $currentMappingInfo = is_array($templatemapping['MappingInfo']) && $cmd != 'clear' ? $templatemapping['MappingInfo'] : [];
            $this->cleanUpMappingInfoAccordingToDS($currentMappingInfo, $dataStruct);
            $sesDat['currentMappingInfo'] = $currentMappingInfo;
            $sesDat['dataStruct'] = $dataStruct;
            TemplaVoilaUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
        } else {
            if ($cmd == 'save_data_mapping' && is_array($inputData)) {
                $sesDat['currentMappingInfo'] = $currentMappingInfo = $this->array_merge_recursive_overrule($currentMappingInfo, $inputData);
                $sesDat['dataStruct'] = $dataStruct; // Adding data structure to session data so that the PREVIEW window can access the DS easily...
                TemplaVoilaUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
            }
        }

        // SAVE to template object
        if ($cmd == 'save_to') {
            $dataArr = [];

            // Set content, either for header or body:
            $templatemapping['MappingInfo_head'] = $currentMappingInfo_head;
            $templatemapping['MappingInfo'] = $currentMappingInfo;

            // Getting cached data:
            reset($dataStruct);
            // Init; read file, init objects:
            $fileContent = GeneralUtility::getUrl($theFile);

            // Init mark up object.
            $this->markupObj = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Domain\Model\HtmlMarkup::class);
            $this->markupObj->init();

            // Fix relative paths in source:
            $relPathFix = dirname(substr($theFile, strlen(PATH_site))) . '/';
            $uniqueMarker = uniqid('###') . '###';
            $fileContent = $this->markupObj->htmlParse->prefixResourcePath($relPathFix, $fileContent, array('A' => $uniqueMarker));
            $fileContent = $this->fixPrefixForLinks($relPathFix, $fileContent, $uniqueMarker);

            // Get BODY content for caching:
            $contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($fileContent, $currentMappingInfo);
            $templatemapping['MappingData_cached'] = $contentSplittedByMapping['sub']['ROOT'];

            // Get HEAD content for caching:
            // Get <head>...</head> from template:
            $splitByHeader = $this->markupObj->htmlParse->splitIntoBlock('head', $fileContent);
            // There should be only one head tag
            $html_header = $this->markupObj->htmlParse->removeFirstAndLastTag($splitByHeader[1]);

            $this->markupObj->tags = $this->head_markUpTags; // Set up the markupObject to process only header-section tags:

            $h_currentMappingInfo = [];
            if (is_array($currentMappingInfo_head['headElementPaths'])) {
                foreach ($currentMappingInfo_head['headElementPaths'] as $kk => $vv) {
                    $h_currentMappingInfo['el_' . $kk]['MAP_EL'] = $vv;
                }
            }

            $contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($html_header, $h_currentMappingInfo);
            $templatemapping['MappingData_head_cached'] = $contentSplittedByMapping;

            // Get <body> tag:
            $reg = '';
            preg_match('/<body[^>]*>/i', $fileContent, $reg);
            $templatemapping['BodyTag_cached'] = $currentMappingInfo_head['addBodyTag'] ? $reg[0] : '';

            $TOuid = BackendUtility::wsMapId('tx_templavoilaplus_tmplobj', $row['uid']);
            $dataArr['tx_templavoilaplus_tmplobj'][$TOuid]['templatemapping'] = serialize($templatemapping);
            $dataArr['tx_templavoilaplus_tmplobj'][$TOuid]['fileref_mtime'] = @filemtime($theFile);
            $dataArr['tx_templavoilaplus_tmplobj'][$TOuid]['fileref_md5'] = @md5_file($theFile);

            $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
            $tce->stripslashes_values = 0;
            $tce->start($dataArr, array());
            $tce->process_datamap();
            unset($tce);

            $this->moduleTemplate->addFlashMessage(
                TemplaVoilaUtility::getLanguageService()->getLL('msgMappingSaved'),
                '',
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );

            $row = BackendUtility::getRecordWSOL('tx_templavoilaplus_tmplobj', $this->displayUid);
            $templatemapping = unserialize($row['templatemapping']);

            if (GeneralUtility::_GP('_save_to_return')) {
                header('Location: ' . GeneralUtility::locationHeaderUrl($this->returnUrl));
                exit;
            }
        }

        // Making the menu
        $menuItems = [];
        $menuItems[] = '<input type="submit" name="_clear" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonClearAll') . '" title="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonClearAllMappingTitle') . '" />';

        // Make either "Preview" button (body) or "Set" button (header)
        if ($headerPart) { // Header:
            $menuItems[] = '<input type="submit" name="_save_data_mapping" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonSet') . '" title="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonSetTitle') . '" />';
        } else { // Body:
            $menuItems[] = '<input type="submit" name="_preview" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonPreview') . '" title="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonPreviewMappingTitle') . '" />';
        }

        $menuItems[] = '<input type="submit" name="_save_to" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonSave') . '" title="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonSaveTOTitle') . '" />';

        if ($this->returnUrl) {
            $menuItems[] = '<input type="submit" name="_save_to_return" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonSaveAndReturn') . '" title="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonSaveAndReturnTitle') . '" />';
        }

        // If a difference is detected...:
        if ((serialize($templatemapping['MappingInfo_head']) != serialize($currentMappingInfo_head)) ||
            (serialize($templatemapping['MappingInfo']) != serialize($currentMappingInfo))
        ) {
            $menuItems[] = '<input type="submit" name="_reload_from" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonRevert') . '" title="' . sprintf(TemplaVoilaUtility::getLanguageService()->getLL('buttonRevertTitle'), $headerPart ? 'HEAD' : 'BODY') . '" />';

            $this->moduleTemplate->addFlashMessage(
                TemplaVoilaUtility::getLanguageService()->getLL('msgMappingIsDifferent'),
                '',
                \TYPO3\CMS\Core\Messaging\FlashMessage::INFO
            );
        }

        $content = '
            <!--
                Menu for saving Template Objects
            -->
            <table border="0" cellpadding="2" cellspacing="2" id="c-toMenu">
                <tr class="bgColor5">
                    <td>' . implode('</td>
                    <td>', $menuItems) . '</td>
                </tr>
            </table>
        ';

        return array($content, $headerPart ? $currentMappingInfo_head : $currentMappingInfo);
    }

    /*******************************
     *
     * Mapper functions
     *
     *******************************/

    /**
     * Renders the table with selection of part from the HTML header + bodytag.
     *
     * @param string $displayFile The abs file name to read
     * @param array $currentHeaderMappingInfo Header mapping information
     * @param boolean $showBodyTag If true, show body tag.
     * @param string $htmlAfterDSTable HTML content to show after the Data Structure table.
     *
     * @return string HTML table.
     */
    public function renderHeaderSelection($displayFile, $currentHeaderMappingInfo, $showBodyTag, $htmlAfterDSTable = '')
    {
        // Get file content
        $this->markupFile = $displayFile;
        $fileContent = GeneralUtility::getUrl($this->markupFile);

        // Init mark up object.
        $this->markupObj = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Domain\Model\HtmlMarkup::class);
        $this->markupObj->init();

        // Get <body> tag:
        $reg = '';
        preg_match('/<body[^>]*>/i', $fileContent, $reg);
        $html_body = $reg[0];

        // Get <head>...</head> from template:
        $splitByHeader = $this->markupObj->htmlParse->splitIntoBlock('head', $fileContent);
        // There should be only one head tag
        $html_header = $this->markupObj->htmlParse->removeFirstAndLastTag($splitByHeader[1]);

        // Set up the markupObject to process only header-section tags:
        $this->markupObj->tags = $this->head_markUpTags;
        $this->markupObj->checkboxPathsSet = is_array($currentHeaderMappingInfo['headElementPaths']) ? $currentHeaderMappingInfo['headElementPaths'] : [];
        $this->markupObj->maxRecursion = 0; // Should not enter more than one level.

        // Markup the header section data with the header tags, using "checkbox" mode:
        $tRows = $this->markupObj->markupHTMLcontent($html_header, '', 'script,style,link,meta', 'checkbox');

        $bodyTagRow = $showBodyTag ? '
                <tr class="info">
                    <td><input type="checkbox" name="addBodyTag" value="1"' . ($currentHeaderMappingInfo['addBodyTag'] ? ' checked="checked"' : '') . ' /></td>
                    <td>' . \Ppi\TemplaVoilaPlus\Domain\Model\HtmlMarkup::getGnyfMarkup('body') . '</td>
                    <td><pre>' . htmlspecialchars($html_body) . '</pre></td>
                </tr>' : '';

        $headerParts =
            '<!-- Header parts: -->
            <table class="table table-striped table-hover">
                <thead>
                    <th>' . TemplaVoilaUtility::getLanguageService()->getLL('include') . ':</th>
                    <th>' . TemplaVoilaUtility::getLanguageService()->getLL('tag') . ':</th>
                    <th>' . TemplaVoilaUtility::getLanguageService()->getLL('tagContent') . ':</th>
                </thead>
                ' . $tRows . '
                ' . $bodyTagRow . '
            </table><br />';

        $this->moduleTemplate->addFlashMessage(
            TemplaVoilaUtility::getLanguageService()->getLL('msgHeaderSet'),
            '',
            \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
        );

        $headerParts .= $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_to_headerParts_buttons') . $htmlAfterDSTable;

        // Return result:
        return $headerParts;
    }

    /**
     * Creates the template mapper table + form for either direct file mapping or Template Object
     *
     * @param string $displayFile The abs file name to read
     * @param string $path The HTML-path to follow. Eg. 'td#content table[1] tr[1] / INNER | img[0]' or so. Normally comes from clicking a tag-image in the display frame.
     * @param array $dataStruct The data Structure to map to
     * @param array $currentMappingInfo The current mapping information
     * @param string $htmlAfterDSTable HTML content to show after the Data Structure table.
     *
     * @return string HTML table.
     */
    public function renderTemplateMapper($displayFile, $path, $dataStruct = array(), $currentMappingInfo = array(), $htmlAfterDSTable = '')
    {
        // Get file content
        $this->markupFile = $displayFile;
        $fileContent = GeneralUtility::getUrl($this->markupFile);

        // Init mark up object.
        $this->markupObj = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Domain\Model\HtmlMarkup::class);

        // Load splitted content from currentMappingInfo array (used to show us which elements maps to some real content).
        $contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($fileContent, $currentMappingInfo);

        // Show path:
        $pathRendered = GeneralUtility::trimExplode('|', $path, 1);
        $acc = [];
        foreach ($pathRendered as $k => $v) {
            $acc[] = $v;
            $pathRendered[$k] = $this->linkForDisplayOfPath($v, implode('|', $acc));
        }
        array_unshift($pathRendered, $this->linkForDisplayOfPath('[ROOT]', ''));

        // Get attributes of the extracted content:
        $contentFromPath = $this->markupObj->splitByPath($fileContent, $path); // ,'td#content table[1] tr[1]','td#content table[1]','map#cdf / INNER','td#content table[2] tr[1] td[1] table[1] tr[4] td.bckgd1[2] table[1] tr[1] td[1] table[1] tr[1] td.bold1px[1] img[1] / RANGE:img[2]'
        $firstTag = $this->markupObj->htmlParse->getFirstTag($contentFromPath[1]);
        list($attrDat) = $this->markupObj->htmlParse->get_tag_attributes($firstTag, 1);

        // Make options:
        $pathLevels = $this->markupObj->splitPath($path);
        $lastEl = end($pathLevels);

        $optDat = [];
        $optDat[$lastEl['path']] = 'OUTER (Include tag)';
        $optDat[$lastEl['path'] . '/INNER'] = 'INNER (Exclude tag)';

        // Tags, which will trigger "INNER" to be listed on top (because it is almost always INNER-mapping that is needed)
        if (GeneralUtility::inList('body,span,h1,h2,h3,h4,h5,h6,div,td,p,b,i,u,a', $lastEl['el'])) {
            $optDat = array_reverse($optDat);
        }

        list($parentElement, $sameLevelElements) = $this->getRangeParameters($lastEl, $this->markupObj->elParentLevel);
        if (is_array($sameLevelElements)) {
            $startFound = 0;
            foreach ($sameLevelElements as $rEl) {
                if ($startFound) {
                    $optDat[$lastEl['path'] . '/RANGE:' . $rEl] = 'RANGE to "' . $rEl . '"';
                }

                // If the element has an ID the path doesn't include parent nodes
                // If it has an ID and a CSS Class - we need to throw that CSS Class(es) away - otherwise they won't match
                $curPath = stristr($rEl, '#') ? preg_replace('/^(\w+)\.?.*#(.*)$/i', '\1#\2', $rEl) : trim($parentElement . ' ' . $rEl);
                if ($curPath == $lastEl['path']) {
                    $startFound = 1;
                }
            }
        }

        // Add options for attributes:
        if (is_array($attrDat)) {
            foreach ($attrDat as $attrK => $v) {
                $optDat[$lastEl['path'] . '/ATTR:' . $attrK] = 'ATTRIBUTE "' . $attrK . '" (= ' . GeneralUtility::fixed_lgd_cs($v, 15) . ')';
            }
        }

        // Create Data Structure table:
        $content = '<!-- Data Structure table: -->
            <table class="table table-striped table-hover">
            <thead>
                <th>' . TemplaVoilaUtility::getLanguageService()->getLL('mapDataElement') . ':'
                . $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_head_dataElement')
                . '</th>'
                . ($this->editDataStruct ? '<th>' . TemplaVoilaUtility::getLanguageService()->getLL('mapField') . ':'
                    . $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_head_Field')
                    . '</th>' : '')
                . '<th>' . (!$this->_preview ? TemplaVoilaUtility::getLanguageService()->getLL('mapInstructions') : TemplaVoilaUtility::getLanguageService()->getLL('mapSampleData')) . ''
                . $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_head_' . (!$this->_preview ? 'mapping_instructions' : 'sample_data'))
                . '</th><th>' . TemplaVoilaUtility::getLanguageService()->getLL('mapHTMLpath') . ':'
                . $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_head_HTMLpath')
                .'</th><th>' . TemplaVoilaUtility::getLanguageService()->getLL('mapAction') . ':'
                . $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_head_Action')
                . '</th><th>' . TemplaVoilaUtility::getLanguageService()->getLL('mapRules') . ':'
                . $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_head_Rules')
                . '</th>'
                . ($this->editDataStruct ? '<th>' . TemplaVoilaUtility::getLanguageService()->getLL('mapEdit') . ':'
                    . $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_head_Edit')
                    . '</th>' : '')
                . '</thead>'
                . implode('', $this->drawDataStructureMap($dataStruct, 1, $currentMappingInfo, $pathLevels, $optDat, $contentSplittedByMapping))
            . '</table>' . $htmlAfterDSTable
            . $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_basics');

        // Make mapping window:
        $limitTags = implode(',', array_keys($this->explodeMappingToTagsStr($this->mappingToTags, 1)));
        if (($this->mapElPath && !$this->doMappingOfPath) || $this->showPathOnly || $this->_preview) {
            $content .=
                '<!-- Visual Mapping Window (Iframe) -->
                <h3>' . TemplaVoilaUtility::getLanguageService()->getLL('mapMappingWindow') . ':</h3>
                <!-- <p><strong>File:</strong> ' . htmlspecialchars($displayFile) . '</p> -->
                <p>'
                . BackendUtility::getFuncMenu('', 'SET[displayMode]', $this->MOD_SETTINGS['displayMode'], $this->MOD_MENU['displayMode'], 'index.php', GeneralUtility::implodeArrayForUrl('', $_GET, '', 1, 1))
                . $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_window_modes')
                . '</p>';

            if ($this->_preview) {
                $content .=
                    '<!-- Preview information table -->
                    <table border="0" cellpadding="4" cellspacing="2" id="c-mapInfo">
                        <tr class="bgColor5"><td><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('mapPreviewInfo') . ':</strong>' .
                    $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_window_help') .
                    '</td></tr></table>';

                // Add the Iframe:
                $content .= $this->makeIframeForVisual($displayFile, '', '', 0, 1);
            } else {
                $tRows = [];
                if ($this->showPathOnly) {
                    $tRows[] = '
                        <tr class="bgColor4">
                            <td class="bgColor5"><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('mapHTMLpath') . ':</strong></td>
                            <td>' . htmlspecialchars(str_replace('~~~', ' ', $this->displayPath)) . '</td>
                        </tr>
                    ';
                } else {
                    $tRows[] = '
                        <tr class="bgColor4">
                            <td class="bgColor5"><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('mapDSelement') . ':</strong></td>
                            <td>' . $this->elNames[$this->mapElPath]['title'] . '</td>
                        </tr>
                        <tr class="bgColor4">
                            <td class="bgColor5"><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('mapLimitToTags') . ':</strong></td>
                            <td>' . htmlspecialchars(($limitTags ? strtoupper($limitTags) : '(ALL TAGS)')) . '</td>
                        </tr>
                        <tr class="bgColor4">
                            <td class="bgColor5"><strong>' . TemplaVoilaUtility::getLanguageService()->getLL('mapInstructions') . ':</strong></td>
                            <td>' . htmlspecialchars($this->elNames[$this->mapElPath]['description']) . '</td>
                        </tr>
                    ';
                }
                $content .=
                    '<!-- Mapping information table -->
                    <table border="0" cellpadding="2" cellspacing="2" id="c-mapInfo">
                        ' . implode('', $tRows) . '
                    </table>';

                // Add the Iframe:
                $content .= $this->makeIframeForVisual($displayFile, $this->displayPath, $limitTags, $this->doMappingOfPath);
            }
        }

        return $content;
    }

    /**
     * Determines parentElement and sameLevelElements for the RANGE mapping mode
     *
     * @todo this functions return value pretty dirty, but due to the fact that this is something which
     * should at least be encapsulated the bad coding habit it preferred just for readability of the remaining code
     *
     * @param array Array containing information about the current element
     * @param array Array containing information about all mapable elements
     *
     * @return array Array containing 0 => parentElement (string) and 1 => sameLevelElements (array)
     */
    protected function getRangeParameters($lastEl, $elParentLevel)
    {
        /**
         * Add options for "samelevel" elements -
         * If element has an id the "parent" is empty, therefore we need two steps to get the elements (see #11842)
         */
        $sameLevelElements = [];
        if (strlen($lastEl['parent'])) {
            // we have a "named" parent
            $parentElement = $lastEl['parent'];
            $sameLevelElements = $elParentLevel[$parentElement];
        } elseif (count($elParentLevel) == 1) {
            // we have no real parent - happens if parent element is mapped with INNER
            $parentElement = $lastEl['parent'];
            $sameLevelElements = $elParentLevel[$parentElement];
        } else {
            //there's no parent - maybe because it was wrapped with INNER therefore we try to find it ourselfs
            $parentElement = '';
            $hasId = stristr($lastEl['path'], '#');
            foreach ($elParentLevel as $pKey => $pValue) {
                if (in_array($lastEl['path'], $pValue)) {
                    $parentElement = $pKey;
                    break;
                } elseif ($hasId) {
                    foreach ($pValue as $pElement) {
                        if (stristr($pElement, '#') && preg_replace('/^(\w+)\.?.*#(.*)$/i', '\1#\2', $pElement) == $lastEl['path']) {
                            $parentElement = $pKey;
                            break;
                        }
                    }
                }
            }

            if (!$hasId && preg_match('/\[\d+\]$/', $lastEl['path'])) {
                // we have a nameless element, therefore the index is used
                $pos = preg_replace('/^.*\[(\d+)\]$/', '\1', $lastEl['path']);
                // index is "corrected" by one to include the current element in the selection
                $sameLevelElements = array_slice($elParentLevel[$parentElement], $pos - 1);
            } else {
                // we have to search ourselfs because there was no parent and no numerical index to find the right elements
                $foundCurrent = false;
                if (is_array($elParentLevel[$parentElement])) {
                    foreach ($elParentLevel[$parentElement] as $element) {
                        $curPath = stristr($element, '#') ? preg_replace('/^(\w+)\.?.*#(.*)$/i', '\1#\2', $element) : $element;
                        if ($curPath == $lastEl['path']) {
                            $foundCurrent = true;
                        }
                        if ($foundCurrent) {
                            $sameLevelElements[] = $curPath;
                        }
                    }
                }
            }
        }

        return array($parentElement, $sameLevelElements);
    }

    /**
     * Renders the hierarchical display for a Data Structure.
     * Calls itself recursively
     *
     * @param array $dataStruct Part of Data Structure (array of elements)
     * @param integer $mappingMode If true, the Data Structure table will show links for mapping actions. Otherwise it will just layout the Data Structure visually.
     * @param array $currentMappingInfo Part of Current mapping information corresponding to the $dataStruct array - used to evaluate the status of mapping for a certain point in the structure.
     * @param array $pathLevels Array of HTML paths
     * @param array $optDat Options for mapping mode control (INNER, OUTER etc...)
     * @param array $contentSplittedByMapping Content from template file splitted by current mapping info - needed to evaluate whether mapping information for a certain level actually worked on live content!
     * @param integer $level Recursion level, counting up
     * @param array $tRows Accumulates the table rows containing the structure. This is the array returned from the function.
     * @param string $formPrefix Form field prefix. For each recursion of this function, two [] parts are added to this prefix
     * @param string $path HTML path. For each recursion a section (divided by "|") is added.
     * @param integer $mapOK
     *
     * @internal param boolean $mapOk If true, the "Map" link can be shown, otherwise not. Used internally in the recursions.
     *
     * @return array Table rows as an array of <tr> tags, $tRows
     */
    public function drawDataStructureMap($dataStruct, $mappingMode = 0, $currentMappingInfo = array(), $pathLevels = array(), $optDat = array(), $contentSplittedByMapping = array(), $level = 0, $tRows = array(), $formPrefix = '', $path = '', $mapOK = 1)
    {
        $bInfo = GeneralUtility::clientInfo();
        $multilineTooltips = ($bInfo['BROWSER'] == 'msie');

        // Data Structure array must be ... and array of course...
        if (is_array($dataStruct)) {
            foreach ($dataStruct as $key => $value) {
                if ($key == 'meta') {
                    // Do not show <meta> information in mapping interface!
                    continue;
                }

                if (is_array($value)) { // The value of each entry must be an array.

                    // ********************
                    // Making the row:
                    // ********************
                    $rowCells = [];

                    // Icon:
                    $info = $this->dsTypeInfo($value);
                    $icon = '<span class="dsType_Icon dsType_' . $info['id'] . '" title="' . $info['title'] . '">' . strtoupper($info['id']) . '</span>';

                    if ($key === 'ROOT') {
                        $fieldTitle = (isset($dataStruct['meta']['title']) ? $dataStruct['meta']['title'] : $value['title']);
                    } else {
                        $fieldTitle = (!empty($value['TCEforms']['label']) ? $value['TCEforms']['label'] : $value['title']);
                    }

                    // Composing title-cell:
                    if (preg_match('/^LLL:/', $fieldTitle)) {
                        $translatedTitle = TemplaVoilaUtility::getLanguageService()->sL($fieldTitle);
                        $translateIcon = '<sup title="' . TemplaVoilaUtility::getLanguageService()->getLL('displayDSTitleTranslated') . '">*</sup>';
                    } else {
                        $translatedTitle = $fieldTitle;
                        $translateIcon = '';
                    }
                    $this->elNames[$formPrefix . '[' . $key . ']']['title'] = $icon . htmlspecialchars(GeneralUtility::fixed_lgd_cs($translatedTitle, 30)) . $translateIcon;
                    $rowCells['title'] = $this->elNames[$formPrefix . '[' . $key . ']']['title'];

                    // Description:
                    $this->elNames[$formPrefix . '[' . $key . ']']['description'] = $rowCells['description'] = htmlspecialchars($value['tx_templavoilaplus']['description']);

                    // In "mapping mode", render HTML page and Command links:
                    if ($mappingMode) {
                        // HTML-path + CMD links:
                        $isMapOK = 0;
                        if ($currentMappingInfo[$key]['MAP_EL']) { // If mapping information exists...:

                            $mappingElement = str_replace('~~~', ' ', $currentMappingInfo[$key]['MAP_EL']);
                            if (isset($contentSplittedByMapping['cArray'][$key])) { // If mapping of this information also succeeded...:
                                $cF = implode(chr(10), GeneralUtility::trimExplode(chr(10), $contentSplittedByMapping['cArray'][$key], 1));

                                if (strlen($cF) > 200) {
                                    $cF = GeneralUtility::fixed_lgd_cs($cF, 90) . ' ' . GeneralUtility::fixed_lgd_cs($cF, -90);
                                }

                                // Render HTML path:
                                list($pI) = $this->markupObj->splitPath($currentMappingInfo[$key]['MAP_EL']);

                                $okTitle = htmlspecialchars($cF ? sprintf(TemplaVoilaUtility::getLanguageService()->getLL('displayDSContentFound'), strlen($contentSplittedByMapping['cArray'][$key])) . ($multilineTooltips ? ':' . chr(10) . chr(10) . $cF : '') : TemplaVoilaUtility::getLanguageService()->getLL('displayDSContentEmpty'));

                                $rowCells['htmlPath'] = $this->iconFactory->getIcon('status-dialog-ok', Icon::SIZE_SMALL)->render()
                                    . \Ppi\TemplaVoilaPlus\Domain\Model\HtmlMarkup::getGnyfMarkup($pI['el'], '---' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($mappingElement, -80)))
                                    . ' '
                                    . ($pI['modifier'] ? $pI['modifier'] . ($pI['modifier_value'] ? ':' . ($pI['modifier'] != 'RANGE' ? $pI['modifier_value'] : '...') : '') : '');
                                $rowCells['htmlPath'] = '<a href="' . $this->linkThisScript(array(
                                        'htmlPath' => $path . ($path ? '|' : '') . preg_replace('/\/[^ ]*$/', '', $currentMappingInfo[$key]['MAP_EL']),
                                        'showPathOnly' => 1,
                                        'DS_element' => GeneralUtility::_GP('DS_element')
                                    )) . '">' . $rowCells['htmlPath'] . '</a>';

                                // CMD links, default content:
                                $rowCells['cmdLinks'] = '<span class="nobr"><input type="submit" value="Re-Map" name="_" onclick="document.location=\'' .
                                    $this->linkThisScript(array(
                                        'mapElPath' => $formPrefix . '[' . $key . ']',
                                        'htmlPath' => $path,
                                        'mappingToTags' => $value['tx_templavoilaplus']['tags'],
                                        'DS_element' => GeneralUtility::_GP('DS_element')
                                    )) . '\';return false;" title="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonRemapTitle') . '" />' .
                                    '<input type="submit" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonChangeMode') . '" name="_" onclick="document.location=\'' .
                                    $this->linkThisScript(array(
                                        'mapElPath' => $formPrefix . '[' . $key . ']',
                                        'htmlPath' => $path . ($path ? '|' : '') . $pI['path'],
                                        'doMappingOfPath' => 1,
                                        'DS_element' => GeneralUtility::_GP('DS_element')
                                    )) . '\';return false;" title="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonChangeMode') . '" /></span>';

                                // If content mapped ok, set flag:
                                $isMapOK = 1;
                            } else { // Issue warning if mapping was lost:
                                $rowCells['htmlPath'] = $this->iconFactory->getIcon('status-dialog-warning', Icon::SIZE_SMALL)->render()
                                    . htmlspecialchars($mappingElement);
                            }
                        } else { // For non-mapped cases, just output a no-break-space:
                            $rowCells['htmlPath'] = '&nbsp;';
                        }

                        // CMD links; Content when current element is under mapping, then display control panel or message:
                        if ($this->mapElPath == $formPrefix . '[' . $key . ']') {
                            if ($this->doMappingOfPath) {
                                // Creating option tags:
                                $lastLevel = end($pathLevels);
                                $tagsMapping = $this->explodeMappingToTagsStr($value['tx_templavoilaplus']['tags']);
                                $mapDat = is_array($tagsMapping[$lastLevel['el']]) ? $tagsMapping[$lastLevel['el']] : $tagsMapping['*'];
                                unset($mapDat['']);
                                if (is_array($mapDat) && !count($mapDat)) {
                                    unset($mapDat);
                                }

                                // Create mapping options:
                                $opt = [];
                                foreach ($optDat as $k => $v) {
                                    list($pI) = $this->markupObj->splitPath($k);

                                    if (($value['type'] == 'attr' && $pI['modifier'] == 'ATTR') || ($value['type'] != 'attr' && $pI['modifier'] != 'ATTR')) {
                                        if ((!$this->markupObj->tags[$lastLevel['el']]['single'] || $pI['modifier'] != 'INNER') &&
                                            (!is_array($mapDat) || ($pI['modifier'] != 'ATTR' && isset($mapDat[strtolower($pI['modifier'] ? $pI['modifier'] : 'outer')])) || ($pI['modifier'] == 'ATTR' && (isset($mapDat['attr']['*']) || isset($mapDat['attr'][$pI['modifier_value']]))))

                                        ) {
                                            if ($k == $currentMappingInfo[$key]['MAP_EL']) {
                                                $sel = ' selected="selected"';
                                            } else {
                                                $sel = '';
                                            }
                                            $opt[] = '<option value="' . htmlspecialchars($k) . '"' . $sel . '>' . htmlspecialchars($v) . '</option>';
                                        }
                                    }
                                }

                                // Finally, put together the selector box:
                                $rowCells['cmdLinks'] = \Ppi\TemplaVoilaPlus\Domain\Model\HtmlMarkup::getGnyfMarkup($pI['el'], '---' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($lastLevel['path'], -80))) .
                                    '<br /><select name="dataMappingForm' . $formPrefix . '[' . $key . '][MAP_EL]">
                                        ' . implode('
                                        ', $opt) . '
                                        <option value=""></option>
                                    </select>
                                    <br />
                                    <input type="submit" name="_save_data_mapping" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonSet') . '" />
                                    <input type="submit" name="_" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonCancel') . '" />';
                                $rowCells['cmdLinks'] .=
                                    $this->cshItem('xMOD_tx_templavoilaplus', 'mapping_modeset');
                            } else {
                                $rowCells['cmdLinks'] = $this->iconFactory->getIcon('status-dialog-notification', Icon::SIZE_SMALL)->render()
                                    . '<strong>' . TemplaVoilaUtility::getLanguageService()->getLL('msgHowToMap') . '</strong>';
                                $rowCells['cmdLinks'] .= '<br />
                                        <input type="submit" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonCancel') . '" name="_" onclick="document.location=\'' .
                                    $this->linkThisScript(array(
                                        'DS_element' => GeneralUtility::_GP('DS_element')
                                    )) . '\';return false;" />';
                            }
                        } elseif (!$rowCells['cmdLinks'] && $mapOK && $value['type'] != 'no_map') {
                            $rowCells['cmdLinks'] = '<input type="submit" value="' . TemplaVoilaUtility::getLanguageService()->getLL('buttonMap') . '" name="_" onclick="document.location=\'' .
                                $this->linkThisScript(array(
                                    'mapElPath' => $formPrefix . '[' . $key . ']',
                                    'htmlPath' => $path,
                                    'mappingToTags' => $value['tx_templavoilaplus']['tags'],
                                    'DS_element' => GeneralUtility::_GP('DS_element')
                                )) . '\';return false;" />';
                        }
                    }

                    // Display mapping rules:
                    $rowCells['tagRules'] = implode('<br />', GeneralUtility::trimExplode(',', strtolower($value['tx_templavoilaplus']['tags']), 1));
                    if (!$rowCells['tagRules']) {
                        $rowCells['tagRules'] = $GLOBALS['LANG']->getLL('all');
                    }

                    // Display edit/delete icons:
                    if ($this->editDataStruct) {
                        $editAddCol = '<a href="' . $this->linkThisScript(array(
                                'DS_element' => $formPrefix . '[' . $key . ']'
                            )) . '">'
                            . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render()
                            . '</a>
                            <a href="' . $this->linkThisScript(array(
                                'DS_element_DELETE' => $formPrefix . '[' . $key . ']'
                            )) . '"
                                            onClick="return confirm(' . GeneralUtility::quoteJSvalue(TemplaVoilaUtility::getLanguageService()->getLL('confirmDeleteEntry'))
                            . ');">'
                            . $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render()
                            . '</a>';
                        $editAddCol = '<td nowrap="nowrap">' . $editAddCol . '</td>';
                    } else {
                        $editAddCol = '';
                    }

                    // Description:
                    if ($this->_preview) {
                        if (!is_array($value['tx_templavoilaplus']['sample_data'])) {
                            $rowCells['description'] = '[' . TemplaVoilaUtility::getLanguageService()->getLL('noSampleData') . ']';
                        } else {
                            $rowCells['description'] = \TYPO3\CMS\Core\Utility\DebugUtility::viewArray($value['tx_templavoilaplus']['sample_data']);
                        }
                    }

                    // Getting editing row, if applicable:
                    list($addEditRows, $placeBefore) = $this->dsEdit->drawDataStructureMap_editItem($formPrefix, $key, $value, $level, $rowCells);

                    // Add edit-row if found and destined to be set BEFORE:
                    if ($addEditRows && $placeBefore) {
                        $tRows[] = $addEditRows;
                    } else // Put row together
                    {
                        if (!$this->mapElPath || $this->mapElPath == $formPrefix . '[' . $key . ']') {
                            $tRows[] = '

                            <tr>
                            <td nowrap="nowrap" valign="top" style="padding-left: ' . (($level) * 16) . 'px">' . $rowCells['title'] . '</td>
                            ' . ($this->editDataStruct ? '<td nowrap="nowrap">' . $key . '</td>' : '') . '
                            <td>' . $rowCells['description'] . '</td>
                            ' . ($mappingMode
                                    ?
                                    '<td nowrap="nowrap">' . $rowCells['htmlPath'] . '</td>
                                <td>' . $rowCells['cmdLinks'] . '</td>'
                                    :
                                    ''
                                ) . '
                            <td>' . $rowCells['tagRules'] . '</td>
                            ' . $editAddCol . '
                        </tr>';
                        }
                    }

                    // Recursive call:
                    if (($value['type'] == 'array') ||
                        ($value['type'] == 'section')
                    ) {
                        $tRows = $this->drawDataStructureMap(
                            $value['el'],
                            $mappingMode,
                            $currentMappingInfo[$key]['el'],
                            $pathLevels,
                            $optDat,
                            $contentSplittedByMapping['sub'][$key],
                            $level + 1,
                            $tRows,
                            $formPrefix . '[' . $key . '][el]',
                            $path . ($path ? '|' : '') . $currentMappingInfo[$key]['MAP_EL'],
                            $isMapOK
                        );
                    }
                    // Add edit-row if found and destined to be set AFTER:
                    if ($addEditRows && !$placeBefore) {
                        $tRows[] = $addEditRows;
                    }
                }
            }
        }

        return $tRows;
    }

    /*******************************
     *
     * Various helper functions
     *
     *******************************/

    /**
     * Returns Data Structure from the $dataStructureXML
     *
     * @param string $dataStructureXML XML content which is parsed into an array, which is returned.
     *
     * @return array The parsed XML as struct array.
     * @throws \Exception If a XML parse error happened the parse error will be thrown.
     */
    public function getDataStructFromDSO($dataStructureXML)
    {
        if ($dataStructureXML !== '') {
            $dataStruct = GeneralUtility::xml2array($dataStructureXML);
            if (!is_array($dataStruct)) {
                throw new \Exception($dataStruct);
            }
        } else {
            $dataStruct = [];
        }

        return $dataStruct;
    }

    /**
     * Creating a link to the display frame for display of the "HTML-path" given as $path
     *
     * @param string $title The text to link
     * @param string $path The path string ("HTML-path")
     *
     * @return string HTML link, pointing to the display frame.
     */
    public function linkForDisplayOfPath($title, $path)
    {
        $theArray = [
            'file' => $this->markupFile,
            'path' => $path,
        ];

        $content .= '<strong><a href="'
            . BackendUtility::getModuleUrl('templavoilaplus_template_disply', $theArray)
            . '" target="display">' . $title . '</a></strong>';

        return $content;
    }

    /**
     * Creates a link to this script, maintaining the values of the displayFile, displayTable, displayUid variables.
     * Primarily used by ->drawDataStructureMap
     *
     * @param array $array Overriding parameters.
     *
     * @return string URL, already htmlspecialchars()'ed
     * @see drawDataStructureMap()
     */
    public function linkThisScript($array = array())
    {
        $theArray = [
            'id' => $this->id, // id of the current sysfolder
            'file' => $this->displayFile,
            'table' => $this->displayTable,
            'uid' => $this->displayUid,
            'returnUrl' => $this->returnUrl,
            '_load_ds_xml_to' => $this->_load_ds_xml_to
        ];

        return BackendUtility::getModuleUrl('templavoilaplus_mapping', array_merge($theArray, $array));
    }

    public function redirectToModifyDSTO($toUid, $dsUid)
    {
        $params = [
            'file' => $this->displayFile,
            '_load_ds_xml' => 1,
            '_load_ds_xml_to' => $toUid,
            'uid' => $dsUid,
            'returnUrl' => BackendUtility::getModuleUrl('web_txtemplavoilaplusCenter', ['id' => (int)$this->_saveDSandTO_pid])
        ];

        header(
            'Location:' . GeneralUtility::locationHeaderUrl(
                $this->getUrlToModifyDSTO(
                    $this->displayFile,
                    $toUid,
                    $dsUid,
                    BackendUtility::getModuleUrl('web_txtemplavoilaplusCenter', ['id' => (int)$this->_saveDSandTO_pid])
                )
            )
        );
    }

    public function getUrlToModifyDSTO($file, $toUid, $dsUid, $returnUrl)
    {
        $params = [
            'file' => $file,
            '_load_ds_xml' => 1,
            '_load_ds_xml_to' => $toUid,
            'uid' => $dsUid,
            'id' => $this->id,
            'returnUrl' => $returnUrl,
        ];

        return BackendUtility::getModuleUrl('templavoilaplus_mapping', $params);
    }

    /**
     * Creates the HTML code for the IFRAME in which the display mode is shown:
     *
     * @param string $file File name to display in exploded mode.
     * @param string $path HTML-page
     * @param string $limitTags Tags which is the only ones to show
     * @param boolean $showOnly If set, the template is only shown, mapping links disabled.
     * @param integer $preview Preview enabled.
     *
     * @return string HTML code for the IFRAME.
     * @see main_display()
     */
    public function makeIframeForVisual($file, $path, $limitTags, $showOnly, $preview = 0)
    {
        $url = BackendUtility::getModuleUrl(
            'templavoilaplus_template_disply',
            [
                'file' => $file,
                'path' => $path,
                'preview' => ($preview ? 1 : 0),
                'show' => ($show ? 1 : 0),
                'limitTags' => $limitTags,
                'mode' => $this->MOD_SETTINGS['displayMode'],
            ]
        );

        return '<iframe id="templavoila-frame-visual" src="' . htmlspecialchars($url) . '#_MARKED_UP_ELEMENT"></iframe>';
    }

    /**
     * Converts a list of mapping rules to an array
     *
     * @param string $mappingToTags Mapping rules in a list
     * @param integer $unsetAll If set, then the ALL rule (key "*") will be unset.
     *
     * @return array Mapping rules in a multidimensional array.
     */
    public function explodeMappingToTagsStr($mappingToTags, $unsetAll = 0)
    {
        $elements = GeneralUtility::trimExplode(',', strtolower($mappingToTags));
        $output = [];
        foreach ($elements as $v) {
            $subparts = GeneralUtility::trimExplode(':', $v);
            $output[$subparts[0]][$subparts[1]][($subparts[2] ? $subparts[2] : '*')] = 1;
        }
        if ($unsetAll) {
            unset($output['*']);
        }

        return $output;
    }

    /**
     * General purpose unsetting of elements in a multidimensional array
     *
     * @param array &$dataStruct Array from which to remove elements (passed by reference!)
     * @param array $ref An array where the values in the specified order points to the position in the array to unset.
     *
     * @return void
     */
    public function unsetArrayPath(&$dataStruct, $ref)
    {
        $key = array_shift($ref);

        if (!count($ref)) {
            unset($dataStruct[$key]);
        } elseif (is_array($dataStruct[$key])) {
            $this->unsetArrayPath($dataStruct[$key], $ref);
        }
    }

    /**
     * Function to clean up "old" stuff in the currentMappingInfo array. Basically it will remove EVERYTHING which is not known according to the input Data Structure
     *
     * @param array &$currentMappingInfo Current Mapping info (passed by reference)
     * @param array $dataStruct Data Structure
     *
     * @return void
     */
    public function cleanUpMappingInfoAccordingToDS(&$currentMappingInfo, $dataStruct)
    {
        if (is_array($currentMappingInfo)) {
            foreach ($currentMappingInfo as $key => $value) {
                if (!isset($dataStruct[$key])) {
                    unset($currentMappingInfo[$key]);
                } else {
                    if (is_array($dataStruct[$key]['el'])) {
                        $this->cleanUpMappingInfoAccordingToDS($currentMappingInfo[$key]['el'], $dataStruct[$key]['el']);
                    }
                }
            }
        }
    }

    /**
     * Generates $this->storageFolders with available sysFolders linked to as storageFolders for the user
     *
     * @return void Modification in $this->storageFolders array
     */
    public function findingStorageFolderIds()
    {
        // Init:
        $readPerms = TemplaVoilaUtility::getBackendUser()->getPagePermsClause(1);
        $this->storageFolders = [];

        // Looking up all references to a storage folder:
        $res = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTquery(
            'uid,storage_pid',
            'pages',
            'storage_pid>0' . BackendUtility::deleteClause('pages')
        );
        while (false !== ($row = TemplaVoilaUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
            if (TemplaVoilaUtility::getBackendUser()->isInWebMount($row['storage_pid'], $readPerms)) {
                $storageFolder = BackendUtility::getRecord('pages', $row['storage_pid'], 'uid,title');
                if ($storageFolder['uid']) {
                    $this->storageFolders[$storageFolder['uid']] = $storageFolder['title'];
                }
            }
        }

        // Looking up all root-pages and check if there's a tx_templavoilaplus.storagePid setting present
        $res = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTquery(
            'pid,root',
            'sys_template',
            'root=1' . BackendUtility::deleteClause('sys_template')
        );
        while (false !== ($row = TemplaVoilaUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
            $tsCconfig = BackendUtility::getModTSconfig($row['pid'], 'tx_templavoilaplus');
            if (isset($tsCconfig['properties']['storagePid']) &&
                TemplaVoilaUtility::getBackendUser()->isInWebMount($tsCconfig['properties']['storagePid'], $readPerms)
            ) {
                $storageFolder = BackendUtility::getRecord('pages', $tsCconfig['properties']['storagePid'], 'uid,title');
                if ($storageFolder['uid']) {
                    $this->storageFolders[$storageFolder['uid']] = $storageFolder['title'];
                }
            }
        }

        // Compopsing select list:
        $sysFolderPIDs = array_keys($this->storageFolders);
        $sysFolderPIDs[] = 0;
        $this->storageFolders_pidList = implode(',', $sysFolderPIDs);
    }

    /**
     * Wrapper function for context sensitive help - for downwards compatibility with TYPO3 prior 3.7.x
     *
     * @param string $table Table name ('_MOD_'+module name)
     * @param string $field Field name (CSH locallang main key)
     * @param string $wrap Wrap code for icon-mode, splitted by "|". Not used for full-text mode.
     *
     * @return string HTML content for help text
     */
    public function cshItem($table, $field, $wrap = '')
    {
        if (is_callable(array('\TYPO3\CMS\Backend\Utility\BackendUtility', 'cshItem'))) {
            return BackendUtility::cshItem($table, $field, '' /*unused*/, $wrap);
        }

        return '';
    }

    /**
     * @param string $formElementName
     *
     * @return string
     */
    public function lipsumLink($formElementName)
    {
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('lorem_ipsum')) {
            $LRobj = GeneralUtility::makeInstance(\tx_loremipsum_wiz::class);

            $PA = array(
                'fieldChangeFunc' => array(),
                'formName' => 'pageform',
                'itemName' => $formElementName . '[]',
                'params' => array(
#                    'type' => 'header',
                    'type' => 'description',
                    'add' => 1,
                    'endSequence' => '46,32',
                )
            );

            return $LRobj->main($PA, 'ID:templavoila');
        }

        return '';
    }

    /**
     * @param array $currentMappingInfo_head
     * @param mixed $html_header
     *
     * @return mixed
     */
    public function buildCachedMappingInfo_head($currentMappingInfo_head, $html_header)
    {
        $h_currentMappingInfo = [];
        if (is_array($currentMappingInfo_head['headElementPaths'])) {
            foreach ($currentMappingInfo_head['headElementPaths'] as $kk => $vv) {
                $h_currentMappingInfo['el_' . $kk]['MAP_EL'] = $vv;
            }
        }

        return $this->markupObj->splitContentToMappingInfo($html_header, $h_currentMappingInfo);
    }

    /**
     * Checks if link points to local marker or not and sets prefix accordingly.
     *
     * @param string $relPathFix Prefix
     * @param string $fileContent Content
     * @param string $uniqueMarker Marker inside links
     *
     * @return string Content
     */
    public function fixPrefixForLinks($relPathFix, $fileContent, $uniqueMarker)
    {
        $parts = explode($uniqueMarker, $fileContent);
        $count = count($parts);
        if ($count > 1) {
            for ($i = 1; $i < $count; $i++) {
                if ($parts[$i]{0} != '#') {
                    $parts[$i] = $relPathFix . $parts[$i];
                }
            }
        }

        return implode($parts);
    }
}
