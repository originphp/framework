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

    protected static $engines = [
        'mysql' => 'Origin\Engine\Datasource\MySQLEngine',
        'pgsql' => 'Origin\Engine\Datasource\PostgreSQLEngine',
    ];
    /**
     * Holds the driver
     *
     * @var string
     */
    public static $driver = null;
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

        $defaults = ['host' => 'localhost', 'database' => null, 'username' => null, 'password' => null,'engine'=>'mysql'];
        
        $config = array_merge($defaults, static::config($name));
       
    
        if(!isset(static::$engines[$config['engine']])){
            throw new MissingDatasourceException("Unkown driver for {$name} datasource.");
        }
        static::$driver = $config['engine'];
      
        $class = static::$engines[$config['engine']];
        $datasource = new $class(['datasource'=>$name]+$config);

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

    /**
     * Returns a list of datasources
     *
     * @return array
     */
    public static function datasources()
    {
        return array_keys(static::$config);
    }
}
