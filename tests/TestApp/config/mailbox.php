<?php

use Origin\Mailbox\Mailbox;

/**
 * Configure the routing for the mailbox
 */
Mailbox::route('/^support@/i', 'Support');

/**
 * Setup email accounts
 */
Mailbox::config('test', [
    'host' => env('EMAIL_IMAP_HOST', '127.0.0.1'),
    'port' => env('EMAIL_IMAP_PORT', 143),
    'username' => env('EMAIL_IMAP_USERNAME'),
    'password' => env('EMAIL_IMAP_PASSWORD'),
    'encryption' => env('EMAIL_IMAP_ENCRYPTION'),
    'validateCert' => false,
    'timeout' => 5
]);
