<?php
namespace %namespace%\Test\Job;

use Origin\TestSuite\OriginTestCase;
use App\Job\%class%Job;

class %class%JobTest extends OriginTestCase
{
    public function testExecute()
    {
        $args = []; // args for execute method

        $job = new %class%Job(...$args);
        $this->assertTrue($job->run()); 

        // Do additional check depending upon job
    }
}