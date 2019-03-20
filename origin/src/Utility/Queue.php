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
 * @todo investigate using pcntl_signal/ pcntl_alarm for timing out tasks
 */

namespace Origin\Utility;

use Origin\Model\Entity;
use Origin\Model\Model;
use Origin\Exception\InvalidArgumentException;
use Origin\Exception\Exception;

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
     * Holds the data
     *
     * @var Object
     */
    protected $data = null;

    public function __construct(Model $job, Entity $entity)
    {
        $this->Job = $job;
        $this->id = $entity->id;
        $this->entity = $entity;
    }

    /**
     * Returns the message data as an object
     *
     * @param boolean $array return as array instead of object
     * @return object|array
     */
    public function getData(bool $array=false)
    {
        return json_decode($this->entity->data, $array);
    }
    
    /**
     * Once the job is finished run this
     *
     * @return bool
     */
    public function executed() : bool
    {
        return $this->setStatus('executed');
    }

    /**
    * If there was an error run this so you can inspect later.
    *
    * @return bool
    */
    public function failed() : bool
    {
        return $this->setStatus('failed');
    }

    /**
     * Run this to delete the job
     *
     * @return boolean
     */
    public function delete() : bool
    {
        return $this->Job->delete($this->entity);
    }

    /**
     * Release a job backinto the queue
     *
     * @param string $strtotime a strtotime compatiable string, now,+5 minutes, 2022-12-31
     * @return void
     */
    public function release($strtotime = 'now') : bool
    {
        $job = $this->entity;
        $job->scheduled = date('Y-m-d H:i:s', strtotime($strtotime));
        return $this->setStatus('queued');
    }

    /**
    * Will retry a job a maximum number of times or bury it
    *
    * @param integer $tries
    * @param string $strtotime
    * @return void
    */
    public function retry(int $tries, $strtotime = 'now')
    {
        $job = $this->entity;
        if ($job->tries < $tries) {
            $job->scheduled = date('Y-m-d H:i:s', strtotime($strtotime));
            return $this->release();
        }
        $this->bury();
    }

    /**
     * Sets the status of a job (and automatically releases it)
     *
     * @param string $status
     * @return boolean
     */
    public function setStatus(string $status)  : bool
    {
        $job = $this->entity;
        $job->status = $status;
        $job->locked = 0;
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
     * Purge the executed Jobs from the queue
     *
     * @param string $queue
     * @return bool
     */
    public function purge(string $queue = null) : bool
    {
        $conditions = ['status'=> 'executed'];
        if ($queue) {
            $conditions['queue'] = $queue;
        }
        return $this->Job->deleteAll($conditions);
    }

    /**
     * Adds a job to the queue
     *
     * @param string $queue name of queue, letters, numbers, underscore and hyphens only.
     * @param array $data this is converted to JSON, and max length is 65535 chars
     * @param string $schedule when to process
     * @return int $id
     */
    public function add(string $queue = null, array $data = [], string $strtotime = 'now') :bool
    {
        if (!preg_match('/^[a-z0-9_-]+$/i', $queue)) {
            throw new InvalidArgumentException('Queue name can only contain letters, numbers, underscores and hypens');
        }
        $entity = $this->Job->new();

        $entity->queue = $queue;
        $entity->data = json_encode($data);
        $entity->status = 'queued';
        $entity->scheduled = date('Y-m-d H:i:s', strtotime($strtotime));

        if (mb_strlen($entity->data) >= 65535) {
            throw new InvalidArgumentException('Data string is longer than 65,535');
        }
        
        return $this->Job->save($entity);
    }


    /**
     * Fetches the next job from a queue and locks it. Remember to work with the queue in a try/catch block
     *
     * @param string $queue name of queue
     * @return \Origin\Model\Entity
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
        $this->Job->query("UPDATE {$this->Job->table} SET locked = 1 , tries = tries + 1, modified = '" . now() . "' WHERE id = {$id};");
        $this->Job->commit();
        if ($result) {
            return $result;
        }
        return false;
    }
      
    /**
    * Returns the Queue Job Model
    *
    * @return \Origin\Model\Model
    */
    public function model() : Model
    {
        return $this->Job;
    }
}
