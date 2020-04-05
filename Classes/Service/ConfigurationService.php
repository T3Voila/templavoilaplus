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

use Ppi\TemplaVoilaPlus\Domain\Model\DataStructurePlace;
use Ppi\TemplaVoilaPlus\Domain\Model\TemplatePlace;

class ConfigurationService implements SingletonInterface
{
    private $extConfig = [];
    private $dataStructurePlaces = [];
    private $templatePlaces = [];
    private $mappingPlaces = [];
    private $availableRenderer = [];
    private $availablePlaceHandler = [];

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

    public function getDataStructurePlaces(): array
    {
        $this->initialize();
        return $this->dataStructurePlaces;
    }

    public function getDataStructurePlace($uuid): DataStructurePlace
    {
        $this->initialize();
        if (!isset($this->dataStructurePlaces[$uuid])) {
            throw new \Exception('DataStructurePlace "' . $uuid . '" not available.');
        }
        return $this->dataStructurePlaces[$uuid];
    }

    public function getTemplatePlaces(): array
    {
        $this->initialize();
        return $this->templatePlaces;
    }

    public function getMappingPlaces(): array
    {
        $this->initialize();
        return $this->mappingPlaces;
    }

    public function getAvailableRenderer(): array
    {
        $this->initialize();
        return $this->availableRenderer;
    }

    public function getAvailablePlaceHandler(): array
    {
        $this->initialize();
        return $this->availablePlaceHandler;
    }

    public function getHandler(
        \Ppi\TemplaVoilaPlus\Domain\Model\Place $place
    ) {
        $availableHandler = $this->availablePlaceHandler[get_class($place)];

        if (!isset($availableHandler[$place->getHandlerName()])) {
            throw new \Exception('Handler with uuid "' . $place->getHandlerName() . '" do not exist');
        }
        return GeneralUtility::makeInstance($availableHandler[$place->getHandlerName()]['class'], $place);
    }

    public function registerDataStructurePlace($uuid, $name, $path, $scope, $dataStructureHandler)
    {
        // @TODO Check if path is inside FAL and add danger hint!
        $pathAbsolute = GeneralUtility::getFileAbsFileName($path);
        if (!is_dir($pathAbsolute) || !is_readable($pathAbsolute)) {
            throw new \Exception('path "' . $path . '" does not exist or is not readable');
        }
        if (isset($this->dataStructurePlaces[$uuid])) {
            throw new \Exception('uuid already exists');
        }
        if (!isset($this->availablePlaceHandler[DataStructurePlace::class][$dataStructureHandler])) {
            throw new \Exception('DataStructureHandler "' . $dataStructureHandler . '" unknown.');
        }

        $dataStructurePlace = new DataStructurePlace($uuid, $name, $scope, $dataStructureHandler, $pathAbsolute);
        $this->dataStructurePlaces[$uuid] = $dataStructurePlace;
    }

    public function registerTemplatePlace($uuid, $name, $path, $scope, $handler)
    {
        // @TODO Check if path is inside FAL and add danger hint!
        $pathAbsolute = GeneralUtility::getFileAbsFileName($path);
        if (!is_dir($pathAbsolute) || !is_readable($pathAbsolute)) {
            throw new \Exception('path "' . $path . '" does not exist or is not readable');
        }
        if (isset($this->templatePlaces[$uuid])) {
            throw new \Exception('uuid already exists');
        }
        if (!isset($this->availablePlaceHandler[TemplatePlace::class][$handler])) {
            throw new \Exception('TemplateHandler "' . $handler . '" unknown.');
        }

        $templatePlace = new TemplatePlace($uuid, $name, $scope, $handler, $pathAbsolute);
        $this->templatePlaces[$uuid] = $templatePlace;
    }

    public function registerMappingPlace($uuid, $name, $path)
    {
        // @TODO Check if path is inside FAL and add danger hint!
        $pathAbsolute = GeneralUtility::getFileAbsFileName($path);
        if (!is_dir($pathAbsolute) || !is_readable($pathAbsolute)) {
            throw new \Exception('path "' . $path . '" does not exist or is not readable');
        }
        if (isset($this->mappingPlaces[$uuid])) {
            throw new \Exception('uuid already exists');
        }

        $this->mappingPlaces[$uuid] = [
            'name' => $name,
            'pathAbs' => $pathAbsolute,
            'pathRel' => PathUtility::stripPathSitePrefix($pathAbsolute),
        ];
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

    public function registerPlaceHandler(
        /* @TODO */ $uuid,
        string $name,
        string $handlerClass,
        string $placeClass
    ) {
        $this->mustExistsAndImplements($placeClass, \Ppi\TemplaVoilaPlus\Domain\Model\Place::class);

        $this->mustExistsAndImplements($handlerClass, $placeClass::getHandlerInterface());

        if (isset($this->availablePlaceHandler[$placeClass][$uuid])) {
            throw new \Exception('uuid already exists for placeType ' . $placeClass);
        }

        $this->availablePlaceHandler[$placeClass][$uuid] = [
            'name' => $name,
            'class' => $handlerClass,
        ];
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

    public function isStaticDataStructureEnabled(): bool
    {
        if (version_compare(TYPO3_version, '9.0.0', '>=')) {
            if ($this->extConfig['staticDS']['enable']) {
                return true;
            }
        } else {
            if ($this->extConfig['staticDS.']['enable']) {
                return true;
            }
        }

        return false;
    }
}
