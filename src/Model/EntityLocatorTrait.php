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

namespace Origin\Model;

/**
 * Locates an entity cache, requires property $model
 */
trait EntityLocatorTrait
{
    /**
     * Cached classes
     *
     * @var array
     */
    private $entityLocatorCache = [];

    /**
     * Gets the entity class for a model
     *
     * @param \Origin\Model\Model $model
     * @return string
     */
    protected function entityClass(Model $model = null) : string
    {
        if ($model === null) {
            return Entity::class;
        }

        if (!isset($this->entityLocatorCache[$model->name])) {
            list($namespace, ) = namespaceSplit(get_class($model));
            $entityClass = $namespace . '\Entity\\' . $model->name ;
            if (!class_exists($entityClass)) {
                $entityClass = Entity::class;
            }
            $this->entityLocatorCache[$model->name] = $entityClass;
        }
      
        return $this->entityLocatorCache[$model->name];
    }
}
