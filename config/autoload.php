<?php
use Origin\Core\Autoloader;

$autoloader = Autoloader::getInstance();
$autoloader->directory(ROOT);

$namespaces = [
    'App' => 'src',
    'App\\Test' => 'tests',
    'Origin' => 'origin/src',
    'Origin\\Test' => 'origin/tests'
];

$autoloader->addNamespaces($namespaces);
$autoloader->register();
