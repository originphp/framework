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

namespace Origin\Core;

class Resolver
{
    public static function className(string $class, $objectType = null, $suffix = null)
    {
        if (strpos($class, '\\') !== false) {
            return $class;
        }
        $namespace = Configure::read('App.namespace');
        list($plugin, $class) = pluginSplit($class);
        if ($plugin) {
            $namespace = $plugin;
        }
        if ($objectType === null) {
            $path = '\\'.$class.$suffix;
        } else {
            $path = '\\'.str_replace('/', '\\', $objectType).'\\'.$class.$suffix;
        }
       
        if (static::classExists($namespace.$path)) {
            return $namespace.$path;
        }

        if (static::classExists('Origin'.$path)) {
            return 'Origin'.$path;
        }

        return null;
    }

    /**
     * To help with testing.
     */
    protected static function classExists(string $className)
    {
        return class_exists($className);
    }
}