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
namespace Origin\Test\Job;

use Origin\Job\Job;
use Origin\Model\Model;
use Origin\Security\Security;
use Origin\Model\ModelRegistry;
use Origin\TestSuite\TestTrait;
use Origin\TestSuite\OriginTestCase;
use Origin\Job\Engine\DatabaseEngine;
use Origin\Model\Exception\MissingModelException;

class PassOrFailJob extends Job
{
    use TestTrait;
    
    protected $connection = 'default';

    protected function initialize(): void
    {
        $this->status = 'new';
        $this->onError('errorHandler');
        $this->successHandler('done');
    }

    public function execute(bool $pass = true): void
    {
        if (! $pass) {
            $a = 1 / 0;
        }
    }

    public function successHandler(bool $pass = true): void
    {
        $this->status = 'success';
    }

    public function errorHandler(\Exception $exception): void
    {
        $this->status = 'error';
        $this->retry(['wait' => 'now','limit' => 1]);
    }

    public function set($array)
    {
        foreach ($array as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function callbacks(string $callback)
    {
        return array_keys($this->registeredCallbacks($callback));
    }
}
class PassOrFailRedis extends PassOrFailJob
{
    protected $connection = 'redis-test';
}

class JobTest extends OriginTestCase
{
    protected $fixtures = ['Origin.Queue'];

    protected function setUp(): void
    {
        $model = new Model(['name' => 'Article','connection' => 'test']);
        ModelRegistry::set('Article', $model);
    }

    public function testConstruct()
    {
        $job = new PassOrFailJob();
        $this->assertIsUUID($job->id());
    }

    public function testGetId()
    {
        $job = new PassOrFailJob();
        $this->assertNotNull($job->id());
    }

    public function testSchedule()
    {
        $job = new PassOrFailJob();
        $job->schedule('+10 minutes');
        $this->assertEquals('+10 minutes', $job->getProperty('wait'));
    }

    public function testBackendId()
    {
        $job = new PassOrFailJob();
        $this->assertNull($job->backendId());
        $job->backendId(12345);
        $this->assertEquals(12345, $job->backendId());
    }

    public function testConnection()
    {
        $job = new PassOrFailJob();
        $connection = $job->connection();
        $this->assertInstanceOf(DatabaseEngine::class, $connection);
        $this->assertEquals('test', $connection->config('connection'));
    }

    public function testDispatch()
    {
        $job = new PassOrFailJob(['wait' => 'tomorrow','queue' => 'foo']);
        $job->dispatch(true);
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
        $job = new PassOrFailJob();
        $job->dispatch(true);
        $connection = $job->connection();

        $job = $connection->fetch();
        $id = $job->backendId();

        $this->assertEquals(0, $job->attempts());

        $this->assertTrue($job->dispatchNow());

        $this->assertEquals(1, $job->attempts());
        $this->assertFalse($connection->model()->exists($id)); # for Databasedriver

        $this->assertEquals('success', $job->status);
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
        $job = new PassOrFailJob();
        $job->dispatch(false);
        $connection = $job->connection();

        $job = $connection->fetch();
        $id = $job->backendId();

        $this->assertEquals(0, $job->attempts());

        $this->assertFalse($job->dispatchNow(false));
        $this->assertEquals(1, $job->attempts());
        $this->assertTrue($connection->model()->exists($id));
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
        $job = new PassOrFailJob();
        $job->dispatch(false);
        $connection = $job->connection();
  
        $job = $connection->fetch();
        $id = $job->backendId();

        $this->assertEquals(0, $job->attempts());

        $this->assertFalse($job->dispatchNow(false));
        $this->assertEquals(1, $job->attempts());

        $record = $connection->model()->get($id);
        $this->assertEquals('queued', $record->status);

        $job = $connection->fetch();
        $this->assertEquals(1, $job->attempts());

        $this->assertFalse($job->dispatchNow(false));
        $record = $connection->model()->get($id);
        $this->assertEquals('failed', $record->status);
    }

    public function testLoadModelException()
    {
        $job = new PassOrFailJob();
        $this->expectException(MissingModelException::class);
        $job->loadModel('Foo');
    }

    public function testLoadModel()
    {
        $job = new PassOrFailJob();
        $this->assertInstanceOf(Model::class, $job->loadModel('Article'));
        // Second time is load from property
        $this->assertInstanceOf(Model::class, $job->loadModel('Article'));
    }

    public function testSerialize()
    {
        $model = new Model(['name' => 'Article','connection' => 'test']);
        $data = ['key' => 'value'];
        $job = new PassOrFailJob();
        $job->set(['arguments' => [$model, $data]]);
        $job->backendId(1000);

        $expected = [
            'className' => 'Origin\Test\Job\PassOrFailJob',
            'id' => $job->id(),
            'backendId' => 1000,
            'queue' => $job->queue(),
            'arguments' => serialize(new \ArrayObject([$model,$data])),
            'attempts' => $job->attempts(),
            'enqueued' => null,
            'serialized' => date('Y-m-d H:i:s'),
        ];
        $this->assertEquals($expected, $job->serialize());
    }

    public function testDeserialize()
    {
        $model = new Model(['name' => 'Article','connection' => 'test']);
        $data = ['key' => 'value'];
        $job = new PassOrFailJob();
        $job->set(['arguments' => [$model, $data]]);
        $id = Security::uuid();
    
        $serialized = [
            'className' => 'Origin\Test\Job\PassOrFailJob',
            'id' => $id,
            'backendId' => 1000,
            'queue' => 'foo',
            'arguments' => serialize(new \ArrayObject([$model,$data])),
            'attempts' => 5,
            'enqueued' => '2019-08-23 08:29:08',
            'serialized' => date('Y-m-d H:i:s'),
        ];

        $job->deserialize($serialized);
        
        $this->assertEquals($id, $job->id());
        $this->assertEquals('foo', $job->queue());
        $this->assertInstanceOf(Model::class, $job->arguments()[0]);
        $this->assertEquals(['key' => 'value'], $job->arguments()[1]);
        $this->assertEquals(5, $job->attempts());
    }

    public function assertIsUUID(string $id = null)
    {
        $this->assertMatchesRegularExpression(
            '/\b[0-9a-f]{8}\b-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-\b[0-9a-f]{12}\b/',
            $id
        );
    }

    public function testBeforeQueue()
    {
        $job = new PassOrFailJob();
        $job->callMethod('beforeQueue', ['foo']);
        $job->callMethod('beforeQueue', ['bar']);
        $this->assertEquals(['foo','bar'], $job->callbacks('beforeQueue'));
    }

    public function testAfterQueue()
    {
        $job = new PassOrFailJob();
        $job->callMethod('afterQueue', ['foo']);
        $job->callMethod('afterQueue', ['bar']);
        $this->assertEquals(['foo','bar'], $job->callbacks('afterQueue'));
    }

    public function testBeforeDispatch()
    {
        $job = new PassOrFailJob();
        $job->callMethod('beforeDispatch', ['foo']);
        $job->callMethod('beforeDispatch', ['bar']);
        $this->assertEquals(['foo','bar'], $job->callbacks('beforeDispatch'));
    }

    public function testAfterDispatch()
    {
        $job = new PassOrFailJob();
        $job->callMethod('afterDispatch', ['foo']);
        $job->callMethod('afterDispatch', ['bar']);
        $this->assertEquals(['foo','bar'], $job->callbacks('afterDispatch'));
    }
}
