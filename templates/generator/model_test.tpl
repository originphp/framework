<?php
namespace %namespace%\Test\Model;

use Origin\TestSuite\OriginTestCase;

/**
 * @property \App\Model\%class% $%class%
 */
class %class%Test extends OriginTestCase
{
    public $fixtures = ['%class%'];

    public function startup()
    {
        parent::startup(); // remember parent
        $this->loadModel('%class%');
    }
}
