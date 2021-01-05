<?php

namespace Origin\Test\Http\Middleware;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\TestSuite\OriginTestCase;
use Origin\Http\Middleware\MinifyMiddleware;

class MinifyMiddlewareTest extends OriginTestCase
{
    public function testMinfiy()
    {
        $html = <<< EOF
<html>
    <body>
        <h1>
            foo
        </h1>
    </body>
</html>
EOF;

        $response = new Response();
        $response->body($html);

        $middleware = new MinifyMiddleware();
        $middleware(new Request(), $response);
        $this->assertEquals('<html> <body> <h1> foo </h1> </body> </html>', $response->body());
    }
    
    public function testConfigIsWorking()
    {
        $html = <<< EOF
<html>
    <body>
        <h1>
            foo
        </h1>
    </body>
</html>
EOF;

        $response = new Response();
        $response->body($html);

        $middleware = new MinifyMiddleware(['conservativeCollapse' => false]);

        $middleware(new Request(), $response);
        $this->assertEquals('<html><body><h1>foo</h1></body></html>', $response->body());
    }

    public function testNotHtml()
    {
        $json = json_encode(['key' => 'value']);

        $response = new Response();
        $response->type('application/json');
        $response->body($json);

        $middleware = new MinifyMiddleware();
        $middleware(new Request(), $response);

        $this->assertEquals($json, $response->body());
    }
    
    public function testNoResponse()
    {
        $response = new Response();
        $middleware = new MinifyMiddleware();
        $middleware(new Request(), $response);
        $this->assertNull($response->body());
    }
}
