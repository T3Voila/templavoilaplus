<?php

$myIcons = [];

$iconsBitmap = [
    'paste' => 'EXT:templavoilaplus/Resources/Public/Icon/clip_pasteafter.gif',
    'pasteSubRef' => 'EXT:templavoilaplus/Resources/Public/Icon/clip_pastesubref.gif',
    'makelocalcopy' => 'EXT:templavoilaplus/Resources/Public/Icon/makelocalcopy.gif',
    'clip_ref' => 'EXT:templavoilaplus/Resources/Public/Icon/clip_ref.gif',
    'clip_ref-release' => 'EXT:templavoilaplus/Resources/Public/Icon/clip_ref_h.gif',
    'htmlvalidate' => 'EXT:templavoilaplus/Resources/Public/Icon/html_go.png',
    'type-fce' => 'EXT:templavoilaplus/Resources/Public/Icon/icon_fce_ce.png',
];
$iconsSvg = [
    'template-default' => 'EXT:templavoilaplus/Resources/Public/Icons/TemplateDefault.svg',
    'datastructure-default' => 'EXT:templavoilaplus/Resources/Public/Icons/DataStructureDefault.svg',
    'folder' => 'EXT:templavoilaplus/Resources/Public/Icons/Folder.svg',
    'menu-item' => 'EXT:templavoilaplus/Resources/Public/Icons/MenuItem.svg',
    'page-module' => 'EXT:templavoilaplus/Resources/Public/Icons/PageModuleIcon.svg',
    'admin-module' => 'EXT:templavoilaplus/Resources/Public/Icons/AdministrationModuleIcon.svg',
    'unlink' => 'EXT:templavoilaplus/Resources/Public/Icons/Unlink.svg',
];

foreach ($iconsBitmap as $identifier => $file) {
    $myIcons['extensions-templavoila-' . $identifier] = [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        'source' => $file,
    ];
}
foreach ($iconsSvg as $identifier => $file) {
    $myIcons['extensions-templavoila-' . $identifier] = [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $file,
    ];
}

return $myIcons;
