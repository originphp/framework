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

namespace Origin\Job\Engine;

use Redis;

use Origin\Job\Job;
use RedisException;
use Origin\Exception\Exception;

class RedisEngine extends BaseEngine
{
    /**
         * Redis Object
         *
         * @var \Redis
         */
    protected $Redis = null;

    protected $defaultConfig = [
        'engine' => 'Redis',
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
        'timeout' => 0,
        'persistent' => true, // Faster!!!
        'path' => null, // Path to redis unix socket
    ];

    # This code has been duplicated from RedisCache Engine

    /**
        * Constructor
        *
        * @param array $config  duration,prefix,path
        */
    public function initialize(array $config)
    {
        $msg = 'Redis extension not loaded.';
        if (extension_loaded('redis')) {
            $this->Redis = new Redis();
            if ($this->connect()) {
                return;
            }
            $msg = 'Error connecting to Redis server.';
        }
        throw new Exception($msg);
    }

    /**
     * Add a job to the queue
     *
     * @param \Origin\Queue\Job $job
     * @return null
     */
    public function add(Job $job, string $strtotime = 'now')
    {
        $serialized = $this->serialize($job);
   
        $when = strtotime($strtotime);

        if ($when <= time()) {
            $this->Redis->rpush('queue:'. $job->queue, $serialized);
        } else {
            $this->Redis->zadd('scheduled:' . $job->queue, $when, $serialized);
        }
    }

    /**
    * Get the next job from the queue
    *
    * @param string $queue
    * @return \Origin\Queue\Job|null
    */
    public function fetch(string $queue = 'default') : ?Job
    {
        $this->migrateScheduledJobs($queue);
        $serialized = $this->Redis->lpop('queue:'. $queue);

        return $serialized ? $this->deserialize($serialized) : null;
    }

    /**
     * Deletes a job from both the queue and scheduled
     *
     * @param \Origin\Queue\Job $job
     * @return null
     */
    public function delete(Job $job)
    {
        $serialized = $this->serialize($job);
        $this->Redis->lrem('queue:'. $job->queue, $serialized);
        $this->Redis->lrem('scheduled:'. $job->queue, $serialized);
    }
    /**
     * Handles a failed job
     *
     * @param \Origin\Queue\Job $job
     * @return null
     */
    public function fail(Job $job)
    {
        $serialized = $this->serialize($job);
      
        $this->Redis->rpush('failed:' . $job->queue, $serialized);
        $this->Redis->lrem('queue:'. $job->queue, $serialized);
    }

    /**
    * Handles a successful job
    *
    * @param \Origin\Queue\Job $job
    * @return null
    */
    public function success(Job $job)
    {
        $this->delete($job);
    }

    /**
    * Retries a failed job
    *
     * @param \Origin\Queue\Job $job
     * @param integer $tries
     * @param string $strtotime
     * @return null
     */
    public function retry(Job $job, int $tries, $strtotime = 'now')
    {
        if ($job->attempts() < $tries + 1) {
            $serialized = $this->serialize($job);
            $this->Redis->lrem('failed:'. $job->queue, $serialized);
            $this->add($job, $strtotime);
        }
    }

    /**
     * Scheduled jobs are stored in a seperate queue
     *
     * @param string $queue
     * @return void
     */
    protected function migrateScheduledJobs(string $queue) : void
    {
        $results = $this->Redis->zrangebyscore('scheduled:' . $queue, '-inf', time());
        if ($results) {
            foreach ($results as $serialized) {
                $this->Redis->rpush('queue:'. $queue, $serialized);
                $this->Redis->zrem('scheduled:' . $queue, $serialized);
            }
        }
    }

    protected function connect()
    {
        $result = false;
        try {
            if (! empty($this->config['path'])) {
                $result = $this->Redis->connect($this->config['path']);
            } elseif (! empty($this->config['persistent'])) {
                $result = $this->Redis->pconnect($this->config['host'], $this->config['port'], $this->config['timeout'], $this->persistentId());
            } else {
                $result = $this->Redis->connect($this->config['host'], $this->config['port'], $this->config['timeout']);
            }
        } catch (RedisException $e) {
            return false;
        }
        if ($result) {
            if (isset($this->config['password'])) {
                return $this->Redis->auth($this->config['password']);
            }
        }

        return $result;
    }

    /**
     * Returns a string id for persistent connections
     *
     * @return string
     */
    protected function persistentId() : string
    {
        if ($this->config['persistent'] === true) {
            return 'origin-php';
        }

        return (string) $this->config['persistent'];
    }

    /**
     * Gets the Redis Object
     *
     * @return \Redis
     */
    public function redis()
    {
        return $this->Redis;
    }
}
