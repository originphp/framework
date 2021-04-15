<?php
namespace App\Test\Http\Middleware;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\TestSuite\OriginTestCase;
use Origin\Http\Middleware\MaintenanceModeMiddleware;
use Origin\Http\Middleware\Exception\MaintainenceModeException;

class MockMaintenanceModeMiddleware extends MaintenanceModeMiddleware
{
    protected $headers = [];
    protected $stopped = false;
    
    protected function sendHeader(string $header): void
    {
        list($key, $value) = explode(': ', $header);
        $this->headers[$key] = $value;
    }

    protected function exit(): void
    {
        $this->stopped = true;
    }

    public function headers(string $header = null)
    {
        if ($header === null) {
            return $this->headers;
        }

        return $this->headers[$header] ?? null;
    }

    public function wasStopped()
    {
        return $this->stopped === true;
    }
}

class MaintenanceModeMiddlewareTest extends OriginTestCase
{
    /**
    * @var \Origin\Http\Request
    */
    protected $request = null;

    /**
    * @var \Origin\Http\Response
    */
    protected $response = null;
    protected $payload = [];

    protected function setUp(): void
    {
        $this->request = new Request();
        $this->response = new Response();
    }

    private function enableMaintenanceMode()
    {
        $this->payload = ['message' => null,'allowed' => ['192.168.1.200'],'retry' => 60,'time' => time()];
        file_put_contents(
            tmp_path('maintenance.json'),
            json_encode($this->payload)
        );
    }

    public function testMaintenanceModeDisabled()
    {
        // Invoke the middleware
        $middleware = new MaintenanceModeMiddleware();
        $middleware($this->request, $this->response);

        $this->assertInstanceOf(MaintenanceModeMiddleware::class, $middleware);
    }

    public function testMaintenanceModeEnabled()
    {
        $this->enableMaintenanceMode();
        
        //REMOTE_ADDR
        $middleware = new MockMaintenanceModeMiddleware();
        $this->request->server('REMOTE_ADDR', '192.168.1.100');

        $this->expectException(MaintainenceModeException::class);
        $middleware($this->request, $this->response);
    }

    public function testMaintenanceModeEnabledAllow()
    {
        $this->enableMaintenanceMode();
        $middleware = new MockMaintenanceModeMiddleware();
        $this->request->server('REMOTE_ADDR', '192.168.1.200');

        $middleware($this->request, $this->response);
        $this->assertFalse($middleware->wasStopped());
    }

    public function testMaintenanceModeEnabledHtml()
    {
        $this->enableMaintenanceMode();

        $middleware = new MockMaintenanceModeMiddleware();
        $middleware->config('html', true);
        
        $middleware($this->request, $this->response);
        $this->assertTrue($middleware->wasStopped());
        $this->assertEquals('/maintenance.html', $middleware->headers('Location'));
    }

    /**
     * Use HTML for this test
     *
     * @return void
     */
    public function testHeaders()
    {
        $this->enableMaintenanceMode();

        $middleware = new MockMaintenanceModeMiddleware();
        $middleware->config('html', true);
        
        $middleware($this->request, $this->response);
        $this->assertTrue($middleware->wasStopped());
        $this->assertEquals('/maintenance.html', $middleware->headers('Location'));
        $this->assertEquals($this->payload['time'], $middleware->headers('Maintenance-Started'));
        $this->assertEquals($this->payload['time'] + 60, $middleware->headers('Retry-After'));
    }

    protected function tearDown(): void
    {
        if (file_exists(tmp_path('maintenance.json'))) {
            unlink(tmp_path('maintenance.json'));
        }
    }
}
