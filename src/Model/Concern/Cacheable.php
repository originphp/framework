<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
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
namespace Origin\Model\Concern;

use Origin\Cache\Cache;

/**
 * Cacheable Concern.
 */
trait Cacheable
{
    /**
     * Cache configuration to use
     *
     * @var string
     */
    private $cacheConfig = 'default';

    /**
     * Flag
     *
     * @var boolean
     */
    private $cacheEnabled = true;

    /**
     * initialize cacheable
     *
     * @return void
     */
    protected function initializeCacheable(): void
    {
        $this->afterCreate('cacheableEvent');
        $this->afterUpdate('cacheableEvent');
        $this->afterDelete('cacheableEvent');
    }

    /**
     * Sets or gets the cache config. Set from initialize in the model.
     *
     * @param string $name
     * @return string
     */
    protected function cacheConfig(string $name = null): ?string
    {
        if ($name === null) {
            return $this->cacheConfig;
        }
        $this->cacheConfig = $name;

        return null;
    }

    /**
    * Runs a find query and caches the result
    *
    * @param string $type  (first,all,count,list)
    * @param array $options  The options array can work with the following keys
    *   - cache: default true. Results are cached.
    *   - conditions: an array of conditions to find by. e.g ['id'=>1234,'status !=>'=>'new]
    *   - fields: an array of fields to fetch for this model. e.g ['id','title','description']
    *   - joins: an array of join arrays e.g. table' => 'authors','alias' => 'authors', 'type' => 'LEFT' ,
    * 'conditions' => ['authors.id = articles.author_id']
    *   - order: the order to fetch e.g. ['title ASC'] or ['category','title ASC']
    *   - limit: the number of records to limit by
    *   - group: the field to group by e.g. ['category']
    *   - callbacks: default is true. Set to false to disable running callbacks such as beforeFind and afterFind
    *   - associated: an array of models to get data for e.g. ['Comment'] or ['Comment'=>['fields'=>['id','body']]]
    * @return mixed $result
    */
    public function find(string $type = 'first', array $options = [])
    {
        $options += ['cache' => true];

        if ($this->cacheEnabled === false || ! $options['cache']) {
            return parent::find($type, $options);
        }

        $key = md5($this->name . $type  . var_export($options, true));
        $cache = Cache::store($this->cacheConfig);
        $cacheId = $cache->read($this->name . '-id') ?: 1;

        # cache_get
        $result = $cache->read($key);
        if ($result && $result['id'] === $cacheId) {
            return $result['data'];
        }
 
        # cache_set
        $result = parent::find($type, $options);
        $cache->write($key, ['id' => $cacheId, 'data' => $result]);
        $cache->write($this->name . '-id', $cacheId);
    
        return $result;
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
        $options += ['cache' => true];

        if ($this->cacheEnabled === false || ! $options['cache']) {
            return parent::calculate($operation, $columnName, $options);
        }

        $key = md5($this->name . $operation . $columnName  . var_export($options, true));
        $cache = Cache::store($this->cacheConfig);
        $cacheId = $cache->read($this->name . '-id') ?: 1;

        # cache_get
        $result = $cache->read($key);
        if ($result && $result['id'] === $cacheId) {
            return $result['data'];
        }
 
        # cache_set
        $result = parent::calculate($operation, $columnName, $options);
        $cache->write($key, ['id' => $cacheId, 'data' => $result]);
        $cache->write($this->name . '-id', $cacheId);
    
        return $result;
    }

    /**
     * This is an event which is triggered using model callbacks
     *
     * @return void
     */
    protected function cacheableEvent(): void
    {
        $this->invalidateCache();
    }

    /**
     * Invalidate the cache
     *
     * If you have used model methods which dont use callbacks, then you should call this method afterwards. The
     * following model methods modify the database but do not trigger callbacks:
     *
     * - updateColumn
     * - deleteAll
     * - updateAll
     * - increment
     * - decrement
     *
     * @param boolean $associated default: true. Clear cache on associated models
     * @return void
     */
    public function invalidateCache(bool $associated = true): void
    {
        // Increment CacheID
        $cache = Cache::store($this->cacheConfig);
        if ($cache->exists($this->name . '-id')) {
            $cache->increment($this->name . '-id');
        }

        if ($associated) {
            foreach ([$this->belongsTo, $this->hasMany, $this->hasOne, $this->hasAndBelongsToMany] as $association) {
                foreach ($association as $alias => $config) {
                    if (method_exists($this->$alias, 'invalidateCache')) {
                        $this->$alias->invalidateCache(false);
                    }
                }
            }
        }
    }

    /**
    * Enables cache after being disabled
    *
    * @return boolean
    */
    public function enableCache(): bool
    {
        if ($this->cacheEnabled) {
            return false;
        }

        return $this->cacheEnabled = true;
    }

    /**
     * Disables the caching
     *
     * @return boolean
     */
    public function disableCache(): bool
    {
        if (! $this->cacheEnabled) {
            return false;
        }

        $this->cacheEnabled = false;

        return true;
    }
}
