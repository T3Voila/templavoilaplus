<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\ViewHelpers;

use Tvp\TemplaVoilaPlus\Service\ConfigurationService;
use Tvp\TemplaVoilaPlus\Utility\ApiHelperUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Like VariableViewHelper but against an array
 */
class RenderLayoutViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('combinedConfigurationIdentifier', 'string', '');
        $this->registerArgument('arguments', 'array', 'Array of variables to be transferred. Use {_all} for all variables', false, []);
        $this->registerArgument('subpart', 'string', 'Subpart to process', false, '');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $combinedConfigurationIdentifier = $arguments['combinedConfigurationIdentifier'];
        $variables = (array)$arguments['arguments'];
        $subpart = (string)$arguments['subpart'];
        $variables['__SUBPART__'] = $subpart;

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);

        $backendLayoutConfiguration = ApiHelperUtility::getBackendLayoutConfiguration($combinedConfigurationIdentifier);
        $renderer = $configurationService->getHandler($backendLayoutConfiguration->getRenderHandlerIdentifier());
        return $renderer->renderTemplate($backendLayoutConfiguration, $variables, []);
    }
}
