<?php
namespace %namespace%\Test\Controller\Component;

use Origin\TestSuite\OriginTestCase;
use Origin\Controller\Controller;
use Origin\Http\Request;
use Origin\Http\Response;
use App\Controller\Component\%class%Component;

class %class%ComponentTest extends OriginTestCase
{
    /**
    * @var \App\Controller\Component\%class%Component
    */
    protected $%class% = null;

    public function startup()
    {
        $controller = new Controller(new Request(),new Response());
        $this->%class% = new %class%Component($controller);
    }
}