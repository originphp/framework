<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\I18n;

class FunctionsTest extends \PHPUnit\Framework\TestCase
{
    public function testTranslate()
    {
        $null = null;
        $this->assertNull(__($null));
        $expected = 'Nothing';
        $translate = __($expected); // no translation return as is
        $this->assertEquals('Nothing', $translate);

        $translated = __('Your password is {password}!', ['password' => 'secret']);
        $this->assertEquals('Your password is secret!', $translated);
        
        $translated = __('Your username is {email} and your password is {password}.', [
            'email' => 'jimbo@example.com',
            'password' => 'secret',
        ]);
        $this->assertEquals('Your username is jimbo@example.com and your password is secret.', $translated);
        $translate = 'You have no apples|You have one apple|You have {count} apples';
        $this->assertEquals('You have no apples', __($translate, ['count' => 0]));
        $this->assertEquals('You have one apple', __($translate, ['count' => 1]));
        $this->assertEquals('You have 2 apples', __($translate, ['count' => 2]));
    }
}
