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
namespace Origin\Test\Utility;

use Origin\Utility\Queue;
use Origin\Model\ConnectionManager;
use Origin\Exception\InvalidArgumentException;

class QueueTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        # Create the database, as we are not using mvc for this we wont use fixture
        $connection  = ConnectionManager::get('test');
        $connection->execute('DROP TABLE IF EXISTS queue');
        $statements = explode(";\n", file_get_contents(ROOT . DS . 'config/schema/queue.sql'));
        foreach ($statements as $statement) {
            $connection->execute($statement);
        }
    }
    public function testAdd()
    {
        $queue = new Queue(['datasource'=>'test']);
        $this->assertNotNull($queue->add('welcome_emails', ['user_id'=>1234]));
        $this->assertEquals(1, $queue->model()->find('count'));
    }

    public function testAddFailQueueName()
    {
        $queue = new Queue(['datasource'=>'test']);
        $this->expectException(InvalidArgumentException::class);
        $queue->add('this has space', ['id'=>1234]);
    }

    public function testAddFailParamsOverload()
    {
        $queue = new Queue(['datasource'=>'test']);
        $this->expectException(InvalidArgumentException::class);
        $queue->add('overload', ['data'=>str_repeat('+', 65535)]);
    }

    public function testExecuted()
    {
        $queue = new Queue(['datasource'=>'test']);
        $queue->add('welcome_emails', ['user_id'=>1234]);
        $job = $queue->fetch('welcome_emails');
        $this->assertTrue($job->executed());

        $job = $queue->model()->get($job->id);

        $this->assertEquals('executed', $job->status);
        $this->assertEquals(0, $job->locked);
    }

    public function testFailed()
    {
        $queue = new Queue(['datasource'=>'test']);
        $queue->add('welcome_emails', ['user_id'=>1234]);
        $job = $queue->fetch('welcome_emails');
        $this->assertTrue($job->failed());

        $job = $queue->model()->get($job->id);

        $this->assertEquals('failed', $job->status);
        $this->assertEquals(0, $job->locked);
    }

    public function testRelease()
    {
        $queue = new Queue(['datasource'=>'test']);
        $queue->add('welcome_emails', ['user_id'=>1234]);
        $job = $queue->fetch('welcome_emails');
        $job->release();
        $job = $queue->model()->get($job->id);

        $this->assertEquals('queued', $job->status);
    }

    public function testRetry()
    {
        $queue = new Queue(['datasource'=>'test']);
        $queue->add('welcome_emails', ['user_id'=>1234]);
        $job = $queue->fetch('welcome_emails');
        $job->retry(1);
        $jobFromDb = $queue->model()->get($job->id);
        $this->assertEquals(1, $jobFromDb->tries);
        $this->assertEquals('queued', $jobFromDb->status);
        $job->retry(1);
        $this->assertEquals(1, $jobFromDb->tries);
    }

    public function testPurge()
    {
        $queue = new Queue(['datasource'=>'test']);
        $queue->add('welcome_emails', ['user_id'=>1234]);
        $this->assertEquals(1, $queue->model()->find('count'));
        $job = $queue->fetch('welcome_emails');
        $job->executed();
        $queue->purge('welcome_emails');
        $this->assertEquals(0, $queue->model()->find('count'));
    }

    public function testDelete()
    {
        $queue = new Queue(['datasource'=>'test']);
        $queue->add('welcome_emails', ['user_id'=>1234]);
        $job = $queue->fetch('welcome_emails');
        $this->assertTrue($job->delete());
        $this->assertEquals(0, $queue->model()->find('count'));
    }

    public function testFetch()
    {
        $queue = new Queue(['datasource'=>'test']);
        $id = $queue->add('welcome_emails', ['user_id'=>1234]);
        // Test job entity
        $job = $queue->fetch('welcome_emails');

        $this->assertEquals($id, $job->id);
        
        $message = $job->data();
        $this->assertEquals(1234, $message->user_id);
        // Test is set to locked
        $job = $queue->model()->get($id);
        $this->assertEquals(1, $job->locked);

        $this->assertFalse($queue->fetch('welcome_emails'));
    }
}
