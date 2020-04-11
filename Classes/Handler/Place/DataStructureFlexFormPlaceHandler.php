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

use Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure;
use Ppi\TemplaVoilaPlus\Domain\Model\DataStructurePlace;
use Ppi\TemplaVoilaPlus\Domain\Model\XmlFileDataStructure;

class DataStructureFlexFormPlaceHandler
    extends AbstractPlaceHandler
    implements DataStructurePlaceHandlerInterface /** @TDOD Needed? */
{
    public const NAME = 'templavoilaplus_handler_place_datastructure_flexform';

    public function __construct(DataStructurePlace $place)
    {
        parent::__construct($place);
    }

    protected function initializeConfigurations()
    {
        if ($this->configurations === null) {
            $this->configurations = [];
            $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();

            $filter = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter::class);
            $filter->setAllowedFileExtensions('xml');

            $folder = $resourceFactory->retrieveFileOrFolderObject($this->place->getPathAbsolute());
            $folder->setFileAndFolderNameFilters([[$filter, 'filterFileList']]);

            $files = $folder->getFiles(0, 0, \TYPO3\CMS\Core\Resource\Folder::FILTER_MODE_USE_OWN_FILTERS, true);

            foreach($files as $file) {
                $this->configurations[$file->getIdentifier()] = new XmlFileDataStructure($file);
            }
        }
    }
}
