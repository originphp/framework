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
namespace Origin\TestSuite;

use Origin\Job\Queue;
use Origin\Job\Engine\DatabaseEngine;

/**
 * Helps testing queued jobs, requires the test driver to be `Database`, remember to setup a fixture for
 * queue, this should be empty
 */

trait JobTestTrait
{
    /**
     * @var \Origin\Job\Engine\DatabaseEngine
     */
    private static $queueEngine;
    
    /**
     * Runs enqueued jobs once, if this function is called again it will only new jobs that might
     * have been generated when run last time
     *
     * @param string $queue
     * @return int number of jobs that were run
     */
    public function runEnqueuedJobs(string $queue = null): int
    {
        $count = 0;

        $jobs = $this->getEnqueuedJobs($queue);
        foreach ($jobs as $job) {
            if (! $job->dispatchNow(...$job->arguments())) {
                trigger_error(sprintf('Job %s with id %s failed', get_class($job), $job->id()), E_USER_WARNING);
            }
            $count ++ ;
        }

        return $count;
    }

    /**
     * Asserts the number of enqueued jobs equals x
     *
     * @param integer $count
     * @param string $queue
     * @return void
     */
    public function assertEnqueuedJobs(int $count, string $queue = null): void
    {
        $this->assertEquals($count, count($this->getEnqueuedJobs($queue)), 'Number of enqueued jobs do not match');
    }

    /**
     * Assert that there are not enqued jobs
     *
     * @param string $queue
     * @return void
     */
    public function assertNoEnqueuedJobs(string $queue = null): void
    {
        $this->assertEquals(0, count($this->getEnqueuedJobs($queue)), 'There are jobs that have been enqueued');
    }

    /**
     * Asserts that a job was enqueued
     *
     * @param string $class
     * @param string $queue
     * @return void
     */
    public function assertJobEnqueued(string $class, string $queue = null): void
    {
        $found = false;
        $jobs = $this->getEnqueuedJobs($queue);
        foreach ($jobs as $job) {
            if (get_class($job) === $class) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Job was not queued');
    }

    /**
     * Asserts that a job was enqueued with a set of arguments
     *
     * @param string $class
     * @param array $arguments
     * @param string $queue
     * @return void
     */
    public function assertJobEnqueuedWith(string $class, array $arguments, string $queue = null): void
    {
        $found = false;
        $jobs = $this->getEnqueuedJobs($queue);
        foreach ($jobs as $job) {
            if (get_class($job) === $class) {
                $this->assertEquals(count($arguments), count($job->arguments()), 'Number of arguments do not match up');
    
                foreach ($job->arguments() as $index => $argument) {
                    $this->assertEquals($argument, $arguments[$index], sprintf('Argument %d does not match', $index));
                }
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Job was not queued or queued but not with those arguments');
    }

    /**
     * Gets, filters and deserializes jobs from the database
     *
     * @param string $queue
     * @return array
     */
    private function getEnqueuedJobs(string $queue = null): array
    {
        if (! static::$queueEngine) {
            static::$queueEngine = Queue::connection('test');
            if (! static::$queueEngine instanceof DatabaseEngine) {
                trigger_error('This assertation requires the DatabaseEngine', E_USER_WARNING);
            }
        }
    
        $out = [];
        foreach (static::$queueEngine->model()->find('all') as $job) {
            $object = static::$queueEngine->deserialize($job->data);
            if ($queue && $object->queue() !== $queue) {
                continue;
            }
            $out[] = $object;
        }
       
        return $out;
    }
}
