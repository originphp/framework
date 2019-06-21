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

namespace Origin\Engine;

use Origin\Core\ConfigTrait;

abstract class StorageEngine
{
    use ConfigTrait;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config=[])
    {
        $this->config($config);
        $this->initialize($config);
    }

    abstract public function initialize(array $config);

    abstract public function read(string $name);

    abstract public function write(string $name, string $data);

    abstract public function delete(string $name, array $options=[]);

    abstract public function exists(string $name);
 
    abstract public function list(string $name = null);
}
