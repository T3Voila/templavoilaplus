<?php

declare(strict_types=1);

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

namespace Tvp\TemplaVoilaPlus\Core\Messaging;

use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage as CoreFlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A class representing flash messages.
 */
class FlashMessage extends CoreFlashMessage
{
    /**
     * @var array Array of buttons to show in a button group under the flash message with subarray of
     * [
     *  'url' => (string)
     *  'label' => (string)
     *  'icon' => (string)
     * ]
     */
    protected $buttons = [];

    /**
     * Constructor for a flash message
     *
     * @param string $message The message.
     * @param string $title Optional message title.
     * @param int $severity Optional severity, must be either of one of \TYPO3\CMS\Core\Messaging\FlashMessage constants
     * @param bool $storeInSession Optional, defines whether the message should be stored in the session or only for one request (default)
     * @param array $buttons Optional array of button configuration
     */
    public function __construct(
        $message,
        $title = '',
        $severity = self::OK,
        $storeInSession = false,
        $buttons = []
    ) {
        parent::__construct($message, $title, $severity, $storeInSession);
        $this->setButtons($buttons);
    }

    /**
     * Factory method. Useful when creating flash messages from a jsonSerialize json_decode() call.
     *
     * @param array<string, string|int|bool> $data
     * @return static
     */
    public static function createFromArray(array $data): self
    {
        return GeneralUtility::makeInstance(
            static::class,
            (string)$data['message'],
            (string)($data['title'] ?? ''),
            (int)($data['severity'] ?? AbstractMessage::OK),
            (bool)($data['storeInSession'] ?? false),
            (array)($data['buttons'] ?? [])
        );
    }

    public function setButtons(array $buttons): void
    {
        $this->buttons = $buttons;
    }

    public function getButtons(): array
    {
        return $this->buttons;
    }

    /**
     * @return array Data which can be serialized by json_encode()
     */
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['buttons'] = $this->buttons;
        return $data;
    }
}
