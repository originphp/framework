<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Origin\Test\Queue;

use Origin\Queue\Queue;
use Origin\Model\ConnectionManager;
use Origin\Exception\InvalidArgumentException;
use Origin\TestSuite\OriginTestCase;

class QueueTest extends OriginTestCase
{
    public $fixtures = ['Origin.Queue'];

    /**
     * Undocumented variable
     *
     * @var \Origin\Queue\Queue
     */
    public $Queue = null;

    protected function setUp(): void
    {
        $this->Queue = new Queue(['datasource'=>'test']);
    }
   
    public function testAdd()
    {
        $this->assertNotNull($this->Queue->add('welcome_emails', ['user_id'=>1234]));
        $this->assertEquals(1, $this->Queue->model()->find('count'));
    }

    public function testAddFailQueueName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->Queue->add('this has space', ['id'=>1234]);
    }

    public function testAddFailParamsOverload()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->Queue->add('overload', ['data'=>str_repeat('+', 65535)]);
    }

    public function testExecuted()
    {
        $this->Queue->add('welcome_emails', ['user_id'=>1234]);
    
        $job = $this->Queue->fetch('welcome_emails');
        $this->assertTrue($job->executed());
     
        $job = $this->Queue->model()->get($job->id);

        $this->assertEquals('executed', $job->status);
        $this->assertEquals(0, $job->locked);
    }

    public function testFailed()
    {
        $this->Queue->add('welcome_emails', ['user_id'=>1234]);
        $job = $this->Queue->fetch('welcome_emails');
        $this->assertTrue($job->failed());

        $job = $this->Queue->model()->get($job->id);

        $this->assertEquals('failed', $job->status);
        $this->assertEquals(0, $job->locked);
    }

    public function testRelease()
    {
        $this->Queue->add('welcome_emails', ['user_id'=>1234]);
        $job = $this->Queue->fetch('welcome_emails');
        $job->release();
        $job = $this->Queue->model()->get($job->id);

        $this->assertEquals('queued', $job->status);
    }

    public function testRetry()
    {
        $this->Queue->add('welcome_emails', ['user_id'=>1234]);
        $job = $this->Queue->fetch('welcome_emails');
        $job->retry(1);
        $jobFromDb = $this->Queue->model()->get($job->id);
        $this->assertEquals(1, $jobFromDb->tries);
        $this->assertEquals('queued', $jobFromDb->status);
        $job->retry(1);
        $this->assertEquals(1, $jobFromDb->tries);
    }

    public function testPurge()
    {
        $this->Queue->add('welcome_emails', ['user_id'=>1234]);
        $this->assertEquals(1, $this->Queue->model()->find('count'));
        $job = $this->Queue->fetch('welcome_emails');
        $job->executed();
        $this->Queue->purge('welcome_emails');
        $this->assertEquals(0, $this->Queue->model()->find('count'));
    }

    public function testDelete()
    {
        $this->Queue->add('welcome_emails', ['user_id'=>1234]);
        $job = $this->Queue->fetch('welcome_emails');
        $this->assertTrue($job->delete());
        $this->assertEquals(0, $this->Queue->model()->find('count'));
    }

    public function testFetch()
    {
        $id = $this->Queue->add('welcome_emails', ['user_id'=>1234]);
        
        // Test job entity
        $job = $this->Queue->fetch('welcome_emails');
        
        $this->assertEquals($id, $job->id);
      
        $message = $job->data();
        $this->assertEquals(1234, $message->user_id);
        // Test is set to locked

        $job = $this->Queue->model()->get($id);
        $this->assertEquals(1, $job->locked);

        $this->assertFalse($this->Queue->fetch('welcome_emails'));
    }
}
