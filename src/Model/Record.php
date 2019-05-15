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

/**
 * New Model class to use different format of aliases to work across more dbs
 */
class Record
{
     /**
     * The name for this model, this generated automatically.
     *
     * @var string
     */
    public $name = null;

    /**
     * The alias name for this model, again this generated automatically
     *
     * @var string
     */
    public $alias = null;

    /**
     * This is the Database configuration to used by model.
     *
     * @var string
     */
    public $datasource = 'default';

    /**
     * This is the table name for the model this will be generated automatically
     * if you want to overide this then change this.
     *
     * @var string
     */
    public $table = null;

    /**
     * Each table should have a primary key and it should be id, because
     * 1. associations wont work without you telling which fields to use
     * 2. not really fully tested using something else, but it should work ;).
     * 3. it might get confusing later
     * @var string
     */
    public $primaryKey = null;

    /**
     * This is the main field on the model, for a contact, it would be contact_name. Things
     * like name, title etc.
     *
     * @var string
     */
    public $displayField = null;

    /**
     * Default order to used when finding.
     *
     * $order = 'Article.title ASC';
     * $order = ['Article.title','Article.created ASC']
     *
     * @var string|array
     */
    public $order = null;

    /**
     * The ID of the last record created, updated, or deleted. When saving
     * associated data, it would be of the main record not the associated.
     *
     * @var mixed
     */
    public $id = null;

    /**
     * belongsTo keys className, foreignKey, conditions, fields, order).
     */
    protected $belongsTo = [];

    /**
     * hasMany keys className, foreignKey, conditions, fields, order, dependent).
     */
    protected $hasMany = [];
    /**
     * hasOne keys className, foreignKey, conditions, fields, order, dependent).
     */
    protected $hasOne = [];

    /**
     * hasAndBelongsToMany Keys
     * className,joinTable,foreignKey,associationForeignKey,conditions,fields,order,
     * dependent, limit,with,unique.
     *
     * @var array
     */
    protected $hasAndBelongsToMany = [];

    /**
     * Behavior registry object
     *
     * @var \Origin\Model\Behavior\BehaviorRegistry
     */
    protected $behaviorRegistry = null;
    /**
     * Constructor
     *
     * @param array $config (name,alias,datasource,table)
     */
    public function __construct(array $config = [])
    {
        $defaults = [
            'name' => $this->name,
            'alias' => $this->alias,
            'datasource' => $this->datasource,
            'table' => $this->table,
        ];

        $config = array_merge($defaults, $config);

        extract($config);

        if (is_null($name)) {
            list($namespace, $name) = namespaceSplit(get_class($this));
        }
        $this->name = $name;

        if (is_null($alias)) {
            $alias = $this->name;
        }
        $this->alias = $alias;

        if (is_null($table)) {
            $table = Inflector::tableize($this->name);
        }
        $this->table = $table;

        if ($this->primaryKey === null) {
            $this->primaryKey = 'id';
        }

        $this->datasource = $datasource;

        // Remove so we can autodetect when needed
        if (!$this->displayField) {
            unset($this->displayField);
        }

        $this->behaviorRegistry = new BehaviorRegistry($this);

        $this->initialize($config);
    }
}
