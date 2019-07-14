<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Controller\Backend\ControlCenter;

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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\Page\PageRepository;

use Ppi\TemplaVoilaPlus\Configuration\BackendConfiguration;
use Ppi\TemplaVoilaPlus\Service\ConfigurationService;
use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

class DataStructuresController extends ActionController
{
    /**
     * Default View Container
     *
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * Initialize action
     */
    protected function initializeAction()
    {
        TemplaVoilaUtility::getLanguageService()->includeLLFile(
            'EXT:templavoilaplus/Resources/Private/Language/Backend/ControlCenter/DataStructures.xlf'
        );
    }

    /**
     * Displays the page with layout and content elements
     */
    public function listAction()
    {
        $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation([]);
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());

        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $button = $buttonBar->makeLinkButton()
            ->setHref($this->getControllerContext()->getUriBuilder()->uriFor('show', [], 'Backend\ControlCenter'))
            ->setTitle('Back')
            ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
        $buttonBar->addButton($button, ButtonBar::BUTTON_POSITION_LEFT, 1);

        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $dataStructurePlaces = $configurationService->getDataStructurePlaces();

        $dataStructurePlaces = $this->enrichDataStructurePlacesWithFiles($dataStructurePlaces);
        $dataStructurePlacesByScope = $this->reorderDataStructurePlacesByScope($dataStructurePlaces);

        $this->view->assign('pageTitle', 'TemplaVoilà! Plus - DataStructure List');

        $this->view->assign('dataStructurePlacesByScope', $dataStructurePlacesByScope);
    }

    protected function enrichDataStructurePlacesWithFiles($dataStructurePlaces): array
    {
        $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();

        $filter = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter::class);
        $filter->setAllowedFileExtensions('xml');

        foreach ($dataStructurePlaces as $uuid => $dataStructurePlace) {
            $folder = $resourceFactory->retrieveFileOrFolderObject($dataStructurePlace['pathAbs']);
            $folder->setFileAndFolderNameFilters([[$filter, 'filterFileList']]);

            $dataStructurePlaces[$uuid]['files'] = $folder->getFiles(0, 0, \TYPO3\CMS\Core\Resource\Folder::FILTER_MODE_USE_OWN_FILTERS, true);
        }

        return $dataStructurePlaces;
    }

    protected function reorderDataStructurePlacesByScope($dataStructurePlaces): array
    {
        $dataStructurePlacesByScope = [];
        foreach ($dataStructurePlaces as $uuid => $dataStructurePlace) {
            $dataStructurePlacesByScope[$dataStructurePlace['scope']][] = $dataStructurePlace;
        }

        return $dataStructurePlacesByScope;
    }
}

