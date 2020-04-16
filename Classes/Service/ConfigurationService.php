<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Service;

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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface;

use Ppi\TemplaVoilaPlus\Domain\Model\Place;

class ConfigurationService implements SingletonInterface
{
    private $extConfig = [];
    private $availableRenderer = [];

    private $isInitialized = false;

    /**
     * @var array
     */
    protected $formSettings;

    public function __construct()
    {
        if (version_compare(TYPO3_version, '9.0.0', '>=')) {
            $this->extConfig = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['templavoilaplus'];
        } else {
            $this->extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoilaplus']);
        }
    }

    private function initialize()
    {
        if (!$this->isInitialized) {
            $this->isInitialized = true;

            \Ppi\TemplaVoilaPlus\Utility\ExtensionUtility::handleAllExtensions();

            $this->formSettings = GeneralUtility::makeInstance(ObjectManager::class)
                ->get(ConfigurationManagerInterface::class)
                ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_YAML_SETTINGS, 'templavoilaplus');
        }
    }

    public function getPlacesService(): PlacesService
    {
        $this->initialize();

        /** @var PlacesService */
        $placesService = GeneralUtility::makeInstance(PlacesService::class);
        return $placesService;
    }

    /**
     * Get the prototype configuration
     *
     * @param string $prototypeName name of the prototype to get the configuration for
     * @return array the prototype configuration
     * @throws PrototypeNotFoundException if prototype with the name $prototypeName was not found
     * @api
     */
    public function getFormPrototypeConfiguration(string $prototypeName): array
    {
        if (!isset($this->formSettings['prototypes'][$prototypeName])) {
//             throw new PrototypeNotFoundException(
            throw new \Exception(
                sprintf('The Prototype "%s" was not found.', $prototypeName),
                1475924277
            );
        }
        return $this->formSettings['prototypes'][$prototypeName];
    }

    public function getExtensionConfig(): array
    {
        $this->initialize();
        return $this->extConfig;
    }

    public function getAvailableRenderer(): array
    {
        $this->initialize();
        return $this->availableRenderer;
    }

    public function getRenderer($uuid): \Ppi\TemplaVoilaPlus\Renderer\RendererInterface
    {
        $this->initialize();
        if (!isset($this->availableRenderer[$uuid])) {
            throw new \Exception('Renderer "' . $uuid . '" not available.');
        }
        return GeneralUtility::makeInstance($this->availableRenderer[$uuid]['class']);
    }

    // Helper function to register new Places
    public function registerPlace(
        string $identifier,
        string $name,
        /** @TODO */ $scope,
        string $configurationHandlerIdentifier,
        string $loadSaveHandlerIdentifier,
        string $entryPoint
    ) {
        /** @var PlacesService */
        $placesService = GeneralUtility::makeInstance(PlacesService::class);

        if ($placesService->doesIdentifierExists($identifier)) {
            throw new \Exception('The identifier "' . $identifier . '" is already registered for Places');
        }
        // $this->checkHandler $configurationHandlerIdentifier
        // $this->checkHandler $loadSaveHandlerIdentifier

        $placesService->registerPlace(
            new Place($identifier, $name, $scope, $configurationHandlerIdentifier, $loadSaveHandlerIdentifier, $entryPoint)
        );
    }

    public function registerRenderer($uuid, $name, $class)
    {
        $interfaces = @class_implements($class);

        if ($interfaces === false) {
            throw new \Exception('Class "' . $class . '" not found');
        }
        if (!isset($interfaces[\Ppi\TemplaVoilaPlus\Renderer\RendererInterface::class])) {
            throw new \Exception('Class "' . $class . '" do not implement renderer interface');
        }
        if (isset($this->availableRenderer[$uuid])) {
            throw new \Exception('uuid already exists');
        }

        $this->availableRenderer[$uuid] = [
            'name' => $name,
            'class' => $class,
        ];
    }

    public function registerHandler(
        string $identifier,
        string $name,
        string $handlerClass,
        string $implementorsInterface
    ) {
        $this->mustExistsAndImplements($handlerClass, $implementorsInterface);

        if (isset($this->availableHandler[$identifier])) {
            throw new \Exception('The identifier "' . $identifier . '" is already registered for Handler');
        }

        $this->availableHandler[$identifier] = [
            'name' => $name,
            'class' => $handlerClass,
        ];
    }

    /**
     */
    public function getHandler(string $handlerIdentifier)
    {
        if (!isset($this->availableHandler[$handlerIdentifier])) {
            throw new \Exception('Handler with identifier "' . $handlerIdentifier . '" do not exist');
        }
        return GeneralUtility::makeInstance($this->availableHandler[$handlerIdentifier]['class'], $place);
    }

    public function mustExistsAndImplements(string $class, string $implements): bool
    {
        $interfaces = @class_implements($class);

        if ($interfaces === false) {
            throw new \Exception('Class "' . $class . '" not found');
        }

        $parents = @class_parents($class);
        if (!isset($interfaces[$implements]) && !isset($parents[$implements])) {
            throw new \Exception('Class "' . $class . '" do not implement "' . $implements . '"');
        }

        return true;
    }
}
