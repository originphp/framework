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

use Origin\Utility\Xml;
use Origin\Utility\Http\Response;

class ResponseTest extends \PHPUnit\Framework\TestCase
{
    public function testStatuCode()
    {
        $response = new Response();
        $response->statusCode(400);
        $this->assertEquals(400, $response->statusCode());
    }

    public function testBody()
    {
        $response = new Response();
        $response->body('abc 1234');
        $this->assertEquals('abc 1234', $response->body());
    }

    public function testHeaders()
    {
        $response = new Response();
        $this->assertEquals(['Access-Control-Allow-Origin' => '*'], $response->header('Access-Control-Allow-Origin: *'));
       
        $this->assertEquals(['Accept-Encoding' => 'gzip,deflate'], $response->header('Accept-Encoding', 'gzip,deflate'));
        
        $this->assertEquals('*', $response->headers('Access-Control-Allow-Origin'));
    
        $expected = [
            'Access-Control-Allow-Origin' => '*',
            'Accept-Encoding' => 'gzip,deflate'
        ];
        $this->assertEquals($expected, $response->headers());
        $this->assertNull($response->headers('abc'));
    }

    public function testCookies()
    {
        $response = new Response();
        $response->cookie('sessionid', 1234);
        $response->cookie('id', 5678);
        $this->assertEquals(5678, $response->cookies('id')['value']);
        $this->assertEquals(1234, $response->cookies('sessionid')['value']);
        $this->assertNull($response->cookies('abc'));
        $result = $response->cookies();
        $this->assertIsArray($result);
        $this->assertEquals(2, count($result));
    }

    public function testJson()
    {
        $data = ['name' => 'foo','value' => 'bar'];
        $response = new Response();
        $this->assertNull($response->json());
        $response->body(json_encode($data));
        $this->assertEquals($data, $response->json());
    }

    public function testXml()
    {
        $data = ['root' => ['name' => 'foo','value' => 'bar']];
        $response = new Response();
        $this->assertNull($response->xml());
        $response->body(Xml::fromArray($data));
        $this->assertEquals($data, $response->xml());
    }

    public function testToString()
    {
        $response = new Response();
        $this->assertNull($response->__toString());
        $response->body('hello world');
        $this->assertEquals('hello world', $response->__toString());
    }
}
