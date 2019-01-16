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

namespace Origin\Test;

class FunctionsTest extends \PHPUnit\Framework\TestCase
{
    public function testPluginSplit()
    {
        list($plugin, $name) = pluginSplit('ContactManager.contacts');
        $this->assertEquals('ContactManager', $plugin);
        $this->assertEquals('contacts', $name);
    }

    public function testNamespaceSplit()
    {
        list($namespace, $classname) = namespaceSplit('Origin\Framework\Dispatcher');
        $this->assertEquals('Origin\Framework', $namespace);
        $this->assertEquals('Dispatcher', $classname);
    }
}
