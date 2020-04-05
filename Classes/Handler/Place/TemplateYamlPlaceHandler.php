<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Handler\Place;

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
use TYPO3\CMS\Core\Utility\StringUtility;

use Ppi\TemplaVoilaPlus\Domain\Model\TemplatePlace;
use Ppi\TemplaVoilaPlus\Domain\Model\TemplateYamlConfiguration;

class TemplateYamlPlaceHandler implements TemplatePlaceHandlerInterface
{
    public const NAME = 'templavoilaplus_handler_place_template_yaml';

    /**
     * @var TemplatePlace
     */
    protected $place;

    /**
     * @var array|null Runtime cache for loaded template configurations
     */
    protected $templateConfigurations;


    public function __construct(TemplatePlace $place)
    {
        $this->place = $place;
    }

    public function getTemplates(): array
    {
        $this->initializeTemplateConfigurations();
        return $this->templateConfigurations;
    }

    public function getTemplateConfiguration(string $identifier): TemplateYamlConfiguration
    {
        $this->initializeTemplateConfigurations();
        return $this->templateConfigurations[$identifier];
    }

    protected function initializeTemplateConfigurations()
    {
        if ($this->templateConfigurations === null) {
            $this->templateConfigurations = [];
            $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();

            $filter = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter::class);
            // WTF? We cann't search for tvp.yaml so search for yaml and filter afterwards
            $filter->setAllowedFileExtensions('yaml');

            $folder = $resourceFactory->retrieveFileOrFolderObject($this->place->getPathAbsolute());
            $folder->setFileAndFolderNameFilters([[$filter, 'filterFileList']]);

            $files = $folder->getFiles(0, 0, \TYPO3\CMS\Core\Resource\Folder::FILTER_MODE_USE_OWN_FILTERS, true);

            foreach($files as $file) {
                if (StringUtility::endsWith($file->getName(), '.tvp.yaml')) {
                    try {
                        $this->templateConfigurations[$file->getIdentifier()] = new TemplateYamlConfiguration($file);
                    } catch (\Exception $e) {
                        // Empty as we can't process this file
                        // @TODO Maybe report into log
                    }
                }
            }
        }
    }
}
