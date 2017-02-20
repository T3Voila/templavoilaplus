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
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Extension\Templavoila\Utility\TemplaVoilaUtility;

$GLOBALS['LANG']->includeLLFile(
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('templavoila') . 'Resources/Private/Language/BackendTemplateMapping.xlf'
);

/**
 * Class for controlling the TemplaVoila module.
 *
 * @author Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @co-author Robert Lemke <robert@typo3.org>
 */
class BackendTemplateDisplayController extends \TYPO3\CMS\Backend\Module\BaseScriptClass
{

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
    protected $moduleName = 'templavoila_display';

    /**
     * holds the extconf configuration
     *
     * @var array
     */
    public $extConf;

    /**
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);
    }
    /**
     * Preparing menu content
     *
     * @return void
     */
    public function menuConfig()
    {
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
        $response->getBody()->write($this->content);
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
        // Setting GPvars:
        $displayFile = GeneralUtility::_GP('file');
        $show = GeneralUtility::_GP('show');
        $preview = GeneralUtility::_GP('preview');
        $limitTags = GeneralUtility::_GP('limitTags');
        $path = GeneralUtility::_GP('path');

        switch (GeneralUtility::_GP('mode')) {
            case 'explode':
            case 'source':
                $mode = GeneralUtility::_GP('mode');
                break;
            default:
                $mode = '';
        }

        // Checking if the displayFile parameter is set:
        if (@is_file($displayFile) && GeneralUtility::getFileAbsFileName($displayFile)) {
            $fileData = GeneralUtility::getUrl($displayFile);
            if ($fileData) {
                $relPathFix = $GLOBALS['BACK_PATH'] . '../' . dirname(substr($displayFile, strlen(PATH_site))) . '/';

                if ($this->preview) { // In preview mode, merge preview data into the template:
                    // Add preview data to file:
                    $this->content = $this->displayFileContentWithPreview($fileData, $relPathFix);
                } else {
                    // Markup file:
                    $this->content = $this->displayFileContentWithMarkup($fileData, $path, $relPathFix, $limitTags, $show, $mode);
                }
            } else {
                $this->displayFrameError(TemplaVoilaUtility::getLanguageService()->getLL('errorNoContentInFile') . ': <em>' . htmlspecialchars($displayFile) . '</em>');
            }
        } else {
            $this->displayFrameError(TemplaVoilaUtility::getLanguageService()->getLL('errorNoFileToDisplay'));
        }
    }

    /**
     * This will mark up the part of the HTML file which is pointed to by $path
     *
     * @param string $content The file content as a string
     * @param string $path The "HTML-path" to split by
     * @param string $relPathFix The rel-path string to fix images/links with.
     * @param string $limitTags List of tags to show
     *
     * @return string
     * @see main_display()
     */
    public function displayFileContentWithMarkup($content, $path, $relPathFix, $limitTags, $show, $mode)
    {
        $markupObj = GeneralUtility::makeInstance(\Extension\Templavoila\Domain\Model\HtmlMarkup::class);
        $markupObj->gnyfImgAdd = $show ? '' : 'onclick="return parent.updPath(\'###PATH###\');"';
        $markupObj->pathPrefix = $path ? $path . '|' : '';
        $markupObj->onlyElements = $limitTags;

        $cParts = $markupObj->splitByPath($content, $path);
        if (is_array($cParts)) {
            $cParts[1] = $markupObj->markupHTMLcontent(
                $cParts[1],
                $relPathFix,
                implode(',', array_keys($markupObj->tags)),
                $mode
            );
            $cParts[0] = $markupObj->passthroughHTMLcontent($cParts[0], $relPathFix, $mode);
            $cParts[2] = $markupObj->passthroughHTMLcontent($cParts[2], $relPathFix, $mode);
            if (trim($cParts[0])) {
                $cParts[1] = '<a name="_MARKED_UP_ELEMENT"></a>' . $cParts[1];
            }

            $markup = implode('', $cParts);
            $styleBlock = '<link media="all" href="/'
                . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('templavoila')
                . '/Resources/Public/StyleSheet/HtmlMarkup.css" type="text/css" rel="stylesheet" />'

                . '<link media="all" href="/'
                . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('templavoila')
                . '/Resources/Public/StyleSheet/cm1_default.css" type="text/css" rel="stylesheet" />'
                ;
            if (preg_match('/<\/head/i', $markup)) {
                $finalMarkup = preg_replace('/(<\/head)/i', $styleBlock . '\1', $markup);
            } else {
                $finalMarkup = $styleBlock . $markup;
            }

            return $finalMarkup;
        }
        $this->displayFrameError($cParts);

        return '';
    }

    /**
     * This will add preview data to the HTML file used as a template according to the currentMappingInfo
     *
     * @param string $content The file content as a string
     * @param string $relPathFix The rel-path string to fix images/links with.
     *
     * @return string
     * @see main_display()
     */
    public function displayFileContentWithPreview($content, $relPathFix)
    {
        // Getting session data to get currentMapping info:
        $sesDat = TemplaVoilaUtility::getBackendUser()->getSessionData($this->sessionKey);
        $currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : array();

        // Init mark up object.
        $this->markupObj = GeneralUtility::makeInstance(\Extension\Templavoila\Domain\Model\HtmlMarkup::class);
        $this->markupObj->htmlParse = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Html\HtmlParser::class);

        // Splitting content, adding a random token for the part to be previewed:
        $contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($content, $currentMappingInfo);
        $token = md5(microtime());
        $content = $this->markupObj->mergeSampleDataIntoTemplateStructure($sesDat['dataStruct'], $contentSplittedByMapping, $token);

        // Exploding by that token and traverse content:
        $pp = explode($token, $content);
        foreach ($pp as $kk => $vv) {
            $pp[$kk] = $this->markupObj->passthroughHTMLcontent($vv, $relPathFix, $this->MOD_SETTINGS['displayMode'], $kk == 1 ? 'font-size:11px; color:#000066;' : '');
        }

        // Adding a anchor point (will work in most cases unless put into a table/tr tag etc).
        if (trim($pp[0])) {
            $pp[1] = '<a name="_MARKED_UP_ELEMENT"></a>' . $pp[1];
        }
        // Implode content and return it:
        $markup = implode('', $pp);
        $styleBlock = '<link media="all" href="/'
            . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('templavoila')
            . '/Resources/Public/StyleSheet/HtmlMarkup.css" type="text/css" rel="stylesheet" />';
        if (preg_match('/<\/head/i', $markup)) {
            $finalMarkup = preg_replace('/(<\/head)/i', $styleBlock . '\1', $markup);
        } else {
            $finalMarkup = $styleBlock . $markup;
        }

        return $finalMarkup;
    }

    /**
     * Outputs a simple HTML page with an error message
     *
     * @param string Error message for output in <h2> tags
     *
     * @return void Echos out an HTML page.
     */
    public function displayFrameError($error)
    {
        echo '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
    <title>Untitled</title>
</head>

<body bgcolor="#eeeeee">
<h2>ERROR: ' . $error . '</h2>
</body>
</html>
            ';
    }
}
