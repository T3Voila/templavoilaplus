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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Class which offers file related functions
 * @TODO Move functions from Domain/Model/File.php at this point, as it has nothing TODO with a domain model
 */
final class FileUtility
{
    /**
     * Checks user access to a file
     *
     * @param string $identifier File identifier or name of (relative) file to check
     *
     * @return boolean
     * @TODO This do not allow ExtensionPath for non-admin and with v9 this should all go into /typo3conf/sites/
     */
    public static function haveTemplateAccess($identifier)
    {
        if (TemplaVoilaUtility::getBackendUser()->isAdmin()) {
            return true;
        }

        list($storageId, $objectIdentifier) = GeneralUtility::trimExplode(':', $identifier);

        // If it is a combined identifier, let the storage do the checks
        if ($objectIdentifier !== null) {
            return \Ppi\TemplaVoilaPlus\Domain\Model\File::is_file($identifier)
                && static::isAllowedFileExtension($identifier);
        }

        // Only accept inside fileadmin, which should be readable for user
        // No support for extension paths yet
        // And with v9 this should all go into /typo3conf/sites/
        if (StringUtility::beginsWith($identifier, 'fileadmin/') && static::isAllowedFileExtension($identifier)) {
            $fileNameAbs = GeneralUtility::getFileAbsFileName($identifier);

            if ($fileNameAbs !== '' // identifier is valid (no path dots)
                && @is_file($fileNameAbs) // identifier is a file
                && @is_readable($fileNameAbs) // identifier is readable
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the file extension (inside the identifier) matches txt,html,htm,tpl,tmpl
     *
     * @param string $identifier The file identifier.
     * @return boolean
     */
    public static function isAllowedFileExtension($identifier)
    {
        return GeneralUtility::inList('txt,html,htm,tpl,tmpl', pathinfo($identifier, PATHINFO_EXTENSION));
    }
}
