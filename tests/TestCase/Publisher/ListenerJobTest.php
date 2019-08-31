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
class ListenerJobTest extends OriginTestCase
{
    public function testExecute()
    {
        $object = (object) ['called' => false];
        $result = (new ListenerJob())->dispatchNow(SimpleListener::class, 'create', [$object,true]);
        $this->assertTrue($result);
        $this->assertTrue($object->called);
    }
}
