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
declare(strict_types = 1);
namespace Origin\Core;

class Resolver
{
    /**
     * Resolves the class name
     *
     * @param string $class
     * @param string $objectType
     * @param string $suffix
     * @param string $group
     * @return string|null
     */
    public static function className(string $class, string $objectType = null, string $suffix = null, string $group = null): ?string
    {
        if (strpos($class, '\\') !== false) {
            return $class;
        }
        
        $namespace = Config::read('App.namespace');
        list($plugin, $class) = pluginSplit($class);
        if ($plugin) {
            $namespace = $plugin;
        }

        $prefix = $group === null ? null : ('\\' . $group);
       
        $path = $prefix . '\\'. $class . $suffix;
        if ($objectType) {
            $path = $prefix . '\\'.str_replace('/', '\\', $objectType).'\\'.$class. $suffix;
        }

        if (static::classExists($namespace.$path)) {
            return $namespace . $path;
        }

        if (static::classExists('Origin' . $path)) {
            return 'Origin' . $path;
        }

        return null;
    }
    /**
     * Helper for testing
     *
     * @param string $class
     * @return boolean
     */
    public static function classExists(string $class): bool
    {
        return class_exists($class);
    }
}
