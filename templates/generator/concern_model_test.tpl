<?php
namespace %namespace%\Test\Model\Concern;

use Origin\TestSuite\OriginTestCase;
use %namespace%\Model\Concern\%class%;
use Origin\Model\Model;

class User extends Model
{
    use %class%;
}

/**
 * @property \App\Model\User $User
 */
class %class%Test extends OriginTestCase
{
    public $fixtures = ['User'];

    public function startup() : void
    {
        $this->loadModel('User', ['className' => User::class]);
    }

    public function testConcernMethod()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}