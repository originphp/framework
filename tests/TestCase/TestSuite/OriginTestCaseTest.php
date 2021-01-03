<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
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
use Origin\Model\Entity;
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

class UserEntity extends Entity
{
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

    public function options(array $options = null): array
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
        $OriginTestCase = new AnotherMockOriginTestCase();
        $mock = $OriginTestCase->getMock('Origin\Test\TestSuite\StrawberryTart', ['true']);
        
        $mock->expects($this->once())
            ->method('true')
            ->willReturn(false);

        $this->assertFalse($mock->true());
    }
    public function testMockOptions()
    {
        $OriginTestCase = new AnotherMockOriginTestCase();
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
        $OriginTestCase = new AnotherMockOriginTestCase();
        $mock = $OriginTestCase->getMockForModel('User', ['true'], ['className' => 'Origin\Test\TestSuite\User']);
        
        // Mock true to return false
        $mock->expects($this->once())
            ->method('true')
            ->willReturn(false);

        $this->assertFalse($mock->true());
        $this->assertEquals('userz', $mock->table());
    }

    public function testTestGetMockModelOptions()
    {
        $OriginTestCase = new AnotherMockOriginTestCase();
        $mock = $OriginTestCase->getMockForModel('User', ['true'], [
            'name' => 'ThisIsUser',
            'alias' => 'SomeUser',
            'entityClass' => UserEntity::class,
            'className' => User::class
        ]);
        $this->assertEquals('ThisIsUser', $mock->name());
        $this->assertEquals('SomeUser', $mock->alias());
        $this->assertEquals(UserEntity::class, $mock->entityClass());
        $this->assertTrue(method_exists($mock, 'find'));

        // test that default connection is test, its hacky, but did not want to polu
        $this->assertEquals('test', $mock->connection()->config()['connection']);
    }

    public function testUnkownClass()
    {
        $this->expectException(MissingModelException::class);

        (new AnotherMockOriginTestCase())->getMockForModel('Foo');
    }
    public function testCallbacks()
    {
        $OriginTestCase = new AnotherMockOriginTestCase();
        $this->assertNull($OriginTestCase->setUp());
        $this->assertNull($OriginTestCase->tearDown());
    }

    public function testFixtures()
    {
        $OriginTestCase = new AnotherMockOriginTestCase();
        $OriginTestCase->fixtures(['Article']);
        $this->assertEquals(['Article'], $OriginTestCase->fixtures());
    }

    public function testDeprecation()
    {
        (new AnotherMockOriginTestCase())->deprecated(function () {
            deprecationWarning('foo is deprecated use bar instead.');
            $this->assertTrue(true);
        });
    }

    public function testAssertStringContains()
    {
        (new AnotherMockOriginTestCase())->assertStringContains('fox', 'A quick brown fox');
    }
    public function testAssertStringNotContains()
    {
        (new AnotherMockOriginTestCase())->assertStringNotContains('foo', 'A quick brown fox');
    }
}
/**
 * This must be AFTER test case declaration
 */
class AnotherMockOriginTestCase extends OriginTestCase
{
}
