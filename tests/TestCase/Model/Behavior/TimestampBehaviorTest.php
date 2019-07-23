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

use Origin\Model\Model;
use Origin\TestSuite\OriginTestCase;
use Origin\Model\Behavior\TimestampBehavior;

class TimestampBehaviorTest extends OriginTestCase
{
    public function initialize()
    {
        $this->loadFixture('Origin.Article');
    }
    public function testBeforeSaveCreate()
    {
        $Article = new Model(['name' => 'Article','datasource' => 'test']);
        $behavior = new TimestampBehavior($Article);
        $data = ['title' => 'Foo Bar'];
        $entity = $Article->new($data);
        $timestamp = date('Y-m-d H:i:s');
        $behavior->beforeSave($entity);
        $this->assertEquals($timestamp, $entity->created);
        $this->assertEquals($timestamp, $entity->modified);
    }
    public function testBeforeSaveUpdate()
    {
        $Article = new Model(['name' => 'Article','datasource' => 'test']);
        $behavior = new TimestampBehavior($Article);
        $data = ['title' => 'Foo Bar','created' => '2019-03-02 20:00:00','modified' => '2019-03-02 20:00:00'];
        $entity = $Article->new($data);
    
        $timestamp = date('Y-m-d H:i:s');
        $behavior->beforeSave($entity);
        $this->assertEquals('2019-03-02 20:00:00', $entity->created);
        $this->assertEquals($timestamp, $entity->modified);

        $data = ['title' => 'Foo Bar'];
        $entity = $Article->new($data);
        $timestamp = date('Y-m-d H:i:s');
        $behavior->beforeSave($entity);
        $this->assertEquals($timestamp, $entity->modified);
    }
}
