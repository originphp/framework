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

/**
 * This is a Queue System with a MySQL backend. For now I want to keep it as one file, until packages are ready.
 */

namespace Origin\Utility;

use Origin\Model\Entity;
use Origin\Model\Model;
use Origin\Exception\InvalidArgumentException;

class Job
{
    /**
     * Holds the last id of Job
     *
     * @var int
     */
    public $id = null;

    /**
     * Holds the model for the job
     *
     * @var Model
     */
    protected $Job = null;

    /**
     * Holds the body
     *
     * @var Object
     */
    protected $body = null;

    public function __construct(Model $job, Entity $entity)
    {
        $this->Job = $job;
        $this->id = $entity->id;
        $this->body = json_decode($entity->body);
    }

    /**
     * Returns the payload body
     *
     * @return Object
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Marks the job as executed
     * @return bool
     */
    public function executed()
    {
        return $this->status('executed');
    }

    /**
     * Marks the job as failed
     *
     * @return bool
     */
    public function failed()
    {
        return $this->status('failed');
    }


    /**
     * Deletes the job from the queue
     *
     * @return bool
     */
    public function delete()
    {
        return $this->Job->delete($this->id);
    }

    public function status(string $status)
    {
        $job = $this->Job->newEntity([
            'id' => $this->id,
            'status' => $status,
            'locked' => 0
        ]);
        return $this->Job->save($job);
    }
}

class Queue
{
    /**
     * Holds the model for the job
     *
     * @var Model
     */
    protected $Job = null;

    /**
     * Constructor - accepts same config as model
     *
     * @param array $config
     */
    public function __construct(array $config=[])
    {
        $config += [
            'name'=>'Job',
            'table'=>'queue',
            'datasource'=>'default'
        ];
        $this->Job = new Model($config);
        $this->Job->loadBehavior('Timestamp');
    }

    /**
     * Adds a job to the queue
     *
     * @param string $queue name of queue, letters, numbers, underscore and hyphens only.
     * @param array $body this is converted to json, and max length is 65535 chars
     * @param string $schedule when to process
     * @return int $id
     */
    public function add(string $queue = null, array $body = [], string $strtotime = 'now')
    {
        if (!preg_match('/^[a-z0-9_-]+$/i', $queue)) {
            throw new InvalidArgumentException('Queue name can only contain letters, numbers, underscores and hypens');
        }
        $entity = $this->Job->newEntity();
        $entity->queue = $queue;
        $entity->body = json_encode($body);
        $entity->status = 'queued';
        $entity->scheduled = date('Y-m-d H:i:s', strtotime($strtotime));

        if (mb_strlen($entity->body) >= 65535) {
            throw new InvalidArgumentException('Body data is longer than 65,535');
        }
        
        return $this->Job->save($entity);
    }


    /**
     * Fetches the next job from a queue. Remeber to work with the queue in a try block
     *
     * @param string $queue name of queue
     * @return void
     */
    public function fetch(string $queue)
    {
        $conditions = ['queue'=>$queue,'status'=>'queued','locked'=>0,'scheduled <=' => date('Y-m-d H:i:s')];
        if ($result = $this->Job->find('first', ['conditions'=>$conditions])) {
            if ($this->claim($result->id)) {
                return new Job($this->Job, $result);
            }
        }
        return false;
    }


    /**
     * Claims a job for processing and record is set to locked, prevents multiple.
     *
     * @param int|string $id
     * @return bool|Entity;
     */
    protected function claim($id)
    {
        $this->Job->begin();
        $result = $this->Job->query("SELECT * FROM {$this->Job->table} WHERE id = {$id} AND locked = 0 FOR UPDATE;");
        $this->Job->query("UPDATE {$this->Job->table} SET locked = 1 , modified = '" . now() . "' WHERE id = {$id};");
        $this->Job->commit();
        if ($result) {
            return $result;
        }
        return false;
    }
    /**
     * Gets stuck jobs
     *  $stuck = $queue->stuck('-5 minutes');
     * @param string $strtotime a string to time valid string
     * @return void
     */
    public function stuck(string $strtotime ='-2 minutes')
    {
        return $this->Job->find('all', ['conditions'=>[
            'locked' => 1,
            'modified <' => date('Y-m-d H:i:s', strtotime($strtotime))
        ]]);
    }
    
    /**
    * Returns the Queue Job Model
    *
    * @return Model
    */
    public function model()
    {
        return $this->Job;
    }
}
