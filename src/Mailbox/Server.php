<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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

use Origin\Security\Security;
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

        // store in temp folder
        if ($this->maintenanceMode()) {
            return $this->saveToDisk($this->readData());
        }

        if (! $this->saveToDb($this->readData())) {
            return false;
        }
        /**
         * Currently emails saved during maintenance mode will be checked on next incoming email
         * through piping. Not sure best way to approach this without creating another command.
         */
        return $this->loadFromDisk();
    }

    /**
     * Saves emails to disk (maintenance mode)
     *
     * @return boolean
     */
    protected function saveToDisk(string $message): bool
    {
        if (! is_dir(tmp_path('mailbox'))) {
            mkdir(tmp_path('mailbox'));
        }

        return (bool) file_put_contents(
            tmp_path('mailbox/' . Security::uuid()),
            $message
        );
    }

    /**
     * Loads and deletes processed emails from disk
     *
     * @return void
     */
    protected function loadFromDisk(): bool
    {
        $files = scandir(tmp_path('mailbox'));
        foreach ($files as $file) {
            if (in_array($file, ['..', '.'])) {
                continue;
            }
            if (! $this->saveToDb(file_get_contents(tmp_path('mailbox/' . $file)))) {
                debug($file);

                return false;
            }
            unlink(tmp_path('mailbox/' . $file));
        }

        return true;
    }

    /**
     * Saves a message to the database
     *
     * @param string $message
     * @return boolean
     */
    protected function saveToDb(string $message): bool
    {
        $inboundEmail = $this->InboundEmail->fromMessage($message);

        if (! $this->InboundEmail->existsInDb($inboundEmail)) {
            return $this->InboundEmail->save($inboundEmail);
        }

        return false;
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

    /**
     * Checks if maintenance is enabled
     *
     * @return boolean
     */
    protected function maintenanceMode(): bool
    {
        return file_exists(tmp_path('maintenance.json'));
    }
}
