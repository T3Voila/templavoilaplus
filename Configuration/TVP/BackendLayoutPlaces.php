<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Configuration;

use Tvp\TemplaVoilaPlus\Domain\Model\Scope;

return [
    'TVP\BackendLayout' => [
        'name' => 'Backend Layout Place',
        'path' => 'EXT:templavoilaplus/Resources/Private/BackendLayouts',
        'scope' => Scope::SCOPE_UNKNOWN,
        'loadSaveHandler' => \Tvp\TemplaVoilaPlus\Handler\LoadSave\YamlLoadSaveHandler::$identifier,
    ],
];
