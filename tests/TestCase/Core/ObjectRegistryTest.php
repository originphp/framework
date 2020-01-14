<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Core;

use Origin\Core\ObjectRegistry;
use Origin\Core\Exception\MissingClassException;

class MockObjectRegistry extends ObjectRegistry
{
    public function getLoaded()
    {
        return array_keys($this->loaded);
    }
}
class LemonPie
{
    protected $name = 'LemonPie';
    
    protected $called = 0;

    public function startup(int $x)
    {
        $this->called = $this->called + $x;
    }
    public function called()
    {
        return $this->called;
    }
}
class ObjectRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testSet()
    {
        $LemonPie = new LemonPie();

        $Registry = new MockObjectRegistry();
        $Registry->set('LemonPie', $LemonPie);
        $this->assertEquals(['LemonPie'], $Registry->getLoaded());
        $this->assertTrue(isset($Registry->LemonPie));
        $this->assertNull($Registry->set('LemonPie', $LemonPie)); // set second time
    }

    /**
     * @depends testSet
     */
    public function testGet()
    {
        $LemonPie = new LemonPie();

        $Registry = new ObjectRegistry();
        $Registry->set('LemonPie', $LemonPie);

        $this->assertEquals($LemonPie, $Registry->get('LemonPie'));
        $this->assertEquals($LemonPie, $Registry->LemonPie);
    }

    /**
     * @depends testSet
     */
    public function testLoaded()
    {
        $LemonPie = new LemonPie();

        $Registry = new ObjectRegistry();
        $Registry->set('LemonPie', $LemonPie);
        $this->assertEquals(['LemonPie'], $Registry->loaded());
    }

    /**
     * @depends testSet
     * formely check
     */
    public function testHas()
    {
        $LemonPie = new LemonPie();

        $Registry = new ObjectRegistry();
        $Registry->LemonPie = $LemonPie;
        $Registry->set('LemonPie', $LemonPie);

        $this->assertFalse($Registry->has('PumpkinPie'));
    }

    /**
     * depends testHas.
     */
    public function testLoad()
    {
        $Registry = new ObjectRegistry();
        $Registry->load(LemonPie::class);
        $this->assertTrue($Registry->has(LemonPie::class));
        $this->expectException(MissingClassException::class);
        $Registry->load('UnkownClass');
    }

    /**
     * @depends testHas
     */
    public function testUnload()
    {
        $Registry = new ObjectRegistry();

        $Registry->load(LemonPie::class);
        $this->assertTrue($Registry->unload(LemonPie::class));
        $this->assertFalse($Registry->has(LemonPie::class));
        $this->assertFalse($Registry->unload('UnkownClass'));
    }

    /**
     * depends testLoad.
     */
    public function testDisable()
    {
        $Registry = new ObjectRegistry();
        $Registry->load(LemonPie::class);
        $this->assertTrue($Registry->disable(LemonPie::class));
        $this->assertFalse($Registry->disable('UnkownObject'));
    }
    /**
     * @depends testDisable
     */
    public function testEnable()
    {
        $Registry = new ObjectRegistry();
        $Registry->load(LemonPie::class);
        $Registry->disable(LemonPie::class);
        $this->assertTrue($Registry->enable(LemonPie::class));
        $this->assertFalse($Registry->enable(LemonPie::class));
    }

    public function testCall()
    {
        $LemonPie = new LemonPie();

        $Registry = new ObjectRegistry();
        $Registry->set('LemonPie', $LemonPie);
        $Registry->enable('LemonPie');
        $this->assertNull($Registry->call('startup', [1]));
        $this->assertEquals(1, $LemonPie->called());
    }
}
