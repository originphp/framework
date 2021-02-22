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
declare(strict_types = 1);
namespace Origin\Schedule;

use DateTime;
use InvalidArgumentException;

/**
 * Simple cron parser
 *
 *      *	any value
 *      ,	value list separator
 *      -	range of values
 *      /	step values
 * @see https://crontab.guru/
 */
class CronExpression
{
    /**
     * @var string
     */
    protected $cron = '* * * * *';

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var array
     */
    protected $segments = [];

    /**
     * @var array
     */
    protected $parsedSegments = [];

    /**
     * Maps the cron syntax position to datetime format
     *
     * @var array
     */
    protected $map = [
        'i', // minute (0-59)
        'H', // hour (0-23)
        'j', // day (month) (1-31)
        'n', // month (1-12)
        'w' // day (week) (0-6, Sunday - Saturday)
    ];

    /**
     * The cron range for each field type
     *
     * @var array
     */
    protected $rangeMap = [
        [0,59],
        [0,23],
        [1,31],
        [1,12],
        [0,6]
    ];

    /**
     * @param string $cron
     * @param string $time
     */
    public function __construct(string $cron, string $time = 'now')
    {
        $dateTime = new DateTime($time);
        $dateTime->setTime(
            (int) $dateTime->format('H'),
            (int) $dateTime->format('i'),
            0
       );
        $this->dateTime = $dateTime;
        $this->cron = $cron;

        $this->parse($cron);
    }

    /**
     * Parses the xpression
     *
     * @param string $cron
     * @return void
     */
    private function parse(string $cron): void
    {
        $this->segments = explode(' ', $cron);

        if (count($this->segments) !== 5) {
            throw new InvalidArgumentException('Invalid cron expression');
        }
        $this->parsedSegments = [
            $this->getSegment(0),
            $this->getSegment(1),
            $this->getSegment(2),
            $this->getSegment(3),
            $this->getSegment(4),
        ];
    }

    /**
     * Checks if a cron expression is due
     *
     * @return boolean
     */
    public function isDue(): bool
    {
        for ($i = 4;$i >= 0;$i--) {
            $segment = $this->parsedSegments[$i];
            if ($segment !== null && ! in_array((int) $this->dateTime->format($this->map[$i]), $segment)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param integer $index
     * @return array|null
     */
    private function getSegment(int $index): ?array
    {
        if (! isset($this->segments[$index]) || $this->segments[$index] === '*') {
            return null;
        }

        $value = $this->segments[$index];

        // handle lists, step values and ranges
        if (strpos($value, '-') !== false) {
            list($min, $max) = explode('-', $value);
            $value = range((int) $min, (int) $max);
        } elseif (strpos($value, '/') !== false) {
            $map = $this->rangeMap[$index];
            list(, $every) = explode('/', $value);
            $value = range($map[0], $map[1], (int) $every);
        } elseif (strpos($value, ',') !== false) {
            $value = array_map('intval', explode(',', $value));
        } else {
            $value = [(int) $value];
        }

        return $value;
    }
    
    /**
     * Gets the next run date that the cron job should run on
     *
     * @return string
     */
    public function nextRunDate(): string
    {
        return $this->walk('+1 minute');
    }

    /**
     * Gets the previous run date that the cron job should run on
     *
     * @return string
     */
    public function previousRunDate(): string
    {
        return $this->walk('-1 minute');
    }

    /**
     * Checking a yearly cron is slow, to check both the next and previous
     * yearly value it took 0.37 seconds, monthly mid month is about 1/10th of that.
     *
     * @param string $step
     * @return string
     */
    private function walk(string $step): string
    {
        $original = clone $this->dateTime;
        
        while (! $this->isDue()) {
            $this->dateTime->modify($step);
        }
        $nextDue = $this->dateTime->format('Y-m-d H:i:s');

        $this->dateTime = $original;

        return $nextDue;
    }
}
