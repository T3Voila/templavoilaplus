<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Configuration;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/** @TODO We need to find modTSconfig? Or break? Minimum template in defaultFolder but mark this with warning as stupid (like DS inside fileadmin) */
/** @TODO Remove if we add an updater, which completely migrates into an extension/site package */
// See BackendControlCenterController::getTemplateFolders

if (!class_exists(TemplatePlaces::class)) {
    class TemplatePlaces
    {
        public static function getTemplatePlaces(): array
        {
            $templatePlaces = [];
            $foundTvStorages[] = 2; // @TODO finde real folders
            foreach ($foundTvStorages as $pageId) {
                $pageTsConfig = BackendUtility::getPagesTSconfig($pageId);
                $modTSconfig = $pageTsConfig['mod.']['web_txtemplavoilaplusCenter.'];

                if (isset($modTSconfig['templatePath'])) {
                    $folders = self::getTemplateFolders($modTSconfig['templatePath']);
                } else {
                    $folders = self::getTemplateFolders('');
                }

                foreach ($folders as $folder) {
                    $templatePlaces[$folder->getIdentifier()] = [
                        'name' => $folder->getPublicUrl(),
                        'path' => $folder->getPublicUrl(),
                        'scope' => 'All Scopes',
                        'loadSaveHandler' => \Ppi\TemplaVoilaPlus\Handler\LoadSave\YamlLoadSaveHandler::$identifier,
                    ];
                }
            }

            return $templatePlaces;
        }


        /**
         * Find and check all template paths
         *
         * @return array all relevant template paths
         */
        protected static function getTemplateFolders($templatePath): array
        {
            $templateFolders = [];
            if (strlen($templatePath)) {
                $paths = GeneralUtility::trimExplode(',', $templatePath, true);
            } else {
                $paths = array('templates');
            }

            $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
            $defaultStorage = $resourceFactory->getDefaultStorage();

            // Check if a default storage was defined
            if ($defaultStorage) {
                foreach ($paths as $path) {
                    try {
                        $folder = $defaultStorage->getFolder('/' . $path);
                    } catch (\Exception $e) {
                        // Blank catch, as we exspect that not all pathes may exists.
                        continue;
                    }
                    $templateFolders[] = $folder;
                }
            }

            return $templateFolders;
        }
    }

}

return TemplatePlaces::getTemplatePlaces();
