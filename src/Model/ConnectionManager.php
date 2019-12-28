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
declare(strict_types = 1);
namespace Origin\Model;

use Origin\Core\Exception\InvalidArgumentException;
use Origin\Configurable\StaticConfigurable as Configurable;

class ConnectionManager
{
    use Configurable;

    protected static $engines = [
        'mysql' => __NAMESPACE__ . '\Engine\MysqlEngine',
        'pgsql' => __NAMESPACE__ . '\Engine\PgsqlEngine',
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
     * @return \Origin\Model\Connection
     */
    public static function get(string $name) : Connection
    {
        if (isset(static::$datasources[$name])) {
            return static::$datasources[$name];
        }

        if (! static::config($name)) {
            throw new InvalidArgumentException(sprintf('The connection configuration `%s` does not exist.', $name));
        }

        $defaults = ['name' => $name, 'host' => 'localhost', 'database' => null, 'username' => null, 'password' => null,'engine' => 'mysql'];
        
        $config = array_merge($defaults, static::config($name));
       
        if (! isset(static::$engines[$config['engine']])) {
            throw new InvalidArgumentException("Unkown engine `{$config['engine']}` in `{$name}` connection.");
        }
        static::$driver = $config['engine'];
      
        $class = static::$engines[$config['engine']];
        $datasource = new $class(['connection' => $name] + $config);

        $datasource->connect($config);

        return static::$datasources[$name] = $datasource;
    }

    public static function create(string $name, array $config) : Connection
    {
        self::config($name, $config);

        return self::get($name);
    }

    /**
     * Drops a connection
     *
     * @param string $name
     * @return bool
     */
    public static function drop(string $name) : bool
    {
        if (isset(static::$datasources[$name])) {
            static::config($name, null);
            unset(static::$datasources[$name]);

            return true;
        }

        return false;
    }

    /**
     * Checks if there is a configured connection
     *
     * @param string $name
     * @return boolean
     */
    public static function has(string $name) : bool
    {
        return isset(static::$datasources[$name]);
    }

    /**
     * Returns a list of datasources (connections)
     *
     * @return array
     */
    public static function datasources() : array
    {
        return array_keys(static::$config);
    }
}
