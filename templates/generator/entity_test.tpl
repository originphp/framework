<?php
namespace %namespace%\Test\Model\Entity;

use Origin\TestSuite\OriginTestCase;
use %namespace%\Model\Entity\%class%;

class %class%Test extends OriginTestCase
{
    public function testSample()
    {
        $data = [];
        $options = [];

        $user = new User($data, $options);
        $user->test = true;
        $this->assertTrue($user->has('test'));
    }
}