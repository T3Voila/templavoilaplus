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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class SectionIndexController
 */
class SectionIndexController
{

    /**
     * Must be public, as it is set from the outside
     *
     * @var ContentObjectRenderer
     */
    public $cObj;

    /**
     * Render section index for TV
     *
     * @param string $content
     * @param array $conf config of tt_content.menu.20.3
     *
     * @return string rendered section index
     */
    public function mainAction($content, $conf) {

        $ceField = $this->cObj->stdWrap($conf['indexField'], $conf['indexField.']);
        $pids = isset($conf['select.']['pidInList.'])
            ? trim($this->cObj->stdWrap($conf['select.']['pidInList'], $conf['select.']['pidInList.']))
            : trim($conf['select.']['pidInList']);
        $contentIds = array();
        if ($pids) {
            $pageIds = GeneralUtility::trimExplode(',', $pids);
            foreach ($pageIds as $pageId) {
                $page = $GLOBALS['TSFE']->sys_page->checkRecord('pages', $pageId);
                if (isset($page) && isset($page['tx_templavoila_flex'])) {
                    $flex = array();
                    $this->cObj->readFlexformIntoConf($page['tx_templavoila_flex'], $flex);
                    $contentIds = array_merge($contentIds, GeneralUtility::trimExplode(',', $flex[$ceField]));
                }
            }
        } else {
            $flex = array();
            $this->cObj->readFlexformIntoConf($GLOBALS['TSFE']->page['tx_templavoila_flex'], $flex);
            $contentIds = array_merge($contentIds, GeneralUtility::trimExplode(',', $flex[$ceField]));
        }

        if (count($contentIds) > 0) {
            $conf['source'] = implode(',', $contentIds);
            $conf['tables'] = 'tt_content';
            $conf['conf.'] = array(
                'tt_content' => $conf['renderObj'],
                'tt_content.' => $conf['renderObj.'],
            );
            $conf['dontCheckPid'] = 1;
            unset($conf['renderObj']);
            unset($conf['renderObj.']);
        }

        // tiny trink to include the section index element itself too
        $GLOBALS['TSFE']->recordRegister[$GLOBALS['TSFE']->currentRecord] = -1;
        $renderedIndex = $this->cObj->cObjGetSingle('RECORDS', $conf);

        $wrap = isset($conf['wrap.'])
            ? $this->cObj->stdWrap($conf['wrap'], $conf['wrap.'])
            : $conf['wrap'];
        if ($wrap) {
            $renderedIndex = $this->cObj->wrap($renderedIndex, $wrap);
        }

        return $renderedIndex;
    }

}