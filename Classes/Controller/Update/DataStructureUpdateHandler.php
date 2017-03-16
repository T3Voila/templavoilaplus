<?php
namespace Ppi\TemplaVoilaPlus\Controller\Update;

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

use Ppi\TemplaVoilaPlus\Domain\Repository\DataStructureRepository;

/**
 * Handles Updates in DataStructure via Callbacks
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class DataStructureUpdateHandler
{
    public function updateAllDs(array $rootCallbacks, array $elementCallbacks)
    {
        $count = 0;

        $dsRepo = GeneralUtility::makeInstance(DataStructureRepository::class);
        foreach ($dsRepo->getAll() as $ds) {
            if ($this->updateDs($ds, $rootCallbacks, $elementCallbacks)) {
                $count++;
            }
        }

        return $count;
    }

    public function updateDs(
        \Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure $ds,
        array $rootCallbacks,
        array $elementCallbacks
    ) {
        $changed = false;
        $data = $ds->getDataprotArray();
        $this->lastKey = $ds->getKey();

        foreach ($rootCallbacks as $callback) {
            if (is_callable($callback)) {
                $changed = $callback($data) || $changed;
            }
        }

        foreach($data['ROOT']['el'] as &$element) {
            $changed = $this->fixPerElement($element, $elementCallbacks) || $changed;
        }

        if ($changed) {
            $this->saveChange(
                $ds,
                GeneralUtility::array2xml_cs(
                    $data,
                    'T3DataStructure',
                    ['useCDATA' => 1]
                )
            );
            return true;
        }
        return false;
    }

    protected function saveChange($ds, $dataProtXML)
    {
        if ($ds->isFilebased()) {
            $path = PATH_site . $ds->getKey();
            GeneralUtility::writeFile($path, $dataProtXML);
        } else {
            $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
            $tce->stripslashes_values = 0;

            $dataArr = [];
            $dataArr['tx_templavoilaplus_datastructure'][$ds->getKey()]['dataprot'] = $dataProtXML;

            // process data
            $tce->start($dataArr, array());
            $tce->process_datamap();
        }
    }

    protected function fixPerElement(array &$element, array $elementCallbacks)
    {
        $changed = false;

        foreach ($elementCallbacks as $callback) {
            if (is_callable($callback)) {
                $changed = $callback($element) || $changed;
            }
        }

        if (isset($element['type']) && $element['type'] === 'array') {
            if (is_array($element['el'])) {
                foreach($element['el'] as &$subElement) {
                    $changed = $this->fixPerElement($subElement, $elementCallbacks) || $changed;
                }
            }
        }

        return $changed;
    }
}
