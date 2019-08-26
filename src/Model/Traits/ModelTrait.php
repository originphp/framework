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

namespace Origin\Model\Traits;

use Origin\Model\Model;
use Origin\Model\ModelRegistry;
use Origin\Model\Exception\MissingModelException;

trait ModelTrait
{
    /**
      * Loads a model, uses from registry or creates a new one.
      *
      * @param string $model
      * @param array $options
      * @return \Origin\Model\Model
      */
    public function loadModel(string $model, array $options = []) : Model
    {
        list($plugin, $alias) = pluginSplit($model);

        if (isset($this->{$alias})) {
            return $this->{$alias};
        }

        $this->{$alias} = ModelRegistry::get($model, $options);

        if ($this->{$alias}) {
            return $this->{$alias};
        }
        throw new MissingModelException($model);
    }
}
