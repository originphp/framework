<?php
namespace %namespace%\Job;
use App\Job\AppJob;

class %class%Job extends AppJob
{
    /**
    * This is the display name for the job
    *
    * @var string
    */
    public $name = '%human%';

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
    * Default wait time before dispatching the job, this is a strtotime compatible
    * string. e.g '+5 minutes' or '+1 day' etc
    *
    * @example '+30 minutes'
    * @var string
    */
    public $wait = null;

    /**
    * The default timeout in second
    *
    * @var integer
    */
    public $timeout = 60;

    /**
    * This is the construct hook
    *
    * @return void
    */
    public function initialize()
    {
    }

    /**
    * Place the job logic here and define the arguments
    * e.g. function execute(User $User,$records);
    */
    public function execute()
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
    * This callback is triggered on exception
    *
    * @param \Exception $exception
    * @return void
    */
    public function onException(\Exception $exception)
    {
    }
}