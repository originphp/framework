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
namespace Origin\Test\Utils;

use Origin\Utils\Queue;
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

    public function testFetch()
    {
        $queue = new Queue(['datasource'=>'test']);
        $id = $queue->add('welcome_emails', ['user_id'=>1234]);
        // Test job entity
        $job = $queue->fetch('welcome_emails');
        $this->assertEquals($id, $job->id);
        // Test is set to locked
        $job = $queue->model()->get($id);
        $this->assertEquals(1, $job->locked);
    }

    

    public function tearDown()
    {
    }
}
