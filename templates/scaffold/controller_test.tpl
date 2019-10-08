<?php
namespace App\Test\Http\Controller;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\IntegrationTestTrait;
use Origin\Model\ModelRegistry;

/**
 * @property \App\Model\%model% $%model%
 */
class %controller%ControllerTest extends OriginTestCase
{
    use IntegrationTestTrait;

    protected $fixtures = ['%model%'];

    protected function setUp(): void
    {
        $this->%model% = ModelRegistry::get('%model%');
    }
}
