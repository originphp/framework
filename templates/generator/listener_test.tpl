<?php
namespace %namespace%\Test\Listener;

use Origin\TestSuite\OriginTestCase;
use App\Listener\%class%Listener;

class %class%ListenerTest extends OriginTestCase
{
    public function testCreate()
    {
        $listener = new %class%Listener();
    }
}