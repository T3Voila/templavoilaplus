<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\ViewHelpers\Backend;

use Tvp\TemplaVoilaPlus\Exception\ConfigurationException;
use Tvp\TemplaVoilaPlus\Exception\MissingPlacesException;
use Tvp\TemplaVoilaPlus\Utility\ApiHelperUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Returns name of MappingConfiguration
 */
class LabelFromMappingConfigurationViewHelper extends AbstractViewHelper
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
        $this->registerArgument('identifier', 'string', 'Key of mapping', true);
    }

    /**
     * @return string
     */
    public function render()
    {
        try {
            $mappingConfiguration = ApiHelperUtility::getMappingConfiguration($this->arguments['identifier']);
            return $mappingConfiguration->getName();
        } catch (ConfigurationException | MissingPlacesException $e) {
            return $e->getMessage();
        }
    }
}
