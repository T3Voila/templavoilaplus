<?php
namespace Ppi\TemplaVoilaPlus\Domain\Model;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * HTML markup/search class; can mark up HTML with small images for each element AND
 * as well help you extract parts of the HTML based on a socalled 'PATH'.
 */
class HtmlMarkup
{
    /**
     * @var array
     */
    protected $rangeEndSearch;

    /**
     * @var array
     */
    protected $rangeStartPath;

    /**
     * Determines which mode is used for markup. Options are:
     *    'explode' In this mode A) container elementers (tables, tablecells...) are marked with borders and B) all the tag-images inserted are inserted 'relative' to the content which means no tag images can be layered over each other. Best mode if you want access to all elements (analytic) BUT it also spoils the page design the most of the options.
     *  'borders' In this mode container elementers (tables, tablecells...) are marked with borders
     *  'source'  In this mode all the HTML code is shown as source code. This mode should be used if you want code-insight OR analyse non-HTML code (like XML or WML)
     *  default      Original page is preserved and tag-images are added as layers (thus non-destructive). However tag-images may overlap each other so you cannot access the tag images you want.
     *
     * @var string
     */
    public $mode = '';

    /**
     * When in source mode the lines are truncated with "..." if longer than this number of characters.
     *
     * @var integer
     */
    public $maxLineLengthInSourceMode = 150;

    /**
     * The mode by which to detect the path of elements
     * true (1)    Default path detection; Will reset path by #id attributes. Will include class-attributes for elements. For the rest, its numeric.
     * false    No path detection applied
     *
     * Currently only one path mode is used. However the idea is if other path modes might be liked in the future. But for now, that is not the case.
     *
     * @var string
     */
    public $pathMode = '1';

    /**
     * Maximum recursions into the HTML code. Works as a break to avoid run-away function calls if that is potentially possible.
     *
     * @var integer
     */
    public $maxRecursion = 99;

    /**
     * Commalist of lowercase tag names which are the only ones which will be added as "GNYF" tag images. If empty, ALL HTML tags will have these elements.
     *
     * @var string
     */
    public $onlyElements = '';

    /**
     * Array with header section paths to set checkbox for.
     *
     * @var array
     */
    public $checkboxPathsSet = array(); //

    /**
     * This defines which tags can be exploded. Input lists of tags will be limited to those entered here.
     * You can override this array with an external setup in case you want to analyse non-HTML (XML or WML). For HTML you should probably keep these values.
     *
     * Notice that there is a distinction between "block elements" which has a begin AND end tag (eg. '<table>...</table>' or '<p>...</p>') and "single elements" which is stand-alone (eg. '<img>'). I KNOW that in XML '<p>..</p>' with no content can legally be '<p/>', however this class does not support that at the moment (and will in fact choke..)
     * For each element you can define and array with key/values;
     *   'single' => true                       Tells the parser that this tag is a single-tag, stand alone (eg. '<img>', '<input>' or '<br>')
     *   'anchor_outside' => true               (Block elements only) This means that the tag-image for this element will be placed outside of the block. Default is to place the image inside.
     *   'wrap' => array('before','after')       (Block elements only) This means that the tag-image for this element will be wrapped in those HTML codes before being placed. Notice how this is cleverly used to represent '<tr>...</tr>' blocks.
     *
     * @var array
     */
    static public $tagConf = [
        'a' => ['anchor_outside' => 1, 'blocktype' => 'text'],
        'abbr' => ['blocktype' => 'text'],
        'address' => ['blocktype' => 'sections'],
        'area' => ['blocktype' => 'embedding', 'single' => 1],
        'article' => ['blocktype' => 'sections'],
        'aside' => ['blocktype' => 'sections'],
        'audio' => ['blocktype' => 'embedding'],
        'b' => ['blocktype' => 'text'],
        'base' => ['blocktype' => 'document', 'single' => 1],
        'bdo' => ['blocktype' => 'text'],
        'blockquote' => ['blocktype' => 'grouping'],
        'body' => ['blocktype' => 'sections'],
        'br' => ['blocktype' => 'grouping', 'single' => 1],
        'button' => ['blocktype' => 'form'],
        'canvas' => ['blocktype' => 'embedding'],
        'caption' => ['blocktype' => 'table'],
        'cite' => ['blocktype' => 'text'],
        'code' => ['blocktype' => 'text'],
        'col' => ['blocktype' => 'table', 'single' => 1],
        'colgroup' => ['blocktype' => 'table'],
        'command' => ['blocktype' => 'interactive', 'single' => 1],
        'datalist' => ['blocktype' => 'form'],
        'dd' => ['blocktype' => 'grouping'],
        'del' => ['blocktype' => 'text'],
        'details' => ['blocktype' => 'interactive'],
        'device' => ['blocktype' => 'embedding'],
        'dfn' => ['blocktype' => 'text'],
        'div' => ['blocktype' => 'grouping'],
        'dl' => ['anchor_outside' => 1, 'blocktype' => 'grouping'],
        'dt' => ['blocktype' => 'grouping'],
        'em' => ['blocktype' => 'text'],
        'embed' => ['blocktype' => 'embedding', 'single' => 1],
        'fieldset' => ['anchor_outside' => 1, 'blocktype' => 'form'],
        'figcaption' => ['blocktype' => 'grouping'],
        'figure' => ['blocktype' => 'grouping'],
        'footer' => ['blocktype' => 'sections'],
        'form' => ['anchor_outside' => 1, 'blocktype' => 'form'],
        'h1' => ['blocktype' => 'sections'],
        'h2' => ['blocktype' => 'sections'],
        'h3' => ['blocktype' => 'sections'],
        'h4' => ['blocktype' => 'sections'],
        'h5' => ['blocktype' => 'sections'],
        'h6' => ['blocktype' => 'sections'],
        'head' => ['blocktype' => 'document'],
        'header' => ['blocktype' => 'sections'],
        'hgroup' => ['blocktype' => 'sections'],
        'hr' => ['blocktype' => 'grouping', 'single' => 1],
//        'html' => ['blocktype'=> 'root'],            -- can't be included since this might break mappings during the upgrade
        'i' => ['blocktype' => 'text'],
        'iframe' => ['anchor_outside' => 1, 'blocktype' => 'embedding'],
        'img' => ['blocktype' => 'embedding', 'single' => 1],
        'input' => ['blocktype' => 'form', 'single' => 1],
        'ins' => ['blocktype' => 'text'],
        'kbd' => ['blocktype' => 'text'],
        'keygen' => ['blocktype' => 'form'],
        'label' => ['blocktype' => 'form'],
        'legend' => ['blocktype' => 'form'],
        'li' => ['blocktype' => 'grouping'],
        'link' => ['blocktype' => 'document', 'single' => 1],
        'map' => ['anchor_outside' => 1, 'blocktype' => 'embedding'],
        'main' => ['blocktype' => 'sections'],
        'mark' => ['blocktype' => 'text'],
        'menu' => ['blocktype' => 'interactive'],
        'meta' => ['blocktype' => 'document', 'single' => 1],
        'meter' => ['blocktype' => 'form'],
        'nav' => ['blocktype' => 'sections'],
        'noscript' => ['blocktype' => 'document'],
        'object' => ['anchor_outside' => 1, 'blocktype' => 'embedding'],
        'ol' => ['anchor_outside' => 1, 'blocktype' => 'grouping'],
        'optgroup' => ['blocktype' => 'form'],
        'option' => ['anchor_outside' => 1, 'wrap' => ['</select>', '<select>'], 'blocktype' => 'form'],
        'output' => ['blocktype' => 'form'],
        'p' => ['blocktype' => 'grouping'],
        'param' => ['blocktype' => 'embedding', 'single' => 1],
        'pre' => ['blocktype' => 'grouping'],
        'progress' => ['blocktype' => 'form'],
        'q' => ['blocktype' => 'text'],
        'rp' => ['blocktype' => 'text'],
        'rt' => ['blocktype' => 'text'],
        'ruby' => ['blocktype' => 'text'],
        'samp' => ['blocktype' => 'text'],
        'script' => ['blocktype' => 'document'],
        'section' => ['blocktype' => 'sections'],
        'select' => ['anchor_outside' => 1, 'blocktype' => 'form'],
        'small' => ['blocktype' => 'text'],
        'source' => ['blocktype' => 'embedding', 'single' => 1],
        'span' => ['blocktype' => 'text'],
        'strong' => ['blocktype' => 'text'],
        'style' => ['blocktype' => 'document'],
        'sub' => ['blocktype' => 'text'],
        'summary' => ['blocktype' => 'interactive'],
        'sup' => ['blocktype' => 'text'],
        'table' => ['anchor_outside' => 1, 'blocktype' => 'table'],
        'tbody' => ['anchor_outside' => 1, 'blocktype' => 'table'],
        'td' => ['blocktype' => 'table'],
        'textarea' => ['anchor_outside' => 1, 'blocktype' => 'form'],
        'tfoot' => ['anchor_outside' => 1, 'blocktype' => 'table'],
        'th' => ['blocktype' => 'table'],
        'thead' => ['anchor_outside' => 1, 'blocktype' => 'table'],
        'time' => ['blocktype' => 'text'],
        'title' => ['blocktype' => 'document'],
        'tr' => ['blocktype' => 'table', 'wrap' => ['<td>', '</td>']],
        'track' => ['blocktype' => 'embedding'],
        'ul' => ['anchor_outside' => 1, 'blocktype' => 'grouping'],
        'var' => ['blocktype' => 'text'],
        'video' => ['blocktype' => 'embedding'],
        'wbr' => ['blocktype' => 'text'],
    ];

