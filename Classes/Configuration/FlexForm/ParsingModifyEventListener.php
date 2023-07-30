<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Configuration\FlexForm;

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

use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureParsedEvent;

class ParsingModifyEventListener
{
    public function setDataStructure(BeforeFlexFormDataStructureParsedEvent $event): void
    {
        $identifier = $event->getIdentifier();
        if (($identifier['type'] ?? '') === 'combinedMappingIdentifier') {
            $dataStructureIdentifier = new DataStructureIdentifierHook();
            $event->setDataStructure(
                $dataStructureIdentifier->parseDataStructureByIdentifierPreProcess($identifier)
            );
        }
    }
}
