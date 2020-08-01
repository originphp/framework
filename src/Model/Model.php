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

/**
 * Multiple entities can be wrapped in array of Model\Collection, must work both ways so
 * that entities with associated data can also be created manually.
 *
 */

use ArrayObject;
use Origin\Core\HookTrait;
use Origin\Core\ModelTrait;
use Origin\Inflector\Inflector;
use Origin\Core\InitializerTrait;
use Origin\Core\Exception\Exception;
use Origin\Core\CallbackRegistrationTrait;
use Origin\Model\Concern\CounterCacheable;
use Origin\Model\Exception\MissingModelException;
use Origin\Core\Exception\InvalidArgumentException;
use Origin\Model\Exception\RecordNotFoundException;

class Model
{
    use InitializerTrait, ModelTrait, CounterCacheable, HookTrait, CallbackRegistrationTrait, EntityLocatorTrait;
    
    /**
     * The name for this model, this generated automatically.
     *
     * @var string
     */
    protected $name = null;

    /**
     * The alias name for this model, again this generated automatically
     *
     * @var string
     */
    protected $alias = null;

    /**
     * This is the Database connection to used by this model.
     *
     * @var string
     */
    protected $connection = 'default';

    /**
     * This is the table name for the model this will be generated automatically
     * if you want to overide this then change this.
     *
     * @var string
     */
    protected $table = null;

    /**
     * Each table should have a primary key and it should be id, because
     * 1. associations wont work without you telling which fields to use
     * 2. not really fully tested using something else, but it should work ;).
     * 3. it might get confusing later
     * @var string
     */
    protected $primaryKey = null;

    /**
     * This is the main field on the model, for a contact, it would be contact_name. Things
     * like name, title etc.
     *
     * @var string
     */
    protected $displayField = null;

    /**
     * Default order to used when finding.
     *
     * $order = 'articles.title ASC';
     * $order = ['articles.title','articles.created ASC']
     *
     * @var string|array
     */
    protected $order = null;

    /**
     * The ID of the last record created, updated, or deleted. When saving
     * associated data, it would be of the main record not the associated.
     *
     * @var mixed
     */
    protected $id = null;

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

    public function __construct(array $config = [])
    {
        $config += [
            'name' => $this->name,
            'alias' => $this->alias,
            'connection' => $this->connection,
            'table' => $this->table,
        ];
     
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
            $table = Inflector::tableName($this->name);
        }
        $this->table = $table;

        if ($this->primaryKey === null) {
            $this->primaryKey = 'id';
        }

        $this->connection = $connection;

        // Remove so we can autodetect when needed
        if (! $this->displayField) {
            unset($this->displayField);
        }
        
