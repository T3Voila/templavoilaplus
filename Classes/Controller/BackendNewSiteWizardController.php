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
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility as CoreGeneralUtility;

use Extension\Templavoila\Utility\GeneralUtility as TemplavoilaGeneralUtility;

$GLOBALS['LANG']->includeLLFile(
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('templavoila') . 'Resources/Private/Language/BackendNewSiteWizard.xlf'
);

/**
 * Module 'TemplaVoila' for the 'templavoila' extension.
 *
 * @author Kasper Skaarhoj <kasper@typo3.com>
 */
class BackendNewSiteWizardController extends \TYPO3\CMS\Backend\Module\BaseScriptClass
{
    /**
     * @var array
     */
    protected $pidCache;

    /**
     * @var string
     */
    protected $backPath;

    /**
     * Import as first page in root!
     *
     * @var integer
     */
    public $importPageUid = 0;

    /**
     * Session data during wizard
     *
     * @var array
     */
    public $wizardData = array();

    /**
     * @var array
     */
    public $pageinfo;

    /**
     * @var array
     */
    public $modTSconfig;

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
    protected $moduleName = 'web_txtemplavoilaM2';

    /**
     * @var array
     */
    public $tFileList = array();

    /**
     * @var array
     */
    public $errorsWarnings = array();

    /**
     * holds the extconf configuration
     *
     * @var array
     */
    public $extConf;

    /**
     * @var string
     */
    public $cm1Link = '../cm1/index.php';

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
        $this->MOD_MENU = array(
            'set_details' => '',
            'wiz_step' => ''
        );

        // page/be_user TSconfig settings and blinding of menu-items
        $this->modTSconfig = BackendUtility::getModTSconfig($this->id, 'mod.' . $this->moduleName);

