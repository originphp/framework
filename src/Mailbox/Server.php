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
/**
 * Instructions
 * make sure
 * - path to php is correct (above)
 * - permissions chmod a+x pipe.php
 */
declare(strict_types=1);
namespace Origin\Mailbox;

use Origin\Model\ModelRegistry;

use Origin\Core\Exception\Exception;
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
    * Dispatches the pipe process
    *
    * @return void
    */
    public function dispatch(): void
    {
        # Set memory limit to prevent issues with large emails
        ini_set('memory_limit', '256M');

        $this->InboundEmail = ModelRegistry::get('InboundEmail', [
            'className' => InboundEmail::class
        ]);

        $inboundEmail = $this->InboundEmail->fromMessage($this->requestData());
        
        if ($this->InboundEmail->checksumExists($inboundEmail->checksum)) {
            return;
        }

        if (! $this->InboundEmail->save($inboundEmail)) {
            throw new Exception('Error saving InboundEmail to database');
        }
    }

    /**
     * Reads the data from the request
     *
     * @param string $stream e.g 'php://stdin' or 'php://input'
     * @return string
     */
    private function requestData(string $stream = 'php://stdin'): string
    {
        $data = '';
        $fh = fopen($stream, 'r');
        while (! feof($fh)) {
            $data .= fread($fh, 1024);
        }
        fclose($fh);

        return $data;
    }
}
