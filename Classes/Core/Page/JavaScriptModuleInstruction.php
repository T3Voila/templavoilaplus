<?php

declare(strict_types=1);

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

namespace Tvp\TemplaVoilaPlus\Core\Page;

use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction as CoreJavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class JavaScriptModuleInstruction extends CoreJavaScriptModuleInstruction
{
    private $pattern = [
        '#TYPO3/CMS/Backend/FormEngineLinkBrowserAdapter#',
    ];

    private $replace = [
        'TYPO3/CMS/Templavoilaplus/FormEngineLinkBrowserAdapter',
    ];

    /**
     * @param string $name Module name
     * @param int $flags
     */
    public function __construct(string $name, int $flags)
    {
        if (version_compare(TYPO3_version, '10.4', '<=')) {
            $name = preg_replace($this->pattern, $this->replace, $name);
        }
        parent::__construct($name, $flags);
    }
}
