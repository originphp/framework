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

namespace Origin\Queue\Engine;

use Origin\Queue\Job;
use Origin\Core\ConfigTrait;

abstract class BaseEngine
{
    use ConfigTrait;

    /**
     * Add a job to the queue
     *
     * @param \Origin\Queue\Job $job
     * @return boolean
     */
    abstract public function add(Job $job) : bool;

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
     * @return boolean
     */
    abstract public function delete(Job $job) : bool;

    /**
     * Handles a failed job
     *
     * @param \Origin\Queue\Job $job
     * @return boolean
     */
    abstract public function fail(Job $job) : bool;

    /**
    * Handles a successful job
    *
    * @param \Origin\Queue\Job $job
    * @return boolean
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
            throw new Exception('Error JSON encoding data: ' . json_last_error());
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
            throw new Exception('Error JSON decoding data: ' . json_last_error());
        }

        $className = $unserialized['className'];
        $job = new $className();
        $job->deserialize($unserialized);

        return $job;
    }
}
