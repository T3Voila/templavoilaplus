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

use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\AbstractConfiguration;
use Tvp\TemplaVoilaPlus\Domain\Model\Place;
use Tvp\TemplaVoilaPlus\Handler\LoadSave\LoadSaveHandlerInterface;

interface ConfigurationHandlerInterface
{
    public function setPlace(Place $place): void;

    public function setLoadSaveHandler(LoadSaveHandlerInterface $loadSaveHandler): void;

    public function loadConfigurations(): void;

    public function saveConfiguration(AbstractConfiguration $configuration): void;
}
