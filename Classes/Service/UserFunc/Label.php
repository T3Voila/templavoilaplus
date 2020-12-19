<?php

namespace Tvp\TemplaVoilaPlus\Service\UserFunc;

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

/**
 * This library contains several functions for displaying the labels in the list view.
 */
class Label implements SingletonInterface
{

    /**
     * Retrive the label for TCEFORM title attribute.
     *
     * @param array $params Current record array
     * @param object
     *
     * @return void
     */
    public function getLabel(&$params, &$pObj)
    {
        $params['title'] = $this->getLanguageService()->sL($params['row']['title']);
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    public function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
