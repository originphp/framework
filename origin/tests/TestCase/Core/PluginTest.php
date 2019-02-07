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

namespace Origin\Test\Core;

use Origin\Core\Plugin;
use Origin\Core\Exception\MissingPluginException;

class PluginTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadException()
    {
        $this->expectException(MissingPluginException::class);
        Plugin::load('PluginThatDoesNotExist');
    }

    public function testLoad()
    {
        Plugin::load('Make');
        $this->assertTrue(Plugin::loaded('Make'));
    }
}
