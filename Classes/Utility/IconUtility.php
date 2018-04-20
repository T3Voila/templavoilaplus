<?php
namespace Ppi\TemplaVoilaPlus\Utility;

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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class which adds an additional layer for icon creation
 */
final class IconUtility
{
    /**
     * @param string $flagName
     * @param array $options
     *
     * @return string
     */
    public static function getFlagIconForLanguage($flagName, $options = array())
    {
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        return '<span alt="' . htmlspecialchars($options['alt']) . '" title="' . htmlspecialchars($options['title']) . '">'
            . $iconFactory->getIcon($flagName ? : 'default-not-found', Icon::SIZE_SMALL)->render()
            . '</span>';
    }

    /**
     * @param string $flagName
     *
     * @return string
     */
    public static function getFlagIconFileForLanguage($flagName)
    {
        $identifier = $flagName ? : 'default-not-found';

        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);

        if (!$iconRegistry->isRegistered($identifier)) {
            $identifier = $iconRegistry->getDefaultIconIdentifier();
        }

        $iconConfiguration = $iconRegistry->getIconConfigurationByIdentifier($identifier);

        if (isset($iconConfiguration['options']['source'])) {
            return '/' . PathUtility::stripPathSitePrefix(
                GeneralUtility::getFileAbsFileName($iconConfiguration['options']['source'])
            );
        }

        return '';
    }
}
