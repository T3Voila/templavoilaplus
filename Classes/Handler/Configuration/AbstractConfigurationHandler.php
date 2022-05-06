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

use Tvp\TemplaVoilaPlus\Domain\Model\Place;
use Tvp\TemplaVoilaPlus\Handler\LoadSave\LoadSaveHandlerInterface;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractConfigurationHandler implements ConfigurationHandlerInterface
{
    /**
     * @var Place
     */
    protected $place;

    /**
     * @var LoadSaveHandlerInterface
     */
    protected $loadSaveHandler;

    public function setPlace(Place $place): void
    {
        $this->place = $place;
    }

    public function setLoadSaveHandler(LoadSaveHandlerInterface $loadSaveHandler): void
    {
        $this->loadSaveHandler = $loadSaveHandler;
    }
    /**
     * @var Logger
     */
    public $logger;

    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(self::class);
    }

    public function loadConfigurations(): void
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
                $this->logger->error('Unable to read the configuration from "' . $file . '"');
            }
        }

        $this->place->setConfigurations($configurations);
    }

    abstract public function createConfigurationFromConfigurationArray($configuration, $identifier, $possibleName);
}
