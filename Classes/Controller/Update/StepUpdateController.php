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

/**
 * Controller to show the switch dialog.
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

        if ($step !== 'start'
            && $step !== 'final'
            && !is_numeric($step)
            && !$this->stepExists($step)
        ) {
            $step = 'start';
        }

        $stepFunction = 'step' . ucfirst($step);
        $this->$stepFunction();
        $this->setStepTemplate($step);

        return parent::run();
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
