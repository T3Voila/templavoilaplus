<?php
namespace Ppi\TemplaVoilaPlus\Slots;

class TranslationServiceSlot
{
    /** @var string */
    protected static $extKey = 'templavoilaplus';

    public function postProcessMirrorUrl($extensionKey, &$mirrorUrl) {
        if ($extensionKey === self::$extKey) {
            $mirrorUrl = 'http://ter.templavoila.plus/templavoilaplus-v7/';
        }
    }
}
