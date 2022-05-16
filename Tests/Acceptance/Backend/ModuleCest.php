<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Tests\Acceptance\Backend;

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

use Tvp\TemplaVoilaPlus\Tests\Acceptance\Support\BackendTester;
use TYPO3\TestingFramework\Core\Acceptance\Helper\Login;

/**
 * Tests the templavoilaplus backend module can be loaded
 */
class ModuleCest
{
    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I)
    {
        $I->loginAsUser();
    }

    /**
     * @param BackendTester $I
     */
    public function tvpIntroMessageShows(BackendTester $I): void
    {
        $I->amOnPage('/typo3/module/web/TemplaVoilaPlusLayout?id=0');
        $I->switchToIFrame('list_frame');
        $I->waitForElement('.callout-title');
        $I->canSee('Page Module', '.callout-title');
    }

    /**
     * @param BackendTester $I
     */
    public function pageWithMapShowsFields(BackendTester $I): void
    {
        $I->amOnPage('/typo3/module/web/TemplaVoilaPlusLayout?id=4');
        $I->switchToIFrame('list_frame');
        $I->waitForElement('#tvp-component-stage-container');
        $I->canSee('Content Area', '#tvp-component-stage-container');
    }

    /**
     * @param BackendTester $I
     */
    public function pageWithoutMapShowsWarning(BackendTester $I): void
    {
        $I->amOnPage('/typo3/module/web/TemplaVoilaPlusLayout?id=7');
        $I->switchToIFrame('list_frame');
        $I->waitForElement('.alert-title');
        $I->canSee('No mapping configuration found', '.alert-title');
    }
}
