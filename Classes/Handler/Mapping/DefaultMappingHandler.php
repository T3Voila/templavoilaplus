<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Handler\Mapping;

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

/**
 * @TODO
 * -Interface
 * -Registration
 * -More needed besides this Default one?
 * - Its hard wired till yet
 */
class DefaultMappingHandler
{
    protected $mappingConfiguration;

    public function __construct($mappingConfiguration)
    {
        $this->mappingConfiguration = $mappingConfiguration;
    }

    public function process($flexformData, $row): array
    {
        $processedMapping = [];
        $mappingToTemplate = $this->mappingConfiguration->getMappingToTemplate();

        foreach($mappingToTemplate as $templateFieldName => $instructions) {
            if ($instructions['fieldType'] === 'row') {
                $processedValue = $row[$instructions['fieldName']] ? : '';
            }
            $processedMapping[$templateFieldName] = $processedValue;
        }

        return $processedMapping;
    }
}
