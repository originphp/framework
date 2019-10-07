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
use Origin\Model\Concern\Timestampable;
use Origin\Model\Model;
use Origin\Model\Entity;

class Queue extends Model
{
    use Timestampable;
}

class DatabaseEngine extends BaseEngine
{
    /**
     * Undocumented variable
     *
     * @var \Origin\Model\Model
     */
    protected $model = null;

    /**
     * Holds the default configuration
     *
     * @var array
     */
    protected $defaultConfig = [
        'engine' => 'Database',
        'connection' => 'default',
    ];

    /**
     * Adds a message to the queue
     *
     * @param \Origin\Job\Job $job
     * @param string $strtotime
     * @return boolean
     */
    public function add(Job $job, string $strtotime = 'now') : bool
    {
        $model = $this->model();
        $entity = $model->new([
            'queue' => $job->queue(),
            'data' => '[]',
            'status' => 'new',
            'scheduled' => date('Y-m-d H:i:s', strtotime($strtotime)),
            'created' => now(),
            'modified' => now(),
        ]);

        if (! $model->save($entity)) {
            return false;
        }

        // Serialize with MySQL id
        $job->backendId($entity->id);
        $entity->data = $this->serialize($job);
        $entity->status = 'queued';

        return $model->save($entity);
    }

    /**
    * Gets the next message in the queue
    *
    * @param string $queue
    * @return \Origin\Job\Job|null
    */
    public function fetch(string $queue = 'default') : ?Job
    {
        $record = $this->model()->find('first', [
            'conditions' => [
                'queue' => $queue,'status' => 'queued','locked' => null,'scheduled <=' => date('Y-m-d H:i:s'),
            ],
            'order' => ['id ASC'],
        ]);
        
        if ($record and $this->lockRecord($record)) {
            $job = $this->deserialize($record->data);

            return $job;
        }

        return null;
    }

    /**
     * Handles a failed job.
     * @internal in Job fail is called before OnException. If in other
     * engines the record is deleted, then job has to be rethought
     *
     * @param \Origin\Job\Job $job
     * @return boolean
     */
    public function fail(Job $job) : bool
    {
        if (! $job->backendId()) {
            return false;
        }

        return $this->updateDatabase([
            'id' => $job->backendId(),
            'status' => 'failed',
            'data' => $this->serialize($job),
            'locked' => null,
        ]);
    }

    /**
    * Handles a job that was successful
    *
    * @param \Origin\Job\Job $job
    * @return boolean
    */
    public function success(Job $job) : bool
    {
        if (! $job->backendId()) {
            return false;
        }

        return $this->delete($job);
    }

    /**
     * Deletes a Job from the queue
     *
     * @param \Origin\Job\Job $job
     * @return boolean
     */
    public function delete(Job $job) : bool
    {
        if (! $job->backendId()) {
            return false;
        }
        
        $entity = $this->model()->new([
            'id' => $job->backendId(),
        ]);

        return $this->model()->delete($entity);
    }

    /**
     * Retry a failed job for a certain amount of times
     *
     * @param \Origin\Job\Job $job
     * @param integer $tries
     * @param string $strtotime
     * @return bool
     */
    public function retry(Job $job, int $tries, $strtotime = 'now') : bool
    {
        if (! $job->backendId()) {
            return false;
        }
        
        if ($job->attempts() < $tries + 1) {
            return $this->updateDatabase([
                'id' => $job->backendId(),
                'status' => 'queued',
                'scheduled' => date('Y-m-d H:i:s', strtotime($strtotime)),
                'data' => $this->serialize($job), // # Update message
                'locked' => null,
            ]);
        }

        return true;
    }

    /**
     * Returns the configured model
     *
     * @return Model
     */
    public function model() : Model
    {
        if (! $this->model) {
            $this->model = new Queue([
                'name' => 'Queue',
                'alias' => 'queue',
                'table' => 'queue',
                'connection' => $this->config('connection'),
            ]);
        }

        return $this->model;
    }

    /**
     * Saves data to the database
     *
     * @param array $data
     * @return bool
     */
    protected function updateDatabase(array $data) : bool
    {
        $model = $this->model();
        $entity = $model->new($data);

        return $this->model()->save($entity);
    }

    /**
     * Locks a record
     *
     * @param Entity $record
     * @return boolean
     */
    protected function lockRecord(Entity $record) : bool
    {
        $model = $this->model();
        $model->begin();
        $result = $this->model->query(
            "SELECT * FROM {$model->table} WHERE id = :id AND locked IS NULL FOR UPDATE;",
            [
                'id' => $record->id,
            ]
        );

        $model->query(
            "UPDATE {$model->table} SET locked = :locked , modified = :modified WHERE id = :id;",
            [
                'id' => $record->id,
                'locked' => now(),
                'modified' => now(),
            ]
        );
        $model->commit();

        return (bool) $result;
    }
}
