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

    public function startup()
    {
        parent::startup(); // remember parent
        $this->loadModel('%model%');
    }
    
%methods%
}