    /**
     * @var array
     */
    public $tags;

    /**
     * Will contain the HTML-parser object. (See init())
     *
     * @var \TYPO3\CMS\Core\Html\HtmlParser
     */
    public $htmlParse;

    /**
     * will contain style-part for gnyf images. (see init())
     *
     * @var string
     */
    public $gnyfStyle = '';

    /**
     * Eg. onclick="return parent.mod.updPath('###PATH###');"
     *
     * @var string
     */
    public $gnyfImgAdd = '';

    /**
     * Prefix for the path returned to the mod frame when tag image is clicked.
     *
     * @var string
     */
    public $pathPrefix = '';

    /**
     * @var string
     */
    public $tDat = '';

    /**
     * Used to register the paths during parsing the code (see init())
     *
     * @var array
     */
    public $elCountArray = array();

    /**
     * Used to register the all elements on the same level
     *
     * @var array
     */
    public $elParentLevel = array();

    /**
     * Used to contain the paths to search for when searching for a paths. (see getContentBasedOnPath())
     *
     * @var array
     */
    public $searchPaths = array();

    /**
     * @return HtmlMarkup
     */
    public function __construct()
    {
        // kept for compatibility reasons since references to this->tags are still present
        $this->tags = self::$tagConf;
    }

    /**
     * Marks up input HTML content string with tag-images based on the list in $showTags
     *
     * @param string $content HTML content
     * @param string $relPathFix The relative path from module position back to the HTML-file position; used to correct paths of HTML since the HTML is modified so it can display correctly from the path of the module using this class.
     * @param string $showTags Comma list of tags which should be exploded. Notice that tags in this list which does not appear in $this->tags will be ignored.
     * @param string $mode The mode of display; [blank], explode, borders. Set in $this->mode. "checkbox" is also an option, used for header data.
     *
     * @return string Modified HTML
     */
    public function markupHTMLcontent($content, $relPathFix, $showTags, $mode = '')
    {
        // Initialize:
        $this->mode = $mode;

        $this->init();

        list($tagList_elements, $tagList_single) = $this->splitTagTypes($showTags);

        // Fix links/paths
        if ($this->mode != 'source') {
            $content = $this->htmlParse->prefixResourcePath($relPathFix, $content);
        }

        // elements:
        $content = $this->recursiveBlockSplitting($content, $tagList_elements, $tagList_single, 'markup');

        // Wrap in <pre>-tags if source
        if ($this->mode == 'source') {
            $content = '<pre>' . $content . '</pre>';
        }

        return $content;
    }

    /**
     * Passes through input HTML content string BUT substitutes relative paths. Used to format the parts of the file which are NOT marked up with markupHTMLcontent()
     *
     * @param string $content HTML content
     * @param string $relPathFix The relative path from module position back to the HTML-file position; used to correct paths of HTML since the HTML is modified so it can display correctly from the path of the module using this class.
     * @param string $mode The mode of display; [blank], explode, borders. Set in $this->mode
     * @param string $altStyle Alternative CSS style value from the style attribute of the <pre></pre>-section
     *
     * @return string Modified HTML
     * @see markupHTMLcontent()
     */
    public function passthroughHTMLcontent($content, $relPathFix, $mode = '', $altStyle = '')
    {
        // Fix links/paths
        if ($mode != 'source') {
            $content = $this->htmlParse->prefixResourcePath($relPathFix, $content);
        }

        // Wrap in <pre>-tags if source
        if ($mode == 'source') {
            $content = '<pre style="' . htmlspecialchars($altStyle ? $altStyle : 'font-size:11px; color:#999999; font-style:italic;') . '">' . str_replace(chr(9), '    ', htmlspecialchars($content)) . '</pre>';
        }

        return $content;
    }

    /**
     * Returns content based on input $pathStrArray.    (an array with values which are paths to get out of HTML.)
     *
     * @param string $content Input HTML to get path from.
     * @param array $pathStrArr The array where the values are paths, eg. array('td#content table[1] tr[1]','td#content table[1]','map#cdf / INNER') - takes only the first level in a path!
     *
     * @return array Content... (not welldefined yet)
     */
    public function getContentBasedOnPath($content, $pathStrArr)
    {
        $this->init();
        $this->searchPaths = array();

        foreach ($pathStrArr as $pathStr) {
            list($pathInfo) = $this->splitPath($pathStr);
            $this->searchPaths[$pathInfo['path']] = $pathInfo;

            # 21/1 2005: Commented out because the line below is commented in...
            #$tagList.=','.$pathInfo['tagList'];
        }

        # 21/1 2005:  USING ALL TAGS (otherwise we may get those strange "lost" references - but I DON'T KNOW what may break because of this!!! It just seems that the taglist being used for the "search" should be the SAME as used for the MARKUP!
        $tagList = implode(',', array_keys($this->tags));

        list($tagsBlock, $tagsSolo) = $this->splitTagTypes($tagList);
        // sort array by key so that smallest keys are first - thus we don't get ... ???

        $newBase = $this->recursiveBlockSplitting($content, $tagsBlock, $tagsSolo, 'search');

        return array(
            'searchparts' => $this->searchPaths,
            'content' => $newBase,
            'md5_hashes' => array(md5($newBase), md5($content), md5($this->mergeSearchpartsIntoContent($newBase, $this->searchPaths))),
        );
    }

