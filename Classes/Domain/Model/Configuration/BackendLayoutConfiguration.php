<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Domain\Model\Configuration;

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
 * Class to provide unique access to BackendLayoutConfiguration
 */
class BackendLayoutConfiguration extends TemplateConfiguration
{
    /** @var bool $isDesign Tells Backend that this is a complete design of the node */
    protected $isDesign = false;

    public function isDesign(): bool
    {
        return $this->isDesign;
    }

    public function setDesign(bool $isDesign): void
    {
        $this->isDesign = $isDesign;
    }
}
