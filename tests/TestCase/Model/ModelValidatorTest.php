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

namespace Origin\Core\Test;

use Origin\TestSuite\OriginTestCase;

use Origin\Model\ModelValidator;
use Origin\Model\Model;
use Origin\Model\Entity;
use Origin\Model\Exception\ValidatorException;
use Origin\Model\ModelRegistry;

class Widget extends Model
{
    public function isOne(int $value)
    {
        return $value === 1;
    }
}

class MockValidator extends ModelValidator
{
    public function invoke(string $method, array $args = [])
    {
        if (empty($args)) {
            return $this->{$method}();
        }

        return call_user_func_array(array($this, $method), $args);
    }
}

class ModelValidatorTest extends OriginTestCase
{
    public $fixtures = ['Framework.Article'];

    protected function setUp(): void
    {
        $Post = new Model(array('name' => 'Post'));
        $this->Validator = new MockValidator($Post);

        // Add Non Existant Model to registry - if mock then create class above
        $this->Article = new Model([
            'name'=>'Article',
            'datasource'=>'test'
            ]);
        ModelRegistry::set('Article', $this->Article);
    }

    /**
     * Rules are standarized so each field has an array of rules.
     */
    public function testPrepareRules()
    {
        $rules = array(
        'field1' => 'email',
        'field2' => array(
            'rule' => array('minLength', '10'),
            'message' => 'Minimum 10 characters long',
        ),
      );
        $this->Validator->rules($rules);
        $result =  $this->Validator->rules();

        $this->assertEquals('email', $result['field1']['rule1']['rule']);
        $this->assertArrayHasKey('message', $result['field2']['rule1']);
    }

    public function testValidationRuleString()
    {
        $Validator = $this->Validator;
        $validate = array(
        'value' => array(
            'rule' => 'numeric',
            'message' => 'This value must be an integer',
        ),
      );

        $Validator->rules($validate);
        $data = new Entity(array('value' => 'some string'));
        $this->assertFalse($Validator->validates($data));

        $data = new Entity(array('value' => 256));
        $this->assertTrue($Validator->validates($data));
    }

    public function testValidatesRequired()
    {
        $Validator = $this->Validator;
 
        $Validator->rules([
            'name' => ['rule' => 'notBlank']
            ]);
            
        $create = true;
        $update = false;

        $this->assertTrue($Validator->validates(new Entity(['name' => 'some string']), $create));
        $this->assertFalse($Validator->validates(new Entity(['name' => null]), $create));
        $this->assertTrue($Validator->validates(new Entity(['name' => 'some string']), $update));
        $this->assertFalse($Validator->validates(new Entity(['name' => null]), $update));
       
        $Validator->rules([
            'name' => ['rule' => 'notBlank','on' => 'create']
            ]);
    
        $this->assertTrue($Validator->validates(new Entity(['name' => 'some string']), $create));
        $this->assertFalse($Validator->validates(new Entity(['name' => null]), $create));
        $this->assertTrue($Validator->validates(new Entity(['name' => 'some string']), $update));
        $this->assertTrue($Validator->validates(new Entity(['name' => null]), $update));

        $Validator->rules([
            'name' => ['rule' => 'notBlank','on' => 'update']
            ]);
    
        $this->assertTrue($Validator->validates(new Entity(['name' => 'some string']), $create));
        $this->assertTrue($Validator->validates(new Entity(['name' => null]), $create));
        $this->assertTrue($Validator->validates(new Entity(['name' => 'some string']), $update));
        $this->assertFalse($Validator->validates(new Entity(['name' => null]), $update));
    }

    public function testValidateRequired()
    {
        $Validator = $this->Validator;
        $Validator->rules([
            'name' => 'alphaNumeric',
            'email' => ['rule'=>'email','required'=>false]
            ]);

        $entity = new Entity(['name' => 'data']);
        $this->assertTrue($Validator->validates($entity));

        $Validator->rules([
            'name' => 'alphaNumeric',
            'email' => ['rule'=>'email','required'=>true]
            ]);
        $this->assertFalse($Validator->validates($entity));

        
        $entity = new Entity(['name' => 'data','email'=>'js@example.com']);
        $this->assertTrue($Validator->validates($entity));
    }

