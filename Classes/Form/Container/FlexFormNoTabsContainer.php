<?php

namespace Tvp\TemplaVoilaPlus\Form\Container;

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

use TYPO3\CMS\Backend\Form\Container\AbstractContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handle a flex form that has no tabs.
 *
 * This container is called by FlexFormEntryContainer if only a default sheet
 * exists. It evaluates the display condition and hands over rendering of single
 * fields to FlexFormElementContainer.
 */
class FlexFormNoTabsContainer extends AbstractContainer
{
    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $table = $this->data['tableName'];
        $row = $this->data['databaseRow'];
        $fieldName = $this->data['fieldName']; // field name of the flex form field in DB
        $parameterArray = $this->data['parameterArray'];
        $flexFormDataStructureArray = $this->data['flexFormDataStructureArray'];
        $flexFormCurrentLanguage = $this->data['flexFormCurrentLanguage'];
        $flexFormRowData = $this->data['flexFormRowData'];
        $resultArray = $this->initializeResultArray();

        // Flex ds was normalized in flex provider to always have a sheet.
        // Determine this single sheet name, most often it ends up with sDEF, except if only one sheet was defined
        $flexFormSheetNames = array_keys($flexFormDataStructureArray['sheets']);
        $sheetName = array_pop($flexFormSheetNames);
        $flexFormRowDataSubPart = ($flexFormRowData['data'][$sheetName][$flexFormCurrentLanguage] ?? '');

        // That was taken from GeneralUtility::resolveSheetDefInDS - no idea if it is important
        unset($flexFormDataStructureArray['meta']);

        if (!is_array($flexFormDataStructureArray['sheets'][$sheetName]['ROOT']['el'])) {
            $resultArray['html'] = 'Data Structure ERROR: No [\'ROOT\'][\'el\'] element found in flex form definition.';
            return $resultArray;
        }

        // Assemble key for loading the correct CSH file
        // @TODO what is that good for? That is for the title of single elements ... see FlexFormElementContainer!
        $dsPointerFields = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['columns'][$fieldName]['config']['ds_pointerField'], true);
        $parameterArray['_cshKey'] = $table . '.' . $fieldName;
        foreach ($dsPointerFields as $key) {
            if (is_string($row[$key]) && $row[$key] !== '') {
                $parameterArray['_cshKey'] .= '.' . $row[$key];
            } elseif (is_array($row[$key]) && isset($row[$key][0]) && is_string($row[$key][0]) && $row[$key][0] !== '') {
                $parameterArray['_cshKey'] .= '.' . $row[$key][0];
            }
        }

        $options = $this->data;
        $options['flexFormDataStructureArray'] = $flexFormDataStructureArray['sheets'][$sheetName][$flexFormCurrentLanguage]['ROOT']['el'];
        $options['flexFormRowData'] = $flexFormRowDataSubPart;
        $options['flexFormSheetName'] = $sheetName;
        $options['flexFormFormPrefix'] = '[data][' . $sheetName . '][' . $flexFormCurrentLanguage . ']';
        $options['parameterArray'] = $parameterArray;

        $options['renderType'] = 'flexFormElementContainer';
        return $this->nodeFactory->create($options)->render();
    }
}
