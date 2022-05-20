<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Domain\Model\Configuration;

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
 * Class to provide unique access to TemplateConfiguration
 */
class TemplateConfiguration extends AbstractConfiguration
{
    /** @var string */
    protected $renderHandlerIdentifier = '';

    /** @var string */
    protected $templateFileName = '';

    /** @var array Options depending on used RenderHandler */
    protected $options = [];

    /** @var array */
    protected $header = [];

    /** @var array */
    protected $mapping = [];

    /**
     * Retrieve the identifier of the RenderHandler for this template
     */
    public function getRenderHandlerIdentifier(): string
    {
        return $this->renderHandlerIdentifier;
    }

    public function setRenderHandlerIdentifier(string $renderHandlerIdentifier): void
    {
        $this->renderHandlerIdentifier = $renderHandlerIdentifier;
    }

    /**
     * Retrieve the filename of the template
     */
    public function getTemplateFileName(): string
    {
        return $this->templateFileName;
    }

    public function setTemplateFileName(string $templateFileName): void
    {
        $this->templateFileName = $templateFileName;
    }

    /**
     * Retrieve the options of the RenderHandler for this template
     */
    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * Retrieve the header of the template
     */
    public function getHeader()
    {
        return $this->header;
    }

    public function setHeader(array $header): void
    {
        $this->header = $header;
    }

    /**
     * Retrieve the mapping of the template
     */
    public function getMapping(): array
    {
        return $this->mapping;
    }

    public function setMapping(array $mapping):void
    {
        $this->mapping = $mapping;
    }
}
