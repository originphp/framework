<?php
namespace App\Test\Publisher;

use Origin\Publisher\ListenerJob;
use Origin\TestSuite\OriginTestCase;

class SimpleListener
{
    public function create(object $object, bool $result)
    {
        $object->called = $result;
    }
}

class ErrorListener
{
    public function create(object $object)
    {
        $a = 1 / 0;
    }
}

class ListenerJobTest extends OriginTestCase
{
    public function testExecute()
    {
        $object = (object) ['called' => false];
        $result = (new ListenerJob())->dispatchNow(SimpleListener::class, 'create', [$object,true]);
        $this->assertTrue($result);
        $this->assertTrue($object->called);
    }

    public function testFail()
    {
        $object = (object) ['called' => false];
        $result = (new ListenerJob())->dispatchNow(ErrorListener::class, 'create', [$object]);
        $this->assertFalse($result);
    }
}
