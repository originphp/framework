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
namespace Origin\Test\Job;

use Origin\Record\Record;
use BadMethodCallException;

class InvalidCheckoutForm extends Record
{
    protected function initialize()
    {
        $this->addField('name', 'password');
    }
}

class CheckoutForm extends Record
{
    protected function initialize()
    {
        $this->addField('name', 'string');
        $this->addField('email', ['type' => 'string','length' => 125]);
        $this->addField('agreeToTerms', ['type' => 'boolean','default' => true]);

        $this->validate('name', 'required');

        $this->validate('email', [
            'required',
            'email'
        ]);

        $this->beforeValidate('changeName');
        $this->afterValidate('changeNameAgain');
    }

    protected function changeName()
    {
        $this->name = strtoupper($this->name);
    }

    protected function changeNameAgain()
    {
        $this->name = strtolower($this->name);
    }
}

class RecordTest extends \PHPUnit\Framework\TestCase
{
    public function testSchema()
    {
        $form = new CheckoutForm();
        $expected = [
            'name' => [
                'type' => 'string',
                'length' => null,
                'default' => null
            ],
            'email' => [
                'type' => 'string',
                'length' => 125,
                'default' => null
            ],

            'agreeToTerms' =>
            [
                'type' => 'boolean',
                'length' => null,
                'default' => true
            ]
        ];
        $this->assertSame($expected, $form->schema());
        $this->assertSame($expected['email'], $form->schema('email'));
        $this->assertNull($form->schema('foo'));
    }

    public function testDefaultValuesAreSet()
    {
        $form = new CheckoutForm();
        $this->assertTrue($form->agreeToTerms);
    }

    public function testValidation()
    {
        $form = new CheckoutForm();
        $form->name = 'joe';
        $form->email = 'invalid-email';
        $this->assertFalse($form->validates());
        $form->email = 'demo@example.com';
        $this->assertTrue($form->validates());
    }

    public function testInvalidFieldType()
    {
        $this->expectException(BadMethodCallException::class);
        $form = new InvalidCheckoutForm();
    }

    public function testCallbacks()
    {
        $form = new CheckoutForm();
        $form->name = 'Joe';
        $this->assertFalse($form->validates());
        $this->assertEquals('JOE', $form->name);

        $form->email = 'demo@example.com';
        $this->assertTrue($form->validates());
        $this->assertEquals('joe', $form->name);
    }
}
