<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Origin\Mailer;

class Message
{
    protected $header = null;
    protected $body = null;

    public function __construct(string $header, string $body)
    {
        $this->header = $header;
        $this->body = $body;
    }

    /**
     * Gets the message header
     *
     * @return string
     */
    public function header() : string
    {
        return $this->header;
    }

    /**
     * Gets the message body
     *
     * @return string
     */
    public function body() : string
    {
        return $this->body;
    }

    /**
     * Returns the full message (header and body)
     *
     * @return string
     */
    public function message() : string
    {
        return $this->header . "\r\n\r\n" . $this->body;
    }

    public function __toString()
    {
        return $this->message();
    }
}
