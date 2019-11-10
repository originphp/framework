<?php

namespace App\Test\Http\Middleware;

use Origin\TestSuite\OriginTestCase;
use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Middleware\IdsMiddleware;

class MockIdsMiddleware extends IdsMiddleware
{
    public function events()
    {
        return $this->events;
    }

    protected function cleanUp() : void
    {
        // don do anything
    }
}

class IdsMiddlewareTest extends OriginTestCase
{
    /**
     * @var \Origin\Http\Request
     */
    protected $request = null;

    /**
     * @var \Origin\Http\Response
     */
    protected $response = null;

    protected function startup(): void
    {
        $this->request = new Request();
        $this->response = new Response();
    }


    public function testGet()
    {
        $_GET = [
            'url' => "bookmarks/view/1000') OR 1 = 1 --"
        ];

        // Invoke the middleware
        $middleware = new MockIdsMiddleware(['level'=>3]);
        $middleware->handle($this->request);
 
        $this->assertContains('SQL Injection Attack', $middleware->events()[0]['matches']);
        $_GET = [];
    }

    public function testQuery()
    {
        //http://localhost:8000/bookmarks/view/1000?id=-1%20UNION%20SELECT%20password%20FROM%20users%20where%20id=1
        $this->request->query('id', '-1 UNION SELECT password FROM users where id=1');

        // Invoke the middleware
        $middleware = new MockIdsMiddleware(['level'=>3]);
        $middleware->handle($this->request);
        $this->assertContains('SQL Injection Attack', $middleware->events()[0]['matches']);
    }

    public function sql()
    {
        return [
            ["' OR 1=1"],
            ["' OR 1 = 1"],
            ["' OR 1<2"],
            ["' OR 2<1"],
            ["' OR 2 < 1"],
            ["' OR 1 < 2"],
            ["' OR '1'='1'"],
            ["' OR '1'='1' #"],
            ["' OR 1 = 1 LIMIT 1' "],
            ["' OR 1 = 1 LIMIT 1 #' "],
            ["') OR 1 = 1 --"],
            ["' OR 2>1"],
            ["-1 UNION SELECT 1, 2, 3"],
            ["'-1 UNION SELECT 1, 2, 3"],
        ];
    }

    /**
    * @dataProvider sql
    */
    public function testSql($data)
    {
        // Invoke the middleware
        $middleware = new MockIdsMiddleware();

        $middleware->run(['data' => ['input' => $data]]);
        $events = $middleware->events();
        $this->assertNotEmpty($events);
        $this->assertContains('SQL Injection Attack', $events[0]['matches']);
    }

    /**
     * @dataProvider xss
     */
    public function testXss($data)
    {
        // Invoke the middleware
        $middleware = new MockIdsMiddleware();

        $middleware->run(['data' => ['input' => $data]]);
        $events = $middleware->events();
        $this->assertNotEmpty($events);
        $this->assertContains('XSS Attack', $events[0]['matches']);
    }

    

    /**
     * Test data from https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet
     *
     * Octal/Hex encoding/Base64 encoding does not detect
     */
    public function xss()
    {
        return [
            ['%3C%73%63%72%69%70%74%3Ealert(\'xss\')'], // <script>
            ['<SCRIPT SRC=http://xss.rocks/xss.js></SCRIPT>'],
            ['<IMG SRC=JaVaScRiPt:alert(\'XSS\')>'],
            ['javascript:/*--></title></style></textarea></script></xmp><svg/onload=\'+/"/+/onmouseover=1/+/[*/[]/+alert(1)//\'>'],
            ['<IMG SRC="javascript:alert(\'XSS\');">'],
            ['<IMG SRC=javascript:alert(\'XSS\')>'],
            ['<IMG SRC=JaVaScRiPt:alert(\'XSS\')>'],
            ['<IMG SRC=javascript:alert(&quot;XSS&quot;)>'],
            ['<IMG SRC=`javascript:alert("RSnake says, \'XSS\'")`>'],
            ['<a onmouseover="alert(document.cookie)">xxs link</a>'],
            ['<IMG """><SCRIPT>alert("XSS")</SCRIPT>">'],
            ['<IMG SRC=javascript:alert(String.fromCharCode(88,83,83))>'],
            ['<IMG SRC=# onmouseover="alert(\'xxs\')">'],
            ['<IMG SRC= onmouseover="alert(\'xxs\')">'],
            ['<IMG SRC=/ onerror="alert(String.fromCharCode(88,83,83))"></img>'],
            ['<img src=x onerror="&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058&#0000097&#0000108&#0000101&#0000114&#0000116&#0000040&#0000039&#0000088&#0000083&#0000083&#0000039&#0000041">'],
            ['<IMG SRC=&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;&#97;&#108;&#101;&#114;&#116;&#40;
            &#39;&#88;&#83;&#83;&#39;&#41;>'],
            ['<IMG SRC=&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058&#0000097&
            #0000108&#0000101&#0000114&#0000116&#0000040&#0000039&#0000088&#0000083&#0000083&#0000039&#0000041>'],
            ['<IMG SRC=&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74&#x3A&#x61&#x6C&#x65&#x72&#x74&#x28&#x27&#x58&#x53&#x53&#x27&#x29>'],
            ['<IMG SRC="jav	ascript:alert(\'XSS\');">'],
            ['<IMG SRC="jav&#x09;ascript:alert(\'XSS\');">'],
            ['<IMG SRC="jav&#x0A;ascript:alert(\'XSS\');">'],
            ['<IMG SRC=" &#14;  javascript:alert(\'XSS\');">'],
            ['<SCRIPT/XSS SRC="http://xss.rocks/xss.js"></SCRIPT>'],
            ['<SCRIPT/SRC="http://xss.rocks/xss.js"></SCRIPT>'],
            ['<<SCRIPT>alert("XSS");//<</SCRIPT>'],
            ['<SCRIPT SRC=http://xss.rocks/xss.js?< B >'],
            ['<SCRIPT SRC=//xss.rocks/.j>'],
            ['<IMG SRC="javascript:alert(\'XSS\')"'],
            ['<iframe src=http://xss.rocks/somepage.html <'],
            ['</script><script>alert(\'XSS\');</script>'],
            ['<svg/onload=alert(\'XSS\')>'],
            ['<BODY ONLOAD=alert(\'XSS\')>'],
            ['<META HTTP-EQUIV="refresh" CONTENT="0; URL=http://;URL=javascript:alert(\'XSS\');">'],
            ['<SCRIPT a=">" \'\' SRC="httx://xss.rocks/xss.js"></SCRIPT>'],
            ['<A HREF="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">XSS</A>'],
            ['<A HREF="http://0x42.0x0000066.0x7.0x93/">XSS</A>'],
            ['<IMG SRC=\'vbscript:msgbox("XSS")\'>']
          
        ];
    }

    /**
    * @dataProvider html
    */
    public function testHtml($data)
    {
        // Invoke the middleware
        $middleware = new MockIdsMiddleware();

        $middleware->run(['data' => ['input' => $data]]);
        $events = $middleware->events();
        $this->assertEmpty($events);
    }


    public function html()
    {
        return [
            ['<a href="foo">test</a>'],
            ['<br/>'],
            ['<h2 class="foo">Foo</h2>']
        ];
    }
}
