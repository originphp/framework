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

use Origin\Utility\Http;
use Origin\Utility\Http\Response;
use Origin\Exception\HttpException;
use Origin\Exception\NotFoundException;

class MockHttp extends Http
{
    protected $response = null;
    protected $options = [];
    protected function send(array $options)
    {
        $this->options = $options;
        if ($this->response) {
            return $this->response;
        }
        return new Response();
    }
    public function response($response = null)
    {
        if ($response === null) {
            return $this->response;
        }
        $this->response = $response;
    }
    public function options($key = null)
    {
        if ($key === null) {
            return $this->options;
        }
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }
        return null;
    }
}

class HttpTest extends \PHPUnit\Framework\TestCase
{
    public function testBuildOptionsUrl()
    {
        $http = new MockHttp();
        $http->get('https://www.google.com', ['query'=>['q'=>'keyword']]);
        $this->assertEquals('https://www.google.com?q=keyword', $http->options(CURLOPT_URL));

        $http = new MockHttp([
            'base'=>'http://www.someapi.com/api/'
            ]);
        $http->get('posts');
        $this->assertEquals('http://www.someapi.com/api/posts', $http->options(CURLOPT_URL));
    }
  
   
    public function testBuildOptionsHeaders()
    {
        $http = new MockHttp();
        $options = ['headers'=>['Foo'=>'bar','Bar: foo']];
        $http->get('https://www.example.com/posts', $options);
        $this->assertNotEmpty($http->options(CURLOPT_HTTPHEADER));
        $this->assertEquals(['Foo: bar','Bar: foo'], $http->options(CURLOPT_HTTPHEADER));
    }
    public function testBuildOptionsCookies()
    {
        $http = new MockHttp();
        $options = ['cookies'=>['Foo'=>'bar','Bar'=>'foo']];
        $http->get('https://www.example.com/posts', $options);

        $this->assertEquals(['Cookie: Foo=bar; Bar=foo'], $http->options(CURLOPT_HTTPHEADER));
    }
    public function testPersistCookies()
    {
        $http = new MockHttp();
        $options = ['cookies'=>['Foo'=>'bar']];
        $http->get('https://www.example.com/posts', $options);
     
       
        $this->assertEquals(['name'=>'Foo','value'=>'bar'], $http->cookies('Foo'));
        $this->assertEquals(['Cookie: Foo=bar'], $http->options(CURLOPT_HTTPHEADER));
        $http->get('https://www.example.com/posts');

        $this->assertEquals(['name'=>'Foo','value'=>'bar'], $http->cookies('Foo'));
        $this->assertEquals(['Cookie: Foo=bar'], $http->options(CURLOPT_HTTPHEADER));
    }

    public function testBuildOptionsType()
    {
        $http = new MockHttp();
        $options = ['type'=>'json'];
        $http->get('https://www.example.com/posts', $options);
        $this->assertEquals(['Accept: application/json','Content-Type: application/json'], $http->options(CURLOPT_HTTPHEADER));
    }
    public function testBuildOptionsMethod()
    {
        $http = new MockHttp();
        $http->get('https://www.example.com/posts');
        $this->assertTrue($http->options(CURLOPT_HTTPGET));
        $http->post('https://www.example.com/posts');
        $this->assertTrue($http->options(CURLOPT_POST));
        $http->head('https://www.example.com/posts');
        $this->assertTrue($http->options(CURLOPT_NOBODY));
        $http->put('https://www.example.com/posts');
        $this->assertEquals('PUT', $http->options(CURLOPT_CUSTOMREQUEST));
    }

    public function testBuildOptionsCustom()
    {
        $http = new MockHttp();
        $http->get('https://www.example.com/posts', [
            'userAgent'=>'OriginPHP','referer'=>'https://www.google.com','redirect'=>true
            ]);
        $this->assertEquals('OriginPHP', $http->options(CURLOPT_USERAGENT));
        $this->assertEquals('https://www.google.com', $http->options(CURLOPT_REFERER));
        $this->assertTrue($http->options(CURLOPT_FOLLOWLOCATION));
    }

    public function testBuildOptionsVerbose()
    {
        $http = new MockHttp();
        $http->get('https://www.example.com/posts', [
            'verbose'=>true
            ]);
        $this->assertEquals(true, $http->options(CURLOPT_VERBOSE));
    }

    public function testBuildOptionsForm()
    {
        $http = new MockHttp();
        $http->post('https://www.example.com/posts', [
            'title'=>'Article title','body' => 'Article body'
            ]);
        $this->assertEquals('title=Article+title&body=Article+body', $http->options(CURLOPT_POSTFIELDS));

        $http->post('https://www.example.com/posts', [ 'title'=>'Article title','body' => 'Article body'], [
            'type'=>'json'
        ]);
        $this->assertEquals('{"title":"Article title","body":"Article body"}', $http->options(CURLOPT_POSTFIELDS));

        $http->post('https://www.example.com/upload', ['file' => '@' . ROOT . DS . 'README.md']);
        $this->assertEquals(
            'file%5Bname%5D=%2Fvar%2Fwww%2FREADME.md&file%5Bmime%5D=text%2Fplain&file%5Bpostname%5D=README.md',
            $http->options(CURLOPT_POSTFIELDS)
        );

        $this->expectException(NotFoundException::class);
        $http->post('https://www.example.com/upload', ['file' => '@/does_not_exist/passwords.txt']);
    }

