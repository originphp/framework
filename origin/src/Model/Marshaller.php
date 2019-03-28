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
    protected function buildAssociationMap($associated) : array
    {
        $map = [];
        $model = $this->model;
        foreach (array_merge($model->hasOne, $model->belongsTo) as $alias => $config) {
            if (in_array($alias, $associated)) {
                $map[lcfirst($alias)] = 'one';
            }
        }
        foreach (array_merge($model->hasMany, $model->hasAndBelongsToMany) as  $alias => $config) {
            if (in_array($alias, $associated)) {
                $key = Inflector::pluralize(lcfirst($alias));
                $map[$key] = 'many';
            }
        }
      
        return $map;
    }

    /**
     * normalize the options for associated data
     *
     * @param array $array
     * @return array
     */
    protected function normalizeAssociated(array $array) : array
    {
        $result = [];
      
        foreach ($array as $key => $value) {
            if (is_int($key)) {
                $key = $value;
                $value = [];
            }
            
            $value += ['fields'=>[]];
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * Creates One Entity
     *
     * Options
     * - name: model name
     *
     * @param array $data
     * @param array $options
     * @return \Origin\Model\Entity
     */
    public function one(array $data, array $options=[]) : Entity
    {
        $options += ['name' => null,'associated'=>[],'fields'=>[]];

        $options['associated'] = $this->normalizeAssociated($options['associated']);
        $propertyMap = $this->buildAssociationMap(array_keys($options['associated']));
        
        $entity = new Entity([], $options);

        $properties = [];
     
        foreach ($data as $property => $value) {
            if (isset($propertyMap[$property])) {
                if (!is_array($value)) {
                    $properties[$property] = null;// remove inconsistent data
                    continue;
                }
                $alias = $property;
                $fields = [];
                if ($propertyMap[$property] === 'many') {
                    $alias = Inflector::singularize($alias);
                }
              
                $model = ucfirst($alias);
                if (isset($options['associated'][$model]['fields'])) {
                    $fields = $options['associated'][$model]['fields'];
                    unset($options['associated'][$model]['fields']);
                }
     
                $properties[$property] = $this->{$propertyMap[$property]}($value, [
                    'name'=>ucfirst($alias),
                    'fields' => $fields,
                    'associated' => $options['associated']
                    ]);
            } else {
                $properties[$property] = $value;
            }
        }
       
        if ($options['fields'] and is_array($options['fields'])) {
            foreach ($properties as $property => $value) {
                if (in_array($property, $options['fields'])) {
                    $entity->set($property, $value);
                }
            }
            return $entity;
        }
        $entity->set($properties);
        return $entity;
    }
    
    /**
     * Handles the hasMany and hasAndBelongsToMany
     *
     * @param array $data
     * @param array $options
     * @return array
     */
    public function many(array $data, array $options=[]) : array
    {
        $result = [];
        foreach ($data as $row) {
            $result[] = $this->one($row, $options);
        }
        return $result;
    }

    /**
     * Patches an existing entity, keeping track on changed fields (used by set, not actual value).
     * NOTE: Associated data will create new entity, as related data can contain references to changed
     * data.
     *
     * @param Entity $entity
     * @param array  $data
     * @return \Origin\Model\Entity
     */
    public function patch(Entity $entity, array $data, array $options=[]) : Entity
    {
        $options += ['name' => $entity->name(),'associated'=>[],'fields'=>[]];
        
        $entity->reset(); // reset modified

        $options['associated'] = $this->normalizeAssociated($options['associated']);
        $propertyMap = $this->buildAssociationMap(array_keys($options['associated']));

        $properties = [];
        foreach ($data as $property => $value) {
            if (isset($propertyMap[$property])) {
                if (!is_array($value)) {
                    $properties[$property] = null;// remove inconsistent data
                    continue;
                }
                $alias = $property;
                $fields = [];
                if ($propertyMap[$property] === 'many') {
                    $alias = Inflector::singularize($alias);
                }

                $model = ucfirst($alias);
                if (isset($options['associated'][$model]['fields'])) {
                    $fields = $options['associated'][$model]['fields'];
                    unset($options['associated'][$model]['fields']);
                }

                $properties[$property] = $this->{$propertyMap[$property]}($value, [
                    'name'=>ucfirst($alias),
                    'fields' => $fields,
                    'associated' => $options['associated'] // passing same data might
                    ]);
            } else {
                $original = $entity->get($property);
                if ($value !== $original) {
                    $properties[$property] = $value;
                }
            }
        }
       
        if ($options['fields'] and is_array($options['fields'])) {
            foreach ($properties as $property => $value) {
                if (in_array($property, $options['fields'])) {
                    $entity->set($property, $value);
                }
            }
            return $entity;
        }
        $entity->set($properties);
        return $entity;
    }
}
