<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Origin\Schedule;

use Exception;
use LogicException;
use RuntimeException;

/**
 * New class, this might be eventually moved into own namespace or into filesystem, not
 * sure yet.
 */
class Lock
{
    const BLOCKING = LOCK_EX;
    const NON_BLOCKING = LOCK_EX | LOCK_NB;

    /**
     * Path to lock file
     *
     * @var string
     */
    private $path;

    /**
     * File pointer
     *
     * @var resource
     */
    private $fp = null;

    /**
     * @param string $path
     */
    public function __construct(string $name)
    {
        $this->path = sys_get_temp_dir() . "/{$name}.lock";
    }

    /**
     * Acquires a lock, if it cannot get a lock it will return false.
     *
     * @param boolean $block
     * @return boolean
     */
    public function acquire(bool $block = true): bool
    {
        if ($this->fp) {
            throw new LogicException('Lock has already been acquired');
        }

        touch($this->path); // Ensure file exists
       
        $this->fp = fopen($this->path, 'r+');
        if (! $this->fp) {
            throw new RuntimeException('Error opening file');
        }

        if (! flock($this->fp, $block ? self::BLOCKING : self::NON_BLOCKING)) {
            $this->closeFile();

            return false;
        }

        return ftruncate($this->fp, 0) && fwrite($this->fp, (string) getmypid()) && fflush($this->fp);
    }

    /**
     * Releases the lock
     *
     * @return void
     */
    public function release(): void
    {
        if (! $this->fp) {
            throw new LogicException('Lock was not acquired');
        }

        if (! flock($this->fp, LOCK_UN)) {
            throw new RuntimeException('Error releasing lock');
        }

        $this->closeFile();
    }

    /**
     * Close the file if its still open
     */
    public function __destruct()
    {
        $this->closeFile();
    }

    /**
     * Closes the file and cleans up
     *
     * @return void
     */
    private function closeFile(): void
    {
        if ($this->fp && ! fclose($this->fp)) {
            throw new Exception('Error closing file');
        }
        $this->fp = null;
    }
}
