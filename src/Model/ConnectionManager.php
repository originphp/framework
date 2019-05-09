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
use Origin\Model\Driver\MySQLDriver;

class ConnectionManager
{
    use StaticConfigTrait;

    protected static $drivers = [
        'mysql' => 'Origin\Model\Driver\MySQLDriver',
        'pgsql' => 'Origin\Model\Driver\PostgreSQLDriver',
    ];
   
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
     * @return \Origin\Model\Datasource
     */
    public static function get(string $name)
    {
        if (isset(static::$datasources[$name])) {
            return static::$datasources[$name];
        }

        if (!static::config($name)) {
            throw new MissingDatasourceException("No configuration for {$name} datasource.");
        }

        $datasource = new MySQLDriver();
  
        $defaults = ['host' => 'localhost', 'database' => null, 'username' => null, 'password' => null];
        $config = array_merge($defaults, static::config($name));
        $datasource->connect($config);

        return static::$datasources[$name] = $datasource;
    }

    public static function create(string $name, array $config)
    {
        self::config($name, $config);
        return self::get($name);
    }

    /**
     * Drops a connection
     *
     * @param string $name
     * @return void
     */
    public static function drop(string $name)
    {
        if (isset(static::$datasources[$name])) {
            static::config($name, null);
            unset(static::$datasources[$name]);
            return true;
        }
        return false;
    }

    public static function has(string $name)
    {
        return isset(static::$datasources[$name]);
    }
}
