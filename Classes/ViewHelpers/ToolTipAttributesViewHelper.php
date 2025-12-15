<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Calls BackendUtility::getItemLabel with given parameters
 */
class ToolTipAttributesViewHelper extends AbstractViewHelper
{
    /**
     * No output escaping as some tags may be allowed
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize ViewHelper arguments
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        $this->registerArgument('text', 'string', 'String to display', true);
        $this->registerArgument('html', 'bool', 'HTML content', false, 'false');
        $this->registerArgument('placement', 'string', 'left or right', false, 'right');
    }

    /**
     * To ensure all tags are removed, child node's output must not be escaped
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * @return string
     */
    public function render()
    {
        return sprintf(
            'data-bs-title="%s" data-bs-toggle="tooltip" data-bs-placement="%s" data-bs-html="%s"',
            $this->arguments['text'],
            $this->arguments['placement'],
            $this->arguments['html']
        );
    }
}
