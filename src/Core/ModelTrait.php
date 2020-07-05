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

use Origin\Core\Exception\Exception;

/**
 * A trait used within the framework to make it easy to load models, this layer
 * is so that other code can still run without throwing missing class exceptions even
 * though models might not be used.
 *
 * This solution seems simple enough to get it work, but need to find out the correct and best
 * way to do this, if this is not it.
 */
trait ModelTrait
{

    /**
      * Loads a model, uses from registry or creates a new one.
      *
      * @param string $model User, MyPlugin.User, User::class
      * @param array $options
      * @return mixed $model type not set on purpose
      */
    public function loadModel(string $model, array $options = [])
    {
        if (! function_exists('modelRegistryGet')) {
            throw new Exception('originphp/model package is not loaded.');
        }

        list($plugin, $alias) = pluginSplit($model);

        if (isset($this->$alias)) {
            return $this->$alias;
        }

        return $this->$alias = modelRegistryGet($model, $options);
    }
}
