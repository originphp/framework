<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\TestSuite;

use Origin\Model\Model;
use Origin\Model\ModelRegistry;
use Origin\TestSuite\OriginTestCase;
use Origin\Model\Exception\MissingModelException;

class User extends Model
{
    public function true()
    {
        return true;
    }
}

class LemonPie
{
    protected $name = 'LemonPie';
    protected $options = [];
    public function __construct(array $options)
    {
        $this->options = $options;
    }
    public function true()
    {
        return true;
    }

    public function options(array $options = null) : array
    {
        if ($options === null) {
            return $this->options;
        }
        return $this->options = $options;
    }
}

class StrawberyTart
{
    protected $name = 'StrawberryTart';
    public function true()
    {
        return true;
    }
}

class OriginTestCaseTest extends \PHPUnit\Framework\TestCase
{
    public function testMock()
    {
        $OriginTestCase = new OriginTestCase();
        $mock = $OriginTestCase->getMock('Origin\Test\TestSuite\StrawberryTart', ['true']);
        
        $mock->expects($this->once())
            ->method('true')
            ->willReturn(false);

        $this->assertFalse($mock->true());
    }
    public function testMockOptions()
    {
        $OriginTestCase = new OriginTestCase();
        $mock = $OriginTestCase->getMock('Origin\Test\TestSuite\LemonPie', ['true'], ['cookingTime' => '20 mins']);
        
        $mock->expects($this->once())
            ->method('true')
            ->willReturn(false);

        $this->assertFalse($mock->true());
        $this->assertEquals('20 mins', $mock->options()['cookingTime']);
    }
    
    public function testGetMockForModel()
    {
        ModelRegistry::config('User', ['table' => 'userz']);
        $OriginTestCase = new OriginTestCase();
        $mock = $OriginTestCase->getMockForModel('User', ['true'], ['className' => 'Origin\Test\TestSuite\User']);
        
        // Mock true to return false
        $mock->expects($this->once())
            ->method('true')
            ->willReturn(false);

        $this->assertFalse($mock->true());
        $this->assertEquals('userz', $mock->table);
    }

    public function testUnkownClass()
    {
        $this->expectException(MissingModelException::class);

        $OriginTestCase = new OriginTestCase();
        $mock = $OriginTestCase->getMockForModel('Foo');
    }
    public function testCallbacks()
    {
        $OriginTestCase = new OriginTestCase();
        $this->assertNull($OriginTestCase->setUp());
        $this->assertNull($OriginTestCase->tearDown());
    }
}