    /**
     * @param string $content
     * @param string $pathString
     *
     * @return array|string
     */
    public function splitByPath($content, $pathString)
    {
        $outArray = array('', $content, '');
        if ($pathString) {
            $pathInfo = $this->splitPath($pathString);
            foreach ($pathInfo as $v) {
                $contentP = $this->getContentBasedOnPath($outArray[1], array($v['fullpath']));
                $pathExtract = $contentP['searchparts'][$v['path']];
                if (isset($pathExtract['placeholder'])) {
                    $cSplit = explode($pathExtract['placeholder'], $contentP['content'], 2);

                    $outArray[0] .= $cSplit[0];
                    $outArray[2] = $cSplit[1] . $outArray[2];
                    $outArray[1] = $pathExtract['content'];
                } else {
                    return 'No placeholder found for path "' . $v['path'] . '"...';
                }
            }
        }

        return $outArray;
    }

    /**
     * @param string $fileContent
     * @param array $currentMappingInfo
     *
     * @return array
     */
    public function splitContentToMappingInfo($fileContent, $currentMappingInfo)
    {
        // Get paths into an array
        $paths = $this->mappingInfoToSearchPath($currentMappingInfo);
        // Split content by the paths.
        $divContent = $this->getContentBasedOnPath($fileContent, $paths);

        // Token for splitting the content further.
        $token = md5(microtime());

        // Replacing all placeholders with the keys from $currentMappingInfo, wrapped in the new token.
        $divContent['content'] = $this->mergeSearchpartsIntoContent($divContent['content'], $divContent['searchparts'], $token);

        // Exploding the new content by the new token; result is an array where all odd key values contain the key name to insert.
        $cP = explode($token, $divContent['content']);

        $newArray = array();
        $newArray['cArray'] = array();
        $newArray['sub'] = array();
        foreach ($cP as $k => $v) {
            if ($k % 2) {
                // Based on the path, find the element in 'searchparts':
                list($pathInfo) = $this->splitPath($v);
                if ($pathInfo['modifier'] == 'ATTR') {
                    $lC = $divContent['searchparts'][$pathInfo['path']]['attr'][$pathInfo['modifier_value']]['content'];
                } else {
                    $lC = $divContent['searchparts'][$pathInfo['path']]['content'];
                }

                // Looking for the key in the currentMappingInfo array:
                $theKeyFound = '';
                foreach ($currentMappingInfo as $key => $val) {
                    if ($val['MAP_EL'] && $val['MAP_EL'] == $v) {
                        $theKeyFound = $key;
                        break;
                    }
                }

                if (!isset($newArray['cArray'][$theKeyFound])) {
                    $newArray['cArray'][$theKeyFound] = $lC;
                    $elements = $currentMappingInfo[$theKeyFound]['el'];
                    if (!is_array($elements)) {
                        // FCE without any child elements
                        $elements = array();
                    }
                    $newArray['sub'][$theKeyFound] = $this->splitContentToMappingInfo($lC, $elements);
                } else {
                    $newArray['cArray'][$k] = $lC;
                }
            } else {
                $newArray['cArray'][$k] = $v;
            }
        }

        return $newArray;
    }

    /**
     * @param array $currentMappingInfo
     *
     * @return array
     */
    public function mappingInfoToSearchPath($currentMappingInfo)
    {
        $paths = array();
        $pathsArrays = array();

        // Post processing, putting together all duplicate data in arrays which are easy to traverse in the next run.
        foreach ($currentMappingInfo as $val) {
            if ($val['MAP_EL']) {
                list($pathInfo) = $this->splitPath($val['MAP_EL']);
                $pathsArrays[$pathInfo['path']][$pathInfo['modifier']][] = $pathInfo['modifier_value'];
            }
        }

        // traverse the post-processed data:
        foreach ($pathsArrays as $k => $v) {
            if (is_array($v['INNER'])) {
                if (is_array($v['ATTR'])) {
                    $paths[] = $k . ' / INNER+ATTR:' . implode(',', $v['ATTR']);
                } else {
                    $paths[] = $k . ' / INNER';
                }
            } elseif (is_array($v['ATTR'])) {
                $paths[] = $k . ' / ATTR:' . implode(',', $v['ATTR']);
            } elseif (is_array($v['RANGE'])) {
                $paths[] = $k . ' / RANGE:' . $v['RANGE'][0];
            } else {
                $paths[] = $k; // OUTER is default...
            }
        }

        return $paths;
    }

    /**
     * Substitutes all placeholders in $content string which are found in the $searchParts array (see syntax from getContentBasedOnPath())
     *
     * @param string $content Content string with markers
     * @param array $searchParts Array with searchPaths which has been modified by $this->recursiveBlockSplitting in search mode to contain content and subparts.
     * @param string $token
     *
     * @return string HTML .
     */
    public function mergeSearchpartsIntoContent($content, $searchParts, $token = '')
    {
        foreach ($searchParts as $path => $pathInfo) {
            if ($pathInfo['placeholder']) {
                $content = str_replace(
                    $pathInfo['placeholder'],
                    $token ? $token . $path . $pathInfo['modifier_lu'] . $token : $pathInfo['content'],
                    $content
                );
            }
            if (is_array($pathInfo['attr'])) {
                foreach ($pathInfo['attr'] as $attrN => $pcPair) {
                    $content = str_replace(
                        $pcPair['placeholder'],
                        $token ? $token . $path . '/ATTR:' . $attrN . $token : $pcPair['content'],
                        $content
                    );
                }
            }
        }

        return $content;
    }

    /**
     * @param array $dataStruct
     * @param array $currentMappingInfo
     * @param string $firstLevelImplodeToken
     * @param string $sampleOrder
     *
     * @return string
     */
    public function mergeSampleDataIntoTemplateStructure(
        $dataStruct,
        $currentMappingInfo,
        $firstLevelImplodeToken = '',
        $sampleOrder = ''
    ) {
        foreach ($currentMappingInfo['cArray'] as $key => $val) {
            if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($key) && $dataStruct[$key]) {
                if ($dataStruct[$key]['type'] == 'array') {
                    if (is_array($currentMappingInfo['sub'][$key])) {
                        $currentMappingInfo['cArray'][$key] = $this->mergeSampleDataIntoTemplateStructure(
                            $dataStruct[$key]['el'],
                            $currentMappingInfo['sub'][$key],
                            '',
                            ($dataStruct[$key]['section'] ?
                                (is_array($dataStruct[$key]['tx_templavoilaplus']['sample_order']) ? $dataStruct[$key]['tx_templavoilaplus']['sample_order'] : array_keys($dataStruct[$key]['el'])) :
                                '')
                        );
                    }
                } else {
                    if (is_array($dataStruct[$key]['tx_templavoilaplus']['sample_data'])) {
                        $point = rand(0, count($dataStruct[$key]['tx_templavoilaplus']['sample_data']) - 1);
                        $sample = $dataStruct[$key]['tx_templavoilaplus']['sample_data'][$point];
                    } else {
                        $sample = '[SAMPLE DATA]';
                    }
                    $currentMappingInfo['cArray'][$key] = $sample;
                }
            }
        }

        if (is_array($sampleOrder)) {
            $out = '';
            foreach ($sampleOrder as $pointer) {
                $out .= $currentMappingInfo['cArray'][$pointer];
            }
        } else {
            $out = implode($firstLevelImplodeToken, $currentMappingInfo['cArray']);
        }

