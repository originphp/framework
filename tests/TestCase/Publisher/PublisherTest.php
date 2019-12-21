<?php
namespace App\Test\Publisher;

use Origin\Model\Entity;
use Origin\Publisher\Listener;
use Origin\Publisher\Publisher;
use Origin\Publisher\PublisherTrait;
use Origin\TestSuite\OriginTestCase;
use Origin\Core\Exception\InvalidArgumentException;

class MockListener extends Listener
{
    public function create(Entity $user, $id)
    {
        $user->id = $id;
    }
    public function save(Entity $user, bool $result)
    {
        $user->name = 'jason';

        return $result;
    }
    public function throwError()
    {
        $a = 1 / 0;
    }
}
class AnotherMockListener extends Listener
{
    public function create(Entity $user, $id)
    {
        $user->id = 'none';
    }
    public function save(Entity $user, bool $result)
    {
        $user->name = 'freddy';

        return true;
    }
}
class SimpleObject
{
    use PublisherTrait;
}

class MockPublisher extends Publisher
{
    public static function destroy()
    {
        static::$instance = null;
    }
}

/**
 * @property \App\Model\User $User
 */
class PublisherTest extends OriginTestCase
{
    protected $fixtures = ['Origin.User','Origin.Queue'];

    public function startup() : void
    {
        $this->loadModel('User');
    }

    public function testSubscribe()
    {
        $simple = new SimpleObject();
        $simple->subscribe(new MockListener);

        $user = $this->User->find('first');
        $simple->publish('create', $user, 12345);
        $this->assertEquals(12345, $user->id);


        $this->assertFalse($simple->subscribe(1234));
    }

    public function testSubscribeString()
    {
        $simple = new SimpleObject();
        $simple->subscribe(MockListener::class);

        $user = $this->User->find('first');
        $simple->publish('create', $user, 12345);
        $this->assertEquals(12345, $user->id);
    }

    public function testSubscribeChained()
    {
        $simple = new SimpleObject();
        $simple->subscribe(new MockListener);
        $simple->subscribe(new AnotherMockListener);
        $user = $this->User->find('first');
        $simple->publish('create', $user, 12345);
        $this->assertEquals('none', $user->id);
    }

    public function testSubscribeOptionsOn()
    {
        $simple = new SimpleObject();
        $simple->subscribe(new MockListener);
        $simple->subscribe(new AnotherMockListener, ['on' => 'save']);
        $user = $this->User->find('first');
        $simple->publish('create', $user, 12345);
        $this->assertEquals(12345, $user->id);
    }

    public function testSubscribeFail()
    {
        $simple = new SimpleObject();
        
        $simple->subscribe(new MockListener);
        $simple->subscribe(new AnotherMockListener);

        $user = $this->User->find('first');
        $simple->publish('save', $user, false);
        $this->assertEquals('jason', $user->name);

        $user = $this->User->find('first');
        $simple->publish('save', $user, true);
        $this->assertEquals('freddy', $user->name);
    }

    public function testSubscribeQueue()
    {
        $simple = new SimpleObject();
        $simple->subscribe(MockListener::class, ['queue' => true]);
        $simple->subscribe(new AnotherMockListener);

        $user = $this->User->find('first');
        $simple->publish('create', $user, 12345);
        $this->assertEquals('none', $user->id); // Second subscribe is to test its reached
    }

    public function testSubscribeQueueFail()
    {
        $this->expectException(InvalidArgumentException::class);
        $simple = new SimpleObject();
        $simple->subscribe(new MockListener(), ['queue' => true]);
    }

    public function testInstance()
    {
        $this->assertInstanceOf(Publisher::class, MockPublisher::instance());
    }

    public function testGlobal()
    {
        $publisher = Publisher::instance();
        $publisher ->subscribe(MockListener::class, ['queue' => true]);

        $publisher = Publisher::instance();
        $this->assertNotEmpty($publisher->listeners());
        
        $publisher->clear();
        $publisher = Publisher::instance();
        $this->assertEmpty($publisher->listeners());
    }
}
