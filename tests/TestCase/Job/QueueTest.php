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

use Origin\Job\Queue;

use Origin\TestSuite\OriginTestCase;
use Origin\Core\Exception\InvalidArgumentException;

class QueueTest extends OriginTestCase
{
    public function testConfig()
    {
        $this->expectException(InvalidArgumentException::class);
        Queue::connection('foo');
    }

    public function testInvalidClassName()
    {
        $this->expectException(InvalidArgumentException::class);
        Queue::config('foo', ['className' => 'Foozibar']);
        Queue::connection('foo');
    }
}
