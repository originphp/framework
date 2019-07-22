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

namespace Origin\Queue;

use Origin\Model\Model;
use Origin\Exception\InvalidArgumentException;
use Origin\Queue\Job;

class Queue
{
    /**
     * Holds the model for the job
     *
     * @var \Origin\Model\Model
     */
    protected $Job = null;

    /**
     * Constructor - accepts same config as model
     *
     * @param array $config
     *    - name: model name example Job
     *    - table: table name for queue e.g. queue
     *    - datasource: which datasource to use
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
     * @return int|bool $id
     */
    public function add(string $queue = null, array $data = [], string $strtotime = 'now')
    {
        if (!preg_match('/^[\w.-]+$/i', $queue)) {
            throw new InvalidArgumentException('Queue name can only contain letters, numbers, underscores, hypens and dots.');
        }
    
        $entity = $this->Job->new();
        $entity->queue = $queue;
        $entity->data = json_encode($data);
        $entity->status = 'queued';
        $entity->scheduled = date('Y-m-d H:i:s', strtotime($strtotime));

        if (mb_strlen($entity->data) >= 65535) {
            throw new InvalidArgumentException('Data string is longer than 65,535');
        }
        return $this->Job->save($entity)?$this->Job->id:false;
    }


    /**
     * Fetches the next job from a queue and locks it. Remember to work with the queue in a try/catch block
     *
     * @param string $queue name of queue
     * @return \Origin\Model\Entity|bool
     */
    public function fetch(string $queue)
    {
        # Boolean fields on Postgre work differently and does not accept 0 only '0'
        $conditions = ['queue'=>$queue,'status'=>'queued','locked = \'0\' ','scheduled <=' => date('Y-m-d H:i:s')];
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
        $result = $this->Job->query("SELECT * FROM {$this->Job->table} WHERE id = :id AND locked = '0' FOR UPDATE;", ['id'=>$id]);
        $this->Job->query("UPDATE {$this->Job->table} SET locked = '1' , tries = tries + 1, modified = '" . now() . "' WHERE id = :id;", ['id'=>$id]);
        $this->Job->commit();
        
        return $result?$result:false;
    }
      
    /**
    * Returns the Queue Job Model
    * @internal this will likely be deprecated when engines are introduced.
    * @return \Origin\Model\Model
    */
    public function model() : model
    {
        return $this->Job;
    }
}
