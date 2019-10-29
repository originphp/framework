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
declare(strict_types = 1);
namespace Origin\Model\Concern;

use Origin\Cache\Cache;

/**
 * Cacheable Concern
 *
 * Elegant model caching, does not work with file based cache as it requires increment and filebased caching will
 * be slower.
 *
 * To use either call the findCached method or override the your find method in your ApplicationModel or
 * any other model which you want all queries to be cached for.
 *
 * for example
 *
 *  public function find(string $type = 'first', array $options = [])
 *  {
 *       return $this->findCached($type, $options);
 *  }
 */
trait Cacheable
{
    /**
     * Register callbacks
     *
     * @return void
     */
    protected function initializeCacheable(): void
    {
        $this->afterSave('invalidateCache'); // create or update
        $this->afterDelete('invalidateCache');
    }

    /**
    * Runs a find query and caches the result
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
    * @return mixed $result
    */
    public function findCached(string $type = 'first', array $options = [])
    {
        $cache = Cache::store($this->cacheConfig ?? 'default');

        $cacheId = $cache->read($this->name . '-id') ?: 1;

        $key = md5($this->name . $type  . serialize($options));

        $result = $cache->read($key);

        if ($result and $result['cacheId'] === $cacheId) {
            return $result['data'];
        }

        $result = parent::find($type, $options);

        $cache->write($key, ['cacheId' => $cacheId, 'data' => $result]);
        $cache->write($this->name . '-id', $cacheId);

        return $result;
    }

    /**
     * Invalidate the cache for this model and associated models
     *
     * If you have used model methods which dont use callbacks, then you should call this method afterwards.
     *
     * The following model methods modify the database but do not trigger callbacks:
     *
     * - updateColumn
     * - deleteAll
     * - updateAll
     * - increment
     * - decrement
     *
     * @return void
     */
    public function invalidateCache(): void
    {
        $this->incrementCacheId();

        foreach ([$this->belongsTo, $this->hasMany, $this->hasOne, $this->hasAndBelongsToMany] as $association) {
            foreach ($association as $alias => $data) {
                if (method_exists($this->$alias, 'incrementCacheId')) {
                    $this->$alias->incrementCacheId();
                }
            }
        }
    }

    /**
     * This increments the cacheId, this used to when invalidating cache on external models. Use
     * invalidateCache instead.
     *
     * @return void
     */
    public function incrementCacheId(): void
    {
        $cache = Cache::store($this->cacheConfig ?? 'default');
        if ($cache->exists($this->name . '-id')) {
            $cache->increment($this->name . '-id', 1);
        }
    }
}
