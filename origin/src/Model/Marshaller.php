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
namespace Origin\Model;

use Origin\Core\Inflector;

use Origin\Model\Model;

class Marshaller
{
    /**
     * Undocumented variable
     *
     * @var \Origin\Model\Model
     */
    protected $model = null;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Creates a map for entity fields.
     * Example:
     *  ['author' => 'one', 'tags' => 'many']
     *
     * @return array
     */
    protected function buildAssociationMap()
    {
        $map = [];
        $model = $this->model;
        foreach (array_merge($model->hasOne, $model->belongsTo) as $alias => $config) {
            $map[lcfirst($alias)] = 'one';
        }
        foreach (array_merge($model->hasMany, $model->hasAndBelongsToMany) as  $alias => $config) {
            $key = Inflector::pluralize(lcfirst($alias));
            $map[$key] = 'many';
        }

        return $map;
    }

    /**
     * Creates One Entity
     *
     * Options
     * - name: model name
     *
     * @param array $data
     * @param array $options
     * @return Entity
     */
    public function one(array $data, array $options=[])
    {
        $options += ['name' => null];

        $propertyMap = $this->buildAssociationMap($options);
   
        $entity = new Entity([], $options);
        foreach ($data as $property => $value) {
            if (isset($propertyMap[$property]) and is_array($value)) {
                $alias = $property;
                if ($propertyMap[$property] === 'many') {
                    $alias = Inflector::singularize($alias);
                }
                $entity->set($property, $this->{$propertyMap[$property]}($value, ['name'=>ucfirst($alias)]));
            } else {
                $entity->set($property, $value);
            }
        }
        return $entity;
    }
    
    /**
     * Handles the hasMany and hasAndBelongsToMany
     *
     * @param array $data
     * @param array $options
     * @return array
     */
    public function many(array $data, array $options=[])
    {
        $result = [];
        foreach ($data as $row) {
            $result[] = $this->one($row, $options);
        }
        return $result;
    }

    /**
     * Patches an existing entity, keeping track on changed fields (used by set, not actual value), this so
     * when saving existing entities, we don't save non submited data
     *
     * @param Entity $entity
     * @param array  $data
     * @return Entity
     */
    public function patch(Entity $entity, array $data, array $options=[])
    {
        $options += ['name' => $entity->name()];
        
        $entity->reset(); // reset modified

        $propertyMap = $this->buildAssociationMap($options);
        
        foreach ($data as $property => $value) {
            if (isset($propertyMap[$property]) and is_array($value)) {
                $alias = $property;
                if ($propertyMap[$property] === 'many') {
                    $alias = Inflector::singularize($alias);
                }
                $entity->set($property, $this->{$propertyMap[$property]}($value, ['name'=>ucfirst($alias)]));
            } else {
                $entity->set($property, $value);
            }
        }
        return $entity;
    }
}
