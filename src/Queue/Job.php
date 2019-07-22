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
 * This is a Queue System with a MySQL backend.
 * @todo investigate using pcntl_signal/ pcntl_alarm for timing out tasks
 */

namespace Origin\Queue;

use Origin\Model\Entity;
use Origin\Model\Model;

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
    public function data(bool $array=false)
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
    * Will retry a job a maximum number of times or bury it (it will be marked as failed)
    *
    * @param integer $tries
    * @param string $strtotime
    * @return void
    */
    public function retry(int $tries, $strtotime = 'now')
    {
        $job = $this->entity;
        if ($job->tries < $tries) {
            $job->tries ++;
            $job->scheduled = date('Y-m-d H:i:s', strtotime($strtotime));
            return $this->release();
        }
        $this->failed();
    }

    /**
     * Sets the status of a job (and automatically releases it)
     * @param string $status
     * @return boolean
     */
    public function setStatus(string $status)  : bool
    {
        // Don't use entity since tries updated at database level
        $job = $this->Job->new([
            'id' => $this->entity->id,
            'status' => $status,
            'locked' => 0,
        ]);
        return $this->Job->save($job);
    }
}
