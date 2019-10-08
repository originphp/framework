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

namespace Origin\Test\Model\Concern;

use Origin\Model\Model;
use Origin\TestSuite\OriginTestCase;
use ArrayObject;
use Origin\Model\Concern\Timestampable;
use Origin\Model\ModelRegistry;
use Origin\Model\Entity;

class AnotherArticle extends Model
{
    use Timestampable;
    protected $table = 'articles';

    public function runCallback(Entity $entity)
    {
        $this->timestambleBeforeSave($entity, new ArrayObject);
    }
}

class TimestampableTest extends OriginTestCase
{
    protected $fixtures = ['Origin.Article'];

    public function startup() : void
    {
        $this->AnotherArticle = ModelRegistry::get('AnotherArticle', ['className'=>AnotherArticle::class]);
    }
    public function testCreated()
    {
        $data = ['title' => 'Foo Bar'];
        $entity = $this->AnotherArticle->new($data);
        $timestamp = date('Y-m-d H:i:s');
        $this->AnotherArticle->runCallback($entity);
        $this->assertEquals($timestamp, $entity->created);
        $this->assertEquals($timestamp, $entity->modified);
    }

    public function testCreatedWithData()
    {
        $data = ['title' => 'Foo Bar','created' => '2019-03-02 20:00:00','modified' => '2019-03-02 20:00:00'];
        $entity = $this->AnotherArticle->new($data);
        $timestamp = date('Y-m-d H:i:s');
        $this->AnotherArticle->runCallback($entity);
        $this->assertEquals('2019-03-02 20:00:00', $entity->created);
        $this->assertEquals('2019-03-02 20:00:00', $entity->modified);
    }

    public function testUpdate()
    {
        $data = ['id'=>12345,'title' => 'Foo Bar','created' => '2019-03-02 20:00:00'];
        $entity = $this->AnotherArticle->new($data);
        $entity->reset();
        $entity->title = 'foo';
        $timestamp = date('Y-m-d H:i:s');
        $this->AnotherArticle->runCallback($entity);
        $this->assertEquals('2019-03-02 20:00:00', $entity->created);
        $this->assertEquals($timestamp, $entity->modified);
    }

    public function testUpdateWithData()
    {
        $data = ['id'=>'foo','title' => 'Foo Bar','created' => '2019-03-02 20:00:00','modified' => '2019-03-02 20:00:00'];
        $entity = $this->AnotherArticle->new($data);
        $timestamp = date('Y-m-d H:i:s');
        $this->AnotherArticle->runCallback($entity);
        $this->assertEquals('2019-03-02 20:00:00', $entity->created);
        $this->assertEquals('2019-03-02 20:00:00', $entity->modified);
    }
}
