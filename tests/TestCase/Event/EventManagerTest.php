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
use Origin\Event\EventManager;

class SampleObject
{
    public $count = 0;
    public function implementedEvents()
    {
        return [
            'Something.startup' => 'startup',
            'Something.shutdown' => 'shutdown',
        ];
    }
    public function __construct()
    {
        $manager = EventManager::instance();
        $manager->subscribe($this);
        
        $manager->dispatch('Something.startup');
        $manager->dispatch('Something.shutdown');
    }
    public function startup(Event $event)
    {
        $this->count = $this->count + 1;
    }
    public function shutdown(Event $event)
    {
        $this->count = $this->count + 10;
    }
}
class EventManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testInstance()
    {
        $this->assertInstanceOf(EventManager::class, EventManager::instance());
    }
    public function testDispatchNoListener()
    {
        $manager = EventManager::instance();
        $this->assertFalse($manager->dispatch('Test.dispatch'));
    }
    public function testDispatch()
    {
        $manager = EventManager::instance();
        $manager->listen('Test.dispatch', function (Event $event) {
            return 'ok';
        });
        $event = $manager->new('Test.dispatch');
        $this->assertTrue($manager->dispatch($event));
        $this->assertEquals('ok', $event->result());
    }

    public function testDispatchStop()
    {
        $manager = EventManager::instance();

        $manager->listen('Test.dispatchStop', function (Event $event) {
            $event->data($event->data() + 1);
        });
        $manager->listen('Test.dispatchStop', function (Event $event) {
            return false;
        });
        $manager->listen('Test.dispatchStop', function (Event $event) {
            $event->data($event->data() + 1);
        });

        $event = $manager->new('Test.dispatchStop', $this, 100);
        $this->assertTrue($manager->dispatch($event));
        $this->assertEquals(101, $event->data());
        $this->assertFalse($event->result());
    }

    public function testDispatchPriority()
    {
        $manager = EventManager::instance();

        $manager->listen('Test.dispatchPriority', function (Event $event) {
            $event->data($event->data() * 3);
        }, 2);
        $manager->listen('Test.dispatchPriority', function (Event $event) {
            $event->data($event->data() + 1);
        }, 1);
        $event = $manager->new('Test.dispatchPriority', $this, 100);
        $manager->dispatch($event);
        $this->assertEquals(303, $event->data());
    }

    public function testSubscribe()
    {
        $sampleObject = new SampleObject();
        $this->assertEquals(11, $sampleObject->count);
    }
}
