<?php

return [
    'configurationHandler' => [
        Tvp\TemplaVoilaPlus\Handler\Configuration\DataConfigurationHandler::$identifier => [
            'name' => 'Handler for creating/validating DataStructure configurations',
            'handlerClass' => Tvp\TemplaVoilaPlus\Handler\Configuration\DataConfigurationHandler::class,
        ],
        Tvp\TemplaVoilaPlus\Handler\Configuration\MappingConfigurationHandler::$identifier => [
            'name' => 'Handler for creating/validating Mapping configurations',
            'handlerClass' => Tvp\TemplaVoilaPlus\Handler\Configuration\MappingConfigurationHandler::class,
        ],
        Tvp\TemplaVoilaPlus\Handler\Configuration\TemplateConfigurationHandler::$identifier => [
            'name' => 'Handler for creating/validating Template configurations',
            'handlerClass' => Tvp\TemplaVoilaPlus\Handler\Configuration\TemplateConfigurationHandler::class,
        ],
        Tvp\TemplaVoilaPlus\Handler\Configuration\BackendLayoutConfigurationHandler::$identifier => [
            'name' => 'Handler for creating/validating BackendLayout configurations',
            'handlerClass' => Tvp\TemplaVoilaPlus\Handler\Configuration\BackendLayoutConfigurationHandler::class,
        ],
    ],
    'loadSaveHandler' => [
        Tvp\TemplaVoilaPlus\Handler\LoadSave\XmlLoadSaveHandler::$identifier => [
            'name' => 'Handler for finding/loading/saving/deleting XML files',
            'handlerClass' => Tvp\TemplaVoilaPlus\Handler\LoadSave\XmlLoadSaveHandler::class,
        ],
        Tvp\TemplaVoilaPlus\Handler\LoadSave\YamlLoadSaveHandler::$identifier => [
            'name' => 'Handler for finding/loading/saving/deleting YAML files',
            'handlerClass' => Tvp\TemplaVoilaPlus\Handler\LoadSave\YamlLoadSaveHandler::class,
        ],
        /** @TODO Realy? Better MarkerLoadSaveHandler? */
        Tvp\TemplaVoilaPlus\Handler\LoadSave\MarkerBasedFileLoadSaveHandler::$identifier => [
            'name' => 'Handler for finding/loading/saving/deleting MarkerBased HTML files',
            'handlerClass' => Tvp\TemplaVoilaPlus\Handler\LoadSave\MarkerBasedFileLoadSaveHandler::class,
        ],
    ],
    'renderHandler' => [
        \Tvp\TemplaVoilaPlus\Handler\Render\XpathRenderHandler::$identifier => [
            'name' => 'XPath Renderer',
            'handlerClass' => \Tvp\TemplaVoilaPlus\Handler\Render\XpathRenderHandler::class,
        ],
        \Tvp\TemplaVoilaPlus\Handler\Render\MarkerBasedRenderHandler::$identifier => [
            'name' => 'Marker Based Renderer',
            'handlerClass' => \Tvp\TemplaVoilaPlus\Handler\Render\MarkerBasedRenderHandler::class,
        ],
        \Tvp\TemplaVoilaPlus\Handler\Render\FluidRenderHandler::$identifier => [
            'name' => 'Fluid Renderer',
            'handlerClass' => \Tvp\TemplaVoilaPlus\Handler\Render\FluidRenderHandler::class,
        ],
    ],
];
