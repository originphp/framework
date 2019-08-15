<?php

/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Utility;

use Origin\Core\StaticConfigTrait;

use Origin\Exception\NotFoundException;
use Origin\Utility\Exception\ElasticsearchException;

class Elasticsearch
{
    use StaticConfigTrait;

    protected static $defaultConfig = [
        'host' => '127.0.0.1',
        'port' => 9200,
        'timeout' => 300,
        'ssl' => false,
    ];

    /**
     * Holds the configurated objects
     *
     * @var array
     */
    protected static $connections = [];

    /**
     * Holds the last response
     *
     * @var array
     */
    protected $response = null;

    /**
     * Gets the connection
     *
     * @param string $name
     * @return Elasticsearch
     */
    public static function connection(string $name = 'default') : Elasticsearch
    {
        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        }
        $config = self::config($name);
        if (empty($config)) {
            throw new ElasticsearchException(sprintf('Configuration `%s` not found', $name));
        }

        return self::$connections[$name] = new Elasticsearch($config);
    }

    /**
     * Holds the URL
     * e.g. http://127.0.0.1:9200
     *
     * @var string
     */
    protected $url = null;

    public function __construct(array $config = [])
    {
        $config += self::$defaultConfig;
        $this->url = $config['ssl']?'https':'http' . '://' . $config['host'] . ':' . $config['port'];
        $this->timeout = $config['timeout'];
    }

    /**
     * Returns the last response from Elasticsearch.
     *
     * @return array
     */
    public function response() : array
    {
        return $this->response;
    }

    /**
     * Gets a list of indexes
     */
    public function indexes(): array
    {
        $this->response = $this->sendRequest('GET', "{$this->url}/_all?pretty");

        return array_keys($this->response['body']);
    }
    /**
     * Adds an index
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-create-index.html
     * @internal indexes are created automatically on Elasticsearch which is configured by action.auto_create_index
     *
     * @param string $name
     * @param array $settings
     * @return bool
     */
    public function addIndex(string $name, array $settings = null): bool
    {
        $this->response = $this->sendRequest('PUT', "{$this->url}/{$name}", $settings);
        
        if (isset($this->response['body']['error'])) {
            throw new ElasticsearchException($this->response['body']['error']['reason']);
        }

        return (isset($this->response['body']['acknowledged']) and $this->response['body']['acknowledged'] === true);
    }

    /**
     * Deletes an index
     *
     * @param string $name
     * @return bool
     */
    public function removeIndex(string $name): bool
    {
        $this->response = $this->sendRequest('DELETE', "{$this->url}/{$name}");

        if (isset($this->response['body']['error'])) {
            throw new ElasticsearchException($this->response['body']['error']['reason']);
        }

        return (isset($this->response['body']['acknowledged']) and $this->response['body']['acknowledged'] === true);
    }

    /**
     * Checks if an index exists
     *
     * @param string $Name
     * @return boolean
     */
    public function indexExists(string $name): bool
    {
        $this->response = $this->sendRequest('HEAD', "{$this->url}/{$name}/?pretty");

        if ($this->response['statusCode'] !== 404 and isset($this->response['body']['error'])) {
            throw new ElasticsearchException($this->response['body']['error']['reason']);
        }

        return ($this->response['statusCode'] === 200);
    }

    /**
     * Gets the information on the index
     *
     * @param string $name
     * @return array|null
     */
    public function index(string $name) : ?array
    {
        $this->response = $this->sendRequest('GET', "{$this->url}/{$name}?pretty");
 
        if (isset($this->response['body']['error'])) {
            throw new ElasticsearchException($this->response['body']['error']['reason']);
        }

        return $this->response['body'][$name] ?? null;
    }

    /**
     * Adds a document to the index
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-index_.html
     * @param string $index index name e.g development_posts
     * @param integer $id
     * @param array $data an array of data to be indexed ['title'=>'article title','body'=>'some description']
     * @return bool
     */
    public function add(string $index, int $id, array $data): bool
    {
        $this->response = $this->sendRequest('PUT', "{$this->url}/{$index}/_doc/{$id}", $data);
 
        if (isset($this->response['body']['error'])) {
            throw new ElasticsearchException($this->response['body']['error']['reason']);
        }

        return (! empty($this->response['body']));
    }

    /**
     * Gets a document from the index
     *
     * @param string $index index name e.g development_posts
     * @param integer $id
     * @return array
     */
    public function get(string $index, int $id): array
    {
        $this->response = $this->sendRequest('GET', "{$this->url}/{$index}/_doc/{$id}");

        if (isset($this->response['body']['error'])) {
            throw new ElasticsearchException($this->response['body']['error']['reason']);
        }
        if (isset($this->response['body']['found']) and $this->response['body']['found']) {
            return $this->response['body']['_source'];
        }
        throw new NotFoundException(sprintf('Document `%s` in index `%s` does not exist', $id, $index));
    }

    /**
     * Checks if a document exists
     *
     * @param string $index index name e.g development_posts
     * @param integer $id
     * @return bool
     */
    public function exists(string $index, int $id) : bool
    {
        $this->response = $this->sendRequest('HEAD', "{$this->url}/{$index}/_doc/{$id}");

        if ($this->response['statusCode'] !== 404 and isset($this->response['body']['error'])) {
            throw new ElasticsearchException($this->response['body']['error']['reason']);
        }

        return ($this->response['statusCode'] === 200);
    }

    /**
     * Deletes a document from the index
     *
     * @param string $index
     * @param integer $id
     * @return bool
     */
    public function delete(string $index, int $id) : bool
    {
        $this->response = $this->sendRequest('DELETE', "{$this->url}/{$index}/_doc/{$id}");

        if (isset($this->response['body']['error'])) {
            throw new ElasticsearchException($this->response['body']['error']['reason']);
        }

        return (isset($this->response['body']['result']) and $this->response['body']['result'] === 'deleted');
    }

    /**
     * Carries out a search using either query string or using request body
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-body.html
     *
     * @param string $index index name e.g development_posts
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
    public function search(string $index, $query): array
    {
        $url = "{$this->url}/{$index}/_search";

        if (is_string($query)) {
            $url .= '?' . http_build_query(['q' => $query]);
            $query = null;
        }
        $this->response = $this->sendRequest('GET', $url, $query);

        if (isset($this->response['body']['error'])) {
            throw new ElasticsearchException($this->response['body']['error']['reason']);
        }

        return $this->convertResults($this->response['body']);
    }

    /**
     * Counts documents in an index
     *
     * @param string $index index name e.g development_posts
     * @param array $query ['title'=>'how to']
     * @return int
     */
    public function count(string $index, array $query = null): int
    {
        $data = [];
        if ($query) {
            $data = ['query' => ['term' => $query]];
        }

        $this->response = $this->sendRequest('GET', "{$this->url}/{$index}/_count", $data);

        if (isset($this->response['body']['error'])) {
            throw new ElasticsearchException($this->response['body']['error']['reason']);
        }

        return $this->response['body']['count'];
    }

    /**
     * Sends the request to the Elasticsearch Server
     *
     * @param string $method
     * @param string $url
     * @param array $data
     * @return array
     */
    public function sendRequest(string $method, string $url, array $data = null): array
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout); //timeout in seconds

        switch ($method) {
            case 'GET':
                curl_setopt($curl, CURLOPT_HTTPGET, true);
                break;
            case 'HEAD':
                curl_setopt($curl, CURLOPT_NOBODY, true);
                break;
            default:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
                break;
        }

        if ($data) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $body = curl_exec($curl);
        if ($body === false) {
            $code = curl_errno($curl);
            $errorMessage = curl_error($curl);
            $status = ($code === CURLE_OPERATION_TIMEOUTED) ? 500 : 504; // error 500 or gateway timeout
            curl_close($curl);

            throw new ElasticsearchException($errorMessage, $status);
        }

        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        return ['statusCode' => $statusCode, 'body' => json_decode($body, true)];
    }

    /**
       * Converts results from search into simple array.
       *
       *  [
       *      [ 'id' => 1234, 'title' => 'this is test' ]
       *  ]
       *
       * @param array $this->response
       * @return array
       */
    protected function convertResults(array $response): array
    {
        $out = [];
        if (isset($this->response['body']['hits']) and $this->response['body']['hits']['total']['value'] > 0) {
            foreach ($this->response['body']['hits']['hits'] as $record) {
                $new = [
                    'id' => $record['_id'],
                ];
                $new += $record['_source'];
                $out[] = $new;
            }
        }

        return $out;
    }
}
