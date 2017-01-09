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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Extension\Templavoila\Utility\TemplaVoilaUtility;

$GLOBALS['LANG']->includeLLFile(
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('templavoila') . 'Resources/Private/Language/BackendFlexformCleaner.xlf'
);

/**
 * Class for displaying color-marked-up version of FlexForm XML content.
 *
 * @author Kasper Skaarhoj <kasper@typo3.com>
 * @co-author Robert Lemke <robert@typo3.org>
 */
class BackendFlexformCleanerController extends \TYPO3\CMS\Backend\Module\BaseScriptClass
{
    /**
     * Array with tablename, uid and fieldname
     *
     * @var array
     */
    public $viewTable = [];

    /**
     * (GPvar "returnUrl") Return URL if the script is supplied with that.
     *
     * @var string
     */
    public $returnUrl = '';

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
    protected $moduleName = 'templavoila_flexform_cleaner';

    /**
     * Preparing menu content
     *
     * @return void
     */
    public function menuConfig()
    {
    }

    /**
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->moduleTemplate = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\ModuleTemplate::class);
        $this->iconFactory = $this->moduleTemplate->getIconFactory();
        $this->buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);
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
     * Main function, drawing marked up XML.
     *
     * @return void
     */
    public function main()
    {
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));

        // XML code:
        $this->viewTable = GeneralUtility::_GP('viewRec');

        $record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($this->viewTable['table'], $this->viewTable['uid']); // Selecting record based on table/uid since adding the field might impose a SQL-injection problem; at least the field name would have to be checked first.
        if (is_array($record)) {
            // Set current XML data:
            $currentXML = $record[$this->viewTable['field_flex']];

            // Clean up XML:
            $cleanXML = '';
            if (TemplaVoilaUtility::getBackendUser()->isAdmin()) {
                if ('tx_templavoila_flex' == $this->viewTable['field_flex']) {
                    $flexObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class);
                    if ($record['tx_templavoila_flex']) {
                        $cleanXML = $flexObj->cleanFlexFormXML($this->viewTable['table'], 'tx_templavoila_flex', $record);

                        // If the clean-button was pressed, save right away:
                        if (GeneralUtility::_POST('_CLEAN_XML')) {
                            $dataArr = array();
                            $dataArr[$this->viewTable['table']][$this->viewTable['uid']]['tx_templavoila_flex'] = $cleanXML;

                            // Init TCEmain object and store:
                            $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                            $tce->stripslashes_values = 0;
                            $tce->start($dataArr, array());
                            $tce->process_datamap();

                            // Re-fetch record:
                            $record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($this->viewTable['table'], $this->viewTable['uid']);
                            $currentXML = $record[$this->viewTable['field_flex']];
                        }
                    }
                }
            }

            if (md5($currentXML) != md5($cleanXML)) {
                // Create diff-result:
                $t3lib_diff_Obj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Utility\DiffUtility::class);
                $diffres = $t3lib_diff_Obj->makeDiffDisplay($currentXML, $cleanXML);

                $flashMessage = GeneralUtility::makeInstance(
                    \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                    TemplaVoilaUtility::getLanguageService()->getLL('needsCleaning', TRUE),
                    '',
                    \TYPO3\CMS\Core\Messaging\FlashMessage::INFO
                );
                $xmlContentMarkedUp = $flashMessage->render();

                $xmlContentMarkedUp .= '<table border="0">
                    <tr class="bgColor5 tableheader">
                        <td>' . TemplaVoilaUtility::getLanguageService()->getLL('current', TRUE) . '</td>
                    </tr>
                    <tr>
                        <td>' . $this->markUpXML($currentXML) . '<br/><br/></td>
                    </tr>
                    <tr class="bgColor5 tableheader">
                        <td>' . TemplaVoilaUtility::getLanguageService()->getLL('clean', TRUE) . '</td>
                    </tr>
                    <tr>
                        <td>' . $this->markUpXML($cleanXML) . '</td>
                    </tr>
                    <tr class="bgColor5 tableheader">
                        <td>' . TemplaVoilaUtility::getLanguageService()->getLL('diff', TRUE) . '</td>
                    </tr>
                    <tr>
                        <td>' . $diffres . '
                        <br/><br/><br/>

                        <form action="' . GeneralUtility::getIndpEnv('REQUEST_URI') . '" method="post">
                            <input type="submit" value="' . TemplaVoilaUtility::getLanguageService()->getLL('cleanUp', TRUE) . '" name="_CLEAN_XML" />
                        </form>

                        </td>
                    </tr>
                </table>

                ';
            } else {
                $xmlContentMarkedUp = '';
                if ($cleanXML) {
                    $flashMessage = GeneralUtility::makeInstance(
                        \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                        TemplaVoilaUtility::getLanguageService()->getLL('XMLclean', TRUE),
                        '',
                        \TYPO3\CMS\Core\Messaging\FlashMessage::OK
                    );
                    $xmlContentMarkedUp = $flashMessage->render();
                }
                $xmlContentMarkedUp .= $this->markUpXML($currentXML);
            }

            $this->content .= $xmlContentMarkedUp;
        }

        $title = TemplaVoilaUtility::getLanguageService()->getLL('title');
        $header = $this->moduleTemplate->header($title);
        $this->moduleTemplate->setTitle($title);

        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($record);
        $this->setDocHeaderButtons();

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
                ]
            )
            ->setSetVariables(array_keys($this->MOD_MENU));
        $this->buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * Adds a back button, if returnUrl exists
     */
    public function addBackButton()
    {
        if ($this->returnUrl) {
            $backButton = $this->buttonBar->makeLinkButton()
                ->setHref($this->returnUrl)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc'))
                ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $this->buttonBar->addButton($backButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
        }
    }

    /**
     * Mark up XML content
     *
     * @param string $str XML input
     *
     * @return string HTML formatted output, marked up in colors
     */
    public function markUpXML($str)
    {
        // Make instance of syntax highlight class:
        $hlObj = GeneralUtility::makeInstance(\Extension\Templavoila\Service\SyntaxHighlightingService::class);

        // Check which document type, if applicable:
        if (strstr(substr($str, 0, 100), '<T3DataStructure')) {
            $title = 'Syntax highlighting <T3DataStructure> XML:';
            $formattedContent = $hlObj->highLight_DS($str);
        } elseif (strstr(substr($str, 0, 100), '<T3FlexForms')) {
            $title = 'Syntax highlighting <T3FlexForms> XML:';
            $formattedContent = $hlObj->highLight_FF($str);
        } else {
            $title = 'Unknown format:';
            $formattedContent = '<span style="font-style: italic; color: #666666;">' . htmlspecialchars($str) . '</span>';
        }

        // Add line numbers
        $lines = explode(chr(10), $formattedContent);
        foreach ($lines as $k => $v) {
            $lines[$k] = '<span style="color: black; font-weight:normal;">' . str_pad($k + 1, 4, ' ', STR_PAD_LEFT) . ':</span> ' . $v;
        }
        $formattedContent = implode(chr(10), $lines);

        // Output:
        return '
            <h3>' . htmlspecialchars($title) . '</h3>
            <pre class="ts-hl">' . $formattedContent . '</pre>
            ';
    }
}
