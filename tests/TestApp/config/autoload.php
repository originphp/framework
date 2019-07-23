<?php
use Origin\Core\Autoloader;

$autoloader = Autoloader::instance();
$autoloader->directory(ROOT);

$namespaces = [
    'App' => 'vendor/originphp/framework/tests/TestApp/src',
];

$autoloader->addNamespaces($namespaces);
$autoloader->register();
