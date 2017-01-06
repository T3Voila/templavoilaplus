<?php
namespace Extension\Templavoila\Controller;

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
use TYPO3\CMS\Core\Utility\GeneralUtility as CoreGeneralUtility;

use Extension\Templavoila\Utility\GeneralUtility as TemplavoilaGeneralUtility;


$GLOBALS['LANG']->includeLLFile(
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('templavoila') . 'Resources/Private/Language/BackendTemplateMapping.xlf'
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
     * @var string
     */
    protected $backPath;

    /**
     * Set to ->MOD_SETTINGS[]
     *
     * @var string
     */
    public $theDisplayMode = '';

    /**
     * @var array
     */
    public $head_markUpTags = array(
        // Block elements:
        'title' => array(),
        'script' => array(),
        'style' => array(),
        // Single elements:

        'link' => array('single' => 1),
        'meta' => array('single' => 1),
    );

    /**
     * Extension key of this module
     *
     * @var string
     */
    public $extKey = 'templavoila';

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'templavoila_mapping';

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
     * @var \Extension\Templavoila\Domain\Model\HtmlMarkup
     */
    public $markupObj;

    /**
     * @var array
     */
    public $elNames = array();

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
    public $storageFolders = array();

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
     * (GPvar "table") The table from which to display element (Data Structure object [tx_templavoila_datastructure], template object [tx_templavoila_tmplobj])
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
     * instance of class Extension\Templavoila\Module\Cm1\DsEdit
     *
     * @var \Extension\Templavoila\Module\Cm1\DsEdit
     */
    public $dsEdit;

    /**
     * instance of class Extension\Templavoila\Module\Cm1\ETypes
     *
     * @var \Extension\Templavoila\Module\Cm1\ETypes
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

        $this->moduleTemplate = CoreGeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\ModuleTemplate::class);
        $this->iconFactory = $this->moduleTemplate->getIconFactory();
        $this->buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);
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
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, CoreGeneralUtility::_GP('SET'), $this->moduleName);
    }


    /**
     * Returns an abbrevation and a description for a given element-type.
     *
     * @param array $conf
     * @TODO Clean up that array has 2 meanings (container or section!!)
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
        $this->dsEdit = CoreGeneralUtility::getUserObj(\Extension\Templavoila\Module\Cm1\DsEdit::class, '');
        $this->dsEdit->init($this);

        // Initialize eTypes
        $this->eTypes = CoreGeneralUtility::getUserObj(\Extension\Templavoila\Module\Cm1\ETypes::class, '');
        $this->eTypes->init($this);

        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);
        $this->staticDS = ($this->extConf['staticDS.']['enable']);

        // Setting GPvars:
        // It can be, that we get a storeg:file link from clickmenu
        $this->displayFile = \Extension\Templavoila\Domain\Model\File::filename(CoreGeneralUtility::_GP('file'));
        $this->displayTable = CoreGeneralUtility::_GP('table');
        $this->displayUid = CoreGeneralUtility::_GP('uid');

        $this->returnUrl = CoreGeneralUtility::sanitizeLocalUrl(CoreGeneralUtility::_GP('returnUrl'));

        // Access check!
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $pageInfoArr = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $access = is_array($pageInfoArr) ? 1 : 0;

        if ($access) {
                    // Add custom styles
            $this->getPageRenderer()->addCssFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($this->extKey) . 'Resources/Public/StyleSheet/cm1_default.css');
            $this->getPageRenderer()->addCssFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($this->extKey) . 'Resources/Public/StyleSheet/HtmlMarkup.css');

            // Adding classic jumpToUrl function, needed for the function menu.
            // And some more functions
            $this->moduleTemplate->addJavaScriptCode('templavoila_function', '
                script_ended = 0;
                function jumpToUrl(URL)    {    //
                    document.location = URL;
                }
                function updPath(inPath)    {    //
                    document.location = "' . CoreGeneralUtility::linkThisScript(array('htmlPath' => '', 'doMappingOfPath' => 1)) . '&htmlPath="+top.rawurlencode(inPath);
                }

                function openValidator(key) {
                    new Ajax.Request("' . $GLOBALS['BACK_PATH'] . 'ajax.php?ajaxID=Extension\\Templavoila\\Module\\Cm1\\Ajax::getDisplayFileContent&key=" + key, {
                        onSuccess: function(response) {
                            var valform = new Element(\'form\',{method: \'post\', target:\'_blank\', action: \'http://validator.w3.org/check#validate_by_input\'});
                            valform.insert(new Element(\'input\',{name: \'fragment\', value:response.responseText, type: \'hidden\'}));$(document.body).insert(valform);
                            valform.submit();
                        }
                    });
                }
            ');

            $this->main_mode();
        } else {
            $flashMessage = CoreGeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('noaccess'),
                '',
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
            $this->content = $flashMessage->render();
        }

        $title = TemplavoilaGeneralUtility::getLanguageService()->getLL('mappingTitle');
        $header = $this->moduleTemplate->header($title);
        $this->moduleTemplate->setTitle($title);

        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageInfoArr);
        $this->setDocHeaderButtons(!isset($pageInfoArr['uid']));

        $this->moduleTemplate->setForm('<form action="' . $this->linkThisScript([]) . '" method="post" name="pageform">');

        $this->moduleTemplate->setContent($header . $this->content);
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
                'templavoila_mapping',
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
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc'))
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

        return str_replace("<>\n", '', str_replace("</>", '', CoreGeneralUtility::array2xml($array, '', -1, '', 0, array('useCDATA' => 1))));
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
                return array();
            }
        }

        return CoreGeneralUtility::xml2array('<grouped>' . $string . '</grouped>');
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
        $this->displayPath = CoreGeneralUtility::_GP('htmlPath');

        // GPvars specific to the DS listing/table and mapping features:
        $this->_preview = CoreGeneralUtility::_GP('_preview');
        $this->mapElPath = CoreGeneralUtility::_GP('mapElPath');
        $this->doMappingOfPath = CoreGeneralUtility::_GP('doMappingOfPath');
        $this->showPathOnly = CoreGeneralUtility::_GP('showPathOnly');
        $this->mappingToTags = CoreGeneralUtility::_GP('mappingToTags');
        $this->DS_element = CoreGeneralUtility::_GP('DS_element');
        $this->DS_cmd = CoreGeneralUtility::_GP('DS_cmd');
        $this->fieldName = CoreGeneralUtility::_GP('fieldName');

        // GPvars specific for DS creation from a file.
        $this->_load_ds_xml_content = CoreGeneralUtility::_GP('_load_ds_xml_content');
        $this->_load_ds_xml_to = CoreGeneralUtility::_GP('_load_ds_xml_to');
        $this->_saveDSandTO_TOuid = CoreGeneralUtility::_GP('_saveDSandTO_TOuid');
        $this->_saveDSandTO_title = CoreGeneralUtility::_GP('_saveDSandTO_title');
        $this->_saveDSandTO_type = CoreGeneralUtility::_GP('_saveDSandTO_type');
        $this->_saveDSandTO_pid = CoreGeneralUtility::_GP('_saveDSandTO_pid');
        $this->DS_element_DELETE = CoreGeneralUtility::_GP('DS_element_DELETE');

        // Finding Storage folder:
        $this->findingStorageFolderIds();

        // dsType configuration
        // @TODO Clean up that type array can have 2 meanings!
        $this->dsTypes = [
            'section' => [
                'id' => 'sc',
                'title' => TemplavoilaGeneralUtility::getLanguageService()->getLL('dsTypes_section') . ': ',
            ],
            'array' => [
                'id' => 'sc',
                'title' => TemplavoilaGeneralUtility::getLanguageService()->getLL('dsTypes_section') . ': ',
            ],
            'container' => [
                'id' => 'co',
                'title' => TemplavoilaGeneralUtility::getLanguageService()->getLL('dsTypes_container') . ': ',
            ],
            'attr' => [
                'id' => 'at',
                'title' => TemplavoilaGeneralUtility::getLanguageService()->getLL('dsTypes_attribute') . ': ',
            ],
            'element' => [
                'id' => 'el',
                'title' => TemplavoilaGeneralUtility::getLanguageService()->getLL('dsTypes_element') . ': ',
            ],
            'no_map' => [
                'id' => 'no',
                'title' => TemplavoilaGeneralUtility::getLanguageService()->getLL('dsTypes_notmapped') . ': ',
            ],
        ];

        // Render content, depending on input values:
        if ($this->displayFile) { // Browsing file directly, possibly creating a template/data object records.
            $this->renderFile();
        } elseif ($this->displayTable == 'tx_templavoila_datastructure') { // Data source display
            $this->renderDSO();
        } elseif ($this->displayTable == 'tx_templavoila_tmplobj') { // Data source display
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
        if (@is_file($this->displayFile) && CoreGeneralUtility::getFileAbsFileName($this->displayFile)) {

            // Converting GPvars into a "cmd" value:
            $cmd = '';
            $msg = array();
            if (CoreGeneralUtility::_GP('_load_ds_xml')) { // Loading DS from XML or TO uid
                $cmd = 'load_ds_xml';
            } elseif (CoreGeneralUtility::_GP('_clear')) { // Resetting mapping/DS
                $cmd = 'clear';
            } elseif (CoreGeneralUtility::_GP('_saveDSandTO')) { // Saving DS and TO to records.
                if (!strlen(trim($this->_saveDSandTO_title))) {
                    $cmd = 'saveScreen';
                    $flashMessage = CoreGeneralUtility::makeInstance(
                        \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                        TemplavoilaGeneralUtility::getLanguageService()->getLL('errorNoToTitleDefined'),
                        '',
                        \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                    );
                    $msg[] = $flashMessage->render();
                } else {
                    $cmd = 'saveDSandTO';
                }
            } elseif (CoreGeneralUtility::_GP('_updateDSandTO')) { // Updating DS and TO
                $cmd = 'updateDSandTO';
            } elseif (CoreGeneralUtility::_GP('_showXMLDS')) { // Showing current DS as XML
                $cmd = 'showXMLDS';
            } elseif (CoreGeneralUtility::_GP('_preview')) { // Previewing mappings
                $cmd = 'preview';
            } elseif (CoreGeneralUtility::_GP('_save_data_mapping')) { // Saving mapping to Session
                $cmd = 'save_data_mapping';
            } elseif (CoreGeneralUtility::_GP('_updateDS')) {
                $cmd = 'updateDS';
            } elseif (CoreGeneralUtility::_GP('DS_element_DELETE')) {
                $cmd = 'DS_element_DELETE';
            } elseif (CoreGeneralUtility::_GP('_saveScreen')) {
                $cmd = 'saveScreen';
            } elseif (CoreGeneralUtility::_GP('_loadScreen')) {
                $cmd = 'loadScreen';
            } elseif (CoreGeneralUtility::_GP('_save')) {
                $cmd = 'saveUpdatedDSandTO';
            } elseif (CoreGeneralUtility::_GP('_saveExit')) {
                $cmd = 'saveUpdatedDSandTOandExit';
            }

            // Init settings:
            $this->editDataStruct = 1; // Edit DS...
            $content = '';

            // Checking Storage Folder PID:
            if (!count($this->storageFolders)) {
                $msg[] = $this->iconFactory->getIcon('status-dialog-error', Icon::SIZE_SMALL)->render()
                    . '<strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('error') . '</strong> '
                    . TemplavoilaGeneralUtility::getLanguageService()->getLL('errorNoStorageFolder');
            }

            // Session data
            $this->sessionKey = $this->MCONF['name'] . '_mappingInfo:' . $this->_load_ds_xml_to;
            if ($cmd == 'clear') { // Reset session data:
                $sesDat = array('displayFile' => $this->displayFile, 'TO' => $this->_load_ds_xml_to, 'DS' => $this->displayUid);
                TemplavoilaGeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
            } else { // Get session data:
                $sesDat = TemplavoilaGeneralUtility::getBackendUser()->getSessionData($this->sessionKey);
            }
            if ($this->_load_ds_xml_to) {
                $toREC = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $this->_load_ds_xml_to);
                if ($this->staticDS) {
                    $dsREC['dataprot'] = CoreGeneralUtility::getURL(CoreGeneralUtility::getFileAbsFileName($toREC['datastructure']));
                } else {
                    $dsREC = BackendUtility::getRecordWSOL('tx_templavoila_datastructure', $toREC['datastructure']);
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
                    $ds = CoreGeneralUtility::xml2array($dsREC['dataprot']);
                    $sesDat['dataStruct'] = $sesDat['autoDS'] = $ds; // Just set $ds, not only its ROOT! Otherwise <meta> will be lost.
                    TemplavoilaGeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                } else {
                    $ds = CoreGeneralUtility::xml2array($this->_load_ds_xml_content);
                    $sesDat = array('displayFile' => $this->displayFile, 'TO' => $this->_load_ds_xml_to, 'DS' => $this->displayUid);
                    $sesDat['dataStruct'] = $sesDat['autoDS'] = $ds;
                    TemplavoilaGeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                }
            }

            // Setting Data Structure to value from session data - unless it does not exist in which case a default structure is created.
            $dataStruct = is_array($sesDat['autoDS']) ? $sesDat['autoDS'] : array(
                'meta' => array(
                    'langDisable' => '1',
                ),
                'ROOT' => array(
                    'tx_templavoila' => array(
                        'title' => 'ROOT',
                        'description' => TemplavoilaGeneralUtility::getLanguageService()->getLL('rootDescription'),
                    ),
                    'type' => 'array',
                    'el' => array()
                )
            );

            // Setting Current Mapping information to session variable content OR blank if none exists.
            $currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : array();
            $this->cleanUpMappingInfoAccordingToDS($currentMappingInfo, $dataStruct); // This will clean up the Current Mapping info to match the Data Structure.

            // CMD switch:
            switch ($cmd) {
                // Saving incoming Mapping Data to session data:
                case 'save_data_mapping':
                    $inputData = CoreGeneralUtility::_GP('dataMappingForm', 1);
                    if (is_array($inputData)) {
                        $sesDat['currentMappingInfo'] = $currentMappingInfo = $this->array_merge_recursive_overrule($currentMappingInfo, $inputData);
                        $sesDat['dataStruct'] = $dataStruct;
                        TemplavoilaGeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                    }
                    break;
                // Saving incoming Data Structure settings to session data:
                case 'updateDS':
                    $inDS = CoreGeneralUtility::_GP('autoDS', 1);
                    if (is_array($inDS)) {
                        $sesDat['dataStruct'] = $sesDat['autoDS'] = $dataStruct = $this->array_merge_recursive_overrule($dataStruct, $inDS);
                        TemplavoilaGeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                    }
                    break;
                // If DS element is requested for deletion, remove it and update session data:
                case 'DS_element_DELETE':
                    $ref = explode('][', substr($this->DS_element_DELETE, 1, -1));
                    $this->unsetArrayPath($dataStruct, $ref);
                    $sesDat['dataStruct'] = $sesDat['autoDS'] = $dataStruct;
                    TemplavoilaGeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                    break;
            }

            // Creating $templatemapping array with cached mapping content:
            if (CoreGeneralUtility::inList('showXMLDS,saveDSandTO,updateDSandTO,saveUpdatedDSandTO,saveUpdatedDSandTOandExit', $cmd)) {

                // Template mapping prepared:
                $templatemapping = array();
                $templatemapping['MappingInfo'] = $currentMappingInfo;
                if (isset($sesDat['currentMappingInfo_head'])) {
                    $templatemapping['MappingInfo_head'] = $sesDat['currentMappingInfo_head'];
                }

                // Getting cached data:
                reset($dataStruct);
                $fileContent = CoreGeneralUtility::getUrl($this->displayFile);
                $htmlParse = CoreGeneralUtility::makeInstance(\TYPO3\CMS\Core\Html\HtmlParser::class);
                $relPathFix = dirname(substr($this->displayFile, strlen(PATH_site))) . '/';
                $fileContent = $htmlParse->prefixResourcePath($relPathFix, $fileContent);
                $this->markupObj = CoreGeneralUtility::makeInstance(\Extension\Templavoila\Domain\Model\HtmlMarkup::class);
                $contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($fileContent, $currentMappingInfo);
                $templatemapping['MappingData_cached'] = $contentSplittedByMapping['sub']['ROOT'];

                list($html_header) = $this->markupObj->htmlParse->getAllParts($htmlParse->splitIntoBlock('head', $fileContent), 1, 0);
                $this->markupObj->tags = $this->head_markUpTags; // Set up the markupObject to process only header-section tags:

                if (isset($templatemapping['MappingInfo_head'])) {
                    $h_currentMappingInfo = array();
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
                    if (!isset($dataStruct['meta'])) {
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
                    $tce = CoreGeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                    $tce->stripslashes_values = 0;

                    // DS:

                    // Modifying data structure with conversion of preset values for field types to actual settings:
                    $storeDataStruct = $dataStruct;
                    if (is_array($storeDataStruct['ROOT']['el'])) {
                        $this->eTypes->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'], $contentSplittedByMapping['sub']['ROOT'], $dataArr['tx_templavoila_datastructure']['NEW']['scope']);
                    }
                    $dataProtXML = CoreGeneralUtility::array2xml_cs($storeDataStruct, 'T3DataStructure', array('useCDATA' => 1));

                    if ($this->staticDS) {
                        $title = preg_replace('|[/,\."\']+|', '_', $this->_saveDSandTO_title) . ' (' . ($this->_saveDSandTO_type == 1 ? 'page' : 'fce') . ').xml';
                        $path = CoreGeneralUtility::getFileAbsFileName($this->_saveDSandTO_type == 2 ? $this->extConf['staticDS.']['path_fce'] : $this->extConf['staticDS.']['path_page']) . $title;
                        CoreGeneralUtility::writeFile($path, $dataProtXML);
                        $newID = substr($path, strlen(PATH_site));
                    } else {
                        $dataArr = array();
                        $dataArr['tx_templavoila_datastructure']['NEW']['pid'] = (int)$this->_saveDSandTO_pid;
                        $dataArr['tx_templavoila_datastructure']['NEW']['title'] = $this->_saveDSandTO_title;
                        $dataArr['tx_templavoila_datastructure']['NEW']['scope'] = $this->_saveDSandTO_type;
                        $dataArr['tx_templavoila_datastructure']['NEW']['dataprot'] = $dataProtXML;

                        // start data processing
                        $tce->start($dataArr, array());
                        $tce->process_datamap();
                        $newID = (int)$tce->substNEWwithIDs['NEW'];
                    }

                    // If that succeeded, create the TO as well:
                    if ($newID) {
                        $dataArr = array();
                        $dataArr['tx_templavoila_tmplobj']['NEW']['pid'] = (int)$this->_saveDSandTO_pid;
                        $dataArr['tx_templavoila_tmplobj']['NEW']['title'] = $this->_saveDSandTO_title . ' [Template]';
                        $dataArr['tx_templavoila_tmplobj']['NEW']['datastructure'] = $newID;
                        $dataArr['tx_templavoila_tmplobj']['NEW']['fileref'] = substr($this->displayFile, strlen(PATH_site));
                        $dataArr['tx_templavoila_tmplobj']['NEW']['templatemapping'] = serialize($templatemapping);
                        $dataArr['tx_templavoila_tmplobj']['NEW']['fileref_mtime'] = @filemtime($this->displayFile);
                        $dataArr['tx_templavoila_tmplobj']['NEW']['fileref_md5'] = @md5_file($this->displayFile);

                        // Init TCEmain object and store:
                        $tce->start($dataArr, array());
                        $tce->process_datamap();
                        $newToID = (int)$tce->substNEWwithIDs['NEW'];
                        if ($newToID) {
                            $msg[] = $this->iconFactory->getIcon('status-dialog-ok', Icon::SIZE_SMALL)->render()
                                . sprintf(TemplavoilaGeneralUtility::getLanguageService()->getLL('msgDSTOSaved'),
                                    $dataArr['tx_templavoila_tmplobj']['NEW']['datastructure'],
                                    $tce->substNEWwithIDs['NEW'], $this->_saveDSandTO_pid);
                        } else {
                            $msg[] = $this->iconFactory->getIcon('status-dialog-warning', Icon::SIZE_SMALL)->render()
                                . '<strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('error') . ':</strong> '
                                . sprintf(TemplavoilaGeneralUtility::getLanguageService()->getLL('errorTONotSaved'), $dataArr['tx_templavoila_tmplobj']['NEW']['datastructure']);
                        }
                    } else {
                        $msg[] = $this->iconFactory->getIcon('status-dialog-warning', Icon::SIZE_SMALL)->render()
                            . ' border="0" align="top" class="absmiddle" alt="" />'
                            . '<strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('error') . ':</strong> '
                            . TemplavoilaGeneralUtility::getLanguageService()->getLL('errorTONotCreated');
                    }

                    unset($tce);
                    if ($newID && $newToID) {
                        //redirect to edit view
                        $this->redirectToModifyDSTO($newToID, $newID);
                        exit;
                    } else {
                        // Clear cached header info because saveDSandTO always resets headers
                        $sesDat['currentMappingInfo_head'] = '';
                        TemplavoilaGeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                    }
                    break;
                // Updating DS and TO records:
                case 'updateDSandTO':
                case 'saveUpdatedDSandTO':
                case 'saveUpdatedDSandTOandExit':

                    if ($cmd == 'updateDSandTO') {
                        // Looking up the records by their uids:
                        $toREC = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $this->_saveDSandTO_TOuid);
                    } else {
                        $toREC = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $this->_load_ds_xml_to);
                    }
                    if ($this->staticDS) {
                        $dsREC['uid'] = $toREC['datastructure'];
                    } else {
                        $dsREC = BackendUtility::getRecordWSOL('tx_templavoila_datastructure', $toREC['datastructure']);
                    }

                    // If they are found, continue:
                    if ($toREC['uid'] && $dsREC['uid']) {
                        // Init TCEmain object and store:
                        $tce = CoreGeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                        $tce->stripslashes_values = 0;

                        // Modifying data structure with conversion of preset values for field types to actual settings:
                        $storeDataStruct = $dataStruct;
                        if (is_array($storeDataStruct['ROOT']['el'])) {
                            $this->eTypes->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'], $contentSplittedByMapping['sub']['ROOT'], $dsREC['scope']);
                        }
                        $dataProtXML = CoreGeneralUtility::array2xml_cs($storeDataStruct, 'T3DataStructure', array('useCDATA' => 1));

                        // DS:
                        if ($this->staticDS) {
                            $path = PATH_site . $dsREC['uid'];
                            CoreGeneralUtility::writeFile($path, $dataProtXML);
                        } else {
                            $dataArr = array();
                            $dataArr['tx_templavoila_datastructure'][$dsREC['uid']]['dataprot'] = $dataProtXML;

                            // process data
                            $tce->start($dataArr, array());
                            $tce->process_datamap();
                        }

                        // TO:
                        $TOuid = BackendUtility::wsMapId('tx_templavoila_tmplobj', $toREC['uid']);
                        $dataArr = array();
                        $dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref'] = substr($this->displayFile, strlen(PATH_site));
                        $dataArr['tx_templavoila_tmplobj'][$TOuid]['templatemapping'] = serialize($templatemapping);
                        $dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref_mtime'] = @filemtime($this->displayFile);
                        $dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref_md5'] = @md5_file($this->displayFile);

                        $tce->start($dataArr, array());
                        $tce->process_datamap();

                        unset($tce);

                        $msg[] = $this->iconFactory->getIcon('status-dialog-notification', Icon::SIZE_SMALL)->render()
                            . sprintf(TemplavoilaGeneralUtility::getLanguageService()->getLL('msgDSTOUpdated'), $dsREC['uid'], $toREC['uid']);

                        if ($cmd == 'updateDSandTO') {
                            if (!$this->_load_ds_xml_to) {
                                //new created was saved to existing DS/TO, redirect to edit view
                                $this->redirectToModifyDSTO($toREC['uid'], $dsREC['uid']);
                                exit;
                            } else {
                                // Clear cached header info because updateDSandTO always resets headers
                                $sesDat['currentMappingInfo_head'] = '';
                                TemplavoilaGeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                            }
                        } elseif ($cmd == 'saveUpdatedDSandTOandExit') {
                            header('Location:' . CoreGeneralUtility::locationHeaderUrl($this->returnUrl));
                        }
                    }
                    break;
            }

            // Header:
            $tRows = array();
            $relFilePath = substr($this->displayFile, strlen(PATH_site));
            $onCl = 'return top.openUrlInWindow(\'' . CoreGeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $relFilePath . '\',\'FileView\');';
            $tRows[] = '
                <tr>
                    <td class="bgColor5" rowspan="2">' . $this->cshItem('xMOD_tx_templavoila', 'mapping_file', '|') . '</td>
                    <td class="bgColor5" rowspan="2"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('templateFile') . ':</strong></td>
                    <td class="bgColor4"><a href="#" onclick="' . htmlspecialchars($onCl) . '">' . htmlspecialchars($relFilePath) . '</a></td>
                </tr>
                 <tr>
                    <td class="bgColor4">
                        <a href="#" onclick ="openValidator(\'' . $this->sessionKey . '\');return false;">'
                            . $this->iconFactory->getIcon('extensions-templavoila-htmlvalidate', Icon::SIZE_SMALL)->render()
                            . ' ' . TemplavoilaGeneralUtility::getLanguageService()->getLL('validateTpl')
                            . '
                        </a>
                    </td>
                </tr>
                <tr>
                    <td class="bgColor5">&nbsp;</td>
                    <td class="bgColor5"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('templateObject') . ':</strong></td>
                    <td class="bgColor4">' . ($toREC ? htmlspecialchars(TemplavoilaGeneralUtility::getLanguageService()->sL($toREC['title'])) : TemplavoilaGeneralUtility::getLanguageService()->getLL('mappingNEW')) . '</td>
                </tr>';
            if ($this->staticDS) {
                $onClick = 'return top.openUrlInWindow(\'' . CoreGeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $toREC['datastructure'] . '\',\'FileView\');';
                $tRows[] = '
                <tr>
                    <td class="bgColor5">&nbsp;</td>
                    <td class="bgColor5"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderDSO_XML') . ':</strong></td>
                    <td class="bgColor4"><a href="#" onclick="' . htmlspecialchars($onClick) . '">' . htmlspecialchars($toREC['datastructure']) . '</a></td>
                </tr>';
            } else {
                $tRows[] = '
                <tr>
                    <td class="bgColor5">&nbsp;</td>
                    <td class="bgColor5"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderTO_dsRecord') . ':</strong></td>
                    <td class="bgColor4">' . ($dsREC ? htmlspecialchars(TemplavoilaGeneralUtility::getLanguageService()->sL($dsREC['title'])) : TemplavoilaGeneralUtility::getLanguageService()->getLL('mappingNEW')) . '</td>
                </tr>';
            }

            // Write header of page:
            $content .= '

                <!--
                    Create Data Structure Header:
                -->
                <table border="0" cellpadding="2" cellspacing="1" id="c-toHeader">
                    ' . implode('', $tRows) . '
                </table><br />
            ';

            // Messages:
            if (is_array($msg)) {
                $content .= '

                    <!--
                        Messages:
                    -->
                    ' . implode('<br />', $msg) . '
                ';
            }

            // Generate selector box options:
            // Storage Folders for elements:
            $sf_opt = array();
            $res = TemplavoilaGeneralUtility::getDatabaseConnection()->exec_SELECTquery(
                '*',
                'pages',
                'uid IN (' . $this->storageFolders_pidList . ')' . BackendUtility::deleteClause('pages'),
                '',
                'title'
            );
            while (false !== ($row = TemplavoilaGeneralUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
                $sf_opt[] = '<option value="' . htmlspecialchars($row['uid']) . '">' . htmlspecialchars($row['title'] . ' (UID:' . $row['uid'] . ')') . '</option>';
            }

            // Template Object records:
            $opt = array();
            $opt[] = '<option value="0"></option>';
            if ($this->staticDS) {
                $res = TemplavoilaGeneralUtility::getDatabaseConnection()->exec_SELECTquery(
                    '*, CASE WHEN LOCATE(' . TemplavoilaGeneralUtility::getDatabaseConnection()->fullQuoteStr('(fce)', 'tx_templavoila_tmplobj') . ', datastructure)>0 THEN 2 ELSE 1 END AS scope',
                    'tx_templavoila_tmplobj',
                    'pid IN (' . $this->storageFolders_pidList . ') AND datastructure!=' . TemplavoilaGeneralUtility::getDatabaseConnection()->fullQuoteStr('', 'tx_templavoila_tmplobj') .
                    BackendUtility::deleteClause('tx_templavoila_tmplobj') .
                    BackendUtility::versioningPlaceholderClause('tx_templavoila_tmplobj'),
                    '',
                    'scope,title'
                );
            } else {
                $res = TemplavoilaGeneralUtility::getDatabaseConnection()->exec_SELECTquery(
                    'tx_templavoila_tmplobj.*,tx_templavoila_datastructure.scope',
                    'tx_templavoila_tmplobj LEFT JOIN tx_templavoila_datastructure ON tx_templavoila_datastructure.uid=tx_templavoila_tmplobj.datastructure',
                    'tx_templavoila_tmplobj.pid IN (' . $this->storageFolders_pidList . ') AND tx_templavoila_tmplobj.datastructure>0 ' .
                    BackendUtility::deleteClause('tx_templavoila_tmplobj') .
                    BackendUtility::versioningPlaceholderClause('tx_templavoila_tmplobj'),
                    '',
                    'tx_templavoila_datastructure.scope, tx_templavoila_tmplobj.pid, tx_templavoila_tmplobj.title'
                );
            }
            $storageFolderPid = 0;
            $optGroupOpen = false;
            while (false !== ($row = TemplavoilaGeneralUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
                $scope = $row['scope'];
                unset($row['scope']);
                BackendUtility::workspaceOL('tx_templavoila_tmplobj', $row);
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
                    htmlspecialchars(TemplavoilaGeneralUtility::getLanguageService()->sL($row['title']) . ' (UID:' . $row['uid'] . ')') . '</option>';
            }
            if ($optGroupOpen) {
                $opt[] = '</optgroup>';
            }

            // Module Interface output begin:
            switch ($cmd) {
                // Show XML DS
                case 'showXMLDS':

                    // Make instance of syntax highlight class:
                    $hlObj = CoreGeneralUtility::makeInstance(\Extension\Templavoila\Service\SyntaxHighlightingService::class);

                    $storeDataStruct = $dataStruct;
                    if (is_array($storeDataStruct['ROOT']['el'])) {
                        $this->eTypes->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'], $contentSplittedByMapping['sub']['ROOT']);
                    }
                    $dataStructureXML = CoreGeneralUtility::array2xml_cs($storeDataStruct, 'T3DataStructure', array('useCDATA' => 1));

                    $content .= '
                        <input type="submit" name="_DO_NOTHING" value="Go back" title="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonGoBack') . '" />
                        <h3>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('titleXmlConfiguration') . ':</h3>
                        ' . $this->cshItem('xMOD_tx_templavoila', 'mapping_file_showXMLDS', '|<br/>') . '
                        <pre>' . $hlObj->highLight_DS($dataStructureXML) . '</pre>';
                    break;
                case 'loadScreen':

                    $content .= '
                        <h3>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('titleLoadDSXml') . '</h3>
                        ' . $this->cshItem('xMOD_tx_templavoila', 'mapping_file_loadDSXML', '|<br/>') . '
                        <p>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('selectTOrecrdToLoadDSFrom') . ':</p>
                        <select name="_load_ds_xml_to">' . implode('', $opt) . '</select>
                        <br />
                        <p>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('pasteDSXml') . ':</p>
                        <textarea rows="15" name="_load_ds_xml_content" wrap="off"' . $GLOBALS['TBE_TEMPLATE']->formWidthText(48, 'width:98%;', 'off') . '></textarea>
                        <br />
                        <input type="submit" name="_load_ds_xml" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('loadDSXml') . '" />
                        <input type="submit" name="_" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonCancel') . '" />
                        ';
                    break;
                case 'saveScreen':

                    $content .= '
                        <h3>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('createDSTO') . ':</h3>
                        ' . $this->cshItem('xMOD_tx_templavoila', 'mapping_file_createDSTO', '|<br/>') . '
                        <table border="0" cellpadding="2" cellspacing="2" class="dso_table">
                            <tr>
                                <td class="bgColor5"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('titleDSTO') . ':</strong></td>
                                <td class="bgColor4"><input type="text" name="_saveDSandTO_title" /></td>
                            </tr>
                            <tr>
                                <td class="bgColor5"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('templateType') . ':</strong></td>
                                <td class="bgColor4">
                                    <select name="_saveDSandTO_type">
                                        <option value="1">' . TemplavoilaGeneralUtility::getLanguageService()->getLL('pageTemplate') . '</option>
                                        <option value="2">' . TemplavoilaGeneralUtility::getLanguageService()->getLL('contentElement') . '</option>
                                        <option value="0">' . TemplavoilaGeneralUtility::getLanguageService()->getLL('undefined') . '</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="bgColor5"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('storeInPID') . ':</strong></td>
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

                        <input type="submit" name="_updateDSandTO" value="UPDATE TO (and DS)" onclick="return confirm(' . CoreGeneralUtility::quoteJSvalue(TemplavoilaGeneralUtility::getLanguageService()->getLL('saveDSTOconfirm')) . ');" />
                        <input type="submit" name="_" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonCancel') . '" />
                        ';
                    break;
                default:
                    // Creating menu:
                    $menuItems = array();
                    $menuItems[] = '<input type="submit" name="_showXMLDS" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonShowXML') . '" title="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonTitle_showXML') . '" />';
                    $menuItems[] = '<input type="submit" name="_clear" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonClearAll') . '" title="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonTitle_clearAll') . '" /> ';
                    $menuItems[] = '<input type="submit" name="_preview" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonPreview') . '" title="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonTitle_preview') . '" />';
                    if (is_array($toREC) && is_array($dsREC)) {
                        $menuItems[] = '<input type="submit" name="_save" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonSave') . '" title="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonTitle_save') . '" />';
                        $menuItems[] = '<input type="submit" name="_saveExit" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonSaveExit') . '" title="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonTitle_saveExit') . '" />';
                    }
                    $menuItems[] = '<input type="submit" name="_saveScreen" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonSaveAs') . '" title="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonTitle_saveAs') . '" />';
                    $menuItems[] = '<input type="submit" name="_loadScreen" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonLoad') . '" title="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonTitle_load') . '" />';
                    $menuItems[] = '<input type="submit" name="_DO_NOTHING" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonRefresh') . '" title="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonTitle_refresh') . '" />';

                    $menuContent = '

                        <!--
                            Menu for creation Data Structures / Template Objects
                        -->
                        <table border="0" cellpadding="2" cellspacing="2" id="c-toMenu">
                            <tr class="bgColor5">
                                <td>' . implode('</td>
                                <td>', $menuItems) . '</td>
                            </tr>
                        </table>
                    ';

                    $content .= '

                    <!--
                        Data Structure creation table:
                    -->
                    <h3>' . $this->cshItem('xMOD_tx_templavoila', 'mapping_file', '|') . TemplavoilaGeneralUtility::getLanguageService()->getLL('buildingDS') . ':</h3>' .
                        $this->renderTemplateMapper($this->displayFile, $this->displayPath, $dataStruct, $currentMappingInfo, $menuContent);
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
            $row = BackendUtility::getRecordWSOL('tx_templavoila_datastructure', $this->displayUid);
            if (is_array($row)) {

                // Get title and icon:
                $icon = $this->iconFactory->getIconForRecord('tx_templavoila_datastructure', $row, Icon::SIZE_SMALL)->render();
                $title = BackendUtility::getRecordTitle('tx_templavoila_datastructure', $row, 1);
                $content .= BackendUtility::wrapClickMenuOnIcon($icon, 'tx_templavoila_datastructure', $row['uid'], true) .
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
                        <h4>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderDSO_dataStructure') . ':</h4>
                        <table border="0" cellspacing="2" cellpadding="2" class="dso_table">
                                    <tr class="bgColor5">
                                        <td nowrap="nowrap"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderDSO_dataElement') . ':</strong>' .
                        $this->cshItem('xMOD_tx_templavoila', 'mapping_head_dataElement') .
                        '</td>
                    <td nowrap="nowrap"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderDSO_mappingInstructions') . ':</strong>' .
                        $this->cshItem('xMOD_tx_templavoila', 'mapping_head_mapping_instructions') .
                        '</td>
                    <td nowrap="nowrap"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderDSO_rules') . ':</strong>' .
                        $this->cshItem('xMOD_tx_templavoila', 'mapping_head_Rules') .
                        '</td>
                </tr>
    ' . implode('', $tRows) . '
                        </table>
                    </div>';

                    // CSH
                    $content .= $this->cshItem('xMOD_tx_templavoila', 'mapping_ds');
                } else {
                    $content .= '<h4>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('error') . ': ' . TemplavoilaGeneralUtility::getLanguageService()->getLL('noDSDefined') . '</h4>';
                }

                // Get Template Objects pointing to this Data Structure
                $res = TemplavoilaGeneralUtility::getDatabaseConnection()->exec_SELECTquery(
                    '*',
                    'tx_templavoila_tmplobj',
                    'pid IN (' . $this->storageFolders_pidList . ') AND datastructure=' . (int)$row['uid'] .
                    BackendUtility::deleteClause('tx_templavoila_tmplobj') .
                    BackendUtility::versioningPlaceholderClause('tx_templavoila_tmplobj')
                );
                $tRows = array();
                $tRows[] = '
                            <tr class="bgColor5">
                                <td><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderDSO_uid') . ':</strong></td>
                                <td><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderDSO_title') . ':</strong></td>
                                <td><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderDSO_fileRef') . ':</strong></td>
                                <td><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderDSO_dataLgd') . ':</strong></td>
                            </tr>';
                $TOicon = $this->iconFactory->getIconForRecord('tx_templavoila_tmplobj', [], Icon::SIZE_SMALL)->render();

                // Listing Template Objects with links:
                while (false !== ($TO_Row = TemplavoilaGeneralUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
                    BackendUtility::workspaceOL('tx_templavoila_tmplobj', $TO_Row);
                    $tRows[] = '
                            <tr class="bgColor4">
                                <td>[' . $TO_Row['uid'] . ']</td>
                                <td nowrap="nowrap">' . BackendUtility::wrapClickMenuOnIcon($TOicon, 'tx_templavoila_tmplobj', $TO_Row['uid'], true) .
                        '<a href="' . htmlspecialchars('index.php?table=tx_templavoila_tmplobj&uid=' . $TO_Row['uid'] . '&_reload_from=1') . '">' .
                        BackendUtility::getRecordTitle('tx_templavoila_tmplobj', $TO_Row, 1) . '</a>' .
                        '</td>
                    <td nowrap="nowrap">' . htmlspecialchars($TO_Row['fileref']) . ' <strong>' .
                        (!CoreGeneralUtility::getFileAbsFileName($TO_Row['fileref'], 1) ? TemplavoilaGeneralUtility::getLanguageService()->getLL('renderDSO_notFound') : TemplavoilaGeneralUtility::getLanguageService()->getLL('renderDSO_ok')) . '</strong></td>
                                <td>' . strlen($TO_Row['templatemapping']) . '</td>
                            </tr>';
                }

                $content .= '

                    <!--
                        Template Objects attached to Data Structure Record:
                    -->
                    <div id="c-to">
                        <h4>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderDSO_usedTO') . ':</h4>
                        <table border="0" cellpadding="2" cellspacing="2" class="dso_table">
                        ' . implode('', $tRows) . '
                        </table>
                    </div>';

                // CSH
                $content .= $this->cshItem('xMOD_tx_templavoila', 'mapping_ds_to');

                // Display XML of data structure:
                if (is_array($dataStruct)) {

                    // Make instance of syntax highlight class:
                    $hlObj = CoreGeneralUtility::makeInstance(\Extension\Templavoila\Service\SyntaxHighlightingService::class);

                    $dataStructureXML = CoreGeneralUtility::array2xml_cs($origDataStruct, 'T3DataStructure', array('useCDATA' => 1));
                    $content .= '

                    <!--
                        Data Structure XML:
                    -->
                    <br />
                    <div id="c-dsxml">
                        <h3>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderDSO_XML') . ':</h3>
                        ' . $this->cshItem('xMOD_tx_templavoila', 'mapping_ds_showXML') . '
                        <p>' . BackendUtility::getFuncCheck('', 'SET[showDSxml]', $this->MOD_SETTINGS['showDSxml'], '', CoreGeneralUtility::implodeArrayForUrl('', $_GET, '', 1, 1)) . ' Show XML</p>
                        <pre>' .
                        ($this->MOD_SETTINGS['showDSxml'] ? $hlObj->highLight_DS($dataStructureXML) : '') . '
                        </pre>
                    </div>
                    ';
                }
            } else {
                $content .= sprintf(TemplavoilaGeneralUtility::getLanguageService()->getLL('errorNoDSrecord'), $this->displayUid);
            }
            $this->content .= '<h2>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderDSO_DSO') . '</h2>' . $content;
        } else {
            $this->content .= '<h2>Error:' . TemplavoilaGeneralUtility::getLanguageService()->getLL('errorInDSO') . '</h2>'
                . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderDSO_noUid');
        }
    }

    /**
     * Renders the display of Template Objects.
     *
     * @return void
     */
    public function renderTO()
    {
        if ((int)$this->displayUid > 0) {
            $row = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $this->displayUid);

            if (is_array($row)) {

                $tRows = array();
                $tRows[] = '
                    <tr class="bgColor5">
                        <td colspan="2"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderTO_toDetails') . ':</strong>' .
                    $this->cshItem('xMOD_tx_templavoila', 'mapping_to') .
                    '</td>
            </tr>';

                // Get title and icon:
                $icon = $this->iconFactory->getIconForRecord('tx_templavoila_tmplobj', $row, Icon::SIZE_SMALL)->render();

                $title = BackendUtility::getRecordTitle('tx_templavoila_tmplobj', $row);
                $title = BackendUtility::getRecordTitlePrep(TemplavoilaGeneralUtility::getLanguageService()->sL($title));
                $tRows[] = '
                    <tr class="bgColor4">
                        <td>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('templateObject') . ':</td>
                        <td>' . BackendUtility::wrapClickMenuOnIcon($icon, 'tx_templavoila_tmplobj', $row['uid'], true) . $title . '</td>
                    </tr>';

                // Session data
                $sessionKey = $this->MCONF['name'] . '_validatorInfo:' . $row['uid'];
                $sesDat = array('displayFile' => $row['fileref']);
                TemplavoilaGeneralUtility::getBackendUser()->setAndSaveSessionData($sessionKey, $sesDat);

                // Find the file:
                $theFile = CoreGeneralUtility::getFileAbsFileName($row['fileref'], 1);
                if ($theFile && @is_file($theFile)) {
                    $relFilePath = substr($theFile, strlen(PATH_site));
                    $onCl = 'return top.openUrlInWindow(\'' . CoreGeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $relFilePath . '\',\'FileView\');';
                    $tRows[] = '
                        <tr class="bgColor4">
                            <td rowspan="2">' . TemplavoilaGeneralUtility::getLanguageService()->getLL('templateFile') . ':</td>
                            <td><a href="#" onclick="' . htmlspecialchars($onCl) . '">' . htmlspecialchars($relFilePath) . '</a></td>
                        </tr>
                        <tr class="bgColor4">
                            <td>
                                <a href="#" onclick ="openValidator(\'' . $sessionKey . '\');return false;">'
                                    . $this->iconFactory->getIcon('extensions-templavoila-htmlvalidate', Icon::SIZE_SMALL)->render()
                                    . ' ' . TemplavoilaGeneralUtility::getLanguageService()->getLL('validateTpl')
                                    . '
                                </a>
                            </td>
                        </tr>';

                    // Finding Data Structure Record:
                    $DSOfile = '';
                    $dsValue = $row['datastructure'];
                    if ($row['parent']) {
                        $parentRec = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $row['parent'], 'datastructure');
                        $dsValue = $parentRec['datastructure'];
                    }

                    if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($dsValue)) {
                        $DS_row = BackendUtility::getRecordWSOL('tx_templavoila_datastructure', $dsValue);
                    } else {
                        $DSOfile = CoreGeneralUtility::getFileAbsFileName($dsValue);
                    }
                    if (is_array($DS_row) || @is_file($DSOfile)) {

                        // Get main DS array:
                        if (is_array($DS_row)) {
                            // Get title and icon:
                            $icon = $this->iconFactory->getIconForRecord('tx_templavoila_datastructure', $DS_row, Icon::SIZE_SMALL)->render();
                            $title = BackendUtility::getRecordTitle('tx_templavoila_datastructure', $DS_row);
                            $title = BackendUtility::getRecordTitlePrep(TemplavoilaGeneralUtility::getLanguageService()->sL($title));

                            $tRows[] = '
                                <tr class="bgColor4">
                                    <td>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderTO_dsRecord') . ':</td>
                                    <td>' . BackendUtility::wrapClickMenuOnIcon($icon, 'tx_templavoila_datastructure', $DS_row['uid'], true) . $title . '</td>
                                </tr>';

                            // Link to updating DS/TO:
                            $onCl = $this->getUrlToModifyDSTO($theFile, $row['uid'], $DS_row['uid'], $this->returnUrl);
                            $onClMsg = '
                                if (confirm(' . CoreGeneralUtility::quoteJSvalue(TemplavoilaGeneralUtility::getLanguageService()->getLL('renderTO_updateWarningConfirm')) . ')) {
                                    document.location=\'' . $onCl . '\';
                                }
                                return false;
                                ';
                            $tRows[] = '
                                <tr class="bgColor4">
                                    <td>&nbsp;</td>
                                    <td><input type="submit" name="_" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderTO_editDSTO') . '" onclick="' . htmlspecialchars($onClMsg) . '"/>' .
                                $this->cshItem('xMOD_tx_templavoila', 'mapping_to_modifyDSTO') .
                                '</td>
                        </tr>';

                            // Read Data Structure:
                            $dataStruct = $this->getDataStructFromDSO($DS_row['dataprot']);
                        } else {
                            // Show filepath of external XML file:
                            $relFilePath = substr($DSOfile, strlen(PATH_site));
                            $onCl = 'return top.openUrlInWindow(\'' . CoreGeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $relFilePath . '\',\'FileView\');';
                            $tRows[] = '
                                <tr class="bgColor4">
                                    <td>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderTO_dsFile') . ':</td>
                                    <td><a href="#" onclick="' . htmlspecialchars($onCl) . '">' . htmlspecialchars($relFilePath) . '</a></td>
                                </tr>';
                            $onCl = $this->getUrlToModifyDSTO($theFile, $row['uid'], $DSOfile, $this->returnUrl);
                            $onClMsg = '
                                if (confirm(' . CoreGeneralUtility::quoteJSvalue(TemplavoilaGeneralUtility::getLanguageService()->getLL('renderTO_updateWarningConfirm')) . ')) {
                                    document.location=\'' . $onCl . '\';
                                }
                                return false;
                                ';
                            $tRows[] = '
                                <tr class="bgColor4">
                                    <td>&nbsp;</td>
                                    <td><input type="submit" name="_" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderTO_editDSTO') . '" onclick="' . htmlspecialchars($onClMsg) . '"/>' .
                                $this->cshItem('xMOD_tx_templavoila', 'mapping_to_modifyDSTO') .
                                '</td>
                        </tr>';

                            // Read Data Structure:
                            $dataStruct = $this->getDataStructFromDSO('', $DSOfile);
                        }

                        // Write header of page:
                        $content .= '

                            <!--
                                Template Object Header:
                            -->
                            <h3>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('renderTO_toInfo') . ':</h3>
                            <table border="0" cellpadding="2" cellspacing="1" id="c-toHeader">
                                ' . implode('', $tRows) . '
                            </table>
                        ';

                        // If there is a valid data structure, draw table:
                        if (is_array($dataStruct)) {

                            // Working on Header and Body of HTML source:

                            // -- Processing the header editing --
                            list($editContent, $currentHeaderMappingInfo) = $this->renderTO_editProcessing($dataStruct, $row, $theFile, 1);

                            // Determine if DS is a template record and if it is a page template:
                            $showBodyTag = !is_array($DS_row) || $DS_row['scope'] == 1 ? true : false;

                            $parts = array();
                            $parts[] = array(
                                'label' => TemplavoilaGeneralUtility::getLanguageService()->getLL('tabTODetails'),
                                'content' => $content
                            );

                            // -- Processing the head editing
                            $headerContent .= '
                                <!--
                                    HTML header parts selection:
                                -->
                            <h3>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('mappingHeadParts') . ': ' . $this->cshItem('xMOD_tx_templavoila', 'mapping_to_headerParts') . '</h3>
                                ' . $this->renderHeaderSelection($theFile, $currentHeaderMappingInfo, $showBodyTag, $editContent);

                            $parts[] = array(
                                'label' => TemplavoilaGeneralUtility::getLanguageService()->getLL('tabHeadParts'),
                                'content' => $headerContent
                            );

                            // -- Processing the body editing --
                            list($editContent, $currentMappingInfo) = $this->renderTO_editProcessing($dataStruct, $row, $theFile, 0);

                            $bodyContent .= '
                                <!--
                                    Data Structure mapping table:
                                -->
                            <h3>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('mappingBodyParts') . ':</h3>
                                ' . $this->renderTemplateMapper($theFile, $this->displayPath, $dataStruct, $currentMappingInfo, $editContent);

                            $parts[] = array(
                                'label' => TemplavoilaGeneralUtility::getLanguageService()->getLL('tabBodyParts'),
                                'content' => $bodyContent
                            );
                        } else {
                            $content .= TemplavoilaGeneralUtility::getLanguageService()->getLL('error') . ': ' . sprintf(TemplavoilaGeneralUtility::getLanguageService()->getLL('errorNoDSfound'), $dsValue);
                        }
                    } else {
                        $content .= TemplavoilaGeneralUtility::getLanguageService()->getLL('error') . ': ' . sprintf(TemplavoilaGeneralUtility::getLanguageService()->getLL('errorNoDSfound'), $dsValue);
                    }
                } else {
                    $content .= TemplavoilaGeneralUtility::getLanguageService()->getLL('error') . ': ' . sprintf(TemplavoilaGeneralUtility::getLanguageService()->getLL('errorFileNotFound'), $row['fileref']);
                }
            } else {
                $content .= TemplavoilaGeneralUtility::getLanguageService()->getLL('error') . ': ' . sprintf(TemplavoilaGeneralUtility::getLanguageService()->getLL('errorNoTOfound'), $this->displayUid);
            }

            $parts[0]['content'] = $content;
        } else {
            $this->content .= '<h2>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('templateObject') . ' ' . TemplavoilaGeneralUtility::getLanguageService()->getLL('error') . '</h2>'
                . TemplavoilaGeneralUtility::getLanguageService()->getLL('errorNoUidFound');
        }

        // show tab menu
        if (is_array($parts)) {
            // Add output:
            $this->content .= $this->moduleTemplate->getDynamicTabMenu($parts, 'TEMPLAVOILA:templateModule:' . $this->id, 1, 0, 300);
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
     */
    public function renderTO_editProcessing(&$dataStruct, $row, $theFile, $headerPart = 0)
    {
        $msg = array();

        // Converting GPvars into a "cmd" value:
        $cmd = '';
        if (CoreGeneralUtility::_GP('_reload_from')) { // Reverting to old values in TO
            $cmd = 'reload_from';
        } elseif (CoreGeneralUtility::_GP('_clear')) { // Resetting mapping
            $cmd = 'clear';
        } elseif (CoreGeneralUtility::_GP('_save_data_mapping')) { // Saving to Session
            $cmd = 'save_data_mapping';
        } elseif (CoreGeneralUtility::_GP('_save_to') || CoreGeneralUtility::_GP('_save_to_return')) { // Saving to Template Object
            $cmd = 'save_to';
        }

        // Getting data from tmplobj
        $templatemapping = unserialize($row['templatemapping']);
        if (!is_array($templatemapping)) {
            $templatemapping = array();
        }

        // If that array contains sheets, then traverse them:
        if (is_array($dataStruct['sheets'])) {
            $dSheets = CoreGeneralUtility::resolveAllSheetsInDS($dataStruct);
            $dataStruct = array(
                'ROOT' => array(
                    'tx_templavoila' => array(
                        'title' => TemplavoilaGeneralUtility::getLanguageService()->getLL('rootMultiTemplate_title'),
                        'description' => TemplavoilaGeneralUtility::getLanguageService()->getLL('rootMultiTemplate_description'),
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
        $sesDat = TemplavoilaGeneralUtility::getBackendUser()->getSessionData($this->sessionKey);

        // Set current mapping info arrays:
        $currentMappingInfo_head = is_array($sesDat['currentMappingInfo_head']) ? $sesDat['currentMappingInfo_head'] : array();
        $currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : array();
        $this->cleanUpMappingInfoAccordingToDS($currentMappingInfo, $dataStruct);

        // Perform processing for head
        // GPvars, incoming data
        $checkboxElement = CoreGeneralUtility::_GP('checkboxElement', 1);
        $addBodyTag = CoreGeneralUtility::_GP('addBodyTag');

        // Update session data:
        if ($cmd == 'reload_from' || $cmd == 'clear') {
            $currentMappingInfo_head = is_array($templatemapping['MappingInfo_head']) && $cmd != 'clear' ? $templatemapping['MappingInfo_head'] : array();
            $sesDat['currentMappingInfo_head'] = $currentMappingInfo_head;
            TemplavoilaGeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
        } else {
            if ($cmd == 'save_data_mapping' || $cmd == 'save_to') {
                $sesDat['currentMappingInfo_head'] = $currentMappingInfo_head = array(
                    'headElementPaths' => $checkboxElement,
                    'addBodyTag' => $addBodyTag ? 1 : 0
                );
                TemplavoilaGeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
            }
        }

        // Perform processing for  body
        // GPvars, incoming data
        $inputData = CoreGeneralUtility::_GP('dataMappingForm', 1);

        // Update session data:
        if ($cmd == 'reload_from' || $cmd == 'clear') {
            $currentMappingInfo = is_array($templatemapping['MappingInfo']) && $cmd != 'clear' ? $templatemapping['MappingInfo'] : array();
            $this->cleanUpMappingInfoAccordingToDS($currentMappingInfo, $dataStruct);
            $sesDat['currentMappingInfo'] = $currentMappingInfo;
            $sesDat['dataStruct'] = $dataStruct;
            TemplavoilaGeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
        } else {
            if ($cmd == 'save_data_mapping' && is_array($inputData)) {
                $sesDat['currentMappingInfo'] = $currentMappingInfo = $this->array_merge_recursive_overrule($currentMappingInfo, $inputData);
                $sesDat['dataStruct'] = $dataStruct; // Adding data structure to session data so that the PREVIEW window can access the DS easily...
                TemplavoilaGeneralUtility::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
            }
        }

        // SAVE to template object
        if ($cmd == 'save_to') {
            $dataArr = array();

            // Set content, either for header or body:
            $templatemapping['MappingInfo_head'] = $currentMappingInfo_head;
            $templatemapping['MappingInfo'] = $currentMappingInfo;

            // Getting cached data:
            reset($dataStruct);
            // Init; read file, init objects:
            $fileContent = CoreGeneralUtility::getUrl($theFile);
            $htmlParse = CoreGeneralUtility::makeInstance(\TYPO3\CMS\Core\Html\HtmlParser::class);
            $this->markupObj = CoreGeneralUtility::makeInstance(\Extension\Templavoila\Domain\Model\HtmlMarkup::class);

            // Fix relative paths in source:
            $relPathFix = dirname(substr($theFile, strlen(PATH_site))) . '/';
            $uniqueMarker = uniqid('###') . '###';
            $fileContent = $htmlParse->prefixResourcePath($relPathFix, $fileContent, array('A' => $uniqueMarker));
            $fileContent = $this->fixPrefixForLinks($relPathFix, $fileContent, $uniqueMarker);

            // Get BODY content for caching:
            $contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($fileContent, $currentMappingInfo);
            $templatemapping['MappingData_cached'] = $contentSplittedByMapping['sub']['ROOT'];

            // Get HEAD content for caching:
            list($html_header) = $this->markupObj->htmlParse->getAllParts($htmlParse->splitIntoBlock('head', $fileContent), 1, 0);
            $this->markupObj->tags = $this->head_markUpTags; // Set up the markupObject to process only header-section tags:

            $h_currentMappingInfo = array();
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

            $TOuid = BackendUtility::wsMapId('tx_templavoila_tmplobj', $row['uid']);
            $dataArr['tx_templavoila_tmplobj'][$TOuid]['templatemapping'] = serialize($templatemapping);
            $dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref_mtime'] = @filemtime($theFile);
            $dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref_md5'] = @md5_file($theFile);

            $tce = CoreGeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
            $tce->stripslashes_values = 0;
            $tce->start($dataArr, array());
            $tce->process_datamap();
            unset($tce);
            $flashMessage = CoreGeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                TemplavoilaGeneralUtility::getLanguageService()->getLL('msgMappingSaved'),
                '',
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
            $msg[] .= $flashMessage->render();
            $row = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $this->displayUid);
            $templatemapping = unserialize($row['templatemapping']);

            if (CoreGeneralUtility::_GP('_save_to_return')) {
                header('Location: ' . CoreGeneralUtility::locationHeaderUrl($this->returnUrl));
                exit;
            }
        }

        // Making the menu
        $menuItems = array();
        $menuItems[] = '<input type="submit" name="_clear" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonClearAll') . '" title="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonClearAllMappingTitle') . '" />';

        // Make either "Preview" button (body) or "Set" button (header)
        if ($headerPart) { // Header:
            $menuItems[] = '<input type="submit" name="_save_data_mapping" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonSet') . '" title="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonSetTitle') . '" />';
        } else { // Body:
            $menuItems[] = '<input type="submit" name="_preview" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonPreview') . '" title="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonPreviewMappingTitle') . '" />';
        }

        $menuItems[] = '<input type="submit" name="_save_to" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonSave') . '" title="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonSaveTOTitle') . '" />';

        if ($this->returnUrl) {
            $menuItems[] = '<input type="submit" name="_save_to_return" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonSaveAndReturn') . '" title="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonSaveAndReturnTitle') . '" />';
        }

        // If a difference is detected...:
        if (
            (serialize($templatemapping['MappingInfo_head']) != serialize($currentMappingInfo_head)) ||
            (serialize($templatemapping['MappingInfo']) != serialize($currentMappingInfo))
        ) {
            $menuItems[] = '<input type="submit" name="_reload_from" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonRevert') . '" title="' . sprintf(TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonRevertTitle'), $headerPart ? 'HEAD' : 'BODY') . '" />';

            $flashMessage = CoreGeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                TemplavoilaGeneralUtility::getLanguageService()->getLL('msgMappingIsDifferent'),
                '',
                \TYPO3\CMS\Core\Messaging\FlashMessage::INFO
            );
            $msg[] .= $flashMessage->render();
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

        // @todo - replace with FlashMessage Queue
        $content .= implode('', $msg);

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
        $fileContent = CoreGeneralUtility::getUrl($this->markupFile);

        // Init mark up object.
        $this->markupObj = CoreGeneralUtility::makeInstance(\Extension\Templavoila\Domain\Model\HtmlMarkup::class);
        $this->markupObj->init();

        // Get <body> tag:
        $reg = '';
        preg_match('/<body[^>]*>/i', $fileContent, $reg);
        $html_body = $reg[0];

        // Get <head>...</head> from template:
        $splitByHeader = $this->markupObj->htmlParse->splitIntoBlock('head', $fileContent);
        list($html_header) = $this->markupObj->htmlParse->getAllParts($splitByHeader, 1, 0);

        // Set up the markupObject to process only header-section tags:
        $this->markupObj->tags = $this->head_markUpTags;
        $this->markupObj->checkboxPathsSet = is_array($currentHeaderMappingInfo['headElementPaths']) ? $currentHeaderMappingInfo['headElementPaths'] : array();
        $this->markupObj->maxRecursion = 0; // Should not enter more than one level.

        // Markup the header section data with the header tags, using "checkbox" mode:
        $tRows = $this->markupObj->markupHTMLcontent($html_header, $GLOBALS['BACK_PATH'], '', 'script,style,link,meta', 'checkbox');
        $bodyTagRow = $showBodyTag ? '
                <tr class="bgColor2">
                    <td><input type="checkbox" name="addBodyTag" value="1"' . ($currentHeaderMappingInfo['addBodyTag'] ? ' checked="checked"' : '') . ' /></td>
                    <td>' . \Extension\Templavoila\Domain\Model\HtmlMarkup::getGnyfMarkup('body') . '</td>
                    <td><pre>' . htmlspecialchars($html_body) . '</pre></td>
                </tr>' : '';

        $headerParts = '
            <!--
                Header parts:
            -->
            <table width="100%" border="0" cellpadding="2" cellspacing="2" id="c-headerParts">
                <tr class="bgColor5">
                    <td><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('include') . ':</strong></td>
                    <td><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('tag') . ':</strong></td>
                    <td><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('tagContent') . ':</strong></td>
                </tr>
                ' . $tRows . '
                ' . $bodyTagRow . '
            </table><br />';

        $flashMessage = CoreGeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Messaging\FlashMessage::class,
            TemplavoilaGeneralUtility::getLanguageService()->getLL('msgHeaderSet'),
            '',
            \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
        );
        $headerParts .= $flashMessage->render();

        $headerParts .= $this->cshItem('xMOD_tx_templavoila', 'mapping_to_headerParts_buttons') . $htmlAfterDSTable;

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
        $fileContent = CoreGeneralUtility::getUrl($this->markupFile);

        // Init mark up object.
        $this->markupObj = CoreGeneralUtility::makeInstance(\Extension\Templavoila\Domain\Model\HtmlMarkup::class);

        // Load splitted content from currentMappingInfo array (used to show us which elements maps to some real content).
        $contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($fileContent, $currentMappingInfo);

        // Show path:
        $pathRendered = CoreGeneralUtility::trimExplode('|', $path, 1);
        $acc = array();
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

        $optDat = array();
        $optDat[$lastEl['path']] = 'OUTER (Include tag)';
        $optDat[$lastEl['path'] . '/INNER'] = 'INNER (Exclude tag)';

        // Tags, which will trigger "INNER" to be listed on top (because it is almost always INNER-mapping that is needed)
        if (CoreGeneralUtility::inList('body,span,h1,h2,h3,h4,h5,h6,div,td,p,b,i,u,a', $lastEl['el'])) {
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
                $optDat[$lastEl['path'] . '/ATTR:' . $attrK] = 'ATTRIBUTE "' . $attrK . '" (= ' . CoreGeneralUtility::fixed_lgd_cs($v, 15) . ')';
            }
        }

        // Create Data Structure table:
        $content = '
            <!--
                Data Structure table:
            -->
            <table border="0" cellspacing="2" cellpadding="2" class="dso_table">
            <tr class="bgColor5">
                <td nowrap="nowrap"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('mapDataElement') . ':</strong>'
                . $this->cshItem('xMOD_tx_templavoila', 'mapping_head_dataElement') .
                '</td>'
                . ($this->editDataStruct ? '<td nowrap="nowrap"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('mapField') . ':</strong>'
                    . $this->cshItem('xMOD_tx_templavoila', 'mapping_head_Field')
                    . '</td>' : '')
                . '<td nowrap="nowrap"><strong>' . (!$this->_preview ? TemplavoilaGeneralUtility::getLanguageService()->getLL('mapInstructions') : TemplavoilaGeneralUtility::getLanguageService()->getLL('mapSampleData')) . '</strong>'
                . $this->cshItem('xMOD_tx_templavoila', 'mapping_head_' . (!$this->_preview ? 'mapping_instructions' : 'sample_data'))
                . '</td><td nowrap="nowrap"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('mapHTMLpath') . ':</strong>'
                . $this->cshItem('xMOD_tx_templavoila', 'mapping_head_HTMLpath')
                .'</td><td nowrap="nowrap"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('mapAction') . ':</strong>'
                . $this->cshItem('xMOD_tx_templavoila', 'mapping_head_Action')
                . '</td><td nowrap="nowrap"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('mapRules') . ':</strong>'
                . $this->cshItem('xMOD_tx_templavoila', 'mapping_head_Rules')
                . '</td>'
                . ($this->editDataStruct ? '<td nowrap="nowrap"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('mapEdit') . ':</strong>'
                    . $this->cshItem('xMOD_tx_templavoila', 'mapping_head_Edit')
                    . '</td>' : '')
                . '</tr>'
                . implode('', $this->drawDataStructureMap($dataStruct, 1, $currentMappingInfo, $pathLevels, $optDat, $contentSplittedByMapping))
            . '</table>' . $htmlAfterDSTable
            . $this->cshItem('xMOD_tx_templavoila', 'mapping_basics');

        // Make mapping window:
        $limitTags = implode(',', array_keys($this->explodeMappingToTagsStr($this->mappingToTags, 1)));
        if (($this->mapElPath && !$this->doMappingOfPath) || $this->showPathOnly || $this->_preview) {
            $content .=
                '

                <!--
                    Visual Mapping Window (Iframe)
                -->
                <h3>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('mapMappingWindow') . ':</h3>
            <!-- <p><strong>File:</strong> ' . htmlspecialchars($displayFile) . '</p> -->
            <p>' .
                BackendUtility::getFuncMenu('', 'SET[displayMode]', $this->MOD_SETTINGS['displayMode'], $this->MOD_MENU['displayMode'], 'index.php', CoreGeneralUtility::implodeArrayForUrl('', $_GET, '', 1, 1)) .
                $this->cshItem('xMOD_tx_templavoila', 'mapping_window_modes') .
                '</p>';

            if ($this->_preview) {
                $content .= '

                    <!--
                        Preview information table
                    -->
                    <table border="0" cellpadding="4" cellspacing="2" id="c-mapInfo">
                        <tr class="bgColor5"><td><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('mapPreviewInfo') . ':</strong>' .
                    $this->cshItem('xMOD_tx_templavoila', 'mapping_window_help') .
                    '</td></tr>
            </table>
        ';

                // Add the Iframe:
                $content .= $this->makeIframeForVisual($displayFile, '', '', 0, 1);
            } else {
                $tRows = array();
                if ($this->showPathOnly) {
                    $tRows[] = '
                        <tr class="bgColor4">
                            <td class="bgColor5"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('mapHTMLpath') . ':</strong></td>
                            <td>' . htmlspecialchars(str_replace('~~~', ' ', $this->displayPath)) . '</td>
                        </tr>
                    ';
                } else {
                    $tRows[] = '
                        <tr class="bgColor4">
                            <td class="bgColor5"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('mapDSelement') . ':</strong></td>
                            <td>' . $this->elNames[$this->mapElPath]['tx_templavoila']['title'] . '</td>
                        </tr>
                        <tr class="bgColor4">
                            <td class="bgColor5"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('mapLimitToTags') . ':</strong></td>
                            <td>' . htmlspecialchars(($limitTags ? strtoupper($limitTags) : '(ALL TAGS)')) . '</td>
                        </tr>
                        <tr class="bgColor4">
                            <td class="bgColor5"><strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('mapInstructions') . ':</strong></td>
                            <td>' . htmlspecialchars($this->elNames[$this->mapElPath]['tx_templavoila']['description']) . '</td>
                        </tr>
                    ';
                }
                $content .= '

                    <!--
                        Mapping information table
                    -->
                    <table border="0" cellpadding="2" cellspacing="2" id="c-mapInfo">
                        ' . implode('', $tRows) . '
                    </table>
                ';

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
        $sameLevelElements = array();
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
        $bInfo = CoreGeneralUtility::clientInfo();
        $multilineTooltips = ($bInfo['BROWSER'] == 'msie');
        $rowIndex = -1;

        // Data Structure array must be ... and array of course...
        if (is_array($dataStruct)) {
            foreach ($dataStruct as $key => $value) {
                $rowIndex++;

                if ($key == 'meta') {
                    // Do not show <meta> information in mapping interface!
                    continue;
                }

                if (is_array($value)) { // The value of each entry must be an array.

                    // ********************
                    // Making the row:
                    // ********************
                    $rowCells = array();

                    // Icon:
                    $info = $this->dsTypeInfo($value);
                    $icon = '<span class="dsType_Icon dsType_' . $info['id'] . '" title="' . $info['title'] . '">' . strtoupper($info['id']) . '</span>';

                    // Composing title-cell:
                    if (preg_match('/^LLL:/', $value['tx_templavoila']['title'])) {
                        $translatedTitle = TemplavoilaGeneralUtility::getLanguageService()->sL($value['tx_templavoila']['title']);
                        $translateIcon = '<sup title="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('displayDSTitleTranslated') . '">*</sup>';
                    } else {
                        $translatedTitle = $value['tx_templavoila']['title'];
                        $translateIcon = '';
                    }
                    $this->elNames[$formPrefix . '[' . $key . ']']['tx_templavoila']['title'] = $icon . '<strong>' . htmlspecialchars(CoreGeneralUtility::fixed_lgd_cs($translatedTitle, 30)) . '</strong>' . $translateIcon;
                    $rowCells['title'] = $this->elNames[$formPrefix . '[' . $key . ']']['tx_templavoila']['title'];

                    // Description:
                    $this->elNames[$formPrefix . '[' . $key . ']']['tx_templavoila']['description'] = $rowCells['description'] = htmlspecialchars($value['tx_templavoila']['description']);

                    // In "mapping mode", render HTML page and Command links:
                    if ($mappingMode) {

                        // HTML-path + CMD links:
                        $isMapOK = 0;
                        if ($currentMappingInfo[$key]['MAP_EL']) { // If mapping information exists...:

                            $mappingElement = str_replace('~~~', ' ', $currentMappingInfo[$key]['MAP_EL']);
                            if (isset($contentSplittedByMapping['cArray'][$key])) { // If mapping of this information also succeeded...:
                                $cF = implode(chr(10), CoreGeneralUtility::trimExplode(chr(10), $contentSplittedByMapping['cArray'][$key], 1));

                                if (strlen($cF) > 200) {
                                    $cF = CoreGeneralUtility::fixed_lgd_cs($cF, 90) . ' ' . CoreGeneralUtility::fixed_lgd_cs($cF, -90);
                                }

                                // Render HTML path:
                                list($pI) = $this->markupObj->splitPath($currentMappingInfo[$key]['MAP_EL']);

                                $okTitle = htmlspecialchars($cF ? sprintf(TemplavoilaGeneralUtility::getLanguageService()->getLL('displayDSContentFound'), strlen($contentSplittedByMapping['cArray'][$key])) . ($multilineTooltips ? ':' . chr(10) . chr(10) . $cF : '') : TemplavoilaGeneralUtility::getLanguageService()->getLL('displayDSContentEmpty'));

                                $rowCells['htmlPath'] = $this->iconFactory->getIcon('status-dialog-ok', Icon::SIZE_SMALL)->render()
                                    . \Extension\Templavoila\Domain\Model\HtmlMarkup::getGnyfMarkup($pI['el'], '---' . htmlspecialchars(CoreGeneralUtility::fixed_lgd_cs($mappingElement, -80))) .
                                    ($pI['modifier'] ? $pI['modifier'] . ($pI['modifier_value'] ? ':' . ($pI['modifier'] != 'RANGE' ? $pI['modifier_value'] : '...') : '') : '');
                                $rowCells['htmlPath'] = '<a href="' . $this->linkThisScript(array(
                                        'htmlPath' => $path . ($path ? '|' : '') . preg_replace('/\/[^ ]*$/', '', $currentMappingInfo[$key]['MAP_EL']),
                                        'showPathOnly' => 1,
                                        'DS_element' => CoreGeneralUtility::_GP('DS_element')
                                    )) . '">' . $rowCells['htmlPath'] . '</a>';

                                // CMD links, default content:
                                $rowCells['cmdLinks'] = '<span class="nobr"><input type="submit" value="Re-Map" name="_" onclick="document.location=\'' .
                                    $this->linkThisScript(array(
                                        'mapElPath' => $formPrefix . '[' . $key . ']',
                                        'htmlPath' => $path,
                                        'mappingToTags' => $value['tx_templavoila']['tags'],
                                        'DS_element' => CoreGeneralUtility::_GP('DS_element')
                                    )) . '\';return false;" title="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonRemapTitle') . '" />' .
                                    '<input type="submit" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonChangeMode') . '" name="_" onclick="document.location=\'' .
                                    $this->linkThisScript(array(
                                        'mapElPath' => $formPrefix . '[' . $key . ']',
                                        'htmlPath' => $path . ($path ? '|' : '') . $pI['path'],
                                        'doMappingOfPath' => 1,
                                        'DS_element' => CoreGeneralUtility::_GP('DS_element')
                                    )) . '\';return false;" title="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonChangeMode') . '" /></span>';

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
                                $tagsMapping = $this->explodeMappingToTagsStr($value['tx_templavoila']['tags']);
                                $mapDat = is_array($tagsMapping[$lastLevel['el']]) ? $tagsMapping[$lastLevel['el']] : $tagsMapping['*'];
                                unset($mapDat['']);
                                if (is_array($mapDat) && !count($mapDat)) {
                                    unset($mapDat);
                                }

                                // Create mapping options:
                                $opt = array();
                                foreach ($optDat as $k => $v) {
                                    list($pI) = $this->markupObj->splitPath($k);

                                    if (($value['type'] == 'attr' && $pI['modifier'] == 'ATTR') || ($value['type'] != 'attr' && $pI['modifier'] != 'ATTR')) {
                                        if (
                                            (!$this->markupObj->tags[$lastLevel['el']]['single'] || $pI['modifier'] != 'INNER') &&
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
                                $rowCells['cmdLinks'] = \Extension\Templavoila\Domain\Model\HtmlMarkup::getGnyfMarkup($pI['el'], '---' . htmlspecialchars(CoreGeneralUtility::fixed_lgd_cs($lastLevel['path'], -80))) .
                                    '<br /><select name="dataMappingForm' . $formPrefix . '[' . $key . '][MAP_EL]">
                                        ' . implode('
                                        ', $opt) . '
                                        <option value=""></option>
                                    </select>
                                    <br />
                                    <input type="submit" name="_save_data_mapping" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonSet') . '" />
                                    <input type="submit" name="_" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonCancel') . '" />';
                                $rowCells['cmdLinks'] .=
                                    $this->cshItem('xMOD_tx_templavoila', 'mapping_modeset');
                            } else {
                                $rowCells['cmdLinks'] = $this->iconFactory->getIcon('status-dialog-notification', Icon::SIZE_SMALL)->render()
                                    . '<strong>' . TemplavoilaGeneralUtility::getLanguageService()->getLL('msgHowToMap') . '</strong>';
                                $rowCells['cmdLinks'] .= '<br />
                                        <input type="submit" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonCancel') . '" name="_" onclick="document.location=\'' .
                                    $this->linkThisScript(array(
                                        'DS_element' => CoreGeneralUtility::_GP('DS_element')
                                    )) . '\';return false;" />';
                            }
                        } elseif (!$rowCells['cmdLinks'] && $mapOK && $value['type'] != 'no_map') {
                            $rowCells['cmdLinks'] = '<input type="submit" value="' . TemplavoilaGeneralUtility::getLanguageService()->getLL('buttonMap') . '" name="_" onclick="document.location=\'' .
                                $this->linkThisScript(array(
                                    'mapElPath' => $formPrefix . '[' . $key . ']',
                                    'htmlPath' => $path,
                                    'mappingToTags' => $value['tx_templavoila']['tags'],
                                    'DS_element' => CoreGeneralUtility::_GP('DS_element')
                                )) . '\';return false;" />';
                        }
                    }

                    // Display mapping rules:
                    $rowCells['tagRules'] = implode('<br />', CoreGeneralUtility::trimExplode(',', strtolower($value['tx_templavoila']['tags']), 1));
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
                                            onClick="return confirm(' . CoreGeneralUtility::quoteJSvalue(TemplavoilaGeneralUtility::getLanguageService()->getLL('confirmDeleteEntry'))
                            . ');">'
                            . $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render()
                            . '</a>';
                        $editAddCol = '<td nowrap="nowrap">' . $editAddCol . '</td>';
                    } else {
                        $editAddCol = '';
                    }

                    // Description:
                    if ($this->_preview) {
                        if (!is_array($value['tx_templavoila']['sample_data'])) {
                            $rowCells['description'] = '[' . TemplavoilaGeneralUtility::getLanguageService()->getLL('noSampleData') . ']';
                        } else {
                            $rowCells['description'] = \TYPO3\CMS\Core\Utility\DebugUtility::viewArray($value['tx_templavoila']['sample_data']);
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

                            <tr class="' . ($rowIndex % 2 ? 'bgColor4' : 'bgColor6') . '">
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
     * Returns Data Structure from the $datString
     *
     * @param string $datString XML content which is parsed into an array, which is returned.
     * @param string $file Absolute filename from which to read the XML data. Will override any input in $datString
     *
     * @return mixed The variable $dataStruct. Should be array. If string, then no structures was found and the function returns the XML parser error.
     */
    public function getDataStructFromDSO($datString, $file = '')
    {
        if ($file) {
            $dataStruct = CoreGeneralUtility::xml2array(CoreGeneralUtility::getUrl($file));
        } else {
            $dataStruct = CoreGeneralUtility::xml2array($datString);
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
            . BackendUtility::getModuleUrl('templavoila_template_disply', $theArray)
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

        return BackendUtility::getModuleUrl('templavoila_mapping', array_merge($theArray, $array));
    }

    public function redirectToModifyDSTO($toUid, $dsUid)
    {
        $params = [
            'file' => $this->displayFile,
            '_load_ds_xml' => 1,
            '_load_ds_xml_to' => $toUid,
            'uid' => $dsUid,
            'returnUrl' => BackendUtility::getModuleUrl('web_txtemplavoilaM2', ['id' => (int)$this->_saveDSandTO_pid])
        ];

        header(
            'Location:' . CoreGeneralUtility::locationHeaderUrl(
                $this->getUrlToModifyDSTO(
                    $this->displayFile,
                    $toUid,
                    $dsUid,
                    BackendUtility::getModuleUrl('web_txtemplavoilaM2', ['id' => (int)$this->_saveDSandTO_pid])
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

        return BackendUtility::getModuleUrl('templavoila_mapping', $params);
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
            'templavoila_template_disply',
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
        $elements = CoreGeneralUtility::trimExplode(',', strtolower($mappingToTags));
        $output = array();
        foreach ($elements as $v) {
            $subparts = CoreGeneralUtility::trimExplode(':', $v);
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
        $readPerms = TemplavoilaGeneralUtility::getBackendUser()->getPagePermsClause(1);
        $this->storageFolders = array();

        // Looking up all references to a storage folder:
        $res = TemplavoilaGeneralUtility::getDatabaseConnection()->exec_SELECTquery(
            'uid,storage_pid',
            'pages',
            'storage_pid>0' . BackendUtility::deleteClause('pages')
        );
        while (false !== ($row = TemplavoilaGeneralUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
            if (TemplavoilaGeneralUtility::getBackendUser()->isInWebMount($row['storage_pid'], $readPerms)) {
                $storageFolder = BackendUtility::getRecord('pages', $row['storage_pid'], 'uid,title');
                if ($storageFolder['uid']) {
                    $this->storageFolders[$storageFolder['uid']] = $storageFolder['title'];
                }
            }
        }

        // Looking up all root-pages and check if there's a tx_templavoila.storagePid setting present
        $res = TemplavoilaGeneralUtility::getDatabaseConnection()->exec_SELECTquery(
            'pid,root',
            'sys_template',
            'root=1' . BackendUtility::deleteClause('sys_template')
        );
        while (false !== ($row = TemplavoilaGeneralUtility::getDatabaseConnection()->sql_fetch_assoc($res))) {
            $tsCconfig = BackendUtility::getModTSconfig($row['pid'], 'tx_templavoila');
            if (
                isset($tsCconfig['properties']['storagePid']) &&
                TemplavoilaGeneralUtility::getBackendUser()->isInWebMount($tsCconfig['properties']['storagePid'], $readPerms)
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
            $LRobj = CoreGeneralUtility::makeInstance(\tx_loremipsum_wiz::class);
            $LRobj->backPath = $this->doc->backPath;

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
        $h_currentMappingInfo = array();
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
