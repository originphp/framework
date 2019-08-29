<?php
/**
 * Email Configuration
 * @see https://www.originphp.com/docs/utility/email/
 */
use Origin\Mailer\Email;

Email::config('default', [
    'host' => env('EMAIL_HOST'),
    'port' => env('EMAIL_PORT'),
    'username' => env('EMAIL_USERNAME'),
    'password' => env('EMAIL_PASSWORD'),
    'timeout' => 30,
    'ssl' => env('EMAIL_SSL'),
    'tls' => env('EMAIL_TLS'),
]);

Email::config('test', [
    'engine' => 'Test'
]);