    public function testValidatesRequiredKey()
    {
        $Validator = $this->Validator;
 
        $Validator->rules([
            'name' => ['rule' => 'alphaNumeric','required'=>true]
            ]);
            
        $create = true;
        $update = false;

        $this->assertTrue($Validator->validates(new Entity(['name' => 'data']), $create));
        $this->assertFalse($Validator->validates(new Entity(['name' => null]), $create));
        $this->assertTrue($Validator->validates(new Entity(['name' => 'data']), $update));
        $this->assertFalse($Validator->validates(new Entity(['name' => null]), $update));
       
        $Validator->rules([
            'name' => ['rule' => 'alphaNumeric','on' => 'create','required'=>true]
            ]);
    
        $this->assertTrue($Validator->validates(new Entity(['name' => 'data']), $create));
        $this->assertFalse($Validator->validates(new Entity(['name' => null]), $create));
        $this->assertTrue($Validator->validates(new Entity(['name' => 'data']), $update));
        $this->assertTrue($Validator->validates(new Entity(['name' => null]), $update));

        $Validator->rules([
            'name' => ['rule' => 'alphaNumeric','on' => 'update','required'=>true]
            ]);
    
        $this->assertTrue($Validator->validates(new Entity(['name' => 'data']), $create));
        $this->assertTrue($Validator->validates(new Entity(['name' => null]), $create));
        $this->assertTrue($Validator->validates(new Entity(['name' => 'data']), $update));
        $this->assertFalse($Validator->validates(new Entity(['name' => null]), $update));
    }

    public function testValidationRuleArray()
    {
        $Validator = $this->Validator;
        $validationRules = array(
            'framework' => array(
                'rule' => array('equalTo', 'origin'),
                'message' => 'This value must be origin',
            ),
        );

        $Validator->rules($validationRules);
        $data = new Entity(array('framework' => 'something else'));
        $this->assertFalse($Validator->validates($data));

        $data = new Entity(array('framework' => 'origin'));
        $this->assertTrue($Validator->validates($data));

        $validationRules = array(
            'framework' => '/^[a-zA-Z ]+$/'
        );
        $Validator->rules($validationRules);
        $data = new Entity(array('framework' => 'origin'));
        $this->assertTrue($Validator->validates($data));
    }

    public function testUnkownValidationRule()
    {
        $this->expectException(ValidatorException::class);
        $rules = array(
          
            'name' => 'php'
        );
        $this->Validator->rules($rules);
        $data = new Entity(array('name' => 'abc'));
        $this->Validator->validates($data);
    }

    public function testAlphaNumeric()
    {
        $this->assertTrue($this->Validator->alphaNumeric('abc123'));
        $this->assertFalse($this->Validator->alphaNumeric('a-123'));
    }

    public function testBoolean()
    {
        $Validator = $this->Validator;
        $this->assertTrue($Validator->boolean(true));
        $this->assertTrue($Validator->boolean(false));
        $this->assertFalse($Validator->boolean(1));
    }

    public function testCustom()
    {
        $Validator = $this->Validator;
        $this->assertTrue($Validator->custom('abc', '/^[a-zA-Z ]+$/'));
        $this->assertFalse($Validator->custom('abc1234', '/^[a-zA-Z ]+$/'));
    }

    public function testDate()
    {
        $Validator = $this->Validator;
        $this->assertTrue($Validator->date('2017-01-01'));
        $this->assertFalse($Validator->date('2017-01-32'));

        $this->assertTrue($Validator->date('01-01-2017', 'd-m-Y'));
        $this->assertFalse($Validator->date('01-14-2017', 'd-m-Y'));

        $this->assertTrue($Validator->date('01/01/2017', 'd/m/Y'));
        $this->assertFalse($Validator->date('01/14/2017', 'd/m/Y'));
    }

    public function testDatetime()
    {
        $Validator = $this->Validator;
        $this->assertTrue($Validator->datetime('2017-01-01 17:32:00'));
        $this->assertFalse($Validator->datetime('2017-01-01 28:32:00'));
    }

