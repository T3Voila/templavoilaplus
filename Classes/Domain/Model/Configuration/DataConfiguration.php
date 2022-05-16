<?php

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
 * Class to provide unique configuration of datastructure
 */
class DataConfiguration extends AbstractConfiguration
{
    /** @var array */
    protected $dataStructure = [];

    public function getDataStructure(): array
    {
        return $this->dataStructure;
    }

    public function setDataStructure(array $dataStructure): void
    {
        $this->dataStructure = $dataStructure;
    }
}
