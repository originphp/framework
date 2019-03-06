<?php
use Origin\Core\Autoloader;

$autoloader = Autoloader::getInstance();
$autoloader->setFolder(ROOT);

$namespaces = [
    'App' => 'origin/tests/TestApp/src',
    'Origin' => 'origin/src',
    'Origin\\Test' => 'origin/tests'
];

$autoloader->addNamespaces($namespaces);
$autoloader->register();
