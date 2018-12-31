<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 *
 * @link          https://www.originphp.com
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Exception;

class ServiceUnavailableException extends Exception
{
    public function __construct($message = null, $code = 503)
    {
        if ($message === null) {
            $message = 'Service Unavailable';
        }
        parent::__construct($message, $code);
    }
}
