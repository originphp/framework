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
namespace Origin\Test\TestCase\Schedule;

use stdClass;
use Origin\Schedule\Event;
use Origin\Mailer\MailerJob;
use Origin\Schedule\Schedule;
use Origin\Schedule\Exception\ScheduleException;
use Origin\Test\TestCase\Schedule\Task\MiscTask;
use Origin\Test\TestCase\Schedule\Task\BackupTask;

class AnotherCallableWasInvoked
{
    public $invoked = false;

    public function __invoke()
    {
        $this->invoked = true;
    }
}

class ScheduleTest extends \PHPUnit\Framework\TestCase
{
    public function testCommand()
    {
        $schedule = new Schedule(new MiscTask);
        $this->assertInstanceOf(Event::class, $schedule->command('ls', ['-lah']));
    }

    public function testJob()
    {
        $schedule = new Schedule(new MiscTask);
        $this->assertInstanceOf(Event::class, $schedule->job(new MailerJob));
    }

    public function testCall()
    {
        $schedule = new Schedule(new MiscTask);
        $this->assertInstanceOf(Event::class, $schedule->call(function () {
            return true;
        }));
    }

    public function testEvents()
    {
        $schedule = new Schedule(new MiscTask);
        
        $this->assertIsArray($schedule->events());
        $this->assertEmpty($schedule->events());
       
        $schedule->call(function () {
            return true;
        });
        $this->assertIsArray($schedule->events());
        $this->assertNotEMpty($schedule->events());
    }

    public function testLimit()
    {
        $schedule = new Schedule(new MiscTask);

        $obj = new stdClass();
        $obj->counter = 0;

        $event = $schedule->call(function ($obj) {
            $foo = 'bar'; // must be unique to other tests in the PID
            $obj->counter ++;

            return true;
        }, [$obj])->everyMinute()->processes(3)->limit(2);

        $schedule->dispatch();
        $this->assertEquals(2, $obj->counter); # Counter does not increase as PID still running
    }

    public function testRun()
    {
        Schedule::run(__DIR__ . '/Task');
        $this->assertNull(null);
    }

    public function testRunIdInvalid()
    {
        $this->expectException(ScheduleException::class);
        Schedule::run(__DIR__ . '/Task', '1234');
    }
    
    public function testRunInvalidPath()
    {
        $this->expectException(ScheduleException::class);
        Schedule::run('/foo');
    }

    public function testDispatch()
    {
        $object = new stdClass();
        $object->called = false;

        $schedule = new Schedule(new MiscTask);

        $schedule->call(function () use ($object) {
            $object->called = true;
        })->everyMinute();

        $schedule->dispatch();

        $this->assertTrue($object->called);
    }

    /**
     * This is problamatic on parrallel testing, something seems to have changed
     * with github actions and previous work around stopped working
     */
    public function testDispatchBackground()
    {
        $backupTask = new BackupTask();
        $schedule = new Schedule($backupTask);

        $backupTask->dispatch();
    
        sleep(2);
        $this->assertFileExists($backupTask->tempName());
    }

    /**
     * @depends testDispatch
     */
    public function testDispatchNotDue()
    {
        $object = new stdClass();
        $object->called = false;

        $schedule = new Schedule(new MiscTask);

        $day = (int) date('d') === 1 ?  2 : 3;
        $expression = "0 0 {$day} * *"; // never run

        $schedule->call(function () use ($object) {
            $object->called = true;
        })->cron($expression);

        $schedule->dispatch();

        $this->assertFalse($object->called);
    }
  
    /**
    * @depends testDispatch
    */
    public function testDispatchInMaintenanceMode()
    {
        $object = new stdClass();
        $object->called = false;

        $schedule = new Schedule(new MiscTask);

        file_put_contents(tmp_path('maintenance.json'), 'foo');

        $schedule->call(function () use ($object) {
            $object->called = true;
        })->everyMinute()->evenInMaintenanceMode();

        $schedule->dispatch();
        
        unlink(tmp_path('maintenance.json'));

        $this->assertTrue($object->called);
    }

    public function testRunId()
    {
        Schedule::run(__DIR__ . '/Task', '3a1787289e29');
        $this->assertNull(null);
    }

    public function testFilter()
    {
        $callable = new AnotherCallableWasInvoked();

        $schedule = new Schedule(new MiscTask);
        $schedule->call($callable)->everyMinute()->when(function () {
            return false;
        });
       
        $schedule->dispatch();
      
        $this->assertFalse($callable->invoked);
       
        $schedule = new Schedule(new MiscTask);
        $schedule->call($callable)->everyMinute()->when(function () {
            return true;
        });
       
        $schedule->dispatch();
       
        $this->assertTrue($callable->invoked);
    }

    public function testFilterBool()
    {
        $callable = new AnotherCallableWasInvoked();

        $schedule = new Schedule(new MiscTask);
        $schedule->call($callable)->everyMinute()->when(false);
        $schedule->dispatch();
        $this->assertFalse($callable->invoked);

        $schedule = new Schedule(new MiscTask);
        $schedule->call($callable)->everyMinute()->when(true);
        $schedule->dispatch();
        $this->assertTrue($callable->invoked);
    }

    public function testReject()
    {
        $callable = new AnotherCallableWasInvoked();

        $schedule = new Schedule(new MiscTask);
        $schedule->call($callable)->everyMinute()->skip(function () {
            return true;
        });
        $schedule->dispatch();
        $this->assertFalse($callable->invoked);

        $schedule = new Schedule(new MiscTask);
        $schedule->call($callable)->everyMinute()->skip(function () {
            return false;
        });
        $schedule->dispatch();
        $this->assertTrue($callable->invoked);
    }

    public function testRejectBool()
    {
        $callable = new AnotherCallableWasInvoked();

        $schedule = new Schedule(new MiscTask);
        $schedule->call($callable)->everyMinute()->skip(true);
        $schedule->dispatch();
        $this->assertFalse($callable->invoked);

        $schedule = new Schedule(new MiscTask);
        $schedule->call($callable)->everyMinute()->skip(false);
        $schedule->dispatch();
        $this->assertTrue($callable->invoked);
    }
}
