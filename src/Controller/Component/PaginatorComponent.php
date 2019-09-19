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

namespace Origin\Controller\Component;

use Origin\Model\Model;
use Origin\Utility\Inflector;
use Origin\Exception\NotFoundException;

class PaginatorComponent extends Component
{
    protected $defaultConfig = [
        'page' => 1,
        'limit' => 20,
    ];

    /**
     * Name of keys allowed to be passed in params
     *
     * @var array
     */
    protected $whitelist = ['direction', 'limit', 'page', 'sort'];

    /**
     * This is the model for the current Pagination request. All functions that need
     * this will use this until paginate is called again, if ever.
     *
     * @var \Origin\Model\Model
     */
    protected $model = null;

    /**
     * Paginates the records
     *
     * @param Model $model
     * @param array $settings
     * @return mixed
     */
    public function paginate(Model $model, array $settings = [])
    {
        $this->model = $model;

        $settings = $this->mergeSettings($settings);
        $settings = $this->prepareSort($settings);

        /**
         * Security: block non integer values here
         */
        if (! ctype_digit((string) $settings['page'])) {
            throw new NotFoundException('Invalid Page');
        }
     
        $sort = $direction = false;
        if (isset($settings['order'])) {
            $sort = key($settings['order']);
            $direction = current($settings['order']);
        }

        $count = $this->getTotal($settings);
        $pages = (int) ceil($count / $settings['limit']);

        $this->controller()->set('paging', [
            'current' => $settings['page'],
            'pages' => ($pages > 1 ? $pages : 1),
            'records' => $count,
            'sort' => $sort,
            'direction' => $direction,
            'prevPage' => ($settings['page'] > 1),
            'nextPage' => ($settings['page'] < $pages),
        ]);
      
        if ($settings['page'] > 1 and $settings['page'] > ($count * $settings['limit'])) {
            throw new NotFoundException();
        }
 
        // Enable sorting on related Fields. e.g author_id - this sort by Display Field
        if ($sort and substr($sort, -3) === '_id') {
            // Setup default sort if intra model fails
            $tableAlias = Inflector::tableName($this->model->alias);
            $settings['order'] = ["{$tableAlias}.{$sort}" => $direction];
            // intra model sorting by display field if configured
            $alias = $this->getModelFromField($sort);
            
            if (isset($this->model->{$alias})) {
                if ($displayField = $this->model->{$alias}->displayField) {
                    $tableAlias = Inflector::tableName($this->model->{$alias}->alias);
                    $settings['order'] = ["{$tableAlias}.{$displayField}" => $direction];
                }
            }
        }

        return $this->fetchResults($settings);
    }
    /**
     * Return
     *
     * @param array $settings
     * @return mixed
     */
    protected function fetchResults(array $settings)
    {
        return $this->model->find('all', $settings);
    }

    /**
     * Get the model name from the field.
     *
     * @param string $field
     * @return string model/alias
     */
    protected function getModelFromField(string $field) : ?string
    {
        $needle = Inflector::studlyCaps(substr($field, 0, -3)); // owner_id -> Owner;
        $belongsTo = $this->model->association('belongsTo');
    
        if (isset($belongsTo[$needle])) {
            return $needle;
        }
        // Fallback Magic Detect
        // Search for field as foreignKey, but only if it is unique across belongsTo
        $found = [];
    
        foreach ($belongsTo as $alias => $config) {
            if (isset($config['foreignKey']) and $config['foreignKey'] == $field) {
                $found[] = $alias;
            }
        }
        if (count($found) === 1) {
            return $found[0];
        }

        return null;
    }

    /**
     * Runs a count
     *
     * @param array $settings
     * @return integer
     */
    protected function getTotal(array $settings) : int
    {
        unset($settings['page'],$settings['limit']);

        return $this->model->find('count', $settings);
    }

    /**
     * Merges settings with defaults, and then checks and whitelists request query  params
     *
     * @param array $settings
     * @return array
     */
    protected function mergeSettings(array $settings) : array
    {
        // merge with defaults
        $settings += $this->config;
        $query = $this->controller()->request->query();
        if ($query) {
            // Ensure page is a number (Security)
            $query = $this->filterArray($this->whitelist, $query);
            $settings = array_merge($settings, $query);
        }

        return $settings;
    }

    /**
     * Return an array
     *
     * @param array $settings
     * @return array
     */
    protected function prepareSort(array $settings) : array
    {
        /**
         * Security issues to consider
         * - Users trying to inject code through query params
         *
         * 1. Check field exists to prevent injections
         * 2. Use pre-defined values for sort
         */
        if (isset($settings['sort']) and ! $this->model->hasField($settings['sort'])) {
            unset($settings['sort']);

            return $settings;
        }

        if (isset($settings['sort'])) {
            $direction = 'asc';
            if (isset($settings['direction']) and strtolower($settings['direction']) === 'desc') {
                $direction = 'desc';
            }

            $settings['order'] = [$settings['sort'] => $direction];
            unset($settings['sort'],$settings['direction']);
        }

        if (isset($settings['order']) and is_string($settings['order'])) {
            $settings['order'] = (array) $settings['order'];
        }

        return $settings;
    }

    /**
     * Filters a array by a list of keys.
     *
     * @example
     *  $extract = ['id','name'];
     *  return $this->filterArray($extract,$data);
     */
    protected function filterArray(array $keys, array $array)
    {
        return array_intersect_key($array, array_flip($keys));
    }
}
