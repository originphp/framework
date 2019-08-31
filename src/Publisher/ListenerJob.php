<?php
namespace Origin\Publisher;

use Origin\Job\Job;

class ListenerJob extends Job
{
    public $queue = 'listeners';

    public function execute(string $className, string $method, array $args = [])
    {
        ( new Publisher())->dispatch(new $className(), $method, $args);
    }

    public function onError(\Exception $exception)
    {
        $this->retry();
    }
}
