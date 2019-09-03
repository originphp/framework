<?php
namespace %namespace%\Test\Model\Repository;

use Origin\TestSuite\OriginTestCase;
use App\Model\Repository\%class%Repository;

/**
 * @property \App\Model\%class% $%class%
 */
class %class%RepositoryTest extends OriginTestCase
{
    public $fixtures = ['%class%'];

    public function startup()
    {
        $this->loadModel('%class%');
    }

    public function testRepositoryMethod()
    {
        $repository = new %class%Repository($this->%class%);
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}
