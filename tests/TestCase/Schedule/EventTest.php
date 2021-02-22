<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Origin\Test\Schedule;

use stdClass;
use Origin\Job\Job;
use Origin\Schedule\Event;
use InvalidArgumentException;

class MyCallable
{
    public $x = 0;

    public function __invoke($x)
    {
        $this->x = $x;
    }
}

class CallableWasInvoked
{
    public $invoked = false;

    public function __invoke()
    {
        $this->invoked = true;
    }
}

class MockEvent extends Event
{
    public function pids()
    {
        return $this->pids;
    }
}

class EventTestJob extends Job
{
    public $dispatched = false;

    /**
    * Dispatches the job to the queue with the given arguments.
    *
    * @return bool
    */
    public function dispatch(): bool
    {
        return $this->dispatched = true;
    }
}

class EventTest extends \PHPUnit\Framework\TestCase
{
    private function eventFixture()
    {
        return  new Event('callable', function () {
            return true;
        });
    }

    public function testWeekdays()
    {
        $event = $this->eventFixture();
        $this->assertInstanceOf(Event::class, $event->weekdays());
        $this->assertEquals('* * * * 1-5', $event->expression());
    }

    public function testQuarterly()
    {
        $event = $this->eventFixture();
        $this->assertInstanceOf(Event::class, $event->quarterly());
        $this->assertEquals('0 0 1 */3 *', $event->expression());
    }

    public function testYearly()
    {
        $event = $this->eventFixture();
        $this->assertInstanceOf(Event::class, $event->yearly());
        $this->assertEquals('0 0 1 1 *', $event->expression());
    }

    public function testBetween()
    {
        $event = $this->eventFixture();
        $this->assertInstanceOf(Event::class, $event->between(9, 17));
        $this->assertEquals('* 9-17 * * *', $event->expression());
    }

    public function testCount()
    {
        $obj = new stdClass();
        $obj->counter = 0;

        $event = new Event('callable', function ($obj) {
            $obj->counter ++;

            return true;
        }, [$obj]);

        $this->assertInstanceOf(Event::class, $event->count(2));
        $event->execute();
        $this->assertEquals(2, $obj->counter);
    }

    public function testLimit()
    {
        $obj = new stdClass();
        $obj->counter = 0;

        $event = new Event('callable', function ($obj) {
            $foo = 'bar'; // must be unique to other tests in the PID
            $obj->counter ++;

            return true;
        }, [$obj]);

        $this->assertInstanceOf(Event::class, $event->limit(2));
        $event->execute();
        $this->assertEquals(1, $obj->counter);
        $event->execute();
        $this->assertEquals(2, $obj->counter);
        $event->execute();
        $this->assertEquals(2, $obj->counter); # Counter does not increase as PID still running
    }

    public function testDaysOfWeek()
    {
        $event = new Event('callable', function () {
            return true;
        });

        $this->assertInstanceOf(Event::class, $event->sundays());
        $this->assertEquals('* * * * 0', $event->expression());

        $this->assertInstanceOf(Event::class, $event->mondays());
        $this->assertEquals('* * * * 1', $event->expression());

        $this->assertInstanceOf(Event::class, $event->tuesdays());
        $this->assertEquals('* * * * 2', $event->expression());

        $this->assertInstanceOf(Event::class, $event->wednesdays());
        $this->assertEquals('* * * * 3', $event->expression());

        $this->assertInstanceOf(Event::class, $event->thursdays());
        $this->assertEquals('* * * * 4', $event->expression());

        $this->assertInstanceOf(Event::class, $event->fridays());
        $this->assertEquals('* * * * 5', $event->expression());

        $this->assertInstanceOf(Event::class, $event->saturdays());
        $this->assertEquals('* * * * 6', $event->expression());
    }

