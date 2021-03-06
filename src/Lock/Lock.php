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
namespace Origin\Lock;

use LogicException;
use RuntimeException;

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
    private $handle = null;

    /**
     * Release on self destruct
     *
     * @var boolean
     */
    private $autoRelease = true;

    /**
     * Constructor
     *
     * @param string $name
     * @param array $options The following options are supported
     *  - autoRelease: default:true. Automatically release on destruct if not released
     */
    public function __construct(string $name, array $options = [])
    {
        $options += ['autoRelease' => true];

        $this->path = sys_get_temp_dir() . "/{$name}.lock";
    }

    /**
     * Checks if a lock has been acquired
     *
     * @return boolean
     */
    public function isAcquired(): bool
    {
        if (! file_exists($this->path)) {
            return false;
        }

        $fp = $this->openFile();
        defer($void, 'fclose', $fp);

        return ! flock($fp, LOCK_SH | LOCK_NB);
    }

    /**
     * Opens the file
     *
     * @return Resource
     */
    private function openFile()
    {
        $fp = fopen($this->path, 'r+');
        if (! $fp) {
            throw new RuntimeException('Error opening lock file');
        }

        return $fp;
    }

    /**
     * Acquires a lock, if it cannot get a lock it will return false.
     *
     * @param boolean $blocking
     * @return boolean
     */
    public function acquire(bool $blocking = true): bool
    {
        if ($this->handle) {
            throw new LogicException('Lock has already been acquired');
        }

        touch($this->path); // Ensure file exists

        $this->handle = $this->openFile();
       
        if (! flock($this->handle, $blocking ? self::BLOCKING : self::NON_BLOCKING)) {
            $this->closeFile();

            return false;
        }

        return ftruncate($this->handle, 0) && (bool) fwrite($this->handle, (string) getmypid()) && fflush($this->handle);
    }

    /**
     * Releases the lock
     *
     * @return void
     */
    public function release(): void
    {
        if (! $this->handle) {
            throw new LogicException('Lock was not acquired');
        }

        if (! flock($this->handle, LOCK_UN)) {
            throw new RuntimeException('Error releasing lock');
        }
        
        $this->closeFile();
    }

    /**
     * Closes the file and cleans up
     *
     * @return void
     */
    private function closeFile(): void
    {
        if ($this->handle && ! fclose($this->handle)) {
            throw new RuntimeException('Error closing lock file');
        }
        $this->handle = null;
    }

    /**
     * Close the file if its still open
     */
    public function __destruct()
    {
        if ($this->handle && $this->autoRelease) {
            $this->release();
        }

        $this->closeFile();
    }
}
