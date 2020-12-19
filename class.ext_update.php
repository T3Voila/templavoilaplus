<?php
namespace Tvp\TemplaVoilaPlus;
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

/**
 * Update wizard for the extension manager
 */
class ext_update
{
    /**
     * @var Tvp\TemplaVoilaPlus\Controller\Backend\ExtensionManagerUpdateController
     */
    protected $controller;

    public function __construct()
    {
        $this->controller = new Controller\Backend\ExtensionManagerUpdateController();
    }

    /**
     * Main function, returning the HTML content of the module
     *
     * @return string HTML
     */
    public function main()
    {
        return $this->controller->run();
    }

    /**
     * Checks if backend user is an administrator
     * (this function is called from the extension manager)
     *
     * @return boolean
     */
    public function access()
    {
        return $this->controller->shouldBeShown();
    }
}
