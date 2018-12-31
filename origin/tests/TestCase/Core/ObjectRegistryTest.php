<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 *
 * @link          https://www.originphp.com
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Core;

use Origin\Core\ObjectRegistry;

class MockObjectRegistry extends ObjectRegistry
{
    public function getLoaded()
    {
        return array_keys($this->loaded);
    }
}
class LemonPie
{
    public $name = 'LemonPie';
}
class ObjectRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testSet()
    {
        $LemonPie = new LemonPie();

        $Registry = new MockObjectRegistry();
        $Registry->set('LemonPie', $LemonPie);
        $this->assertEquals(['LemonPie'], $Registry->getLoaded());
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
        $Registry->load('Origin\Test\Core\LemonPie');
        $this->assertTrue($Registry->has('Origin\Test\Core\LemonPie'));
    }

    /**
     * @depends testHas
     */
    public function testUnload()
    {
        $Registry = new ObjectRegistry();

        $Registry->load('Origin\Test\Core\LemonPie');
        $this->assertTrue($Registry->unload('Origin\Test\Core\LemonPie'));
        $this->assertFalse($Registry->has('Origin\Test\Core\LemonPie'));
    }
}
