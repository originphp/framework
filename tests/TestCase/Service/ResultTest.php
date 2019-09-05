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
namespace Origin\Test\Job;

use Origin\Service\Result;

class ResultTest extends \PHPUnit\Framework\TestCase
{
    public function testInitialize()
    {
        $result = new Result(['key' => 'value','array' => ['foo' => 'bar']]);
        $this->assertEquals('value', $result->key);
        $this->assertEquals(['foo' => 'bar'], $result->array);
    }
}
