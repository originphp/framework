<?php
declare(strict_types = 1);
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

/**
 * This is a Queue System with a MySQL backend. For now I want to keep it as one file, until packages are ready.
 * @todo investigate using pcntl_signal/ pcntl_alarm for timing out tasks
 */

namespace Origin\Job;

use Origin\Job\Engine\BaseEngine;
use Origin\Configurable\StaticConfigurable as Configurable;
use Origin\Exception\InvalidArgumentException;

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
    public static function connection(string $name) : BaseEngine
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
     * @throws \Origin\Exception\InvalidArgumentException
     * @return \Origin\Job\Engine\BaseEngine
     */
    protected static function buildEngine(string $name) : BaseEngine
    {
        $config = static::config($name);
        if ($config) {
            if (isset($config['engine'])) {
                $config['className'] = __NAMESPACE__  . "\Engine\\{$config['engine']}Engine";
            }
            if (empty($config['className']) or ! class_exists($config['className'])) {
                throw new InvalidArgumentException("Queue engine for {$name} could not be found");
            }

            return new $config['className']($config);
        }
        throw new InvalidArgumentException(sprintf('The queue configuration `%s` does not exist.', $name));
    }
}
