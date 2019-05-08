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
trait ConfigTrait
{

    /**
     * Holds the config.
     *
     * @var array
     */
    protected $config = null;

    protected function initConfig()
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
        if (is_array($key) or  func_num_args() === 2) {
            return $this->setConfig($key, $value);
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
    public function setConfig($key, $value = null)
    {
        if ($this->config === null) {
            $this->initConfig();
        }
        $config = $key;
        if (is_string($key)) {
            $config = [$key => $value];
        }
        foreach ($config as $key => $value) {
            $this->config[$key] = $value;
        }
        return true;
    }
    /**
     * Gets a config
     *
     * @param string $key
     * @param mixed $default default value to return
     * @return void
     */
    public function getConfig(string $key = null, $default =null)
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
