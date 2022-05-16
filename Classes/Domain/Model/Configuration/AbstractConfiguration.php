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

use Symfony\Component\Finder\SplFileInfo;
use Tvp\TemplaVoilaPlus\Domain\Model\Place;
use Tvp\TemplaVoilaPlus\Handler\Configuration\ConfigurationHandlerInterface;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Class to provide unique configuration of whatever extends this
 */
class AbstractConfiguration
{
    /** @var string */
    protected $identifier = '';

    /** @var Place */
    protected $place;

    /** @var ConfigurationHandlerInterface */
    protected $configurationHandler;

    /** @var SplFileInfo */
    protected $file;

    /** @var string */
    protected $name = '';

    /**
     */
    public function __construct(string $identifier, Place $place, ConfigurationHandlerInterface $configurationHandler, SplFileInfo $file)
    {
        $this->identifier = $identifier;
        $this->place = $place;
        $this->configurationHandler = $configurationHandler;
        $this->file = $file;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getPlace(): Place
    {
        return $this->place;
    }

    public function getConfigurationHandler(): ConfigurationHandlerInterface
    {
        return $this->configurationHandler;
    }

    public function getFile(): SplFileInfo
    {
        return $this->file;
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
