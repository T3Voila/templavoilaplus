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
 * Class to place data
 */
class Place
{
    /**
     * @var integer
     */
    protected $scope = 0;

    /**
     * @var string
     */
    protected $identifier = '';

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var string Identifier of the handler to manage coverting between configuration object and configuration array
     */
    protected $configurationHandlerIdentifier = '';

    /**
     * @var string Identifier of the handler to manage the loading and saving of configuration array
     */
    protected $loadSaveHandlerIdentifier = '';

    /**
     * @var string An entryPoint may be the path to the directory or a table or anything else
     */
    protected $entryPoint = '';

    /**
     * @var array An array of the configurations with the identifier as key
     */
    protected $configurations = [];

    /**
     * @param $identifier string The global name of this place it should be unique, take the PHP Namespace as an orientation.
     * @param $name string A name for this place in english or an LLL entry.
     * @param $scope mixed The scope for this place (Page/FCE/tablename) @TODO A better scope handling will hapen later on
     * @param $configurationHandlerIdentifier string The identifier of the class which will handle the conversation between the array and object @TODO better naming, will this class do more
     * @param $loadSaveHandlerIdentifier string Identifier of the class which will handle the searching, loading and saving of the configurations (Yaml, XML, ...)
     * @param $entryPoint string An entry point for this place, mostly a path to the files but may have also other meanings (f.e. a pid for core beLayouts)
     */
    public function __construct(
        string $identifier,
        string $name,
        /* @TODO */ $scope,
        string $configurationHandlerIdentifier,
        string $loadSaveHandlerIdentifier,
        string $entryPoint
    ) {
        $this->identifier = $identifier;
        $this->name = $name;
        $this->scope = $scope;
        $this->configurationHandlerIdentifier = $configurationHandlerIdentifier;
        $this->loadSaveHandlerIdentifier = $loadSaveHandlerIdentifier;
        $this->entryPoint = $entryPoint;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return TemplaVoilaUtility::getLanguageService()->sL($this->name);
    }

    public function getConfigurationHandlerIdentifier(): string
    {
        return $this->configurationHandlerIdentifier;
    }

    public function getLoadSaveHandlerIdentifier(): string
    {
        return $this->loadSaveHandlerIdentifier;
    }

    public function getEntryPoint(): string
    {
        return $this->entryPoint;
    }

    public function getConfigurations(): array
    {
        return $this->configurations;
    }

    public function setConfigurations(array $configurations)
    {
        $this->configurations = $configurations;
    }

    public function getConfiguration(string $configurationIdentifier): object
    {
        if (!isset($this->configurations[$configurationIdentifier])) {
            throw new \Exception('Configuration with identifer "' . $configurationIdentifier . '" not found');
        }

        return $this->configurations[$configurationIdentifier]['configuration'];
    }

    /** @TODO No processing in models and a entryPoint could also be a non directory */
    public function getPathRelative(): string
    {
        return PathUtility::stripPathSitePrefix($this->entryPoint);
    }
}
