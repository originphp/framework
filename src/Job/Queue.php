<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * OriginPHP Queue System
 */
declare(strict_types = 1);
namespace Origin\Job;

use Origin\Job\Engine\BaseEngine;
use Origin\Core\Exception\InvalidArgumentException;
use Origin\Configurable\StaticConfigurable as Configurable;

class Queue
{
    use Configurable;

    /**
     * Holds the queue engines
     *
     * @var array
     */
    protected static $loaded = [];
    
    /**
     * Gets the configured connection
     *
     * @param string $name
     * @return \Origin\Job\Engine\BaseEngine
     */
    public static function connection(string $name): BaseEngine
    {
        if (isset(static::$loaded[$name])) {
            return static::$loaded[$name];
        }

        return static::$loaded[$name] = static::buildEngine($name);
    }

    /**
     * Builds an engine using the configuration
     *
     * @param string $name
     * @throws \Origin\Core\Exception\InvalidArgumentException
     * @return \Origin\Job\Engine\BaseEngine
     */
    protected static function buildEngine(string $name): BaseEngine
    {
        $config = static::config($name);
        if ($config) {
            if (isset($config['engine'])) {
                $config['className'] = __NAMESPACE__  . "\Engine\\{$config['engine']}Engine";
            }
            if (empty($config['className']) || ! class_exists($config['className'])) {
                throw new InvalidArgumentException("Queue engine for {$name} could not be found");
            }

            return new $config['className']($config);
        }
        throw new InvalidArgumentException(sprintf('The queue configuration `%s` does not exist.', $name));
    }
}
