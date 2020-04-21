<?php
namespace Ppi\TemplaVoilaPlus\Controller\Backend\Update;

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
 * Controller to handle an update process in multiple steps.
 * Please hold in mind, the code is fragile the steps do not check if template is available for it.
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class StepUpdateController extends AbstractUpdateController
{
    /**
     * @return string The HTML to be shown.
     */
    public function run()
    {
        $step = GeneralUtility::_GP('step');

        if (!$this->stepExists($step)) {
            $step = 'start';
        }
        $this->runStep($step);

        return parent::run();
    }

    protected function runStep($step)
    {
        if (!$this->stepExists($step)) {
            throw new \Exception('Step not found');
        }

        $stepFunction = 'step' . ucfirst($step);
        $switchStep = $this->$stepFunction();

        if ($switchStep !== null) {
            $this->runStep($switchStep);
        } else {
            $this->setStepTemplate($step);
        }
    }

    public function stepExists($step)
    {
        return method_exists($this, 'step' . ucfirst($step));
    }

    public function setStepTemplate($step)
    {
        $this->setTemplate(
            $this->getTemplate()
            . 'Step' . ucfirst($step)
        );
    }

    protected function stepStart()
    {
    }

    protected function stepFinal()
    {
    }
}
