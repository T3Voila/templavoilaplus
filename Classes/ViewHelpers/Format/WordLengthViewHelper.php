<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\ViewHelpers\Format;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Splits words by whitespace after given length
 */
class WordLengthViewHelper extends AbstractViewHelper
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
        $this->registerArgument('value', 'string', 'string to format');
        $this->registerArgument('maxCharacters', 'int', 'Place where to truncate the word', true);
    }

    /**
     * To ensure all tags are removed, child node's output must not be escaped
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Applies a preg_replace with maxCharacters on the specified value.
     *
     * @return string
     */
    public function render()
    {
        $value = $this->renderChildren();

        if (!is_string($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            return $value;
        }

        return  preg_replace('/(\S{' . $this->arguments['maxCharacters'] . '})/', '\1 ', (string)$value);
    }
}
