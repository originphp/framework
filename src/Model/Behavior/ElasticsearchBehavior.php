<?php
namespace Origin\Model\Behavior;

use Origin\Model\Entity;
use Origin\Exception\Exception;
use Origin\Utility\Elasticsearch;

/**
 * @todo
 *  1. delete all
 *  2. update all
 */
class ElasticsearchBehavior extends Behavior
{
    protected $defaultConfig = [
        'connection' => 'default',
    ];

    /**
     * Holds the index name
     * e.g. default_posts
     *
     * @var string
     */
    protected $indexName = null;

    /**
     * Holds the index
     *
     * @var array
     */
    protected $indexes = [];

    /**
     * Settings for index
     * e.g number_of_shards = 3
     *
     * @var array
     */
    protected $indexSettings = [];

    /**
     * Use this so you don't have to overide __construct.
     *
     * @param array $config
     * @return void
     */
    public function initialize(array $config)
    {
        $datasource = $this->model()->datasource;
        $model = $this->model()->table;
        $this->indexName = $datasource . '_' . $model;
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
        $schema = $this->model()->schema($name);
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
     * Gets the settings for indexes, if this is not available then
     * it dynamically maps all fields
     *
     * @return array
     */
    protected function indexes()
    {
        if (empty($this->indexes)) {
            $this->indexes = $this->dynamicMapping();
        }
    
        return $this->indexes;
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
     * Deletes an index
     *
     * @return boolean
     */
    protected function deleteIndex() : bool
    {
        $elasticsearch = $this->connection();
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
    protected function createIndex() : bool
    {
        $settings = [
            'mappings' => ['properties' => $this->indexes()],
        ];
        if ($this->indexSettings) {
            $settings['settings'] = $this->indexSettings; // adding empty value will cause for adding to fail
        }

        return $this->connection()->addIndex($this->indexName, $settings);
    }

    /**
     * Imports records into the index
     *
     * @return bool|int
     */
    protected function indexRecords()
    {
        $counter = 0;
        foreach ($this->model()->find('all') as $entity) {
            $this->indexRecord($entity);
            $counter ++;
        }

        return $counter;
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
        $this->indexRecord($entity);
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
        if (! $this->connection()->deindex($this->indexName, $entity->id)) {
            throw new Exception(sprintf('Elasticsearch: Error deleting from index for model `%s`', $this->model()->name));
        }
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
        $results = $this->connection()->search($this->indexName, $query);

        return $this->model()->newEntities($results);
    }

    /**
    * Gets the Elasticsearch object
    *
    * @return \Origin\Utility\Elasticsearch
    */
    public function connection()
    {
        return Elasticsearch::connection($this->config['connection']);
    }

    /**
     * Indexes a record
     *
     * @param Entity $entity
     * @return void
     */
    protected function indexRecord(Entity $entity) : void
    {
        $out = [];

        $indexes = array_keys($this->indexes());
        foreach ($indexes as $column) {
            $out[$column] = $entity->get($column);
        }
 
        // Issues connecting to server or no columns found for indexing
        if (! $this->connection()->index($this->indexName, $entity->id, $out)) {
            throw new Exception(sprintf('Elasticsearch: Error adding record to index for model `%s`', $this->model()->name));
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
    protected function dynamicMapping()
    {
        $out = [];
        $columns = array_keys($this->model()->schema()['columns']);
        $schema = $this->model()->schema();
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
    protected function mapColumn(string $type) : string
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
