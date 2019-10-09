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

namespace Origin\Storage\Engine;

use Origin\Core\ConfigTrait;
use Origin\Core\HookTrait;

abstract class BaseEngine
{
    use ConfigTrait,HookTrait;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config($config);
        $this->executeHook('initialize', [$config]);
    }

    /**
     * Reads from the storage
     *
     * @param string $name
     * @return string
     */
    abstract public function read(string $name) : string;

    /**
     * Writes to the storage
     *
     * @param string $name
     * @param string $data
     * @return bool
     */
    abstract public function write(string $name, string $data) : bool;

    /**
     * Deletes from the storage
     *
     * @param string $name
     * @return bool
     */
    abstract public function delete(string $name) : bool;

    /**
     * Checks if a file exists on the storage
     *
     * @param string $name
     * @return bool
     */
    abstract public function exists(string $name) : bool;
    
    /**
     * Returns the list of files from the storage
     *
     * @param string $name
     * @return array
     */
    abstract public function list(string $name = null) : array;
}
