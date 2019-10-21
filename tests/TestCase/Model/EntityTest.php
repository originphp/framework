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

namespace Origin\Test\Model;

use Origin\Model\Entity;

class User extends Entity
{
    protected function getFullName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    protected function setFirstName($value)
    {
        return ucfirst(strtolower($value));
    }
}

class EntityTest extends \PHPUnit\Framework\TestCase
{
    public function testSet()
    {
        $entity = new Entity();
        $entity->id = 1000;
        $this->assertTrue(isset($entity->id));
        $this->assertEquals(1000, $entity->id);
        $entity->set('foo', 'bar');
        $this->assertEquals('bar', $entity->foo);

        $entity['bar'] = 'foo';
        $this->assertEquals('foo', $entity['bar']);
    }

    /**
     * @depends testSet
     */
    public function testGet()
    {
        $entity = new Entity();
        $entity->id = 1001;
        $entity->empty = null;

        $this->assertEquals(1001, $entity->id);
        $this->assertEquals(null, $entity->empty);
        $this->assertEquals(null, $entity->nonExistant);

        $this->assertEquals(1001, $entity->get('id'));
        $this->assertEquals(null, $entity->get('empty'));
        $this->assertEquals(null, $entity->get('nonExistant'));

        $this->assertEquals(1001, $entity['id']);
        $this->assertEquals(null, $entity['foo']);
    }

    /**
     * @depends testSet
     */
    public function testIsset()
    {
        $entity = new Entity();
        $entity->id = 1002;
        $this->assertTrue(isset($entity->id));
        $this->assertFalse(isset($entity->name));
        $this->assertTrue($entity->has('id'));
        $this->assertFalse($entity->has('name'));
        $this->assertTrue($entity['id']);
        $this->assertFalse($entity['name']);
    }

    /**
     * @depends testSet
     */
    public function testUnset()
    {
        $entity = new Entity();
        $entity->id = 1003;
        unset($entity->id);
        $this->assertTrue(! isset($entity->id));
        $entity->foo = 'bar';
        $this->assertTrue(isset($entity->foo));
        $entity->unset('foo');
        $this->assertTrue(! isset($entity->foo));

        $entity['foo'] = 'bar';
        unset($entity['foo']);
        $this->assertTrue(! isset($entity['foo']));
    }

    /**
     * @depends testSet
     * Empty runs isset first, then get
     */
    public function testEmptyFunc()
    {
        $entity = new Entity();
        $entity->id = null;
        $this->assertTrue(empty($entity->id));
        $this->assertTrue(empty($entity->name));
    }

    public function testCreate()
    {
        $data = [
            'id' => 1004,
            'name' => 'EntityName',
        ];
        $entity = new Entity($data);
        $this->assertEquals(1004, $entity->id);
        $this->assertEquals('EntityName', $entity->name);
    }

    /**
     * @depends testCreate
     */
    public function testToArray()
    {
        $article = new Entity();
        $article->id = 256;
        $author = new Entity();
        $author->name = 'Tony';
        $comment = new Entity();
        $comment->description = 'a comment';

        $article->author = $author;
        $article->comments = [
            $comment,
        ];
        $expected = [
            'id' => 256,
            'author' => ['name' => 'Tony'],
            'comments' => [
                ['description' => 'a comment'],
            ],
        ];

        $this->assertEquals($expected, $article->toArray());
    }

    public function testhas()
    {
        $data = [
            'title' => 'Article Title',
            'author_id' => null,
        ];

        $entity = new Entity($data);

        $this->assertTrue($entity->has('title'));
        $this->assertFalse($entity->has('author_id'));
        $this->assertFalse($entity->has('undefined'));
    }

    public function testInvalidate()
    {
        $data = [
            'title' => 'Article Title',
            'author_id' => null,
        ];

        $entity = new Entity($data);
        $entity->invalidate('title', 'invalid title');
     
        $this->assertEquals(['invalid title'], $entity->errors('title'));
    }

    public function testToString()
    {
        $entity = new Entity(['name' => '1234']);
        $expected = "{\n    \"name\": \"1234\"\n}";
        $this->assertEquals($expected, (string) $entity);
    }

    public function testProperties()
    {
        $data = ['foo' => 'bar','a' => 'b'];
        $entity = new Entity($data);
        $this->assertEquals(['foo','a'], $entity->properties());
    }

    public function testPropertyExists()
    {
        $entity = new Entity(['name' => 'test']);
        $this->assertTrue($entity->propertyExists('name'));
        $entity = new Entity(['name' => null]);
        $this->assertTrue($entity->propertyExists('name'));
        $this->assertFalse($entity->propertyExists('foo'));
    }

    public function testDebugInfo()
    {
        $data = ['name' => 'test'];
        $entity = new Entity($data);
        $this->assertEquals($data, $entity->__debugInfo());
    }

    public function testErrors()
    {
        $entity = new Entity(['name' => 'test']);
        $entity->errors('name', 'Can\'t be called test');
        $this->assertEquals(['Can\'t be called test'], $entity->errors('name'));
    }

    public function testModified()
    {
        $entity = new Entity(['name' => 'test'], ['markClean' => true]);
        $this->assertEquals([], $entity->modified());
        $this->assertFalse($entity->modified('name'));

        $entity->name = 'new name';
        $this->assertEquals(['name'], $entity->modified());
        $entity->foo = 'bar';
        $this->assertEquals(['name','foo'], $entity->modified());
        
        $this->assertTrue($entity->modified('name'));
        $this->assertTrue($entity->modified('foo'));
    }

    public function testStates()
    {
        $entity = new Entity(['name' => 'test']);
        $this->assertFalse($entity->created());
        $this->assertFalse($entity->deleted());

        $this->assertTrue($entity->created(true));
        $this->assertTrue($entity->deleted(true));
    }

    public function testToJson()
    {
        $data = [
            'title' => 'Article Title',
            'body' => 'Article body',
        ];
        $entity = new Entity($data, ['name' => 'Article']);

        $expected = '{"title":"Article Title","body":"Article body"}';
        $this->assertEquals($expected, $entity->toJson());
    }

    public function testToXml()
    {
        $data = [
            'title' => 'Article Title',
            'body' => 'Article body',
        ];
        $entity = new Entity($data, ['name' => 'Article']);

        $expected = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<article><title>Article Title</title><body>Article body</body></article>\n";
        $this->assertEquals($expected, $entity->toXml());

        $data = [
            'title' => 'Article Title',
            'body' => 'Article body',
        ];
        $entity = new Entity($data);

        $expected = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<record><title>Article Title</title><body>Article body</body></record>\n";
        $this->assertEquals($expected, $entity->toXml());
    }

    public function testAccessor()
    {
        $user = new User();
        $user->first_name = 'Bob';
        $user->last_name = 'Hope';
        $this->assertEquals('Bob Hope', $user->fullName);
    }

    public function testMutator()
    {
        $user = new User();
        $user->first_name = 'BOB';
        $this->assertEquals('Bob', $user->first_name);
    }

    public function testVirtualFields()
    {
        $user = new User();
        $user->first_name = 'bob';
        $user->last_name = 'Hope';
        $user->virtual(['full_name']);
        $array = $user->toArray();
        $this->assertEquals('Bob Hope', $array['full_name']);
    }

    public function testHidden()
    {
        $user = new User();
        $user->first_name = 'bob';
        $user->last_name = 'Hope';
        $user->password = 'secret';

        $array = $user->toArray();
        $this->assertEquals('secret', $array['password']);

        $user->hidden(['password']);
        $array = $user->toArray();
        $this->assertFalse(isset($array['password']));
    }
}
