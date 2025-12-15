<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\ViewHelpers\Backend;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Calls BackendUtility::getItemLabel with given parameters
 */
class ItemLabelViewHelper extends AbstractViewHelper
{
    /**
     * No output escaping as some tags may be allowed
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * To ensure all tags are removed, child node's output must not be escaped
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Initialize ViewHelper arguments
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        $this->registerArgument('table', 'string', 'Name of table', true);
        $this->registerArgument('fieldName', 'string', 'Name of field', true);
    }

    /**
     * @return string
     */
    public function render()
    {
        return BackendUtility::getItemLabel($this->arguments['table'], $this->arguments['fieldName']);
    }
}
