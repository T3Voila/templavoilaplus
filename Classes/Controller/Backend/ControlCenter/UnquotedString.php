<?php

namespace Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter;

class UnquotedString
{
    private $value = '';

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
