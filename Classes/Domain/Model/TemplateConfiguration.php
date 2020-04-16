<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Domain\Model;

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
    /**
     * @var string
     */
    protected $renderHandlerIdentifier = '';

    /**
     * @var array
     */
    protected $header = [];

    /**
     * @var array
     */
    protected $mapping = [];

    /**
     * Retrieve the identifier of the RenderHandler for this template
     *
     * @return string
     */
    public function getRenderHandlerIdentifier(): string
    {
        return $this->renderHandlerIdentifier;
    }

    public function setRenderHandlerIdentifier(string $renderHandlerIdentifier)
    {
        $this->renderHandlerIdentifier = $renderHandlerIdentifier;
    }

    /**
     * Retrieve the header of the template
     *
     * @return string
     */
    public function getHeader()
    {
        return $this->header;
    }

    public function setHeader(array $header)
    {
        $this->header = $header;
    }

    /**
     * Retrieve the mapping of the template
     *
     * @return string
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    public function setMapping(array $mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * @TODO This is a stupid idea
     */
    public function getTemplateFile()
    {
    }
}
