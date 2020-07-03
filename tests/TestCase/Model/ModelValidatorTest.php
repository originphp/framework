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

namespace Origin\Core\Test;

use Origin\Model\Model;

use Origin\Model\Entity;
use Origin\Model\ModelRegistry;
use Origin\Model\ModelValidator;
use Origin\TestSuite\OriginTestCase;
use Origin\Model\Exception\ValidatorException;

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

        return call_user_func_array([$this, $method], $args);
    }
}

class ModelValidatorTest extends OriginTestCase
{
    protected $fixtures = ['Framework.Article'];

    protected function setUp(): void
    {
        $Post = new Model(['name' => 'Post']);
        $this->Validator = new MockValidator($Post);

        // Add Non Existant Model to registry - if mock then create class above
        $this->Article = new Model([
            'name' => 'Article',
            'connection' => 'test',
        ]);
        ModelRegistry::set('Article', $this->Article);
    }

    /**
     * Rules are standarized so each field has an array of rules.
     */
    public function testPrepareRules()
    {
        $rules = [
            'field1' => 'email',
            'field2' => [
                'rule' => ['minLength', '10'],
                'message' => 'Minimum 10 characters long',
            ],
        ];
        $this->Validator->rules($rules);
        $result = $this->Validator->rules();

        $this->assertEquals('email', $result['field1']['rule-1']['rule']);
        $this->assertArrayHasKey('message', $result['field2']['rule-1']);
    }

    public function testValidationRuleString()
    {
        $Validator = $this->Validator;
        $validate = [
            'value' => [
                'rule' => 'numeric',
                'message' => 'This value must be an integer',
            ],
        ];

        $Validator->rules($validate);
        $data = new Entity(['value' => 'some string']);
        $this->assertFalse($Validator->validates($data));

        $data = new Entity(['value' => 256]);
        $this->assertTrue($Validator->validates($data));
    }

    public function testValidationFileUploadIsBlank()
    {
        $Validator = $this->Validator;

        $Validator->rules([
            'file' => ['rule' => 'upload'],
        ]);

        $entity = new Entity(['file' => ['tmp_name' => null, 'error' => UPLOAD_ERR_NO_FILE]]);

        $this->assertFalse($Validator->validates($entity));
    }

    public function testValidatesNotEmpty()
    {
        $Validator = $this->Validator;

        $Validator->rules([
            'name' => ['rule' => 'notEmpty'],
        ]);

        $this->assertTrue($Validator->validates(new Entity(['name' => 'some string'])));
        $this->assertFalse($Validator->validates(new Entity(['name' => null])));
    }

    public function testValidatesNotBlank()
    {
        $Validator = $this->Validator;

        $Validator->rules([
            'name' => ['rule' => 'notBlank'],
        ]);

        $this->assertTrue($Validator->validates(new Entity(['name' => 'some string'])));
        $this->assertFalse($Validator->validates(new Entity(['name' => null])));
    }

    /**
     * @depends testValidatesNotEmpty
     */
    public function testValidatesOn()
    {
        $Validator = $this->Validator;

        $Validator->rules([
            'name' => ['rule' => 'notEmpty'],
        ]);

        $create = true;
        $update = false;

        $this->assertTrue($Validator->validates(new Entity(['name' => 'some string']), $create));
        $this->assertFalse($Validator->validates(new Entity(['name' => null]), $create));
        $this->assertTrue($Validator->validates(new Entity(['name' => 'some string']), $update));
        $this->assertFalse($Validator->validates(new Entity(['name' => null]), $update));

        $Validator->rules([
            'name' => ['rule' => 'notEmpty', 'on' => 'create'],
        ]);

        $this->assertTrue($Validator->validates(new Entity(['name' => 'some string']), $create));
        $this->assertFalse($Validator->validates(new Entity(['name' => null]), $create));
        $this->assertTrue($Validator->validates(new Entity(['name' => 'some string']), $update));
        $this->assertTrue($Validator->validates(new Entity(['name' => null]), $update));

        $Validator->rules([
            'name' => ['rule' => 'notEmpty', 'on' => 'update'],
        ]);

        $this->assertTrue($Validator->validates(new Entity(['name' => 'some string']), $create));
        $this->assertTrue($Validator->validates(new Entity(['name' => null]), $create));
        $this->assertTrue($Validator->validates(new Entity(['name' => 'some string']), $update));
        $this->assertFalse($Validator->validates(new Entity(['name' => null]), $update));
    }

