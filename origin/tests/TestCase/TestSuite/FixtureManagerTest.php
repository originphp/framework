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

namespace Origin\Test\TestSuite;

use Origin\TestSuite\FixtureManager;
use Origin\TestSuite\TestTrait;
use Origin\TestSuite\OriginTestCase;

class MockFixtureManager extends FixtureManager
{
    use TestTrait;
}

class FixtureManagerTest extends OriginTestCase
{
    public function testResolveFixture()
    {
        $FixtureManager = new MockFixtureManager();

        $result = $FixtureManager->callMethod('resolveFixture', ['App.Article']);
        $this->assertEquals('App\Test\Fixture\ArticleFixture', $result);

        $result = $FixtureManager->callMethod('resolveFixture', ['Framework.Contact']);
        $this->assertEquals('Origin\Test\Fixture\ContactFixture', $result);

        $result = $FixtureManager->callMethod('resolveFixture', ['PluginName.Lead']);
        $this->assertEquals('PluginName\Test\Fixture\LeadFixture', $result);
    }
}
