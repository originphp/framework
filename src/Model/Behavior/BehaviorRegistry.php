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

namespace Origin\Model\Behavior;

use Origin\Core\ObjectRegistry;
use Origin\Model\Model;
use Origin\Core\Resolver;
use Origin\Model\Exception\MissingBehaviorException;

/**
 * A quick and easy way to create models and add them to registry. Not sure if
 * this will be added.
 */
class BehaviorRegistry extends ObjectRegistry
{
    /**
     * Model
     *
     * @var \Origin\Model\Model
     */
    protected $model = null;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    protected function className(string $class)
    {
        return Resolver::className($class, 'Model/Behavior');
    }

    /**
     * Undocumented function
     *
     * @param string $class
     * @param array $options
     * @return \Origin\Model\Behavior\Behavior
     */
    protected function createObject(string $class, array $options = [])
    {
        return new $class($this->model, $options);
    }

    protected function throwException(string $object)
    {
        throw new MissingBehaviorException($object);
    }
}
