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
 * Controller to migrate/update the DataStructure
 * @TODO We need more migrations, see TcaMigration in TYPO3 Core
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class DataStructureUpdateController extends StepUpdateController
{
    protected $errors = [];
    protected $lastKey = '';

    protected function stepStart()
    {
        $dsRepo = GeneralUtility::makeInstance(DataStructureRepository::class);
        $this->fluid->assignMultiple([
            'dataStructures' => $dsRepo->getAll(),
        ]);
    }

    protected function stepFinal()
    {
        $handler = GeneralUtility::makeInstance(DataStructureUpdateHandler::class);
        $count = $handler->updateAllDs([], [[$this, 'fixWizardScript']]);

        $this->fluid->assignMultiple([
            'count' => $count,
            'errors' => $this->errors,
        ]);
    }

    public function fixWizardScript(array &$element)
    {
        $changed = false;

        if (isset($element['TCEforms']['config']['wizards'])) {
            foreach ($element['TCEforms']['config']['wizards'] as &$wizard) {
                $cleaned = false;

                // Convert ['script'] to ['module']['name']
                if (isset($wizard['script'])) {
                    if (!isset($wizard['module']['name'])) {
                        if (StringUtility::beginsWith($wizard['script'], 'browse_links.php')) {
                            $cleaned = true;
                            $wizard['module']['name'] = 'wizard_link';
                        } else {
                            $this->errors[] = 'Cannot fix wizard script: ' . $wizard['script'] . ' Key: ' . $this->lastKey;
                        }
                    } else {
                        $cleaned = true;
                    }

                    if ($cleaned) {
                        unset($wizard['script']);
                    }
                }

                // Convert ['module']['name'] = 'wizard_element_browser'
                // && ['module']['urlParameters']['mode'] = 'wizard'
                // to ['module']['name'] = 'wizard_link'
                if (isset($wizard['module']['name'])
                    && $wizard['module']['name'] === 'wizard_element_browser'
                    && isset($wizard['module']['urlParameters']['mode'])
                    && $wizard['module']['urlParameters']['mode'] === 'wizard'
                ) {
                    $wizard['module']['name'] = 'wizard_link';
                    unset ($wizard['module']['urlParameters']['mode']);
                    if (empty($wizard['module']['urlParameters'])) {
                        unset($wizard['module']['urlParameters']);
                    }
                    $cleaned = true;
                }

                $changed = $changed || $cleaned;
            }
        }
        return $changed;
    }
}
