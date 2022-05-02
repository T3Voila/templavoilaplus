<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\ViewHelpers\Backend;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use Tvp\TemplaVoilaPlus\Exception\ConfigurationException;
use Tvp\TemplaVoilaPlus\Utility\ApiHelperUtility;

/**
 * Returns name of MappingConfiguration
 */
class LabelFromMappingConfigurationViewHelper extends AbstractViewHelper
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
        $this->registerArgument('identifier', 'string', 'Key of mapping', true);
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
        try {
            $mappingConfiguration = ApiHelperUtility::getMappingConfiguration($arguments['identifier']);
            return $mappingConfiguration->getName();
        } catch (ConfigurationException $e) {
            return $e->getMessage();
        }
    }
}
