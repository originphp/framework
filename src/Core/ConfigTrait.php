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

trait ConfigTrait
{
    /**
     * Holds the config.
     *
     * @var array
     */
    protected $config = null;

    /**
     * Intializes the configuration array
     *
     * @return void
     */
    protected function initConfig() : void
    {
        $this->config = [];
        if (isset($this->defaultConfig)) {
            $this->config = $this->defaultConfig;
        }
    }
    /**
     * Sets/Gets config
     *
     *  $this->config($config);
     *  $this->config('setting',true);
     *
     *  $config = $this->config();
     *  $setting = $this->config('setting');
     * @param null|array|string $key
     * @param mixed $value
     * @return mixed
     */
    public function config($key = null, $value = null)
    {
        if (is_array($key) or func_num_args() === 2) {
            $this->setConfig($key, $value);

            return;
        }

        return $this->getConfig($key);
    }
    /**
     * Sets the config
     *
     * @param string|array $key
     * @param mixed $value
     * @return void
     */
    protected function setConfig($key, $value = null) : void
    {
        if ($this->config === null) {
            $this->initConfig();
        }
        $config = $key;
        if (is_string($key)) {
            $config = [$key => $value];
        }
        foreach ($config as $key => $value) {
            if ($value === null) {
                unset($this->config[$key]);
                continue;
            }
            $this->config[$key] = $value;
        }
    }
    /**
     * Gets an item from config
     *
     * @param string $key
     * @param mixed $default default value to return
     * @return mixed
     */
    protected function getConfig(string $key = null, $default = null)
    {
        if ($this->config === null) {
            $this->initConfig();
        }
        if ($key === null) {
            return $this->config;
        }
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }

        return $default;
    }
}
