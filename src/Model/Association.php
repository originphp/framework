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

declare(strict_types=1);

namespace Origin\Model;

use Origin\Model\Model;
use Origin\Core\Inflector;

class Association
{
    /**
     * The model
     *
     * @var \Origin\Model\Model
     */
    protected $model = null;

    /**
     * The constructor
     *
     * @param Model $model
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
            $options['foreignKey'] = Inflector::underscore($this->model->name) . '_id';
        }
        $tableAlias = Inflector::tableize($this->model->alias);
        $associationTableAlias = Inflector::tableize($association);
        $conditions = ["{$tableAlias}.id = {$associationTableAlias}.{$options['foreignKey']}"];

        if (!empty($options['conditions'])) {
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
        $defaults = [
            'className' => $association,
            'foreignKey' => null,
            'conditions' => null,
            'fields' => null,
            'type' => 'LEFT',
        ];

        $options = array_merge($defaults, $options);

        if (is_null($options['foreignKey'])) {
            $options['foreignKey'] = Inflector::underscore($options['className']) . '_id';
        }
        $alias = Inflector::tableize($this->model->alias);
        $associatedAlias = Inflector::tableize($association);

        $conditions = ["{$alias}.{$options['foreignKey']} = {$associatedAlias}.id"];

        if (!empty($options['conditions'])) {
            $conditions = array_merge($conditions, (array) $options['conditions']);
        }
        $options['conditions'] = $conditions;

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
            $options['foreignKey'] =  Inflector::underscore($this->model->name) . '_id';
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

        // join table in alphabetic order
        $models = [$this->model->name, $options['className']];
        sort($models);
        $models = array_values($models);

        $with = Inflector::pluralize($models[0]) . $models[1];
        if (is_null($options['with'])) {
            $options['with'] = $with;
        }
        if (is_null($options['joinTable'])) {
            $options['joinTable'] = Inflector::pluralize(Inflector::underscore($options['with']));
        }
        if (is_null($options['foreignKey'])) {
            $options['foreignKey'] = Inflector::underscore($this->model->name) . '_id';
        }
        if (is_null($options['associationForeignKey'])) {
            $options['associationForeignKey'] = Inflector::underscore($options['className']) . '_id';
        }
        $withAlias = Inflector::tableize($options['with']);
        $optionsClassAlias = Inflector::tableize($options['className']);
        $conditions = ["{$withAlias}.{$options['associationForeignKey']} = {$optionsClassAlias}.id"];

        if (!empty($options['conditions'])) {
            $conditions = array_merge($conditions, (array) $options['conditions']);
        }
        $options['conditions'] = $conditions;

        return $options;
    }
}
