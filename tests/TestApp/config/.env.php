<?php
/**
 * Environment Variables which will be set in $_ENV. Save this file as .env.php
 *
 * If you need to use a standard .env script then you just place in this folder and it
 * will be loaded automatically
 *
 * # Do not store this file or .env files in repos
 *
 * @return array
 */
return [
    /**
     * Application stuff
     */
    'APP_DEBUG' => true,
    'APP_URL' => 'http://localhost',
    'APP_ENV' => 'development',  # development,staging or production

    /**
     * Generate a key using Security::generateKey()
     */
    'APP_KEY' => md5('-----ORIGIN PHP-----'),
];
