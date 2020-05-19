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

namespace Origin\Test\Model\Concern;

use Origin\Model\Model;
use Origin\Model\Entity;
use Origin\Model\ModelRegistry;
use Origin\TestSuite\OriginTestCase;
use Origin\Model\Concern\Timestampable;

class AnotherArticle extends Model
{
    use Timestampable;
    protected $table = 'articles';
}

class TimestampableTest extends OriginTestCase
{
    protected $fixtures = ['Origin.Article'];

    protected function setUp(): void
    {
        $this->AnotherArticle = ModelRegistry::get('AnotherArticle', ['className' => AnotherArticle::class]);
    }
    public function testCreated()
    {
        $data = ['title' => 'Foo Bar'];
        $entity = $this->AnotherArticle->new($data);
        $timestamp = date('Y-m-d H:i:s');
        $this->assertTrue($this->AnotherArticle->save($entity));
        $this->assertEquals($timestamp, $entity->created);
        $this->assertEquals($timestamp, $entity->modified);
    }

    public function testCreatedWithData()
    {
        $data = ['title' => 'Foo Bar','created' => '2019-03-02 20:00:00','modified' => '2019-03-02 20:00:00'];
        $entity = $this->AnotherArticle->new($data);
        $timestamp = date('Y-m-d H:i:s');
        $this->assertTrue($this->AnotherArticle->save($entity));
        $this->assertEquals('2019-03-02 20:00:00', $entity->created);
        $this->assertEquals('2019-03-02 20:00:00', $entity->modified);
    }

    public function testUpdate()
    {
        // Create the entity
        $data = ['title' => 'Foo Bar'];
        $entity = $this->AnotherArticle->new($data);
        $this->assertTrue($this->AnotherArticle->save($entity));
        $this->assertTrue($this->AnotherArticle->updateColumn($entity->id, 'created', '2019-03-02 20:00:00'));
        $entity = $this->AnotherArticle->get($entity->id);

        $entity->title = 'foo';
        $timestamp = date('Y-m-d H:i:s');
        $this->assertTrue($this->AnotherArticle->save($entity));
        $this->assertEquals('2019-03-02 20:00:00', $entity->created);
        $this->assertEquals($timestamp, $entity->modified);
    }

    public function testUpdateWithData()
    {
        // Create the entity
        $data = ['title' => 'Foo Bar'];
        $entity = $this->AnotherArticle->new($data);
        $this->assertTrue($this->AnotherArticle->save($entity));
        $entity = $this->AnotherArticle->get($entity->id);

        // modifiy data
        $entity->title = 'foo';
        $entity->created = '2019-03-02 20:00:00';
        $entity->modified = '2019-03-02 20:00:00';
        
        $this->assertTrue($this->AnotherArticle->save($entity));
        $this->assertEquals('2019-03-02 20:00:00', $entity->created);
        $this->assertEquals('2019-03-02 20:00:00', $entity->modified);
    }
}
