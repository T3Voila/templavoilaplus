<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\ViewHelpers\Backend;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Calls BackendUtility::getItemLabel with given parameters
 */
class ThumbCodeViewHelper extends AbstractViewHelper
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
        $this->registerArgument('row', 'array', 'Content of row', true);
        $this->registerArgument('table', 'string', 'Name of table', true);
        $this->registerArgument('fieldName', 'string', 'Name of field', true);
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
     * @see https://www.php.net/manual/function.strip-tags.php
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        return BackendUtility::thumbCode(
            $arguments['row'],
            $arguments['table'],
            $arguments['fieldName'],
            '',
            '',
            null,
            0,
            '',
            '',
            false // No $linkInfoPopup
        );
    }
}