    public function testDecimal()
    {
        $Validator = $this->Validator;
        $this->assertTrue($Validator->decimal(256.0));
        $this->assertTrue($Validator->decimal('512.00'));
        $this->assertTrue($Validator->decimal(1024.256));
        $this->assertTrue($Validator->decimal('2048.512'));
        $this->assertFalse($Validator->decimal(32));
        $this->assertFalse($Validator->decimal(64));
    }

    public function testEmail()
    {
        $Validator = $this->Validator;
        $this->assertTrue($Validator->email('john.smith123@example.com'));
        $this->assertFalse($Validator->email('john.smith1234 @example.com'));
        $this->assertTrue($Validator->email('bjørn@hammeröath.com'));
        $this->assertTrue($Validator->email('bjørn@ragnarrloþbrók.com'));

        $this->assertTrue($Validator->email('root@localhost'));
        $this->assertFalse($Validator->email('john.smith[at]example.com'));
    }

    public function testEqualTo()
    {
        $Validator = $this->Validator;
        $this->assertTrue($Validator->equalTo(5, 5));
        $this->assertFalse($Validator->equalTo(10, 5));
    }

    public function testExtension()
    {
        $Validator = $this->Validator;
        $this->assertTrue($Validator->extension('bootstrap.css', 'css'));
        $this->assertTrue($Validator->extension('bootstrap.css', ['js', 'css']));
        $this->assertTrue($Validator->extension('Logo.JPG', ['gif', 'png', 'jpg']));
        $this->assertFalse($Validator->extension('bootstrap.js', 'css'));

        $post = ['name'=>'bootstrap.css'];
        $this->assertTrue($Validator->extension($post, 'css'));
    }

    public function testInList()
    {
        $Validator = $this->Validator;
        $this->assertTrue($Validator->inList('new', ['draft', 'new', 'published']));
        $this->assertFalse($Validator->inList('dropped', ['draft', 'new', 'published']));
    }

    public function testIp()
    {
        $Validator = $this->Validator;
        $this->assertTrue($Validator->ip('192.168.1.37'));
    }

    public function testValidateCustomRule()
    {
        $validator = new MockValidator(new Widget());
     
        $this->assertTrue($validator->validate(1, 'isOne'));
        $this->assertFalse($validator->validate(2, 'isOne'));
    }

    public function testMaxLength()
    {
        $Validator = $this->Validator;
        $this->assertTrue($Validator->maxLength('string', 10));
        $this->assertFalse($Validator->maxLength('string', 3));
    }

    public function testMinLength()
    {
        $Validator = $this->Validator;
        $this->assertTrue($Validator->minLength('password', 8));
        $this->assertFalse($Validator->minLength('nada', 8));
    }

    public function testNotBlank()
    {
        $Validator = $this->Validator;
        $this->assertTrue($Validator->notBlank('foo'));
        $this->assertFalse($Validator->notBlank(''));
        $this->assertFalse($Validator->notBlank(null));
        $this->assertTrue($Validator->notBlank(0));
        $this->assertTrue($Validator->notBlank('0'));
        $this->assertFalse($Validator->notBlank(' '));
        $this->assertFalse($Validator->notBlank('   '));
        $this->assertTrue($Validator->notBlank(' o'));
        $this->assertTrue($Validator->notBlank('o '));
    }

    public function testNotEmpty()
    {
        $Validator = $this->Validator;
        $this->assertTrue($Validator->notEmpty('foo'));
        $this->assertFalse($Validator->notEmpty(''));
        $this->assertFalse($Validator->notEmpty(null));
        $this->assertTrue($Validator->notEmpty(0));
        $this->assertTrue($Validator->notEmpty('0'));
    }

    public function testNumeric()
    {
        $Validator = $this->Validator;
        $this->assertTrue($Validator->numeric(1));
        $this->assertTrue($Validator->numeric('1'));
        $this->assertFalse($Validator->numeric('one'));
        $this->assertTrue($Validator->numeric(1.2));
    }

    public function testFloat()
    {
        $Validator = $this->Validator;
        $this->assertTrue($Validator->float(123.45));
        $this->assertTrue($Validator->float('123.45'));
        $this->assertFalse($Validator->float(123456));
        $this->assertFalse($Validator->float('one'));
        $this->assertFalse($Validator->float('12345'));
    }

