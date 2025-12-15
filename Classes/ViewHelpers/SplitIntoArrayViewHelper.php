<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Like VariableViewHelper but against an array
 */
class SplitIntoArrayViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        $this->registerArgument('value', 'mixed', 'Value to assign. If not in arguments then taken from tag content');
        $this->registerArgument('pattern', 'string', 'The delimiter/separator for the string as regex (mostly \R) without delimiter');
        $this->registerArgument('delimiterDecimal', 'int', 'A delimiter char as decimal number (like table_delimiter)');
        $this->registerArgument('limit', 'string', 'It will be split in limit elements as maximum');
    }

    /**
     * @return array
     */
    public function render()
    {
        $pattern = $this->arguments['pattern'] ?? ($this->arguments['delimiterDecimal'] ? '\x' . dechex($this->arguments['delimiterDecimal']) : '');
        $value = $this->arguments['value'] ?? $this->renderChildren() ?? '';
        $limit = $this->arguments['limit'] ?? -1;
        // mb_split default is -1

        $result = mb_split($pattern, $value, $limit);

        // Return empty array for false
        if ($result === false) {
            return [];
        }
        return $result;
    }
}
