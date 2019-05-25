<?php
namespace App\Test\Controller;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\IntegrationTestTrait;
use Origin\Model\ModelRegistry;

/**
 * @property \App\Model\%model% $%model%
 */
class %controller%ControllerTest extends OriginTestCase
{
    use IntegrationTestTrait;

    public $fixtures = ['%model%'];

    public function setUp()
    {
        $this->%model% = ModelRegistry::get('%model%');
    }
}
