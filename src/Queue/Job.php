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

namespace Origin\Queue;

use \ArrayObject;
use Origin\Model\ModelRegistry;
use Origin\Queue\Engine\BaseEngine;
use Origin\Model\Exception\MissingModelException;

class Job
{
    /**
     * The name of the queue for this job
     *
     * @var string
     */
    public $queue = 'default';

    /**
     * The name of the connection to use
     *
     * @var string
     */
    public $connection = 'default';

    /**
     * A strtotime compatible string used to schedule when the job
     * will be run
     *
     * @example '+5 minutes' '2019-08-20 10:23:00'
     * @var string
     */
    public $schedule = 'now';

    /**
     * This is the display name for the job
     *
     * @var string
     */
    public $name = null;

    /**
     * Job identifier
     *
     * @var mixed
     */
    protected $id = null;
    
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
    protected $tries = 0;

    /**
     * The date this job was enqueued.
     *
     * @var string
     */
    protected $created = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->arguments = func_get_args();
        $this->id = uuid();
        
        if ($this->name === null) {
            $this->name = get_class($this);
        }
        
        if (! method_exists($this, 'execute')) {
            throw new Exception('Job must have an execute method');
        }

        $this->initialize();
    }

    /**
     * This is the construct hook
     *
     * @return void
     */
    public function initialize()
    {
    }

    /**
     * This is called just before execute
     *
     * @return void
     */
    public function startup()
    {
    }

    /**
     * This is called after execute
     *
     * @return void
     */
    public function shutdown()
    {
    }

    /**
     * This is the callback when an error has occured processing the job
     *
     * @return void
     */
    public function onError(Throwable $exception)
    {
    }

    /**
     * Setter and getter for Job ID
     *
     * @param string|int
     * @return
     */
    public function id($id = null)
    {
        if ($id === null) {
            return $this->id;
        }
        $this->id = $id;
    }

    /**
    * Gets the display name
    *
    * @return string
    */
    public function name() : string
    {
        if (isset($this->name)) {
            return $this->name;
        }

        return get_class($this);
    }

    /**
     * Returns the amount of times this job has been executed
     *
     * @return int
     */
    public function tries() : int
    {
        return $this->tries;
    }

    /**
     * Inkokes the execute method to run the job.
     *
     * @return void
     */
    public function runOld() : void
    {
        $this->tries ++;
        $this->startup();
        $this->execute(...$this->arguments);
        $this->shutdown();
    }

    /**
     * Returns the connection for the Queue
     *
     * @return void
     */
    public function connection() : BaseEngine
    {
        return Queue::connection($this->connection);
    }

    public function enqueue(array $options = [])
    {
    }

    /**
     * Inkokes the execute method to run the job.
     *
     * @return void
     */
    public function run() : void
    {
        $this->tries ++;
        try {
            $this->startup();
            $this->execute(...$this->arguments);
            $this->shutdown();
        } catch (\Exception $e) {
            $this->onException($e);
        }
    }
    /**
     * This callback is triggered on exception
     *
     * @param \Exception $exception
     * @return void
     */
    public function onException(\Exception $exception)
    {
    }

    /**
     * Retries a job
     *
     * @param array $options
     * @return void
     */
    public function retry(array $options = [])
    {
        $options += ['wait' => 'now','tries' => 3];
        $this->connection()->retry($this, $options['tries'], $options['wait']);
    }

    /**
     * Loads a model
     *
     * @param string $model
     * @param array $options
     * @return \Origin\Model\Model
     */
    public function loadModel(string $model, array $options = []) : Model
    {
        list($plugin, $alias) = pluginSplit($model);

        if (isset($this->{$alias})) {
            return $this->{$alias};
        }

        $this->{$alias} = ModelRegistry::get($model, $options);

        if ($this->{$alias}) {
            return $this->{$alias};
        }
        throw new MissingModelException($model);
    }

    /**
     * Dispatch the job with the given arguments.
     *
     * @return bool
     */
    public static function dispatch() : bool
    {
        $job = new static(...func_get_args());

        return Queue::connection($job->connection)->add($job);
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
            'queue' => $this->queue,
            'connection' => $this->connection,
            'arguments' => serialize(new ArrayObject($this->arguments)),
            'tries' => $this->tries,
            'created' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Deserializes data from Job::serialize
     *
     * @see https://api.rubyonrails.org/v6.0.0/classes/ActiveJob/Core.html
     * @param array $data
     * @return void
     */
    public function deserialize(array $data) : void
    {
        $this->id = $data['id'];
        $this->queue = $data['queue'];
        $this->connection = $data['connection'];
        $this->arguments = unserialize($data['arguments']);
        $this->tries = $data['tries'];
        $this->created = $data['created'];
    }
}
