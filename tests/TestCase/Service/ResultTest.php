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
namespace Origin\Test\Job;

use Origin\Service\Result;

class SomeObject
{
    private $data = [];
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
    public function toArray()
    {
        return $this->data;
    }
}

class ResultTest extends \PHPUnit\Framework\TestCase
{
    public function testInitialize()
    {
        $result = new Result(['key' => 'value','array' => ['foo' => 'bar']]);
        $this->assertEquals('value', $result->key);
        $this->assertEquals(['foo' => 'bar'], $result->array);
    }

    public function testToJson()
    {
        $result = new Result(['success' => true,'data' => ['foo' => 'bar']]);
        $expected = '{"success":true,"data":{"foo":"bar"}}';

        $this->assertEquals($expected, $result->toJson());
      
        $expected = <<< EOF
{
    "success": true,
    "data": {
        "foo": "bar"
    }
}
EOF;
        $this->assertEquals($expected, $result->toJson(['pretty' => true]));
        $this->assertEquals($expected, (string) $result);
    }

    public function testToArray()
    {
        $result = new Result([
            'data' => [
                'foo' => 'bar',
                'objects' => [
                    new SomeObject(['key' => 'value'])
                ]
            ]
        ]);
        $expected = [
            'data' => [
                'foo' => 'bar',
                'objects' => [
                    ['key' => 'value']
                ]
            ]];
        $this->assertSame($expected, $result->toArray());
    }

    public function testSuccess()
    {
        $result = new Result(['data' => []]);
        $this->assertTrue($result->success());

        $result = new Result(['error' => []]);
        $this->assertFalse($result->success());
    }

    public function testData()
    {
        $data = ['foo' => 'bar'];
        $result = new Result(['data' => $data]);
        $this->assertEquals('bar', $result->data('foo'));
        $this->assertNull($result->data('bar'));
        $this->assertEquals($data, $result->data());

        $result = new Result(['error' => []]);
        $this->assertNull($result->data('bar'));
    }

    public function testError()
    {
        $data = ['foo' => 'bar'];
        $result = new Result(['error' => $data]);
        $this->assertEquals('bar', $result->error('foo'));
        $this->assertNull($result->error('bar'));
        $this->assertEquals($data, $result->error());

        $result = new Result(['data' => []]);
        $this->assertNull($result->error('bar'));
    }
}
