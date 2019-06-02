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

namespace Origin\Engine\Storage;

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
        $this->setConfig($config);
        $this->initialize($config);
    }

    abstract function initialize(array $config);

    abstract function read(string $name);

    abstract function write(string $name,string $data);

    abstract function delete(string $name);

    abstract function exists(string $name);
 
    abstract function list(string $name = null);
}