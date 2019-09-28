<?php
namespace %namespace%\Test\Http\Controller\Component;

use Origin\TestSuite\OriginTestCase;
use Origin\Http\Controller\Controller;
use Origin\Http\Request;
use Origin\Http\Response;
use %namespace%\Controller\Component\%class%Component;

class %class%ComponentTest extends OriginTestCase
{
    /**
    * @var \%namespace%\Controller\Component\%class%Component
    */
    protected $%class% = null;

    public function startup() : void
    {
        $controller = new Controller(new Request(),new Response());
        $this->%class% = new %class%Component($controller);
    }
}