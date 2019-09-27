<?php
namespace %namespace%\Test\Job;

use Origin\TestSuite\OriginTestCase;
use %namespace%\Job\%class%Job;

class %class%JobTest extends OriginTestCase
{
    public function testExecute()
    {
        $result = (new %class%Job())->dispatchNow();
        $this->assertTrue($result); 
    }
}