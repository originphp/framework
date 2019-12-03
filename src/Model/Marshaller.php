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

use Origin\Inflector\Inflector;

class Marshaller
{
    use EntityLocatorTrait;
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
        foreach (array_merge($model->association('hasOne'), $model->association('belongsTo')) as $alias => $config) {
            if (in_array($alias, $associated)) {
                $map[lcfirst($alias)] = 'one';
            }
        }
        foreach (array_merge($model->association('hasMany'), $model->association('hasAndBelongsToMany')) as  $alias => $config) {
            if (in_array($alias, $associated)) {
                $key = Inflector::plural(lcfirst($alias));
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
            
            $value += ['fields' => []];
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
    public function one(array $data, array $options = []) : Entity
    {
        $options += ['name' => null,'associated' => [],'fields' => []];

        $options['associated'] = $this->normalizeAssociated($options['associated']);
        $propertyMap = $this->buildAssociationMap(array_keys($options['associated']));
        
        /**
         * Get Model from the ModelRegistry
         */
        $model = null;
        if ($options['name']) {
            $model = ModelRegistry::get($options['name']);
        }
       
        $entityClass = $this->entityClass($model);
        $entity = new $entityClass([], $options);
       
        $properties = [];
     
        foreach ($data as $property => $value) {
            if (isset($propertyMap[$property])) {
                if (! is_array($value)) {
                    $properties[$property] = null;// remove inconsistent data
                    continue;
                }
                $alias = $property;
                $fields = [];
                if ($propertyMap[$property] === 'many') {
                    $alias = Inflector::singular($alias);
                }
              
                $alias = ucfirst($alias);
                if (isset($options['associated'][$alias]['fields'])) {
                    $fields = $options['associated'][$alias]['fields'];
                    unset($options['associated'][$alias]['fields']);
                }

                $marshaller = new Marshaller($this->model->$alias);
                $properties[$property] = $marshaller->{$propertyMap[$property]}($value, [
                    'name' => $alias,
                    'fields' => $fields,
                    'associated' => $options['associated'],
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
    public function many(array $data, array $options = []) : array
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
     * @param \Origin\Model\Entity $entity
     * @param array  $data
     * @return \Origin\Model\Entity
     */
    public function patch(Entity $entity, array $data, array $options = []) : Entity
    {
        $options += ['name' => $entity->name(),'associated' => [],'fields' => []];
        
        $entity->reset(); // reset modified

        $options['associated'] = $this->normalizeAssociated($options['associated']);
        $propertyMap = $this->buildAssociationMap(array_keys($options['associated']));
      
        $properties = [];

        foreach ($data as $property => $value) {
            if (isset($propertyMap[$property])) {
                if (! is_array($value)) {
                    $properties[$property] = null;// remove inconsistent data
                    continue;
                }
                $alias = $property;
                $fields = [];
                if ($propertyMap[$property] === 'many') {
                    $alias = Inflector::singular($alias);
                }

                $model = ucfirst($alias);
                if (isset($options['associated'][$model]['fields'])) {
                    $fields = $options['associated'][$model]['fields'];
                    unset($options['associated'][$model]['fields']);
                }

                $patchOptions = [
                    'name' => ucfirst($alias),
                    'fields' => $fields,
                    'associated' => $options['associated']
                ];

                /**
                 * Match records for hasOne and belongsTo
                 */
                if ($propertyMap[$property] === 'one' and isset($this->model->association('hasOne')[$model])) {
                    $parentPrimaryKey = $this->getPrimaryKey($entity->name());
                    $foreignKey = $this->model->association('hasOne')[$model]['foreignKey'];
                    $matched = ($foreignKey and isset($value[$foreignKey]) and $value[$foreignKey] === $entity->{$parentPrimaryKey});
                } else {
                    $primaryKey = $this->getPrimaryKey($model);
                    $matched = ($primaryKey and isset($value[$primaryKey]) and $value[$primaryKey] === $entity->$property->$primaryKey);
                }
  
                if ($propertyMap[$property] === 'one' and $entity->$property instanceof Entity and $matched) {
                    $properties[$property] = $this->patch($entity->$property, $value, $patchOptions);
                } elseif ($propertyMap[$property] === 'many' and $entity->$property instanceof Collection) {
                    $properties[$property] = $this->matchMany($entity->$property, $value, $patchOptions);
                } else {
                    $properties[$property] = $this->{$propertyMap[$property]}($value, $patchOptions);
                }
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

    /**
     * Part of the patch process, check each record if the primaryKey matches then patch, if not overwrite the
     * data. Issues here are hasAndBelongsToMany with just ID, ids not matching up.
     *
     * @param \Origin\Model\Collection $collection
     * @param array $data
     * @param array $options
     * @return void
     */
    private function matchMany(Collection $collection, array $data, array $options = [])
    {
        // for matching we need a model
        $primaryKey = $this->getPrimaryKey($options['name']);
        if (! $primaryKey) {
            return $this->many($data, $options);
        }

        $out = [];
        foreach ($data as $index => $record) {
            $fields = count($record);
            $hasPrimaryKey = isset($record[$primaryKey]) and isset($collection[$index]->primaryKey);
            if ($hasPrimaryKey and $fields > 1 and $collection[$index]->$primaryKey === $record[$primaryKey]) {
                $out[] = $this->patch($collection[$index], $record, $options);
            } else {
                $out[] = $this->one($record, $options);
            }
        }

        return $out;
    }

    /**
     * Finds a primary key for an entity
     *
     * @param string $name
     * @return string|null
     */
    private function getPrimaryKey(string $name) : ?string
    {
        $model = ModelRegistry::get($name);

        return $model ? $model->primaryKey() : null;
    }
}
