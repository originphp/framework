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

use ArrayObject;
use Origin\Model\Entity;
use Origin\Exception\Exception;
use Origin\Utility\Elasticsearch as Es;

/**
 * By default it uses the default connection to use a different one
 * set a property in your model protected $elasticsearchConnection ='somethingElse'
 */
trait Elasticsearch
{
    /**
     * Holds the index name
     * e.g. default_posts
     *
     * @var string
     */
    private $indexName = null;

    /**
     * Holds the index
     *
     * @var array
     */
    private $indexes = [];

    /**
     * Settings for index
     * e.g number_of_shards = 3
     *
     * @var array
     */
    private $indexSettings = [];

    /**
     * Concern initializer
     *
     * @return void
     */
    public function initializeElasticsearch() : void
    {
        $connection = $this->elasticSearchConnection ?? 'default';
        $this->indexName = $connection  . '_' . $this->table;
        $this->afterSave('elasticsearchAfterSave');
        $this->afterDelete('elasticsearchAfterDelete');
    }

    /**
     * Adds a column to be indexed
     *
     * @param string $name name of the column to be indexed
     * @param array $options e.g ['type'=>'keyword','analyzer'=>'english']
     * @return void
     */
    public function index(string $name, array $options = []) : void
    {
        $schema = $this->schema($name);
        if (! $schema) {
            throw new Exception('Unkown column ' . $name);
        }
        if (empty($options)) {
            $options = ['type' => $this->mapColumn($schema['type'])];
        }
        $this->indexes[$name] = $options;
    }

    /**
     * Sets settings for an index
     *
     * @param array $settings e.g ['number_of_shards' => 1]
     * @return void
     */
    public function indexSettings(array $settings) : void
    {
        $this->indexSettings = $settings;
    }

    /**
     * Searches the Elasticsearch engine using either a query string dsl or request body array. Only
     * data from the _source property is returned, so if it is not indexed
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-body.html
     *
     * @param array|string a query string or array query.
     *  example query strings:  'php', '+php +framework', 'title:how to', '(new york city) OR (big apple)'
     *  example using request body
    *   $query = [
    *        'query' => [
    *           'multi_match' => [
    *           'query' => 'search keywords',
    *           'fields' => ['title','body']
    *           ]
    *      ]
    *   ];
     * @return array
     */
    public function search($query) : array
    {
        $results = $this->elasticSearchConnection()->search($this->indexName, $query);

        return $this->newEntities($results);
    }

    /**
     * This deletes the index if it exists, creates it again and indexes all records in the database
     *
     * @return integer
     */
    public function reindex() : int
    {
        $this->deleteIndex();
        $this->createIndex();

        return $this->indexRecords();
    }

    /**
     * After save callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return void
     */
    protected function elasticsearchAfterSave(Entity $entity, ArrayObject $options) : void
    {
        $this->indexRecord($entity);
    }

    /**
     * After delete
     *
     * @param \Origin\Model\Entity $entity
      * @param ArrayObject $options
     * @return void
     */
    protected function elasticsearchAfterDelete(Entity $entity, ArrayObject $options) : void
    {
        if (! $this->elasticSearchConnection()->deindex($this->indexName, $entity->id)) {
            throw new Exception(sprintf('Elasticsearch: Error deleting from index for model `%s`', $this->name));
        }
    }

    /**
     * Gets the settings for indexes, if this is not available then
     * it dynamically maps all fields
     *
     * @return array
     */
    private function indexes()
    {
        if (empty($this->indexes)) {
            $this->indexes = $this->dynamicMapping();
        }
    
        return $this->indexes;
    }

    /**
     * Deletes an index
     *
     * @return boolean
     */
    private function deleteIndex() : bool
    {
        $elasticsearch = $this->elasticSearchConnection();
        if ($elasticsearch->indexExists($this->indexName)) {
            return $elasticsearch->removeIndex($this->indexName);
        }

        return false;
    }

    /**
     * Creates the index
     *
     * @return boolean
     */
    private function createIndex() : bool
    {
        $settings = [
            'mappings' => ['properties' => $this->indexes()],
        ];
        if ($this->indexSettings) {
            $settings['settings'] = $this->indexSettings; // adding empty value will cause for adding to fail
        }

        return $this->elasticSearchConnection()->addIndex($this->indexName, $settings);
    }

    /**
     * Imports records into the index
     *
     * @return bool|int
     */
    private function indexRecords()
    {
        $counter = 0;
        foreach ($this->find('all') as $entity) {
            $this->indexRecord($entity);
            $counter ++;
        }

        return $counter;
    }

    /**
    * Gets the Elasticsearch object
    *
    * @return \Origin\Utility\Elasticsearch
    */
    private function elasticsearchConnection() : Es
    {
        return Es::connection($this->elasticsearchConnection ?? 'default');
    }

    /**
     * Indexes a record
     *
     * @param Entity $entity
     * @return void
     */
    private function indexRecord(Entity $entity) : void
    {
        $out = [];

        $indexes = array_keys($this->indexes());
        foreach ($indexes as $column) {
            $out[$column] = $entity->get($column);
        }

        // Issues connecting to server or no columns found for indexing
        if (! $this->elasticSearchConnection()->index($this->indexName, $entity->id, $out)) {
            throw new Exception(sprintf('Elasticsearch: Error adding record to index for model `%s`', $this->name));
        }
    }

    /**
    * Create mapping settings for creating indexes in Elasticsearch
    *
    * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html
    * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-types.html
    * @param array $columns
    * @return array
    */
    private function dynamicMapping()
    {
        $out = [];
        $columns = array_keys($this->schema()['columns']);
        $schema = $this->schema();
        foreach ($columns as $column) {
            $type = $schema['columns'][$column]['type'];
            $out[$column] = ['type' => $this->mapColumn($type)];
            if ($out[$column]['type'] === 'date') {
                $out[$column]['format'] = 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||HH:mm:ss';
            }
        }

        return $out;
    }

    /**
     * Maps a column type to Elasticasearch
     *
     * @param string $type
     * @return string
     */
    private function mapColumn(string $type) : string
    {
        $map = [
            'string' => 'keyword',
            'text' => 'text',
            'integer' => 'integer',
            'bigint' => 'long',
            'float' => 'float',
            'decimal' => 'double',
            'datetime' => 'date',
            'time' => 'date',
            'timestamp' => 'date',
            'time' => 'date',
            'binary' => 'binary',
            'boolean' => 'boolean',
        ];

        return $map[$type] ?? 'string';
    }
}
