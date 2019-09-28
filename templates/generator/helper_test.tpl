<?php
namespace %namespace%\View\Helper;

use Origin\TestSuite\OriginTestCase;
use Origin\Http\Controller\Controller;
use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\View\View;
use %namespace%\View\Helper\%class%Helper;

class %class%HelperTest extends OriginTestCase
{
    /**
    * @var \%namespace%\View\Helper\%class%Helper
    */
    protected $%class% = null;

    public function startup() : void
    {
        $controller = new Controller(new Request(),new Response());
        $view = new View($controller);
        $this->%class% = new %class%Helper($view);
    }
}