<?php

return [
    'renderer' => [
        \Ppi\TemplaVoilaPlus\Renderer\XpathRenderer::NAME => [
            'name' => 'XPath Renderer',
            'class' => \Ppi\TemplaVoilaPlus\Renderer\XpathRenderer::class,
        ],
    ],
    'placesHandler' => [
        \Ppi\TemplaVoilaPlus\Handler\Place\DataStructureFlexFormPlaceHandler::NAME => [
            'name' => 'FlexForm Handler',
            'handlerClass' => \Ppi\TemplaVoilaPlus\Handler\Place\DataStructureFlexFormPlaceHandler::class,
            'placeClass' => \Ppi\TemplaVoilaPlus\Domain\Model\DataStructurePlace::class
        ],
        \Ppi\TemplaVoilaPlus\Handler\Place\MappingYamlPlaceHandler::NAME => [
            'name' => 'Mapping Yaml Handler',
            'handlerClass' => \Ppi\TemplaVoilaPlus\Handler\Place\MappingYamlPlaceHandler::class,
            'placeClass' => \Ppi\TemplaVoilaPlus\Domain\Model\MappingPlace::class
        ],
        \Ppi\TemplaVoilaPlus\Handler\Place\TemplateYamlPlaceHandler::NAME => [
            'name' => 'Template Yaml Handler',
            'handlerClass' => \Ppi\TemplaVoilaPlus\Handler\Place\TemplateYamlPlaceHandler::class,
            'placeClass' => \Ppi\TemplaVoilaPlus\Domain\Model\TemplatePlace::class
        ],
    ],
];
