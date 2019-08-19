<?php
namespace %namespace%\Test\Controller;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\IntegrationTestTrait;
use Origin\Model\ModelRegistry;

/**
 * @property \App\Model\%model% $%model%
 */
class %class%ControllerTest extends OriginTestCase
{
    use IntegrationTestTrait;

    public $fixtures = ['%model%'];

    public function startup()
    {
        parent::startup();
        $this->%model% = ModelRegistry::get('%model%');
    }
    
%methods%
}