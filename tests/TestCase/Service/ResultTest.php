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

use Origin\Service\Result;

class ResultTest extends \PHPUnit\Framework\TestCase
{
    public function testInitialize()
    {
        $result = new Result(['key' => 'value','array' => ['foo' => 'bar']]);
        $this->assertEquals('value', $result->key);
        $this->assertEquals(['foo' => 'bar'], $result->array);
    }

    public function testSuccess()
    {
        $result = new Result(['foo' => 'bar']);
        $this->assertFalse($result->success());

        $result = new Result(['success' => true]);
        $this->assertTrue($result->success());

        $result = new Result(['data' => []]);
        $this->assertTrue($result->success());
    }

    public function testError()
    {
        $result = new Result(['foo' => 'bar']);
        $this->assertFalse($result->error());

        $result = new Result(['success' => false]);
        $this->assertTrue($result->error());

        $result = new Result(['error' => []]);
        $this->assertTrue($result->error());
    }

    public function testToJson()
    {
        $result = new Result(['success' => true,'data' => ['foo' => 'bar']]);
        $expected = '{"success":true,"data":{"foo":"bar"}}';
        $this->assertEquals($expected, $result->toJson());
        $this->assertEquals($expected, (string) $result);
        $expected = <<< EOF
{
    "success": true,
    "data": {
        "foo": "bar"
    }
}
EOF;
        $this->assertEquals($expected, $result->toJson(['pretty' => true]));
    }
}
