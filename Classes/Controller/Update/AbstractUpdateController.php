<?php
namespace Ppi\TemplaVoilaPlus\Controller\Update;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract Controller for Update Scripts
 *
 * @author Alexander Opitz <opitz.alexander@pluspol-interactive.de>
 */
class AbstractUpdateController
{
    /**
     * @var \TYPO3\CMS\Fluid\View\StandaloneView
     */
    protected $fluid;

    /**
     * Filename of template to view in fluid
     *
     * @var string
     */
    protected $template;

    /**
     * holds the extconf configuration
     *
     * @var array
     */
    protected $extConf;

    public function __construct()
    {
        $this->fluid = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
        $this->fluid->setPartialRootPaths([
            GeneralUtility::getFileAbsFileName('EXT:templavoilaplus/Resources/Private/Partials/')
        ]);
        $this->fluid->setTemplateRootPaths([
            GeneralUtility::getFileAbsFileName('EXT:templavoilaplus/Resources/Private/Templates/')
        ]);
        $classPartsName = explode('\\', get_class($this));
        $this->setTemplate('Update/' . substr(array_pop($classPartsName), 0, -16));

        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoilaplus']);
    }

    public function setTemplate($template)
    {
        $this->template = $template;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return string The HTML to be shown.
     */
    public function run()
    {
        $this->fluid->setTemplate($this->template);
        $this->fluid->assignMultiple([
            'is85OrNewer' => version_compare(TYPO3_version, '8.5.0', '>=') ? true : false,
            'typo3Version' => TYPO3_version,
            'useStaticDS' => ($this->extConf['staticDS.']['enable']),
        ]);
        return $this->fluid->render();
    }
}
