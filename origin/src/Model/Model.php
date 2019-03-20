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
use Origin\Model\Behavior\BehaviorRegistry;
use Origin\Model\Exception\MissingModelException;
use Origin\Exception\NotFoundException;
use Origin\Exception\InvalidArgumentException;
use Origin\Core\Logger;
use Origin\Model\Collection;

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
     * This really should be id, because
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
     * @todo change to ->associations();
     *
     * @var array
     */
    protected $associations = [
      'belongsTo', 'hasMany','hasOne','hasAndBelongsToMany',
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
        if (!$this->displayField) {
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
            $object = new Model(array(
                        'name' => $className,
                        'table' => $this->hasAndBelongsToMany[$alias]['joinTable'],
                        'datasource' => $this->datasource,
                      ));

            if (count($object->fields()) === 2) {
                $object->primaryKey = $this->hasAndBelongsToMany[$alias]['foreignKey'];
            }

            ModelRegistry::set($name, $object);
        }

        if ($object) {
            $this->{$name} = $object;

            return true;
        }

        return false;
    }

    /**
     * Call the model lazyLoad and also detect displayField when called to
     * not have to call schema before every operation or on creation of model.
     *
     * @param string $name
     */
    public function __get($name)
    {
        if ($name === 'displayField') {
            $this->displayField = $this->detectDisplayField();

            return $this->displayField;
        }
        if (isset($this->{$name})) {
            return $this->{$name};
        }
    }

    public function __call(string $method, array $arguments)
    {
        // Runs behavior on first found method and returns result
        foreach ($this->behaviorRegistry()->enabled() as $Behavior) {
            if (method_exists($this->behaviorRegistry()->{$Behavior}, $method)) {
                return call_user_func_array(
                  array($this->behaviorRegistry()->{$Behavior}, $method),
                    $arguments
                );
            }
        }
        trigger_error('Call to undefined method '  .get_class($this) . '\\'.  $method .'()');
    }

    /**
     * Gets the display field if set or tries to detect it.
     *
     * @return string
     */
    protected function detectDisplayField()
    {
        $fields = array_keys($this->schema());

        $needles = array(
          Inflector::underscore($this->name).'_name',
          'name',
          'title',
          $this->primaryKey,
        );

        foreach ($needles as $needle) {
            if (in_array($needle, $fields)) {
                return $needle;
            }
        }

        return null;
    }

    /**
     * Gets the association relationship from outside the model.
     *
     * @param string $name hasOne|belongsTo etc
     *
     * @return array
     */
    public function association(string $name)
    {
        if (in_array($name, $this->associations())) {
            return $this->{$name};
        }

        return null;
    }

    /**
     * Gets the assoc list. [hasMany,belongsTo..].
     *
     * @return array
     */
    public function associations()
    {
        return $this->associations;
    }

    /**
     * Hook to call just after model creation.
     */
    public function initialize(array $config)
    {
    }

    public function behaviorRegistry()
    {
        return $this->behaviorRegistry;
    }
    /**
     * Loads a model behavior
     *
     * @param string $name
     * @param array $config
     * @return void
     */
    public function loadBehavior(string $name, array $config = [])
    {
        list($plugin, $behavior) = pluginSplit($name);
        $config = array_merge(['className' => $name.'Behavior'], $config);
        $this->{$behavior} = $this->behaviorRegistry()->load($name, $config);
        return $this->{$behavior};
    }

    /**
     * This will load any model regardless if it is associated or
     * not.
     * If you are loading a model with same name like in a plugin, then best set a unique
     * alias.
     * example:
     * $this->loadModel('CustomModel2',['className'=>'Plugin.CustomModel']);
     * $results = $this->CustomModel2->find('all');
     * @param string $model
     * @param array $config
     * @return Model
     */
    public function loadModel(string $name, array $config=[])
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
     * JOINING MODELS TOGETHER - These functions help do that if models and fields
     * are named properly. Models should be CamelCase and foreign keys should be
     * underscored_model_id and the primary key field should be id. Whilst we can easily
     * use the setting from this->primaryKey we would have to load the other model. At this
     * stage we wont do this. So magic only works if you follow the conventions, if not you
     * have to manually create the params.
     */

    /**
     * Creates a hasOne relationship. By default we assume that naming standards
     * are followed using primary key as id. Anything else then you have set the
     * options manually (even if you change the primary key setting).
     *
     * @param string $association e.g Comment
     * @param array  $options     (className, foreignKey, conditions, fields, order, dependent)
     */
    public function hasOne(string $association, $options = [])
    {
        $options += [
          'className' => $association,
          'foreignKey' => null,
          'conditions' => null,
          'fields' => null,
          'order' => null,
          'dependent' => false,
        ];
  
        if (is_null($options['foreignKey'])) {
            $options['foreignKey'] = Inflector::underscore($this->name).'_id';
        }
        $conditions = array(
          "{$this->alias}.id = {$association}.{$options['foreignKey']}",
          );
        if (!empty($options['conditions'])) {
            $conditions = array_merge($conditions, (array) $options['conditions']);
        }
        $options['conditions'] = $conditions;
        $this->hasOne[$association] = $options;

        return $options;
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
     * @param array  $options     (className, foreignKey, conditions, fields, order, type)
     */
    public function belongsTo(string $association, $options = [])
    {
        $defaults = array(
          'className' => $association,
          'foreignKey' => null,
          'conditions' => null,
          'fields' => null,
          'order' => null,
          'type' => 'LEFT',
        );

        $options = array_merge($defaults, $options);

        if (is_null($options['foreignKey'])) {
            $options['foreignKey'] = Inflector::underscore($options['className']).'_id';
        }
        $conditions = array(
            "{$this->alias}.{$options['foreignKey']} = {$association}.id",
        );
        if (!empty($options['conditions'])) {
            $conditions = array_merge($conditions, (array) $options['conditions']);
        }
        $options['conditions'] = $conditions;

        $this->belongsTo[$association] = $options;

        return $options;
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
     * @param array  $options     (className, foreignKey (in other model), conditions, fields, order, dependent, limit,offset)
     */
    public function hasMany(string $association, $options = [])
    {
        $options += [
          'className' => $association,
          'foreignKey' => null,
          'conditions' => [],
          'fields' => null,
          'order' => null,
          'dependent' => false,
          'limit' => null,
          'offset' => null,
        ];

        if (is_null($options['foreignKey'])) {
            $options['foreignKey'] =  Inflector::underscore($this->name).'_id';
        }

        $this->hasMany[$association] = $options;

        return $options;
    }

    /**
     * Creates a hasAndBelongsToMany relationship. By default we assume that naming
     * standards are followed using primary key as id. Anything else then
     * you have set the options manually (even if you change the primary
     * key setting).
     *
     * className: name of model associating to other model
     * foreignKey: foreign key found in this model
     * associationForeignKey: foreign key for other model
     * with: name of JoinModel.  e.g ContactsTag (must be Alphabetical Order)
     * mode: replace or append. Default is replace.
     *
     * @param string $association e.g Comment
     * @param array  $options     (className, foreignKey (in other model), conditions, fields, order, dependent, limit)
     */
    public function hasAndBelongsToMany(string $association, $options = [])
    {
        $options += [
          'className' => $association,
          'joinTable' => null,
          'foreignKey' => null,
          'associationForeignKey' => null,
          'conditions' => null,
          'fields' => null,
          'order' => null,
          'dependent' => false,
          'limit' => null,
          'offset' => null,
          'with' => null,
          'mode' => 'replace',
        ];

        if ($options['mode'] !== 'append') {
            $options['mode'] = 'replace';
        }

        // join table in alphabetic order
        $models = array($this->name, $options['className']);
        sort($models);
        $models = array_values($models);

        $with = Inflector::pluralize($models[0]).$models[1];
        if (is_null($options['with'])) {
            $options['with'] = $with;
        }
        if (is_null($options['joinTable'])) {
            $options['joinTable'] = Inflector::pluralize(Inflector::underscore($options['with']));
        }
        if (is_null($options['foreignKey'])) {
            $options['foreignKey'] = Inflector::underscore($this->name).'_id';
        }
        if (is_null($options['associationForeignKey'])) {
            $options['associationForeignKey'] = Inflector::underscore($options['className']).'_id';
        }
        $conditions = array(
          "{$options['with']}.{$options['associationForeignKey']} = {$options['className']}.id",
        );
        if (!empty($options['conditions'])) {
            $conditions = array_merge($conditions, (array) $options['conditions']);
        }
        $options['conditions'] = $conditions;
        $this->hasAndBelongsToMany[$association] = $options;

        return $options;
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
     * $this->validate($validationRules);
     *
     * @param string/array $field
     * @param array $options
     * @return void
     *
     */
    public function validate($field, $options)
    {
        if (is_array($field)) {
            foreach ($field as $key => $value) {
                if (is_int($key)) {
                    $key = $value;
                    $value = [];
                }

                $this->validate($key, $value);
            }

            return;
        }
        $this->validator()->setRule($field, $options);
    }

    /**
     * Returns the field list for this model.
     *
     * @return array fields
     */
    public function fields($quote = true)
    {
        $schema = $this->schema();
        if (empty($schema)) {
            return null;
        }

        if ($quote === true) {
            return $this->prepareFields(array_keys($schema));
        }

        return array_keys($schema);
    }

    public function hasField(string $field)
    {
        $fieldSchema = $this->schema($field);

        return !empty($fieldSchema);
    }

    /**
     * Adds aliases to an array of fields. Skips fields that
     * 1. Have space example somefield AS anotherName
     * 2. Are a MySQL function example count,max,avg,quarter,date etc
     * 3. Already alaised Post.title.
     *
     * @param array $fields [description]
     *
     * @return array quotedFields
     */
    protected function prepareFields($fields)
    {
        foreach ($fields as $index => $field) {
            if (strpos($field, ' ') === false and strpos($field, '.') === false and strpos($field, '(') === false) {
                $fields[$index] = "{$this->alias}.{$field}";
            }
        }

        return $fields;
    }

    /**
     * loads the schema for this model or specificied field.
     *
     * @param string $field
     *
     * @return string|null|array $field;
     */
    public function schema(string $field = null)
    {
        if ($this->schema === null) {
            $this->schema =  $this->connection()->schema($this->table);
        }
        if ($field === null) {
            return $this->schema;
        }
        if (isset($this->schema[$field])) {
            return $this->schema[$field];
        }

        return null;
    }

    /**
     * Validates model data in the object.
     *
     * @param array $data
     * @return bool true or false
     */
    public function validates(Entity $data, bool $create = true)
    {
        return $this->validator()->validates($data, $create);
    }

    /**
     * Gets the model validator object and stores.
     *
     * @return \Origin\Model\ModelValidator
     */
    public function validator()
    {
        if (!isset($this->ModelValidator)) {
            $this->ModelValidator = new ModelValidator($this);
        }

        return $this->ModelValidator;
    }

  
    protected function processSave(Entity $entity, array $options = [])
    {
        $options += ['validate' => true, 'callbacks' => true, 'transaction' => true];

        $this->id = null;
        if ($entity->has($this->primaryKey)) {
            $this->id = $entity->{$this->primaryKey};
        }

        $exists = $this->exists($this->id);
   
        if ($options['validate'] === true) {
            if ($options['callbacks'] === true and !$this->triggerCallback('beforeValidate', [$entity])) {
                return false;
            }
            $validated = $this->validates($entity, !$exists);
            
            if ($options['callbacks'] === true) {
                $this->triggerCallback('afterValidate', [$entity,$validated]);
            }
 
            if (!$validated) {
                return false;
            }
        }
       
        if ($options['callbacks'] === true or $options['callbacks'] === 'before') {
            if (!$this->triggerCallback('beforeSave', [$entity, $options])) {
                return false;
            }
        }

        $hasAndBelongsToMany = [];
        foreach ($this->hasAndBelongsToMany as $alias => $habtm) {
            $needle = Inflector::pluralize(lcfirst($alias)); // ArticleTag -> articleTags
            if (isset($entity->{$needle}) and is_array($entity->{$needle})) {
                $hasAndBelongsToMany[$alias] = $entity->{$needle};
            }
        }
        
        /**
         * Only modified fields are saved. The values can be the same, but still counted as modified.
         */
        $columns = array_intersect(array_keys($this->schema()), $entity->modified());
       
        $data = $entity->extract($columns);
        
        /**
         * Data should not be objects or arrays. Invalidate any objects or array data
         * e.g. unvalidated datetime fields.
         */
        foreach ($data as $key => $value) {
            if (is_array($value) or is_object($value)) {
                $entity->setError($key, 'Invalid data');
            }
        }
        
        if (empty($data) or $entity->hasErrors()) {
            return false;
        }

        $result = null;

        // Don't save if only field set is id (e.g savingHABTM)
        if (count($data) > 1 or !isset($data[$this->primaryKey])) {
            $connection = $this->connection();
            if ($exists) {
                $result = $connection->update($this->table, $data, array(
                  $this->primaryKey => $this->id,
                ));
            } else {
                $result = $connection->insert($this->table, $data);
                $this->id = $connection->lastInsertId();
                $entity->{$this->primaryKey} = $this->id;
            }
        }

        if ($result) {
            if ($options['callbacks'] === true or $options['callbacks'] === 'after') {
                $this->triggerCallback('afterSave', [$entity, !$exists, $options]);
            }
        }

        if ($hasAndBelongsToMany) {
            $result = $this->saveHABTM($hasAndBelongsToMany, $options['callbacks']);
        }

        unset($data,$options);

        return $result;
    }

    /**
     * Saves a single field on the current record.
     *
     * @params int|string $primaryKey the id for the record
     * @param string $fieldName
     * @param mixed  $fieldValue
     * @param array  $options    (callbacks, validate,transaction)
     *
     * @return bool true or false
     */
    public function saveField($primaryKey, string $fieldName, $fieldValue, array $options = [])
    {
        return $this->save(new Entity([
            $this->primaryKey => $primaryKey,
            $fieldName => $fieldValue,
        ]), $options);
    }

    /**
     * Updates one or many records at time, no callbacks are called.
     *
     * @param array $data       array(field=>$value)
     * @param array $conditions
     *
     * @return bool true or false
     */
    public function updateAll(array $data, array $conditions)
    {
        return $this->connection()->update($this->table, $data, $conditions);
    }

    protected function saveHABTM(array $hasAndBelongsToMany, bool $callbacks)
    {
        $connection = $this->connection();

        foreach ($hasAndBelongsToMany as $association => $data) {
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

                $tag = $this->{$association}->find('first', array(
                  'conditions' => array($needle => $row->get($needle)),
                  'callbacks' => false,
                ));

                if ($tag) {
                    $links[] = $tag->get($primaryKey);
                } else {
                    if (!$this->{$association}->save($row, array(
                      'callbacks' => $callbacks,
                      'transaction' => false,
                    ))) {
                        return false;
                    }
                    $links[] = $this->{$association}->id;
                }

                $joinModel = $this->{$config['with']};
            }
            $existingJoins = $joinModel->find('list', array(
                'conditions' => array($config['foreignKey'] => $this->id),
                'fields' => array($config['associationForeignKey']),
              ));

            $connection = $joinModel->connection();
            // By adding ID field we can do delete callbacks
            if ($config['mode'] === 'replace') {
                $connection->delete($config['joinTable'], array(
                      $config['foreignKey'] => $this->id,
                  ));
            } else {
                $remove = array_diff($existingJoins, $links);
                if (!empty($remove)) {
                    $connection->delete($config['joinTable'], array(
                  $config['foreignKey'] => $this->id,
                  $config['associationForeignKey'] => $remove,
                ));
                }
            }

            foreach ($links as $linkId) {
                if ($config['mode'] === 'append' and in_array($linkId, $existingJoins)) {
                    continue;
                }
                $insertData = array(
                  $config['foreignKey'] => $this->id,
                  $config['associationForeignKey'] => $linkId,
                );

                if (!$connection->insert($joinModel->table, $insertData)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
       * Save model data to database, hasAndBelongsToMany will also be saved.
       *
       * # Options
       *
       * The options array can be passed with the following keys:
       *
       * - validate: wether to validate data or not
       * - callbacks: call the callbacks duing each stage.  You can also put only before or after
       * - transaction: wether to save through a database transaction (default:true)
       * - associated: an array of associated data to save as well
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
       * @param array  $options keys include:
       *
       * @return bool true or false
       */
    public function save(Entity $data, $options = [])
    {
        $options += ['validate' => true, 'callbacks' => true, 'transaction' => true,'associated'=>[]];
      
        $associatedOptions = ['transaction' => false] + $options;

        if ($options['transaction']) {
            $this->begin();
        }
        $id = false;
        $result = true;
        // Save BelongsTo
        foreach ($this->belongsTo as $alias => $config) {
            if (!in_array($alias, $options['associated'])) {
                continue;
            }
            $key = lcfirst($alias);
            if ($data->has($key) and $data->{$key} instanceof Entity and $data->{$key}->modified()) {
                if (!$this->{$alias}->save($data->{$key}, $associatedOptions)) {
                    $result = false;
                    break;
                }
                $foreignKey = $this->belongsTo[$alias]['foreignKey'];
                $data->$foreignKey = $this->{$alias}->id;
            }
        }

        if ($result) {
            $result = $this->processSave($data, $options);
        }

        if ($result) {
            foreach ($this->hasOne as $alias => $config) {
                if (!in_array($alias, $options['associated'])) {
                    continue;
                }
                $key = lcfirst($alias);
                if ($data->has($key) and $data->{$key} instanceof Entity and $data->{$key}->modified()) {
                    $foreignKey = $this->hasOne[$alias]['foreignKey'];
                    $data->{$key}->{$foreignKey} = $this->id;

                    if (!$this->{$alias}->save($data->get($key), $associatedOptions)) {
                        $result = false;
                        break;
                    }
                }
            }

            // Save hasMany
            foreach ($this->hasMany as $alias => $config) {
                if (!in_array($alias, $options['associated'])) {
                    continue;
                }
                
                $key = Inflector::pluralize(lcfirst($alias));
               
                if ($data->has($key)) {
                    $foreignKey = $this->hasMany[$alias]['foreignKey'];
                    
                    foreach ($data->get($key) as $record) {
                        if ($record instanceof Entity and $record->modified()) {
                            $record->$foreignKey = $data->{$this->primaryKey};
                            if (!$this->{$alias}->save($record, $associatedOptions)) {
                                $result = false;
                                break;
                            }
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
     * @param entity|array $data    to save =
     *                              array(
        *                              $entity,
        *                              $entity
     *                              );
     * @param array        $options keys include:
     *                              validate: wether to validate data or not
     *                              callbacks: call the callbacks duing each stage.  You can also put only before or after
     *                              transaction: if set true, the save will be as a transaction and rolledback upon
     *                              any errors. If false, then it will just save what it can
     *
     * @return bool true or false
     */
    public function saveMany(array $data, array $options = [])
    {
        if (empty($data)) {
            return false;
        }
        $defaults = ['validate' => true, 'callbacks' => true, 'transaction' => true];
        $options = array_merge($defaults, $options);

        if ($options['transaction']) {
            $this->begin();
        }
        $result = true;
        foreach ($data as $key => $row) {
            if (!$this->save($row, array('transaction' => false) + $options)) {
                $result = false;
            }
        }

        if ($result and $options['transaction']) {
            $this->commit();
        }

        if (!$result and $options['transaction']) {
            $this->rollback();
        }

        return $result;
    }

    /**
     * Checks if the record exists using the primaryKey.
     *
     * @param int|string $id
     *
     * @return bool true if the record exists
     */
    public function exists($id = null)
    {
        if ($id === null) {
            return false;
        }

        return (bool) $this->find('count', array(
        'conditions' => array("{$this->alias}.{$this->primaryKey}" => $id),
        'callbacks' => false
      ));
    }

    /**
     * PSR friendly find by id.
     *
     * @param int|string $id      id of record to fetch
     * @param array $options  (conditions, fields, joins, order,limit, group, callbacks,contain)
     *
     * @return \Origin\Model\Entity
     */
    public function get($id, array $options = [])
    {
        $options += ['conditions' => []];

        $options['conditions'][$this->primaryKey] = $id;
        if ($result = $this->find('first', $options)) {
            return $result;
        }
        throw new NotFoundException(sprintf('Record not found in %s table with the primary key %s', $this->table, $id));
    }

    /**
     * The R in CRUD.
     *
     * @param string $type  (first,all,count,list)
     * @param array  $query  (conditions, fields, joins, order,limit, group, callbacks,contain)
     * @return \Origin\Model\Entity|\Origin\Model\Collection|array|int $resultSet
     */
    public function find(string $type = 'first', $options = [])
    {
        $default = array(
             'conditions' => null,
             'fields' => [],
             'joins' => [],
             'order' => $this->order,
             'limit' => null,
             'group' => null,
             'page' => null,
             'offset' => null,
             'callbacks' => true,
             'associated' => []
           );

        $options = array_merge($default, $options);

        if ($options['callbacks'] === true) {
            $options = $this->triggerCallback('beforeFind', [$options], true);
        }

        $options = $this->prepareQuery($type, $options); // AutoJoin

        $results = $this->{'finder'.ucfirst($type)}($options);

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
     * @param bool           $cascade   delete hasOne,hasMany, hasAndBelongsToMany records
     * @param bool           $callbacks call beforeDelete and afterDelete callbacks
     *
     * @return bool true or false
     */
    public function delete(Entity $entity, $cascade = true, $callbacks = true)
    {
        $this->id = $entity->get($this->primaryKey);

        if (empty($this->id) or !$this->exists($this->id)) {
            return false;
        }

        if ($callbacks) {
            if (!$this->triggerCallback('beforeDelete', [$entity,$cascade])) {
                return false;
            }
        }

        $this->deleteHABTM($this->id);
        if ($cascade) {
            $this->deleteDependent($this->id);
        }

        $result = $this->connection()->delete($this->table, [$this->primaryKey => $this->id]);

        if ($callbacks) {
            $this->triggerCallback('afterDelete', [$entity,$result]);
        }
        if ($result) {
            $entity->exists(false);
        }
        return $result;
    }

    /**
     * Deletes the hasOne and hasMany associated records.
     *
     * @var int|string
     */
    protected function deleteDependent($id)
    {
        foreach (array_merge($this->hasOne, $this->hasMany) as $association => $config) {
            if (isset($config['dependent']) and $config['dependent'] === true) {
                $conditions = [$config['foreignKey'] => $id];
                $ids = $this->{$association}->find('list', [ 'conditions' => $conditions]);
                foreach ($ids as $id) {
                    $conditions = [$this->{$association}->primaryKey => $id];
                    $result = $this->{$association}->find('first', ['conditions'=>$conditions,'callbacks'=>false]);
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
     * @var int|string
     */
    protected function deleteHABTM($id)
    {
        foreach ($this->hasAndBelongsToMany as $association => $config) {
            $associatedModel = $config['with'];
            $conditions = [$config['foreignKey'] => $id];
            $ids = $this->$associatedModel->find('list', ['conditions' => $conditions]);
            foreach ($ids as $id) {
                $conditions = [$this->{$associatedModel}->primaryKey => $id];
                $result = $this->{$associatedModel}->find('first', ['conditions'=>$conditions,'callbacks'=>false]);
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
     *
     * @return bool true or false
     */
    public function deleteAll($conditions)
    {
        return $this->connection()->delete($this->table, $conditions);
    }

    /**
     * Finder for find('first').
     *
     * @param array $query (conditions,fields, joins, order,limit, group, callbacks,etc)
     *
     * @return array results
     */
    protected function finderFirst($query = [])
    {
        // Modify Query
        $query['limit'] = 1;

        // Run Query
        $results = $this->readDataSource($query);

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
     *
     * @return \Origin\Model\Collection
     */
    protected function finderAll($query)
    {
        // Run Query
        $results = $this->readDataSource($query);

        // Modify Results
        if (empty($results)) {
            return [];
        }
        // return $results;
        return new Collection($results, ['name'=>$this->alias]);
    }

    /**
     * Finder for find('list')
     *  3 different list types ['a','b','c'] or ['a'=>'b'] or ['c'=>['a'=>'b']] depending upon how many columns are selected. If more than 3 columns selected it returns ['a'=>'b'].
     *
     * @param array $query (conditions,fields, joins, order,limit, group, callbacks,etc)
     *
     * @return array results
     */
    protected function finderList($query)
    {
        if (empty($query['fields'])) {
            $query['fields'][] = $this->primaryKey;
            if ($this->displayField) {
                $query['fields'][] = $this->displayField;
            }
        }

        // Run Query
        $results = $this->readDataSource($query, 'list');

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
     *
     * @return int count
     */
    protected function finderCount($query)
    {
        // Modify Query
        $query['fields'] = ['COUNT(*) AS count'];
        $query['order'] = null;
        $query['limit'] = null;

        // Run Query
        $results = $this->readDataSource($query, 'assoc');

        // Modify Results
        return $results[0]['count'];
    }

    /**
     * Add default keys, auto join models etc.
     *
     * @param array $query
     *
     * @return $query
     */
    protected function prepareQuery(string $type, array $query)
    {
        if (($type === 'first' or $type === 'all') and empty($query['fields'])) {
            $query['fields'] = $this->fields();
        }

        $query['associated'] = $this->associatedConfig($query);
     
        foreach (['belongsTo', 'hasOne'] as $association) {
            foreach ($this->{$association} as $alias => $config) {
                if (isset($query['associated'][$alias]) === false) {
                    continue;
                }

                if (!isset($this->{$alias})) {
                    throw new MissingModelException($config['className'].':'.$alias);
                }

                $config = array_merge($config, $query['associated'][$alias]);
              
                $query['joins'][] = array(
                    'table' => $this->{$alias}->table,
                    'alias' => $alias,
                    'type' => ($association === 'belongsTo' ? $config['type'] : 'LEFT'),
                    'conditions' => $config['conditions'],
                    'datasource' => $this->datasource,
                    );

                if (!empty($config['order'])) {
                    if ($query['order'] === null) {
                        $query['order'] = [];
                    }
                    $query['order'][] = $config['order'];
                }

                if (empty($config['fields'])) {
                    $config['fields'] = $this->{$alias}->fields();
                }
                // If throw an error, then it can be confusing to know source, so turn to array
                $query['fields'] = array_merge((array) $query['fields'], (array) $config['fields']);
            }
        }

        return $query;
    }

    /**
     * Standardizes the config for eager loading of related
     * data
     *
     * @param array $query
     * @return void
     */
    protected function associatedConfig(array $query)
    {
        $contain = [];
        foreach ((array) $query['associated'] as $alias => $config) {
            if (is_int($alias)) {
                $alias = $config;
                $config = [];
            }
            if (isset($config['fields'])) {
                foreach ($config['fields'] as $key => $value) {
                    $config['fields'][$key] = "{$alias}.{$value}";
                }
            }
            $contain[$alias] = $config;
           
            if (!$this->findAssociation($alias)) {
                throw new InvalidArgumentException("{$this->name} is not associated with {$alias}.");
            }
        }
        return $contain;
    }

    /**
     * Searches for the associations
     *
     * @param string $name
     * @return void
     */
    protected function findAssociation(string $name)
    {
        foreach ($this->associations as $association) {
            if (isset($this->{$association}[$name])) {
                return $this->{$association}[$name];
            }
        }
        return null;
    }

    /**
     * Takes results from the datasource and converts into an entity. Different
     * from model::new which takes an array which can include hasMany
     * and converts.
     *
     * @param array $results results from datasource
     *
     * @return Entity
     */
    protected function prepareResults(array $results)
    {
        $buffer = [];
        foreach ($results as $record) {
            $thisData = (isset($record[$this->alias])?$record[$this->alias]:[]); // Work with group and no fields from db
            $entity = new Entity($thisData, ['name'=>$this->alias,'exists'=>true]);
            unset($record[$this->alias]);
           
            foreach ($record as $model => $data) {
                if (is_string($model)) {
                    $associated = Inflector::variable($model);
                    $entity->{$associated} = new Entity($data, ['name'=>$associated,'exists'=>true]);
                } else {
                    /**
                     * Any data is here is not matched to model, e.g. group by and non existant fields
                     * add them to model so we can put them in entity nicely. This seems to be cleanest solution
                     * the resulting entity might not contain any real data from the entity.
                     */
                    foreach ($data as $k => $v) {
                        $entity->{$k} = $v;
                    }
                }
            }

            $buffer[] = $entity;
        }
        return $buffer;
    }
   
    protected function loadAssociatedHasMany($query, $results)
    {
        foreach ($this->hasMany as $alias => $config) {
            if (isset($query['associated'][$alias]) === false) {
                continue;
            }

            if (!isset($this->{$alias})) {
                throw new MissingModelException($config['className'].':'.$alias);
            }

            $config = array_merge($config, $query['associated'][$alias]);
        
            if (empty($config['fields'])) {
                $config['fields'] = $this->{$alias}->fields();
            }
            
            foreach ($results as $index => &$result) {
                if (isset($result->{$this->primaryKey})) {
                    $config['conditions']["{$alias}.{$config['foreignKey']}"] = $result->{$this->primaryKey};
                    $models = Inflector::pluralize(Inflector::variable($alias));
                    $result->{$models} = $this->{$alias}->find('all', $config);
                }
            }
        }

        return $results;
    }

    protected function loadAssociatedHasAndBelongsToMany($query, $results)
    {
        foreach ($this->hasAndBelongsToMany as $alias => $config) {
            if (isset($query['associated'][$alias]) === false) {
                continue;
            }

            if (!isset($this->{$alias})) {
                throw new MissingModelException($config['className'] . ':' . $alias);
            }

            $config = array_merge($config, $query['associated'][$alias]);

            $config['joins'][0] = array(
              'table' => $config['joinTable'],
              'alias' => $config['with'],
              'type' => 'INNER',
              'conditions' => $config['conditions'],
            );
            $config['conditions'] = [];
            if (empty($config['fields'])) {
                $config['fields'] = array_merge(
                $this->{$alias}->fields(),
                $this->{$config['with']}->fields()
              );
            }

            foreach ($results as $index => &$result) {
                if (isset($result->{$this->primaryKey})) {
                    $config['joins'][0]['conditions']["{$config['with']}.{$config['foreignKey']}"] = $result->{$this->primaryKey};
                }

                $models = Inflector::pluralize(Inflector::variable($alias));
                $result->{$models} = $this->{$alias}->find('all', $config);
            }
        }

        return  $results;
    }

    /**
     * Reads the datasource using query array and returns the result set.
     *
     * @param string $type
     * @param array  $query (conditions,joins,fields,order,limit etc)
     *
     * @return array|\Origin\Model\Entity|\Origin\Model\Collection
     */
    protected function readDataSource(array $query, $type = 'model')
    {
        $QueryBuilder = new QueryBuilder($this->table, $this->alias);
        $sql = $QueryBuilder->selectStatement($query);
    
        $connection = $this->connection();
       
        $connection->execute($sql, $QueryBuilder->getValues());

        if ($type == 'list') {
            return $connection->fetchList();
        }
        

        $results = $connection->fetchAll($type);

        if ($results and $type === 'model') {
            $results = $this->prepareResults($results);

            $results = $this->loadAssociatedHasMany($query, $results);
            $results = $this->loadAssociatedHasAndBelongsToMany($query, $results);
        }

        unset($QueryBuilder,$sql,$connection);

        return $results;
    }

    /**
     * Runs a query and returns the result set if there are any
     * if not returns true or false.
     *
     * @param string $sql
     * @param array  $params bind values
     *
     * @return bool
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
    public function connection() : Datasource
    {
        return ConnectionManager::get($this->datasource);
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
     *
     * @return bool
     */
    public function isUnique(Entity $entity, array $fields = [])
    {
        $conditions = [];
        foreach ($fields as $field) {
            if (isset($entity->{$field})) {
                $conditions[$field] = $entity->{$field};
            }
        }
        return $this->find('count', array('conditions' => $conditions)) === 0;
    }

    public function begin()
    {
        return $this->connection()->begin();
    }

    public function commit()
    {
        return $this->connection()->commit();
    }

    public function rollback()
    {
        return $this->connection()->rollBack();
    }

    /**
     * Creates an instance of an Entity
     *
     *
     * @param array $requestData
     * @param array $options
     * @return \Origin\Model\Entity
     */
    public function new(array $requestData = [], array $options=[])
    {
        $options += ['name' => $this->alias];
        return $this->marshaller()->one($requestData, $options);
    }

    /**
     * Creates many Entities from an array of data.
     *
     * @param array $data
     * @param array $options parse default is set to true
    * @var \Origin\Model\Collection
     */
    public function newEntities(array $requestData, array $options=[])
    {
        $options += ['name' => $this->alias];

        return new Collection($this->marshaller()->many($requestData, $options));
    }

    /**
     * Patches an existing entity with requested data
     *
     * @param Entity $entity
     * @param array  $requestData
     * @param array $options parse
     * @var \Origin\Model\Entity
     */
    public function patch(Entity $entity, array $requestData, array $options=[])
    {
        return $this->marshaller()->patch($entity, $requestData, $options);
    }

    /**
     * Gets the Marshaller object.
     *
     * @var \Origin\Model\Marshaller
     */
    protected function marshaller()
    {
        if ($this->marshaller === null) {
            $this->marshaller = new Marshaller($this);
        }

        return $this->marshaller;
    }

    /**
     * triggerCallback.
     *
     * @param string $callback   [description]
     * @param array  $arguments  [description]
     * @param bool   $passedArgs if result is array overwrite
     *
     * @return [type] [description]
     */
    protected function triggerCallback(string $callback, $arguments = [], $passedArgs = false)
    {
        $callbacks = array(
            array($this, $callback),
        );

        foreach ($this->behaviorRegistry()->enabled() as $behavior) {
            $callbacks[] = array($this->{$behavior}, $callback);
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
        unset($callbacks,$result);

        if ($passedArgs) {
            return $arguments[0]; // was if not exist return result
        }

        return true;
    }

    /**
     * Returns a Logger Object
     *
     * @param string $channel
     * @return \Origin\Core\Logger
     */
    public function logger(string $channel = 'Model')
    {
        return new Logger($channel);
    }

    /**
     * Enables a behavior that has been disabled
     *
     * @param string $name
     * @return bool
     */
    public function enableBehavior(string $name)
    {
        return $this->behaviorRegistry->enable($name);
    }
    /**
     * Disables a behavior
     *
     * @param string $name
     * @return bool
     */
    public function disableBehavior(string $name)
    {
        return $this->behaviorRegistry->disable($name);
    }
}