        return $out;
    }

    /**
     * @param array $editStruct
     * @param array $currentMappingInfo
     * @param string $firstLevelImplodeToken
     * @param string $valueKey
     *
     * @return string
     */
    public function mergeFormDataIntoTemplateStructure(
        $editStruct,
        $currentMappingInfo,
        $firstLevelImplodeToken = '',
        $valueKey = 'vDEF'
    ) {
        $isSection = 0;
        $htmlParse = ($this->htmlParse ? $this->htmlParse : GeneralUtility::makeInstance(\TYPO3\CMS\Core\Html\HtmlParser::class));
        if (is_array($editStruct) && count($editStruct)) {
        	if (version_compare(TYPO3_version, '8.6.0', '>=')) {
        		if(isset(current($editStruct)['_TOGGLE'])) $isSection = true;
        	}
        	else {
        		$testInt = implode('', array_keys($editStruct));
        		$isSection = !preg_match('/[^0-9]/', $testInt);
        	}
        }
        $out = '';
        if ($isSection) {
            foreach ($editStruct as $section) {
                if (is_array($section)) {
                    $secKey = key($section);
                    $secDat = $section[$secKey];
                    if ($currentMappingInfo['sub'][$secKey]) {
                        $out .= $this->mergeFormDataIntoTemplateStructure($secDat['el'], $currentMappingInfo['sub'][$secKey], '', $valueKey);
                    }
                }
            }
        } else {
            if (is_array($currentMappingInfo['cArray'])) {
                foreach ($currentMappingInfo['cArray'] as $key => $val) {
                    if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($key)) {
                        if (is_array($editStruct[$key]['el']) && $currentMappingInfo['sub'][$key]) {
                            $currentMappingInfo['cArray'][$key] = $this->mergeFormDataIntoTemplateStructure($editStruct[$key]['el'], $currentMappingInfo['sub'][$key], '', $valueKey);
                        } else {
                            # NO htmlspecialchars()'ing here ... it might be processed values that should be allowed to go through easily.
                            $currentMappingInfo['cArray'][$key] = $editStruct[$key][$valueKey];
                        }
                    } else {
                        $currentMappingInfo['cArray'][$key] = $htmlParse->HTMLcleaner($currentMappingInfo['cArray'][$key], [], true);
                    }
                }
                $out = implode($firstLevelImplodeToken, $currentMappingInfo['cArray']);
            }
        }

        return $out;
    }

    /**
     * Processing of a path; It splits the path by tokens like "|", "/" and " " etc and returns an array with path-levels and properties etc.
     *
     * @param string $pathStr The total path string to explode into smaller units.
     *
     * @return array Array with the information inside.
     */
    public function splitPath($pathStr)
    {
        $subPaths = GeneralUtility::trimExplode('|', $pathStr, 1);

        foreach ($subPaths as $index => $path) {
            $subPaths[$index] = array();
            $subPaths[$index]['fullpath'] = $path;

            // Get base parts of the page: the PATH and the COMMAND
            list($thePath, $theCmd) = GeneralUtility::trimExplode('/', $path, 1);

            // Split the path part into its units: results in an array with path units.
            $splitParts = preg_split('/\s+/', $thePath);

            // modifier:
            $modArr = GeneralUtility::trimExplode(':', $theCmd, 1);
            if ($modArr[0]) {
                $subPaths[$index]['modifier'] = $modArr[0];
                $subPaths[$index]['modifier_value'] = $modArr[1];
                if (strstr($modArr[0], 'INNER')) {
                    $subPaths[$index]['modifier_lu'] = '/INNER';
                } elseif ($modArr[0] == 'RANGE') {
                    $subPaths[$index]['modifier_lu'] = '/RANGE:' . $modArr[1];
                } else {
                    $subPaths[$index]['modifier_lu'] = ''; // Outer.
                }
            }

            // Tag list
            $tagIndex = array();
            $tagSplitParts = $splitParts;
            if ($subPaths[$index]['modifier'] == 'RANGE' && $subPaths[$index]['modifier_value']) {
                $tagSplitParts[] = $subPaths[$index]['modifier_value'];
            }
            foreach ($tagSplitParts as $tagV) {
                list($tagName) = preg_split('/[^a-zA-Z0-9_-]/', $tagV);
                $tagIndex[$tagName]++;
            }
            $subPaths[$index]['tagList'] = implode(',', array_keys($tagIndex));

            // Setting "path" and "parent"
            $subPaths[$index]['path'] = implode(' ', $splitParts); // Cleaning up the path
            list($elName) = preg_split('/[^a-zA-Z0-9_-]/', end($splitParts));
            $subPaths[$index]['el'] = $elName;
            array_pop($splitParts); // Removing last item to get parent.
            $subPaths[$index]['parent'] = implode(' ', $splitParts); // Cleaning up the path
        }

        return $subPaths;
    }

    /**
     * For use in both frontend and backend
     *
     * @param integer $uid
     *
     * @return string|boolean
     */
    public function getTemplateArrayForTO($uid)
    {
        global $TCA;
        if (isset($TCA['tx_templavoilaplus_tmplobj'])) {
            $res = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTquery(
                '*',
                'tx_templavoilaplus_tmplobj',
                'uid=' . (int)$uid . ($TCA['tx_templavoilaplus_tmplobj']['ctrl']['delete'] ? ' AND NOT ' . $TCA['tx_templavoilaplus_tmplobj']['ctrl']['delete'] : '')
            );
            $row = TemplaVoilaUtility::getDatabaseConnection()->sql_fetch_assoc($res);
            $this->tDat = unserialize($row['templatemapping']);

            return $this->tDat['MappingData_cached'];
        }

        return false;
    }

    /**
     * @param array $TA
     * @param array $data
     *
     * @return string|boolean
     */
    public function mergeDataArrayToTemplateArray($TA, $data)
    {
        if (is_array($TA['cArray'])) {
            foreach ($data as $key => $value) {
                if (isset($TA['cArray'][$key])) {
                    $TA['cArray'][$key] = $value;
                }
            }

            return implode('', $TA['cArray']);
        }

        return false;
    }

    /**
     * Returns the right template record for the current display
     * Requires the extension "TemplaVoila"
     *
     * @param integer $uid The UID of the template record
     * @param string $renderType
     * @param integer $langUid
     *
     * @return mixed The record array or <code>false</code>
     */
    public function getTemplateRecord($uid, $renderType, $langUid)
    {
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('templavoilaplus')) {
            $rec = $GLOBALS['TSFE']->sys_page->checkRecord('tx_templavoilaplus_tmplobj', $uid);
            $parentUid = $rec['uid'];
            $rendertype_ref = $rec['rendertype_ref'] ? $GLOBALS['TSFE']->sys_page->checkRecord('tx_templavoilaplus_tmplobj', $rec['rendertype_ref']) : false;

            if (is_array($rec)) {
                if ($renderType) { // If print-flag try to find a proper print-record. If the lang-uid is also set, try to find a combined print/lang record, but if not found, the print rec. will take precedence.

                    // Look up print-row for default language:
                    $printRow = $this->getTemplateRecord_query($parentUid, 'AND rendertype=' . TemplaVoilaUtility::getDatabaseConnection()->fullQuoteStr($renderType, 'tx_templavoilaplus_tmplobj') . ' AND sys_language_uid=0');
                    if (is_array($printRow)) {
                        $rec = $printRow;
                    } elseif ($rendertype_ref) { // Look in rendertype_ref record:
                        $printRow = $this->getTemplateRecord_query($rendertype_ref['uid'], 'AND rendertype=' . TemplaVoilaUtility::getDatabaseConnection()->fullQuoteStr($renderType, 'tx_templavoilaplus_tmplobj') . ' AND sys_language_uid=0');
                        if (is_array($printRow)) {
                            $rec = $printRow;
                        }
                    }

                    if ($langUid) { // If lang_uid is set, try to look up for current language:
                        $printRow = $this->getTemplateRecord_query($parentUid, 'AND rendertype=' . TemplaVoilaUtility::getDatabaseConnection()->fullQuoteStr($renderType, 'tx_templavoilaplus_tmplobj') . ' AND sys_language_uid=' . (int)$langUid);
                        if (is_array($printRow)) {
                            $rec = $printRow;
                        } elseif ($rendertype_ref) { // Look in rendertype_ref record:
                            $printRow = $this->getTemplateRecord_query($rendertype_ref['uid'], 'AND rendertype=' . TemplaVoilaUtility::getDatabaseConnection()->fullQuoteStr($renderType, 'tx_templavoilaplus_tmplobj') . ' AND sys_language_uid=' . (int)$langUid);
                            if (is_array($printRow)) {
                                $rec = $printRow;
                            }
                        }
                    }
                } elseif ($langUid) { // If the language uid is set, then try to find a regular record with sys_language_uid
                    $printRow = $this->getTemplateRecord_query($parentUid, 'AND rendertype=\'\' AND sys_language_uid=' . (int)$langUid);
                    if (is_array($printRow)) {
                        $rec = $printRow;
                    } elseif ($rendertype_ref) { // Look in rendertype_ref record:
                        $printRow = $this->getTemplateRecord_query($rendertype_ref['uid'], 'AND rendertype=\'\' AND sys_language_uid=' . (int)$langUid);
                        if (is_array($printRow)) {
                            $rec = $printRow;
                        }
                    }
                }
            }

            return $rec;
        }

        return false;
    }

    /**
     * @param integer $uid
     * @param string $renderType
     * @param integer $langUid
     * @param string $sheet
     *
     * @return mixed
     */
    public function getTemplateMappingArray($uid, $renderType, $langUid, $sheet)
    {
        $row = $this->getTemplateRecord($uid, $renderType, $langUid);
        $tDat = unserialize($row['templatemapping']);

        return $sheet ? $tDat['MappingData_cached']['sub'][$sheet] : $tDat['MappingData_cached'];
    }

    /**
     * Helper function to build the query for searching print/language templates.
     *
     * @param integer $uid The UID of the template record
     * @param string $where The where clause.
     *
     * @return mixed An array if a record is found, otherwise null
     * @access private
     * @see getTemplateRecord()
     */
    public function getTemplateRecord_query($uid, $where)
    {
        global $TSFE;

        $res = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTquery(
            '*',
            'tx_templavoilaplus_tmplobj',
            'parent=' . (int)$uid . ' ' . $where . $TSFE->sys_page->enableFields('tx_templavoilaplus_tmplobj')
        );
        $printRow = TemplaVoilaUtility::getDatabaseConnection()->sql_fetch_assoc($res);

        return $printRow;
    }

    /**
     * Will set header content and BodyTag for template.
     *
     * @param array $MappingInfo_head ...
     * @param array $MappingData_head_cached ...
     * @param string $BodyTag_cached ...
     * @param boolean $pageRenderer try to use the pageRenderer for script and style inclusion
     *
     * @return void
     */
    public function setHeaderBodyParts(
        $MappingInfo_head,
        $MappingData_head_cached,
        $BodyTag_cached = '',
        $pageRenderer = false
    ) {
        if (is_array($MappingInfo_head)) {
            $htmlParse = ($this->htmlParse ? $this->htmlParse : GeneralUtility::makeInstance(\TYPO3\CMS\Core\Html\HtmlParser::class));
            /* @var $htmlParse \TYPO3\CMS\Core\Html\HtmlParser */

            $types = array(
                'LINK' => 'text/css',
                'STYLE' => 'text/css',
                'SCRIPT' => 'text/javascript'
            );

            // Traversing mapped header parts:
            if (array_key_exists('headElementPaths', $MappingInfo_head)
                && is_array($MappingInfo_head['headElementPaths'])) {
                $extraHeaderData = array();
                foreach (array_keys($MappingInfo_head['headElementPaths']) as $kk) {
                    if (isset($MappingData_head_cached['cArray']['el_' . $kk])) {
                        $tag = strtoupper($htmlParse->getFirstTagName($MappingData_head_cached['cArray']['el_' . $kk]));
                        $attr = $htmlParse->get_tag_attributes($MappingData_head_cached['cArray']['el_' . $kk]);
                        if (isset($GLOBALS['TSFE']) &&
                            $pageRenderer &&
                            isset($attr[0]['type']) &&
                            isset($types[$tag]) &&
                            $types[$tag] == $attr[0]['type']
                        ) {
                            $name = 'templavoila#' . md5($MappingData_head_cached['cArray']['el_' . $kk]);
                            /** @var \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer */
                            $pageRenderer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
                            switch ($tag) {
                                case 'LINK':
                                    $rel = isset($attr[0]['rel']) ? $attr[0]['rel'] : 'stylesheet';
                                    $media = isset($attr[0]['media']) ? $attr[0]['media'] : 'all';
                                    $pageRenderer->addCssFile($attr[0]['href'], $rel, $media);
                                    break;
                                case 'STYLE':
                                    $cont = $htmlParse->removeFirstAndLastTag($MappingData_head_cached['cArray']['el_' . $kk]);
                                    if ($GLOBALS['TSFE']->config['config']['inlineStyle2TempFile']) {
                                        $pageRenderer->addCssFile(\TYPO3\CMS\Frontend\Page\PageGenerator::inline2TempFile($cont, 'css'));
                                    } else {
                                        $pageRenderer->addCssInlineBlock($name, $cont);
                                    }
                                    break;
                                case 'SCRIPT':
                                    if (isset($attr[0]['src']) && $attr[0]['src']) {
                                        $pageRenderer->addJsFile($attr[0]['src']);
                                    } else {
                                        $cont = $htmlParse->removeFirstAndLastTag($MappingData_head_cached['cArray']['el_' . $kk]);
                                        $pageRenderer->addJsInlineCode($name, $cont);
                                    }
                                    break;
                                default:
                                    // can't happen due to condition
                            }
                        } else {
                            $uKey = md5(trim($MappingData_head_cached['cArray']['el_' . $kk]));
                            $extraHeaderData['TV_' . $uKey]
                                = chr(10) . chr(9)
                                . trim(
                                    $htmlParse->HTMLcleaner(
                                        $MappingData_head_cached['cArray']['el_' . $kk],
                                        [],
                                        1,
                                        0,
                                        ['xhtml' => 1]
                                    )
                                );
                        }
                    }
                }
                // Set 'page.headerData', use the lowest possible free index!
                // This will make sure that header data appears the very first on the page
                // but unfortunately after styles from extensions
                for ($i = 1; $i < PHP_INT_MAX; $i++) {
                    if (!isset($GLOBALS['TSFE']->pSetup['headerData.'][$i])) {
                        $GLOBALS['TSFE']->pSetup['headerData.'][$i] = 'TEXT';
                        $GLOBALS['TSFE']->pSetup['headerData.'][$i . '.']['value'] = implode('', $extraHeaderData) . chr(10);
                        break;
                    }
                }
                // Alternative way is to prepend it additionalHeaderData but that
                // will still put JS/CSS after any page.headerData. So this code is
                // kept commented here.
                //$GLOBALS['TSFE']->additionalHeaderData = $extraHeaderData + $GLOBALS['TSFE']->additionalHeaderData;
            }

            // Body tag:
            if ($MappingInfo_head['addBodyTag'] && $BodyTag_cached) {
                $GLOBALS['TSFE']->defaultBodyTag = $BodyTag_cached;
            }
        }
    }

    /**
     *
     * Various sub processing
     *
     */

    /**
     * Init function, should be called by the processing functions above before doing any recursive parsing of the HTML code.
     *
     * @return void
     */
    public function init()
    {
        // HTML parser object initialized.
        $this->htmlParse = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Html\HtmlParser::class);
        /* @var $this ->htmlParse \TYPO3\CMS\Core\Html\HtmlParser */

        // Resetting element count array
        $this->elCountArray = array();
        $this->elParentLevel = array();

        // Setting gnyf style
        $style = '';
        $style .= (!GeneralUtility::inList('explode,checkbox', $this->mode) ? 'position:absolute;' : '');
        $this->gnyfStyle = $style ? ' style="' . htmlspecialchars($style) . '"' : '';
    }

    /**
     * Takes the input list of tags to markup and validates it against $this->tags array.
     * Returns an array with two strings, the list of block tags and the list of single tags.
     *
     * @param string $showTags Comma list of tags, input to processing functions in top of class.
     *
     * @return array array with two strings, the list of block tags and the list of single tags.
     */
    public function splitTagTypes($showTags)
    {
        $showTagsArr = GeneralUtility::trimExplode(',', strtolower($showTags), 1);
        $showTagsArr = array_flip($showTagsArr);
        $tagList_elements = array();
        $tagList_single = array();

        foreach ($this->tags as $tagname => $tagconfig) {
            if (isset($showTagsArr[$tagname])) {
                if ($tagconfig['single']) {
                    $tagList_single[] = $tagname;
                } else {
                    $tagList_elements[] = $tagname;
                }
            }
        }

        return array(implode(',', $tagList_elements), implode(',', $tagList_single));
    }

    /**
     *
     * SPLITTING functions
     *
     */

    /**
     * Main splitting function - will split the input $content HTML string into sections based on the strings with tags, $tagsBlock and $tagsSolo
     * WARNING: No currect support for XML-ended tags, eg. <p/>. In fact there is not even support for block tags like <p> which does not have a counter part ending it!!! (This support must come from the htmlparser class btw.)
     *
     * @param string $content HTML content
     * @param string $tagsBlock list of block tags; which has a start and end (eg. <p>...</p>, <table>...</table>, <tr>...</tr>, <div>...</div>)
     * @param string $tagsSolo list of solo (single) tags; which are stand-alone (eg. <img>, <br>, <hr>, <input>)
     * @param string $mode Denotes which mode of operation to apply: 'markup' will markup the html, 'search' will return HTML code with markers inserted for the found paths. Default does nothing.
     * @param string $path Used to accumulate the tags 'path' in the document
     * @param integer $recursion Used internally to control recursion.
     *
     * @return string HTML
     */
    public function recursiveBlockSplitting($content, $tagsBlock, $tagsSolo, $mode, $path = '', $recursion = 0)
    {
        // Splitting HTML string by all block-tags
        $blocks = $this->htmlParse->splitIntoBlock($tagsBlock, $content, 1);
        $this->rangeEndSearch[$recursion] = '';
        $this->rangeStartPath[$recursion] = '';

        $startCCTag = $endCCTag = '';

        //pre-processing of blocks
        if ((GeneralUtility::inList($tagsBlock, 'script') && GeneralUtility::inList($tagsBlock, 'style')) && count($blocks) > 1) {
            // correct the blocks (start of CC could be in prior block, end of CC in net block)

            if (count($blocks) > 1) {
                foreach ($blocks as $key => $block) {
                    // possible that CC for style start end of block
                    $matchCount1 = preg_match_all('/<!([-]+)?\[if(.+)\]([-]+)?>(<!-->)?/', $block, $matches1);
                    $matchCount2 = preg_match_all('/(<!-->)?<!([-]+)?\[endif\]([-]+)?>/', $block, $matches2);
                    if ($matchCount2 < $matchCount1) {
                        $startCCTag = $matches1[0][$matchCount1 - 1];
                        //endtag is start of block3
                        $endCCTag = $matches2[0][0];
                        //manipulate blocks
                        $blocks[$key] = substr(rtrim($block), 0, -1 * strlen($startCCTag));
                        $blocks[$key + 1] = $startCCTag . chr(10) . trim($blocks[$key + 1]) . chr(10) . $endCCTag;
                        $blocks[$key + 2] = substr(ltrim($blocks[$key + 2]), strlen($endCCTag));
                    }
                }
            }
        }

        // Traverse all sections of blocks
        foreach ($blocks as $k => $v) { // INSIDE BLOCK: Processing of block content. This includes a recursive call to this function for the inner content of the block tags.
            // If inside a block tag
            if ($k % 2) {
                $firstTag = $this->htmlParse->getFirstTag($v); // The first tag's content
                $firstTagName = strtolower($this->htmlParse->getFirstTagName($v)); // The 'name' of the first tag
                $endTag = $firstTag == $startCCTag ? $endCCTag : '</' . $firstTagName . '>'; // Create proper end-tag
                $v = $this->htmlParse->removeFirstAndLastTag($v); // Finally remove the first tag (unless we do this, the recursivity will be eternal!
                $params = $this->htmlParse->get_tag_attributes($firstTag, 1); // Get attributes

                // IF pathMode is set:
                $subPath = $this->makePath($path, $firstTagName, $params[0]);

                // Make the call again - recursively.
                if ($recursion < $this->maxRecursion && !($mode == 'search' && isset($this->searchPaths[$subPath]) && ($this->searchPaths[$subPath]['modifier'] != 'ATTR'))) {
                    $v = $this->recursiveBlockSplitting($v, $tagsBlock, $tagsSolo, $mode, $subPath, $recursion + 1);
                }

                if ($mode == 'markup') {
                    $v = $this->getMarkupCode('block', $v, $params, $firstTagName, $firstTag, $endTag, $subPath, $recursion);
                } elseif ($mode == 'search') {
                    $v = $this->getSearchCode('block', $v, $params, $firstTagName, $firstTag, $endTag, $subPath, $path, $recursion);
                } else {
                    $v = $firstTag . $v . $endTag;
                }
            } else {
                if ($tagsSolo) { // OUTSIDE of block; Processing of SOLO tags in there...

                    // Split content by the solo tags
                    $soloParts = $this->htmlParse->splitTags($tagsSolo, $v);

                    //search for conditional comments
                    $startTag = '';
                    if (count($soloParts) > 1 && $recursion == 0) {
                        foreach ($soloParts as $key => $value) {
                            //check for downlevel-hidden and downlevel-revealed syntax, see http://msdn.microsoft.com/de-de/library/ms537512(en-us,VS.85).aspx
                            $matchCount1 = preg_match_all('/<!([-]+)?\[if(.+)\]([-]+)?>(<!-->)?/', $value, $matches1);
                            $matchCount2 = preg_match_all('/(<!--)?<!([-]+)?\[endif\]([-]+)?>/', $value, $matches2);

                            // startTag was in last element
                            if ($startTag) {
                                $soloParts[$key] = $startTag . chr(10) . $soloParts[$key];
                                $startTag = '';
                            }
                            // starttag found: store and remove from element
                            if ($matchCount1) {
                                $startTag = $matches1[0][0];
                                $soloParts[$key] = str_replace($startTag, '', $soloParts[$key]);
                            }
                            // endtag found: store in last element and remove from element
                            if ($matchCount2) {
                                $soloParts[$key] = str_replace($matches2[0][0], '', $soloParts[$key]);
                                if ($key > 0) {
                                    $soloParts[$key - 1] .= chr(10) . $matches2[0][0];
                                } else {
                                    #$soloParts = array_merge(array(chr(10) . $matches2[0][0]), $soloParts);
                                }
                            }
                        }
                    }

                    // Traverse solo tags
                    foreach ($soloParts as $kk => $vv) {
                        if ($kk % 2) {
                            $firstTag = $vv; // The first tag's content
                            $firstTagName = strtolower($this->htmlParse->getFirstTagName($vv)); // The 'name' of the first tag
                            $params = $this->htmlParse->get_tag_attributes($firstTag, 1);

                            // Get path for THIS element:
                            $subPath = $this->makePath($path, $firstTagName, $params[0]);

                            if ($mode == 'markup') {
                                $vv = $this->getMarkupCode('', $vv, $params, $firstTagName, $firstTag, '', $subPath, $recursion + 1);
                            } elseif ($mode == 'search') {
                                $vv = $this->getSearchCode('', $vv, $params, $firstTagName, '', '', $subPath, $path, $recursion);
                            }
                        } elseif ($this->mode == 'source' && $mode == 'markup') {
                            $vv = $this->sourceDisplay($vv, $recursion, '', 1);
                        } elseif ($this->mode == 'checkbox') {
                            $vv = $this->checkboxDisplay($vv, $recursion, '', '', 1);
                        } elseif ($mode == 'search' && $this->rangeEndSearch[$recursion]) {
                            $this->searchPaths[$this->rangeStartPath[$recursion]]['content'] .= $vv;
                            $vv = '';
                        }
                        $soloParts[$kk] = $vv;
                    }
                    $v = implode('', $soloParts);
                }
            }
            $blocks[$k] = $v;
        }

        // Implode and return all blocks
        return implode('', $blocks);
    }

    /**
     * In markup mode, this function is used to add the gnyf image to the HTML plus set all necessary attributes etc in order to mark up the code visually.
     *
     * @param string $mode Element type: block or '' (single/solo)
     * @param string $v Sub HTML code.
     * @param array $params Attributes of the current tag
     * @param string $firstTagName Current tags name (lowercase)
     * @param string $firstTag Current tag, full
     * @param string $endTag End tag for the current tag
     * @param string $subPath Current path of element
     * @param integer $recursion The recursion number
     *
     * @return string Modified sub HTML code ($v)
     */
    public function getMarkupCode($mode, $v, $params, $firstTagName, $firstTag, $endTag, $subPath, $recursion)
    {
        // Get gnyf:
        $attrInfo = '';
        if ($params[0]['class']) {
            $attrInfo .= ' CLASS="' . $params[0]['class'] . '"';
        }
        if ($params[0]['id']) {
            $attrInfo .= ' ID="' . $params[0]['id'] . '"';
        }
        $gnyf = $this->getGnyf($firstTagName, $subPath, $subPath . ($attrInfo ? ' - ' . $attrInfo : ''));

        if ($mode == 'block') {
            // Disable A tags:
            if ($firstTagName == 'a') {
                $params[0]['onclick'] = 'return false;';
                $firstTag = '<' . trim($firstTagName . ' ' . GeneralUtility::implodeAttributes($params[0])) . '>';
            }
            // Display modes:
            if ($this->mode == 'explode') {
                if ($firstTagName == 'table') {
                    $params[0]['border'] = 0;
                    $params[0]['cellspacing'] = 4;
                    $params[0]['cellpadding'] = 0;
                    $params[0]['style'] .= '; border: 1px dotted #666666;';
                    $firstTag = '<' . trim($firstTagName . ' ' . GeneralUtility::implodeAttributes($params[0])) . '>';
                } elseif ($firstTagName == 'td') {
                    $params[0]['style'] .= '; border: 1px dotted #666666;';
                    $firstTag = '<' . trim($firstTagName . ' ' . GeneralUtility::implodeAttributes($params[0])) . '>';

                    $v = (string) $v != '' ? $v : '&nbsp;';
                }
            } elseif ($this->mode == 'borders') {
                if ($firstTagName == 'table') {
                    $params[0]['style'] .= '; border: 1px dotted #666666;';
                    $firstTag = '<' . trim($firstTagName . ' ' . GeneralUtility::implodeAttributes($params[0])) . '>';
                } elseif ($firstTagName == 'td') {
                    $params[0]['style'] .= '; border: 1px dotted #666666;';
                    $firstTag = '<' . trim($firstTagName . ' ' . GeneralUtility::implodeAttributes($params[0])) . '>';
                }
            }
            // Get tag configuration
            $tagConf = $this->tags[$firstTagName];

            // If source mode or normal
            if ($this->mode == 'source') {
                $v = $this->sourceDisplay($firstTag, $recursion, $gnyf) . $v . $this->sourceDisplay($endTag, $recursion);
            } elseif ($this->mode == 'checkbox') {
                $v = $this->checkboxDisplay($firstTag . $v . $endTag, $recursion, $subPath, $gnyf);
            } else {
                // Find wrapping value for tag.
                if (is_array($tagConf['wrap']) && $gnyf) {
                    $gnyf = $tagConf['wrap'][0] . $gnyf . $tagConf['wrap'][1];
                }
                // Place gnyf relative to the tags and content.
                if ($tagConf['anchor_outside']) {
                    $v = $gnyf . $firstTag . $v . $endTag;
                } else {
                    $v = $firstTag . $gnyf . $v . $endTag;
                }
            }
        } else { // If solo/single element:
            // Adding gnyf to the tag:
            if ($this->mode == 'source') {
                $v = $this->sourceDisplay($v, $recursion, $gnyf);
            } elseif ($this->mode == 'checkbox') {
                $v = $this->checkboxDisplay($v, $recursion, $subPath, $gnyf);
            } else {
                $v = $gnyf . $v;
            }
        }

        // return sub HTML code with the original tags wrapped around plus the gnyf inside.
        return $v;
    }

    /**
     * In search mode, this function is used to process the content.
     *
     * @param string $mode Element type: block or '' (single/solo)
     * @param string $v Sub HTML code.
     * @param array $params Attributes of the current tag
     * @param string $firstTagName Current tags name (lowercase)
     * @param string $firstTag Current tag, full
     * @param string $endTag End tag for the current tag
     * @param string $subPath Current path of element
     * @param string $path
     * @param integer $recursion The recursion number
     *
     * @return string Modified sub HTML code ($v)
     */
    public function getSearchCode($mode, $v, $params, $firstTagName, $firstTag, $endTag, $subPath, $path, $recursion)
    {
        if ($this->rangeEndSearch[$recursion]) {
            $this->searchPaths[$this->rangeStartPath[$recursion]]['content'] .= $firstTag . $v . $endTag;
            $v = '';

            if ($this->rangeEndSearch[$recursion] == $subPath) {
                $this->searchPaths[$this->rangeStartPath[$recursion]]['closed'] = 1;
                $this->rangeEndSearch[$recursion] = '';
                $this->rangeStartPath[$recursion] = '';
            }
        } elseif ($this->searchPaths[$subPath]) {
            $placeholder = md5(uniqid(rand(), true));

            switch ((string) $this->searchPaths[$subPath]['modifier']) {
                case 'ATTR':
                case 'INNER+ATTR':
                    // Attribute
                    if ($this->searchPaths[$subPath]['modifier_value']) {
                        $attributeArray = array_unique(GeneralUtility::trimExplode(',', $this->searchPaths[$subPath]['modifier_value'], 1));
                        foreach ($attributeArray as $attr) {
                            $placeholder = '###' . $placeholder . '###';
                            $this->searchPaths[$subPath]['attr'][$attr]['placeholder'] = $placeholder;
                            $this->searchPaths[$subPath]['attr'][$attr]['content'] = $params[0][$attr];
                            $params[0][$attr] = $placeholder;
                            $placeholder = md5(uniqid(rand(), true));
                        }
                        $firstTag = '<' . trim($firstTagName . ' ' . GeneralUtility::implodeAttributes($params[0])) . ($mode != 'block' ? ' /' : '') . '>';
                        if ($mode != 'block') {
                            $v = $firstTag;
                            $firstTag = '';
                        }
                    }

                    if ($mode == 'block' && (string) $this->searchPaths[$subPath]['modifier'] == 'INNER+ATTR') {
                        // INNER
                        $placeholder = '<!--###' . $placeholder . '###-->';
                        $this->searchPaths[$subPath]['placeholder'] = $placeholder;
                        $this->searchPaths[$subPath]['content'] = $v;
                        $v = $firstTag . $placeholder . $endTag;
                    } else {
                        $v = $firstTag . $v . $endTag;
                    }
                    break;
                case 'INNER':
                    // INNER
                    $placeholder = '<!--###' . $placeholder . '###-->';
                    $this->searchPaths[$subPath]['placeholder'] = $placeholder;
                    $this->searchPaths[$subPath]['content'] = $v;
                    $v = $firstTag . $placeholder . $endTag;
                    break;
                case 'RANGE':
                    $placeholder = '<!--###' . $placeholder . '###-->';
                    $this->searchPaths[$subPath]['placeholder'] = $placeholder;
                    $this->searchPaths[$subPath]['content'] = $firstTag . $v . $endTag;
                    $v = $placeholder;

                    $this->rangeEndSearch[$recursion] = trim($path . ' ' . $this->searchPaths[$subPath]['modifier_value']);
                    $this->rangeStartPath[$recursion] = $subPath;
                    break;
                default:
                    // OUTER
                    $placeholder = '<!--###' . $placeholder . '###-->';
                    $this->searchPaths[$subPath]['placeholder'] = $placeholder;
                    $this->searchPaths[$subPath]['content'] = $firstTag . $v . $endTag;
                    $v = $placeholder;
                    break;
            }
        } else {
            $v = $firstTag . $v . $endTag;
        }

        return $v;
    }

    /**
     * Will format content for display in 'source' mode.
     *
     * @param string $str Input string to format.
     * @param integer $recursion The recursion integer - used to indent the code.
     * @param string $gnyf The gnyf-image to display.
     * @param integer $valueStr If set, then the line will be formatted in color as a "value" (means outside of the tag which might otherwise be what is shown)
     *
     * @return string Formatted input.
     */
    public function sourceDisplay($str, $recursion, $gnyf = '', $valueStr = 0)
    {
        if (strcmp(trim($str), '')) {
            return str_pad('', $recursion * 2, ' ', STR_PAD_LEFT) .
            $gnyf .
            ($valueStr ? '<font color="#6666FF"><em>' : '') .
            htmlspecialchars(GeneralUtility::fixed_lgd_cs(preg_replace('/\s+/', ' ', $str), $this->maxLineLengthInSourceMode)) .
            ($valueStr ? '</em></font>' : '') .
            chr(10);
        }

        return '';
    }

    /**
     * Will format content for display in 'checkbox' mode.
     *
     * @param string $str Input string to format.
     * @param integer $recursion The recursion integer - used to indent the code.
     * @param string $path HTML path
     * @param string $gnyf The gnyf-image to display.
     * @param integer $valueStr If set, then the line will be formatted in color as a "value" (means outside of the tag which might otherwise be what is shown)
     *
     * @return string Formatted input.
     */
    public function checkboxDisplay($str, $recursion, $path, $gnyf = '', $valueStr = 0)
    {
        if ($valueStr) {
            return trim($str) ? '
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>' . $this->passthroughHTMLcontent(trim($str), '', 'source') . '</td>
                </tr>' : '';
        }

        return '<tr>
                    <td><input type="checkbox" name="checkboxElement[]" value="' . $path . '"' . (in_array($path, $this->checkboxPathsSet) ? ' checked="checked"' : '') . ' /></td>
                    <td>' . $gnyf . '</td>
                    <td><pre>' . trim(htmlspecialchars($str)) . '</pre></td>
                </tr>';
    }

    /**
     * Compile the path value for the current path/tagname and attributes
     *
     * @param string $path Current path string for the parent level.
     * @param string $firstTagName The tag name for the current element on that level
     * @param string $attr The attributes for the tag in an array with key/value pairs
     *
     * @return string The sub path.
     */
    public function makePath($path, $firstTagName, $attr)
    {
        // Detect if pathMode is set and then construct the path based on the mode set.
        $subPath = 'Undefined';
        if ($this->pathMode) {
            switch ($this->pathMode) {
                default:
                    $counterIDstr = $firstTagName . ($attr['class'] ? '.' . preg_replace('/\s/', '~~~', $attr['class']) : ''); // Counter ID string
                    $this->elCountArray[$path][$counterIDstr]++; // Increase counter, include
                    // IF id attribute is set, then THAT will reset everything since IDs must be unique. (expecting that ID is a string with no whitespace... at least not checking for that here!)
                    if ($attr['id']) {
                        $subPath = $firstTagName . '#' . trim($attr['id']);
                        $this->elParentLevel[$path][] = $counterIDstr . '#' . $attr['id'];
                    } else {
                        $subPath = trim($path . ' ' . $counterIDstr . '[' . $this->elCountArray[$path][$counterIDstr] . ']');
                        $this->elParentLevel[$path][] = $counterIDstr . '[' . $this->elCountArray[$path][$counterIDstr] . ']';
                    }
                    break;
            }
        }

        return $subPath;
    }

    /**
     * Returns the GNYF image (tag-image)
     *
     * @param string $firstTagName The tag name in lowercase, eg. "table" or "tr"
     * @param string $path Path string for the link and title-attribute of the image.
     * @param string $title
     *
     * @return string HTML
     */
    public function getGnyf($firstTagName, $path, $title)
    {
        if (!$this->onlyElements || GeneralUtility::inList($this->onlyElements, $firstTagName)) {
            $onclick = str_replace('###PATH###', $this->pathPrefix . $path, $this->gnyfImgAdd);

            $gnyf = self::getGnyfMarkup($firstTagName, $title, $onclick);
            $gnyf .= $this->mode == 'explode' ? '<br />' : '';

            return $gnyf;
        }

        return '';
    }

    /**
     * @param string $tagName
     * @param string $title
     * @param string $onclick
     *
     * @return string
     */
    public static function getGnyfMarkup($tagName, $title = '', $onclick = '')
    {
        $tag = strtolower($tagName);
        if (!isset(self::$tagConf[$tag])) {
            return '';
        } else {
            return '<span ' . $onclick . ' class="gnyfElement gnyf' . ucfirst(self::$tagConf[$tag]['blocktype']) . '" title="' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($title, -200)) . '">' . htmlspecialchars($tag) . '</span>';
        }
    }
}
