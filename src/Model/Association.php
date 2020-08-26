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

use ArrayObject;
use Origin\Inflector\Inflector;

class Association
{
    /**
     * The model
     *
     * @var \Origin\Model\Model
     */
    protected $model;

    /**
     * The constructor
     *
     * @param \Origin\Model\Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }
    /**
     * Creates a hasOne relationship. By default we assume that naming standards
     * are followed using primary key as id. Anything else then you have set the
     * options manually (even if you change the primary key setting).
     *
     * @param string $association e.g Comment
     * @param array  $options The options array accepts any of the following keys
     *   - className: is the name of the class that you want to load (with or without the namespace).
     *   - foreignKey: the foreign key in the other model. The default value would be the underscored name of the current model suffixed with '\_id'.
     *   - conditions: an array of additional conditions to the join
     *   - fields: an array of fields to return from the join model, by default it returns all
     *   - dependent: default is false, if set to true when delete is called with cascade it will related records.
     */
    public function hasOne(string $association, array $options = []): array
    {
        $options += [
            'className' => $association,
            'foreignKey' => null,
            'conditions' => null,
            'fields' => null,
            'dependent' => false,
        ];

        if (is_null($options['foreignKey'])) {
            $options['foreignKey'] = Inflector::underscored($this->model->name()) . '_id';
        }
        $tableAlias = Inflector::tableName($this->model->alias());
        list($plugin, $associated) = pluginSplit($association);
        $associatedAlias = Inflector::tableName($associated);
        $conditions = ["{$tableAlias}.id = {$associatedAlias}.{$options['foreignKey']}"];

        if (! empty($options['conditions'])) {
            $conditions = array_merge($conditions, (array) $options['conditions']);
        }
        $options['conditions'] = $conditions;

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
     * @param array  $options The options array accepts any of the following keys
     *   - className: is the name of the class that you want to load (with or without the namespace).
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
        $options += [
            'className' => $association,
            'foreignKey' => null,
            'conditions' => null,
            'fields' => null,
            'type' => 'LEFT',
        ];

        if (is_null($options['foreignKey'])) {
            $options['foreignKey'] = Inflector::underscored($this->extractClass($options['className'])) . '_id';
        }
        $alias = Inflector::tableName($this->model->alias());

        list($plugin, $associated) = pluginSplit($association);
        $associatedAlias = Inflector::tableName($associated);

        $conditions = ["{$alias}.{$options['foreignKey']} = {$associatedAlias}.id"];

        if (! empty($options['conditions'])) {
            $conditions = array_merge($conditions, (array) $options['conditions']);
        }
        $options['conditions'] = $conditions;

        return $options;
    }

    private function extractClass(string $class): string
    {
        if (strpos($class, '\\')) {
            list($namespace, $class) = namespaceSplit($class);
        } else {
            list($plugin, $class) = pluginSplit($class);
        }

        return $class;
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
     *   - className: is the name of the class that you want to load (with or without the namespace).
     *   - foreignKey: the foreign key in the other model. The default value would be the underscored name of the
     * current model suffixed with '\_id'.
     *   - conditions: an array of additional conditions to the join
     *   - fields: an array of fields to return from the join model, by default it returns all
     *   - order: a string or array of how to order the result
     *   - dependent: default is false, if set to true when delete is called with cascade it will related records.
     *   - limit: default is null, set a value to limit how many rows to return
     *   - offset: if you are using limit then set from where to start fetching
     */
    public function hasMany(string $association, array  $options = []): array
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
            $options['foreignKey'] = Inflector::underscored($this->model->name()) . '_id';
        }
   
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
     * @param array  $options The options array accepts any of the following keys
     *   - className: is the name of the class that you want to load (with or without the namespace).
     *   - joinTable: the name of the table used by this relationship
     *   - with: the name of the model which uses the join table
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

        $class = $this->extractClass($options['className']);

        // join table in alphabetic order
        $models = [$this->model->name(), $class];
        sort($models);
        $models = array_values($models);

        $with = Inflector::plural($models[0]) . $models[1];
        if (is_null($options['with'])) {
            $options['with'] = $with;
        }
        if (is_null($options['joinTable'])) {
            $options['joinTable'] = Inflector::plural(Inflector::underscored($options['with']));
        }
        if (is_null($options['foreignKey'])) {
            $options['foreignKey'] = Inflector::underscored($this->model->name()) . '_id';
        }
        if (is_null($options['associationForeignKey'])) {
            $options['associationForeignKey'] = Inflector::underscored($class) . '_id';
        }
        $withAlias = Inflector::tableName($options['with']);
        $optionsClassAlias = Inflector::tableName($class);
        $conditions = ["{$withAlias}.{$options['associationForeignKey']} = {$optionsClassAlias}.id"];

        if (! empty($options['conditions'])) {
            $conditions = array_merge($conditions, (array) $options['conditions']);
        }
        $options['conditions'] = $conditions;

