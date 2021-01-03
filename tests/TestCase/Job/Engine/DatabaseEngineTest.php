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
namespace Origin\Test\Job\Engine;

use Origin\Job\Job;
use Origin\Job\Queue;
use Origin\TestSuite\OriginTestCase;

class PassOrFailJob extends Job
{
    protected $connection = 'default';

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
    }
}

class DatabaseEngineTest extends OriginTestCase
{
    protected $fixtures = ['Origin.Queue'];

    /**
     * Engine
     *
     * @var \Origin\Job\Engine\DatabaseEngine
     */
    protected $engine = null;

    protected function setUp(): void
    {
        $this->engine = Queue::connection('test');
    }

    public function testAdd()
    {
        $job = new PassOrFailJob();
        $job->setArguments(true);
        $this->assertTrue($this->engine->add($job));
    }

    public function testFetch()
    {
        $job = new PassOrFailJob();
        $job->setArguments(true);
        $this->assertTrue($this->engine->add($job));

        $job = $this->engine->fetch();
        $this->assertInstanceOf(Job::class, $job);

        $id = $job->backendId();
        $this->assertIsInt($id);
        $this->assertGreaterThanOrEqual(1, $id);
   
        $this->assertEquals([true], $job->get('arguments')); # Check Serialization

        $result = $this->engine->model()->get($id);
        $this->assertNotNull($result->locked);

        // Check it was locked
        $job = $this->engine->fetch();
        $this->assertNull($job);
    }

    /**
     * @depends testFetch
     *
     */
    public function testFail()
    {
        $job = new PassOrFailJob();
        $job->setArguments(true);
        $this->assertTrue($this->engine->add($job));
        
        $job = $this->engine->fetch();
        $id = $job->backendId();

        $this->assertEquals($id, $job->backendId());
        $this->assertTrue($this->engine->fail($job));

        $result = $this->engine->model()->get($id);
        $this->assertEquals('failed', $result->status);
        $this->assertNull($result->locked);

        $newJobWithNoId = new PassOrFailJob();
        $this->assertFalse($this->engine->fail($newJobWithNoId));
    }

    public function testSuccess()
    {
        $job = new PassOrFailJob();
        $job->setArguments(true);
        $this->assertTrue($this->engine->add($job));

        $job = $this->engine->fetch();
        $id = $job->backendId();

        $this->assertEquals($id, $job->backendId());
        $this->assertTrue($this->engine->success($job));

        $result = $this->engine->model()->exists($id);
        $this->assertFalse($result);

        $newJobWithNoId = new PassOrFailJob();
        $this->assertFalse($this->engine->success($newJobWithNoId));
    }

    /**
     * Even though right now success uses delete, keep this seperate
     *
     * @return void
     */
    public function testDelete()
    {
        $job = new PassOrFailJob();
        $job->setArguments(true);
        $this->assertTrue($this->engine->add($job));

        $job = $this->engine->fetch();
        $id = $job->backendId();

        $this->assertEquals($id, $job->backendId());
        $this->assertTrue($this->engine->delete($job));

        $result = $this->engine->model()->exists($id);
        $this->assertFalse($result);

        $newJobWithNoId = new PassOrFailJob();
        $this->assertFalse($this->engine->delete($newJobWithNoId));
    }

    public function testRetry()
    {
        $job = new PassOrFailJob();
        $job->setArguments(true);
        $this->assertTrue($this->engine->add($job));

        $job = $this->engine->fetch();
        $id = $job->backendId();

        $job->increment();
        $this->assertTrue($this->engine->fail($job));
        
        # Retry 1
        $expected = date('Y-m-d H:i:s', strtotime('-5 hours'));
        $this->engine->retry($job, 3, '-5 hours');
        $result = $this->engine->model()->get($id);
        $this->assertEquals($expected, $result->scheduled);
        $this->assertEquals('queued', $result->status);
        $this->assertEquals([true], $job->get('arguments'));

        $job = $this->engine->fetch();
        $job->increment();
        $this->assertTrue($this->engine->fail($job));

        # Retry 2
        $expected = date('Y-m-d H:i:s');
        $this->engine->retry($job, 3);
        $result = $this->engine->model()->get($id);
        $this->assertEquals($expected, $result->scheduled);
        $this->assertEquals('queued', $result->status);
        $this->assertEquals([true], $job->get('arguments'));

        $job = $this->engine->fetch();
        $job->increment();
        $this->assertTrue($this->engine->fail($job));

        # Retry 3
        $this->engine->retry($job, 3, '-5 hours');
        $result = $this->engine->model()->get($id);
        $this->assertEquals('queued', $result->status);
        $this->assertEquals([true], $job->get('arguments'));

        $job = $this->engine->fetch();
        $job->increment();
        $this->assertTrue($this->engine->fail($job));

        # Retry 4 - Fail
        $this->engine->retry($job, 3, '-5 hours');
        $result = $this->engine->model()->get($id);
        $this->assertEquals('failed', $result->status);

        $newJobWithNoId = new PassOrFailJob();
        $this->assertFalse($this->engine->retry($newJobWithNoId, 3));
    }
}
