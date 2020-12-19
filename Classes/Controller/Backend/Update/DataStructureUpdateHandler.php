<?php

namespace Tvp\TemplaVoilaPlus\Controller\Backend\Update;

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
use Tvp\TemplaVoilaPlus\Domain\Repository\DataStructureRepository;
use Tvp\TemplaVoilaPlus\Domain\Repository\TemplateRepository;
use Tvp\TemplaVoilaPlus\Utility\DataStructureUtility;

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

    public function updateAllToLocals(array $rootCallbacks, array $elementCallbacks)
    {
        $count = 0;

        $tsRepo = GeneralUtility::makeInstance(TemplateRepository::class);
        foreach ($tsRepo->getAll() as $to) {
            if ($this->updateTo($to, $rootCallbacks, $elementCallbacks)) {
                $count++;
            }
        }

        return $count;
    }

    public function updateDs(
        \Tvp\TemplaVoilaPlus\Domain\Model\AbstractDataStructure $ds,
        array $rootCallbacks,
        array $elementCallbacks
    ) {
        $data = $ds->getDataStructureArray();

        $changed = $this->processUpdate($data, $rootCallbacks, $elementCallbacks);

        if ($changed) {
            $this->saveChange(
                $ds->getKey(),
                'tx_templavoilaplus_datastructure',
                'dataprot',
                DataStructureUtility::array2xml($data),
                $ds->isFilebased()
            );
            return true;
        }
        return false;
    }

    public function updateTo(
        \Tvp\TemplaVoilaPlus\Domain\Model\Template $to,
        array $rootCallbacks,
        array $elementCallbacks
    ) {
        $data = $to->getLocalDataprotArray(true);

        $changed = $this->processUpdate($data, $rootCallbacks, $elementCallbacks);

        if ($changed) {
            $this->saveChange(
                $to->getKey(),
                'tx_templavoilaplus_tmplobj',
                'localprocessing',
                DataStructureUtility::array2xml($data)
            );
            return true;
        }
        return false;
    }


    public function processUpdate(
        array &$data,
        array $rootCallbacks,
        array $elementCallbacks
    ) {
        $changed = false;

        if (empty($data)) {
            return false;
        }

        foreach ($rootCallbacks as $callback) {
            if (is_callable($callback)) {
                $changed = $callback($data) || $changed;
            } else {
                throw new \Exception('Callback function "' . $callback[1] . '" not available. Cann\'t update DataStructure.');
            }
        }

        foreach ($data['ROOT']['el'] as &$element) {
            $changed = $this->fixPerElement($element, $elementCallbacks) || $changed;
        }

        return $changed;
    }

    protected function saveChange($key, $table, $field, $dataProtXML, $filebased = false)
    {
        if ($filebased) {
            $path = PATH_site . $key;
            GeneralUtility::writeFile($path, $dataProtXML);
        } else {
            $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
            $tce->stripslashes_values = 0;

            $dataArr = [];
            $dataArr[$table][$key][$field] = $dataProtXML;

            // process data
            $tce->start($dataArr, []);
            $tce->process_datamap();
        }
    }

    protected function fixPerElement(array &$element, array $elementCallbacks)
    {
        $changed = false;

        foreach ($elementCallbacks as $callback) {
            if (is_callable($callback)) {
                $changed = $callback($element) || $changed;
            } else {
                throw new \Exception('Callback function "' . $callback[1] . '" not available. Cann\'t update DataStructure.');
            }
        }

        if (isset($element['type']) && $element['type'] === 'array') {
            if (is_array($element['el'])) {
                foreach ($element['el'] as &$subElement) {
                    $changed = $this->fixPerElement($subElement, $elementCallbacks) || $changed;
                }
            }
        }

        return $changed;
    }
}
