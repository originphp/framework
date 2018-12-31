<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 *
 * @link          https://www.originphp.com
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Model;

use Origin\Model\Exception\MissingDatasourceException;

class ConnectionManager
{
    /**
     * Holds all the connections.
     *
     * @var array
     */
    protected static $datasources = [];

    /**
     * Config for database connections.
     *
     * @var array
     */
    protected static $config = [];

    /**
     * Dynamically create a new connection.
     *
     * @param string $name   name of connection
     * @param array  $config array(host,database,login,password)
     */
    public static function config(string $name, array $config)
    {
        $defaults = array('host' => 'localhost', 'database' => null, 'username' => null, 'password' => null);
        static::$config[$name] = array_merge($defaults, $config);
    }

    /**
     * Gets a datasource.
     *
     * @param string $name default
     *
     * @return [type] [description]
     */
    public static function get(string $name)
    {
        if (isset(static::$datasources[$name])) {
            return static::$datasources[$name];
        }

        if (!isset(static::$config[$name])) {
            throw new MissingDatasourceException($name);
        }

        $datasource = new Datasource();
        $datasource->connect(static::$config[$name]);

        static::$datasources[$name] = $datasource;

        return $datasource;
    }

    public static function has(string $name)
    {
        return isset(static::$datasources[$name]);
    }
}
