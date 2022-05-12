<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Tests\Acceptance\Support\Extension;

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

use TYPO3\TestingFramework\Core\Acceptance\Extension\BackendEnvironment;

/**
 * Load various core extensions and styleguide and call styleguide generator
 */
class BackendTemplavoilaplusEnvironment extends BackendEnvironment
{
    /**
     * Load a list of core extensions and styleguide
     *
     * @var array
     */
    protected $localConfig = [
        'coreExtensionsToLoad' => [
            'core',
            'extbase',
            'fluid',
            'backend',
            'extensionmanager',
            'install',
            'frontend',
            'recordlist',
        ],
        'testExtensionsToLoad' => [
            'typo3conf/ext/templavoilaplus',
            'typo3conf/ext/tvplus_test_theme',
        ],
        'xmlDatabaseFixtures' => [
            'typo3conf/ext/templavoilaplus/Tests/Fixtures/be_users.xml',
            'typo3conf/ext/templavoilaplus/Tests/Fixtures/be_groups.xml',
            'typo3conf/ext/templavoilaplus/Tests/Fixtures/pages.xml',
            'typo3conf/ext/templavoilaplus/Tests/Fixtures/tt_content.xml',
        ],
    ];
}
