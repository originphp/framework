<?php
declare(strict_types = 1);
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

use Origin\Job\Job;
use Origin\Core\ConfigTrait;
use Origin\Exception\Exception;

abstract class BaseEngine
{
    use ConfigTrait;

    /**
    * Constructor
    *
    * @param array $config
    */
    public function __construct(array $config = [])
    {
        $this->config($config);
        $this->initialize($config);
    }

    public function initialize(array $config)
    {
    }
    /**
     * Add a job to the queue
     *
     * @param \Origin\Queue\Job $job
     * @param string $strtotime
     * @return bool
     */
    abstract public function add(Job $job, string $strtotime = 'now') : bool;

    /**
    * Get the next job from the queue
    *
    * @param string $queue
    * @return \Origin\Queue\Job|null
    */
    abstract public function fetch(string $queue = 'default') : ?Job;

    /**
     * Deletes a job
     *
     * @param \Origin\Queue\Job $job
     * @return bool
     */
    abstract public function delete(Job $job) : bool;

    /**
     * Handles a failed job
     *
     * @param \Origin\Queue\Job $job
     * @return bool
     */
    abstract public function fail(Job $job) : bool;

    /**
    * Handles a successful job
    *
    * @param \Origin\Queue\Job $job
    * @return bool
    */
    abstract public function success(Job $job) : bool;

    /**
    * Retries a job
    *
     * @param \Origin\Queue\Job $job
     * @param integer $tries
     * @param string $strtotime
     * @return bool
     */
    abstract public function retry(Job $job, int $tries, $strtotime = 'now') : bool;

    /**
    * Serializes a job
    *
    * @return string
    */
    public function serialize(Job $job) : string
    {
        $serialized = json_encode($job->serialize());

        // https://www.php.net/manual/en/function.json-last-error.php
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON decoding data Error: ' . json_last_error());
        }

        return $serialized;
    }

    /**
     * Returns a new job instance using serialized data
     *
     * @param string $data
     * @return \Origin\Queue\Job
     */
    public function deserialize(string $data) : Job
    {
        $unserialized = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON decoding data Error: ' . json_last_error());
        }

        $className = $unserialized['className'];
        $job = new $className();
        $job->deserialize($unserialized);

        return $job;
    }
}