    public function testValidateRequired()
    {
        $this->deprecated(function () {
            $Validator = $this->Validator;
            $Validator->rules([
                'name' => 'alphaNumeric',
                'email' => ['rule' => 'email', 'required' => false],
            ]);
    
            $entity = new Entity(['name' => 'data']);
            $this->assertTrue($Validator->validates($entity));
    
            $Validator->rules([
                'name' => 'alphaNumeric',
                'email' => ['rule' => 'email', 'required' => true],
            ]);
            $this->assertFalse($Validator->validates($entity));
    
            $entity = new Entity(['name' => 'data', 'email' => 'js@example.com']);
            $this->assertTrue($Validator->validates($entity));
        });
    }

    /**
     * FOR BC
     *
     * @return void
     */
    public function testValidatesRequiredKey()
    {
        $this->deprecated(function () {
            $Validator = $this->Validator;

            $Validator->rules([
                'name' => ['rule' => 'alphaNumeric', 'required' => true],
            ]);

            $create = true;
            $update = false;

            $this->assertTrue($Validator->validates(new Entity(['name' => 'data']), $create));
            $this->assertFalse($Validator->validates(new Entity(['foo' => 'bar']), $create));
            $this->assertTrue($Validator->validates(new Entity(['name' => 'data']), $update));
            $this->assertFalse($Validator->validates(new Entity(['foo' => 'bar']), $update));

            $Validator->rules([
                'name' => ['rule' => 'alphaNumeric', 'on' => 'create', 'required' => true],
            ]);

            $this->assertTrue($Validator->validates(new Entity(['name' => 'data']), $create));
            $this->assertFalse($Validator->validates(new Entity(['foo' => 'bar']), $create));
            $this->assertTrue($Validator->validates(new Entity(['name' => 'data']), $update));
            $this->assertTrue($Validator->validates(new Entity(['foo' => 'bar']), $update));

            $Validator->rules([
                'name' => ['rule' => 'alphaNumeric', 'on' => 'update', 'required' => true],
            ]);

            $this->assertTrue($Validator->validates(new Entity(['name' => 'data']), $create));
            $this->assertTrue($Validator->validates(new Entity(['foo' => 'bar']), $create));
            $this->assertTrue($Validator->validates(new Entity(['name' => 'data']), $update));
            $this->assertFalse($Validator->validates(new Entity(['foo' => 'bar']), $update));
        });
    }

    public function testValidationRuleArray()
    {
        $Validator = $this->Validator;
        $validationRules = [
            'framework' => [
                'rule' => ['equalTo', 'origin'],
                'message' => 'This value must be origin',
            ],
        ];

        $Validator->rules($validationRules);
        $data = new Entity(['framework' => 'something else']);
        $this->assertFalse($Validator->validates($data));

        $data = new Entity(['framework' => 'origin']);
        $this->assertTrue($Validator->validates($data));

        $validationRules = [
            'framework' => '/^[a-zA-Z ]+$/',
        ];
        $Validator->rules($validationRules);
        $data = new Entity(['framework' => 'origin']);
        $this->assertTrue($Validator->validates($data));
    }

    public function testUnkownValidationRule()
    {
        $this->expectException(ValidatorException::class);
        $rules = [

            'name' => 'php',
        ];
        $this->Validator->rules($rules);
        $data = new Entity(['name' => 'abc']);
        $this->Validator->validates($data);
    }

    public function testValidateCustomRule()
    {
        $validator = new MockValidator(new Widget());

        $this->assertTrue($validator->validate(1, 'isOne'));
        $this->assertFalse($validator->validate(2, 'isOne'));
    }

    /**
     * Test the new Validation class is being called
     */
    public function testValidationClassCalling()
    {
        $this->Article->validate('title', 'alpha');
        $article = $this->Article->new(['title' => 'foo']);
        $this->assertTrue($this->Article->validates($article));
        $article->title = 'foo123';
        $this->assertFalse($this->Article->validates($article));
    }

