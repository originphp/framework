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
/*
 * In computer science, marshalling (similar to serialization) is the process
 * of transforming the memory representation of an object to a data format suitable
 * for storage or transmission. It is typically used when data must be moved
 * between different parts of a computer program or from one program to another.
 * Source: wikipedia.
 */
/*
 * 23.12.18 Moved toArray to entity and moved model newEntity/Patch entity here. So that
 * we can access model information when transforming entities. E.g date time fields etc.
 *
 * Marshaller is used to process request data as it converts localized fields such as dates,
 * decimals etc.
 */
use Origin\Utils\Date;
use Origin\Utils\Number;

class Marshaller
{
    protected $model = null;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Creates a map for entity fields.
     *
     * @return array
     */
    protected function buildAssociationMap()
    {
        $map = [];
        $model = $this->model;
        foreach (array_merge($model->hasOne, $model->belongsTo) as $alias => $config) {
            $map[lcfirst($alias)] = $alias;
        }
        foreach (array_merge($model->hasMany, $model->hasAndBelongsToMany) as  $alias => $config) {
            $key = Inflector::pluralize(lcfirst($alias));
            $map[$key] = $alias;
        }

        return $map;
    }

    /**
     * Takes data from user input.
     *
     * @todo I am thinking validation should take place in Marshaller to deal
     * with date/time fields
     *
     * @param array $data
     */
    protected function parseData(array $data)
    {
        $schema = $this->model->schema();

        foreach ($data as $field => $value) {
            if (!is_string($field) or !isset($schema[$field])) {
                continue;
            }
            if ($value === '' or $value === null) {
                continue;
            }
            $column = $schema[$field];

            switch ($column['type']) {
                case 'datetime':
                    /*
                     * If the field stays an array then its because its invalid
                     */
                    if (is_array($value)) {
                        if (empty($value['date']) and empty($value['time'])) {
                            $data[$field] = null;
                        } elseif (!empty($value['date']) and !empty($value['time'])) {
                            $date = Date::parseDate($value['date']);
                            $time = Date::parseTime($value['time']);

                            if ($date and $time) {
                                $data[$field] = $date.' '.$time;
                            }
                        }
                    } elseif (is_string($value)) {
                        $data[$field] = Date::parseDatetime($value);
                    }

                break;
                case 'date':
                    $date = Date::parseDate($value);
                    if ($date) {
                        $data[$field] = $date;
                    }
                break;
                case 'time':
                    $time = Date::parseTime($value);
                    if ($time) {
                        $data[$field] = $time;
                    }
                break;
                case 'decimal':
                case 'float':
                case 'integer':
                   $data[$field] = Number::parse($value);
                break;
            break;
            }
        }

        return $data;
    }

    /**
     * Creates an entity object from data.
     *
     * @param array $array
     * @param array $options name
     *
     * @return Entity
     */
    public function newEntity(array $array, array $options = [])
    {
        $associations = $this->buildAssociationMap();

        $data = [];
        foreach ($array as $key => $value) {
            if (is_string($key) and is_array($value)) {
                if (isset($associations[$key])) {
                    $single = $many = [];
                    $model = $associations[$key];
                    foreach ($value as $k => $v) {
                        if (is_int($k)) {
                            $many[] = $this->newEntity($v, ['name' => $model]);   // hasMany/hasAndbelongsToMany
                        } else {
                            $single[$k] = $v; // belongsTo/hasOne
                        }
                    }
                    if ($single) {
                        $data[$key] = new Entity($single, ['name' => $model]);
                    } else {
                        $data[$key] = $many;
                    }
                    continue;
                }
                $data[$key] = $value; // If there is no assocation put the data back.
            } elseif (is_string($key)) {
                $data[$key] = $value;
            }
        }
        $data = $this->parseData($data);
        /*
         * Here we join datetime field arrays
         */
        /* foreach ($dateFields as $field) {
             if (isset($data[$field]) and is_array($data[$field])) {
                 if (!empty($data[$field]['date']) and !empty($data[$field]['time'])) {
                     $data[$field] = $data[$field]['date'].' '.$data[$field]['time'].':00';
                 }
             }
         }*/

        return new Entity($data, $options);
    }

    /**
     * Patches an entity. Still working on this. Currently favor new entity each time, as we are resaving
     * all data. If keep track of dirty data, then primary keys need to be set, each model
     * needs to be loaded.
     *
     * @param Entity $entity
     * @param array  $data
     *
     * @return Entity
     */
    public function patchEntity(Entity $entity, array $data)
    {
        foreach ($data as $key => $value) {
            if (is_string($key) and is_array($value)) {
                foreach ($value as $k => $v) {
                    $subEntity = $entity->{$key};
                    if (is_int($k)) { // hasMany
                        $subEntity[$k] = $this->patchEntity($subEntity[$k], $v);
                    } elseif ($subEntity instanceof Entity) {
                        $subEntity->set($k, $v);
                    }
                }
            } elseif (is_string($key)) {
                $entity->set($key, $value);
            }
        }

        return $entity;
    }
}
