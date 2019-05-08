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

namespace Origin\Test\Utility;

use Origin\Core\Configure;
use Origin\Utility\Security;

class SecurityTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        Configure::write('Security.salt', 'B1816172FD2BA98F3AF520EF572E3A47');
    }
    public function testHash()
    {
        $plain = 'The quick brown fox jumps over the lazy dog';
        $expected = '2fd4e1c67a2d28fced849ee1bb76e7391b93eb12';
       
        $this->assertEquals($expected, Security::hash($plain));
   
        $expected = 'd7a8fbb307d7809469ca9abcb0082e4f8d5651e46d3cdb762d02d0bf37c9e592';
        $this->assertEquals($expected, Security::hash($plain, 'sha256'));
        
        $expected = '2a70c8107928b49f2c2b64bac4aacb820aef818b';
        $this->assertEquals($expected, Security::hash($plain, 'sha1', 'OriginPHP'));

        Configure::write('Security.salt', 'OriginPHP');
        $expected = '2a70c8107928b49f2c2b64bac4aacb820aef818b';
        $this->assertEquals($expected, Security::hash($plain, 'sha1', true));
    }

    public function testEncryptDecrypt()
    {
        $plain = 'The quick brown fox jumps over the lazy dog';
        $encrypted = Security::encrypt($plain);
        $this->assertTrue($encrypted != $plain);
        $decrypted = Security::decrypt($encrypted);
        $this->assertEquals($plain, $decrypted);

        $key = '----- KEY BLOCK -----';
        $encrypted2 = Security::encrypt($plain, $key);
        $this->assertTrue($encrypted != $encrypted2);
        
        $decrypted2 = Security::decrypt($encrypted2); // test wrong key
        $this->assertFalse($decrypted2 === $plain);

        $decrypted2 = Security::decrypt($encrypted2, $key); // test wrong key
        $this->assertEquals($plain, $decrypted2);
    }
}
