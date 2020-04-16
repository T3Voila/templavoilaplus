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
 * Class to provide unique configuration of whatever extends this
 */
class AbstractConfiguration
{
    /**
     * @var string
     */
    protected $identifier = '';

    /**
     * @var Place
     */
    protected $place;

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @param \TYPO3\CMS\Core\Resource\File $file
     */
    public function __construct(Place $place, $identifier)
    {
        $this->identifier = $identifier;
        $this->place = $place;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getPlace(): Place
    {
        return $this->place;
    }

    public function getName(): string
    {
        return TemplaVoilaUtility::getLanguageService()->sL($this->name);
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }
}
