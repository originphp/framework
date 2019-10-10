<?php
namespace %namespace%\Test\Controller\Concern;

use Origin\TestSuite\OriginTestCase;
use %namespace%\Http\Controller\Concern\%class%;
use Origin\Http\Controller\Controller;

class DummyController extends Controller
{
    use %class%;
}

/**
 * @property \App\Model\User $User
 */
class %class%Test extends OriginTestCase
{
    public function testConcernMethod()
    {
        $controller = new DummyController();
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}