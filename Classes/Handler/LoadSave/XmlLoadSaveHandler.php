<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Handler\LoadSave;

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

use Symfony\Component\Finder\SplFileInfo;
use Tvp\TemplaVoilaPlus\Utility\DataStructureUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class XmlLoadSaveHandler extends AbstractFileLoadSaveHandler implements LoadSaveHandlerInterface
{
    public static $identifier = 'TVP\LoadSaveHandler\Xml';

    protected $fileExtension = '.xml';

    public function load(SplFileInfo $file): array
    {
        $configuration = [];

        $xmlContent = $file->getContents();

        if (strlen($xmlContent) > 1) {
            $configuration = GeneralUtility::xml2array($xmlContent);
            if (!is_array($configuration)) {
                throw new \Exception(
                    'XML file "' . $file->getFilename() . '" cant\'t be read, we get following error: ' . $configuration
                );
            }
        }

        return $configuration;
    }

    public function save(SplFileInfo $file, array $data): void
    {
        $xmlContent = DataStructureUtility::array2xml($data, $this->place->getIndentation());
        GeneralUtility::writeFile($file->getPathname(), $xmlContent);
    }

    public function delete(SplFileInfo $file): void
    {
        throw new \Exception('Not Yet Implemented');
    }
}
