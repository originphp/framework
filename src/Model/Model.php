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

/**
 * Multiple entities can be wrapped in array of Model\Collection, must work both ways so
 * that entities with associated data can also be created manually.
 *
 */

use Origin\Core\Inflector;
use Origin\Exception\Exception;
use Origin\Exception\NotFoundException;
use Origin\Model\Behavior\BehaviorRegistry;
use Origin\Exception\InvalidArgumentException;
use Origin\Model\Exception\MissingModelException;

class Model
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
     * List of Associations
     * @var array
     */
    protected $associations = [
        'belongsTo', 'hasMany', 'hasOne', 'hasAndBelongsToMany',
    ];

    /**
     * The column data describing the table.
     *
     * @var array
     */
    protected $schema = null;

    /**
     * Marshaller
     *
     * @var \Origin\Model\Marshaller
     */
    protected $marshaller = null;

    /**
     * Behavior registry object
     *
     * @var \Origin\Model\Behavior\BehaviorRegistry
     */
    protected $behaviorRegistry = null;

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
        if (! $this->displayField) {
            unset($this->displayField);
        }

        $this->behaviorRegistry = new BehaviorRegistry($this);

        $this->initialize($config);
    }

    /**
     * Magic function for lazyLoading.
     * It will throw a missing model exception if the association is defined but the
     * model is not found. If there is no assocation then isset does not throw an exception.
     * hasAndBelongsToMany: Dynamically create hasAndBelongsToMany model if this does not exist.
     * If hasAndBelongsToMany table only has 2 or less fields, then set the primary
     * key as the foreignKey. Assume more than that id is set.
     */
    public function __isset($name)
    {
        $className = null;
        $habtmModel = false;

        $association = $this->findAssociation($name);
        if ($association) {
            $className = $association['className'];
        } else {
            foreach ($this->hasAndBelongsToMany as $alias => $config) {
                if (isset($config['with']) and $config['with'] === $name) {
                    $className = $config['with'];
                    $habtmModel = true;
                    break;
                }
            }
        }

        if ($className === null and $habtmModel === false) {
            return false;
        }

        $object = ModelRegistry::get($name, ['className' => $className, 'alias' => $name]);
        if ($object === false and $habtmModel === false) {
            throw new MissingModelException($name);
        }

        if ($habtmModel) {
            $object = new Model([
                'name' => $className,
                'table' => $this->hasAndBelongsToMany[$alias]['joinTable'],
                'datasource' => $this->datasource,
            ]);

            if (count($object->fields()) === 2) {
                $object->primaryKey = $this->hasAndBelongsToMany[$alias]['foreignKey'];
            }

            ModelRegistry::set($name, $object);
        }

        $this->{$name} = $object;

        return true;
    }

    /**
     * Call the model lazyLoad and also detect displayField when called to
     * not have to call schema before every operation or on creation of model.
     *
     * @param string $name
     */
    public function __get(string $name)
    {
        if ($name === 'displayField') {
            $this->displayField = $this->detectDisplayField();

            return $this->displayField;
        }
        if (isset($this->{$name})) {
            return $this->{$name};
        }

        return null;
    }

    /**
     * Magic method it call the first loaded behavior method if its available
     *
     * @param string $method
     * @param array $arguments
     * @return void
     */
    public function __call(string $method, array $arguments)
    {
        //
        foreach ($this->behaviorRegistry()->enabled() as $Behavior) {
            if (method_exists($this->behaviorRegistry()->{$Behavior}, $method)) {
                return call_user_func_array(
                    [$this->behaviorRegistry()->{$Behavior}, $method],
                    $arguments
                );
            }
        }
        throw new Exception('Call to undefined method '  . get_class($this) . '\\' .  $method . '()');
    }

    /**
     * Gets the display field if set or tries to detect it.
     *
     * @return string
     */
    protected function detectDisplayField(): string
    {
        $fields = array_keys($this->schema()['columns']);

        $needles = [
            Inflector::underscore($this->name) . '_name',
            'name',
            'title',
            $this->primaryKey,
        ];

        foreach ($needles as $needle) {
            if (in_array($needle, $fields)) {
                return $needle;
            }
        }

        throw new Exception('Error getting display field, Set displayField or make sure at least a primary key like id is set.');
    }

    /**
     * Gets the association relationship from outside the model.
     *
     * @param string $name hasOne|belongsTo etc
     * @return array
     */
    public function association(string $name): array
    {
        if (in_array($name, $this->associations())) {
            return $this->{$name};
        }
        throw new Exception('Unkown association ' . $name);
    }

    /**
     * Gets the assoc list. [hasMany,belongsTo..].
     *
     * @return array
     */
    public function associations(): array
    {
        return $this->associations;
    }

    /**
     * Hook to call just after model creation.
     */
    public function initialize(array $config)
    {
    }
    /**
     * Returns the behaviorRegistry object
     *
     * @return \Origin\Model\Behavior\BehaviorRegistry
     */
    public function behaviorRegistry(): BehaviorRegistry
    {
        return $this->behaviorRegistry;
    }
    /**
     * Loads a model behavior
     *
     * @param string $name
     * @param array $config
     * @return \Origin\Model\Behavior\Behavior
     */
    public function loadBehavior(string $name, array $config = []) //no return type cause of mocking
    {
        list($plugin, $behavior) = pluginSplit($name);
        $config = array_merge(['className' => $name . 'Behavior'], $config);
        $this->{$behavior} = $this->behaviorRegistry()->load($name, $config);

        return $this->{$behavior};
    }

    /**
     * This will load any model regardless if it is associated or not.
     * If you are loading a model with same name like in a plugin, then best set a unique
     * alias.
     * example:
     *
     * $this->loadModel('CustomModel2',['className'=>'Plugin.CustomModel']);
     * $results = $this->CustomModel2->find('all');
     *
     * @param string $model
     * @param array $config
     * @return \Origin\Model\Model
     */
    public function loadModel(string $name, array $config = []): Model
    {
        list($plugin, $alias) = pluginSplit($name);
        $config = array_merge(['className' => $name], $config);
        $this->{$alias} = ModelRegistry::get($alias, $config);
        if ($this->{$alias}) {
            return $this->{$alias};
        }
        throw new MissingModelException($name);
    }

    /**
     * JOINING MODELS TOGETHER - These functions help if models and fields
     * are named properly. Models should be CamelCase (with first letter capitalized)
     * and foreign keys should be underscored_model_id and the primary key field should be id.
     * Whilst we can easily use the setting from this->primaryKey we would have to load the
     * other model. At this stage we wont do this. So magic only works if you follow the
     * conventions, if not you have to manually create the params.
     */

    /**
     * Creates a hasOne relationship. By default we assume that naming standards
     * are followed using primary key as id. Anything else then you have set the
     * options manually (even if you change the primary key setting).
     *
     * @param string $association e.g Comment
     * @param array  $options The options array accepts any of the following keys
     *   - className: is the name of the class that you want to load (with or without namespace)
     *   - foreignKey: the foreign key in the other model. The default value would be the underscored name of the current model suffixed with '\_id'.
     *   - conditions: an array of additional conditions to the join
     *   - fields: an array of fields to return from the join model, by default it returns all
     *   - dependent: default is false, if set to true when delete is called with cascade it will related records.
     */
    public function hasOne(string $association, array $options = []): array
    {
        $assoc = new Association($this);

        return  $this->hasOne[$association] = $assoc->hasOne($association, $options);
    }

    /**
     * Creates a belongsTo relationship. By default we assume that naming
     * standards are followed using primary key as id. Anything else then
     * you have set the options manually (even if you change the primary
     * key setting). If conditions are supplied they will be merged with autojoin.
     *
     * The Current model contians the foreign key This.other_id
     *
     * @param string $association e.g Comment
     * @param array  $options The options array accepts any of the following keys
     *   - className: is the name of the class that you want to load (with or without namespace)
     *   - foreignKey: the foreign key in the current model.  The default value would be the underscored name of
     * the other model suffixed with '\_id'.
     *   - conditions: an array of additional conditions to the join
     *   - fields: an array of fields to return from the join model, by default it returns all
     *   - type: default is LEFT, this is the join type used to fetch the associated record.
     *   - counterCache: default is null. Counter cache allows you to cache counts of records instead of running
     * counts each time. If you use counter cache anytime a record is created or deleted the counter will be
     * updated. Set a field name to update the count, if set to true it will use the plural of the current model
     * with e.g. comments_count. Lets say you wanted to track number of comments for each post, in your Post model,
     * when setup the belongsTo assocation, say for Comment, set counterCache to true or the name of the field to
     * increment and decrement.
     */
    public function belongsTo(string $association, array $options = []): array
    {
        $assoc = new Association($this);
        $this->belongsTo[$association] = $assoc->belongsTo($association, $options);
        if (isset($options['counterCache']) and ! isset($this->CounterCache)) {
            $this->loadBehavior('CounterCache');
        }

        return $this->belongsTo[$association];
    }

    /**
     * Creates a hasMany relationship. By default we assume that naming
     * standards are followed using primary key as id. Anything else then
     * you have set the options manually (even if you change the primary
     * key setting).
     *
     * Conditions are additional to the record id of the parent record
     *
     * @param string $association e.g Comment
     * @param array  $options The options array accepts any of the following keys
     *   - className: is the name of the class that you want to load.
     *   - foreignKey: the foreign key in the other model. The default value would be the underscored name of the
     * current model suffixed with '\_id'.
     *   - conditions: an array of additional conditions to the join
     *   - fields: an array of fields to return from the join model, by default it returns all
     *   - order: a string or array of how to order the result
     *   - dependent: default is false, if set to true when delete is called with cascade it will related records.
     *   - limit: default is null, set a value to limit how many rows to return
     *   - offset: if you are using limit then set from where to start fetching
     */
    public function hasMany(string $association, array $options = []): array
    {
        $assoc = new Association($this);

        return  $this->hasMany[$association] = $assoc->hasMany($association, $options);
    }

    /**
     * Creates a hasAndBelongsToMany relationship. By default we assume that naming
     * standards are followed using primary key as id. Anything else then
     * you have set the options manually (even if you change the primary
     * key setting).
     *
    * @param string $association e.g Comment
     * @param array  $options The options array accepts any of the following keys
     *   - className: is the name of the class that you want to load (with or without the namespace).
     *   - joinTable: the name of the table used by this relationship
     *   - with: the name of the model which uses the join table e.g ContactsTag (must be in alphabetical order)
     *   - foreignKey: - the foreign key in the current model. The default value would be the underscored name of the other model suffixed with '\_id'.
     *   - associationForeignKey: the foreign key in the other model. The default value would be the underscored name of the other model suffixed with '\_id'.
     *   - conditions: an array of additional conditions to the join
     *   - fields: an array of fields to return from the join model, by default it returns all
     *   - order: a string or array of how to order the result
     *   - dependent: default is false, if set to true when delete is called with cascade it will related records.
     *   - limit: default is null, set a value to limit how many rows to return
     *   - offset: if you are using limit then set from where to start fetching
     *   - mode: default mode is replace.
     *      - replace: In replace, when adding records, all other relationships are deleted first. So it assumes one save contains all the joins. Typically the table will just have two fields, and a composite primary key.
     *      - append: this should be set to append, if you will store other data in the join table, as it wont delete relationships which it is adding back. The table should have an id column and it should be set as the primary key.
     */
    public function hasAndBelongsToMany(string $association, array $options = []): array
    {
        $assoc = new Association($this);

        return  $this->hasAndBelongsToMany[$association] = $assoc->hasAndBelongsToMany($association, $options);
    }

    /**
     * Sets the validation rule/s
     * Examples:
     * $this->validate('first_name','notBlank');
     * $this->validate('first_name',['rule'=>'notBlank']);
     * $this->validate('email', [
     *   'notBlank' =>  ['rule' => 'notBlank'],
     *   'email' =>  ['rule' => 'email']
     *  ]);
     *
     * @param string|array $field Field name to validate
     * @param array $options either the rule name e.g. notBlank or an options array with any of the following keys:
     *   - rule: name of rule e.g. date
     *   - message: the message to show if the rule fails
     *   - on: default null. set to create or update to run the rule only on those
     *   - required: the key must be present
     *   - allowBlank: dont run rule on blank value
     * @return void
     *
     */
    public function validate(string $field, $options) : void
    {
        $this->validator()->setRule($field, $options);
    }

    /**
     * Returns the field list for this model.
     *
     * @return array fields
     */
    public function fields(bool $quote = true) : array
    {
        $schema = $this->schema()['columns'];

        if ($quote === true) {
            return $this->prepareFields(array_keys($schema));
        }

        return array_keys($schema);
    }
    /**
     * Checks if this model has a field
     *
     * @param string $field
     * @return boolean
     */
    public function hasField(string $field) : bool
    {
        $fieldSchema = $this->schema($field);

        return ! empty($fieldSchema);
    }

    /**
     * Adds aliases to an array of fields. Skips fields that
     * 1. Have space example somefield AS anotherName
     * 2. Are a MySQL function example count,max,avg,quarter,date etc
     * 3. Already alaised Post.title.
     *
     * @param array $fields [description]
     * @return array quotedFields
     */
    protected function prepareFields(array $fields) : array
    {
        $alias = Inflector::tableize($this->alias);
        foreach ($fields as $index => $field) {
            if (strpos($field, ' ') === false and strpos($field, '.') === false and strpos($field, '(') === false) {
                $fields[$index] = "{$alias}.{$field}";
            }
        }

        return $fields;
    }

    /**
     * loads the schema for this model or specified field.
     *
     * @param string $field
     * @return string|null|array $field;
     */
    public function schema(string $field = null)
    {
        if ($this->schema === null) {
            $this->schema = $this->connection()->describe($this->table);
        }
        if ($field === null) {
            return $this->schema;
        }
        if (isset($this->schema['columns'][$field])) {
            return $this->schema['columns'][$field];
        }

        return null;
    }

    /**
     * Validates model data in the object.
     *
     * @param array $data
     * @return bool true or false
     */
    public function validates(Entity $data, bool $create = true) : bool
    {
        return $this->validator()->validates($data, $create);
    }

    /**
     * Gets the model validator object and keeps a copy
     *
     * @return \Origin\Model\ModelValidator
     */
    public function validator(): ModelValidator
    {
        if (! isset($this->ModelValidator)) {
            $this->ModelValidator = new ModelValidator($this);
        }

        return $this->ModelValidator;
    }

    /**
     * This does the save
     *
     * @param Entity $entity
     * @param array $options
     * @return bool
     */
    protected function processSave(Entity $entity, array $options = []) : bool
    {
        $options += ['validate' => true, 'callbacks' => true, 'transaction' => true];

        $this->id = null;
        if ($entity->has($this->primaryKey)) {
            $this->id = $entity->{$this->primaryKey};
        }

        $exists = $this->exists($this->id);

        if ($options['validate'] === true) {
            if ($options['callbacks'] === true and ! $this->triggerCallback('beforeValidate', [$entity])) {
                return false;
            }
            $validated = $this->validates($entity, ! $exists);

            if ($options['callbacks'] === true) {
                $this->triggerCallback('afterValidate', [$entity, $validated]);
            }

            if (! $validated) {
                return false;
            }
        }

        if ($options['callbacks'] === true or $options['callbacks'] === 'before') {
            if (! $this->triggerCallback('beforeSave', [$entity, $options])) {
                return false;
            }
        }

        /**
         * Extract HABTM data to prevent being marked as invalid
         * When finding records from db, these are returned as Model\Collection. When marshalling
         * or manually creating it would be array. So we need
         */
        $hasAndBelongsToMany = [];
        foreach ($this->hasAndBelongsToMany as $alias => $habtm) {
            $needle = Inflector::pluralize(lcfirst($alias)); // ArticleTag -> articleTags
            if (in_array($alias, $options['associated'])) {
                $data = $entity->get($needle);

                if (is_array($data) or $data instanceof Collection) {
                    $hasAndBelongsToMany[$alias] = $entity->{$needle};
                }
            }
        }

        /**
         * Only modified fields are saved. The values can be the same, but still counted as modified.
         */
        $columns = array_intersect(array_keys($this->schema()['columns']), $entity->modified());

        $data = [];
        foreach ($columns as $column) {
            $data[$column] = $entity->get($column);
        }

        /**
         * Data should not be objects or arrays. Invalidate any objects or array data
         * e.g. unvalidated datetime fields.
         */
        foreach ($data as $key => $value) {
            if (is_array($value) or is_object($value)) {
                $entity->invalidate($key, 'Invalid data');
            }
        }

        if (empty($data) or $entity->errors()) {
            return false;
        }

        $result = false;

        // Don't save if only field set is id (e.g savingHABTM)
        if (count($data) > 1 or ! isset($data[$this->primaryKey])) {
            $connection = $this->connection();
            if ($exists) {
                $result = $connection->update($this->table, $data, [$this->primaryKey => $this->id]);
            } else {
                $result = $connection->insert($this->table, $data);
                $this->id = $connection->lastInsertId();
                $entity->{$this->primaryKey} = $this->id;
            }
        }

        if ($result) {
            if ($options['callbacks'] === true or $options['callbacks'] === 'after') {
                $this->triggerCallback('afterSave', [$entity, ! $exists, $options]);
            }
        }

        /**
         * Save HABTM. It is here, because control is needed on false result from here
         */
        foreach ($hasAndBelongsToMany as $alias => $data) {
            if (! $this->saveHABTM($alias, $data, $options['callbacks'])) {
                return false;
            }
            $result = true;
        }

        unset($data, $options);

        if ($result) {
            $entity->reset();
        }

        return $result;
    }

    /**
     * Saves a single field on the current record.
     *
     * @params int|string $primaryKey the id for the record
     * @param int|string $fieldName
     * @param mixed  $fieldValue
     * @param array  $options    (callbacks, validate,transaction)
     * @return bool true or false
     */
    public function saveField($primaryKey, string $fieldName, $fieldValue, array $options = []) : bool
    {
        return $this->save(new Entity([
            $this->primaryKey => $primaryKey,
            $fieldName => $fieldValue,
        ]), $options);
    }

    /**
     * Updates one or many records at time, no callbacks are called.
     *
     * @param array $data array(field=>$value)
     * @param array $conditions
     * @return bool true or false
     */
    public function updateAll(array $data, array $conditions) : bool
    {
        return $this->connection()->update($this->table, $data, $conditions);
    }

    /**
     * Increases a column value
     *
     * @param string $column the name of the column to increase e.g. views
     * @param integer $id
     * @return boolean
     */
    public function increment(string $column, int $id): bool
    {
        $sql = "UPDATE {$this->table} SET {$column} = {$column} + 1 WHERE {$this->primaryKey} = :id";

        return $this->connection()->execute($sql, ['id' => $id]);
    }

    /**
     * Decreases a column value
     *
     * @param string $column the name of the column to increase e.g. views
     * @param integer $id
     * @return boolean
     */
    public function decrement(string $column, int $id): bool
    {
        $sql = "UPDATE {$this->table} SET {$column} = {$column} - 1 WHERE {$this->primaryKey} = :id";

        return $this->connection()->execute($sql, ['id' => $id]);
    }

    /**
     * Saves the hasAndBelongsToMany data
     *
     * @param string $association
     * @param Collection|array $data
     * @param boolean $callbacks
     * @return bool
     */
    protected function saveHABTM(string $association, $data, bool $callbacks) : bool
    {
        $connection = $this->connection();

        $config = $this->hasAndBelongsToMany[$association];
        $joinModel = $this->{$config['with']};

        $links = [];

        foreach ($data as $row) {
            $primaryKey = $this->{$association}->primaryKey;
            $displayField = $this->{$association}->displayField;

            // Either primaryKey or DisplayField must be set in data
            if ($row->has($primaryKey)) {
                $needle = $primaryKey;
            } elseif ($row->has($displayField)) {
                $needle = $displayField;
            } else {
                return false;
            }

            $tag = $this->{$association}->find('first', [
                'conditions' => [$needle => $row->get($needle)],
                'callbacks' => false,
            ]);

            if ($tag) {
                $id = $tag->get($primaryKey);
                $links[] = $id;
                $row->set($primaryKey, $id);
            } else {
                if (! $this->{$association}->save($row, [
                    'callbacks' => $callbacks,
                    'transaction' => false,
                ])) {
                    return false;
                }
                $links[] = $this->{$association}->id;
            }

            $joinModel = $this->{$config['with']};
        }

        $existingJoins = $joinModel->find('list', [
            'conditions' => [$config['foreignKey'] => $this->id],
            'fields' => [$config['associationForeignKey']],
        ]);

        $connection = $joinModel->connection();
        // By adding ID field we can do delete callbacks
        if ($config['mode'] === 'replace') {
            $connection->delete($config['joinTable'], [$config['foreignKey'] => $this->id]);
        }

        foreach ($links as $linkId) {
            if ($config['mode'] === 'append' and in_array($linkId, $existingJoins)) {
                continue;
            }
            $insertData = [
                $config['foreignKey'] => $this->id,
                $config['associationForeignKey'] => $linkId,
            ];

            $connection->insert($joinModel->table, $insertData);
        }

        return true;
    }

    /**
     * Returns an normalized array of ssociated settings for dealing with
     * creating entities and saving data. Different than used by find
     *
     * [] = normalizeAssociated(false);
     * $all = normalizeAssociated(true);
     * $some = normalizeAssociated(['Tag','User']);
     *
     * @param array|bool $option
     * @return array
     */
    protected function normalizeAssociated($option) : array
    {
        $associated = [];
        if ($option === false) {
            return [];
        }
        if (is_array($option)) {
            $associated = $option;
        }
        // add keys if not set
        if ($option === true) {
            foreach ($this->associations() as $assocation) {
                $associated = array_merge($associated, array_keys($this->{$assocation}));
            }
        }

        return $associated;
    }

    /**
     * Save model data to database, it can save one level of associations.
     *
     * ## Options
     *
     * The options array can be passed with the following keys:
     *
     * - validate: wether to validate data or not
     * - callbacks: call the callbacks duing each stage.  You can also put only before or after
     * - transaction: wether to save through a database transaction (default:true)
     * - associated: default true. boolean or an array of associated data to save as well
     *
     * # Callbacks
     *
     * The following callbacks will called in this Model and enabled Behaviors
     *
     * - beforeValidate
     * - afterValidate
     * - beforeSave
     * - afterSave
     *
     * @param entity $entity to save
     * @param array  $options keys (validate,callbacks,transaction,associated)
     * @return bool true or false
     */
    public function save(Entity $data, $options = []) : bool
    {
        $options += ['validate' => true, 'callbacks' => true, 'transaction' => true, 'associated' => true];

        $options['associated'] = $this->normalizeAssociated($options['associated']);

        $associatedOptions = ['transaction' => false] + $options;

        if ($options['transaction']) {
            $this->begin();
        }

        $result = true;
        // Save BelongsTo
        foreach ($this->belongsTo as $alias => $config) {
            $key = lcfirst($alias);
            if (! in_array($alias, $options['associated']) or ! $data->has($key) or ! $data->{$key} instanceof Entity) {
                continue;
            }
            if ($data->{$key}->modified()) {
                if (! $this->{$alias}->save($data->{$key}, $associatedOptions)) {
                    $result = false;
                    break;
                }
                $foreignKey = $this->belongsTo[$alias]['foreignKey'];
                $data->$foreignKey = $this->{$alias}->id;
            }
        }

        if ($result) {
            /**
             * This will save record and hasAndBelongsToMany records. This is because
             * it can return false but HABTM is ok, and we need to capture false from
             * callbacks.
             */

            $result = $this->processSave($data, $options);
        }

        if ($result) {
            foreach ($this->hasOne as $alias => $config) {
                $key = lcfirst($alias);
                if (! in_array($alias, $options['associated']) or ! $data->has($key) or ! $data->{$key} instanceof Entity) {
                    continue;
                }
                if ($data->{$key}->modified()) {
                    $foreignKey = $this->hasOne[$alias]['foreignKey'];
                    $data->{$key}->{$foreignKey} = $this->id;

                    if (! $this->{$alias}->save($data->get($key), $associatedOptions)) {
                        $result = false;
                        break;
                    }
                }
            }

            // Save hasMany
            foreach ($this->hasMany as $alias => $config) {
                $key = Inflector::pluralize(lcfirst($alias));
                if (! in_array($alias, $options['associated']) or ! $data->has($key)) {
                    continue;
                }

                $foreignKey = $this->hasMany[$alias]['foreignKey'];

                foreach ($data->get($key) as $record) {
                    if ($record instanceof Entity and $record->modified()) {
                        $record->$foreignKey = $data->{$this->primaryKey};
                        if (! $this->{$alias}->save($record, $associatedOptions)) {
                            $result = false;
                            break;
                        }
                    }
                }
            }
        }

        if ($result) {
            if ($options['transaction']) {
                $this->commit();
            }

            return true;
        }
        if ($options['transaction']) {
            $this->rollback();
        }

        return false;
    }

    /**
     * Save many records at once.
     *
     * @param entity|array $data to e.g. [$entity1,$entity2]
     * @param array $options You can pass the following keys
     *  - validate: wether to validate data or not
     *  - callbacks: call the callbacks duing each stage. You can also put only before or after
     *  - transaction: if set true, the save will be as a transaction and rolledback upon
     *  - any errors. If false, then it will just save what it can
     *
     * @return bool true or false
     */
    public function saveMany(array $data, array $options = [])
    {
        $options += ['validate' => true, 'callbacks' => true, 'transaction' => true];

        if ($options['transaction']) {
            $this->begin();
        }
        $result = true;
        foreach ($data as $row) {
            if (! $this->save($row, ['transaction' => false] + $options)) {
                $result = false;
                break;
            }
        }

        if ($result and $options['transaction']) {
            $this->commit();
        }

        if (! $result and $options['transaction']) {
            $this->rollback();
        }

        return $result;
    }

    /**
     * Checks if the record exists using the primaryKey.
     *
     * @param int|string $id
     * @return bool true if the record exists
     */
    public function exists($id = null) : bool
    {
        if ($id === null) {
            return false;
        }
        $tableAlias = Inflector::tableize($this->alias);

        return (bool) $this->find('count', [
            'conditions' => ["{$tableAlias}.{$this->primaryKey}" => $id],
            'callbacks' => false,
        ]);
    }

    /**
     * Gets an individual record, if it does not exist then it throws an exception
     *
     * @param int|string $id  id of record to fetch
     * @param array $options  The options array can work with the following keys
     *   - conditions: an array of conditions to find by. e.g ['id'=>1234,'status !=>'=>'new]
     *   - fields: an array of fields to fetch for this model. e.g ['id','title','description']
     *   - joins: an array of join arrays e.g. table' => 'authors','alias' => 'authors', 'type' => 'LEFT' ,
     * 'conditions' => ['authors.id = articles.author_id']
     *   - order: the order to fetch e.g. ['title ASC'] or ['category','title ASC']
     *   - limit: the number of records to limit by
     *   - group: the field to group by e.g. ['category']
     *   - callbacks: default is true. Set to false to disable running callbacks such as beforeFind and afterFind
     *   - associated: an array of models to get data for e.g. ['Comment'] or ['Comment'=>['fields'=>['id','body']]]
     * @return \Origin\Model\Entity
     */
    public function get($id, array $options = []) : Entity
    {
        $options += ['conditions' => []];

        $options['conditions'][$this->primaryKey] = $id;
        if ($result = $this->find('first', $options)) {
            return $result;
        }
        throw new NotFoundException(sprintf('Record not found in %s table with the primary key %s', $this->table, $id));
    }

    /**
     * Runs a find query
     *
     * @param string $type  (first,all,count,list)
     * @param array $options  The options array can work with the following keys
     *   - conditions: an array of conditions to find by. e.g ['id'=>1234,'status !=>'=>'new]
     *   - fields: an array of fields to fetch for this model. e.g ['id','title','description']
     *   - joins: an array of join arrays e.g. table' => 'authors','alias' => 'authors', 'type' => 'LEFT' ,
     * 'conditions' => ['authors.id = articles.author_id']
     *   - order: the order to fetch e.g. ['title ASC'] or ['category','title ASC']
     *   - limit: the number of records to limit by
     *   - group: the field to group by e.g. ['category']
     *   - callbacks: default is true. Set to false to disable running callbacks such as beforeFind and afterFind
     *   - associated: an array of models to get data for e.g. ['Comment'] or ['Comment'=>['fields'=>['id','body']]]
     * @return \Origin\Model\Entity|\Origin\Model\Collection|array|int $resultSet
     */
    public function find(string $type = 'first', $options = [])
    {
        $default = [
            'conditions' => null,
            'fields' => [],
            'joins' => [],
            'order' => $this->order,
            'limit' => null,
            'group' => null,
            'page' => null,
            'offset' => null,
            'callbacks' => true,
            'associated' => [],
        ];

        $options = array_merge($default, $options);

        if ($options['callbacks'] === true) {
            $result = $this->triggerCallback('beforeFind', [$options], true);
            if ($result === false) {
                return null;
            }
            if (is_array($result)) {
                $options = $result;
            }
        }

        $options = $this->prepareQuery($type, $options); // AutoJoin

        $results = $this->{'finder' . ucfirst($type)}($options);

        if ($options['callbacks'] === true) {
            $results = $this->triggerCallback('afterFind', [$results], true);
        }

        unset($options);

        return $results;
    }

    /**
     * Deletes a record.
     *
     * @param \Origin\Model\Entity $entity
     * @param bool  $cascade   delete hasOne,hasMany, hasAndBelongsToMany records
     * @param bool $callbacks call beforeDelete and afterDelete callbacks
     * @return bool true or false
     */
    public function delete(Entity $entity, $cascade = true, $callbacks = true)
    {
        $this->id = $entity->get($this->primaryKey);

        if (empty($this->id) or ! $this->exists($this->id)) {
            return false;
        }

        if ($callbacks) {
            if (! $this->triggerCallback('beforeDelete', [$entity, $cascade])) {
                return false;
            }
        }

        $this->deleteHABTM($this->id);
        if ($cascade) {
            $this->deleteDependent($this->id);
        }

        $result = $this->connection()->delete($this->table, [$this->primaryKey => $this->id]);

        if ($callbacks) {
            $this->triggerCallback('afterDelete', [$entity, $result]);
        }

        return $result;
    }

    /**
     * Deletes the hasOne and hasMany associated records.
     *
     * @var int|string
     */
    protected function deleteDependent($primaryKey)
    {
        foreach (array_merge($this->hasOne, $this->hasMany) as $association => $config) {
            if (isset($config['dependent']) and $config['dependent'] === true) {
                $conditions = [$config['foreignKey'] => $primaryKey];
                $ids = $this->{$association}->find('list', ['conditions' => $conditions, 'fields' => [$this->primaryKey]]);
                foreach ($ids as $id) {
                    $conditions = [$this->{$association}->primaryKey => $id];
                    $result = $this->{$association}->find('first', ['conditions' => $conditions, 'callbacks' => false]);
                    if ($result) {
                        $this->{$association}->delete($result);
                    }
                }
            }
        }
    }

    /**
     * Deletes the hasAndBelongsToMany associated records.
     *
     * @var int|string $id
     * @return void
     */
    protected function deleteHABTM($id) : void
    {
        foreach ($this->hasAndBelongsToMany as $association => $config) {
            $associatedModel = $config['with'];
            $conditions = [$config['foreignKey'] => $id];
            $ids = $this->$associatedModel->find('list', ['conditions' => $conditions]);

            foreach ($ids as $id) {
                $conditions = [$this->{$associatedModel}->primaryKey => $id];
                $result = $this->{$associatedModel}->find('first', ['conditions' => $conditions, 'callbacks' => false]);
                if ($result) {
                    $this->{$associatedModel}->delete($result);
                }
            }
        }
    }

    /**
     * Bulk deletes records, does not delete associated data, use model::delete for that.
     *
     * @param array $conditions e.g ('Article.status' => 'draft')
     * @return bool true or false
     */
    public function deleteAll($conditions) : bool
    {
        return $this->connection()->delete($this->table, $conditions);
    }

    /**
     * Finder for find('first').
     *
     * @param array $query (conditions,fields, joins, order,limit, group, callbacks,etc)
     * @return array|null results
     */
    protected function finderFirst($options = []) : ?Entity
    {
        // Modify Query
        $options['limit'] = 1;

        // Run Query
        $query = new Query($this);
        $results = $query->find($options);
        // $results = $this->readDataSource($query);

        if (empty($results)) {
            return null;
        }

        // Modify Results
        return $results[0];
    }

    /**
     * Finder for find('all').
     *
     * @param array $query (conditions,fields, joins, order,limit, group, callbacks,etc)
     * @return \Origin\Model\Collection|array
     */
    protected function finderAll(array $options = [])
    {
        // Run Query
       
        $query = new Query($this);
        $results = $query->find($options);
        // $results = $this->readDataSource($query);

        // Modify Results
        if (empty($results)) {
            return [];
        }

        return new Collection($results, ['name' => $this->alias]);
    }

    /**
     * Finder for find('list')
     *  3 different list types ['a','b','c'] or ['a'=>'b'] or ['c'=>['a'=>'b']] depending upon how many columns are selected. If more than 3 columns selected it returns ['a'=>'b'].
     *
     * @param array $query (conditions,fields, joins, order,limit, group, callbacks,etc)
     * @return array $results
     */
    protected function finderList(array $options): array
    {
        if (empty($options['fields'])) {
            $options['fields'][] = $this->primaryKey;
            if ($this->displayField) {
                $options['fields'][] = $this->displayField;
            }
        }

        // Run Query
        $query = new Query($this);
        $results = $query->find($options, 'list');
        // $results = $this->readDataSource($query, 'list');

        // Modify Results
        if (empty($results)) {
            return [];
        }

        return $results;
    }

    /**
     * This is the find('count').
     *
     * @param array $query (conditions,fields, joins, order,limit, group, callbacks,etc)
     * @return int count
     */
    protected function finderCount(array $options) : int
    {
        // Modify Query
        $options['fields'] = ['COUNT(*) AS count'];
        $options['order'] = null;
        $options['limit'] = null;

        // Run Query
        $query = new Query($this);
        $results = $query->find($options, 'assoc');
        //$results = $this->readDataSource($query, 'assoc');

        // Modify Results
        return $results[0]['count'];
    }

    /**
     * Add default keys, auto join models etc.
     *
     * @param array $query
     * @return array $query
     */
    protected function prepareQuery(string $type, array $query) : array
    {
        if ($type === 'first' or $type === 'all') {
            if (empty($query['fields'])) {
                $query['fields'] = $this->fields();
            } else {
                $query['fields'] = $this->prepareFields($query['fields']);
            }
        }

        $query['associated'] = $this->associatedConfig($query);
        foreach (['belongsTo', 'hasOne'] as $association) {
            foreach ($this->{$association} as $alias => $config) {
                if (isset($query['associated'][$alias])) {
                    $config = array_merge($config, $query['associated'][$alias]); /// fields
                    $query['joins'][] = [
                        'table' => $this->{$alias}->table,
                        'alias' => Inflector::tableize($alias),
                        'type' => ($association === 'belongsTo' ? $config['type'] : 'LEFT'),
                        'conditions' => $config['conditions'],
                        'datasource' => $this->datasource,
                    ];

                    if (empty($config['fields'])) {
                        $config['fields'] = $this->{$alias}->fields();
                    }

                    // If it throw an error, then it can be confusing to know source, so turn to array
                    $query['fields'] = array_merge((array) $query['fields'], (array) $config['fields']);
                }
            }
        }

        return $query;
    }

    /**
     * Standardizes the config for eager loading of related
     * data
     *
     * @param array $query
     * @return array
     */
    protected function associatedConfig(array $query) : array
    {
        $out = [];
        foreach ((array) $query['associated'] as $alias => $config) {
            if (is_int($alias)) {
                $alias = $config;
                $config = [];
            }
            $config += ['fields' => []];
            $tableAlias = Inflector::tableize($alias);
            foreach ($config['fields'] as $key => $value) {
                $config['fields'][$key] = "{$tableAlias}.{$value}";
            }
            $out[$alias] = $config;

            if (! $this->findAssociation($alias)) {
                throw new InvalidArgumentException("{$this->name} is not associated with {$alias}.");
            }
        }

        return $out;
    }

    /**
     * Searches associations
     *
     * @param string $name
     * @return array|null
     */
    protected function findAssociation(string $name): ?array
    {
        foreach ($this->associations as $association) {
            if (isset($this->{$association}[$name])) {
                return $this->{$association}[$name];
            }
        }

        return null;
    }
 
    /**
     * Runs a query and returns the result set if there are any
     * if not returns true or false.
     *
     * @param string $sql
     * @param array  $params bind values
     * @return bool|array|null
     */
    public function query(string $sql, array $params = [])
    {
        $connection = $this->connection();
        $result = $connection->execute($sql, $params);

        if (preg_match('/^SELECT/i', $sql)) {
            return $connection->fetchAll('assoc');
        }

        return $result;
    }

    /**
     * Returns the current connection for this model
     *
     * @return \Origin\Model\Datasource
     */
    public function connection(): Datasource
    {
        return ConnectionManager::get($this->datasource);
    }

    /**
     * Callback that is triggered just before the request data is marshalled. This will
     * be triggered when passing data through model::new, model::patch or model::newEntities
     *
     * @param array $requestData
     * @return array
     */
    public function beforeMarshal(array $requestData = [])
    {
        return $requestData;
    }

    /**
     * Before find callback. Must return either the query or true to continue
     * @return array|bool query or bool
     */
    public function beforeFind(array $query = [])
    {
        return $query;
    }

    /**
     * After find callback, this should return the results
     * @return \Origin\Model\Entity|\Origin\Model\Collection|array|int $results
     */
    public function afterFind($results)
    {
        return $results;
    }

    /**
     * Before Validation takes places, must return true to continue
     *
     * @param \Origin\Model\Entity $entity
     * @return bool
     */
    public function beforeValidate(Entity $entity)
    {
        return true;
    }

    /**
     * Before Validation takes places, must return true to continue
     *
     * @param \Origin\Model\Entity $entity
     * @param bool $success validation result
     * @return bool
     */
    public function afterValidate(Entity $entity, bool $success)
    {
    }

    /**
     * Before save callback
     *
     * @param \Origin\Model\Entity $entity
     * @param array $options
     * @return bool must return true to continue
     */
    public function beforeSave(Entity $entity, array $options = [])
    {
        return true;
    }

    /**
     * After save callback
     *
     * @param \Origin\Model\Entity $entity
     * @param boolean $created if this is a new record
     * @param array $options these were the options passed to save
     * @return void
     */
    public function afterSave(Entity $entity, bool $created, array $options = [])
    {
    }

    /**
     * Before delete, must return true to continue
     *
     * @param \Origin\Model\Entity $entity
     * @param boolean $cascade
     * @return bool
     */
    public function beforeDelete(Entity $entity, bool $cascade = true)
    {
        return true;
    }
    /**
     * After delete
     *
     * @param \Origin\Model\Entity $entity
     * @param boolean $sucess wether or not it deleted the record
     * @return bool
     */
    public function afterDelete(Entity $entity, bool $success)
    {
    }

    /**
     * Checks values in an entity are unique, this could be that a username is not already
     * taken or an email is not used twice
     * @param \Origin\Model\Entity $entity
     * @param array  $fields array of fields to check values in entity
     * @return bool
     */
    public function isUnique(Entity $entity, array $fields = []) : bool
    {
        $conditions = [];
        foreach ($fields as $field) {
            $conditions[$field] = null;
            if (isset($entity->{$field})) {
                $conditions[$field] = $entity->{$field};
            }
        }

        return $this->find('count', ['conditions' => $conditions]) === 0;
    }

    /**
     * Starts a database transaction
     *
     * @return bool
     */
    public function begin(): bool
    {
        return $this->connection()->begin();
    }

    /**
     * Commits a database transaction
     *
     * @return boolean
     */
    public function commit(): bool
    {
        return $this->connection()->commit();
    }

    /**
     * Rollsback a database transaction
     *
     * @return boolean
     */
    public function rollback(): bool
    {
        return $this->connection()->rollBack();
    }

    /**
     * Creates an instance of an Entity. If you pass data as an argument this then it will
     * go through the marshalling process.
     *
     * @param array $requestData
     * @param array $options
     * @return \Origin\Model\Entity
     */
    public function new(array $requestData = null, array $options = []) : Entity
    {
        if ($requestData === null) {
            return new Entity([], ['name' => $this->alias]);
        }
        $options += ['name' => $this->alias, 'associated' => true];
        $options['associated'] = $this->normalizeAssociated($options['associated']);

        $requestData = $this->triggerCallback('beforeMarshal', [$requestData], true);

        return $this->marshaller()->one($requestData, $options);
    }

    /**
     * Creates many Entities from an array of data.
     *
     * @param array $data
     * @param array $options parse default is set to true
     * @var array
     */
    public function newEntities(array $requestData, array $options = []) : array
    {
        $options += ['name' => $this->alias, 'associated' => true];
        $options['associated'] = $this->normalizeAssociated($options['associated']);
        $requestData = $this->triggerCallback('beforeMarshal', [$requestData], true);

        return $this->marshaller()->many($requestData, $options);
    }

    /**
     * Patches an existing entity with requested data
     *
     * @param Entity $entity
     * @param array  $requestData
     * @param array $options parse
     * @var \Origin\Model\Entity
     */
    public function patch(Entity $entity, array $requestData, array $options = []) : Entity
    {
        $options += ['associated' => true];
        $options['associated'] = $this->normalizeAssociated($options['associated']);
        $requestData = $this->triggerCallback('beforeMarshal', [$requestData], true);

        return $this->marshaller()->patch($entity, $requestData, $options);
    }

    /**
     * Gets the Marshaller object.
     *
     * @var \Origin\Model\Marshaller
     */
    protected function marshaller() : Marshaller
    {
        return new Marshaller($this);
    }

    /**
     * triggerCallback.
     *
     * @param string $callback
     * @param array  $arguments
     * @param bool   $passedArgs if result is array overwrite
     * @return mixed
     */
    protected function triggerCallback(string $callback, $arguments = [], $passedArgs = false)
    {
        $callbacks = [
            [$this, $callback],
        ];

        foreach ($this->behaviorRegistry()->enabled() as $behavior) {
            $callbacks[] = [$this->{$behavior}, $callback];
        }

        foreach ($callbacks as $callable) {
            $result = call_user_func_array($callable, $arguments);

            if ($result === false) {
                return false;
            }
            // overwrite first argument with last result if its array
            if ($passedArgs and is_array($result)) {
                $arguments[0] = $result;
            }
            /**
             * Bug Fix. When reloading an entity with new belongsTo in afterFind
             * and trying to replace result is overwritten by original after timestamp
             * behavior is called. This only happened when overwriting the entire Entity
             * not when adjusting eg. $entity->property = 'foo'
             */
            if ($result instanceof Entity) {
                $arguments[0] = $result;
            }
        }
        // Free Mem
        unset($callbacks, $result);

        if ($passedArgs) {
            return $arguments[0]; // was if not exist return result
        }

        return true;
    }

    /**
     * Enables a behavior that has been disabled
     *
     * @param string $name
     * @return bool
     */
    public function enableBehavior(string $name) :bool
    {
        return $this->behaviorRegistry->enable($name);
    }
    /**
     * Disables a behavior
     *
     * @param string $name
     * @return bool
     */
    public function disableBehavior(string $name) : bool
    {
        return $this->behaviorRegistry->disable($name);
    }
}
