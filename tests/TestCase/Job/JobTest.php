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
namespace Origin\Test\Job;

use Origin\Job\Job;
use Origin\Model\Model;
use Origin\Exception\Exception;
use Origin\Model\ModelRegistry;
use Origin\TestSuite\OriginTestCase;
use Origin\Job\Engine\DatabaseEngine;
use Origin\Model\Exception\MissingModelException;

class PassOrFailJob extends Job
{
    public $connection = 'default';

    public function execute(bool $pass = true)
    {
        if (! $pass) {
            $a = 1 / 0;
        }
    }
    public function onException(\Exception $exception)
    {
        parent::onException($exception);
        $this->retry(['wait' => 'now','limit' => 1]);
    }
}

class JobTest extends OriginTestCase
{
    public $fixtures = ['Origin.Queue'];

    public function setUp() : void
    {
        $model = new Model(['name' => 'Article','datasource' => 'test']);
        ModelRegistry::set('Article', $model);
    }
    public function testNoExecuteException()
    {
        $this->expectException(Exception::class);
        $job = new Job();
    }

    public function testSetGetId()
    {
        $job = new PassOrFailJob(true);
        $this->assertNull($job->id());
        $job->id(12345);
        $this->assertEquals(12345, $job->id());
    }

    public function testConnection()
    {
        $job = new PassOrFailJob(true);
        $connection = $job->connection();
        $this->assertInstanceOf(DatabaseEngine::class, $connection);
        $this->assertEquals('test', $connection->config('connection'));
    }

    public function testDispatch()
    {
        $job = new PassOrFailJob(true);
        $job->dispatch(['wait' => 'tomorrow','queue' => 'foo']);
        $record = $job->connection()->model()->find('first');
        $this->assertEquals('foo', $record->queue);
        $this->assertEquals(date('Y-m-d H:i:s', strtotime('tomorrow')), $record->scheduled);
    }

    /**
    * @depends testDispatch
    *
    */
    public function testRunSuccess()
    {
        /**
         * Test using dispatch and check db
         */
        $job = new PassOrFailJob(true);
        $job->dispatch();
        $connection = $job->connection();

        $job = $connection->fetch();
        $this->assertEquals(0, $job->attempts());

        $this->assertTrue($job->dispatchNow());

        $this->assertEquals(1, $job->attempts());
        $this->assertFalse($connection->model()->exists(1000));
    }

    /**
     * @depends testDispatch
     *
     */
    public function testRunFail()
    {
        /**
          * Test using dispatch and check db
          */
        $job = new PassOrFailJob(false);
        $job->dispatch();
        $connection = $job->connection();

        $job = $connection->fetch();
        $this->assertEquals(0, $job->attempts());

        $this->assertFalse($job->dispatchNow());
        $this->assertEquals(1, $job->attempts());
        $this->assertTrue($connection->model()->exists(1000));
    }

    /**
     * @depends testDispatch
     *
     */
    public function testRetry()
    {
        /**
           * Test using dispatch and check db
           */
        $job = new PassOrFailJob(false);
        $job->dispatch();
        $connection = $job->connection();
  
        $job = $connection->fetch();
        $this->assertEquals(0, $job->attempts());

        $this->assertFalse($job->dispatchNow());
        $this->assertEquals(1, $job->attempts());

        $record = $connection->model()->get(1000);
        $this->assertEquals('queued', $record->status);

        $job = $connection->fetch();
        $this->assertEquals(1, $job->attempts());

        $this->assertFalse($job->dispatchNow());
        $record = $connection->model()->get(1000);
        $this->assertEquals('failed', $record->status);
    }

    public function testLoadModelException()
    {
        $job = new PassOrFailJob(true);
        $this->expectException(MissingModelException::class);
        $job->loadModel('Foo');
    }

    public function testLoadModel()
    {
        $job = new PassOrFailJob(true);
        $this->assertInstanceOf(Model::class, $job->loadModel('Article'));
        // Second time is load from property
        $this->assertInstanceOf(Model::class, $job->loadModel('Article'));
    }

    public function testSerialize()
    {
        $model = new Model(['name' => 'Article','datasource' => 'test']);
        $data = ['key' => 'value'];
        $job = new PassOrFailJob($model, $data);
        $job->id(uuid());
        $expected = [
            'className' => 'Origin\Test\Job\PassOrFailJob',
            'id' => $job->id(),
            'queue' => $job->queue,
            'arguments' => serialize(new \ArrayObject([$model,$data])),
            'attempts' => $job->attempts(),
            'created' => date('Y-m-d H:i:s'),
        ];
        $this->assertEquals($expected, $job->serialize());
    }

    public function testDeserialize()
    {
        $model = new Model(['name' => 'Article','datasource' => 'test']);
        $data = ['key' => 'value'];
        $job = new PassOrFailJob($model, $data);
        $id = uuid();
    
        $serialized = [
            'className' => 'Origin\Test\Job\PassOrFailJob',
            'id' => $id,
            'queue' => 'foo',
            'arguments' => serialize(new \ArrayObject([$model,$data])),
            'attempts' => 5,
            'created' => '2019-08-23 08:29:08',
        ];

        $job->deserialize($serialized);
        
        $this->assertEquals($id, $job->id());
        $this->assertEquals('foo', $job->queue);
        $this->assertInstanceOf(Model::class, $job->arguments()[0]);
        $this->assertEquals(['key' => 'value'], $job->arguments()[1]);
        $this->assertEquals(5, $job->attempts());
    }
}
