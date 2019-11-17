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

use ArrayObject;
use Origin\Inflector\Inflector;

/**
 * This handles the model finds
 */
class Finder
{
    use EntityLocatorTrait;
    protected $model = null;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Reads the datasource using query array and returns the result set.
     *
     * @param string $type
     * @param ArrayObject  $query (conditions,joins,fields,order,limit etc)
     * @return mixed
     */
    public function find(ArrayObject $query, string $type = 'model')
    {
        $query = (array) $query;
        
        $connection = $this->model->connection();
        $connection->select($this->model->table(), $query + ['alias' => Inflector::tableName($this->model->alias())]);

        if ($type === 'list') {
            return $connection->fetchList();
        }
        // used by count
        if ($type === 'assoc') {
            return $connection->fetchAll('assoc');
        }

        $results = $connection->fetchAll('num'); // change to num and enableMapResults

        if ($results) {
            $results = $connection->mapNumericResults($results, $query['fields']); // use with Num instead of model
           
            # If foreignKeys are missing data then objects wont be put together
            # to prevent empty records, but this means valid records wont show as well.
            $results = $this->prepareResults($results);
            $results = $this->loadAssociatedBelongsTo($query, $results);
            $results = $this->loadAssociatedHasOne($query, $results);
            $results = $this->loadAssociatedHasMany($query, $results);
            $results = $this->loadAssociatedHasAndBelongsToMany($query, $results);
            $results = new Collection($results, ['name' => $this->model->alias()]);
        }

        unset($sql, $connection);

        return $results;
    }

    /**
     * Takes results from the datasource and converts into an entity. Different from model::new which
     * takes an array which can include hasMany and converts.
     *
     * @internal using marshaller
     *
     * @param array $results results from datasource
     * @return array
     */
    protected function prepareResults(array $results)
    {
        $buffer = [];

        $alias = Inflector::tableName($this->model->alias());

        $belongsTo = $this->model->association('belongsTo');
        $hasOne = $this->model->association('hasOne');
       
        $entityClass = $this->entityClass($this->model);

        foreach ($results as $record) {
            $thisData = (isset($record[$alias]) ? $record[$alias] : []); // Work with group and no fields from db
            
            $entity = new $entityClass($thisData, ['name' => $this->model->alias(), 'exists' => true, 'markClean' => true]);
            unset($record[$alias]);

            foreach ($record as $tableAlias => $data) {
                if (is_string($tableAlias)) {
                    $model = Inflector::className($tableAlias);
                    $associated = Inflector::camelCase($model);
                  
                    /**
                     * Remove empty records. If the foreignKey is not present then the associated
                     * data will not be present. This is correct.
                     */
                    $foreignKey = null;
                    if (isset($belongsTo[$model])) {
                        $foreignKey = $belongsTo[$model]['foreignKey'];
                        $primaryKey = $this->model->$model->primaryKey();
                        if (empty($entity->$foreignKey) or empty($data[$primaryKey])) {
                            continue;
                        }
                    } elseif (isset($hasOne[$model])) {
                        $foreignKey = $hasOne[$model]['foreignKey'];
                        $primaryKey = $this->model->primaryKey();
                        if (empty($entity->$primaryKey) or empty($data[$foreignKey])) {
                            continue;
                        }
                    }
        
                    $associatedEntityClass = $this->entityClass($this->model->$model);
                    $entity->$associated = new $associatedEntityClass($data, ['name' => $associated, 'exists' => true, 'markClean' => true]);
                } else {
                    /**
                     * Any data is here is not matched to model, e.g. group by and non existant fields
                     * add them to model so we can put them in entity nicely. This seems to be cleanest solution
                     * the resulting entity might not contain any real data from the entity.
                     */
                    foreach ($data as $k => $v) {
                        $entity->$k = $v;
                    }
                }
            }

            $buffer[] = $entity;
        }
        unset($belongsTo,$hasOne,$thisData,$entity);

        return $buffer;
    }