        // CLEANSE SETTINGS
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, CoreGeneralUtility::_GP('SET'), $this->moduleName);
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
     * Main function of the module.
     *
     * @return void
     */
    public function main()
    {
        // Access check!
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $pageInfoArr = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $access = is_array($pageInfoArr) ? 1 : 0;

        if ($access) {
            // Draw the header.

            // Add custom styles
            $this->getPageRenderer()->addCssFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($this->extKey) . 'Resources/Public/StyleSheet/mod2_default.css');

            $this->getPageRenderer()->loadJquery();

            // Setup JS for ClickMenu which isn't loaded by ModuleTemplate
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');

            // Set up JS for dynamic tab menu and side bar
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Tabs');

            // Adding classic jumpToUrl function, needed for the function menu.
            // Also, the id in the parent frameset is configured.
            $this->moduleTemplate->addJavaScriptCode('templavoila_function', '
                function jumpToUrl(URL)    { //
                    document.location = URL;
                    return false;
                }
                function setHighlight(id)    {    //
                    if (top.fsMod) {
                        top.fsMod.recentIds["web"]=id;
                        top.fsMod.navFrameHighlightedID["web"]="pages"+id+"_"+top.fsMod.currentBank;    // For highlighting

                        if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav)    {
                            top.content.nav_frame.refresh_nav();
                        }
                    }
                }
            ');

            $this->renderModuleContent();
        } else {
            $flashMessage = CoreGeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('noaccess'),
                '',
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
            $this->content = $flashMessage->render();
        }

        $this->moduleTemplate->setTitle(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('title'));
        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageInfoArr);
        $this->setDocHeaderButtons(!isset($pageInfoArr['uid']));

        $this->moduleTemplate->setContent($this->content);
    }

    /**
     * Prints out the module HTML
     *
     * @return void
     */
    public function printContent()
    {
        echo $this->moduleTemplate->renderContent();
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

        if ($this->MOD_SETTINGS['wiz_step']) {
            $this->addDocHeaderButton(
                $this->moduleName,
                TemplavoilaGeneralUtility::getLanguageService()->getLL('newsitewizard_cancel', true),
                'actions-close',
                [
                    'SET' => [
                        'wiz_step' => 0,
                    ],
                ],
                ButtonBar::BUTTON_POSITION_LEFT,
                1
            );
        }
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
                ]
            )
            ->setSetVariables(array_keys($this->MOD_MENU));
        $this->buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }


    /******************************
     *
     * Rendering module content:
     *
     *******************************/

    /**
     * Renders module content:
     *
     * @return void
     */
    public function renderModuleContent()
    {
        if ($this->MOD_SETTINGS['wiz_step']) { // Run wizard instead of showing overview.
            $this->renderNewSiteWizard_run();
        } else {

            // Select all Data Structures in the PID and put into an array:
            $res = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTquery(
                'count(*)',
                'tx_templavoila_datastructure',
                'pid=' . (int)$this->id . BackendUtility::deleteClause('tx_templavoila_datastructure')
            );
            list($countDS) = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_fetch_row($res);
            \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_free_result($res);

            // Select all Template Records in PID:
            $res = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTquery(
                'count(*)',
                'tx_templavoila_tmplobj',
                'pid=' . (int)$this->id . BackendUtility::deleteClause('tx_templavoila_tmplobj')
            );
            list($countTO) = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_fetch_row($res);
            \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->sql_free_result($res);

            $this->renderNewSiteWizard_overview();
        }
    }

    /**
     * Stores errors/warnings inside the class.
     *
     * @param string $scope Scope string, 1=page, 2=ce, _ALL= all errors
     * @param string $type "fatal" or "warning"
     * @param string $HTML HTML content for the error.
     *
     * @return void
     * @see getErrorLog()
     */
    public function setErrorLog($scope, $type, $HTML)
    {
        $this->errorsWarnings['_ALL'][$type][] = $this->errorsWarnings[$scope][$type][] = $HTML;
    }

    /**
     * Returns status for a single scope
     *
     * @param string $scope Scope string
     *
     * @return array Array with content
     * @see setErrorLog()
     */
    public function getErrorLog($scope)
    {
        $errStat = false;
        if (is_array($this->errorsWarnings[$scope])) {
            $errStat = array();

            if (is_array($this->errorsWarnings[$scope]['warning'])) {
                $errStat['count'] = count($this->errorsWarnings[$scope]['warning']);
                $errStat['content'] = '<h3>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('warnings', true) . '</h3>' . implode('<hr/>', $this->errorsWarnings[$scope]['warning']);
                $errStat['iconCode'] = 2;
            }

            if (is_array($this->errorsWarnings[$scope]['fatal'])) {
                $errStat['count'] = count($this->errorsWarnings[$scope]['fatal']) . ($errStat['count'] ? '/' . $errStat['count'] : '');
                $errStat['content'] .= '<h3>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('fatalerrors', true) . '</h3>' . implode('<hr/>', $this->errorsWarnings[$scope]['fatal']);
                $errStat['iconCode'] = 3;
            }
        }

        return $errStat;
    }


    /******************************
     *
     * Wizard for new site
     *
     *****************************/

    /**
     * Wizard overview page - before the wizard is started.
     *
     * @return void
     */
    public function renderNewSiteWizard_overview()
    {
        if ($this->modTSconfig['properties']['hideNewSiteWizard']) {
            return;
        }

        if (\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->isAdmin()) {

            // Introduction:
            $outputString = nl2br(sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_intro', true), implode('", "', $this->getTemplatePaths(true, false))));

            // Checks:
            $missingExt = $this->wizard_checkMissingExtensions();
            $missingConf = $this->wizard_checkConfiguration();
            $missingDir = $this->wizard_checkDirectory();
            if (!$missingExt && !$missingConf) {
                $outputString .= '<br/><br/>'
                    . $this->buildButtonFromUrl(
                        'document.location=\'' . $this->getBaseUrl(['SET' => ['wiz_step' => 1]]) . '\'; return false;',
                        '',
                        'content-special-html',
                        \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_startnow', true)
                    );
            } else {
                $outputString .= '<br/><br/>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_problem');
            }

            // Add output:
            $this->content .= $this->moduleTemplate->section(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('wiz_title'), $outputString, 0, 1);

            // Missing extension warning:
            if ($missingExt) {
                $msg = CoreGeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $missingExt, \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_missingext'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
                $this->content .= $msg->render();
            }

            // Missing configuration warning:
            if ($missingConf) {
                $msg = CoreGeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_missingconf_description'), \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_missingconf'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
                $this->content .= $msg->render();
            }

            // Missing directory warning:
            if ($missingDir) {
                $this->content .= $this->moduleTemplate->section(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_missingdir'), $missingDir, 0, 1, 3);
            }
        }
    }

    /**
     * Running the wizard. Basically branching out to sub functions.
     * Also gets and saves session data in $this->wizardData
     *
     * @return void
     */
    public function renderNewSiteWizard_run()
    {
        // Getting session data:
        $this->wizardData = \Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->getSessionData('tx_templavoila_wizard');

        if (\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->isAdmin()) {
            switch ($this->MOD_SETTINGS['wiz_step']) {
                case 1:
                    $this->wizard_step1();
                    break;
                case 2:
                    $this->wizard_step2();
                    break;
                case 3:
                    $this->wizard_step3();
                    break;
                case 4:
                    $this->wizard_step4();
                    break;
                case 5:
                    $this->wizard_step5('field_menu');
                    break;
                case 5.1:
                    $this->wizard_step5('field_submenu');
                    break;
                case 6:
                    $this->wizard_step6();
                    break;
            }

            // Add output:
            $this->content .= $this->moduleTemplate->section('', $outputString, 0, 1);
        }

        // Save session data:
        \Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->setAndSaveSessionData('tx_templavoila_wizard', $this->wizardData);
    }

    /**
     * Pre-checking for extensions
     *
     * @return string If string is returned, an error occured.
     */
    public function wizard_checkMissingExtensions()
    {

        $outputString = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_missingext_description', true);

        // Create extension status:
        $checkExtensions = explode(',', 'css_styled_content,impexp');
        $missingExtensions = false;

        $tRows = array();
        $tRows[] = '<tr class="tableheader bgColor5">
            <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_missingext_extkey', true) . '</td>
            <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_missingext_installed', true) . '</td>
        </tr>';

        foreach ($checkExtensions as $extKey) {
            $tRows[] = '<tr class="bgColor4">
                <td>' . $extKey . '</td>
                <td align="center">' . (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extKey) ? \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_missingext_yes', true) : '<span class="typo3-red">' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_missingext_no', true) . '</span>') . '</td>
            </tr>';

            if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extKey))
                $missingExtensions = true;
        }

        $outputString .= '<table border="0" cellpadding="1" cellspacing="1">' . implode('', $tRows) . '</table>';

        // If no extensions are missing, simply go to step two:
        return ($missingExtensions) ? $outputString : '';
    }

    /**
     * Pre-checking for TemplaVoila configuration
     *
     * @return boolean If string is returned, an error occured.
     */
    public function wizard_checkConfiguration()
    {
        $TVconfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);

        return !is_array($TVconfig);
    }

    /**
     * Pre-checking for directory of extensions.
     *
     * @return string If string is returned, an error occured.
     */
    public function wizard_checkDirectory()
    {
        $paths = $this->getTemplatePaths(true);
        if (empty($paths)) {
            return nl2br(sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_missingdir_instruction'), implode(' or ', $this->getTemplatePaths(true, false)), $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']));
        }

        return false;
    }

    /**
     * Wizard Step 1: Selecting template file.
     *
     * @return void
     */
    public function wizard_step1()
    {
        $paths = $this->getTemplatePaths();
        $files = $this->getTemplateFiles();
        if (!empty($paths) && !empty($files)) {

            $this->wizardData = array();
            $pathArr = CoreGeneralUtility::removePrefixPathFromList($paths, PATH_site);
            $outputString = sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_firststep'), implode('", "', $pathArr)) . '<br/>';

            // Get all HTML files:
            $fileArr = CoreGeneralUtility::removePrefixPathFromList($files, PATH_site);

            // Prepare header:
            $tRows = array();
            $tRows[] = '<tr class="tableheader bgColor5">
                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('toused_path', true) . ':</td>
                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('usage', true) . ':</td>
                <td>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('action', true) . ':</td>
            </tr>';

            // Traverse available template files:
            foreach ($fileArr as $file) {

                // Has been used:
                $tosForTemplate = \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->exec_SELECTgetRows(
                    'uid',
                    'tx_templavoila_tmplobj',
                    'fileref=' . \Extension\Templavoila\Utility\GeneralUtility::getDatabaseConnection()->fullQuoteStr($file, 'tx_templavoila_tmplobj') .
                    BackendUtility::deleteClause('tx_templavoila_tmplobj')
                );

                // Preview link
                $onClick = 'vHWin=window.open(\'' . $this->doc->backPath . '../' . $file . '\',\'tvTemplatePreview\',\'status=1,menubar=1,scrollbars=1,location=1\');vHWin.focus();return false;';

                // Make row:
                $tRows[] = '<tr class="bgColor4">
                    <td>' . htmlspecialchars($file) . '</td>
                    <td>' . (count($tosForTemplate) ? sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_usedtimes', true), count($tosForTemplate)) : \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_notused', true)) . '</td>
                    <td>' .
                    '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_preview', true) . '</a> ' .
                    '<a href="' . htmlspecialchars('index.php?SET[wiz_step]=2&CFG[file]=' . rawurlencode($file)) . '">' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_choose', true) . '</a> ' .
                    '</td>
            </tr>';
            }
            $outputString .= '<table border="0" cellpadding="1" cellspacing="1" class="lrPadding">' . implode('', $tRows) . '</table>';

            // Refresh button:
            $this->addDocHeaderButton(
                $this->moduleName,
                TemplavoilaGeneralUtility::getLanguageService()->getLL('refresh', true),
                'actions-system-refresh',
                [
                    'SET' => [
                        'wiz_step' => 1,
                    ],
                ],
                ButtonBar::BUTTON_POSITION_LEFT,
                2
            );

            // Add output:
            $this->content .= $this->moduleTemplate->section(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_selecttemplate', true), $outputString, 0, 1);
        } else {
            $this->content .= $this->moduleTemplate->section('TemplaVoila wizard error', \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_errornodir', true), 0, 1);
        }
    }

    /**
     * Step 2: Enter default values:
     *
     * @return void
     */
    public function wizard_step2()
    {
        // Save session data with filename:
        $cfg = CoreGeneralUtility::_GET('CFG');
        if ($cfg['file'] && CoreGeneralUtility::getFileAbsFileName($cfg['file'])) {
            $this->wizardData['file'] = $cfg['file'];
        }

        // Show selected template file:
        if ($this->wizardData['file']) {
            $outputString = htmlspecialchars(sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_templateselected'), $this->wizardData['file']));
            $outputString .= '<br/><iframe src="' . htmlspecialchars($this->doc->backPath . '../' . $this->wizardData['file']) . '" width="640" height="300"></iframe>';

            // Enter default data:
            $outputString .= '
                <br/><br/><br/>
                ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_step2next', true) . '
                <br/>
    <br/>
                <b>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_step2_name', true) . ':</b><br/>
                ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_step2_required', true) . '<br/>
                ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_step2_valuename', true) . '<br/>
                <input type="text" name="CFG[sitetitle]" value="' . htmlspecialchars($this->wizardData['sitetitle']) . '" /><br/>
    <br/>
                <b>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_step2_url', true) . ':</b><br/>
                ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_step2_optional', true) . '<br/>
                ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_step2_valueurl', true) . '<br/>
                <input type="text" name="CFG[siteurl]" value="' . htmlspecialchars($this->wizardData['siteurl']) . '" /><br/>
    <br/>
                <b>' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_step2_editor', true) . ':</b><br/>
                ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_step2_required', true) . '<br/>
                ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_step2_username', true) . '<br/>
                <input type="text" name="CFG[username]" value="' . htmlspecialchars($this->wizardData['username']) . '" /><br/>
    <br/>
                <input type="hidden" name="SET[wiz_step]" value="3" />
                <input type="submit" name="_create_site" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_step2_createnewsite', true) . '" />
            ';
        } else {
            $outputString = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_step2_notemplatefound', true);
        }

        // Add output:
        $this->content .= $this->moduleTemplate->section(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_step2', true), $outputString, 0, 1);
    }

    /**
     * Step 3: Begin template mapping
     *
     * @return void
     */
    public function wizard_step3()
    {
        // Save session data with filename:
        $cfg = CoreGeneralUtility::_POST('CFG');
        if (isset($cfg['sitetitle'])) {
            $this->wizardData['sitetitle'] = trim($cfg['sitetitle']);
        }
        if (isset($cfg['siteurl'])) {
            $this->wizardData['siteurl'] = trim($cfg['siteurl']);
        }
        if (isset($cfg['username'])) {
            $this->wizardData['username'] = trim($cfg['username']);
        }

        // If the create-site button WAS clicked:
        $outputString = '';
        if (CoreGeneralUtility::_POST('_create_site')) {

            // Show selected template file:
            if ($this->wizardData['file'] && $this->wizardData['sitetitle'] && $this->wizardData['username']) {

                // DO import:
                $import = $this->getImportObj();
                if (isset($this->modTSconfig['properties']['newTvSiteFile'])) {
                    $inFile = CoreGeneralUtility::getFileAbsFileName($this->modTSconfig['properties']['newTVsiteTemplate']);
                } else {
                    $inFile = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('templavoila') . 'Resources/Private/Template/new_tv_site.xml';
                }
                if (@is_file($inFile) && $import->loadFile($inFile, 1)) {

                    $import->importData($this->importPageUid);

                    // Update various fields (the index values, eg. the "1" in "$import->import_mapId['pages'][1]]..." are the UIDs of the original records from the import file!)
                    $data = array();
                    $data['pages'][BackendUtility::wsMapId('pages', $import->import_mapId['pages'][1])]['title'] = $this->wizardData['sitetitle'];
                    $data['sys_template'][BackendUtility::wsMapId('sys_template', $import->import_mapId['sys_template'][1])]['title'] = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_maintemplate', true) . ' ' . $this->wizardData['sitetitle'];
                    $data['sys_template'][BackendUtility::wsMapId('sys_template', $import->import_mapId['sys_template'][1])]['sitetitle'] = $this->wizardData['sitetitle'];
                    $data['tx_templavoila_tmplobj'][BackendUtility::wsMapId('tx_templavoila_tmplobj', $import->import_mapId['tx_templavoila_tmplobj'][1])]['fileref'] = $this->wizardData['file'];
                    $data['tx_templavoila_tmplobj'][BackendUtility::wsMapId('tx_templavoila_tmplobj', $import->import_mapId['tx_templavoila_tmplobj'][1])]['templatemapping'] = serialize(
                        array(
                            'MappingInfo' => array(
                                'ROOT' => array(
                                    'MAP_EL' => 'body[1]/INNER'
                                )
                            ),
                            'MappingInfo_head' => array(
                                'headElementPaths' => array('link[1]', 'link[2]', 'link[3]', 'style[1]', 'style[2]', 'style[3]'),
                                'addBodyTag' => 1
                            )
                        )
                    );

                    // Update user settings
                    $newUserID = BackendUtility::wsMapId('be_users', $import->import_mapId['be_users'][2]);
                    $newGroupID = BackendUtility::wsMapId('be_groups', $import->import_mapId['be_groups'][1]);

                    $data['be_users'][$newUserID]['username'] = $this->wizardData['username'];
                    $data['be_groups'][$newGroupID]['title'] = $this->wizardData['username'];

                    foreach ($import->import_mapId['pages'] as $newID) {
                        $data['pages'][$newID]['perms_userid'] = $newUserID;
                        $data['pages'][$newID]['perms_groupid'] = $newGroupID;
                    }

                    // Set URL if applicable:
                    if (strlen($this->wizardData['siteurl'])) {
                        $data['sys_domain']['NEW']['pid'] = BackendUtility::wsMapId('pages', $import->import_mapId['pages'][1]);
                        $data['sys_domain']['NEW']['domainName'] = $this->wizardData['siteurl'];
                    }

                    // Execute changes:
                    $tce = CoreGeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                    $tce->stripslashes_values = 0;
                    $tce->dontProcessTransformations = 1;
                    $tce->start($data, Array());
                    $tce->process_datamap();

                    // Setting environment:
                    $this->wizardData['rootPageId'] = $import->import_mapId['pages'][1];
                    $this->wizardData['templateObjectId'] = BackendUtility::wsMapId('tx_templavoila_tmplobj', $import->import_mapId['tx_templavoila_tmplobj'][1]);
                    $this->wizardData['typoScriptTemplateID'] = BackendUtility::wsMapId('sys_template', $import->import_mapId['sys_template'][1]);

                    BackendUtility::setUpdateSignal('updatePageTree');

                    $outputString .= \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_maintemplate', true) . '<hr/>';
                }
            } else {
                $outputString .= \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_maintemplate', true);
            }
        }

        // If a template Object id was found, continue with mapping:
        if ($this->wizardData['templateObjectId']) {
            $url = '../cm1/index.php?table=tx_templavoila_tmplobj&uid=' . $this->wizardData['templateObjectId'] . '&SET[selectHeaderContent]=0&_reload_from=1&id=' . $this->id . '&returnUrl=' . rawurlencode('../mod2/index.php?SET[wiz_step]=4');

            $outputString .= \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_step3ready') . '
                <br/>
                <br/>
                <img src="Resources/Public/Image/mapbody_animation.gif" style="border: 2px black solid;" alt=""><br/>
                <br/>
                <br/><input type="submit" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_startmapping', true) . '" onclick="' . htmlspecialchars('document.location=\'' . $url . '\'; return false;') . '" />
            ';
        }

        // Add output:
        $this->content .= $this->moduleTemplate->section(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_beginmapping', true), $outputString, 0, 1);
    }

    /**
     * Step 4: Select HTML header parts.
     *
     * @return void
     */
    public function wizard_step4()
    {
        $url = '../cm1/index.php?table=tx_templavoila_tmplobj&uid=' . $this->wizardData['templateObjectId'] . '&SET[selectHeaderContent]=1&_reload_from=1&id=' . $this->id . '&returnUrl=' . rawurlencode('../mod2/index.php?SET[wiz_step]=5');
        $outputString = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_headerinclude') . '
            <br/>
            <img src="Resources/Public/Image/maphead_animation.gif" style="border: 2px black solid;" alt=""><br/>
            <br/>
            <br/><input type="submit" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_headerselect') . '" onclick="' . htmlspecialchars('document.location=\'' . $url . '\'; return false;') . '" />
            ';

        // Add output:
        $this->content .= $this->moduleTemplate->section(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_step4'), $outputString, 0, 1);
    }

    /**
     * Step 5: Create dynamic menu
     *
     * @param string $menuField Type of menu (main or sub), values: "field_menu" or "field_submenu"
     *
     * @return void
     */
    public function wizard_step5($menuField)
    {
        $menuPart = $this->getMenuDefaultCode($menuField);
        $menuType = $menuField === 'field_menu' ? 'mainMenu' : 'subMenu';
        $menuTypeText = $menuField === 'field_menu' ? 'main menu' : 'sub menu';
        $menuTypeLetter = $menuField === 'field_menu' ? 'a' : 'b';
        $menuTypeNextStep = $menuField === 'field_menu' ? 5.1 : 6;
        $menuTypeEntryLevel = $menuField === 'field_menu' ? 0 : 1;

        $this->saveMenuCode();

        if (strlen($menuPart)) {

            // Main message:
            $outputString = sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_basicsshouldwork', true), $menuTypeText, $menuType, $menuTypeText);

            // Start up HTML parser:
            $htmlParser = CoreGeneralUtility::makeInstance(\TYPO3\CMS\Core\Html\HtmlParser::class);

            // Parse into blocks
            $parts = $htmlParser->splitIntoBlock('td,tr,table,a,div,span,ol,ul,li,p,h1,h2,h3,h4,h5', $menuPart, 1);

            // If it turns out to be only a single large block we expect it to be a container for the menu item. Therefore we will parse the next level and expect that to be menu items:
            if (count($parts) == 3) {
                $totalWrap = array();
                $totalWrap['before'] = $parts[0] . $htmlParser->getFirstTag($parts[1]);
                $totalWrap['after'] = '</' . strtolower($htmlParser->getFirstTagName($parts[1])) . '>' . $parts[2];

                $parts = $htmlParser->splitIntoBlock('td,tr,table,a,div,span,ol,ul,li,p,h1,h2,h3,h4,h5', $htmlParser->removeFirstAndLastTag($parts[1]), 1);
            } else {
                $totalWrap = array();
            }

            $menuPart_HTML = trim($totalWrap['before']) . chr(10) . implode(chr(10), $parts) . chr(10) . trim($totalWrap['after']);

            // Traverse expected menu items:
            $menuWraps = array();
            $GMENU = false;
            $mouseOver = false;
            $key = '';

            foreach ($parts as $k => $value) {
                if ($k % 2) { // Only expecting inner elements to be of use:

                    $linkTag = $htmlParser->splitIntoBlock('a', $value, 1);
                    if ($linkTag[1]) {
                        $newValue = array();
                        $attribs = $htmlParser->get_tag_attributes($htmlParser->getFirstTag($linkTag[1]), 1);
                        $newValue['A-class'] = $attribs[0]['class'];
                        if ($attribs[0]['onmouseover'] && $attribs[0]['onmouseout'])
                            $mouseOver = true;

                        // Check if the complete content is an image - then make GMENU!
                        $linkContent = trim($htmlParser->removeFirstAndLastTag($linkTag[1]));
                        if (preg_match('/^<img[^>]*>$/i', $linkContent)) {
                            $GMENU = true;
                            $attribs = $htmlParser->get_tag_attributes($linkContent, 1);
                            $newValue['I-class'] = $attribs[0]['class'];
                            $newValue['I-width'] = $attribs[0]['width'];
                            $newValue['I-height'] = $attribs[0]['height'];

                            $filePath = CoreGeneralUtility::getFileAbsFileName(CoreGeneralUtility::resolveBackPath(PATH_site . $attribs[0]['src']));
                            if (@is_file($filePath)) {
                                $newValue['backColorGuess'] = $this->getBackgroundColor($filePath);
                            } else $newValue['backColorGuess'] = '';

                            if ($attribs[0]['onmouseover'] && $attribs[0]['onmouseout'])
                                $mouseOver = true;
                        }

                        $linkTag[1] = '|';
                        $newValue['wrap'] = preg_replace('/[' . chr(10) . chr(13) . ']*/', '', implode('', $linkTag));

                        $md5Base = $newValue;
                        unset($md5Base['I-width']);
                        unset($md5Base['I-height']);
                        $md5Base = serialize($md5Base);
                        $md5Base = preg_replace('/name=["\'][^"\']*["\']/', '', $md5Base);
                        $md5Base = preg_replace('/id=["\'][^"\']*["\']/', '', $md5Base);
                        $md5Base = preg_replace('/\s/', '', $md5Base);
                        $key = md5($md5Base);

                        if (!isset($menuWraps[$key])) { // Only if not yet set, set it (so it only gets set once and the first time!)
                            $menuWraps[$key] = $newValue;
                        } else { // To prevent from writing values in the "} elseif ($key) {" below, we clear the key:
                            $key = '';
                        }
                    } elseif ($key) {

                        // Add this to the previous wrap:
                        $menuWraps[$key]['bulletwrap'] .= str_replace('|', '&#' . ord('|') . ';', preg_replace('/[' . chr(10) . chr(13) . ']*/', '', $value));
                    }
                }
            }

            // Construct TypoScript for the menu:
            reset($menuWraps);
            if (count($menuWraps) == 1) {
                $menu_normal = current($menuWraps);
                $menu_active = next($menuWraps);
            } else { // If more than two, then the first is the active one.
                $menu_active = current($menuWraps);
                $menu_normal = next($menuWraps);
            }

            if ($GMENU) {
                $typoScript = '
lib.' . $menuType . ' = HMENU
lib.' . $menuType . '.entryLevel = ' . $menuTypeEntryLevel . '
' . (count($totalWrap) ? 'lib.' . $menuType . '.wrap = ' . preg_replace('/[' . chr(10) . chr(13) . ']/', '', implode('|', $totalWrap)) : '') . '
lib.' . $menuType . '.1 = GMENU
lib.' . $menuType . '.1.NO.wrap = ' . $this->makeWrap($menu_normal) .
                    ($menu_normal['I-class'] ? '
lib.' . $menuType . '.1.NO.imgParams = class="' . htmlspecialchars($menu_normal['I-class']) . '" ' : '') . '
lib.' . $menuType . '.1.NO {
    XY = ' . ($menu_normal['I-width'] ? $menu_normal['I-width'] : 150) . ',' . ($menu_normal['I-height'] ? $menu_normal['I-height'] : 25) . '
    backColor = ' . ($menu_normal['backColorGuess'] ? $menu_normal['backColorGuess'] : '#FFFFFF') . '
    10 = TEXT
    10.text.field = title // nav_title
    10.fontColor = #333333
    10.fontSize = 12
    10.offset = 15,15
    10.fontFace = typo3/sysext/core/Resources/Private/Font/nimbus.ttf
}
    ';

                if ($mouseOver) {
                    $typoScript .= '
lib.' . $menuType . '.1.RO < lib.' . $menuType . '.1.NO
lib.' . $menuType . '.1.RO = 1
lib.' . $menuType . '.1.RO {
    backColor = ' . CoreGeneralUtility::modifyHTMLColorAll(($menu_normal['backColorGuess'] ? $menu_normal['backColorGuess'] : '#FFFFFF'), -20) . '
    10.fontColor = red
}
            ';
                }
                if (is_array($menu_active)) {
                    $typoScript .= '
lib.' . $menuType . '.1.ACT < lib.' . $menuType . '.1.NO
lib.' . $menuType . '.1.ACT = 1
lib.' . $menuType . '.1.ACT.wrap = ' . $this->makeWrap($menu_active) .
                        ($menu_active['I-class'] ? '
lib.' . $menuType . '.1.ACT.imgParams = class="' . htmlspecialchars($menu_active['I-class']) . '" ' : '') . '
lib.' . $menuType . '.1.ACT {
    backColor = ' . ($menu_active['backColorGuess'] ? $menu_active['backColorGuess'] : '#FFFFFF') . '
}
            ';
                }
            } else {
                $typoScript = '
lib.' . $menuType . ' = HMENU
lib.' . $menuType . '.entryLevel = ' . $menuTypeEntryLevel . '
' . (count($totalWrap) ? 'lib.' . $menuType . '.wrap = ' . preg_replace('/[' . chr(10) . chr(13) . ']/', '', implode('|', $totalWrap)) : '') . '
lib.' . $menuType . '.1 = TMENU
lib.' . $menuType . '.1.NO {
    allWrap = ' . $this->makeWrap($menu_normal) .
                    ($menu_normal['A-class'] ? '
    ATagParams = class="' . htmlspecialchars($menu_normal['A-class']) . '"' : '') . '
}
    ';

                if (is_array($menu_active)) {
                    $typoScript .= '
lib.' . $menuType . '.1.ACT = 1
lib.' . $menuType . '.1.ACT {
    allWrap = ' . $this->makeWrap($menu_active) .
                        ($menu_active['A-class'] ? '
    ATagParams = class="' . htmlspecialchars($menu_active['A-class']) . '"' : '') . '
}
            ';
                }
            }

            // Output:

            // HTML defaults:
            $outputString .= '
            <br/>
            <br/>
            ' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_menuhtmlcode', true) . '
            <hr/>
            <pre>' . htmlspecialchars($menuPart_HTML) . '</pre>
            <hr/>
            <br/>';

            if (trim($menu_normal['wrap']) != '|') {
                $outputString .= sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_menuenc', true), htmlspecialchars(str_replace('|', ' ... ', $menu_normal['wrap'])));
            } else {
                $outputString .= \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_menunoa', true);
            }
            if (count($totalWrap)) {
                $outputString .= sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_menuwrap', true), htmlspecialchars(str_replace('|', ' ... ', implode('|', $totalWrap))));
            }
            if ($menu_normal['bulletwrap']) {
                $outputString .= sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_menudiv', true), htmlspecialchars($menu_normal['bulletwrap']));
            }
            if ($GMENU) {
                $outputString .= \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_menuimg', true);
            }
            if ($mouseOver) {
                $outputString .= \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_menumouseover', true);
            }

            $outputString .= '<br/><br/>';
            $outputString .= \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_menuts', true) . '
            <br/><br/>';
            $outputString .= '<hr/>' . $this->syntaxHLTypoScript($typoScript) . '<hr/><br/>';

            $outputString .= \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_menufinetune', true);
            $outputString .= '<textarea name="CFG[menuCode]"' . $GLOBALS['TBE_TEMPLATE']->formWidthText() . ' rows="10">' . CoreGeneralUtility::formatForTextarea($typoScript) . '</textarea><br/><br/>';
            $outputString .= '<input type="hidden" name="SET[wiz_step]" value="' . $menuTypeNextStep . '" />';
            $outputString .= '<input type="submit" name="_" value="' . sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_menuwritets', true), $menuTypeText) . '" />';
        } else {
            $outputString = sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_menufinished', true), $menuTypeText) . '<br />';
            $outputString .= '<input type="hidden" name="SET[wiz_step]" value="' . $menuTypeNextStep . '" />';
            $outputString .= '<input type="submit" name="_" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_menunext', true) . '" />';
        }

        // Add output:
        $this->content .= $this->moduleTemplate->section(sprintf(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_step5', true), $menuTypeLetter), $outputString, 0, 1);
    }

    /**
     * Step 6: Done.
     *
     * @return void
     */
    public function wizard_step6()
    {
        $this->saveMenuCode();

        $outputString = \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_sitecreated') . '

        <br/>
        <br/>
        <input type="submit" value="' . \Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_finish', true) . '" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($this->wizardData['rootPageId'], $this->doc->backPath) . 'document.location=\'index.php?SET[wiz_step]=0\'; return false;') . '" />
        ';

        // Add output:
        $this->content .= $this->moduleTemplate->section(\Extension\Templavoila\Utility\GeneralUtility::getLanguageService()->getLL('newsitewizard_done', true), $outputString, 0, 1);
    }

    /**
     * Initialize the import-engine
     *
     * @return \TYPO3\CMS\Impexp\ImportExport Returns object ready to import the import-file used to create the basic site!
     */
    public function getImportObj()
    {
        /** @var \TYPO3\CMS\Impexp\ImportExport $import */
        $import = CoreGeneralUtility::makeInstance(\tx_impexp::class);
        $import->init(0, 'import');
        $import->enableLogging = true;

        return $import;
    }

    /**
     * Syntax Highlighting of TypoScript code
     *
     * @param string $v String of TypoScript code
     *
     * @return string HTML content with it highlighted.
     */
    public function syntaxHLTypoScript($v)
    {
        $tsparser = CoreGeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::class);
        $tsparser->lineNumberOffset = 0;
        $TScontent = $tsparser->doSyntaxHighlight(trim($v) . chr(10), '', 1);

        return $TScontent;
    }

    /**
     * Produce WRAP value
     *
     * @param array $cfg menuItemSuggestion configuration
     *
     * @return string Wrap for TypoScript
     */
    public function makeWrap($cfg)
    {
        if (!$cfg['bulletwrap']) {
            $wrap = $cfg['wrap'];
        } else {
            $wrap = $cfg['wrap'] . '  |*|  ' . $cfg['bulletwrap'] . $cfg['wrap'];
        }

        return preg_replace('/[' . chr(10) . chr(13) . chr(9) . ']/', '', $wrap);
    }

    /**
     * Returns the code that the menu was mapped to in the HTML
     *
     * @param string $field "Field" from Data structure, either "field_menu" or "field_submenu"
     *
     * @return string
     */
    public function getMenuDefaultCode($field)
    {
        // Select template record and extract menu HTML content
        $toRec = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $this->wizardData['templateObjectId']);
        $tMapping = unserialize($toRec['templatemapping']);

        return $tMapping['MappingData_cached']['cArray'][$field];
    }

    /**
     * Saves the menu TypoScript code
     *
     * @return void
     */
    public function saveMenuCode()
    {
        // Save menu code to template record:
        $cfg = CoreGeneralUtility::_POST('CFG');
        if (isset($cfg['menuCode'])) {

            // Get template record:
            $TSrecord = BackendUtility::getRecord('sys_template', $this->wizardData['typoScriptTemplateID']);
            if (is_array($TSrecord)) {
                $data = array();
                $data['sys_template'][$TSrecord['uid']]['config'] = '

## Menu [Begin]
' . trim($cfg['menuCode']) . '
## Menu [End]



' . $TSrecord['config'];

                // Execute changes:
                $tce = CoreGeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                $tce->stripslashes_values = 0;
                $tce->dontProcessTransformations = 1;
                $tce->start($data, Array());
                $tce->process_datamap();
            }
        }
    }

    /**
     * Tries to fetch the background color of a GIF or PNG image.
     *
     * @param string $filePath Filepath (absolute) of the image (must exist)
     *
     * @return string HTML hex color code, if any.
     */
    public function getBackgroundColor($filePath)
    {
        if (substr($filePath, -4) == '.gif' && function_exists('imagecreatefromgif')) {
            $im = @imagecreatefromgif($filePath);
        } elseif (substr($filePath, -4) == '.png' && function_exists('imagecreatefrompng')) {
            $im = @imagecreatefrompng($filePath);
        } else {
            $im = null;
        }

        if (is_resource($im)) {
            $values = imagecolorsforindex($im, imagecolorat($im, 3, 3));
            $color = '#' . substr('00' . dechex($values['red']), -2) .
                substr('00' . dechex($values['green']), -2) .
                substr('00' . dechex($values['blue']), -2);

            return $color;
        }

        return false;
    }

    /**
     * Find and check all template paths
     *
     * @param boolean $relative if true returned paths are relative
     * @param boolean $check if true the patchs are checked
     *
     * @return array all relevant template paths
     */
    protected function getTemplatePaths($relative = false, $check = true)
    {
        $templatePaths = array();
        if (strlen($this->modTSconfig['properties']['templatePath'])) {
            $paths = CoreGeneralUtility::trimExplode(',', $this->modTSconfig['properties']['templatePath'], true);
        } else {
            $paths = array('templates');
        }

        $prefix = CoreGeneralUtility::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']);

        foreach (\Extension\Templavoila\Utility\GeneralUtility::getBackendUser()->getFileStorages() AS $driver) {
            /** @var TYPO3\CMS\Core\Resource\ResourceStorage $driver */
            $driverpath = $driver->getConfiguration();
            $driverpath = CoreGeneralUtility::getFileAbsFileName($driverpath['basePath']);
            foreach ($paths as $path) {
                if (CoreGeneralUtility::isFirstPartOfStr($prefix . $path, $driverpath) && is_dir($prefix . $path)) {
                    $templatePaths[] = ($relative ? $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] : $prefix) . $path;
                } else {
                    if (!$check) {
                        $templatePaths[] = ($relative ? $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] : $prefix) . $path;
                    }
                }
            }
        }

        return $templatePaths;
    }

    /**
     * Find and check all templates within the template paths
     *
     * @return array all relevant templates
     */
    protected function getTemplateFiles()
    {
        $paths = $this->getTemplatePaths();
        $files = array();
        foreach ($paths as $path) {
            $files = array_merge(CoreGeneralUtility::getAllFilesAndFoldersInPath(array(), $path . ((substr($path, -1) != '/') ? '/' : ''), 'html,htm,tmpl', 0), $files);
        }

        return $files;
    }

    public function getLinkParameters(array $extraParams = [])
    {
        return array_merge(
            [
                'id' => $this->id,
            ],
            $extraParams
        );
    }

    public function getBaseUrl(array $extraParams = [])
    {
        return BackendUtility::getModuleUrl(
            $this->moduleName,
            $this->getLinkParameters($extraParams)
        );
    }

    /**
     * Builds a bootstrap button for given url
     *
     * @param string $clickUrl
     * @param string $title
     * @param string $icon
     * @param string $text
     * @param string $buttonType Type of the html button, see bootstrap
     * @param string $extraClass Extra class names to add to the bootstrap button classes
     * @param string $rel Data for the rel attrib
     * @return string
     */
    public function buildButtonFromUrl(
        $clickUrl, $title, $icon, $text = '', $buttonType = 'default', $extraClass = '', $rel = null
    ) {
        return '<a href="#"' . ($rel ? ' rel="' . $rel . '"' : '')
            . ' class="btn btn-' . $buttonType . ' btn-sm' . ($extraClass ? ' ' . $extraClass : '') . '"'
            . ' onclick="' . $clickUrl . '" title="' . $title . '">'
            . $this->iconFactory->getIcon($icon, Icon::SIZE_SMALL)->render()
            . ($text ? ' ' . $text : '')
            . '</a>';
    }

    /**
     * Adds an icon button to the document header button bar (left or right)
     *
     * @param string $module Name of the module this icon should link to
     * @param string $title Title of the button
     * @param string $icon Name of the Icon (inside IconFactory)
     * @param array $params Array of parameters which should be added to module call
     * @param string $buttonPosition left|right to position button inside the bar
     * @param integer $buttonGroup Number of the group the icon should go in
     */
    public function addDocHeaderButton(
        $module,
        $title,
        $icon,
        array $params = [],
        $buttonPosition = ButtonBar::BUTTON_POSITION_LEFT,
        $buttonGroup = 1
    ) {
        $url = BackendUtility::getModuleUrl(
            $module,
            array_merge(
                $params,
                [
                    'returnUrl' => CoreGeneralUtility::getIndpEnv('REQUEST_URI'),
                ]
            )
        );

        $button = $this->buttonBar->makeLinkButton()
            ->setHref($url)
            ->setTitle($title)
            ->setIcon($this->iconFactory->getIcon($icon, Icon::SIZE_SMALL));
        $this->buttonBar->addButton($button, $buttonPosition, $buttonGroup);
    }
}
