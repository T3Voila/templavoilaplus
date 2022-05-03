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

use Symfony\Component\Finder\Finder;
use Tvp\TemplaVoilaPlus\Domain\Model\Place;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractFileLoadSaveHandler implements LoadSaveHandlerInterface
{
    public static $identifier = 'TVP\LoadSaveHandler\AbstractFile';

    /**
     * @var Place
     */
    protected $place;

    protected $fileExtension = '.txt';

    public function setPlace(Place $place)
    {
        $this->place = $place;
    }

    public function find()
    {
        $path = GeneralUtility::getFileAbsFileName($this->place->getEntryPoint());

        $finder = new Finder();
        return $finder
            ->files()
            ->name('*' . $this->fileExtension)
            ->in($path)
            ->getIterator();
    }

    abstract public function load(\Symfony\Component\Finder\SplFileInfo $file): array;

    abstract public function save();
}
