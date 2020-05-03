<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

use Ppi\TemplaVoilaPlus\Service\ConfigurationService;
use Ppi\TemplaVoilaPlus\Utility\ApiHelperUtility;

/**
 * Like VariableViewHelper but against an array
 */
class RenderLayoutViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('combinedConfigurationIdentifier', 'string', '');
        $this->registerArgument('arguments', 'array', 'Array of variables to be transferred. Use {_all} for all variables', false, []);
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
        $combinedConfigurationIdentifier = $arguments['combinedConfigurationIdentifier'];
        $variables = (array) $arguments['arguments'];

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);

        $backendLayoutConfiguration = ApiHelperUtility::getBackendLayoutConfiguration($combinedConfigurationIdentifier);
        $renderer = $configurationService->getHandler($backendLayoutConfiguration->getRenderHandlerIdentifier());
        return $renderer->renderTemplate($backendLayoutConfiguration, $variables, []);
    }
}
