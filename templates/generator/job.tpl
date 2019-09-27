<?php
namespace %namespace%\Job;
use App\Job\ApplicationJob;

class %class%Job extends ApplicationJob
{
    /**
    * The name of the queue for this job
    *
    * @var string
    */
    public $queue = 'default';

    /**
    * Default wait time before dispatching the job, this is a strtotime compatible
    * string. e.g '+5 minutes' or '+1 day' etc
    *
    * @example '+30 minutes'
    * @var string
    */
    public $wait = null;

    /**
    * The default timeout in seconds
    *
    * @var integer
    */
    public $timeout = 60;

    /**
    * This is called when the job is created for dispatching
    *
    * @return void
    */
    public function initialize() : void
    {
    }


    /**
    * This is called before execute
    *
    * @return void
    */
    public function startup() : void
    {
    }

    /**
    * Place the job logic here and define the arguments
    * e.g. function execute(User $User,$records);
    */
    public function execute() : void
    {



    }

    /**
    * This callback is triggered when an error occurs
    *
    * @param \Exception $exception
    * @return void
    */
    public function onError(\Exception $exception) : void
    {

    }

    /**
    * Place the job logic here and define the arguments
    * e.g. function execute(User $User,$records);
    */
    public function onSuccess() : void
    {

    }


    /**
    * This is called after execute
    *
    * @return void
    */
    public function shutdown() : void
    {
    }

    
}