    /**
     * Recursively load associated belongsTo
     *
     * @param array $query
     * @param array $results
     * @return array
     */
    protected function loadAssociatedBelongsTo(array $query, array $results) : array
    {
        $belongsTo = $this->model->association('belongsTo');
        foreach ($query['associated'] as $model => $config) {
            if (isset($config['associated']) and isset($belongsTo[$model])) {
                $foreignKey = $belongsTo[$model]['foreignKey'];
                $property = lcfirst($model);
                foreach ($results as &$result) {
                    if (isset($result->$foreignKey)) {
                        $config['conditions'] = [$this->model->$model->primaryKey() => $result->$foreignKey];
                        $result->$property = $this->model->$model->find('first', $config);
                    }
                }
            }
        }
        unset($belongsTo);

        return $results;
    }

    /**
     * Recursively load associated hasOne
     *
     * @param array $query
     * @param array $results
     * @return array
     */
    public function loadAssociatedHasOne(array $query, array  $results) : array
    {
        $hasOne = $this->model->association('hasOne');
        foreach ($query['associated'] as $model => $config) {
            if (isset($config['associated']) and isset($hasOne[$model])) {
                $foreignKey = $hasOne[$model]['foreignKey']; // author_id
                $property = lcfirst($model);
                $primaryKey = $this->model->$model->primaryKey();
                $modelTableAlias = Inflector::tableName($model);
                foreach ($results as &$result) {
                    if (isset($result->{$this->model->primaryKey()})) { // Author id
                        $config['conditions'] = $hasOne[$model]['conditions'];
                        $config['conditions'] = ["{$modelTableAlias}.{$foreignKey}" => $result->{$this->model->primaryKey()}];
                        $result->$property = $this->model->$model->find('first', $config);
                    }
                }
            }
        }
        unset($hasOne);

        return $results;
    }

    /**
     * Loads the associated hasMany records
     *
     * @param array $query
     * @param array $results
     * @return array
     */
    protected function loadAssociatedHasMany(array $query, array $results) : array
    {
        $hasMany = $this->model->association('hasMany');
        foreach ($hasMany as $alias => $config) {
            if (isset($query['associated'][$alias])) {
                $config = array_merge($config, $query['associated'][$alias]);

                if (empty($config['fields'])) {
                    $config['fields'] = $this->model->{$alias}->fields();
                }

                foreach ($results as $index => &$result) {
                    if (isset($result->{$this->model->primaryKey()})) {
                        $tableAlias = Inflector::tableName($alias);
                        $config['conditions']["{$tableAlias}.{$config['foreignKey']}"] = $result->{$this->model->primaryKey()};
                        $models = Inflector::plural(Inflector::camelCase($alias));
                        $result->$models = $this->model->$alias->find('all', $config);
                    }
                }
            }
        }
        unset($hasMany);

        return $results;
    }
    /**
     * Loads the hasAndBelongsToMany data
     *
     * @param array $query
     * @param array $results
     * @return array
     */
    protected function loadAssociatedHasAndBelongsToMany(array $query, array $results) : array
    {
        $hasAndBelongsToMany = $this->model->association('hasAndBelongsToMany');
        foreach ($hasAndBelongsToMany as $alias => $config) {
            if (isset($query['associated'][$alias])) {
                $config = array_merge($config, $query['associated'][$alias]);

                $config['joins'][0] = [
                    'table' => $config['joinTable'],
                    'alias' => Inflector::tableName($config['with']),
                    'type' => 'INNER',
                    'conditions' => $config['conditions'],
                ];
                $config['conditions'] = [];
                if (empty($config['fields'])) {
                    $config['fields'] = array_merge($this->model->$alias->fields(), $this->model->{$config['with']}->fields());
                }

                foreach ($results as $index => &$result) {
                    if (isset($result->{$this->model->primaryKey()})) {
                        $withAlias = Inflector::tableName($config['with']);
                        $config['joins'][0]['conditions']["{$withAlias}.{$config['foreignKey']}"] = $result->{$this->model->primaryKey()};
                    }

                    $models = Inflector::plural(Inflector::camelCase($alias));
                    $result->$models = $this->model->$alias->find('all', $config);
                }
            }
        }
        unset($hasAndBelongsToMany);

        return $results;
    }
}
