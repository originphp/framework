<?php
namespace %namespace%\Test\Controller;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\IntegrationTestTrait;

/**
 * @property \App\Model\%model% $%model%
 */
class %class%ControllerTest extends OriginTestCase
{
    use IntegrationTestTrait;

    public $fixtures = ['%model%'];

    public function startup() : void
    {
        $this->loadModel('%model%');
    }

    public function testIndexExample()
    {
        $this->get('/%underscored%/index');
        $this->assertResponseOk();
        $this->assertResponseContains('<h1>%human%</h1>');
    }

    public function testNotFoundExample()
    {
        $this->get('/%underscored%/does-not-exist');
        $this->assertResponseNotFound();
    }
    
%methods%
}