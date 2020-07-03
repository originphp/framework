<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Origin\Test\Job\Engine;

use Origin\Job\Job;
use Origin\Job\Queue;
use Origin\TestSuite\OriginTestCase;

class RedisPassOrFailJob extends Job
{
    protected $connection = 'redis-test';

    protected $queue = 'test';

    public function execute(bool $pass = true)
    {
        if (! $pass) {
            $a = 1 / 0;
        }
    }
    public function onException(\Exception $exception)
    {
        $this->retry();
    }

    public function set(string $key, $value)
    {
        $this->{$key} = $value;
    }
    public function get(string $key)
    {
        return $this->{$key};
    }

    public function increment()
    {
        $this->attempts ++;
    }

    public function setArguments()
    {
        $this->arguments = func_get_args();

        return $this;
    }
}

class RedisEngineTest extends OriginTestCase
{
   
    /**
     * Engine
     *
     * @var \Origin\Job\Engine\DatabaseEngine
     */
    protected $engine = null;

    protected function setUp(): void
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not loaded');
        }

        if (! env('REDIS_HOST') or ! env('REDIS_PORT')) {
            $this->markTestSkipped('Redis settings not found');
        }

        Queue::config('redis-test', [
            'engine' => 'Redis',
            'host' => env('REDIS_HOST'),
            'port' => (int) env('REDIS_PORT'),
        ]);

        $this->engine = Queue::connection('redis-test');
        $this->engine->redis()->flushdb();
    }

    public function testAdd()
    {
        $job = (new RedisPassOrFailJob())->setArguments(true);
        $this->assertTrue($this->engine->add($job));
     
        // Check it was added
        $result = $this->engine->redis()->lpop('queue:test');
        $this->assertMatchesRegularExpression('/' . $job->id().'/', $result);
    }

    public function testAddScheduled()
    {
        $job = (new RedisPassOrFailJob())->setArguments(true);
        $this->assertTrue($this->engine->add($job, '+5 minutes'));

        // Check it was added
        $result = $this->engine->redis()->zrange('scheduled:test', 0, -1); // schedule uses different type of list
        $this->assertMatchesRegularExpression('/' . $job->id().'/', $result[0]);
    }

    public function testFetch()
    {
        # Prepare
        $job = new RedisPassOrFailJob();
        $job->setArguments(true);
        $this->assertTrue($this->engine->add($job));

        # Test
        $job = $this->engine->fetch('test');
        $this->assertInstanceOf(Job::class, $job);
        $this->assertEquals([true], $job->get('arguments')); # Check Serialization

        // Check it was removed from queue
        $job = $this->engine->fetch('test');
        $this->assertNull($job);
    }

    public function testFetchScheduled()
    {
        # Prepare
        $job = new RedisPassOrFailJob();
        $job->setArguments(true);
        $this->assertTrue($this->engine->add($job, '+1 second'));
        $this->assertNull($this->engine->fetch('test'));
        sleep(1);
        $scheduledJob = $this->engine->fetch('test');
        $this->assertInstanceOf(Job::class, $scheduledJob);
        $this->assertEquals($job->id(), $scheduledJob->id());
    }

    /**
     * @depends testFetch
     *
     */
    public function testFail()
    {
        # Prepare
        $job = new RedisPassOrFailJob();
        $job->setArguments(true);
        $this->assertTrue($this->engine->add($job));
        
        $job = $this->engine->fetch('test');
        $this->assertInstanceOf(Job::class, $job);

        $this->engine->fail($job);

        # Check its no longer in queue
        $result = $this->engine->redis()->lrange('queue:test', 0, -1);
        $this->assertEmpty($result);

        # Check its in failed list and can be returned!!!
        $result = $this->engine->redis()->lrange('failed:jobs', 0, -1);
        $result = $this->engine->deserialize($result[0]);
        $this->assertInstanceOf(Job::class, $result);
        $this->assertEquals($job->id(), $result->id());
    }

    // succes not used
    public function testSuccess()
    {
        # Prepare
        $job = new RedisPassOrFailJob();
        $job->setArguments(true);
        $this->assertTrue($this->engine->add($job));

        $job = $this->engine->fetch('test');
        $this->assertInstanceOf(Job::class, $job);

        $this->engine->success($job); // Can't assert
        $result = $this->engine->redis()->lrange('queue:test', 0, -1);
        $this->assertEmpty($result);
    }

    /**
     * Even though right now success uses delete, keep this seperate
     *
     * @return void
     */
    public function testDelete()
    {
        # Prepare
        $job = new RedisPassOrFailJob();
        $job->setArguments(true);
        $this->assertTrue($this->engine->add($job));

        $this->assertTrue($this->engine->delete($job));
        $result = $this->engine->redis()->lrange('queue:test', 0, -1);
        $this->assertEmpty($result);

        $this->assertFalse($this->engine->delete($job));
    }

    public function testRetry()
    {
        # Prepare
        $job = new RedisPassOrFailJob();
        $job->setArguments(true);
        $this->engine->add($job);

        # Fake dispatch
        $job = $this->engine->fetch('test');
        $job->increment();
        $this->engine->fail($job);
        $this->engine->retry($job, 3, '+1 seconds');
        
        # Test that its in scheduled queue and migration process works
        $result = $this->engine->redis()->zrange('scheduled:test', 0, -1);
        $result = $this->engine->deserialize($result[0]);
        $this->assertInstanceOf(Job::class, $result);
        $this->assertEquals($job->id(), $result->id()); # Sanity check
        $this->assertEquals([true], $job->get('arguments'));

        sleep(1);

        # Retry 1

        $job = $this->engine->fetch('test');
        $this->assertInstanceOf(Job::class, $job);
        $job->increment();
        $this->engine->fail($job);
        $this->engine->retry($job, 3);

        # Retry 2
      
        $job = $this->engine->fetch('test');
        $this->assertInstanceOf(Job::class, $job);
        $job->increment();
        $this->engine->fail($job);
        $this->engine->retry($job, 3);

        # Retry 3
      
        $job = $this->engine->fetch('test');
        $this->assertInstanceOf(Job::class, $job);
        $job->increment();
        $this->engine->fail($job);
        $this->engine->retry($job, 3);

        # Retry 4
      
        $job = $this->engine->fetch('test');
        $this->assertNull($job);
    }
}
