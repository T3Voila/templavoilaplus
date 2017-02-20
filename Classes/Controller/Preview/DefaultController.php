<?php
namespace Extension\Templavoila\Controller\Preview;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Default controller
 */
class DefaultController
{

    /**
     * @param array $row
     * @param string $table
     * @param string $output
     * @param boolean $alreadyRendered
     * @param object $ref
     *
     * @return string
     */
    public function render_previewContent($row, $table, $output, $alreadyRendered, &$ref)
    {
        return '<strong>' . $this->getLanguageService()->sL(BackendUtility::getLabelFromItemlist('tt_content', 'CType', $row['CType'])) . '</strong>';
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
