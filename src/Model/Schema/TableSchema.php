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

namespace Origin\Model\Schema;

/**
 * This design is based upon CakePHP, which I have to say at first glance it did not seem that impressive as I
 * probably did not understand how simple yet powerful it was.
 *
 * I need a better way to create the table schema, in fixtures and when dumping.
 */

use Origin\Model\Connection;
use Origin\Core\Exception\Exception;

class TableSchema
{
    /**
     * Holds the table name
     *
     * @var string
     */
    protected $table = null;

    /**
     * Array with the column meta
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Indexes
     *
     * @var array
     */
    protected $indexes = [];

    /**
     * Constraints
     * Example
     *  [
     *      'primary' => ['type' => 'primary','column=>'id']]
     *      'bookmarks_ibfk_1' => ['type' => 'foreign', 'column' => 'user_id', 'references' => ['users', 'id']]
     *  ]
     *
     * @var array
     */
    protected $constraints = [];

    /**
     * Holds database specific create table options
     * e.g  ['engine'=>'InnoDB','charset=>'latin1', collate'=>'utf8_unicode_ci']
     *
     * @var array
     */
    protected $options = [];

    public function __construct(string $table, array $columns = [], array $options = [])
    {
        $this->table = $table;
       
        foreach ($columns as $name => $definition) {
            $this->addColumn($name, $definition);
        }
        
        if (isset($options['constraints'])) {
            foreach ($options['constraints'] as $name => $definition) {
                $this->addConstraint($name, $definition);
            }
        }
        if (isset($options['indexes'])) {
            foreach ($options['indexes'] as $name => $definition) {
                $this->addIndex($name, $definition);
            }
        }
        if (isset($options['options'])) {
            $this->options($options['options']);
        }
    }

    /**
     * Gets the statements
     *
     * @param Datasource $datasource
     * @return array
     */
    public function toSql(Connection $datasource) : array
    {
        return $datasource->adapter()->createTableSql($this->table, $this->columns, [
            'constraints' => $this->constraints,'indexes' => $this->indexes,'options' => $this->options,
        ]);
    }

    /**
     * Adds a column to the table
     *
     * @param string $name
     * @param string|array $attributes
     * @return \Origin\Model\Schema\TableSchema
     */
    public function addColumn(string $name, $attributes) : TableSchema
    {
        if (is_string($attributes)) {
            $attributes = ['type' => $attributes];
        }
        $attributes += ['name' => $name];

        $this->columns[$name] = $attributes;

        return $this;
    }

    /**
     * Adds an index
     * @internal pgsql requires table
     *
     * @param string $name
     * @param string|array $attributes
     * @return \Origin\Model\Schema\TableSchema
     */
    public function addIndex(string $name, $attributes) : TableSchema
    {
        if (is_string($attributes)) {
            $attributes = ['column' => [$attributes]];
        }
        $attributes += ['name' => $name, 'table' => $this->table,'type' => 'index'];
        if (empty($attributes['column'])) {
            throw new Exception(sprintf('Index %s is missing column information', $name));
        }
        $this->indexes[$name] = $attributes;

        return $this;
    }

    /**
     * Adds a constraint
     *
     * @param string $name
     * @param array $attributes
     * @return \Origin\Model\Schema\TableSchema
     */
    public function addConstraint(string $name, array $attributes) : TableSchema
    {
        $map = ['cascade' => 'CASCADE','restrict' => 'RESTRICT','setNull' => 'SET NULL','setDefault' => 'SET DEFAULT','noAction' => 'NO ACTION'];

        $attributes += ['name' => $name,'type' => null,'column' => null];
        if (empty($attributes['type']) or ! in_array($attributes['type'], ['primary','unique','foreign'])) {
            throw new Exception(sprintf('Invalid or missing constraint type for %s', $name));
        }
        
        if (empty($attributes['column'])) {
            throw new Exception(sprintf('Constraint %s is missing columns', $name));
        }

        if ($attributes['type'] === 'foreign') {
            $attributes += ['table' => $this->table];

            if (empty($attributes['references'])) {
                throw new Exception(sprintf('Constraint %s is missing references', $name));
            }
            if (! is_array($attributes['references']) or count($attributes['references']) !== 2) {
                throw new Exception(sprintf('Constraint %s references should be an array with table and column name', $name));
            }
        
            /**
             * Default behavior restrict
             * @see https://dev.mysql.com/doc/refman/5.6/en/create-table-foreign-keys.html
             */
            if (isset($attributes['update']) or isset($attributes['delete'])) {
                $attributes += ['update' => 'restrict','delete' => 'restrict'];
            }
            if (isset($attributes['update']) and ! isset($map[$attributes['update']])) {
                throw new Exception(sprintf('Update action %s is invalid must be %s', $attributes['update'], implode(', ', array_keys($map))));
            }
            if (isset($attributes['delete']) and ! isset($map[$attributes['delete']])) {
                throw new Exception(sprintf('Delete action %s is invalid must be %s', $attributes['delete'], implode(', ', array_keys($map))));
            }
        }
        $this->constraints[$name] = $attributes;

        return $this;
    }

    /**
     * Sets or gets the table options (MySQL only)
     *
     * e.g ['engine'=>'InnoDB','collate'=>'utf8_unicode_ci']
     *
     * @param array $options
     * @return array
     */
    public function options(array $options = null) : array
    {
        if ($options === null) {
            return $this->options;
        }

        return $this->options = $options;
    }

    /**
     * Returns columns or information about column
     *
     * @param string $name
     * @return array|null
     */
    public function columns(string $name = null): ? array
    {
        if ($name === null) {
            return $this->columns;
        }

        return $this->columns[$name] ?? null;
    }

    /**
     * Returns constraints or information about constraint
     *
     * @param string $name
     * @return array|null
     */
    public function constraints(string $name = null): ? array
    {
        if ($name === null) {
            return $this->constraints;
        }

        return $this->constraints[$name] ?? null;
    }

    /**
     * Returns indexes or information about index
     *
     * @param string $name
     * @return array|null
     */
    public function indexes(string $name = null): ? array
    {
        if ($name === null) {
            return $this->indexes;
        }

        return $this->indexes[$name] ?? null;
    }

    /**
     * Gets the primary Key
     *
     * @return string|array|null
     */
    public function primaryKey()
    {
        $primaryKey = null;
        foreach ($this->constraints as $name => $attributes) {
            if ($attributes['type'] === 'primary') {
                $primaryKey = $attributes['column'];
                break;
            }
        }

        return $primaryKey;
    }
}
