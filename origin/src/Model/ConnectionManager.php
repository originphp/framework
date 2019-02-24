<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Model;

use Origin\Model\Exception\MissingDatasourceException;
use Origin\Core\StaticConfigTrait;

class ConnectionManager
{
    use StaticConfigTrait;
    /**
     * Holds all the connections.
     *
     * @var array
     */
    protected static $datasources = [];

    /**
     * Gets a datasource.
     *
     * @param string $name default
     *
     * @return Datasource
     */
    public static function get(string $name)
    {
        if (isset(static::$datasources[$name])) {
            return static::$datasources[$name];
        }

        if (!static::config($name)) {
            throw new MissingDatasourceException($name);
        }

        $datasource = new Datasource();

        $defaults = [
            'host' => 'localhost', 'database' => null, 'username' => null, 'password' => null
        ];
        $config = array_merge($defaults, static::config($name));
        $datasource->connect($config);

        static::$datasources[$name] = $datasource;

        return $datasource;
    }

    public static function has(string $name)
    {
        return isset(static::$datasources[$name]);
    }
}
