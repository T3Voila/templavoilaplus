<?php

return [
    'renderer' => [
        \Ppi\TemplaVoilaPlus\Renderer\XpathRenderer::NAME => [
            'name' => 'XPath Renderer',
            'class' => \Ppi\TemplaVoilaPlus\Renderer\XpathRenderer::class,
        ],
    ],
    'dataStructureHandler' => [
        \Ppi\TemplaVoilaPlus\DataStructureHandler\FlexFormHandler::NAME => [
            'name' => 'FlexForm Handler',
            'class' => \Ppi\TemplaVoilaPlus\DataStructureHandler\FlexFormHandler::class,
        ],
    ],
];
