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

namespace Origin\Test\Controller;

use Origin\Http\Response;
use Origin\Exception\NotFoundException;

class ResponseTest extends \PHPUnit\Framework\TestCase
{
    public function testBody()
    {
        $content = '<h1>Title</h1>';
        $response  = new Response();
        $response->body($content);
        $this->assertEquals($content, $response->body());
    }
    public function testHeader()
    {
        $response  = new Response();
        
        $response->header('Accept-Language', 'en-us,en;q=0.5');
        $response->header(['Accept-Encoding'=>'gzip,deflate']);

        $headers = $response->headers();
        
        $this->assertEquals('en-us,en;q=0.5', $headers['Accept-Language']);
        $this->assertEquals('gzip,deflate', $headers['Accept-Encoding']);
    }
    public function testCookie()
    {
        $response  = new Response();
        $response->cookie('foo', 'bar');
        $this->assertEquals('bar', $response->cookie('foo'));

        $cookies = $response->cookies();

        $this->assertEquals('bar', $cookies['foo']['value']);
        $this->assertNull($response->cookie('jar'));
    }
    public function testStatusCode()
    {
        $response  = new Response();
        $response->statusCode(501);
        $this->assertEquals(501, $response->statusCode());
    }

    public function testSend()
    {
        $response  = new Response();
        $response->statusCode(200);
        $response->header('Accept-Language', 'en-us,en;q=0.5');
        $this->assertNull($response->send()); // or $response->send()
    }

    public function testType()
    {
        $response  = new Response();
        
        // Test Set
        $this->assertEquals('text/html', $response->type());
        $this->assertEquals('application/json', $response->type('json'));

        $response->type(['swf' => 'application/x-shockwave-flash']);
        $this->assertEquals('application/x-shockwave-flash', $response->type('swf'));
        $mpeg = 'audio/mpeg';
        $this->assertEquals($mpeg, $response->Type($mpeg));
        $this->assertFalse($response->type('foo'));
    }

    public function testFile()
    {
        $response  = new Response();
       
       
        $response->file('/var/www/README.md', ['download'=>true]);
        $headers = $response->headers();
        $this->assertEquals('attachment; filename="README.md"', $headers['Content-Disposition']);

        $this->expectException(NotFoundException::class);
        $response->file('/var/www/---does-not-exist.md', ['download'=>true]);
    }
}
