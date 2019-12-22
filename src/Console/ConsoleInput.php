<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Origin\Console;

class ConsoleInput
{
    /**
     * Holds the stream for reading
     *
     * @var resource
     */
    protected $stream = null;
    
    /**
    * Constructs a new instance
    * @param string $stream fopen stream php://stdin
    */
    public function __construct(string $stream = 'php://stdin')
    {
        $this->stream = fopen($stream, 'r');
    }
    
    /**
     * Reads from the stream
     *
     * @return string|null
     */
    public function read() : ?string
    {
        $data = fgets($this->stream);

        return $data ? trim($data) : null;
    }

    /**
     * Closes the stream
     *
     * @return void
     */
    public function close() : void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
