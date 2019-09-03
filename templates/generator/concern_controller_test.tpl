<?php
namespace %namespace%\Test\Controller\Concern;

use Origin\TestSuite\OriginTestCase;
use App\Controller\Concern\%class%Concern;
use %namespace%\Controller\AppController;

use Origin\Http\Request;
use Origin\Http\Response;

class %class%ConcernTest extends OriginTestCase
{
   public function startup()
    {
        $this->controller = new AppController(
            new Request('/controller/action'),
            new Response()
        );
    }

    public function testConcernMethod()
    {
        $concern = new %class%Concern($this->controller);
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}
