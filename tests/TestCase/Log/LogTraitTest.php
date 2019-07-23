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
namespace Origin\Test\Log;

use Origin\Log\Log;
use Origin\Log\LogTrait;

class Controller
{
    use LogTrait;
}

class LogTraitTest extends \PHPUnit\Framework\TestCase
{
    public function setUp() :void
    {
        Log::reset();
    }
    public function testTrait()
    {
        Log::config('default', ['engine' => 'File']);
        $controller = new Controller();
        $id = uniqid();
        $controller->log('debug', 'XXX {id} ', ['id' => $id]);
        $this->assertContains($id, file_get_contents(TMP . DS . 'development.log'));
    }

    public function tearDown() : void
    {
        $log = TMP . DS . 'development.log';
        if (file_exists($log)) {
            unlink($log);
            touch($log);
        }
    }
}
