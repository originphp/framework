<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Exception;

class BaseException extends \Exception
{
    protected $template = null;
    protected $defaultErrorCode = null;

    public function __construct($message, $code = 500)
    {
        if ($this->template !== null) {
            if (!is_array($message)) {
                $message = array($message);
            }
            $message = vsprintf($this->template, $message);
        }
        if ($this->defaultErrorCode !== null) {
            $code = $this->defaultErrorCode;
        }

        parent::__construct($message, $code);
    }
}

class Exception extends BaseException
{
}
