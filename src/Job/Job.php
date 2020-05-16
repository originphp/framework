<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Origin\Job;

use \ArrayObject;
use Origin\Log\Log;
use Origin\Core\HookTrait;
use Origin\Model\ModelTrait;
use Origin\Security\Security;
use Origin\Job\Engine\BaseEngine;
use Origin\Core\CallbackRegistrationTrait;

/**
 * (new SendUserWelcomeEmail($user))->dispatch();
 * (new SendUserWelcomeEmail($user))->dispatch(['wait' => '+5 minutes']);
 */

class Job
{
    use ModelTrait,HookTrait,CallbackRegistrationTrait;
    /**
     * This is the display name for the job
     *
     * @example SendWelcomeEmail
     *
     * @var string
     */
    protected $name = null;

    /**
     * The name of the queue for this job
     *
     * @var string
     */
    protected $queue = 'default';

    /**
     * The name of the queue connection to use
     *
     * @var string
     */
    protected $connection = 'default';

    /**
     * Default wait time before dispatching the job, this is a strtotime compatible
     * string. e.g '+5 minutes' or '+1 day' etc
     *
     * @example '+30 minutes'
     * @var string
     */
    protected $wait = null;

    /**
     * The default timeout in seconds. Set to false
     * to disable
     *
     * @var integer
     */
    protected $timeout = 60;

    /**
     * Job identifier
     *
     * @var mixed
     */
    protected $id = null;
    
    /**
     * Adapter id
     *
     * @var mixed
     */
    protected $backendId = null;

    /**
     * These are the arguments that will be passed on to execute
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * Number of times this has been executed
     *
     * @var integer
     */
    protected $attempts = 0;

    /**
     * The date this job was enqueued.
     *
     * @var string
     */
    protected $enqueued = null;

    /**
     * If retry is called the info is stored here.
     *
     * @var array
     */
    protected $retryOptions = null;

    /**
     * Constructor
     * @internal no dependency injection here because it would also have be serialized and there is the initialize
     * issue as well, Only execute method
     */
    public function __construct(array $options = [])
    {
        $options += ['wait' => $this->wait,'queue' => $this->queue];

        $this->wait = $options['wait'];
        $this->queue = $options['queue'];
        
        $this->id = Security::uuid(['macAddress' => true]);
        
        if ($this->name === null) {
            list($namespace, $name) = namespaceSplit(get_class($this));
  
            $this->name = substr($name, 0, -3);
        }
    }

    /**
     * Registers a callback to be called before a job is queued
     *
     * @param string $method
     * @return void
     */
    protected function beforeQueue(string $method) : void
    {
        $this->registerCallback('beforeQueue', $method);
    }

    /**
    * Registers a callback to be called before a job is queued
    *
    * @param string $method
    * @return void
    */
    protected function afterQueue(string $method) : void
    {
        $this->registerCallback('afterQueue', $method);
    }

    /**
     * Registers a callback to be called before a job is dispatch
     *
     * @param string $method
     * @return void
     */
    protected function beforeDispatch(string $method) : void
    {
        $this->registerCallback('beforeDispatch', $method);
    }

    /**
    * Registers a callback to be called before a job is dispatch
    *
    * @param string $method
    * @return void
    */
    protected function afterDispatch(string $method) : void
    {
        $this->registerCallback('afterDispatch', $method);
    }

    /**
     * Gets the id for this job
     *
     * @return string
     */
    public function id() : string
    {
        return $this->id;
    }

    /**
     * Sets/gets the id by backend if any
     *
     * @param int|string $id
     * @return int|string|void
     */
    public function backendId($id = null)
    {
        if ($id === null) {
            return $this->backendId;
        }
        $this->backendId = $id;
    }

    /**
    * Returns the connection for the Queue
    *
    * @return \Origin\Job\Engine\BaseEngine;
    */
    public function connection() : BaseEngine
    {
        $connection = env('ORIGIN_ENV') === 'test' ? 'test' : $this->connection;

        return Queue::connection($connection);
    }

