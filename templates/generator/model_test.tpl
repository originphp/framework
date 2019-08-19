<?php
namespace %namespace%\Test\Model;

use Origin\TestSuite\OriginTestCase;
use Origin\Model\ModelRegistry;

/**
 * @property \App\Model\%class% $%class%
 */
class %class%Test extends OriginTestCase
{
    public $fixtures = ['%class%'];

    public function startup()
    {
        parent::startup();
        $this->%class% = ModelRegistry::get('%class%');
        parent::setUp();
    }
}
