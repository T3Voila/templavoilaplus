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

use Tvp\TemplaVoilaPlus\Domain\Model\TemplateConfiguration;
use Tvp\TemplaVoilaPlus\Domain\Model\Place;
use Tvp\TemplaVoilaPlus\Handler\LoadSave\LoadSaveHandlerInterface;

class TemplateConfigurationHandler implements ConfigurationHandlerInterface
{
    public static $identifier = 'TVP\ConfigurationHandler\TemplateConfiguration';

    /**
     * @var Place
     */
    protected $place;

    /**
     * @var LoadSaveHandlerInterface
     */
    protected $loadSaveHandler;

    public function setPlace(Place $place)
    {
        $this->place = $place;
    }

    public function setLoadSaveHandler(LoadSaveHandlerInterface $loadSaveHandler)
    {
        $this->loadSaveHandler = $loadSaveHandler;
    }

    /** @TODO it may be possible that this could go into an abstract */
    public function loadConfigurations()
    {
        $configurations = [];
        $files = $this->loadSaveHandler->find();

        /** @TODO No, we don't know if this are files, this may be something totaly different! */
        foreach ($files as $file) {
            $content = $this->loadSaveHandler->load($file);

            $identifier = $file->getRelativePath() . $file->getFilename();

            try {
                $mappingConfiguration = $this->createConfigurationFromConfigurationArray(
                    $content,
                    $identifier,
                    pathinfo($file->getFilename(), PATHINFO_FILENAME)
                );
                $configurations[$identifier] = [
                    'configuration' => $mappingConfiguration,
                    'store' => ['file' => $file], /** @TODO Better place to save this information? */
                ];
            } catch (\Exception $e) {
                /** @TODO log error, that we can't read the configuration */
            }
        }

        $this->place->setConfigurations($configurations);
    }

    public function createConfigurationFromConfigurationArray(array $configuration, $identifier, $possibleName): TemplateConfiguration
    {
        $templateConfiguration = new TemplateConfiguration($this->place, $identifier);
        $templateConfiguration->setName($possibleName);

        if (!isset($configuration['tvp-template'])) {
            throw new \Exception('No TemplaVoilà! Plus template configuration');
        }

        if (isset($configuration['tvp-template']['meta']['name'])) {
            $templateConfiguration->setName($configuration['tvp-template']['meta']['name']);
        }
        if (isset($configuration['tvp-template']['meta']['renderer'])) {
            /** @TODO check before setting */
            $templateConfiguration->setRenderHandlerIdentifier($configuration['tvp-template']['meta']['renderer']);
        }
        if (isset($configuration['tvp-template']['meta']['template'])) {
            /**
             * @TODO check before setting
             * @TODO Relative to Place or configuration file? Support Absolute or 'EXT:' (insecure?)
             */
            $templateConfiguration->setTemplateFileName($configuration['tvp-template']['meta']['template']);
        }
        if (isset($configuration['tvp-template']['header']) && is_array($configuration['tvp-template']['header'])) {
            $templateConfiguration->setHeader($configuration['tvp-template']['header']);
        }
        if (isset($configuration['tvp-template']['mapping']) && is_array($configuration['tvp-template']['mapping'])) {
            $templateConfiguration->setMapping($configuration['tvp-template']['mapping']);
        }

        return $templateConfiguration;
    }
}
