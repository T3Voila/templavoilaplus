<?php

return [
    'renderer' => [
        \Ppi\TemplaVoilaPlus\Renderer\XpathRenderer::NAME => [
            'name' => 'XPath Renderer',
            'class' => \Ppi\TemplaVoilaPlus\Renderer\XpathRenderer::class,
        ],
    ],
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
    ],
];
