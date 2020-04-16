<?php
namespace Ppi\TemplaVoilaPlus\Domain\Model;

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

use Ppi\TemplaVoilaPlus\Domain\Model\Place;
use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Class to provide unique configuration of datastructure
 */
class DataStructure extends AbstractConfiguration
{
    /**
     * @var array
     */
    protected $dataStructureArray = [];

    public function getDataStructureArray(): array
    {
        return $this->dataStructureArray;
    }

    public function setDataStructureArray(array $dataStructureArray)
    {
        $this->dataStructureArray = $dataStructureArray;
    }
}
