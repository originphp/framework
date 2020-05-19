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
use Origin\Core\Exception\Exception;
use Origin\TestSuite\OriginTestCase;

use Origin\Job\Engine\DatabaseEngine;

class MockJob extends Job
{
    public function execute(): void
    {
    }
    public function setArguments()
    {
        $this->arguments = func_get_args();

        return $this;
    }
}

class BaseEngineTest extends OriginTestCase
{
    public function testSerializeDeserialize()
    {
        $engine = new DatabaseEngine();
        $job = new MockJob();
        $serialized = $engine->serialize($job);
        $job = $engine->deserialize($serialized);
        $this->assertInstanceOf(Job::class, $job);
    }

    public function testSerializeDeserializeArguments()
    {
        $engine = new DatabaseEngine();
        $job = (new MockJob())->setArguments(['key' => 'value']);
        $serialized = $engine->serialize($job);
        $job = $engine->deserialize($serialized);
        $this->assertInstanceOf(Job::class, $job);
    }

    public function testSerializeException()
    {
        $this->expectException(Exception::class);
        $engine = new DatabaseEngine();
        $engine->serialize((new MockJob())->setArguments("\xB1\x31"));
    }

    public function testDeserializeException()
    {
        $engine = new DatabaseEngine();
        $job = new MockJob();
        $serialized = $engine->serialize($job);
        
        $this->expectException(Exception::class);
        $job = $engine->deserialize($serialized .'foo');
    }
}
