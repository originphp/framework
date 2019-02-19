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

/**
 * Need to have a universal config throughout framework.
 */
trait StaticConfigTrait
{

    /**
     * Holds the config.
     *
     * @var array
     */
    protected static $config = null;

    /**
     * Sets/Gets config
     *
     *  Class::config($config);
     *  Class::config('setting',true);
     *
     *  $config = Class::config();
     *  $setting = Class::config('setting');
     * @param null|array|string $key
     * @param mixed $value
     * @return void
     */
    public static function config($key = null, $value = null)
    {
        if (static::$config === null) {
            static::$config = [];
            if (isset(static::$defaultConfig)) {
                static::$config = static::$defaultConfig;
            }
        }

        if (is_array($key) or  func_num_args() === 2) {
            return static::setConfig($key, $value);
        }
        return static::getConfig($key);
    }
    /**
     * Sets the config
     *
     * @param string|array $key
     * @param mixed $value
     * @return void
     */
    protected function setConfig($key = null, $value = null)
    {
        $config = $key;
        if (is_string($key)) {
            $config = [$key => $value];
        }
        foreach ($config as $key => $value) {
            static::$config[$key] = $value;
        }
        return true;
    }
    /**
     * Gets the config (all or part)
     *
     * @param string|array $key
     * @param mixed $value
     * @return void
     */
    protected function getConfig($key = null)
    {
        if ($key === null) {
            return static::config;
        }
        if (isset(static::config[$key])) {
            return static::config[$key];
        }
    }
}
