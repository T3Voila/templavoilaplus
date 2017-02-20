<?php
namespace Extension\Templavoila\Domain\Model;

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
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * File model
 */
class File
{

    /**
     * Retrieve filename from the FAL resource or pass the
     * given string along as this is a filename already.
     *
     * @param $filename
     *
     * @return string
     */
    public static function filename($filename)
    {
        try {
            /** @var $resourceFactory ResourceFactory */
            $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
            $file = $resourceFactory->getObjectFromCombinedIdentifier($filename);
            $filename = $file->getForLocalProcessing(false);
        } catch (\Exception $e) {
        }

        return $filename;
    }

    /**
     * Check whether the given input points to an (existing) file.
     *
     * @param string $filename
     *
     * @return boolean
     */
    public static function is_file($filename)
    {
        try {
            /** @var $resourceFactory ResourceFactory */
            $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
            $resourceFactory->getObjectFromCombinedIdentifier($filename);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check whether the given file can be used for mapping
     * purposes (is an XML file).
     *
     *
     * @param string $filename
     *
     * @return boolean
     */
    public static function is_xmlFile($filename)
    {
        $isXmlFile = false;
        try {
            /** @var $resourceFactory ResourceFactory */
            $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
            $file = $resourceFactory->getObjectFromCombinedIdentifier($filename);
            if (!$file instanceof \TYPO3\CMS\Core\Resource\FolderInterface) {
                $isXmlFile = in_array($file->getMimeType(), array('text/html', 'application/xml'));
            }
        } catch (\Exception $e) {
        }

        return $isXmlFile;
    }
}
