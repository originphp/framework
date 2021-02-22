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

use Origin\Schedule\Event;
use Origin\Mailer\MailerJob;
use Origin\Schedule\Schedule;

class ScheduleTest extends \PHPUnit\Framework\TestCase
{
    public function testCommand()
    {
        $schedule = new Schedule();
        $this->assertInstanceOf(Event::class, $schedule->command('ls', ['-lah']));
    }

    public function testJob()
    {
        $schedule = new Schedule();
        $this->assertInstanceOf(Event::class, $schedule->job(new MailerJob));
    }

    public function testCall()
    {
        $schedule = new Schedule();
        $this->assertInstanceOf(Event::class, $schedule->call(function () {
            return true;
        }));
    }

    public function testEvents()
    {
        $schedule = new Schedule();
        
        $this->assertIsArray($schedule->events());
        $this->assertEmpty($schedule->events());
       
        $schedule->call(function () {
            return true;
        });
        $this->assertIsArray($schedule->events());
        $this->assertNotEMpty($schedule->events());
    }
}
