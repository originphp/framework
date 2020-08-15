<?php

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__, 3));
define('ORIGIN', ROOT);

define('APP', ORIGIN . '/tests/TestApp/app');
define('CONFIG', ORIGIN . '/tests/TestApp/config');
define('DATABASE', ORIGIN . '/tests/TestApp/database');
define('PLUGINS', ORIGIN . '/tests/TestApp/plugins');
define('TESTS', ORIGIN . '/tests/TestApp/tests');
define('WEBROOT', ROOT . '/tests/TestApp/public');
define('STORAGE', ORIGIN . '/tests/TestApp/storage');

define('TMP', sys_get_temp_dir());
define('LOGS', TMP . '/logs');
define('CACHE', TMP . '/cache');

@mkdir(LOGS);
@mkdir(CACHE);
