<?php

namespace Extension\Templavoila\Xclass;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class PagePositionMap extends \TYPO3\CMS\Backend\Tree\View\PagePositionMap
{
    /**
     * Creates the onclick event for the insert-icons.
     *
     * @param int $pid The pid.
     * @param int $newPagePID New page id.
     * @return string Onclick attribute content
     */
    public function onClickEvent($pid, $newPagePID)
    {
        $location = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl(
            'web_txtemplavoilaM1',
            [
                'cmd' => 'crPage',
                'positionPid' => $pid,
                'id' => $newPagePID,
            ]
        );
        return 'window.location.href=' . GeneralUtility::quoteJSvalue($location) . '; return false;';
    }
}
