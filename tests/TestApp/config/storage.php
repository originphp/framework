<?php
/**
 * Storage Configuration
 * Engines are Local, Ftp, Sftp
 * @see https://www.originphp.com/docs/storage/
 */
use Origin\Storage\Storage;

Storage::config('default', [
    'engine' => 'Local'
]);
