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
use Origin\Schedule\Event;
use Origin\Mailer\MailerJob;
use InvalidArgumentException;
use Origin\Schedule\Schedule;

class ScheduleTest extends \PHPUnit\Framework\TestCase
{
    public function testCommand()
    {
        $schedule = new Schedule;
        $this->assertInstanceOf(Event::class, $schedule->command('ls', ['-lah']));
    }

    public function testJob()
    {
        $schedule = new Schedule;
        $this->assertInstanceOf(Event::class, $schedule->job(new MailerJob));
    }

    public function testCall()
    {
        $schedule = new Schedule;
        $this->assertInstanceOf(Event::class, $schedule->call(function () {
            return true;
        }));
    }

    public function testEvents()
    {
        $schedule = new Schedule;
        
        $this->assertIsArray($schedule->events());
        $this->assertEmpty($schedule->events());
       
        $schedule->call(function () {
            return true;
        });
        $this->assertIsArray($schedule->events());
        $this->assertNotEMpty($schedule->events());
    }

    public function testRun()
    {
        Schedule::run(__DIR__ . '/Task');
        $this->assertNull(null);
    }

    public function testRunId()
    {
        Schedule::run(__DIR__ . '/Task', 'c6cc44a85c4d');
        $this->assertNull(null);
    }

    public function testRunIdInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        Schedule::run(__DIR__ . '/Task', '1234');
    }
    
    public function testRunInvalidPath()
    {
        $this->expectException(InvalidArgumentException::class);
        Schedule::run('/foo');
    }

    public function testDispatch()
    {
        $object = new stdClass();
        $object->called = false;

        $schedule = new Schedule;

        $schedule->call(function () use ($object) {
            $object->called = true;
        })->everyMinute();

        $schedule->dispatch();

        $this->assertTrue($object->called);
    }

    /**
     * @depends testDispatch
     */
    public function testDispatchBackground()
    {
        $schedule = new Schedule;

        $schedule->call(function () {
            echo 'foo';
        })->everyMinute()->inBackground();

        $schedule->dispatch();

        $this->assertNull(null); // Check no errors caught, does not mean it worked
    }

    /**
     * @depends testDispatch
     */
    public function testDispatchNotDue()
    {
        $object = new stdClass();
        $object->called = false;

        $schedule = new Schedule;

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
    public function testDispatchMaintenanceMode()
    {
        $object = new stdClass();
        $object->called = false;

        $schedule = new Schedule;

        file_put_contents(tmp_path('maintenance.json'), 'foo');

        $schedule->call(function () use ($object) {
            $object->called = true;
        })->everyMinute();

        $schedule->dispatch();
        
        unlink(tmp_path('maintenance.json'));

        $this->assertFalse($object->called);
    }

    /**
    * @depends testDispatch
    */
    public function testDispatchInMaintenanceMode()
    {
        $object = new stdClass();
        $object->called = false;

        $schedule = new Schedule;

        file_put_contents(tmp_path('maintenance.json'), 'foo');

        $schedule->call(function () use ($object) {
            $object->called = true;
        })->everyMinute()->inMaintenanceMode();

        $schedule->dispatch();
        
        unlink(tmp_path('maintenance.json'));

        $this->assertTrue($object->called);
    }
}
