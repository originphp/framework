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
    protected function buildAssociationMap($associated)
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

   
    public function prepareFieldOptions(array $options)
    {
        $associated = [];
        if (isset($options['associated'])) {
            $associated = $options['associated'];
            unset($options['associated']);
        }
        return [$this->model->alias=>$options] + $associated;
    }


    protected function standardizeAssociated(array $array)
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
     * @return \Origin\Model\
     */
    public function one(array $data, array $options=[])
    {
        $options += ['name' => null,'associated'=>[],'fields'=>[]];

        $options['associated'] = $this->standardizeAssociated($options['associated']);
        $propertyMap = $this->buildAssociationMap(array_keys($options['associated']));
        
        $entity = new Entity([], $options);

        $properties = [];
        foreach ($data as $property => $value) {
            if (isset($propertyMap[$property]) and is_array($value)) {
                $alias = $property;
                $fields = [];
                if ($propertyMap[$property] === 'many') {
                    $alias = Inflector::singularize($alias);
                }
                if (isset($options['associated'][$alias]['fields'])) {
                    $fields = $options['associated'][$alias]['fields'];
                    unset($options['associated'][$alias]);
                }
                $properties[$property] = $this->{$propertyMap[$property]}($value, [
                    'name'=>ucfirst($alias),
                    'fields' => $fields,
                    'associated' => $options['associated'] // passing same data might
                    ]);
            } else {
                $properties[$property] = $value;
            }
        }
        if ($options['fields']) {
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
    public function many(array $data, array $options=[])
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
    public function patch(Entity $entity, array $data, array $options=[])
    {
        $options += ['name' => $entity->name(),'associated'=>[],'fields'=>[]];
        
        $entity->reset(); // reset modified

        $options['associated'] = $this->standardizeAssociated($options['associated']);
        $propertyMap = $this->buildAssociationMap(array_keys($options['associated']));

        $properties = [];
        foreach ($data as $property => $value) {
            if (isset($propertyMap[$property]) and is_array($value)) {
                $alias = $property;
                $fields = [];
                if ($propertyMap[$property] === 'many') {
                    $alias = Inflector::singularize($alias);
                }
                if (isset($options['associated'][$alias]['fields'])) {
                    $fields = $options['associated'][$alias]['fields'];
                    unset($options['associated'][$alias]);
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
        if ($options['fields']) {
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
