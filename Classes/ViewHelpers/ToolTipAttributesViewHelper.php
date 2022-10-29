<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Calls BackendUtility::getItemLabel with given parameters
 */
class ToolTipAttributesViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

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
     * Applies strip_tags() on the specified value.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     *
     * @return string
     * @see https://www.php.net/manual/function.strip-tags.php
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        if (version_compare(TYPO3_version, '11.0.0', '>=')) {
            return sprintf(
                'data-bs-title="%s" data-bs-toggle="tooltip" data-bs-placement="%s" data-bs-html="%s"',
                $arguments['text'],
                $arguments['placement'],
                $arguments['html']
            );
        }
        return sprintf(
            'data-title="%s" data-toggle="tooltip" data-placement="%s" data-html="%s"',
            $arguments['text'],
            $arguments['placement'],
            $arguments['html']
        );
    }
}