        $this->executeHook('initialize', [$config]);
        $this->initializeTraits($config);
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
                if (isset($config['with']) && $config['with'] === $name) {
                    $className = $config['with'];
                    $habtmModel = true;
                    break;
                }
            }
        }

        if ($className === null && $habtmModel === false) {
            return false;
        }

        $object = ModelRegistry::get($name, ['className' => $className, 'alias' => $name]);
        if ($object === null && $habtmModel === false) {
            throw new MissingModelException($name);
        }

        if ($habtmModel) {
            $object = new Model([
                'name' => $className,
                'table' => $this->hasAndBelongsToMany[$alias]['joinTable'],
                'connection' => $this->connection,
            ]);

            if (count($object->fields()) === 2) {
                $object->primaryKey = $this->hasAndBelongsToMany[$alias]['foreignKey'];
            }

            ModelRegistry::set($name, $object);
        }

        $this->$name = $object;

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
            return $this->displayField = $this->detectDisplayField();
        }
        if (isset($this->$name) && $this->$name instanceof Model) {
            return $this->$name;
        }

        return null;
    }

    /**
     * Gets the display field if set or tries to detect it.
     *
     * @return string
     */
    private function detectDisplayField(): string
    {
        $fields = array_keys($this->schema()['columns']);

        $needles = [
            Inflector::underscored($this->name) . '_name',
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
        if (in_array($name, $this->associations)) {
            return $this->$name;
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
        return $this->hasOne[$association] = (new Association($this))->hasOne($association, $options);
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
        $this->belongsTo[$association] = (new Association($this))->belongsTo($association, $options);
        if (isset($options['counterCache']) && ! isset($this->CounterCache)) {
            $this->enableCounterCache();
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
        return  $this->hasMany[$association] = (new Association($this))->hasMany($association, $options);
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
        return $this->hasAndBelongsToMany[$association] = (new Association($this))->hasAndBelongsToMany($association, $options);
    }

    /**
     * Sets the validation rule/s
     *
     * Examples:
     *
     * $this->validate('first_name','notBlank');
     *
     * $this->validate('first_name',[
     *      'required'
     * ]);
     *
     * $this->validate('first_name',[
     *      'rule' => 'required'
     * ]);
     *
     * $this->validate('email', [
     *   'required',
     *   'email' =>  ['rule' => 'email','message'=>'Invalid email address']
     *  ]);
     *
     * @param string $field Field name to validate
     * @param string|array $options either the rule name e.g. notBlank or an options array with any of the following keys:
     *   - rule: name of rule e.g. required, numeric, ['date', 'Y-m-d']
     *   - message: the error message to show if the rule fails
     *   - on: default:null. set to create or update to run the rule only on those
     *   - present: default:false the field (key) must be present but can be empty
     *   - allowEmpty: default:false accepts null values
     *   - stopOnFail: default:true stop on validation failure
     * @return void
     */
    public function validate(string $field, $options): void
    {
        $this->validator()->setRule($field, $options);
    }

    /**
     * Returns the field list for this model.
     *
     * @return array fields
     */
    public function fields(bool $quote = true): array
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
    public function hasField(string $field): bool
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
    protected function prepareFields(array $fields): array
    {
        $alias = Inflector::tableName($this->alias);
        foreach ($fields as $index => $field) {
            if (strpos($field, ' ') === false && strpos($field, '.') === false && strpos($field, '(') === false) {
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
            $this->schema = $this->table ? $this->connection()->describe($this->table) : [
                'columns' => [],
                'indexes' => [],
                'constraints' => []
            ];
        }
        if ($field === null) {
            return $this->schema;
        }

        return $this->schema['columns'][$field] ?? null;
    }

    /**
     * Validates model data in the object.
     *
     * @param \Origin\Model\Entity $data
     * @return bool $create set to false if this is update
     */
    public function validates(Entity $data, bool $create = true): bool
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
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return bool
     */
    protected function processSave(Entity $entity, ArrayObject $options): bool
    {
        $this->id = $entity->get($this->primaryKey);
    
        $exists = $this->exists($this->id);
        $entity->exists($exists);

        $event = $exists === true ? 'update' : 'create';

        if ($options['validate'] === true) {
            if ($options['callbacks'] === true && ! $this->triggerCallback('beforeValidate', $event, [$entity, $options])) {
                return false;
            }

            $validated = $this->validates($entity, ! $exists);

            if ($options['callbacks'] === true) {
                $this->triggerCallback('afterValidate', $event, [$entity, $options]);
            }

            if (! $validated) {
                return false;
            }
        }
      
        if ($options['callbacks'] === true || $options['callbacks'] === 'before') {
            if (! $this->triggerCallback('beforeSave', $event, [$entity, $options])) {
                return false;
            }

            $callback = $exists ? 'beforeUpdate' : 'beforeCreate';
            if (! $this->triggerCallback($callback, $event, [$entity, $options])) {
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
            $needle = Inflector::plural(lcfirst($alias)); // ArticleTag -> articleTags
            if (in_array($alias, $options['associated'])) {
                $data = $entity->get($needle);

                if (is_array($data) || $data instanceof Collection) {
                    $hasAndBelongsToMany[$alias] = $entity->$needle;
                }
            }
        }

        /**
         * Only modified fields are saved. The values can be the same, but still counted as modified.
         */
        $columns = array_intersect(array_keys($this->schema()['columns']), $entity->dirty());

        $data = [];
        foreach ($columns as $column) {
            $data[$column] = $entity->get($column);
        }

        /**
         * Data should not be objects or arrays. Invalidate any objects or array data
         * e.g. unvalidated datetime fields.
         */
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $entity->invalidate($key, 'Invalid data');
            }
        }
     
        if (empty($data) || $entity->errors()) {
            return false;
        }

        $result = false;

        // Don't save if only field set is id (e.g savingHABTM)
        if (count($data) > 1 || ! isset($data[$this->primaryKey])) {
            if ($exists) {
                $result = $this->connection()->update($this->table, $data, [$this->primaryKey => $this->id]);
            } else {
                $result = $this->connection()->insert($this->table, $data);
                
                if ($result) {
                    $entity->created(true);
                }
               
                /**
                 * Postgresql lastInsertId error if you specify wrong id.
                 * @internal lastval is not yet defined in this session
                 */
                if (empty($entity->{$this->primaryKey})) {
                    $entity->{$this->primaryKey} = (int) $this->connection()->lastInsertId();
                }
                $this->id = $entity->{$this->primaryKey};
            }
            // handle callbacks
            if ($result && ($options['callbacks'] === true || $options['callbacks'] === 'after')) {
                $callback = $exists ? 'afterUpdate' : 'afterCreate';
                $this->triggerCallback($callback, $event, [$entity, $options], false);
                $this->triggerCallback('afterSave', $event, [$entity, $options], false);
            }
        }
      
        /**
        * Save HABTM. It is here, because control is needed on false result from here
        */
        if ($hasAndBelongsToMany) {
            $result = (new Association($this))->saveHasAndBelongsToMany($hasAndBelongsToMany, $options['callbacks']);
        }
  
        unset($data, $options, $hasAndBelongsToMany);

        if ($result) {
            $entity->reset();
        }

        return $result;
    }

    /**
     * Updates a column in the table, no validation checks and no callbacks are triggered
     *
     * @param int|string $primaryKey the id for the record
     * @param string $name column name
     * @param mixed $value
     * @return bool true or false
     */
    public function updateColumn($primaryKey, string $name, $value): bool
    {
        return $this->connection()->update($this->table, [$name => $value], [$this->primaryKey => $primaryKey]);
    }

    /**
     * Updates one or many records at time, no callbacks are called.
     *
     * @param array $data array(field=>$value)
     * @param array $conditions
     * @return bool true or false
     */
    public function updateAll(array $data, array $conditions): bool
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
    protected function normalizeAssociated($option): array
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
            foreach ($this->associations as $assocation) {
                $associated = array_merge($associated, array_keys($this->$assocation));
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
     * The following callbacks will called in this Model
     *
     * - beforeValidate
     * - afterValidate
     * - beforeSave
     * - beforeCreate/beforeCreate
     * - afterCreate/afterUpdate
     * - afterSave
     * - afterCommit/afterRollback
     *
     * @param \Origin\Model\Entity $data data to save
     * @param array $options
     *   - validate: set to false to skip validation
     *   - callbacks: call the callbacks duing each stage.  You can also put only before or after
     *   - transaction: wether to save through a database transaction (default:true)
     *   - associated: default true. boolean or an array of associated data to save as well
     * @return bool $result true or false
     */
    public function save(Entity $data, array $options = []): bool
    {
        $options = new ArrayObject($options + [
            'validate' => true, 'callbacks' => true, 'transaction' => true, 'associated' => true
        ]);

        $options['associated'] = $this->normalizeAssociated($options['associated']);

        if ($options['transaction']) {
            $this->begin();
        }
        $assocation = new Association($this);
        
        $result = $assocation->saveBelongsTo($data, $options);
      
        if ($result) {
            $event = $data->exists() === true ? 'update' : 'create';
            try {
                $result = $this->processSave($data, $options);
            } catch (\Exception $e) {
                if ($options['callbacks']) {
                    $this->triggerCallback('onError', $event, [$e]);
                }
           
                $this->cancelTransaction($data, $options, $event);
                throw $e;
            }
        }

        if ($result) {
            $result = $assocation->saveHasOne($data, $options);
        }

        if ($result) {
            $result = $assocation->saveHasMany($data, $options);
        }
        
        $event = $data->exists() === true ? 'update' : 'create';

        if ($result) {
            $this->commitTransaction($data, $options, $event);
        } else {
            $this->cancelTransaction($data, $options, $event);
        }

        return $result;
    }

    /**
     * Save many records at once.
     *
     * @param array $data to e.g. [$entity1,$entity2]
     * @param array $options You can pass the following keys
     *  - validate: wether to validate data or not
     *  - callbacks: call the callbacks duing each stage. You can also put only before or after
     *  - transaction: if set true, the save will be as a transaction and rolledback upon
     *  - any errors. If false, then it will just save what it can
     *
     * @return bool true or false
     */
    public function saveMany(array $data, array $options = []): bool
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
       
        if ($options['transaction']) {
            $result === true ? $this->commit() : $this->rollback();
    
            if ($options['callbacks'] === true || $options['callbacks'] === 'after') {
                $options = new ArrayObject($options);
                foreach ($data as $row) {
                    $event = $row->exists() === true ? 'create' : 'update';
                    $callback = $result ? 'afterCommmit' : 'afterRollback';
                    $this->triggerCallback($callback, $event, [$row,$options]);
                }
            }
        }
    
        return $result;
    }

    /**
     * Checks if the record exists using the primaryKey.
     *
     * @param int|string $id
     * @return bool true if the record exists
     */
    public function exists($id = null): bool
    {
        if ($id === null) {
            return false;
        }
        $tableAlias = Inflector::tableName($this->alias);

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
    public function get($id, array $options = []): Entity
    {
        $options += ['conditions' => []];

        $options['conditions'][$this->primaryKey] = $id;
        if ($result = $this->find('first', $options)) {
            return $result;
        }
        throw new RecordNotFoundException(sprintf('Record not found in %s table with the primary key %s', $this->table, $id));
    }

    /**
     * Runs a find query
     *
     * @param string $type  (first,all,count,list)
     * @param array $options  The options array can work with the following keys
     *   - conditions: an array of conditions to find by. e.g ['id'=>1234,'status !=>'=>'new]
     *   - fields: an array of fields to fetch for this model. e.g ['id','title','description']
     *   - joins: an array of join arrays e.g. 'table' => 'authors','alias' => 'authors', 'type' => 'LEFT' ,
     * 'conditions' => ['authors.id = articles.author_id']
     *   - order: the order to fetch e.g. ['title ASC'] or ['category','title ASC']
     *   - limit: the number of records to limit by
     *   - group: the field to group by e.g. ['category']
     *   - callbacks: default is true. Set to false to disable running callbacks such as beforeFind and afterFind
     *   - associated: an array of models to get data for e.g. ['Comment'] or ['Comment'=>['fields'=>['id','body']]]
     *   - lock: default false. set to true for a SELECT FOR UPDATE statement
     *   - having: an array of conditions for a having clause
     * @return mixed $result
     */
    public function find(string $type = 'first', array $options = [])
    {
        $options = new ArrayObject($options + [
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
        ]);
        
        if ($options['callbacks'] === true) {
            if ($this->triggerCallback('beforeFind', 'find', [$options]) === false) {
                return null;
            }
        }

        $options = $this->prepareQuery($type, $options); // AutoJoin

        $results = $this->{'finder' . ucfirst($type)}($options);
        unset($options);

        return $results;
    }

    /**
     * Finds the first record by a set of conditions
     *
     * @param array $conditions
     * @param array $options  The options array can work with the following keys
     *   - fields: an array of fields to fetch for this model. e.g ['id','title','description']
     *   - joins: an array of join arrays e.g. table' => 'authors','alias' => 'authors', 'type' => 'LEFT' ,
     * 'conditions' => ['authors.id = articles.author_id']
     *   - order: the order to fetch e.g. ['title ASC'] or ['category','title ASC']
     *   - limit: the number of records to limit by
     *   - group: the field to group by e.g. ['category']
     *   - callbacks: default is true. Set to false to disable running callbacks such as beforeFind and afterFind
     *   - associated: an array of models to get data for e.g. ['Comment'] or ['Comment'=>['fields'=>['id','body']]]
     * @return \Origin\Model\Entity|null
     */
    public function findBy(array $conditions = [], array $options = []): ?Entity
    {
        return $this->find('first', array_merge($options, ['conditions' => $conditions]));
    }

    /**
    * Finds the first record that matches conditions
    *
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
    *   - lock: default false. set to true for a SELECT FOR UPDATE statement
    *   - having: an array of conditions for a having clause
    * @return \Origin\Model\Entity|null $result
    */
    public function first(array $options = []): ?Entity
    {
        return $this->find('first', $options);
    }

    /**
     * Runs a find all query
     *
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
     *   - lock: default false. set to true for a SELECT FOR UPDATE statement
     *   - having: an array of conditions for a having clause
     * @return \Origin\Model\Collection|array $result
     */
    public function all(array $options = [])
    {
        return $this->find('all', $options);
    }

    /**
    * Finds a list (using the List finder)
    *
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
    *   - lock: default false. set to true for a SELECT FOR UPDATE statement
    *   - having: an array of conditions for a having clause
    * @return array
    */
    public function list(array $options = []): array
    {
        return $this->find('list', $options);
    }

    /**
     * Finds all records by array of conditions
     *
     * @param array $conditions
     * @param array $options  The options array can work with the following keys
     *   - fields: an array of fields to fetch for this model. e.g ['id','title','description']
     *   - joins: an array of join arrays e.g. table' => 'authors','alias' => 'authors', 'type' => 'LEFT' ,
     * 'conditions' => ['authors.id = articles.author_id']
     *   - order: the order to fetch e.g. ['title ASC'] or ['category','title ASC']
     *   - limit: the number of records to limit by
     *   - group: the field to group by e.g. ['category']
     *   - callbacks: default is true. Set to false to disable running callbacks such as beforeFind and afterFind
     *   - associated: an array of models to get data for e.g. ['Comment'] or ['Comment'=>['fields'=>['id','body']]]
     * @return \Origin\Model\Collection|array
     */
    public function findAllBy(array $conditions = [], array $options = [])
    {
        return $this->find('all', array_merge($options, ['conditions' => $conditions]));
    }

    /**
     * Starts a fluent query builder interface using conditions for the query
     *
     * @param array $conditions
     * @return \Origin\Model\Query;
     */
    public function where(array $conditions): Query
    {
        return new Query($this, $conditions);
    }

    /**
     * Starts a fluent query builder interface by selecting columns to use in a query
     *
     * @param array $columns
     * @return \Origin\Model\Query;
     */
    public function select(array $columns): Query
    {
        return new Query($this, [], $columns);
    }

    /**
     * Counts the number of rows
     *
     * @param string $columnName all (alias for *), DISTINCT clients.id
     * @param array $options You can use some of the Model::find options. (group,having,joins,callbacks)
     * @return integer|array
     */
    public function count(string $columnName = 'all', array $options = [])
    {
        return $this->calculate('count', $columnName === 'all' ? '*' : $columnName, $options);
    }

    /**
     * Calculates the sum of a column
     *
     * @param string $columnName
     * @param array $options You can use some of the Model::find options. (group,having,joins,callbacks)
     * @return integer|float|array|null
     */
    public function sum(string $columnName, array $options = [])
    {
        return $this->calculate('sum', $columnName, $options);
    }

    /**
     * Calculates the average for a column
     *
     * @param string $columnName
     * @param array $options You can use some of the Model::find options. (group,having,joins,callbacks)
     * @return float|array|null
     */
    public function average(string $columnName, array $options = [])
    {
        return $this->calculate('average', $columnName, $options);
    }

    /**
     * Calculates the minimum for a column
     *
     * @param string $columnName
     * @param array $options You can use some of the Model::find options. (group,having,joins,callbacks)
     * @return integer|array|null
     */
    public function minimum(string $columnName, array $options = [])
    {
        return $this->calculate('minimum', $columnName, $options);
    }

    /**
     * Calculates the maximum for a column
     *
     * @param string $columnName
     * @param array $options You can use some of the Model::find options. (group,having,joins,callbacks)
     * @return integer|array|null
     */
    public function maximum(string $columnName, array $options = [])
    {
        return $this->calculate('maximum', $columnName, $options);
    }

    /**
     * Deletes a record.
     *
     * @param \Origin\Model\Entity $entity
     * @param array $options supports the following keys
     *   - cascade: delete hasOne,hasMany, hasAndBelongsToMany records that depend on this record
     *   - callbacks: call beforeDelete and afterDelete callbacks
     *  - transaction: wether to save through a database transaction (default:true)
     * @return bool $result true or false
     */
    public function delete(Entity $entity, array $options = []): bool
    {
        $options = new ArrayObject($options + [
            'cascade' => true,'callbacks' => true,'transaction' => true
        ]);

        $this->id = $entity->get($this->primaryKey);

        if (empty($this->id) || ! $this->exists($this->id)) {
            return false;
        }

        return $this->processDelete($entity, $options);
    }

    /**
    * The delete process
    *
    * @param \Origin\Model\Entity $entity
    * @param \ArrayObject $options supports the following keys
    *   - cascade: delete hasOne,hasMany, hasAndBelongsToMany records that depend on this record
    *   - callbacks: call beforeDelete and afterDelete callbacks
    *  - transaction: wether to save through a database transaction (default:true)
    * @return bool $result true or false
    */
    protected function processDelete(Entity $entity, Arrayobject $options): bool
    {
        if ($options['callbacks'] === true || $options['callbacks'] === 'before') {
            if (! $this->triggerCallback('beforeDelete', 'delete', [$entity, $options])) {
                return false;
            }
        }
        if ($options['transaction']) {
            $this->begin();
        }
        $association = new Association($this);
        $association->deleteHasAndBelongsToMany($this->id, $options['callbacks']);
        if ($options['cascade']) {
            $association->deleteDependent($this->id, $options['callbacks']);
        }

        try {
            $result = $this->connection()->delete($this->table, [$this->primaryKey => $this->id]);
            $entity->deleted($result);
        } catch (\Exception $e) {
            if ($options['callbacks']) {
                $this->triggerCallback('onError', 'delete', [$e]);
            }
            $this->cancelTransaction($entity, $options, 'delete');
            throw $e;
        }

        if ($result) {
            if ($options['callbacks'] === true || $options['callbacks'] === 'after') {
                $this->triggerCallback('afterDelete', 'delete', [$entity, $options], false);
            }
            $this->commitTransaction($entity, $options, 'delete');
        } else {
            $this->cancelTransaction($entity, $options, 'delete');
        }

        return $result;
    }

    /**
     * Bulk deletes records, does not delete associated data, use model::delete for that.
     *
     * @param array $conditions e.g ('Article.status' => 'draft')
     * @return bool true or false
     */
    public function deleteAll(array $conditions): bool
    {
        return $this->connection()->delete($this->table, $conditions);
    }

    /**
     * Finder for find('first').
     *
     * @param \ArrayObject $options (conditions,fields, joins, order,limit, group, callbacks,etc)
     * @return \Origin\Model\Entity|null
     */
    protected function finderFirst(ArrayObject $options): ?Entity
    {
        // Modify Query
        $options['limit'] = 1;

        // Run Query
        $collection = (new Finder($this))->find($options);

        if (empty($collection)) {
            return null;
        }

        if ($options['callbacks'] === true) {
            $this->triggerCallback('afterFind', 'find', [$collection, $options], false);
        }

        // Modify Results
        return $collection->first();
    }

    /**
     * Finder for find('all').
     *
     * @param \ArrayObject $options (conditions,fields, joins, order,limit, group, callbacks,etc)
     * @return \Origin\Model\Collection|array
     */
    protected function finderAll(ArrayObject $options)
    {
        // Run Query
        $collection = (new Finder($this))->find($options);

        // Modify Results
        if (empty($collection)) {
            return [];
        }

        if ($options['callbacks'] === true) {
            $this->triggerCallback('afterFind', 'find', [$collection, $options], false);
        }

        return $collection;
    }

    /**
     * Finder for find('list')
     *  3 different list types ['a','b','c'] or ['a'=>'b'] or ['c'=>['a'=>'b']] depending upon how many columns are selected. If more than 3 columns selected it returns ['a'=>'b'].
     *
     * @param \ArrayObject $options (conditions,fields, joins, order,limit, group, callbacks,etc)
     * @return array $results
     */
    protected function finderList(ArrayObject $options): array
    {
        if (empty($options['fields'])) {
            $options['fields'][] = $this->primaryKey;
            if ($this->displayField) {
                $options['fields'][] = $this->displayField;
            }
        }

        // Run Query
        $results = (new Finder($this))->find($options, 'list');

        // Modify Results
        if (empty($results)) {
            return [];
        }

        return $results;
    }

    /**
     * This is the count finder
     *
     * @param \ArrayObject $options (conditions,fields, joins, group, callbacks,etc)
     * @return int|array count
     */
    protected function finderCount(ArrayObject $options)
    {
        return $this->calculate('count', '*', (array) $options);
    }

    /**
     * Runs count, sum, average, minimum, and maximum queries
     *
     * @param string $operation
     * @param string $columnName
     * @param array $options
     * @return mixed
     */
    protected function calculate(string $operation, string $columnName, array $options = [])
    {
        // do not add default model order since this might caused sql errors on group queries
        $options = new ArrayObject($options + [
            'conditions' => null,
            'fields' => [],
            'joins' => [],
            'order' => null,
            'limit' => null,
            'group' => null,
            'page' => null,
            'offset' => null,
            'callbacks' => true,
            'associated' => [],
        ]);
        
        if ($options['callbacks'] === true) {
            if ($this->triggerCallback('beforeFind', 'find', [$options]) === false) {
                return null;
            }
        }

        $options = $this->prepareQuery('all', $options); // AutoJoin

        $operationMap = [
            'count' => 'COUNT','sum' => 'SUM','average' => 'AVG','minimum' => 'MIN','maximum' => 'MAX'
        ];
        if (! isset($operationMap[$operation])) {
            throw new Exception('Invalid Operation ' . $operation);
        }

        $options['fields'] = ["{$operationMap[$operation]}({$columnName}) AS {$operation}"];

        if ($options['group']) {
            $options['fields'] = array_merge($options['fields'], (array) $options['group']);
        }

        $results = (new Finder($this))->find($options, 'assoc');
    
        /**
         * handle results for group and none groups
         */
        if (empty($options['group'])) {
            $results = ctype_digit((string) $results[0][$operation]) ? (int) $results[0][$operation] : (float) $results[0][$operation];
        } else {
            $results = $results ? $results : [];
        }
 
        return $results;
    }

    /**
     * Add default keys, auto join models etc.
     *
     * @param string $type e.g. first,all,list, count
     * @param \ArrayObject $query
     * @return ArrayObject $query
     */
    protected function prepareQuery(string $type, ArrayObject $query): ArrayObject
    {
        if ($type === 'first' || $type === 'all') {
            $query['fields'] = empty($query['fields']) ? $this->fields() : $this->prepareFields($query['fields']);
        }

        $query['associated'] = $this->associatedConfig($query);
        foreach (['belongsTo', 'hasOne'] as $association) {
            foreach ($this->$association as $alias => $config) {
                if (isset($query['associated'][$alias])) {
                    $fields = $config['fields']; // create copy before overwrite
                    $config = array_merge($config, $query['associated'][$alias]); /// fields

                    $query['joins'][] = [
                        'table' => $this->$alias->table,
                        'alias' => Inflector::tableName($alias),
                        'type' => ($association === 'belongsTo' ? $config['type'] : 'LEFT'),
                        'conditions' => $config['conditions'],
                        'connection' => $this->connection,
                    ];

                    /**
                     * If the value is null add, but not an empty array
                     */
                    if ($config['fields'] === null) {
                        $config['fields'] = empty($fields) ? $this->$alias->fields() : $fields;
                    }

                    if ($config['fields']) {
                        // If it throw an error, then it can be confusing to know source, so turn to array
                        $query['fields'] = array_merge((array) $query['fields'], (array) $config['fields']);
                    }
                }
            }
        }

        return $query;
    }

    /**
     * Standardizes the config for eager loading of related
     * data
     *
     * @param ArrayObject $query
     * @return array
     */
    protected function associatedConfig(ArrayObject $query): array
    {
        $out = [];
        foreach ((array) $query['associated'] as $alias => $config) {
            if (is_int($alias)) {
                $alias = $config;
                $config = [];
            }
            $config += ['fields' => null];
            $tableAlias = Inflector::tableName($alias);
            
            if ($config['fields']) {
                foreach ($config['fields'] as $key => $value) {
                    // be flexible for custom joins or renaming a column
                    $addPrefix = strpos($value, '.') === false && strpos($value, ' ') === false;
                    $config['fields'][$key] = $addPrefix ? "{$tableAlias}.{$value}" : $value;
                }
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
            if (isset($this->$association[$name])) {
                return $this->$association[$name];
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
     * @return \Origin\Model\Connection
     */
    public function connection(): Connection
    {
        return ConnectionManager::get($this->connection);
    }

    /**
     * Checks values in an entity are unique, this could be that a username is not already
     * taken or an email is not used twice
     * @param \Origin\Model\Entity $entity
     * @param array  $fields array of fields to check values in entity
     * @return bool
     */
    public function isUnique(Entity $entity, array $fields = []): bool
    {
        $conditions = [];
        foreach ($fields as $field) {
            $conditions[$field] = null;
            if (isset($entity->$field)) {
                $conditions[$field] = $entity->$field;
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
    public function new(array $requestData = null, array $options = []): Entity
    {
        $entityClass = $this->entityClass($this);

        if ($requestData === null) {
            return new $entityClass([], ['name' => $this->alias]);
        }
        $options += ['name' => $this->alias, 'associated' => true];
        $options['associated'] = $this->normalizeAssociated($options['associated']);

        return $this->marshaller()->one($requestData, $options);
    }

    /**
     * Creates many Entities from an array of data.
     *
     * @param array $requestData
     * @param array $options parse default is set to true
     * @var array
     */
    public function newEntities(array $requestData, array $options = []): array
    {
        $options += ['name' => $this->alias, 'associated' => true];
        $options['associated'] = $this->normalizeAssociated($options['associated']);
    
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
    public function patch(Entity $entity, array $requestData, array $options = []): Entity
    {
        $options += ['name' => $this->alias,'associated' => true];
        $options['associated'] = $this->normalizeAssociated($options['associated']);
    
        return $this->marshaller()->patch($entity, $requestData, $options);
    }

    /**
     * Gets the Marshaller object.
     *
     * @var \Origin\Model\Marshaller
     */
    protected function marshaller(): Marshaller
    {
        return new Marshaller($this);
    }
    
    /**
     * Register a before find callback
     *
     * @param string $method
     * @param array $options (no options for this callback)
     * @return void
     */
    public function beforeFind(string $method, array $options = []): void
    {
        $options['on'] = 'find';
        $this->registerCallback('beforeFind', $method, $options);
    }

    /**
     * Register an after find callback
     *
     * @param string $method
     * @param array $options (no options for this callback)
     * @return void
     */
    public function afterFind(string $method, array $options = []): void
    {
        $options['on'] = 'find';
        $this->registerCallback('afterFind', $method, $options);
    }

    /**
     * Register a before validate callback
     *
     * @param string $method
     * @param array $options The options array supports the following keys:
     *   - on: default: ['create','update'] which events to run on create, update.
     * @return void
     */
    public function beforeValidate(string $method, array $options = []): void
    {
        $options += ['on' => ['create','update']];
        $this->registerCallback('beforeValidate', $method, $options);
    }

    /**
     * Register an after validate callback
     *
     * @param string $method
     * @param array $options The options array supports the following keys:
     *   - on: default: ['create','update'] which events to run on create, update.
     * @return void
     */
    public function afterValidate(string $method, array $options = []): void
    {
        $options += ['on' => ['create','update']];
        $this->registerCallback('afterValidate', $method, $options);
    }

    /**
     * Register a before create callback
     *
     * @param string $method
     * @param array $options (no options for this callback)
     * @return void
     */
    public function beforeCreate(string $method, array $options = []): void
    {
        $options['on'] = 'create';
        $this->registerCallback('beforeCreate', $method, $options);
    }

    /**
    * Register an after create callback
    *
    * @param string $method
    * @param array $options (no options for this callback)
    * @return void
    */
    public function afterCreate(string $method, array $options = []): void
    {
        $options['on'] = 'create';
        $this->registerCallback('afterCreate', $method, $options);
    }

    /**
     * Register a before update callback
     *
     * @param string $method
     * @param array $options (no options for this callback)
     * @return void
     */
    public function beforeUpdate(string $method, array $options = []): void
    {
        $options['on'] = 'update';
        $this->registerCallback('beforeUpdate', $method, $options);
    }

    /**
    * Register an after update callback
    *
    * @param string $method
    * @param array $options (no options for this callback)
    * @return void
    */
    public function afterUpdate(string $method, array $options = []): void
    {
        $options['on'] = 'update';
        $this->registerCallback('afterUpdate', $method, $options);
    }

    /**
     * Register a before save callback
     *
     * @param string $method
     * @param array $options The options array supports the following keys:
     *   - on: default: ['create','update'] which events to run on create, update.
     * @return void
     */
    public function beforeSave(string $method, array $options = []): void
    {
        $options += ['on' => ['create','update']];
        $this->registerCallback('beforeSave', $method, $options);
    }

    /**
    * Register an after save callback
    *
    * @param string $method
    * @param array $options The options array supports the following keys:
    *   - on: default: ['create','update'] which events to run on create, update.
    * @return void
    */
    public function afterSave(string $method, array $options = []): void
    {
        $options += ['on' => ['create','update']];
        $this->registerCallback('afterSave', $method, $options);
    }

    /**
    * Register a before delete callback
    *
    * @param string $method
    * @param array $options (no options for this callback)
    * @return void
    */
    public function beforeDelete(string $method, array $options = []): void
    {
        $options['on'] = 'delete';
        $this->registerCallback('beforeDelete', $method, $options);
    }

    /**
    * Register an after update callback
    *
    * @param string $method
    * @param array $options (no options for this callback)
    * @return void
    */
    public function afterDelete(string $method, array $options = []): void
    {
        $options['on'] = 'delete';
        $this->registerCallback('afterDelete', $method, $options);
    }

    /**
    * Register an after commit callback
    *
    * @param string $method
    * @param array $options The options array supports the following keys:
    *   - on: default: ['create','update','delete'] which events to run on create, update and delete
    * @return void
    */
    public function afterCommit(string $method, array $options = []): void
    {
        $options += ['on' => ['create','update','delete']];
        $this->registerCallback('afterCommit', $method, $options);
    }

    /**
    * Register an after commit callback
    *
    * @param string $method
    * @param array $options The options array supports the following keys:
    *   - on: default: ['create','update','delete'] which events to run on create, update and delete
    * @return void
    */
    public function afterRollback(string $method, array $options = []): void
    {
        $options += ['on' => ['create','update','delete']];
        $this->registerCallback('afterRollback', $method, $options);
    }

    /**
    * Register a callback for error handling
    *
    * @param string $method
    * @param array $options (no options for this callback)
    * @return void
    */
    public function onError(string $method, array $options = []): void
    {
        $options += ['on' => ['create','update','delete']];
        $this->registerCallback('onError', $method, $options);
    }

    /**
     * This is called when a callback is triggered, it looks up the registered callbacks and
     * then calls the dispatcher
     *
     * @param string $callback
     * @param string $event
     * @param array $arguments
     * @param boolean $isStoppable
     * @return boolean
     */
    protected function triggerCallback(string $callback, string $event, array $arguments = [], bool $isStoppable = true): bool
    {
        foreach ($this->registeredCallbacks($callback) as $method => $options) {
            if (! in_array($event, (array) $options['on'])) {
                continue;
            }
            $result = $this->dispatchCallback($method, $arguments, $isStoppable);
            if ($isStoppable && $result === false) {
                return false;
            }
        }
    
        return true;
    }

    /**
     * dispatches a Callback.
     *
     * @param string $callback
     * @param array $arguments
     * @param boolean $isStoppable
     * @return bool
     */
    protected function dispatchCallback(string $callback, array $arguments = [], bool $isStoppable = true): bool
    {
        $this->validateCallback($callback);
        if (call_user_func_array([$this,$callback], $arguments) === false && $isStoppable) {
            return false;
        }

        return true;
    }

    /**
     * Internal function for commiting a transaction, and triggering the callbacks
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @param string $event
     * @return void
     */
    private function commitTransaction(Entity $entity, ArrayObject $options, string $event): void
    {
        if ($options['transaction']) {
            $this->commit();
            if ($options['callbacks'] === true || $options['callbacks'] === 'after') {
                $this->triggerCallback('afterCommit', $event, [$entity, $options], false);
            }
        }
    }

    /**
     * Internal function for rollingback a transaction, and triggering the callbacks
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @param string $event
     * @return void
     */
    private function cancelTransaction(Entity $entity, ArrayObject $options, string $event): void
    {
        if ($options['transaction']) {
            $this->rollback();
            if ($options['callbacks'] === true || $options['callbacks'] === 'after') {
                $this->triggerCallback('afterRollback', $event, [$entity, $options], false);
            }
        }
    }

    /**
     * Gets the table name for this Model
     *
     * @return string
     */
    public function table(): string
    {
        return $this->table;
    }

    /**
     * Gets the name of this Model
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Gets the alias for this Model
     *
     * @return string
     */
    public function alias(): string
    {
        return $this->alias;
    }

    /**
     * Returns the ID of the record that is being saved, deleted or that has just
     * been created.
     *
     * @return int|string|null
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * Gets the primaryKey for this Model
     *
     * @return string $primaryKey
     */
    public function primaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * Gets the displayField for this Model
     *
     * @return string $primaryKey
     */
    public function displayField(): string
    {
        return $this->displayField;
    }
}