    public function testBuildOptionsCookieJar()
    {
        $http = new MockHttp([
            'cookieJar'=> sys_get_temp_dir() . DS . 'cookieJar'
            ]);
        $http->get('https://www.example.com/posts');
   
        $this->assertEquals(sys_get_temp_dir() . DS . 'cookieJar', $http->options(CURLOPT_COOKIEFILE));
        $this->assertEquals(sys_get_temp_dir() . DS . 'cookieJar', $http->options(CURLOPT_COOKIEJAR));
    }

    public function testBuildOptionsAuth()
    {
        $http = new MockHttp();
        $http->get('https://www.example.com/posts', [
            'auth' => ['username'=>'foo','password'=>'bar','type'=>'basic']
            ]);
        $this->assertEquals(CURLAUTH_BASIC, $http->options(CURLOPT_HTTPAUTH));
        $this->assertEquals('foo:bar', $http->options(CURLOPT_USERPWD));
        $http->get('https://www.example.com/posts', [
            'auth' => ['username'=>'foo','password'=>'bar','type'=>'digest']
            ]);
        $this->assertEquals(CURLAUTH_DIGEST, $http->options(CURLOPT_HTTPAUTH));
    }

    public function testBuildOptionsProxy()
    {
        $http = new MockHttp();
        $http->get('https://www.example.com/posts', [
            'proxy' => ['proxy'=>'192.168.1.7:1000']
            ]);
        $this->assertEquals('192.168.1.7:1000', $http->options(CURLOPT_PROXY));
        $http->get('https://www.example.com/posts', [
            'proxy' => ['proxy'=>'192.168.1.7:2000','username'=>'foo','password'=>'bar']
            ]);
        $this->assertEquals('192.168.1.7:2000', $http->options(CURLOPT_PROXY));
        $this->assertEquals('foo:bar', $http->options(CURLOPT_PROXYUSERPWD));

        $http->get('https://www.example.com/posts', [
            'proxy' => ['proxy'=>'192.168.1.7:2000','username'=>'1234-5678']
            ]);
        $this->assertEquals('1234-5678:', $http->options(CURLOPT_PROXYUSERPWD));
    }

    public function testBuildOptionsCurl()
    {
        $http = new MockHttp();
        //
        $http->get('https://www.example.com/posts', [
            'curl' => ['safe_upload'=>true]
            ]);
        $this->assertTrue($http->options(CURLOPT_SAFE_UPLOAD));
    }

    public function testHead()
    {
        $http = new Http();
        $response = $http->head('https://jsonplaceholder.typicode.com/posts/1');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->ok());
        $this->assertNotEmpty($response->headers());
        $this->assertEmpty($response->body());
    }

    public function testGet()
    {
        $http = new Http();
        $response = $http->get('https://jsonplaceholder.typicode.com/posts/1');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->ok());
        $result = json_decode($response->body()); // test using body
        $this->assertFalse($response->redirect());
        $this->assertEquals(1, $result->id);
        $this->assertNotEmpty($result->title);
    }

    public function testException()
    {
        $http = new Http();
        $this->expectException(HttpException::class);
        $response = $http->get('https://foozer');
    }

    public function testPost()
    {
        $http = new Http();
        $data = ['title'=>'curl post test','body'=>'A simple test for curl posting','userId'=>1234];
        
        $response = $http->post('https://jsonplaceholder.typicode.com/posts', $data);
 
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->success());
        $this->assertFalse($response->redirect());
        $result = json_decode($response->body()); // test using body
        $this->assertEquals(101, $result->id);
        $this->assertEquals('curl post test', $result->title);
        $this->assertEquals('A simple test for curl posting', $result->body);
    }

    public function testPut()
    {
        $http = new Http();
        $data = ['title'=>'curl put test','body'=>'A simple test for curl putting','userId'=>1234];
        
        $response = $http->put('https://jsonplaceholder.typicode.com/posts/1', $data);
        $this->assertInstanceOf(Response::class, $response);
      
        $result = json_decode($response->body()); // test using body
        $this->assertTrue($response->success());
        $this->assertEquals(1, $result->id);
        $this->assertEquals('curl put test', $result->title);
        $this->assertEquals('A simple test for curl putting', $result->body);
    }

    public function testPatch()
    {
        $http = new Http();
        $data = ['title'=>'curl patch test'];
        
        $response = $http->patch('https://jsonplaceholder.typicode.com/posts/1', ['form'=>$data]);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->success());
        $result = json_decode($response->body()); // test using body
        $this->assertEquals(1, $result->id);
        $this->assertEquals('curl patch test', $result->title);
    }

    public function testDelete()
    {
        $http = new Http();
  
        $response = $http->delete('https://jsonplaceholder.typicode.com/posts/1');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->success());

        $response = $http->delete('https://jsonplaceholder.typicode.com/foos');
        $this->assertFalse($response->success());
    }

    public function testCookies()
    {
        $http = new Http();
        $response = $http->get('http://www.cnbc.com');
        $cookies = $response->cookies();
        $this->assertEquals('WORLD', $cookies['region']['value']);
    }
}
