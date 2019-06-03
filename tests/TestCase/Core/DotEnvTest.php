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

use Origin\Core\DotEnv;

class MockDotEnv extends DotEnv
{
    protected $env = [];
    protected function env(string $key, $value)
    {
        $this->env[$key] = $value;
    }
    public function getEnv()
    {
        return $this->env;
    }
}

class DotEnvTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad()
    {
        $dotenv = new MockDotEnv(CONFIG);
        $dotenv->load();
        $results = $dotenv->getEnv();
        $this->assertEquals('f9b287655e4decf548a20ae5ea5987af', md5(json_encode($results)));
    }
}
