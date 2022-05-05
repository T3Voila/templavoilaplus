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
     * @var string Url for use on button
     */
    protected $buttonUrl = '';

    /**
     * @var string Label for use on button
     */
    protected $buttonLabel = '';

    /**
     * @var string Icon name for use on button
     */
    protected $buttonIcon = '';

    /**
     * Constructor for a flash message
     *
     * @param string $message The message.
     * @param string $title Optional message title.
     * @param int $severity Optional severity, must be either of one of \TYPO3\CMS\Core\Messaging\FlashMessage constants
     * @param bool $storeInSession Optional, defines whether the message should be stored in the session or only for one request (default)
     */
    public function __construct(
        $message,
        $title = '',
        $severity = self::OK,
        $storeInSession = false,
        $buttonUrl = '',
        $buttonLabel = '',
        $buttonIcon = ''
    ) {
        parent::__construct($message, $title, $severity, $storeInSession);
        $this->setButtonUrl($buttonUrl);
        $this->setButtonLabel($buttonLabel);
        $this->setButtonIcon($buttonIcon);
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
            (string)($data['buttonUrl'] ?? ''),
            (string)($data['buttonLabel'] ?? ''),
            (string)($data['buttonIcon'] ?? '')
        );
    }

    public function setButtonUrl(string $buttonUrl): void
    {
        $this->buttonUrl = $buttonUrl;
    }

    public function getButtonUrl(): string
    {
        return $this->buttonUrl;
    }

    public function setButtonLabel(string $buttonLabel): void
    {
        $this->buttonLabel = $buttonLabel;
    }

    public function getButtonLabel(): string
    {
        return $this->buttonLabel;
    }

    public function setButtonIcon(string $buttonIcon): void
    {
        $this->buttonIcon = $buttonIcon;
    }

    public function getButtonIcon(): string
    {
        return $this->buttonIcon;
    }

    /**
     * @return array Data which can be serialized by json_encode()
     */
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['buttonUrl'] = $this->buttonUrl;
        $data['buttonLabel'] = $this->buttonLabel;
        $data['buttonIcon'] = $this->buttonIcon;
        return $data;
    }
}
