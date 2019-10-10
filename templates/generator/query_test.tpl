<?php
namespace %namespace%\Test\Model\Query;

use Origin\TestSuite\OriginTestCase;
use %namespace%\Model\Query\%class%Query;

class %class%QueryTest extends OriginTestCase
{
    protected $fixtures = ['User'];

    protected function initialize() : void
    {
        $this->loadModel('User');
    }

    public function testQueryExecute()
    {
        $result = (new %class%Query($this->User))->execute();
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
