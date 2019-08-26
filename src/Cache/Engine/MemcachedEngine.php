<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
/**
 * Docker Installation
 *  Add to docker-compose.yml - server host will be called 'memached'
 *  memcached:
 *   image: memcached
 * In the docker file add the following line to where it installs exentions etc
 *   php-memcached \
 *
 * For non docker containers:
 * Ubunut/Debian based
 * Instructions to install memcached server and php extension
 * apt-get update
 * apt-get install memcached
 * apt-get install php-memcached
 *
 * Redhat/Fedora based
 * yum update
 * yum install memcached
 * yum install php-memcached
 *
 * If you are working in Docker, then you will need to adjust the docker file and build it again using docker-compose build
 */

namespace Origin\Cache\Engine;

use Memcached;
use Origin\Exception\Exception;

class MemcachedEngine extends BaseEngine
{

    /**
     * Memcached Object
     *
     * @var Memcached
     */
    protected $Memcached = null;

    protected $defaultConfig = [
        'host' => '127.0.0.1',
        'port' => '11211',
        'servers' => [], // if this is defined then a pool is used instead [memached]comptabile with http://php.net/manual/en/memcached.addservers.php
        'username' => null,
        'password' => null,
        'persistent' => false, // set true or string id e.g my-app-xx-, my-app-yyy etc
        'path' => null, // Path to memcached unix socket,
        'duration' => 3600, // memcache has limits if more than 30 days
        'prefix' => 'origin_',
    ];

    /**
     * Constructor
     *
     * @param array $config
     */
    public function initialize(array $config)
    {
        $msg = 'Memcached extension not loaded.';
        if (extension_loaded('memcached')) {
            $id = null;
            if ($this->config['persistent']) {
                $id = $this->persistentId();
            }
            $this->Memcached = new Memcached($id);
            $msg = 'Error connecting to Memcached server(s).';
            if ($this->connect()) {
                // Login
                if ($this->config['username'] and $this->config['password']) {
                    $this->Memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
                    $this->Memcached->setSaslAuthData($this->config['username'], $this->config['password']);
                }
                if ($this->Memcached->set('---origin-php---', true, 1)) {
                    return true;
                }
            }
        }
        throw new Exception($msg);
    }

    protected function connect()
    {
        if (! empty($this->config['servers'])) {
            return $this->Memcached->addServers($this->config['servers']);
        }
        extract($this->config);
        if ($this->config['path']) {
            $host = $this->config['path'];
            $port = 0;
        }

        return $this->Memcached->addServer($host, $port);
    }

    /**
     * Sets a value in the cache
     *
     * @param string $key  max 250 chars
     * @param mixed $value
     * @return bool
     */
    public function write(string $key, $value) :bool
    {
        return $this->Memcached->set($this->key($key), $value, $this->config['duration']);
    }
    /**
     * Gets the value;
     * @todo returns false always
     * @param string $key
     * @return mixed
     */
    public function read(string $key)
    {
        return $this->Memcached->get($this->key($key));
    }
    /**
     * Checks if a key exists in the cache
     *
     * @param string $key
     * @return boolean
     */
    public function exists(string $key) :bool
    {
        $this->Memcached->get($this->key($key));

        return ($this->Memcached->getResultCode() === Memcached::RES_SUCCESS);
    }
    /**
     * Deletes a kehy from the cache
     *
     * @param string $key
     * @return boolean
     */
    public function delete(string $key) :bool
    {
        return $this->Memcached->delete($this->key($key));
    }

    /**
     * Clears the Cache
     *
     * @return bool
     */
    public function clear() :bool
    {
        return $this->Memcached->flush();
    }

    /**
     * Increases a value
     *
     *  Cache::write('my_value',100);
     *  $value = Cache::increment('my_value');
     *
     * @param string $key
     * @param integer $offset
     * @return integer
     */
    public function increment(string $key, int $offset = 1) : int
    {
        return $this->Memcached->increment($this->key($key), $offset);
    }

    /**
     * Decreases a value
     *
     * @param string $key
     * @param integer $offset
     * @return integer
     */
    public function decrement(string $key, int $offset = 1): int
    {
        return $this->Memcached->decrement($this->key($key), $offset);
    }
}
