<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Controller\Backend\Ajax;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Tvp\TemplaVoilaPlus\Service\ConfigurationService;

class ContentElementWizard extends AbstractResponse
{
    // All for Wizard
    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function wizardAction(ServerRequestInterface $request): ResponseInterface
    {
        $contentElements = $this->getContentElements($request);
        $contentElementsConfig = $this->convertContentElementsWizardArray($contentElements);
        $contentElementsConfig = $this->modifyContentElementsConfig($contentElementsConfig);
        $contentElementsConfig = $this->convertParamsValue($contentElementsConfig);

        $view = $this->getFluidTemplateObject('EXT:templavoilaplus/Resources/Private/Templates/Backend/Ajax/ContentElements.html');
        $view->assign('contentElementsConfig', $contentElementsConfig);

        return new HtmlResponse($view->render());
    }

    private function getContentElements(ServerRequestInterface $request): array
    {
        if (version_compare(TYPO3_version, '11.2.0', '>=')) {
            $extended = new ExtendedNewContentElementController(
                GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class),
                GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class),
                GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class),
                GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\ModuleTemplateFactory::class)
            );
        } else {
            $extended = new ExtendedNewContentElementController();
        }
        return $extended->getWizardsByRequest($request);
    }

    /**
     * Converts the wizards array from TYPO3 Cores NewContentElementWizard into a better manageable array with
     * subarray. The overrides and simpleView can be handled better inside.
     *
     * @param array The array result from getWizards with wizardItemsHook
     * @result array An array with the contentElements as subArray inside the "tabs" elements
     */
    private function convertContentElementsWizardArray(array $contentElements): array
    {
        $contentElementsConfig = [];
        $sectionKey = '#';
        foreach ($contentElements as $key => $elementItem) {
            if (isset($elementItem['header'])) {
                // Section Element
                $sectionKey = $key;
                $contentElementsConfig[$sectionKey] = [
                    'label' => $elementItem['header'] ?: '-',
                    'contentElements' => [],
                ];
            } else {
                // Real Content Element
                $contentElementsConfig[$sectionKey]['contentElements'][$key] = $elementItem;
            }
        }

        return $contentElementsConfig;
    }

    /**
     * Modiefies our contentElementsConfiguration array with the overwrites and SimpleView values from
     * the "Theme" extension configurations.
     *
     * @TODO Refactor to lower complexity/possible bug rate
     *
     * @param array Our contentElementsConfiguration
     * @result array The updated/manipulated contentElementsConfiguration
     */
    private function modifyContentElementsConfig(array $contentElementsConfig): array
    {
        /** @var ConfigurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $newContentElementWizardConfiguration = $configurationService->getNewContentElementWizardConfiguration();

        $newContentElementsConfig = $contentElementsConfig;

        if (isset($newContentElementWizardConfiguration['overwrites'])) {
            foreach($newContentElementWizardConfiguration['overwrites'] as $tabKey => $overwrite) {
                if (!isset($contentElementsConfig[$tabKey])) {
                    $newContentElementsConfig[$tabKey] = [
                        'label' => $tabKey,
                    ];
                }
                // Manage unset of an tab/menu
                if (isset($overwrite['unset']) && $overwrite['unset']) {
                    unset($newContentElementsConfig[$tabKey]);
                }
                // Manage ordering before
                if (isset($overwrite['before'])) {
                    $newContentElementsConfig[$tabKey]['before'] = $overwrite['before'];
                }
                // Manage ordering before
                if (isset($overwrite['after'])) {
                    $newContentElementsConfig[$tabKey]['after'] = $overwrite['after'];
                }
                // Manage move
                if (isset($overwrite['move'])) {
                    foreach($overwrite['move'] as $elementKey => $position) {
                        if (isset($newContentElementsConfig[$tabKey]['contentElements'][$elementKey])) {
                            // Put into new position
                            $contentElementConfig = $newContentElementsConfig[$tabKey]['contentElements'][$elementKey];

                            if (isset($position['before'])) {
                                $contentElementConfig['before'] = $position['before'];
                            }
                            if (isset($position['after'])) {
                                $contentElementConfig['after'] = $position['after'];
                            }

                            $newContentElementsConfig[$position['tab']]['contentElements'][$elementKey] = $contentElementConfig;
                            unset($newContentElementsConfig[$tabKey]['contentElements'][$elementKey]);
                        }
                    }
                }
            }
        }

        /** @var DependencyOrderingService */
        $dependencyOrderingService = GeneralUtility::makeInstance(DependencyOrderingService::class);
        $newContentElementsConfig = $dependencyOrderingService->orderByDependencies($newContentElementsConfig);

        // Do dependency ordering inside the tabs or unset tab if empty */
        foreach ($newContentElementsConfig as $tabKey => $tabConfig) {
            if (count($newContentElementsConfig[$tabKey]['contentElements']) === 0) {
                unset($newContentElementsConfig[$tabKey]);
            } else {
                $newContentElementsConfig[$tabKey]['contentElements']
                    = $dependencyOrderingService->orderByDependencies($tabConfig['contentElements']);
            }
        }

        return $newContentElementsConfig;
    }

    /**
     * @param array Our contentElementsConfiguration
     * @result array The updated/manipulated contentElementsConfiguration
     */
    private function convertParamsValue(array $contentElementsConfig): array
    {
        foreach ($contentElementsConfig as $tabKey => $tabConfig) {
            foreach ($tabConfig['contentElements'] as $_key => $contentElement) {
                $contentElement['element-row'] = [];

                parse_str($contentElement['params'], $contentElementParams);
                if (isset($contentElementParams['defVals']['tt_content'])) {
                    $contentElement['element-row'] = $contentElementParams['defVals']['tt_content'];
                }
                $contentElementsConfig[$tabKey]['contentElements'][$_key] = $contentElement;
            }
        }
        return $contentElementsConfig;
    }
}

class ExtendedNewContentElementController extends \TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController
{
    /**
     * Returns the array of elements in the wizard display.
     * For the plugin section there is support for adding elements there from a global variable.
     *
     * @return array
     */
    public function getWizardsByRequest(ServerRequestInterface $request): array
    {
        $this->init($request);
        $wizardItems = $this->getWizards();

        // Hook for manipulating wizardItems, wrapper, onClickEvent etc.
        // Yes, thats done outside the function wich gathers the wizards!
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'] ?? [] as $className) {
            /** @var NewContentElementWizardHookInterface */
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof NewContentElementWizardHookInterface) {
                throw new \UnexpectedValueException(
                    $className . ' must implement interface ' . NewContentElementWizardHookInterface::class,
                    1227834741
                );
            }
            $hookObject->manipulateWizardItems($wizardItems, $this);
        }

        return $wizardItems;
    }
}

