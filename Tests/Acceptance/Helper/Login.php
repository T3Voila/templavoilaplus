<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Tests\Acceptance\Helper;

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

use Codeception\Exception\ConfigurationException;
use Codeception\Module;
use Codeception\Module\WebDriver;
use Codeception\Util\Locator;

/**
 * Helper class to log in backend users and load backend.
 */
class Login extends Module
{
    public function loginAsUser($username = 'admin', $password = 'password')
    {
        $webDriver = $this->getWebDriver();

        $hasSession = $this->_loadSession();

        if (!$hasSession) {
            $webDriver->amOnPage('/typo3/index.php');
            $webDriver->waitForElement('body[data-typo3-login-ready]');
            $webDriver->fillField('#t3-username', 'admin');
            $webDriver->fillField('#t3-password', 'password');
            $webDriver->click('#t3-login-submit');
            $webDriver->waitForElement('#t3js-modal-loginrefresh');
            $webDriver->saveSessionSnapshot('login');
        }

        // Reload the page to have a logged in backend.
        $webDriver->amOnPage('/typo3/index.php');

        // Ensure main content frame is fully loaded, otherwise there are load-race-conditions ..
        $webDriver->waitForElement('iframe[name="list_frame"]');
        $webDriver->switchToIFrame('list_frame');
        $webDriver->waitForElement(Locator::firstElement('div.module'));
        // .. and switch back to main frame.
        $webDriver->switchToIFrame();
    }

    /**
     * @return bool
     */
    public function _loadSession()
    {
        return $this->getWebDriver()->loadSessionSnapshot('login');
    }

    public function _deleteSession()
    {
        $webDriver = $this->getWebDriver();
        $webDriver->resetCookie('be_typo_user');
        $webDriver->resetCookie('be_lastLoginProvider');
        $webDriver->deleteSessionSnapshot('login');
    }

    /**
     * @param string $userSessionId
     */
    public function _createSession($userSessionId)
    {
        $webDriver = $this->getWebDriver();
        $webDriver->setCookie('be_typo_user', $userSessionId);
        $webDriver->setCookie('be_lastLoginProvider', '1433416747');
        $webDriver->saveSessionSnapshot('login');
    }

    /**
     * @return string
     */
    protected function getUserSessionId()
    {
        $userSessionId = $this->getWebDriver()->grabCookie('be_typo_user');
        return $userSessionId ?? '';
    }

    /**
     * @return WebDriver
     * @throws \Codeception\Exception\ModuleException
     */
    protected function getWebDriver()
    {
        return $this->getModule('WebDriver');
    }
}
