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
declare(strict_types=1);
namespace Origin\Mailbox;

use Origin\Model\ModelRegistry;
use Origin\Mailbox\Model\InboundEmail;

/**
 * Server for handling PIPE requests
 */
class Server
{
    /**
     * InboundEmail Model
     *
     * @var \Origin\Mailbox\Model\InboundEmail
     */
    protected $inboundEmail = null;

    /**
     * Pipe stream
     *
     * @var string
     */
    protected $stream = 'php://stdin';

    /**
    * Dispatches the pipe process
    *
    * @return bool
    */
    public function dispatch(): bool
    {
        # Set memory limit to prevent issues with large emails
        ini_set('memory_limit', '256M');

        $this->InboundEmail = ModelRegistry::get('InboundEmail', [
            'className' => InboundEmail::class
        ]);

        $inboundEmail = $this->InboundEmail->fromMessage($this->readData());
      
        return $this->InboundEmail->save($inboundEmail);
    }

    /**
     * Reads the data that is being piped
     *
     * @param string $stream e.g 'php://stdin' or 'php://input'
     * @return string
     */
    protected function readData(): string
    {
        return file_get_contents($this->stream);
    }
}
