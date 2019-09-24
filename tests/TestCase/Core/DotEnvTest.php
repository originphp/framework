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
use Origin\Exception\Exception;
use Origin\Exception\InvalidArgumentException;
use Origin\Utility\Security;

class MockDotEnv extends DotEnv
{
    protected $env = [];
    protected function env(string $key, $value) : void
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
    public function testLoadAndParsing()
    {
        $dotenv = new MockDotEnv();
        $dotenv->load(CONFIG .DS . '.env.test'); // TestApp
        
        $results = $dotenv->getEnv();
  
        $this->assertEquals('3a4e41e63f65b228e2927d6045c09577', md5(json_encode($results)));
    }

    public function testLoadExecption()
    {
        $dotenv = new MockDotEnv();
        $this->expectException(InvalidArgumentException::class);
        $dotenv->load();
    }

    public function testLoadReal()
    {
        $dotenv = new DotEnv();
        $tmp = TMP . DS . Security::uid();
        $value = Security::uuid();
        file_put_contents($tmp, "ENVTEST_UUID={$value}\n");

        $dotenv->load($tmp); // TestApp
        $this->assertEquals($value, env('ENVTEST_UUID'));
    }

    public function testMultiLine()
    {
        $key = <<< EOF
ENVTEST_KEY="-----BEGIN RSA PRIVATE KEY-----
...
AbDE7...
...
-----END RSA PRIVATE KEY-----"
EOF;
        $dotenv = new DotEnv();
        $tmp = TMP . DS . Security::uid();
        
        file_put_contents($tmp, $key);
        $dotenv->load($tmp); // TestApp
        $this->assertEquals('05a378a68f104bb1a076fed1c5d770ea', md5(env('ENVTEST_KEY')));
    }

    public function testMultiLineException()
    {
        $key = <<< EOF
ENVTEST_KEY="This is
a multi line that does not
end with a quotation
EOF;
        $dotenv = new DotEnv();
        $tmp = TMP . DS . Security::uid();
        
        file_put_contents($tmp, $key);
        $this->expectException(Exception::class);
        $dotenv->load($tmp); // TestApp
    }
}
