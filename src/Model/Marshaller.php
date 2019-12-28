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
declare(strict_types = 1);
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
                // determine model
                $model = ucfirst($property);
                if ($propertyMap[$property] === 'many') {
                    $model = Inflector::singular($model);
                }
 
                // extract fields
                $fields = [];
                if (isset($options['associated'][$model]['fields'])) {
                    $fields = $options['associated'][$model]['fields'];
                    unset($options['associated'][$model]['fields']);
                }

                $marshaller = new Marshaller($this->model->$model);
                $properties[$property] = $marshaller->{$propertyMap[$property]}($value, [
                    'name' => $model,
                    'fields' => $fields,
                    'associated' => $options['associated'],
                ]);
            } else {
                $properties[$property] = $value;
            }
        }
       
        return $this->setProperties($entity, $properties, $options);
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
                // remove inconsistent data
                if (! is_array($value)) {
                    $properties[$property] = null;
                    continue;
                }
               
                // determine model
                $model = ucfirst($property);
                if ($propertyMap[$property] === 'many') {
                    $model = Inflector::singular($model);
                }

                // extract fields
                $fields = [];
                if (isset($options['associated'][$model]['fields'])) {
                    $fields = $options['associated'][$model]['fields'];
                    unset($options['associated'][$model]['fields']);
                }

                $patchOptions = [
                    'name' => $model, 'fields' => $fields,'associated' => $options['associated']
                ];

                if (! $entity->$property instanceof Entity and ! $entity->$property instanceof Collection) {
                    $properties[$property] = $this->{$propertyMap[$property]}($value, $patchOptions);
                    continue;
                }

                // entities will be patched in primary key matches, if not a new entity will be created
                // with patched data.
                
                // Match hasOne and belongsTo using primaryKey
                if ($propertyMap[$property] === 'one') {
                    $primaryKey = $this->getPrimaryKey($model);
                    if (($primaryKey and isset($value[$primaryKey]) and (string) $value[$primaryKey] === (string) $entity->$property->$primaryKey)) {
                        $properties[$property] = $this->patch($entity->$property, $value, $patchOptions);
                        continue;
                    }
                }

                // Match hasMany and hasAndBelongsToMany
                if ($propertyMap[$property] === 'many') {
                    $properties[$property] = $this->matchMany($entity->$property, $value, $patchOptions);
                } else {
                    $properties[$property] = $this->{$propertyMap[$property]}($value, $patchOptions);
                }
            } else {
                $original = $entity->get($property);
                // only set properties that have values that were changed
                // forms posting of null values are "" and integers are strings
                if ($value !== $original and ! ($value === '' and $original === null) and
                ! (is_numeric($original) and (string) $value === (string) $original)) {
                    $properties[$property] = $value;
                }
            }
        }

        return $this->setProperties($entity, $properties, $options);
    }

    /**
     * Sets the
     *
     * @param Entity $entity
     * @param array $properties
     * @param array $options
     * @return Entity
     */
    private function setProperties(Entity $entity, array $properties, array $options)  : Entity
    {
        if ($options['fields']) {
            $fields = (array) $options['fields'];
            foreach ($properties as $property => $value) {
                if (in_array($property, $fields)) {
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

            if ($hasPrimaryKey and $fields > 1 and (string) $collection[$index]->$primaryKey === (string) $record[$primaryKey]) {
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
