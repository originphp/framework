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
namespace Origin\Http\Session\Engine;

use Origin\Core\Dot;

class ArrayEngine extends BaseEngine
{
    /**
     * @var \Origin\Core\Dot
     */
    protected $hash;

    protected function initialize(): void
    {
        $this->hash = new Dot();
    }

    /**
     * Starts the session
     *
     * @return boolean
     */
    public function start(): bool
    {
        if ($this->started) {
            return false;
        }

        if (! $this->id || ! $this->isValid($this->id)) {
            $this->id = $this->generateId();
        }

        if ($this->timedOut()) {
            $this->destroy();
            $this->start();
        }

        return $this->started = true;
    }

    /**
     * Checks if the session was started
     *
     * @return boolean
     */
    public function started(): bool
    {
        return $this->started;
    }

    /**
     * Reads from the session
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function read(string $key, $default = null)
    {
        return $this->hash->get($key, $default);
    }

    /**
     * Writes to the session
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function write(string $key, $value): void
    {
        $this->hash->set($key, $value);
    }

    /**
     * Deletes an item from the session
     *
     * @param string $key
     * @return boolean
     */
    public function delete(string $key): bool
    {
        return $this->hash->delete($key);
    }

    /**
     * Checks if a key exists in the session
     *
     * @param string $key
     * @return boolean
     */
    public function exists(string $key): bool
    {
        return $this->hash->has($key);
    }

    /**
     * Clears the session
     *
     * @return void
     */
    public function clear(): void
    {
        $this->hash = new Dot();
    }

    /**
     * Closes the session
     *
     * @return boolean
     */
    public function close(): bool
    {
        if ($this->started() === false) {
            return false;
        }
        $this->started = false;

        return true;
    }

    /**
     * Destroys the session
     *
     * @return void
     */
    public function destroy(): void
    {
        $this->hash = new Dot();
        $this->started = false;
        $this->id = null;
    }

    /**
     * Gets the session data as an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->hash->items();
    }
}
