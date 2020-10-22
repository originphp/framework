<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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
use Origin\Inflector\Inflector;
use Origin\Core\Exception\InvalidArgumentException;

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

    /**
     * Associated data to get
     *
     * @var array
     */
    private $associated = [];

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
    public function select(array $columns): Query
    {
        $this->query['fields'] = $columns;

        return $this;
    }

    /**
     * Sets the SELECT statement to return only distinct (different) values
     *
     * @return \Origin\Model\Query
     */
    public function distinct(): Query
    {
        $this->query['distinct'] = true;

        return $this;
    }
    
    /**
     * The conditions to use for this query
     *
     * @param array $conditions
     * @return \Origin\Model\Query
     */
    public function where(array $conditions): Query
    {
        $this->query['conditions'] = $conditions;

        return $this;
    }
    /**
     * Orders the results
     *
     * @param string|array $order 'title DESC' , ['title' => 'DESC'] or ['category','title ASC']
     * @return \Origin\Model\Query
     */
    public function order($order): Query
    {
        $this->query['order'] = (array) $order;

        return $this;
    }

    /**
     * Limits the number of rows that will be returned
     *
     * @param integer $limit
     * @return \Origin\Model\Query
     */
    public function limit(int $limit): Query
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
    public function offset(int $offset): Query
    {
        $this->query['offset'] = $offset;

        return $this;
    }

    /**
    * Sets the columns to group the query by
    *
    * @param string|array $columns
    * @return \Origin\Model\Query
    */
    public function group($columns): Query
    {
        $this->query['group'] = (array) $columns;

        return $this;
    }

    /**
    * Sets the HAVING clause for the query, used as conditions for group.
    *
    * @param array $conditions e.g ['COUNT(customer_id) >' =>  5]
    * @return \Origin\Model\Query
    */
    public function having(array $conditions): Query
    {
        $this->query['having'] = $conditions;

        return $this;
    }

    /**
     * Locks a record or records for update (SELECT FOR UPDATE)
     *
    * @return \Origin\Model\Query
     */
    public function lock(): Query
    {
        $this->query['lock'] = true;

        return $this;
    }

    /**
     * Joins tables to together. For autodetection assumes that you follow conventions, table name is lower underscored plural
     * and primary key fields are ID.
     *
     * @param string|arrray $options table name or array of join options with the following keys
     *  - table: table name
     *  - alias: the alias for the table usually the lower case plural name of model
     *  - type: default: INNER. (INNER|LEFT|RIGHT|FULL)
     *  - conditions: array of conditions. e.g ['bookmarks.user_id = users.id']
    * @return \Origin\Model\Query
     */
    public function join($options): Query
    {
        if (is_string($options)) {
            $options = ['table' => $options];
        }

        # Add default Options
        $options += [
            'table' => null,
            'alias' => null,
            'type' => 'INNER',
            'fields' => [],
            'conditions' => []
        ];

        if (empty($options['table'])) {
            throw new InvalidArgumentException('No table name provided');
        }

        if (empty($options['alias'])) {
            $options['alias'] = $options['table'];
        }

        if (empty($conditions)) {
            $tableAlias = Inflector::tableName($this->model->alias());
            $foreignKey = Inflector::singular($options['table']) . '_id';
            $options['conditions'] = [
                "{$tableAlias}.{$foreignKey} = {$options['alias']}.id"
            ];
        }
   
        $this->query['joins'][] = $options;
        
        return $this;
    }

    /**
    * Gets results with associated data.
    *
    * @param string|array $assocation User or ['User'=>['Comment']]
    * @return \Origin\Model\Query
    */
    public function with($assocation): Query
    {
        $this->associated = (array) $assocation;

        return $this;
    }

    /**
     * Gets the first record that is found
     *
     * @return \Origin\Model\Entity|null
     */
    public function first(): ? Entity
    {
        return $this->model->first($this->toArray());
    }

    /**
     * Gets all records that are found
     *
     * @return \Origin\Model\Collection|array
     */
    public function all()
    {
        return $this->model->all($this->toArray());
    }

    /**
     * Counts the number of records that match
     *
     * @return mixed
     */
    public function count(string $columnName = 'all')
    {
        return $this->model->count($columnName === 'all' ? '*' : $columnName, $this->toArray());
    }

    /**
     * Calculates the sum of a column
     *
     * @param string $columnName
     * @return integer|float|array|null
     */
    public function sum(string $columnName)
    {
        return $this->model->sum($columnName, $this->toArray());
    }

    /**
     * Calculates the average for a column
     *
     * @param string $columnName

     * @return float|array|null
     */
    public function average(string $columnName)
    {
        return $this->model->average($columnName, $this->toArray());
    }

    /**
     * Calculates the minimum for a column
     *
     * @param string $columnName
     * @return integer|array|null
     */
    public function minimum(string $columnName)
    {
        return $this->model->minimum($columnName, $this->toArray());
    }

    /**
     * Calculates the maximum for a column
     *
     * @param string $columnName
     * @return integer|array|null
     */
    public function maximum(string $columnName)
    {
        return $this->model->maximum($columnName, $this->toArray());
    }

    /**
     * Chunks query results in batches and passes them to a callback
     *
     * @param integer $size
     * @param callable $callback
     * @return boolean
     */
    public function chunk(int $size, callable $callback) : bool
    {
        $page = 1;

        $conditions = $this->toArray();

        $conditions['offset'] = null;
        $conditions['limit'] = $size;
        
        do {
            $conditions['page'] = $page;
          
            $collection = $this->model->all($conditions);

            $found = $collection->count();

            if ($found === 0) {
                break;
            }

            if ($callback($collection, $page) === false) {
                return false;
            }
            unset($collection);

            $page ++;
        } while ($found === $size);

        return true;
    }

    /**
     * Executes a callback for each item through chunking to make it more memory efficient to
     * work with large datasets.
     *
     * @param callable $callback
     * @param integer $chunkSize
     * @return bool
     */
    public function each(callable $callback, int $chunkSize = 1000) : bool
    {
        return $this->chunk($chunkSize, function ($collection) use ($callback) {
            foreach ($collection as $index => $entity) {
                if ($callback($entity, $index) === false) {
                    return false;
                }
            }
        });
    }

    /**
     * Gets the query as an array ready for use by the model
     *
     * @return array
     */
    public function toArray(): array
    {
        if ($this->associated) {
            $this->processsAssociated();
        }
        $query = $this->query;

        if (empty($query['fields'])) {
            $query['fields'] = $this->model->fields();
        }

        return $query;
    }

    /**
     * Process associated before query is run to find out if select was used.
     *
     * @return \Origin\Model\Query
     */
    private function processsAssociated(): Query
    {
        $assocation = $this->normalizeAssociated($this->associated);
        $this->query['associated'] = $assocation['associated'];

        return $this;
    }
        
    protected function normalizeAssociated(array $value)
    {
        $hasSelected = ! empty($this->query['fields']);

        $out = [] ;
        foreach ($value as $key => $value) {
            // ['Article'=>'Author'] is incorrect should be ['Article'=>['Author']]
            if (is_string($key) && is_string($value)) {
                $value = (array) $value;
            }
            if (is_array($value)) {
                $out[$key] = $this->normalizeAssociated($value);
            } else {
                $out[$value] = ['fields' => $hasSelected ? [] : null];
            }
        }

        return ['associated' => $out,'fields' => $hasSelected ? [] : null];
    }

    /**
     * Gets the SQL statement for the query
     *
     * @return string
     */
    public function sql(): string
    {
        $builder = new QueryBuilder($this->model->table(), Inflector::tableName($this->model->alias()));

        return $builder->selectStatement($this->toArray());
    }

    /**
     * Executes the query and returns the results, this is required by the IteratorAggregate interface.
     * This will automatically execute the query on for each loop on the query object.
     */
    public function getIterator()
    {
        return $this->all();
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return $this->toArray();
    }
}