    /**
     * Test the new Validation class is being called
     */
    public function testValidationConfirm()
    {
        $this->Article->validate('password', 'confirm');
        $article = $this->Article->new(['password' => 'foo', 'password_confirm' => 'foo']);
        $this->assertTrue($this->Article->validates($article));
        $article->password_confirm = 'foo123';
        $this->assertFalse($this->Article->validates($article));
        unset($article->password_confirm);
        $this->assertFalse($this->Article->validates($article));
    }

    /**
     * This is a bit of mess, so we have to test through model.
     */
    public function testIsUnique()
    {
        $this->Article->validate('id', ['rule' => 'isUnique']);
        $article = $this->Article->new();
        $article->id = 1;
        $this->assertTrue($this->Article->validates($article));

        $article = $this->Article->new();
        $article->id = 1000;
        $this->assertFalse($this->Article->validates($article));

        $this->Article->validate('id', ['rule' => ['isUnique', ['id', 'title']]]);
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
        $this->deprecated(function () {
            $this->Article->validate('title', 'email');

            $article = $this->Article->new(['title' => '']);
            $this->assertFalse($this->Article->validates($article));
    
            $this->Article->validate('title', ['rule' => 'email', 'required' => true]);
            $article = $this->Article->new(['foo' => 'bar']);
            $this->assertFalse($this->Article->validates($article));
    
            # Run validation again despite no data being modified, still should show errors
            $this->assertFalse($this->Article->validates($article));
    
            $this->Article->validate('title', ['rule' => 'email', 'allowBlank' => true]);
            $article = $this->Article->new(['title' => '']);
            $this->assertTrue($this->Article->validates($article));
    
            $this->Article->validate('body', 'alphanumeric');
            $article = $this->Article->new(['title' => '', 'body' => ['bad data']]);
            $this->assertFalse($this->Article->validates($article));
        });
    }

    /**
     * Run the validation, all will faill but want to stop at numeric
     */
    public function testValidatesMultipleStop()
    {
        $this->Article->validate('title', [
            ['rule' => 'notEmpty', 'stopOnFail' => false],
            ['rule' => 'alphaNumeric', 'stopOnFail' => false],
            ['rule' => 'alpha', 'stopOnFail' => false],
            ['rule' => 'numeric', 'stopOnFail' => false, 'message' => 'numeriic'],
            ['rule' => 'alpha', 'stopOnFail' => false],
        ]);
        $article = $this->Article->new(['title' => 'abcd', 'body' => ['bad data']]);
        $this->assertFalse($this->Article->validates($article));
        $this->assertEquals(1, count($article->errors('title')));
        $this->assertEquals('numeriic', $article->errors('title')[0]);
    }

    public function testOptionalRule()
    {
        $this->Article->validate('url', [
            'optional',
            'url'
        ]);

        $articleValid1 = $this->Article->new(['title' => 'foo', 'url' => null]);
        $articleValid2 = $this->Article->new(['title' => 'foo', 'url' => 'https://www.originphp.com/docs/getting-started/']);
       
        $articleInvalid = $this->Article->new(['title' => 'foo', 'url' => 'not a url']);
       
        $this->assertTrue($this->Article->validates($articleValid1));
        $this->assertTrue($this->Article->validates($articleValid2));
        $this->assertFalse($this->Article->validates($articleInvalid));
    }

    /**
    * This was added after bug found which the presentKey test was
    * not checking, which was subsequent rules. Left as bug fix as a
    * reminder
    */
    public function testRequiredRule()
    {
        $this->Article->validate('url', [
            'required',
            'url'
        ]);

        $articleValid = $this->Article->new(['title' => 'foo', 'url' => 'https://www.originphp.com/docs/getting-started/']);
        $articleInvalid1 = $this->Article->new(['title' => 'foo']);
        $articleInvalid2 = $this->Article->new(['title' => 'foo', 'url' => 'not a url']);
       
        $this->assertTrue($this->Article->validates($articleValid));
        $this->assertFalse($this->Article->validates($articleInvalid1));
        $this->assertFalse($this->Article->validates($articleInvalid2));
    }

