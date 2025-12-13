<?php

namespace Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\Update;

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

use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Controller to migrate/update the DataStructure for TYPO3 v13 LTS
 *
 * @author Alexander Opitz <opitz.alexander@googlemail.com>
 */
class DataStructureV13Controller extends AbstractUpdateController
{
    protected $errors = [];

    protected function stepStartAction()
    {
        return $this->moduleTemplate->renderResponse('Backend/ControlCenter/Update/DataStructureV13/StepStart');
    }

    protected function stepFinalAction()
    {
        /** @var DataStructureUpdateHandler */
        $handler = GeneralUtility::makeInstance(DataStructureUpdateHandler::class);
        $count = $handler->updateAllDs(
            [
                // TCA migrations
                [$this, 'migrateT3EditorToCodeEditorDS'],
            ],
            [
                // TCA migrations
                [$this, 'removeMmHasUidField'],
                [$this, 'migrateT3EditorToCodeEditor'],
            ]
        );

        $this->moduleTemplate->assignMultiple([
            'count' => $count,
            'hasErrors' => !empty($this->errors),
            'errors' => $this->errors,
        ]);
        return $this->moduleTemplate->renderResponse('Backend/ControlCenter/Update/DataStructureV13/StepFinal');
    }

    public function migrateT3EditorToCodeEditorDS(array &$dataStructure): bool
    {
        $changed = false;

        foreach ($dataStructure['types'] ?? [] as $typeName => $typeConfig) {
            foreach ($typeConfig['columnsOverrides'] ?? [] as $columnOverride => $columnOverrideConfig) {
                if (($columnOverrideConfig['config']['renderType'] ?? '') === 't3editor') {
                    $dataStructure['types'][$typeName]['columnsOverrides'][$columnOverride]['config']['renderType'] = 'codeEditor';

                    $changed = true;
                }
            }
        }

        return $changed;
    }

    public function removeMmHasUidField(array &$fieldConfig): bool
    {
        if (isset($fieldConfig['config']['MM_hasUidField'])) {
            unset($fieldConfig['config']['MM_hasUidField']);
            return true;
        }
        return false;
    }

    public function migrateT3EditorToCodeEditor(array &$fieldConfig): bool
    {
        if (($fieldConfig['config']['renderType'] ?? '') === 't3editor') {
            $fieldConfig['config']['renderType'] = 'codeEditor';

            return true;
        }

        return false;
    }
}
