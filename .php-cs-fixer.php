<?php

$config = \TYPO3\CodingStandards\CsFixerConfig::create();
$config->getFinder()->in('Classes')->in('Configuration')->in('Tests');
$config->getFinder()->exclude(['Form','Acceptance/Support/_generated']);
return $config->setRules([
    'phpdoc_align' => ['align' => 'left']
]);
