<?php
use Origin\Core\Autoloader;

$autoloader = Autoloader::instance();
$autoloader->directory(APP);

$namespaces = [
    'App' => 'src',
];

$autoloader->addNamespaces($namespaces);
$autoloader->register();

require 'application.php';
