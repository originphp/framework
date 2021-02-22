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

use InvalidArgumentException;
use Origin\Schedule\CronExpression;

class Cron
{
    const HOURLY = '0 * * * *';
    const DAILY = '0 0 * * *';
    const WEEKLY = '0 0 * * 0';
    const MONTHLY = '0 0 1 * *';
    const YEARLY = '0 0 1 1 *';
    const WEEKDAYS = '0 0 * * 1-5';
    const QUARTERLY = '0 0 1 */3 *';
}

class CronExpressionTest extends \PHPUnit\Framework\TestCase
{
    public function testInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        new CronExpression('foo');
    }
    public function testIsDueHourly()
    {
        $cron = new CronExpression(Cron::HOURLY, '2021-02-20 00:01:00');
        $this->assertFalse($cron->isDue());
        
        $cron = new CronExpression(Cron::HOURLY, '2021-02-20 01:01:00');
        $this->assertFalse($cron->isDue());

        $cron = new CronExpression(Cron::HOURLY, '2021-02-20 00:00:00');
        $this->assertTrue($cron->isDue());

        $cron = new CronExpression(Cron::HOURLY, '2021-02-20 01:00:00');
        $this->assertTrue($cron->isDue());
    }

    public function testIsDueDaily()
    {
        $cron = new CronExpression(Cron::DAILY, '2021-02-20 00:01:00');
        $this->assertFalse($cron->isDue());

        $cron = new CronExpression(Cron::DAILY, '2021-02-20 01:01:00');
        $this->assertFalse($cron->isDue());

        $cron = new CronExpression(Cron::DAILY, '2021-02-20 00:00:00');
        $this->assertTrue($cron->isDue());
    }

    public function testIsDueWeekly()
    {
        $cron = new CronExpression(Cron::WEEKLY, '2021-02-21 00:01:00');
        $this->assertFalse($cron->isDue());

        $cron = new CronExpression(Cron::WEEKLY, '2021-02-21 01:01:00');
        $this->assertFalse($cron->isDue());

        $cron = new CronExpression(Cron::WEEKLY, '2021-02-21 00:00:00');
        $this->assertTrue($cron->isDue());

        $cron = new CronExpression(Cron::WEEKLY, '2021-02-22 00:00:00');
        $this->assertFalse($cron->isDue());
    }

    public function testIsDueMonthly()
    {
        $cron = new CronExpression(Cron::MONTHLY, '2021-02-01 00:01:00');
        $this->assertFalse($cron->isDue());

        $cron = new CronExpression(Cron::MONTHLY, '2021-02-01 01:01:00');
        $this->assertFalse($cron->isDue());

        $cron = new CronExpression(Cron::MONTHLY, '2021-02-01 00:00:00');
        $this->assertTrue($cron->isDue());

        $cron = new CronExpression(Cron::MONTHLY, '2021-02-02 00:00:00');
        $this->assertFalse($cron->isDue());

        $cron = new CronExpression(Cron::MONTHLY, '2021-02-02 00:01:00');
        $this->assertFalse($cron->isDue());
    }

    public function testIsDueRange()
    {
        $cron = new CronExpression(Cron::WEEKDAYS, '2021-02-21 00:00:00');
        $this->assertFalse($cron->isDue());

        $cron = new CronExpression(Cron::WEEKDAYS, '2021-02-22 00:00:00');
        $this->assertTrue($cron->isDue());

        $cron = new CronExpression(Cron::WEEKDAYS, '2021-02-23 00:00:00');
        $this->assertTrue($cron->isDue());

        $cron = new CronExpression(Cron::WEEKDAYS, '2021-02-24 00:00:00');
        $this->assertTrue($cron->isDue());
    }

    public function testIsDueEvery()
    {
        $cron = new CronExpression(Cron::QUARTERLY, '2021-01-01 00:00:00');
        $this->assertTrue($cron->isDue());

        $cron = new CronExpression(Cron::QUARTERLY, '2021-02-01 00:00:00');
        $this->assertFalse($cron->isDue());

        $cron = new CronExpression(Cron::QUARTERLY, '2021-03-01 00:00:00');
        $this->assertFalse($cron->isDue());

        $cron = new CronExpression(Cron::QUARTERLY, '2021-04-01 00:00:00');
        $this->assertTrue($cron->isDue());

        $cron = new CronExpression(Cron::QUARTERLY, '2021-04-01 00:01:00');
        $this->assertFalse($cron->isDue());

        $expression = '0 */2 * * *'; // At minute 0 past every 2nd hour
        $cron = new CronExpression($expression, '2021-01-01 00:00:00');
        $this->assertTrue($cron->isDue());
        
        $cron = new CronExpression($expression, '2021-01-01 01:00:00');
        $this->assertFalse($cron->isDue());

        $cron = new CronExpression($expression, '2021-01-01 02:00:00');
        $this->assertTrue($cron->isDue());
        
        $cron = new CronExpression($expression, '2021-01-01 03:00:00');
        $this->assertFalse($cron->isDue());
    }

    public function testIsValueList()
    {
        $expression = '* * * 1,2,3 *'; // Each minute in January, February or March;
        $cron = new CronExpression($expression, '2021-01-01 00:00:00');
        $this->assertTrue($cron->isDue());

        $cron = new CronExpression($expression, '2021-02-01 00:00:00');
        $this->assertTrue($cron->isDue());

        $cron = new CronExpression($expression, '2021-03-01 00:00:00');
        $this->assertTrue($cron->isDue());

        $cron = new CronExpression($expression, '2021-04-01 00:00:00');
        $this->assertFalse($cron->isDue());
    }

    public function testNextRunDate()
    {
        $cron = new CronExpression(Cron::HOURLY, '2021-02-20 12:30:00');
        $this->assertEquals('2021-02-20 13:00:00', $cron->nextRunDate());

        $cron = new CronExpression(Cron::DAILY, '2021-02-20 12:30:00');
        $this->assertEquals('2021-02-21 00:00:00', $cron->nextRunDate());

        $cron = new CronExpression(Cron::MONTHLY, '2021-02-20 12:30:00');
        $this->assertEquals('2021-03-01 00:00:00', $cron->nextRunDate());
    }

    public function testPreviousRunDate()
    {
        $cron = new CronExpression(Cron::HOURLY, '2021-02-20 12:30:00');
        $this->assertEquals('2021-02-20 12:00:00', $cron->previousRunDate());

        $cron = new CronExpression(Cron::DAILY, '2021-02-20 12:30:00');
        $this->assertEquals('2021-02-20 00:00:00', $cron->previousRunDate());

        $cron = new CronExpression(Cron::MONTHLY, '2021-02-20 12:30:00');
        $this->assertEquals('2021-02-01 00:00:00', $cron->previousRunDate());
    }
}
