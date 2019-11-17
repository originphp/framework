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

use IteratorAggregate;
use Origin\Core\Exception\Exception;
use Origin\Core\Exception\InvalidArgumentException;
use Origin\Inflector\Inflector;

/**
 * Fluent Query Interface for Models
 */
class Query implements IteratorAggregate
{
    /**
     * @param \Origin\Model\Model $model
     */
    private $model;

    /**
     * Query array that will be passed to model method
     *
     * @var array
     */
    private $query = [
        'conditions' => null,
        'fields' => [],
        'joins' => [],
        'order' => null,
        'limit' => null,
        'group' => null,
        'page' => null,
        'offset' => null,
        'associated' => []
    ];

    private $joinFields = [];

    /**
     * Query starts with a model and where conditions
     *
     * @param \Origin\Model\Model $model
     * @param array $conditions
     * @param array $columns
     */
    public function __construct(Model $model, array $conditions = [], array $columns = [])
    {
        $this->model = $model;
        $this->query['conditions'] = $conditions;
        $this->query['fields'] = $columns;
    }

    /**
     * The columns that you want to select, if you are joining tables you will need to include
     * the table alias.
     *
     * @param array $columns ['id','user_id','users.id']
     * @return \Origin\Model\Query
     */
    public function select(array $columns) : Query
    {
        $this->query['fields'] = $columns;

        return $this;
    }

    /**
     * Orders the results
     *
     * @param string|array $order 'title DESC' or ['category','title ASC']
     * @return \Origin\Model\Query
     */
    public function order($order) : Query
    {
        $this->query['order'] = $order;

        return $this;
    }

    /**
     * Limits the number of rows that will be returned
     *
     * @param integer $limit
     * @return \Origin\Model\Query
     */
    public function limit(int $limit) : Query
    {
        $this->query['limit'] = $limit;

        return $this;
    }

    /**
     * Sets the number of records to skip
     *
     * @param integer $offset
     * @return \Origin\Model\Query
     */
    public function offset(int $offset) : Query
    {
        $this->query['offset'] = $offset;

        return $this;
    }

    /**
    * Sets the columns to group the query by
    *
    * @param array $columns
    * @return \Origin\Model\Query
    */
    public function group(array $columns) : Query
    {
        $this->query['group'] = $columns;

        return $this;
    }

    /**
    * Sets the having conditions for the query
    *
    * @param array $conditions e.g ['COUNT(customer_id) >' =>  5]
    * @return \Origin\Model\Query
    */
    public function having(array $conditions) : Query
    {
        $this->query['having'] = $conditions;

        return $this;
    }

    /**
     * Locks a record or records for update (SELECT FOR UPDATE)
     *
    * @return \Origin\Model\Query
     */
    public function lock() : Query
    {
        $this->query['lock'] = true;

        return $this;
    }

    /**
     * Joins tables to together, using either the name of a configurated association or a config array
     *
     * @param string|arrray $options Association name e.g. User, Comment or array of join options with the following keys
     *  - table: table name
     *  - alias: the alias for the table usually the lower case plural name of model
     *  - type: default: INNER. (INNER|LEFT|RIGHT|FULL)
     *  - conditions: array of conditions
    * @return \Origin\Model\Query
     */
    public function join($options) : Query
    {
        if (is_string($options)) {
            foreach (['belongsTo', 'hasOne'] as $association) {
                foreach ($this->model->association($association) as $alias => $assoc) {
                    if ($options === $alias) {
                        $options = $assoc;
                        $options['alias'] = Inflector::tableName($alias);
                        $options['table'] = $this->model->$alias->table();
                        $options['fields'] = $this->model->$alias->fields();
                        break;
                    }
                }
            }
            $this->query['fields'] = array_merge($this->query['fields'], (array) $options['fields']);
            if (is_string($options)) {
                throw new Exception('Unkown association ' . $$options);
            }
        }

        # Add default Options
        $options += [
            'table' => null,
            'alias' => null,
            'type'=> 'INNER',
            'fields' => [],
            'conditions' => []
        ];

        if (!empty($options['fields'])) {
            $this->joinFields = array_merge($this->joinFields, $options['fields']);
        }

        if (empty($options['alias']) or empty($options['table'])) {
            throw new InvalidArgumentException('Invalid Join options');
        }
   
        $this->query['joins'][] = $options;
        
        return $this;
    }

    /**
     * Sets the SELECT statement to return only distinct (different) values
     *
     * @return \Origin\Model\Query
     */
    public function distinct() : Query
    {
        $this->query['distinct'] = true;
        return $this;
    }

    /**
     * Gets the first record that is found
     *
     * @return \Origin\Model\Entity|null
     */
    public function first() :? Entity
    {
        return $this->model->first($this->getQuery());
    }

    /**
     * Gets all records that are found
     *
     * @return \Origin\Model\Collection|array
     */
    public function all()
    {
        return $this->model->all($this->getQuery());
    }

    /**
     * Counts the number of records that match
     *
     * @return mixed
     */
    public function count(string $columnName = 'all')
    {
        return $this->model->count($columnName === 'all' ? '*' : $columnName, $this->getQuery());
    }

    /**
     * Calculates the sum of a column
     *
     * @param string $columnName
     * @return integer|float|array|null
     */
    public function sum(string $columnName)
    {
        return $this->model->sum($columnName, $this->getQuery());
    }

    /**
     * Calculates the average for a column
     *
     * @param string $columnName

     * @return float|array|null
     */
    public function average(string $columnName)
    {
        return $this->model->average($columnName, $this->getQuery());
    }

    /**
     * Calculates the minimum for a column
     *
     * @param string $columnName
     * @return integer|array|null
     */
    public function minimum(string $columnName)
    {
        return $this->model->minimum($columnName, $this->getQuery());
    }

    /**
     * Calculates the maximum for a column
     *
     * @param string $columnName
     * @return integer|array|null
     */
    public function maximum(string $columnName)
    {
        return $this->model->maximum($columnName, $this->getQuery());
    }

    /**
     * Gets the query, if select was not called then it will get all the fields for this model and any joins
     * that were called.
     *
     * @return array
     */
    private function getQuery() : array
    {
        $query = $this->query;
      
        if (empty($query['fields'])) {
            $query['fields'] = array_merge($this->model->fields(), $this->joinFields);
        }
         
        return $query;
    }

    /**
     * Executes the query and returns the results, this is required by the IteratorAggregate interface.
     * This will automatically execute the query on for each loop on the query object.
     */
    public function getIterator()
    {
        return $this->all();
    }

    public function __debugInfo()
    {
        return $this->query;
    }
}