    /**
     * Dispatches the job to the queue with the given arguments.
     *
     * @return bool
     */
    public function dispatch() : bool
    {
        $this->arguments = func_get_args();
        $this->enqueued = date('Y-m-d H:i:s');

        $this->dispatchCallbacks('beforeQueue', [$this->arguments]);
        $result = $this->connection()->add($this, $this->wait ?: 'now');
        $this->dispatchCallbacks('afterQueue', [$this->arguments]);

        return $result;
    }

    /**
     * Dispatches the job immediately with the given arguments.
     *
     * @return bool
     */
    public function dispatchNow() : bool
    {
        $this->attempts ++;
        $this->arguments = func_get_args(); // proces the arguments

        try {
            $this->executeHook('initialize');
            $this->executeHook('startup');

            if ($this->dispatchCallbacks('beforeDispatch', [$this->arguments])) {
                $this->execute(...$this->arguments);
                $this->dispatchCallbacks('afterDispatch', [$this->arguments]);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        
            if ($this->enqueued) {
                $this->connection()->fail($this);
            }

            $this->executeHook('onError', [$e]);

            if ($this->enqueued && $this->retryOptions) {
                $this->connection()->retry($this, $this->retryOptions['limit'], $this->retryOptions['wait']);
            }
            
            $this->executeHook('shutdown');

            return false;
        }

        if ($this->enqueued) {
            $this->connection()->success($this);
        }

        $this->executeHook('onSuccess', $this->arguments);
        $this->executeHook('shutdown');

        return true;
    }

    /**
     * Dispatches callbacks, if stopped it will return false
     *
     * @param string $callback
     * @return bool continue
     */
    private function dispatchCallbacks(string $callback, array $arguments = []) : bool
    {
        foreach ($this->registeredCallbacks($callback) as $method => $options) {
            $this->validateCallback($method);
            if ($this->$method(...$arguments) === false) {
                return false;
            }
        }

        return true;
    }

    /**
    * Retries a job
    *
    * @param array $options The following option keys are supported :
    *   - wait: a strtotime comptabile string defaults to 5 seconds. e.g. '+ 5 minutes'
    *   - limit: The maximum number of retries to do. Default:3
    * @return bool
    */
    public function retry(array $options = []) : bool
    {
        $options += ['wait' => '+ 5 seconds','limit' => 3];

        if ($this->attempts < $options['limit'] + 1) {
            $this->retryOptions = $options;

            return true;
        }

        return false;
    }

    /**
     * Gets the number of attempts
     *
     * @return int
     */
    public function attempts() : int
    {
        return $this->attempts;
    }

    /**
     * Gets an array of the arguments that will be called with execute
     *
     * @return array
     */
    public function arguments() : array
    {
        return $this->arguments;
    }

    /**
     * Returns an array of data to be passed to connection
     * to be serialized
     */
    public function serialize() : array
    {
        return [
            'className' => get_class($this),
            'id' => $this->id,
            'backendId' => $this->backendId,
            'queue' => $this->queue,
            'arguments' => serialize(new ArrayObject($this->arguments)),
            'attempts' => $this->attempts,
            'enqueued' => $this->enqueued,
            'serialized' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Deserializes data from Job::serialize
     *
     * @param array $data
     * @return void
     */
    public function deserialize(array $data) : void
    {
        $this->id = $data['id'];
        $this->backendId = $data['backendId'];
        $this->queue = $data['queue'];
        $this->arguments = (array) unserialize($data['arguments']); # unserialize object and convert to []
        $this->attempts = $data['attempts'];
        $this->enqueued = $data['enqueued'];
        $this->serialized = $data['serialized'];
    }

    /**
     * Schedules a job using wait and returns this (chainable)
     *
     * @param string $strtotime a strtotime compatiable string e.g '+5 hours' ,'2020-01-01 10:40:00'
     * @return \Origin\Job\Job
     */
    public function schedule(string $strtotime) : Job
    {
        $this->wait = $strtotime;

        return $this;
    }

    /**
     * Gets the queue name
     *
     * @return string
     */
    public function queue() : string
    {
        return $this->queue;
    }

    /**
     * Gets the timeout for this job
     *
     * @return integer
     */
    public function timeout() : int
    {
        return $this->timeout;
    }

    /**
     * Gets the display name for the job
     *
     * @return string
     */
    public function name() : string
    {
        return $this->name;
    }
}
