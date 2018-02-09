<?php
namespace Ppi\TemplaVoilaPlus\Hooks;

use \TYPO3\CMS\Core\Database\TableConfigurationPostProcessingHookInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

class TableConfigurationPostProcessingHook implements TableConfigurationPostProcessingHookInterface
{
    public function processData()
    {
        $this->registerFormEngineContainer();
        $this->registerFormEngineProviders();
        $this->registerHookFormEngine();
    }

    public function registerFormEngineContainer()
    {
        // Register language aware flex form handling in FormEngine
        // Register render elements
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1443361297] = [
            'nodeName' => 'flex',
            'priority' => 40,
            'class' => \Ppi\TemplaVoilaPlus\Form\Container\FlexFormEntryContainer::class,
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1443361298] = [
            'nodeName' => 'flexFormNoTabsContainer',
            'priority' => 40,
            'class' => \Ppi\TemplaVoilaPlus\Form\Container\FlexFormNoTabsContainer::class,
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1443361299] = [
            'nodeName' => 'flexFormTabsContainer',
            'priority' => 40,
            'class' => \Ppi\TemplaVoilaPlus\Form\Container\FlexFormTabsContainer::class,
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1443361300] = [
            'nodeName' => 'flexFormElementContainer',
            'priority' => 40,
            'class' => \Ppi\TemplaVoilaPlus\Form\Container\FlexFormElementContainer::class,
        ];
        if (version_compare(TYPO3_version, '8.5.0', '>=')) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1443361301] = [
                'nodeName' => 'flexFormSectionContainer',
                'priority' => 40,
                'class' => \Ppi\TemplaVoilaPlus\Form\Container\FlexFormSectionContainer::class,
            ];
        }
    }

    public function registerFormEngineProviders()
    {
        // Unregister stock TcaFlex* data provider and substitute with own data provider at the same dependency position
        \Ppi\TemplaVoilaPlus\Utility\FormEngineUtility::replaceInFormDataGroups(
            [
                \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class
                    => \Ppi\TemplaVoilaPlus\Form\FormDataProvider\TcaFlexProcess::class,
                \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexFetch::class
                    => \Ppi\TemplaVoilaPlus\Form\FormDataProvider\TcaFlexFetch::class,
                \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class
                    => \Ppi\TemplaVoilaPlus\Form\FormDataProvider\TcaFlexPrepare::class,
                // Maybe there is compatibility6 installed, then also set it to us!
                \TYPO3\CMS\Compatibility6\Form\FormDataProvider\TcaFlexProcess::class
                    => \Ppi\TemplaVoilaPlus\Form\FormDataProvider\TcaFlexProcess::class,
            ]
        );
        if (version_compare(TYPO3_version, '8.5.0', '>=')) {
            \Ppi\TemplaVoilaPlus\Utility\FormEngineUtility::replaceInFormDataGroups(
                [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\EvaluateDisplayConditions::class
                        => \Ppi\TemplaVoilaPlus\Form\FormDataProvider\EvaluateDisplayConditions::class,
                ]
            );
        }
        // In TYPO3 8 there is no TcaFlexFetch, so readd it.
        \Ppi\TemplaVoilaPlus\Utility\FormEngineUtility::addTcaFlexFetch();
    }

    public function registerHookFormEngine()
    {
        if (version_compare(TYPO3_version, '8.5.0', '>=')) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class]['className']
                = \Ppi\TemplaVoilaPlus\Configuration\FlexForm\FlexFormTools8::class;
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class]['className']
                = \Ppi\TemplaVoilaPlus\Configuration\FlexForm\FlexFormTools::class;
        }
    }
}
