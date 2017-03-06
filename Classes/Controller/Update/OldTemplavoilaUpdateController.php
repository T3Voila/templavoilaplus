<?php
namespace Ppi\TemplaVoilaPlus\Controller\Update;

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
use TYPO3\CMS\Core\Utility\StringUtility;

use Ppi\TemplaVoilaPlus\Domain\Repository\DataStructureRepository;

/**
 * Controller to migrate/update from old TemplaVoila
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class OldTemplavoilaUpdateController extends StepUpdateController
{
    protected function stepStart()
    {
    }

    protected function stepFinal()
    {
    }
}
