<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Generate\Console\Task;

use Origin\Console\Task\Task;
use Origin\Model\ConnectionManager;
use Origin\Core\Inflector;

class GenerateTask extends Task
{
    protected $schema = [];

    public function introspectDatabase()
    {
        $connection = ConnectionManager::get('default');
        $tables = $connection->tables();
        foreach ($tables as $table) {
            $model = Inflector::classify($table);
            $this->schema[$model] = $connection->schema($table);
        }
    }

    public function validationRules()
    {
        $validationRules = [];
        foreach ($this->schema as $model => $schema) {
            $validationRules[$model] = [];
            foreach ($schema as $field => $meta) {
                if (isset($meta['key']) and $meta['key'] === 'primary') {
                    continue;
                }
                $validationRules[$model][$field] = [];
                if ($meta['null'] == false) {
                    $validationRules[$model][$field][] = 'notBlank';
                }
                if ($field === 'email') {
                    $validationRules[$model][$field][] = 'email';
                }
                if (in_array($field, ['url','website'])) {
                    $validationRules[$model][$field][] = 'url';
                }
                foreach (['date','datetime','time'] as $type) {
                    if ($meta['type'] === $type) {
                        $validationRules[$model][$field][] = $type;
                    }
                }
            }
        }
        return $validationRules;
    }

    /**
     * Builds an array map of vars,validation rules and associations
     *
     * @return array $map ['vars'=>$data,'associations'=>$associations,'validate'=>$validationRules,'schema'=>$schema];
     */
    public function build()
    {
        $models = array_keys($this->schema);
       
        $template = [
            'belongsTo' => [],
            'hasMany' => [],
            'hasAndBelongsToMany' => []
        ];

        $associations = ['ignore'=>[]];
        foreach ($models as $model) {
            $associations[$model] = $template;
            $associations = $this->findBelongsTo($model, $associations);
            $associations = $this->findHasAndBelongsToMany($model, $associations);
            $associations = $this->findHasMany($model, $associations); // callLast due to ignore
        }
        $validationRules = $this->validationRules();
     
        // Remove dynamic models jointable models
        foreach ($associations['ignore'] as $remove) {
            unset($associations[$remove]);
            unset($validationRules[$remove]);
            unset($this->schema[$remove]);
        }
        unset($associations['ignore']);
        
        /**
         *  [model] => BookmarksTag
         *  [controller] => BookmarksTags
         *  [singularName] => bookmarksTag
         *  [pluralName] => bookmarksTags
         *  [singularHuman] => Bookmarks Tag
         *  [pluralHuman] => Bookmarks Tags
         *  [singularHumanLower] => bookmarks tag
         *  [pluralHumanLower] => bookmarks tags
         */
        foreach ($models as $model) {
            $plural = Inflector::pluralize($model);
            $data[$model] = [
                'model' => $model,
                'controller' => $plural ,
                'singularName' => Inflector::variable($model), // for vars
                'pluralName' => Inflector::variable($plural), // for vars
                'singularHuman' => Inflector::humanize(Inflector::underscore($model)),
                'pluralHuman' =>   Inflector::humanize(Inflector::underscore($plural)),
                'singularHumanLower' => strtolower(Inflector::humanize(Inflector::underscore($model))),
                'pluralHumanLower' =>   strtolower(Inflector::humanize(Inflector::underscore($plural))),
            ];
        }

        return ['vars'=>$data,'associations'=>$associations,'validate'=>$validationRules,'schema'=>$this->schema];
    }

    /**
     * Finds the belongsTo
     *
     * @param string $model
     * @param array $associations
     * @return void
     */
    public function findBelongsTo(string $model, array $associations=[])
    {
        $fields = $this->schema[$model];
        foreach ($fields as $field => $schema) {
            if (substr($field, -3) === '_id' and empty($schema['key'])) {
                $associatedModel = Inflector::camelize(substr($field, 0, -3));
                $associations[$model]['belongsTo'][] = $associatedModel;
            }
        }
        return $associations;
    }
    /**
     * Finds the hasMany relations (these can also be hasOne)
     *
     * @param string $model
     * @param array $associations
     * @return void
     */
    public function findHasMany(string $model, array $associations=[])
    {
        $models = array_keys($this->schema);
        foreach ($models as $otherModel) {
            if ($otherModel === $model or in_array($otherModel, $associations['ignore'])) {
                continue;
            }
            $schema = $this->schema[$otherModel];
            $foreignKey = Inflector::underscore($model) . '_id';
       
            if (isset($schema[$foreignKey]) and empty($schema['key'])) {
                $associations[$model]['hasMany'][] = $otherModel;
            }
        }
        return $associations;
    }
    /**
     * Finds the hasAndToBelongsToMany using table names. Table name needs to be alphabetical order if not
     * it will be ignored.
     */
    public function findHasAndBelongsToMany(string $model, array $associations=[])
    {
        $models = array_keys($this->schema);
        foreach ($models as $otherModel) {
            $array = [Inflector::pluralize($model),Inflector::pluralize(($otherModel))];
            sort($array);
            $hasAndBelongsToMany = Inflector::singularize(implode('', $array));
            if (isset($this->schema[$hasAndBelongsToMany])) {
                $associations[$model]['hasAndBelongsToMany'][] = $otherModel;
                if (in_array($hasAndBelongsToMany, $associations['ignore']) === false) {
                    $associations['ignore'][] = $hasAndBelongsToMany;
                }
            }
        }
        return $associations;
    }

    /**
     * Gets the primary key for a model
     *
     * @param string $model
     * @return string|null field
     */
    public function primaryKey(string $model)
    {
        if (isset($this->schema[$model])) {
            $schema = $this->schema[$model];
            foreach ($schema as $field => $meta) {
                if (isset($meta['key']) and $meta['key'] === 'primary') {
                    return $field;
                }
            }
        }
        return null;
    }
}
