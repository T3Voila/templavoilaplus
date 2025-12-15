<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\ViewHelpers;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Like VariableViewHelper but against an array
 */
class ArrayVariableViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        $this->registerArgument('value', 'mixed', 'Value to assign. If not in arguments then taken from tag content');
        $this->registerArgument('name', 'string', 'Name of array to create/enhance', true);
        $this->registerArgument('key', 'string', 'Name of array key to create', true);
    }

    /**
     * @return void
     */
    public function render()
    {
        $value = ($this->arguments['value'] ?? $this->renderChildren());

        $container = [];

        if ($this->renderingContext->getVariableProvider()->exists($this->arguments['name'])) {
            $container = $this->renderingContext->getVariableProvider()->get($this->arguments['name']);
        }

        $container = ArrayUtility::setValueByPath($container, $this->arguments['key'], $value, '.');

        $this->renderingContext->getVariableProvider()->add($this->arguments['name'], $container);
    }
}
