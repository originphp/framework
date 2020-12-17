<?php

/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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

namespace Origin\Model;

use Origin\Model\Engine\MysqlEngine;
use Origin\Model\Engine\SqliteEngine;
use Origin\Model\Engine\PostgresEngine;
use Origin\Core\Exception\InvalidArgumentException;
use Origin\Configurable\StaticConfigurable as Configurable;

class ConnectionManager
{
    use Configurable;

    protected static $engineMap = [
        'mysql' => MysqlEngine::class,
        'postgres' => PostgresEngine::class,
        'sqlite' => SqliteEngine::class
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
    public static function get(string $name): Connection
    {
        if (isset(static::$datasources[$name])) {
            return static::$datasources[$name];
        }

        if (! static::config($name)) {
            throw new InvalidArgumentException(sprintf('The connection configuration `%s` does not exist.', $name));
        }

        $defaults = ['name' => $name, 'host' => 'localhost', 'database' => null, 'username' => null, 'password' => null];

        $config = array_merge($defaults, static::config($name));

        if (isset($config['engine'])) {
            $config['className'] = static::$engineMap[$config['engine']] ?? null;
        } elseif (isset($config['className'])) {
            $classMap = array_flip(static::$engineMap);
            $config['engine'] = $classMap[$config['className']] ?? null;
        }

        if (empty($config['className']) || ! class_exists($config['className'])) {
            throw new InvalidArgumentException('Invalid database engine');
        }

        /**
         * If the SQLite databse does not start with / then add ROOT path.
         * e.g. database/main.sqlite3 is idea.
         */
        if ($config['engine'] === 'sqlite' && $config['database'] && substr($config['database'], 0, 1) !== '/') {
            $config['database'] = ROOT . '/' . $config['database'];
        }

        $datasource = new $config['className'](['connection' => $name] + $config);

        $datasource->connect($config);

        return static::$datasources[$name] = $datasource;
    }

    public static function create(string $name, array $config): Connection
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
    public static function drop(string $name): bool
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
    public static function has(string $name): bool
    {
        return isset(static::$datasources[$name]);
    }

    /**
     * Returns a list of datasources (connections)
     * @codeCoverageIgnore
     * @deprecated Use ConnectionManager::list() instead
     * @return array
     */
    public static function datasources(): array
    {
        deprecationWarning('ConnectionManager::datasources has been deprecated use ConnectionManager::list instead');

        return static::list();
    }

    /**
     * Returns a list of connections
     *
     * @return array
     */
    public static function list(): array
    {
        return array_keys(static::$config);
    }
}
