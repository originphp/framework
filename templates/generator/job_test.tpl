<?php
namespace %namespace%\Test\Job;

use Origin\TestSuite\OriginTestCase;
use App\Job\%class%Job;

class %class%JobTest extends OriginTestCase
{
    public function testExecute()
    {
        $result = (new %class%Job())->dispatchNow();
        $this->assertTrue($result); 
    }
}