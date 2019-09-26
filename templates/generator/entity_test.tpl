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

        $entity = new %class%($data, $options);
        $entity->test = true;
        $this->assertTrue($entity->has('test'));
    }
}