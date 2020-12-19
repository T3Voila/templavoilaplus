<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * Like VariableViewHelper but against an array
 */
class ArrayVariableViewHelper extends AbstractViewHelper
{
    use CompileWithContentArgumentAndRenderStatic;

    /**
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('value', 'mixed', 'Value to assign. If not in arguments then taken from tag content');
        $this->registerArgument('name', 'string', 'Name of array to create/enhance', true);
        $this->registerArgument('key', 'string', 'Name of array key to create', true);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return null
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $value = $renderChildrenClosure();

        $container = [];

        if ($renderingContext->getVariableProvider()->exists($arguments['name'])) {
            $container = $renderingContext->getVariableProvider()->get($arguments['name']);
        }

        $container[$arguments['key']] = $value;

        $renderingContext->getVariableProvider()->add($arguments['name'], $container);
    }
}
