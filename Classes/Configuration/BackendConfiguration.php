<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Configuration;

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
use TYPO3\CMS\Frontend\Page\PageRepository;

class BackendConfiguration
{
    // Handler => Classes with a handler function
    const HANDLER_DOCTYPE = 100;

    // Renderer => Functions which return string data
    const RENDER_HEADER = 200;
    const RENDER_BODY = 200;
    const RENDER_FOOTER = 200;

    protected $items = [
        self::HANDLER_DOCTYPE => [],
        self::RENDER_HEADER => [],
        self::RENDER_BODY => [],
        self::RENDER_FOOTER => [],
    ];

    public function __construct()
    {
        $this->initDefaults();
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['templavoilaplus']['BackendConfiguration'])) {
            $configurationFunctionHooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['templavoilaplus']['BackendConfiguration'];
            if (is_array($configurationFunctionHooks)) {
                foreach ($configurationFunctionHooks as $hook) {
                    GeneralUtility::callUserFunction($hook, [], $this);
                }
            }
        }
    }

    public function initDefaults()
    {
        $this->setItem(self::HANDLER_DOCTYPE, PageRepository::DOKTYPE_DEFAULT, \Ppi\TemplaVoilaPlus\Controller\Backend\Handler\DoktypeDefaultHandler::class);
        $this->setItem(self::HANDLER_DOCTYPE, PageRepository::DOKTYPE_LINK, \Ppi\TemplaVoilaPlus\Controller\Backend\Handler\DoktypeLinkHandler::class);
        $this->setItem(self::HANDLER_DOCTYPE, PageRepository::DOKTYPE_SHORTCUT, \Ppi\TemplaVoilaPlus\Controller\Backend\Handler\DoktypeShortcutHandler::class);
        $this->setItem(self::HANDLER_DOCTYPE, PageRepository::DOKTYPE_BE_USER_SECTION, \Ppi\TemplaVoilaPlus\Controller\Backend\Handler\DoktypeDefaultHandler::class);
        $this->setItem(self::HANDLER_DOCTYPE, PageRepository::DOKTYPE_MOUNTPOINT, \Ppi\TemplaVoilaPlus\Controller\Backend\Handler\DoktypeMountpointHandler::class);
        $this->setItem(self::HANDLER_DOCTYPE, PageRepository::DOKTYPE_SPACER, \Ppi\TemplaVoilaPlus\Controller\Backend\Handler\DoktypeSpacerHandler::class);
        $this->setItem(self::HANDLER_DOCTYPE, PageRepository::DOKTYPE_SYSFOLDER, \Ppi\TemplaVoilaPlus\Controller\Backend\Handler\DoktypeSysfolderHandler::class);
        $this->setItem(self::HANDLER_DOCTYPE, PageRepository::DOKTYPE_RECYCLER, \Ppi\TemplaVoilaPlus\Controller\Backend\Handler\DoktypeRecyclerHandler::class);
    }

    public function setItem($type, $key, $value)
    {
        $this->items[$type][$key] = $value;
    }

    public function removeItem($type, $key)
    {
        if (isset($this->items[$type][$key])) {
            unset($this->items[$type][$key]);
        }
    }

    public function haveItem($type, $key)
    {
        return isset($this->items[$type][$key]);
    }

    public function getItem($type, $key)
    {
        if (isset($this->items[$type][$key])) {
            return $this->items[$type][$key];
        }
        return null;
    }
}
