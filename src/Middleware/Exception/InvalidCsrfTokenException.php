<?php
namespace Origin\Middleware\Exception;

use Origin\Http\Exception\HttpException;

class InvalidCsrfTokenException extends HttpException
{
    public function __construct($message = null, $code = 403)
    {
        if ($message === null) {
            $message = 'Invalid CSRF Token';
        }
        parent::__construct($message, $code);
    }
}