    public function testExpressions()
    {
        $event = new Event('callable', function () {
            return true;
        });
      
        $this->assertInstanceOf(Event::class, $event->everyMinute());
        $this->assertEquals('* * * * *', $event->expression());

        $this->assertInstanceOf(Event::class, $event->hourly());
        $this->assertEquals('0 * * * *', $event->expression());
   
        $this->assertInstanceOf(Event::class, $event->daily());
        $this->assertEquals('0 0 * * *', $event->expression());
     
        $this->assertInstanceOf(Event::class, $event->weekly());
        $this->assertEquals('0 0 * * 0', $event->expression());
    
        $this->assertInstanceOf(Event::class, $event->monthly());
        $this->assertEquals('0 0 1 * 0', $event->expression());
    
        $this->assertInstanceOf(Event::class, $event->cron('* * * * *'));
        $this->assertEquals('* * * * *', $event->expression());
    
        # reset test
        $event->everyMinute();
        $this->assertEquals('* * * * *', $event->expression());

        $this->assertInstanceOf(Event::class, $event->weekly()->on(1));
        $this->assertEquals('0 0 * * 1', $event->expression());

        $this->assertInstanceOf(Event::class, $event->weekly()->at(15, 30));
        $this->assertEquals('30 15 * * 0', $event->expression());

        $this->expectException(InvalidArgumentException::class);
        $event->cron('foo');
    }

    public function testinMaintenanceMode()
    {
        $event = new Event('callable', function () {
            return true;
        });
        $this->assertFalse($event->runsInMaintenanceMode());
        $this->assertInstanceOf(Event::class, $event->inMaintenanceMode());
        $this->assertTrue($event->runsInMaintenanceMode());
    }

    public function testCallableDispatch()
    {
        $callable = new MyCallable;
        $event = new Event('callable', $callable, [123]);
        $event->execute();
        $this->assertEquals(123, $callable->x);
    }

    public function testCommandDispatch()
    {
        $event = new Event('command', 'php -v');
        $event->wait();
        $output = temp_name();

        $this->assertInstanceOf(Event::class, $event->output($output));
        $event->execute();
        $this->assertStringContainsString('Zend Technologies', file_get_contents($output));
    }

    public function testJobDispatch()
    {
        $job = new EventTestJob();
        $event = new Event('job', $job);
        $this->assertFalse($job->dispatched);
        $event->execute();
        $this->assertTrue($job->dispatched);
    }

    public function testBeforeCallback()
    {
        $callable = new CallableWasInvoked();
        $event = new Event('callable', $callable);
        
        $this->assertFalse($callable->invoked);
        $event->before($callable);
        $event->execute();
        $this->assertTrue($callable->invoked);
    }

    public function testAfterCallback()
    {
        $callable = new CallableWasInvoked();
        $event = new Event('callable', $callable);
        $this->assertFalse($callable->invoked);
        $event->after($callable);
        $event->execute();
        $this->assertTrue($callable->invoked);
    }

    public function testFilter()
    {
        $callable = new CallableWasInvoked();
        $event = new Event('callable', $callable);
      
        $return = $event->when(function () {
            return false;
        });
        $this->assertInstanceOf(Event::class, $return);

        $event->execute();
        $this->assertFalse($callable->invoked);

        $callable = new CallableWasInvoked();
        $event = new Event('callable', $callable);
      
        $event->when(function () {
            return true;
        });

        $event->execute();
        $this->assertTrue($callable->invoked);
    }

    public function testFilterBool()
    {
        $callable = new CallableWasInvoked();
        $event = new Event('callable', $callable);
      
        $return = $event->when(false);
        $this->assertInstanceOf(Event::class, $return);

        $event->execute();
        $this->assertFalse($callable->invoked);

        $callable = new CallableWasInvoked();
        $event = new Event('callable', $callable);
      
        $event->when(true);

        $event->execute();
        $this->assertTrue($callable->invoked);
    }

    public function testReject()
    {
        $callable = new CallableWasInvoked();
        $event = new Event('callable', $callable);
      
        $return = $event->skip(function () {
            return true;
        });
        $this->assertInstanceOf(Event::class, $return);

        $event->execute();
        $this->assertFalse($callable->invoked);

        $callable = new CallableWasInvoked();
        $event = new Event('callable', $callable);
      
        $event->skip(function () {
            return false;
        });

        $event->execute();
        $this->assertTrue($callable->invoked);
    }

    public function testRejectBool()
    {
        $callable = new CallableWasInvoked();
        $event = new Event('callable', $callable);
      
        $return = $event->skip(true);
        $this->assertInstanceOf(Event::class, $return);

        $event->execute();
        $this->assertFalse($callable->invoked);

        $callable = new CallableWasInvoked();
        $event = new Event('callable', $callable);
      
        $event->skip(false);

        $event->execute();
        $this->assertTrue($callable->invoked);
    }

    public function testId()
    {
        $event = new Event('callable', function () {
            return true;
        });
        $this->assertEquals('391d6f1a8706', $event->id());
    }
}