    public function testInteger()
    {
        $Validator = $this->Validator;
        $this->assertTrue($Validator->integer(1));
        $this->assertTrue($Validator->integer('1'));
        $this->assertFalse($Validator->integer('one'));
        $this->assertFalse($Validator->integer(1.2));
        $this->assertFalse($Validator->integer('12-000'));
        $this->assertFalse($Validator->integer('1234.56'));
        $this->assertFalse($Validator->integer(10.00));
        $this->assertFalse($Validator->integer('10.00'));
        $this->assertFalse($Validator->integer(-10.00));
        $this->assertFalse($Validator->integer('-10.00'));
    }

    public function testRange()
    {
        $Validator = $this->Validator;
        $this->assertFalse($Validator->range('xxx', 5, 10));
        $this->assertTrue($Validator->range(5, 5, 10));
        $this->assertTrue($Validator->range(10, 5, 10));
        $this->assertFalse($Validator->range(1, 5, 10));
        $this->assertFalse($Validator->range(11, 5, 10));
    }

    public function testTime()
    {
        $Validator = $this->Validator;
        $this->assertTrue($Validator->time('10:15', 'H:i'));
        $this->assertFalse($Validator->time('10.15', 'H:i'));
    }

    public function testUrl()
    {
        $Validator = $this->Validator;
        $this->assertTrue($Validator->url('http://www.google.com', true));
        $this->assertTrue($Validator->url('https://www.google.com', true));
        $this->assertFalse($Validator->url('www.google.com', true));

        $this->assertFalse($Validator->url('http://www.google.com', false));
        $this->assertFalse($Validator->url('https://www.google.com', false));
        $this->assertTrue($Validator->url('www.google.com', false));

        $this->assertFalse($Validator->url('ftp://www.google.com', false));
        $this->assertFalse($Validator->url('origin://www.google.com', false));
    }

    public function testMimeType()
    {
        $Validator = $this->Validator;
        $post = ['tmp_name'=> ORIGIN . DS .'phpunit.xml.dist'];
        $this->assertTrue($Validator->mimeType($post, ['text/xml']));
        $this->assertFalse($Validator->mimeType($post, ['text/plain']));

        $this->assertTrue($Validator->mimeType(ORIGIN . DS .'phpunit.xml.dist', ['text/xml']));
    }

    public function testFileUpload()
    {
        $Validator = $this->Validator;
        $post = ['tmp_name'=>null,'error'=>UPLOAD_ERR_NO_FILE];
        $this->assertFalse($Validator->upload($post));
        $this->assertTrue($Validator->upload($post, true));

        $post = ['tmp_name'=>null,'error'=>UPLOAD_ERR_OK];
        $this->assertTrue($Validator->upload($post));
    }

    /**
     * This is a bit of mess, so we have to test through model.
     */
    public function testIsUnique()
    {
        $this->Article->validate('id', ['rule'=> 'isUnique']);
        $article = $this->Article->new();
        $article->id = 1;
        $this->assertTrue($this->Article->validates($article));

        $article = $this->Article->new();
        $article->id = 1000;
        $this->assertFalse($this->Article->validates($article));
 
        $this->Article->validate('id', ['rule'=> ['isUnique',['id','title']]]);
        $article = $this->Article->new();
        $article->id = 1000;
        $article->title = 'Article #1';
        $this->assertFalse($this->Article->validates($article));
    }

    /**
     * Check multiple rule including blanks w
     *
     * @return void
     */
    public function testValidates()
    {
        $this->Article->validate('title', 'email');
    
        $article = $this->Article->new(['title'=>'']);
        $this->assertFalse($this->Article->validates($article));

        $this->Article->validate('title', ['rule'=>'email','required'=>true]);
        $article = $this->Article->new(['title'=>'']);
        $this->Article->validates($article);

        $this->assertFalse($this->Article->validates($article));

        $this->Article->validate('title', ['rule'=>'email','allowBlank'=>true]);
        $article = $this->Article->new(['title'=>'']);
        $this->assertTrue($this->Article->validates($article));
        
        $this->Article->validate('body', 'alphanumeric');
        $article = $this->Article->new(['title'=>'','body'=>['bad data']]);
        $this->assertFalse($this->Article->validates($article));
    }
}
