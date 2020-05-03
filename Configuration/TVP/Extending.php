<?php

return [
    'configurationHandler' => [
        Ppi\TemplaVoilaPlus\Handler\Configuration\DataStructureConfigurationHandler::$identifier => [
            'name' => 'Handler for creating/validating DataStructure configurations',
            'handlerClass' => Ppi\TemplaVoilaPlus\Handler\Configuration\DataStructureConfigurationHandler::class,
        ],
        Ppi\TemplaVoilaPlus\Handler\Configuration\MappingConfigurationHandler::$identifier => [
            'name' => 'Handler for creating/validating Mapping configurations',
            'handlerClass' => Ppi\TemplaVoilaPlus\Handler\Configuration\MappingConfigurationHandler::class,
        ],
        Ppi\TemplaVoilaPlus\Handler\Configuration\TemplateConfigurationHandler::$identifier => [
            'name' => 'Handler for creating/validating Template configurations',
            'handlerClass' => Ppi\TemplaVoilaPlus\Handler\Configuration\TemplateConfigurationHandler::class,
        ],
        Ppi\TemplaVoilaPlus\Handler\Configuration\BackendLayoutConfigurationHandler::$identifier => [
            'name' => 'Handler for creating/validating BackendLayout configurations',
            'handlerClass' => Ppi\TemplaVoilaPlus\Handler\Configuration\BackendLayoutConfigurationHandler::class,
        ],
    ],
    'loadSaveHandler' => [
        Ppi\TemplaVoilaPlus\Handler\LoadSave\XmlLoadSaveHandler::$identifier => [
            'name' => 'Handler for finding/loading/saving/deleting XML files',
            'handlerClass' => Ppi\TemplaVoilaPlus\Handler\LoadSave\XmlLoadSaveHandler::class,
        ],
        Ppi\TemplaVoilaPlus\Handler\LoadSave\YamlLoadSaveHandler::$identifier => [
            'name' => 'Handler for finding/loading/saving/deleting YAML files',
            'handlerClass' => Ppi\TemplaVoilaPlus\Handler\LoadSave\YamlLoadSaveHandler::class,
        ],
        /** @TODO Realy? Better MarkerLoadSaveHandler? */
        Ppi\TemplaVoilaPlus\Handler\LoadSave\MarkerBasedFileLoadSaveHandler::$identifier => [
            'name' => 'Handler for finding/loading/saving/deleting MarkerBased HTML files',
            'handlerClass' => Ppi\TemplaVoilaPlus\Handler\LoadSave\MarkerBasedFileLoadSaveHandler::class,
        ],
    ],
    'renderHandler' => [
        \Ppi\TemplaVoilaPlus\Handler\Render\XpathRenderHandler::$identifier => [
            'name' => 'XPath Renderer',
            'handlerClass' => \Ppi\TemplaVoilaPlus\Handler\Render\XpathRenderHandler::class,
        ],
        \Ppi\TemplaVoilaPlus\Handler\Render\MarkerBasedRenderHandler::$identifier => [
            'name' => 'Marker Based Renderer',
            'handlerClass' => \Ppi\TemplaVoilaPlus\Handler\Render\MarkerBasedRenderHandler::class,
        ],
    ],
];
