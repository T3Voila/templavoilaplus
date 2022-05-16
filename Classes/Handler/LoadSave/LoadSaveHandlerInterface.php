<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Handler\LoadSave;

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

use Symfony\Component\Finder\SplFileInfo;
use Tvp\TemplaVoilaPlus\Domain\Model\Place;

interface LoadSaveHandlerInterface
{
//     static public $identifier = 'TVP\LoadSaveHandler\Interface';

    public function setPlace(Place $place);

    public function find();

    /**
     * @TODO The identifier inside place would be more cool, so we get filepath from place for more secure handling
     */
    public function load(SplFileInfo $file): array;

    public function save(SplFileInfo $file, array $data): void;

    public function delete(SplFileInfo $file): void;
}
