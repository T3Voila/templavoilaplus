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
 * Like core:icon, but replaces the icon label with pre10LTS variant if on 8LTS or 9LTS
 */
class IconViewHelper extends \TYPO3\CMS\Core\ViewHelpers\IconViewHelper
{
    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {

        if (version_compare(TYPO3_version, '9.99.0', '<=')) {
            $arguments['identifier'] = static::switchIconIdentifierWith9Variant($arguments['identifier']);
        }
        return \TYPO3\CMS\Core\ViewHelpers\IconViewHelper::renderStatic($arguments,$renderChildrenClosure,$renderingContext);
    }

    protected static function switchIconIdentifierWith9Variant(string $identifier) {
        $mapping10to9 = [
            'actions-document-add' => 'actions-document-new',
            'actions-clipboard' => 'actions-edit-copy',
            'actions-cog-alt' =>'actions-system-extension-configure'
        ];
        return $mapping10to9[$identifier] ?? $identifier;
    }
}
