<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * Like VariableViewHelper but against an array
 */
class SplitIntoArrayViewHelper extends AbstractViewHelper
{
    use CompileWithContentArgumentAndRenderStatic;

    /**
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('value', 'mixed', 'Value to assign. If not in arguments then taken from tag content');
        $this->registerArgument('pattern', 'string', 'The delimiter/separator for the string as regex (mostly \R) without delimiter');
        $this->registerArgument('delimiterDecimal', 'int', 'A delimiter char as decimal number (like table_delimiter)');
        $this->registerArgument('limit', 'string', 'It will be split in limit elements as maximum');
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
        $pattern = $arguments['pattern'] ?? ($arguments['delimiterDecimal'] ? '\x' . dechex($arguments['delimiterDecimal']) : '');
        $value = $arguments['value'] ?? $renderChildrenClosure();
        $limit = $arguments['limit'] ?? -1; // mb_split default is -1

        $result = mb_split($pattern, $value, $limit);

        // Return empty array for false
        if ($result === false) {
            return [];
        }
        return $result;
    }
}
