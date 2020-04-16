<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Configuration;

use Ppi\TemplaVoilaPlus\Domain\Model\Scope;

return [
    'TVP\BackendLayout' => [
        'name' => 'Backend Layout Place',
        'path' => 'EXT:templavoilaplus/Resources/Private/BackendLayouts',
        'scope' => Scope::SCOPE_UNKNOWN,
        'loadSaveHandler' => \Ppi\TemplaVoilaPlus\Handler\LoadSave\YamlLoadSaveHandler::$identifier,
    ],
];
