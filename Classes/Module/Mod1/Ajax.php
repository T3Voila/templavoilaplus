<?php
namespace Extension\Templavoila\Module\Mod1;

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

/**
 * Class 'Ajax' for module 1 of the 'templavoila' extension.
 *
 * @author Nikolas Hagelstein <lists@shr-now.de>
 */
class Ajax
{
    /**
     * @var \Extension\Templavoila\Service\ApiService
     */
    private $apiObj;

    /**
     * @return \Ajax
     */
    public function __construct()
    {
        $this->apiObj = GeneralUtility::makeInstance(\Extension\Templavoila\Service\ApiService::class);
    }

    /**
     * Performs a move action for the requested element
     *
     * @param array $params
     * @param object $ajaxObj
     *
     * @return void
     */
    public function moveRecord($params, &$ajaxObj)
    {

        $sourcePointer = $this->apiObj->flexform_getPointerFromString(GeneralUtility::_GP('source'));

        $destinationPointer = $this->apiObj->flexform_getPointerFromString(GeneralUtility::_GP('destination'));

        $this->apiObj->moveElement($sourcePointer, $destinationPointer);
    }

    /**
     * Performs a move action for the requested element
     *
     * @param array $params
     * @param object $ajaxObj
     *
     * @return void
     */
    public function unlinkRecord($params, &$ajaxObj)
    {

        $unlinkPointer = $this->apiObj->flexform_getPointerFromString(GeneralUtility::_GP('unlink'));

        $this->apiObj->unlinkElement($unlinkPointer);
    }
}
