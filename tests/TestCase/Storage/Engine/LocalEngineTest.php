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
namespace Origin\Test\Storage\Engine;

use Origin\Storage\Engine\LocalEngine;
use Origin\Exception\InvalidArgumentException;

include_once 'EngineTestTrait.php'; // @todo recreate test with providers maybe

class LocalEngineTest extends \PHPUnit\Framework\TestCase
{
    use EngineTestTrait;
    public $engine = null;

    public function engine()
    {
        if ($this->engine === null) {
            $this->engine = new LocalEngine();
        }

        return $this->engine;
    }
    public function testConfig()
    {
        $this->assertEquals(APP . DS . 'storage', $this->engine()->config('root'));
    }

    public function testInvalidRoot()
    {
        $this->expectException(InvalidArgumentException::class);
        $engine = new LocalEngine([
            'root' => '/some-directory/that-does-not-exist',
        ]);
    }
}
