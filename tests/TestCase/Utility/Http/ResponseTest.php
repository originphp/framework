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

use Origin\Utility\Http\Response;
use Origin\Utility\Xml;

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
        $this->assertNull($response->header('Access-Control-Allow-Origin: *'));
       
        $this->assertNull($response->header('Access-Control-Allow-Origin', '*'));
        
        $this->assertEquals('*', $response->headers('Access-Control-Allow-Origin'));
    
        $expected = [
            'Access-Control-Allow-Origin: *' => null,
            'Access-Control-Allow-Origin' => '*'
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
        $data =['name'=>'foo','value'=>'bar'];
        $response = new Response();
        $response->body(json_encode($data));
        $this->assertEquals($data, $response->json());
    }

    public function testXml()
    {
        $data = ['root'=>['name'=>'foo','value'=>'bar']];
        $response = new Response();
        $response->body(Xml::fromArray($data));
        $this->assertEquals($data, $response->xml());
    }
}
