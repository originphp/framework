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
declare(strict_types=1);
namespace Origin\Http\Session;

interface SessionEngineInterface
{
    /**
     * Sets or gets the session ID
     *
     * @param string|null $id
     * @return string|void
     */
    public function id(string $id = null);

    /**
     * Sets or gets the session name
     *
     * @param string $name
     * @return string|void
     */
    public function name(string $name = null);

    /**
     * Starts the session
     *
     * @return boolean
     */
    public function start(): bool;

    /**
     * Checks if the session was started
     *
     * @return boolean
     */
    public function started(): bool;

    /**
     * Reads a value from the Session
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function read(string $name, $default = null);

    /**
     * Writes a value to the Session
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function write(string $name, $value): void;

    /**
     * Deletes a value from the session
     *
     * @param string $name
     * @return boolean
     */
    public function delete(string $name): bool;

    /**
     * Checks if the session has a value
     *
     * @param string $name
     * @return boolean
     */
    public function exists(string $name): bool;

    /**
     * Clears the data in the session but it keeps it open
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Writes the data and closes the session
     *
     * @return boolean
     */
    public function close(): bool;

    /**
     * Destroys all the data for this session and sets started to faled
     *
     * @return void
     */
    public function destroy(): void;

    /**
     * Gets all the session values
     */
    public function toArray(): array;
}
