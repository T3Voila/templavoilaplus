<?php
namespace Ppi\TemplaVoilaPlus\Controller\Preview;

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

/**
 * Uploads controller
 */
class UploadsController extends TextController
{

    /**
     * @var string
     */
    protected $previewField = 'media';

    /**
     * @param array $row
     *
     * @return string
     */
    protected function getPreviewData($row)
    {
        $data = $this->preparePreviewData($row[$this->previewField]);

        return str_replace(',', '<br />', $data);
    }
}
