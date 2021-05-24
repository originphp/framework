<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Origin\Test\Model;

use Origin\Model\Record;
use BadMethodCallException;

class SomeRecord extends Record
{
    protected $schema = [
        'name' => 'string'
    ];
}

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
        $this->afterValidate('changeEmail');
    }

    protected function changeName()
    {
        $this->name = strtoupper($this->name);
    }

    protected function changeEmail()
    {
        $this->email = strtoupper($this->email);
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

    public function testNormalizeSchema()
    {
        $object = new SomeRecord();
        $expected = [
            'type' => 'string',
            'length' => null,
            'default' => null
        ];
        $this->assertEquals($expected, $object->schema('name'));
    }

    public function testMarkClean()
    {
        $form = new CheckoutForm(['name' => 'Peter Hook']);
        $this->assertFalse($form->isClean());
        $form = new CheckoutForm(['name' => 'Peter Hook'], ['markClean' => true]);
        $this->assertTrue($form->isClean());
    }

    public function testDefaultValuesAreSet()
    {
        $form = new CheckoutForm();
        $this->assertTrue($form->agreeToTerms);
    }

    public function testValidationFail()
    {
        $form = new CheckoutForm();
        $form->name = 'joe';
        $form->email = 'invalid-email';
        $this->assertFalse($form->validates());
    }

    public function testValidation()
    {
        $form = new CheckoutForm();
        $form->name = 'joe';
        $form->email = 'demo@example.com';
        $this->assertTrue($form->validates());
    }

    public function testValidationPreset()
    {
        $form = new CheckoutForm();
        $form->name = 'joe';
        $form->email = 'demo@example.com';

        $form->error('name', 'Needs to start with a capital letter');

        $this->assertFalse($form->validates());
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
        $form->email = 'demo@example.com';

        $form->validates();
        $this->assertEquals('JOE', $form->name);
        $this->assertEquals('DEMO@EXAMPLE.COM', $form->email);
    }

    public function testNew()
    {
        $data = ['name' => 'foo','email' => 'demo@example.com'];
        $form = Record::new($data);
        $this->assertInstanceOf(Record::class, $form);
        $this->assertEquals($data, $form->toArray());

        $data = ['name' => 'foo','email' => 'demo@example.com'];
        $form = Record::new($data, ['fields' => ['name']]);
        $this->assertInstanceOf(Record::class, $form);
        $this->assertEquals(['name' => 'foo'], $form->toArray());
    }

    public function testPatch()
    {
        $data = ['name' => 'foo','email' => 'demo@example.com'];
        $form = Record::new($data);
        $this->assertInstanceOf(Record::class, $form);
   
        $data = ['name' => 'bar','email' => 'jon.snow@example.com'];
        $form = Record::patch($form, $data, ['fields' => ['name']]);
        $this->assertInstanceOf(Record::class, $form);
        $this->assertEquals(['name' => 'bar','email' => 'demo@example.com'], $form->toArray());
    }

    public function testPatchDirty()
    {
        $form = Record::new([
            'name' => 'foo',
            'email' => 'demo@example.com'
        ], ['markClean' => true]);
      
        $form = Record::patch($form, ['email' => 'demo2@example.com']);
       
        $this->assertEquals(['email'], $form->modified());
    }
}
