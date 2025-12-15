<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\ViewHelpers;

use Tvp\TemplaVoilaPlus\Service\ConfigurationService;
use Tvp\TemplaVoilaPlus\Utility\ApiHelperUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Like VariableViewHelper but against an array
 */
class RenderLayoutViewHelper extends AbstractViewHelper
{
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
     * @return string
     */
    public function render()
    {
        $combinedConfigurationIdentifier = $this->arguments['combinedConfigurationIdentifier'];
        $variables = (array)$this->arguments['arguments'];
        $subpart = (string)$this->arguments['subpart'];

        if ($subpart) {
            $variables['__SUBPART__'] = $subpart;
        }

        /** Add scoped variables ('settings') to container */
        $variables = $this->renderingContext->getVariableProvider()->getScopeCopy($variables)->getAll();

        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);

        $backendLayoutConfiguration = ApiHelperUtility::getBackendLayoutConfiguration($combinedConfigurationIdentifier);
        $renderer = $configurationService->getHandler($backendLayoutConfiguration->getRenderHandlerIdentifier());
        return $renderer->renderTemplate($backendLayoutConfiguration, $variables, [], $this->renderingContext->getRequest());
    }
}
