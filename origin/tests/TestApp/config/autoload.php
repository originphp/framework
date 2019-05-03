<?php
use Origin\Core\Autoloader;

$autoloader = Autoloader::instance();
$autoloader->directory(ROOT);

$namespaces = [
    'App' => 'origin/tests/TestApp/src',
    'Origin' => 'origin/src',
    'Origin\\Test' => 'origin/tests'
];

$autoloader->addNamespaces($namespaces);
$autoloader->register();
