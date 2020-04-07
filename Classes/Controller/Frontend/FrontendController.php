<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Controller\Frontend;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

/** @TODO Missing Base class */
use Ppi\TemplaVoilaPlus\Domain\Model\TemplateYamlConfiguration;
use Ppi\TemplaVoilaPlus\Service\ConfigurationService;
use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

class FrontendController extends AbstractPlugin
{
    /**
     * Same as class name
     * @TODO Rename?
     *
     * @var string
     */
    public $prefixId = 'tx_templavoilaplus_pi1';

    /**
     * The extension key.
     *
     * @var string
     */
    public $extKey = 'templavoilaplus';

    /**
     * Main function for rendering of Flexible Content elements of TemplaVoila
     *
     * @param string $content Standard content input. Ignore.
     * @param array $conf TypoScript array for the plugin.
     *
     * @return string HTML content for the Flexible Content elements.
     */
    public function renderPage($content, $conf)
    {
        // Current page record which we MIGHT manipulate a little:
        $pageRecord = $GLOBALS['TSFE']->page;

        // Find DS and Template in root line IF there is no Data Structure set for the current page:
        if (!$pageRecord['tx_templavoilaplus_map']) {
            $pageRecord['tx_templavoilaplus_map'] = $this->getMapIdentifierFromRootline();
        }

        return $this->renderElement($pageRecord, 'pages');
    }

    /**
     * @return string|null
     */
    protected function getMapIdentifierFromRootline()
    {
        $mapBackupIdentifier = null;

        // Find in rootline upwards
        foreach ($GLOBALS['TSFE']->rootLine as $key => $pageRecord) {
            if ($key === 0) {
                continue;
            }
            if ($pageRecord['tx_templavoilaplus_next_map']) { // If there is a next-level MAP:
                return $pageRecord['tx_templavoilaplus_next_map'];
            } elseif ($pageRecord['tx_templavoilaplus_map'] && !$mapBackupIdentifier) { // Otherwise try the NORMAL MAP as backup
                $mapBackupIdentifier = $pageRecord['tx_templavoilaplus_map'];
            }
        }

        return $mapBackupIdentifier;
    }

    /**
     * Common function for rendering of the Flexible Content / Page Templates.
     * For Page Templates the input row may be manipulated to contain the proper reference to a data structure (pages can have those inherited which content elements cannot).
     *
     * @param array $row Current data record, either a tt_content element or page record.
     * @param string $table Table name, either "pages" or "tt_content".
     *
     * @throws \RuntimeException
     *
     * @return string HTML output.
     */
    public function renderElement($row, $table)
    {
try {
        $mappingConfiguration = $this->getMappingConfiguration($row['tx_templavoilaplus_map']);
        // getDS from Mapping

        // getTemplateConfiguration from MappingConfiguration
        // @TODO Identifier seams wrong should be only Default.tvp.yaml
        $templateConfiguration = $this->getTemplateConfiguration('283274d1-5281-4939-8dd4-e1e8c987d275:/Default.tvp.yaml'/*$mappingConfiguration->getCombinedTemplateConfigurationIdentifier()*/);

        // getDSdata from DS
        // Run TypoScript over DSdata and include TypoScript vars while mapping into TemplateData
        $processedValues = [];
        // get renderer from templateConfiguration

        $rendererName = $templateConfiguration->getRendererName();
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $renderer = $configurationService->getRenderer($rendererName);

        // Manipulate header data
        // give TemplateData to renderer
        // return result
        return $renderer->renderTemplate($templateConfiguration, $processedValues, $row);
} catch (\Exception $e) {
    var_dump($e->getMessage());
    die('Error message shown');
}
    }

    /**
     * @TODO
     * Following functions should reside inside an API so they can be used on
     * other points inside TV+ or other extensions.
     */



    public function getMappingConfiguration($mapIdentifier)
    {
        return []; // MappingConfiguration object
    }

    public function getTemplateConfiguration($combinedTemplateConfigurationIdentifier): TemplateYamlConfiguration
    {
        list($placeIdentifier, $templateConfigurationIdentifier) = explode(':', $combinedTemplateConfigurationIdentifier);

        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $templatePlace = $configurationService->getTemplatePlace($placeIdentifier);
        return $templatePlace->getHandler()->getTemplateConfiguration($templateConfigurationIdentifier);
    }
}
