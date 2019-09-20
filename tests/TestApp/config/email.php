<?php
/**
 * Email Configuration
 * @see https://www.originphp.com/docs/utility/email/
 */
use Origin\Mailer\Email;

Email::config('test', [
    'engine' => 'Test'
]);
