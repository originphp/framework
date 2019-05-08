<?php
use Origin\Core\Autoloader;

$autoloader = Autoloader::instance();
$autoloader->directory(ROOT);

$namespaces = [
    'App' => 'vendor/originphp/originphp/tests/TestApp/src'
];

$autoloader->addNamespaces($namespaces);
$autoloader->register();
