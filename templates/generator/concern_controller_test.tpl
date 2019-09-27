<?php
namespace %namespace%\Test\Controller\Concern;

use Origin\TestSuite\OriginTestCase;
use App\Controller\ApplicationController;
use %namespace%\Controller\Concern\%class%Concern;

use Origin\Http\Request;
use Origin\Http\Response;

class %class%ConcernTest extends OriginTestCase
{
   public function startup() : void
    {
        $this->controller = new ApplicationController(
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
