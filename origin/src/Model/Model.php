<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
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

class Model
{
    public $name = null;
    public $alias = null;

    /**
     * Database configuration to use by model.
     *
     * @var string
     */
    public $datasource = 'default';

    public $table = null;

    public $primaryKey = null;

    public $displayField = null;

    /**
     * Level to get records
     * -1 no joins
     * 0 record with domain
     * 1 same as 0 but with related records
     * 2 same but with related related.
     *
     * @var int
     */
    public $recursive = -1; // ≈

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
     * Default order when finding.
     *
     * @var string
     */
    public $order = null;

    /**
     * @todo change to ->associations();
     *
     * @var array
     */
    protected $associations = array(
      'hasOne', 'belongsTo', 'hasMany', 'hasAndBelongsToMany',
    );

    /**
     * The column data describing the table.
     *
     * @var array
     */
    protected $schema = null;

    /**
     * The ID of the last record created or updated.
     *
     * @var mixed
     */
    public $id = null;

    /**
     * Validation rules.
     *
     * @var array
     */
    protected $validate = [];

    public function __construct(array $config = [])
    {
        $defaults = array(
            'name' => $this->name,
            'alias' => $this->alias,
            'datasource' => $this->datasource,
            'table' => $this->table,
        );

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

        $this->behaviors = new BehaviorRegistry($this);

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

        foreach ($this->associations as $association) {
            if (isset($this->{$association}[$name]['className'])) {
                $className = $this->{$association}[$name]['className'];
                break;
            }
        }

        $habtmModel = false;
        if ($className === null) {
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

        $object = ModelRegistry::get(
            $name,
          array('className' => $className, 'alias' => $name)
        );
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
        foreach ($this->behaviors->enabled() as $Behavior) {
            if (method_exists($this->behaviors->{$Behavior}, $method)) {
                return call_user_func_array(
                  array($this->behaviors->{$Behavior}, $method),
                    $arguments
                );
            }
        }
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
        if (isset($this->{$name})) {
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

    public function loadBehavior(string $name, array $config = [])
    {
        $config = array_merge(['className' => $name.'Behavior'], $config);

        $this->{$name} = $this->behaviors->load($name, $config);
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
        $defaults = array(
          'className' => $association,
          'foreignKey' => null,
          'conditions' => null,
          'fields' => null,
          'order' => null,
          'dependent' => false,
        );
        $options = array_merge($defaults, $options);

        $foreignKey = Inflector::underscore($this->name).'_id';
        if (is_null($options['foreignKey'])) {
            $options['foreignKey'] = $foreignKey;
        }
        $conditions = array(
          "{$this->alias}.id = {$association}.{$foreignKey}",
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
        $foreignKey = Inflector::underscore($options['className']).'_id';
        if (is_null($options['foreignKey'])) {
            $options['foreignKey'] = $foreignKey;
        }
        $conditions = array(
            "{$this->alias}.{$foreignKey} = {$association}.id",
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
        $defaults = array(
          'className' => $association,
          'foreignKey' => null,
          'conditions' => array(),
          'fields' => null,
          'order' => null,
          'dependent' => false,
          'limit' => null,
          'offset' => null,
        );

        $options = array_merge($defaults, $options);
        $foreignKey = Inflector::underscore($this->name).'_id';
        if (is_null($options['foreignKey'])) {
            $options['foreignKey'] = $foreignKey;
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
     * with: name of JoinModel.  e.g ContactsTag (must be Alphabetical Order an)
     * mode: replace or append. Default is replace.
     *
     * @param string $association e.g Comment
     * @param array  $options     (className, foreignKey (in other model), conditions, fields, order, dependent, limit)
     */
    public function hasAndBelongsToMany(string $association, $options = [])
    {
        $defaults = array(
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
        );

        $options = array_merge($defaults, $options);

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

        $foreignKey = Inflector::underscore($this->name).'_id';
        $associationForeignKey = Inflector::underscore($options['className']).'_id';

        if (is_null($options['foreignKey'])) {
            $options['foreignKey'] = $foreignKey;
        }
        if (is_null($options['associationForeignKey'])) {
            $options['associationForeignKey'] = $associationForeignKey;
        }
        $conditions = array(
          "{$options['with']}.{$associationForeignKey} = {$options['className']}.id",
        );
        if (!empty($options['conditions'])) {
            $conditions = array_merge($conditions, (array) $options['conditions']);
        }
        $options['conditions'] = $conditions;
        $this->hasAndBelongsToMany[$association] = $options;

        return $options;
    }

    public function validate($field, array $options = [])
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

        if (is_string($options)) {
            $options = array(
              $field => array('rule' => $options, 'message' => 'Invalid data'),
            );
        }
        if (isset($options['rule'])) {
            $options = array('rule1' => $options);
        }
        $this->validate[$field] = $options;
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
     * 2. Are a MySql function example count,max,avg,quarter,date etc
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
     * @return string $field;
     */
    public function schema(string $field = null)
    {
        if ($this->schema === null) {
            $this->schema = [];

            $connection = $this->getConnection();

            $this->schema = $connection->schema($this->table);
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
     *
     * @return bool true or false
     */
    public function validates(Entity $data, bool $create = true)
    {
        $Validator = $this->validator();
        if ($Validator->validates($data, $create)) {
            return true;
        }

        return false;
    }

    /**
     * Gets the model validator object and stores.
     *
     * @return ModelValidator
     */
    public function validator()
    {
        if (!isset($this->ModelValidator)) {
            $this->ModelValidator = new ModelValidator($this, $this->validate);
        }

        return $this->ModelValidator;
    }

    /**
     * Save model data to databse.
     *
     * @param entity|array $entity  to save
     * @param array        $options keys include:
     *                              validate: wether to validate data or not
     *                              callbacks: call the callbacks duing each stage.  You can also put only before or after
     *
     * @return bool true or false
     */
    public function save(Entity $entity, array $options = [])
    {
        $defaults = array('validate' => true, 'callbacks' => true, 'transaction' => true);
        $options = array_merge($defaults, $options);

        if (empty($entity)) {
            return false;
        }
        $this->id = null;

        if ($entity->hasProperty($this->primaryKey) and $entity->{$this->primaryKey} !== null) {
            $this->id = $entity->{$this->primaryKey};
        }

        $exists = $this->exists($this->id);

        if ($options['validate'] === true) {
            if ($options['callbacks'] === true and !$this->triggerCallback('beforeValidate', [$entity])) {
                return false;
            }

            if ($this->validates($entity, !$exists) == false) {
                return false;
            }

            if ($options['callbacks'] === true) {
                $this->triggerCallback('afterValidate', [$entity]);
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
            if (isset($entity->{$needle})) {
                $hasAndBelongsToMany[$alias] = $entity->{$needle};
            }
        }

        $data = $entity->get(array_keys($this->schema()));

        /**
         * All data should be scalar. Invalidate any objects or array data e.g. unvalidated datetime fields.
         */
        $invalidData = false;
        foreach ($data as $key => $value) {
            if (!is_scalar($value)) {
                $entity->invalidate($key, 'Invalid data');
                $invalidData = true;
            }
        }

        if (empty($data) or $invalidData) {
            return false;
        }

        $result = null;

        // Don't save if only field set is id (e.g savingHABTM)
        if (count($data) > 1 or !isset($data[$this->primaryKey])) {
            $connection = $this->getConnection();
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

        $this->data = null;
        unset($data,$options);

        return $result;
    }

    /**
     * Saves a single field on the current record. Must set $this->id for this work.
     *
     * @param string $fieldName
     * @param mixed  $fieldValue [description]
     * @param array  $params     (callbacks, validate)
     *
     * @return bool true or false
     */
    public function saveField(string $fieldName, $fieldValue, array $params = [])
    {
        if (!$this->id) {
            return false;
        }

        return $this->save(new Entity([
            $this->primaryKey => $this->id,
            $fieldName => $fieldValue,
          ]), $params);
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
        $connection = $this->getConnection();

        return $connection->update($this->table, $data, $conditions);
    }

    protected function saveHABTM(array $hasAndBelongsToMany, bool $callbacks)
    {
        $connection = $this->getConnection();

        foreach ($hasAndBelongsToMany as $association => $data) {
            $config = $this->hasAndBelongsToMany[$association];
            $joinModel = $this->{$config['with']};

            $links = [];
            foreach ($data as $row) {
                $primaryKey = $this->{$association}->primaryKey;
                $displayField = $this->{$association}->displayField;

                // Either primaryKey or DisplayField must be set in data
                if ($row->hasProperty($primaryKey)) {
                    $needle = $primaryKey;
                } elseif ($row->hasProperty($displayField)) {
                    $needle = $displayField;
                } else {
                    return false;
                }

                $tag = $this->{$association}->find('first', array(
                  'conditions' => array($needle => $row->get($needle)),
                  'recursive' => -1,
                  'callbacks' => false,
                ));

                if ($tag) {
                    $links[] = $tag->get($primaryKey);
                } else {
                    $this->{$association}->create();
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

            $connection = $joinModel->getConnection();
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
     * Can save data with multiple associations.
     *
     * @param entity|array $data    record
     * @param array        $options (validate,callbacks,transaction)
     *
     * @return bool true or false
     */
    public function saveAssociated($data, $options = array())
    {
        if (empty($data)) {
            return false;
        }
        $defaults = array('validate' => true, 'callbacks' => true, 'transaction' => true);
        $options = array_merge($defaults, $options);

        $associatedOptions = array('transaction' => false) + $options;

        if ($options['transaction']) {
            $this->begin();
        }
        $result = true;
        // Save BelongsTo
        foreach ($this->belongsTo as $alias => $config) {
            $key = lcfirst($alias);
            if ($data->hasProperty($key) and $data->{$key} instanceof Entity) {
                if (!$this->{$alias}->save($data->{$key}, $associatedOptions)) {
                    $result = false;
                    break;
                }
                $foreignKey = $this->belongsTo[$alias]['foreignKey'];
                $data->$foreignKey = $this->{$alias}->id;
            }
        }

        if ($result) {
            $this->create();
            $result = $this->save($data, $options);
        }

        if ($result) {
            foreach ($this->hasOne as $alias => $config) {
                $key = lcfirst($alias);
                if ($data->hasProperty($key) and $data->{$key} instanceof Entity) {
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
                $key = Inflector::pluralize(lcfirst($alias));
                if ($data->hasProperty($key)) {
                    $foreignKey = $this->hasMany[$alias]['foreignKey'];
                    foreach ($data->get($key) as $record) {
                        if ($record instanceof Entity) {
                            $record->$foreignKey = $this->id;
                            $this->{$alias}->create();
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
     *                              array('title' => 'title 1'),
     *                              array('title' => 'title 2')
     *                              );
     * @param array        $options keys include:
     *                              validate: wether to validate data or not
     *                              callbacks: call the callbacks duing each stage.  You can also put only before or after
     *                              transaction: if set true, the save will be as a transaction and rolledback upon
     *                              any errors. If false, then it will just save what it can
     *
     * @return bool true or false
     */
    public function saveMany($data, array $options = [])
    {
        if (empty($data)) {
            return false;
        }
        $defaults = array('validate' => true, 'callbacks' => true, 'transaction' => true);
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
     * A wrappper for saveMany and saveAssociated.
     *
     * @param array|object $data   record or multiple records
     * @param array        $params (validate|callbacks|transaction)
     *
     * @return bool true or false
     */
    public function saveAll($data, array $params = [])
    {
        if (is_object($data) or array_keys($data) !== range(0, count($data) - 1)) {
            return $this->saveAssociated($data, $params);
        }

        return $this->saveMany($data, $params);
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
        'callbacks' => false,
        'recursive' => -1,
      ));
    }

    /**
     * PSR friendly find by id.
     *
     * @param int|string $id      id of record to fetch
     * @param array      $options [fields,order,conditions,recursive etc]
     *
     * @return result
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
     * @param array  $query (conditions,fields, joins, order,limit, group, callbacks,etc)
     *
     * @return object $resultSet
     */
    public function find(string $type = 'first', $options = [])
    {
        $default = array(
             'conditions' => null,
             'fields' => [],
             'joins' => array(),
             'order' => $this->order,
             'limit' => null,
             'group' => null,
             'page' => null,
             'offset' => null,
             'callbacks' => true,
             'recursive' => $this->recursive,
           );

        $options = array_merge($default, $options);

        $options = $this->prepareQuery($type, $options); // AutoJoin

        if ($options['callbacks'] === true) {
            $options = $this->triggerCallback('beforeFind', [$options], true);
        }

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
     * @param integer/string $id
     * @param bool           $cascade   delete hasOne,hasMany, hasAndBelongsToMany records
     * @param bool           $callbacks call beforeDelete and afterDelete callbacks
     *
     * @return bool true or false
     */
    public function delete($id, $cascade = true, $callbacks = true)
    {
        if (empty($id) or !$this->exists($id)) {
            return false;
        }

        $this->id = $id;
        if ($callbacks) {
            if (!$this->triggerCallback('beforeDelete', [$cascade])) {
                return false;
            }
        }

        $this->deleteHABTM($id);
        if ($cascade) {
            $this->deleteDependent($id);
        }

        $conditions = array($this->primaryKey => $id);

        $connection = $this->getConnection();
        $result = $connection->delete($this->table, $conditions);

        if ($callbacks) {
            $this->triggerCallback('afterDelete');
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
                $deleteConditions = array($config['foreignKey'] => $id);
                $this->{$association}->deleteAll($deleteConditions);
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
            $deleteConditions = array($config['foreignKey'] => $id);
            $this->{$config['with']}->deleteAll($deleteConditions, true, false);
        }
    }

    /**
     * Deletes multiple records.
     *
     * @param array $conditions e.g ('Article.status' => 'draft')
     * @param bool  $cascade    delete hasOne,hasMany, hasAndBelongsToMany records
     * @param bool  $callbacks  call beforeDelete and afterDelete callbacks
     *
     * @return bool true or false
     */
    public function deleteAll($conditions = [], $cascade = false, $callbacks = false)
    {
        if (empty($conditions)) {
            return false;
        }

        $ids = $this->find('list', array(
          'fields' => array($this->primaryKey),
          'conditions' => $conditions,
          'recursive' => -1, ));

        if (empty($ids)) {
            return false;
        }

        if ($callbacks === true) {
            foreach ($ids as $id) {
                if (!$this->delete($id, $cascade, $callbacks)) {
                    return false;
                }
            }

            return true;
        }

        foreach ($ids as $id) {
            $this->deleteHABTM($id);
            if ($cascade) {
                $this->deleteDependent($id);
            }
        }

        $connection = $this->getConnection();

        return $connection->delete($this->table, array($this->primaryKey => $ids));
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
     * @return array results
     */
    protected function finderAll($query)
    {
        // Run Query
        $results = $this->readDataSource($query);

        // Modify Results
        if (empty($results)) {
            return array();
        }

        return $results;
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
            return array();
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
        $query['fields'] = array('COUNT(*) AS count');
        $query['order'] = null;
        $query['limit'] = null;

        // Run Query
        $results = $this->readDataSource($query, 'assoc');

        // Modify Results
        if (empty($results)) {
            return array();
        }

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

        // Add AutoJoins
        if ($query['recursive'] >= 0) {
            foreach (['belongsTo', 'hasOne'] as $association) {
                foreach ($this->{$association} as $alias => $config) {
                    if (!isset($this->{$alias})) {
                        throw new MissingModelException($config['className'].':'.$alias);
                    }

                    $query['joins'][] = array(
                        'table' => Inflector::tableize($config['className']),
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
                    $query['fields'] = array_merge($query['fields'], $config['fields']);
                }
            }
        }

        return $query;
    }

    /**
     * Takes results from the datasource and converts into an entity. Different
     * from model::toEntity which takes an array which can include hasMany
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
            $entity = $this->createEntity($record[$this->alias], $this->alias);
            unset($record[$this->alias]);

            foreach ($record as $model => $data) {
                $associated = Inflector::variable($model);
                $entity->{$associated} = $this->createEntity($data, $associated);
            }

            $buffer[] = $entity;
        }

        return $buffer;
    }

    /**
     * Transforms a single row into an entity, overide this to use a custom
     * Entity class. E.g ContactEntity
     * This does not convert data.
     *
     * @param array  $resultSet result from database
     * @param string $name      name of entity basically model alias
     *
     * @return Entity
     */
    protected function createEntity(array $resultSet, string $name)
    {
        return new Entity($resultSet, ['name' => $name]);
    }

    protected function loadAssociatedHasMany($query, $results)
    {
        foreach ($this->hasMany as $alias => $config) {
            if (!isset($this->{$alias})) {
                throw new MissingModelException($config['className'].':'.$alias);
            }

            $config['recursive'] = $query['recursive'];

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
            if (!isset($this->{$alias})) {
                throw new MissingModelException($config['className'].':'.$alias);
            }

            $config['recursive'] = $query['recursive'];
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
     * @param string $type  [description]
     * @param array  $query [description]
     *
     * @return [type] [description]
     */
    protected function readDataSource(array $query, $type = 'model')
    {
        $QueryBuilder = new QueryBuilder($this->table, $this->alias);
        $sql = $QueryBuilder->selectStatement($query);

        $connection = $this->getConnection();
        $connection->execute($sql, $QueryBuilder->getValues());

        if ($type == 'list') {
            return $connection->fetchList();
        }

        $results = $connection->fetchAll($type);

        if ($results and $type === 'model') {
            $results = $this->prepareResults($results);

            if ($query['recursive'] >= 1) {
                $query['recursive'] = $query['recursive'] - 2; // Skip 1 level to get right
                $results = $this->loadAssociatedHasMany($query, $results);
                $results = $this->loadAssociatedHasAndBelongsToMany($query, $results);
            }
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
        $connection = $this->getConnection();
        $result = $connection->execute($sql, $params);

        if (preg_match('/^SELECT/i', $sql)) {
            return $connection->fetchAll('assoc');
        }

        return $result;
    }

    /**
     * Returns the current data source.
     *
     * @return DataSource
     */
    public function getConnection()
    {
        return ConnectionManager::get($this->datasource);
    }

    /**
     * Return either the query or true.
     */
    public function beforeFind($query = [])
    {
        return $query;
    }

    public function afterFind($results)
    {
        return $results;
    }

    /**
     * This must return true;.
     *
     * @return bool true
     */
    public function beforeValidate(Entity $entity)
    {
        return true;
    }

    /**
     * Called after validating data.
     */
    public function afterValidate(Entity $entity)
    {
    }

    public function beforeSave(Entity $entity, array $options = [])
    {
        return true;
    }

    public function afterSave(Entity $entity, bool $created, array $options = [])
    {
    }

    public function beforeDelete(bool $cascade = true)
    {
        return true;
    }

    public function afterDelete()
    {
    }

    /**
     * @todo test
     *
     * @param Entity $entity [description]
     * @param array  $fields [description]
     *
     * @return bool [description]
     */
    public function isUnique(Entity $entity, $fields = [])
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
        return $this->getConnection()->begin();
    }

    public function commit()
    {
        return $this->getConnection()->commit();
    }

    public function rollback()
    {
        return $this->getConnection()->rollBack();
    }

    /**
     * Creates an Entity from an array of data.
     *
     * @param array $data
     *
     * @return Entity
     */
    public function newEntity(array $array = [])
    {
        $marshaller = $this->marshaller();

        return $marshaller->newEntity($array, ['name' => $this->alias]);
    }

    /**
     * Merges data array into an entity.
     *
     * @param Entity $entity
     * @param array  $data
     *
     * @return Entity
     */
    public function patchEntity(Entity $entity, array $data)
    {
        $marshaller = $this->marshaller();

        return $marshaller->patchEntity($entity, $data);
    }

    /**
     * Gets the Marshaller object.
     */
    protected function marshaller()
    {
        if (!isset($this->marshaller)) {
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
    public function triggerCallback(string $callback, $arguments = [], $passedArgs = false)
    {
        $callbacks = array(
        array($this, $callback),
      );

        foreach ($this->behaviors->enabled() as $behavior) {
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
        }
        // Free Mem
        unset($callbacks,$result);

        if ($passedArgs) {
            return $arguments[0]; // was if not exist return result
        }

        return true;
    }
}
