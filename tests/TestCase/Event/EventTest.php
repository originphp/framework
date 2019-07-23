<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Event;

use Origin\Event\Event;

class AnotherSampleObject
{
}
class EventTest extends \PHPUnit\Framework\TestCase
{
    public function testNew()
    {
        $name = 'Test.event';
        $object = new AnotherSampleObject();
        $data = ['foo' => 'bar'];

        $event = new Event($name, $object, $data);
        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals($name, $event->name());
        $this->assertEquals($object, $event->subject());
        $this->assertEquals($data, $event->data());
        $this->assertFalse($event->isStopped());
        $event->stop();
        $this->assertTrue($event->isStopped());
    }

    public function testData()
    {
        $event = new Event('test.data');
        $event->data('1234');
        $this->assertEquals('1234', $event->data());
    }
    public function testResult()
    {
        $event = new Event('test.data');
        $event->result('abc');
        $this->assertEquals('abc', $event->result());
    }
}
