<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Handler\Configuration;

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

use Tvp\TemplaVoilaPlus\Domain\Model\DataStructure;

class DataStructureConfigurationHandler extends AbstractConfigurationHandler
{
    public static $identifier = 'TVP\ConfigurationHandler\DataStructure';

    public function createConfigurationFromConfigurationArray($dataStructureArray, $identifier, $possibleName): DataStructure
    {
        $dataStructure = new DataStructure($this->place, $identifier);
        $dataStructure->setName($possibleName);
        // Read title from XML file and set, if not empty or ROOT
        if (
            !empty($dataStructureArray['meta']['title'])
            && $dataStructureArray['meta']['title'] !== 'ROOT'
        ) {
            $dataStructure->setName($dataStructureArray['meta']['title']);
        }

        /** @TODO setIcon */
        $dataStructure->setDataStructureArray($dataStructureArray);

        return $dataStructure;
    }
}
