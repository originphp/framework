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
    protected $queue = 'default';

    /**
    * Default wait time before dispatching the job, this is a strtotime compatible
    * string. e.g '+5 minutes' or '+1 day' etc
    *
    * @example '+30 minutes'
    * @var string
    */
    protected $wait = null;

    /**
    * The default timeout in seconds
    *
    * @var integer
    */
    protected $timeout = 60;

    /**
    * This is called when the job is created for dispatching
    *
    * @return void
    */
    protected function initialize() : void
    {
    }

    /**
    * Place the job logic here and define the arguments
    * e.g. function execute(User $User,$records);
    */
    protected function execute() : void
    {
    }

    /**
    * This callback is triggered when an error occurs
    *
    * @param \Exception $exception
    * @return void
    */
    protected function onError(\Exception $exception) : void
    {
    }

    /**
    * Place the job logic here and define the arguments
    * e.g. function execute(User $User,$records);
    */
    protected function onSuccess() : void
    {
    }   
}