<?php
declare(strict_types = 1);
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

use Ppi\TemplaVoilaPlus\Exception\DataStructureException;
use Ppi\TemplaVoilaPlus\Service\ConfigurationService;
use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Class to provide unique access to datastructure
 */
class DataStructurePlace
{
    /**
     * @var integer
     */
    protected $scope = 0;

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string Name of the handler for operations of this types
     */
    protected $handlerName;

    /**
     * @var string
     */
    protected $pathAbsolute;

    public function __construct($uuid, $name, $scope, string $handlerName, $pathAbsolute)
    {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->scope = $scope;
        $this->handlerName = $handlerName;
        $this->pathAbsolute = $pathAbsolute;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function getName()
    {
        return TemplaVoilaUtility::getLanguageService()->sL($this->name);
    }

    public function getHandlerName(): string
    {
        return $this->handlerName;
    }

    public function getHandler(): \Ppi\TemplaVoilaPlus\DataStructureHandler\DataStructureHandlerInterface
    {
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        return $configurationService->getDataStructureHandler($this->getHandlerName(), $this);
    }

    public function getPathAbsolute()
    {
        return $this->pathAbsolute;
    }

    public function getPathRelative()
    {
        return PathUtility::stripPathSitePrefix($this->pathAbsolute);
    }
}
