<?php
namespace %namespace%\Test\Model\Repository;

use Origin\TestSuite\OriginTestCase;
use %namespace%\Model\Repository\%class%Repository;

class %class%RepositoryTest extends OriginTestCase
{
    public $fixtures = ['%model%'];

    public function testRepositoryMethod()
    {
        $repository = new %class%Repository();
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