        return $options;
    }

    /**
     * Saves the parents
     *
     * @param \Origin\Model\Entity $data
     * @param \ArrayObject $options
     * @return boolean
     */
    public function saveBelongsTo(Entity $data, ArrayObject $options): bool
    {
        $associatedOptions = ['transaction' => false] + (array) $options;
       
        foreach ($this->model->association('belongsTo') as $alias => $config) {
            $key = lcfirst($alias);
            if (! in_array($alias, $options['associated']) || ! $data->has($key) || ! $data->$key instanceof Entity) {
                continue;
            }

            if ($data->$key->isDirty()) {
                if (! $this->model->$alias->save($data->$key, $associatedOptions)) {
                    return false;
                }
                $foreignKey = $this->model->association('belongsTo')[$alias]['foreignKey'];
                $data->$foreignKey = $this->model->$alias->id();
            }
        }

        return true;
    }

    public function saveHasOne(Entity $data, ArrayObject $options): bool
    {
        $associatedOptions = ['transaction' => false] + (array) $options;
        foreach ($this->model->association('hasOne') as $alias => $config) {
            $key = lcfirst($alias);
            if (! in_array($alias, $options['associated']) || ! $data->has($key) || ! $data->$key instanceof Entity) {
                continue;
            }
            if ($data->$key->isDirty()) {
                $foreignKey = $this->model->association('hasOne')[$alias]['foreignKey'];
                $data->$key->$foreignKey = $this->model->id();

                if (! $this->model->$alias->save($data->get($key), $associatedOptions)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function saveHasMany(Entity $data, ArrayObject $options): bool
    {
        $associatedOptions = ['transaction' => false] + (array) $options;
        foreach ($this->model->association('hasMany') as $alias => $config) {
            $key = Inflector::plural(lcfirst($alias));
            if (! in_array($alias, $options['associated']) || ! $data->has($key)) {
                continue;
            }

            $foreignKey = $this->model->association('hasMany')[$alias]['foreignKey'];

            foreach ($data->get($key) as $record) {
                if (! $record instanceof Entity) {
                    continue;
                }
                if ($record->isDirty()) {
                    $record->$foreignKey = $data->{$this->model->primaryKey()};
                    if (! $this->model->$alias->save($record, $associatedOptions)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function saveHasAndBelongsToMany(array $habtm, bool $callbacks): bool
    {
        foreach ($habtm as $alias => $data) {
            if (! $this->saveHABTM($alias, $data, $callbacks)) {
                return false;
            }
        }

        return true;
    }

    /**
    * Saves the hasAndBelongsToMany data
    *
    * @param string $association
    * @param Collection|array $data
    * @param boolean $callbacks
    * @return bool
    */
    private function saveHABTM(string $association, $data, bool $callbacks): bool
    {
        $connection = $this->model->connection();

        $config = $this->model->association('hasAndBelongsToMany')[$association];
        $joinModel = $this->model->{$config['with']};

        $links = [];

        foreach ($data as $row) {
            $primaryKey = $this->model->$association->primaryKey();
            $displayField = $this->model->$association->displayField();

            // Either primaryKey or DisplayField must be set in data
            if ($row->has($primaryKey)) {
                $needle = $primaryKey;
            } elseif ($row->has($displayField)) {
                $needle = $displayField;
            } else {
                return false;
            }

            $tag = $this->model->$association->find('first', [
                'conditions' => [$needle => $row->get($needle)],
                'callbacks' => false,
            ]);

            if ($tag) {
                $id = $tag->get($primaryKey);
                $links[] = $id;
                $row->set($primaryKey, $id);
            } else {
                if (! $this->model->$association->save($row, [
                    'callbacks' => $callbacks,
                    'transaction' => false,
                ])) {
                    return false;
                }
                $links[] = $this->model->$association->id();
            }

            $joinModel = $this->model->{$config['with']};
        }

        $existingJoins = $joinModel->find('list', [
            'conditions' => [$config['foreignKey'] => $this->model->id()],
            'fields' => [$config['associationForeignKey']],
        ]);

        $connection = $joinModel->connection();
        // By adding ID field we can do delete callbacks
        if ($config['mode'] === 'replace') {
            $connection->delete($config['joinTable'], [$config['foreignKey'] => $this->model->id()]);
        }

        foreach ($links as $linkId) {
            if ($config['mode'] === 'append' && in_array($linkId, $existingJoins)) {
                continue;
            }
            $insertData = [
                $config['foreignKey'] => $this->model->id(),
                $config['associationForeignKey'] => $linkId,
            ];
            $connection->insert($joinModel->table(), $insertData);
        }

        return true;
    }

    /**
     * Deletes the hasOne and hasMany associated records.
     *
     * @param int|string $primaryKey
     * @param boolean $callbacks
     * @return boolean
     */
    public function deleteDependent($primaryKey, bool $callbacks): bool
    {
        foreach (array_merge($this->model->association('hasOne'), $this->model->association('hasMany')) as $association => $config) {
            if (isset($config['dependent']) && $config['dependent'] === true) {
                $conditions = [$config['foreignKey'] => $primaryKey];
                $ids = $this->model->$association->find('list', [
                    'conditions' => $conditions,
                    'fields' => [$this->model->primaryKey()]
                ]);
                foreach ($ids as $id) {
                    $conditions = [$this->model->$association->primaryKey() => $id];
                    $result = $this->model->$association->find('first', [
                        'conditions' => $conditions, 'callbacks' => false
                    ]);
                    if ($result) {
                        $this->model->$association->delete($result, [
                            'transaction' => false,'callbacks' => $callbacks
                        ]);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Deletes the hasAndBelongsToMany associated records.
     *
     * @param int|string $id
     * @param boolean $callbacks
     * @return boolean
     */
    public function deleteHasAndBelongsToMany($id, bool $callbacks): bool
    {
        foreach ($this->model->association('hasAndBelongsToMany') as $association => $config) {
            $associatedModel = $config['with'];
            $conditions = [$config['foreignKey'] => $id];
            $ids = $this->model->$associatedModel->find('list', [
                'conditions' => $conditions
            ]);

            foreach ($ids as $id) {
                $conditions = [$this->model->$associatedModel->primaryKey() => $id];
                $result = $this->model->$associatedModel->find('first', [
                    'conditions' => $conditions, 'callbacks' => false
                ]);
                if ($result) {
                    $this->model->$associatedModel->delete($result, [
                        'transaction' => false,'callbacks' => $callbacks
                    ]);
                }
            }
        }

        return true;
    }
}
