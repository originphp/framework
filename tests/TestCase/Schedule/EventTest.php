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
use LogicException;
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
        return new Event('callable', function () {
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

    public function testLimit()
    {
        $event = $this->eventFixture();
        $this->assertInstanceOf(Event::class, $event->limit(3));
        $this->assertEquals(3, $event->config()['max']);
    }

    public function testLimitCatch()
    {
        $event = new Event('callable', function () {
            $var = 'foo';
        });
        $event->limit(3);
     
        $this->assertTrue($event->execute());
        $this->assertTrue($event->execute());
        $this->assertTrue($event->execute());
        $this->assertFalse($event->execute());
        $this->assertEquals(3, count($event->pids()));
    }

    public function testProcesses()
    {
        $event = $this->eventFixture();
        $this->assertInstanceOf(Event::class, $event->processes(3));
        $this->assertEquals(3, $event->config()['processes']);
    }

    public function testWhen()
    {
        $event = $this->eventFixture();
        $this->assertInstanceOf(Event::class, $event->when(function () {
            return true;
        }));
    }

    public function testSkip()
    {
        $event = $this->eventFixture();
        $this->assertInstanceOf(Event::class, $event->skip(function () {
            return true;
        }));
    }

    public function testStartStop()
    {
        $event = new Event('command', 'sleep 30');
        $this->assertTrue($event->start());

        $process = $event->getProcess();
        $this->assertTrue($process->isRunning());

        $event->stop();
    
        $this->assertFalse($process->isRunning());
    }

    public function testStopLogicException()
    {
        $event = $this->eventFixture();
        $this->expectException(LogicException::class);
        $event->stop();
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

    public function testMinutes()
    {
        $this->assertEquals('*/5 * * * *', ($this->eventFixture())->every5Minutes()->expression());
        $this->assertEquals('*/10 * * * *', ($this->eventFixture())->every10Minutes()->expression());
        $this->assertEquals('*/15 * * * *', ($this->eventFixture())->every15Minutes()->expression());
        $this->assertEquals('*/20 * * * *', ($this->eventFixture())->every20Minutes()->expression());
        $this->assertEquals('*/30 * * * *', ($this->eventFixture())->every30Minutes()->expression());
    }

    public function testinMaintenanceMode()
    {
        $event = $this->eventFixture();
        $this->assertArrayHasKey('maintenanceMode', $event->config());

        $this->assertFalse($event->config()['maintenanceMode']);
        $this->assertInstanceOf(Event::class, $event->evenInMaintenanceMode());
        $this->assertTrue($event->config()['maintenanceMode']);
    }

    public function testinBackground()
    {
        $event = new Event('callable', function () {
            return true;
        });
        $this->assertArrayHasKey('background', $event->config());

        $this->assertFalse($event->config()['background']);
        $this->assertInstanceOf(Event::class, $event->background());
        $this->assertTrue($event->config()['background']);
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
        $obj = new stdClass();
        $obj->wasCalled = false;

        $event = $this->eventFixture();

        $closure = function () use ($obj) {
            $obj->wasCalled = true;
        };

        $this->assertFalse($obj->wasCalled);
        $event->before($closure);
        $event->execute();
        $this->assertTrue($obj->wasCalled);
    }

    public function testAfterCallback()
    {
        $obj = new stdClass();
        $obj->wasCalled = false;

        $event = $this->eventFixture();

        $closure = function () use ($obj) {
            $obj->wasCalled = true;
        };

        $this->assertFalse($obj->wasCalled);
        $event->after($closure);
        $event->execute();
        $this->assertTrue($obj->wasCalled);
    }

    public function testOnSuccess()
    {
        $obj = new stdClass();
        $obj->success = false;
        $obj->error = false;

        $event = new Event('command', 'ls -lah');
        $event->everyMinute()->onSuccess(function () use ($obj) {
            $obj->success = true;
        })->onError(function () use ($obj) {
            $obj->error = true;
        });

        $event->execute();

        $this->assertTrue($obj->success);
        $this->assertFalse($obj->error);
    }

    public function testOnError()
    {
        $obj = new stdClass();
        $obj->success = false;
        $obj->error = false;

        $event = new Event('command', 'foo');
        $event->everyMinute()->onSuccess(function () use ($obj) {
            $obj->success = true;
        })->onError(function () use ($obj) {
            $obj->error = true;
        });

        $event->execute();

        $this->assertFalse($obj->success);
        $this->assertTrue($obj->error);
    }

    public function testIsDueMaintenanceMode()
    {
        $event = $this->eventFixture()->everyMinute();
                
        $this->assertTrue($event->isDue());

        file_put_contents(tmp_path('maintenance.json'), '[]');

        $this->assertFalse($event->isDue());

        $event->evenInMaintenanceMode();

        $this->assertTrue($event->isDue());
        
        unlink(tmp_path('maintenance.json'));
    }

    /**
     * Test the various ID are generated without errors
     *
     * @return void
     */
    public function testId()
    {
        $event = new Event('callable', function () {
            return true;
        });
        $this->assertEquals('16e4afeadf8a', $event->id());

        $callable = new MyCallable;
        $event = new Event('callable', $callable, [123]);
        $this->assertEquals('221d9d9ddf34', $event->id());

        $job = new EventTestJob();
        $event = new Event('job', $job);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{12}+/', $event->id());
    }
}