    public function testNotEmpty()
    {
        $this->Article->validate('title', ['notEmpty', 'alphaNumeric']);
        $article = $this->Article->new(['title' => 'foo', 'body' => 'not important']);
        $this->assertTrue($this->Article->validates($article));
        $article->title = '';
        $this->assertFalse($this->Article->validates($article));
        $article->title = null;
    }

    public function testNotBlank()
    {
        $this->deprecated(function () {
            $this->Article->validate('title', ['notBlank', 'alphaNumeric']);
            $article = $this->Article->new(['title' => 'foo', 'body' => 'not important']);
            $this->assertTrue($this->Article->validates($article));
            $article->title = '';
            $this->assertFalse($this->Article->validates($article));
            $article->title = null;

            $this->Article->validate('title', [
                'rule' => 'notBlank',
                'required' => true,
                'on' => 'create']);
        
            $article->author_id = 1001;
            $article->title = null;
            $article->body = 'Title is blank so it should fail';
            $this->assertFalse($this->Article->validates($article));
        });
    }

    public function testPresentRule()
    {
        $this->Article->validate('url', [
            'present',
            'url'
        ]);

        $articleValid = $this->Article->new(['title' => 'foo', 'url' => 'https://www.originphp.com/docs/getting-started/']);
        $articleInvalid1 = $this->Article->new(['title' => 'foo']);
        $articleInvalid2 = $this->Article->new(['title' => 'foo', 'url' => 'not a url']);
       
        $this->assertTrue($this->Article->validates($articleValid));
        $this->assertFalse($this->Article->validates($articleInvalid1));
        $this->assertFalse($this->Article->validates($articleInvalid2));
    }

    public function testPresentKey()
    {
        $articleValid = $this->Article->new(['title' => 'foo', 'body' => 'not important']);
        $articleInvalid = $this->Article->new(['body' => 'not important']);

        $this->Article->validate('title', [
            'alphaNumeric' => [
                'rule' => 'alphaNumeric',
                'present' => true
            ],
        ]);

        $this->assertTrue($this->Article->validates($articleValid));
        $this->assertFalse($this->Article->validates($articleInvalid));

        $articleValid = $this->Article->new(['title' => 'abc', 'body' => 'test with empty']);
        $this->assertTrue($this->Article->validates($articleValid));
    }

    public function testStopOnFail()
    {
        # Check Present termination
        $this->Article->validate('title', [
            'alphaNumeric' => [
                'rule' => 'alphaNumeric',
                'present' => true,
                'stopOnFail' => true
            ],
        ]);
        $articleValid = $this->Article->new(['title' => 'foo', 'body' => 'not important']);
        $articleInvalid = $this->Article->new(['body' => 'not important']);

        $this->assertTrue($this->Article->validates($articleValid));
        $this->assertFalse($this->Article->validates($articleInvalid));

        # Check notEmpty
        $this->Article->validate('title', [
            'notEmpty' => ['rule' => 'notEmpty','stopOnFail' => true],
            'alphaNumeric'
        ]);
        $this->assertFalse($this->Article->validates($articleInvalid));

        # Check Required (this is same as notEmpty but for sanity)
        $this->Article->validate('title', [
            'required',
            'alphaNumeric'
        ]);
        $this->assertFalse($this->Article->validates($articleInvalid));

        # Check Required (this is same as notEmpty but for sanity)
        $this->Article->validate('title', [
            'notEmpty' => ['rule' => 'alphaNumeric','stopOnFail' => true],
            'numeric'
        ]);
        $this->assertFalse($this->Article->validates($articleInvalid));
    }

    public function testNotEmptyFileUpload()
    {
        $this->Article->validate('file', 'notEmpty');
     
        $entity = $this->Article->new([
            'file' => [
                'name' => '', // exactly like from request
                'type' => '',
                'tmp_name' => '',
                'error' => 4,
                'size' => 0
            ]
        ]);

        $this->assertFalse($this->Article->validates($entity));

        $entity = $this->Article->new([
            'file' => [
                'name' => 'image.png',
                'type' => 'image/png',
                'tmp_name' => '/tmp/phpq2Ev5I',
                'error' => 0,
                'size' => 137306
            ]
        ]);

        $this->assertTrue($this->Article->validates($entity));
    }
}
