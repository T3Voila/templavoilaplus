<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Configuration;

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
                $params = [];
                foreach ($configurationFunctionHooks as $hook) {
                    GeneralUtility::callUserFunction($hook, $params, $this);
                }
            }
        }
    }

    public function initDefaults()
    {
        $this->setItem(self::HANDLER_DOCTYPE, \TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_DEFAULT, \Tvp\TemplaVoilaPlus\Controller\Backend\Handler\DoktypeDefaultHandler::class);
        $this->setItem(self::HANDLER_DOCTYPE, \TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_LINK, \Tvp\TemplaVoilaPlus\Controller\Backend\Handler\DoktypeLinkHandler::class);
        $this->setItem(self::HANDLER_DOCTYPE, \TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_SHORTCUT, \Tvp\TemplaVoilaPlus\Controller\Backend\Handler\DoktypeShortcutHandler::class);
        $this->setItem(self::HANDLER_DOCTYPE, \TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_BE_USER_SECTION, \Tvp\TemplaVoilaPlus\Controller\Backend\Handler\DoktypeDefaultHandler::class);
        $this->setItem(self::HANDLER_DOCTYPE, \TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_MOUNTPOINT, \Tvp\TemplaVoilaPlus\Controller\Backend\Handler\DoktypeMountpointHandler::class);
        $this->setItem(self::HANDLER_DOCTYPE, \TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_SPACER, \Tvp\TemplaVoilaPlus\Controller\Backend\Handler\DoktypeSpacerHandler::class);
        $this->setItem(self::HANDLER_DOCTYPE, \TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_SYSFOLDER, \Tvp\TemplaVoilaPlus\Controller\Backend\Handler\DoktypeSysfolderHandler::class);
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
